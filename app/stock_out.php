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
    ->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO PHIẾU XUẤT - NHIỀU SẢN PHẨM
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];

    $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $recipient = trim($_POST['recipient'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!is_array($productIds) || !is_array($quantities) || !is_array($unitPrices)) {
        $error = 'Dữ liệu danh sách sản phẩm không hợp lệ.';
    } elseif (count($productIds) === 0) {
        $error = 'Vui lòng thêm ít nhất một sản phẩm.';
    } elseif (count($productIds) !== count($quantities) || count($productIds) !== count($unitPrices)) {
        $error = 'Dữ liệu sản phẩm, số lượng và đơn giá không khớp.';
    } elseif ($recipient === '') {
        $error = 'Vui lòng nhập người hoặc bộ phận nhận.';
    } else {

        $items = [];
        $seenProducts = [];

        foreach ($productIds as $i => $rawProductId) {
            $productId = (int) $rawProductId;
            $quantity = (int) ($quantities[$i] ?? 0);
            $unitPrice = (float) ($unitPrices[$i] ?? 0);

            if ($productId <= 0) {
                $error = 'Dòng ' . ($i + 1) . ': vui lòng chọn sản phẩm.';
                break;
            }

            if ($quantity <= 0) {
                $error = 'Dòng ' . ($i + 1) . ': số lượng xuất phải lớn hơn 0.';
                break;
            }

            if ($unitPrice < 0) {
                $error = 'Dòng ' . ($i + 1) . ': đơn giá không được âm.';
                break;
            }

            if (isset($seenProducts[$productId])) {
                $error = 'Sản phẩm bị trùng trong phiếu. Vui lòng mỗi sản phẩm chỉ xuất một lần trong cùng một phiếu.';
                break;
            }

            $seenProducts[$productId] = true;

            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();

                /*
                |------------------------------------------------------------------
                | TẠO MÃ PHIẾU XUẤT
                |------------------------------------------------------------------
                */
                $issueCode = 'PX-'
                    . date('YmdHis')
                    . '-'
                    . random_int(100, 999);

                /*
                |------------------------------------------------------------------
                | TẠO PHIẾU XUẤT CHA
                |------------------------------------------------------------------
                */
                $insertIssue = $pdo->prepare("
                    INSERT INTO stock_issues (
                        issue_code,
                        created_by,
                        issue_date,
                        recipient,
                        note
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $insertIssue->execute([
                    $issueCode,
                    $_SESSION['user_id'],
                    $issueDate,
                    $recipient,
                    $note
                ]);

                $issueId = (int) $pdo->lastInsertId();

                /*
                |------------------------------------------------------------------
                | CHUẨN BỊ INSERT CHI TIẾT
                |------------------------------------------------------------------
                */
                $insertItem = $pdo->prepare("
                    INSERT INTO stock_issue_items (
                        issue_id,
                        product_id,
                        quantity,
                        unit_price
                    )
                    VALUES (?, ?, ?, ?)
                ");

                /*
                |------------------------------------------------------------------
                | TRỪ TỒN KHO ATOMIC + LƯU CHI TIẾT
                |
                | WHERE quantity >= ? giúp tránh race condition:
                | hai người cùng xuất sẽ không thể làm tồn kho âm.
                |------------------------------------------------------------------
                */
                $updateStock = $pdo->prepare("
                    UPDATE products
                    SET quantity = quantity - ?
                    WHERE id = ?
                      AND quantity >= ?
                ");

                foreach ($items as $item) {
                    $updateStock->execute([
                        $item['quantity'],
                        $item['product_id'],
                        $item['quantity']
                    ]);

                    if ($updateStock->rowCount() !== 1) {
                        $productNameStmt = $pdo->prepare("
                            SELECT product_name, quantity
                            FROM products
                            WHERE id = ?
                        ");
                        $productNameStmt->execute([$item['product_id']]);
                        $productInfo = $productNameStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$productInfo) {
                            throw new Exception('Sản phẩm ID ' . $item['product_id'] . ' không tồn tại.');
                        }

                        throw new Exception(
                            'Không đủ hàng cho sản phẩm "'
                            . $productInfo['product_name']
                            . '". Tồn hiện tại: '
                            . (int) $productInfo['quantity']
                        );
                    }

                    $insertItem->execute([
                        $issueId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price']
                    ]);
                }

                /*
                |------------------------------------------------------------------
                | TẠO THÔNG BÁO
                |------------------------------------------------------------------
                */
                require_once __DIR__ . '/config/notifications.php';

                $stmtUser = $pdo->prepare("
                    SELECT full_name
                    FROM users
                    WHERE id = ?
                ");

                $stmtUser->execute([$_SESSION['user_id']]);
                $creatorName = $stmtUser->fetchColumn();

                if (!$creatorName) {
                    $creatorName = 'Người dùng';
                }

                createNotification(
                    $pdo,
                    'stock_out',
                    'Phiếu xuất kho mới',
                    $issueCode . ' — ' . $creatorName . ' vừa tạo phiếu xuất kho gồm ' . count($items) . ' sản phẩm.'
                );

                $pdo->commit();

                header('Location: stock_out.php?message=success');
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = $e->getMessage();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| THÔNG BÁO
|--------------------------------------------------------------------------
*/
if (isset($_GET['message']) && $_GET['message'] === 'success') {
    $message = 'Tạo phiếu xuất thành công. Tồn kho của tất cả sản phẩm đã được cập nhật.';
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

        INNER JOIN stock_issue_items
            ON stock_issues.id = stock_issue_items.issue_id

        INNER JOIN products
            ON stock_issue_items.product_id = products.id

        ORDER BY stock_issues.id DESC, stock_issue_items.id ASC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Xuất kho</title>

<style>
* { box-sizing: border-box; }

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

.header h1 { margin: 0; font-size: 23px; }
.header a { color: white; text-decoration: none; }

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
}

.box {
    min-width: 0;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
}

.box h2 { margin-top: 0; }

label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
}

input, select, textarea {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 90px;
    resize: vertical;
}

button {
    border: none;
    border-radius: 7px;
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.btn-add {
    width: 100%;
    margin: 4px 0 18px;
    padding: 11px;
    color: #1e40af;
    background: #dbeafe;
}

.btn-add:hover { background: #bfdbfe; }

.btn-save {
    width: 100%;
    padding: 13px;
    background: #dc2626;
    font-size: 15px;
}

.btn-save:hover { background: #b91c1c; }

.btn-remove {
    width: 38px;
    height: 38px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 18px;
}

.btn-remove:hover { background: #fecaca; }

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

.items-wrap {
    overflow-x: auto;
    margin-bottom: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.items-table {
    width: 100%;
    min-width: 500px;
    border-collapse: collapse;
}

.items-table th,
.items-table td {
    padding: 8px;
    vertical-align: middle;
    border-bottom: 1px solid #e2e8f0;
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

.items-table .col-no { width: 45px; text-align: center; }
.items-table .col-product { min-width: 220px; }
.items-table .col-qty { width: 100px; }
.items-table .col-price { width: 125px; }
.items-table .col-action { width: 55px; text-align: center; }

.stock-info {
    margin-top: 5px;
    padding: 7px 9px;
    color: #1e40af;
    background: #dbeafe;
    border-radius: 6px;
    font-size: 12px;
}

.stock-info.warning {
    color: #991b1b;
    background: #fee2e2;
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

table.history {
    width: max-content;
    min-width: 1050px;
    border-collapse: collapse;
}

.history th,
.history td {
    padding: 11px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

.history th { background: #f8fafc; }
.total { color: #dc2626; font-weight: bold; }
.empty { padding: 25px; text-align: center; color: #64748b; }

.form-summary {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin: 12px 0 18px;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 7px;
    font-size: 13px;
}

.form-summary strong { color: #dc2626; }

@media (max-width: 1100px) {
    .grid { grid-template-columns: 1fr; }
}

@media (max-width: 650px) {
    .header { padding: 18px 20px; }
    .container { padding: 0 12px; }
    .box { padding: 16px; }
}

html, body { max-width: 100%; overflow-x: hidden !important; }
</style>
</head>

<body>
<header class="header">
    <h1>XUẤT KHO</h1>
    <a href="index.php">← Dashboard</a>
</header>

<main class="container">

<a class="back" href="index.php">← Quay lại Dashboard</a>

<?php if ($message !== ''): ?>
<div class="success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid">

<div class="box">
    <h2>Tạo phiếu xuất</h2>

    <form method="POST" id="issueForm">

        <label>Ngày xuất *</label>
        <input
            type="date"
            name="issue_date"
            required
            value="<?= htmlspecialchars($_POST['issue_date'] ?? date('Y-m-d')) ?>"
        >

        <label>Người hoặc bộ phận nhận *</label>
        <input
            type="text"
            name="recipient"
            required
            value="<?= htmlspecialchars($_POST['recipient'] ?? '') ?>"
            placeholder="Ví dụ: Đội thi công số 1"
        >

        <label>Danh sách sản phẩm xuất *</label>

        <div class="items-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-no">STT</th>
                        <th class="col-product">Sản phẩm</th>
                        <th class="col-qty">Số lượng</th>
                        <th class="col-price">Đơn giá</th>
                        <th class="col-action">Xóa</th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <button type="button" class="btn-add" id="addItemBtn">
            + Thêm sản phẩm
        </button>

        <div class="form-summary">
            <span>Số mặt hàng: <strong id="itemCount">0</strong></span>
            <span>Tổng tiền: <strong id="grandTotal">0 đ</strong></span>
        </div>

        <label>Ghi chú</label>
        <textarea
            name="note"
            placeholder="Ví dụ: Xuất vật tư cho công trình..."
        ><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>

        <button type="submit" class="btn-save">
            Lưu phiếu xuất
        </button>

    </form>
</div>

<div class="box">
    <h2>Lịch sử xuất kho</h2>

    <?php if (count($issues) === 0): ?>
        <div class="empty">Chưa có phiếu xuất nào.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="history">
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
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['issue_code']) ?></td>
                        <td><?= htmlspecialchars($issue['issue_date']) ?></td>
                        <td>
                            <?= htmlspecialchars($issue['product_code']) ?>
                            -
                            <?= htmlspecialchars($issue['product_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($issue['recipient']) ?></td>
                        <td><?= number_format($issue['quantity']) ?></td>
                        <td><?= htmlspecialchars($issue['unit']) ?></td>
                        <td>
                            <?= number_format($issue['unit_price'], 0, ',', '.') ?>đ
                        </td>
                        <td class="total">
                            <?= number_format($issue['quantity'] * $issue['unit_price'], 0, ',', '.') ?>đ
                        </td>
                        <td><?= htmlspecialchars($issue['note'] ?? '') ?></td>
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
const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const body = document.getElementById('itemsBody');
const addItemBtn = document.getElementById('addItemBtn');
const itemCount = document.getElementById('itemCount');
const grandTotal = document.getElementById('grandTotal');

function formatMoney(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' đ';
}

function updateSummary() {
    const rows = body.querySelectorAll('.item-row');
    let total = 0;

    rows.forEach(row => {
        const qty = Number(row.querySelector('.quantity-input')?.value || 0);
        const price = Number(row.querySelector('.price-input')?.value || 0);
        total += qty * price;
    });

    itemCount.textContent = rows.length;
    grandTotal.textContent = formatMoney(total);

    rows.forEach((row, index) => {
        const no = row.querySelector('.row-no');
        if (no) no.textContent = index + 1;
    });
}

function updateRow(row) {
    const select = row.querySelector('.product-select');
    const quantity = row.querySelector('.quantity-input');
    const price = row.querySelector('.price-input');
    const stockInfo = row.querySelector('.stock-info');

    const option = select.options[select.selectedIndex];

    if (!option || !option.value) {
        stockInfo.textContent = 'Chọn sản phẩm để xem tồn kho.';
        stockInfo.classList.remove('warning');
        quantity.max = '';
        price.value = '';
        updateSummary();
        return;
    }

    const stock = Number(option.dataset.stock || 0);
    const unit = option.dataset.unit || '';
    const defaultPrice = option.dataset.price || '0';

    stockInfo.textContent = 'Tồn hiện tại: ' + stock.toLocaleString('vi-VN') + ' ' + unit;
    stockInfo.classList.remove('warning');
    quantity.max = String(stock);

    if (price.value === '') {
        price.value = defaultPrice;
    }

    if (Number(quantity.value || 0) > stock) {
        stockInfo.textContent = '⚠ Số lượng xuất vượt tồn kho. Tồn hiện tại: ' + stock.toLocaleString('vi-VN') + ' ' + unit;
        stockInfo.classList.add('warning');
    }

    updateSummary();
}

function buildProductOptions() {
    let html = '<option value="">-- Chọn sản phẩm --</option>';

    products.forEach(product => {
        html += '<option'
            + ' value="' + Number(product.id) + '"'
            + ' data-stock="' + Number(product.quantity) + '"'
            + ' data-unit="' + escapeHtml(product.unit || '') + '"'
            + ' data-price="' + Number(product.sale_price || 0) + '"'
            + '>'
            + escapeHtml(product.product_code || '')
            + ' - '
            + escapeHtml(product.product_name || '')
            + '</option>';
    });

    return html;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function addRow(selectedProduct = '', selectedQuantity = '', selectedPrice = '') {
    const row = document.createElement('tr');
    row.className = 'item-row';

    row.innerHTML = `
        <td class="col-no row-no"></td>
        <td class="col-product">
            <select name="product_id[]" class="product-select" required>
                ${buildProductOptions()}
            </select>
            <div class="stock-info">Chọn sản phẩm để xem tồn kho.</div>
        </td>
        <td class="col-qty">
            <input
                type="number"
                name="quantity[]"
                class="quantity-input"
                min="1"
                step="1"
                required
                value="${escapeHtml(selectedQuantity)}"
            >
        </td>
        <td class="col-price">
            <input
                type="number"
                name="unit_price[]"
                class="price-input"
                min="0"
                step="0.01"
                required
                value="${escapeHtml(selectedPrice)}"
            >
        </td>
        <td class="col-action">
            <button type="button" class="btn-remove" title="Xóa dòng">×</button>
        </td>
    `;

    body.appendChild(row);

    const select = row.querySelector('.product-select');
    const quantity = row.querySelector('.quantity-input');
    const remove = row.querySelector('.btn-remove');

    if (selectedProduct !== '') {
        select.value = String(selectedProduct);
    }

    select.addEventListener('change', () => updateRow(row));
    quantity.addEventListener('input', () => updateRow(row));

    remove.addEventListener('click', () => {
        const rows = body.querySelectorAll('.item-row');

        if (rows.length === 1) {
            select.value = '';
            quantity.value = '';
            row.querySelector('.price-input').value = '';
            updateRow(row);
            return;
        }

        row.remove();
        updateSummary();
    });

    updateRow(row);
}

addItemBtn.addEventListener('click', () => addRow());

/*
|--------------------------------------------------------------------------
| KHỞI TẠO 1 DÒNG
|--------------------------------------------------------------------------
*/
addRow();

/*
|--------------------------------------------------------------------------
| NẾU POST LỖI: KHÔI PHỤC CÁC DÒNG ĐÃ NHẬP
|--------------------------------------------------------------------------
*/
<?php if ($error !== '' && isset($_POST['product_id']) && is_array($_POST['product_id'])): ?>
body.innerHTML = '';
const oldProducts = <?= json_encode(array_values($_POST['product_id']), JSON_UNESCAPED_UNICODE) ?>;
const oldQuantities = <?= json_encode(array_values($_POST['quantity'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
const oldPrices = <?= json_encode(array_values($_POST['unit_price'] ?? []), JSON_UNESCAPED_UNICODE) ?>;

oldProducts.forEach((product, index) => {
    addRow(
        product,
        oldQuantities[index] ?? '',
        oldPrices[index] ?? ''
    );
});
<?php endif; ?>

/*
|--------------------------------------------------------------------------
| KIỂM TRA TRƯỚC KHI GỬI
|--------------------------------------------------------------------------
*/
document.getElementById('issueForm').addEventListener('submit', function (event) {
    const rows = body.querySelectorAll('.item-row');
    const selected = new Set();

    if (rows.length === 0) {
        event.preventDefault();
        alert('Vui lòng thêm ít nhất một sản phẩm.');
        return;
    }

    for (const row of rows) {
        const select = row.querySelector('.product-select');
        const quantity = row.querySelector('.quantity-input');
        const price = row.querySelector('.price-input');
        const productId = select.value;
        const qty = Number(quantity.value || 0);
        const max = Number(quantity.max || 0);

        if (!productId) {
            event.preventDefault();
            alert('Vui lòng chọn đầy đủ sản phẩm.');
            select.focus();
            return;
        }

        if (selected.has(productId)) {
            event.preventDefault();
            alert('Bạn đang chọn trùng sản phẩm trong cùng một phiếu.');
            select.focus();
            return;
        }

        selected.add(productId);

        if (qty <= 0) {
            event.preventDefault();
            alert('Số lượng xuất phải lớn hơn 0.');
            quantity.focus();
            return;
        }

        if (max > 0 && qty > max) {
            event.preventDefault();
            alert('Số lượng xuất vượt tồn kho của sản phẩm đã chọn.');
            quantity.focus();
            return;
        }

        if (Number(price.value || 0) < 0) {
            event.preventDefault();
            alert('Đơn giá không được âm.');
            price.focus();
            return;
        }
    }
});
</script>

</body>
</html>
