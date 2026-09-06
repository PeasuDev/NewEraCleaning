using Business_Tools_Suite.Client.Models;

namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Delivers a public quote request to the business. The Blazor Web App uses the
/// Interactive Auto render mode, so the quote form can run on the server (during
/// prerendering / interactive server rendering) or in the browser (WebAssembly).
/// SMTP credentials must never reach the browser, so the server implementation
/// sends the mail directly while the client implementation posts to a server-side
/// endpoint. Each render environment resolves its own implementation from DI.
/// </summary>
public interface IQuoteSubmissionService
{
    /// <summary>
    /// Sends the request to the business. Never throws: transport problems are
    /// reported through <see cref="QuoteSubmissionResult"/> so the form can show a
    /// real error rather than a success panel that delivered nothing.
    /// </summary>
    Task<QuoteSubmissionResult> SubmitAsync(QuoteRequest request, CancellationToken cancellationToken = default);
}

/// <summary>
/// Outcome of a quote submission, with a message suitable for showing to the visitor.
/// </summary>
/// <param name="Sent">Whether the notification was handed off to the mail server.</param>
/// <param name="ErrorMessage">Visitor-facing explanation when <paramref name="Sent"/> is false.</param>
public sealed record QuoteSubmissionResult(bool Sent, string? ErrorMessage = null)
{
    /// <summary>The notification was accepted by the mail server.</summary>
    public static QuoteSubmissionResult Success() => new(true);

    /// <summary>The notification could not be sent.</summary>
    public static QuoteSubmissionResult Failure(string errorMessage) => new(false, errorMessage);
}
