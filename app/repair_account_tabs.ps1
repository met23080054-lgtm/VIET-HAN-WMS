$ErrorActionPreference = "Stop"

$file = "account.php"

Copy-Item $file "account_before_tab_repair.php" -Force

$inputLines = [IO.File]::ReadAllLines(
    $file,
    [Text.UTF8Encoding]::new($false)
)

$output = New-Object System.Collections.Generic.List[string]

$i = 0

while ($i -lt $inputLines.Count) {

    $line = $inputLines[$i]

    # PROFILE TAB
    if ($line.Contains('href="account.php?tab=profile"')) {

        $output.Add($line)
        $i++

        while ($i -lt $inputLines.Count) {

            if ($inputLines[$i].Trim() -eq "</a>") {

                $output.Add(
                    "                    &#128100; Th&#244;ng tin c&#225; nh&#226;n"
                )

                $output.Add($inputLines[$i])

                $i++
                break
            }

            $i++
        }

        continue
    }

    # PASSWORD TAB
    if ($line.Contains('href="account.php?tab=password"')) {

        $output.Add($line)
        $i++

        while ($i -lt $inputLines.Count) {

            if ($inputLines[$i].Trim() -eq "</a>") {

                $output.Add(
                    "                    &#128274; &#272;&#7893;i m&#7853;t kh&#7849;u"
                )

                $output.Add($inputLines[$i])

                $i++
                break
            }

            $i++
        }

        continue
    }

    # AVATAR TAB
    if ($line.Contains('href="account.php?tab=avatar"')) {

        $output.Add($line)
        $i++

        while ($i -lt $inputLines.Count) {

            if ($inputLines[$i].Trim() -eq "</a>") {

                $output.Add(
                    "                    &#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n"
                )

                $output.Add($inputLines[$i])

                $i++
                break
            }

            $i++
        }

        continue
    }

    $output.Add($line)

    $i++
}

[IO.File]::WriteAllLines(
    $file,
    $output,
    [Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "======================================"
Write-Host " TAB REPAIR COMPLETED"
Write-Host "======================================"
Write-Host "Backup: account_before_tab_repair.php"