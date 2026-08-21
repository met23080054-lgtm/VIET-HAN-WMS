<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

require_once __DIR__ . '/config/database.php';

/* =====================================================
   NẾU ĐÃ ĐĂNG NHẬP
===================================================== */

if (
    isset(
        $_SESSION['user_id']
    )
) {

    header(
        'Location: index.php'
    );

    exit;

}

/* =====================================================
   MÃ BẢO MẬT NỘI BỘ

   Có thể thay đổi tại đây.
===================================================== */

const EMPLOYEE_SECURITY_CODE =
    'NV2026';

const ADMIN_SECURITY_CODE =
    'ADMIN2026';

/* =====================================================
   BIẾN MẶC ĐỊNH
===================================================== */

$error = '';

$fullName = '';

$username = '';

$email = '';

$selectedRole =
    'employee';

/* =====================================================
   XỬ LÝ ĐĂNG KÝ
===================================================== */

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $fullName = trim(
        $_POST['full_name']
        ?? ''
    );

    $username = trim(
        $_POST['username']
        ?? ''
    );

    $email = trim(
        $_POST['email']
        ?? ''
    );

    $password =
        $_POST['password']
        ?? '';

    $confirmPassword =
        $_POST['confirm_password']
        ?? '';

    $selectedRole =
        $_POST['role']
        ?? 'employee';

    $securityCode = trim(
        $_POST['security_code']
        ?? ''
    );

    /* =================================================
       KIỂM TRA DỮ LIỆU
    ================================================= */

    if (
        $fullName === ''
        ||
        $username === ''
        ||
        $password === ''
        ||
        $confirmPassword === ''
        ||
        $securityCode === ''
    ) {

        $error =
            'Vui lòng nhập đầy đủ các thông tin bắt buộc.';

    } elseif (
        mb_strlen(
            $fullName
        )
        < 2
    ) {

        $error =
            'Họ và tên phải có ít nhất 2 ký tự.';

    } elseif (
        mb_strlen(
            $username
        )
        < 3
    ) {

        $error =
            'Tên đăng nhập phải có ít nhất 3 ký tự.';

    } elseif (
        !preg_match(
            '/^[a-zA-Z0-9_.-]+$/',
            $username
        )
    ) {

        $error =
            'Tên đăng nhập chỉ được chứa chữ cái, số, dấu chấm, dấu gạch dưới hoặc dấu gạch ngang.';

    } elseif (
        $email !== ''
        &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Địa chỉ email không đúng định dạng.';

    } elseif (
        strlen(
            $password
        )
        < 6
    ) {

        $error =
            'Mật khẩu phải có ít nhất 6 ký tự.';

    } elseif (
        $password
        !==
        $confirmPassword
    ) {

        $error =
            'Xác nhận mật khẩu không khớp.';

    } elseif (
        !in_array(
            $selectedRole,
            [
                'employee',
                'admin'
            ],
            true
        )
    ) {

        $error =
            'Vai trò được chọn không hợp lệ.';

    } else {

        /* =============================================
           KIỂM TRA MÃ BẢO MẬT
        ============================================= */

        $securityCodeIsCorrect =
            false;

        if (
            $selectedRole
            ===
            'employee'
            &&
            hash_equals(
                EMPLOYEE_SECURITY_CODE,
                $securityCode
            )
        ) {

            $securityCodeIsCorrect =
                true;

        }

        if (
            $selectedRole
            ===
            'admin'
            &&
            hash_equals(
                ADMIN_SECURITY_CODE,
                $securityCode
            )
        ) {

            $securityCodeIsCorrect =
                true;

        }

        if (
            !$securityCodeIsCorrect
        ) {

            $error =
                'Mã bảo mật nội bộ không chính xác!';

        } else {

            /* =========================================
               KIỂM TRA TÊN ĐĂNG NHẬP
            ========================================= */

            $checkUser =
                $pdo->prepare(
                    "
                    SELECT
                        id
                    FROM users
                    WHERE username = ?
                    LIMIT 1
                    "
                );

            $checkUser->execute(
                [
                    $username
                ]
            );

            $existingUser =
                $checkUser->fetch();

            if (
                $existingUser
            ) {

                $error =
                    'Tên đăng nhập này đã tồn tại.';

            } else {

                /* =====================================
                   TÌM ROLE_ID

                   Hỗ trợ các tên:
                   admin
                   Admin

                   employee
                   Employee
                   Nhân viên
                ====================================== */

                if (
                    $selectedRole
                    ===
                    'admin'
                ) {

                    $roleStatement =
                        $pdo->prepare(
                            "
                            SELECT
                                id,
                                role_name
                            FROM roles
                            WHERE
                                LOWER(
                                    TRIM(
                                        role_name
                                    )
                                )
                                IN
                                (
                                    'admin',
                                    'administrator',
                                    'quản trị viên'
                                )
                            LIMIT 1
                            "
                        );

                } else {

                    $roleStatement =
                        $pdo->prepare(
                            "
                            SELECT
                                id,
                                role_name
                            FROM roles
                            WHERE
                                LOWER(
                                    TRIM(
                                        role_name
                                    )
                                )
                                IN
                                (
                                    'employee',
                                    'staff',
                                    'nhân viên'
                                )
                            LIMIT 1
                            "
                        );

                }

                $roleStatement->execute();

                $databaseRole =
                    $roleStatement->fetch();

                if (
                    !$databaseRole
                ) {

                    $error =
                        'Không tìm thấy vai trò phù hợp trong bảng roles.';

                } else {

                    /* =================================
                       MÃ HÓA MẬT KHẨU
                    ================================= */

                    $passwordHash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    /* =================================
                       LƯU TÀI KHOẢN
                    ================================= */

                    try {

                        $insertUser =
                            $pdo->prepare(
                                "
                                INSERT INTO users
                                (
                                    full_name,
                                    username,
                                    password,
                                    role_id,
                                    email,
                                    created_at
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    datetime(
                                        'now',
                                        'localtime'
                                    )
                                )
                                "
                            );

                        $insertUser->execute(
                            [
                                $fullName,
                                $username,
                                $passwordHash,
                                $databaseRole['id'],
                                $email !== ''
                                    ? $email
                                    : null
                            ]
                        );

                        /* =============================
                           CHUYỂN VỀ LOGIN
                        ============================== */

                        header(
                            'Location: login.php?register=success'
                        );

                        exit;

                    } catch (
                        PDOException $exception
                    ) {

                        $error =
                            'Không thể tạo tài khoản. Vui lòng kiểm tra lại database.';

                    }

                }

            }

        }

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
Đăng ký tài khoản | VIỆT HÀN WMS
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
   SPLIT SCREEN
===================================================== */

.register-page {

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        minmax(
            460px,
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
        65px;

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
        -180px;

    left:
        -160px;

    width:
        440px;

    height:
        440px;

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
        620px;

}

.brand-logo {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    margin-bottom:
        60px;

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
        1.5px;

}

.brand-badge {

    display:
        inline-flex;

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
            0.25
        );

    border-radius:
        30px;

    font-size:
        11px;

    font-weight:
        800;

}

.brand-title {

    margin:

        22px
        0
        17px;

    font-size:

        clamp(
            38px,
            4vw,
            58px
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
        560px;

    color:
        #c3d2df;

    font-size:
        16px;

    line-height:
        1.8;

}

.feature-list {

    display:
        grid;

    gap:
        14px;

    margin-top:
        42px;

}

.feature-item {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    padding:
        15px;

    background:

        rgba(
            255,
            255,
            255,
            0.07
        );

    border:

        1px solid

        rgba(
            255,
            255,
            255,
            0.10
        );

    border-radius:
        13px;

}

.feature-icon {

    display:
        grid;

    flex:
        0 0 auto;

    width:
        39px;

    height:
        39px;

    place-items:
        center;

    background:

        rgba(
            242,
            140,
            40,
            0.16
        );

    border-radius:
        10px;

}

.feature-text strong {

    display:
        block;

    font-size:
        12px;

}

.feature-text span {

    display:
        block;

    margin-top:
        4px;

    color:
        #b8c9da;

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

        40px
        45px;

    background:
        white;

}

.register-box {

    width:
        100%;

    max-width:
        470px;

}

.form-eyebrow {

    color:
        #f28c28;

    font-size:
        11px;

    font-weight:
        900;

    letter-spacing:
        1.5px;

}

.form-title {

    margin:

        10px
        0
        8px;

    color:
        #102b47;

    font-size:
        32px;

}

.form-description {

    margin:

        0
        0
        25px;

    color:
        #667085;

    font-size:
        13px;

    line-height:
        1.7;

}

.error-message {

    margin-bottom:
        18px;

    padding:

        13px
        15px;

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

.form-grid {

    display:
        grid;

    grid-template-columns:

        1fr
        1fr;

    gap:
        15px;

}

.form-group {

    margin-bottom:
        16px;

}

.form-group.full {

    grid-column:

        1
        /
        -1;

}

.form-label {

    display:
        block;

    margin-bottom:
        7px;

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
        14px;

    width:
        19px;

    height:
        19px;

    color:
        #98a2b3;

    transform:

        translateY(
            -50%
        );

}

.form-input,

.form-select {

    width:
        100%;

    height:
        50px;

    padding:

        0
        14px;

    color:
        #172033;

    background:
        white;

    border:

        1px solid
        #d8dee8;

    border-radius:
        9px;

    outline:
        none;

    font-size:
        13px;

    transition:
        0.2s;

}

.input-with-icon {

    padding-left:
        46px;

}

.form-input:focus,

.form-select:focus {

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
        8px;

    display:
        grid;

    width:
        36px;

    height:
        36px;

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

    background:
        #f2f4f7;

}

.security-note {

    margin:

        2px
        0
        18px;

    padding:
        12px;

    color:
        #5d4a2f;

    background:
        #fff8ed;

    border:

        1px solid
        #fed7aa;

    border-radius:
        9px;

    font-size:
        11px;

    line-height:
        1.6;

}

.register-button {

    position:
        relative;

    width:
        100%;

    height:
        52px;

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
        0.2s;

}

.register-button:hover {

    box-shadow:

        0 12px 25px

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

.login-link {

    margin-top:
        22px;

    color:
        #667085;

    text-align:
        center;

    font-size:
        12px;

}

.login-link a {

    color:
        #b95f09;

    font-weight:
        800;

    text-decoration:
        none;

}

.mobile-logo {

    display:
        none;

}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (
    max-width:
    1050px
) {

    .register-page {

        grid-template-columns:

            40%
            60%;

    }

    .brand-side {

        padding:
            40px;

    }

}

@media (
    max-width:
    760px
) {

    .register-page {

        display:
            block;

    }

    .brand-side {

        display:
            none;

    }

    .form-side {

        padding:

            30px
            20px;

    }

    .form-grid {

        grid-template-columns:

            1fr;

    }

    .form-group.full {

        grid-column:
            auto;

    }

    .mobile-logo {

        display:
            flex;

        align-items:
            center;

        gap:
            10px;

        margin-bottom:
            30px;

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

<div class="register-page">

<!-- =================================================
     CỘT TRÁI
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

TẠO TÀI KHOẢN HỆ THỐNG

</div>

<h1 class="brand-title">

Tham gia hệ thống.

<br>

Vận hành

<span>

thông minh.

</span>

</h1>

<p class="brand-description">

Tạo tài khoản để truy cập nền tảng quản lý kho VIỆT HÀN WMS và quản lý vật tư hiệu quả trên một hệ thống tập trung.

</p>

<div class="feature-list">

<div class="feature-item">

<div class="feature-icon">

📦

</div>

<div class="feature-text">

<strong>

Quản lý kho tập trung

</strong>

<span>

Theo dõi sản phẩm và tồn kho

</span>

</div>

</div>

<div class="feature-item">

<div class="feature-icon">

🔐

</div>

<div class="feature-text">

<strong>

Phân quyền tài khoản

</strong>

<span>

Kiểm soát quyền truy cập hệ thống

</span>

</div>

</div>

<div class="feature-item">

<div class="feature-icon">

📊

</div>

<div class="feature-text">

<strong>

Dữ liệu rõ ràng

</strong>

<span>

Hỗ trợ quản lý và ra quyết định

</span>

</div>

</div>

</div>

</div>

</section>

<!-- =================================================
     CỘT PHẢI
================================================== -->

<section class="form-side">

<div class="register-box">

<div class="mobile-logo">

<div class="mobile-logo-icon">

VH

</div>

<div>

<strong>

VIỆT HÀN WMS

</strong>

<br>

<small>

WAREHOUSE MANAGEMENT

</small>

</div>

</div>

<div class="form-eyebrow">

ĐĂNG KÝ TÀI KHOẢN

</div>

<h1 class="form-title">

Tạo tài khoản mới

</h1>

<p class="form-description">

Điền đầy đủ thông tin và sử dụng mã bảo mật nội bộ phù hợp với vai trò đăng ký.

</p>

<?php if (
    $error !== ''
): ?>

<div class="error-message">

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

<?php endif; ?>

<form
    method="POST"
    id="registerForm"
    autocomplete="off"
>

<div class="form-grid">

<div class="form-group full">

<label
    class="form-label"
    for="full_name"
>

Họ và tên *

</label>

<div class="input-wrap">

<span class="input-icon">

👤

</span>

<input
    class="form-input input-with-icon"
    id="full_name"
    name="full_name"
    type="text"
    value="<?= htmlspecialchars(
        $fullName,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    placeholder="Nhập họ và tên"
    required
>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="username"
>

Tên đăng nhập *

</label>

<div class="input-wrap">

<span class="input-icon">

👤

</span>

<input
    class="form-input input-with-icon"
    id="username"
    name="username"
    type="text"
    value="<?= htmlspecialchars(
        $username,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    placeholder="Ví dụ: nguyenvana"
    required
>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="email"
>

Email

</label>

<div class="input-wrap">

<span class="input-icon">

✉

</span>

<input
    class="form-input input-with-icon"
    id="email"
    name="email"
    type="email"
    value="<?= htmlspecialchars(
        $email,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    placeholder="email@example.com"
>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="password"
>

Mật khẩu *

</label>

<div class="input-wrap">

<input
    class="form-input"
    id="password"
    name="password"
    type="password"
    placeholder="Tối thiểu 6 ký tự"
    required
>

<button
    class="password-toggle"
    type="button"
    data-toggle="password"
>

👁

</button>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="confirm_password"
>

Xác nhận mật khẩu *

</label>

<div class="input-wrap">

<input
    class="form-input"
    id="confirm_password"
    name="confirm_password"
    type="password"
    placeholder="Nhập lại mật khẩu"
    required
>

<button
    class="password-toggle"
    type="button"
    data-toggle="confirm_password"
>

👁

</button>

</div>

</div>

<div class="form-group">

<label
    class="form-label"
    for="role"
>

Vai trò *

</label>

<select
    class="form-select"
    id="role"
    name="role"
    required
>

<option
    value="employee"
    <?= $selectedRole === 'employee'
        ? 'selected'
        : ''
    ?>
>

Nhân viên

</option>

<option
    value="admin"
    <?= $selectedRole === 'admin'
        ? 'selected'
        : ''
    ?>
>

Quản trị viên / Admin

</option>

</select>

</div>

<div class="form-group">

<label
    class="form-label"
    for="security_code"
>

Mã bảo mật nội bộ *

</label>

<div class="input-wrap">

<span class="input-icon">

🔐

</span>

<input
    class="form-input input-with-icon"
    id="security_code"
    name="security_code"
    type="password"
    placeholder="Nhập mã được cấp"
    required
>

</div>

</div>

</div>

<div class="security-note">

🔒 Mã bảo mật được kiểm tra theo vai trò đã chọn. Không chia sẻ mã nội bộ cho người không có quyền đăng ký.

</div>

<button
    class="register-button"
    type="submit"
>

Tạo tài khoản

</button>

</form>

<div class="login-link">

Đã có tài khoản?

<a href="login.php">

Đăng nhập ngay

</a>

</div>

</div>

</section>

</div>

<script>

/* =====================================================
   HIỆN / ẨN MẬT KHẨU
===================================================== */

document
.querySelectorAll(
    "[data-toggle]"
)
.forEach(
    function (
        button
    ) {

        button.addEventListener(
            "click",
            function () {

                const inputId =

                    button.getAttribute(
                        "data-toggle"
                    );

                const input =

                    document.getElementById(
                        inputId
                    );

                if (
                    input.type
                    ===
                    "password"
                ) {

                    input.type =
                        "text";

                    button.textContent =
                        "🙈";

                } else {

                    input.type =
                        "password";

                    button.textContent =
                        "👁";

                }

            }
        );

    }
);

</script>

</body>

</html>