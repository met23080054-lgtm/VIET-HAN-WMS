<?php

$productImportError =
    $_SESSION['product_import_error'] ?? '';

$productImportSuccess =
    $_SESSION['product_import_success'] ?? '';

$productImportRows =
    $_SESSION['product_import_rows'] ?? [];

$productImportPreview =
    isset($_GET['import_preview']);

$productImportValid = 0;
$productImportDuplicate = 0;
$productImportErrorCount = 0;

if (is_array($productImportRows)) {

    foreach ($productImportRows as $item) {

        $status =
            $item['status'] ?? '';

        if ($status === 'valid') {
            $productImportValid++;
        } elseif ($status === 'duplicate') {
            $productImportDuplicate++;
        } elseif ($status === 'error') {
            $productImportErrorCount++;
        }
    }
}

?>

<?php if ($productImportError !== ''): ?>

<div style="
    margin:0 0 20px;
    padding:12px 15px;
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
    border-radius:8px;
">

    <?= htmlspecialchars(
        $productImportError,
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</div>

<?php endif; ?>


<?php if ($productImportSuccess !== ''): ?>

<div style="
    margin:0 0 20px;
    padding:12px 15px;
    background:#dcfce7;
    color:#166534;
    border:1px solid #86efac;
    border-radius:8px;
">

    <?= htmlspecialchars(
        $productImportSuccess,
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</div>

<?php endif; ?>


<?php if ($isAdmin): ?>

<div style="
    margin:0 0 20px;
    padding:18px;
    background:#f8fafc;
    border:1px solid #dbeafe;
    border-radius:10px;
">

    <div style="
        font-size:18px;
        font-weight:bold;
        color:#1e3a5f;
        margin-bottom:8px;
    ">
        Thêm sản phẩm từ Excel
    </div>

    <div style="
        font-size:13px;
        color:#64748b;
        margin-bottom:14px;
    ">
        Hỗ trợ XLSX, XLS và CSV.
        Hệ thống sẽ kiểm tra dữ liệu trước khi nhập.
    </div>


    <form
        method="POST"
        action="import_products.php"
        enctype="multipart/form-data"
        style="
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:wrap;
        "
    >

        <input
            type="file"
            name="excel_file"
            accept=".xlsx,.xls,.csv"
            required
            style="
                flex:1;
                min-width:260px;
                margin:0;
            "
        >

        <button
            type="submit"
            style="
                width:auto;
                padding:10px 18px;
                background:#16a34a;
                border-radius:6px;
            "
        >
            Xem trước dữ liệu
        </button>

    </form>

</div>

<?php endif; ?>


<?php if ($productImportPreview && !empty($productImportRows)): ?>

<div style="
    margin:0 0 20px;
    padding:18px;
    background:#ffffff;
    border:2px solid #bfdbfe;
    border-radius:10px;
">

    <h3 style="
        margin:0 0 15px;
        color:#1e3a5f;
    ">
        Kết quả kiểm tra file Excel
    </h3>


    <div style="
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-bottom:15px;
    ">

        <span style="
            padding:8px 12px;
            background:#dcfce7;
            color:#166534;
            border-radius:6px;
        ">
            Hợp lệ:
            <?= $productImportValid ?>
        </span>


        <span style="
            padding:8px 12px;
            background:#fef3c7;
            color:#92400e;
            border-radius:6px;
        ">
            Trùng:
            <?= $productImportDuplicate ?>
        </span>


        <span style="
            padding:8px 12px;
            background:#fee2e2;
            color:#991b1b;
            border-radius:6px;
        ">
            Lỗi:
            <?= $productImportErrorCount ?>
        </span>

    </div>


    <div style="overflow-x:auto;">

        <table style="
            min-width:1100px;
            width:100%;
            border-collapse:collapse;
        ">

            <thead>

                <tr>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Dòng
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Mã sản phẩm
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Tên sản phẩm
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Danh mục
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Nhà cung cấp
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        ĐVT
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Giá nhập
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Giá bán
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Tồn
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Trạng thái
                    </th>

                    <th style="
                        padding:10px;
                        background:#1e3a5f;
                        color:white;
                    ">
                        Ghi chú
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($productImportRows as $item): ?>

                <?php
                $status =
                    $item['status'] ?? 'error';
                ?>

                <tr>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= (int)($item['row'] ?? 0) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['product_code']
                            ?? $item['code']
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['product_name']
                            ?? $item['name']
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['category_name']
                            ?? $item['category']
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['supplier_name']
                            ?? $item['supplier']
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['unit'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['import_price'] ?? 0,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['sale_price'] ?? 0,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                        <?= htmlspecialchars(
                            $item['quantity'] ?? 0,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">

                        <?php if ($status === 'valid'): ?>

                            <span style="
                                padding:5px 8px;
                                background:#dcfce7;
                                color:#166534;
                                border-radius:5px;
                            ">
                                Hợp lệ
                            </span>

                        <?php elseif ($status === 'duplicate'): ?>

                            <span style="
                                padding:5px 8px;
                                background:#fef3c7;
                                color:#92400e;
                                border-radius:5px;
                            ">
                                Trùng
                            </span>

                        <?php else: ?>

                            <span style="
                                padding:5px 8px;
                                background:#fee2e2;
                                color:#991b1b;
                                border-radius:5px;
                            ">
                                Lỗi
                            </span>

                        <?php endif; ?>

                    </td>

                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;">

                        <?= htmlspecialchars(
                            $item['message']
                            ?? $item['error']
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <?php if ($productImportValid > 0): ?>

    <div style="
        margin-top:16px;
        padding:14px;
        background:#f0fdf4;
        border:1px solid #86efac;
        border-radius:8px;
    ">

        <div style="
            margin-bottom:10px;
            color:#166534;
            font-weight:bold;
        ">
            Có <?= $productImportValid ?>
            sản phẩm hợp lệ, sẵn sàng nhập.
        </div>


        <form
            method="POST"
            action="import_products_commit.php"
            onsubmit="return confirm(
                'Bạn có chắc muốn nhập <?= $productImportValid ?> sản phẩm hợp lệ vào database?'
            );"
        >

            <button
                type="submit"
                style="
                    width:auto;
                    padding:10px 18px;
                    background:#16a34a;
                    color:white;
                    border:0;
                    border-radius:6px;
                    cursor:pointer;
                    font-weight:bold;
                "
            >
                Xác nhận nhập
                <?= $productImportValid ?>
                dòng hợp lệ
            </button>

        </form>

    </div>

    <?php endif; ?>

</div>

<?php endif; ?>