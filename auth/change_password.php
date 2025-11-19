<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = Database::getConnection();
$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'] ?? '';
    $newPass2 = $_POST['new_password_confirm'] ?? '';

    if ($newPass === '' || $newPass2 === '') {
        $error = 'Por favor, llena ambos campos.';
    } elseif ($newPass !== $newPass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($newPass) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            UPDATE users
            SET password = :password, must_change_password = 0
            WHERE id = :id
        ');
        $stmt->execute([
            ':password' => $hash,
            ':id'       => $_SESSION['user_id'],
        ]);

        $success = 'Contraseña actualizada correctamente. Redirigiendo a tu panel...';

        // Redireccionar según el rol después de unos segundos
        $rol = (int)($_SESSION['user_rol'] ?? 4);

        switch ($rol) {
            case 1:
                $redirect = '../modules/dashboard/sa.php';
                break;
            case 2:
                $redirect = '../modules/dashboard/admin.php';
                break;
            case 3:
                $redirect = '../modules/dashboard/analista.php';
                break;
            case 4:
            default:
                $redirect = '../modules/dashboard/usuario.php';
                break;
        }

        header("Refresh: 2; URL={$redirect}");
    }
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$userLast = $_SESSION['user_last'] ?? '';
$nombreCompleto = trim($userName . ' ' . $userLast);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar contraseña | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-title">
                <p class="login-subtitle">ACTUALIZA TU CONTRASEÑA</p>
                <p class="login-brand">
                    <span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                </p>
                <p style="font-size:0.8rem;color:#6b7280;margin-top:6px;">
                    Hola <?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?>,
                    por seguridad necesitas registrar una nueva contraseña personal.
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group input-line">
                    <span class="input-icon">&#128273;</span>
                    <input
                        type="password"
                        name="new_password"
                        placeholder="Nueva contraseña"
                        required
                    >
                </div>

                <div class="form-group input-line">
                    <span class="input-icon">&#128273;</span>
                    <input
                        type="password"
                        name="new_password_confirm"
                        placeholder="Repite tu nueva contraseña"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">
                    Guardar nueva contraseña
                </button>
            </form>
        </div>
    </div>
</body>
</html>
