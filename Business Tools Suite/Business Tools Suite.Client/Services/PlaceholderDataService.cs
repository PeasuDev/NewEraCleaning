using Business_Tools_Suite.Client.Models;

namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Supplies and stores placeholder data for the dashboard until the real
/// review-scraping and quote-request submission features are wired in. The
/// service holds in-memory lists so quote requests, clients, and the
/// "new requests" ticker stay consistent as the user moves between pages.
/// </summary>
public class PlaceholderDataService
{
    private readonly List<Review> _reviews;
    private readonly List<QuoteRequest> _quoteRequests;
    private readonly List<ClientProfile> _clients;

    public PlaceholderDataService()
    {
        _reviews = SeedReviews();
        _quoteRequests = SeedQuoteRequests();
        _clients = SeedClients(_quoteRequests);
    }

    /// <summary>
    /// Raised whenever quote requests or clients change so pages can refresh.
    /// </summary>
    public event Action? Changed;

    /// <summary>
    /// Simulated reviews gathered from external sources, newest first.
    /// </summary>
    public IReadOnlyList<Review> GetReviews() =>
        _reviews.OrderByDescending(r => r.Date).ToList();

    /// <summary>
    /// Quote requests submitted through the public form, newest first.
    /// </summary>
    public IReadOnlyList<QuoteRequest> GetQuoteRequests() =>
        _quoteRequests.OrderByDescending(q => q.SubmittedOn).ToList();

    /// <summary>
    /// Clients of the business, newest first.
    /// </summary>
    public IReadOnlyList<ClientProfile> GetClients() =>
        _clients.OrderByDescending(c => c.CreatedOn).ToList();

    /// <summary>
    /// Finds a single client by their email address, which acts as the unique
    /// key for a client record. Returns <c>null</c> if no match is found.
    /// </summary>
    public ClientProfile? GetClientByEmail(string email) =>
        _clients.FirstOrDefault(c =>
            string.Equals(c.Email, email, StringComparison.OrdinalIgnoreCase));

    /// <summary>
    /// Reviews believed to have been posted by the given client, matched on the
    /// author's name. Newest first.
    /// </summary>
    public IReadOnlyList<Review> GetReviewsForClient(ClientProfile client) =>
        _reviews
            .Where(r => IsLikelyAuthor(r.Author, client.Name))
            .OrderByDescending(r => r.Date)
            .ToList();

    /// <summary>
    /// Decides whether a review author name likely refers to the given client.
    /// Matches an exact name, or a shortened "First L." author against a
    /// "First Last" client name.
    /// </summary>
    private static bool IsLikelyAuthor(string reviewAuthor, string clientName)
    {
        if (string.Equals(reviewAuthor, clientName, StringComparison.OrdinalIgnoreCase))
        {
            return true;
        }

        var authorParts = reviewAuthor.Split(' ', StringSplitOptions.RemoveEmptyEntries);
        var clientParts = clientName.Split(' ', StringSplitOptions.RemoveEmptyEntries);
        if (authorParts.Length < 2 || clientParts.Length < 2)
        {
            return false;
        }

        var sameFirstName = string.Equals(authorParts[0], clientParts[0], StringComparison.OrdinalIgnoreCase);
        var authorLastInitial = char.ToUpperInvariant(authorParts[^1][0]);
        var clientLastInitial = char.ToUpperInvariant(clientParts[^1][0]);
        return sameFirstName && authorLastInitial == clientLastInitial;
    }

    /// <summary>
    /// Quote requests submitted by the given client that are still outstanding —
    /// i.e. not yet <see cref="QuoteRequestStatus.Won"/> or
    /// <see cref="QuoteRequestStatus.Lost"/>. Matched on email (the unique key),
    /// newest first.
    /// </summary>
    public IReadOnlyList<QuoteRequest> GetOutstandingQuoteRequestsForClient(ClientProfile client) =>
        _quoteRequests
            .Where(q => string.Equals(q.Email, client.Email, StringComparison.OrdinalIgnoreCase))
            .Where(q => q.Status is not (QuoteRequestStatus.Won or QuoteRequestStatus.Lost))
            .OrderByDescending(q => q.SubmittedOn)
            .ToList();

    /// <summary>
    /// Number of quote requests still in the <see cref="QuoteRequestStatus.New"/>
    /// state, used to drive the "new requests" ticker.
    /// </summary>
    public int NewQuoteRequestCount =>
        _quoteRequests.Count(q => q.Status == QuoteRequestStatus.New);

    /// <summary>
    /// Adds a quote request and automatically creates a matching client record.
    /// </summary>
    public void AddQuoteRequest(QuoteRequest request)
    {
        _quoteRequests.Add(request);

        var alreadyClient = _clients.Any(c =>
            string.Equals(c.Email, request.Email, StringComparison.OrdinalIgnoreCase));

        if (!alreadyClient)
        {
            _clients.Add(new ClientProfile(
                request.Name,
                request.Email,
                request.Phone,
                request.ServiceType,
                request.SubmittedOn,
                ClientSource.QuoteRequest));
        }

        Changed?.Invoke();
    }

    /// <summary>
    /// Adds a manually created client.
    /// </summary>
    public void AddClient(ClientProfile client)
    {
        _clients.Add(client);
        Changed?.Invoke();
    }

    /// <summary>
    /// Removes a client identified by email (the unique key). Returns <c>true</c>
    /// if a matching client was found and removed. Quote requests and reviews are
    /// left untouched.
    /// </summary>
    public bool DeleteClient(string email)
    {
        var removed = _clients.RemoveAll(c =>
            string.Equals(c.Email, email, StringComparison.OrdinalIgnoreCase));

        if (removed > 0)
        {
            Changed?.Invoke();
        }

        return removed > 0;
    }

    private static List<Review> SeedReviews() => new()
    {
        new("Jordan M.", "Google", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-2)),
            "New Era Cleaning did an amazing job on our move-out deep clean. Spotless from top to bottom and the team was on time and professional."),
        new("Priya S.", "Yelp", 4, DateOnly.FromDateTime(DateTime.Now.AddDays(-6)),
            "Great recurring service for our office. The only reason I'm not giving five stars is they missed the break room baseboards once — fixed it right away though."),
        new("Marcus T.", "Facebook", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-11)),
            "Booked a post-construction cleanup and they handled all the dust and debris perfectly. Highly recommend for anyone renovating."),
        new("Elena R.", "Google", 3, DateOnly.FromDateTime(DateTime.Now.AddDays(-15)),
            "Decent house cleaning but a little pricey compared to others nearby. The carpets did come out looking great though."),
        new("Derek W.", "Angi", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-21)),
            "Best cleaning crew we've hired, hands down. They went above and beyond on our kitchen and bathrooms. Booking again next month."),
        new("Aisha K.", "Google", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-28)),
            "Reliable, friendly, and thorough. New Era has been cleaning our home biweekly for months and it's always done right."),
        new("Tom B.", "Yelp", 4, DateOnly.FromDateTime(DateTime.Now.AddDays(-34)),
            "Solid commercial cleaning for our storefront. Consistent results and easy to schedule. Would like a little earlier availability."),
        new("Nina P.", "Facebook", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-41)),
            "They tackled years of grime in our rental and made it look brand new. Worth every penny — thank you New Era Cleaning!"),
        new("The Whitlock Family", "Google", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-9)),
            "We've had New Era cleaning our home biweekly for months now. Always thorough, always on time, and so easy to work with."),
        new("Megan C.", "Yelp", 4, DateOnly.FromDateTime(DateTime.Now.AddDays(-12)),
            "Great with our two dogs and very flexible on scheduling. House always looks fantastic after they visit."),
        new("Daniel O.", "Facebook", 5, DateOnly.FromDateTime(DateTime.Now.AddDays(-18)),
            "Handled the post-remodel dust in our kitchen perfectly. Not a speck left behind. Would absolutely book again.")
    };

    private static List<QuoteRequest> SeedQuoteRequests() => new()
    {
        new("Sarah Whitfield", "sarah.w@example.com", "(555) 014-2298", "Move-Out Deep Clean",
            "Need a full move-out clean for a 3-bed, 2-bath townhouse before the 30th. Carpets included if possible.",
            DateTime.Now.AddHours(-3), QuoteRequestStatus.New),
        new("Greenfield Dental", "office@greenfielddental.example", "(555) 087-6610", "Recurring Commercial",
            "Looking for weekly evening cleaning for a 6-room dental office. Please send pricing for a 12-month agreement.",
            DateTime.Now.AddHours(-9), QuoteRequestStatus.New),
        new("Daniel Ortiz", "d.ortiz@example.com", "(555) 233-7741", "Post-Construction",
            "Just finished a kitchen remodel and need the dust handled. Roughly 400 sq ft affected.",
            DateTime.Now.AddDays(-1).AddHours(-2), QuoteRequestStatus.Contacted),
        new("Megan Cho", "megan.cho@example.com", "(555) 909-1203", "Recurring Residential",
            "Interested in biweekly house cleaning for a 4-bedroom home. Two friendly dogs on site.",
            DateTime.Now.AddDays(-2).AddHours(-5), QuoteRequestStatus.Quoted),
        new("Harbor Point Realty", "leasing@harborpoint.example", "(555) 661-8890", "Turnover Cleaning",
            "We manage 12 units and need reliable turnover cleaning between tenants. Volume pricing?",
            DateTime.Now.AddDays(-3).AddHours(-1), QuoteRequestStatus.New),
        new("Anthony Russo", "a.russo@example.com", "(555) 445-2017", "One-Time Deep Clean",
            "Spring deep clean for a 2-bedroom condo, including inside oven and fridge.",
            DateTime.Now.AddDays(-4).AddHours(-6), QuoteRequestStatus.Won)
    };

    private static List<ClientProfile> SeedClients(IEnumerable<QuoteRequest> quoteRequests)
    {
        var clients = new List<ClientProfile>
        {
            new("Westside Yoga Studio", "hello@westsideyoga.example", "(555) 332-1180", "Recurring Commercial",
                DateTime.Now.AddDays(-120), ClientSource.Manual, "Mats and floors are a priority. Closed Mondays."),
            new("The Whitlock Family", "whitlock.home@example.com", "(555) 778-9043", "Recurring Residential",
                DateTime.Now.AddDays(-78), ClientSource.Manual, "Gate code on file. Biweekly Thursdays.")
        };

        foreach (var request in quoteRequests.Where(q =>
                     q.Status is QuoteRequestStatus.Contacted
                         or QuoteRequestStatus.Quoted
                         or QuoteRequestStatus.Won))
        {
            clients.Add(new ClientProfile(
                request.Name,
                request.Email,
                request.Phone,
                request.ServiceType,
                request.SubmittedOn,
                ClientSource.QuoteRequest));
        }

        return clients;
    }
}
