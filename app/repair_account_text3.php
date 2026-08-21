<?php

$file = __DIR__ . '/account.php';

$backup = __DIR__ . '/account_before_final_text_fix3.php';

copy($file, $backup);

$s = file_get_contents($file);

$replace = [

    // PHP messages
    'T?p t?i ln khng ph?i hnh ?nh h?p l?.'
        => 'T&#7879;p t&#7843;i l&ecirc;n kh&ocirc;ng ph&#7843;i h&igrave;nh &#7843;nh h&#7907;p l&ecirc;.',

    'Da c?p nh?t ?nh d?i di?n.'
        => '&#272;&atilde; c&#7853;p nh&#7853;t &#7843;nh &#273;&#7841;i di&#7879;n.',

    // Page title
    'Ti kho?n - VI&#7878;T HAN WMS'
        => 'T&#224;i kho&#7843;n - VI&#7878;T HAN WMS',

    // Header
    'Qu?n ly thng tin c nhn,'
        => 'Qu&#7843;n l&yacute; th&ocirc;ng tin c&aacute; nh&acirc;n,',

    'm?t kh?u v ?nh d?i di?n.'
        => 'm&#7853;t kh&#7849;u v&agrave; &#7843;nh &#273;&#7841;i di&#7879;n.',

    // Role
    'Nhn vin'
        => 'Nh&#226;n vi&#234;n',

    // Tabs
    '?? Thng tin c nhn'
        => '&#128100; Th&ocirc;ng tin c&aacute; nh&acirc;n',

    // Profile
    'Thng tin c nhn'
        => 'Th&ocirc;ng tin c&aacute; nh&acirc;n',

    'H? v tn'
        => 'H&#7885; v&agrave; t&ecirc;n',

    'Tn dang nh?p'
        => 'T&ecirc;n &#273;&#259;ng nh&#7853;p',

    'Quyn ti kho?n'
        => 'Quy&#7873;n t&agrave;i kho&#7843;n',

    // Password
    'M?t kh?u hi?n t?i'
        => 'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i',

    'M?t kh?u m?i'
        => 'M&#7853;t kh&#7849;u m&#7899;i',

    'Nh?p l?i m?t kh?u m?i'
        => 'Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i',

    'T?i thi?u 8 ky t?.'
        => 'T&#7889;i thi&#7875;u 8 k&yacute; t&#7921;.',

    // Avatar
    '?nh hi?n t?i'
        => '&#7842;nh hi&#7879;n t&#7841;i',

    'JPG, PNG ho?c WEBP.'
        => 'JPG, PNG ho&#7863;c WEBP.',

    'Dung lu?ng t?i da 5 MB.'
        => 'Dung l&#432;&#7907;ng t&#7889;i &#273;a 5 MB.',

    'T?i ?nh ln'
        => 'T&#7843;i &#7843;nh l&ecirc;n',

    // Comments - không ảnh hưởng chức năng nhưng sửa luôn
    'CAP NHAT THONG TIN CA NHAN'
        => 'CAP NHAT THONG TIN CA NHAN',

    'D?I M?T KH?U'
        => 'DOI MAT KHAU',

    'D?I ?NH D?I DI?N'
        => 'DOI ANH DAI DIEN',

    'D?C L?I THONG TIN SAU KHI C?P NH?T'
        => 'DOC LAI THONG TIN SAU KHI CAP NHAT'
];

foreach ($replace as $old => $new) {
    $s = str_replace($old, $new, $s);
}

file_put_contents(
    $file,
    $s,
    LOCK_EX
);

echo "======================================\n";
echo " ACCOUNT TEXT FIX 3 COMPLETED\n";
echo "======================================\n";
echo "Backup: account_before_final_text_fix3.php\n";
