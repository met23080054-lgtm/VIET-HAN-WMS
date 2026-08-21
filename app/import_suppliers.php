<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: suppliers.php');
    exit;
}

if (
    !isset($_FILES['excel_file']) ||
    $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK
) {
    $_SESSION['supplier_import_error'] =
        'Không nhận được file Excel.';

    header('Location: suppliers.php');
    exit;
}

$file = $_FILES['excel_file'];

$extension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);

$allowedExtensions = [
    'xlsx',
    'xls',
    'csv'
];

if (!in_array($extension, $allowedExtensions, true)) {

    $_SESSION['supplier_import_error'] =
        'Chỉ hỗ trợ file XLSX, XLS hoặc CSV.';

    header('Location: suppliers.php');
    exit;
}

try {

    /*
     * Đọc Excel
     */
    $spreadsheet = IOFactory::load(
        $file['tmp_name']
    );

    $sheet = $spreadsheet->getActiveSheet();

    $rows = $sheet->toArray(
        null,
        true,
        true,
        true
    );

    if (count($rows) < 2) {

        throw new Exception(
            'File Excel không có dữ liệu.'
        );
    }

    /*
     * Chuẩn hóa chuỗi
     */
    function normalizeSupplierValue($value): string
    {
        $value = (string)$value;

        // Loại bỏ khoảng trắng đầu/cuối
        $value = trim($value);

        // Chuyển nhiều khoảng trắng liên tiếp thành 1
        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return trim($value);
    }

    /*
     * Lấy NCC hiện tại
     */
    $existingSuppliers = [];

    $query = $pdo->query(
        'SELECT id, supplier_name
         FROM suppliers'
    );

    while ($supplier = $query->fetch(PDO::FETCH_ASSOC)) {

        $key = mb_strtolower(
            normalizeSupplierValue(
                $supplier['supplier_name']
            ),
            'UTF-8'
        );

        $existingSuppliers[$key] = [
            'id' => $supplier['id'],
            'name' => $supplier['supplier_name']
        ];
    }

    /*
     * Kết quả kiểm tra
     */
    $preview = [];

    $validCount = 0;
    $duplicateCount = 0;
    $errorCount = 0;

    foreach ($rows as $rowNumber => $row) {

        // Bỏ dòng tiêu đề
        if ($rowNumber === 1) {
            continue;
        }

        $supplierName =
            normalizeSupplierValue(
                $row['A'] ?? ''
            );

        $phone =
            normalizeSupplierValue(
                $row['B'] ?? ''
            );

        $email =
            normalizeSupplierValue(
                $row['C'] ?? ''
            );

        $address =
            normalizeSupplierValue(
                $row['D'] ?? ''
            );

        /*
         * Bỏ dòng hoàn toàn trống
         */
        if (
            $supplierName === '' &&
            $phone === '' &&
            $email === '' &&
            $address === ''
        ) {
            continue;
        }

        /*
         * Kiểm tra tên NCC
         */
        if ($supplierName === '') {

            $preview[] = [
                'row' => $rowNumber,
                'name' => '',
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'status' => 'error',
                'message' =>
                    'Thiếu tên nhà cung cấp.'
            ];

            $errorCount++;

            continue;
        }

        /*
         * Kiểm tra email
         */
        if (
            $email !== '' &&
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $preview[] = [
                'row' => $rowNumber,
                'name' => $supplierName,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'status' => 'error',
                'message' =>
                    'Email không hợp lệ.'
            ];

            $errorCount++;

            continue;
        }

        /*
         * Kiểm tra trùng với database
         */
        $key = mb_strtolower(
            $supplierName,
            'UTF-8'
        );

        if (isset($existingSuppliers[$key])) {

            $preview[] = [
                'row' => $rowNumber,
                'name' => $supplierName,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'status' => 'duplicate',
                'message' =>
                    'NCC đã tồn tại trong hệ thống.'
            ];

            $duplicateCount++;

            continue;
        }

        /*
         * Kiểm tra trùng ngay trong file Excel
         */
        $excelDuplicate = false;

        foreach ($preview as $item) {

            if (
                $item['status'] === 'valid' &&
                mb_strtolower(
                    $item['name'],
                    'UTF-8'
                ) === $key
            ) {

                $excelDuplicate = true;

                break;
            }
        }

        if ($excelDuplicate) {

            $preview[] = [
                'row' => $rowNumber,
                'name' => $supplierName,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'status' => 'duplicate',
                'message' =>
                    'NCC bị trùng trong file Excel.'
            ];

            $duplicateCount++;

            continue;
        }

        /*
         * Hợp lệ
         */
        $preview[] = [
            'row' => $rowNumber,
            'name' => $supplierName,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'status' => 'valid',
            'message' => 'Sẵn sàng nhập.'
        ];

        $validCount++;
    }

    /*
     * Lưu kết quả preview vào session
     */
    $_SESSION['supplier_import_preview'] = [
        'file_name' => $file['name'],
        'rows' => $preview,
        'valid' => $validCount,
        'duplicate' => $duplicateCount,
        'error' => $errorCount
    ];

    header(
        'Location: suppliers.php?import_preview=1'
    );

    exit;

} catch (Throwable $e) {

    $_SESSION['supplier_import_error'] =
        'Không thể đọc file Excel: '
        . $e->getMessage();

    header('Location: suppliers.php');

    exit;
}