<?php

$file = __DIR__ . '/account.php';

$backup = __DIR__ . '/account_before_final_utf8_fix.php';

copy($file, $backup);

$s = file_get_contents($file);

/*
 * Sửa phần giao diện account bằng HTML entities.
 * Không đưa trực tiếp ký tự tiếng Việt vào file script
 * để tránh PowerShell/CMD làm hỏng encoding.
 */

$replacements = [

    'VI?T HAN WMS'
        => 'VI&#7878;T HAN WMS',

    'T∩┐╜i kho?n'
        => 'T&#224;i kho&#7843;n',

    'Qu?n ly th∩┐╜ng tin c∩┐╜ nh∩┐╜n,'
        => 'Qu&#7843;n l&#253; th&#244;ng tin c&#225; nh&#226;n,',

    'm?t kh?u v∩┐╜ ?nh d?i di?n.'
        => 'm&#7853;t kh&#7849;u v&#224; &#7843;nh &#273;&#7841;i di&#7879;n.',

    'Quay l?i Dashboard'
        => 'Quay l&#7841;i Dashboard',

    'Nh∩┐╜n vi∩┐╜n'
        => 'Nh&#226;n vi&#234;n',

    '?? Th∩┐╜ng tin c∩┐╜ nh∩┐╜n'
        => '&#128100; Th&#244;ng tin c&#225; nh&#226;n',

    '?? &#272;&#7893;i m&#7853;t kh&#7849;u'
        => '&#128274; &#272;&#7893;i m&#7853;t kh&#7849;u',

    '??? &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n'
        => '&#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n',

    '? &#272;&#259;ng xu&#7845;t'
        => '&#8617; &#272;&#259;ng xu&#7845;t',

    'Th?ng tin c? nh?n'
        => 'Th&#244;ng tin c&#225; nh&#226;n',

    'H? v? t?n'
        => 'H&#7885; v&#224; t&#234;n',

    'T?n dang nh?p'
        => 'T&#234;n &#273;&#259;ng nh&#7853;p',

    'Quy?n t?i kho?n'
        => 'Quy&#7873;n t&#224;i kho&#7843;n',

    'Luu th?ng tin'
        => 'L&#432;u th&#244;ng tin',

    'D?i m?t kh?u'
        => '&#272;&#7893;i m&#7853;t kh&#7849;u',

    'M?t kh?u hi?n t?i'
        => 'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i',

    'M?t kh?u m?i'
        => 'M&#7853;t kh&#7849;u m&#7899;i',

    'Nh?p l?i m?t kh?u m?i'
        => 'Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i',

    'D?i ?nh d?i di?n'
        => '&#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n',

    '?nh d?i di?n'
        => '&#7842;nh &#273;&#7841;i di&#7879;n',

    '?nh hi?n t?i'
        => '&#7842;nh hi&#7879;n t&#7841;i',

    'T?i ?nh'
        => 'T&#7843;i &#7843;nh',

    'T?p t?i l?n'
        => 'T&#7879;p t&#7843;i l&#234;n',

    'Email kh∩┐╜ng h?p l?.'
        => 'Email kh&#244;ng h&#7907;p l&#7879;.',

    'Da c?p nh?t thΓÇ£ng tin c┬á nh╞Æn.'
        => '&#272;&#227; c&#7853;p nh&#7853;t th&#244;ng tin c&#225; nh&#226;n.',

];

foreach ($replacements as $old => $new) {
    $s = str_replace($old, $new, $s);
}

/*
 * Các chuỗi mojibake còn lại có thể xuất hiện dưới dạng
 * ký tự ? trong file. Thay trực tiếp những phần giao diện
 * đã xác định theo cấu trúc HTML.
 */

$s = preg_replace(
    '/(<div class="brand">\s*)(.*?)(\s*<\/div>)/s',
    '$1VI&#7878;T HAN WMS$3',
    $s,
    1
);

$s = preg_replace(
    '/(<div class="title">\s*)(.*?)(\s*<\/div>)/s',
    '$1T&#224;i kho&#7843;n$3',
    $s,
    1
);

$s = preg_replace(
    '/(<div class="sub">\s*)(.*?)(\s*<\/div>)/s',
    '$1Qu&#7843;n l&#253; th&#244;ng tin c&#225; nh&#226;n,<br>m&#7853;t kh&#7849;u v&#224; &#7843;nh &#273;&#7841;i di&#7879;n.$3',
    $s,
    1
);

/*
 * Tab menu: thay toàn bộ nội dung bên trong nav.tabs.
 */

$tabs = <<<'HTML'
            <nav class="tabs">

                <a
                    class="tab
                    <?= $tab === 'profile'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=profile"
                >
                    &#128100; Th&#244;ng tin c&#225; nh&#226;n
                </a>

                <a
                    class="tab
                    <?= $tab === 'password'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=password"
                >
                    &#128274; &#272;&#7893;i m&#7853;t kh&#7849;u
                </a>

                <a
                    class="tab
                    <?= $tab === 'avatar'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=avatar"
                >
                    &#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n
                </a>

                <a
                    class="tab"
                    href="logout.php"
                >
                    &#8617; &#272;&#259;ng xu&#7845;t
                </a>

            </nav>
HTML;

$s = preg_replace(
    '/\s*<nav class="tabs">.*?<\/nav>/s',
    "\n\n" . $tabs,
    $s,
    1
);

/*
 * Đảm bảo charset.
 */

$s = preg_replace(
    '/<meta\s+charset="[^"]*"\s*>/i',
    '<meta charset="UTF-8">',
    $s,
    1
);

/*
 * Ghi UTF-8 không BOM.
 */

file_put_contents(
    $file,
    $s
);

echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo " ACCOUNT FINAL UTF8 FIX COMPLETED" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo "Backup: account_before_final_utf8_fix.php" . PHP_EOL;
echo PHP_EOL;