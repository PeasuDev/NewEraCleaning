# Rescued content — the four `/testimonials/…` pages

Captured from the live site **7 Sep 2026**, before the theme switch.

## Why this file exists

The `testimonials` post type is registered by the site's **current** theme. The moment the
new theme is activated it deregisters: the posts stay in the database but vanish from
wp-admin, and the four URLs stop resolving immediately. `IMPORT-GUIDE.md` step 4c calls
for copying anything worth keeping out of them *before* step 5. This is that copy.

**These four reviews are not duplicates of the theme's seeded content.** Checked against
both `newera-steam-cleaning/inc/testimonials.php` (22 entries) and `newera-content.xml` —
none of this text appears in either. Without this file the reviews are gone.

The URLs themselves are still being redirected to `/#reviews` as planned; see
`REDIRECTS.md`. That decision was about traffic (every query reaching them is a variant of
the literal string `xxxx` left in their titles), not about the quality of the reviews.

## The reviews

Reviewer names were already anonymised to initials plus the placeholder `xxxx` on the live
site. No source (Yelp, Angi, Facebook) is attributed on any of these pages.

### 1. J.E., Texas — `/testimonials/j-e-xxxx-texas/`

> They are a very good company. Excellent work. I will definitely be using their services
> again. Best thing, they will come to Houston to do jobs. I highly recommend using them.

### 2. L.P., Texas — `/testimonials/l-p-xxxx-texas/`

> Had the most amazing experience yesterday! My carpet was disgusting, to the point where I
> was told there was no remedy and it would need to be pulled up and replaced! Charles
> Hegler, owner and operator, was able to clean and save my carpet! I am so thankful for
> his amazing work and would highly recommend him to anyone needing this type of service!

### 3. J.E., Texas — `/testimonials/j-e-xxxx-texas-2/`

> It is Amazing what New Era cleaning can do to restore your floors! Great customer service
> and very prompt. Give a family owned business a chance and they will show you how much
> more they care.

### 4. M.F., Texas — `/testimonials/m-f-xxxx-texas/`

> They are a very good company. Excellent work. I will definitely be using their services
> again. Best thing, they will come to Houston to do jobs. I highly recommend using them.

**Note:** this is word-for-word identical to J.E. above. Four pages, three distinct
reviews. Whoever built the original pages appears to have pasted the same review under two
sets of initials, so only one of the two should ever be republished.

## If you want these on the new site

They are not in the package today. Adding them means editing **both**
`inc/testimonials.php` (the theme's seed data and its fallback) and `newera-content.xml`
(the import file) — the two are maintained by hand and must agree, or the site and the
import drift apart. A theme change also means rebuilding `newera-steam-cleaning.zip`.

Two things to settle first:

- **Attribution.** Every one of the theme's existing 22 testimonials carries a `source`
  (Yelp or Angi). These four carry none, and inventing one would be putting a claim in a
  customer's mouth. Either find where they originally came from, or add a seed entry whose
  source is honestly blank and check the template renders that acceptably.
- **The duplicate.** Republish review 1 or review 4, not both.
