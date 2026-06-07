using System.Globalization;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;
using Business_Tools_Suite.Client.Models;
using Business_Tools_Suite.Client.Services;
using Microsoft.Extensions.Options;

namespace Business_Tools_Suite.Services;

/// <summary>
/// Server-side <see cref="IReviewProvider"/> that fetches live reviews from the
/// Yelp Fusion API. The API key lives only on the server. Results are cached in
/// memory for <see cref="YelpReviewOptions.CacheDuration"/> to limit API usage.
/// Any failure returns an empty list so callers fall back to placeholder reviews
/// rather than surfacing an error to visitors.
/// </summary>
/// <remarks>
/// The Yelp reviews endpoint returns at most three review excerpts (roughly 160
/// characters each); this is a Yelp restriction, not a limitation of this code.
/// </remarks>
public sealed class ServerYelpReviewProvider : IReviewProvider
{
    private const string HttpClientName = "Yelp";
    private const string Source = "Yelp";

    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true
    };

    private readonly IHttpClientFactory _httpClientFactory;
    private readonly YelpReviewOptions _options;
    private readonly ILogger<ServerYelpReviewProvider> _logger;
    private readonly SemaphoreSlim _gate = new(1, 1);

    private IReadOnlyList<Review> _cached = Array.Empty<Review>();
    private DateTimeOffset _cacheExpiresAt = DateTimeOffset.MinValue;

    public ServerYelpReviewProvider(
        IHttpClientFactory httpClientFactory,
        IOptions<YelpReviewOptions> options,
        ILogger<ServerYelpReviewProvider> logger)
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

            var reviews = await FetchFromYelpAsync(cancellationToken);
            _cached = reviews;
            _cacheExpiresAt = DateTimeOffset.UtcNow.Add(_options.CacheDuration);
            return reviews;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to fetch Yelp reviews for business {BusinessId}.", _options.BusinessId);
            // Serve a stale cache if we have one; otherwise fall back to empty.
            return _cached;
        }
        finally
        {
            _gate.Release();
        }
    }

    private async Task<IReadOnlyList<Review>> FetchFromYelpAsync(CancellationToken cancellationToken)
    {
        var client = _httpClientFactory.CreateClient(HttpClientName);

        using var request = new HttpRequestMessage(
            HttpMethod.Get,
            $"v3/businesses/{Uri.EscapeDataString(_options.BusinessId)}/reviews?limit=3");
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _options.ApiKey);
        request.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        using var response = await client.SendAsync(request, cancellationToken);
        if (!response.IsSuccessStatusCode)
        {
            var body = await response.Content.ReadAsStringAsync(cancellationToken);
            _logger.LogWarning(
                "Yelp Fusion returned {StatusCode}: {Body}", (int)response.StatusCode, body);
            return _cached;
        }

        var payload = await response.Content.ReadFromJsonAsync<YelpReviewsResponse>(
            JsonOptions, cancellationToken);

        if (payload?.Reviews is not { Count: > 0 } yelpReviews)
        {
            return Array.Empty<Review>();
        }

        return yelpReviews
            .Select(MapReview)
            .Where(r => r is not null)
            .Select(r => r!)
            .OrderByDescending(r => r.Date)
            .ToList();
    }

    private static Review? MapReview(YelpReview review)
    {
        if (string.IsNullOrWhiteSpace(review.Text))
        {
            return null;
        }

        var author = string.IsNullOrWhiteSpace(review.User?.Name)
            ? "Yelp user"
            : review.User!.Name!;

        var date = DateTime.TryParse(
            review.TimeCreated,
            CultureInfo.InvariantCulture,
            DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
            out var parsed)
            ? DateOnly.FromDateTime(parsed)
            : DateOnly.FromDateTime(DateTime.UtcNow);

        var rating = Math.Clamp(review.Rating, 1, 5);

        return new Review(author, Source, rating, date, review.Text!);
    }

    // ---- Yelp Fusion reviews response shapes ----

    private sealed class YelpReviewsResponse
    {
        [JsonPropertyName("reviews")]
        public List<YelpReview>? Reviews { get; set; }
    }

    private sealed class YelpReview
    {
        [JsonPropertyName("rating")]
        public int Rating { get; set; }

        [JsonPropertyName("text")]
        public string? Text { get; set; }

        [JsonPropertyName("time_created")]
        public string? TimeCreated { get; set; }

        [JsonPropertyName("user")]
        public YelpUser? User { get; set; }
    }

    private sealed class YelpUser
    {
        [JsonPropertyName("name")]
        public string? Name { get; set; }
    }
}
