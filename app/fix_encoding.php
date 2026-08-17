<?php

$file = __DIR__ . '/account.php';
$backup = __DIR__ . '/account_before_encoding_fix.php';

copy($file, $backup);

$data = file_get_contents($file);

if ($data === false) {
    exit("KHONG DOC DUOC account.php\n");
}

/*
 * File hiện tại có dấu hiệu bị mojibake.
 * Thử phục hồi chuỗi bằng cách:
 *
 * UTF-8 -> Windows-1252 -> UTF-8
 *
 * Đây là dạng lỗi thường gặp khi UTF-8 bị đọc nhầm
 * thành Windows-1252 rồi ghi ngược lại.
 */

$fixed = $data;

for ($i = 0; $i < 3; $i++) {

    $test = @iconv(
        'UTF-8',
        'Windows-1252//IGNORE',
        $fixed
    );

    if ($test === false) {
        break;
    }

    $test = @iconv(
        'Windows-1252',
        'UTF-8//IGNORE',
        $test
    );

    if ($test === false) {
        break;
    }

    if ($test === $fixed) {
        break;
    }

    $fixed = $test;
}

/*
 * Ghi UTF-8 không BOM
 */
$utf8 = new \SplFileObject($file, 'w');
$utf8->fwrite($fixed);
$utf8 = null;

echo "======================================\n";
echo " ACCOUNT ENCODING REPAIR COMPLETED\n";
echo "======================================\n";
echo "Backup: account_before_encoding_fix.php\n";
echo "File:   account.php\n";