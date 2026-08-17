<?php

$file = __DIR__ . '/account.php';

copy(
    $file,
    __DIR__ . '/account_before_final_text_fix2.php'
);

$s = file_get_contents($file);

$map = [

    // ===== PHP comments =====

    'C?P NH?T THONG TIN CA NHAN'
        => 'CAP NHAT THONG TIN CA NHAN',

    'D?I M?T KH?U'
        => 'DOI MAT KHAU',

    'D?I ?NH D?I DI?N'
        => 'DOI ANH DAI DIEN',

    'D?C L?I THONG TIN SAU KHI C?P NH?T'
        => 'DOC LAI THONG TIN SAU KHI CAP NHAT',

    // ===== PHP messages =====

    "'T?p t?i ln khng ph?i hnh ?nh h?p l.'"
        => "'T&#7879;p t&#7843;i l&#234;n kh&#244;ng ph&#7843;i h&#236;nh &#7843;nh h&#7907;p l&#7879;.'",

    "'Da c?p nh?t ?nh d?i di?n.'"
        => "'&#272;&#227; c&#7853;p nh&#7853;t &#7843;nh &#273;&#7841;i di&#7879;n.'",

    // ===== title =====

    'Ti kho?n'
        => 'T&#224;i kho&#7843;n',

    // ===== subtitle =====

    'm?t kh?u v ?nh d?i di?n.'
        => 'm&#7853;t kh&#7849;u v&#224; &#7843;nh &#273;&#7841;i di&#7879;n.',

    // ===== role =====

    "'Nhn vin'"
        => "'Nh&#226;n vi&#234;n'",

    // ===== login =====

    'Tn dang nh?p'
        => 'T&#234;n &#273;&#259;ng nh&#7853;p',

    // ===== password =====

    'M?t kh?u hi?n t?i'
        => 'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i',

    'M?t kh?u m?i'
        => 'M&#7853;t kh&#7849;u m&#7899;i',

    'Nh?p l?i m?t kh?u m?i'
        => 'Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i',

    // ===== avatar =====

    'alt="?nh hi?n t?i"'
        => 'alt="&#7842;nh hi&#7879;n t&#7841;i"',

    'T?i ?nh ln'
        => 'T&#7843;i &#7843;nh l&#234;n',

];

foreach ($map as $old => $new) {
    $s = str_replace($old, $new, $s);
}

file_put_contents($file, $s);

echo "======================================\n";
echo " ACCOUNT TEXT FIX 2 COMPLETED\n";
echo "======================================\n";
echo "Backup: account_before_final_text_fix2.php\n";
