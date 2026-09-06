using System.Linq;
using Business_Tools_Suite.Client.Models;
using Business_Tools_Suite.Client.Services;

namespace Business_Tools_Suite.Services;

/// <summary>
/// Server-side validation for quote requests arriving at <c>/api/quote</c>. The
/// WebAssembly client validates with data annotations before posting, but that copy
/// of the rules runs in the browser and can be bypassed, so the endpoint re-checks
/// everything. The rules mirror the WordPress handler in
/// <c>inc/quote-handler.php</c>.
/// </summary>
public static class QuoteRequestValidator
{
    /// <summary>
    /// Returns a visitor-facing message describing the first problem found, or
    /// <c>null</c> when the request is valid.
    /// </summary>
    public static string? Validate(QuoteRequest? request)
    {
        if (request is null)
        {
            return "Please complete the form.";
        }

        if (string.IsNullOrWhiteSpace(request.Name))
        {
            return "Please enter your name.";
        }

        if (string.IsNullOrWhiteSpace(request.Phone))
        {
            return "Please enter a phone number.";
        }

        if (request.Phone.Count(char.IsDigit) < 10)
        {
            return "Please enter a valid phone number.";
        }

        if (string.IsNullOrWhiteSpace(request.Email))
        {
            return "Please enter your email.";
        }

        if (!IsProbablyEmail(request.Email))
        {
            return "Please enter a valid email address.";
        }

        if (string.IsNullOrWhiteSpace(request.ServiceType))
        {
            return "Please choose a service.";
        }

        if (!ServiceCatalog.Names.Contains(request.ServiceType))
        {
            return "Please choose a service from the list.";
        }

        if (string.IsNullOrWhiteSpace(request.Message))
        {
            return "Please tell us a little about the job.";
        }

        return null;
    }

    /// <summary>
    /// A deliberately loose shape check. The address is only used for Reply-To, and
    /// the authoritative test of an address is whether mail to it is delivered.
    /// </summary>
    private static bool IsProbablyEmail(string value)
    {
        var at = value.IndexOf('@');

        return at > 0 &&
            at == value.LastIndexOf('@') &&
            at < value.Length - 1 &&
            !value.Any(char.IsWhiteSpace);
    }
}
