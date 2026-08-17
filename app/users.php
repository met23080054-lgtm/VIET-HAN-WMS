<?php

require_once __DIR__ . '/config/auth.php';

requireAdmin();

require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("
    SELECT
        users.id,
        users.full_name,
        users.username,
        users.email,
        users.created_at,
        roles.role_name
    FROM users
    INNER JOIN roles
        ON users.role_id = roles.id
    ORDER BY users.id DESC
");

$users = $stmt->fetchAll(
    PDO::FETCH_ASSOC
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

<title>
Quản lý tài khoản
</title>

<style>

body {

    margin: 0;

    padding: 35px;

    background: #f4f6f9;

    font-family:
        Arial,
        sans-serif;

}

.container {

    max-width: 1100px;

    margin: auto;

    padding: 30px;

    background: white;

    border-radius: 14px;

    box-shadow:
        0 8px 25px
        rgba(
            0,
            0,
            0,
            0.08
        );

}

h1 {

    color:
        #0f2742;

}

.back {

    display:
        inline-block;

    margin-bottom:
        20px;

    color:
        #ffffff;

    background:
        #0f2742;

    padding:
        10px 15px;

    border-radius:
        7px;

    text-decoration:
        none;

}

table {

    width:
        100%;

    border-collapse:
        collapse;

}

th,

td {

    padding:
        13px;

    border:
        1px solid
        #dce1e7;

    text-align:
        left;

}

th {

    color:
        white;

    background:
        #0f2742;

}

.role-admin {

    color:
        #92400e;

    font-weight:
        bold;

}

.role-staff {

    color:
        #166534;

    font-weight:
        bold;

}

</style>

</head>

<body>

<div class="container">

<a
    class="back"
    href="index.php"
>

← Dashboard

</a>

<h1>

Quản lý tài khoản

</h1>

<table>

<thead>

<tr>

<th>ID</th>

<th>Họ và tên</th>

<th>Tên đăng nhập</th>

<th>Email</th>

<th>Vai trò</th>

<th>Ngày tạo</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $users
    as $user
): ?>

<tr>

<td>

<?= (int)
$user['id']
?>

</td>

<td>

<?= htmlspecialchars(
$user['full_name']
) ?>

</td>

<td>

<?= htmlspecialchars(
$user['username']
) ?>

</td>

<td>

<?= htmlspecialchars(
$user['email']
?? ''
) ?>

</td>

<td>

<span
class="
<?= $user['role_name']
=== 'Admin'
? 'role-admin'
: 'role-staff'
?>
"
>

<?= htmlspecialchars(
$user['role_name']
) ?>

</span>

</td>

<td>

<?= htmlspecialchars(
$user['created_at']
) ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</body>

</html>