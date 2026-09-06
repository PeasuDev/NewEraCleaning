# Live Review Tally Implementation Plan

> **SUPERSEDED, same day. Do not execute.** Tasks 1–10 were completed and then
> reverted: the hero now shows a static "240+ reviews" with links to the three
> profiles. Yelp Fusion is paid-only, and Angi/Facebook block server-side
> fetches (403/400, measured). See the spec's status note for detail.
>
> The tests and fixtures this plan creates were deleted with the feature.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the hero card's "from 22 reviews" — which counts stored testimonial quotes, not reviews — with a real tally of 240 across Yelp, Facebook and Angi, refreshed daily and degrading safely to manual values per source.

**Architecture:** A pure parsing/merging layer (`inc/review-parsers.php`) with no WordPress dependencies, unit-tested against captured fixtures via a standalone `php` binary; and a WordPress layer (`inc/review-stats.php`) holding HTTP fetch, option storage, WP-Cron and the admin screen. The split exists so the fragile parts — the Angi JSON-LD walk and the Facebook text match — are the parts that get real red/green tests.

**Tech Stack:** PHP 8.3 (standalone binary, no Composer), WordPress theme APIs (`wp_remote_get`, Settings API, WP-Cron), Blazor/C# for preview parity.

**Spec:** `docs/superpowers/specs/2026-09-06-review-tally-design.md`

## Global Constraints

- **No Composer, no `vendor/`.** The theme zip is built by `Compress-Archive` of the theme directory; anything inside it ships. All test code lives in `tests/` at repo root, outside the theme.
- **Credentials come from `wp-config.php` constants, never the options table:** `NEWERA_YELP_API_KEY`, `NEWERA_YELP_BUSINESS_ID`, `NEWERA_FACEBOOK_PAGE_TOKEN`.
- **Default Yelp business id:** `new-era-steam-cleaning-and-restoration-sugar-land`
- **Option name:** `newera_review_stats`. **Cron hook:** `newera_refresh_review_stats`.
- **Seed values** (used when the option is unset): total `240`, average `5.0`, sources Yelp `207`/`5.0`, Angi `14`/`4.928571428571429`, Facebook `19`/no rating/`100`% recommend.
- **Facebook is excluded from the average and included in the total.** This is what makes "5.0 from 240 reviews" true rather than approximate. Never let a Facebook rating of `0` enter the mean.
- **Card copy is exactly:** `5.0` on one line, `from 240 reviews` on the next. No source names.
- **`aggregateRating` must NOT be added to structured data** (`inc/schema.php:16-19` documents why).
- **Sanity guard thresholds:** reject `count <= 0`; reject `count` below 90% of the stored count (skipped on first write); reject `rating` outside 1–5 on a rated source.
- **Admin notice fires after 3 consecutive failures** on a source.
- Every public function is prefixed `newera_`. US spelling in all user-facing copy.
- Escape at output: `esc_html()` / `esc_attr()`. Nonce-protect both admin actions.

## Prerequisites

- [ ] **Install PHP 8.3** — `winget install PHP.PHP.8.3`, then open a new shell and confirm: `php -v` prints `PHP 8.3.x`. Nothing else is installed; there is no Composer or PHPUnit in this plan.

**Version control warning:** this working copy is *not* a git repository. This plan modifies six files across two codebases and deletes a function. Either run `git init && git add -A && git commit -m "baseline"` first, or take a copy of the folder before Task 3. The `Commit` steps below assume you did the former; if you chose a folder copy instead, treat them as "re-run the full test suite and verify clean."

## File Structure

| File | Responsibility |
|---|---|
| `tests/bootstrap.php` | Minimal `WP_Error` stub, `is_wp_error()`, assertion helpers, pass/fail exit code |
| `tests/parse-angi.php` | Angi JSON-LD parser tests |
| `tests/parse-facebook.php` | Facebook text-match parser tests |
| `tests/parse-yelp.php` | Yelp Fusion response parser tests |
| `tests/merge-stats.php` | Sanity guard + aggregation tests |
| `tests/check-calls.mjs` | Node: verifies every `newera_*` call resolves to a definition |
| `tests/fixtures/*` | Captured 2026-09-06 (already on disk — see Task 1) |
| `wordpress/newera-steam-cleaning/inc/review-parsers.php` | **Pure.** Parsing, sanity guard, aggregation. No WP calls except `WP_Error`. |
| `wordpress/newera-steam-cleaning/inc/review-stats.php` | **WP-bound.** HTTP fetch, option read/write, cron, admin screen, notice. |
| `wordpress/newera-steam-cleaning/functions.php` | Require the two new files |
| `wordpress/newera-steam-cleaning/front-page.php:21,91,92` | Read reputation stats instead of quote stats |
| `wordpress/newera-steam-cleaning/inc/template-tags.php:358-375` | Delete `newera_testimonial_stats()` |
| `wordpress/newera-steam-cleaning/inc/schema.php:19` | Update the comment naming the deleted function |
| `Business Tools Suite/.../Pages/Public/Home.razor` | Mirrored constants for preview parity |

**Note on the spec:** it named a single `inc/review-stats.php`. This plan splits the pure logic into `inc/review-parsers.php` so it can be executed by a bare `php` binary with no WordPress present. That split is the reason the fragile parsers are testable at all.

---

### Task 1: Test harness and fixture verification

**Files:**
- Create: `tests/bootstrap.php`
- Verify: `tests/fixtures/angi-reviews-2026-09-06.html`, `tests/fixtures/angi-agg-not-first.html`, `tests/fixtures/angi-no-rating.html`, `tests/fixtures/facebook-reviews-2026-09-06.html`, `tests/fixtures/yelp-business.json`

**Interfaces:**
- Produces: `it(string $name, callable $fn): void`, `assert_same($expected, $actual, string $label = ''): void`, `assert_wp_error($actual, string $label = ''): void`, `newera_test_summary(): void`, plus `WP_Error` and `is_wp_error()` stubs.

- [ ] **Step 1: Confirm the five fixtures exist**

```powershell
Get-ChildItem tests\fixtures | Select-Object Name, Length
```

Expected: five files. `angi-reviews-2026-09-06.html`, `angi-agg-not-first.html`, `angi-no-rating.html`, `facebook-reviews-2026-09-06.html`, `yelp-business.json`. If any are missing, stop — they were captured from live pages and cannot be invented.

- [ ] **Step 2: Write the harness**

Create `tests/bootstrap.php`:

```php
<?php
/**
 * Minimal test harness. No Composer, no PHPUnit - just a php binary.
 * Run any test file directly: php tests/parse-angi.php
 */

$GLOBALS['newera_test_results'] = array( 'pass' => 0, 'fail' => 0 );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_code() {
			return $this->code;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

function it( $name, callable $fn ) {
	try {
		$fn();
		++$GLOBALS['newera_test_results']['pass'];
		echo "  PASS  {$name}\n";
	} catch ( Throwable $e ) {
		++$GLOBALS['newera_test_results']['fail'];
		echo "  FAIL  {$name}\n";
		echo "        " . $e->getMessage() . "\n";
	}
}

function assert_same( $expected, $actual, $label = '' ) {
	if ( $expected !== $actual ) {
		throw new Exception( sprintf(
			'%sexpected %s, got %s',
			$label ? $label . ': ' : '',
			var_export( $expected, true ),
			var_export( $actual, true )
		) );
	}
}

function assert_wp_error( $actual, $label = '' ) {
	if ( ! is_wp_error( $actual ) ) {
		throw new Exception( sprintf(
			'%sexpected WP_Error, got %s',
			$label ? $label . ': ' : '',
			var_export( $actual, true )
		) );
	}
}

function newera_fixture( $name ) {
	$path = __DIR__ . '/fixtures/' . $name;
	if ( ! is_readable( $path ) ) {
		throw new Exception( "fixture not readable: {$path}" );
	}
	return file_get_contents( $path );
}

function newera_test_summary() {
	$r = $GLOBALS['newera_test_results'];
	echo "\n{$r['pass']} passed, {$r['fail']} failed\n";
	exit( $r['fail'] > 0 ? 1 : 0 );
}
```

- [ ] **Step 3: Verify the harness reports failure correctly**

Create a scratch file `tests/harness-check.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
it( 'passes when equal', function () {
	assert_same( 1, 1 );
} );
it( 'fails when not equal', function () {
	assert_same( 1, 2 );
} );
newera_test_summary();
```

Run: `php tests/harness-check.php`
Expected output:
```
  PASS  passes when equal
  FAIL  fails when not equal
        expected 1, got 2

1 passed, 1 failed
```
Then check the exit code: `echo $LASTEXITCODE` → `1`

A harness that cannot fail is worthless; this proves it can.

- [ ] **Step 4: Delete the scratch file**

```powershell
Remove-Item tests\harness-check.php
```

- [ ] **Step 5: Commit**

```bash
git add tests/bootstrap.php tests/fixtures
git commit -m "test: add PHP test harness and captured review fixtures"
```

---

### Task 2: Angi JSON-LD parser

**Files:**
- Create: `wordpress/newera-steam-cleaning/inc/review-parsers.php`
- Test: `tests/parse-angi.php`

**Interfaces:**
- Consumes: `it()`, `assert_same()`, `assert_wp_error()`, `newera_fixture()`, `WP_Error` from Task 1.
- Produces: `newera_parse_angi_jsonld( string $html ): array|WP_Error` returning `array( 'count' => int, 'rating' => float, 'recommend_pct' => null )`.

- [ ] **Step 1: Write the failing tests**

Create `tests/parse-angi.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../wordpress/newera-steam-cleaning/inc/review-parsers.php';

it( 'reads count and rating from the live-shaped page', function () {
	$r = newera_parse_angi_jsonld( newera_fixture( 'angi-reviews-2026-09-06.html' ) );
	assert_same( 14, $r['count'], 'count' );
	assert_same( 4.928571428571429, $r['rating'], 'rating' );
	assert_same( null, $r['recommend_pct'], 'recommend_pct' );
} );

it( 'scans every block, not just the first', function () {
	$r = newera_parse_angi_jsonld( newera_fixture( 'angi-agg-not-first.html' ) );
	assert_same( 14, $r['count'], 'count' );
	assert_same( 4.928571428571429, $r['rating'], 'rating' );
} );

it( 'errors when no aggregateRating is present', function () {
	assert_wp_error( newera_parse_angi_jsonld( newera_fixture( 'angi-no-rating.html' ) ) );
} );

it( 'errors on malformed JSON rather than throwing', function () {
	$html = '<script type="application/ld+json">{"@type":"LocalBusiness",</script>';
	assert_wp_error( newera_parse_angi_jsonld( $html ) );
} );

it( 'errors on a page with no JSON-LD at all', function () {
	assert_wp_error( newera_parse_angi_jsonld( '<html><body>nothing here</body></html>' ) );
} );

it( 'errors on an empty response body', function () {
	assert_wp_error( newera_parse_angi_jsonld( '' ) );
} );

newera_test_summary();
```

- [ ] **Step 2: Run to verify it fails**

Run: `php tests/parse-angi.php`
Expected: PHP fatal error — `Failed to open stream` for `inc/review-parsers.php`, because the file does not exist yet.

- [ ] **Step 3: Write the minimal implementation**

Create `wordpress/newera-steam-cleaning/inc/review-parsers.php`:

```php
<?php
/**
 * Pure parsing and aggregation for the review tally.
 *
 * Nothing in this file may call a WordPress function other than constructing a
 * WP_Error. That constraint is what lets tests/ run it under a bare php binary
 * with no WordPress present - which matters because the Angi and Facebook
 * parsers are the parts most likely to break when those sites change.
 *
 * @package newera
 */

/*
 * Deliberately no `defined( 'ABSPATH' ) || exit;` guard - unlike every other
 * file in inc/, this one is also included by tests/ under a bare php binary,
 * where ABSPATH does not exist. It is safe to load directly: it declares
 * functions and has no side effects on include.
 */

/**
 * Extract review count and rating from an Angi review page.
 *
 * Angi serves four separate application/ld+json blocks (LocalBusiness,
 * BreadcrumbList, ImageObject, FAQPage); only one carries aggregateRating and
 * its position is not guaranteed, so every block is scanned. A page with no
 * rating returns WP_Error rather than a zero count - handing zero downstream is
 * exactly the corruption the sanity guard exists to prevent.
 *
 * @param string $html Raw response body.
 * @return array|WP_Error
 */
function newera_parse_angi_jsonld( $html ) {
	if ( ! is_string( $html ) || '' === trim( $html ) ) {
		return new WP_Error( 'newera_angi_empty', 'Empty Angi response body.' );
	}

	$found = preg_match_all(
		'#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
		$html,
		$matches
	);

	if ( ! $found ) {
		return new WP_Error( 'newera_angi_no_jsonld', 'No JSON-LD blocks on the Angi page.' );
	}

	foreach ( $matches[1] as $block ) {
		$data = json_decode( trim( $block ), true );

		if ( null === $data ) {
			continue;
		}

		$agg = newera_find_aggregate_rating( $data );

		if ( null === $agg ) {
			continue;
		}

		$count  = isset( $agg['reviewCount'] ) ? (int) $agg['reviewCount'] : 0;
		$rating = isset( $agg['ratingValue'] ) ? (float) $agg['ratingValue'] : 0.0;

		if ( $count <= 0 ) {
			return new WP_Error( 'newera_angi_zero_count', 'Angi reported a non-positive review count.' );
		}

		return array(
			'count'         => $count,
			'rating'        => $rating,
			'recommend_pct' => null,
		);
	}

	return new WP_Error( 'newera_angi_no_rating', 'No aggregateRating found on the Angi page.' );
}

/**
 * Depth-first search for the first aggregateRating object in a decoded structure.
 *
 * @param mixed $node Decoded JSON fragment.
 * @return array|null
 */
function newera_find_aggregate_rating( $node ) {
	if ( ! is_array( $node ) ) {
		return null;
	}

	if ( isset( $node['aggregateRating'] ) && is_array( $node['aggregateRating'] ) ) {
		return $node['aggregateRating'];
	}

	foreach ( $node as $child ) {
		$hit = newera_find_aggregate_rating( $child );

		if ( null !== $hit ) {
			return $hit;
		}
	}

	return null;
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php tests/parse-angi.php`
Expected:
```
  PASS  reads count and rating from the live-shaped page
  PASS  scans every block, not just the first
  PASS  errors when no aggregateRating is present
  PASS  errors on malformed JSON rather than throwing
  PASS  errors on a page with no JSON-LD at all
  PASS  errors on an empty response body

6 passed, 0 failed
```

- [ ] **Step 5: Commit**

```bash
git add tests/parse-angi.php wordpress/newera-steam-cleaning/inc/review-parsers.php
git commit -m "feat: parse Angi review count from JSON-LD"
```

---

### Task 3: Facebook parser

**Files:**
- Modify: `wordpress/newera-steam-cleaning/inc/review-parsers.php`
- Test: `tests/parse-facebook.php`

**Interfaces:**
- Consumes: harness from Task 1.
- Produces: `newera_parse_facebook_html( string $html ): array|WP_Error` returning `array( 'count' => int, 'rating' => null, 'recommend_pct' => int )`; and `newera_parse_facebook_graph( array $json ): array|WP_Error`.

- [ ] **Step 1: Write the failing tests**

Create `tests/parse-facebook.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../wordpress/newera-steam-cleaning/inc/review-parsers.php';

it( 'reads count and recommend percent from captured markup', function () {
	$r = newera_parse_facebook_html( newera_fixture( 'facebook-reviews-2026-09-06.html' ) );
	assert_same( 19, $r['count'], 'count' );
	assert_same( 100, $r['recommend_pct'], 'recommend_pct' );
	assert_same( null, $r['rating'], 'rating' );
} );

it( 'never returns a rating - Facebook has no stars', function () {
	$r = newera_parse_facebook_html( '<span>87% recommend (43 reviews)</span>' );
	assert_same( null, $r['rating'], 'rating' );
	assert_same( 43, $r['count'], 'count' );
	assert_same( 87, $r['recommend_pct'], 'recommend_pct' );
} );

it( 'handles the singular "1 review"', function () {
	$r = newera_parse_facebook_html( '<span>100% recommend (1 review)</span>' );
	assert_same( 1, $r['count'], 'count' );
} );

it( 'errors when the pattern is absent', function () {
	assert_wp_error( newera_parse_facebook_html( '<html><body>Log In</body></html>' ) );
} );

it( 'errors on an empty body', function () {
	assert_wp_error( newera_parse_facebook_html( '' ) );
} );

it( 'reads rating_count from a Graph API response', function () {
	$r = newera_parse_facebook_graph( array( 'rating_count' => 19, 'id' => '123' ) );
	assert_same( 19, $r['count'], 'count' );
	assert_same( null, $r['rating'], 'rating' );
} );

it( 'errors when the Graph response has no rating_count', function () {
	assert_wp_error( newera_parse_facebook_graph( array( 'id' => '123' ) ) );
} );

newera_test_summary();
```

- [ ] **Step 2: Run to verify it fails**

Run: `php tests/parse-facebook.php`
Expected: FAIL on every test with `Call to undefined function newera_parse_facebook_html()`.

- [ ] **Step 3: Write the minimal implementation**

Append to `wordpress/newera-steam-cleaning/inc/review-parsers.php`:

```php
/**
 * Extract review count and recommend percentage from a logged-out Facebook page.
 *
 * Facebook pages carry recommendations, not stars, so 'rating' is always null -
 * these reviews count toward the total but must never enter the star average.
 *
 * The match is on text, not selectors: the surrounding class names are
 * build-generated (x193iq5w, xeuugli, ...) and change without notice. Verified
 * 2026-09-06 that the pattern occurs exactly once in the raw 1MB response.
 *
 * @param string $html Raw response body.
 * @return array|WP_Error
 */
function newera_parse_facebook_html( $html ) {
	if ( ! is_string( $html ) || '' === trim( $html ) ) {
		return new WP_Error( 'newera_fb_empty', 'Empty Facebook response body.' );
	}

	if ( ! preg_match( '/(\d+)%\s*recommend\s*\((\d+)\s*reviews?\)/i', $html, $m ) ) {
		return new WP_Error( 'newera_fb_no_match', 'Recommend/review string not found on the Facebook page.' );
	}

	$count = (int) $m[2];

	if ( $count <= 0 ) {
		return new WP_Error( 'newera_fb_zero_count', 'Facebook reported a non-positive review count.' );
	}

	return array(
		'count'         => $count,
		'rating'        => null,
		'recommend_pct' => (int) $m[1],
	);
}

/**
 * Extract review count from a Graph API page response.
 *
 * Used when NEWERA_FACEBOOK_PAGE_TOKEN is configured. Preferred over the HTML
 * path because it does not depend on page markup.
 *
 * @param array $json Decoded Graph response.
 * @return array|WP_Error
 */
function newera_parse_facebook_graph( $json ) {
	if ( ! is_array( $json ) || ! isset( $json['rating_count'] ) ) {
		return new WP_Error( 'newera_fb_graph_shape', 'Graph response carried no rating_count.' );
	}

	$count = (int) $json['rating_count'];

	if ( $count <= 0 ) {
		return new WP_Error( 'newera_fb_zero_count', 'Graph reported a non-positive rating_count.' );
	}

	return array(
		'count'         => $count,
		'rating'        => null,
		'recommend_pct' => isset( $json['recommend_pct'] ) ? (int) $json['recommend_pct'] : null,
	);
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php tests/parse-facebook.php`
Expected: `7 passed, 0 failed`

- [ ] **Step 5: Commit**

```bash
git add tests/parse-facebook.php wordpress/newera-steam-cleaning/inc/review-parsers.php
git commit -m "feat: parse Facebook recommendation count"
```

---

### Task 4: Yelp parser

**Files:**
- Modify: `wordpress/newera-steam-cleaning/inc/review-parsers.php`
- Test: `tests/parse-yelp.php`

**Interfaces:**
- Produces: `newera_parse_yelp_response( array $json ): array|WP_Error` returning `array( 'count' => int, 'rating' => float, 'recommend_pct' => null )`.

**Caveat:** the fixture is synthetic — the Yelp page is CAPTCHA-walled and no API key exists. These tests pin the documented response shape, not an observed one. Recapture and re-run once a key is available.

- [ ] **Step 1: Write the failing tests**

Create `tests/parse-yelp.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../wordpress/newera-steam-cleaning/inc/review-parsers.php';

it( 'reads review_count and rating from a Fusion business response', function () {
	$json = json_decode( newera_fixture( 'yelp-business.json' ), true );
	$r    = newera_parse_yelp_response( $json );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 5.0, $r['rating'], 'rating' );
	assert_same( null, $r['recommend_pct'], 'recommend_pct' );
} );

it( 'errors when review_count is missing', function () {
	assert_wp_error( newera_parse_yelp_response( array( 'rating' => 5.0 ) ) );
} );

it( 'errors when rating is missing', function () {
	assert_wp_error( newera_parse_yelp_response( array( 'review_count' => 207 ) ) );
} );

it( 'errors on a Fusion error envelope', function () {
	assert_wp_error( newera_parse_yelp_response( array(
		'error' => array( 'code' => 'BUSINESS_NOT_FOUND', 'description' => 'not found' ),
	) ) );
} );

it( 'errors on a zero count', function () {
	assert_wp_error( newera_parse_yelp_response( array( 'review_count' => 0, 'rating' => 5.0 ) ) );
} );

newera_test_summary();
```

- [ ] **Step 2: Run to verify it fails**

Run: `php tests/parse-yelp.php`
Expected: FAIL on every test with `Call to undefined function newera_parse_yelp_response()`.

- [ ] **Step 3: Write the minimal implementation**

Append to `wordpress/newera-steam-cleaning/inc/review-parsers.php`:

```php
/**
 * Extract review count and rating from a Yelp Fusion business response.
 *
 * GET /v3/businesses/{id} returns review_count and rating. The reviews endpoint
 * is deliberately not used: it caps at three excerpts and carries no total.
 *
 * @param array $json Decoded Fusion response.
 * @return array|WP_Error
 */
function newera_parse_yelp_response( $json ) {
	if ( ! is_array( $json ) ) {
		return new WP_Error( 'newera_yelp_shape', 'Yelp response was not an object.' );
	}

	if ( isset( $json['error'] ) ) {
		$code = isset( $json['error']['code'] ) ? $json['error']['code'] : 'unknown';
		return new WP_Error( 'newera_yelp_api_error', 'Yelp Fusion returned an error: ' . $code );
	}

	if ( ! isset( $json['review_count'] ) || ! isset( $json['rating'] ) ) {
		return new WP_Error( 'newera_yelp_shape', 'Yelp response was missing review_count or rating.' );
	}

	$count = (int) $json['review_count'];

	if ( $count <= 0 ) {
		return new WP_Error( 'newera_yelp_zero_count', 'Yelp reported a non-positive review count.' );
	}

	return array(
		'count'         => $count,
		'rating'        => (float) $json['rating'],
		'recommend_pct' => null,
	);
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php tests/parse-yelp.php`
Expected: `5 passed, 0 failed`

- [ ] **Step 5: Commit**

```bash
git add tests/parse-yelp.php wordpress/newera-steam-cleaning/inc/review-parsers.php
git commit -m "feat: parse Yelp Fusion business response"
```

---

### Task 5: Sanity guard and aggregation

**Files:**
- Modify: `wordpress/newera-steam-cleaning/inc/review-parsers.php`
- Test: `tests/merge-stats.php`

**Interfaces:**
- Produces:
  - `newera_apply_fetch_result( array $stored, $result ): array` — merges a parser result (or `WP_Error`) into a stored source record, applying the sanity guard.
  - `newera_derive_review_stats( array $sources ): array` — returns `array( 'total' => int, 'average' => float )`.

This is the task that decides whether a broken scrape can reach the live page. Test it harder than the parsers.

- [ ] **Step 1: Write the failing tests**

Create `tests/merge-stats.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../wordpress/newera-steam-cleaning/inc/review-parsers.php';

function stored( $count = 207, $rating = 5.0, $fails = 0 ) {
	return array(
		'count'         => $count,
		'rating'        => $rating,
		'has_rating'    => true,
		'recommend_pct' => null,
		'status'        => 'ok',
		'fetched'       => '2026-09-05T00:00:00+00:00',
		'fails'         => $fails,
		'pinned'        => false,
	);
}

function ok( $count, $rating = 5.0 ) {
	return array( 'count' => $count, 'rating' => $rating, 'recommend_pct' => null );
}

it( 'accepts a normal increase', function () {
	$r = newera_apply_fetch_result( stored( 207 ), ok( 210 ) );
	assert_same( 210, $r['count'], 'count' );
	assert_same( 'ok', $r['status'], 'status' );
	assert_same( 0, $r['fails'], 'fails' );
} );

it( 'rejects a collapse to zero and keeps the stored value', function () {
	$r = newera_apply_fetch_result( stored( 207 ), ok( 0 ) );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 'failed', $r['status'], 'status' );
	assert_same( 1, $r['fails'], 'fails' );
} );

it( 'rejects a drop of more than ten percent', function () {
	$r = newera_apply_fetch_result( stored( 207 ), ok( 150 ) );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 'failed', $r['status'], 'status' );
} );

it( 'allows a drop within ten percent', function () {
	$r = newera_apply_fetch_result( stored( 207 ), ok( 190 ) );
	assert_same( 190, $r['count'], 'count' );
	assert_same( 'ok', $r['status'], 'status' );
} );

it( 'skips the drop check on the first ever write', function () {
	$r = newera_apply_fetch_result( stored( 0 ), ok( 207 ) );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 'ok', $r['status'], 'status' );
} );

it( 'rejects a rating above five', function () {
	$r = newera_apply_fetch_result( stored( 207, 5.0 ), ok( 208, 6.0 ) );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 5.0, $r['rating'], 'rating' );
	assert_same( 'failed', $r['status'], 'status' );
} );

it( 'rejects a rating below one', function () {
	$r = newera_apply_fetch_result( stored( 207, 5.0 ), ok( 208, 0.0 ) );
	assert_same( 'failed', $r['status'], 'status' );
} );

it( 'keeps stored values and increments fails on WP_Error', function () {
	$r = newera_apply_fetch_result( stored( 207, 5.0, 1 ), new WP_Error( 'x', 'boom' ) );
	assert_same( 207, $r['count'], 'count' );
	assert_same( 5.0, $r['rating'], 'rating' );
	assert_same( 'failed', $r['status'], 'status' );
	assert_same( 2, $r['fails'], 'fails' );
} );

it( 'resets the failure counter on a good fetch', function () {
	$r = newera_apply_fetch_result( stored( 207, 5.0, 2 ), ok( 208 ) );
	assert_same( 0, $r['fails'], 'fails' );
	assert_same( 'ok', $r['status'], 'status' );
} );

it( 'accepts a null rating on an unrated source without failing', function () {
	$fb = array(
		'count' => 19, 'rating' => null, 'has_rating' => false, 'recommend_pct' => 100,
		'status' => 'ok', 'fetched' => '', 'fails' => 0, 'pinned' => false,
	);
	$r = newera_apply_fetch_result( $fb, array( 'count' => 20, 'rating' => null, 'recommend_pct' => 100 ) );
	assert_same( 20, $r['count'], 'count' );
	assert_same( 'ok', $r['status'], 'status' );
	assert_same( null, $r['rating'], 'rating' );
} );

/* ---- aggregation ---- */

function three_sources() {
	return array(
		'yelp'     => array( 'count' => 207, 'rating' => 5.0, 'has_rating' => true ),
		'angi'     => array( 'count' => 14, 'rating' => 4.928571428571429, 'has_rating' => true ),
		'facebook' => array( 'count' => 19, 'rating' => null, 'has_rating' => false ),
	);
}

it( 'totals every source including Facebook', function () {
	$s = newera_derive_review_stats( three_sources() );
	assert_same( 240, $s['total'], 'total' );
} );

it( 'excludes Facebook from the average', function () {
	// Including Facebook as a zero would give 4.6. It must be 5.0.
	$s = newera_derive_review_stats( three_sources() );
	assert_same( 5.0, $s['average'], 'average' );
} );

it( 'weights the average by count, not by source', function () {
	// Unweighted mean of 5.0 and 3.0 would be 4.0; weighted by 200 vs 10 it is 4.9.
	$s = newera_derive_review_stats( array(
		'a' => array( 'count' => 200, 'rating' => 5.0, 'has_rating' => true ),
		'b' => array( 'count' => 10, 'rating' => 3.0, 'has_rating' => true ),
	) );
	assert_same( 4.9, $s['average'], 'average' );
} );

it( 'falls back to 5.0 when no rated source has any reviews', function () {
	$s = newera_derive_review_stats( array(
		'facebook' => array( 'count' => 19, 'rating' => null, 'has_rating' => false ),
	) );
	assert_same( 19, $s['total'], 'total' );
	assert_same( 5.0, $s['average'], 'average' );
} );

it( 'handles an empty source list without dividing by zero', function () {
	$s = newera_derive_review_stats( array() );
	assert_same( 0, $s['total'], 'total' );
	assert_same( 5.0, $s['average'], 'average' );
} );

newera_test_summary();
```

- [ ] **Step 2: Run to verify it fails**

Run: `php tests/merge-stats.php`
Expected: FAIL on every test with `Call to undefined function newera_apply_fetch_result()`.

- [ ] **Step 3: Write the minimal implementation**

Append to `wordpress/newera-steam-cleaning/inc/review-parsers.php`:

```php
/**
 * Merge a fetch result into a stored source record, applying the sanity guard.
 *
 * The guard is the reason automated scraping is safe to put on a live page: a
 * parser that starts returning garbage looks exactly like a review count
 * collapsing, so an implausible result is refused and the last known good value
 * survives. Failures accumulate in 'fails' so the admin notice can surface a
 * source that has been quietly broken for days.
 *
 * @param array          $stored Existing source record.
 * @param array|WP_Error $result Parser output.
 * @return array Updated source record.
 */
function newera_apply_fetch_result( $stored, $result ) {
	$next = $stored;

	if ( is_wp_error( $result ) ) {
		$next['status'] = 'failed';
		$next['fails']  = (int) $stored['fails'] + 1;
		return $next;
	}

	$count      = isset( $result['count'] ) ? (int) $result['count'] : 0;
	$rating     = isset( $result['rating'] ) ? $result['rating'] : null;
	$has_rating = ! empty( $stored['has_rating'] );
	$prev       = isset( $stored['count'] ) ? (int) $stored['count'] : 0;

	$implausible = $count <= 0
		|| ( $prev > 0 && $count < $prev * 0.9 )
		|| ( $has_rating && ( null === $rating || $rating < 1 || $rating > 5 ) );

	if ( $implausible ) {
		$next['status'] = 'failed';
		$next['fails']  = (int) $stored['fails'] + 1;
		return $next;
	}

	$next['count']  = $count;
	$next['rating'] = $has_rating ? (float) $rating : null;
	$next['status'] = 'ok';
	$next['fails']  = 0;

	if ( array_key_exists( 'recommend_pct', $result ) ) {
		$next['recommend_pct'] = null === $result['recommend_pct'] ? null : (int) $result['recommend_pct'];
	}

	return $next;
}

/**
 * Derive the displayed total and average from the per-source records.
 *
 * Every source contributes to the total. Only sources flagged has_rating
 * contribute to the average - Facebook carries recommendations rather than
 * stars, and folding it in as a zero would drag a true 5.0 down to 4.6.
 *
 * @param array $sources Per-source records.
 * @return array{total:int,average:float}
 */
function newera_derive_review_stats( $sources ) {
	$total          = 0;
	$rated_count    = 0;
	$weighted_total = 0.0;

	foreach ( $sources as $source ) {
		$count  = isset( $source['count'] ) ? (int) $source['count'] : 0;
		$total += $count;

		if ( ! empty( $source['has_rating'] ) && $count > 0 && isset( $source['rating'] ) ) {
			$rated_count    += $count;
			$weighted_total += $count * (float) $source['rating'];
		}
	}

	$average = $rated_count > 0 ? round( $weighted_total / $rated_count, 1 ) : 5.0;

	return array(
		'total'   => $total,
		'average' => (float) $average,
	);
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php tests/merge-stats.php`
Expected: `15 passed, 0 failed`

- [ ] **Step 5: Run the whole suite**

```powershell
Get-ChildItem tests\*.php -Exclude bootstrap.php | ForEach-Object { php $_.FullName }
```

Expected: four files, `6 + 7 + 5 + 15 = 33` passing, zero failures.

- [ ] **Step 6: Commit**

```bash
git add tests/merge-stats.php wordpress/newera-steam-cleaning/inc/review-parsers.php
git commit -m "feat: add sanity guard and weighted review aggregation"
```

---

### Task 6: WordPress layer — option storage and fetchers

**Files:**
- Create: `wordpress/newera-steam-cleaning/inc/review-stats.php`
- Modify: `wordpress/newera-steam-cleaning/functions.php`

**Interfaces:**
- Consumes: every function from Tasks 2–5.
- Produces: `newera_review_stats(): array` returning `array( 'sources' => array, 'total' => int, 'average' => float, 'updated' => string )`; `newera_review_stats_defaults(): array`; `newera_yelp_business_id(): string`; `newera_review_request_args(): array`; `newera_fetch_yelp()`, `newera_fetch_angi()`, `newera_fetch_facebook()`, each `array|WP_Error`; `newera_refresh_review_stats(): void`.

**Not locally testable** — these call `wp_remote_get` and `get_option`. Verification is Task 10 (structural) and the staging pass.

- [ ] **Step 1: Write the file**

Create `wordpress/newera-steam-cleaning/inc/review-stats.php`:

```php
<?php
/**
 * Review tally: fetching, storage and scheduling.
 *
 * Pure parsing and aggregation live in review-parsers.php so they can be unit
 * tested without WordPress. This file is the part that touches the outside
 * world. See docs/superpowers/specs/2026-09-06-review-tally-design.md.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

const NEWERA_REVIEW_STATS_OPTION = 'newera_review_stats';
const NEWERA_REVIEW_CRON_HOOK    = 'newera_refresh_review_stats';
const NEWERA_REVIEW_FAIL_LIMIT   = 3;

/**
 * Seed values, used until the first successful refresh.
 *
 * Measured 2026-09-06. Facebook carries recommendations rather than stars, so
 * has_rating is false and it is excluded from the average.
 *
 * @return array
 */
function newera_review_stats_defaults() {
	$sources = array(
		'yelp' => array(
			'count' => 207, 'rating' => 5.0, 'has_rating' => true, 'recommend_pct' => null,
			'status' => 'ok', 'fetched' => '', 'fails' => 0, 'pinned' => false,
		),
		'angi' => array(
			'count' => 14, 'rating' => 4.928571428571429, 'has_rating' => true, 'recommend_pct' => null,
			'status' => 'ok', 'fetched' => '', 'fails' => 0, 'pinned' => false,
		),
		'facebook' => array(
			'count' => 19, 'rating' => null, 'has_rating' => false, 'recommend_pct' => 100,
			'status' => 'ok', 'fetched' => '', 'fails' => 0, 'pinned' => false,
		),
	);

	return array_merge(
		array( 'sources' => $sources, 'updated' => '' ),
		newera_derive_review_stats( $sources )
	);
}

/**
 * The reputation figures shown on the front page.
 *
 * @return array
 */
function newera_review_stats() {
	$stored = get_option( NEWERA_REVIEW_STATS_OPTION );

	if ( ! is_array( $stored ) || empty( $stored['sources'] ) ) {
		return newera_review_stats_defaults();
	}

	return $stored;
}

/**
 * Yelp business id, overridable via wp-config.php.
 *
 * @return string
 */
function newera_yelp_business_id() {
	return defined( 'NEWERA_YELP_BUSINESS_ID' ) && NEWERA_YELP_BUSINESS_ID
		? (string) NEWERA_YELP_BUSINESS_ID
		: 'new-era-steam-cleaning-and-restoration-sugar-land';
}

/**
 * Fetch the Yelp review count via Fusion.
 *
 * The public biz page is behind a CAPTCHA, so the API is the only route.
 *
 * @return array|WP_Error
 */
function newera_fetch_yelp() {
	if ( ! defined( 'NEWERA_YELP_API_KEY' ) || ! NEWERA_YELP_API_KEY ) {
		return new WP_Error( 'newera_yelp_unconfigured', 'NEWERA_YELP_API_KEY is not set.' );
	}

	$response = wp_remote_get(
		'https://api.yelp.com/v3/businesses/' . rawurlencode( newera_yelp_business_id() ),
		array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . NEWERA_YELP_API_KEY,
				'Accept'        => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return newera_parse_yelp_response(
		json_decode( wp_remote_retrieve_body( $response ), true )
	);
}

/**
 * Fetch the Angi review count by reading the listing's JSON-LD.
 *
 * @return array|WP_Error
 */
function newera_fetch_angi() {
	$links = newera_social_links();

	if ( empty( $links['angi'] ) ) {
		return new WP_Error( 'newera_angi_no_url', 'No Angi URL in newera_social_links().' );
	}

	$response = wp_remote_get( $links['angi'], newera_review_request_args() );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return newera_parse_angi_jsonld( wp_remote_retrieve_body( $response ) );
}

/**
 * Shared arguments for the two scraped sources.
 *
 * WordPress sends "WordPress/6.x; https://example.com" by default, which is a
 * plausible thing for Angi or Facebook to refuse. Identify as a normal browser
 * and say who we are in the referer.
 *
 * @return array
 */
function newera_review_request_args() {
	return array(
		'timeout'    => 10,
		'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36',
		'headers'    => array(
			'Accept'          => 'text/html,application/xhtml+xml',
			'Accept-Language' => 'en-US,en;q=0.9',
		),
	);
}

/**
 * Fetch the Facebook recommendation count.
 *
 * Prefers the Graph API when a page token is configured; otherwise reads the
 * logged-out reviews page, which exposes the count as plain text.
 *
 * @return array|WP_Error
 */
function newera_fetch_facebook() {
	if ( defined( 'NEWERA_FACEBOOK_PAGE_TOKEN' ) && NEWERA_FACEBOOK_PAGE_TOKEN ) {
		$response = wp_remote_get(
			add_query_arg(
				array(
					'fields'       => 'rating_count',
					'access_token' => NEWERA_FACEBOOK_PAGE_TOKEN,
				),
				'https://graph.facebook.com/v21.0/me'
			),
			array( 'timeout' => 10 )
		);

		if ( ! is_wp_error( $response ) ) {
			$parsed = newera_parse_facebook_graph(
				json_decode( wp_remote_retrieve_body( $response ), true )
			);

			if ( ! is_wp_error( $parsed ) ) {
				return $parsed;
			}
		}
		// Fall through to the HTML path when Graph is unavailable.
	}

	$links = newera_social_links();

	if ( empty( $links['facebook'] ) ) {
		return new WP_Error( 'newera_fb_no_url', 'No Facebook URL in newera_social_links().' );
	}

	$response = wp_remote_get(
		trailingslashit( $links['facebook'] ) . 'reviews',
		newera_review_request_args()
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return newera_parse_facebook_html( wp_remote_retrieve_body( $response ) );
}

/**
 * Refresh every source, then recompute the derived totals.
 *
 * Sources are fetched independently: one failing must not abort the others, and
 * a pinned source is skipped entirely so a hand-entered value stays put.
 *
 * @return void
 */
function newera_refresh_review_stats() {
	$stats    = newera_review_stats();
	$fetchers = array(
		'yelp'     => 'newera_fetch_yelp',
		'angi'     => 'newera_fetch_angi',
		'facebook' => 'newera_fetch_facebook',
	);

	foreach ( $fetchers as $key => $fetcher ) {
		if ( empty( $stats['sources'][ $key ] ) || ! empty( $stats['sources'][ $key ]['pinned'] ) ) {
			continue;
		}

		$result = call_user_func( $fetcher );
		$merged = newera_apply_fetch_result( $stats['sources'][ $key ], $result );

		if ( 'ok' === $merged['status'] ) {
			$merged['fetched'] = gmdate( 'c' );
		}

		$stats['sources'][ $key ] = $merged;
	}

	$stats            = array_merge( $stats, newera_derive_review_stats( $stats['sources'] ) );
	$stats['updated'] = gmdate( 'c' );

	update_option( NEWERA_REVIEW_STATS_OPTION, $stats );
}
add_action( NEWERA_REVIEW_CRON_HOOK, 'newera_refresh_review_stats' );

/**
 * Schedule the daily refresh when the theme is activated.
 *
 * @return void
 */
function newera_schedule_review_refresh() {
	if ( ! wp_next_scheduled( NEWERA_REVIEW_CRON_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', NEWERA_REVIEW_CRON_HOOK );
	}
}
add_action( 'after_switch_theme', 'newera_schedule_review_refresh' );

/**
 * Clear the schedule when the theme is deactivated.
 *
 * @return void
 */
function newera_unschedule_review_refresh() {
	wp_clear_scheduled_hook( NEWERA_REVIEW_CRON_HOOK );
}
add_action( 'switch_theme', 'newera_unschedule_review_refresh' );
```

- [ ] **Step 2: Require both files**

In `wordpress/newera-steam-cleaning/functions.php`, find the block of `require_once` calls for `inc/` files and add, keeping parsers first since `review-stats.php` depends on them:

```php
require_once get_template_directory() . '/inc/review-parsers.php';
require_once get_template_directory() . '/inc/review-stats.php';
```

- [ ] **Step 3: Confirm the parser tests still pass**

`review-parsers.php` is now loaded by WordPress too, so its `defined( 'ABSPATH' )` guard must not have broken standalone execution.

Run: `php tests/merge-stats.php`
Expected: `15 passed, 0 failed`

- [ ] **Step 4: Commit**

```bash
git add wordpress/newera-steam-cleaning/inc/review-stats.php wordpress/newera-steam-cleaning/functions.php
git commit -m "feat: fetch and store review counts from all three sources"
```

---

### Task 7: Admin screen and staleness notice

**Files:**
- Modify: `wordpress/newera-steam-cleaning/inc/review-stats.php`

**Interfaces:**
- Consumes: `newera_review_stats()`, `NEWERA_REVIEW_STATS_OPTION`, `NEWERA_REVIEW_FAIL_LIMIT`.
- Produces: a Settings → Reviews page at `options-general.php?page=newera-reviews`.

- [ ] **Step 1: Append the admin surface**

Append to `wordpress/newera-steam-cleaning/inc/review-stats.php`:

```php
/**
 * Register the Settings > Reviews screen.
 *
 * @return void
 */
function newera_review_stats_menu() {
	add_options_page(
		__( 'Review Counts', 'newera' ),
		__( 'Reviews', 'newera' ),
		'manage_options',
		'newera-reviews',
		'newera_review_stats_page'
	);
}
add_action( 'admin_menu', 'newera_review_stats_menu' );

/**
 * Handle the two POST actions: save hand-entered values, or refresh now.
 *
 * @return void
 */
function newera_review_stats_handle_post() {
	if ( ! current_user_can( 'manage_options' ) || empty( $_POST['newera_reviews_action'] ) ) {
		return;
	}

	check_admin_referer( 'newera_reviews' );

	$action = sanitize_key( wp_unslash( $_POST['newera_reviews_action'] ) );

	if ( 'refresh' === $action ) {
		newera_refresh_review_stats();
		add_settings_error( 'newera_reviews', 'refreshed', __( 'Review counts refreshed.', 'newera' ), 'updated' );
		return;
	}

	if ( 'save' !== $action ) {
		return;
	}

	$stats = newera_review_stats();

	foreach ( array_keys( $stats['sources'] ) as $key ) {
		$count  = isset( $_POST[ 'count_' . $key ] ) ? (int) $_POST[ 'count_' . $key ] : 0;
		$pinned = ! empty( $_POST[ 'pinned_' . $key ] );

		if ( $count > 0 ) {
			$stats['sources'][ $key ]['count'] = $count;
		}

		if ( ! empty( $stats['sources'][ $key ]['has_rating'] ) && isset( $_POST[ 'rating_' . $key ] ) ) {
			$rating = (float) $_POST[ 'rating_' . $key ];

			if ( $rating >= 1 && $rating <= 5 ) {
				$stats['sources'][ $key ]['rating'] = $rating;
			}
		}

		$stats['sources'][ $key ]['pinned'] = $pinned;

		if ( $pinned ) {
			$stats['sources'][ $key ]['status'] = 'ok';
			$stats['sources'][ $key ]['fails']  = 0;
		}
	}

	$stats            = array_merge( $stats, newera_derive_review_stats( $stats['sources'] ) );
	$stats['updated'] = gmdate( 'c' );

	update_option( NEWERA_REVIEW_STATS_OPTION, $stats );
	add_settings_error( 'newera_reviews', 'saved', __( 'Review counts saved.', 'newera' ), 'updated' );
}
add_action( 'load-settings_page_newera-reviews', 'newera_review_stats_handle_post' );

/**
 * Render the Settings > Reviews screen.
 *
 * @return void
 */
function newera_review_stats_page() {
	$stats = newera_review_stats();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Review Counts', 'newera' ); ?></h1>
		<?php settings_errors( 'newera_reviews' ); ?>
		<p>
			<?php
			printf(
				/* translators: %1$s: total review count, %2$s: average rating. */
				esc_html__( 'The home page currently shows %1$s reviews at an average of %2$s.', 'newera' ),
				'<strong>' . esc_html( $stats['total'] ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['average'], 1 ) ) . '</strong>'
			);
			?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Facebook carries recommendations rather than star ratings, so it counts toward the total but not the average. Pin a source to stop the daily refresh overwriting a value you set by hand.', 'newera' ); ?>
		</p>
		<form method="post">
			<?php wp_nonce_field( 'newera_reviews' ); ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'newera' ); ?></th>
						<th><?php esc_html_e( 'Count', 'newera' ); ?></th>
						<th><?php esc_html_e( 'Rating', 'newera' ); ?></th>
						<th><?php esc_html_e( 'Status', 'newera' ); ?></th>
						<th><?php esc_html_e( 'Last fetched', 'newera' ); ?></th>
						<th><?php esc_html_e( 'Pinned', 'newera' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $stats['sources'] as $key => $source ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( ucfirst( $key ) ); ?></th>
						<td>
							<input type="number" min="0" name="<?php echo esc_attr( 'count_' . $key ); ?>"
								value="<?php echo esc_attr( $source['count'] ); ?>" />
						</td>
						<td>
							<?php if ( ! empty( $source['has_rating'] ) ) : ?>
								<input type="number" step="0.01" min="1" max="5"
									name="<?php echo esc_attr( 'rating_' . $key ); ?>"
									value="<?php echo esc_attr( $source['rating'] ); ?>" />
							<?php else : ?>
								<span aria-hidden="true">&mdash;</span>
								<span class="screen-reader-text"><?php esc_html_e( 'No star rating', 'newera' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							echo 'failed' === $source['status']
								? '<strong>' . esc_html__( 'Failed', 'newera' ) . '</strong> (' . esc_html( $source['fails'] ) . ')'
								: esc_html__( 'OK', 'newera' );
							?>
						</td>
						<td><?php echo esc_html( $source['fetched'] ? $source['fetched'] : __( 'never', 'newera' ) ); ?></td>
						<td>
							<input type="checkbox" name="<?php echo esc_attr( 'pinned_' . $key ); ?>"
								value="1" <?php checked( ! empty( $source['pinned'] ) ); ?> />
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" name="newera_reviews_action" value="save" class="button button-primary">
					<?php esc_html_e( 'Save', 'newera' ); ?>
				</button>
				<button type="submit" name="newera_reviews_action" value="refresh" class="button">
					<?php esc_html_e( 'Refresh now', 'newera' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Warn in wp-admin when a source has failed repeatedly.
 *
 * Automated scraping is only safe on a live page if it breaks loudly. Angi
 * reshaping their markup is a matter of when, not if.
 *
 * @return void
 */
function newera_review_stats_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$stats  = newera_review_stats();
	$broken = array();

	foreach ( $stats['sources'] as $key => $source ) {
		if ( (int) $source['fails'] >= NEWERA_REVIEW_FAIL_LIMIT ) {
			$broken[] = ucfirst( $key );
		}
	}

	if ( ! $broken ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		sprintf(
			/* translators: %1$s: comma-separated source names, %2$s: settings page URL. */
			esc_html__( 'Review counts could not be refreshed from: %1$s. The last known values are still showing. Check the %2$s screen.', 'newera' ),
			esc_html( implode( ', ', $broken ) ),
			'<a href="' . esc_url( admin_url( 'options-general.php?page=newera-reviews' ) ) . '">' . esc_html__( 'Review Counts', 'newera' ) . '</a>'
		)
	);
}
add_action( 'admin_notices', 'newera_review_stats_notice' );
```

- [ ] **Step 2: Confirm the parser tests are unaffected**

Run: `php tests/merge-stats.php`
Expected: `15 passed, 0 failed`

- [ ] **Step 3: Commit**

```bash
git add wordpress/newera-steam-cleaning/inc/review-stats.php
git commit -m "feat: add Settings > Reviews screen and failure notice"
```

---

### Task 8: Front-page rendering

**Files:**
- Modify: `wordpress/newera-steam-cleaning/front-page.php:21,91,92`
- Modify: `wordpress/newera-steam-cleaning/inc/template-tags.php:358-375` (delete)
- Modify: `wordpress/newera-steam-cleaning/inc/schema.php:19` (comment)

**Interfaces:**
- Consumes: `newera_review_stats()` from Task 6.

`newera_testimonial_stats()` has exactly one consumer — `$newera_stats` at `front-page.php:21`, read only at lines 91 and 92. Once the hero switches source, it has zero callers, so it is deleted rather than left as dead code.

- [ ] **Step 1: Point the hero at the reputation figures**

In `front-page.php`, change line 21 from:

```php
$newera_stats    = newera_testimonial_stats();
```

to:

```php
$newera_stats    = newera_review_stats();
```

Line 20 (`$newera_reviews = newera_get_testimonials();`) stays — it still feeds the featured quote and the testimonial grid.

- [ ] **Step 2: Read the new keys**

Line 91 is already correct (`$newera_stats['average']`). Change line 92 from:

```php
<span class="hero-card-rating-label">from <?php echo esc_html( $newera_stats['count'] ); ?> reviews</span>
```

to:

```php
<span class="hero-card-rating-label">from <?php echo esc_html( $newera_stats['total'] ); ?> reviews</span>
```

`newera_stars( 5 )` on line 90 is unchanged.

- [ ] **Step 3: Delete the orphaned function**

Remove `newera_testimonial_stats()` entirely from `inc/template-tags.php` (lines 358–375, including its docblock).

- [ ] **Step 4: Fix the dangling comment reference**

In `inc/schema.php`, line 19 currently reads:

```
 *   stay visible page content — see newera_testimonial_stats().
```

Change to:

```
 *   stay visible page content — see newera_review_stats().
```

Leave the surrounding paragraph intact: the decision not to emit `aggregateRating` still stands, and automating the fetch does not change it.

- [ ] **Step 5: Verify no references survive**

```powershell
Select-String -Path wordpress\newera-steam-cleaning\* -Pattern "newera_testimonial_stats" -Recurse
```

Expected: no matches.

- [ ] **Step 6: Commit**

```bash
git add wordpress/newera-steam-cleaning/front-page.php wordpress/newera-steam-cleaning/inc/template-tags.php wordpress/newera-steam-cleaning/inc/schema.php
git commit -m "feat: show the real review tally on the home page hero"
```

---

### Task 9: Blazor preview parity

**Files:**
- Modify: `Business Tools Suite/Business Tools Suite.Client/Pages/Public/Home.razor:99,313-314,330-334`

The owner runs `deploy-and-launch.ps1` as their live preview of the public site. If the preview keeps deriving the count from stored quotes it will read 22 while WordPress reads 240.

- [ ] **Step 1: Replace the derived fields with mirrored constants**

In the `@code` block, remove the `reviewCount` and `averageRating` fields (lines 313–314) and add:

```csharp
    // Mirrors the newera_review_stats option in the WordPress theme, which is
    // the source of truth. The preview has no WordPress to read from, so these
    // are updated by hand whenever the shipped numbers change.
    // Measured 2026-09-06: Yelp 207, Angi 14, Facebook 19.
    private const int ReviewCount = 240;
    private const double AverageRating = 5.0;
```

- [ ] **Step 2: Stop deriving them in OnInitializedAsync**

Delete these two lines from `OnInitializedAsync` (lines 333–334):

```csharp
        reviewCount = reviews.Count;
        averageRating = reviews.Count > 0 ? reviews.Average(r => r.Rating) : 5.0;
```

The rest of the method — `testimonials`, `featuredReview` — is unchanged; it still works from the loaded reviews.

- [ ] **Step 3: Update the markup**

Line 98–99 becomes:

```razor
                <strong>@AverageRating.ToString("0.0")</strong>
                <span class="hero-card-rating-label">from @ReviewCount reviews</span>
```

- [ ] **Step 4: Build**

Run: `dotnet build "Business Tools Suite/Business Tools Suite.Client"`
Expected: build succeeds with no errors. Warnings about the now-unused `Review.Rating` are acceptable.

If the .NET SDK is unavailable, say so plainly rather than marking this step done — the edit is still correct but unverified.

- [ ] **Step 5: Commit**

```bash
git add "Business Tools Suite/Business Tools Suite.Client/Pages/Public/Home.razor"
git commit -m "feat: mirror the review tally in the Blazor preview"
```

---

### Task 10: Structural checks and zip rebuild

**Files:**
- Rebuild: `wordpress/newera-steam-cleaning.zip`

- [ ] **Step 1: Re-run the full PHP suite**

```powershell
Get-ChildItem tests\*.php -Exclude bootstrap.php | ForEach-Object { php $_.FullName }
```

Expected: 33 passing, zero failures.

- [ ] **Step 2: Check every `newera_*` call resolves to a definition**

This is the check that catches the Task 8 deletion leaving a caller behind. Create `tests/check-calls.mjs`:

```js
// Verifies every newera_* function called in the theme is also defined there.
// Deliberately narrow: it does not balance braces or tags. A brace scanner here
// has to track <?php ... ?> regions and skip heredocs, or functions.php and
// inc/template-tags.php each report a phantom +1 from braces in inline HTML.
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';

const ROOT = 'wordpress/newera-steam-cleaning';

const walk = (dir) => readdirSync(dir).flatMap((entry) => {
  const path = join(dir, entry);
  return statSync(path).isDirectory() ? walk(path) : path.endsWith('.php') ? [path] : [];
});

const files = walk(ROOT);
const defined = new Set();
const called = new Map();

for (const file of files) {
  const src = readFileSync(file, 'utf8');
  for (const m of src.matchAll(/function\s+(newera_\w+)\s*\(/g)) defined.add(m[1]);
  for (const m of src.matchAll(/(?<!function\s)\b(newera_\w+)\s*\(/g)) {
    if (!called.has(m[1])) called.set(m[1], file);
  }
  for (const m of src.matchAll(/['"](newera_\w+)['"]/g)) {
    if (!called.has(m[1])) called.set(m[1], file);
  }
}

const missing = [...called].filter(([name]) => !defined.has(name));
for (const [name, file] of missing) console.error(`UNDEFINED  ${name}  (${file})`);
console.log(`${defined.size} defined, ${called.size} referenced, ${missing.length} undefined`);
process.exit(missing.length ? 1 : 0);
```

Run: `node tests/check-calls.mjs`

Expected: `0 undefined`, exit code 0. Specifically, `newera_testimonial_stats` must NOT appear — if it does, a caller survived the Task 8 deletion. `newera_review_stats`, `newera_derive_review_stats`, `newera_apply_fetch_result` and `newera_review_request_args` must all resolve.

Note the script reports WordPress core functions as undefined only if they happen to be `newera_`-prefixed, which none are — a non-empty `UNDEFINED` list is a real defect, not noise.

- [ ] **Step 3: Confirm no test code leaked into the theme**

```powershell
Get-ChildItem wordpress\newera-steam-cleaning -Recurse -Include *test*,vendor,composer.json
```

Expected: no results. Tests live at repo root by design.

- [ ] **Step 4: Rebuild the zip**

The zip is a stale build artifact — editing theme source does not touch it, and a staging install from an old zip silently gets old code.

```powershell
Compress-Archive -Path wordpress\newera-steam-cleaning -DestinationPath wordpress\newera-steam-cleaning.zip -Force
```

Verify the entries are rooted at `newera-steam-cleaning/`:

```powershell
[System.IO.Compression.ZipFile]::OpenRead((Resolve-Path wordpress\newera-steam-cleaning.zip)).Entries |
  Select-Object -First 5 FullName
```

Expected: paths beginning `newera-steam-cleaning/`.

- [ ] **Step 5: Commit**

```bash
git add tests/check-calls.mjs wordpress/newera-steam-cleaning.zip
git commit -m "build: add call checker and rebuild theme zip"
```

---

### Task 11: Staging verification

Nothing in Tasks 6–8 has been executed — there is no PHP runtime with WordPress here, and no Yelp key. This task is the first time the WordPress layer actually runs, and it is not optional.

- [ ] **Step 1: Install the rebuilt zip on staging and activate the theme**

Confirm the hero reads `5.0` / `from 240 reviews` from the seed defaults before any cron has run.

- [ ] **Step 2: Confirm the schedule registered**

The daily `newera_refresh_review_stats` event should exist after activation. Check via a cron viewer plugin or `wp cron event list` if WP-CLI is available on the host.

- [ ] **Step 3: Force a refresh and check each source**

Settings → Reviews → "Refresh now". Expected:
- **Angi** → `ok`, count 14, rating ~4.93, fetched timestamp set.
- **Facebook** → `ok`, count 19. If the host's IP gets a login wall instead of the public page, expect `failed` — this is the known-fragile path. Pin the value and note it.
- **Yelp** → `failed` with `newera_yelp_unconfigured` until a key is set. Expected, not a defect. Pin 207 meanwhile.

- [ ] **Step 4: Prove the sanity guard on real data**

Temporarily point the Angi URL at a page with no `aggregateRating` via the `newera_social_links` filter. Refresh. Expected: the count **stays 14**, status flips to `failed`, and the hero total is unchanged. Restore the filter.

This is the single most important verification in the plan — it is what makes an automated scrape acceptable on a live page.

- [ ] **Step 5: Confirm the notice appears**

Refresh three times with a broken source. Expected: a warning naming it, linking to Settings → Reviews.

- [ ] **Step 6: Confirm pinning works**

Pin a source, change its value by hand, refresh. Expected: the hand-entered value survives.

---

## Deferred

- **Yelp Fusion key.** Until `NEWERA_YELP_API_KEY` is set, Yelp stays pinned at 207 and the site is still correct — it just does not move. Review Yelp's display requirements before shipping their data.
- **Replacing `tests/fixtures/yelp-business.json`** with a real captured response, and re-running `php tests/parse-yelp.php` against it.
- **Facebook page token.** The HTML path works today; the Graph path is written and ready but unexercised.
