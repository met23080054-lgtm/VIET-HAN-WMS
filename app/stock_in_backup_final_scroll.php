<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/auth.php';

requireLogin();

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| LẤY NHÀ CUNG CẤP
|--------------------------------------------------------------------------
*/

$suppliers = $pdo
    ->query("
        SELECT *
        FROM suppliers
        ORDER BY supplier_name ASC
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| LẤY SẢN PHẨM
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT *
        FROM products
        ORDER BY product_name ASC
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO PHIẾU NHẬP
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $supplierId = (int) (
        $_POST['supplier_id'] ?? 0
    );

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 0
    );

    $unitPrice = (float) (
        $_POST['unit_price'] ?? 0
    );

    $receiptDate = trim(
        $_POST['receipt_date']
        ?? date('Y-m-d')
    );

    $note = trim(
        $_POST['note'] ?? ''
    );

    if ($supplierId <= 0) {

        $error =
            'Vui lòng chọn nhà cung cấp.';

    } elseif ($productId <= 0) {

        $error =
            'Vui lòng chọn sản phẩm.';

    } elseif ($quantity <= 0) {

        $error =
            'Số lượng nhập phải lớn hơn 0.';

    } elseif ($unitPrice < 0) {

        $error =
            'Đơn giá không được âm.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | BẮT ĐẦU TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | TẠO MÃ PHIẾU NHẬP
            |--------------------------------------------------------------------------
            */

            $receiptCode =
                'PN-'
                . date('YmdHis')
                . '-'
                . random_int(
                    100,
                    999
                );

            /*
            |--------------------------------------------------------------------------
            | TẠO PHIẾU NHẬP
            |--------------------------------------------------------------------------
            */

            $insertReceipt =
                $pdo->prepare("
                    INSERT INTO
                    stock_receipts (
                        receipt_code,
                        supplier_id,
                        created_by,
                        receipt_date,
                        note
                    )
                    VALUES (
                        ?, ?, ?, ?, ?
                    )
                ");

            $insertReceipt->execute([
                $receiptCode,
                $supplierId,
                $_SESSION['user_id'],
                $receiptDate,
                $note
            ]);

            $receiptId =
                (int) $pdo
                    ->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | LƯU CHI TIẾT PHIẾU
            |--------------------------------------------------------------------------
            */

            $insertItem =
                $pdo->prepare("
                    INSERT INTO
                    stock_receipt_items (
                        receipt_id,
                        product_id,
                        quantity,
                        unit_price
                    )
                    VALUES (
                        ?, ?, ?, ?
                    )
                ");

            $insertItem->execute([
                $receiptId,
                $productId,
                $quantity,
                $unitPrice
            ]);

            /*
            |--------------------------------------------------------------------------
            | TĂNG TỒN KHO
            |--------------------------------------------------------------------------
            */

            $updateStock =
                $pdo->prepare("
                    UPDATE products
                    SET quantity =
                        quantity + ?
                    WHERE id = ?
                ");

            $updateStock->execute([
                $quantity,
                $productId
            ]);

            /*
            |--------------------------------------------------------------------------
            | XÁC NHẬN LƯU
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            header(
                'Location: stock_in.php?message=success'
            );

            exit;

        } catch (
            Throwable $e
        ) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }

            $error =
                'Không thể tạo phiếu nhập: '
                . $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| THÔNG BÁO
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['message'])
    && $_GET['message']
        === 'success'
) {

    $message =
        'Tạo phiếu nhập thành công. '
        . 'Số lượng tồn kho đã được cập nhật.';
}

/*
|--------------------------------------------------------------------------
| LỊCH SỬ NHẬP KHO
|--------------------------------------------------------------------------
*/

$receipts = $pdo
    ->query("
        SELECT
            stock_receipts.id,
            stock_receipts.receipt_code,
            stock_receipts.receipt_date,
            stock_receipts.note,

            suppliers.supplier_name,

            users.full_name,

            products.product_code,
            products.product_name,
            products.unit,

            stock_receipt_items.quantity,
            stock_receipt_items.unit_price

        FROM stock_receipts

        LEFT JOIN suppliers
            ON stock_receipts.supplier_id
            = suppliers.id

        LEFT JOIN users
            ON stock_receipts.created_by
            = users.id

        INNER JOIN stock_receipt_items
            ON stock_receipts.id
            = stock_receipt_items.receipt_id

        INNER JOIN products
            ON stock_receipt_items.product_id
            = products.id

        ORDER BY
            stock_receipts.id DESC
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
Nhập kho
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
    justify-content:
        space-between;
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
    max-width: 1350px;
    margin: 30px auto;
    padding: 0 20px;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: #2563eb;
    font-weight: bold;
    text-decoration: none;
}

.grid {
    display: grid;
    grid-template-columns:
        390px 1fr;
    gap: 25px;
}

.box {
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow:
        0 5px 18px
        rgba(0, 0, 0, 0.08);
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
select,
textarea {
    width: 100%;
    margin-bottom: 16px;
    padding: 11px;
    border:
        1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 90px;
    resize: vertical;
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 7px;
    color: white;
    background: #16a34a;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #15803d;
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
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    overflow-y: hidden;
    box-sizing: border-box;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

th,
td {
    padding: 11px;
    border-bottom:
        1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

th {
    background: #f8fafc;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #64748b;
}

.total {
    font-weight: bold;
    color: #15803d;
}

@media (
    max-width: 950px
) {

    .grid {
        grid-template-columns:
            1fr;
    }

    .header {
        padding: 18px 20px;
    }

}

</style>

<style>html,body{max-width:100%;overflow-x:hidden!important;}</style></head>

<body>

<header class="header">

<h1>
NHẬP KHO
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

<?php if (
    $message !== ''
): ?>

<div class="success">

<?= htmlspecialchars(
    $message
) ?>

</div>

<?php endif; ?>

<?php if (
    $error !== ''
): ?>

<div class="error">

<?= htmlspecialchars(
    $error
) ?>

</div>

<?php endif; ?>

<div class="grid">

<div class="box">

<h2>
Tạo phiếu nhập
</h2>

<form method="POST">

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
Sản phẩm *
</label>

<select
    name="product_id"
    required
>

<option value="">

-- Chọn sản phẩm --

</option>

<?php foreach (
    $products
    as $product
): ?>

<option
    value="<?= $product['id'] ?>"
>

<?= htmlspecialchars(
    $product[
        'product_code'
    ]
) ?>

-

<?= htmlspecialchars(
    $product[
        'product_name'
    ]
) ?>

</option>

<?php endforeach; ?>

</select>

<label>
Ngày nhập *
</label>

<input
    type="date"
    name="receipt_date"
    required
    value="<?= date(
        'Y-m-d'
    ) ?>"
>

<label>
Số lượng nhập *
</label>

<input
    type="number"
    name="quantity"
    min="1"
    required
>

<label>
Đơn giá nhập *
</label>

<input
    type="number"
    name="unit_price"
    min="0"
    step="0.01"
    required
>

<label>
Ghi chú
</label>

<textarea
    name="note"
    placeholder="
Ví dụ:
Nhập vật tư cho công trình...
"
></textarea>

<button type="submit">

Lưu phiếu nhập

</button>

</form>

</div>

<div class="box">

<h2>
Lịch sử nhập kho
</h2>

<?php if (
    count($receipts) === 0
): ?>

<div class="empty">

Chưa có phiếu nhập nào.

</div>

<?php else: ?>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>Mã phiếu</th>

<th>Ngày nhập</th>

<th>Nhà cung cấp</th>

<th>Sản phẩm</th>

<th>SL</th>

<th>ĐVT</th>

<th>Đơn giá</th>

<th>Thành tiền</th>

<th>Người tạo</th>

<th>Ghi chú</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $receipts
    as $receipt
): ?>

<tr>

<td>

<?= htmlspecialchars(
    $receipt[
        'receipt_code'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'receipt_date'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'supplier_name'
    ] ?? ''
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'product_name'
    ]
) ?>

</td>

<td>

<?= number_format(
    $receipt[
        'quantity'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'unit'
    ]
) ?>

</td>

<td>

<?= number_format(
    $receipt[
        'unit_price'
    ],
    0,
    ',',
    '.'
) ?>

đ

</td>

<td class="total">

<?= number_format(
    $receipt[
        'quantity'
    ]
    *
    $receipt[
        'unit_price'
    ],
    0,
    ',',
    '.'
) ?>

đ

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'full_name'
    ] ?? ''
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt[
        'note'
    ] ?? ''
) ?>

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

</body>

</html>