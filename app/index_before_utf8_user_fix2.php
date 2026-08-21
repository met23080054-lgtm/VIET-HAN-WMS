<?php

require_once __DIR__ . '/config/auth.php';

requireLogin();

require_once __DIR__ . '/config/database.php';


/* =========================================================
   1. THỐNG KÊ KPI
========================================================= */

$totalProducts = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
    ")
    ->fetchColumn();


$totalCategories = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM categories
    ")
    ->fetchColumn();


$totalSuppliers = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM suppliers
    ")
    ->fetchColumn();


$totalStock = (int) $pdo
    ->query("
        SELECT COALESCE(
            SUM(quantity),
            0
        )
        FROM products
    ")
    ->fetchColumn();


$totalImportMonth = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM stock_receipts
        WHERE strftime(
            '%Y-%m',
            receipt_date
        ) = strftime(
            '%Y-%m',
            'now',
            'localtime'
        )
    ")
    ->fetchColumn();


$totalExportMonth = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM stock_issues
        WHERE strftime(
            '%Y-%m',
            issue_date
        ) = strftime(
            '%Y-%m',
            'now',
            'localtime'
        )
    ")
    ->fetchColumn();


/* =========================================================
   2. CẢNH BÁO TỒN KHO
========================================================= */

$lowStock = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE quantity > 0
        AND quantity <= 10
    ")
    ->fetchColumn();


$outOfStock = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE quantity <= 0
    ")
    ->fetchColumn();


/* =========================================================
   3. SẢN PHẨM GẦN ĐÂY
========================================================= */

$recentProducts = $pdo
    ->query("
        SELECT
            product_code,
            product_name,
            unit,
            quantity

        FROM products

        ORDER BY id DESC

        LIMIT 6
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   4. PHIẾU NHẬP GẦN ĐÂY
========================================================= */

$recentReceipts = $pdo
    ->query("
        SELECT
            receipt_code,
            receipt_date,
            note

        FROM stock_receipts

        ORDER BY id DESC

        LIMIT 5
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   5. PHIẾU XUẤT GẦN ĐÂY
========================================================= */

$recentIssues = $pdo
    ->query("
        SELECT
            issue_code,
            issue_date,
            note

        FROM stock_issues

        ORDER BY id DESC

        LIMIT 5
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   6. LỊCH SỬ THAO TÁC
========================================================= */

$logLimit = 5;


if (
    isset($_GET['show_logs'])
    &&
    $_GET['show_logs'] === '10'
) {

    $logLimit = 10;

}


$activities = $pdo
    ->query("
        SELECT

            activity_logs.action,

            activity_logs.created_at,

            COALESCE(
                users.full_name,
                'Hệ thống'
            ) AS full_name

        FROM activity_logs

        LEFT JOIN users

        ON activity_logs.user_id = users.id

        ORDER BY activity_logs.id DESC

        LIMIT $logLimit
    ")
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   7. THÔNG TIN NGƯỜI DÙNG
========================================================= */

$userName = $_SESSION['full_name']
    ?? 'Người dùng';


$userRole = $_SESSION['role']
    ?? 'Nhân viên';


$userInitial = mb_strtoupper(
    mb_substr(
        trim($userName),
        0,
        1
    )
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
Dashboard | VIỆT HÀN WMS
</title>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<style>

/* =========================================================
   1. BIẾN MÀU
========================================================= */

:root {

    --navy:
        #102b46;

    --navy-light:
        #1b456e;

    --orange:
        #f28c28;

    --orange-dark:
        #d96e08;

    --background:
        #f4f7fb;

    --white:
        #ffffff;

    --text:
        #182230;

    --muted:
        #667085;

    --border:
        #e4e7ec;

    --green:
        #15803d;

    --green-bg:
        #ecfdf3;

    --yellow:
        #b54708;

    --yellow-bg:
        #fff7ed;

    --red:
        #dc2626;

    --red-bg:
        #fef2f2;

    --blue:
        #2563eb;

    --blue-bg:
        #eff6ff;

    --shadow:

        0 8px 24px
        rgba(
            15,
            39,
            66,
            0.06
        );

}


/* =========================================================
   2. RESET
========================================================= */

* {

    box-sizing:
        border-box;

}


html {

    scroll-behavior:
        smooth;

}


body {

    margin:
        0;

    background:
        var(--background);

    color:
        var(--text);

    font-family:

        Inter,
        "Segoe UI",
        Arial,
        sans-serif;

}


/* =========================================================
   3. APP
========================================================= */

.app {

    min-height:
        100vh;

}


/* =========================================================
   4. SIDEBAR
========================================================= */

.sidebar {

    position:
        fixed;

    z-index:
        30;

    top:
        0;

    left:
        0;

    width:
        250px;

    height:
        100vh;

    padding:
        22px 14px;

    overflow-y:
        auto;

    background:
        var(--white);

    border-right:

        1px solid
        var(--border);

}


.brand {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        8px 12px 25px;

    border-bottom:

        1px solid
        var(--border);

}


.brand-mark {

    display:
        grid;

    place-items:
        center;

    width:
        44px;

    min-width:
        44px;

    height:
        44px;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            var(--navy),
            var(--navy-light)
        );

    border-radius:
        12px;

    font-size:
        18px;

    font-weight:
        900;

}


.brand-name {

    color:
        var(--navy);

    font-size:
        16px;

    font-weight:
        900;

}


.brand-subtitle {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        1.2px;

}


.nav {

    margin-top:
        22px;

}


.nav-label {

    margin:
        20px 0 8px;

    padding:
        0 12px;

    color:
        #98a2b3;

    font-size:
        10px;

    font-weight:
        900;

    letter-spacing:
        1px;

}


.nav a {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    margin:
        4px 0;

    padding:
        12px;

    color:
        #475467;

    border-radius:
        10px;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        700;

    transition:
        0.2s;

}


.nav a:hover {

    color:
        var(--navy);

    background:
        #f3f5f8;

}


.nav a.active {

    color:
        var(--navy);

    background:
        #fff4e8;

    box-shadow:

        inset 3px 0 0
        var(--orange);

}


.nav-icon {

    display:
        grid;

    place-items:
        center;

    width:
        21px;

}


.nav-icon svg {

    width:
        18px;

    height:
        18px;

}


.sidebar-bottom {

    margin-top:
        25px;

    padding:
        14px;

    color:
        var(--muted);

    background:
        #f8fafc;

    border:

        1px solid
        var(--border);

    border-radius:
        11px;

    font-size:
        11px;

}


.sidebar-bottom strong {

    display:
        block;

    margin-bottom:
        4px;

    color:
        var(--navy);

}


/* =========================================================
   5. MAIN
========================================================= */

.main {

    width:
        calc(
            100% - 250px
        );

    min-height:
        100vh;

    margin-left:
        250px;

}


/* =========================================================
   6. HEADER
========================================================= */

.topbar {

    position:
        sticky;

    z-index:
        20;

    top:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    min-height:
        76px;

    padding:
        12px 34px;

    background:

        rgba(
            255,
            255,
            255,
            0.94
        );

    backdrop-filter:
        blur(14px);

    border-bottom:

        1px solid
        var(--border);

}


.breadcrumb {

    color:
        var(--muted);

    font-size:
        11px;

}


.page-title {

    margin:
        4px 0 0;

    color:
        var(--navy);

    font-size:
        22px;

}


.topbar-right {

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

}


.notification {

    display:
        grid;

    place-items:
        center;

    width:
        39px;

    height:
        39px;

    color:
        var(--muted);

    background:
        #f8fafc;

    border:

        1px solid
        var(--border);

    border-radius:
        10px;

}

.notification-wrapper {
    position: relative;
}

.notification {
    position: relative;
    display: grid;
    place-items: center;

    width: 39px;
    height: 39px;

    padding: 0;

    color: var(--muted);
    background: #f8fafc;

    border: 1px solid var(--border);
    border-radius: 10px;

    cursor: pointer;

    transition: all 0.2s ease;
}

.notification:hover {
    color: var(--navy);
    background: #eef2f7;
}

.notification-bell {
    font-size: 20px;
    line-height: 1;
}

.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;

    min-width: 19px;
    height: 19px;

    padding: 0 5px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #dc2626;
    color: white;

    border: 2px solid white;
    border-radius: 999px;

    font-size: 10px;
    font-weight: 700;

    line-height: 1;
}

.notification-panel {
    position: absolute;

    top: calc(100% + 12px);
    right: 0;

    width: 380px;
    max-width: calc(100vw - 30px);

    background: white;

    border: 1px solid var(--border);
    border-radius: 14px;

    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.16);

    overflow: hidden;

    z-index: 9999;

    display: none;
}

.notification-panel.show {
    display: block;
}

.notification-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 15px 17px;

    border-bottom: 1px solid var(--border);

    background: #f8fafc;
}

.notification-panel-header strong {
    color: var(--navy);
    font-size: 15px;
}

.notification-panel-header span {
    margin-left: 6px;
    color: var(--muted);
    font-size: 12px;
}

.notification-close {
    border: 0;
    background: transparent;

    color: var(--muted);

    font-size: 24px;
    line-height: 1;

    cursor: pointer;

    padding: 0 4px;
}

.notification-list {
    max-height: 420px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 12px;

    padding: 14px 16px;

    border-bottom: 1px solid #f1f5f9;
}

.notification-item:hover {
    background: #f8fafc;
}

.notification-icon {
    flex: 0 0 36px;

    width: 36px;
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #f1f5f9;

    font-size: 18px;
}

.notification-content {
    min-width: 0;
}

.notification-title {
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;

    margin-bottom: 4px;
}

.notification-message {
    color: #475569;

    font-size: 12px;
    line-height: 1.45;
}

.notification-time {
    color: #94a3b8;

    font-size: 10px;

    margin-top: 5px;
}

.notification-empty {
    padding: 35px 20px;

    text-align: center;

    color: #94a3b8;

    font-size: 13px;
}

@media (max-width: 600px) {
    .notification-panel {
        position: fixed;

        top: 70px;
        left: 15px;
        right: 15px;

        width: auto;
        max-width: none;
    }
}

.profile {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

}


.avatar {

    display:
        grid;

    place-items:
        center;

    width:
        41px;

    height:
        41px;

    color:
        white;

    background:
        var(--navy);

    border:

        3px solid
        #fff4e8;

    border-radius:
        50%;

    font-size:
        14px;

    font-weight:
        900;

}


.profile-name {

    font-size:
        13px;

    font-weight:
        800;

}


.profile-role {

    margin-top:
        3px;

    color:
        var(--muted);

    font-size:
        10px;

}


.logout {

    padding:
        9px 13px;

    color:
        var(--red);

    background:
        white;

    border:

        1px solid
        #fecaca;

    border-radius:
        8px;

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        800;

}


/* =========================================================
   7. CONTENT
========================================================= */

.content {

    width:
        100%;

    max-width:
        1800px;

    margin:
        0 auto;

    padding:
        28px 32px 50px;

}


/* =========================================================
   8. HERO
========================================================= */

.hero {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        25px;

    width:
        100%;

    margin-bottom:
        20px;

    padding:
        27px 30px;

    color:
        white;

    background:

        linear-gradient(
            120deg,
            #102b46,
            #1d527f
        );

    border-radius:
        16px;

    box-shadow:

        0 16px 32px
        rgba(
            15,
            39,
            66,
            0.16
        );

}


.hero small {

    color:
        #cbd5e1;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        1px;

}


.hero h2 {

    margin:
        8px 0 0;

    font-size:
        24px;

}


.hero p {

    max-width:
        750px;

    margin:
        8px 0 0;

    color:
        #dce7f1;

    font-size:
        13px;

    line-height:
        1.6;

}


.hero-action {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    min-width:
        145px;

    padding:
        12px 17px;

    color:
        var(--navy);

    background:
        var(--orange);

    border-radius:
        9px;

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        900;

}


/* =========================================================
   9. CẢNH BÁO
========================================================= */

.warning {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    margin-bottom:
        20px;

    padding:
        14px 17px;

    color:
        #92400e;

    background:
        #fffbeb;

    border:

        1px solid
        #fde68a;

    border-radius:
        11px;

    font-size:
        12px;

}


.warning a {

    color:
        #b45309;

    font-weight:
        900;

    text-decoration:
        none;

}


/* =========================================================
   10. KPI - 6 CARD
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:

        repeat(
            6,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px;

    width:
        100%;

}


.stat-card {

    position:
        relative;

    min-width:
        0;

    overflow:
        hidden;

    padding:
        18px;

    background:
        white;

    border:

        1px solid
        var(--border);

    border-radius:
        14px;

    box-shadow:
        var(--shadow);

}


.stat-card::before {

    position:
        absolute;

    top:
        0;

    left:
        0;

    width:
        100%;

    height:
        4px;

    content:
        "";

    background:
        var(--blue);

}


.stat-card.orange::before {

    background:
        var(--orange);

}


.stat-card.green::before {

    background:
        var(--green);

}


.stat-card.red::before {

    background:
        var(--red);

}


.stat-top {

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        10px;

}


.stat-label {

    color:
        var(--muted);

    font-size:
        10px;

    font-weight:
        900;

    line-height:
        1.4;

}


.stat-icon {

    display:
        grid;

    place-items:
        center;

    width:
        38px;

    min-width:
        38px;

    height:
        38px;

    color:
        var(--blue);

    background:
        var(--blue-bg);

    border-radius:
        10px;

}


.stat-card.orange .stat-icon {

    color:
        var(--orange-dark);

    background:
        #fff4e8;

}


.stat-card.green .stat-icon {

    color:
        var(--green);

    background:
        var(--green-bg);

}


.stat-card.red .stat-icon {

    color:
        var(--red);

    background:
        var(--red-bg);

}


.stat-icon svg {

    width:
        20px;

    height:
        20px;

}


.stat-value {

    margin-top:
        16px;

    color:
        var(--navy);

    font-size:
        28px;

    font-weight:
        900;

}


.stat-detail {

    margin-top:
        8px;

    font-size:
        10px;

    font-weight:
        700;

}


.stat-detail.positive {

    color:
        var(--green);

}


.stat-detail.warning-text {

    color:
        var(--yellow);

}


.stat-detail.danger {

    color:
        var(--red);

}


/* =========================================================
   11. PANEL
========================================================= */

.panel {

    min-width:
        0;

    overflow:
        hidden;

    background:
        white;

    border:

        1px solid
        var(--border);

    border-radius:
        14px;

    box-shadow:
        var(--shadow);

}


.panel-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    min-height:
        68px;

    padding:
        16px 20px;

    border-bottom:

        1px solid
        var(--border);

}


.panel-title {

    margin:
        0;

    color:
        var(--navy);

    font-size:
        15px;

}


.panel-subtitle {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        10px;

}


.panel-link {

    color:
        var(--orange-dark);

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        900;

}


/* =========================================================
   12. DÒNG 3: BIỂU ĐỒ + ACTIVITY
========================================================= */

.dashboard-row-3 {

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            7fr
        )

        minmax(
            300px,
            3fr
        );

    gap:
        18px;

    width:
        100%;

    margin-top:
        18px;

}


.chart-panel {

    min-height:
        450px;

}


.chart-filter {

    padding:
        9px 12px;

    color:
        var(--navy);

    background:
        #f8fafc;

    border:

        1px solid
        var(--border);

    border-radius:
        8px;

    outline:
        none;

    font-size:
        11px;

    font-weight:
        700;

}


.chart-container {

    position:
        relative;

    width:
        100%;

    height:
        360px;

    padding:
        20px;

}


/* =========================================================
   13. ACTIVITY
========================================================= */

.activity-list {

    max-height:
        380px;

    overflow-y:
        auto;

}


.activity {

    position:
        relative;

    padding:
        16px 18px 16px 48px;

    border-bottom:

        1px solid
        #f0f2f5;

}


.activity::before {

    position:
        absolute;

    top:
        21px;

    left:
        20px;

    width:
        10px;

    height:
        10px;

    content:
        "";

    background:
        var(--orange);

    border-radius:
        50%;

}


.activity:last-child {

    border-bottom:
        none;

}


.activity-title {

    color:
        var(--navy);

    font-size:
        11px;

    font-weight:
        800;

    line-height:
        1.5;

}


.activity-time {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        10px;

}


/* =========================================================
   14. DÒNG 4: 2 CỘT 50/50
========================================================= */

.dashboard-row-4 {

    display:
        grid;

    grid-template-columns:
    minmax(
        0,
        3fr
    )
    minmax(
        300px,
        1fr
    );

    gap:
        18px;

    width:
        100%;

    margin-top:
        18px;

}


/* =========================================================
   15. TABLE
========================================================= */

.table-wrap {

    width:
        100%;

    overflow-x:
        auto;

}


table {

    width:
        100%;

    min-width:
        650px;

    border-collapse:
        collapse;

}


th {

    padding:
        12px 16px;

    color:
        var(--muted);

    background:
        #f9fafb;

    border-bottom:

        1px solid
        var(--border);

    text-align:
        left;

    font-size:
        9px;

    font-weight:
        900;

    letter-spacing:
        0.5px;

    text-transform:
        uppercase;

}


td {

    padding:
        14px 16px;

    border-bottom:

        1px solid
        #f0f2f5;

    font-size:
        11px;

}


tbody tr:hover {

    background:
        #fafcff;

}


.product-name {

    color:
        var(--navy);

    font-weight:
        800;

}


.code {

    color:
        var(--muted);

    font-size:
        10px;

}


.stock {

    color:
        var(--navy);

    font-weight:
        900;

}


/* =========================================================
   16. BADGE
========================================================= */

.status {

    display:
        inline-flex;

    align-items:
        center;

    padding:
        5px 9px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        900;

}


.status.good {

    color:
        var(--green);

    background:
        var(--green-bg);

}


.status.low {

    color:
        var(--yellow);

    background:
        var(--yellow-bg);

}


.status.out {

    color:
        var(--red);

    background:
        var(--red-bg);

}


.status.processing {

    color:
        var(--blue);

    background:
        var(--blue-bg);

}


/* =========================================================
   17. TAB NHẬP / XUẤT
========================================================= */

.tabs {

    display:
        flex;

    gap:
        7px;

    padding:
        12px 16px;

    border-bottom:

        1px solid
        var(--border);

}


.tab-button {

    padding:
        8px 12px;

    color:
        var(--muted);

    background:
        #f8fafc;

    border:

        1px solid
        var(--border);

    border-radius:
        8px;

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        900;

}


.tab-button.active {

    color:
        white;

    background:
        var(--navy);

    border-color:
        var(--navy);

}


.tab-content {

    display:
        none;

}


.tab-content.active {

    display:
        block;

}


.document-item {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        15px 17px;

    border-bottom:

        1px solid
        #f0f2f5;

}


.document-code {

    color:
        var(--navy);

    font-size:
        11px;

    font-weight:
        900;

}


.document-time {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        10px;

}


.empty {

    padding:
        30px;

    color:
        var(--muted);

    text-align:
        center;

    font-size:
        11px;

}


/* =========================================================
   18. RESPONSIVE
========================================================= */

@media (
    max-width:
    1450px
) {

    .stats {

        grid-template-columns:

            repeat(
                3,
                1fr
            );

    }

}


@media (
    max-width:
    1100px
) {

    .dashboard-row-3 {

        grid-template-columns:
            1fr;

    }

    .dashboard-row-4 {

        grid-template-columns:
            1fr;

    }

}


@media (
    max-width:
    900px
) {

    .sidebar {

        position:
            static;

        width:
            100%;

        height:
            auto;

    }

    .main {

        width:
            100%;

        margin-left:
            0;

    }

    .app {

        display:
            block;

    }

}


@media (
    max-width:
    700px
) {

    .stats {

        grid-template-columns:

            repeat(
                2,
                1fr
            );

    }

    .content {

        padding:
            18px 14px 35px;

    }

    .topbar {

        padding:
            12px 15px;

    }

    .profile-details {

        display:
            none;

    }

    .logout {

        display:
            none;

    }

    .hero {

        align-items:
            flex-start;

        flex-direction:
            column;

    }

}


@media (
    max-width:
    480px
) {

    .stats {

        grid-template-columns:
            1fr;

    }

    .topbar-right {

        gap:
            7px;

    }

    .chart-container {

        height:
            300px;

        padding:
            10px;

    }

}

</style>

</head>


<body>

<div class="app">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


<div class="brand">

<div class="brand-mark">

VH

</div>


<div>

<div class="brand-name">

VIỆT HÀN

</div>

<div class="brand-subtitle">

WAREHOUSE MANAGEMENT

</div>

</div>

</div>


<nav class="nav">


<div class="nav-label">

TỔNG QUAN

</div>


<a
    class="active"
    href="index.php"
>

<span class="nav-icon">

⌂

</span>

Dashboard

</a>


<div class="nav-label">

DỮ LIỆU KHO

</div>


<a href="categories.php">

<span class="nav-icon">

☷

</span>

Danh mục vật tư

</a>


<a href="products.php">

<span class="nav-icon">

▣

</span>

Sản phẩm

</a>


<a href="suppliers.php">

<span class="nav-icon">

♙

</span>

Nhà cung cấp

</a>


<div class="nav-label">

NGHIỆP VỤ

</div>


<a href="stock_in.php">

<span class="nav-icon">

↓ 

</span>

Nhập kho

</a>


<a href="stock_out.php">

<span class="nav-icon">

↑

</span>

Xuất kho

</a>


<a href="inventory_report.php">

<span class="nav-icon">

▥

</span>

Báo cáo tồn kho

</a>


</nav>


<div class="sidebar-bottom">

<strong>

VIỆT HÀN WMS

</strong>

Hệ thống quản lý vật tư

</div>


</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<section class="main">


<!-- HEADER -->

<header class="topbar">


<div>

<div class="breadcrumb">

Trang chủ / Tổng quan

</div>

<h1 class="page-title">

Dashboard

</h1>

</div>


<div class="topbar-right">


<div class="notification-wrapper">

    <button
        type="button"
        class="notification"
        id="notificationButton"
        aria-label="Thông báo"
    >
        <span class="notification-bell">&#128276;</span>

        <span
            class="notification-badge"
            id="notificationBadge"
            style="display:none;"
        >0</span>
    </button>

    <div
        class="notification-panel"
        id="notificationPanel"
    >

        <div class="notification-panel-header">

            <div>
                <strong>Thông báo</strong>
                <span id="notificationCountText"></span>
            </div>

            <button
                type="button"
                id="closeNotification"
                class="notification-close"
            >×</button>

        </div>

        <div
            class="notification-list"
            id="notificationList"
        >
            <div class="notification-empty">
                Đang tải thông báo...
            </div>
        </div>

    </div>

</div>


<style>
.profile-account {
    position: relative;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 0;
    background: transparent;
    padding: 4px 6px;
    cursor: pointer;
    color: inherit;
    font-family: inherit;
    border-radius: 10px;
}

.profile-trigger:hover {
    background: rgba(247,147,30,.08);
}

.profile-chevron {
    font-size: 10px;
    margin-left: 3px;
    transition: transform .2s ease;
}

.profile-account.open .profile-chevron {
    transform: rotate(180deg);
}

.profile-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 265px;
    background: #fff;
    border: 1px solid #e1e7ed;
    border-radius: 14px;
    box-shadow: 0 14px 40px rgba(15,47,77,.16);
    padding: 8px;
    z-index: 99999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);
    transition: opacity .18s ease,transform .18s ease,visibility .18s ease;
}

.profile-account.open .profile-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-menu-user {
    padding: 12px 13px 10px;
    border-bottom: 1px solid #edf1f5;
    margin-bottom: 6px;
}

.profile-menu-name {
    font-size: 14px;
    font-weight: 800;
    color: #173b5f;
}

.profile-menu-role {
    margin-top: 3px;
    font-size: 12px;
    color: #7b8794;
}

.profile-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border-radius: 9px;
    text-decoration: none;
    color: #26384a;
    font-size: 13px;
    font-weight: 700;
}

.profile-menu a:hover {
    background: #fff4e8;
    color: #f7931e;
}

.profile-menu-divider {
    height: 1px;
    background: #edf1f5;
    margin: 6px 4px;
}

.profile-menu a.logout-item {
    color: #c0392b;
}
</style>
<div class="profile">


<div class="avatar">

<?= htmlspecialchars(
    $userInitial
) ?>

</div>


<div class="profile-details">

<div class="profile-name">

<?= htmlspecialchars(
    $userName
) ?>

</div>


<div class="profile-role">

<?= htmlspecialchars(
    $userRole
) ?>

</div>

</div>


</div>


<a
    class="logout"
    href="logout.php"
>

Đăng xuất

</a>


</div>


</header>


<main class="content">


<!-- =====================================================
     DÒNG 1: BANNER
===================================================== -->

<section class="hero">


<div>

<small>

HỆ THỐNG QUẢN LÝ KHO

</small>


<h2>

Chào mừng trở lại,
<?= htmlspecialchars(
    $userName
) ?>

</h2>


<p>

Theo dõi tồn kho, quản lý nhập xuất và kiểm soát vật tư công trình trên cùng một hệ thống.

</p>

</div>


<a
    class="hero-action"
    href="stock_in.php"
>

+ Tạo phiếu nhập

</a>


</section>


<!-- CẢNH BÁO -->

<?php if (
    $lowStock > 0
    ||
    $outOfStock > 0
): ?>


<div class="warning">


<div>

Hiện có

<strong>

<?= number_format(
    $lowStock
) ?>

</strong>

sản phẩm sắp hết

và

<strong>

<?= number_format(
    $outOfStock
) ?>

</strong>

sản phẩm hết hàng.

</div>


<a href="inventory_report.php">

Xem chi tiết →

</a>


</div>


<?php endif; ?>


<!-- =====================================================
     DÒNG 2: 6 KPI
===================================================== -->

<section class="stats">


<!-- KPI 1 -->

<div class="stat-card">


<div class="stat-top">


<div class="stat-label">

TỔNG SẢN PHẨM

</div>


<div class="stat-icon">

📦

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalProducts
) ?>

</div>


<div class="stat-detail positive">

✓ Sản phẩm đang quản lý

</div>


</div>


<!-- KPI 2 -->

<div class="stat-card orange">


<div class="stat-top">


<div class="stat-label">

DANH MỤC VẬT TƯ

</div>


<div class="stat-icon">

☷

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalCategories
) ?>

</div>


<div class="stat-detail positive">

✓ Nhóm vật tư đang hoạt động

</div>


</div>


<!-- KPI 3 -->

<div class="stat-card green">


<div class="stat-top">


<div class="stat-label">

NHÀ CUNG CẤP

</div>


<div class="stat-icon">

🚚

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalSuppliers
) ?>

</div>


<div class="stat-detail positive">

+ Đối tác đang hợp tác

</div>


</div>


<!-- KPI 4 -->

<div class="stat-card orange">


<div class="stat-top">


<div class="stat-label">

TỔNG TỒN KHO

</div>


<div class="stat-icon">

🏭

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalStock
) ?>

</div>


<div class="stat-detail warning-text">

Theo dõi mức tồn tối thiểu

</div>


</div>


<!-- KPI 5 -->

<div class="stat-card green">


<div class="stat-top">


<div class="stat-label">

PHIẾU NHẬP THÁNG NÀY

</div>


<div class="stat-icon">

↓ 

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalImportMonth
) ?>

</div>


<div class="stat-detail positive">

+ Hoạt động nhập kho

</div>


</div>


<!-- KPI 6 -->

<div class="stat-card red">


<div class="stat-top">


<div class="stat-label">

PHIẾU XUẤT THÁNG NÀY

</div>


<div class="stat-icon">

↑

</div>


</div>


<div class="stat-value">

<?= number_format(
    $totalExportMonth
) ?>

</div>


<div class="stat-detail danger">

Theo dõi lượng xuất kho

</div>


</div>


</section>


<!-- =====================================================
     DÒNG 3: BIỂU ĐỒ + LỊCH SỬ
===================================================== -->

<section class="dashboard-row-3">


<!-- BIỂU ĐỒ -->

<div class="panel chart-panel">


<div class="panel-header">


<div>

<h2 class="panel-title">

Biểu đồ nhập - xuất kho

</h2>


<div class="panel-subtitle">

Thống kê số phiếu theo ngày

</div>

</div>


<select
    id="chartPeriod"
    class="chart-filter"
>

<option value="7">

7 ngày qua

</option>

<option
    value="30"
    selected
>

30 ngày qua

</option>

<option value="month">

Tháng này

</option>

</select>


</div>


<div class="chart-container">

<canvas
    id="stockChart"
></canvas>

</div>


</div>


<!-- LỊCH SỬ -->

<div class="panel">


<div class="panel-header">


<div>

<h2 class="panel-title">

Lịch sử thao tác

</h2>


<div class="panel-subtitle">

Hoạt động mới nhất

</div>

</div>


<a
    class="panel-link"
    href="index.php?show_logs=10"
>

Xem thêm →

</a>


</div>


<div class="activity-list">


<?php if (
    count($activities) === 0
): ?>


<div class="empty">

Chưa có lịch sử thao tác.

</div>


<?php endif; ?>


<?php foreach (
    $activities
    as $log
): ?>


<div class="activity">


<div class="activity-title">

<?= htmlspecialchars(
    $log['full_name']
) ?>

—

<?= htmlspecialchars(
    $log['action']
) ?>

</div>


<div class="activity-time">

<?= htmlspecialchars(
    $log['created_at']
) ?>

</div>


</div>


<?php endforeach; ?>


</div>


</div>


</section>


<!-- =====================================================
     DÒNG 4: SẢN PHẨM + PHIẾU
===================================================== -->

<section class="dashboard-row-4">


<!-- SẢN PHẨM -->

<div class="panel">


<div class="panel-header">


<h2 class="panel-title">

Sản phẩm gần đây

</h2>


<a
    class="panel-link"
    href="products.php"
>

Xem tất cả →

</a>


</div>


<div class="table-wrap">


<table>


<thead>


<tr>

<th>Mã</th>

<th>Tên vật tư</th>

<th>Đơn vị</th>

<th>Tồn</th>

<th>Trạng thái</th>

</tr>


</thead>


<tbody>


<?php if (
    count($recentProducts) === 0
): ?>


<tr>

<td
    colspan="5"
    class="empty"
>

Chưa có sản phẩm.

</td>

</tr>


<?php endif; ?>


<?php foreach (
    $recentProducts
    as $product
): ?>


<?php

$quantity =
    (int)
    $product['quantity'];


if (
    $quantity <= 0
) {

    $statusClass =
        'out';

    $statusText =
        'Hết hàng';

}

elseif (
    $quantity <= 10
) {

    $statusClass =
        'low';

    $statusText =
        'Sắp hết';

}

else {

    $statusClass =
        'good';

    $statusText =
        'Ổn định';

}

?>


<tr>


<td class="code">

<?= htmlspecialchars(
    $product['product_code']
) ?>

</td>


<td class="product-name">

<?= htmlspecialchars(
    $product['product_name']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $product['unit']
) ?>

</td>


<td class="stock">

<?= number_format(
    $quantity
) ?>

</td>


<td>

<span
    class="
    status
    <?= $statusClass ?>
    "
>

<?= $statusText ?>

</span>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


</div>


<!-- PHIẾU -->

<div class="panel">


<div class="panel-header">


<div>

<h2 class="panel-title">

Phiếu gần đây

</h2>


<div class="panel-subtitle">

Theo dõi nhập và xuất kho

</div>

</div>


</div>


<div class="tabs">


<button
    type="button"
    class="tab-button active"
    data-tab="receipt"
>

Phiếu nhập

</button>


<button
    type="button"
    class="tab-button"
    data-tab="issue"
>

Phiếu xuất

</button>


</div>


<!-- TAB NHẬP -->

<div
    id="receipt"
    class="tab-content active"
>


<?php if (
    count($recentReceipts) === 0
): ?>


<div class="empty">

Chưa có phiếu nhập.

</div>


<?php endif; ?>


<?php foreach (
    $recentReceipts
    as $receipt
): ?>


<div class="document-item">


<div>


<div class="document-code">

<?= htmlspecialchars(
    $receipt['receipt_code']
) ?>

</div>


<div class="document-time">

<?= htmlspecialchars(
    $receipt['receipt_date']
) ?>

</div>


</div>


<span class="status good">

Đã xử lý

</span>


</div>


<?php endforeach; ?>


</div>


<!-- TAB XUẤT -->

<div
    id="issue"
    class="tab-content"
>


<?php if (
    count($recentIssues) === 0
): ?>


<div class="empty">

Chưa có phiếu xuất.

</div>


<?php endif; ?>


<?php foreach (
    $recentIssues
    as $issue
): ?>


<div class="document-item">


<div>


<div class="document-code">

<?= htmlspecialchars(
    $issue['issue_code']
) ?>

</div>


<div class="document-time">

<?= htmlspecialchars(
    $issue['issue_date']
) ?>

</div>


</div>


<span class="status processing">

Đã xử lý

</span>


</div>


<?php endforeach; ?>


</div>


</div>


</section>


</main>


</section>


</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


/* =========================================================
   TAB PHIẾU
========================================================= */

const tabButtons =

    document.querySelectorAll(
        '.tab-button'
    );


const tabContents =

    document.querySelectorAll(
        '.tab-content'
    );


tabButtons.forEach(

    function(button) {


        button.addEventListener(

            'click',

            function() {


                const tabId =

                    button.dataset.tab;


                tabButtons.forEach(

                    function(item) {

                        item.classList.remove(
                            'active'
                        );

                    }

                );


                tabContents.forEach(

                    function(item) {

                        item.classList.remove(
                            'active'
                        );

                    }

                );


                button.classList.add(
                    'active'
                );


                document
                    .getElementById(
                        tabId
                    )
                    .classList.add(
                        'active'
                    );


            }

        );


    }

);


/* =========================================================
   CHART
========================================================= */

/* =========================================================
   BIỂU ĐỒ NHẬP - XUẤT KHO
========================================================= */

let stockChart = null;


/* =========================================================
   TẠO CẤU HÌNH ĐỘ RỘNG CỘT
========================================================= */

function getBarSettings(
    totalDays
) {

    /*
    7 ngày:

    Cột rộng hơn
    để lấp đầy biểu đồ.
    */

    if (
        totalDays <= 7
    ) {

        return {

            barThickness:
                'flex',

            maxBarThickness:
                35,

            categoryPercentage:
                0.88,

            barPercentage:
                0.82

        };

    }


    /*
    30 ngày:

    Cột nhỏ hơn
    để không bị chật.
    */

    return {

        barThickness:
            'flex',

        maxBarThickness:
            22,

        categoryPercentage:
            0.90,

        barPercentage:
            0.80

    };

}


/* =========================================================
   TẠO BIỂU ĐỒ
========================================================= */

function createStockChart(
    chartData
) {


    const canvas =

        document.getElementById(
            'stockChart'
        );


    const totalDays =

        chartData.labels.length;


    const barSettings =

        getBarSettings(
            totalDays
        );


    stockChart =

        new Chart(

            canvas,

            {

                type:
                    'bar',


                data: {

                    labels:
                        chartData.labels,


                    datasets: [

                        {

                            label:
                                'Phiếu nhập',


                            data:
                                chartData.receipts,


                            backgroundColor:
                                'rgba(21,128,61,.82)',


                            borderColor:
                                '#15803d',


                            borderWidth:
                                1,


                            borderRadius:
                                6,


                            borderSkipped:
                                false,


                            barThickness:
                                barSettings
                                .barThickness,


                            maxBarThickness:
                                barSettings
                                .maxBarThickness,


                            categoryPercentage:
                                barSettings
                                .categoryPercentage,


                            barPercentage:
                                barSettings
                                .barPercentage

                        },


                        {

                            label:
                                'Phiếu xuất',


                            data:
                                chartData.issues,


                            backgroundColor:
                                'rgba(242,140,40,.88)',


                            borderColor:
                                '#db7414',


                            borderWidth:
                                1,


                            borderRadius:
                                6,


                            borderSkipped:
                                false,


                            barThickness:
                                barSettings
                                .barThickness,


                            maxBarThickness:
                                barSettings
                                .maxBarThickness,


                            categoryPercentage:
                                barSettings
                                .categoryPercentage,


                            barPercentage:
                                barSettings
                                .barPercentage

                        }

                    ]

                },


                options: {

                    responsive:
                        true,


                    maintainAspectRatio:
                        false,


                    animation: {

                        duration:
                            650,


                        easing:
                            'easeOutQuart'

                    },


                    interaction: {

                        mode:
                            'index',


                        intersect:
                            false

                    },


                    plugins: {


                        legend: {

                            position:
                                'top',


                            align:
                                'end',


                            labels: {

                                usePointStyle:
                                    true,


                                pointStyle:
                                    'circle',


                                padding:
                                    18,


                                font: {

                                    size:
                                        11,


                                    weight:
                                        '600'

                                }

                            }

                        },


                        tooltip: {

                            backgroundColor:
                                '#102b46',


                            titleColor:
                                '#ffffff',


                            bodyColor:
                                '#ffffff',


                            padding:
                                12,


                            cornerRadius:
                                9,


                            callbacks: {


                                title:

                                function(
                                    context
                                ) {

                                    return (

                                        'Ngày '

                                        +

                                        context[0]
                                        .label

                                    );

                                },


                                label:

                                function(
                                    context
                                ) {

                                    return (

                                        context
                                        .dataset
                                        .label

                                        +

                                        ': '

                                        +

                                        context
                                        .parsed
                                        .y

                                        +

                                        ' phiếu'

                                    );

                                }

                            }

                        }

                    },


                    scales: {


                        x: {

                            offset:
                                true,


                            grid: {

                                display:
                                    false

                            },


                            ticks: {

                                color:
                                    '#667085',


                                maxRotation:
                                    0,


                                minRotation:
                                    0,


                                autoSkip:
                                    totalDays > 15,


                                maxTicksLimit:

                                    totalDays <= 7

                                    ?

                                    7

                                    :

                                    15

                            }

                        },


                        y: {

                            beginAtZero:
                                true,


                            ticks: {

                                precision:
                                    0,


                                color:
                                    '#667085',


                                stepSize:
                                    1

                            },


                            grid: {

                                color:

                                'rgba(148,163,184,.16)'

                            },


                            title: {

                                display:
                                    true,


                                text:
                                    'Số lượng phiếu',


                                color:
                                    '#667085',


                                font: {

                                    size:
                                        10

                                }

                            }

                        }

                    }

                }

            }

        );

}


/* =========================================================
   CẬP NHẬT DỮ LIỆU BIỂU ĐỒ
========================================================= */

function updateStockChart(
    chartData
) {


    const totalDays =

        chartData.labels.length;


    const barSettings =

        getBarSettings(
            totalDays
        );


    stockChart.data.labels =

        chartData.labels;


    stockChart.data.datasets[0].data =

        chartData.receipts;


    stockChart.data.datasets[1].data =

        chartData.issues;


    stockChart.data.datasets.forEach(

        function(
            dataset
        ) {


            dataset.maxBarThickness =

                barSettings
                .maxBarThickness;


            dataset.categoryPercentage =

                barSettings
                .categoryPercentage;


            dataset.barPercentage =

                barSettings
                .barPercentage;


        }

    );


    stockChart.options.scales.x
    .ticks.autoSkip =

        totalDays > 15;


    stockChart.options.scales.x
    .ticks.maxTicksLimit =

        totalDays <= 7

        ?

        7

        :

        15;


    /*
    update() tạo hiệu ứng
    chuyển đổi mượt.
    */

    stockChart.update();


}


/* =========================================================
   TẢI DỮ LIỆU AJAX
========================================================= */

async function loadStockChart() {


    const selectElement =

        document.getElementById(
            'chartPeriod'
        );


    const period =

        selectElement.value;


    /*
    Khóa Dropdown
    trong lúc tải.
    */

    selectElement.disabled = true;


    try {


        const response =

            await fetch(

                'chart_data.php?period='

                +

                encodeURIComponent(
                    period
                )

                +

                '&_='

                +

                Date.now(),

                {

                    cache:
                        'no-store'

                }

            );


        if (
            !response.ok
        ) {

            throw new Error(

                'Không thể tải dữ liệu biểu đồ.'

            );

        }


        const chartData =

            await response.json();


        if (
            !chartData.success
        ) {

            throw new Error(

                'Dữ liệu biểu đồ không hợp lệ.'

            );

        }


        if (
            stockChart === null
        ) {

            createStockChart(
                chartData
            );

        }

        else {

            updateStockChart(
                chartData
            );

        }


    }

    catch (
        error
    ) {


        console.error(
            error
        );


        alert(

            'Không thể tải dữ liệu biểu đồ. '
            +
            'Vui lòng kiểm tra file '
            +
            'chart_data.php.'

        );


    }

    finally {


        selectElement.disabled = false;


    }


}


/* =========================================================
   ĐỔI BỘ LỌC
========================================================= */

document
.getElementById(
    'chartPeriod'
)
.addEventListener(

    'change',

    function() {

        loadStockChart();

    }

);


/* =========================================================
   TẢI BIỂU ĐỒ LẦN ĐẦU
========================================================= */

loadStockChart();


/* =========================================================
   TỰ ĐỘNG CẬP NHẬT MỖI 30 GIÂY
========================================================= */

setInterval(

    loadStockChart,

    30000

);


</script>

<script src="notifications.js"></script>

<script>
(function () {

    const profile =
        document.querySelector('.profile');

    const logout =
        document.querySelector('a.logout');

    if (!profile || !logout) {
        return;
    }

    const account =
        document.createElement('div');

    account.className =
        'profile-account';

    account.id =
        'profileAccount';

    const trigger =
        document.createElement('button');

    trigger.type =
        'button';

    trigger.className =
        'profile-trigger';

    trigger.id =
        'profileTrigger';

    trigger.setAttribute(
        'aria-expanded',
        'false'
    );

    trigger.setAttribute(
        'aria-haspopup',
        'true'
    );

    const menu =
        document.createElement('div');

    menu.className =
        'profile-menu';

    menu.id =
        'profileMenu';

    const name =
        profile.querySelector(
            '.profile-name'
        )?.textContent.trim() || '';

    const role =
        profile.querySelector(
            '.profile-role'
        )?.textContent.trim() || '';

    menu.innerHTML =
        '<div class="profile-menu-user">' +
        '<div class="profile-menu-name">' +
        name +
        '</div>' +
        '<div class="profile-menu-role">' +
        role +
        '</div>' +
        '</div>' +

        '<a href="account.php?tab=profile" role="menuitem"><span>&#128100;</span><span>Th&#244;ng tin c&#225; nh&#226;n</span></a>' +

        '<a href="account.php?tab=password" role="menuitem"><span>&#128274;</span><span>&#272;&#7893;i m&#7853;t kh&#7849;u</span></a>' +

        '<a href="account.php?tab=avatar" role="menuitem"><span>&#128444;&#65039;</span><span>&#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n</span></a>' +

        '<div class="profile-menu-divider"></div>' +

        '<a class="logout-item" href="logout.php" role="menuitem"><span>&#8617;</span><span>&#272;&#259;ng xu&#7845;t</span></a>';

    profile.parentNode.insertBefore(
        account,
        profile
    );

    account.appendChild(
        trigger
    );

    trigger.appendChild(
        profile
    );

    const chevron =
        document.createElement('span');

    chevron.className =
        'profile-chevron';

    chevron.innerHTML = '&#9660;';

    trigger.appendChild(
        chevron
    );

    account.appendChild(
        menu
    );

    logout.remove();

    trigger.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            const open =
                account.classList.toggle(
                    'open'
                );

            trigger.setAttribute(
                'aria-expanded',
                open ? 'true' : 'false'
            );
        }
    );

    menu.addEventListener(
        'click',
        function (event) {
            event.stopPropagation();
        }
    );

    document.addEventListener(
        'click',
        function () {

            account.classList.remove(
                'open'
            );

            trigger.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    );

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                account.classList.remove(
                    'open'
                );

                trigger.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }
        }
    );

})();
</script>
</body>

</html>