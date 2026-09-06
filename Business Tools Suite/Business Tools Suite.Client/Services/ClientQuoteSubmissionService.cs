using System.Net.Http.Json;
using Business_Tools_Suite.Client.Models;

namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Client-side (WebAssembly) <see cref="IQuoteSubmissionService"/>. The browser must
/// never see the mail account's credentials, so this implementation posts the request
/// to the server endpoint (<c>/api/quote</c>), which performs the authenticated SMTP
/// send. Mirrors <see cref="ClientLiveReviewProvider"/>.
/// </summary>
public sealed class ClientQuoteSubmissionService : IQuoteSubmissionService
{
    private readonly HttpClient _httpClient;

    public ClientQuoteSubmissionService(HttpClient httpClient)
    {
        _httpClient = httpClient;
    }

    public async Task<QuoteSubmissionResult> SubmitAsync(
        QuoteRequest request,
        CancellationToken cancellationToken = default)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync("api/quote", request, cancellationToken);

            if (response.IsSuccessStatusCode)
            {
                return QuoteSubmissionResult.Success();
            }

            // The endpoint reports validation and configuration problems as a plain
            // text body; surface it rather than a bare status code.
            var detail = await response.Content.ReadAsStringAsync(cancellationToken);

            return QuoteSubmissionResult.Failure(string.IsNullOrWhiteSpace(detail)
                ? "We couldn't send your request. Please call us instead."
                : detail);
        }
        catch (Exception)
        {
            return QuoteSubmissionResult.Failure(
                "We couldn't reach the server. Please check your connection or call us instead.");
        }
    }
}
