# Troubleshooting Notes

Hard-won fixes for non-obvious issues. Read before re-diagnosing layout/CSS problems.

---

## Whole-site layout collapses: everything stacked vertically with no padding

**Date:** 2026-06 · **Status:** Fixed & verified in-browser

### Symptoms
- Pages render as a single vertical column with **no padding, grids, or flex spacing**.
- Confusingly, **some** scoped styles still work (e.g. `ReviewCard` avatars and colored
  source badges rendered) while **page-level** layout rules (`.review-summary` grid,
  `.summary-card`, `.review-toolbar`) did **not**.
- Nav-menu icons were also missing.

### Root cause — a corrupt scoped CSS bundle
Blazor concatenates **every** `*.razor.css` scoped stylesheet into a **single** bundle
(`Business_Tools_Suite.styles.css`, referenced from `Components/App.razor`; the server
bundle just `@import`s the client bundle). If **one** file contains a malformed
declaration, it can break parsing for **everything after it** in the bundle.

Here the culprit was **truncated SVG `url("...` data URIs** that were missing their
closing quote, `)`, and `;`:

```css
/* BROKEN — unterminated string */
.bi-star-fill {
	background-image: url("data:image/svg+xml,%3Csvg ... class='bi bi-star-fill'
}
```

A browser CSS parser hitting an unterminated string keeps consuming text until the next
quote, swallowing all subsequent rules. Component styles **earlier** in the bundle
survived; page layout rules **later** in the bundle were eaten — hence the "some styles
work, some don't" pattern.

Affected files:
- `Business Tools Suite.Client/Pages/Home.razor.css` (`.bi-star-fill`, `.bi-graph-up`)
- `Business Tools Suite.Client/Layout/NavMenu.razor.css` (11 `.bi-*-nav-menu` icon rules)

### Fix
Complete every `url(...)` data URI with the full Bootstrap Icons SVG path, `viewBox`,
and a terminating `");`.

### Fast diagnosis for future agents
Don't assume "missing CSS." A *partial* scoped-style failure almost always means a
**syntax error upstream in the bundle**, not absent rules. Quick checks:

1. **Balance scan** every scoped stylesheet for unbalanced `()` / `"` (the real tell):
   ```powershell
   Get-ChildItem -Recurse "Business Tools Suite.Client" -Filter *.razor.css | ForEach-Object {
	   $c = Get-Content $_.FullName -Raw
	   $o = ([regex]::Matches($c,'\(')).Count; $cl = ([regex]::Matches($c,'\)')).Count
	   $q = ([regex]::Matches($c,'"')).Count
	   if ($o -ne $cl -or ($q % 2)) { "MISMATCH: $($_.Name) parens($o/$cl) quotes($q)" } else { "OK: $($_.Name)" }
   }
   ```
2. Inspect the generated bundle to confirm rules survive after the suspect declaration:
   `Business Tools Suite.Client/obj/Debug/net10.0/scopedcss/bundle/Business_Tools_Suite.Client.styles.css`
3. Rebuild (regenerates the bundle) and re-run the balance scan; it should print `OK` for all files.

### Prevention
When hand-authoring inline SVG `data:` URIs in scoped CSS, always close the string:
`url("...%3E%3C/svg%3E");`. Prefer copying a complete icon (with `viewBox` + full `path`)
over truncating one.

---

## A new page component breaks the build with `CS1061 / CS0029` on an unrelated model

**Date:** 2026-06 · **Status:** Fixed & verified in-browser

### Symptoms
- Added a new page named `ClientProfile.razor`; the build then failed with dozens of errors
  like `'ClientProfile' does not contain a definition for 'Name'/'Email'/'Source'...` and
  `Cannot implicitly convert type 'Models.ClientProfile' to 'Pages.ClientProfile'`.
- Errors appeared in **`Clients.razor`** too — a file I hadn't touched.

### Root cause — component name shadows a model record
A Razor component's class name comes from its **filename**. Naming the page `ClientProfile`
created `Business_Tools_Suite.Client.Pages.ClientProfile`, which **shadowed** the
`Business_Tools_Suite.Client.Models.ClientProfile` record for **every** file in the `Pages`
namespace (both are pulled in via `_Imports.razor`). Existing pages that used the *model*
suddenly resolved to the *component*.

### Fix
Rename the page so it can't collide: **`ClientProfilePage.razor`** (the `@page "/clients/{Email}"`
route is unaffected). Rename the scoped CSS to match (`ClientProfilePage.razor.css`).

### Prevention
Never name a component the same as a model/record in scope. This mirrors the existing
`Client` → `ClientProfile` rename note in `PROJECT_OVERVIEW.md`.

---

## `BLAZOR102`: scoped CSS file with no associated component (Release only)

**Date:** 2026-06 · **Status:** Fixed & verified

### Symptoms
- IDE/Debug builds were green, but the Release build (and `deploy-and-launch.ps1`) failed:
  ```
  error BLAZOR102: The scoped css file 'Pages\ClientProfile.razor.css' was defined
  but no associated razor component or view was found for it.
  ```

### Root cause — orphaned `.razor.css` after a rename
After renaming `ClientProfile.razor` → `ClientProfilePage.razor` from the **terminal**,
Visual Studio re-created an empty **0-byte `ClientProfile.razor.css`** stub at the old path.
Release's `_ProcessScopedCssFiles` step requires every `*.razor.css` to have a matching
component; the orphan had none. (Debug's faster pipeline tolerated it.)

### Fix
Delete the orphaned scoped CSS so only the renamed pair remains:
```powershell
Get-ChildItem "Business Tools Suite.Client\Pages" -Filter "ClientProfile*"   # spot the orphan
Remove-Item "Business Tools Suite.Client\Pages\ClientProfile.razor.css"
```

### Fast diagnosis for future agents
After any razor rename, confirm each `*.razor.css` has a same-named `*.razor` sibling, and
**always run a Release build** (`dotnet build -c Release`) before relying on the deploy
script — Debug can mask `BLAZOR102`.
