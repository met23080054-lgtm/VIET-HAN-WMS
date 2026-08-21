<?php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập.'
    ]);

    exit;
}

$action = $_GET['action'] ?? 'count';


/*
|--------------------------------------------------------------------------
| ĐẾM THÔNG BÁO CHƯA ĐỌC
|--------------------------------------------------------------------------
*/

if ($action === 'count') {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    $stmt->execute([
        $userId
    ]);

    echo json_encode([
        'success' => true,
        'count' => (int) $stmt->fetchColumn()
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LẤY THÔNG BÁO VÀ ĐÁNH DẤU ĐÃ ĐỌC
|--------------------------------------------------------------------------
*/

if ($action === 'list') {

    $stmt = $pdo->prepare("
        SELECT
            id,
            type,
            title,
            message,
            created_at
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
        ORDER BY id DESC
        LIMIT 20
    ");

    $stmt->execute([
        $userId
    ]);

    $notifications = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | ĐÁNH DẤU TOÀN BỘ THÔNG BÁO ĐANG HIỆN LÀ ĐÃ ĐỌC
    |--------------------------------------------------------------------------
    */

    if (!empty($notifications)) {

        $ids = array_column(
            $notifications,
            'id'
        );

        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($ids),
                    '?'
                )
            );

        $update = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
            AND id IN ($placeholders)
        ");

        $update->execute(
            array_merge(
                [$userId],
                $ids
            )
        );
    }


    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACTION KHÔNG HỢP LỆ
|--------------------------------------------------------------------------
*/

http_response_code(400);

echo json_encode([
    'success' => false,
    'message' => 'Action không hợp lệ.'
]);