#requires -Version 5.1
<#
.SYNOPSIS
  One-stop tool to extract customer reviews into JSON and merge sources.

.DESCRIPTION
  Modes:
	Ocr       Run offline Windows.Media.Ocr over every image in -InputDir.
			  Writes raw OCR text (_raw-ocr.txt) plus a best-effort structured
			  reviews.json (name/location/date parsed heuristically; the review
			  text is the remainder). Star ratings on review sites are rendered
			  as images, so OCR cannot read them: 'rating' is set to
			  -DefaultRating (null unless you pass a value such as 5). Any row
			  missing name/location/date is flagged needsVerification=true so you
			  know to eyeball it against the screenshot.

	AngiHtml  Parse schema.org JSON-LD out of a saved Angi page (view-source
			  HTML). Produces EXACT name/rating/date/reviewText with no OCR
			  error. 'location' is null because Angi omits it.

	Merge     Combine multiple extracted reviews.json files into one
			  all-reviews.json, tagging every record with its 'source'.

.EXAMPLE
  # Screenshots -> JSON (e.g. Yelp), where you've confirmed every review is 5 stars:
  .\Extract-Reviews.ps1 -Mode Ocr -InputDir "C:\...\Yelp Reviews" -DefaultRating 5

.EXAMPLE
  # Saved Angi page -> JSON (ratings and dates are exact, no OCR):
  .\Extract-Reviews.ps1 -Mode AngiHtml -InputDir "C:\...\Angi Reviews"

.EXAMPLE
  # Merge both into one file with a 'source' label on each review:
  .\Extract-Reviews.ps1 -Mode Merge
#>
[CmdletBinding()]
param(
	[ValidateSet('Ocr', 'AngiHtml', 'Merge')]
	[string]$Mode = 'Merge',

	[string]$InputDir,
	[string]$Output,

	# --- Ocr mode ---
	[double]$Upscale = 2.5,
	# Ratings can't be OCR'd from star images. Pass e.g. 5 to apply a confirmed
	# rating to every row; leave at -1 to record null.
	[double]$DefaultRating = -1,

	# --- AngiHtml mode ---
	[string]$HtmlFile,

	# --- Merge mode ---
	[string[]]$InputFiles = @(
		"C:\Users\tpeas\Business Tools\New Era\Yelp Reviews\reviews.json",
		"C:\Users\tpeas\Business Tools\New Era\Angi Reviews\reviews.json"
	),
	[string[]]$SourceNames = @('Yelp', 'Angi'),

	[string]$Business = 'New Era Steam Cleaning & Restoration'
)

$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------------------
# Shared helpers
# ---------------------------------------------------------------------------

function Decode-EntitiesStable {
	# View-source HTML can double/triple encode entities (e.g. &amp;amp;#39;),
	# so decode repeatedly until the string stops changing.
	param([string]$Value)
	if ([string]::IsNullOrEmpty($Value)) { return $Value }
	$prev = $null; $cur = $Value; $guard = 0
	while ($cur -ne $prev -and $guard -lt 6) {
		$prev = $cur
		$cur = [System.Net.WebUtility]::HtmlDecode($cur)
		$guard++
	}
	$cur = $cur -replace '\\"', '"' -replace '\\/', '/'
	return $cur.Trim()
}

function Convert-IsoDate {
	param([string]$Raw)
	[datetime]$parsed = [datetime]::MinValue
	if ([datetime]::TryParse($Raw, [ref]$parsed)) {
		return $parsed.ToString('MMM d, yyyy', [System.Globalization.CultureInfo]::InvariantCulture)
	}
	return $null
}

function Write-Json {
	param($Object, [string]$Path)
	$dir = Split-Path $Path -Parent
	if ($dir -and -not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
	$Object | ConvertTo-Json -Depth 8 | Set-Content -Path $Path -Encoding UTF8
}

# ---------------------------------------------------------------------------
# OCR mode
# ---------------------------------------------------------------------------

function Invoke-OcrMode {
	param([string]$InputDir, [string]$Output, [double]$Upscale, [double]$DefaultRating)

	if (-not $InputDir) { throw "-InputDir is required for Ocr mode." }
	if (-not $Output) { $Output = Join-Path $InputDir 'reviews.json' }

	Add-Type -AssemblyName System.Runtime.WindowsRuntime | Out-Null
	Add-Type -AssemblyName System.Drawing | Out-Null

	$asTaskGeneric = [System.WindowsRuntimeSystemExtensions].GetMethods() |
		Where-Object {
			$_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and
			$_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
		} | Select-Object -First 1

	function Await([object]$WinRtTask, [Type]$ResultType) {
		$netTask = $asTaskGeneric.MakeGenericMethod($ResultType).Invoke($null, @($WinRtTask))
		[void]$netTask.Wait(-1)
		$netTask.Result
	}

	[void][Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime]
	[void][Windows.Storage.Streams.IRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime]
	[void][Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics.Imaging, ContentType = WindowsRuntime]
	[void][Windows.Graphics.Imaging.SoftwareBitmap, Windows.Graphics.Imaging, ContentType = WindowsRuntime]
	[void][Windows.Media.Ocr.OcrEngine, Windows.Media.Ocr, ContentType = WindowsRuntime]

	$engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
	if (-not $engine) { throw "No OCR language pack is installed for any user-profile language." }

	$tempDir = Join-Path $env:TEMP 'ReviewOcr'
	New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

	# Windows OCR accuracy drops on small/anti-aliased text; upscaling first recovers it.
	function Get-Upscaled([string]$SourcePath, [double]$Factor) {
		$src = [System.Drawing.Image]::FromFile($SourcePath)
		try {
			$w = [int][Math]::Round($src.Width * $Factor)
			$h = [int][Math]::Round($src.Height * $Factor)
			$bmp = New-Object System.Drawing.Bitmap $w, $h
			$g = [System.Drawing.Graphics]::FromImage($bmp)
			try {
				$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
				$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
				$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
				$g.DrawImage($src, 0, 0, $w, $h)
			} finally { $g.Dispose() }
			$out = Join-Path $tempDir ([IO.Path]::GetFileNameWithoutExtension($SourcePath) + '_x.png')
			$bmp.Save($out, [System.Drawing.Imaging.ImageFormat]::Png)
			$bmp.Dispose()
			return $out
		} finally { $src.Dispose() }
	}

	$dateRx = '(?i)\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{1,2},?\s+\d{4}\b'
	$locRx = "\b([A-Z][A-Za-z.\-' ]+?,\s+(?:[A-Z]{2}\b|[A-Z][a-z]+))"
	$nameRx = "^\s*([A-Z][A-Za-z'\-]*(?:\s+[A-Z][A-Za-z'\-]*)*\s+[A-Z]\.)"

	function Parse-OcrText([string]$Text) {
		$name = $null; $loc = $null; $date = $null
		$m = [regex]::Match($Text, $nameRx); if ($m.Success) { $name = $m.Groups[1].Value.Trim() }
		$m = [regex]::Match($Text, $dateRx); if ($m.Success) { $date = $m.Value.Trim(); $dateEnd = $m.Index + $m.Length } else { $dateEnd = -1 }
		$m = [regex]::Match($Text, $locRx); if ($m.Success) { $loc = $m.Groups[1].Value.Trim(); $locEnd = $m.Index + $m.Length } else { $locEnd = -1 }

		# Review text = everything after the date (Yelp lays out name/location/meta/date/body).
		$cut = [Math]::Max($dateEnd, $locEnd)
		$body = if ($cut -ge 0 -and $cut -lt $Text.Length) { $Text.Substring($cut) } else { $Text }
		# Strip common header noise: star runs, "N <topic> reviews", "@ N photos".
		$body = $body -replace '0{2,}', ' ' -replace '\b\d+\s+[A-Za-z ]+reviews\b', ' ' -replace '@\s*\d+\s*photos?', ' '
		$body = ($body -replace '\s+', ' ').Trim(' ', '.', ',', '-')

		$needs = [bool]([string]::IsNullOrEmpty($name) -or [string]::IsNullOrEmpty($date) -or [string]::IsNullOrEmpty($loc))
		return [ordered]@{ name = $name; location = $loc; date = $date; body = $body; needs = $needs }
	}

	$images = Get-ChildItem $InputDir -File | Where-Object { $_.Extension -in '.png', '.jpg', '.jpeg' } | Sort-Object Name
	$raw = New-Object System.Text.StringBuilder
	$rating = if ($DefaultRating -ge 0) { $DefaultRating } else { $null }

	$reviews = foreach ($img in $images) {
		$prep = Get-Upscaled $img.FullName $Upscale
		$file = Await ([Windows.Storage.StorageFile]::GetFileFromPathAsync($prep)) ([Windows.Storage.StorageFile])
		$stream = Await ($file.OpenAsync([Windows.Storage.FileAccessMode]::Read)) ([Windows.Storage.Streams.IRandomAccessStream])
		$decoder = Await ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
		$bitmap = Await ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
		$result = Await ($engine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])
		$stream.Dispose()

		[void]$raw.AppendLine("===== $($img.Name) =====").AppendLine($result.Text).AppendLine('')
		$p = Parse-OcrText $result.Text
		[ordered]@{
			sourceImage       = $img.Name
			name              = $p.name
			location          = $p.location
			rating            = $rating
			date              = $p.date
			reviewText        = $p.body
			needsVerification = $p.needs
		}
	}

	$rawPath = Join-Path $InputDir '_raw-ocr.txt'
	$raw.ToString() | Set-Content -Path $rawPath -Encoding UTF8

	$doc = [ordered]@{
		source           = "Screenshots in $InputDir"
		extractedOn      = (Get-Date).ToString('yyyy-MM-dd')
		extractionMethod = 'Offline Windows.Media.Ocr over images (best-effort structured parse)'
		notes            = @(
			"Star ratings are images, not text, so OCR cannot read them. 'rating' = $(if ($null -eq $rating) { 'null' } else { $rating }) (set via -DefaultRating).",
			"Rows with needsVerification=true are missing a confidently-parsed name/location/date; check them against the screenshot and _raw-ocr.txt."
		)
		reviewCount      = @($reviews).Count
		reviews          = @($reviews)
	}
	Write-Json $doc $Output
	Write-Host "OCR complete: $(@($reviews).Count) reviews -> $Output"
	Write-Host "Raw OCR text -> $rawPath"
	@($reviews) | ForEach-Object { [pscustomobject]$_ } | Select-Object name, location, date, needsVerification | Format-Table -AutoSize | Out-String | Write-Host
}

# ---------------------------------------------------------------------------
# Angi HTML (JSON-LD) mode
# ---------------------------------------------------------------------------

function Invoke-AngiMode {
	param([string]$InputDir, [string]$Output, [string]$HtmlFile)

	if (-not $InputDir -and -not $HtmlFile) { throw "Provide -InputDir or -HtmlFile for AngiHtml mode." }
	if (-not $HtmlFile) {
		$HtmlFile = (Get-ChildItem $InputDir -File | Where-Object { $_.Extension -in '.htm', '.html' } | Select-Object -First 1).FullName
		if (-not $HtmlFile) { throw "No .htm/.html file found in $InputDir." }
	}
	if (-not $Output) {
		$dir = if ($InputDir) { $InputDir } else { Split-Path $HtmlFile -Parent }
		$Output = Join-Path $dir 'reviews.json'
	}

	Add-Type -AssemblyName System.Web | Out-Null
	$html = Get-Content $HtmlFile -Raw

	$pattern = '(?s)\{"@type":"Review","reviewBody":"(?<body>.*?)","reviewRating":\{"@type":"Rating","ratingValue":(?<rating>\d+(?:\.\d+)?),.*?"datePublished":"(?<date>.*?)","author":\{"@type":"Person","name":"(?<name>.*?)"\}'
	$matches = [regex]::Matches($html, $pattern)

	$reviews = foreach ($m in $matches) {
		[ordered]@{
			sourcePage        = 'angi.com review page (JSON-LD)'
			name              = Decode-EntitiesStable $m.Groups['name'].Value
			location          = $null
			rating            = [double]$m.Groups['rating'].Value
			date              = Convert-IsoDate $m.Groups['date'].Value
			reviewText        = Decode-EntitiesStable $m.Groups['body'].Value
			needsVerification = $false
		}
	}

	$doc = [ordered]@{
		source           = 'Angi review page — ' + $Business
		extractedOn      = (Get-Date).ToString('yyyy-MM-dd')
		extractionMethod = 'Parsed schema.org JSON-LD from saved Angi reviews HTML'
		notes            = @(
			"Ratings, dates, names, and text are parsed directly from the page's JSON-LD, so they are exact (no OCR).",
			"Angi's structured data has no per-reviewer location, so 'location' is null."
		)
		reviewCount      = @($reviews).Count
		reviews          = @($reviews)
	}
	Write-Json $doc $Output
	Write-Host "Angi parse complete: $(@($reviews).Count) reviews -> $Output"
	@($reviews) | ForEach-Object { [pscustomobject]$_ } | Select-Object name, rating, date | Format-Table -AutoSize | Out-String | Write-Host
}

# ---------------------------------------------------------------------------
# Merge mode
# ---------------------------------------------------------------------------

function Invoke-MergeMode {
	param([string[]]$InputFiles, [string[]]$SourceNames, [string]$Output, [string]$Business)

	if ($InputFiles.Count -ne $SourceNames.Count) {
		throw "-InputFiles and -SourceNames must have the same number of entries."
	}
	if (-not $Output) { $Output = "C:\Users\tpeas\Business Tools\New Era\all-reviews.json" }

	$all = New-Object System.Collections.Generic.List[object]
	for ($i = 0; $i -lt $InputFiles.Count; $i++) {
		$path = $InputFiles[$i]; $label = $SourceNames[$i]
		if (-not (Test-Path $path)) { Write-Warning "Skipping missing file: $path"; continue }
		$data = Get-Content $path -Raw | ConvertFrom-Json
		foreach ($r in $data.reviews) {
			$all.Add([ordered]@{
					source     = $label
					name       = $r.name
					location   = $r.location
					rating     = $r.rating
					date       = $r.date
					reviewText = $r.reviewText
				})
		}
	}

	$doc = [ordered]@{
		business    = $Business
		mergedOn    = (Get-Date).ToString('yyyy-MM-dd')
		sources     = $SourceNames
		reviewCount = $all.Count
		reviews     = $all.ToArray()
	}
	Write-Json $doc $Output
	Write-Host "Merge complete: $($all.Count) reviews -> $Output"
	$all | ForEach-Object { [pscustomobject]$_ } | Group-Object source |
		Select-Object @{n = 'Source'; e = { $_.Name } }, Count | Format-Table -AutoSize | Out-String | Write-Host
}

# ---------------------------------------------------------------------------
# Dispatch
# ---------------------------------------------------------------------------

switch ($Mode) {
	'Ocr' { Invoke-OcrMode -InputDir $InputDir -Output $Output -Upscale $Upscale -DefaultRating $DefaultRating }
	'AngiHtml' { Invoke-AngiMode -InputDir $InputDir -Output $Output -HtmlFile $HtmlFile }
	'Merge' { Invoke-MergeMode -InputFiles $InputFiles -SourceNames $SourceNames -Output $Output -Business $Business }
}
