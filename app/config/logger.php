<?php


function logActivity($pdo, $action)
{

    if (!isset($_SESSION['user_id'])) {
        return;
    }


    $stmt = $pdo->prepare("
        INSERT INTO activity_logs
        (
            user_id,
            action,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            datetime('now')
        )
    ");


    $stmt->execute([
        $_SESSION['user_id'],
        $action
    ]);

}