<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$isAdmin = isset($_SESSION['role'])
    && mb_strtolower(
        trim($_SESSION['role'])
    ) === 'admin';

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| LẤY DANH MỤC VÀ NHÀ CUNG CẤP
|--------------------------------------------------------------------------
*/

$categories = $pdo
    ->query("
        SELECT *
        FROM categories
        ORDER BY category_name ASC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo
    ->query("
        SELECT *
        FROM suppliers
        ORDER BY supplier_name ASC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| THÊM SẢN PHẨM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add'
    && $isAdmin
) {

    $productCode = trim(
        $_POST['product_code'] ?? ''
    );

    $productName = trim(
        $_POST['product_name'] ?? ''
    );

    $categoryId = (int) (
        $_POST['category_id'] ?? 0
    );

    $supplierId = (int) (
        $_POST['supplier_id'] ?? 0
    );

    $unit = trim(
        $_POST['unit'] ?? ''
    );

    $importPrice = (float) (
        $_POST['import_price'] ?? 0
    );

    $salePrice = (float) (
        $_POST['sale_price'] ?? 0
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 0
    );

    if (
        $productCode === ''
        || $productName === ''
        || $categoryId <= 0
        || $supplierId <= 0
        || $unit === ''
    ) {

        $error =
            'Vui lòng nhập đầy đủ thông tin bắt buộc.';

    } elseif (
        $importPrice < 0
        || $salePrice < 0
        || $quantity < 0
    ) {

        $error =
            'Giá và số lượng không được âm.';

    } else {

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM products
            WHERE product_code = ?
        ");

        $check->execute([
            $productCode
        ]);

        if (
            (int) $check->fetchColumn() > 0
        ) {

            $error =
                'Mã sản phẩm đã tồn tại.';

        } else {

            $insert = $pdo->prepare("
                INSERT INTO products (
                    product_code,
                    product_name,
                    category_id,
                    supplier_id,
                    unit,
                    import_price,
                    sale_price,
                    quantity
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $insert->execute([
                $productCode,
                $productName,
                $categoryId,
                $supplierId,
                $unit,
                $importPrice,
                $salePrice,
                $quantity
            ]);

            header(
                'Location: products.php?message=added'
            );

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| CẬP NHẬT SẢN PHẨM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'update'
    && $isAdmin
) {

    $id = (int) (
        $_POST['id'] ?? 0
    );

    $productCode = trim(
        $_POST['product_code'] ?? ''
    );

    $productName = trim(
        $_POST['product_name'] ?? ''
    );

    $categoryId = (int) (
        $_POST['category_id'] ?? 0
    );

    $supplierId = (int) (
        $_POST['supplier_id'] ?? 0
    );

    $unit = trim(
        $_POST['unit'] ?? ''
    );

    $importPrice = (float) (
        $_POST['import_price'] ?? 0
    );

    $salePrice = (float) (
        $_POST['sale_price'] ?? 0
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 0
    );

    if (
        $id <= 0
        || $productCode === ''
        || $productName === ''
        || $categoryId <= 0
        || $supplierId <= 0
        || $unit === ''
    ) {

        $error =
            'Dữ liệu cập nhật không hợp lệ.';

    } elseif (
        $importPrice < 0
        || $salePrice < 0
        || $quantity < 0
    ) {

        $error =
            'Giá và số lượng không được âm.';

    } else {

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM products
            WHERE product_code = ?
            AND id != ?
        ");

        $check->execute([
            $productCode,
            $id
        ]);

        if (
            (int) $check->fetchColumn() > 0
        ) {

            $error =
                'Mã sản phẩm đã tồn tại.';

        } else {

            $update = $pdo->prepare("
                UPDATE products
                SET
                    product_code = ?,
                    product_name = ?,
                    category_id = ?,
                    supplier_id = ?,
                    unit = ?,
                    import_price = ?,
                    sale_price = ?,
                    quantity = ?
                WHERE id = ?
            ");

            $update->execute([
                $productCode,
                $productName,
                $categoryId,
                $supplierId,
                $unit,
                $importPrice,
                $salePrice,
                $quantity,
                $id
            ]);

            header(
                'Location: products.php?message=updated'
            );

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| XÓA SẢN PHẨM
|--------------------------------------------------------------------------
*/

if (
    $isAdmin
    && isset($_GET['delete'])
    && $_GET['delete'] !== ''
) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {

        $checkReceipt = $pdo->prepare("
            SELECT COUNT(*)
            FROM stock_receipt_items
            WHERE product_id = ?
        ");

        $checkReceipt->execute([
            $id
        ]);

        $checkIssue = $pdo->prepare("
            SELECT COUNT(*)
            FROM stock_issue_items
            WHERE product_id = ?
        ");

        $checkIssue->execute([
            $id
        ]);

        if (
            (int) $checkReceipt
                ->fetchColumn() > 0
            ||
            (int) $checkIssue
                ->fetchColumn() > 0
        ) {

            header(
                'Location: products.php?message=used'
            );

            exit;
        }

        $delete = $pdo->prepare("
            DELETE FROM products
            WHERE id = ?
        ");

        $delete->execute([
            $id
        ]);

        header(
            'Location: products.php?message=deleted'
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| LẤY SẢN PHẨM CẦN SỬA
|--------------------------------------------------------------------------
*/

$editProduct = null;

if (
    $isAdmin
    && isset($_GET['edit'])
    && $_GET['edit'] !== ''
) {

    $id = (int) $_GET['edit'];

    $selectEdit = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id = ?
    ");

    $selectEdit->execute([
        $id
    ]);

    $editProduct = $selectEdit
        ->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| THÔNG BÁO
|--------------------------------------------------------------------------
*/

if (isset($_GET['message'])) {

    if (
        $_GET['message'] === 'added'
    ) {

        $message =
            'Thêm sản phẩm thành công.';
    }

    if (
        $_GET['message'] === 'updated'
    ) {

        $message =
            'Cập nhật sản phẩm thành công.';
    }

    if (
        $_GET['message'] === 'deleted'
    ) {

        $message =
            'Xóa sản phẩm thành công.';
    }

    if (
        $_GET['message'] === 'used'
    ) {

        $error =
            'Không thể xóa vì sản phẩm đã có giao dịch nhập hoặc xuất kho.';
    }
}

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH SẢN PHẨM
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            products.*,
            categories.category_name,
            suppliers.supplier_name
        FROM products

        LEFT JOIN categories
            ON products.category_id
            = categories.id

        LEFT JOIN suppliers
            ON products.supplier_id
            = suppliers.id

        ORDER BY products.id ASC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Quản lý sản phẩm
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 40px;
    color: white;
    background: #1e3a5f;
}

.header h1 {
    margin: 0;
    font-size: 23px;
}

.header a {
    color: white;
    text-decoration: none;
}

.container {
    max-width: 1450px;
    margin: 30px auto;
    padding: 0 20px;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}

.grid {
    display: grid;
    grid-template-columns: 390px minmax(0, 1fr);
    gap: 25px;
    align-items: start;
}

/* Khi là Nhân viên: chỉ còn bảng sản phẩm,
   bảng sẽ chiếm toàn bộ chiều rộng */
.grid.staff-view {
    display: block;
}

.box {
    padding: 28px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow:
        0 10px 30px
        rgba(15, 23, 42, 0.08);
}

.box h2 {
    margin: 0 0 22px;
    padding-bottom: 16px;
    color: #1e3a5f;
    font-size: 23px;
    border-bottom: 2px solid #e2e8f0;
}

.box h2 {
    margin-top: 0;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
}

input,
select {
    width: 100%;
    margin-bottom: 15px;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 7px;
    color: white;
    background: #2563eb;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

.success {
    margin-bottom: 20px;
    padding: 13px;
    color: #166534;
    background: #dcfce7;
    border-radius: 7px;
}

.error {
    margin-bottom: 20px;
    padding: 13px;
    color: #991b1b;
    background: #fee2e2;
    border-radius: 7px;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 1150px;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
}

th,
td {
    padding: 16px 15px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

th {
    color: #ffffff;
    background: #1e3a5f;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

th:first-child {
    border-top-left-radius: 10px;
}

th:last-child {
    border-top-right-radius: 10px;
}

tbody tr {
    transition:
        background 0.2s ease,
        transform 0.2s ease;
}

tbody tr:nth-child(even) {
    background: #f8fafc;
}

tbody tr:hover {
    background: #eff6ff;
}

tbody tr:last-child td {
    border-bottom: none;
}

td {
    color: #334155;
    font-size: 14px;
}

td:nth-child(2) {
    color: #2563eb;
    font-weight: 700;
}

td:nth-child(3) {
    color: #1e293b;
    font-weight: 700;
}

td:nth-child(7),
td:nth-child(8) {
    color: #047857;
    font-weight: 700;
}

td:nth-child(9) {
    color: #1e3a5f;
    font-weight: 700;
}

.action {
    display: flex;
    gap: 7px;
}

.edit {
    padding: 7px 10px;
    color: white;
    background: #f59e0b;
    border-radius: 5px;
    text-decoration: none;
}

.delete {
    padding: 7px 10px;
    color: white;
    background: #dc2626;
    border-radius: 5px;
    text-decoration: none;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #64748b;
}

.cancel {
    display: inline-block;
    margin-top: 15px;
}

@media (max-width: 1000px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .header {
        padding: 18px 20px;
    }

}


/* Thông báo dành cho tài khoản Nhân viên */

.view-only {
    display: flex;
    align-items: center;
    gap: 12px;

    margin-bottom: 22px;
    padding: 16px 20px;

    color: #1e40af;
    background: #eff6ff;

    border: 1px solid #bfdbfe;
    border-left: 5px solid #2563eb;

    border-radius: 10px;
}

.view-only strong {
    white-space: nowrap;
    font-size: 15px;
}

.view-only span {
    color: #475569;
    font-size: 14px;
    line-height: 1.6;
}

</style>

</head>

<body>

<header class="header">

<h1>
QUẢN LÝ SẢN PHẨM
</h1>

<a href="index.php">
← Dashboard
</a>

</header>

<main class="container">

<a
    class="back"
    href="index.php"
>

← Quay lại Dashboard

</a>

<?php if ($message !== ''): ?>

<div class="success">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<?php if ($error !== ''): ?>

<div class="error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<?php if (!$isAdmin): ?>

<div class="view-only">

    <strong>
        Chế độ xem dành cho Nhân viên
    </strong>

    <span>
        Bạn có thể xem danh sách và thông tin sản phẩm,
        nhưng không có quyền thêm, sửa hoặc xóa dữ liệu.
    </span>

</div>

<?php endif; ?>

<?php
require __DIR__ . '/product_import_panel.php';
?>

<div class="grid <?= !$isAdmin ? 'staff-view' : '' ?>">

<?php if ($isAdmin): ?>

<div class="box">

<h2>

<?= $editProduct
    ? 'Cập nhật sản phẩm'
    : 'Thêm sản phẩm'
?>

</h2>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="<?= $editProduct
        ? 'update'
        : 'add'
    ?>"
>

<?php if ($editProduct): ?>

<input
    type="hidden"
    name="id"
    value="<?= $editProduct['id'] ?>"
>

<?php endif; ?>

<label>
Mã sản phẩm *
</label>

<input
    type="text"
    name="product_code"
    required
    placeholder="VD: VT-001"
    value="<?= htmlspecialchars(
        $editProduct[
            'product_code'
        ] ?? ''
    ) ?>"
>

<label>
Tên sản phẩm *
</label>

<input
    type="text"
    name="product_name"
    required
    value="<?= htmlspecialchars(
        $editProduct[
            'product_name'
        ] ?? ''
    ) ?>"
>

<label>
Danh mục *
</label>

<select
    name="category_id"
    required
>

<option value="">
-- Chọn danh mục --
</option>

<?php foreach (
    $categories
    as $category
): ?>

<option
    value="<?= $category['id'] ?>"
    <?= (
        (int) (
            $editProduct[
                'category_id'
            ] ?? 0
        )
        ===
        (int) $category['id']
    )
    ? 'selected'
    : ''
    ?>
>

<?= htmlspecialchars(
    $category[
        'category_name'
    ]
) ?>

</option>

<?php endforeach; ?>

</select>

<label>
Nhà cung cấp *
</label>

<select
    name="supplier_id"
    required
>

<option value="">
-- Chọn nhà cung cấp --
</option>

<?php foreach (
    $suppliers
    as $supplier
): ?>

<option
    value="<?= $supplier['id'] ?>"
    <?= (
        (int) (
            $editProduct[
                'supplier_id'
            ] ?? 0
        )
        ===
        (int) $supplier['id']
    )
    ? 'selected'
    : ''
    ?>
>

<?= htmlspecialchars(
    $supplier[
        'supplier_name'
    ]
) ?>

</option>

<?php endforeach; ?>

</select>

<label>
Đơn vị tính *
</label>

<input
    type="text"
    name="unit"
    required
    placeholder="VD: Cái, Mét, Kg, Tấn..."
    value="<?= htmlspecialchars(
        $editProduct[
            'unit'
        ] ?? ''
    ) ?>"
>

<label>
Giá nhập
</label>

<input
    type="number"
    name="import_price"
    min="0"
    step="0.01"
    value="<?= htmlspecialchars(
        $editProduct[
            'import_price'
        ] ?? 0
    ) ?>"
>

<label>
Giá bán
</label>

<input
    type="number"
    name="sale_price"
    min="0"
    step="0.01"
    value="<?= htmlspecialchars(
        $editProduct[
            'sale_price'
        ] ?? 0
    ) ?>"
>

<label>
Số lượng tồn ban đầu
</label>

<input
    type="number"
    name="quantity"
    min="0"
    value="<?= htmlspecialchars(
        $editProduct[
            'quantity'
        ] ?? 0
    ) ?>"
>

<button type="submit">

<?= $editProduct
    ? 'Cập nhật sản phẩm'
    : 'Thêm sản phẩm'
?>

</button>

</form>

<?php if ($editProduct): ?>

<a
    class="cancel"
    href="products.php"
>

Hủy chỉnh sửa

</a>

<?php endif; ?>

</div>

<?php endif; ?>

<div class="box">

<h2>
Danh sách sản phẩm
</h2>

<?php if (
    count($products) === 0
): ?>

<div class="empty">

Chưa có sản phẩm nào.

</div>

<?php else: ?>

<div
    id="productFilterBar"
    style="
        display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,300px) auto auto; gap:12px; align-items:center;



        margin-bottom:16px;
        padding:14px;
        background:#f7f9fc;
        border:1px solid #dfe6ef;
        border-radius:12px;
    "
>

    <div
        style="
            grid-column:1 / -1; position:relative;
        "
    >

        <span
            style="
                position:absolute; left:12px; top:0; width:28px; height:42px; display:flex; align-items:center; justify-content:center; font-size:17px; line-height:1; color:#2563eb;
                pointer-events:none;
            "
        >&#128269;</span>

        <input
            type="text"
            id="productSearch"
            placeholder="Tìm mã hoặc tên sản phẩm..."
            autocomplete="off"
            style="
                width:100%;
                box-sizing:border-box;
                height:42px;
                padding:0 42px 0 44px;
                border:1px solid #cfd9e6;
                border-radius:8px;
                background:#fff;
                font-size:14px;
                outline:none;
            "
        >

        <button
            type="button"
            id="clearProductSearch"
            title="Xóa nội dung tìm kiếm"
            style="
                position:absolute;
                right:8px;
                top:50%;
                transform:translateY(-50%);
                border:0;
                background:transparent;
                color:#64748b;
                cursor:pointer;
                font-size:20px;
                line-height:1;
                padding:4px 7px;
                display:none;
            "
        >&times;</button>

    </div>


    <div
        style="
            display:flex;
            align-items:center;
            gap:8px;
            flex:0 1 300px; min-width:280px; height:42px; box-sizing:border-box;
        "
    >

        <label
            for="productCategoryFilter"
            style="
                font-weight:600;
                color:#1f416b;
                white-space:nowrap;
            "
        >
            Danh mục:
        </label>

        <select
            id="productCategoryFilter"
            style="
                flex:1;
                height:42px;
                box-sizing:border-box;
                margin-bottom:0;
                padding:0 12px;
                background:#fff;
                font-size:14px;
                color:#243b53;
                cursor:pointer;
            "
        >

            <option value="">Tất cả danh mục</option>

        </select>

    </div>


    <div
        id="productFilterCount"
        style="
            height:42px; box-sizing:border-box; display:flex; align-items:center; justify-content:center;
            padding:0 14px;
            border:1px solid #d7e0eb;
            border-radius:8px;
            background:#fff;
            color:#1f416b;
            font-weight:600;
            white-space:nowrap;
        "
    >
        Hiển thị: 0 / 0
    </div>


    <button
        type="button"
        id="clearProductFilters"
        style="
            height:42px; box-sizing:border-box;
            padding:0 16px;
            border:1px solid #d7e0eb;border-radius:8px;background:#e7edf5;
            color:#243b53;
            font-weight:600;
            cursor:pointer;
        "
    >
        Xóa lọc
    </button>

</div>
<div class="table-wrap">

<table>

<thead>

<tr>

<th>ID</th>

<th>Mã</th>

<th>Tên sản phẩm</th>

<th>Danh mục</th>

<th>Nhà cung cấp</th>

<th>ĐVT</th>

<th>Giá nhập</th>

<th>Giá bán</th>

<th>Tồn</th>

<th>Thao tác</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $products
    as $product
): ?>

<tr>

<td>

<?= $product['id'] ?>

</td>

<td>

<?= htmlspecialchars(
    $product[
        'product_code'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $product[
        'product_name'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $product[
        'category_name'
    ] ?? 'Chưa có'
) ?>

</td>

<td>

<?= htmlspecialchars(
    $product[
        'supplier_name'
    ] ?? 'Chưa có'
) ?>

</td>

<td>

<?= htmlspecialchars(
    $product['unit']
) ?>

</td>

<td>

<?= number_format(
    (float) $product[
        'import_price'
    ],
    0,
    ',',
    '.'
) ?>

d

</td>

<td>

<?= number_format(
    (float) $product[
        'sale_price'
    ],
    0,
    ',',
    '.'
) ?>

d

</td>

<td>

<?= $product['quantity'] ?>

</td>

<td>

<?php if ($isAdmin): ?>

<div class="action">

<a
    class="edit"
    href="products.php?edit=<?= $product['id'] ?>"
>

                    Sửa

</a>

<a
    class="delete"
    href="products.php?delete=<?= $product['id'] ?>"
    onclick="
        return confirm(
            'Bạn có chắc muốn xóa sản phẩm này?'
        );
    "
>

Xóa

</a>

</div>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>

</main>

<script id="productFilterScript">

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById("productSearch");

        const categoryFilter =
            document.getElementById(
                "productCategoryFilter"
            );

        const clearSearch =
            document.getElementById(
                "clearProductSearch"
            );

        const clearFilters =
            document.getElementById(
                "clearProductFilters"
            );

        const countElement =
            document.getElementById(
                "productFilterCount"
            );

        const table =
            document.querySelector(
                "#productFilterBar ~ .table-wrap table"
            );


        if (
            !searchInput ||
            !categoryFilter ||
            !clearSearch ||
            !clearFilters ||
            !countElement ||
            !table
        ) {

            console.error(
                "Product filter: khong tim thay thanh phan."
            );

            return;
        }


        const tbody =
            table.tBodies.length
                ? table.tBodies[0]
                : null;


        if (!tbody) {

            console.error(
                "Product filter: khong tim thay tbody."
            );

            return;
        }


        const rows =
            Array.from(
                tbody.querySelectorAll("tr")
            );


        function normalizeText(text) {

            return String(text || "")
                .toLocaleLowerCase("vi-VN")
                .normalize("NFD")
                .replace(
                    /[\u0300-\u036f]/g,
                    ""
                )
                .replace(
                    /đ/g,
                    "d"
                )
                .trim();

        }


        function getProductCode(row) {

            return row.cells[1]
                ? row.cells[1].textContent
                : "";

        }


        function getProductName(row) {

            return row.cells[2]
                ? row.cells[2].textContent
                : "";

        }


        function getCategoryName(row) {

            return row.cells[3]
                ? row.cells[3].textContent
                : "";

        }


        function populateCategories() {

            const categoryMap =
                new Map();


            rows.forEach(
                function (row) {

                    const original =
                        getCategoryName(row)
                            .replace(
                                /\s+/g,
                                " "
                            )
                            .trim();


                    if (!original) {
                        return;
                    }


                    const normalized =
                        normalizeText(
                            original
                        );


                    if (
                        normalized &&
                        !categoryMap.has(
                            normalized
                        )
                    ) {

                        categoryMap.set(
                            normalized,
                            original
                        );

                    }

                }
            );


            const categories =
                Array.from(
                    categoryMap.values()
                ).sort(
                    function (a, b) {

                        return normalizeText(a)
                            .localeCompare(
                                normalizeText(b),
                                "vi"
                            );

                    }
                );


            categories.forEach(
                function (category) {

                    const option =
                        document.createElement(
                            "option"
                        );

                    option.value =
                        normalizeText(
                            category
                        );

                    option.textContent =
                        category;

                    categoryFilter.appendChild(
                        option
                    );

                }
            );

        }


        function applyFilters() {

            const keyword =
                normalizeText(
                    searchInput.value
                );


            const selectedCategory =
                categoryFilter.value;


            let visibleCount = 0;


            rows.forEach(
                function (row) {

                    const code =
                        normalizeText(
                            getProductCode(row)
                        );


                    const name =
                        normalizeText(
                            getProductName(row)
                        );


                    const category =
                        normalizeText(
                            getCategoryName(row)
                        );


                    const matchSearch =
                        keyword === "" ||
                        code.includes(keyword) ||
                        name.includes(keyword) ||
                        category.includes(keyword);


                    const matchCategory =
                        selectedCategory === "" ||
                        category === selectedCategory;


                    const matched =
                        matchSearch &&
                        matchCategory;


                    row.style.display =
                        matched
                            ? ""
                            : "none";


                    if (matched) {
                        visibleCount++;
                    }

                }
            );


            countElement.textContent =
                "Hiển thị: " +
                visibleCount +
                " / " +
                rows.length;


            clearSearch.style.display =
                searchInput.value
                    ? "block"
                    : "none";

        }


        function clearAllFilters() {

            searchInput.value = "";

            categoryFilter.value = "";

            applyFilters();

            searchInput.focus();

        }


        searchInput.addEventListener(
            "input",
            applyFilters
        );


        categoryFilter.addEventListener(
            "change",
            applyFilters
        );


        clearSearch.addEventListener(
            "click",
            function () {

                searchInput.value = "";

                applyFilters();

                searchInput.focus();

            }
        );


        clearFilters.addEventListener(
            "click",
            clearAllFilters
        );


        populateCategories();

        applyFilters();


        console.log(
            "Product filter: JavaScript da hoat dong."
        );

    }
);

</script>
</body>

</html>
