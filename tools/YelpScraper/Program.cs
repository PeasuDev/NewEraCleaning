using System.Globalization;
using System.Text;
using System.Text.Json;
using Microsoft.Playwright;

namespace YelpScraper;

/// <summary>
/// Standalone console scraper that drives a real Chromium browser (via Playwright)
/// to load a Yelp business page, execute Yelp's client-side JavaScript, paginate
/// through every review page, and write Name, Location, Rating and Review Text to
/// a CSV file.
///
/// A plain HTTP request cannot be used because Yelp (a) returns 403 to non-browser
/// clients and (b) renders the review list with JavaScript after page load.
///
/// NOTE: Automated scraping is against Yelp's Terms of Service. This tool is
/// intended for a business owner collecting reviews from their OWN listing.
/// </summary>
internal static class Program
{
    private const string DefaultSlug = "new-era-steam-cleaning-and-restoration-sugar-land";
    private const string DefaultOutput =
        @"C:\Users\tpeas\Business Tools\New Era\Yelp Reviews\reviews.csv";

    private static async Task<int> Main(string[] args)
    {
        var options = ScraperOptions.Parse(args);

        Console.WriteLine("Yelp review scraper");
        Console.WriteLine($"  Business : {options.Slug}");
        Console.WriteLine($"  Output   : {options.OutputPath}");
        Console.WriteLine($"  Headless : {options.Headless}");
        Console.WriteLine($"  MaxPages : {options.MaxPages}");
        Console.WriteLine();

        using var playwright = await Playwright.CreateAsync();

        IBrowserContext context;
        IAsyncDisposable? toDispose = null;

        if (options.ConnectPort > 0)
        {
            // ATTACH MODE: connect to a Chrome the user already opened (and already
            // solved any CAPTCHA in) via the DevTools protocol. DataDome trusts the
            // user's real browser session, so this avoids the bot challenge entirely.
            //
            // Start Chrome first, e.g.:
            //   & "C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222 --user-data-dir="C:\YelpProfile"
            // then browse to the Yelp page and solve the CAPTCHA if shown.
            var endpoint = $"http://localhost:{options.ConnectPort}";
            Console.WriteLine($"  Attaching to Chrome at {endpoint} ...");

            var browser = await playwright.Chromium.ConnectOverCDPAsync(endpoint);
            toDispose = browser;
            context = browser.Contexts.Count > 0
                ? browser.Contexts[0]
                : await browser.NewContextAsync();
        }
        else
        {
            // PERSISTENT MODE: launch a Playwright-controlled Chromium with an on-disk
            // profile so a solved challenge is reused on later runs.
            var profileDir = Path.Combine(
                Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
                "YelpScraper", "profile");
            Directory.CreateDirectory(profileDir);

            var persistent = await playwright.Chromium.LaunchPersistentContextAsync(
                profileDir,
                new BrowserTypeLaunchPersistentContextOptions
                {
                    Headless = options.Headless,
                    Args = new[] { "--disable-blink-features=AutomationControlled" },
                    UserAgent =
                        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 " +
                        "(KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36",
                    ViewportSize = new ViewportSize { Width = 1366, Height = 900 },
                    Locale = "en-US"
                });
            context = persistent;
            toDispose = persistent;
        }

        var page = context.Pages.Count > 0 ? context.Pages[0] : await context.NewPageAsync();

        var collected = new List<ReviewRow>();
        var seen = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        for (var pageIndex = 0; pageIndex < options.MaxPages; pageIndex++)
        {
            var start = pageIndex * 10;
            var url = $"https://www.yelp.com/biz/{options.Slug}?start={start}";
            Console.WriteLine($"[page {pageIndex + 1}] {url}");

            try
            {
                await page.GotoAsync(url, new PageGotoOptions
                {
                    WaitUntil = WaitUntilState.DOMContentLoaded,
                    Timeout = 45_000
                });
            }
            catch (PlaywrightException ex)
            {
                Console.WriteLine($"  navigation failed: {ex.Message}");
                break;
            }

            if (await IsBlockedAsync(page))
            {
                Console.WriteLine("  Yelp presented a DataDome bot challenge / CAPTCHA.");
                if (options.Headless)
                {
                    Console.WriteLine("  Re-run WITHOUT --headless and solve the challenge in the window.");
                    break;
                }

                Console.WriteLine("  Solve the CAPTCHA in the browser window now.");
                Console.WriteLine("  Waiting for the challenge to clear (up to 3 minutes)...");

                if (!await WaitUntilUnblockedAsync(page, TimeSpan.FromMinutes(3)))
                {
                    Console.WriteLine("  Challenge was not cleared in time. Stopping.");
                    break;
                }

                Console.WriteLine("  Challenge cleared - continuing. (Session is saved for next time.)");
            }

            // Give the review list time to hydrate, then nudge lazy content into view.
            await WaitForReviewsAsync(page);

            var rows = await ExtractReviewsAsync(page);
            Console.WriteLine($"  extracted {rows.Count} review(s)");

            if (rows.Count == 0)
            {
                // First page with nothing usually means the selectors need updating
                // (or a challenge). Dump diagnostics so we can inspect the DOM.
                if (pageIndex == 0)
                {
                    await DumpDiagnosticsAsync(page, options.OutputPath, pageIndex);
                }

                // No more reviews (past the last page) or selectors need updating.
                break;
            }

            var added = 0;
            foreach (var row in rows)
            {
                var key = $"{row.Name}|{row.Text}";
                if (seen.Add(key))
                {
                    collected.Add(row);
                    added++;
                }
            }

            // If a page only repeats reviews we've already captured, we've reached the end.
            if (added == 0)
            {
                Console.WriteLine("  no new reviews on this page; stopping.");
                break;
            }

            await page.WaitForTimeoutAsync(options.DelayMs);
        }

        Console.WriteLine();
        Console.WriteLine($"Total unique reviews: {collected.Count}");

        var exitCode = 0;
        if (collected.Count > 0)
        {
            WriteCsv(options.OutputPath, collected);
            Console.WriteLine($"Saved CSV -> {options.OutputPath}");
        }
        else
        {
            Console.WriteLine("No reviews extracted.");
            Console.WriteLine("Tip: open the page in Chrome (--remote-debugging-port), solve the CAPTCHA, then re-run with --connect <port>.");
            exitCode = 1;
        }

        // In attach mode, leave the user's Chrome open; only dispose what we own when
        // we launched the persistent context ourselves.
        if (options.ConnectPort == 0 && toDispose is not null)
        {
            await toDispose.DisposeAsync();
        }

        return exitCode;
    }

    private static async Task<bool> IsBlockedAsync(IPage page)
    {
        // DataDome serves a tiny interstitial whose body embeds the challenge iframe.
        // The page can be mid-navigation while the user solves it, so guard every
        // call - a transient PlaywrightException must not crash the run.
        try
        {
            var html = await page.ContentAsync();
            if (html.Contains("captcha-delivery.com", StringComparison.OrdinalIgnoreCase) ||
                html.Contains("DataDome", StringComparison.OrdinalIgnoreCase) ||
                html.Contains("geo.captcha", StringComparison.OrdinalIgnoreCase))
            {
                return true;
            }

            var title = await page.TitleAsync();
            if (title.Contains("challenge", StringComparison.OrdinalIgnoreCase) ||
                title.Contains("robot", StringComparison.OrdinalIgnoreCase) ||
                title.Contains("blocked", StringComparison.OrdinalIgnoreCase))
            {
                return true;
            }

            var body = await page.InnerTextAsync("body");
            return body.Contains("Are you a human", StringComparison.OrdinalIgnoreCase) ||
                   body.Contains("complete the bot challenge", StringComparison.OrdinalIgnoreCase);
        }
        catch (Exception)
        {
            // Navigation in flight; treat as "still blocked" so the caller keeps waiting.
            return true;
        }
    }

    /// <summary>
    /// Polls until the DataDome challenge is gone (the real Yelp page has loaded) or
    /// the timeout elapses. The user solves the CAPTCHA in the visible window.
    /// </summary>
    private static async Task<bool> WaitUntilUnblockedAsync(IPage page, TimeSpan timeout)
    {
        var deadline = DateTime.UtcNow + timeout;
        while (DateTime.UtcNow < deadline)
        {
            await page.WaitForTimeoutAsync(2000);

            if (!await IsBlockedAsync(page))
            {
                // Real review content present?
                var hasReviews = await page.EvaluateAsync<bool>(
                    "() => !!document.querySelector(\"a[href^='/user_details']\")");
                if (hasReviews)
                {
                    return true;
                }
            }
        }

        return false;
    }

    private static async Task WaitForReviewsAsync(IPage page)
    {
        // The recommended-reviews list renders <li> items each containing a user link.
        // WaitForSelectorAsync throws TimeoutException (not PlaywrightException) on
        // timeout, so catch the broad Exception and fall back to extraction heuristics.
        try
        {
            await page.WaitForSelectorAsync(
                "a[href^='/user_details']",
                new PageWaitForSelectorOptions { Timeout = 15_000, State = WaitForSelectorState.Attached });
        }
        catch (Exception)
        {
            // Fall through; extraction has its own fallbacks.
        }

        // Nudge lazy content into view by scrolling the page in steps.
        for (var i = 0; i < 4; i++)
        {
            await page.EvaluateAsync("() => window.scrollBy(0, document.body.scrollHeight / 4)");
            await page.WaitForTimeoutAsync(600);
        }
        await page.WaitForTimeoutAsync(1200);
    }

    /// <summary>
    /// Writes a screenshot and the rendered HTML next to the CSV so selector issues
    /// can be diagnosed when a page yields no reviews.
    /// </summary>
    private static async Task DumpDiagnosticsAsync(IPage page, string outputPath, int pageIndex)
    {
        try
        {
            var dir = Path.GetDirectoryName(outputPath) ?? ".";
            Directory.CreateDirectory(dir);

            var shot = Path.Combine(dir, $"_debug_page{pageIndex + 1}.png");
            await page.ScreenshotAsync(new PageScreenshotOptions { Path = shot, FullPage = true });

            var html = await page.ContentAsync();
            var htmlPath = Path.Combine(dir, $"_debug_page{pageIndex + 1}.html");
            await File.WriteAllTextAsync(htmlPath, html);

            Console.WriteLine($"  diagnostics written: {shot}");
        }
        catch (Exception ex)
        {
            Console.WriteLine($"  (could not write diagnostics: {ex.Message})");
        }
    }

    /// <summary>
    /// Walks the rendered DOM inside the browser and returns one record per review.
    /// Uses stable anchors (user_details links, star-rating aria-labels) rather than
    /// Yelp's hashed CSS class names.
    /// </summary>
    private static async Task<List<ReviewRow>> ExtractReviewsAsync(IPage page)
    {
        const string script = """
        () => {
            const results = [];

            // A review block is the closest <li> that contains both a reviewer link
            // and a star-rating element. Collect those <li> elements uniquely.
            const userLinks = Array.from(document.querySelectorAll("a[href^='/user_details']"));
            const blocks = new Set();
            for (const link of userLinks) {
                const li = link.closest("li");
                if (li && li.querySelector("[role='img'][aria-label*='star rating']")) {
                    blocks.add(li);
                }
            }

            const cityState = /^[A-Za-z .'-]+,\s*[A-Z]{2}$/;

            for (const li of blocks) {
                const link = li.querySelector("a[href^='/user_details']");
                const name = link ? link.textContent.trim() : "";

                const ratingEl = li.querySelector("[role='img'][aria-label*='star rating']");
                let rating = 0;
                if (ratingEl) {
                    const m = ratingEl.getAttribute("aria-label").match(/([0-9.]+)\s*star/i);
                    if (m) rating = parseFloat(m[1]);
                }

                // Review body: the longest <p> within the block is the comment.
                let text = "";
                for (const p of li.querySelectorAll("p")) {
                    const t = p.textContent.trim();
                    if (t.length > text.length) text = t;
                }

                // Location: a short span matching "City, ST" near the reviewer name.
                let location = "";
                for (const span of li.querySelectorAll("span")) {
                    const t = span.textContent.trim();
                    if (cityState.test(t)) { location = t; break; }
                }

                // Date: first text that looks like a US date (M/D/YYYY or "Mon D, YYYY").
                let date = "";
                const dateMatch = li.textContent.match(
                    /([A-Z][a-z]{2,8}\s+\d{1,2},\s*\d{4})|(\d{1,2}\/\d{1,2}\/\d{4})/);
                if (dateMatch) date = dateMatch[0];

                if (name && text) {
                    results.push({ name, location, rating, text, date });
                }
            }

            return results;
        }
        """;

        var json = await page.EvaluateAsync<JsonElement>(script);

        var rows = new List<ReviewRow>();
        foreach (var item in json.EnumerateArray())
        {
            rows.Add(new ReviewRow(
                Name: item.GetProperty("name").GetString() ?? string.Empty,
                Location: item.GetProperty("location").GetString() ?? string.Empty,
                Rating: item.GetProperty("rating").GetDouble(),
                Text: item.GetProperty("text").GetString() ?? string.Empty,
                Date: item.GetProperty("date").GetString() ?? string.Empty));
        }

        return rows;
    }

    private static void WriteCsv(string path, IReadOnlyList<ReviewRow> rows)
    {
        var dir = Path.GetDirectoryName(path);
        if (!string.IsNullOrEmpty(dir))
        {
            Directory.CreateDirectory(dir);
        }

        var sb = new StringBuilder();
        sb.AppendLine("Name,Location,Rating,Date,ReviewText");
        foreach (var row in rows)
        {
            sb.Append(Csv(row.Name)).Append(',')
              .Append(Csv(row.Location)).Append(',')
              .Append(row.Rating.ToString("0.#", CultureInfo.InvariantCulture)).Append(',')
              .Append(Csv(row.Date)).Append(',')
              .Append(Csv(row.Text)).Append('\n');
        }

        File.WriteAllText(path, sb.ToString(), new UTF8Encoding(encoderShouldEmitUTF8Identifier: true));
    }

    private static string Csv(string value)
    {
        value ??= string.Empty;
        var needsQuotes = value.Contains(',') || value.Contains('"') ||
                          value.Contains('\n') || value.Contains('\r');
        value = value.Replace("\"", "\"\"");
        return needsQuotes ? $"\"{value}\"" : value;
    }

    private sealed record ReviewRow(string Name, string Location, double Rating, string Text, string Date);

    private sealed class ScraperOptions
    {
        public string Slug { get; private set; } = DefaultSlug;
        public string OutputPath { get; private set; } = DefaultOutput;
        public bool Headless { get; private set; }
        public int MaxPages { get; private set; } = 25;
        public int DelayMs { get; private set; } = 1200;
        public int ConnectPort { get; private set; }

        public static ScraperOptions Parse(string[] args)
        {
            var options = new ScraperOptions();

            for (var i = 0; i < args.Length; i++)
            {
                switch (args[i].ToLowerInvariant())
                {
                    case "--slug" when i + 1 < args.Length:
                        options.Slug = args[++i];
                        break;
                    case "--url" when i + 1 < args.Length:
                        options.Slug = SlugFromUrl(args[++i]);
                        break;
                    case "--output" or "-o" when i + 1 < args.Length:
                        options.OutputPath = args[++i];
                        break;
                    case "--headless":
                        options.Headless = true;
                        break;
                    case "--max-pages" when i + 1 < args.Length:
                        if (int.TryParse(args[++i], out var mp) && mp > 0)
                        {
                            options.MaxPages = mp;
                        }
                        break;
                    case "--delay" when i + 1 < args.Length:
                        if (int.TryParse(args[++i], out var d) && d >= 0)
                        {
                            options.DelayMs = d;
                        }
                        break;
                    case "--connect" or "--port" when i + 1 < args.Length:
                        if (int.TryParse(args[++i], out var port) && port > 0)
                        {
                            options.ConnectPort = port;
                        }
                        break;
                }
            }

            return options;
        }

        private static string SlugFromUrl(string url)
        {
            // Accept a full Yelp URL and pull out the /biz/<slug> segment.
            var marker = "/biz/";
            var idx = url.IndexOf(marker, StringComparison.OrdinalIgnoreCase);
            if (idx < 0)
            {
                return url;
            }

            var rest = url[(idx + marker.Length)..];
            var end = rest.IndexOfAny(new[] { '?', '/', '#' });
            return end < 0 ? rest : rest[..end];
        }
    }
}
