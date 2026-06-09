<#
.SYNOPSIS
	Builds the Business Tools Suite, commits the current state to Git, and triggers
	deployment to the Azure Web App "NewEraAdmin".

.DESCRIPTION
	Deployment to Azure is handled by the GitHub Actions workflow
	'.github/workflows/main_neweraadmin.yml', which runs automatically on every push to
	the 'main' branch (build .slnx -> publish server project -> azure/webapps-deploy).

	This script:
	  1. Verifies the required tooling (dotnet, git) is available.
	  2. Restores, builds, and publishes the server project in Release as a pre-flight
		 check so a broken build is never pushed (skip with -SkipBuild).
	  3. Stages, commits, and pushes the current changes to the target branch.
	  4. Because pushing to 'main' triggers the pipeline, the push deploys to Azure.
		 With -Watch (and the GitHub CLI 'gh' installed) the run is followed to completion.

.PARAMETER Message
	Git commit message. Defaults to a timestamped deploy message.

.PARAMETER Branch
	Branch to push. Defaults to the current branch. Azure deployment only runs from 'main'.

.PARAMETER SkipBuild
	Skip the local Release build/publish verification step.

.PARAMETER Watch
	Watch the triggered GitHub Actions deployment run (requires the GitHub CLI 'gh').

.EXAMPLE
	.\deploy.ps1 -Message "Add client profile contact actions"

.EXAMPLE
	.\deploy.ps1 -Message "Hotfix login allow-list" -Watch
#>
[CmdletBinding()]
param(
	[string]$Message,
	[string]$Branch,
	[switch]$SkipBuild,
	[switch]$Watch
)

$ErrorActionPreference = 'Stop'

# --- Constants (match .github/workflows/main_neweraadmin.yml) -----------------
$AppName       = 'NewEraAdmin'
$DeployBranch  = 'main'
$SolutionPath  = Join-Path $PSScriptRoot 'Business Tools Suite.slnx'
$ServerProject = Join-Path $PSScriptRoot 'Business Tools Suite\Business Tools Suite.csproj'

# --- Helpers -----------------------------------------------------------------
function Write-Step($text) { Write-Host "`n==> $text" -ForegroundColor Cyan }
function Write-Ok($text)   { Write-Host "[OK]  $text" -ForegroundColor Green }
function Fail($text)       { Write-Host "[ERR] $text" -ForegroundColor Red; exit 1 }

function Test-Tool($name) {
	if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
		Fail "Required tool '$name' was not found on PATH."
	}
}

# --- 0. Pre-flight ------------------------------------------------------------
Write-Step "Checking tooling"
Test-Tool dotnet
Test-Tool git
if (-not (Test-Path $SolutionPath))  { Fail "Solution not found: $SolutionPath" }
if (-not (Test-Path $ServerProject)) { Fail "Server project not found: $ServerProject" }
Write-Ok "dotnet and git found"

# Locate the Git repository root so commits/pushes work regardless of CWD.
$repoRoot = (git -C $PSScriptRoot rev-parse --show-toplevel 2>$null)
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($repoRoot)) {
	Fail "This directory is not inside a Git repository."
}
Set-Location $repoRoot
Write-Ok "Repository root: $repoRoot"

# Resolve the working branch.
if ([string]::IsNullOrWhiteSpace($Branch)) {
	$Branch = (git rev-parse --abbrev-ref HEAD).Trim()
}
Write-Ok "Target branch: $Branch"
if ($Branch -ne $DeployBranch) {
	Write-Host "[WARN] Azure deployment only runs from '$DeployBranch'. Pushing '$Branch' will NOT deploy." -ForegroundColor Yellow
}

# --- 1. Build / publish verification -----------------------------------------
if ($SkipBuild) {
	Write-Host "[WARN] Skipping local build verification (-SkipBuild)." -ForegroundColor Yellow
} else {
	Write-Step "Restoring & building (Release)"
	dotnet build $SolutionPath --configuration Release
	if ($LASTEXITCODE -ne 0) { Fail "Build failed. Nothing was pushed." }
	Write-Ok "Build succeeded"

	Write-Step "Publishing server project (Release) to verify deploy package"
	$publishDir = Join-Path $env:TEMP 'neweraadmin-publish'
	dotnet publish $ServerProject -c Release -o $publishDir | Out-Null
	if ($LASTEXITCODE -ne 0) { Fail "Publish failed. Nothing was pushed." }
	Write-Ok "Publish succeeded ($publishDir)"
}

# --- 2. Commit ----------------------------------------------------------------
Write-Step "Staging changes"
git add -A

$pending = git status --porcelain
if ([string]::IsNullOrWhiteSpace($pending)) {
	Write-Host "[WARN] No changes to commit." -ForegroundColor Yellow
	$hasCommit = $false
} else {
	if ([string]::IsNullOrWhiteSpace($Message)) {
		$Message = "Deploy: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
	}
	git commit -m $Message
	if ($LASTEXITCODE -ne 0) { Fail "git commit failed." }
	Write-Ok "Committed: $Message"
	$hasCommit = $true
}

# --- 3. Push (triggers Azure deployment when branch is 'main') ---------------
Write-Step "Pushing to origin/$Branch"
git push origin $Branch
if ($LASTEXITCODE -ne 0) { Fail "git push failed." }
Write-Ok "Pushed to origin/$Branch"

if ($Branch -eq $DeployBranch) {
	if ($hasCommit) {
		Write-Host "`nDeployment to Azure Web App '$AppName' triggered via GitHub Actions." -ForegroundColor Green
	} else {
		Write-Host "`nNo new commit; the existing 'main' is already deployed. Use 'gh workflow run' to redeploy." -ForegroundColor Yellow
	}
} else {
	Write-Host "`nPushed '$Branch'. Open a PR into '$DeployBranch' to deploy to Azure." -ForegroundColor Yellow
}

# --- 4. Optionally watch the deployment --------------------------------------
if ($Watch -and $Branch -eq $DeployBranch -and $hasCommit) {
	if (Get-Command gh -ErrorAction SilentlyContinue) {
		Write-Step "Watching GitHub Actions deployment (Ctrl+C to stop)"
		Start-Sleep -Seconds 5
		gh run watch --exit-status
	} else {
		Write-Host "[WARN] GitHub CLI 'gh' not found; cannot watch the run. Track it on GitHub > Actions." -ForegroundColor Yellow
	}
}
