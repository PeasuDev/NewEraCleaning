using System.Net.Http.Json;
using Business_Tools_Suite.Client.Models;

namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Client-side (WebAssembly) <see cref="IReviewProvider"/>. The browser must never
/// see a review-platform API key, so this implementation calls the server endpoint
/// (<c>/api/reviews/live</c>), which performs the authenticated request to the
/// active review source (Yelp, Google, etc.). Any failure returns an empty list so
/// callers fall back to placeholder reviews.
/// </summary>
public sealed class ClientLiveReviewProvider : IReviewProvider
{
    private readonly HttpClient _httpClient;

    public ClientLiveReviewProvider(HttpClient httpClient)
    {
        _httpClient = httpClient;
    }

    public async Task<IReadOnlyList<Review>> GetLiveReviewsAsync(CancellationToken cancellationToken = default)
    {
        try
        {
            var reviews = await _httpClient.GetFromJsonAsync<List<Review>>(
                "api/reviews/live", cancellationToken);
            return reviews ?? new List<Review>();
        }
        catch
        {
            return Array.Empty<Review>();
        }
    }
}
