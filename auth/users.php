<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/dashboard/sa/directory.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
/* ========== CREAR USUARIO ========== */
if ($action === 'create') {
    $sap   = trim($_POST['number_sap'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $area  = trim($_POST['area'] ?? '');
    $rol   = (int)($_POST['rol'] ?? 0);

    $assignPassword = isset($_POST['assign_password']);

    if (
        $sap === '' || $name === '' || $last === '' ||
        $email === '' || $area === '' || $rol === 0 || !$assignPassword
    ) {
        header('Location: ../modules/dashboard/sa/directory.php?error=datos');
        exit;
    }

    $tempPassword = '12345a';
    $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
        INSERT INTO users (number_sap, name, last_name, email, 
        password, rol, area, must_change_password)
        VALUES (:sap, :name, :last, :email, :password, :rol, :area, 1)
    ');

    $stmt->execute([
        ':sap'      => $sap,
        ':name'     => $name,
        ':last'     => $last,
        ':email'    => $email,
        ':password' => $hash,
        ':rol'      => $rol,
        ':area'     => $area,
    ]);

    header('Location: ../modules/dashboard/sa/directory.php?created=1');
    exit;
}

    /* ========== ACTUALIZAR USUARIO ========== */
    if ($action === 'update') {
    $id         = (int)($_POST['id'] ?? 0);
    $number_sap = trim($_POST['number_sap'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $area       = trim($_POST['area'] ?? '');
    $rol        = (int)($_POST['rol'] ?? 0);

    // Checkbox de reinicio de contraseña
        $resetPassword = isset($_POST['reset_password']);

    if ($id <= 0) {
        header(header: 'Location: ../modules/dashboard/sa/directory.php?error=1');
        exit;
    }

    if ($resetPassword) {
        $newPlain = '12345a';
        $newHash  = password_hash($newPlain, PASSWORD_DEFAULT);

        $sql = '
            UPDATE users
            SET number_sap = :sap,
                name       = :name,
                last_name  = :last,
                email      = :email,
                area       = :area,
                rol        = :rol,
                password   = :pwd,
                must_change_password = 1
            WHERE id = :id
        ';

        $params = [
            ':sap'  => $number_sap,
            ':name' => $name,
            ':last' => $last_name,
            ':email'=> $email,
            ':area' => $area,
            ':rol'  => $rol,
            ':pwd'  => $newHash,
            ':id'   => $id,
        ];
    } else {
        // Actualización normal, sin tocar contraseña
        $sql = '
            UPDATE users
            SET number_sap = :sap,
                name       = :name,
                last_name  = :last,
                email      = :email,
                area       = :area,
                rol        = :rol
            WHERE id = :id
        ';

        $params = [
            ':sap'  => $number_sap,
            ':name' => $name,
            ':last' => $last_name,
            ':email'=> $email,
            ':area' => $area,
            ':rol'  => $rol,
            ':id'   => $id,
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Redirigir de regreso al directorio con alerta
    // Puedes diferenciar si quieres, pero por ahora usamos updated
    header('Location: ../modules/dashboard/sa/directory.php?updated=1');
    exit;
}

    /* ========== ELIMINAR USUARIO ========== */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ../modules/dashboard/sa/directory.php?error=delete_id');
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

header('Location: ../modules/dashboard/sa/directory.php?deleted=1');
        exit;
    }

    // Acción desconocida
    header('Location: ../modules/dashboard/sa/directory.php?error=accion');
    exit;

} catch (PDOException $e) {
    // Para debug rápido, si quieres ver el error real:
    // echo "Error BD: " . $e->getMessage();
    // exit;
    header('Location: ../modules/dashboard/sa/directory.php?error=db');
    exit;
}
