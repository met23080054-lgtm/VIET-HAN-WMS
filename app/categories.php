<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| KI?M TRA �ANG NH?P V� PH�N QUY?N
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/auth.php';

requireLogin();

$isAdmin = isAdmin();

require_once __DIR__ . '/config/database.php';


$message = '';
$error = '';


/*
|--------------------------------------------------------------------------
| THÊM DANH MỤC
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add'
    && $isAdmin
) {

    $categoryName = trim(
        $_POST['category_name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    if ($categoryName === '') {

        $error =
            'Tên danh mục không được để trống.';

    } else {

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM categories
            WHERE category_name = ?
        ");

        $check->execute([
            $categoryName
        ]);

        if (
            (int) $check->fetchColumn() > 0
        ) {

            $error =
                'Danh mục này đã tồn tại.';

        } else {

                /*
     * CONTIGUOUS_CATEGORY_ID_V1
     * Always reuse the smallest missing positive category ID.
     */
    $nextCategoryId = (int)$pdo->query("
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
    ")->fetchColumn();
$insert = $pdo->prepare("
                INSERT INTO categories (
                    id,
                    category_name,
                    description
                )
                VALUES (?, ?, ?)
            ");

            $insert->execute([
                $nextCategoryId,
                $categoryName,
                $description
            ]);

            header(
                'Location: categories.php?message=added'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| CẬP NHẬT DANH MỤC
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

    $categoryName = trim(
        $_POST['category_name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    if (
        $id <= 0
        || $categoryName === ''
    ) {

        $error =
            'Dữ liệu cập nhật không hợp lệ.';

    } else {

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM categories
            WHERE category_name = ?
            AND id != ?
        ");

        $check->execute([
            $categoryName,
            $id
        ]);

        if (
            (int) $check->fetchColumn() > 0
        ) {

            $error =
                'Tên danh mục đã tồn tại.';

        } else {

            $update = $pdo->prepare("
                UPDATE categories
                SET
                    category_name = ?,
                    description = ?
                WHERE id = ?
            ");

            $update->execute([
                $categoryName,
                $description,
                $id
            ]);

            header(
                'Location: categories.php?message=updated'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| XÓA DANH MỤC
|--------------------------------------------------------------------------
*/

if (
    $isAdmin
    && isset($_GET['delete'])
    && $_GET['delete'] !== ''
) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {

        $checkProduct = $pdo->prepare("
            SELECT COUNT(*)
            FROM products
            WHERE category_id = ?
        ");

        $checkProduct->execute([
            $id
        ]);

        if (
            (int) $checkProduct
                ->fetchColumn() > 0
        ) {

            header(
                'Location: categories.php?message=used'
            );

            exit;
        }

        $delete = $pdo->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");

        $delete->execute([
            $id
        ]);

        header(
            'Location: categories.php?message=deleted'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LẤY DANH MỤC CẦN SỬA
|--------------------------------------------------------------------------
*/

$editCategory = null;

if (
    $isAdmin
    && isset($_GET['edit'])
    && $_GET['edit'] !== ''
) {

    $id = (int) $_GET['edit'];

    $selectEdit = $pdo->prepare("
        SELECT *
        FROM categories
        WHERE id = ?
    ");

    $selectEdit->execute([
        $id
    ]);

    $editCategory = $selectEdit
        ->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| THÔNG BÁO
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['message'])
) {

    if (
        $_GET['message'] === 'added'
    ) {

        $message =
            'Thêm danh mục thành công.';
    }

    if (
        $_GET['message'] === 'updated'
    ) {

        $message =
            'Cập nhật danh mục thành công.';
    }

    if (
        $_GET['message'] === 'deleted'
    ) {

        $message =
            'Xóa danh mục thành công.';
    }

    if (
        $_GET['message'] === 'used'
    ) {

        $error =
            'Không thể xóa vì danh mục đang có sản phẩm.';
    }
}


/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH DANH MỤC
|--------------------------------------------------------------------------
*/

$importPreview = null;

if (
    $isAdmin
    && isset($_GET['import_preview'])
) {
    $importPreview =
        $_SESSION['category_import_preview'] ?? null;
}

$importError =
    $_SESSION['category_import_error'] ?? '';

if ($importError !== '') {
    unset($_SESSION['category_import_error']);
}

$categories = $pdo
    ->query("
        SELECT *
        FROM categories
        ORDER BY id ASC
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );

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
Quản lý danh mục
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


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| CONTAINER
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| LAYOUT
|--------------------------------------------------------------------------
*/

.right-column{
    min-width:0;
    display:flex;
    flex-direction:column;
    gap:16px;
}
.grid {
    display: grid;

    grid-template-columns:
        390px minmax(0, 1fr);

    gap: 25px;

    align-items: start;
}


/*
| Khi là Staff:
| chỉ còn bảng danh mục
| và bảng chiếm toàn bộ chiều rộng
*/

.grid.staff-view {
    display: block;
}


/*
|--------------------------------------------------------------------------
| BOX
|--------------------------------------------------------------------------
*/

.box {
    padding: 28px;

    background: #ffffff;

    border:
        1px solid #e2e8f0;

    border-radius: 16px;

    box-shadow:
        0 10px 30px
        rgba(
            15,
            23,
            42,
            0.08
        );
}


.box h2 {
    margin:
        0 0 22px;

    padding-bottom:
        16px;

    color:
        #1e3a5f;

    font-size:
        23px;

    border-bottom:
        2px solid
        #e2e8f0;
}


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

label {
    display: block;

    margin-bottom:
        7px;

    font-weight:
        bold;
}


input,
textarea {

    width:
        100%;

    margin-bottom:
        15px;

    padding:
        11px;

    border:
        1px solid
        #cbd5e1;

    border-radius:
        7px;

    font-size:
        14px;
}


textarea {

    min-height:
        130px;

    resize:
        vertical;
}


button {

    width:
        100%;

    padding:
        12px;

    border:
        none;

    border-radius:
        7px;

    color:
        white;

    background:
        #2563eb;

    font-size:
        15px;

    font-weight:
        bold;

    cursor:
        pointer;
}


button:hover {

    background:
        #1d4ed8;
}


/*
|--------------------------------------------------------------------------
| THÔNG BÁO
|--------------------------------------------------------------------------
*/

.success {

    margin-bottom:
        20px;

    padding:
        13px;

    color:
        #166534;

    background:
        #dcfce7;

    border-radius:
        7px;
}


.error {

    margin-bottom:
        20px;

    padding:
        13px;

    color:
        #991b1b;

    background:
        #fee2e2;

    border-radius:
        7px;
}


/*
|--------------------------------------------------------------------------
| THÔNG BÁO STAFF
|--------------------------------------------------------------------------
*/

.view-only {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    margin-bottom:
        22px;

    padding:
        16px 20px;

    color:
        #1e40af;

    background:
        #eff6ff;

    border:
        1px solid
        #bfdbfe;

    border-left:
        5px solid
        #2563eb;

    border-radius:
        10px;
}


.view-only strong {

    white-space:
        nowrap;

    font-size:
        15px;
}


.view-only span {

    color:
        #475569;

    font-size:
        14px;

    line-height:
        1.6;
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.table-wrap {

    width:
        100%;

    overflow-x:
        auto;

    border:
        1px solid
        #e2e8f0;

    border-radius:
        12px;
}


table {

    width:
        100%;

    min-width:
        850px;

    border-collapse:
        separate;

    border-spacing:
        0;

    background:
        white;
}


th,
td {

    padding:
        16px 15px;

    border-bottom:
        1px solid
        #e2e8f0;

    text-align:
        left;

    vertical-align:
        middle;
}


th {

    color:
        #ffffff;

    background:
        #1e3a5f;

    font-size:
        13px;

    font-weight:
        700;

    text-transform:
        uppercase;

    letter-spacing:
        0.4px;

    white-space:
        nowrap;
}


th:first-child {

    border-top-left-radius:
        10px;
}


th:last-child {

    border-top-right-radius:
        10px;
}


tbody tr {

    transition:
        background
        0.2s ease;
}


tbody tr:nth-child(even) {

    background:
        #f8fafc;
}


tbody tr:hover {

    background:
        #eff6ff;
}


tbody tr:last-child td {

    border-bottom:
        none;
}


td {

    color:
        #334155;

    font-size:
        14px;
}


td:nth-child(2) {

    color:
        #1e293b;

    font-weight:
        700;
}


/*
|--------------------------------------------------------------------------
| NÚT THAO TÁC
|--------------------------------------------------------------------------
*/

.action {

    display:
        flex;

    gap:
        7px;
}


.edit {

    padding:
        7px 10px;

    color:
        white;

    background:
        #f59e0b;

    border-radius:
        5px;

    text-decoration:
        none;
}


.delete {

    padding:
        7px 10px;

    color:
        white;

    background:
        #dc2626;

    border-radius:
        5px;

    text-decoration:
        none;
}


.edit:hover {

    background:
        #d97706;
}


.delete:hover {

    background:
        #b91c1c;
}


.cancel {

    display:
        inline-block;

    margin-top:
        15px;

    color:
        #2563eb;

    font-weight:
        bold;

    text-decoration:
        none;
}


.empty {

    padding:
        25px;

    text-align:
        center;

    color:
        #64748b;
}


/*
|--------------------------------------------------------------------------
| TIM KIEM VA LOC DANH MUC
|--------------------------------------------------------------------------
*/

.category-tools {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;

    padding: 15px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    border-radius: 12px;

    flex-wrap: wrap;
}


.category-search {

    position: relative;

    flex: 1;

    min-width: 280px;
}


.category-search-icon {

    position: absolute;

    left: 14px;

    top: 50%;

    transform: translateY(-50%);

    font-size: 17px;

    color: #64748b;

    pointer-events: none;
}


.category-search input {

    width: 100%;

    margin: 0;

    padding: 12px 14px 12px 42px;

    border: 1px solid #cbd5e1;

    border-radius: 8px;

    background: #ffffff;

    color: #1e293b;

    font-size: 14px;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}


.category-search input:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, 0.12);
}


.category-sort {

    display: flex;

    align-items: center;

    gap: 8px;

}


.category-sort label {

    margin: 0;

    white-space: nowrap;

    color: #475569;

    font-size: 13px;

    font-weight: 700;
}


.category-sort select {

    margin: 0;

    padding: 11px 12px;

    border: 1px solid #cbd5e1;

    border-radius: 8px;

    background: #ffffff;

    color: #1e293b;

    font-size: 14px;

    cursor: pointer;

}


.category-count {

    padding: 9px 12px;

    color: #1e40af;

    background: #eff6ff;

    border: 1px solid #bfdbfe;

    border-radius: 8px;

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;
}


.clear-search {

    width: auto !important;

    margin: 0 !important;

    padding: 10px 14px !important;

    color: #334155 !important;

    background: #e2e8f0 !important;

    border-radius: 8px !important;

    font-size: 13px !important;

    font-weight: 700 !important;

    white-space: nowrap;
}


.clear-search:hover {

    background: #cbd5e1 !important;
}


.search-empty {

    display: none;

    margin-bottom: 15px;

    padding: 16px;

    text-align: center;

    color: #64748b;

    background: #f8fafc;

    border: 1px dashed #cbd5e1;

    border-radius: 10px;

    font-size: 14px;
}


@media (
    max-width: 900px
) {

    .category-tools {

        align-items: stretch;

        flex-direction: column;

    }


    .category-search {

        min-width: 100%;

    }


    .category-sort {

        justify-content: space-between;

    }


    .category-sort select {

        flex: 1;

    }


    .category-count {

        text-align: center;

    }


    .clear-search {

        width: 100% !important;

    }

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (
    max-width: 1000px
) {

    .grid {

        grid-template-columns:
            1fr;
    }


    .header {

        padding:
            18px 20px;
    }


    .view-only {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

}

</style>

</head>


<body>


<header class="header">

<h1>

QUẢN LÝ DANH MỤC

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

Ch? d? xem d�nh cho Nh�n vi�n

</strong>


<span>

Bạn có thể xem danh sách và thông tin
danh mục vật tư, nhưng không có quyền
thêm, sửa hoặc xóa dữ liệu.

</span>

</div>

<?php endif; ?>


<div
class="
grid
<?=
    !$isAdmin
    ? 'staff-view'
    : ''
?>
"
>


<?php if ($isAdmin): ?>


<div class="box">


<h2>

<?=
$editCategory
? 'Cập nhật danh mục'
: 'Thêm danh mục'
?>

</h2>


<form method="POST">


<input
type="hidden"
name="action"
value="<?= $editCategory ? 'update' : 'add' ?>"
>


<?php if ($editCategory): ?>


<input
type="hidden"
name="id"
value="
<?=
$editCategory['id']
?>
"
>


<?php endif; ?>


<label>

Tên danh mục *

</label>


<input
type="text"
name="category_name"
required
placeholder="
VD: Vật tư điện
"
value="
<?= htmlspecialchars(
$editCategory[
'category_name'
] ?? ''
) ?>
"
>


<label>

Mô tả

</label>


<textarea
name="description"
placeholder="
Nhập mô tả danh mục...
"
><?= htmlspecialchars(
$editCategory[
'description'
] ?? ''
) ?></textarea>


<button
type="submit"
>

<?=
$editCategory
? 'Cập nhật danh mục'
: 'Thêm danh mục'
?>

</button>


</form>


<?php if ($editCategory): ?>


<a
class="cancel"
href="categories.php"
>

Hủy chỉnh sửa

</a>


<?php endif; ?>


</div>


<?php endif; ?>

<div class="right-column">



<?php if ($isAdmin): ?>

<div style="margin:0 0 20px;padding:18px;background:#f8fafc;border:1px solid #dbeafe;border-radius:10px;">

    <div style="font-size:18px;font-weight:bold;color:#1e3a5f;margin-bottom:8px;">
        Thêm danh mục sản phẩm từ Excel
    </div>

    <div style="font-size:13px;color:#64748b;margin-bottom:14px;">
        Hỗ trợ XLSX, XLS và CSV. Hệ thống sẽ kiểm tra dữ liệu trước khi nhập.
    </div>

    <?php if ($importError !== ''): ?>

        <div style="margin-bottom:14px;padding:10px 12px;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:6px;">
            <?= htmlspecialchars($importError, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="import_categories.php"
        enctype="multipart/form-data"
        style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;"
    >

        <input
            type="file"
            name="excel_file"
            accept=".xlsx,.xls,.csv"
            required
            style="flex:1;min-width:260px;margin:0;"
        >

        <button
            type="submit"
            style="width:auto;padding:10px 18px;background:#16a34a;border-radius:6px;"
        >
            Xem trước dữ liệu
        </button>

    </form>

</div>

<?php endif; ?>

<?php if ($importPreview): ?>

<div style="margin:0 0 20px;padding:18px;background:#ffffff;border:2px solid #bfdbfe;border-radius:10px;">

    <h3 style="margin:0 0 15px;color:#1e3a5f;">
        Kết quả kiểm tra file Excel
    </h3>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;">

        <span style="padding:8px 12px;background:#dcfce7;color:#166534;border-radius:6px;">
            Hợp lệ:
            <?= (int)($importPreview['valid'] ?? 0) ?>
        </span>

        <span style="padding:8px 12px;background:#fef3c7;color:#92400e;border-radius:6px;">
            Trùng:
            <?= (int)($importPreview['duplicate'] ?? 0) ?>
        </span>

        <span style="padding:8px 12px;background:#fee2e2;color:#991b1b;border-radius:6px;">
            Lỗi:
            <?= (int)($importPreview['error'] ?? 0) ?>
        </span>

    </div>

    <div style="overflow-x:auto;">

        <table style="min-width:700px;">

            <thead>
                <tr>
                    <th>Dòng</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach (($importPreview['rows'] ?? []) as $item): ?>

                <tr>

                    <td>
                        <?= (int)($item['row'] ?? 0) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $item['name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $item['description'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td>

                        <?php if (($item['status'] ?? '') === 'valid'): ?>

                            <span style="padding:5px 8px;background:#dcfce7;color:#166534;border-radius:5px;">
                                Hợp lệ
                            </span>

                        <?php elseif (($item['status'] ?? '') === 'duplicate'): ?>

                            <span style="padding:5px 8px;background:#fef3c7;color:#92400e;border-radius:5px;">
                                Trùng
                            </span>

                        <?php else: ?>

                            <span style="padding:5px 8px;background:#fee2e2;color:#991b1b;border-radius:5px;">
                                Lỗi
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $item['message'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php if (($importPreview['valid'] ?? 0) > 0): ?>

        <div style="margin-top:16px;padding:14px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">

            <div style="margin-bottom:10px;color:#166534;font-weight:bold;">
                Có <?= (int)$importPreview['valid'] ?> danh mục hợp lệ, sẵn sàng nhập.
            </div>

            <form
                method="POST"
                action="import_categories_commit.php"
                onsubmit="return confirm('Bạn có chắc muốn nhập <?= (int)$importPreview['valid'] ?> danh mục hợp lệ vào database?');"
            >

                <button
                    type="submit"
                    style="width:auto;padding:10px 18px;background:#16a34a;color:white;border:0;border-radius:6px;cursor:pointer;font-weight:bold;"
                >
                    Xác nhận nhập <?= (int)$importPreview['valid'] ?> dòng hợp lệ
                </button>

            </form>

        </div>

    <?php endif; ?>

</div>

<?php endif; ?>

<div class="box">


<h2>

Danh sách danh mục

</h2>

<div class="category-tools">

    <div class="category-search">

        <span class="category-search-icon">&#128269;</span>

        <input
            type="search"
            id="categorySearch"
            placeholder="T&igrave;m theo t&ecirc;n danh m&#7909;c..."
            autocomplete="off"
            spellcheck="false"
        >

    </div>


    <div class="category-sort">

        <label for="categorySort">
            S&#7855;p x&#7871;p:
        </label>

        <select id="categorySort">

            <option value="default">
                M&#7863;c &#273;&#7883;nh
            </option>

            <option value="az">
                T&ecirc;n A &rarr; Z
            </option>

            <option value="za">
                T&ecirc;n Z &rarr; A
            </option>

        </select>

    </div>


    <div class="category-count">

        Hi&#7875;n th&#7883;:
        <span id="categoryCount">
            <?= count($categories) ?>
        </span>
        / <?= count($categories) ?>

    </div>


    <button
        type="button"
        id="clearCategorySearch"
        class="clear-search"
    >
        X&oacute;a l&#7885;c
    </button>

</div>


<div
    id="categorySearchEmpty"
    class="search-empty"
>
    Kh&ocirc;ng t&igrave;m th&#7845;y danh m&#7909;c ph&ugrave; h&#7907;p.
</div>



<?php if (
count($categories) === 0
): ?>


<div class="empty">

Chưa có danh mục nào.

</div>


<?php else: ?>


<div class="table-wrap">


<table>


<thead>


<tr>

<th>ID</th>

<th>Tên danh mục</th>

<th>Mô tả</th>

<?php if ($isAdmin): ?>

<th>Thao tác</th>

<?php endif; ?>

</tr>


</thead>


<tbody>


<?php foreach (
$categories
as $category
): ?>


<tr>


<td>

<?=
$category['id']
?>

</td>


<td>

<?= htmlspecialchars(
$category[
'category_name'
]
) ?>

</td>


<td>

<?= htmlspecialchars(
$category[
'description'
] ?? ''
) ?>

</td>


<?php if ($isAdmin): ?>


<td>


<div class="action">


<a
class="edit"
href="
categories.php?edit=
<?=
$category['id']
?>
"
>

Sửa

</a>


<a
class="delete"
href="
categories.php?delete=
<?=
$category['id']
?>
"
onclick="
return confirm(
'Bạn có chắc muốn xóa danh mục này?'
);
"
>

Xóa

</a>


</div>


</td>


<?php endif; ?>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php endif; ?>


</div>
</div>



</div>


</main>


<script id="categoryFilterScript">

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById("categorySearch");

        const sortSelect =
            document.getElementById("categorySort");

        const clearButton =
            document.getElementById("clearCategorySearch");

        const countElement =
            document.getElementById("categoryCount");

        const emptyElement =
            document.getElementById("categorySearchEmpty");

        const table =
            document.querySelector(".table-wrap table");


        if (
            !searchInput ||
            !sortSelect ||
            !clearButton ||
            !countElement ||
            !emptyElement ||
            !table
        ) {

            console.error(
                "Category filter: khong tim thay thanh phan giao dien."
            );

            return;
        }


        const tbody =
            table.tBodies.length
                ? table.tBodies[0]
                : table.createTBody();


        const rows =
            Array.from(
                tbody.querySelectorAll("tr")
            );


        const originalOrder =
            rows.slice();


        function normalizeText(text) {

            return String(text || "")
                .toLocaleLowerCase("vi-VN")
                .normalize("NFD")
                .replace(
                    /[\u0300-\u036f]/g,
                    ""
                )
                .replace(
                    /Ä‘/g,
                    "d"
                )
                .trim();

        }


        function getCategoryName(row) {

            if (!row.cells || row.cells.length < 2) {
                return "";
            }

            return row.cells[1].textContent || "";

        }


        function sortRows() {

            const mode =
                sortSelect.value;


            let sortedRows;


            if (mode === "az") {

                sortedRows =
                    rows.slice().sort(
                        function (a, b) {

                            const nameA =
                                normalizeText(
                                    getCategoryName(a)
                                );

                            const nameB =
                                normalizeText(
                                    getCategoryName(b)
                                );

                            return nameA.localeCompare(
                                nameB,
                                "vi"
                            );

                        }
                    );

            }

            else if (mode === "za") {

                sortedRows =
                    rows.slice().sort(
                        function (a, b) {

                            const nameA =
                                normalizeText(
                                    getCategoryName(a)
                                );

                            const nameB =
                                normalizeText(
                                    getCategoryName(b)
                                );

                            return nameB.localeCompare(
                                nameA,
                                "vi"
                            );

                        }
                    );

            }

            else {

                sortedRows =
                    originalOrder.slice();

            }


            sortedRows.forEach(
                function (row) {

                    tbody.appendChild(row);

                }
            );

        }


        function applyFilter() {

            const keyword =
                normalizeText(
                    searchInput.value
                );


            let visibleCount = 0;


            rows.forEach(
                function (row) {

                    const categoryName =
                        normalizeText(
                            getCategoryName(row)
                        );


                    const matched =
                        keyword === "" ||
                        categoryName.includes(
                            keyword
                        );


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
                visibleCount;


            emptyElement.style.display =
                visibleCount === 0
                    ? "block"
                    : "none";

        }


        function refresh() {

            sortRows();

            applyFilter();

        }


        searchInput.addEventListener(
            "input",
            function () {

                applyFilter();

            }
        );


        searchInput.addEventListener(
            "keyup",
            function () {

                applyFilter();

            }
        );


        sortSelect.addEventListener(
            "change",
            function () {

                refresh();

            }
        );


        clearButton.addEventListener(
            "click",
            function () {

                searchInput.value = "";

                sortSelect.value = "default";

                refresh();

                searchInput.focus();

            }
        );


        refresh();


        console.log(
            "Category filter: JavaScript da hoat dong."
        );

    }
);

</script>
</body>

</html>