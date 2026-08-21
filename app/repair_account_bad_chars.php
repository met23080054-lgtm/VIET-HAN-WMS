<?php

$file = __DIR__ . '/account.php';
$backup = __DIR__ . '/account_before_bad_chars_repair.php';

copy($file, $backup);

$lines = file($file);

$fix = [

    108 => "            'Phi&#234;n l&#224;m vi&#7879;c kh&#244;ng h&#7907;p l&#7879;. Vui l&#242;ng th&#7917; l&#7841;i.',",

    133 => "                'H&#7885; v&#224; t&#234;n kh&#244;ng &#273;&#432;&#7907;c &#273;&#7875; tr&#7889;ng.',",

    148 => "                'Email kh&#244;ng h&#7907;p l&#7879;.',",

    174 => "            '&#272;&#227; c&#7853;p nh&#7853;t th&#244;ng tin c&#225; nh&#226;n.',",

    210 => "                'Vui l&#242;ng nh&#7853;p &#273;&#7847;y &#273;&#7911; m&#7853;t kh&#7849;u.',",

    223 => "                'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i kh&#244;ng &#273;&#250;ng.',",

    231 => "                'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i c&#243; &#237;t nh&#7845;t 8 k&#253; t&#7921;.',",

    239 => "                'X&#225;c nh&#7853;n m&#7853;t kh&#7849;u kh&#244;ng kh&#7899;p.',",

    252 => "                'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i kh&#225;c m&#7853;t kh&#7849;u hi&#7879;n t&#7841;i.',",

    274 => "            '&#272;&#7893;i m&#7853;t kh&#7849;u th&#224;nh c&#244;ng.',",

    293 => "                'Vui l&#242;ng ch&#7885;n m&#7897;t &#7843;nh h&#7907;p l&#7879;.',",

    315 => "                'T&#7879;p t&#7843;i l&#234;n kh&#244;ng ph&#7843;i h&#236;nh &#7843;nh h&#7907;p l&#7879;.',",

    350 => "                'Kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c l&#432;u &#7843;nh.',",

    374 => "                'Kh&#244;ng th&#7875; l&#432;u &#7843;nh l&#234;n m&#225;y ch&#7911;.',",

    490 => '<title>T&#224;i kho&#7843;n - VI&#7878;T HAN WMS</title>',

    874 => "                        ?? 'Nh&#226;n vi&#234;n'",

    947 => "                        Th&#244;ng tin c&#225; nh&#226;n",

    967 => "                                H&#7885; v&#224; t&#234;n",

    984 => "                                T&#234;n &#273;&#259;ng nh&#7853;p",

    1000 => "                                Quy&#7873;n t&#224;i kho&#7843;n",

    1007 => "                                    ?? 'Nh&#226;n vi&#234;n'",

    1032 => "                                    L&#432;u th&#244;ng tin",

    1179 => "                                T&#7843;i &#7843;nh l&#234;n",

];

foreach ($fix as $lineNumber => $replacement) {

    $index = $lineNumber - 1;

    if (isset($lines[$index])) {
        $lines[$index] = $replacement . PHP_EOL;
    }
}

file_put_contents(
    $file,
    implode('', $lines)
);

echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo " ACCOUNT BAD CHAR REPAIR COMPLETED" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo "Backup: account_before_bad_chars_repair.php" . PHP_EOL;
echo PHP_EOL;