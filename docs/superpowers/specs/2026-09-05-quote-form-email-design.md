# Quote form email delivery — design

**Date:** 2026-09-05
**Goal:** A submitted quote request arrives as email at `tpease87@gmail.com`, on both
the WordPress theme and the Blazor local preview, so the owner can test it end to end.

## Starting position

The two surfaces were in very different states.

**WordPress** (`wordpress/newera-steam-cleaning/inc/quote-handler.php`) was already
complete: it validates the submission, stores it as a private `newera_quote` post,
and calls `wp_mail()` from `newera_send_quote_notification()`. The recipient is
`get_option( 'admin_email' )`, already wrapped in a `newera_quote_recipient` filter.
Two things were missing — a way to point that filter at a chosen address, and an
SMTP transport, since a staging host's `wp_mail()` usually drops mail silently.

**Blazor** (`Business Tools Suite.Client/Components/QuoteForm.razor`) had no email
path at all. `HandleSubmit` added the request to the in-memory
`PlaceholderDataService` and stopped there.

## Constraint that shapes the Blazor design

The public pages render with `InteractiveAuto` (`Components/App.razor:31`), so
`QuoteForm` executes on the server during prerender/interactive-server, and in the
browser under WebAssembly once the runtime downloads. A WASM component cannot open
an SMTP socket, and must never hold the Gmail app password.

The codebase already solves this exact problem for Yelp reviews via `IReviewProvider`:
one interface, a server implementation that talks to the outside world directly, and
a client implementation that calls a server endpoint. This design mirrors that
pattern rather than inventing a second one.

## Blazor components

| Piece | Project | Responsibility |
| --- | --- | --- |
| `IQuoteSubmissionService` | Client | `SubmitAsync(QuoteRequest, CancellationToken)` → `QuoteSubmissionResult` |
| `QuoteSubmissionResult` | Client | `Sent` flag plus a user-facing `ErrorMessage` |
| `ClientQuoteSubmissionService` | Client | WASM path: POSTs JSON to `api/quote` |
| `QuoteMailOptions` | Server | Bound from the `QuoteMail` config section |
| `SmtpQuoteSubmissionService` | Server | Builds and sends the message over SMTP |
| `POST /api/quote` | Server | Re-validates, delegates to the server service |

`QuoteForm.razor` injects the interface, makes `HandleSubmit` async, and keeps its
existing `DataService.AddQuoteRequest` call so current behaviour is unchanged; email
is added on top.

**Configuration source.** Options bind from the `QuoteMail` section, which picks up
both user-secrets and environment variables. This matters: `deploy-and-launch.ps1:147`
runs with `--no-launch-profile` when `-NoLaunchBrowser` is passed, which leaves
`ASPNETCORE_ENVIRONMENT` unset. It then defaults to Production and user-secrets do
**not** load. Environment variables keep working in that case.

**Transport.** `System.Net.Mail.SmtpClient` from the base class library — no new
NuGet package, so no restore risk on an offline machine.

**Failure is visible.** If the mail options are unconfigured, or the send throws, the
form shows an error instead of the success panel. A test that silently does nothing
must not look like a test that passed.

**Server-side validation.** `/api/quote` re-validates every field. The browser copy
of the rules can be bypassed, so the endpoint cannot trust its input. The endpoint is
anonymous and calls `.DisableAntiforgery()`, since it is a JSON POST from WASM rather
than a form post.

## WordPress components

A new `inc/mail.php`, required from `functions.php` after `quote-handler.php`:

- `newera_quote_recipient` filter callback returning the `NEWERA_QUOTE_RECIPIENT`
  constant when defined (comma-separated addresses allowed), otherwise leaving the
  `admin_email` default alone. The theme stays correct on an install that defines
  nothing.
- A `phpmailer_init` hook routing `wp_mail()` through Gmail SMTP, active only when
  `NEWERA_SMTP_HOST` and credentials are defined.

Both are configured from `wp-config.php`, so no credential enters the repository.
The existing `Reply-To: customer` header (`quote-handler.php:278`) is unchanged.

## Message body

The bodies match line for line:

```
A new quote request came in through the website.

Name:    <name>
Phone:   <phone>
Email:   <email>
Service: <service>

Message:
<message>
```

**One intentional difference:** the WordPress version appends a
`View in admin: <edit link>` line, because a stored `newera_quote` post exists to link
to. The Blazor preview has no such record, so it omits that line rather than emit a
fabricated URL.

## Setup the owner must perform

Neither surface can send without a credential, and the credential can only be created
by the account holder.

### 1. Google App Password

Requires 2-Step Verification on the account first.

1. Turn on 2-Step Verification at <https://myaccount.google.com/signinoptions/two-step-verification>.
2. Go to <https://myaccount.google.com/apppasswords>.
3. Name it (e.g. `New Era site`) and create it.
4. Copy the 16-character password. Google shows it once. Spaces are display-only and
   may be omitted.

A normal account password will not work — Google rejects it for SMTP.

### 2. Blazor

From the `Business Tools Suite` folder:

```powershell
dotnet user-secrets --project "Business Tools Suite\Business Tools Suite.csproj" set "QuoteMail:UserName" "tpease87@gmail.com"
dotnet user-secrets --project "Business Tools Suite\Business Tools Suite.csproj" set "QuoteMail:Password" "<app password>"
dotnet user-secrets --project "Business Tools Suite\Business Tools Suite.csproj" set "QuoteMail:FromAddress" "tpease87@gmail.com"
dotnet user-secrets --project "Business Tools Suite\Business Tools Suite.csproj" set "QuoteMail:ToAddress" "tpease87@gmail.com"
```

Host (`smtp.gmail.com`), port (`587`) and SSL default correctly in `appsettings.json`.

If the app is ever started with `-NoLaunchBrowser`, set the same values as
environment variables (`QuoteMail__UserName`, …) instead, for the reason above.

### 3. WordPress staging

In `wp-config.php`, above the `/* That's all, stop editing! */` line:

```php
define( 'NEWERA_QUOTE_RECIPIENT', 'tpease87@gmail.com' );
define( 'NEWERA_SMTP_HOST', 'smtp.gmail.com' );
define( 'NEWERA_SMTP_PORT', 587 );
define( 'NEWERA_SMTP_SECURE', 'tls' );
define( 'NEWERA_SMTP_USER', 'tpease87@gmail.com' );
define( 'NEWERA_SMTP_PASS', '<app password>' );
define( 'NEWERA_SMTP_FROM', 'tpease87@gmail.com' );
```

`NEWERA_QUOTE_RECIPIENT` replaces the recipient outright. That is correct for a
staging site, and deliberately wrong for production — on live it would divert real
customer leads to a personal inbox. Production should leave the constant undefined.

## Verification boundary

- The Blazor side can be compiled and run here; `dotnet build` must pass.
- **No test email can be sent during implementation.** It needs the app password,
  which only the account holder can generate.
- The WordPress side **cannot be executed at all** — there is no PHP runtime on this
  machine. It is checked structurally only.
