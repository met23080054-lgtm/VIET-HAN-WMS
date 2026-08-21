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
    ->fetchAll(PDO::FETCH_ASSOC);

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
            import_price,
            quantity
        FROM products
        ORDER BY product_name ASC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO PHIẾU NHẬP - 1 PHIẾU NHIỀU SẢN PHẨM
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $supplierId = (int) ($_POST['supplier_id'] ?? 0);

    $receiptDate = trim(
        $_POST['receipt_date'] ?? date('Y-m-d')
    );

    $note = trim(
        $_POST['note'] ?? ''
    );

    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];

    /*
     * Bảo đảm dữ liệu luôn là mảng.
     */
    if (!is_array($productIds)) {
        $productIds = [$productIds];
    }

    if (!is_array($quantities)) {
        $quantities = [$quantities];
    }

    if (!is_array($unitPrices)) {
        $unitPrices = [$unitPrices];
    }

    $items = [];
    $seenProducts = [];

    if ($supplierId <= 0) {

        $error = 'Vui lòng chọn nhà cung cấp.';

    } elseif ($receiptDate === '') {

        $error = 'Vui lòng chọn ngày nhập.';

    } elseif (count($productIds) === 0) {

        $error = 'Phiếu nhập phải có ít nhất 1 sản phẩm.';

    } else {

        /*
         * Đọc toàn bộ các dòng sản phẩm trước khi mở transaction.
         * Không cho phép cùng một sản phẩm xuất hiện 2 lần trong cùng phiếu.
         */
        foreach ($productIds as $index => $rawProductId) {

            $productId = (int) $rawProductId;

            $quantity = (int) (
                $quantities[$index] ?? 0
            );

            $rawUnitPrice = trim(
                (string) (
                    $unitPrices[$index] ?? ''
                )
            );

            if ($productId <= 0) {
                $error = 'Dòng sản phẩm số ' . ($index + 1)
                    . ' chưa chọn sản phẩm.';
                break;
            }

            if (isset($seenProducts[$productId])) {
                $error = 'Sản phẩm bị trùng trong phiếu: '
                    . 'vui lòng chỉ nhập mỗi sản phẩm một lần.';
                break;
            }

            if ($quantity <= 0) {
                $error = 'Số lượng ở dòng ' . ($index + 1)
                    . ' phải lớn hơn 0.';
                break;
            }

            if ($rawUnitPrice === '' || !is_numeric($rawUnitPrice)) {
                $error = 'Đơn giá ở dòng ' . ($index + 1)
                    . ' không hợp lệ.';
                break;
            }

            $unitPrice = (float) $rawUnitPrice;

            if ($unitPrice < 0) {
                $error = 'Đơn giá ở dòng ' . ($index + 1)
                    . ' không được âm.';
                break;
            }

            $seenProducts[$productId] = true;

            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice
            ];
        }

        if ($error === '' && count($items) === 0) {
            $error = 'Phiếu nhập phải có ít nhất 1 sản phẩm.';
        }
    }

    if ($error === '') {

        try {

            /*
             * TRANSACTION:
             * - Tạo 1 dòng ở stock_receipts
             * - Tạo N dòng ở stock_receipt_items
             * - Cập nhật tồn kho cho N sản phẩm
             *
             * Nếu bất kỳ bước nào lỗi -> rollback toàn bộ.
             */
            $pdo->beginTransaction();

            /*
             * Tạo mã phiếu.
             */
            $receiptCode =
                'PN-'
                . date('YmdHis')
                . '-'
                . random_int(100, 999);

            /*
             * Tạo phiếu nhập cha.
             */
            $insertReceipt = $pdo->prepare("
                INSERT INTO stock_receipts (
                    receipt_code,
                    supplier_id,
                    created_by,
                    receipt_date,
                    note
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $insertReceipt->execute([
                $receiptCode,
                $supplierId,
                $_SESSION['user_id'],
                $receiptDate,
                $note
            ]);

            $receiptId = (int) $pdo->lastInsertId();

            /*
             * Chuẩn bị câu lệnh dùng lại cho tất cả sản phẩm.
             */
            $insertItem = $pdo->prepare("
                INSERT INTO stock_receipt_items (
                    receipt_id,
                    product_id,
                    quantity,
                    unit_price
                )
                VALUES (?, ?, ?, ?)
            ");

            /*
             * UPDATE có điều kiện:
             * chỉ tăng tồn kho cho sản phẩm thực sự tồn tại.
             */
            $updateStock = $pdo->prepare("
                UPDATE products
                SET quantity = quantity + ?
                WHERE id = ?
            ");

            /*
             * Lưu N dòng chi tiết + tăng tồn N sản phẩm.
             */
            foreach ($items as $item) {

                $insertItem->execute([
                    $receiptId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price']
                ]);

                $updateStock->execute([
                    $item['quantity'],
                    $item['product_id']
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new Exception(
                        'Không tìm thấy sản phẩm ID '
                        . $item['product_id']
                        . ' để cập nhật tồn kho.'
                    );
                }
            }

            /*
             * Tạo thông báo sau khi dữ liệu phiếu đã hợp lệ.
             */
            require_once __DIR__ . '/config/notifications.php';

            $stmtUser = $pdo->prepare("
                SELECT full_name
                FROM users
                WHERE id = ?
            ");

            $stmtUser->execute([
                $_SESSION['user_id']
            ]);

            $creatorName = $stmtUser->fetchColumn();

            if (!$creatorName) {
                $creatorName = 'Người dùng';
            }

            createNotification(
                $pdo,
                'stock_in',
                'Phiếu nhập kho mới',
                $receiptCode
                    . ' — '
                    . $creatorName
                    . ' vừa tạo phiếu nhập '
                    . count($items)
                    . ' sản phẩm.'
            );

            /*
             * Xác nhận toàn bộ transaction.
             */
            $pdo->commit();

            header(
                'Location: stock_in.php?message=success'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
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
    && $_GET['message'] === 'success'
) {
    $message =
        'Tạo phiếu nhập thành công. '
        . 'Tất cả sản phẩm trong phiếu đã được cập nhật tồn kho.';
}

/*
|--------------------------------------------------------------------------
| LỊCH SỬ NHẬP KHO
|--------------------------------------------------------------------------
|
| Một phiếu có thể có nhiều dòng sản phẩm.
| Vì vậy truy vấn này trả về 1 dòng cho mỗi sản phẩm trong phiếu.
|
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
            ON stock_receipts.supplier_id = suppliers.id

        LEFT JOIN users
            ON stock_receipts.created_by = users.id

        INNER JOIN stock_receipt_items
            ON stock_receipts.id = stock_receipt_items.receipt_id

        INNER JOIN products
            ON stock_receipt_items.product_id = products.id

        ORDER BY
            stock_receipts.id DESC,
            stock_receipt_items.id ASC
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

<title>Nhập kho</title>

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
    font-weight: bold;
    text-decoration: none;
}

.grid {
    display: grid;
    grid-template-columns: 560px minmax(0, 1fr);
    gap: 25px;
    align-items: start;
}

.box {
    min-width: 0;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
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
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 90px;
    resize: vertical;
}

.form-field {
    margin-bottom: 16px;
}

.items-title {
    margin: 20px 0 10px;
    font-size: 17px;
}

.items-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.items-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

.items-table th,
.items-table td {
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.items-table th {
    background: #f8fafc;
    text-align: left;
    white-space: nowrap;
}

.items-table input,
.items-table select {
    margin: 0;
}

.items-table .col-stt {
    width: 45px;
    text-align: center;
}

.items-table .col-product {
    min-width: 280px;
}

.items-table .col-qty {
    width: 110px;
}

.items-table .col-price {
    width: 150px;
}

.items-table .col-action {
    width: 70px;
    text-align: center;
}

.remove-row {
    width: auto;
    padding: 8px 10px;
    border: 0;
    border-radius: 6px;
    color: white;
    background: #dc2626;
    cursor: pointer;
    font-weight: bold;
}

.remove-row:hover {
    background: #b91c1c;
}

.add-row {
    width: auto;
    margin-top: 10px;
    padding: 9px 14px;
    border: 1px solid #2563eb;
    border-radius: 7px;
    color: #2563eb;
    background: white;
    cursor: pointer;
    font-weight: bold;
}

.add-row:hover {
    color: white;
    background: #2563eb;
}

.save-button {
    width: 100%;
    margin-top: 18px;
    padding: 13px;
    border: none;
    border-radius: 7px;
    color: white;
    background: #16a34a;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

.save-button:hover {
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

.history-table {
    width: max-content;
    min-width: 1100px;
    border-collapse: collapse;
}

.history-table th,
.history-table td {
    padding: 11px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

.history-table th {
    background: #f8fafc;
}

.total {
    font-weight: bold;
    color: #15803d;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #64748b;
}

.receipt-code {
    font-weight: bold;
    color: #1d4ed8;
}

@media (max-width: 1050px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .header {
        padding: 18px 20px;
    }
}

html,
body {
    max-width: 100%;
    overflow-x: hidden !important;
}

</style>

</head>

<body>

<header class="header">

<h1>NHẬP KHO</h1>

<a href="index.php">
← Dashboard
</a>

</header>

<main class="container">

<a class="back" href="index.php">
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

<div class="grid">

<!-- ==========================================================
     FORM TẠO PHIẾU NHẬP
     ========================================================== -->

<div class="box">

<h2>Tạo phiếu nhập</h2>

<form method="POST" id="stockInForm">

<div class="form-field">

<label>
Nhà cung cấp *
</label>

<select name="supplier_id" required>

<option value="">
-- Chọn nhà cung cấp --
</option>

<?php foreach ($suppliers as $supplier): ?>

<option value="<?= $supplier['id'] ?>">

<?= htmlspecialchars(
    $supplier['supplier_name']
) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-field">

<label>
Ngày nhập *
</label>

<input
    type="date"
    name="receipt_date"
    required
    value="<?= date('Y-m-d') ?>"
>

</div>

<div class="items-title">
Danh sách sản phẩm nhập
</div>

<div class="items-wrap">

<table class="items-table">

<thead>

<tr>

<th class="col-stt">
STT
</th>

<th class="col-product">
Sản phẩm *
</th>

<th class="col-qty">
Số lượng *
</th>

<th class="col-price">
Đơn giá *
</th>

<th class="col-action">
Xóa
</th>

</tr>

</thead>

<tbody id="itemsBody">

<tr class="item-row">

<td class="stt">
1
</td>

<td>

<select
    name="product_id[]"
    class="product-select"
    required
>

<option value="">
-- Chọn sản phẩm --
</option>

<?php foreach ($products as $product): ?>

<option
    value="<?= $product['id'] ?>"
    data-price="<?= htmlspecialchars(
        $product['import_price']
    ) ?>"
>

<?= htmlspecialchars(
    $product['product_code']
) ?>
-
<?= htmlspecialchars(
    $product['product_name']
) ?>

</option>

<?php endforeach; ?>

</select>

</td>

<td>

<input
    type="number"
    name="quantity[]"
    min="1"
    step="1"
    required
>

</td>

<td>

<input
    type="number"
    name="unit_price[]"
    class="unit-price"
    min="0"
    step="0.01"
    required
>

</td>

<td class="col-action">

<button
    type="button"
    class="remove-row"
    onclick="removeRow(this)"
>
×
</button>

</td>

</tr>

</tbody>

</table>

</div>

<button
    type="button"
    class="add-row"
    onclick="addRow()"
>
+ Thêm sản phẩm
</button>

<div class="form-field" style="margin-top:18px;">

<label>
Ghi chú
</label>

<textarea
    name="note"
    placeholder="Ví dụ: Nhập vật tư cho công trình..."
></textarea>

</div>

<button
    type="submit"
    class="save-button"
>
Lưu phiếu nhập
</button>

</form>

</div>

<!-- ==========================================================
     LỊCH SỬ
     ========================================================== -->

<div class="box">

<h2>Lịch sử nhập kho</h2>

<?php if (count($receipts) === 0): ?>

<div class="empty">
Chưa có phiếu nhập nào.
</div>

<?php else: ?>

<div class="table-wrap">

<table class="history-table">

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

<?php foreach ($receipts as $receipt): ?>

<tr>

<td class="receipt-code">

<?= htmlspecialchars(
    $receipt['receipt_code']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt['receipt_date']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt['supplier_name'] ?? ''
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt['product_name']
) ?>

</td>

<td>

<?= number_format(
    $receipt['quantity']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt['unit']
) ?>

</td>

<td>

<?= number_format(
    $receipt['unit_price'],
    0,
    ',',
    '.'
) ?>
đ

</td>

<td class="total">

<?= number_format(
    $receipt['quantity']
    * $receipt['unit_price'],
    0,
    ',',
    '.'
) ?>
đ

</td>

<td>

<?= htmlspecialchars(
    $receipt['full_name'] ?? ''
) ?>

</td>

<td>

<?= htmlspecialchars(
    $receipt['note'] ?? ''
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

const productOptionsHtml = `
<option value="">
-- Chọn sản phẩm --
</option>
<?php foreach ($products as $product): ?>
<option
    value="<?= $product['id'] ?>"
    data-price="<?= htmlspecialchars(
        $product['import_price']
    ) ?>"
>
<?= htmlspecialchars(
    $product['product_code']
) ?>
-
<?= htmlspecialchars(
    $product['product_name']
) ?>
</option>
<?php endforeach; ?>
`;

function updateRowNumbers() {

    const rows =
        document.querySelectorAll(
            '#itemsBody .item-row'
        );

    rows.forEach(function (row, index) {

        const stt =
            row.querySelector('.stt');

        stt.textContent =
            index + 1;

    });
}

function attachProductChange(row) {

    const select =
        row.querySelector('.product-select');

    const priceInput =
        row.querySelector('.unit-price');

    select.addEventListener(
        'change',
        function () {

            const option =
                this.options[
                    this.selectedIndex
                ];

            const price =
                option.dataset.price;

            if (
                price !== undefined
                && price !== ''
            ) {

                priceInput.value = price;

            }
        }
    );
}

function addRow() {

    const tbody =
        document.getElementById(
            'itemsBody'
        );

    const row =
        document.createElement('tr');

    row.className =
        'item-row';

    row.innerHTML = `
        <td class="stt"></td>

        <td>
            <select
                name="product_id[]"
                class="product-select"
                required
            >
                ${productOptionsHtml}
            </select>
        </td>

        <td>
            <input
                type="number"
                name="quantity[]"
                min="1"
                step="1"
                required
            >
        </td>

        <td>
            <input
                type="number"
                name="unit_price[]"
                class="unit-price"
                min="0"
                step="0.01"
                required
            >
        </td>

        <td class="col-action">
            <button
                type="button"
                class="remove-row"
                onclick="removeRow(this)"
            >
                ×
            </button>
        </td>
    `;

    tbody.appendChild(row);

    attachProductChange(row);

    updateRowNumbers();
}

function removeRow(button) {

    const tbody =
        document.getElementById(
            'itemsBody'
        );

    const rows =
        tbody.querySelectorAll(
            '.item-row'
        );

    /*
     * Luôn giữ lại ít nhất 1 dòng.
     */
    if (rows.length <= 1) {

        alert(
            'Phiếu nhập phải có ít nhất 1 sản phẩm.'
        );

        return;
    }

    button.closest('tr').remove();

    updateRowNumbers();
}

/*
 * Tự động điền đơn giá khi chọn sản phẩm
 * cho dòng đầu tiên.
 */
document.querySelectorAll(
    '#itemsBody .item-row'
).forEach(function (row) {

    attachProductChange(row);

});

/*
 * Chặn submit nếu có sản phẩm trùng.
 */
document.getElementById(
    'stockInForm'
).addEventListener(
    'submit',
    function (event) {

        const selects =
            document.querySelectorAll(
                '.product-select'
            );

        const selected =
            new Set();

        for (
            const select
            of selects
        ) {

            if (
                select.value === ''
            ) {

                continue;
            }

            if (
                selected.has(
                    select.value
                )
            ) {

                event.preventDefault();

                alert(
                    'Bạn đang chọn trùng sản phẩm. '
                    + 'Mỗi sản phẩm chỉ nên xuất hiện '
                    + 'một lần trong cùng một phiếu nhập.'
                );

                select.focus();

                return;
            }

            selected.add(
                select.value
            );
        }

    }
);

</script>

</body>

</html>
