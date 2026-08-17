$ErrorActionPreference = "Stop"

$file = "account.php"

Copy-Item $file "account_before_ui_fix5.php" -Force

$s = [IO.File]::ReadAllText(
    $file,
    [Text.UTF8Encoding]::new($false)
)

# =========================================================
# 1. SUA ROLE FALLBACK
# =========================================================

$oldRole = @"
$user['role_name']
                        ?? 'Nh∩┐╜n vi∩┐╜n'
"@

$newRole = @"
$user['role_name']
                        ?? 'Nh&#226;n vi&#234;n'
"@

$s = $s.Replace($oldRole, $newRole)


# =========================================================
# 2. SUA TAB THONG TIN CA NHAN
# =========================================================

$oldProfile = @"
                    ?? Th∩┐╜ng tin c∩┐╜ nh∩┐╜n
"@

$newProfile = @"
                    &#128100; Th&#244;ng tin c&#225; nh&#226;n
"@

$s = $s.Replace($oldProfile, $newProfile)


# =========================================================
# 3. SUA TAB DOI MAT KHAU
# =========================================================

$oldPassword = @"
                    ?? D?i m?t kh?u
"@

$newPassword = @"
                    &#128274; &#272;&#7893;i m&#7853;t kh&#7849;u
"@

$s = $s.Replace($oldPassword, $newPassword)


# =========================================================
# 4. SUA TAB DOI ANH DAI DIEN
# =========================================================

$oldAvatar = @"
                    ??? D?i ?nh d?i di?n
"@

$newAvatar = @"
                    &#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n
"@

$s = $s.Replace($oldAvatar, $newAvatar)


# =========================================================
# 5. GHI FILE UTF-8 KHONG BOM
# =========================================================

[IO.File]::WriteAllText(
    $file,
    $s,
    [Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "======================================"
Write-Host " ACCOUNT UI FIX 5 THANH CONG"
Write-Host "======================================"
Write-Host "Backup: account_before_ui_fix5.php"