#requires -Version 5.1
<#
.SYNOPSIS
	Builds and launches the Business Tools Suite Blazor Web App locally, then opens it in the browser.

.DESCRIPTION
	Restores and builds the server host project (which hosts the WebAssembly client), starts it with
	the selected launch profile, and opens the default browser once the app is accepting connections.

	Run it from this folder:

		cd "Business Tools Suite"
		.\deploy-and-launch.ps1

	If PowerShell refuses with "is not digitally signed", the file is carrying the Mark of the Web
	because the project was downloaded as a zip. Clear it once with:

		Get-ChildItem -Recurse -Filter *.ps1 | Unblock-File

	The app serves the public marketing site at / anonymously; /admin redirects to a login.

.PARAMETER Configuration
	MSBuild configuration to build/run. Defaults to 'Release'.

.PARAMETER LaunchProfile
	Launch profile from Properties/launchSettings.json. 'https' (default) or 'http'.

.PARAMETER NoBuild
	Skip the explicit build step and run the previously built output.

.PARAMETER NoLaunchBrowser
	Start the server without opening a browser window.

.EXAMPLE
	.\deploy-and-launch.ps1
	Builds in Release, starts the app, and opens https://localhost:7159.

.EXAMPLE
	.\deploy-and-launch.ps1 -LaunchProfile http -NoBuild
	Runs the existing build and opens http://localhost:5173.
#>
[CmdletBinding()]
param(
	[string]$Configuration = 'Release',

	[ValidateSet('https', 'http')]
	[string]$LaunchProfile = 'https',

	[switch]$NoBuild,

	[switch]$NoLaunchBrowser
)

$ErrorActionPreference = 'Stop'

# Resolve the server host project relative to this script's location.
$root = $PSScriptRoot
$serverProject = Join-Path $root 'Business Tools Suite\Business Tools Suite.csproj'

if (-not (Test-Path $serverProject)) {
	throw "Server project not found at: $serverProject"
}

# Ports per launch profile, from Business Tools Suite\Properties\launchSettings.json.
# The https profile binds BOTH ports, so both have to be free for it to start.
if ($LaunchProfile -eq 'https') {
	$appUrl = 'https://localhost:7159'
	$ports  = @(7159, 5173)
	$urls   = 'https://localhost:7159;http://localhost:5173'
} else {
	$appUrl = 'http://localhost:5173'
	$ports  = @(5173)
	$urls   = 'http://localhost:5173'
}

Write-Host '==> Business Tools Suite - build & launch' -ForegroundColor Cyan
Write-Host "    Configuration : $Configuration"
Write-Host "    Launch profile: $LaunchProfile"
Write-Host "    URL           : $appUrl"
Write-Host ''

# --- Pre-flight: are the ports free? -----------------------------------------
# Without this, a server left running from a previous session fails deep inside
# Kestrel with "Failed to bind to address", which reads like a code fault rather
# than "something else is already using this port".
$blocked = @()
foreach ($port in $ports) {
	$conn = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
	if ($conn) {
		$owner = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
		$name  = if ($owner) { $owner.ProcessName } else { 'unknown' }
		$blocked += [pscustomobject]@{ Port = $port; Pid = $conn.OwningProcess; Name = $name }
	}
}

if ($blocked.Count -gt 0) {
	Write-Host '[ERR] Cannot start - these ports are already in use:' -ForegroundColor Red
	foreach ($b in $blocked) {
		Write-Host ("      port {0} -> PID {1} ({2})" -f $b.Port, $b.Pid, $b.Name) -ForegroundColor Red
	}
	Write-Host ''
	Write-Host '      Usually this is the app still running from a previous launch. Stop it with:' -ForegroundColor Yellow
	Write-Host ("      Stop-Process -Id {0} -Force" -f ($blocked[0].Pid)) -ForegroundColor Yellow
	exit 1
}

# --- HTTPS development certificate -------------------------------------------
if ($LaunchProfile -eq 'https') {
	dotnet dev-certs https --check --trust | Out-Null
	if ($LASTEXITCODE -ne 0) {
		Write-Host '==> Trusting ASP.NET Core HTTPS development certificate...' -ForegroundColor Yellow
		Write-Host '    (Windows may show a certificate prompt - accept it.)' -ForegroundColor Yellow
		dotnet dev-certs https --trust
	}
}

# --- Build --------------------------------------------------------------------
if ($NoBuild) {
	# dotnet run --no-build fails with an unhelpful message when nothing has been
	# built yet, so check for the output first and say so plainly.
	$assembly = Join-Path $root ("Business Tools Suite\bin\{0}\net10.0\Business_Tools_Suite.dll" -f $Configuration)
	if (-not (Test-Path $assembly)) {
		Write-Host "[ERR] -NoBuild was given but no $Configuration build exists yet." -ForegroundColor Red
		Write-Host "      Expected: $assembly" -ForegroundColor Red
		Write-Host '      Run once without -NoBuild first.' -ForegroundColor Yellow
		exit 1
	}
	Write-Host '==> Skipping build (-NoBuild)' -ForegroundColor Yellow
} else {
	Write-Host "==> Building ($Configuration)..." -ForegroundColor Cyan
	dotnet build $serverProject -c $Configuration
	if ($LASTEXITCODE -ne 0) { throw "Build failed with exit code $LASTEXITCODE." }
}

# --- Run ----------------------------------------------------------------------
# The launch profiles set "launchBrowser": true, so dotnet run opens the browser
# itself. This script used to open a second one as well, which is why two tabs
# appeared -- and why -NoLaunchBrowser did nothing, since it only suppressed the
# script's own tab and not the profile's. Suppressing it properly means running
# without the profile and supplying its settings directly.
Write-Host '==> Starting server (press Ctrl+C to stop)...' -ForegroundColor Cyan
Write-Host ''

if ($NoLaunchBrowser) {
	$env:ASPNETCORE_URLS        = $urls
	$env:ASPNETCORE_ENVIRONMENT = 'Development'
	dotnet run --project $serverProject -c $Configuration --no-launch-profile --no-build
} else {
	dotnet run --project $serverProject -c $Configuration --launch-profile $LaunchProfile --no-build
}
