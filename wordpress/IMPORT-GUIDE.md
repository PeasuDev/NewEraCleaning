# WordPress Import Runbook

How to get the ported marketing site onto `neweracleaning.com` — in the order that
avoids the failure modes that are painful to unpick afterwards.

**Package:** `wordpress/`, theme v1.4.0 · **Time:** about ninety minutes, mostly checking
· **Updated:** 10 Sep 2026

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

The site is on **SiteGround** — Speed Optimizer and Security Optimizer are installed and
there is a "Purge SG Cache" control in the admin toolbar. Use Site Tools → WordPress →
Staging to create a staging copy from production, and do all the installation work there.

> An earlier version of this guide said Nexcess / Liquid Web. That was wrong. If you find
> any other reference to Nexcess in these files, it is a leftover — the host is SiteGround.

If staging is not available on your plan, the alternative is a local copy (LocalWP, or a
subdomain like `staging.neweracleaning.com` with search engines discouraged in
**Settings → Reading**). What you must not do is install straight onto the live site
and hope — the theme swap is immediate, and visitors will see whatever state you are
mid-way through.

### 2. Take a full backup

Files and database, both, even on staging. You want a restore point that predates the
theme switch, because switching themes back does not undo imported content.

SiteGround takes automatic daily backups, and **Duplicator is already installed** on the
site, so you have two routes. Duplicator is one of the fifteen plugins behind on updates —
update it before you rely on it. Either way take a manual backup so you know exactly what
moment you are restoring to, and confirm it completed before continuing.

### 2b. Update the plugins, before anything else changes

Twenty-nine plugins are installed, twenty-seven active, and **fifteen are behind on
updates** — including ACF PRO, Duplicator, MonsterInsights and Yoast. Update them on
staging *before* the theme switch. Doing it afterwards means any breakage has two possible
causes instead of one.

While you are in there, **uninstall All in One SEO.** It is installed but deactivated, and
reactivating it — which someone will eventually do while troubleshooting — puts two SEO
plugins on the site at once. The theme now raises a notice if that happens, but not having
it installed is better. Its companion, Broken Link Checker by AIOSEO, is a separate plugin
and can stay.

### 3. Export your current titles and meta descriptions

**Do this before anything else changes.** Yoast → Tools → Import and Export → Export
settings. These are already tuned for local search — today's carpet page is
*"Houston Area Carpet Cleaning Services - New Era Steam Cleaning & Restoration"* — and
if a page is replaced rather than kept, that wording is gone with no error to tell you.

> **The eight services are already handled.** `newera-content.xml` now carries
> `_yoast_wpseo_title` and `_yoast_wpseo_metadesc` for each one, copied verbatim from what
> the live site serves, so the trash-and-replace in step 4a no longer loses them. You do
> not need to retype anything for the services. Two of them —
> `pet-odor-removal` and `water-damage-restoration` — carry the shorter
> "New Era Cleaning" brand name on the live site; that was preserved deliberately rather
> than corrected, so the migration changes nothing on its own. Fix those two afterwards,
> as a separate edit, where the effect can be seen in isolation.
>
> `stone-travertine-restoration` is new and has a freshly written pair.

**Search Console has been read** (16 months to 5 Sep 2026) and the step 4 decisions are
already made below. The findings that drove them:

- Nothing being removed earns meaningful traffic. `/contact-us/` has 1 click in sixteen
  months, `/pricing/specials/` has 1.
- **All ten external backlinks point at the home page.** No service page and no
  testimonial page has any, so nothing being trashed carries link equity.
- The two testimonial pages that look busy in the Pages report — 31 and 26 clicks at
  position 7 — rank only for the literal `xxxx` placeholder left in their titles. Every
  query is a variant of it. Redirect them; there is no customer behind those clicks.
- Baseline to compare against after launch: **21 pages indexed, 0 404s.**

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

> **Step 4 above assumes the old content is prose in the editor. On three pages it is
> not.** Advanced Custom Fields PRO is running with three field groups attached to Pages —
> **Home** (7 fields), **Pricing Tables** (4) and **Gallery** (1). That content lives in
> custom fields, and the new templates only read `post_content`. Attaching a template
> keeps the URL and the Yoast metadata; it does not carry the fields across, and nothing
> errors when it doesn't.
>
> - **Pricing** is the one that matters — it is the second most-clicked page on the site,
>   and its coupon prices are ACF values the theme does not read. **The prices in
>   `inc/pricing.php` are the ones to publish** (confirmed with the owner, 7 Sep 2026).
>   They also match the live ACF values exactly as of 6 Sep, so nothing visible changes
>   at launch.
>
>   **This was a silent failure until 7 Sep 2026.** `page-pricing.php` used to render the
>   seeded tables only when the page was *empty*. The existing `/pricing/` page is not
>   empty — it holds a 719-character intro paragraph, with the prices themselves in ACF.
>   So attaching the template to it rendered the intro, skipped the tables, and published
>   a pricing page **with no prices on it**. No error, no warning.
>
>   The template now tests whether the content contains a table rather than whether it is
>   empty, so the seeded tables render alongside any prose the page carries. Nothing needs
>   doing for this to work. The theme also prints its own intro above the tables, so the
>   existing page's intro is now duplicated — clearing the page's editor content gives the
>   tidiest result, and is optional.
>
>   From now on prices are edited in `inc/pricing.php`, or as a table block on the Pricing
>   page, which overrides the seeded tables when present. **Do not edit them in ACF** —
>   nothing reads those fields once the theme is switched.
> - **Job Photos** is the same shape — the gallery is ACF, and the theme ships a
>   hard-coded set of twenty. **This one has been checked and needs no action.** The live
>   page serves 27 files, but seven are duplicates of shots already in the set: the
>   `nw-era-img5`–`img8` banner crops, `nw-era-img8-1` (byte-identical to `nw-era-img8`)
>   and `neweracleaning-img15` (a letterboxed copy of the travertine pair). That leaves
>   20 unique images, which is exactly what the theme carries. Re-verified against the
>   live page on 7 Sep 2026 — still 27 files, unchanged. Only revisit this if new work is
>   added to the live gallery before you switch.
> - **Home** is moot: `front-page.php` replaces that page's rendering entirely.

#### 4c. The four pages with no replacement

| Page | What to do |
| --- | --- |
| `/contact-us/` | **Decided: 301 it to `/get-quote/`.** 1 click and 168 impressions in sixteen months, average position 31 — it is not quietly earning brand or near-me searches. Redirecting it is also what makes the Contact Form 7 stack removable. |
| `/pricing/specials/` | 301 to `/pricing/#coupon-prices`; the coupon prices are tables there now. |
| `/privacy-policy/` | Keep. Not in the import. The footer links to it automatically once it is set in Settings → Privacy. |
| `/terms-of-service/` | Keep. Not in the import. |

The four `/testimonials/…` URLs also disappear and need 301s. All of this is in
`REDIRECTS.md`.

> **Those four have a sequencing trap.** The `testimonials` post type is registered by the
> *current* theme — ACF registers no post types and no plugin provides one — so the moment
> you activate the new theme it deregisters. The posts stay in the database but vanish
> from wp-admin, and the URLs stop resolving immediately.
>
> So: **copy anything worth keeping out of them before step 5**, and have their 301s live
> at the moment of the switch rather than added afterwards in step 12. Otherwise those
> URLs serve 404s for however long the gap is.
>
> **The copy is done** — see [`RESCUED-TESTIMONIALS.md`](RESCUED-TESTIMONIALS.md), captured
> 7 Sep 2026. Worth knowing what it found: all four reviews are genuinely unique, appearing
> in neither `inc/testimonials.php` nor `newera-content.xml`, so activating the theme would
> have destroyed them outright. Two of the four are also word-for-word identical, making
> three distinct reviews across four URLs. They are **not** in the package — republishing
> them is a separate decision, and that file sets out what it would take.

---

## Phase 2 — Install

*Order matters.*

### 5. Upload and activate the theme

**Appearance → Themes → Add New → Upload Theme**

Choose `newera-steam-cleaning.zip` (2.5 MB), click Install Now, then **Activate**.

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

Choose `newera-content.xml` (86 KB) and upload it. On the options screen:

- Assign all posts to your own admin user.
- Leave **"Download and import file attachments" unchecked.** There are no attachments —
  the 20 photos ship inside the theme.

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
- [ ] Pricing shows three tables with teal headers, not plain unstyled tables — **and
      check the prices are actually on the page.** This is the one failure that looks like
      a normal page rather than a broken one: intro text, then straight to the call to
      action, with the tables silently missing. Confirm you can see $139 / $159 / $199 /
      $229 / $259 on the carpet table.
- [ ] Job Photos shows a **twenty**-image grid — count them. A short grid means photos
      are missing, and it looks like a normal page rather than a broken one.
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
3. An email arrives at the recipient address, with the customer set as Reply-To. On live
   that is `NEWERA_QUOTE_RECIPIENT` in `wp-config.php`; with the constant undefined it
   falls back to **Settings → General**. Set it before testing — see step 11 — or the
   test lands wherever the install was first configured.

Then submit again with the email field left blank, and confirm you get a readable error
rather than a blank page or a lost submission.

> **Careful.** If items 1 and 2 work but no email arrives, the form is fine and mail
> delivery is the problem — that is step 11. Requests are always saved to the database
> first, so nothing is ever lost when mail fails. Nobody is simply told about them.

---

## Phase 3 — Go live

### 11. Make email reliable

**Decided: pin the recipient, leave the transport unconfigured, and test it.**
Notifications go out as `Website Quote Request <wordpress@neweracleaning.com>` through
SiteGround's own mailer. That is the WordPress default transport and it is an acceptable
starting point — but it is unauthenticated mail, so confirm it before trusting it.

Two settings, independent of each other — this is the part that gets conflated:

| | Address | Set where |
| --- | --- | --- |
| **Sender** — what recipients see mail coming *from* | `Website Quote Request <wordpress@neweracleaning.com>` | Nothing to set; the name is the theme's, the address is WordPress's |
| **Recipient** — where the lead lands | `newerasteamclean@gmail.com` | `wp-config.php` → `NEWERA_QUOTE_RECIPIENT` |

The sender **name** is the theme's, replacing core's literal `WordPress`. Quote
notifications use `Website Quote Request`, so they identify themselves in an inbox list;
all other site mail (password resets, admin notices) uses Settings → General → Site Title.
Neither depends on SMTP. If a quote notification arrives from "WordPress", something else
on the site is filtering the sender name at a later priority.

The sender **address** is derived from the site URL at send time, so on a staging copy it
arrives as `wordpress@staging.neweracleaning.com`. Expected, not a fault.

**The test that matters: check the spam folder, not just the inbox.** The recipient is a
Gmail account and the mail is unauthenticated — no DKIM, and SPF only passes if
SiteGround's sending IPs are in the domain's SPF record. That combination is the classic
spam-file, and it fails in the worst possible way: the form works, the request is saved,
the owner sees nothing and concludes nobody is enquiring.

If it does land in spam — or in a week, stops arriving — the fix is SMTP, and the theme
does it natively with no plugin. `inc/mail.php` routes `wp_mail()` through authenticated
SMTP as soon as `NEWERA_SMTP_HOST` is defined, and stands completely down when it is not.
That needs `wordpress@neweracleaning.com` created as a real mailbox under Site Tools →
Email → Accounts — it does not exist today; it is only the address WordPress *falls back
to*, which makes it look like it does. The theme README has the full block. Configuring
SMTP changes the sender **address** to that mailbox; the display name is the theme's
either way and does not change.

Either way, repeat the step 10 test afterwards and confirm delivery to an address you can
actually check — ideally not on the same domain, so you are testing more than local
loopback.

#### Where the lead lands — pin it

Quote notifications default to **Settings → General → admin email**, and on the live site
that destination is **pinned in `wp-config.php`** so an unrelated change to the admin email
cannot quietly redirect it:

```php
/* Quote leads go to the business inbox regardless of what the admin email says. */
define( 'NEWERA_QUOTE_RECIPIENT', 'newerasteamclean@gmail.com' );
```

Add this on live **before the theme is activated**, so the first real lead is never the
thing that tests it. It changes nothing on the site: it is the same address the admin
email should already hold, so the default would deliver to the same inbox anyway.

**Then check it from wp-admin.** Settings → General carries a read-only
**Quote notifications** readout, directly below the Google Business Profile field. It shows
the address mail would go to right now and which mechanism decided it — so you can confirm
the pinning took without SSH, and without reading email headers. It resolves through the
same filter the send path uses, so it cannot report one thing while the site does another.

Four things it can say:

| Readout | Meaning |
| --- | --- |
| Pinned by `NEWERA_QUOTE_RECIPIENT` | Working as intended. This is what you want to see on live. |
| Using the administration email above | The constant is absent. Delivery follows Settings → General. |
| `NEWERA_QUOTE_RECIPIENT` is defined but was ignored | **Typo.** Nothing in it parsed as an address, so delivery silently fell back to the admin email. |
| Set by a filter on `newera_quote_recipient` | Something else — a plugin, snippet or mu-plugin — is overriding it. |

> **The constant *replaces* rather than adds.** Changing Settings → General later will move
> every other WordPress notification but not this one, and the readout above is the only
> thing that will tell you. Keep the two addresses equal, and when one has to change, change
> both. To notify more than one inbox, give the constant a comma-separated list with the
> business address included — invalid entries are dropped silently, and if none survive it
> falls back to the admin email rather than erroring.

**Set the admin email too.** It is still what password resets, plugin notices and every
other WordPress mail uses, and a WordPress install keeps whatever address it was set up
with. Over SSH:

```bash
wp option update admin_email newerasteamclean@gmail.com
wp option get admin_email        # confirm
```

Prefer WP-CLI over Settings → General here. Changing it in the admin does not take effect
immediately — WordPress emails a confirmation link to the *new* address and leaves the
change pending until someone clicks it. On this site mail delivery is the one thing not
yet proven, so that can deadlock: you cannot fix the address because the address does not
work. `wp option update` writes straight to the database and skips the confirmation.

### 12. Set up the redirects

**Nothing to do — the theme serves them.** `inc/redirects.php` issues all six 301s, so
they start working the moment the theme is activated. No redirect plugin is needed.

Confirm them anyway once the site is live, with the verification script in `REDIRECTS.md`.
That file also documents how to change a destination, and keeps plugin and nginx routes on
record for the case where the theme is ever replaced.

### 13. Settle the SEO configuration

- **Turn off the WPCode schema snippet.** This one is easy to miss because it is not in
  the theme. WPCode Lite has one active snippet — "Custom HTML", site-wide header, added
  29 June 2026 — containing a hand-written `LocalBusiness` JSON-LD block under `@id`
  `#localbusiness`. Yoast's business node uses `#organization`, and the theme extends
  *that* one. Leave the snippet on and the site publishes two business entities describing
  the same company. **It lives in a plugin, so activating the theme does not remove it.**
  Deactivate it. The theme publishes everything it contained and more: `foundingDate`,
  `priceRange`, Facebook and Yelp in `sameAs`, state-qualified service-area cities, and `Service` nodes
  that resolve back to the catalogue.

  Its `description` also claims the business has served *"since the mid-1990s"*, which is
  wrong — New Era was founded in 2014, which is what the About page, the footer and the
  theme's `foundingDate` all say. Do not carry that sentence over when you remove the
  snippet. (The separate "25+ years" figure on the home page and About page is a different
  claim — the technicians' trade experience, which predates the company — and is correct
  as written. See the docblock on `newera_years_of_experience()`.)
- **Titles and descriptions.** Walk the pages in Yoast. For pages you kept, the existing
  title and description are still attached — improve them deliberately rather than
  replacing them. **The eight services already carry theirs** from the import; see step 3.
- **Fix the `/pricing/` meta description.** This one is not optional, and nothing in the
  package can do it for you: `/pricing/` is a page you keep, so its Yoast fields survive
  the migration untouched — including the error. It currently reads:

  > Home carpet cleaning, carpet repair & floor cleaning prices for **Austin, Texas** from
  > New Era Cleaning & Restoration.

  The business was founded in Austin and moved to the Houston area years ago; this is a
  leftover from that era. It is the description Google shows for the site's
  **second most-clicked page**, and it names a city the business does not serve. Replace
  it in Yoast → the Pricing page → SEO → Meta description. Either of these fits the
  snippet width:

  ```
  Home carpet cleaning, carpet repair and floor cleaning prices for Sugar Land, Katy, Richmond and the Houston area. Whole-house coupons cover up to 6 rooms.
  ```
  ```
  Current coupon prices for carpet, tile, grout and upholstery cleaning in Sugar Land and the Houston area. House coupons cover up to 6 rooms, halls and closets.
  ```

  The page **title** is fine as it stands — "Cleaning Services Pricing | New Era Steam
  Cleaning & Restoration" already uses the correct business name. Leave it.

  `/pricing/specials/` carries the same Austin error but is being redirected to
  `/pricing/`, so its description stops being served and needs no edit.

- **Fix the `/guarantee/` meta description too.** It advertises a
  **"100% satisfaction guarantee"**, which the owner has confirmed is not accurate — the
  promise is the 10-day, no-hassle guarantee, and that is the wording the new site uses
  throughout. Same situation as pricing: `/guarantee/` is a kept page, so the description
  survives the migration unless you change it in Yoast. Something like:

  ```
  Every job is backed by our 10-day, no-hassle guarantee. If you are not happy, we return and re-service the work — up to three times within those 10 days.
  ```

  The page's own body copy is already accurate and says exactly this, so it carries over
  unchanged. Only the description is wrong.
- **Organization name.** Yoast → Settings → Site representation currently reads
  "New Era Cleaning & Restoration". The search data is unambiguous that the "Steam"
  version is the one customers use — it earns 88 clicks to the shorter name's 23 and ranks
  at position 1.2–1.5 — and Settings → General is already set to it. Change the Yoast
  field to match, and check both against the Google Business Profile listing.
- **Services in the sitemap.** Yoast → Settings → Content Types → Services, confirm it is
  set to appear in search results. The archive has its own switch. The theme's admin
  notice now checks both, but confirm it once by eye.
- **Remove the development block.** Settings → Reading → uncheck "Discourage search
  engines", and check Yoast's site-wide indexing switch too. Verify from outside:
  `curl -s https://neweracleaning.com/ | grep robots` should show nothing saying noindex.
- **Revisit the 403 rule.** The site returns 403 to modern Chrome and Edge user agents
  arriving from datacentre IPs — reproducible as of 3 Sep 2026: Chrome 131 gets 403,
  Googlebot and Firefox get 200. It does not block real customers, but it blocks
  Lighthouse, PageSpeed, uptime monitors, link-preview fetchers and Search Console's Live
  Test, in the exact week you most need them. Security Optimizer is the likely source;
  check its settings before opening a SiteGround ticket.

### 13b. Retire the plugins the theme replaces

Not required for launch, but this is the moment when it is obvious what is safe to remove,
and twenty-seven active plugins is the single biggest thing slowing the site down.

The theme ships its own quote form — nonce, honeypot, server-side validation, stored to
the database and emailed — so the whole form stack is redundant once `/contact-us/` is
redirected: **Contact Form 7, Contact Form CFDB7, CF7 Apps, ReCaptcha v2 for Contact Form
7, Advanced Google reCAPTCHA and WPForms Lite.** CF7 renders on two pages today while
loading its CSS and JavaScript on every page of the site.

Export any stored CFDB7 submissions you want to keep first — they are form entries, and
uninstalling takes them with it.

Others worth a decision, none urgent:

| Plugin | Why |
| --- | --- |
| MonsterInsights *or* Site Kit | Both are doing Google Analytics. One is enough. |
| CookieYes *or* WPConsent | Two consent managers on one site. |
| WP Engine Automated Migration | The site is not on WP Engine. |
| Simple CAPTCHA with Cloudflare Turnstile | Already deactivated. |
| All in One SEO | Already covered in step 2b — uninstall it. |

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
| `newera-steam-cleaning.zip` | The theme. Upload this. 2.5 MB, includes all 20 photos and the icon font. |
| `newera-content.xml` | The content import. 5 pages, 8 services, 22 testimonials. |
| `REDIRECTS.md` | Every live URL, what happens to it, and how to implement the six 301s. |
| `newera-steam-cleaning/` | The same theme unzipped, for reading and editing. |
| `README.md` | Condensed version of this guide, kept with the code. |

Once installed, here is where each kind of content is edited:

| To change | Go to |
| --- | --- |
| Service copy, bullets, icons | Services → edit a service |
| Coupon prices | `inc/pricing.php`, or a table block on Pages → Pricing, which overrides it |
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
