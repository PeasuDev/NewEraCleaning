using Business_Tools_Suite.Client.Models;

namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Abstraction over the active live review source (Yelp, Google, etc.). The Blazor
/// Web App uses the Interactive Auto render mode, so a component can run on the
/// server (during prerendering / interactive server rendering) or on the client
/// (WebAssembly). To keep platform API keys off the browser, the server
/// implementation calls the review platform directly while the client
/// implementation calls a server-side endpoint. Each render environment resolves
/// its own implementation from DI.
/// </summary>
public interface IReviewProvider
{
    /// <summary>
    /// Returns live reviews for the configured business, newest first. Returns an
    /// empty list when the source is not configured or unavailable so callers can
    /// fall back to placeholder data.
    /// </summary>
    Task<IReadOnlyList<Review>> GetLiveReviewsAsync(CancellationToken cancellationToken = default);
}
