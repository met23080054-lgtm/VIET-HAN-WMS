<?php

$file = __DIR__ . '/account.php';

$backup = __DIR__ . '/account_before_ui_text_fix4.php';

copy($file, $backup);

$s = file_get_contents($file);

/*
 * Chỉ sửa các text UI còn lỗi.
 * Dùng HTML Entity để tránh lỗi encoding.
 */

$replace = [

    // Dòng 829
    'uay l?i Dashboard'
        => '&#8592; Quay l&#7841;i Dashboard',

    // Tab đổi mật khẩu
    '?? &#272;&#7893;i m&#7853;t kh&#7849;u'
        => '&#128274; &#272;&#7893;i m&#7853;t kh&#7849;u',

    // Tab đổi ảnh đại diện
    '??? &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n'
        => '&#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n',

    // Đăng xuất
    '? &#272;&#259;ng xu&#7845;t'
        => '&#8617; &#272;&#259;ng xu&#7845;t',

    // Các text còn lỗi
    'T?i thi?u 8 ky t?.'
        => 'T&#7889;i thi&#7875;u 8 k&yacute; t&#7921;.',

    'Dung lu?ng t?i da 5 MB.'
        => 'Dung l&#432;&#7907;ng t&#7889;i &#273;a 5 MB.',

    'T?i ?nh ln'
        => 'T&#7843;i &#7843;nh l&ecirc;n',

    'JPG, PNG ho?c WEBP.'
        => 'JPG, PNG ho&#7863;c WEBP.'
];

foreach ($replace as $old => $new) {
    $s = str_replace($old, $new, $s);
}

file_put_contents($file, $s, LOCK_EX);

echo "======================================\n";
echo " ACCOUNT UI TEXT FIX 4 COMPLETED\n";
echo "======================================\n";
echo "Backup: account_before_ui_text_fix4.php\n";