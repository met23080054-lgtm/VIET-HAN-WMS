$ErrorActionPreference = "Stop"

$file = "account.php"

Copy-Item $file "account_before_account_ui_fix3.php" -Force

$s = [IO.File]::ReadAllText(
    $file,
    [Text.UTF8Encoding]::new($false)
)

# ===== PHAN HEADER =====

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

# ===== ROLE =====

$s = $s.Replace(
    '??''Nh∩┐╜n vi∩┐╜n''',
    '''Nh&#226;n vi&#234;n'''
)

$s = $s.Replace(
    '''Nh∩┐╜n vi∩┐╜n''',
    '''Nh&#226;n vi&#234;n'''
)

# ===== TAB 1 =====

$s = $s.Replace(
    '?? Th∩┐╜ng tin c∩┐╜ nh∩┐╜n',
    '&#128100; Th&#244;ng tin c&#225; nh&#226;n'
)

# ===== TAB 2 =====

$s = $s.Replace(
    '?? D?i m?t kh?u',
    '&#128274; &#272;&#7893;i m&#7853;t kh&#7849;u'
)

$s = $s.Replace(
    '?? D?i m?t kh╞Æu',
    '&#128274; &#272;&#7893;i m&#7853;t kh&#7849;u'
)

# ===== TAB 3 =====

$s = $s.Replace(
    '??? D?i ?nh d?i di?n',
    '&#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n'
)

# ===== LOGOUT =====

$s = $s.Replace(
    '? Dang xu?t',
    '&#8617; &#272;&#259;ng xu&#7845;t'
)

$s = $s.Replace(
    '? D?ng xu?t',
    '&#8617; &#272;&#259;ng xu&#7845;t'
)

# ===== THONG TIN CA NHAN =====

$s = $s.Replace(
    'ThΓÇ£ng tin c┬á nh╞Æn',
    'Th&#244;ng tin c&#225; nh&#226;n'
)

$s = $s.Replace(
    'T╦ån dang nh?p',
    'T&#234;n &#273;&#259;ng nh&#7853;p'
)

$s = $s.Replace(
    'Quy?n tΓÇªi kho?n',
    'Quy&#7873;n t&#224;i kho&#7843;n'
)

$s = $s.Replace(
    'Luu thΓÇ£ng tin',
    'L&#432;u th&#244;ng tin'
)

# ===== PASSWORD =====

$s = $s.Replace(
    'M?t kh?u hi?n t?i',
    'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i'
)

$s = $s.Replace(
    'M?t kh?u m?i',
    'M&#7853;t kh&#7849;u m&#7899;i'
)

$s = $s.Replace(
    'Nh?p l?i m?t kh?u m?i',
    'Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i'
)

# ===== AVATAR =====

$s = $s.Replace(
    'D?i ?nh d?i di?n',
    '&#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n'
)

$s = $s.Replace(
    '?nh hi?n t?i',
    '&#7842;nh hi&#7879;n t&#7841;i'
)

$s = $s.Replace(
    'T?i ?nh l╦ån',
    'T&#7843;i &#7843;nh l&#234;n'
)

# ===== GHI FILE UTF-8 KHONG BOM =====

[IO.File]::WriteAllText(
    $file,
    $s,
    [Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "======================================"
Write-Host " ACCOUNT UI FIX 2 THANH CONG"
Write-Host "======================================"
Write-Host "Backup: account_before_account_ui_fix3.php"