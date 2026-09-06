# Job photo tap-to-enlarge — design

**Date:** 2026-09-05
**Scope:** `wordpress/newera-steam-cleaning/` — the Job Photos page only.

## Problem

The Job Photos page renders twelve tiles in a `gallery-grid`. Every tile is
forced to `aspect-ratio: 4 / 3` with `object-fit: cover`, but the source photos
are 800×800, 800×600, 600×800, 800×521 and 800×533. The portrait and square
photos are therefore cropped, and there is no way to see the whole image. The
tiles are inert: nothing happens on tap or click.

## Goal

Tapping or clicking a tile opens the whole, uncropped photo, and it behaves the
same on a phone, a tablet and a desktop. Not a hover-only or desktop-only
affordance.

## Approach: CSS-only `:target` overlay

The theme ships **zero JavaScript** — the mobile nav and the home carousel are
both pure CSS. The lightbox keeps that property. Each tile links to `#photo-N`,
and a fixed full-screen overlay per photo is revealed by `:target`.

Rejected alternatives:

- **A small vanilla JS lightbox.** Would add Escape, focus trapping and
  arrow keys, but breaks the theme's no-JS record and leaves the gallery inert
  if the script fails to load.
- **CSS baseline plus progressive-enhancement JS** for Escape and arrow keys.
  Same benefit, but two mechanisms to keep in sync for one small feature.

The trade-off accepted: no Escape key and no focus trap. See *Known limits*.

## Markup — `page-job-photos.php`

Per photo, inside `.gallery-grid`:

1. `<figure class="gallery-tile" id="tile-N">` — the id is the close target.
2. Inside it, `<a class="gallery-tile-link" href="#photo-N">` wrapping only the
   `<img>`, so `<figcaption>` stays a direct child of `<figure>` and the markup
   stays valid. A stretched `::after` makes the whole tile — caption strip
   included — the tap target.
3. Immediately after the figure, `<div class="lightbox" id="photo-N">`
   containing a backdrop close link, the full image with its caption, a close
   control, and previous/next controls linking to `#photo-N±1` with wraparound.

The overlay lives *inside* the grid, next to the tile that opens it. It is
`display: none` until targeted, so it is never a grid item, never in the tab
order, and its image is never fetched. When it opens, the close control is the
next thing a keyboard user reaches after the tile they activated.

**Close targets `#tile-N`, not the top of the grid.** Dismissing photo twelve
returns the reader to photo twelve.

**No new image assets.** The overlay `src` is the same 800 px file the tile
already downloaded, so opening is a cache hit rather than a second request.
800 px on the long edge is the full resolution that ships with the theme.

## CSS — new `Gallery lightbox` block in `style.css`

- `.lightbox { position: fixed; inset: 0; z-index: 200; display: none }` and
  `.lightbox:target { display: flex }`. z-200 clears the sticky header (z-50)
  and stays under the skip link (z-100000). No ancestor of the grid sets
  `transform` or `filter`, so `fixed` is viewport-relative as intended.
- Image: `max-width: 100%`, `max-height: 78dvh` with a `vh` fallback,
  `object-fit: contain` — every shape shows whole.
- Backdrop is a full-bleed `<a>`; tapping outside the photo closes. It carries
  `tabindex="-1" aria-hidden="true"` so it is not a second unlabelled tab stop
  duplicating the close button.
- Controls are 48 px round links — above the 44 px touch-target minimum.
- `overscroll-behavior: contain` on the overlay.
- `.gallery-tile { scroll-margin-top: 6rem }` so the close jump does not park
  the tile under the sticky header.
- Under 640 px the side arrows would sit on top of a photo that now fills the
  width, so they move to a centred row below the caption and the image cap
  drops to 62dvh.

## Behaviour per format

| Format | Open | Close |
| --- | --- | --- |
| Phone / tablet | tap tile | tap backdrop, tap ×, or back gesture |
| Desktop | click, or Tab + Enter | click backdrop or ×, or Back |

Pinch-zoom on the enlarged photo still works; the theme's viewport tag already
permits it.

## Known limits

Inherent to the CSS-only approach, accepted deliberately:

- No Escape key and no focus trap. The close control is a real focusable link,
  and the browser Back button or gesture also closes.
- Previous/next each push a history entry, so Back steps through viewed photos
  before leaving the gallery.
- A touch-drag over the backdrop can still scroll the page behind;
  `overscroll-behavior` reduces but does not eliminate this.

## Verification

No PHP runtime is available on this machine, so checking is structural:
re-create `check-theme.mjs` (brace and tag balance, undefined `newera_*` calls,
missing assets) and run it against the theme. Then bump `NEWERA_VERSION` to
`1.1.1` so the changed stylesheet busts cache, and rebuild
`wordpress/newera-steam-cleaning.zip`.
