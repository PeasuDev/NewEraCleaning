namespace Business_Tools_Suite.Services;

/// <summary>
/// Configuration for the server-side Google Places review integration. Bind from
/// the "GooglePlaces" configuration section. The <see cref="ApiKey"/> must be
/// supplied through a secure source (user-secrets, environment variables, or a
/// key vault) and is never sent to the browser.
/// </summary>
public class GoogleReviewOptions
{
    public const string SectionName = "GooglePlaces";

    /// <summary>
    /// Google Maps Platform API key with the Places API (New) enabled.
    /// </summary>
    public string ApiKey { get; set; } = string.Empty;

    /// <summary>
    /// The Google Place ID for the business whose reviews are displayed.
    /// </summary>
    public string PlaceId { get; set; } = string.Empty;

    /// <summary>
    /// How long fetched reviews are cached before Google is queried again.
    /// Keeps API costs down and respects rate limits. Defaults to 12 hours.
    /// </summary>
    public TimeSpan CacheDuration { get; set; } = TimeSpan.FromHours(12);

    /// <summary>
    /// True when both an API key and a Place ID have been configured.
    /// </summary>
    public bool IsConfigured =>
        !string.IsNullOrWhiteSpace(ApiKey) && !string.IsNullOrWhiteSpace(PlaceId);
}
