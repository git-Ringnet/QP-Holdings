<#
.SYNOPSIS
  Dựng site QP Holdings ở local từ file backup Plesk (backup_qpholdings.vn_*.tar).

.DESCRIPTION
  1. Giải nén lõi WordPress, plugin bên thứ ba, uploads từ backup vào thư mục repo
     (KHÔNG đè code trong git: theme qp-holdings, plugin qp-llm-translate, mu-plugin branding;
      KHÔNG lấy wp-config.php / .htaccess / .user.ini của production; bỏ cache, log, backup rác).
  2. Import database (tự bỏ dòng "sandbox mode" mà MariaDB < 10.5.25 của XAMPP không đọc được).
  3. Tạo wp-config.php + .htaccess cho local nếu chưa có.
  4. Đổi URL production -> local bằng WP-CLI (an toàn với dữ liệu serialize),
     tắt Wordfence + LiteSpeed Cache ở local.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File tools\restore-plesk-backup.ps1 -Backup "$env:USERPROFILE\Downloads\backup_qpholdings.vn_2611041217.tar"
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$Backup,
    [string]$SiteRoot,
    [string]$DbName   = 'qpholdings',
    [string]$DbUser   = 'root',
    [string]$DbPass   = '',
    [string]$DbHost   = 'localhost',
    [string]$MysqlBin = 'C:\xampp\mysql\bin',
    [string]$PhpExe   = 'C:\xampp\php\php.exe',
    [string]$LocalUrl = 'http://localhost/qpholdings',
    [string]$ProdUrl  = 'https://qpholdings.vn',
    [switch]$SkipFiles,
    [switch]$SkipDb,
    [switch]$Force
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

if (-not $SiteRoot) { $SiteRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path }
$Backup = (Resolve-Path $Backup).Path
$work = Join-Path $env:TEMP ("qp-restore-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
New-Item -ItemType Directory -Force $work | Out-Null
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Step($msg) { Write-Host ''; Write-Host "==> $msg" -ForegroundColor Cyan }

# Chạy lệnh qua file .cmd để pipe nhị phân (tar | tar, mysql < file) không bị PowerShell làm hỏng.
function Invoke-CmdLine([string]$line, [switch]$Capture) {
    $bat = Join-Path $work ("run-" + [guid]::NewGuid().ToString('N').Substring(0, 6) + '.cmd')
    [System.IO.File]::WriteAllText($bat, "@echo off`r`nchcp 65001 >nul`r`n$line`r`nexit /b %ERRORLEVEL%`r`n", $utf8)
    if ($Capture) {
        return (& cmd.exe /d /c "`"$bat`"")
    }
    & cmd.exe /d /c "`"$bat`"" | Out-Host
    return $LASTEXITCODE
}

function Get-MysqlArgs {
    $a = "-u $DbUser -h $DbHost"
    if ($DbPass) { $a += " -p$DbPass" }
    return $a
}

try {
    # --- Tìm các thành phần trong backup ---
    Step 'Đọc danh sách thành phần trong backup'
    $entries = & tar -tf $Backup
    if ($LASTEXITCODE -ne 0) { throw "Không đọc được file backup: $Backup" }
    $userData = $entries | Where-Object { $_ -match '^backup_user-data_.*\.tzst$' } | Select-Object -First 1
    $sqlDump  = $entries | Where-Object { $_ -match '^databases/.+/backup_sqldump_.*\.tzst$' } | Select-Object -First 1
    if (-not $userData) { throw 'Không thấy backup_user-data_*.tzst — đây có phải backup Plesk không?' }
    if (-not $sqlDump)  { throw 'Không thấy databases/*/backup_sqldump_*.tzst trong backup.' }
    Write-Host "  Files : $userData"
    Write-Host "  DB    : $sqlDump"

    # --- 1. Files ---
    if (-not $SkipFiles) {
        Step "Giải nén file website vào $SiteRoot (mất vài phút, ~6 GB)"
        $excludes = @(
            'httpdocs/wp-config.php', 'httpdocs/.htaccess', 'httpdocs/.htaccess.*', 'httpdocs/.user.ini',
            'httpdocs/wordfence-waf.php', 'httpdocs/public_html', 'httpdocs/error_log', 'httpdocs/*.log',
            'httpdocs/wp-content/themes/qp-holdings',
            'httpdocs/wp-content/plugins/qp-llm-translate',
            'httpdocs/wp-content/mu-plugins/qpholdings-admin-branding*',
            'httpdocs/wp-content/cache', 'httpdocs/wp-content/litespeed', 'httpdocs/wp-content/wflogs',
            'httpdocs/wp-content/backups-dup-pro', 'httpdocs/wp-content/upgrade', 'httpdocs/wp-content/upgrade-temp-backup',
            'httpdocs/wp-content/temp-write-test-*', 'httpdocs/wp-content/debug.log', 'httpdocs/wp-content/maintenance*'
        )
        $ex = ($excludes | ForEach-Object { "--exclude `"$_`"" }) -join ' '
        $code = Invoke-CmdLine "tar -xOf `"$Backup`" `"$userData`" | tar -xf - -C `"$SiteRoot`" --strip-components 1 $ex httpdocs"
        if ($code -ne 0) { throw "Giải nén file thất bại (exit $code)." }
        Write-Host '  Xong.'
    }

    # --- 2. Database ---
    if (-not $SkipDb) {
        Step 'Giải nén database'
        & tar -xf $Backup -C $work $sqlDump
        if ($LASTEXITCODE -ne 0) { throw 'Không giải nén được file dump.' }
        $inner = Join-Path $work ($sqlDump -replace '/', '\')
        $rawSql = Join-Path $work 'dump-raw.sql'
        $code = Invoke-CmdLine "tar -xOf `"$inner`" > `"$rawSql`""
        if ($code -ne 0) { throw 'Không giải nén được file .tzst của database.' }

        # Bỏ dòng "/*M!999999\- enable the sandbox mode */" theo byte (không đọc như text để giữ dữ liệu binary)
        $sqlFile = Join-Path $work 'dump.sql'
        $in = [System.IO.File]::OpenRead($rawSql)
        try {
            $head = New-Object byte[] 64; $n = $in.Read($head, 0, 64)
            $txt = [System.Text.Encoding]::ASCII.GetString($head, 0, $n)
            $in.Position = if ($txt.StartsWith('/*M!999999')) { $txt.IndexOf("`n") + 1 } else { 0 }
            $out = [System.IO.File]::Create($sqlFile)
            try { $in.CopyTo($out) } finally { $out.Close() }
        } finally { $in.Close() }
        Remove-Item $rawSql -Force

        $prefixMatch = Select-String -Path $sqlFile -Pattern 'CREATE TABLE `([A-Za-z0-9_]+)options`' | Select-Object -First 1
        $script:TablePrefix = if ($prefixMatch) { $prefixMatch.Matches[0].Groups[1].Value } else { 'wp_' }
        Write-Host "  Tiền tố bảng: $TablePrefix"

        Step "Import vào database '$DbName'"
        $mysql = Join-Path $MysqlBin 'mysql.exe'
        $margs = Get-MysqlArgs
        if ($DbName -notmatch '^[A-Za-z0-9_]+$') { throw 'DbName chỉ được chứa chữ, số, dấu gạch dưới.' }
        $exists = Invoke-CmdLine "`"$mysql`" $margs -N -e `"SHOW DATABASES LIKE '$DbName'`"" -Capture
        if ($exists) {
            if (-not $Force) { throw "Database '$DbName' đã tồn tại. Thêm -Force để xoá và import lại, hoặc dùng -DbName khác." }
            Write-Host "  Xoá database cũ '$DbName' (-Force)" -ForegroundColor Yellow
            if ((Invoke-CmdLine "`"$mysql`" $margs -e `"DROP DATABASE $DbName`"") -ne 0) { throw 'Không xoá được database cũ.' }
        }
        if ((Invoke-CmdLine "`"$mysql`" $margs -e `"CREATE DATABASE $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`"") -ne 0) {
            throw 'Không tạo được database. MySQL trong XAMPP đã bật chưa?'
        }
        $code = Invoke-CmdLine "`"$mysql`" $margs --binary-mode --default-character-set=utf8mb4 --max-allowed-packet=512M $DbName < `"$sqlFile`""
        if ($code -ne 0) { throw "Import database thất bại (exit $code)." }
        Write-Host '  Xong.'
    } else {
        $script:TablePrefix = 'wp_'
    }

    # --- 3. wp-config.php ---
    $cfg = Join-Path $SiteRoot 'wp-config.php'
    $script:CfgDbMatches = $true
    if (Test-Path $cfg) {
        Step 'wp-config.php đã có — giữ nguyên.'
        $m = Select-String -Path $cfg -Pattern "define\(\s*'DB_NAME'\s*,\s*'([^']*)'" | Select-Object -First 1
        $cfgDb = if ($m) { $m.Matches[0].Groups[1].Value } else { '' }
        if ($cfgDb -ne $DbName) {
            $script:CfgDbMatches = $false
            Write-Warning "wp-config.php đang dùng DB_NAME='$cfgDb', khác '$DbName' vừa import → BỎ QUA bước đổi URL bằng WP-CLI để không sửa nhầm database."
        }
    } else {
        Step 'Tạo wp-config.php cho local'
        $sample = Join-Path $SiteRoot 'wp-config-sample.php'
        if (-not (Test-Path $sample)) { throw 'Thiếu wp-config-sample.php (lõi WordPress chưa có — đừng dùng -SkipFiles).' }
        $c = [System.IO.File]::ReadAllText($sample)
        $c = $c.Replace('database_name_here', $DbName).Replace('username_here', $DbUser).Replace('password_here', $DbPass)
        $c = $c.Replace("define( 'DB_HOST', 'localhost' );", "define( 'DB_HOST', '$DbHost' );")
        $c = $c.Replace("`$table_prefix = 'wp_';", "`$table_prefix = '$TablePrefix';")
        $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_[]{}<>~+=,.;:/?|'
        while ($c.Contains('put your unique phrase here')) {
            $bytes = New-Object byte[] 64; $rng.GetBytes($bytes)
            $salt = -join ($bytes | ForEach-Object { $chars[$_ % $chars.Length] })
            $i = $c.IndexOf('put your unique phrase here')
            $c = $c.Substring(0, $i) + $salt + $c.Substring($i + 'put your unique phrase here'.Length)
        }
        $extra = @"
define( 'WP_HOME', '$LocalUrl' );
define( 'WP_SITEURL', '$LocalUrl' );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

"@
        $marker = "/* That's all, stop editing!"
        $i = $c.IndexOf($marker)
        if ($i -lt 0) { $c += "`n" + $extra } else { $c = $c.Substring(0, $i) + $extra + $c.Substring($i) }
        [System.IO.File]::WriteAllText($cfg, $c, $utf8)
        Write-Host '  Xong (khoá bảo mật được sinh ngẫu nhiên).'
    }

    # --- 4. .htaccess ---
    $ht = Join-Path $SiteRoot '.htaccess'
    if (-not (Test-Path $ht)) {
        Step 'Tạo .htaccess cho local'
        $base = ([Uri]$LocalUrl).AbsolutePath.TrimEnd('/') + '/'
        $rules = @"
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase $base
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . ${base}index.php [L]
</IfModule>
# END WordPress
"@
        [System.IO.File]::WriteAllText($ht, $rules.Replace("`r`n", "`n"), $utf8)
    }

    # --- 5. WP-CLI: đổi URL, tắt plugin không cần ở local ---
    if (-not $SkipDb -and $CfgDbMatches) {
        Step "Đổi URL $ProdUrl -> $LocalUrl"
        $wpCmd = $null
        if (Get-Command wp -ErrorAction SilentlyContinue) {
            $wpCmd = 'wp'
        } else {
            $cache = Join-Path $SiteRoot 'tools\.cache'
            $phar = Join-Path $cache 'wp-cli.phar'
            if (-not (Test-Path $phar)) {
                New-Item -ItemType Directory -Force $cache | Out-Null
                Write-Host '  Tải WP-CLI...'
                [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
                Invoke-WebRequest 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar' -OutFile $phar -UseBasicParsing
            }
            $wpCmd = "`"$PhpExe`" `"$phar`""
        }
        $wpBase = "$wpCmd --path=`"$SiteRoot`" --skip-plugins --skip-themes"
        $prodHost = ([Uri]$ProdUrl).Host
        $localNoScheme = $LocalUrl -replace '^https?:', ''
        $pairs = @(
            @($ProdUrl, $LocalUrl),
            @("http://$prodHost", $LocalUrl),
            @("https://www.$prodHost", $LocalUrl),
            @("//$prodHost", $localNoScheme)
        )
        foreach ($p in $pairs) {
            Write-Host "  $($p[0]) -> $($p[1])"
            $code = Invoke-CmdLine "$wpBase search-replace `"$($p[0])`" `"$($p[1])`" --all-tables-with-prefix --precise --report-changed-only"
            if ($code -ne 0) { Write-Warning "search-replace '$($p[0])' lỗi (exit $code)." }
        }
        Step 'Tắt Wordfence, LiteSpeed Cache ở local; flush permalink'
        [void](Invoke-CmdLine "$wpBase plugin deactivate wordfence litespeed-cache")
        [void](Invoke-CmdLine "$wpBase rewrite flush")
    }

    Step 'Hoàn tất'
    Write-Host "  Website : $LocalUrl"
    Write-Host "  Admin   : $LocalUrl/wp-admin  (tài khoản giống production)"
    Write-Host '  Đổi mật khẩu admin local nếu cần: wp user update <login> --user_pass=<mới>'
    Write-Host '  Lưu ý: wp-content/uploads giờ là bản tại thời điểm backup.'
} finally {
    if (Test-Path $work) { Remove-Item $work -Recurse -Force -ErrorAction SilentlyContinue }
}
