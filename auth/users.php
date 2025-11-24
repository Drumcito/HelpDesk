<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/directory/directory.php');
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
        $tempPassword = $_POST['temp_password'] ?? '';

        if (
            $sap === '' || $name === '' || $last === '' ||
            $email === '' || $area === '' || $rol === 0 ||
            $tempPassword === ''
        ) {
            
            header('Location: ../modules/directory/directory.php?error=datos');
            exit;
        }

        $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

        // SIN must_change_password, solo las columnas que ya tienes
        $stmt = $pdo->prepare('
            INSERT INTO users (
                number_sap, name, last_name,
                email, password, rol, area
            ) VALUES (
                :sap, :name, :last,
                :email, :pass, :rol, :area
            )
        ');

        $stmt->execute([
            ':sap'   => $sap,
            ':name'  => $name,
            ':last'  => $last,
            ':email' => $email,
            ':pass'  => $hash,
            ':rol'   => $rol,
            ':area'  => $area,
        ]);

header('Location: ../modules/directory/directory.php?created=1');
        exit;
    }

    /* ========== ACTUALIZAR USUARIO ========== */
    if ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $sap   = trim($_POST['number_sap'] ?? '');
        $name  = trim($_POST['name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $area  = trim($_POST['area'] ?? '');
        $rol   = (int)($_POST['rol'] ?? 0);

        if (
            $id <= 0 || $sap === '' || $name === '' || $last === '' ||
            $email === '' || $area === '' || $rol === 0
        ) {
            header('Location: ../modules/directory/directory.php?error=datos_update');
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE users
            SET number_sap = :sap,
                name       = :name,
                last_name  = :last,
                email      = :email,
                area       = :area,
                rol        = :rol
            WHERE id = :id
        ');

        $stmt->execute([
            ':sap'   => $sap,
            ':name'  => $name,
            ':last'  => $last,
            ':email' => $email,
            ':area'  => $area,
            ':rol'   => $rol,
            ':id'    => $id,
        ]);

header('Location: ../modules/directory/directory.php?updated=1');
        exit;
    }

    /* ========== ELIMINAR USUARIO ========== */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ../modules/directory/directory.php?error=delete_id');
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

header('Location: ../modules/directory/directory.php?deleted=1');
        exit;
    }

    // Acción desconocida
    header('Location: ../modules/directory/directory.php?error=accion');
    exit;

} catch (PDOException $e) {
    // Para debug rápido, si quieres ver el error real:
    // echo "Error BD: " . $e->getMessage();
    // exit;
    header('Location: ../modules/directory/directory.php?error=db');
    exit;
}
