# Porting the New Era public site to WordPress — Design

**Date:** 2026-09-03
**Scope:** Convert the Blazor public marketing site into an installable WordPress theme plus a content import file. The Business Tools Suite admin dashboard is out of scope and is not published.

---

## Goal

`neweracleaning.com` already runs WordPress (nginx / Nexcess, Yoast SEO). This repo holds a newer, better-structured rebuild of the public marketing site written in Blazor, which WordPress cannot run. The deliverable is therefore a translation, not a deployment: a theme the owner uploads through **Appearance → Themes → Add New**, and a WXR file imported through **Tools → Import**.

## What ships and what does not

| Source | Destination |
| --- | --- |
| `Pages/Public/*.razor` (8 page types) | PHP templates |
| `Layout/PublicLayout.razor` | `header.php` + `footer.php` |
| `wwwroot/public.css` (1,224 lines) | `style.css`, nesting flattened |
| `wwwroot/img/*` + logo | `assets/img/` |
| `ServiceCatalog.cs` | `newera_service` posts + PHP fallback |
| Yelp/Angi reviews in `PlaceholderDataService` | `newera_testimonial` posts + PHP fallback |
| `SeedPriceTables()` | Pricing page content as core table blocks |
| `QuoteForm.razor` | PHP handler + `newera_quote` post type |
| **Business Tools Suite admin** (Reviews, Analytics, Quote Requests, Clients) | **Not ported** |
| **`tools/YelpScraper`, `tools/ReviewImageExtractor`** | **Not ported** |

The .NET source stays in the repo untouched. Nothing is deleted — this working copy is not under version control, so removals would be unrecoverable.

## Architecture

### Data layer with fallbacks

Three accessors sit between the templates and the content. Each returns edited WordPress content when it exists and falls back to the values ported from C# when it does not:

- `newera_get_services()` → `newera_service` posts, else `inc/services.php`
- `newera_get_testimonials()` → `newera_testimonial` posts, else `inc/testimonials.php`
- Pricing → the Pricing page's table blocks, else `inc/pricing.php`

This makes the theme render correctly the moment it is activated, before the import has run. The import then converts that same content into something editable in wp-admin. It also means a botched import can be deleted without leaving a blank site.

### Routing

Services are a custom post type registered with `rewrite => ['slug' => 'services']` and `has_archive => 'services'`, which reproduces the Blazor routes exactly: `/services/` for the index and `/services/carpet-cleaning/` for each detail page. The archive template is the services index, so the import must **not** create a page with the slug `services` — two things cannot own one path.

The homepage is `front-page.php`, which WordPress uses for the front page whether or not a static page is assigned. The hero, carousel, and section rhythm are deliberate layout that a block editor would flatten, so they stay in PHP; the parts that change (services, testimonials, phone number, years of experience) come from the data layer.

Interior pages render their designed chrome in PHP and drop `the_content()` into the prose slot, falling back to the ported copy when the page is empty. The owner edits words in Gutenberg without being able to break the layout.

### Quote form

`QuoteForm.razor` posted into an in-memory service that no longer exists. The replacement is self-contained: a nonce-protected POST to `admin-post.php`, validated and sanitized, which stores a `newera_quote` post and emails the site admin, then redirects back to the originating page with a success flag. A honeypot field absorbs the obvious bots. Submissions are visible in wp-admin under **Quote Requests** with columns for name, phone, email, and service.

### SEO

`SeoHead.razor` and the homepage `<HeadContent>` become `inc/seo.php`, which **stands down entirely when Yoast, Rank Math, AIOSEO, or SEOPress is active** so the site never emits two canonicals or two Open Graph blocks. The live site runs Yoast, so in practice the plugin wins and this code is a safety net for a site without one.

The JSON-LD is emitted separately and always, because it is additive. The Blazor version declared the business as `HousePainter`, which is wrong for a carpet cleaner; the port corrects it to `LocalBusiness` with the `HomeAndConstructionBusiness` type and keeps the service area, hours, and phone.

The port also preserves three fixes the Blazor rebuild had already made over the live site, all flagged in `docs/neweracleaning-website-analysis.md`: exactly one `<h1>` per page, a clean `h1 → h2 → h3` order, and a viewport tag without `user-scalable=no`.

### Icons

The Blazor app pulled Bootstrap Icons 1.11.3 from jsDelivr. The theme enqueues a local copy from `assets/vendor/bootstrap-icons/` when present and falls back to the same CDN URL when it is not, so the markup stays identical either way and self-hosting is a drop-in later. Vendoring the binary font files is not something this port can do offline.

### Images

Job photos, carousel slides, and the logo ship inside the theme and are referenced with `get_theme_file_uri()`. They are not imported into the Media Library: WXR attachments are fetched over HTTP at import time from a URL that is not known until the site exists, which is the single most common way these imports fail. The gallery and carousel lists are filterable so they can be repointed at Media Library images later.

## Deliverable

```
wordpress/
├── newera-steam-cleaning/      theme source, for review
├── newera-steam-cleaning.zip   upload this
├── newera-content.xml          import this
└── README.md                   install order and post-install checklist
```

Install order matters: activate the theme first so the `newera_service` and `newera_testimonial` post types exist, then run the import, or the imported posts land as an unregistered type and stay invisible.

## Verification

No PHP runtime is available in this environment, so the theme cannot be executed here. Checks performed instead:

- brace/paren balance and `<?php` tag pairing across every PHP file
- WXR parsed as XML and asserted well-formed, with post counts and slugs matching the C# source
- flattened CSS checked for brace balance and rule count against the original
- every asset referenced by a template confirmed present in `assets/`

Running the site is left to the owner; the README lists what to click and what should appear.

## Out of scope

Rebuilding the admin dashboard as a WordPress plugin, live review scraping, and migrating the existing live site's pages or URL structure. If the new theme replaces the current one, redirects from any retired URLs are a separate task.
