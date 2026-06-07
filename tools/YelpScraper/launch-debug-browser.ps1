<#
.SYNOPSIS
  Launches a Chromium browser (Edge or Chrome) with remote debugging enabled so the
  YelpScraper tool can attach to it via --connect.

.DESCRIPTION
  DataDome (Yelp's anti-bot) blocks automation-controlled browsers. The reliable
  workaround is: open your OWN browser with a debugging port, solve the CAPTCHA
  yourself, then attach the scraper to that already-cleared session.

.EXAMPLE
  .\launch-debug-browser.ps1
  # ...solve the CAPTCHA in the window that opens, then in another terminal:
  dotnet run -- --connect 9222
#>
param(
	[int]$Port = 9222,
	[string]$Url = "https://www.yelp.com/biz/new-era-steam-cleaning-and-restoration-sugar-land?start=0"
)

$profileDir = Join-Path $env:LOCALAPPDATA "YelpScraper\debug-profile"
New-Item -ItemType Directory -Force -Path $profileDir | Out-Null

$candidates = @(
	"$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
	"${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
	"$env:LocalAppData\Google\Chrome\Application\chrome.exe",
	"$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
	"${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
)

$browser = $candidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $browser) {
	Write-Error "No Chrome or Edge found. Install one, or edit this script."
	exit 1
}

Write-Host "Launching: $browser"
Write-Host "Debug port: $Port"
Write-Host "Profile   : $profileDir"
Write-Host ""
Write-Host "When the window opens, solve the Yelp CAPTCHA, then run:"
Write-Host "    dotnet run -- --connect $Port"

& $browser "--remote-debugging-port=$Port" "--user-data-dir=$profileDir" $Url
