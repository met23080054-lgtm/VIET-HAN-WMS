<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| KIỂM TRA ĐĂNG NHẬP VÀ PHÂN QUYỀN
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

            $insert = $pdo->prepare("
                INSERT INTO categories (
                    category_name,
                    description
                )
                VALUES (?, ?)
            ");

            $insert->execute([
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

$categories = $pdo
    ->query("
        SELECT *
        FROM categories
        ORDER BY id DESC
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

Chế độ xem dành cho Nhân viên

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
value="
<?=
$editCategory
? 'update'
: 'add'
?>
"
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


<div class="box">


<h2>

Danh sách danh mục

</h2>


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


</main>


</body>

</html>