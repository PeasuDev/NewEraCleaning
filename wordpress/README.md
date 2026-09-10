# New Era Steam Cleaning — WordPress package

The public marketing site, converted from the Blazor rebuild in this repo into
something WordPress can run. Three files matter:

| File | What it is | Where it goes |
| --- | --- | --- |
| `newera-steam-cleaning.zip` | The theme: templates, styles, photos, quote form | Appearance → Themes → Add New → Upload |
| `newera-content.xml` | The page and service copy, as editable content | Tools → Import → WordPress |
| `REDIRECTS.md` | What happens to every URL the live site serves | Read before launch |

`newera-steam-cleaning/` is the same theme unzipped, for reading and editing.

> **Doing the install?** Follow [`IMPORT-GUIDE.md`](IMPORT-GUIDE.md) instead of this file.
> It is the full runbook: staging, backups, the page audit that has to happen before you
> import, a verification checklist, and troubleshooting. What follows here is the short
> version for people reading the code.

The Business Tools Suite admin dashboard is **not** part of this package, and
neither are the Yelp/Angi scraper tools. Nothing in the `.NET` side of the repo
was modified or deleted.

---

## The URLs are deliberate

This theme is built around the URLs `neweracleaning.com` already serves, not tidier
ones. Services live under **`/carpet-tile-grout-steam-cleaning-services/`**, and the
pages use `about-us`, `get-quote`, `pricing`, `guarantee`, `job-photos`.

That path is not pretty. It is the path eight service pages have ranked on since 2014,
and keeping it means 16 of the 22 indexed URLs need no redirect at all. Only six do —
see `REDIRECTS.md`.

The base is defined once, in `newera_services_base()` (`inc/post-types.php`). Everything
that builds a service URL reads it, so changing it moves the whole catalogue — but do
not, without a redirect plan.

**Two pages cannot coexist with it.** WordPress serves a real page in preference to a
post type archive at the same address, so the eight existing service pages have to be
moved to the Trash before the imported services can claim those URLs. They need deleting,
not redirecting — the new services take the identical addresses. The theme prints an
admin notice listing any that are still in the way.

---

## Install

**Order matters.** Activate the theme first. The import file contains services
and testimonials, which are custom post types the *theme* registers — import
first and WordPress files them under a type nothing knows about, and they
silently do not appear.

1. **Upload the theme.** Appearance → Themes → Add New → Upload Theme →
   `newera-steam-cleaning.zip` (2.5 MB) → Install → **Activate**.

   At this point the whole site already works. Every page renders using copy
   built into the theme, so you can look before importing anything.

2. **Import the content.** Tools → Import → WordPress (install the importer if
   prompted) → Run Importer → choose `newera-content.xml`.
   - Assign posts to your admin user.
   - Leave "Download and import file attachments" **unchecked** — there are no
     attachments; the photos ship inside the theme.

3. **Clear the URL conflicts.** Trash the eight old service pages, and decide what
   happens to the five page collisions. The guide walks through both.

4. **Refresh permalinks.** Settings → Permalinks → Save Changes. This makes the
   service URLs resolve. Activating the theme normally does this automatically;
   saving again costs nothing and rules out the most common "service pages 404" cause.

5. **Check the quote form.** Submit a test request from `/get-quote/`. It should
   land in **Quote Requests** in the admin sidebar and email the address in
   Settings → General.

---

## After installing

**The homepage needs no page.** It is the theme's `front-page.php`, and
WordPress uses it whether Settings → Reading is set to "Your latest posts" or to
a static page. There is deliberately no "Home" page in the import.

**Navigation** falls back to the correct menu automatically, and resolves each page by
its template rather than a hard-coded slug — so attaching the About template to a page
at any address keeps the menu correct. To take control, build a menu at
Appearance → Menus and assign it to "Primary Navigation".

**Set the Site Title to match.** Yoast builds page titles and its Organization schema
from Settings → General → Site Title, while the theme renders
`newera_business_name()`. If those disagree, search results show one name and the page
shows another. The theme raises an admin notice when they drift.

**Email delivery.** `wp_mail()` on shared hosting is unreliable and quote requests
are the site's revenue path. Submissions are always stored in the database, so
nothing is lost if mail fails — but nobody gets told about them, which looks
exactly like an empty inbox. Confirm a test submission actually arrives.

`inc/mail.php` reads the settings below from `wp-config.php`, so no credential
lands in the theme. Every one of them is optional; with none defined the theme
behaves like stock WordPress and notifies the Settings → General admin email.

### Production — recipient pinned, transport unconfigured

`neweracleaning.com` defines exactly one of these constants:

```php
/* Quote leads go to the business inbox regardless of what the admin email says. */
define( 'NEWERA_QUOTE_RECIPIENT', 'newerasteamclean@gmail.com' );
```

**Pinning it changes nothing today.** That is already the Settings → General address, so
the default would deliver to the same inbox. It is there for later: a change to the admin
email, made for some unrelated reason by someone who has never read this file, cannot
silently redirect customer leads.

The cost is a second source of truth. **Keep the two addresses equal** — the admin email
still needs to be right, because every *other* WordPress notification uses it.

So that the pinning is not invisible, **Settings → General carries a read-only
"Quote notifications" readout** (`inc/settings.php`) showing the address mail would go to
right now and where that came from — the constant, the admin email above it, or a filter
some plugin or snippet has added. It resolves by running the same
`newera_quote_recipient` filter the send path runs, so it cannot report one thing while
the site does another, and it calls out the case that would otherwise pass silently: a
constant defined with a typo, which `is_email()` discards before falling back to the admin
address.

**The transport, by contrast, is deliberately left alone.** Notifications go out as
`WordPress <wordpress@neweracleaning.com>` through the host's mailer, which is stock
WordPress behaviour: core supplies the literal name `WordPress` and builds the address
from the site URL, and both of the theme's sender filters stand down because
`newera_smtp_configured()` is false.

The owner reviewed this and accepted it. Worth recording *why* it is a live decision
rather than an oversight, so nobody "fixes" it without cause — and what would change it:

- **The sender address needs no setup.** `wordpress@neweracleaning.com` is what WordPress
  falls back to. There is no such mailbox and none is required while SMTP is off.
- **The risk is deliverability, not identity.** Unauthenticated mail from a shared host
  to a Gmail recipient is the classic spam-file. It fails invisibly: the form works, the
  request is stored, and the absence of email looks like an absence of customers.
- **The display name is coupled to SMTP.** `newera_mail_from_name()` swaps `WordPress`
  for the Site Title only when SMTP is configured. Keeping "WordPress" and gaining
  authenticated delivery are not both available without filtering the name back.

If quote mail starts landing in spam or stops arriving, this is the fix:

```php
/* Route mail through authenticated SMTP instead of the host's mailer. */
define( 'NEWERA_SMTP_HOST', 'mail.neweracleaning.com' );
define( 'NEWERA_SMTP_PORT', 465 );
define( 'NEWERA_SMTP_SECURE', 'ssl' );   // 'ssl' on 465, 'tls' on 587
define( 'NEWERA_SMTP_USER', 'wordpress@neweracleaning.com' );
define( 'NEWERA_SMTP_PASS', '<mailbox password>' );
define( 'NEWERA_SMTP_FROM', 'wordpress@neweracleaning.com' );

/* NEWERA_QUOTE_RECIPIENT belongs above, not here — it is unrelated to transport. */
```

> **That turns `wordpress@neweracleaning.com` into something that must actually exist.**
> As a fallback sender it is fine imaginary; as an SMTP account it is not — authentication
> needs a real mailbox. Create it under Site Tools → Email → Accounts, and take the exact
> host and port from that account's Mail Configuration panel rather than trusting the
> values above. `mail.<domain>` on 465 is the normal SiteGround shape, but confirm it.

Once that mailbox exists, bounce notices and out-of-office replies start landing in it,
and nothing routinely reads it. The customer's address is on `Reply-To` so answering a
quote still works, but a bouncing lead fails silently — check it if a customer says they
never heard back.

### Staging

Gmail is fine for a staging copy, where the point is only to prove mail leaves the
server:

```php
/* Staging only. Never copy this block to production. */
define( 'NEWERA_QUOTE_RECIPIENT', 'you@example.com' );
define( 'NEWERA_SMTP_HOST', 'smtp.gmail.com' );
define( 'NEWERA_SMTP_PORT', 587 );
define( 'NEWERA_SMTP_SECURE', 'tls' );
define( 'NEWERA_SMTP_USER', 'you@gmail.com' );
define( 'NEWERA_SMTP_PASS', 'xxxxxxxxxxxxxxxx' );
define( 'NEWERA_SMTP_FROM', 'you@gmail.com' );
```

> **`NEWERA_QUOTE_RECIPIENT` replaces the recipient, it does not add one.** Production
> pins it to the business inbox on purpose — see above. The danger is this *staging*
> value reaching live, where it would replace the business address with a personal one
> and divert real customer leads while the form still looks perfectly healthy. To notify
> more than one inbox, give it a comma-separated list with the business address included,
> since it is replaced rather than added to.

> **Do not let the staging block reach production.** A staging-to-live push copies
> `wp-config.php` edits along with everything else, and the result is every quote
> notification going out as a personal Gmail account and landing in a personal inbox.
> Delete the block after the push, or keep the values out of `wp-config.php` entirely on
> staging by putting them in a separate file that is not promoted. The in-place cutover
> avoids this by never pushing `wp-config.php` at all.

For Gmail, `NEWERA_SMTP_PASS` must be an **App Password**, not the account
password — Google rejects account passwords over SMTP. Generate one at
<https://myaccount.google.com/apppasswords>; the option only appears once
2-Step Verification is enabled on the account. Google shows the 16 characters
once, and its display spaces can be omitted. A SiteGround mailbox password is an
ordinary password and needs none of this.

### Either configuration

`inc/mail.php` aligns the From header with the account actually doing the sending. A
mismatched From is a reliable way to fail SPF/DMARC, and Gmail rewrites one regardless.
The customer's address stays on `Reply-To`, so replying from the notification reaches
them.

With `WP_DEBUG` on, a refused send is written to the PHP error log rather than
disappearing.

---

## What you can edit, and where

| Content | Where |
| --- | --- |
| Service copy, taglines, bullet lists, icons | Services → edit a service |
| Coupon prices | `inc/pricing.php`, or a table block on Pages → Pricing, which overrides it |
| Customer reviews | Testimonials |
| About and Guarantee copy | Pages → the relevant page |
| Business name, phone, hours, address, service area, social links | `inc/company.php`, or the `newera_*` filters it defines |
| Google Business Profile URL | **Settings → General**, or the `newera_google_business_profile` filter — feeds `sameAs`, and clears the wp-admin checklist item |
| The services URL base | `newera_services_base()` in `inc/post-types.php` |
| Job photos and carousel slides | `inc/template-tags.php`, or the `newera_gallery_photos` / `newera_carousel_slides` filters |

Each page template renders its own layout and drops the page's editor content
into the prose slot, so rewriting the words in Gutenberg cannot break the design.
Leave a page empty and it falls back to the copy ported from the Blazor source.

All five page templates carry `Template Name:` headers, which means they can be
attached to a page at any URL — that is what makes it possible to keep the existing
pages and their accumulated Yoast metadata rather than importing replacements.

---

## Notes

**Icons.** Bootstrap Icons 1.11.3 is vendored into
`assets/vendor/bootstrap-icons/` and the font is preloaded, so no request leaves the
site. If that directory is ever removed the theme falls back to jsDelivr automatically,
with no code change — but the local copy is the intended configuration.

**SEO.** Titles, meta descriptions and canonicals are emitted **only when no SEO
plugin is active**. The live site runs Yoast, which keeps owning them. `inc/seo.php`
holds those plus robots; `inc/schema.php` holds structured data; `inc/seo-audit.php`
is the wp-admin checklist described below.

Structured data works the same way. Rather than adding a second business entity beside
Yoast's `Organization` node, the theme promotes that node to `LocalBusiness` through
`wpseo_schema_organization` and adds the local-business properties Yoast has no field
for — telephone, opening hours, service area, address, price range, and an
`OfferCatalog` of the eight services. `wpseo_schema_graph` adds a `Service` node on each
service page and an `ItemList` on the catalogue, both pointing `provider` at whichever
`@id` Yoast's Organization node actually uses. Without Yoast the theme emits the
equivalent `@graph` itself, adding `WebSite` and `WebPage` only when no SEO plugin at
all is present.

`BreadcrumbList` is emitted in the **footer**, not the head. Google requires breadcrumb
markup to match the visible trail, and the trail is not known until
`newera_page_banner()` has rendered — four templates pass a banner title that differs
from the post title. So the banner hands its trail to `newera_breadcrumb_trail()` as it
draws it and the markup is built from that record. JSON-LD is valid anywhere in the
document, and the two cannot drift.

Deliberately absent, so it does not get "fixed" back in later:

| Not there | Why |
| --- | --- |
| `aggregateRating` | The testimonials are republished from Yelp and Angi. Averaging them into a rating for ourselves is self-serving review markup — unsupported on `LocalBusiness`, and a manual-action risk. The stars stay visible page content, which is where reviews belong. |
| `FAQPage`, `HowTo` | Both withdrawn from Google's rich results gallery. Markup with no consumer. |
| `SearchAction` / sitelinks search box | Deprecated, and there is no search UI to describe. |
| `llms.txt` | Google states Search does not use it. |
| A city templated into all seven service titles | Repeated boilerplate, which Google's title guidance warns against, and the doorway pattern this site was built to avoid. |

The Blazor source declared the business as schema type `HousePainter`. That was
wrong for a cleaning company and is corrected to `LocalBusiness`.

**The wp-admin SEO checklist.** `newera_seo_notice()` reports what code cannot supply
for itself, on the Dashboard, Themes, Pages, Services, Reading and Import screens: the
site-wide noindex switch being left on, **Yoast set to hide services or pages from search
results**, **two SEO plugins active at once**, a missing Google Business Profile URL,
services with no featured image, and summaries that are blank or too long for a snippet.
Each of those fails silently — the page still renders and the markup is still valid —
which is why there is a notice at all.

The Business Profile URL is the one worth doing first. For a service-area business the
Profile is what ranks in the local map pack, and `sameAs` is what ties it to this site.
Paste it into **Settings → General** and it flows into the schema and
the checklist stops asking.

**Robots.** The theme emits no robots tag of its own. WordPress owns it through the
`wp_robots` filter, which is what Settings → Reading → "Discourage search engines"
drives — so that setting behaves as expected and per-page noindex works. Two exceptions,
both added through that same filter rather than printed over the top of it:

- The quote confirmation URL is `noindex, follow`, so a form submission cannot create an
  indexable duplicate of every service page.
- Date and author archives are `noindex, follow` **when no SEO plugin is active**. A
  marketing site with no blog still exposes them, listing the same handful of posts as
  each other under URLs nothing links to. Skipped under Yoast and its peers, which have
  their own settings for exactly these archives — a theme quietly overriding a choice
  made in the plugin's UI is worse than leaving it alone. Search results need no rule
  here; core already noindexes them.

**Sitemaps.** On an install with no SEO plugin these are core's `wp-sitemap.xml`. The
live site runs Yoast, which disables core's and serves **`sitemap_index.xml`** instead —
that is the URL to submit in Search Console, and `wp-sitemap.xml` 301s to it. Services
appear in either because the post type is registered `public`; testimonials and quote
requests do not, because they are not.

Yoast can still exclude them: **Yoast → Settings → Content types → Services** has its own
"Show Services in search results?" switch, and turning it off drops all eight from the
sitemap and the index while everything else looks healthy. The archive has a separate
switch again. `newera_seo_notice()` checks both and says so in wp-admin.

**Images.** Twenty-five files, 2.2 MB — content photos at 800 px on the long edge, three
carousel slides at 1200×500, with filenames that describe what they show. They ship inside the theme rather than the Media Library,
which means WordPress cannot generate responsive sizes for them — if that matters later,
move them into the Media Library and render with `wp_get_attachment_image()`.

**Caching.** The quote form confirmation is delivered via a redirect carrying a
one-time token. If a page cache serves the site, exclude URLs containing
`newera_quote` so visitors see their confirmation rather than a cached page.

**This has not been executed.** No PHP runtime was available where the port was
built, so the theme was verified structurally — brace and tag balance across all
23 PHP files, every helper defined, every referenced asset present, the import file
parsed as XML, and its slugs cross-checked against `inc/services.php`. It has not been
loaded in a browser. Walk the pages after installing.
