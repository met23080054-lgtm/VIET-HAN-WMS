<?php

session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$isAdmin = isset($_SESSION['role'])
    && mb_strtolower((string)$_SESSION['role'], 'UTF-8') === 'admin';

if (!$isAdmin) {
    http_response_code(403);
    exit('Bạn không có quyền thực hiện thao tác này.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: suppliers.php');
    exit;
}

$preview = $_SESSION['supplier_import_preview'] ?? null;

if (
    !$preview ||
    !isset($preview['rows']) ||
    !is_array($preview['rows'])
) {
    header('Location: suppliers.php');
    exit;
}

$validRows = [];

foreach ($preview['rows'] as $item) {

    if (($item['status'] ?? '') !== 'valid') {
        continue;
    }

    $validRows[] = $item;
}

$inserted = 0;

try {

    $pdo->beginTransaction();

    $check = $pdo->prepare(
        'SELECT COUNT(*)
         FROM suppliers
         WHERE LOWER(TRIM(supplier_name))
             = LOWER(TRIM(?))'
    );

    $insert = $pdo->prepare(
        'INSERT INTO suppliers
            (supplier_name, phone, email, address)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($validRows as $item) {

        $supplierName =
            trim((string)($item['name'] ?? ''));

        $phone =
            trim((string)($item['phone'] ?? ''));

        $email =
            trim((string)($item['email'] ?? ''));

        $address =
            trim((string)($item['address'] ?? ''));

        if ($supplierName === '') {

            throw new RuntimeException(
                'Dòng hợp lệ không có tên nhà cung cấp.'
            );
        }

        $check->execute([
            $supplierName
        ]);

        if ((int)$check->fetchColumn() > 0) {

            throw new RuntimeException(
                'NCC đã tồn tại tại thời điểm xác nhận: '
                . $supplierName
            );
        }

        $insert->execute([
            $supplierName,
            $phone,
            $email,
            $address
        ]);

        $inserted++;
    }

    $pdo->commit();

    unset(
        $_SESSION['supplier_import_preview']
    );

    header(
        'Location: suppliers.php?import_success='
        . $inserted
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['supplier_import_error'] =
        'Nhập thất bại: ' . $e->getMessage();

    header(
        'Location: suppliers.php?import_preview=1'
    );

    exit;
}