# Redirects for the neweracleaning.com relaunch

Every URL the live site currently serves, and what happens to it. Taken from
`sitemap_index.xml` on 3 Sep 2026: **18 pages + 4 testimonials = 22 indexed URLs**,
all returning `200` at the time of writing.

The theme was deliberately built around the existing URLs rather than tidier ones,
so **16 of the 22 need no redirect at all**. Only six do.

---

## 1. URLs that stay exactly as they are — do nothing

These resolve to the same address after the migration. Do not redirect them, do not
rename them.

| URL | Served by, after launch |
| --- | --- |
| `/` | Theme `front-page.php` |
| `/carpet-tile-grout-steam-cleaning-services/` | Service catalogue (post type archive) |
| `/carpet-tile-grout-steam-cleaning-services/carpet-cleaning/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/carpet-repairs/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/commercial/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/tile-stone-and-grout/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/upholstery/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/pet-odor-removal/` | Service |
| `/carpet-tile-grout-steam-cleaning-services/water-damage-restoration/` | Service |
| `/about-us/` | Imported page, About template |
| `/get-quote/` | Imported page, Get a Quote template |
| `/pricing/` | Imported page, Pricing template |
| `/guarantee/` | Imported page, Guarantee template |
| `/job-photos/` | Imported page, Job Photos template |
| `/privacy-policy/` | Your existing page, unchanged |
| `/terms-of-service/` | Your existing page, unchanged |

> **The eight service URLs need a deletion, not a redirect.** WordPress serves a real
> page in preference to a post type archive at the same address, so the eight existing
> service *pages* have to be moved to the Trash before the imported *services* can claim
> those URLs. The theme prints an admin notice listing any that are still in the way.
> Do this on staging and confirm each URL loads the new design before pushing live.

---

## 1b. One URL that is new

```
/carpet-tile-grout-steam-cleaning-services/stone-travertine-restoration/
```

Natural stone and travertine restoration used to share a page with tile and grout.
They are different jobs bought by different customers, so they are now two services.
Tile and grout keeps the original `tile-stone-and-grout` URL and all of its history;
the stone work gets this new address.

Nothing redirects here — there is no old URL to redirect from. It just needs to be
crawled: it will appear in `sitemap_index.xml` automatically, and it is worth running
URL Inspection on it after launch to get it indexed sooner.

---

## 2. URLs that need a 301

Six. Two are straightforward, four need a decision about where reviews should land.

All six are now confirmed against Search Console (16 months to 5 Sep 2026). Nothing here
is still a judgement call.

| Old URL | Redirect to | 16-mo clicks | Notes |
| --- | --- | --- | --- |
| `/pricing/specials/` | `/pricing/` | 1 | Coupon prices are now tables on the pricing page. |
| `/contact-us/` | `/get-quote/` | 1 | Confirmed — see below. |
| `/testimonials/j-e-xxxx-texas/` | `/#reviews` | 31 | Junk-string traffic — see below. |
| `/testimonials/l-p-xxxx-texas/` | `/#reviews` | 26 | |
| `/testimonials/j-e-xxxx-texas-2/` | `/#reviews` | 4 | |
| `/testimonials/m-f-xxxx-texas/` | `/#reviews` | 0 | |

**No page other than the home page has a single external backlink.** Ten exist in total
and all ten point at `/`, from powerwashmaps, BBB, Fyple, MapQuest and Nextdoor. So no
redirect target here needs choosing to preserve link equity — these are traffic decisions
only.

### `/contact-us/` — confirmed, redirect it

The concern was that a page named "contact us" picks up brand and "near me" searches a
quote form does not answer. It does not: **1 click and 168 impressions in sixteen months,
average position 31.3.** Send it to `/get-quote/`.

That also settles the plugin question — with no contact page to serve, the Contact Form 7
stack has nothing left to render and can be retired.

### The testimonial URLs — confirmed, redirect all four

Two of these look alarming in the Pages report: `j-e-xxxx-texas` earns 31 clicks at
position 6.8 with a 3.8% CTR, `l-p-xxxx-texas` earns 26 at 7.0. Best click-through rates
on the site. On that evidence you would keep them.

**Filter the query report by page and it evaporates.** The reviewers' surnames were
anonymised to the literal string `xxxx` in the page titles, and every query reaching these
pages is that string: `xxxx je`, `je xxxx`, `jexxxx`, `لبxxxx`, `cleaning xxxx`,
`era xxxx`, `lp xxxx`, `xxxx texas`, `texas xxxx`, `xxxx txx`. Across both pages not one
query mentions cleaning, carpet, or Sugar Land. Almost nothing else on the web contains
"xxxx", so they rank fourth for a garbage string. Real clicks, no customer behind them.

They also have zero received internal links, so nothing on the site depends on them.

`/#reviews` is an anchor on the home page and Google will treat it as a redirect to `/`.
That is fine here.

> **Timing matters for these four.** The `testimonials` post type is registered by the
> *current* theme, so it deregisters the instant the new theme is activated and the URLs
> stop resolving straight away. Put these 301s in place at the moment of the switch, not
> afterwards.

---

## 3. Implementing them

**The theme now serves all six itself.** `inc/redirects.php` holds the map and issues the
301s on `template_redirect`, so activating the theme is all that is required — no plugin,
nothing to transcribe, and the rules are version-controlled alongside the code that
depends on them.

Matching is on the request path rather than on what WordPress resolves the request to.
That matters for `/pricing/specials/`, which is still a published page today: a rule that
only fired on a 404 would never reach it. Trailing slashes and letter case are both
ignored, and any query string on the old link is carried through to the destination.

To change a destination, filter the map rather than editing the file:

```php
add_filter( 'newera_redirect_map', function ( $map ) {
	$map['/contact-us'] = '/about-us/';
	return $map;
} );
```

The wp-admin SEO checklist reports any redirect whose destination page is missing, so a
rule pointing at a trashed page does not go unnoticed.

The two routes below are no longer needed. They are kept for the case where the theme is
ever replaced but the URLs still have to resolve.

### Option A — Redirection plugin

Install **Redirection**, then Tools → Redirection → Import/Export and paste this CSV:

```csv
source,target,type
/pricing/specials/,/pricing/,301
/contact-us/,/get-quote/,301
/testimonials/j-e-xxxx-texas/,/#reviews,301
/testimonials/m-f-xxxx-texas/,/#reviews,301
/testimonials/l-p-xxxx-texas/,/#reviews,301
/testimonials/j-e-xxxx-texas-2/,/#reviews,301
```

### Option B — server rules, via SiteGround support

Faster, and does not add a plugin. Send these to the host:

```nginx
location = /pricing/specials/            { return 301 /pricing/; }
location = /contact-us/                  { return 301 /get-quote/; }
location = /testimonials/j-e-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/m-f-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/l-p-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/j-e-xxxx-texas-2/ { return 301 /#reviews; }
```

### ~~Option C — Yoast Premium~~ — not available

Checked in wp-admin on 7 Sep 2026: the site runs **Yoast SEO free**, with the Premium
upsell panel still in the sidebar. There is no redirect manager. Use Option A or B.

---

## 4. Verifying, after launch

Run this from a machine with a **non-Chrome user agent** — the site's anti-bot rule returns
`403` to modern Chrome and Edge user agents from datacentre IPs, which will otherwise make
every check look broken:

```bash
for u in \
  / \
  /carpet-tile-grout-steam-cleaning-services/ \
  /carpet-tile-grout-steam-cleaning-services/carpet-cleaning/ \
  /carpet-tile-grout-steam-cleaning-services/carpet-repairs/ \
  /carpet-tile-grout-steam-cleaning-services/commercial/ \
  /carpet-tile-grout-steam-cleaning-services/tile-stone-and-grout/ \
  /carpet-tile-grout-steam-cleaning-services/upholstery/ \
  /carpet-tile-grout-steam-cleaning-services/pet-odor-removal/ \
  /carpet-tile-grout-steam-cleaning-services/water-damage-restoration/ \
  /about-us/ /get-quote/ /pricing/ /guarantee/ /job-photos/ \
  /privacy-policy/ /terms-of-service/ \
  /pricing/specials/ /contact-us/ \
  /testimonials/j-e-xxxx-texas/ /testimonials/m-f-xxxx-texas/ \
  /testimonials/l-p-xxxx-texas/ /testimonials/j-e-xxxx-texas-2/
do
  printf '%-62s ' "$u"
  curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' \
    -A 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/128.0' \
    "https://neweracleaning.com$u"
done
```

Expected: the sixteen in section 1 return `200`, and the six in section 2 return `301`
to the listed target. Anything returning `404`, or a `301` that lands on another `301`,
needs fixing before you resubmit the sitemap.

Then in Search Console: resubmit `sitemap_index.xml`, and run URL Inspection on the home
page and two or three service pages.
