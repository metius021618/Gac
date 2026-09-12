# Free disk space then run deploy pipeline; log everything to E: drive
$ErrorActionPreference = 'Continue'
$log = 'E:\TRABAJO\STREAMIN\gac\SISTEMA_GAC\_deploy_run.log'
function Log($m) { Add-Content -Path $log -Value $m }

'' | Set-Content -Path $log -Encoding UTF8
Log "=== START $(Get-Date -Format o) ==="

try {
  Get-PSDrive -PSProvider FileSystem | ForEach-Object {
    Log ("DRIVE {0}: Used={1:N0} Free={2:N0}" -f $_.Name, $_.Used, $_.Free)
  }
} catch { Log "drive check: $_" }

# Aggressive temp cleanup
$paths = @(
  $env:TEMP,
  "$env:LOCALAPPDATA\Temp",
  "$env:LOCALAPPDATA\npm-cache",
  "$env:LOCALAPPDATA\pip\Cache",
  "$env:USERPROFILE\.cache"
)
foreach ($p in $paths) {
  if (Test-Path $p) {
    Log "Cleaning $p"
    Get-ChildItem $p -Force -ErrorAction SilentlyContinue | ForEach-Object {
      Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
    }
  }
}

# Clean Cursor project terminals older junk if any
$termDir = 'C:\Users\MATHIAS\.cursor\projects\e-TRABAJO-STREAMIN-gac-SISTEMA-GAC\terminals'
if (Test-Path $termDir) {
  Get-ChildItem $termDir -Filter '*.txt' -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
}

Log "=== AFTER CLEAN ==="
try {
  Get-PSDrive -PSProvider FileSystem | ForEach-Object {
    Log ("DRIVE {0}: Used={1:N0} Free={2:N0}" -f $_.Name, $_.Used, $_.Free)
  }
} catch { Log "drive check2: $_" }

Set-Location 'E:\TRABAJO\STREAMIN\gac\SISTEMA_GAC'
Log "=== gh auth switch ==="
gh auth switch --user metius021618 2>&1 | ForEach-Object { Log $_ }
Log "gh exit: $LASTEXITCODE"

Log "=== git status ==="
git status -sb 2>&1 | ForEach-Object { Log $_ }
Log "=== git log ==="
git log -2 --oneline 2>&1 | ForEach-Object { Log $_ }

$status = git status --porcelain
if ($status) {
  Log "=== committing ==="
  git add . 2>&1 | ForEach-Object { Log $_ }
  git commit -m "Add asuntos especiales: body match, discard/save, consult gating." 2>&1 | ForEach-Object { Log $_ }
  Log "commit exit: $LASTEXITCODE"
} else {
  Log "No uncommitted changes"
}

Log "=== push ==="
git push origin main 2>&1 | ForEach-Object { Log $_ }
Log "push exit: $LASTEXITCODE"

if ($LASTEXITCODE -ne 0) {
  Log "=== retry gh auth + push ==="
  gh auth switch --user metius021618 2>&1 | ForEach-Object { Log $_ }
  git push origin main 2>&1 | ForEach-Object { Log $_ }
  Log "push2 exit: $LASTEXITCODE"
}

Log "=== SSH deploy ==="
$sshCmd = 'cd ~/www/new.pocoyoni.com; git pull origin main; bash scripts/sync_public_html_assets.sh; python3 scripts/migrate_asuntos_especiales.py; python3 scripts/seed_asuntos_especiales_netflix.py; python3 scripts/test_asuntos_especiales_cron_sim.py; python3 -m unittest cron.tests.test_asuntos_especiales_classify -v; git log -1 --oneline'
ssh -o BatchMode=yes -o ConnectTimeout=15 -p 18765 u2553-kpdyyrivjtwb@gtxm1328.siteground.biz $sshCmd 2>&1 | ForEach-Object { Log $_ }
Log "ssh exit: $LASTEXITCODE"

Log "=== final status ==="
git status -sb 2>&1 | ForEach-Object { Log $_ }
git log -1 --oneline 2>&1 | ForEach-Object { Log $_ }
Log "=== END $(Get-Date -Format o) ==="
