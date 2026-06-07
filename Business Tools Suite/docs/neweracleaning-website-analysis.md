# Website Analysis — neweracleaning.com

**Date of analysis:** 2026-06-07
**Analyst:** Automated technical review (HTTP/HTML/DNS level, public + cellular browser test)
**Target site:** https://neweracleaning.com/ (New Era Cleaning & Restoration — Houston, TX)

> Scope note: Findings are based on publicly served HTTP responses, HTML markup, robots.txt,
> XML sitemaps, structured data, DNS/RDAP, and a real-device test on Chrome over cellular data.
> No authenticated/admin access was used. Color-contrast and full rendered-DOM accessibility
> checks were not performed.

---

## Executive Summary

The site is technically well-optimized for search engines (clean metadata, schema, sitemaps,
fast and lightweight). However, two issues stand out:

1. **An over-tuned anti-bot rule** returns **403 Forbidden** to modern Chrome/Edge User-Agents
   coming from **datacenter IPs**, while allowing Googlebot and residential browsers. Confirmed
   **not** to block real customers (loads fine on Chrome + cellular), but it **blocks audit/
   monitoring tools** and uses a spoofable allow-listing method.
2. **Low search-result visibility** — the site appears far down the results when searched in
   Chrome on cellular data, despite being technically indexable. This is a separate SEO problem
   that aligns with the on-page issues documented below.

---

## 1. The 403 / Anti-Bot Behavior

### What was observed (deterministic, repeated tests)

| Requester signature | Result |
|---|---|
| Googlebot UA | ✅ 200 (full ~86 KB page) |
| Bingbot UA | ✅ 200 |
| `curl` | ✅ 200 |
| Firefox / Safari (any version) | ✅ 200 |
| Old Chrome (v90–109) | ✅ 200 |
| **Modern Chrome (110, 120, 131) from datacenter IP** | 🔴 **403** |
| **Edge (131) from datacenter IP** | 🔴 **403** |
| HeadlessChrome | 🔴 403 |
| **Chrome on real phone + cellular (residential IP)** | ✅ **Loads fine** |

- Block is **deterministic** (8/8 Chrome → 403, 8/8 Firefox → 200).
- 403 response body is **empty** with `Server: nginx` → blocked at the **edge/web-server layer,
  before WordPress PHP runs** (not a styled plugin block page).

### Root cause (conclusion)

The rule triggers on **"modern desktop browser User-Agent + datacenter/non-residential IP"** —
the classic fingerprint of a scraper spoofing a real browser. The cellular test **confirms real
residential customers are NOT blocked**.

### Why it still matters

- **Cloaking appearance:** serving 200 to Googlebot but 403 to browser-like agents is differential
  behavior Google's guidelines discourage if the gap widens.
- **Audit/monitoring lockout:** Lighthouse/PageSpeed proxies, WAVE/axe crawlers, uptime monitors,
  and social/link-preview fetchers run from datacenter IPs and get 403'd — you can't easily audit
  your own site.
- **Weak as security:** allow-listing by the **Googlebot UA string** is trivially spoofable
  (we got 200 simply by sending a Googlebot UA). Real Googlebot should be verified by
  **reverse-DNS to googlebot.com / Google IP ranges**, not the UA string.

### Recommended fixes

1. Change crawler allow-listing from **UA-string matching** to **verified reverse-DNS / IP-range**.
2. **Allow-list** the IPs of your audit/monitoring tools so you can run SEO/accessibility scans.
3. Stop 403'ing modern Chrome/Edge UAs outright; keep bot protection but narrow the rule.
4. Source of the rule (in order of likelihood on this stack):
   - **Host edge security** — headers (`X-Httpd-Modphp`, hashed `Host-Header`, `X-Proxy-Cache`)
	 indicate **Nexcess / Liquid Web managed WordPress**; open a support ticket with the exact
	 symptom ("403 to modern Chrome/Edge from datacenter IPs; Googlebot/curl get 200").
   - **Security plugin** — Wordfence / All-In-One Security / Sucuri (check "block fake Googlebot",
	 "bad bot / UA blocking", live traffic logs).
   - **Server config** — `.htaccess` / nginx `if ($http_user_agent ...) return 403;`.

---

## 2. Search Engine Parsibility (Indexing) — Mostly Strong

Confirmed working (tested as Googlebot, which receives 200):

- ✅ **robots.txt** — clean, allows all, references sitemap (Yoast-generated).
- ✅ **Yoast SEO** powering metadata.
- ✅ **Title:** "Houston Steam Cleaning & More | New Era Cleaning & Restoration".
- ✅ **Meta description** present and descriptive.
- ✅ **Canonical URL** set correctly.
- ✅ **Open Graph** tags complete (title, description, image, url, site_name).
- ✅ **Structured data (JSON-LD):** Organization, WebSite, WebPage, BreadcrumbList, SearchAction,
  ImageObject.
- ✅ **XML sitemaps:** `sitemap_index.xml` → 18 pages + testimonials.
- ✅ **Performance:** ~446 ms load, ~86 KB page (lightweight, fast).
- ✅ **HTTPS** with HTTP→HTTPS redirect.
- ✅ `lang="en"` set on `<html>`.

---

## 3. On-Page SEO Issues (likely contributors to low ranking)

### 🟠 Multiple H1 tags on the homepage (5 found)

1. "Carpet Cleaning"
2. "Commercial Carpet Cleaning"
3. "Tile, Grout, Stone Cleaning"
4. "Your Houston Area Carpet & Steam Cleaning Experts"
5. "Organic - All natural green options available upon request!"

**Fix:** Keep **one** descriptive H1 (e.g., #4) and demote the rest to H2/H3.

### 🟠 Jumbled heading hierarchy

H1s and H2s are interleaved out of logical order, weakening semantic structure for crawlers and
screen readers. **Fix:** reorder to a clean H1 → H2 → H3 flow.

### Other ranking considerations (not directly measured)

- Verify the site is **verified in Google Search Console** and inspect the homepage's index status.
- Ensure consistent **NAP** (Name/Address/Phone) and a complete **Google Business Profile** for
  local SEO (the business is local/Houston-focused).
- Confirm the 403 anti-bot rule isn't degrading Google's ability to fully render/crawl assets.

---

## 4. Accessibility Issues (WCAG)

### 🟠 Zoom disabled (WCAG 1.4.4 / 1.4.10 failure)

Viewport meta tag contains:

```
width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no
```

`maximum-scale=1` + `user-scalable=no` block pinch-zoom, harming low-vision users.
**Fix:** remove `maximum-scale=1, user-scalable=no` → `width=device-width, initial-scale=1`.

### Other

- ✅ All 9 homepage images have `alt` attributes (verify the text is descriptive, not filenames).
- 🟠 Heading hierarchy (see §3) also affects screen-reader navigation.
- ⚠️ Not tested: color contrast, keyboard navigation, focus order, full rendered-DOM ARIA.
  Run **WAVE**, **axe DevTools**, or **Lighthouse** in a real browser for a complete audit
  (after allow-listing the tool or running from a residential connection).

---

## 5. Infrastructure Snapshot

| Attribute | Value |
|---|---|
| Platform | WordPress + PHP |
| Web server | nginx (with proxy cache: `X-Proxy-Cache: HIT`, `X-Cache-Enabled: True`) |
| Likely host | Nexcess / Liquid Web managed WordPress (`X-Httpd-Modphp`, hashed `Host-Header`) |
| SEO plugin | Yoast SEO |
| Domain registered | 2014-12-02 (GoDaddy), expires 2034-12-02 |
| Security headers present | `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Strict-Transport-Security` (via host) |

---

## 6. Related Domain Check — neweracleaningservice.com

A separate domain (`www.neweracleaningservice.com`) was reviewed for possible impersonation/cloning.
**Conclusion: NOT an illegitimate copy** — it is a **different, unrelated business** sharing the
generic "New Era Cleaning" name.

| Signal | neweracleaning.com (original) | neweracleaningservice.com |
|---|---|---|
| Backend | WordPress / nginx | **Vercel + Next.js** |
| Domain age | Registered **2014** | Registered **2025-09-06** (new) |
| Phone area code | Houston, TX | **(928) — Arizona** |
| Email | (WP contact form) | neweracarpetcleaning@gmail.com |
| Service area | Houston / Sugar Land / Katy / Richmond, TX | Generic, no TX references |
| Reused original images/copy? | — | **No** (own `/logo.png`, `/welcome.jpg`) |

No copied content, no hotlinked images, no impersonated contact details, entirely different tech
stack. Any concern would be a **brand-name overlap** (trademark — legal question), **not** a
technical clone/fraud.

---

## Priority Action List

| Priority | Issue | Fix |
|---|---|---|
| 🔴 High | Low search visibility | Fix multiple H1s + heading order; verify Search Console indexing; complete Google Business Profile / local SEO |
| 🟠 Med | 403 blocks audit/monitoring tools | Switch crawler allow-list to verified reverse-DNS/IP; allow-list audit tools; narrow UA rule |
| 🟠 Med | Zoom disabled (accessibility) | Remove `maximum-scale=1, user-scalable=no` from viewport |
| 🟢 Low | Heading hierarchy | Reorder to logical H1→H2→H3 |
| 🟢 Low | Alt text quality | Confirm alt attributes are meaningful, not filenames |
| 🟢 Low | Full a11y audit | Run WAVE/axe/Lighthouse from residential IP or allow-listed tool |

---

## Confirmed via real-device test (2026-06-07)

- ✅ Site **loads on Chrome over cellular data** → 403 does **not** affect real residential visitors.
- ⚠️ Site appears **far down** in Chrome search results on cellular → real-world **SEO visibility
  problem**, consistent with the on-page issues in §3.
