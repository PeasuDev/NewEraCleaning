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
    /// Customer reviews gathered from external sources (Yelp and Angi), newest first.
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

    /// <summary>
    /// Updates the private internal notes for a client identified by email (the
    /// unique key). These notes are for the owner only and are never shown to
    /// the client. Returns <c>true</c> if a matching client was found and updated.
    /// </summary>
    public bool UpdateClientNotes(string email, string? notes)
    {
        var index = _clients.FindIndex(c =>
            string.Equals(c.Email, email, StringComparison.OrdinalIgnoreCase));

        if (index < 0)
        {
            return false;
        }

        _clients[index] = _clients[index] with { Notes = notes };
        Changed?.Invoke();
        return true;
    }

    // Real customer reviews extracted from the New Era Yelp and Angi profiles.
    // GetReviews() sorts these newest-first, so seed order is for readability only.
    private static List<Review> SeedReviews() => new()
    {
        // ---- Yelp (new-era-steam-cleaning-and-restoration) ----
        new("Nicole W.", "Yelp", 5, new DateOnly(2026, 4, 30),
            "I needed light colored dining room chairs that had become dingy and a few rugs cleaned, but I was needing to be careful because most of the chairs were a performance crypton fabric with special instructions that only water could be used to clean and it couldn't be too hot. The owner went above and beyond to understand the cleaning code, get the best products to use, and forming the best strategy to balance risk management but improving the state of the chairs. I understood he couldn't guarantee results, but felt that he worked hard and long to ensure the best outcome possible. In the end, we did see a big improvement in the chairs, and while they don't look brand new, it's much better. Communication was also easy throughout the process, and price was very reasonable after I checked with other companies as well. I'd recommend and use him again."),
        new("Ashton S.", "Yelp", 5, new DateOnly(2026, 1, 16),
            "Charles was the utmost in professionalism. My home has 25yo carpets that have seen better days. I had very low expectations on the outcome and Charles used his expertise to give me what I needed. One room was so soiled, he recommended that the carpet be replaced and he didn't charge me for it. I appreciate the clear communication and trustworthiness of his business practices. I'd definitely recommend Charles in the future."),
        new("Willie M.", "Yelp", 5, new DateOnly(2025, 11, 28),
            "We've been using New Era Steam Cleaning & Restoration for years, and Charles never disappoints! With a child and pets, our carpets see plenty of accidents, but every time he comes, they look brand new and smell amazing. Reliable, professional, and always friendly—highly recommend!"),
        new("M P.", "Yelp", 5, new DateOnly(2025, 10, 25),
            "New Era was professional and efficient. They showed up during the agreed arrival window, and did the job thoroughly and quickly. At the end of the job, the owner walked me through the job, and pointed out areas where he had to use extra effort to clean the carpet. I am very happy with the job!"),
        new("Amy N.", "Yelp", 5, new DateOnly(2025, 7, 26),
            "Easy communication. Job well done. Would highly recommend! Shower floor now looks much better!"),
        new("Nicole B.", "Yelp", 5, new DateOnly(2025, 6, 13),
            "Forget the rest. CALL NEW ERA STEAM. He will address all your questions. Clear communication. Competitive prices. Made everything I needed easy and fast. Thank you so very much. I wasn't sure what I needed for moving out of my house and it came out better than expected. Everyone was happy."),
        new("Brenda W.", "Yelp", 5, new DateOnly(2025, 5, 6),
            "Charles did an excellent job! He was on time and got the job done fast. We will use his service again! The carpet looked and smelled great."),
        new("Hookem H.", "Yelp", 5, new DateOnly(2025, 1, 6),
            "Selected Charles based on his large number of 5-star reviews. He communicated quickly and easily and came precisely at the scheduled time. He was quick and efficient and it is easy to see why he has so many 5-star reviews. He made our 33+ year old carpet look great and our house feels fresh and clean. He has our recommendation and we will use him again."),
        new("Daneille T.", "Yelp", 5, new DateOnly(2024, 10, 28),
            "New era was quick to respond to my request. I'm so grateful he was able to get our grout cleaned and carpets shampooed before the movers came. The before and after visually was a world of difference but the smell was even better. The previous owners had pets, so there was a heavy smell of dander in our home. I'm very happy with this service and would recommend him to anyone who needs cleaning and restoration. Lastly he was very nice and educated us on cleaning materials we use that may have been causing more wear on our tile."),
        new("Richard W.", "Yelp", 5, new DateOnly(2024, 5, 10),
            "Great job, honest and communicative throughout the project. We felt like we got what we wanted and a fair price. Highly recommend this company."),

        // ---- Angi (new-era-steam-cleaning-and-restoration) ----
        new("Paul T.", "Angi", 5, new DateOnly(2024, 3, 17),
            "Charles was excellent! He came, worked hard all day, and left me with a very clean house. The price was good and I am very pleased with the results."),
        new("Kedar K.", "Angi", 4, new DateOnly(2019, 8, 16),
            "The service overall with them was good. Scheduling was easy."),
        new("Susan C.", "Angi", 5, new DateOnly(2017, 9, 15),
            "Great — loved them!"),
        new("Kyle P.", "Angi", 5, new DateOnly(2017, 6, 6),
            "Charles came out and did an AMAZING job, he was early to the appointment and was very informative on ways to clean and maintain my carpets. Overall a fantastic job done by New Era and wouldn't recommend any company for the job other than them. Thanks, and we will be seeing you again!"),
        new("Samantha H.", "Angi", 5, new DateOnly(2017, 3, 20),
            "It went great. My previously gray carpet is now white. They were able to get stains out that I wasn't able to. I would highly recommend them."),
        new("Toby P.", "Angi", 5, new DateOnly(2017, 3, 7),
            "The technician, Charles, was pretty knowledgeable about cleaning the carpet and removing scratches on the wood trim. He was also a nice guy and did a great job."),
        new("Daniel M.", "Angi", 5, new DateOnly(2017, 3, 2),
            "It went splendid! He was really prompt and respectful."),
        new("Jake Z.", "Angi", 5, new DateOnly(2017, 3, 1),
            "It went awesome. They got a bunch of stains up I didn't think would come up."),
        new("Brian J.", "Angi", 5, new DateOnly(2017, 3, 1),
            "He was very professional. He showed up right on time and was able to get the job done promptly, he even finished a few minutes early. I would definitely use him again."),
        new("Melissa D.", "Angi", 5, new DateOnly(2017, 3, 1),
            "They did a great job. He pre-treated the carpet then cleaned the carpet very well."),
        new("Ryan B.", "Angi", 5, new DateOnly(2017, 2, 22),
            "I called New Era to clean our carpet and tile. He was extremely courteous and knowledgeable walking me through the process. The owner, Charles, came out (on time) to give me an estimate, honored his coupon prices and got straight to work. I couldn't be happier with the work that Charles provided. My carpets look and smell fresh and the tile looks just as good as the day it was installed! He earned my repeat business with his effort and attention to detail. I definitely recommend New Era steam cleaning!"),
        new("Brooke E.", "Angi", 5, new DateOnly(2017, 2, 20),
            "Charles was here within 2 hours of calling at 2am on a Sunday night. We had water throughout 3 rooms of our house. He was able to give us a very detailed estimate and exact game plan for the drying process. He was very detailed in explaining what he was doing and exactly why he was doing it. He even gave us a link to a company called IICRC, who generate the guidelines for water restoration so we could double check and make sure what he was stating was true. He explained the drying could take 3-5 days to be complete, he was done in 3! He was very reasonable on pricing since we decided to pay out of pocket instead of using our insurance. All around great guy, professional and honest. We've used him for years for carpet and tile cleaning, hopefully we won't have to call for water damage again, but he will be the guy we call if it happens!")
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

        // Clients are created automatically when a quote request comes in, so
        // every seeded request gets a matching client. This keeps the "View
        // profile" link on each request card pointing at a real profile.
        foreach (var request in quoteRequests)
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
