# Live review tally across Yelp, Facebook and Angi

**Date:** 2026-09-06
**Status:** SUPERSEDED, same day. Not implemented — do not build from this.

> The automated tally was built and then removed in favour of a static
> "240+ reviews" plus three profile links in the hero card. Two things killed
> it: Yelp Fusion turned out to be paid-only (free tier withdrawn), and Angi and
> Facebook both refuse server-side fetches — measured 403 and 400 respectively,
> with a Chrome user agent, over both cURL and PHP streams. A datacenter host
> fares no better. Only the browser can read those pages, so no cron job on
> Nexcess can.
>
> Kept for the measurements in "Ground truth" below, which are still accurate,
> and as the record of why automation was abandoned.

## Problem

The hero card on the home page reads "5.0 — from 22 reviews". The 22 is not a
stale hardcoded figure: it is `count( $reviews )` over the testimonial quotes the
theme is carrying.

- WordPress: `inc/template-tags.php:360` → rendered at `front-page.php:92`
- Blazor preview: `Home.razor:333` → rendered at `Home.razor:99`

So the label describes the theme's own content, not the business's reputation,
and undercounts by roughly 10×. The three linked profiles hold 240 reviews.

## Ground truth (measured 2026-09-06)

| Source | Count | Rating | How it was read |
|---|---|---|---|
| Yelp | 207 | 5.0 | Owner-supplied. The public page is behind a DataDome CAPTCHA and was **not** independently verified. |
| Angi | 14 | 4.928571… | `aggregateRating` JSON-LD on the review page, read in-browser. |
| Facebook | 19 | n/a — "100% recommend" | Logged-out page text, read in-browser. |
| **Total** | **240** | **5.0** | |

Two facts drive the whole design:

- **Yelp is 86% of the number** and the only source that grows meaningfully.
  Its public page cannot be scraped; the Fusion API is the only legitimate route.
- **Facebook has no star ratings.** Its page is recommend/don't-recommend, so its
  19 count as reviews but cannot contribute to a star average.

The blended average over the star-bearing sources is
`(5.0 × 207 + 4.928571 × 14) / 221 = 4.9955`, which is 5.0 at one decimal place.
"5.0 from 240 reviews" is therefore accurate, not approximate — but only because
Facebook is excluded from the average while included in the count.

## Decisions

| Decision | Choice |
|---|---|
| Approach | Automate all three sources (option C), with per-source degradation to manual |
| Card copy | `5.0` / `from 240 reviews` — current shape, corrected count |
| Code location | `inc/review-stats.php` in the theme |
| `aggregateRating` in schema | Stays out |

### Why the theme and not a plugin

`inc/quote-handler.php` is already functionality — form handling and mail — living
in the theme. Following that precedent keeps the install steps in `README.md`
unchanged. Adding a plugin would add a fourth ordering dependency to an install
that already has a silent-failure trap (theme must be active before the WXR
import).

### Why `aggregateRating` stays out

`inc/schema.php:16-19` and `README.md:193` rejected averaging republished
third-party reviews into self-serving markup on `LocalBusiness`. Automating the
fetch does not change that reasoning — the numbers stay visible page content.

## Architecture

### Data model

A single option, `newera_review_stats`. Derived values are computed **on write**,
so the front page performs one option read and no arithmetic:

```php
array(
  'sources' => array(
    'yelp' => array(
      'count'   => 207,
      'rating'  => 5.0,
      'has_rating' => true,
      'status'  => 'ok',        // 'ok' | 'failed'
      'fetched' => '2026-09-06T14:02:11+00:00',
      'fails'   => 0,
      'pinned'  => false,
    ),
    'angi'     => array( /* same shape, has_rating => true  */ ),
    'facebook' => array( /* same shape, has_rating => false, plus 'recommend_pct' => 100 */ ),
  ),
  'total'   => 240,
  'average' => 5.0,
  'updated' => '2026-09-06T14:02:11+00:00',
)
```

`total` is the sum of every source's `count`. `average` is the count-weighted
mean over sources where `has_rating` is true, rounded to one decimal.

Source URLs come from `newera_social_links()` (`inc/company.php:146`). No second
copy of them.

### Credentials

`wp-config.php` constants, never the options table — a DB dump must not leak
them. This matches the convention `YelpReviewOptions.cs:5-7` already states.

- `NEWERA_YELP_API_KEY`
- `NEWERA_YELP_BUSINESS_ID` (default: the slug `new-era-steam-cleaning-and-restoration-sugar-land`)
- `NEWERA_FACEBOOK_PAGE_TOKEN` (optional; absent means the HTML path is used)

### Parsing is separated from fetching

Each source splits into a pure parser and a thin HTTP wrapper:

```php
newera_parse_angi_jsonld( string $html ): array|WP_Error
newera_parse_facebook_html( string $html ): array|WP_Error
newera_parse_yelp_response( array $json ): array|WP_Error
```

This is what makes the feature testable without a WordPress install or network
access: parsers run against saved fixture HTML. `New Era/Angi Reviews/
view-source_…-8435486.htm` is already a usable Angi fixture.

Fetchers return `array( 'count' => int, 'rating' => float|null, 'recommend_pct' => int|null )`
or a `WP_Error`.

| Fetcher | Method |
|---|---|
| `newera_fetch_yelp()` | Fusion `GET /v3/businesses/{id}` with `Authorization: Bearer`; reads `review_count` and `rating` |
| `newera_fetch_angi()` | `wp_remote_get` the review URL, walk every `script[type="application/ld+json"]` for the first node carrying `aggregateRating`, read `reviewCount` / `ratingValue` |
| `newera_fetch_facebook()` | Graph `?fields=rating_count` when a page token is set; otherwise `wp_remote_get` the `/reviews` URL and match `/(\d+)%\s*recommend\s*\((\d+)\s*reviews?\)/i` |

All HTTP calls use a 10-second timeout and an identifying user agent.

### Failure containment

This is load-bearing: option C was chosen knowing the Angi scrape and the
Facebook HTML path will break eventually. The requirement is that they break
*visibly* and never corrupt the live number.

1. **A failed fetch never overwrites a good value.** The stored `count` and
   `rating` persist; `status` becomes `failed` and `fails` increments.
2. **Sanity guard before any write.** Reject the result if:
   - `count` is zero or negative, or
   - `count` is below 90% of the stored count (reviews rarely vanish; a broken
     parser looks exactly like a collapse), or
   - `rating` falls outside 1–5 on a `has_rating` source.

   The drop check is skipped on the first write, when there is no stored count
   to compare against.
3. **Admin notice after 3 consecutive failures**, naming the source.
4. **Per-source pin.** Entering a value by hand sets `pinned => true` and the
   scheduled fetcher skips that source until it is unpinned.

The net effect is that each source degrades independently to manual entry.

### Schedule

Daily WP-Cron event `newera_refresh_review_stats`. Sources are fetched
independently inside the handler — one throwing or erroring must not abort the
others. Registered on `after_switch_theme`, cleared on `switch_theme`.

### Admin surface

A Settings → Reviews screen (`manage_options`). One row per source showing
count, rating, status and last-fetched time, with an editable count/rating field
and a pin checkbox. A "Refresh now" button runs the fetch immediately. Settings
save and refresh are both nonce-protected.

### Rendering

`newera_testimonial_stats()` is **replaced**, not supplemented. Its only consumer
is the hero card: `front-page.php:21` assigns `$newera_stats`, used solely at
lines 91 and 92. Once the hero reads reputation figures instead, the function has
zero callers, so keeping it would leave dead code behind.

`newera_review_stats()` takes its place, returning the figures from the option
and falling back to the seed values (240 / 5.0) when the option is unset, so a
fresh install renders correctly before the first cron run.

Changes required:

- `inc/template-tags.php:358-375` — delete `newera_testimonial_stats()`.
- `front-page.php:21` — `$newera_stats = newera_review_stats();`. The separate
  `$newera_reviews` on line 20 stays; it still feeds the featured quote and the
  testimonial grid.
- `front-page.php:91` — reads `$newera_stats['average']`.
- `front-page.php:92` — reads `$newera_stats['total']` (was `['count']`).
- `inc/schema.php:19` — the comment points readers at
  `newera_testimonial_stats()` by name. Update it to `newera_review_stats()` or
  it references a function that no longer exists.

The hardcoded `newera_stars( 5 )` on line 90 is unaffected.

### Preview parity

Per the sync rule, the Blazor preview must match or `deploy-and-launch.ps1`
silently diverges from what ships. `Home.razor` gets mirrored constants:

```csharp
// Mirrors the newera_review_stats option in the WordPress theme, which is the
// source of truth. Update both together.
private const int ReviewCount = 240;
private const double AverageRating = 5.0;
```

`Home.razor:333-334` stop deriving these from `reviews.Count` / `reviews.Average`.
The preview only needs to look right; it does not need live data.

## Testing

No PHP runtime exists on this machine, so nothing here can be executed locally.

**Can be verified locally:**
- Parser unit tests against saved fixtures (the pure-function split above).
- `check-theme.mjs` structural checks — brace/tag balance, undefined `newera_*`
  calls. Note its documented traps: strip `<!-- … -->` first, and count braces
  only inside `<?php … ?>` regions.
- Live endpoint shape for Angi and Facebook, re-read through the browser.

**Cannot be verified locally, and must be checked on staging:**
- The Yelp fetcher in full. It ships **unverified** until an API key exists.
- WP-Cron scheduling and the admin screen.
- The sanity guard and failure paths end to end.

## Risks

| Risk | Mitigation |
|---|---|
| Angi reshapes their page | Sanity guard rejects the garbage; admin notice after 3 days; pin as escape hatch |
| Facebook HTML path breaks | Same, plus the Graph token path as an upgrade |
| Yelp key never obtained | Yelp stays pinned at 207 and the site is still correct |
| Yelp Fusion display requirements | Review Yelp's terms before shipping their data |
| Scraping fragility generally | Every source degrades to manual independently |

## Out of scope

- `aggregateRating` structured data.
- Importing review *text* from any source — this is counts and ratings only.
- Google Business Profile, which is not currently linked
  (`newera_google_business_profile()` returns empty).
- Anything in the retired Blazor admin dashboard.
