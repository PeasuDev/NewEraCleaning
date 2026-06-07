namespace Business_Tools_Suite;

/// <summary>
/// Configuration-driven allow-list that restricts which email accounts are
/// permitted to sign in (and register). Bound from the "LoginAllowList"
/// section of configuration.
/// </summary>
public class LoginAllowListOptions
{
    public const string SectionName = "LoginAllowList";

    /// <summary>
    /// Email addresses permitted to access the admin portal. Comparison is
    /// case-insensitive and ignores surrounding whitespace.
    /// </summary>
    public string[] AllowedEmails { get; set; } = Array.Empty<string>();

    /// <summary>
    /// Returns true if the supplied email is on the allow-list. When the
    /// allow-list is empty, access is denied by default (fail closed).
    /// </summary>
    public bool IsAllowed(string? email)
    {
        if (string.IsNullOrWhiteSpace(email) || AllowedEmails.Length == 0)
        {
            return false;
        }

        return AllowedEmails.Any(allowed =>
            string.Equals(allowed?.Trim(), email.Trim(), StringComparison.OrdinalIgnoreCase));
    }
}
