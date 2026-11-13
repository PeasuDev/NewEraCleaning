# Rescued page metadata

Read out of the trash on **11 Sep 2026** over the REST API as `taylor`, after the step 7
collision work trashed the originals instead of the imported duplicates.

Nothing here is lost yet. All 18 trashed pages are intact with their content, their
templates and their Yoast metadata. This file exists so the strings survive even if the
trash is emptied before the fix lands.

## The five that swapped places

The imported duplicates are published and the originals are in the trash. The clean slugs
are therefore **free**, so untrashing an original restores its own slug with no clash.

| Original | ID | Template it carried | Duplicate now live | ID |
| --- | --- | --- | --- | --- |
| `about-us` | 15 | `page-about.php` | `about-us-2` | 560 |
| `pricing` | 93 | `page-pricing.php` | `pricing-3` | 101 |
| `job-photos` | 21 | (default) | `job-photos-2` | 561 |
| `guarantee` | 25 | (default) | `guarantee-2` | 103 |
| `get-quote` | 109 | `page-quote.php` | `get-quote-2` | 562 |

Three of the five originals already had the new template attached, so step 4b was
partly done before the wrong page was trashed. `job-photos` and `guarantee` still need
theirs set to **Job Photos** and **Guarantee** after restoring.

**Every live duplicate has an empty Yoast description.** That is the whole SEO cost of
leaving things as they are.

## Yoast strings on the originals

### about-us (15)
- **Title:** About Us: New Era Steam Cleaning & Restoration Story
- **Description:** Since 2014, we've served the greater Harris & Fort Bend County area, offering expert cleaning services for homes and businesses.

### pricing (93) — page title "Cleaning Services And Current Coupon Prices"
- **Title:** Cleaning Services Pricing | New Era Steam Cleaning & Restoration
- **Description:** Home carpet cleaning, carpet repair & floor cleaning prices for Austin, Texas from New Era Cleaning & Restoration.

> The description says **Austin**. The business serves Houston, Sugar Land and Katy.
> Worth correcting rather than restoring verbatim.

### job-photos (21)
- **Title:** See Photos Of Our Cleaning Work | New Era Cleaning
- **Description:** Restoring the look of your carpets & floors is our specialty. See how we've transformed these homes & businesses.

### guarantee (25)
- **Title:** Cleaning Satisfaction Guaranteed | New Era Cleaning
- **Description:** We know that you're going to love working with us, which is why we offer a 100% satisfaction guarantee on all our work.

### get-quote (109) — page title "Get A Quote"
- **Title:** Contact Us For A Free Quote | New Era Cleaning
- **Description:** Have a question? Contact us today for a free, no obligation quote for cleaning your home or business in Harris & Fort Bend County

### privacy-policy (57)
- **Title:** Privacy Policy - New Era Steam Cleaning & Restoration
- **Description:** Your privacy matters. Check out the New Era Steam Cleaning & Restoration Privacy Policy

### terms-of-service (59)
- **Title:** Terms of Service - New Era Steam Cleaning & Restoration
- **Description:** New Era Steam Cleaning & Restoration Terms of Service

All seven are `index, follow` with `max-image-preview:large`. None carries a custom
canonical — the `?page_id=` canonicals the API reports are an artefact of reading a
trashed page, not a stored setting.

## Two that were trashed by mistake

`privacy-policy` (57, 4,965 characters) and `terms-of-service` (59, 3,282 characters).
Section 4c of the runbook says keep both; neither is in the import and nothing replaces
them. Both currently serve 404. Untrash them as they are.

## Content worth reading before you trash a duplicate

- **guarantee.** The original names a specific **10-day, no hassle guarantee**. The
  imported duplicate replaces that with generic insured-and-bonded copy and no term.
  The 10-day promise is a real commitment the owner made; do not drop it silently.
- **pricing.** The live `pricing-3` holds the price tables as real table blocks in the
  editor, so those override the seeded tables in `inc/pricing.php`. The original page 93
  is a 771-character intro paragraph with no tables, and its prices lived in ACF. Restore
  93 and the seeded tables render instead. Per the 7 Sep decision the seeded prices are
  the ones to publish and they match, so the figures do not change either way. Clearing
  93's intro afterwards avoids showing the intro twice.
- **about-us.** Both have real prose. The original is 1,633 characters, the duplicate
  1,057. Compare before discarding either.

## Keep these in the trash

The eight old service pages are correctly trashed and must stay that way. Restoring any
of them puts a real page back in front of the new catalogue at the same URL, and the
whole services section silently disappears.

`carpet-tile-grout-steam-cleaning-services` (19) and its children `carpet-cleaning` (28),
`tile-stone-and-grout` (30), `upholstery` (32), `carpet-repairs` (34),
`pet-odor-removal` (36), `commercial` (38), `water-damage-restoration` (40).

Also staying trashed by design: `contact-us` (495), `specials` (112, a child of pricing)
and `restoring-your-floors-refreshing-your-space` (2, the old front page). All three need
301s per `REDIRECTS.md`.

## ACF

The API returns an empty ACF payload for every trashed page, so the old field values
cannot be read back this way. Nothing depends on it: pricing comes from
`inc/pricing.php`, the Job Photos gallery is the theme's hard-coded twenty, and
`front-page.php` replaces the Home page rendering entirely.
