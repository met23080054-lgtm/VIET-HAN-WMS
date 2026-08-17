<?php

session_start();

require_once __DIR__
    . '/config/database.php';

require_once __DIR__
    . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;

$mailConfig =
    require __DIR__
    . '/config/mail.php';

/* =====================================================
   CẤU HÌNH MÃ BẢO MẬT NỘI BỘ
===================================================== */

const EMPLOYEE_SECURITY_CODE =
    'NV2026';

const ADMIN_SECURITY_CODE =
    'ADMIN2026';

/* =====================================================
   HÀM XÓA PHIÊN OTP
===================================================== */

function clearResetSession(): void
{

    unset(

        $_SESSION['reset_step'],

        $_SESSION['reset_user_id'],

        $_SESSION['reset_email'],

        $_SESSION['reset_role'],

        $_SESSION['reset_otp'],

        $_SESSION['reset_otp_expires']

    );

}

/* =====================================================
   BIẾN GIAO DIỆN
===================================================== */

$error = '';

$success = '';

$step = 1;

$email = '';

/* =====================================================
   MỞ TRANG BẰNG GET

   Khi truy cập trực tiếp:
   forgot-password.php

   hệ thống sẽ xóa OTP cũ
   và luôn bắt đầu từ BƯỚC 1.
===================================================== */

if (
    $_SERVER['REQUEST_METHOD']
    === 'GET'
) {

    clearResetSession();

}

/* =====================================================
   XỬ LÝ FORM POST
===================================================== */

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $action =
        $_POST['action']
        ?? '';

    /* =================================================
       BƯỚC 1
       KIỂM TRA EMAIL VÀ GỬI OTP
    ================================================= */

    if (
        $action
        === 'send_otp'
    ) {

        $step = 1;

        $email = trim(

            $_POST['email']
            ?? ''

        );

        if (
            $email === ''
        ) {

            $error =
                'Vui lòng nhập địa chỉ email.';

        } elseif (

            !filter_var(

                $email,

                FILTER_VALIDATE_EMAIL

            )

        ) {

            $error =
                'Địa chỉ email không hợp lệ.';

        } else {

            $statement =
                $pdo->prepare(

                    "
                    SELECT

                        users.id,

                        users.full_name,

                        users.email,

                        roles.role_name
                        AS role

                    FROM users

                    LEFT JOIN roles

                    ON users.role_id
                    =
                    roles.id

                    WHERE LOWER(
                        users.email
                    )
                    =
                    LOWER(?)

                    LIMIT 1
                    "

                );

            $statement->execute(

                [
                    $email
                ]

            );

            $user =
                $statement->fetch(

                    PDO::FETCH_ASSOC

                );

            if (
                !$user
            ) {

                $error =
                    'Không tìm thấy tài khoản '
                    .
                    'sử dụng email này.';

            } else {

                /*
                =========================================
                TẠO OTP 6 CHỮ SỐ
                =========================================
                */

                $otp =
                    (string) random_int(

                        100000,

                        999999

                    );

                /*
                =========================================
                GỬI EMAIL BẰNG PHPMAILER
                =========================================
                */

                try {

                    $mail =
                        new PHPMailer(
                            true
                        );

                    $mail->isSMTP();

                    $mail->Host =
                        'smtp.gmail.com';

                    $mail->SMTPAuth =
                        true;

                    $mail->Username =
                        $mailConfig['username'];

                    $mail->Password =
                        $mailConfig['password'];

                    $mail->SMTPSecure =
                        PHPMailer::ENCRYPTION_STARTTLS;

                    $mail->Port =
                        587;

                    $mail->CharSet =
                        'UTF-8';

                    $mail->setFrom(

                        $mailConfig['from_email'],

                        $mailConfig['from_name']

                    );

                    $mail->addAddress(

                        $user['email'],

                        $user['full_name']

                    );

                    $mail->isHTML(
                        true
                    );

                    $mail->Subject =
                        'Mã OTP đặt lại mật khẩu - VIỆT HÀN WMS';

                    $safeName =
                        htmlspecialchars(

                            $user['full_name']
                            ?: 'Người dùng',

                            ENT_QUOTES,

                            'UTF-8'

                        );

                    $safeOtp =
                        htmlspecialchars(

                            $otp,

                            ENT_QUOTES,

                            'UTF-8'

                        );

                    $mail->Body =

                        '
                        <!DOCTYPE html>

                        <html lang="vi">

                        <head>

                        <meta charset="UTF-8">

                        </head>

                        <body
                            style="
                                margin:0;
                                padding:0;
                                background:#f4f7fb;
                                font-family:
                                Arial,
                                sans-serif;
                            "
                        >

                        <div
                            style="
                                max-width:600px;
                                margin:30px auto;
                                background:#ffffff;
                                border-radius:16px;
                                overflow:hidden;
                                box-shadow:
                                0 10px 35px
                                rgba(
                                    16,
                                    43,
                                    71,
                                    0.12
                                );
                            "
                        >

                        <div
                            style="
                                padding:28px;
                                color:#ffffff;
                                text-align:center;
                                background:
                                linear-gradient(
                                    135deg,
                                    #102b47,
                                    #1b5f8f
                                );
                            "
                        >

                        <div
                            style="
                                display:inline-block;
                                padding:12px 16px;
                                color:#102b47;
                                background:#f28c28;
                                border-radius:12px;
                                font-size:20px;
                                font-weight:900;
                            "
                        >

                        VH

                        </div>

                        <h1
                            style="
                                margin:
                                18px 0 5px;
                                font-size:24px;
                            "
                        >

                        VIỆT HÀN WMS

                        </h1>

                        <p
                            style="
                                margin:0;
                                color:#d6e4ef;
                                font-size:12px;
                                letter-spacing:1px;
                            "
                        >

                        WAREHOUSE MANAGEMENT SYSTEM

                        </p>

                        </div>

                        <div
                            style="
                                padding:35px;
                                color:#344054;
                            "
                        >

                        <h2
                            style="
                                margin-top:0;
                                color:#102b47;
                            "
                        >

                        Xác nhận đặt lại mật khẩu

                        </h2>

                        <p>

                        Xin chào
                        <strong>'
                        .
                        $safeName
                        .
                        '</strong>,

                        </p>

                        <p
                            style="
                                line-height:1.7;
                            "
                        >

                        Bạn đã yêu cầu đặt lại mật khẩu
                        cho tài khoản VIỆT HÀN WMS.

                        </p>

                        <div
                            style="
                                margin:28px 0;
                                padding:22px;
                                text-align:center;
                                background:#fff7ed;
                                border:
                                1px solid
                                #fed7aa;
                                border-radius:12px;
                            "
                        >

                        <div
                            style="
                                margin-bottom:8px;
                                color:#9a4d08;
                                font-size:12px;
                                font-weight:800;
                            "
                        >

                        MÃ OTP CỦA BẠN

                        </div>

                        <div
                            style="
                                color:#102b47;
                                font-size:34px;
                                font-weight:900;
                                letter-spacing:9px;
                            "
                        >'

                        .
                        $safeOtp

                        .

                        '</div>

                        </div>

                        <p
                            style="
                                color:#667085;
                                line-height:1.7;
                            "
                        >

                        Mã có hiệu lực trong
                        <strong>
                        10 phút
                        </strong>.

                        Không chia sẻ mã này
                        cho bất kỳ ai.

                        </p>

                        <p
                            style="
                                color:#98a2b3;
                                font-size:11px;
                                line-height:1.6;
                            "
                        >

                        Nếu bạn không yêu cầu
                        đặt lại mật khẩu,
                        vui lòng bỏ qua email này.

                        </p>

                        </div>

                        <div
                            style="
                                padding:18px;
                                color:#98a2b3;
                                background:#f8fafc;
                                text-align:center;
                                font-size:10px;
                            "
                        >

                        © '
                        .
                        date('Y')
                        .
                        '
                        VIỆT HÀN WMS

                        </div>

                        </div>

                        </body>

                        </html>
                        ';

                    $mail->AltBody =

                        'Mã OTP đặt lại mật khẩu '
                        .
                        'VIỆT HÀN WMS của bạn là: '
                        .
                        $otp
                        .
                        '. Mã có hiệu lực trong 10 phút.';

                    $mail->send();

                    /*
                    =====================================
                    CHỈ LƯU SESSION
                    SAU KHI GỬI MAIL THÀNH CÔNG
                    =====================================
                    */

                    session_regenerate_id(
                        true
                    );

                    $_SESSION['reset_step']
                        = 2;

                    $_SESSION['reset_user_id']
                        = $user['id'];

                    $_SESSION['reset_email']
                        = $user['email'];

                    $_SESSION['reset_role']
                        = $user['role'];

                    $_SESSION['reset_otp']
                        = $otp;

                    $_SESSION['reset_otp_expires']
                        = time() + 600;

                    $step = 2;

                    $email =
                        $user['email'];

                    $success =
                        'Mã OTP đã được gửi đến email của bạn. '
                        .
                        'Vui lòng kiểm tra hộp thư '
                        .
                        '(bao gồm cả thư rác/Spam).';

                } catch (
                    Exception $exception
                ) {

                    clearResetSession();

                    $step = 1;

                    $error =
                        'Không thể gửi mã OTP. '
                        .
                        'Vui lòng kiểm tra cấu hình Gmail '
                        .
                        'và App Password.';

                    /*
                    Khi cần xem lỗi SMTP chi tiết,
                    có thể tạm dùng:

                    $error =
                    'Lỗi gửi email: '
                    .
                    $mail->ErrorInfo;
                    */

                }

            }

        }

    }

    /* =================================================
       BƯỚC 2
       KIỂM TRA OTP VÀ ĐỔI MẬT KHẨU
    ================================================= */

    elseif (
        $action
        === 'reset_password'
    ) {

        /*
        ================================================
        KIỂM TRA SESSION TRƯỚC

        Nếu chưa gửi OTP,
        quay về BƯỚC 1.
        ================================================
        */

        if (

            !isset(
                $_SESSION['reset_step']
            )

            ||

            (int)
            $_SESSION['reset_step']
            !== 2

            ||

            !isset(
                $_SESSION['reset_user_id']
            )

            ||

            !isset(
                $_SESSION['reset_email']
            )

            ||

            !isset(
                $_SESSION['reset_otp']
            )

            ||

            !isset(
                $_SESSION['reset_otp_expires']
            )

        ) {

            clearResetSession();

            $step = 1;

            $error =
                'Bạn chưa gửi mã OTP. '
                .
                'Vui lòng nhập email để bắt đầu.';

        } else {

            $step = 2;

            $email =
                $_SESSION['reset_email'];

            $otp = trim(

                $_POST['otp']
                ?? ''

            );

            $newPassword =

                $_POST['new_password']
                ?? '';

            $confirmPassword =

                $_POST['confirm_password']
                ?? '';

            $securityCode = trim(

                $_POST['security_code']
                ?? ''

            );

            if (

                $otp === ''

                ||

                $newPassword === ''

                ||

                $confirmPassword === ''

                ||

                $securityCode === ''

            ) {

                $error =
                    'Vui lòng nhập đầy đủ thông tin.';

            } elseif (

                !preg_match(

                    '/^[0-9]{6}$/',

                    $otp

                )

            ) {

                $error =
                    'Mã OTP phải gồm đúng 6 chữ số.';

            } elseif (

                time()

                >

                (int)
                $_SESSION['reset_otp_expires']

            ) {

                clearResetSession();

                $step = 1;

                $error =
                    'Mã OTP đã hết hạn. '
                    .
                    'Vui lòng gửi lại mã mới.';

            } elseif (

                !hash_equals(

                    (string)
                    $_SESSION['reset_otp'],

                    $otp

                )

            ) {

                $error =
                    'Mã OTP không chính xác.';

            } elseif (

                strlen(
                    $newPassword
                )
                < 6

            ) {

                $error =
                    'Mật khẩu mới phải có '
                    .
                    'ít nhất 6 ký tự.';

            } elseif (

                $newPassword
                !==
                $confirmPassword

            ) {

                $error =
                    'Xác nhận mật khẩu '
                    .
                    'không khớp.';

            } else {

                $role = mb_strtolower(

                    trim(

                        (string)
                        (
                            $_SESSION['reset_role']
                            ?? ''
                        )

                    )

                );

                $validSecurityCode =
                    false;

                if (

                    $role === 'admin'

                    ||

                    $role === 'administrator'

                    ||

                    $role === 'quản trị viên'

                ) {

                    $validSecurityCode =

                        hash_equals(

                            ADMIN_SECURITY_CODE,

                            $securityCode

                        );

                } elseif (

                    $role === 'employee'

                    ||

                    $role === 'staff'

                    ||

                    $role === 'nhân viên'

                ) {

                    $validSecurityCode =

                        hash_equals(

                            EMPLOYEE_SECURITY_CODE,

                            $securityCode

                        );

                }

                if (
                    !$validSecurityCode
                ) {

                    $error =
                        'Mã bảo mật nội bộ '
                        .
                        'không chính xác '
                        .
                        'hoặc không phù hợp '
                        .
                        'với vai trò tài khoản.';

                } else {

                    $newPasswordHash =

                        password_hash(

                            $newPassword,

                            PASSWORD_DEFAULT

                        );

                    $updateStatement =

                        $pdo->prepare(

                            "
                            UPDATE users

                            SET password = ?

                            WHERE id = ?
                            "

                        );

                    $updateStatement->execute(

                        [

                            $newPasswordHash,

                            $_SESSION[
                                'reset_user_id'
                            ]

                        ]

                    );

                    clearResetSession();

                    $_SESSION[
                        'login_success'
                    ] =

                        'Đặt lại mật khẩu thành công. '
                        .
                        'Bạn có thể đăng nhập '
                        .
                        'bằng mật khẩu mới.';

                    header(

                        'Location: login.php'

                    );

                    exit;

                }

            }

        }

    }

}

/* =====================================================
   KIỂM TRA TRẠNG THÁI BƯỚC 2

   Chỉ cho phép Bước 2 khi:
   - Có reset_step = 2
   - Có email
   - Có OTP
   - OTP chưa hết hạn
===================================================== */

if (

    $_SERVER['REQUEST_METHOD']
    !== 'POST'

) {

    $step = 1;

}

/*
Không dùng Session cũ để tự động
nhảy sang Bước 2 khi mở trang.
*/

?>

<!DOCTYPE html>

<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>

Quên mật khẩu | VIỆT HÀN WMS

</title>

<style>

* {

    box-sizing:
        border-box;

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

        "Segoe UI",

        Arial,

        sans-serif;

}

.page {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    min-height:
        100vh;

}

.brand-side {

    display:
        flex;

    align-items:
        center;

    padding:
        70px;

    color:
        white;

    background:

        linear-gradient(

            135deg,

            #081d35,

            #10385f,

            #1a5a83

        );

}

.brand-content {

    max-width:
        620px;

}

.logo {

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
        54px;

    height:
        54px;

    place-items:
        center;

    color:
        #102b47;

    background:
        #f28c28;

    border-radius:
        14px;

    font-weight:
        900;

}

.logo-name {

    font-size:
        20px;

    font-weight:
        900;

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

.badge {

    display:
        inline-block;

    padding:
        8px 14px;

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
        23px 0 17px;

    font-size:

        clamp(

            38px,

            4vw,

            60px

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

    line-height:
        1.8;

}

.form-side {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        45px;

    background:
        white;

}

.form-box {

    width:
        100%;

    max-width:
        450px;

}

.eyebrow {

    color:
        #f28c28;

    font-size:
        11px;

    font-weight:
        900;

    letter-spacing:
        1.5px;

}

h1 {

    margin:
        12px 0 9px;

    color:
        #102b47;

    font-size:
        33px;

}

.description {

    margin:
        0 0 30px;

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

label {

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

input {

    width:
        100%;

    height:
        53px;

    padding:
        0 16px;

    border:

        1px solid
        #d8dee8;

    border-radius:
        9px;

    outline:
        none;

    font-size:
        14px;

}

input:focus {

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

.button {

    width:
        100%;

    height:
        53px;

    margin-top:
        5px;

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

}

.button:hover {

    box-shadow:

        0 10px 22px

        rgba(
            16,
            43,
            71,
            0.20
        );

    transform:
        translateY(-1px);

}

.message {

    margin-bottom:
        20px;

    padding:
        13px;

    border-radius:
        9px;

    font-size:
        12px;

    line-height:
        1.6;

}

.error {

    color:
        #b42318;

    background:
        #fef3f2;

    border:

        1px solid
        #fecdca;

}

.success {

    color:
        #027a48;

    background:
        #ecfdf3;

    border:

        1px solid
        #abefc6;

}

.step {

    display:
        flex;

    gap:
        8px;

    margin-bottom:
        26px;

}

.step-item {

    flex:
        1;

    padding:
        9px;

    color:
        #98a2b3;

    background:
        #f2f4f7;

    border-radius:
        7px;

    text-align:
        center;

    font-size:
        10px;

    font-weight:
        800;

}

.step-item.active {

    color:
        white;

    background:
        #102b47;

}

.email-note {

    margin:

        -5px 0 20px;

    color:
        #667085;

    font-size:
        11px;

    line-height:
        1.6;

}

.back-link {

    display:
        block;

    margin-top:
        23px;

    color:
        #1b5f8f;

    text-align:
        center;

    font-size:
        12px;

    font-weight:
        700;

    text-decoration:
        none;

}

.back-link:hover {

    color:
        #f28c28;

    text-decoration:
        underline;

}

@media (

    max-width:
    800px

) {

    .page {

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
            35px 20px;

    }

}

</style>

</head>

<body>

<div class="page">

<section class="brand-side">

<div class="brand-content">

<div class="logo">

<div class="logo-icon">

VH

</div>

<div>

<div class="logo-name">

VIỆT HÀN WMS

</div>

<div class="logo-subtitle">

WAREHOUSE MANAGEMENT SYSTEM

</div>

</div>

</div>

<div class="badge">

BẢO MẬT TÀI KHOẢN

</div>

<h2 class="brand-title">

Khôi phục quyền truy cập.

<br>

Vận hành

<span>

liên tục.

</span>

</h2>

<p class="brand-description">

Xác thực tài khoản bằng email,
mã OTP và mã bảo mật nội bộ
trước khi thiết lập mật khẩu mới.

</p>

</div>

</section>

<section class="form-side">

<div class="form-box">

<div class="eyebrow">

KHÔI PHỤC TÀI KHOẢN

</div>

<h1>

Quên mật khẩu?

</h1>

<p class="description">

<?php if (
    $step === 1
): ?>

Nhập email đã đăng ký
để nhận mã OTP qua Gmail.

<?php else: ?>

Nhập mã OTP và thiết lập
mật khẩu mới cho tài khoản.

<?php endif; ?>

</p>

<div class="step">

<div
    class="step-item
    <?= $step === 1
        ? 'active'
        : ''
    ?>"
>

1. Xác thực Email

</div>

<div
    class="step-item
    <?= $step === 2
        ? 'active'
        : ''
    ?>"
>

2. Đặt lại mật khẩu

</div>

</div>

<?php if (
    $error !== ''
): ?>

<div class="message error">

<?= htmlspecialchars(
    $error
) ?>

</div>

<?php endif; ?>

<?php if (
    $success !== ''
): ?>

<div class="message success">

<?= htmlspecialchars(
    $success
) ?>

</div>

<?php endif; ?>

<?php if (
    $step === 1
): ?>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="send_otp"
>

<div class="form-group">

<label for="email">

Email tài khoản

</label>

<input
    id="email"
    name="email"
    type="email"
    value="<?= htmlspecialchars(
        $email
    ) ?>"
    placeholder="Nhập email đã đăng ký"
    autocomplete="email"
    required
>

</div>

<button
    class="button"
    type="submit"
>

Gửi mã OTP qua Email

</button>

</form>

<?php else: ?>

<p class="email-note">

Mã OTP đã được gửi đến:

<strong>

<?= htmlspecialchars(
    $email
) ?>

</strong>

</p>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="reset_password"
>

<div class="form-group">

<label for="otp">

Mã OTP gồm 6 chữ số

</label>

<input
    id="otp"
    name="otp"
    type="text"
    inputmode="numeric"
    pattern="[0-9]{6}"
    maxlength="6"
    placeholder="Nhập mã OTP"
    required
>

</div>

<div class="form-group">

<label for="new_password">

Mật khẩu mới

</label>

<input
    id="new_password"
    name="new_password"
    type="password"
    minlength="6"
    autocomplete="new-password"
    placeholder="Ít nhất 6 ký tự"
    required
>

</div>

<div class="form-group">

<label for="confirm_password">

Xác nhận mật khẩu mới

</label>

<input
    id="confirm_password"
    name="confirm_password"
    type="password"
    minlength="6"
    autocomplete="new-password"
    placeholder="Nhập lại mật khẩu mới"
    required
>

</div>

<div class="form-group">

<label for="security_code">

Mã bảo mật nội bộ

</label>

<input
    id="security_code"
    name="security_code"
    type="password"
    placeholder="Nhập mã NV hoặc mã Admin"
    required
>

</div>

<button
    class="button"
    type="submit"
>

Xác nhận và đổi mật khẩu

</button>

</form>

<?php endif; ?>

<a
    class="back-link"
    href="login.php"
>

← Quay lại trang đăng nhập

</a>

</div>

</section>

</div>

<script>

/* =====================================================
   CHỈ CHO PHÉP NHẬP SỐ Ở Ô OTP
===================================================== */

const otpInput =
    document.getElementById(
        'otp'
    );

if (
    otpInput
) {

    otpInput.addEventListener(

        'input',

        function () {

            this.value =
                this.value.replace(
                    /[^0-9]/g,
                    ''
                );

        }

    );

}

</script>

</body>

</html>