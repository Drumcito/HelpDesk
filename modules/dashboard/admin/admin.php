<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
$activePage = 'inicio';
include __DIR__ . '/../../../template/navbar.php'; 


if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if ($rol !== 2) {
    header('Location: /../../../modules/dashboard/admin/admin.php');
    exit;
}
$pdo = Database::getConnection();

$nombreCompleto = $_SESSION['user_name'] . ' ' . $_SESSION['user_last'];

$stmt = $pdo->query('
    SELECT id, number_sap, name, last_name, email, rol, area
    FROM users
    ORDER BY last_name ASC, name ASC
');


$usuarios = $stmt->fetchAll();

function rolLabel(int $rol): string {
    return match ($rol) {
        1 => 'SA',
        2 => 'Admin',
        3 => 'Analista',
        4 => 'Usuario',
        default => '—',
    };
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin</title>
</head>
<body>
    <h1> ADMIN</h1>
</body>
</html>