<?php

$pdo = new PDO(
    'sqlite:database/quan_ly_kho.sqlite'
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

echo "=== CAU TRUC BANG SUPPLIERS ===" . PHP_EOL;

$sql = $pdo
    ->query(
        "SELECT sql
         FROM sqlite_master
         WHERE type = 'table'
         AND name = 'suppliers'"
    )
    ->fetchColumn();

echo $sql . PHP_EOL . PHP_EOL;

echo "=== SQLITE SEQUENCE ===" . PHP_EOL;

$stmt = $pdo->prepare(
    "SELECT name, seq
     FROM sqlite_sequence
     WHERE name = :name"
);

$stmt->execute([
    ':name' => 'suppliers'
]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "name = " . $row['name'] . PHP_EOL;
    echo "seq  = " . $row['seq'] . PHP_EOL;
} else {
    echo "KHONG CO SQLITE_SEQUENCE" . PHP_EOL;
}

echo PHP_EOL;
echo "=== ID HIEN TAI ===" . PHP_EOL;

$stmt = $pdo->query(
    "SELECT id, supplier_name
     FROM suppliers
     ORDER BY id"
);

foreach ($stmt as $r) {
    echo $r['id'] . " | " . $r['supplier_name'] . PHP_EOL;
}