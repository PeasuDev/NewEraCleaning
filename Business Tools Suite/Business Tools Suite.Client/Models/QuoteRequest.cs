namespace Business_Tools_Suite.Client.Models;

/// <summary>
/// A quote request submitted by a prospective customer through the public
/// Quote Request form. Placeholder data uses this same shape today.
/// </summary>
public record QuoteRequest(
    string Name,
    string Email,
    string Phone,
    string ServiceType,
    string Message,
    DateTime SubmittedOn,
    QuoteRequestStatus Status);

/// <summary>
/// Tracks where a quote request is in the owner's follow-up workflow.
/// </summary>
public enum QuoteRequestStatus
{
    New,
    Contacted,
    Quoted,
    Won,
    Lost
}
