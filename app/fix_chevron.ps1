$ErrorActionPreference = "Stop"

$file = "index.php"

$source = Get-Content -Raw -Encoding UTF8 $file

$pattern = "chevron\.textContent\s*=\s*'[^']*';"

$replacement = "chevron.innerHTML = '&#9660;';"

$count = [regex]::Matches($source, $pattern).Count

if ($count -ne 1) {
    Write-Host "ERROR: Tim thay $count vi tri chevron"
    exit 1
}

$source = [regex]::Replace(
    $source,
    $pattern,
    $replacement,
    1
)

[System.IO.File]::WriteAllText(
    $file,
    $source,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host "CHEVRON FIX THANH CONG"