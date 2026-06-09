namespace Business_Tools_Suite.Client.Models;

/// <summary>
/// A client of New Era Steam Cleaning. Clients are created automatically when a quote
/// request is received, or manually through the Create a Client interface.
/// </summary>
public record ClientProfile(
    string Name,
    string Email,
    string Phone,
    string ServiceType,
    DateTime CreatedOn,
    ClientSource Source,
    string? Notes = null);

/// <summary>
/// Indicates how a client record came to exist.
/// </summary>
public enum ClientSource
{
    QuoteRequest,
    Manual
}
