<?php

function createNotification(
    PDO $pdo,
    string $type,
    string $title,
    string $message
): void {

    $users = $pdo
        ->query("
            SELECT id
            FROM users
        ")
        ->fetchAll(PDO::FETCH_COLUMN);

    if (empty($users)) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications
        (
            user_id,
            type,
            title,
            message,
            is_read,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            0,
            CURRENT_TIMESTAMP
        )
    ");

    foreach ($users as $userId) {
        $stmt->execute([
            (int) $userId,
            $type,
            $title,
            $message
        ]);
    }
}