# WordPress Import Runbook

How to get the ported marketing site onto `neweracleaning.com` — in the order that
avoids the failure modes that are painful to unpick afterwards.

**Package:** `wordpress/` v1.1.0 · **Time:** about ninety minutes, mostly checking
· **Updated:** 4 Sep 2026

> ### Read this first
>
> `neweracleaning.com` is a live site with 18 pages and 4 testimonial URLs, all indexed.
> Activating this theme changes the entire site's appearance the instant you click
> Activate — there is no preview step and no staged rollout. Do **steps 1 through 9 on a
> staging copy**, then push.
>
> This build deliberately reuses the URLs the live site already serves, so most of the
> work below is about clearing the old pages *out of the way* of the new content that is
> taking their addresses. Read [`REDIRECTS.md`](REDIRECTS.md) alongside this.

---

## Phase 1 — Prepare

*Nothing changes yet.*

### 1. Work on a staging copy

Your response headers point to Nexcess / Liquid Web managed WordPress, which includes
one-click staging in the client portal. Create a staging site from the current
production site and do all the installation work there.

If staging is not available, the alternative is a local copy (LocalWP, or a subdomain
like `staging.neweracleaning.com` with search engines discouraged in
**Settings → Reading**). What you must not do is install straight onto the live site
and hope — the theme swap is immediate, and visitors will see whatever state you are
mid-way through.

### 2. Take a full backup

Files and database, both, even on staging. You want a restore point that predates the
theme switch, because switching themes back does not undo imported content.

Nexcess takes automatic nightly backups, but take a manual one anyway so you know
exactly what moment you are restoring to. Confirm it completed before continuing.

### 3. Export your current titles and meta descriptions

**Do this before anything else changes.** Yoast → Tools → Import and Export → Export
settings, and separately note the title and meta description of every page. These are
already tuned for local search — today's carpet page is
*"Houston Area Carpet Cleaning Services - New Era Steam Cleaning & Restoration"* — and
if a page is replaced rather than kept, that wording is gone with no error to tell you.

While you are there, pull **Search Console → Performance → Pages**, last 16 months, and
**Links → Top linked pages**. You need both to make the decisions in step 4.

### 4. Decide what happens to each existing page

This is the step people skip, and it is the one that creates the mess.

#### 4a. The eight service pages — these must be trashed

The new service catalogue takes these exact URLs:

```
/carpet-tile-grout-steam-cleaning-services/
/carpet-tile-grout-steam-cleaning-services/carpet-cleaning/
/carpet-tile-grout-steam-cleaning-services/carpet-repairs/
/carpet-tile-grout-steam-cleaning-services/commercial/
/carpet-tile-grout-steam-cleaning-services/tile-stone-and-grout/
/carpet-tile-grout-steam-cleaning-services/upholstery/
/carpet-tile-grout-steam-cleaning-services/pet-odor-removal/
/carpet-tile-grout-steam-cleaning-services/water-damage-restoration/
```

> **Critical.** WordPress serves a real *page* in preference to a post type archive or
> single at the same address. Leave these pages published and they win the rewrite
> match, the old design keeps rendering, and the entire new catalogue is invisible.
> There is no error — it simply looks like nothing happened.

So they need **trashing, not redirecting**. The new services inherit the identical URLs,
which is the whole point of the design. Copy anything you want to keep out of them
first; the ported copy is already in the theme, but yours may have been edited since.

The theme prints an admin notice listing any that are still in the way, so you have a
check rather than a memory test.

#### 4b. The five page slugs — keeping yours is usually better

The import creates five pages using the slugs your site already uses:

`about-us` · `pricing` · `job-photos` · `guarantee` · `get-quote`

All five will collide. WordPress does not overwrite on import — it creates a second page
and appends a number, so `/about-us/` stays yours and the imported one lands at
`/about-us-2/`. The imported page still *looks* right, because the design is attached to
the template rather than the URL, which is exactly what makes this easy to miss.

**The recommended resolution is to keep your existing pages.** Every page template in
this theme carries a `Template Name:` header, which means you can attach the new design
to the page you already have:

1. Let the import run and create the `-2` duplicates.
2. Open your **existing** page — say `/about-us/`.
3. In the sidebar, set **Template** to **About**.
4. Copy across any prose you want from the imported duplicate.
5. Trash the duplicate.

Your page keeps its URL, its post ID, its Yoast title and meta description, and whatever
history it has accumulated. You get the new design. Nothing needs redirecting.

The alternative — trash yours, then rename the imported `-2` page — loses the Yoast
metadata you exported in step 3 and gains nothing.

#### 4c. The four pages with no replacement

| Page | What to do |
| --- | --- |
| `/contact-us/` | No equivalent in the new build. Check its Search Console numbers: if it earns impressions, keep it as a real page and add it to the menu. Otherwise 301 it to `/get-quote/`. |
| `/pricing/specials/` | 301 to `/pricing/`; the coupon prices are tables there now. |
| `/privacy-policy/` | Keep. Not in the import. The footer links to it automatically once it is set in Settings → Privacy. |
| `/terms-of-service/` | Keep. Not in the import. |

The four `/testimonials/…` URLs also disappear — testimonials become a non-public post
type — and need 301s. All of this is in `REDIRECTS.md`.

---

## Phase 2 — Install

*Order matters.*

### 5. Upload and activate the theme

**Appearance → Themes → Add New → Upload Theme**

Choose `newera-steam-cleaning.zip` (1.6 MB), click Install Now, then **Activate**.

Now open the site in a new tab. The homepage and all eight services should already
work — the theme carries a full copy of the content built in, so you get to look at the
finished thing before importing anything.

> **Verify.** Homepage shows the teal hero, a photo carousel, eight service cards and
> customer reviews. If it does, the theme is healthy, and the import in the next step is
> purely about making that content editable.

At this point wp-admin will show a warning listing the old service pages still sitting
on the catalogue's URLs, and possibly one about the Site Title. Both are expected here.

### 6. Import the content

**Tools → Import → WordPress → Install Now → Run Importer**

Choose `newera-content.xml` (82 KB) and upload it. On the options screen:

- Assign all posts to your own admin user.
- Leave **"Download and import file attachments" unchecked.** There are no attachments —
  the 16 photos ship inside the theme.

> **Critical.** The theme must already be active before you run this — you did that in
> step 5. The import contains services and testimonials, which are content types the
> *theme* creates. Import onto a different theme and they are filed under a type nothing
> recognises: no error, no warning, they simply never appear anywhere.

When it finishes you should have 5 pages (as `-2` duplicates), 8 services and 22
testimonials.

### 7. Clear the conflicts

Now do what you decided in step 4:

- [ ] Trash the eight old service pages (4a). The admin notice should go quiet.
- [ ] For each of the five collisions, assign the template to your existing page and
      trash the imported duplicate (4b).
- [ ] Confirm no page is left at `/carpet-tile-grout-steam-cleaning-services/` or any
      path beneath it.

### 8. Resave permalinks

**Settings → Permalinks → Save Changes**

You do not need to change anything on that screen — just click Save. This rebuilds the
URL rules so the service pages resolve. Activating the theme normally does this for you;
clicking Save costs five seconds and eliminates the single most common cause of "the
service pages give me a 404".

### 9. Walk the site

Open each of these and confirm it looks right.

- [ ] Homepage — hero, carousel, eight services, reviews, quote form
- [ ] `/carpet-tile-grout-steam-cleaning-services/` lists all eight services
- [ ] `/carpet-tile-grout-steam-cleaning-services/carpet-cleaning/` opens with the new
      design, its bullet list and a quote form below
- [ ] The other six service URLs, from the list in step 4a
- [ ] `/about-us/`, `/pricing/`, `/job-photos/`, `/guarantee/`, `/get-quote/`
- [ ] `/privacy-policy/` and `/terms-of-service/` still render acceptably
- [ ] Pricing shows three tables with teal headers, not plain unstyled tables
- [ ] Job Photos shows a twelve-image grid
- [ ] Icons appear as symbols, not empty boxes
- [ ] Navigation and footer links all go somewhere real, including Privacy Policy
- [ ] No `-2` pages remain
- [ ] On a phone: the menu opens, and pinch-zoom works
- [ ] A made-up URL like `/nonsense/` shows the styled Page Not Found

> **Note.** The theme has never been run in a browser — it was verified structurally, not
> executed. This walk-through is the first real test, so go slowly and note anything that
> looks off.

### 10. Send a test quote request

Go to `/get-quote/`, fill the form in properly and submit. Three things should happen:

1. The form is replaced by a thank-you panel.
2. A new entry appears under **Quote Requests** in the admin sidebar, with the phone,
   email and service in the list columns.
3. An email arrives at the address in **Settings → General**, with the customer set as
   Reply-To.

Then submit again with the email field left blank, and confirm you get a readable error
rather than a blank page or a lost submission.

> **Careful.** If items 1 and 2 work but no email arrives, the form is fine and mail
> delivery is the problem — that is step 11. Requests are always saved to the database
> first, so nothing is ever lost when mail fails. Nobody is simply told about them.

---

## Phase 3 — Go live

### 11. Make email reliable

WordPress sends mail through the web server by default, which on shared hosting
frequently lands in spam or is dropped outright. Quote requests are the site's revenue
path, so this is worth doing properly rather than hoping.

Install an SMTP plugin (WP Mail SMTP, Post SMTP, or similar) and point it at a real
mailbox or a sending service. Then repeat the step 10 test and confirm delivery to an
address you can actually check — ideally not on the same domain, so you are testing more
than local loopback.

Consider also setting a dedicated recipient rather than the site admin address. The theme
exposes a `newera_quote_recipient` filter for that.

### 12. Set up the redirects

Six URLs need a 301. `REDIRECTS.md` has the list, a ready-made CSV for the Redirection
plugin, nginx rules if you would rather not add a plugin, and a verification script.

No redirect plugin is installed today, so this needs adding — unless Yoast Premium is
licensed, in which case its redirect manager will do.

### 13. Settle the SEO configuration

- **Titles and descriptions.** Walk the pages in Yoast. For pages you kept, the existing
  title and description are still attached — improve them deliberately rather than
  replacing them. For the eight services, which are new posts, write titles in the style
  of the ones you exported in step 3: they lead with the service and the city, not just
  the service.
- **Site Title.** Settings → General must match the business name the theme renders, or
  the admin notice will tell you it does not. Both must match the Google Business Profile.
- **Services in the sitemap.** Yoast → Settings → Content Types → Services, confirm it is
  set to appear in search results.
- **Remove the development block.** Settings → Reading → uncheck "Discourage search
  engines", and check Yoast's site-wide indexing switch too. Verify from outside:
  `curl -s https://neweracleaning.com/ | grep robots` should show nothing saying noindex.
- **Revisit the 403 rule.** The site returns 403 to modern Chrome and Edge user agents
  arriving from datacentre IPs — reproducible as of 3 Sep 2026: Chrome 131 gets 403,
  Googlebot and Firefox get 200. It does not block real customers, but it blocks
  Lighthouse, PageSpeed, uptime monitors, link-preview fetchers and Search Console's Live
  Test, in the exact week you most need them. Open a Nexcess ticket with that symptom.

### 14. Push staging to live

Use the host's staging-to-production push. Before you do, take a fresh backup of the live
site — the push overwrites it.

Immediately afterwards, repeat the step 9 walk-through and the step 10 form test on the
live domain, then run the verification script in `REDIRECTS.md`. Staging and production
differ in ways that matter here: caching, SSL, mail routing and the 403 rule are all
environment-specific, and the form test in particular is not proven until it has passed
on the real domain.

> **Careful.** If a page cache or CDN sits in front of the site, exclude URLs containing
> `newera_quote` from caching. The form's confirmation is delivered through a redirect
> carrying a one-time token; if that response gets cached, visitors submit the form and
> see a stale page instead of their thank-you.

### 15. Tell Google

- Resubmit `sitemap_index.xml` in Search Console.
- Run URL Inspection on the homepage and two or three service pages.
- Watch Coverage for a week. A handful of "Crawled – currently not indexed" on the
  retired URLs is normal while the 301s are picked up; a rise in 404s is not, and means
  a redirect is missing.

---

## Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| Service pages 404 | Rewrite rules are stale. Settings → Permalinks → Save Changes. |
| Service URLs show the *old* design | An old service page is still published on that URL. Trash it — see step 4a. The admin notice lists them. |
| The catalogue page shows one service, not all eight | A page is sitting on the catalogue base URL. Trash it, then resave permalinks. |
| Pages landed at `/about-us-2/` | Expected. Resolve as in step 4b: assign the template to your original page, trash the duplicate. |
| Services and testimonials missing after import | The theme was not active when you imported. Activate it, delete the partial import, and run the importer again. |
| Icons show as empty boxes | The vendored font is missing from `assets/vendor/bootstrap-icons/`. Re-upload the theme zip. |
| Form submit gives 404 or a blank page | A security plugin or firewall is blocking `admin-post.php`. Allow it. |
| Form works but no thank-you appears | The redirect is being cached. Exclude `newera_quote` URLs from the page cache. |
| Requests saved but no email | Mail delivery, not the form. Set up SMTP — step 11. |
| Two sets of meta tags in the page source | Should not happen — the theme stands down when Yoast is active. If it does, note which SEO plugin is running. |
| Admin warns the Site Title does not match | Settings → General. Yoast builds titles and schema from it; the theme renders its own name. They must agree. |
| Homepage shows blog posts | Check Settings → Reading. Either setting works, but a static front page must not point at one of the five template pages. |

---

## How to undo it

**Appearance-only rollback.** Activate your previous theme. The site returns to its old
look immediately. Imported pages remain but render in the old theme; services and
testimonials disappear from wp-admin because the theme that defined them is no longer
active. Nothing is deleted — reactivating brings it all back.

Note that trashed pages stay trashed. If you got as far as step 7, restore them from the
Trash as well.

**Full rollback.** Restore the backup from step 2. This is the only clean way to remove
imported content and the theme in one move, which is exactly why step 2 is not optional.

---

## What's in the package

| File | What it is |
| --- | --- |
| `newera-steam-cleaning.zip` | The theme. Upload this. 1.6 MB, includes all 16 photos and the icon font. |
| `newera-content.xml` | The content import. 5 pages, 8 services, 22 testimonials. |
| `REDIRECTS.md` | Every live URL, what happens to it, and how to implement the six 301s. |
| `newera-steam-cleaning/` | The same theme unzipped, for reading and editing. |
| `README.md` | Condensed version of this guide, kept with the code. |

Once installed, here is where each kind of content is edited:

| To change | Go to |
| --- | --- |
| Service copy, bullets, icons | Services → edit a service |
| Coupon prices | Pages → Pricing (ordinary table blocks) |
| Customer reviews | Testimonials |
| About and Guarantee wording | Pages → the relevant page |
| Business name, phone, hours, address, service area | Theme file `inc/company.php` |
| The services URL base | `newera_services_base()` in `inc/post-types.php` |
| Job photos and carousel slides | Theme file `inc/template-tags.php` |

Each page template renders its own layout and drops the page's editor content into the
prose slot, so rewriting words in the block editor cannot break the design. Leave a page
empty and it falls back to the copy ported from the original build.

---

The Business Tools Suite admin dashboard and the review scraper tools are not part of
this package and are not installed by it. The .NET source in the repository was left
untouched.

Design rationale for the port is in
`docs/superpowers/specs/2026-09-03-wordpress-port-design.md`.
