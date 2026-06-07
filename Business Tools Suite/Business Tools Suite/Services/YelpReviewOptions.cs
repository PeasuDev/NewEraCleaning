namespace Business_Tools_Suite.Services;

/// <summary>
/// Configuration for the server-side Yelp Fusion review integration. Bind from
/// the "Yelp" configuration section. The <see cref="ApiKey"/> must be supplied
/// through a secure source (user-secrets, environment variables, or a key vault)
/// and is never sent to the browser.
/// </summary>
public class YelpReviewOptions
{
    public const string SectionName = "Yelp";

    /// <summary>
    /// Yelp Fusion API key (the "API Key" from a Yelp developer app).
    /// </summary>
    public string ApiKey { get; set; } = string.Empty;

    /// <summary>
    /// The Yelp business id or alias (slug). For New Era this is the slug from
    /// the business URL, e.g. "new-era-steam-cleaning-and-restoration-sugar-land".
    /// </summary>
    public string BusinessId { get; set; } = string.Empty;

    /// <summary>
    /// How long fetched reviews are cached before Yelp is queried again. Keeps
    /// API usage within rate limits. Defaults to 12 hours.
    /// </summary>
    public TimeSpan CacheDuration { get; set; } = TimeSpan.FromHours(12);

    /// <summary>
    /// True when both an API key and a business id have been configured.
    /// </summary>
    public bool IsConfigured =>
        !string.IsNullOrWhiteSpace(ApiKey) && !string.IsNullOrWhiteSpace(BusinessId);
}
