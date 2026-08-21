<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

require_once __DIR__ . '/config/database.php';

if (
    isset($_SESSION['user_id'])
) {

    header(
        'Location: index.php'
    );

    exit;

}

$error = '';

$success = '';

if (
    isset(
        $_GET['register']
    )
    &&
    $_GET['register']
    ===
    'success'
) {

    $success =
        'Đăng ký tài khoản thành công. Vui lòng đăng nhập.';

}

$username = '';

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $username = trim(
        $_POST['username']
        ?? ''
    );

    $password =
        $_POST['password']
        ?? '';

    if (
        $username === ''
        ||
        $password === ''
    ) {

        $error =
            'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';

    } else {

        /*
        ------------------------------------------------
        Truy vấn tài khoản.

        Nếu bảng users của bạn dùng cột:
        username
        password

        thì giữ nguyên.

        Nếu dùng:
        password_hash

        hãy đổi users.password
        thành users.password_hash.
        ------------------------------------------------
        */

        $statement = $pdo->prepare(
    "
    SELECT
        users.id,
        users.username,
        users.full_name,
        users.password,

        roles.role_name
        AS role

    FROM users

    LEFT JOIN roles

    ON users.role_id
    =
    roles.id

    WHERE users.username = ?

    LIMIT 1
    "
);

        $statement->execute(
            [
                $username
            ]
        );

        $user =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        $loginSuccess = false;

        if (
            $user
        ) {

            /*
            Ưu tiên mật khẩu đã mã hóa.
            */

            if (
                password_verify(
                    $password,
                    $user['password']
                )
            ) {

                $loginSuccess = true;

            }

            /*
            Hỗ trợ dữ liệu demo cũ
            đang lưu mật khẩu dạng text.

            Sau khi hệ thống ổn định,
            nên xóa đoạn so sánh này.
            */

            if (
                $password
                ===
                $user['password']
            ) {

                $loginSuccess = true;

            }

        }

        if (
            $loginSuccess
        ) {

            session_regenerate_id(
                true
            );

            $_SESSION['user_id']
                =
                $user['id'];

            $_SESSION['username']
                =
                $user['username'];

            $_SESSION['full_name']
                =
                $user['full_name'];

            $_SESSION['role']
                =
                $user['role'];

            /*
            Ghi lịch sử đăng nhập.

            Nếu bảng activity_logs
            chưa có hoặc cấu trúc khác,
            hệ thống vẫn tiếp tục đăng nhập.
            */

            try {

                $logStatement =
                    $pdo->prepare(
                        "
                        INSERT INTO
                        activity_logs
                        (
                            user_id,
                            action,
                            created_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            datetime(
                                'now',
                                'localtime'
                            )
                        )
                        "
                    );

                $logStatement->execute(
                    [
                        $user['id'],
                        'Đăng nhập hệ thống'
                    ]
                );

            } catch (
                Throwable $exception
            ) {

                /*
                Không chặn đăng nhập
                nếu chưa có bảng log.
                */

            }

            header(
                'Location: index.php'
            );

            exit;

        }

        $error =
            'Tên đăng nhập hoặc mật khẩu không chính xác.';

    }

}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="color-scheme"
    content="light"
>

<title>
Đăng nhập | VIỆT HÀN WMS
</title>

<style>

/* =====================================================
   RESET
===================================================== */

* {

    box-sizing:
        border-box;

}

html {

    min-height:
        100%;

}

body {

    min-height:
        100vh;

    margin:
        0;

    color:
        #172033;

    background:
        #f5f7fb;

    font-family:

        Inter,
        "Segoe UI",
        Arial,
        sans-serif;

}

/* =====================================================
   LOGIN LAYOUT
===================================================== */

.login-page {

    display:
        grid;

    grid-template-columns:
        minmax(
            0,
            1fr
        )
        minmax(
            420px,
            1fr
        );

    min-height:
        100vh;

}

/* =====================================================
   LEFT BRAND
===================================================== */

.brand-side {

    position:
        relative;

    display:
        flex;

    overflow:
        hidden;

    align-items:
        center;

    min-height:
        100vh;

    padding:
        70px;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            #081d35 0%,
            #10385f 52%,
            #1a5a83 100%
        );

}

.brand-side::before {

    position:
        absolute;

    top:
        -180px;

    right:
        -160px;

    width:
        500px;

    height:
        500px;

    content:
        "";

    border:

        70px solid
        rgba(
            255,
            255,
            255,
            0.04
        );

    border-radius:
        50%;

}

.brand-side::after {

    position:
        absolute;

    bottom:
        -160px;

    left:
        -160px;

    width:
        420px;

    height:
        420px;

    content:
        "";

    background:

        rgba(
            242,
            140,
            40,
            0.10
        );

    border-radius:
        50%;

}

.brand-content {

    position:
        relative;

    z-index:
        2;

    width:
        100%;

    max-width:
        650px;

}

.brand-logo {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    margin-bottom:
        65px;

}

.logo-icon {

    display:
        grid;

    width:
        52px;

    height:
        52px;

    place-items:
        center;

    color:
        #102c49;

    background:
        #f28c28;

    border-radius:
        14px;

    font-size:
        19px;

    font-weight:
        900;

    box-shadow:

        0 12px 30px
        rgba(
            0,
            0,
            0,
            0.18
        );

}

.logo-name {

    font-size:
        19px;

    font-weight:
        900;

    letter-spacing:
        0.8px;

}

.logo-subtitle {

    margin-top:
        4px;

    color:
        #b8c9da;

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        1.7px;

}

.brand-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    padding:
        8px 13px;

    color:
        #ffd8ac;

    background:

        rgba(
            242,
            140,
            40,
            0.12
        );

    border:

        1px solid
        rgba(
            242,
            140,
            40,
            0.22
        );

    border-radius:
        30px;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        0.8px;

}

.brand-title {

    max-width:
        620px;

    margin:
        22px 0 17px;

    font-size:

        clamp(
            38px,
            4vw,
            62px
        );

    line-height:
        1.1;

}

.brand-title span {

    color:
        #f6a64e;

}

.brand-description {

    max-width:
        570px;

    color:
        #c3d2df;

    font-size:
        16px;

    line-height:
        1.8;

}

/* =====================================================
   WAREHOUSE VISUAL
===================================================== */

.warehouse-card {

    display:
        grid;

    grid-template-columns:

        1fr
        1fr
        1fr;

    gap:
        13px;

    max-width:
        610px;

    margin-top:
        48px;

}

.warehouse-item {

    min-height:
        120px;

    padding:
        20px;

    background:

        rgba(
            255,
            255,
            255,
            0.08
        );

    border:

        1px solid
        rgba(
            255,
            255,
            255,
            0.12
        );

    border-radius:
        17px;

    backdrop-filter:
        blur(10px);

}

.warehouse-icon {

    display:
        grid;

    width:
        40px;

    height:
        40px;

    place-items:
        center;

    color:
        #ffd5a5;

    background:

        rgba(
            242,
            140,
            40,
            0.14
        );

    border-radius:
        11px;

}

.warehouse-number {

    margin-top:
        17px;

    font-size:
        20px;

    font-weight:
        900;

}

.warehouse-label {

    margin-top:
        5px;

    color:
        #b9cad9;

    font-size:
        11px;

}

/* =====================================================
   RIGHT FORM
===================================================== */

.form-side {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    min-height:
        100vh;

    padding:
        45px;

    background:
        #ffffff;

}

.login-box {

    width:
        100%;

    max-width:
        450px;

}

.mobile-logo {

    display:
        none;

}

.form-eyebrow {

    color:
        #f28c28;

    font-size:
        11px;

    font-weight:
        900;

    letter-spacing:
        1.6px;

}

.form-title {

    margin:
        12px 0 9px;

    color:
        #102b47;

    font-size:
        34px;

}

.form-description {

    margin:
        0 0 31px;

    color:
        #667085;

    font-size:
        14px;

    line-height:
        1.7;

}

.form-group {

    margin-bottom:
        20px;

}

.form-label {

    display:
        block;

    margin-bottom:
        8px;

    color:
        #344054;

    font-size:
        12px;

    font-weight:
        800;

}

.input-wrap {

    position:
        relative;

}

.input-icon {

    position:
        absolute;

    top:
        50%;

    left:
        15px;

    width:
        20px;

    height:
        20px;

    color:
        #98a2b3;

    transform:
        translateY(
            -50%
        );

}

.form-input {

    width:
        100%;

    height:
        53px;

    padding:
        0 48px;

    color:
        #172033;

    background:
        #ffffff;

    border:

        1px solid
        #d8dee8;

    border-radius:
        9px;

    outline:
        none;

    font-size:
        14px;

    transition:
        0.2s;

}

.form-input:focus {

    border-color:
        #f28c28;

    box-shadow:

        0 0 0 4px
        rgba(
            242,
            140,
            40,
            0.12
        );

}

.password-toggle {

    position:
        absolute;

    top:
        50%;

    right:
        10px;

    display:
        grid;

    width:
        37px;

    height:
        37px;

    padding:
        0;

    place-items:
        center;

    color:
        #667085;

    background:
        transparent;

    border:
        none;

    border-radius:
        8px;

    cursor:
        pointer;

    transform:
        translateY(
            -50%
        );

}

.password-toggle:hover {

    color:
        #102b47;

    background:
        #f2f4f7;

}

/* =====================================================
   FORGOT PASSWORD
===================================================== */

.forgot-password-row {

    display:
        flex;

    justify-content:
        flex-end;

    margin-top:
        -7px;

    margin-bottom:
        22px;

}

.forgot-password-link {

    color:
        #1b5f8f;

    font-size:
        12px;

    font-weight:
        700;

    text-decoration:
        none;

    transition:
        color 0.2s,
        text-decoration-color 0.2s;

}

.forgot-password-link:hover {

    color:
        #f28c28;

    text-decoration:
        underline;

}

/* =====================================================
   LOGIN BUTTON
===================================================== */

.login-button {

    position:
        relative;

    width:
        100%;

    height:
        53px;

    margin-top:
        12px;

    overflow:
        hidden;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            #102b47,
            #1b4f77
        );

    border:
        none;

    border-radius:
        8px;

    cursor:
        pointer;

    font-size:
        14px;

    font-weight:
        800;

    transition:

        transform 0.2s,
        box-shadow 0.2s;

}

.login-button:hover {

    box-shadow:

        0 12px 24px
        rgba(
            16,
            43,
            71,
            0.22
        );

    transform:
        translateY(
            -2px
        );

}

.login-button:active {

    transform:
        translateY(
            0
        );

}

.ripple {

    position:
        absolute;

    width:
        10px;

    height:
        10px;

    background:

        rgba(
            255,
            255,
            255,
            0.45
        );

    border-radius:
        50%;

    pointer-events:
        none;

    transform:
        translate(
            -50%,
            -50%
        )
        scale(
            0
        );

    animation:

        ripple-effect
        0.65s
        linear;

}

@keyframes ripple-effect {

    to {

        opacity:
            0;

        transform:

            translate(
                -50%,
                -50%
            )

            scale(
                35
            );

    }

}

/* =====================================================
   ERROR
===================================================== */

.success-message {

    margin-bottom:
        20px;

    padding:
        13px 15px;

    color:
        #067647;

    background:
        #ecfdf3;

    border:

        1px solid
        #abefc6;

    border-radius:
        9px;

    font-size:
        12px;

}

.error-message {

    margin-bottom:
        20px;

    padding:
        13px 15px;

    color:
        #b42318;

    background:
        #fef3f2;

    border:

        1px solid
        #fecdca;

    border-radius:
        9px;

    font-size:
        12px;

}

/* =====================================================
   REGISTER LINK
===================================================== */

.register-link {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        5px;

    margin-top:
        20px;

    color:
        #667085;

    font-size:
        13px;

    line-height:
        1.5;

}

.register-link a {

    color:
        #1b4f77;

    font-weight:
        700;

    text-decoration:
        none;

    transition:

        color 0.2s ease,
        text-decoration-color 0.2s ease;

}

.register-link a:hover {

    color:
        #f28c28;

    text-decoration:
        underline;

}

.register-link a:focus {

    outline:
        none;

    text-decoration:
        underline;

}

/* =====================================================
   FOOTER
===================================================== */

.login-footer {

    margin-top:
        25px;

    color:
        #98a2b3;

    text-align:
        center;

    font-size:
        10px;

}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (
    max-width:
    1000px
) {

    .login-page {

        grid-template-columns:
            42% 58%;

    }

    .brand-side {

        padding:
            40px;

    }

    .warehouse-card {

        grid-template-columns:
            1fr;

    }

    .warehouse-item {

        min-height:
            auto;

    }

}

@media (
    max-width:
    760px
) {

    .login-page {

        display:
            block;

    }

    .brand-side {

        display:
            none;

    }

    .form-side {

        min-height:
            100vh;

        padding:
            28px 20px;

    }

    .mobile-logo {

        display:
            flex;

        align-items:
            center;

        gap:
            10px;

        margin-bottom:
            38px;

    }

    .mobile-logo-icon {

        display:
            grid;

        width:
            43px;

        height:
            43px;

        place-items:
            center;

        color:
            white;

        background:
            #102b47;

        border-radius:
            11px;

        font-weight:
            900;

    }

}

</style>

</head>

<body>

<div class="login-page">

<!-- =================================================
     LEFT
================================================== -->

<section class="brand-side">

<div class="brand-content">

<div class="brand-logo">

<div class="logo-icon">

VH

</div>

<div>

<div class="logo-name">

VIỆT HÀN

</div>

<div class="logo-subtitle">

WAREHOUSE MANAGEMENT SYSTEM

</div>

</div>

</div>

<div class="brand-badge">

● NỀN TẢNG QUẢN LÝ KHO THÔNG MINH

</div>

<h1 class="brand-title">

Kiểm soát vật tư.

<br>

Vận hành

<span>

hiệu quả.

</span>

</h1>

<p class="brand-description">

Quản lý tập trung sản phẩm, tồn kho, nhà cung cấp, phiếu nhập và phiếu xuất trên một nền tảng thống nhất.

</p>

<div class="warehouse-card">

<div class="warehouse-item">

<div class="warehouse-icon">

📦

</div>

<div class="warehouse-number">

Tồn kho

</div>

<div class="warehouse-label">

Theo dõi theo thời gian thực

</div>

</div>

<div class="warehouse-item">

<div class="warehouse-icon">

🚚

</div>

<div class="warehouse-number">

Nhập xuất

</div>

<div class="warehouse-label">

Kiểm soát luồng vật tư

</div>

</div>

<div class="warehouse-item">

<div class="warehouse-icon">

📊

</div>

<div class="warehouse-number">

Báo cáo

</div>

<div class="warehouse-label">

Hỗ trợ quyết định nhanh

</div>

</div>

</div>

</div>

</section>

<!-- =================================================
     RIGHT
================================================== -->

<section class="form-side">

<div class="login-box">

<div class="mobile-logo">

<div class="mobile-logo-icon">

VH

</div>

<div>

<strong>

VIỆT HÀN

</strong>

<br>

<small>

WAREHOUSE MANAGEMENT

</small>

</div>

</div>

<div class="form-eyebrow">

CHÀO MỪNG TRỞ LẠI

</div>

<h2 class="form-title">

Đăng nhập hệ thống

</h2>

<p class="form-description">

Nhập thông tin tài khoản để truy cập hệ thống quản lý kho VIỆT HÀN WMS.

</p>

<?php if (
    $success !== ''
): ?>

<div class="success-message">

<?= htmlspecialchars(
    $success
) ?>

</div>

<?php endif; ?>

<?php if (
    $error !== ''
): ?>

<div class="error-message">

<?= htmlspecialchars(
    $error
) ?>

</div>

<?php endif; ?>

<form
    method="POST"
    id="loginForm"
>

<div class="form-group">

<label
    class="form-label"
    for="username"
>

Tên đăng nhập

</label>

<div class="input-wrap">

<span class="input-icon">

<svg
    viewBox="0 0 24 24"
    fill="none"
>

<path
    d="M20 21a8 8 0 0 0-16 0"
    stroke="currentColor"
    stroke-width="1.8"
/>

<circle
    cx="12"
    cy="7"
    r="4"
    stroke="currentColor"
    stroke-width="1.8"
/>

</svg>

</span>

<input
    class="form-input"
    id="username"
    name="username"
    type="text"
    value="<?= htmlspecialchars($username) ?>"
    placeholder="Nhập tên đăng nhập"
    autocomplete="username"
    required
>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="password"
>

Mật khẩu

</label>

<div class="input-wrap">

<span class="input-icon">

<svg
    viewBox="0 0 24 24"
    fill="none"
>

<rect
    x="4"
    y="10"
    width="16"
    height="11"
    rx="2"
    stroke="currentColor"
    stroke-width="1.8"
/>

<path
    d="M8 10V7a4 4 0 0 1 8 0v3"
    stroke="currentColor"
    stroke-width="1.8"
/>

</svg>

</span>

<input
    class="form-input"
    id="password"
    name="password"
    type="password"
    placeholder="Nhập mật khẩu"
    autocomplete="current-password"
    required
>

<button
    class="password-toggle"
    type="button"
    id="passwordToggle"
    aria-label="Hiện hoặc ẩn mật khẩu"
>

<svg
    id="eyeIcon"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="none"
>

<path
    d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"
    stroke="currentColor"
    stroke-width="1.8"
/>

<circle
    cx="12"
    cy="12"
    r="2.5"
    stroke="currentColor"
    stroke-width="1.8"
/>

</svg>

</button>

</div>

</div>

<div class="forgot-password-row">

    <a
        href="forgot-password.php"
        class="forgot-password-link"
    >

        Quên mật khẩu?

    </a>

</div>

<button
    class="login-button"
    type="submit"
>

Đăng nhập vào hệ thống

</button>

<div class="register-link">

    <span>
        Chưa có tài khoản?
    </span>

    <a
        href="register.php"
    >
        Đăng ký ngay
    </a>

</div>

</form>

<div class="login-footer">

© <?= date('Y') ?>

VIỆT HÀN WMS

· Hệ thống quản lý vật tư

</div>

</div>

</section>

</div>

<script>

const passwordInput =
    document.getElementById(
        "password"
    );

const passwordToggle =
    document.getElementById(
        "passwordToggle"
    );



/* ================================================
   HIỆN / ẨN MẬT KHẨU
================================================ */

passwordToggle.addEventListener(
    "click",
    function () {

        const isPassword =

            passwordInput.type
            ===
            "password";

        passwordInput.type =

            isPassword
            ? "text"
            : "password";

    }
);



/* ================================================
   RIPPLE BUTTON
================================================ */

document
.querySelector(
    ".login-button"
)
.addEventListener(
    "click",
    function (
        event
    ) {

        const ripple =
            document.createElement(
                "span"
            );

        ripple.className =
            "ripple";

        const rect =
            this.getBoundingClientRect();

        ripple.style.left =

            (
                event.clientX
                -
                rect.left
            )
            +
            "px";

        ripple.style.top =

            (
                event.clientY
                -
                rect.top
            )
            +
            "px";

        this.appendChild(
            ripple
        );

        setTimeout(
            function () {

                ripple.remove();

            },
            700
        );

    }
);

</script>

</body>

</html>
