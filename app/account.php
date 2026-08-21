<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

function h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirectTab(
    string $tab,
    string $message = '',
    string $error = ''
): void {
    $url = 'account.php?tab=' . urlencode($tab);

    if ($message !== '') {
        $url .= '&message=' . urlencode($message);
    }

    if ($error !== '') {
        $url .= '&error=' . urlencode($error);
    }

    header('Location: ' . $url);
    exit;
}

if (empty($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(
        random_bytes(32)
    );
}

$csrf = $_SESSION['account_csrf'];

$stmt = $pdo->prepare("
    SELECT
        users.id,
        users.full_name,
        users.username,
        users.email,
        users.avatar,
        users.password,
        roles.role_name
    FROM users
    LEFT JOIN roles
        ON users.role_id = roles.id
    WHERE users.id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();

    header('Location: login.php');
    exit;
}

$tab = $_GET['tab'] ?? 'profile';

$allowedTabs = [
    'profile',
    'password',
    'avatar'
];

if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'profile';
}


/* =========================================================
   X&#7916; L&#221; FORM
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !hash_equals(
            $csrf,
            (string) ($_POST['csrf_token'] ?? '')
        )
    ) {
        redirectTab(
            $tab,
            '',
            'Phi&#234;n l&#224;m vi&#7879;c kh&#244;ng h&#7907;p l&#7879;. Vui l&#242;ng th&#7917; l&#7841;i.'
        );
    }

    $action = $_POST['action'] ?? '';


    /* =====================================================
       CAP NHAT THONG TIN CA NHAN
    ===================================================== */

    if ($action === 'profile') {

        $fullName = trim(
            (string) ($_POST['full_name'] ?? '')
        );

        $email = trim(
            (string) ($_POST['email'] ?? '')
        );

        if ($fullName === '') {
            redirectTab(
                'profile',
                '',
                'H&#7885; v&#224; t&#234;n kh&#244;ng &#273;&#432;&#7907;c &#273;&#7875; tr&#7889;ng.'
            );
        }

        if (
            $email !== ''
            &&
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            redirectTab(
                'profile',
                '',
                'Email kh&#244;ng h&#7907;p l&#7879;.'
            );
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                full_name = ?,
                email = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $email,
            $userId
        ]);

        $_SESSION['full_name'] = $fullName;

        if (isset($_SESSION['email'])) {
            $_SESSION['email'] = $email;
        }

        redirectTab(
            'profile',
            '&#272;&#227; c&#7853;p nh&#7853;t th&#244;ng tin c&#225; nh&#226;n.'
        );
    }


    /* =====================================================
       DOI MAT KHAU
    ===================================================== */

    if ($action === 'password') {

        $currentPassword =
            (string) (
                $_POST['current_password'] ?? ''
            );

        $newPassword =
            (string) (
                $_POST['new_password'] ?? ''
            );

        $confirmPassword =
            (string) (
                $_POST['confirm_password'] ?? ''
            );

        if (
            $currentPassword === ''
            ||
            $newPassword === ''
            ||
            $confirmPassword === ''
        ) {
            redirectTab(
                'password',
                '',
                'Vui l&#242;ng nh&#7853;p &#273;&#7847;y &#273;&#7911; m&#7853;t kh&#7849;u.'
            );
        }

        if (
            !password_verify(
                $currentPassword,
                (string) $user['password']
            )
        ) {
            redirectTab(
                'password',
                '',
                'M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i kh&#244;ng &#273;&#250;ng.'
            );
        }

        if (strlen($newPassword) < 8) {
            redirectTab(
                'password',
                '',
                'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i c&#243; &#237;t nh&#7845;t 8 k&#253; t&#7921;.'
            );
        }

        if ($newPassword !== $confirmPassword) {
            redirectTab(
                'password',
                '',
                'X&#225;c nh&#7853;n m&#7853;t kh&#7849;u kh&#244;ng kh&#7899;p.'
            );
        }

        if (
            password_verify(
                $newPassword,
                (string) $user['password']
            )
        ) {
            redirectTab(
                'password',
                '',
                'M&#7853;t kh&#7849;u m&#7899;i ph&#7843;i kh&#225;c m&#7853;t kh&#7849;u hi&#7879;n t&#7841;i.'
            );
        }

        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $passwordHash,
            $userId
        ]);

        redirectTab(
            'password',
            '&#272;&#7893;i m&#7853;t kh&#7849;u thnh cng.'
        );
    }


    /* =====================================================
       DOI ANH DAI DIEN
    ===================================================== */

    if ($action === 'avatar') {

        if (
            !isset($_FILES['avatar'])
            ||
            $_FILES['avatar']['error'] !== UPLOAD_ERR_OK
        ) {
            redirectTab(
                'avatar',
                '',
                'Vui l&#242;ng ch&#7885;n m&#7897;t &#7843;nh h&#7907;p l&#7879;.'
            );
        }

        $file = $_FILES['avatar'];

        if ($file['size'] > 5 * 1024 * 1024) {
            redirectTab(
                'avatar',
                '',
                '&#7842;nh t&#7889;i &#273;a 5 MB.'
            );
        }

        $imageInfo = @getimagesize(
            $file['tmp_name']
        );

        if ($imageInfo === false) {
            redirectTab(
                'avatar',
                '',
                'T&#7879;p t&#7843;i l&ecirc;n kh&ocirc;ng ph&#7843;i h&igrave;nh &#7843;nh h&#7907;p l&ecirc;.'
            );
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $mime = $imageInfo['mime'] ?? '';

        if (!isset($allowed[$mime])) {
            redirectTab(
                'avatar',
                '',
                'Ch&#7881; h&#7895; tr&#7907; JPG, PNG ho&#7863;c WEBP.'
            );
        }

        $uploadDir =
            __DIR__ . '/uploads/avatars';

        if (
            !is_dir($uploadDir)
            &&
            !mkdir(
                $uploadDir,
                0755,
                true
            )
        ) {
            redirectTab(
                'avatar',
                '',
                'Kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c l&#432;u &#7843;nh.'
            );
        }

        $newName =
            'avatar_'
            . $userId
            . '_'
            . bin2hex(random_bytes(10))
            . '.'
            . $allowed[$mime];

        $destination =
            $uploadDir . '/' . $newName;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {
            redirectTab(
                'avatar',
                '',
                'Kh&#244;ng th&#7875; l&#432;u &#7843;nh l&#234;n m&#225;y ch&#7911;.'
            );
        }

        $oldAvatar =
            (string) (
                $user['avatar'] ?? ''
            );

        $avatarPath =
            'uploads/avatars/' . $newName;

        $stmt = $pdo->prepare("
            UPDATE users
            SET avatar = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $avatarPath,
            $userId
        ]);

        if (
            $oldAvatar !== ''
            &&
            strpos(
                $oldAvatar,
                'uploads/avatars/'
            ) === 0
        ) {
            $oldFile =
                __DIR__ . '/' . $oldAvatar;

            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        redirectTab(
            'avatar',
            '&#272;&#227; c&#7853;p nh&#7853;t &#7843;nh &#273;&#7841;i di&#7879;n.'
        );
    }
}


/* =========================================================
   DOC LAI THONG TIN SAU KHI CAP NHAT
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        users.id,
        users.full_name,
        users.username,
        users.email,
        users.avatar,
        roles.role_name
    FROM users
    LEFT JOIN roles
        ON users.role_id = roles.id
    WHERE users.id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$avatar =
    trim(
        (string) (
            $user['avatar'] ?? ''
        )
    );

$avatarUrl = '';

if ($avatar !== '') {
    $avatarUrl = $avatar;
}

$initial = mb_strtoupper(
    mb_substr(
        trim(
            (string) $user['full_name']
        ),
        0,
        1
    )
);

$message =
    (string) (
        $_GET['message'] ?? ''
    );

$error =
    (string) (
        $_GET['error'] ?? ''
    );

?>
<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>T&#224;i kho&#7843;n - VI&#7878;T H&#192;N WMS</title>

<style>

:root {
    --navy: #173b5f;
    --navy2: #0f2f4d;
    --orange: #f7931e;
    --bg: #f3f6fa;
    --text: #17324d;
    --muted: #718096;
    --border: #dce4ec;
    --red: #dc3545;
    --green: #16834b;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    background: var(--bg);
    color: var(--text);
}

.top {
    height: 72px;
    background: #fff;
    border-bottom:
        1px solid var(--border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 32px;
}

.brand {
    font-weight: 900;
    color: var(--navy);
    letter-spacing: .5px;
}

.back {
    color: var(--orange);
    text-decoration: none;
    font-weight: 800;
}

.wrap {
    max-width: 1050px;
    margin: 32px auto;
    padding: 0 20px;
}

.title {
    font-size: 28px;
    font-weight: 900;
    margin-bottom: 6px;
}

.sub {
    color: var(--muted);
    margin-bottom: 24px;
}

.card {
    background: #fff;
    border:
        1px solid var(--border);

    border-radius: 16px;

    box-shadow:
        0 8px 25px
        rgba(15, 47, 77, .06);

    overflow: hidden;
}

.account-head {
    display: flex;
    align-items: center;
    gap: 16px;

    padding: 24px;

    border-bottom:
        1px solid var(--border);
}

.avatar {
    width: 72px;
    height: 72px;

    border-radius: 50%;

    display: grid;
    place-items: center;

    background: var(--navy);
    color: #fff;

    font-size: 25px;
    font-weight: 900;

    overflow: hidden;

    border:
        4px solid #fff4e8;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.name {
    font-size: 20px;
    font-weight: 900;
}

.role {
    color: var(--muted);
    margin-top: 4px;
}

.body {
    display: grid;
    grid-template-columns: 220px 1fr;
    min-height: 430px;
}

.tabs {
    border-right:
        1px solid var(--border);

    padding: 16px;
}

.tab {
    display: block;

    padding: 12px 14px;

    margin-bottom: 7px;

    border-radius: 10px;

    text-decoration: none;

    color: var(--text);

    font-weight: 700;
}

.tab:hover,
.tab.active {
    background: #fff4e8;
    color: var(--orange);
}

.content {
    padding: 28px;
}

.content h2 {
    margin:
        0 0 20px;
}

.row {
    margin-bottom: 16px;
}

.row label {
    display: block;

    font-size: 13px;
    font-weight: 800;

    margin-bottom: 7px;
}

.row input {
    width: 100%;

    padding: 12px;

    border:
        1px solid var(--border);

    border-radius: 9px;

    font-size: 14px;

    outline: none;
}

.row input:focus {
    border-color: var(--orange);
}

.readonly {
    background: #f7f9fb;
}

button {
    border: 0;

    border-radius: 9px;

    padding:
        12px 18px;

    background: var(--orange);

    color: #fff;

    font-weight: 900;

    cursor: pointer;
}

.note {
    font-size: 12px;
    color: var(--muted);
    margin-top: 7px;
}

.alert {
    padding:
        12px 14px;

    border-radius: 9px;

    margin-bottom: 18px;

    font-weight: 700;
}

.success {
    background: #eaf8f0;
    color: var(--green);
}

.error {
    background: #fff0f0;
    color: var(--red);
}

.upload {
    border:
        2px dashed var(--border);

    border-radius: 12px;

    padding: 25px;

    text-align: center;
}

.preview {
    width: 120px;
    height: 120px;

    margin:
        0 auto 16px;

    border-radius: 50%;

    overflow: hidden;

    background: var(--navy);

    color: #fff;

    display: grid;
    place-items: center;

    font-size: 40px;
    font-weight: 900;
}

.preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (max-width: 700px) {

    .body {
        grid-template-columns: 1fr;
    }

    .tabs {
        border-right: 0;

        border-bottom:
            1px solid var(--border);
    }

    .top {
        padding: 0 15px;
    }

    .wrap {
        margin: 20px auto;
    }

    .content {
        padding: 20px;
    }
}

</style>

</head>

<body>

<header class="top">

    <div class="brand">
        VI&#7878;T H&#192;N WMS
    </div>

    <a
    class="back"
    href="index.php"
>
    &#8592; Quay l&#7841;i Dashboard
</a>

</header>

<div class="wrap">

    <div class="title">
        T&#224;i kho&#7843;n
    </div>

    <div class="sub">
        Qu&#7843;n l&yacute; th&ocirc;ng tin c&aacute; nh&acirc;n,
        m&#7853;t kh&#7849;u v&#224; &#7843;nh &#273;&#7841;i di&#7879;n.
    </div>

    <div class="card">

        <div class="account-head">

            <div class="avatar">

                <?php if ($avatarUrl !== ''): ?>

                    <img
                        src="<?= h($avatarUrl) ?>"
                        alt="&#7842;nh &#273;&#7841;i di&#7879;n"
                    >

                <?php else: ?>

                    <?= h($initial) ?>

                <?php endif; ?>

            </div>

            <div>

                <div class="name">
                    <?= h($user['full_name']) ?>
                </div>

                <div class="role">
                    <?= h(
                        $user['role_name']
                        ?? 'Nh&#226;n vi&#234;n'
                    ) ?>
                </div>

            </div>

        </div>

        <div class="body">

            <nav class="tabs">

                <a
                    class="tab
                    <?= $tab === 'profile'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=profile"
                >
                    &#128100; Th&ocirc;ng tin c&aacute; nh&acirc;n
                </a>

                <a
                    class="tab
                    <?= $tab === 'password'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=password"
                >
                    &#128274; &#272;&#7893;i m&#7853;t kh&#7849;u
                </a>

                <a
                    class="tab
                    <?= $tab === 'avatar'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=avatar"
                >
                    &#128444;&#65039; &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n
                </a>

                <a
                    class="tab"
                    href="logout.php"
                >
                    &#8617; &#272;&#259;ng xu&#7845;t
                </a>

            </nav>

            <section class="content">

                <?php if ($message !== ''): ?>

                    <div class="alert success">
                        <?= h(html_entity_decode($message, ENT_QUOTES, 'UTF-8')) ?>
                    </div>

                <?php endif; ?>

                <?php if ($error !== ''): ?>

                    <div class="alert error">
                        <?= h(html_entity_decode($error, ENT_QUOTES, 'UTF-8')) ?>
                    </div>

                <?php endif; ?>


                <?php if ($tab === 'profile'): ?>

                    <h2>
                        Th&ocirc;ng tin c&aacute; nh&acirc;n
                    </h2>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= h($csrf) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="profile"
                        >

                        <div class="row">

                            <label>
                                H&#7885; v&#224; t&#234;n
                            </label>

                            <input
                                name="full_name"
                                value="<?= h(
                                    $user['full_name']
                                ) ?>"
                                maxlength="150"
                                required
                            >

                        </div>

                        <div class="row">

                            <label>
                                T&#234;n &#273;&#259;ng nh&#7853;p
                            </label>

                            <input
                                class="readonly"
                                value="<?= h(
                                    $user['username']
                                ) ?>"
                                readonly
                            >

                        </div>

                        <div class="row">

                            <label>
                                Quy&#7873;n t&#224;i kho&#7843;n
                            </label>

                            <input
                                class="readonly"
                                value="<?= h(
                                    $user['role_name']
                                    ?? 'Nh&#226;n vi&#234;n'
                                ) ?>"
                                readonly
                            >

                        </div>

                        <div class="row">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?= h(
                                    $user['email']
                                ) ?>"
                                maxlength="190"
                            >

                        </div>

                        <button type="submit">
                            L&#432;u th&#244;ng tin
                        </button>

                    </form>


                <?php elseif ($tab === 'password'): ?>

                    <h2>
                        &#272;&#7893;i m&#7853;t kh&#7849;u
                    </h2>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= h($csrf) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="password"
                        >

                        <div class="row">

                            <label>
                                M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                        <div class="row">

                            <label>
                                M&#7853;t kh&#7849;u m&#7899;i
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <div class="note">
                                T&#7889;i thi&#7875;u 8 k&#253; t&#7921;.
                            </div>

                        </div>

                        <div class="row">

                            <label>
                                Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <button type="submit">
                            &#272;&#7893;i m&#7853;t kh&#7849;u
                        </button>

                    </form>


                <?php else: ?>

                    <h2>
                        &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n
                    </h2>

                    <form
                        method="post"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= h($csrf) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="avatar"
                        >

                        <div class="upload">

                            <div class="preview">

                                <?php if ($avatarUrl !== ''): ?>

                                    <img
                                        src="<?= h(
                                            $avatarUrl
                                        ) ?>"
                                        alt="&#7842;nh hi&#7879;n t&#7841;i"
                                    >

                                <?php else: ?>

                                    <?= h($initial) ?>

                                <?php endif; ?>

                            </div>

                            <input
                                type="file"
                                name="avatar"
                                accept="
                                    image/jpeg,
                                    image/png,
                                    image/webp
                                "
                                required
                            >

                            <div class="note">
                                JPG, PNG ho&#7863;c WEBP.
                                Dung l&#432;&#7907;ng t&#7889;i &#273;a 5 MB.
                            </div>

                            <br>

                            <button type="submit">
                                T&#7843;i &#7843;nh l&#234;n
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </div>

    </div>

</div>

</body>

</html>
