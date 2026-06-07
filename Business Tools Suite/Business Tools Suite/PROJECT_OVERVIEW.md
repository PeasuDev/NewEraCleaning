# Business Tools Suite — Project Overview

An internal **administration dashboard for New Era Cleaning** ([neweracleaning.com](https://neweracleaning.com/)).
The site gives the business owner a single place to monitor reputation, incoming
work, and clients. It is currently backed by **placeholder data**, with the data
shapes designed so real integrations (review scraping/APIs, a public quote form,
a database) can be plugged in later.

## Tech Stack

- **.NET 10** Blazor Web App
- **Interactive Server** + **Interactive WebAssembly** render modes (InteractiveAuto)
- **ASP.NET Core Identity** (SQL Server) for authentication, including passkeys
- Scoped/global CSS with a shared design system

## Solution Structure

| Project | Role |
| --- | --- |
| `Business Tools Suite` | Server host: Identity/auth, configuration, prerendering, endpoint mapping |
| `Business Tools Suite.Client` | WebAssembly client: pages, layout, models, and the shared data service |

## Features

### Authentication & Access Control
- **Login allow-list enforcement** — only configured email accounts may sign in or register.
  - `LoginAllowListOptions` (case-insensitive, **fail-closed** when empty) bound from the
	`LoginAllowList:AllowedEmails` section of `appsettings.json`.
  - Enforced in `Login.razor` on both the **password path** (pre-check) and the
	**passkey path** (assert once → validate resolved email → persist passkey → sign in).
  - Enforced in `Register.razor` (rejects non-allow-listed emails before account creation).
- Sign-up / register links removed from the public UI.

### Home — "What's New"
- Landing page branded with the New Era Cleaning logo and name.
- A **What's New** section with two containers: **Recent Quote Requests** and **Recent Reviews**.

### Reviews
- Aggregated customer reviews (placeholder) with a source filter, summary cards, and per-review containers.
- Consumes the shared `PlaceholderDataService`.

### Analytics
- Review rating trends over time by source (SVG-based chart).

### Quote Requests (`/quote-requests`)
- Incoming quote requests displayed as individual cards.
- **New-requests "ticker"** — an animated, pulsing indicator showing the count of `New` requests.
- Status filter chips and a "Simulate incoming request" action for testing.

### Clients (`/clients`)
- Clients are **auto-created when a quote request is received**, or added manually via a
  **Create a Client** form.
- Cards are tagged **Auto** (from a quote request) vs **Manual**, with summary stats.
- Each `ClientCard` links to that client's **Client Profile** (name + "View profile →").

### Client Profile (`/clients/{Email}`)
- Per-client page keyed by the client's **email** (the de-facto unique key); the email is
  URL-encoded on the link and decoded by the router.
- **Client info** — hero header (avatar, name, service type, source badge) plus a details
  card (email, phone, service type, "client since", notes).
- **Reviews from this client** — reuses the `ReviewCard` component. Because reviews only carry
  an author **name** (no client link), matching is heuristic via `IsLikelyAuthor`: exact name,
  or a shortened "First L." author against a "First Last" client.
- **Contact options** — functional `mailto:`, `tel:`, and `sms:` actions.
- Renders a friendly **not-found** state when no client matches the email.

> **Component naming:** the page component is **`ClientProfilePage`**, *not* `ClientProfile`.
> A component named `ClientProfile` shadows the `Models.ClientProfile` record across the whole
> `Pages` namespace and breaks the build. See [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md).

## Shared Data Layer

`PlaceholderDataService` (registered as a **singleton** in both client and server DI so
state persists across navigation and prerendering):

- `GetReviews()`, `GetQuoteRequests()`, `GetClients()`
- `GetClientByEmail(email)` — single-client lookup by email (the unique key)
- `GetReviewsForClient(client)` — reviews matched to a client by author name (`IsLikelyAuthor`)
- `NewQuoteRequestCount` — drives the ticker and the nav badge
- `AddQuoteRequest(...)` — appends a request and **auto-creates a matching client**
- `AddClient(...)` — appends a manually created client
- `Changed` event — notifies pages/nav to refresh live

### Models (`Business Tools Suite.Client/Models`)
- `Review` — Author, Source, Rating, Date, Text
- `QuoteRequest` + `QuoteRequestStatus` (New, Contacted, Quoted, Won, Lost)
- `ClientProfile` + `ClientSource` (QuoteRequest, Manual)

> **Note:** the client model is named **`ClientProfile`** (not `Client`) because `Client`
> collides with the `Business_Tools_Suite.Client` project namespace.

## Design System

- Global typography scale and brand palette via CSS variables in `Business Tools Suite/wwwroot/app.css`
  (brand teal `#1090C0` / `#0c6f96`, sampled from the logo).
- Reusable minimalist container utilities: `.surface-card`, `.card-grid`, `.pill`,
  `.page-header`, `.meta-row`.
- Each data component (review, quote request, client) is wrapped in its own bordered container.
- White sidebar navigation with brand-colored icons; Quote Requests shows a **live new-request badge**.

> **Scoped CSS gotcha:** all `*.razor.css` files are concatenated into one bundle
> (`Business_Tools_Suite.styles.css`). A single malformed declaration (e.g. an unterminated
> SVG `url("...")` data URI) can silently break every rule **after** it in the bundle —
> producing a "some styles apply, some don't" layout collapse. See
> [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md).

## Navigation

`Home · Reviews · Quote Requests (badge) · Clients · Analytics · Auth/Login`

## Configuration

`appsettings.json`:

```json
"LoginAllowList": {
  "AllowedEmails": [ "owner@neweracleaning.com" ]
}
```

> Replace the placeholder email with the real authorized account(s).

## Current Limitations / Next Steps

- All data is **in-memory placeholder data** (singleton); simulated quote requests and
  manually created clients persist for the app session but **reset on restart**.
- Reviews are imaginary — a real implementation should prefer **official review APIs** over
  HTML scraping (the live site returns 403 to direct scraping).
- No public-facing **Quote Request submission form** yet (the admin side consumes that data shape today).
- Clients/quotes are not yet persisted to a **database**.
- Client Profile **review matching is heuristic** (author-name based). Once reviews carry a real
  client/customer identifier, replace `IsLikelyAuthor` with an exact key match.
