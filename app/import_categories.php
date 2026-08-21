<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    exit('Bạn không có quyền thực hiện thao tác này.');
}

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

if (
    !isset($_FILES['excel_file']) ||
    $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK
) {
    $_SESSION['category_import_error'] =
        'Không nhận được file Excel.';

    header('Location: categories.php');
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

    $_SESSION['category_import_error'] =
        'Chỉ hỗ trợ file XLSX, XLS hoặc CSV.';

    header('Location: categories.php');
    exit;
}

try {

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

    function normalizeCategoryValue($value): string
    {
        $value = trim((string)$value);

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return trim($value);
    }

    $existingCategories = [];

    $query = $pdo->query(
        'SELECT id, category_name
         FROM categories'
    );

    while ($category = $query->fetch(PDO::FETCH_ASSOC)) {

        $key = mb_strtolower(
            normalizeCategoryValue(
                $category['category_name']
            ),
            'UTF-8'
        );

        $existingCategories[$key] = [
            'id' => $category['id'],
            'name' => $category['category_name']
        ];
    }

    $preview = [];

    $validCount = 0;
    $duplicateCount = 0;
    $errorCount = 0;

    $excelNames = [];

    foreach ($rows as $rowNumber => $row) {

        if ($rowNumber === 1) {
            continue;
        }

        $categoryName =
            normalizeCategoryValue(
                $row['A'] ?? ''
            );

        $description =
            normalizeCategoryValue(
                $row['B'] ?? ''
            );

        if (
            $categoryName === '' &&
            $description === ''
        ) {
            continue;
        }

        if ($categoryName === '') {

            $preview[] = [
                'row' => $rowNumber,
                'name' => '',
                'description' => $description,
                'status' => 'error',
                'message' =>
                    'Thiếu tên danh mục.'
            ];

            $errorCount++;
            continue;
        }

        $key = mb_strtolower(
            $categoryName,
            'UTF-8'
        );

        if (isset($existingCategories[$key])) {

            $preview[] = [
                'row' => $rowNumber,
                'name' => $categoryName,
                'description' => $description,
                'status' => 'duplicate',
                'message' =>
                    'Danh mục đã tồn tại trong hệ thống.'
            ];

            $duplicateCount++;
            continue;
        }

        if (isset($excelNames[$key])) {

            $preview[] = [
                'row' => $rowNumber,
                'name' => $categoryName,
                'description' => $description,
                'status' => 'duplicate',
                'message' =>
                    'Danh mục bị trùng trong file Excel.'
            ];

            $duplicateCount++;
            continue;
        }

        $excelNames[$key] = true;

        $preview[] = [
            'row' => $rowNumber,
            'name' => $categoryName,
            'description' => $description,
            'status' => 'valid',
            'message' => 'Sẵn sàng nhập.'
        ];

        $validCount++;
    }

    $_SESSION['category_import_preview'] = [
        'file_name' => $file['name'],
        'rows' => $preview,
        'valid' => $validCount,
        'duplicate' => $duplicateCount,
        'error' => $errorCount
    ];

    header(
        'Location: categories.php?import_preview=1'
    );

    exit;

} catch (Throwable $e) {

    $_SESSION['category_import_error'] =
        'Không thể đọc file Excel: '
        . $e->getMessage();

    header('Location: categories.php');
    exit;
}