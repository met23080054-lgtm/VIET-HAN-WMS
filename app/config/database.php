<?php

$dbPath = __DIR__ . '/../database/quan_ly_kho.sqlite';

try {

    $pdo = new PDO(
        'sqlite:' . $dbPath
    );

    // Báo lỗi SQL bằng Exception
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Trả dữ liệu dưới dạng mảng kết hợp
    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Bật kiểm tra khóa ngoại của SQLite
    $pdo->exec(
        'PRAGMA foreign_keys = ON;'
    );

    // Giảm lỗi khóa database khi có nhiều thao tác
    $pdo->exec(
        'PRAGMA busy_timeout = 5000;'
    );

} catch (PDOException $e) {

    die(
        'Lỗi kết nối cơ sở dữ liệu: '
        . $e->getMessage()
    );

}