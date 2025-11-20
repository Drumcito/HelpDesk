<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

$pdo = Database::getConnection();

$nombreCompleto = $_SESSION['user_name'] . ' ' . $_SESSION['user_last'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/directory/directory.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        /* ========== CREAR USUARIO ========== */
        case 'create':
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
                // Podrías manejar errores mediante $_SESSION['flash']
                header('Location: ../modules/directory/directory.php?error=datos');
                exit;
            }

            // Hashear contraseña temporal
            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

            // IMPORTANTE:
            // Si NO creaste la columna must_change_password en la tabla users,
            // elimina ", must_change_password" y ", 1" de la consulta.
            $stmt = $pdo->prepare('
                INSERT INTO users (
                    number_sap, name, last_name,
                    email, password, rol, area, must_change_password
                ) VALUES (
                    :sap, :name, :last,
                    :email, :pass, :rol, :area, 1
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

        /* ========== ACTUALIZAR USUARIO ========== */
        case 'update':
            $id    = (int)($_POST['id'] ?? 0);
            $sap   = trim($_POST['number_sap'] ?? '');
            $name  = trim($_POST['name'] ?? '');
            $last  = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $area  = trim($_POST['area'] ?? '');
            $rol   = (int)($_POST['rol'] ?? 0);

            if ($id <= 0 || $sap === '' || $name === '' || $last === '' ||
                $email === '' || $area === '' || $rol === 0) {

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

        /* ========== ELIMINAR USUARIO ========== */
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                header('Location: ../modules/directory/directory.php?error=delete_id');
                exit;
            }

            // Evitar que se borre a sí mismo
            if ($id === (int)$_SESSION['user_id']) {
                header('Location: ../modules/directory/directory.php?error=no_self_delete');
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);

            header('Location: ../modules/directory/directory.php?deleted=1');
            exit;

        default:
            header('Location: ../modules/directory/directory.php?error=accion');
            exit;
    }
} catch (PDOException $e) {
    // En ambiente real, loggear el error. Aquí solo redirigimos.
    header('Location: ../modules/directory/directory.php?error=db');
    exit;
}
