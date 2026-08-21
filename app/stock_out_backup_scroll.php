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

$products = [];

$issues = [];
/*
|--------------------------------------------------------------------------
| LẤY SẢN PHẨM
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            id,
            product_code,
            product_name,
            unit,
            sale_price,
            quantity
        FROM products
        ORDER BY product_name ASC
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO PHIẾU XUẤT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 0
    );

    $unitPrice = (float) (
        $_POST['unit_price'] ?? 0
    );

    $issueDate = trim(
        $_POST['issue_date']
        ?? date('Y-m-d')
    );

    $recipient = trim(
        $_POST['recipient'] ?? ''
    );

    $note = trim(
        $_POST['note'] ?? ''
    );

    if ($productId <= 0) {

        $error =
            'Vui lòng chọn sản phẩm.';

    } elseif ($quantity <= 0) {

        $error =
            'Số lượng xuất phải lớn hơn 0.';

    } elseif ($unitPrice < 0) {

        $error =
            'Đơn giá không được âm.';

    } elseif ($recipient === '') {

        $error =
            'Vui lòng nhập người hoặc bộ phận nhận.';

    } else {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | KIỂM TRA TỒN KHO
            |--------------------------------------------------------------------------
            */

            $checkProduct =
                $pdo->prepare("
                    SELECT
                        product_name,
                        quantity
                    FROM products
                    WHERE id = ?
                ");

            $checkProduct->execute([
                $productId
            ]);

            $product =
                $checkProduct->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$product) {

                throw new Exception(
                    'Sản phẩm không tồn tại.'
                );
            }

            $currentQuantity =
                (int) $product[
                    'quantity'
                ];

            if (
                $quantity
                > $currentQuantity
            ) {

                throw new Exception(
                    'Không đủ hàng trong kho. '
                    . 'Tồn hiện tại: '
                    . $currentQuantity
                );
            }

            /*
            |--------------------------------------------------------------------------
            | TẠO MÃ PHIẾU XUẤT
            |--------------------------------------------------------------------------
            */

            $issueCode =
                'PX-'
                . date('YmdHis')
                . '-'
                . random_int(
                    100,
                    999
                );

            /*
            |--------------------------------------------------------------------------
            | TẠO PHIẾU XUẤT
            |--------------------------------------------------------------------------
            */

            $insertIssue =
                $pdo->prepare("
                    INSERT INTO
                    stock_issues (
                        issue_code,
                        created_by,
                        issue_date,
                        recipient,
                        note
                    )
                    VALUES (
                        ?, ?, ?, ?, ?
                    )
                ");

            $insertIssue->execute([
                $issueCode,
                $_SESSION['user_id'],
                $issueDate,
                $recipient,
                $note
            ]);

            $issueId =
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
                    stock_issue_items (
                        issue_id,
                        product_id,
                        quantity,
                        unit_price
                    )
                    VALUES (
                        ?, ?, ?, ?
                    )
                ");

            $insertItem->execute([
                $issueId,
                $productId,
                $quantity,
                $unitPrice
            ]);

            /*
            |--------------------------------------------------------------------------
            | TRỪ TỒN KHO
            |--------------------------------------------------------------------------
            */

            $updateStock =
                $pdo->prepare("
                    UPDATE products
                    SET quantity =
                        quantity - ?
                    WHERE id = ?
                ");

            $updateStock->execute([
                $quantity,
                $productId
            ]);

            /*
            |--------------------------------------------------------------------------
            | XÁC NHẬN GIAO DỊCH
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            header(
                'Location: stock_out.php?message=success'
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
                $e->getMessage();
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
        'Tạo phiếu xuất thành công. '
        . 'Tồn kho đã được cập nhật.';
}

/*
|--------------------------------------------------------------------------
| LỊCH SỬ XUẤT KHO
|--------------------------------------------------------------------------
*/

$issues = $pdo
    ->query("
        SELECT
            stock_issues.id,
            stock_issues.issue_code,
            stock_issues.issue_date,
            stock_issues.recipient,
            stock_issues.note,

            products.product_code,
            products.product_name,
            products.unit,

            stock_issue_items.quantity,
            stock_issue_items.unit_price

        FROM stock_issues

        INNER JOIN
        stock_issue_items

            ON stock_issues.id
            =
            stock_issue_items.issue_id

        INNER JOIN products

            ON
            stock_issue_items.product_id
            =
            products.id

        ORDER BY
            stock_issues.id DESC
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
Xuất kho
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
    background: #dc2626;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #b91c1c;
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

.stock-info {
    margin-top: -8px;
    margin-bottom: 15px;
    padding: 10px;
    color: #1e40af;
    background: #dbeafe;
    border-radius: 7px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1000px;
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

.total {
    color: #dc2626;
    font-weight: bold;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #64748b;
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

</head>

<body>

<header class="header">

<h1>
XUẤT KHO
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
Tạo phiếu xuất
</h2>

<form method="POST">

<label>
Sản phẩm *
</label>

<select
    name="product_id"
    id="product_id"
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
    data-stock="<?= $product['quantity'] ?>"
    data-unit="<?= htmlspecialchars(
        $product['unit']
    ) ?>"
    data-price="<?= $product['sale_price'] ?>"
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

<div
    class="stock-info"
    id="stock_info"
>

Chọn sản phẩm để xem tồn kho.

</div>

<label>
Ngày xuất *
</label>

<input
    type="date"
    name="issue_date"
    required
    value="<?= date(
        'Y-m-d'
    ) ?>"
>

<label>
Người hoặc bộ phận nhận *
</label>

<input
    type="text"
    name="recipient"
    required
    placeholder="
Ví dụ:
Đội thi công số 1
"
>

<label>
Số lượng xuất *
</label>

<input
    type="number"
    name="quantity"
    id="quantity"
    min="1"
    required
>

<label>
Đơn giá xuất *
</label>

<input
    type="number"
    name="unit_price"
    id="unit_price"
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
Xuất vật tư cho công trình...
"
></textarea>

<button type="submit">

Lưu phiếu xuất

</button>

</form>

</div>

<div class="box">

<h2>
Lịch sử xuất kho
</h2>

<?php if (
    count($issues) === 0
): ?>

<div class="empty">

Chưa có phiếu xuất nào.

</div>

<?php else: ?>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>Mã phiếu</th>

<th>Ngày xuất</th>

<th>Sản phẩm</th>

<th>Người nhận</th>

<th>SL</th>

<th>ĐVT</th>

<th>Đơn giá</th>

<th>Thành tiền</th>

<th>Ghi chú</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $issues
    as $issue
): ?>

<tr>

<td>

<?= htmlspecialchars(
    $issue[
        'issue_code'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $issue[
        'issue_date'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $issue[
        'product_name'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $issue[
        'recipient'
    ]
) ?>

</td>

<td>

<?= number_format(
    $issue[
        'quantity'
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $issue[
        'unit'
    ]
) ?>

</td>

<td>

<?= number_format(
    $issue[
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
    $issue[
        'quantity'
    ]
    *
    $issue[
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
    $issue[
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

<script>

const productSelect =
    document.getElementById(
        'product_id'
    );

const stockInfo =
    document.getElementById(
        'stock_info'
    );

const quantityInput =
    document.getElementById(
        'quantity'
    );

const priceInput =
    document.getElementById(
        'unit_price'
    );

productSelect.addEventListener(
    'change',
    function () {

        const option =
            this.options[
                this.selectedIndex
            ];

        const stock =
            option.dataset.stock;

        const unit =
            option.dataset.unit;

        const price =
            option.dataset.price;

        if (!stock) {

            stockInfo.innerText =
                'Chọn sản phẩm để xem tồn kho.';

            quantityInput.max = '';

            return;
        }

        stockInfo.innerText =
            'Tồn hiện tại: '
            + stock
            + ' '
            + unit;

        quantityInput.max =
            stock;

        priceInput.value =
            price;
    }
);

</script>

</body>

</html>