<?php

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| TẠO CÁC BẢNG DATABASE
|--------------------------------------------------------------------------
*/

$pdo->exec("
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    address TEXT
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_code TEXT NOT NULL UNIQUE,
    product_name TEXT NOT NULL,
    category_id INTEGER,
    supplier_id INTEGER,
    unit TEXT NOT NULL,
    import_price REAL DEFAULT 0,
    sale_price REAL DEFAULT 0,
    quantity INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id),

    FOREIGN KEY (supplier_id)
        REFERENCES suppliers(id)
);

CREATE TABLE IF NOT EXISTS stock_receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_code TEXT NOT NULL UNIQUE,
    supplier_id INTEGER,
    created_by INTEGER,
    receipt_date DATE NOT NULL,
    note TEXT,

    FOREIGN KEY (supplier_id)
        REFERENCES suppliers(id),

    FOREIGN KEY (created_by)
        REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS stock_receipt_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL,

    FOREIGN KEY (receipt_id)
        REFERENCES stock_receipts(id),

    FOREIGN KEY (product_id)
        REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS stock_issues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    issue_code TEXT NOT NULL UNIQUE,
    created_by INTEGER,
    issue_date DATE NOT NULL,
    recipient TEXT,
    note TEXT,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS stock_issue_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    issue_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL,

    FOREIGN KEY (issue_id)
        REFERENCES stock_issues(id),

    FOREIGN KEY (product_id)
        REFERENCES products(id)
);
");

/*
|--------------------------------------------------------------------------
| TẠO DỮ LIỆU MẪU
|--------------------------------------------------------------------------
*/

$pdo->exec("
INSERT OR IGNORE INTO roles (id, role_name)
VALUES
(1, 'Admin'),
(2, 'Nhân viên');
");

$checkUser = $pdo->prepare(
    'SELECT COUNT(*) FROM users WHERE username = ?'
);

$checkUser->execute(['admin']);

if ((int) $checkUser->fetchColumn() === 0) {

    $passwordHash = password_hash(
        '123456',
        PASSWORD_DEFAULT
    );

    $insertUser = $pdo->prepare("
        INSERT INTO users (
            full_name,
            username,
            password,
            role_id
        )
        VALUES (?, ?, ?, ?)
    ");

    $insertUser->execute([
        'Quản trị viên',
        'admin',
        $passwordHash,
        1
    ]);
}

echo '<h2>Tạo database thành công!</h2>';
echo '<p>Tài khoản: <b>admin</b></p>';
echo '<p>Mật khẩu: <b>123456</b></p>';
