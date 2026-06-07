# Review Extractor

`Extract-Reviews.ps1` pulls customer reviews into structured JSON from either
**screenshots** (offline OCR) or a **saved Angi page** (exact, no OCR), and can
**merge** multiple sources into one file tagged by `source`.

Runs on Windows PowerShell 5.1 (the built-in `powershell.exe`). No internet, no
NuGet, no Azure — OCR uses the offline `Windows.Media.Ocr` engine.

## Output fields

`source` (merge only), `name`, `location`, `rating`, `date`, `reviewText`
(OCR mode also adds `sourceImage` + `needsVerification`; Angi adds `sourcePage`).

> **Ratings:** review-site star ratings are images, so OCR can't read them. For
> screenshot sources, confirm the rating visually and pass `-DefaultRating`.
> The Angi HTML path reads real ratings directly, so they're exact.

## Common commands

```powershell
cd "C:\Users\tpeas\Business Tools\tools\ReviewImageExtractor"

# 1) Screenshots -> JSON (e.g. Yelp). You confirmed all are 5 stars:
.\Extract-Reviews.ps1 -Mode Ocr -InputDir "C:\Users\tpeas\Business Tools\New Era\Yelp Reviews" -DefaultRating 5

# 2) Saved Angi page -> JSON (ratings + dates are exact). Auto-finds the .htm in the folder:
.\Extract-Reviews.ps1 -Mode AngiHtml -InputDir "C:\Users\tpeas\Business Tools\New Era\Angi Reviews"

# 3) Merge sources into one all-reviews.json with a 'source' label per review:
.\Extract-Reviews.ps1 -Mode Merge
```

Each mode writes `reviews.json` into the input folder (OCR also writes
`_raw-ocr.txt` for auditing). Merge writes `New Era\all-reviews.json`.

## Useful options

| Param | Mode | Purpose |
|-------|------|---------|
| `-InputDir` | Ocr / AngiHtml | Folder of images, or folder containing the saved Angi `.htm`. |
| `-Output` | any | Override the output path. |
| `-DefaultRating 5` | Ocr | Apply a confirmed star rating to every row (omit to record `null`). |
| `-Upscale 2.5` | Ocr | Bicubic upscale factor before OCR. Raise (e.g. `4`) for tiny/low-contrast text. |
| `-HtmlFile` | AngiHtml | Point at a specific saved page if the folder has several. |
| `-InputFiles` / `-SourceNames` | Merge | Parallel lists of `reviews.json` paths and their source labels. |

## How to save the Angi page (for AngiHtml mode)

On the Angi reviews page, **Ctrl+S → "Webpage, HTML Only"** (or *view-source* +
save) into the reviews folder. The script parses the embedded schema.org
JSON-LD, so review text/ratings/dates come out exact.

## Tips

- After OCR, scan for `needsVerification: true` rows — those are missing a
  cleanly parsed name/location/date and should be eyeballed against the image.
- If a screenshot OCRs poorly, re-export it larger / higher-contrast and re-run;
  bumping `-Upscale` to `4` also helps.
