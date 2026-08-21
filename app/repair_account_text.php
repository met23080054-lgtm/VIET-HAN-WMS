<?php

$file = __DIR__ . '/account.php';

$backup = __DIR__ . '/account_before_final_text_fix.php';
copy($file, $backup);

$s = file_get_contents($file);

$replacements = [

    // PHP messages
    "'Phin lm vi?c khng h?p l?. Vui lng th? l?i.'"
        => "'Phi&#234;n l&#224;m vi&#7879;c kh&#244;ng h&#7907;p l&#7879;. Vui l&#242;ng th&#7917; l&#7841;i.'",

    "'H? v tn khng du?c d? tr?ng.'"
        => "'H&#7885; v&#224; t&#234;n kh&#244;ng &#273;&#432;&#7907;c &#273;&#7875; tr&#7889;ng.'",

    "'Email khng h?p l?.'"
        => "'Email kh&#244;ng h&#7907;p l&#7879;.'",

    "'Da c?p nh?t thng tin c nhn.'"
        => "'&#272;&#227; c&#7853;p nh&#7853;t th&#244;ng tin c&#225; nh&#226;n.'",

    "'Vui lng nh?p d?y d? m?t kh?u.'"
        => "'Vui l&#242;ng nh&#7853;p &#273;&#7847;y &#273;&#7911; m&#7853;t kh&#7849;u.'",

    "'M?t kh?u hi?n t?i khng dng.'"
        => "'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i kh&#244;ng &#273;&#250;ng.'",

    "'M?t kh?u m?i ph?i c t nh?t 8 ky t?.'"
        => "'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i c&#243; &#237;t nh&#7845;t 8 k&#253; t&#7921;.'",

    "'Xc nh?n m?t kh?u khng kh?p.'"
        => "'X&#225;c nh&#7853;n m&#7853;t kh&#7849;u kh&#244;ng kh&#7899;p.'",

    "'M?t kh?u m?i ph?i khc m?t kh?u hi?n t?i.'"
        => "'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i kh&#225;c m&#7853;t kh&#7849;u hi&#7879;n t&#7841;i.'",

    "'M?t kh?u m?i thnh cng.'"
        => "'M&#272;&#7893;i m&#7853;t kh&#7849;u th&#224;nh c&#244;ng.'",

    "'Vui lng ch?n m?t ?nh h?p l?.'"
        => "'Vui l&#242;ng ch&#7885;n m&#7897;t &#7843;nh h&#7907;p l&#7879;.'",

    "'?nh t?i da 5 MB.'"
        => "'&#7842;nh t&#7889;i &#273;a 5 MB.'",

    "'Ch? h? tr? JPG, PNG ho?c WEBP.'"
        => "'Ch&#7881; h&#7895; tr&#7907; JPG, PNG ho&#7863;c WEBP.'",

    "'Khng th? t?o thu m?c luu ?nh.'"
        => "'Kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c l&#432;u &#7843;nh.'",

    "'Khng th? luu ?nh ln my ch?.'"
        => "'Kh&#244;ng th&#7875; l&#432;u &#7843;nh l&#234;n m&#225;y ch&#7911;.'",

    // HTML text
    'T?i kho?n'
        => 'T&#224;i kho&#7843;n',

    'Th?ng tin c nhn'
        => 'Th&#244;ng tin c&#225; nh&#226;n',

    'H? v tn'
        => 'H&#7885; v&#224; t&#234;n',

    'Quy?n ti kho?n'
        => 'Quy&#7873;n t&#224;i kho&#7843;n',

    'Luu thng tin'
        => 'L&#432;u th&#244;ng tin',

    'T?i thi?u 8 ky t?.'
        => 'T&#7889;i thi&#7875;u 8 k&#253; t&#7921;.',

    'Dung lu?ng t?i da 5 MB.'
        => 'Dung l&#432;&#7907;ng t&#7889;i &#273;a 5 MB.',

    'JPG, PNG ho?c WEBP.'
        => 'JPG, PNG ho&#7863;c WEBP.',
];

foreach ($replacements as $old => $new) {
    $s = str_replace($old, $new, $s);
}

file_put_contents($file, $s);

echo "======================================\n";
echo " ACCOUNT TEXT REPAIR COMPLETED\n";
echo "======================================\n";
echo "Backup: account_before_final_text_fix.php\n";