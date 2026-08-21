<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo instanceof PDO) {
    die('Không thể kết nối database.');
}

/*
|--------------------------------------------------------------------------
| CHỈ CHO PHÉP POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: import_products.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LẤY DỮ LIỆU PREVIEW TỪ SESSION
|--------------------------------------------------------------------------
*/

$rows = $_SESSION['product_import_rows'] ?? [];

if (!is_array($rows) || empty($rows)) {
    $_SESSION['product_import_message'] =
        'Không tìm thấy dữ liệu import. Vui lòng upload lại file Excel.';

    header('Location: import_products.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| KIỂM TRA LẠI DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | LOAD CATEGORY
    |--------------------------------------------------------------------------
    */

    $categoryMap = [];

    $stmt = $pdo->query(
        'SELECT id, category_name FROM categories'
    );

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $name = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)$r['category_name']
            )
        );

        $categoryMap[$name] = (int)$r['id'];
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD SUPPLIER
    |--------------------------------------------------------------------------
    */

    $supplierMap = [];

    $stmt = $pdo->query(
        'SELECT id, supplier_name FROM suppliers'
    );

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $name = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)$r['supplier_name']
            )
        );

        $supplierMap[$name] = (int)$r['id'];
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD EXISTING PRODUCT CODES
    |--------------------------------------------------------------------------
    */

    $existingCodes = [];

    $stmt = $pdo->query(
        'SELECT product_code FROM products'
    );

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $code = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)$r['product_code']
            )
        );

        $existingCodes[$code] = true;
    }

    /*
    |--------------------------------------------------------------------------
    | PREPARE INSERT
    |--------------------------------------------------------------------------
    */

    $insert = $pdo->prepare(
        'INSERT INTO products
        (
            product_code,
            product_name,
            category_id,
            supplier_id,
            unit,
            import_price,
            sale_price,
            quantity
        )
        VALUES
        (
            :product_code,
            :product_name,
            :category_id,
            :supplier_id,
            :unit,
            :import_price,
            :sale_price,
            :quantity
        )'
    );

    $inserted = 0;

    /*
    |--------------------------------------------------------------------------
    | VALIDATE + INSERT
    |--------------------------------------------------------------------------
    */

    foreach ($rows as $row) {

        $productCode = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)($row['product_code'] ?? '')
            )
        );

        $productName = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)($row['product_name'] ?? '')
            )
        );

        $categoryName = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)($row['category_name'] ?? '')
            )
        );

        $supplierName = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)($row['supplier_name'] ?? '')
            )
        );

        $unit = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string)($row['unit'] ?? '')
            )
        );

        /*
        |--------------------------------------------------------------------------
        | REQUIRED
        |--------------------------------------------------------------------------
        */

        if (
            $productCode === '' ||
            $productName === '' ||
            $unit === ''
        ) {
            throw new Exception(
                'Dữ liệu bắt buộc của sản phẩm không hợp lệ.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE PRODUCT CODE
        |--------------------------------------------------------------------------
        */

        if (isset($existingCodes[$productCode])) {

            throw new Exception(
                'Mã sản phẩm "' .
                $productCode .
                '" đã tồn tại.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        $categoryId = null;

        if ($categoryName !== '') {

            if (!isset($categoryMap[$categoryName])) {

                throw new Exception(
                    'Danh mục "' .
                    $categoryName .
                    '" không tồn tại.'
                );
            }

            $categoryId =
                $categoryMap[$categoryName];
        }

        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        $supplierId = null;

        if ($supplierName !== '') {

            if (!isset($supplierMap[$supplierName])) {

                throw new Exception(
                    'Nhà cung cấp "' .
                    $supplierName .
                    '" không tồn tại.'
                );
            }

            $supplierId =
                $supplierMap[$supplierName];
        }

        /*
        |--------------------------------------------------------------------------
        | NUMERIC DATA
        |--------------------------------------------------------------------------
        */

        $importPrice =
            isset($row['import_price'])
                ? (float)$row['import_price']
                : 0;

        $salePrice =
            isset($row['sale_price'])
                ? (float)$row['sale_price']
                : 0;

        $quantity =
            isset($row['quantity'])
                ? (int)$row['quantity']
                : 0;

        if ($importPrice < 0) {
            throw new Exception(
                'Giá nhập không được âm.'
            );
        }

        if ($salePrice < 0) {
            throw new Exception(
                'Giá bán không được âm.'
            );
        }

        if ($quantity < 0) {
            throw new Exception(
                'Số lượng không được âm.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $insert->execute([
            ':product_code' => $productCode,
            ':product_name' => $productName,
            ':category_id' => $categoryId,
            ':supplier_id' => $supplierId,
            ':unit' => $unit,
            ':import_price' => $importPrice,
            ':sale_price' => $salePrice,
            ':quantity' => $quantity
        ]);

        $existingCodes[$productCode] = true;

        $inserted++;
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | XÓA SESSION IMPORT
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['product_import_rows']);

    $_SESSION['product_import_success'] =
        'Đã nhập thành công ' .
        $inserted .
        ' sản phẩm vào database.';

    header('Location: products.php');
    exit;

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['product_import_error'] =
        'Import thất bại: ' .
        $e->getMessage();

    header('Location: import_products.php');
    exit;
}