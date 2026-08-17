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
   X? LY FORM
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
            'Phin lm vi?c khng h?p l?. Vui lng th? l?i.'
        );
    }

    $action = $_POST['action'] ?? '';


    /* =====================================================
       C?P NH?T THONG TIN CA NHAN
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
                'H? v tn khng du?c d? tr?ng.'
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
                'Email khng h?p l?.'
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
            'Da c?p nh?t thng tin c nhn.'
        );
    }


    /* =====================================================
       D?I M?T KH?U
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
                'Vui lng nh?p d?y d? m?t kh?u.'
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
                'M?t kh?u hi?n t?i khng dng.'
            );
        }

        if (strlen($newPassword) < 8) {
            redirectTab(
                'password',
                '',
                'M?t kh?u m?i ph?i c t nh?t 8 ky t?.'
            );
        }

        if ($newPassword !== $confirmPassword) {
            redirectTab(
                'password',
                '',
                'Xc nh?n m?t kh?u khng kh?p.'
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
                'M?t kh?u m?i ph?i khc m?t kh?u hi?n t?i.'
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
       D?I ?NH D?I DI?N
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
                'Vui lng ch?n m?t ?nh h?p l?.'
            );
        }

        $file = $_FILES['avatar'];

        if ($file['size'] > 5 * 1024 * 1024) {
            redirectTab(
                'avatar',
                '',
                '?nh t?i da 5 MB.'
            );
        }

        $imageInfo = @getimagesize(
            $file['tmp_name']
        );

        if ($imageInfo === false) {
            redirectTab(
                'avatar',
                '',
                'T?p t?i ln khng ph?i hnh ?nh h?p l?.'
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
                'Ch? h? tr? JPG, PNG ho?c WEBP.'
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
                'Khng th? t?o thu m?c luu ?nh.'
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
                'Khng th? luu ?nh ln my ch?.'
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
            'Da c?p nh?t ?nh d?i di?n.'
        );
    }
}


/* =========================================================
   D?C L?I THONG TIN SAU KHI C?P NH?T
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

<title>Ti kho?n - VI&#7878;T HAN WMS</title>

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
        VI&#7878;T HAN WMS
    </div>

    <a
        class="back"
        href="index.php"
    >
         Quay l?i Dashboard
    </a>

</header>

<div class="wrap">

    <div class="title">
        Ti kho?n
    </div>

    <div class="sub">
        Qu?n ly thng tin c nhn,
        m?t kh?u v ?nh d?i di?n.
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
                        ?? 'Nhn vin'
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
                    ?? Thng tin c nhn
                </a>

                <a
                    class="tab
                    <?= $tab === 'password'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=password"
                >
                    ?? &#272;&#7893;i m&#7853;t kh&#7849;u
                </a>

                <a
                    class="tab
                    <?= $tab === 'avatar'
                        ? 'active'
                        : '' ?>"
                    href="account.php?tab=avatar"
                >
                    ??? &#272;&#7893;i &#7843;nh &#273;&#7841;i di&#7879;n
                </a>

                <a
                    class="tab"
                    href="logout.php"
                >
                    ? &#272;&#259;ng xu&#7845;t
                </a>

            </nav>

            <section class="content">

                <?php if ($message !== ''): ?>

                    <div class="alert success">
                        <?= h($message) ?>
                    </div>

                <?php endif; ?>

                <?php if ($error !== ''): ?>

                    <div class="alert error">
                        <?= h($error) ?>
                    </div>

                <?php endif; ?>


                <?php if ($tab === 'profile'): ?>

                    <h2>
                        Thng tin c nhn
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
                                H? v tn
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
                                Tn dang nh?p
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
                                Quy?n ti kho?n
                            </label>

                            <input
                                class="readonly"
                                value="<?= h(
                                    $user['role_name']
                                    ?? 'Nhn vin'
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
                            Luu thng tin
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
                                M?t kh?u hi?n t?i
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
                                M?t kh?u m?i
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <div class="note">
                                T?i thi?u 8 ky t?.
                            </div>

                        </div>

                        <div class="row">

                            <label>
                                Nh?p l?i m?t kh?u m?i
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
                                        alt="?nh hi?n t?i"
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
                                JPG, PNG ho?c WEBP.
                                Dung lu?ng t?i da 5 MB.
                            </div>

                            <br>

                            <button type="submit">
                                T?i ?nh ln
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
