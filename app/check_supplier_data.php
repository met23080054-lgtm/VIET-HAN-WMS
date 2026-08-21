<?php

require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query(
    'SELECT id, supplier_name, phone, email, address
     FROM suppliers
     ORDER BY id DESC
     LIMIT 10'
);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "===== 10 NCC MOI NHAT =====" . PHP_EOL;

foreach ($rows as $r) {
    echo "ID      = [" . $r['id'] . "]" . PHP_EOL;
    echo "NAME    = [" . $r['supplier_name'] . "]" . PHP_EOL;
    echo "PHONE   = [" . $r['phone'] . "]" . PHP_EOL;
    echo "EMAIL   = [" . $r['email'] . "]" . PHP_EOL;
    echo "ADDRESS = [" . $r['address'] . "]" . PHP_EOL;
    echo "----------------------------" . PHP_EOL;
}