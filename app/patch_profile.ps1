$ErrorActionPreference = "Stop"

$file = "index.php"

Copy-Item "index.php" "index_before_account_dropdown_final.php" -Force

$source = Get-Content -Raw -Encoding UTF8 $file

if ($source.Contains("profile-account")) {
    Write-Host "ERROR: Dropdown da ton tai"
    exit 1
}

$css = @"
<style>
.profile-account {
    position: relative;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 0;
    background: transparent;
    padding: 4px 6px;
    cursor: pointer;
    color: inherit;
    font-family: inherit;
    border-radius: 10px;
}

.profile-trigger:hover {
    background: rgba(247,147,30,.08);
}

.profile-chevron {
    font-size: 10px;
    margin-left: 3px;
    transition: transform .2s ease;
}

.profile-account.open .profile-chevron {
    transform: rotate(180deg);
}

.profile-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 265px;
    background: #fff;
    border: 1px solid #e1e7ed;
    border-radius: 14px;
    box-shadow: 0 14px 40px rgba(15,47,77,.16);
    padding: 8px;
    z-index: 99999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);
    transition: opacity .18s ease,transform .18s ease,visibility .18s ease;
}

.profile-account.open .profile-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-menu-user {
    padding: 12px 13px 10px;
    border-bottom: 1px solid #edf1f5;
    margin-bottom: 6px;
}

.profile-menu-name {
    font-size: 14px;
    font-weight: 800;
    color: #173b5f;
}

.profile-menu-role {
    margin-top: 3px;
    font-size: 12px;
    color: #7b8794;
}

.profile-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border-radius: 9px;
    text-decoration: none;
    color: #26384a;
    font-size: 13px;
    font-weight: 700;
}

.profile-menu a:hover {
    background: #fff4e8;
    color: #f7931e;
}

.profile-menu-divider {
    height: 1px;
    background: #edf1f5;
    margin: 6px 4px;
}

.profile-menu a.logout-item {
    color: #c0392b;
}
</style>
"@

$marker = '<div class="profile">'

if (-not $source.Contains($marker)) {
    Write-Host "ERROR: Khong tim thay profile"
    exit 1
}

$source = $source.Replace(
    $marker,
    $css + "`r`n" + $marker
)

$js = @"
<script>
(function () {

    const profile =
        document.querySelector('.profile');

    const logout =
        document.querySelector('a.logout');

    if (!profile || !logout) {
        return;
    }

    const account =
        document.createElement('div');

    account.className =
        'profile-account';

    account.id =
        'profileAccount';

    const trigger =
        document.createElement('button');

    trigger.type =
        'button';

    trigger.className =
        'profile-trigger';

    trigger.id =
        'profileTrigger';

    trigger.setAttribute(
        'aria-expanded',
        'false'
    );

    trigger.setAttribute(
        'aria-haspopup',
        'true'
    );

    const menu =
        document.createElement('div');

    menu.className =
        'profile-menu';

    menu.id =
        'profileMenu';

    const name =
        profile.querySelector(
            '.profile-name'
        )?.textContent.trim() || '';

    const role =
        profile.querySelector(
            '.profile-role'
        )?.textContent.trim() || '';

    menu.innerHTML =
        '<div class="profile-menu-user">' +
        '<div class="profile-menu-name">' +
        name +
        '</div>' +
        '<div class="profile-menu-role">' +
        role +
        '</div>' +
        '</div>' +

        '<a href="account.php?tab=profile">' +
        '<span>👤</span>' +
        '<span>Thông tin cá nhân</span>' +
        '</a>' +

        '<a href="account.php?tab=password">' +
        '<span>🔒</span>' +
        '<span>Đổi mật khẩu</span>' +
        '</a>' +

        '<a href="account.php?tab=avatar">' +
        '<span>🖼️</span>' +
        '<span>Đổi ảnh đại diện</span>' +
        '</a>' +

        '<div class="profile-menu-divider"></div>' +

        '<a class="logout-item" href="logout.php">' +
        '<span>↪</span>' +
        '<span>Đăng xuất</span>' +
        '</a>';

    profile.parentNode.insertBefore(
        account,
        profile
    );

    account.appendChild(
        trigger
    );

    trigger.appendChild(
        profile
    );

    const chevron =
        document.createElement('span');

    chevron.className =
        'profile-chevron';

    chevron.textContent =
        '▼';

    trigger.appendChild(
        chevron
    );

    account.appendChild(
        menu
    );

    logout.remove();

    trigger.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            const open =
                account.classList.toggle(
                    'open'
                );

            trigger.setAttribute(
                'aria-expanded',
                open ? 'true' : 'false'
            );
        }
    );

    menu.addEventListener(
        'click',
        function (event) {
            event.stopPropagation();
        }
    );

    document.addEventListener(
        'click',
        function () {

            account.classList.remove(
                'open'
            );

            trigger.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    );

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                account.classList.remove(
                    'open'
                );

                trigger.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }
        }
    );

})();
</script>
"@

$body = '</body>'

if (-not $source.Contains($body)) {
    Write-Host "ERROR: Khong tim thay body"
    exit 1
}

$source = $source.Replace(
    $body,
    $js + "`r`n" + $body
)

[System.IO.File]::WriteAllText(
    $file,
    $source,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "===================================="
Write-Host " PATCH THANH CONG"
Write-Host "===================================="
Write-Host ""
Write-Host "Backup: index_before_account_dropdown_final.php"
Write-Host "CSS: OK"
Write-Host "JavaScript: OK"
Write-Host ""