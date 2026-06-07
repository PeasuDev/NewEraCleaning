using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;
using Business_Tools_Suite.Client.Models;
using Business_Tools_Suite.Client.Services;
using Microsoft.Extensions.Options;

namespace Business_Tools_Suite.Services;

/// <summary>
/// Server-side <see cref="IReviewProvider"/> that fetches live reviews from the
/// Google Places API (New) Place Details endpoint. The API key lives only on the
/// server. Results are cached in memory for <see cref="GoogleReviewOptions.CacheDuration"/>
/// to limit API usage. Any failure returns an empty list so callers fall back to
/// placeholder reviews rather than surfacing an error to visitors.
/// </summary>
public sealed class ServerGoogleReviewProvider : IReviewProvider
{
    private const string HttpClientName = "GooglePlaces";
    private const string Source = "Google";

    // Places API (New): https://developers.google.com/maps/documentation/places/web-service/place-details
    private const string FieldMask = "reviews.rating,reviews.text,reviews.originalText,reviews.authorAttribution,reviews.publishTime";

    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true
    };

    private readonly IHttpClientFactory _httpClientFactory;
    private readonly GoogleReviewOptions _options;
    private readonly ILogger<ServerGoogleReviewProvider> _logger;
    private readonly SemaphoreSlim _gate = new(1, 1);

    private IReadOnlyList<Review> _cached = Array.Empty<Review>();
    private DateTimeOffset _cacheExpiresAt = DateTimeOffset.MinValue;

    public ServerGoogleReviewProvider(
        IHttpClientFactory httpClientFactory,
        IOptions<GoogleReviewOptions> options,
        ILogger<ServerGoogleReviewProvider> logger)
    {
        _httpClientFactory = httpClientFactory;
        _options = options.Value;
        _logger = logger;
    }

    public async Task<IReadOnlyList<Review>> GetLiveReviewsAsync(CancellationToken cancellationToken = default)
    {
        if (!_options.IsConfigured)
        {
            return Array.Empty<Review>();
        }

        if (DateTimeOffset.UtcNow < _cacheExpiresAt)
        {
            return _cached;
        }

        await _gate.WaitAsync(cancellationToken);
        try
        {
            if (DateTimeOffset.UtcNow < _cacheExpiresAt)
            {
                return _cached;
            }

            var reviews = await FetchFromGoogleAsync(cancellationToken);
            _cached = reviews;
            _cacheExpiresAt = DateTimeOffset.UtcNow.Add(_options.CacheDuration);
            return reviews;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to fetch Google reviews for place {PlaceId}.", _options.PlaceId);
            // Serve a stale cache if we have one; otherwise fall back to empty.
            return _cached;
        }
        finally
        {
            _gate.Release();
        }
    }

    private async Task<IReadOnlyList<Review>> FetchFromGoogleAsync(CancellationToken cancellationToken)
    {
        var client = _httpClientFactory.CreateClient(HttpClientName);

        using var request = new HttpRequestMessage(
            HttpMethod.Get,
            $"v1/places/{Uri.EscapeDataString(_options.PlaceId)}");
        request.Headers.Add("X-Goog-Api-Key", _options.ApiKey);
        request.Headers.Add("X-Goog-FieldMask", FieldMask);

        using var response = await client.SendAsync(request, cancellationToken);
        if (!response.IsSuccessStatusCode)
        {
            var body = await response.Content.ReadAsStringAsync(cancellationToken);
            _logger.LogWarning(
                "Google Places returned {StatusCode}: {Body}", (int)response.StatusCode, body);
            return _cached;
        }

        var payload = await response.Content.ReadFromJsonAsync<PlaceDetailsResponse>(
            JsonOptions, cancellationToken);

        if (payload?.Reviews is not { Count: > 0 } googleReviews)
        {
            return Array.Empty<Review>();
        }

        return googleReviews
            .Select(MapReview)
            .Where(r => r is not null)
            .Select(r => r!)
            .OrderByDescending(r => r.Date)
            .ToList();
    }

    private static Review? MapReview(GoogleReview review)
    {
        var text = review.Text?.Text ?? review.OriginalText?.Text;
        if (string.IsNullOrWhiteSpace(text))
        {
            return null;
        }

        var author = string.IsNullOrWhiteSpace(review.AuthorAttribution?.DisplayName)
            ? "Google user"
            : review.AuthorAttribution!.DisplayName!;

        var date = review.PublishTime is { } published
            ? DateOnly.FromDateTime(published.UtcDateTime)
            : DateOnly.FromDateTime(DateTime.UtcNow);

        var rating = Math.Clamp(review.Rating, 1, 5);

        return new Review(author, Source, rating, date, text!);
    }

    // ---- Google Places API (New) response shapes ----

    private sealed class PlaceDetailsResponse
    {
        [JsonPropertyName("reviews")]
        public List<GoogleReview>? Reviews { get; set; }
    }

    private sealed class GoogleReview
    {
        [JsonPropertyName("rating")]
        public int Rating { get; set; }

        [JsonPropertyName("text")]
        public LocalizedText? Text { get; set; }

        [JsonPropertyName("originalText")]
        public LocalizedText? OriginalText { get; set; }

        [JsonPropertyName("authorAttribution")]
        public AuthorAttribution? AuthorAttribution { get; set; }

        [JsonPropertyName("publishTime")]
        public DateTimeOffset? PublishTime { get; set; }
    }

    private sealed class LocalizedText
    {
        [JsonPropertyName("text")]
        public string? Text { get; set; }
    }

    private sealed class AuthorAttribution
    {
        [JsonPropertyName("displayName")]
        public string? DisplayName { get; set; }
    }
}
