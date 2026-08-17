$ErrorActionPreference = 'Stop'

$f = Join-Path $PSScriptRoot 'index.php'
$backup = Join-Path $PSScriptRoot 'index_before_utf8_user_fix3.php'

Copy-Item $f $backup -Force

$s = [System.IO.File]::ReadAllText(
    $f,
    [System.Text.Encoding]::UTF8
)

$s = [regex]::Replace(
    $s,
    "(?ms)(\$userName\s*=\s*\$_SESSION\['full_name'\]\s*\r?\n\s*\?\?\s*)'[^']*'",
    '$1' + "'\u{4e}g\u{432}\u{7901}i d\u{249}ng'"
)

$s = [regex]::Replace(
    $s,
    "(?ms)(\$userRole\s*=\s*\$_SESSION\['role'\]\s*\r?\n\s*\?\?\s*)'[^']*'",
    '$1' + "'Nh\u{226}n vi\u{234}n'"
)

$s = [regex]::Replace(
    $s,
    '(?ms)/\* =========================================================\s*7\..*?\*/',
    "/* =========================================================`r`n   7. USER INFORMATION`r`n========================================================= */"
)

[System.IO.File]::WriteAllText(
    $f,
    $s,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host ''
Write-Host '======================================'
Write-Host ' INDEX CLEANUP COMPLETED'
Write-Host '======================================'
Write-Host 'Backup: index_before_utf8_user_fix3.php'
Write-Host ''