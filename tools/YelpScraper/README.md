# Yelp Review Scraper

A standalone .NET 10 console tool that uses [Microsoft.Playwright](https://playwright.dev/dotnet/)
to drive a real Chromium browser, load a Yelp business page, run Yelp's
client-side JavaScript, paginate through every review page, and export
**Name, Location, Rating, Date, Review Text** to a CSV file.

A plain HTTP request does not work because Yelp returns `403 Forbidden` to
non-browser clients and renders the review list with JavaScript after the page
loads. A real browser engine is required.

> ⚠️ **Terms of Service**: Automated scraping is against Yelp's Terms of Service.
> This tool is intended for a business owner gathering reviews from their **own**
> listing. The official, ToS-compliant alternative is the Yelp Fusion API, which
> returns at most three review excerpts per business (already integrated in the
> main app via `ServerYelpReviewProvider`).

## One-time setup

```powershell
cd "C:\Users\tpeas\Business Tools\tools\YelpScraper"
dotnet build
# Install the Chromium browser binaries Playwright drives:
pwsh bin\Debug\net10.0\playwright.ps1 install chromium
```

## Run

```powershell
# Default: New Era listing, visible browser, CSV to New Era\Yelp Reviews\reviews.csv
dotnet run

# Point at any business by URL or slug, change the output file:
dotnet run -- --url "https://www.yelp.com/biz/new-era-steam-cleaning-and-restoration-sugar-land" -o "C:\path\reviews.csv"

# Run without a visible window (only works if Yelp doesn't challenge):
dotnet run -- --headless
```

## Options

| Option | Description | Default |
| --- | --- | --- |
| `--slug <slug>` | Yelp business slug (the part after `/biz/`). | New Era slug |
| `--url <url>` | Full Yelp business URL (slug is extracted from it). | – |
| `--output`, `-o <path>` | CSV output path. | `New Era\Yelp Reviews\reviews.csv` |
| `--headless` | Run Chromium without a visible window. | off (visible) |
| `--max-pages <n>` | Maximum review pages to walk (10 reviews/page). | 25 |
| `--delay <ms>` | Delay between pages, in milliseconds. | 1200 |

## Notes

- Run **visible** (the default) the first time. If Yelp shows a CAPTCHA, solve it
  in the window and press **ENTER** in the console to continue.
- Yelp uses hashed CSS class names that change over time. Extraction is anchored
  on stable attributes (`a[href^="/user_details"]`, `aria-label="N star rating"`),
  but selectors in `Program.cs` may still need an occasional tweak.
- The reviewer "location" field is sometimes absent on Yelp; it is left blank when
  not present.
