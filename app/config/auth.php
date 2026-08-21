<?php
require_once __DIR__ . '/app.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| KIỂM TRA ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {

        header('Location: login.php');

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| KIỂM TRA ADMIN
|--------------------------------------------------------------------------
*/

function isAdmin(): bool
{
    return isset($_SESSION['role'])
        && mb_strtolower(
            trim($_SESSION['role']),
            'UTF-8'
        ) === 'admin';
}

/*
|--------------------------------------------------------------------------
| BẮT BUỘC LÀ ADMIN
|--------------------------------------------------------------------------
*/

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {

        http_response_code(403);

        echo '
        <!DOCTYPE html>
        <html lang="vi">

        <head>

            <meta charset="UTF-8">

            <title>Không có quyền truy cập</title>

            <style>

                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    font-family: Arial, sans-serif;
                    background: #f1f5f9;
                }

                .box {
                    width: 100%;
                    max-width: 550px;
                    padding: 45px;
                    text-align: center;
                    background: white;
                    border-radius: 18px;
                    box-shadow:
                        0 15px 45px
                        rgba(0,0,0,0.15);
                }

                h1 {
                    color: #a52a22;
                }

                p {
                    color: #475569;
                    font-size: 17px;
                    line-height: 1.7;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 14px 25px;
                    color: white;
                    text-decoration: none;
                    background: #1e3a5f;
                    border-radius: 9px;
                }

            </style>

        </head>

        <body>

            <div class="box">

                <h1>
                    Không có quyền truy cập
                </h1>

                <p>

                    Tài khoản của bạn là Nhân viên.

                    Bạn không có quyền thực hiện
                    thao tác quản trị này.

                </p>

                <a href="index.php">

                    Quay về Dashboard

                </a>

            </div>

        </body>

        </html>
        ';

        exit;
    }
}