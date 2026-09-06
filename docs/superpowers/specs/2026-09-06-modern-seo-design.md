# Modern SEO pass — New Era WordPress theme

**Date:** 2026-09-06
**Scope:** `wordpress/newera-steam-cleaning/` only. The Blazor public pages are
deliberately *not* changed this time — head tags and JSON-LD are invisible in a
local preview, and the on-page changes here are small enough not to make the
preview misleading.

## Grounding

Researched against Google Search Central, September 2026:

- SEO Starter Guide — descriptive URLs, descriptive anchor text, unique titles,
  1–2 sentence descriptions, image alt text, no keyword stuffing.
- Title link best practices — unique, concise, brand at one end, no boilerplate
  repeated across pages.
- LocalBusiness structured data — `address` and `name` required; `telephone`,
  `openingHoursSpecification`, `priceRange`, `url`, `geo` recommended.
- Rich results gallery — Breadcrumb and Local business are supported. **FAQ,
  HowTo and the sitelinks search box are gone.**
- Optimizing for generative AI features (May 2026) — "structured data isn't
  required for generative AI search, and there's no special schema.org markup
  you need to add"; `llms.txt` is not used by Google Search.

## Explicit non-goals

Recorded here because each is a plausible-looking thing to add that would be
wrong, and the next person to touch this file should not have to re-derive it:

| Not doing | Why |
|---|---|
| `FAQPage` / `HowTo` | Retired from Google's rich results gallery. Markup with no consumer. |
| `SearchAction` / sitelinks search box | Deprecated. The site has no search UI anyway. |
| `aggregateRating` on the business | Self-serving review markup; unsupported on `LocalBusiness`, and a manual-action risk. Reasoning already in `inc/seo.php`. |
| `llms.txt` | Google states Search does not use it. |
| City name templated into all 7 service titles | The doorway pattern this theme was explicitly built to avoid, and the boilerplate-title pattern Google's title doc warns about. |
| `meta keywords` | Unused by Google Search. |

## Design

### 1. `inc/schema.php` (new) — one graph, two consumers

Replaces the single front-page-only `LocalBusiness` node with a connected
`@graph`. `inc/seo.php` keeps head meta and robots only.

Nodes:

- `LocalBusiness` `#business` — as today, plus `hasOfferCatalog` enumerating all
  services, and `areaServed` upgraded from bare strings to `City` objects.
- `WebSite` `#website` and `WebPage` `#webpage` — **only when no SEO plugin is
  active.** Yoast already emits both; a second copy under a different `@id` is
  the drift this theme has consistently avoided.
- `Service` — on each service page. `provider` points at `#business` by `@id`.
- `CollectionPage` + `ItemList` — on the services archive.
- `BreadcrumbList` — see below.

Under Yoast: hook `wpseo_schema_graph` to *add* the `Service` / `ItemList`
nodes into Yoast's existing graph, and keep the existing
`wpseo_schema_organization` extension. Under no plugin: emit the whole graph.

### 2. Breadcrumbs — markup derived from the rendered trail

Google requires breadcrumb markup to match the visible trail. `wp_head` runs
before `newera_page_banner()` renders, so a `wp_head` emitter would have to
*re-derive* the trail from the query and would silently disagree with the four
templates that pass a custom banner title (`page-pricing.php` renders "Simple,
Transparent Pricing" for a page titled "Pricing"; the archive renders "Our
Cleaning &amp; Restoration Services").

So: `newera_page_banner()` records the trail it actually rendered, and a
`wp_footer` hook emits the `BreadcrumbList` from that record. JSON-LD is valid
in the body, and this makes the markup structurally incapable of disagreeing
with the page. Suppressed when an SEO plugin is active.

### 3. Descriptions and the front-page title

`newera_page_description()` currently returns one ~230-character default for
every page except services. Add a `newera_page_kind()` helper (resolving page
templates *and* the live slugs `about-us` / `get-quote`, which do not match the
template hierarchy) and give each kind a 1–2 sentence description under ~155
characters.

Every claim in those descriptions is lifted from copy already on the page —
10-day guarantee, insured and bonded, 6 rooms/halls/closets, founded 2014,
service areas, free quotes. No new claims are introduced.

Titles: front page only, via `document_title_parts`. Service and interior page
titles are left as core produces them — unique already, and templating a city
into all seven is the pattern ruled out above.

### 4. On-page structure

- **Service card anchor text.** Every card links with the text "Learn more".
  Append a `screen-reader-text` span naming the service, giving descriptive
  anchor text without changing the visual design. Accessibility win too.
- **Logo images missing dimensions** — `front-page.php:172` and
  `page-about.php:31`. The file is 300×150 and the CSS is
  `max-width:300px; height:auto`, so the intrinsic size is the correct
  reservation.
- **Service featured image** — pass `loading`/`fetchpriority`/`decoding`
  explicitly, and fall back to a generated `alt` when the media library entry
  has none. Core's heuristic probably already gets the loading attributes right
  here; making them explicit removes the dependency on a threshold that has
  changed between core versions.
- **`noindex` on date and author archives** when no SEO plugin is active. Core
  already noindexes search results; Yoast handles these itself.
- **`preconnect`** to `cdn.jsdelivr.net` when the icon font is not vendored.

### 5. `inc/seo-audit.php` (new) — one consolidated admin notice

Follows the existing `newera_*_notice()` pattern, on the same restricted set of
screens. Lists only outstanding items:

- No Google Business Profile URL in `newera_social_links()`. This is the
  largest missing local signal — `sameAs` currently has Facebook, Yelp and Angi.
  A `newera_google_business_profile()` accessor is added so filling it in is a
  one-line filter.
- Services with no featured image.
- Computed titles or descriptions over length guidance.

## Verification

No PHP runtime is available on this machine, so nothing here can be executed.
Verification is structural only: the `check-theme.mjs` scanner (brace/tag
balance, undefined `newera_*` calls, missing assets) plus a JSON-LD shape check
that builds the same graph in JS from the PHP arrays. Both must be re-created —
they live in a previous session's scratchpad. This limitation is stated in the
final report rather than glossed.

Rebuild `newera-steam-cleaning.zip` afterwards, or a staging install silently
gets old code.
