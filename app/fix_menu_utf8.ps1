$ErrorActionPreference = "Stop"

$file = "index.php"

$source = Get-Content -Raw -Encoding UTF8 $file

$source = $source -replace '(?s)<a href="account\.php\?tab=profile".*?</a>', '<a href="account.php?tab=profile" role="menuitem"><span>&#128100;</span><span>Th&#244;ng tin c&#225; nh&#226;n</span></a>'

$source = $source -replace '(?s)<a href="account\.php\?tab=password".*?</a>', '<a href="account.php?tab=password" role="menuitem"><span>&#128274;</span><span>&#272;&#7893;i m&#7853;t kh&#7849;u</span></a>'

$source = $source -replace '(?s)<a href="account\.php\?tab=avatar".*?</a>', '<a href="account.php?tab=avatar" role="menuitem"><span>&#128444;&#65039;</span><span>&#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n</span></a>'

$source = $source -replace '(?s)<a class="logout-item" href="logout\.php".*?</a>', '<a class="logout-item" href="logout.php" role="menuitem"><span>&#8617;</span><span>&#272;&#259;ng xu&#7845;t</span></a>'

[System.IO.File]::WriteAllText(
    $file,
    $source,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host "MENU UTF8 FIX THANH CONG"
