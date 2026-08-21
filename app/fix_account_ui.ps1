$ErrorActionPreference = "Stop"

$file = "account.php"

# Backup
Copy-Item $file "account_before_account_ui_entities.php" -Force

$s = [IO.File]::ReadAllText(
    $file,
    [Text.UTF8Encoding]::new($false)
)

# ================================
# SUA CAC CHUOI GIAO DIEN
# DUNG HTML ENTITY DE TRANH LOI ENCODING
# ================================

$s = $s.Replace(
    'VI?T HAN WMS',
    'VI&#7878;T HAN WMS'
)

$s = $s.Replace(
    '← Quay l?i Dashboard',
    '&#8592; Quay l&#7841;i Dashboard'
)

$s = $s.Replace(
    'T∩┐╜i kho?n',
    'T&#224;i kho&#7843;n'
)

$s = $s.Replace(
    'Qu?n ly th∩┐╜ng tin c∩┐╜ nh∩┐╜n,',
    'Qu&#7843;n l&#253; th&#244;ng tin c&#225; nh&#226;n,'
)

$s = $s.Replace(
    'm?t kh?u v∩┐╜ ?nh d?i di?n.',
    'm&#7853;t kh&#7849;u v&#224; &#7843;nh &#273;&#7841;i di&#7879;n.'
)

$s = $s.Replace(
    'alt="?nh d?i di?n"',
    'alt="&#7842;nh &#273;&#7841;i di&#7879;n"'
)

# Các chuỗi bị lỗi ở menu/tab
$s = $s.Replace(
    'ThΓÇ£ng tin c┬á nh╞Æn',
    'Th&#244;ng tin c&#225; nh&#226;n'
)

$s = $s.Replace(
    'D?i m?t kh?u',
    '&#272;&#7893;i m&#7853;t kh&#7849;u'
)

$s = $s.Replace(
    'D?i ?nh d?i di?n',
    '&#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n'
)

$s = $s.Replace(
    'Dang xu?t',
    '&#272;&#259;ng xu&#7845;t'
)

# ================================
# LUU UTF-8 KHONG BOM
# ================================

[IO.File]::WriteAllText(
    $file,
    $s,
    [Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "===================================="
Write-Host " ACCOUNT UI ENTITY FIX THANH CONG"
Write-Host "===================================="
Write-Host "Backup: account_before_account_ui_entities.php"