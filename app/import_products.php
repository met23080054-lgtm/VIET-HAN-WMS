<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

use PhpOffice\PhpSpreadsheet\IOFactory;

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo instanceof PDO) {
    die('Không thể kết nối database.');
}

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];
$rows = [];
$success = false;

/*
|--------------------------------------------------------------------------
| EXPECTED EXCEL HEADERS
|--------------------------------------------------------------------------
*/

$expectedHeaders = [
    'MÃ SẢN PHẨM',
    'TÊN SẢN PHẨM',
    'DANH MỤC',
    'NHÀ CUNG CẤP',
    'ĐƠN VỊ',
    'GIÁ NHẬP',
    'GIÁ BÁN',
    'SỐ LƯỢNG'
];

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function normalizeText($value): string
{
    $value = trim((string)$value);

    // Chuẩn hóa khoảng trắng liên tiếp
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim($value);
}

/*
|--------------------------------------------------------------------------
| HANDLE UPLOAD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['excel_file'])) {

        $errors[] = 'Vui lòng chọn file Excel.';

    } else {

        $file = $_FILES['excel_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors[] = 'Upload file thất bại. Mã lỗi: ' . $file['error'];

        } else {

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            $allowedExtensions = ['xlsx', 'xls', 'csv'];

            if (!in_array($extension, $allowedExtensions, true)) {

                $errors[] =
                    'Chỉ hỗ trợ file XLSX, XLS hoặc CSV.';

            } else {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | LOAD EXCEL
                    |--------------------------------------------------------------------------
                    */

                    $spreadsheet = IOFactory::load($file['tmp_name']);

                    $sheet = $spreadsheet->getActiveSheet();

                    $data = $sheet->toArray(
                        null,
                        true,
                        true,
                        false
                    );

                    if (count($data) < 2) {

                        throw new Exception(
                            'File Excel không có dữ liệu sản phẩm.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CHECK HEADER
                    |--------------------------------------------------------------------------
                    */

                    $headers = array_map(
                        'normalizeText',
                        $data[0]
                    );

                    $headers = array_slice($headers, 0, 8);

                    if ($headers !== $expectedHeaders) {

                        $errors[] = 'Tiêu đề Excel không đúng.';

                        $errors[] =
                            'Yêu cầu: ' .
                            implode(' | ', $expectedHeaders);

                        $errors[] =
                            'File hiện tại: ' .
                            implode(' | ', $headers);

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | LOAD CATEGORIES
                        |--------------------------------------------------------------------------
                        */

                        $categoryMap = [];

                        $stmt = $pdo->query(
                            'SELECT id, category_name FROM categories'
                        );

                        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $categoryMap[
                                normalizeText($r['category_name'])
                            ] = (int)$r['id'];
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | LOAD SUPPLIERS
                        |--------------------------------------------------------------------------
                        */

                        $supplierMap = [];

                        $stmt = $pdo->query(
                            'SELECT id, supplier_name FROM suppliers'
                        );

                        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $supplierMap[
                                normalizeText($r['supplier_name'])
                            ] = (int)$r['id'];
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

                            $existingCodes[
                                normalizeText($r['product_code'])
                            ] = true;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDATE EACH ROW
                        |--------------------------------------------------------------------------
                        */

                        foreach ($data as $index => $row) {

                            // Bỏ dòng header
                            if ($index === 0) {
                                continue;
                            }

                            $excelRow = $index + 1;

                            // Đảm bảo có đủ 8 cột
                            $row = array_pad($row, 8, '');

                            $productCode = normalizeText($row[0]);
                            $productName = normalizeText($row[1]);
                            $categoryName = normalizeText($row[2]);
                            $supplierName = normalizeText($row[3]);
                            $unit = normalizeText($row[4]);

                            $importPrice = trim((string)$row[5]);
                            $salePrice = trim((string)$row[6]);
                            $quantity = trim((string)$row[7]);

                            $rowErrors = [];

                            /*
                            |--------------------------------------------------------------------------
                            | REQUIRED FIELDS
                            |--------------------------------------------------------------------------
                            */

                            if ($productCode === '') {
                                $rowErrors[] =
                                    'Mã sản phẩm không được để trống.';
                            }

                            if ($productName === '') {
                                $rowErrors[] =
                                    'Tên sản phẩm không được để trống.';
                            }

                            if ($unit === '') {
                                $rowErrors[] =
                                    'Đơn vị không được để trống.';
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | CATEGORY
                            |--------------------------------------------------------------------------
                            */

                            $categoryId = null;

                            if ($categoryName !== '') {

                                if (
                                    !array_key_exists(
                                        $categoryName,
                                        $categoryMap
                                    )
                                ) {

                                    $rowErrors[] =
                                        'Danh mục "' .
                                        $categoryName .
                                        '" không tồn tại.';

                                } else {

                                    $categoryId =
                                        $categoryMap[$categoryName];
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | SUPPLIER
                            |--------------------------------------------------------------------------
                            */

                            $supplierId = null;

                            if ($supplierName !== '') {

                                if (
                                    !array_key_exists(
                                        $supplierName,
                                        $supplierMap
                                    )
                                ) {

                                    $rowErrors[] =
                                        'Nhà cung cấp "' .
                                        $supplierName .
                                        '" không tồn tại.';

                                } else {

                                    $supplierId =
                                        $supplierMap[$supplierName];
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | PRICE
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $importPrice !== '' &&
                                !is_numeric($importPrice)
                            ) {

                                $rowErrors[] =
                                    'Giá nhập không hợp lệ.';
                            }

                            if (
                                $salePrice !== '' &&
                                !is_numeric($salePrice)
                            ) {

                                $rowErrors[] =
                                    'Giá bán không hợp lệ.';
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $quantity !== '' &&
                                (
                                    !is_numeric($quantity) ||
                                    (float)$quantity < 0 ||
                                    floor((float)$quantity) != (float)$quantity
                                )
                            ) {

                                $rowErrors[] =
                                    'Số lượng phải là số nguyên >= 0.';
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | PRODUCT CODE DUPLICATE
                            |--------------------------------------------------------------------------
                            */

                            if ($productCode !== '') {

                                if (
                                    isset($existingCodes[$productCode])
                                ) {

                                    $rowErrors[] =
                                        'Mã sản phẩm "' .
                                        $productCode .
                                        '" đã tồn tại trong database.';
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | DUPLICATE INSIDE EXCEL
                            |--------------------------------------------------------------------------
                            */

                            static $excelCodes = [];

                            if ($productCode !== '') {

                                if (
                                    isset($excelCodes[$productCode])
                                ) {

                                    $rowErrors[] =
                                        'Mã sản phẩm "' .
                                        $productCode .
                                        '" bị trùng trong file Excel.';

                                } else {

                                    $excelCodes[$productCode] = true;
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | ADD PREVIEW ROW
                            |--------------------------------------------------------------------------
                            */

                            $rows[] = [

                                'excel_row' => $excelRow,

                                'product_code' => $productCode,

                                'product_name' => $productName,

                                'category_name' => $categoryName,

                                'category_id' => $categoryId,

                                'supplier_name' => $supplierName,

                                'supplier_id' => $supplierId,

                                'unit' => $unit,

                                'import_price' =>
                                    $importPrice === ''
                                        ? 0
                                        : (float)$importPrice,

                                'sale_price' =>
                                    $salePrice === ''
                                        ? 0
                                        : (float)$salePrice,

                                'quantity' =>
                                    $quantity === ''
                                        ? 0
                                        : (int)$quantity,

                                'errors' => $rowErrors,

                                'valid' => empty($rowErrors)
                            ];
                        }
                    }

                } catch (Throwable $e) {

                    $errors[] =
                        'Không thể đọc file Excel: ' .
                        $e->getMessage();
                }
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($rows)) {

    $_SESSION['product_import_rows'] = $rows;

}

$totalRows = count($rows);

$validRows = 0;
$errorRows = 0;

foreach ($rows as $r) {

    if ($r['valid']) {
        $validRows++;
    } else {
        $errorRows++;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Nhập sản phẩm từ Excel</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}

.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
}

.card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

h1 {
    margin: 0 0 8px;
    font-size: 24px;
}

.subtitle {
    color: #6b7280;
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
}

.btn {
    display: inline-block;
    border: 0;
    border-radius: 8px;
    padding: 11px 18px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}

.btn-primary {
    background: #2563eb;
    color: #fff;
}

.btn-secondary {
    background: #6b7280;
    color: #fff;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 12px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.summary {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.summary-item {
    padding: 15px 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.summary-number {
    font-size: 22px;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th,
td {
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    vertical-align: top;
}

th {
    background: #f8fafc;
    font-weight: 600;
    white-space: nowrap;
}

.status-valid {
    color: #15803d;
    font-weight: 600;
}

.status-error {
    color: #dc2626;
    font-weight: 600;
}

.error-list {
    margin: 0;
    padding-left: 18px;
    color: #dc2626;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.small {
    font-size: 13px;
    color: #6b7280;
}

</style>

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Nhập sản phẩm từ Excel</h1>

        <div class="subtitle">
            Tải file Excel để kiểm tra dữ liệu trước khi nhập vào kho.
        </div>

        <?php foreach ($errors as $error): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endforeach; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-group">

                <label for="excel_file">
                    Chọn file Excel
                </label>

                <input
                    type="file"
                    id="excel_file"
                    name="excel_file"
                    accept=".xlsx,.xls,.csv"
                    required
                >

                <div class="small">
                    Hỗ trợ XLSX, XLS, CSV.
                </div>

            </div>

            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Kiểm tra dữ liệu
                </button>

                <a
                    href="products.php"
                    class="btn btn-secondary"
                >
                    Quay lại
                </a>

            </div>

        </form>

    </div>


    <?php if ($totalRows > 0): ?>

        <div class="card">

            <h2>Preview dữ liệu</h2>

            <div class="summary">

                <div class="summary-item">
                    <div>Tổng dòng</div>
                    <div class="summary-number">
                        <?= $totalRows ?>
                    </div>
                </div>

                <div class="summary-item">
                    <div>Hợp lệ</div>
                    <div class="summary-number status-valid">
                        <?= $validRows ?>
                    </div>
                </div>

                <div class="summary-item">
                    <div>Lỗi</div>
                    <div class="summary-number status-error">
                        <?= $errorRows ?>
                    </div>
                </div>

            </div>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th>Dòng</th>

                        <th>Mã sản phẩm</th>

                        <th>Tên sản phẩm</th>

                        <th>Danh mục</th>

                        <th>Nhà cung cấp</th>

                        <th>Đơn vị</th>

                        <th>Giá nhập</th>

                        <th>Giá bán</th>

                        <th>Số lượng</th>

                        <th>Trạng thái</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td>
                                <?= $r['excel_row'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['product_code']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['product_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['category_name']) ?>

                                <?php if ($r['category_id'] !== null): ?>

                                    <div class="small">
                                        ID: <?= $r['category_id'] ?>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($r['supplier_name']) ?>

                                <?php if ($r['supplier_id'] !== null): ?>

                                    <div class="small">
                                        ID: <?= $r['supplier_id'] ?>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($r['unit']) ?>
                            </td>

                            <td>
                                <?= number_format($r['import_price'], 0, ',', '.') ?>
                            </td>

                            <td>
                                <?= number_format($r['sale_price'], 0, ',', '.') ?>
                            </td>

                            <td>
                                <?= number_format($r['quantity'], 0, ',', '.') ?>
                            </td>

                            <td>

                                <?php if ($r['valid']): ?>

                                    <span class="status-valid">
                                        ✓ Hợp lệ
                                    </span>

                                <?php else: ?>

                                    <span class="status-error">
                                        ✗ Có lỗi
                                    </span>

                                    <ul class="error-list">

                                        <?php foreach ($r['errors'] as $error): ?>

                                            <li>
                                                <?= htmlspecialchars($error) ?>
                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

          <?php if ($validRows > 0 && $errorRows === 0): ?>

    <div class="alert alert-success" style="margin-top:20px;">

        Tất cả <?= $validRows ?> dòng đều hợp lệ.

        <br>

        <strong>Kiểm tra lần cuối trước khi nhập vào database.</strong>

    </div>

    <form
        method="POST"
        action="import_products_commit.php"
        onsubmit="return confirm('Bạn có chắc chắn muốn nhập <?= $validRows ?> sản phẩm vào database không?');"
    >

        <div class="actions">

            <a
                href="products.php"
                class="btn btn-secondary"
            >
                Hủy
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Nhập <?= $validRows ?> sản phẩm
            </button>

        </div>

    </form>

<?php elseif ($errorRows > 0): ?>

                <div class="alert alert-error" style="margin-top:20px;">

                    File còn <?= $errorRows ?> dòng lỗi.
                    Vui lòng sửa Excel trước khi nhập.

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>