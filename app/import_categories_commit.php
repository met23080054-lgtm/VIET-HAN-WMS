<?php

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    exit('Bạn không có quyền thực hiện thao tác này.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

$preview =
    $_SESSION['category_import_preview'] ?? null;

if (
    !$preview ||
    !isset($preview['rows']) ||
    !is_array($preview['rows'])
) {
    header('Location: categories.php');
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
         FROM categories
         WHERE LOWER(TRIM(category_name))
             = LOWER(TRIM(?))'
    );

    $nextCategoryIdSql = "
        SELECT COALESCE(
            (
                SELECT MIN(c.id + 1)
                FROM categories c
                LEFT JOIN categories n
                    ON n.id = c.id + 1
                WHERE c.id >= 1
                  AND n.id IS NULL
            ),
            1
        )
    ";

    $insert = $pdo->prepare(
        'INSERT INTO categories
            (id, category_name, description)
         VALUES (?, ?, ?)'
    );

    foreach ($validRows as $item) {

        $categoryName =
            trim((string)($item['name'] ?? ''));

        $description =
            trim((string)($item['description'] ?? ''));

        if ($categoryName === '') {

            throw new RuntimeException(
                'Dòng hợp lệ không có tên danh mục.'
            );
        }

        $check->execute([
            $categoryName
        ]);

        if ((int)$check->fetchColumn() > 0) {

            throw new RuntimeException(
                'Danh mục đã tồn tại tại thời điểm xác nhận: '
                . $categoryName
            );
        }

        $nextCategoryId =
            (int)$pdo->query($nextCategoryIdSql)->fetchColumn();

        $insert->execute([
            $nextCategoryId,
            $categoryName,
            $description
        ]);

        $inserted++;
    }

    $pdo->commit();

    unset(
        $_SESSION['category_import_preview']
    );

    header(
        'Location: categories.php?import_success='
        . $inserted
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['category_import_error'] =
        'Nhập thất bại: ' . $e->getMessage();

    header(
        'Location: categories.php?import_preview=1'
    );

    exit;
}