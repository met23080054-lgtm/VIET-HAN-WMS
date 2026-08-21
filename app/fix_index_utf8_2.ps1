$ErrorActionPreference = 'Stop'

$f = Join-Path $PSScriptRoot 'index.php'
$backup = Join-Path $PSScriptRoot 'index_before_utf8_user_fix2.php'

Copy-Item $f $backup -Force

$s = [System.IO.File]::ReadAllText(
    $f,
    [System.Text.Encoding]::UTF8
)

# Fix default username without matching the corrupted Vietnamese text
$s = [regex]::Replace(
    $s,
    '(?ms)(\$userName\s*=\s*\$_SESSION\[''full_name''\]\s*\r?\n\s*\?\?\s*)''[^'']*''',
    '$1''Ng&#432;&#7901;i d&#249;ng'''
)

# Fix default role without matching the corrupted Vietnamese text
$s = [regex]::Replace(
    $s,
    '(?ms)(\$userRole\s*=\s*\$_SESSION\[''role''\]\s*\r?\n\s*\?\?\s*)''[^'']*''',
    '$1''Nh&#226;n vi&#234;n'''
)

# Fix dashboard title
$s = [regex]::Replace(
    $s,
    '(?ms)(<title>\s*)Dashboard\s*\|\s*.*?(\s*</title>)',
    '$1Dashboard | VI&#7878;T H&#192;N WMS$2'
)

[System.IO.File]::WriteAllText(
    $f,
    $s,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host ''
Write-Host '======================================'
Write-Host ' INDEX UTF8 FIX COMPLETED'
Write-Host '======================================'
Write-Host 'Backup: index_before_utf8_user_fix2.php'
Write-Host ''