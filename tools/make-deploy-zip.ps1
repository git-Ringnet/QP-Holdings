<#
.SYNOPSIS
  Tạo gói zip deploy (theme + plugin tự viết) để upload lên hosting Plesk.

.DESCRIPTION
  - Mặc định lấy file thay đổi kể từ tag deploy-* gần nhất đến HEAD.
  - -Full: đóng gói toàn bộ file đang được git theo dõi trong wp-content/.
  - -Since <ref>: chọn mốc so sánh khác.
  - Kiểm tra qp-llm-translate/theme-updates/ có khớp theme không (plugin sẽ copy đè theme khi đổi version).
    -SyncThemeUpdates: copy bản theme sang theme-updates/ để khớp lại.
  Kết quả: dist\deploy-<time>\qp-deploy-<time>.zip, FILES.txt, DELETED.txt

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File tools\make-deploy-zip.ps1 -Full
#>
[CmdletBinding()]
param(
    [string]$Since,
    [switch]$Full,
    [switch]$SyncThemeUpdates
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root

function Invoke-Git {
    $out = & git -c core.quotepath=false @args
    if ($LASTEXITCODE -ne 0) { throw "git $($args -join ' ') thất bại (exit $LASTEXITCODE)" }
    return $out
}

# --- 0. Phải là repo riêng của dự án, không phải repo ở ổ C:\ ---
$top = (Invoke-Git rev-parse --show-toplevel | Select-Object -First 1) -replace '/', '\'
if ($top.TrimEnd('\') -ne $root.TrimEnd('\')) {
    throw "Gốc git là '$top', không phải '$root'. Hãy chạy 'git init' trong thư mục dự án."
}

# --- 1. Kiểm tra theme-updates khớp theme ---
$themeDir = Join-Path $root 'wp-content\themes\qp-holdings'
$updDir   = Join-Path $root 'wp-content\plugins\qp-llm-translate\theme-updates'
if (Test-Path $updDir) {
    $drift = @()
    Get-ChildItem $updDir -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($updDir.Length + 1)
        $themeFile = Join-Path $themeDir $rel
        if (-not (Test-Path $themeFile)) {
            Write-Warning "theme-updates có '$rel' nhưng theme không có file này."
            return
        }
        $a = (Get-FileHash $_.FullName -Algorithm SHA256).Hash
        $b = (Get-FileHash $themeFile -Algorithm SHA256).Hash
        if ($a -ne $b) { $drift += [pscustomobject]@{ Rel = $rel; Src = $themeFile; Dst = $_.FullName } }
    }
    if ($drift.Count -gt 0) {
        if ($SyncThemeUpdates) {
            foreach ($d in $drift) { Copy-Item $d.Src $d.Dst -Force; Write-Host "Đồng bộ theme -> theme-updates: $($d.Rel)" }
            Write-Host ''
            Write-Host 'Đã đồng bộ. Hãy commit các thay đổi trong theme-updates/ rồi chạy lại script.' -ForegroundColor Yellow
            exit 2
        }
        Write-Host 'DỪNG: các file sau trong qp-llm-translate/theme-updates/ KHÁC với theme:' -ForegroundColor Red
        $drift | ForEach-Object { Write-Host "  - $($_.Rel)" }
        Write-Host 'Nếu deploy như vậy, lần tăng version plugin kế tiếp sẽ ghi đè theme bằng bản cũ.'
        Write-Host 'Chạy lại với -SyncThemeUpdates (theme là bản đúng), commit, rồi chạy lại.'
        exit 1
    }
}

# Chỉ đóng gói CODE TỰ VIẾT. Repo còn chứa lõi WordPress, plugin bên thứ ba, wp-config.php và
# .htaccess bản DEV — những thứ đó tuyệt đối không được đưa lên hosting.
$DeployPaths = @(
    'wp-content/themes/qp-holdings',
    'wp-content/plugins/qp-auto-translate',
    'wp-content/plugins/qp-llm-translate',
    'wp-content/plugins/qp-webp-convert',
    'wp-content/mu-plugins/qpholdings-admin-branding.php',
    'wp-content/mu-plugins/qpholdings-admin-branding'
)

# --- 2. Không cho chạy khi còn thay đổi chưa commit ---
$dirty = Invoke-Git status --porcelain -- @DeployPaths
if ($dirty) {
    Write-Host 'DỪNG: còn thay đổi chưa commit trong code sẽ deploy:' -ForegroundColor Red
    $dirty | ForEach-Object { Write-Host "  $_" }
    exit 1
}

# --- 3. Xác định danh sách file ---
$deleted = @()
if ($Full) {
    $files = Invoke-Git ls-files -- @DeployPaths
    $label = 'TOÀN BỘ code'
} else {
    if (-not $Since) {
        $Since = & git describe --tags --match 'deploy-*' --abbrev=0 2>$null
        if ($LASTEXITCODE -ne 0 -or -not $Since) {
            throw "Chưa có tag deploy-* nào. Lần đầu hãy chạy với -Full, hoặc chỉ định -Since <commit>."
        }
    }
    $files   = Invoke-Git diff --name-only --no-renames --diff-filter=ACMT $Since HEAD -- @DeployPaths
    $deleted = Invoke-Git diff --name-only --no-renames --diff-filter=D $Since HEAD -- @DeployPaths
    $label = "thay đổi từ $Since đến HEAD"
}
$files   = @($files | Where-Object { $_ })
$deleted = @($deleted | Where-Object { $_ })

if ($files.Count -eq 0 -and $deleted.Count -eq 0) {
    Write-Host "Không có thay đổi nào ($label). Không cần deploy."
    exit 0
}

# --- 4. Đóng gói ---
$stamp  = Get-Date -Format 'yyyyMMdd-HHmmss'
$outDir = Join-Path $root "dist\deploy-$stamp"
New-Item -ItemType Directory -Force $outDir | Out-Null
$zipPath = Join-Path $outDir "qp-deploy-$stamp.zip"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($rel in $files) {
        $absPath = Join-Path $root ($rel -replace '/', '\')
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip, $absPath,($rel -replace '\\', '/'), [System.IO.Compression.CompressionLevel]::Optimal)
    }
} finally {
    $zip.Dispose()
}

$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllLines((Join-Path $outDir 'FILES.txt'), [string[]]$files, $utf8)
[System.IO.File]::WriteAllLines((Join-Path $outDir 'DELETED.txt'), [string[]]$deleted, $utf8)

# --- 5. Tóm tắt ---
$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
Write-Host ''
Write-Host "Gói deploy ($label):" -ForegroundColor Green
Write-Host "  Zip     : $zipPath ($sizeMb MB)"
Write-Host "  Ghi đè  : $($files.Count) file  (FILES.txt)"
Write-Host "  Cần xoá : $($deleted.Count) file  (DELETED.txt)"
if ($files -contains 'wp-content/plugins/qp-llm-translate/qp-llm-translate.php') {
    Write-Host '  Lưu ý  : file chính của plugin qp-llm-translate có thay đổi (kiểm tra version).' -ForegroundColor Yellow
}
Write-Host ''
Write-Host 'Bước tiếp theo: làm theo docs/ai/deploy-plesk.md mục 3.'
Write-Host "Sau khi site chạy tốt: git tag deploy-$stamp"
