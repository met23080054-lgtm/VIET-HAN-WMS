<?php

require_once __DIR__ . '/config/auth.php';

requireLogin();

require_once __DIR__ . '/config/database.php';


/* =========================================================
   TRẢ KẾT QUẢ DẠNG JSON
========================================================= */

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate'
);


/* =========================================================
   NHẬN THAM SỐ LỌC
========================================================= */

$period = $_GET['period']
    ?? '30';


$allowedPeriods = [

    '7',

    '30',

    'month'

];


if (
    !in_array(
        $period,
        $allowedPeriods,
        true
    )
) {

    $period = '30';

}


/* =========================================================
   XÁC ĐỊNH NGÀY BẮT ĐẦU VÀ KẾT THÚC
========================================================= */

$today = new DateTime(
    'today'
);


if (
    $period === '7'
) {

    /*
    Bao gồm hôm nay.

    Ví dụ:
    Nếu hôm nay là 05/08
    thì hiển thị:
    30/07 đến 05/08.
    */

    $startDate = clone $today;

    $startDate->modify(
        '-6 days'
    );

    $endDate = clone $today;

}


elseif (
    $period === '30'
) {

    /*
    Bao gồm hôm nay.

    Hiển thị đúng 30 ngày.
    */

    $startDate = clone $today;

    $startDate->modify(
        '-29 days'
    );

    $endDate = clone $today;

}


else {

    /*
    THÁNG NÀY:

    Từ ngày 01
    đến ngày hiện tại.
    */

    $startDate = new DateTime(
        'first day of this month'
    );

    $endDate = clone $today;

}


/* =========================================================
   TẠO TOÀN BỘ MỐC NGÀY
========================================================= */

$labels = [];

$receipts = [];

$issues = [];


/*
Dùng mảng này để:

Ngày không có dữ liệu
vẫn hiển thị giá trị 0.
*/

$receiptData = [];

$issueData = [];


$currentDate = clone $startDate;


while (
    $currentDate <= $endDate
) {

    $dateKey =

        $currentDate->format(
            'Y-m-d'
        );


    $receiptData[
        $dateKey
    ] = 0;


    $issueData[
        $dateKey
    ] = 0;


    $currentDate->modify(
        '+1 day'
    );

}


/* =========================================================
   LẤY SỐ PHIẾU NHẬP THEO NGÀY
========================================================= */

$receiptStatement =

    $pdo->prepare(

        "
        SELECT

            date(
                receipt_date
            ) AS transaction_date,

            COUNT(*) AS total

        FROM stock_receipts

        WHERE date(
            receipt_date
        )

        BETWEEN ? AND ?

        GROUP BY

            date(
                receipt_date
            )

        ORDER BY

            transaction_date ASC
        "

    );


$receiptStatement->execute(

    [

        $startDate->format(
            'Y-m-d'
        ),

        $endDate->format(
            'Y-m-d'
        )

    ]

);


$receiptRows =

    $receiptStatement->fetchAll(

        PDO::FETCH_ASSOC

    );


foreach (
    $receiptRows
    as $row
) {

    $dateKey =

        $row[
            'transaction_date'
        ];


    if (
        array_key_exists(
            $dateKey,
            $receiptData
        )
    ) {

        $receiptData[
            $dateKey
        ] =

            (int)
            $row['total'];

    }

}


/* =========================================================
   LẤY SỐ PHIẾU XUẤT THEO NGÀY
========================================================= */

$issueStatement =

    $pdo->prepare(

        "
        SELECT

            date(
                issue_date
            ) AS transaction_date,

            COUNT(*) AS total

        FROM stock_issues

        WHERE date(
            issue_date
        )

        BETWEEN ? AND ?

        GROUP BY

            date(
                issue_date
            )

        ORDER BY

            transaction_date ASC
        "

    );


$issueStatement->execute(

    [

        $startDate->format(
            'Y-m-d'
        ),

        $endDate->format(
            'Y-m-d'
        )

    ]

);


$issueRows =

    $issueStatement->fetchAll(

        PDO::FETCH_ASSOC

    );


foreach (
    $issueRows
    as $row
) {

    $dateKey =

        $row[
            'transaction_date'
        ];


    if (
        array_key_exists(
            $dateKey,
            $issueData
        )
    ) {

        $issueData[
            $dateKey
        ] =

            (int)
            $row['total'];

    }

}


/* =========================================================
   ĐƯA DỮ LIỆU VÀO ĐÚNG THỨ TỰ NGÀY
========================================================= */

foreach (
    $receiptData
    as $dateKey => $total
) {

    $dateObject =

        new DateTime(
            $dateKey
        );


    /*
    Nhãn hiển thị:

    05/08
    */

    $labels[] =

        $dateObject->format(
            'd/m'
        );


    $receipts[] =

        (int)
        $total;


    $issues[] =

        (int)
        $issueData[
            $dateKey
        ];

}


/* =========================================================
   TRẢ JSON CHO JAVASCRIPT
========================================================= */

echo json_encode(

    [

        'success' => true,

        'period' => $period,

        'labels' => $labels,

        'receipts' => $receipts,

        'issues' => $issues

    ],

    JSON_UNESCAPED_UNICODE

);

exit;