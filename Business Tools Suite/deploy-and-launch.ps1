#requires -Version 5.1
<#
.SYNOPSIS
	Builds and launches the Business Tools Suite Blazor Web App locally, then opens it in the browser.

.DESCRIPTION
	Restores and builds the server host project (which hosts the WebAssembly client), starts it with
	the selected launch profile, and opens the default browser once the app is accepting connections.

.PARAMETER Configuration
	MSBuild configuration to build/run. Defaults to 'Release'.

.PARAMETER LaunchProfile
	Launch profile from Properties/launchSettings.json. 'https' (default) or 'http'.

.PARAMETER NoBuild
	Skip the explicit build step and run the previously built output.

.PARAMETER NoLaunchBrowser
	Start the server without opening a browser window.

.EXAMPLE
	./deploy-and-launch.ps1
	Builds in Release, starts the app, and opens https://localhost:7159.

.EXAMPLE
	./deploy-and-launch.ps1 -LaunchProfile http -NoBuild
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

# App URL per launch profile (see Business Tools Suite\Properties\launchSettings.json).
$appUrl = if ($LaunchProfile -eq 'https') { 'https://localhost:7159' } else { 'http://localhost:5173' }

Write-Host '==> Business Tools Suite — deploy & launch' -ForegroundColor Cyan
Write-Host "    Configuration : $Configuration"
Write-Host "    Launch profile: $LaunchProfile"
Write-Host "    URL           : $appUrl"
Write-Host ''

# Ensure the local HTTPS dev certificate is trusted (no-op if already trusted).
if ($LaunchProfile -eq 'https') {
	dotnet dev-certs https --check --trust | Out-Null
	if ($LASTEXITCODE -ne 0) {
		Write-Host '==> Trusting ASP.NET Core HTTPS development certificate...' -ForegroundColor Yellow
		dotnet dev-certs https --trust
	}
}

# Build the host (and the referenced WebAssembly client) unless skipped.
if (-not $NoBuild) {
	Write-Host "==> Building ($Configuration)..." -ForegroundColor Cyan
	dotnet build $serverProject -c $Configuration
	if ($LASTEXITCODE -ne 0) { throw "Build failed with exit code $LASTEXITCODE." }
}

# Open the browser once the server is accepting connections.
$browserJob = $null
if (-not $NoLaunchBrowser) {
	$browserJob = Start-Job -ScriptBlock {
		param($url)
		$uri = [Uri]$url
		for ($i = 0; $i -lt 60; $i++) {
			try {
				$client = [System.Net.Sockets.TcpClient]::new()
				$client.Connect($uri.Host, $uri.Port)
				$client.Close()
				Start-Sleep -Milliseconds 750
				Start-Process $url
				return
			}
			catch {
				Start-Sleep -Seconds 1
			}
		}
	} -ArgumentList $appUrl
}

# Run the server host in the foreground (Ctrl+C to stop). It was just built, so skip the rebuild.
Write-Host '==> Starting server (press Ctrl+C to stop)...' -ForegroundColor Cyan
try {
	dotnet run --project $serverProject -c $Configuration --launch-profile $LaunchProfile --no-build
}
finally {
	if ($browserJob) { Remove-Job $browserJob -Force -ErrorAction SilentlyContinue }
}
