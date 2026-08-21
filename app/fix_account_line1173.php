<?php

$file = __DIR__ . '/account.php';
$backup = __DIR__ . '/account_before_line1173.php';

copy($file, $backup);

$lines = file($file);

$lines[1172] = "                                Dung lượng tối đa 5 MB." . PHP_EOL;

file_put_contents($file, implode('', $lines));

echo "DA SUA DONG 1173" . PHP_EOL;
echo "Backup: account_before_line1173.php" . PHP_EOL;