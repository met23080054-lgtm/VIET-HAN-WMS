<?php

$f = 'index.php';

$s = file_get_contents($f);

if ($s === false) {
    exit("KHONG DOC DUOC index.php\n");
}

/* =========================================================
   BACKUP
========================================================= */

$backup = 'index_before_avatar_fix.php';

if (!file_exists($backup)) {
    copy($f, $backup);
}


/* =========================================================
   1. THEM BIEN $userAvatar
========================================================= */

$needle = <<<'PHP'
$userInitial = mb_strtoupper(
PHP;

if (strpos($s, '$userAvatar') === false) {

    $avatarCode = <<<'PHP'

/* =========================================================
   AVATAR NGUOI DUNG
========================================================= */

$userAvatar = '';

try {

    $stmtAvatar = $pdo->prepare(
        "SELECT avatar FROM users WHERE id = ? LIMIT 1"
    );

    $stmtAvatar->execute([
        (int) ($_SESSION['user_id'] ?? 0)
    ]);

    $avatarValue = $stmtAvatar->fetchColumn();

    if (
        $avatarValue !== false
        && trim((string) $avatarValue) !== ''
    ) {
        $userAvatar = trim((string) $avatarValue);
    }

} catch (Throwable $e) {

    $userAvatar = '';

}

PHP;

    $pos = strpos($s, $needle);

    if ($pos === false) {
        exit("KHONG TIM THAY $userInitial TRONG index.php\n");
    }

    $s = substr_replace(
        $s,
        $avatarCode . "\n",
        $pos,
        0
    );

    echo "DA THEM BIEN userAvatar\n";

} else {

    echo "userAvatar DA TON TAI\n";

}


/* =========================================================
   2. SUA HTML AVATAR
========================================================= */

$oldAvatar = <<<'HTML'
<div class="avatar">

<?= htmlspecialchars(
    $userInitial
) ?>

</div>
HTML;


$newAvatar = <<<'HTML'
<div class="avatar">

<?php if ($userAvatar !== ''): ?>

<img
    src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>"
    alt="Ảnh đại diện"
    class="dashboard-avatar"
>

<?php else: ?>

<?= htmlspecialchars(
    $userInitial
) ?>

<?php endif; ?>

</div>
HTML;


if (strpos($s, $oldAvatar) !== false) {

    $s = str_replace(
        $oldAvatar,
        $newAvatar,
        $s,
        $count
    );

    echo "DA SUA HTML AVATAR: {$count} lan\n";

} else {

    echo "KHONG TIM THAY HTML AVATAR CU\n";

}


/* =========================================================
   3. THEM CSS
========================================================= */

$cssNeedle = '</style>';

$css = <<<'CSS'

/* =========================================================
   DASHBOARD USER AVATAR
========================================================= */

.dashboard-avatar {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: 50%;
}

CSS;


if (strpos($s, '.dashboard-avatar') === false) {

    $s = str_replace(
        $cssNeedle,
        $css . "\n" . $cssNeedle,
        $s,
        $cssCount
    );

    echo "DA THEM CSS AVATAR: {$cssCount} lan\n";

}


/* =========================================================
   4. LUU FILE
========================================================= */

file_put_contents($f, $s);

echo "\n";
echo "======================================\n";
echo " INDEX AVATAR FIX COMPLETED\n";
echo "======================================\n";
echo "Backup: {$backup}\n";
