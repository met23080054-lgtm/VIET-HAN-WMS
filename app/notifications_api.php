<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $dbPath = __DIR__ . '/database/quan_ly_kho.sqlite';

    $pdo = new PDO(
        'sqlite:' . $dbPath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $userId = (int) $_SESSION['user_id'];

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

        $count = (int) $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'count' => $count
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY DANH SÁCH THÔNG BÁO
    |--------------------------------------------------------------------------
    */

    if ($action === 'list') {

        $stmt = $pdo->prepare("
            SELECT
                id,
                type,
                title,
                message,
                is_read,
                created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 30
        ");

        $stmt->execute([
            $userId
        ]);

        $notifications = $stmt->fetchAll();

        /*
        |----------------------------------------------------------------------
        | ĐÁNH DẤU TOÀN BỘ THÔNG BÁO CHƯA ĐỌC THÀNH ĐÃ ĐỌC
        |----------------------------------------------------------------------
        */

        $markRead = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        $markRead->execute([
            $userId
        ]);

        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Action không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}