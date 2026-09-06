namespace Business_Tools_Suite.Services;

/// <summary>
/// Configuration for the outbound quote-request notification email. Bind from the
/// "QuoteMail" configuration section. <see cref="Password"/> must be supplied through
/// a secure source (user-secrets, environment variables, or a key vault) and is never
/// sent to the browser.
/// </summary>
/// <remarks>
/// Supply the values with user-secrets:
/// <code>
/// dotnet user-secrets set "QuoteMail:UserName" "you@gmail.com"
/// dotnet user-secrets set "QuoteMail:Password" "&lt;16-char app password&gt;"
/// dotnet user-secrets set "QuoteMail:FromAddress" "you@gmail.com"
/// dotnet user-secrets set "QuoteMail:ToAddress" "you@gmail.com"
/// </code>
/// User-secrets only load in the Development environment. deploy-and-launch.ps1
/// passes --no-launch-profile when -NoLaunchBrowser is used, which leaves
/// ASPNETCORE_ENVIRONMENT unset and therefore Production; set the same values as
/// environment variables (QuoteMail__UserName, ...) for that case.
///
/// Gmail rejects an account password over SMTP. The credential must be an App
/// Password, which requires 2-Step Verification on the account.
/// </remarks>
public class QuoteMailOptions
{
    public const string SectionName = "QuoteMail";

    /// <summary>SMTP host. Defaults to Gmail.</summary>
    public string Host { get; set; } = "smtp.gmail.com";

    /// <summary>SMTP port. 587 is STARTTLS submission.</summary>
    public int Port { get; set; } = 587;

    /// <summary>Whether to negotiate TLS. Required by Gmail.</summary>
    public bool EnableSsl { get; set; } = true;

    /// <summary>Account used to authenticate to the SMTP server.</summary>
    public string UserName { get; set; } = string.Empty;

    /// <summary>App password for <see cref="UserName"/>.</summary>
    public string Password { get; set; } = string.Empty;

    /// <summary>
    /// Envelope sender. Gmail rewrites this to the authenticated account, so it
    /// should normally match <see cref="UserName"/>.
    /// </summary>
    public string FromAddress { get; set; } = string.Empty;

    /// <summary>Display name shown as the sender.</summary>
    public string FromName { get; set; } = "New Era Steam Cleaning";

    /// <summary>
    /// Where notifications are delivered. Accepts a comma-separated list.
    /// </summary>
    public string ToAddress { get; set; } = string.Empty;

    /// <summary>
    /// True when enough is configured to attempt a send. Without this the form
    /// reports a failure instead of showing a success panel that delivered nothing.
    /// </summary>
    public bool IsConfigured =>
        !string.IsNullOrWhiteSpace(Host) &&
        !string.IsNullOrWhiteSpace(UserName) &&
        !string.IsNullOrWhiteSpace(Password) &&
        !string.IsNullOrWhiteSpace(ToAddress);

    /// <summary>
    /// The sender address to use, falling back to the authenticating account when
    /// no explicit From has been configured.
    /// </summary>
    public string EffectiveFromAddress =>
        string.IsNullOrWhiteSpace(FromAddress) ? UserName : FromAddress;
}
