using System.Net;
using System.Net.Mail;
using Business_Tools_Suite.Client.Models;
using Business_Tools_Suite.Client.Services;
using Microsoft.Extensions.Options;

namespace Business_Tools_Suite.Services;

/// <summary>
/// Server-side <see cref="IQuoteSubmissionService"/>. Sends the quote notification
/// over SMTP using credentials that stay on the server. Used directly when the quote
/// form renders interactively on the server, and indirectly by the WebAssembly client
/// through the <c>/api/quote</c> endpoint.
/// </summary>
public sealed class SmtpQuoteSubmissionService : IQuoteSubmissionService
{
    private readonly QuoteMailOptions _options;
    private readonly ILogger<SmtpQuoteSubmissionService> _logger;

    public SmtpQuoteSubmissionService(
        IOptions<QuoteMailOptions> options,
        ILogger<SmtpQuoteSubmissionService> logger)
    {
        _options = options.Value;
        _logger = logger;
    }

    public async Task<QuoteSubmissionResult> SubmitAsync(
        QuoteRequest request,
        CancellationToken cancellationToken = default)
    {
        if (!_options.IsConfigured)
        {
            // Deliberately loud: a quote request that goes nowhere must not be
            // reported to the visitor as delivered.
            _logger.LogError(
                "A quote request from {Name} could not be emailed because the {Section} " +
                "configuration section is incomplete. Set UserName, Password and ToAddress.",
                request.Name,
                QuoteMailOptions.SectionName);

            return QuoteSubmissionResult.Failure(
                "Email delivery isn't configured on this server, so your request wasn't sent. " +
                "Please call us instead.");
        }

        try
        {
            using var message = BuildMessage(request);
            using var client = new SmtpClient(_options.Host, _options.Port)
            {
                EnableSsl = _options.EnableSsl,
                Credentials = new NetworkCredential(_options.UserName, _options.Password),
            };

            await client.SendMailAsync(message, cancellationToken);

            _logger.LogInformation(
                "Emailed a quote request from {Name} to {Recipient}.",
                request.Name,
                _options.ToAddress);

            return QuoteSubmissionResult.Success();
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to email the quote request from {Name}.", request.Name);

            return QuoteSubmissionResult.Failure(
                "We couldn't send your request just now. Please call us instead.");
        }
    }

    /// <summary>
    /// Builds the notification. The body matches the WordPress theme's
    /// newera_send_quote_notification() line for line, so the local preview shows
    /// what the live site will send. The one exception is the trailing
    /// "View in admin" link, which exists only in WordPress because a stored
    /// newera_quote post exists there to link to.
    /// </summary>
    private MailMessage BuildMessage(QuoteRequest request)
    {
        var body = string.Join(
            "\n",
            "A new quote request came in through the website.",
            string.Empty,
            "Name:    " + request.Name,
            "Phone:   " + request.Phone,
            "Email:   " + request.Email,
            "Service: " + request.ServiceType,
            string.Empty,
            "Message:",
            request.Message);

        var message = new MailMessage
        {
            From = new MailAddress(_options.EffectiveFromAddress, _options.FromName),
            Subject = $"[{_options.FromName}] New quote request from {request.Name}",
            Body = body,
            IsBodyHtml = false,
        };

        foreach (var recipient in SplitAddresses(_options.ToAddress))
        {
            message.To.Add(recipient);
        }

        // Reply-To is the customer so the owner can answer straight from the
        // notification, matching the WordPress behaviour.
        if (!string.IsNullOrWhiteSpace(request.Email))
        {
            message.ReplyToList.Add(new MailAddress(request.Email, request.Name));
        }

        return message;
    }

    /// <summary>Splits a comma or semicolon separated recipient list.</summary>
    private static IEnumerable<string> SplitAddresses(string addresses) =>
        addresses
            .Split(new[] { ',', ';' }, StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries);
}
