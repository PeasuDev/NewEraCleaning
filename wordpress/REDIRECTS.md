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

| Old URL | Redirect to | Notes |
| --- | --- | --- |
| `/pricing/specials/` | `/pricing/` | Coupon prices are now tables on the pricing page. |
| `/contact-us/` | `/get-quote/` | **Decide first** — see below. |
| `/testimonials/j-e-xxxx-texas/` | `/#reviews` | Testimonials are no longer standalone pages. |
| `/testimonials/m-f-xxxx-texas/` | `/#reviews` | |
| `/testimonials/l-p-xxxx-texas/` | `/#reviews` | |
| `/testimonials/j-e-xxxx-texas-2/` | `/#reviews` | |

### Before you set the `/contact-us/` redirect

The new build has no contact page — the quote form is the contact route, and it appears
on the home page, on `/get-quote/`, and on all eight service pages. Sending `/contact-us/`
to `/get-quote/` is reasonable, but check Search Console first: a page named "contact us"
often picks up brand and "near me" searches that a quote form does not answer as well.

If it has meaningful impressions, the better move is to keep a real contact page at
`/contact-us/` and add it to the navigation, rather than redirecting it away.

### About the testimonial URLs

`/#reviews` is an anchor on the home page, and Google will treat it as a redirect to `/`.
That is acceptable for four thin pages. If any of them turns out to have backlinks, point
that one at the most relevant service page instead.

---

## 3. Implementing them

No redirect plugin is installed on the live site today, so pick one route.

### Option A — Redirection plugin (simplest)

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

### Option B — nginx, via Nexcess support

Faster, and does not add a plugin. Send these to the host:

```nginx
location = /pricing/specials/            { return 301 /pricing/; }
location = /contact-us/                  { return 301 /get-quote/; }
location = /testimonials/j-e-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/m-f-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/l-p-xxxx-texas/   { return 301 /#reviews; }
location = /testimonials/j-e-xxxx-texas-2/ { return 301 /#reviews; }
```

### Option C — Yoast Premium

If the site already has Yoast Premium, its redirect manager handles this with no extra
plugin. Check the licence before assuming.

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
