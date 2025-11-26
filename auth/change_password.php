<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

$pdo = Database::getConnection();

/* 1) Solo usuarios logueados pueden estar aquí */
if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$fullName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['new_password'] ?? '';
    $pass2 = $_POST['new_password_confirm'] ?? '';

    if ($pass1 === '' || $pass2 === '') {
        $error = 'Por favor, completa ambos campos.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($pass1) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Actualizar password en BD y limpiar el flag
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            UPDATE users
            SET password = :pwd, must_change_password = 0
            WHERE id = :id
        ');
        $stmt->execute([
            ':pwd' => $hash,
            ':id'  => $userId,
        ]);

        // Cerrar sesión para obligar a login con contraseña nueva
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }

        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Redirigir al login con mensaje de éxito
        header('Location: /HelpDesk_EQF/auth/login.php?pwd_changed=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar contraseña | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">
</head>
<body class="change-pass-body">

<div class="change-pass-wrapper">
    <div class="change-pass-card">
        <div class="change-pass-header">
            <h2>Actualizar contraseña</h2>
            <?php if ($fullName !== ''): ?>
                <p class="change-pass-user">
                    <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="change-pass-error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="change-pass-form" autocomplete="off">
            <!-- Nueva contraseña -->
            <div class="change-pass-field">
                <label for="new_password">Nueva contraseña</label>
                <div class="change-pass-input-wrap">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        required
                    >
                    <button type="button"
                            class="change-pass-toggle"
                            data-target="new_password"
                            aria-label="Mostrar u ocultar contraseña">
                        👁
                    </button>
                </div>
            </div>

            <!-- Repetir contraseña -->
            <div class="change-pass-field">
                <label for="new_password_confirm">Repetir contraseña</label>
                <div class="change-pass-input-wrap">
                    <input
                        type="password"
                        id="new_password_confirm"
                        name="new_password_confirm"
                        required
                    >
                    <button type="button"
                            class="change-pass-toggle"
                            data-target="new_password_confirm"
                            aria-label="Mostrar u ocultar contraseña">
                        👁
                    </button>
                </div>
            </div>

            <div class="change-pass-actions">
                <button type="submit" class="btn-change-pass">
                    actualizar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Mostrar / ocultar contraseña (ojito)
document.querySelectorAll('.change-pass-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const input    = document.getElementById(targetId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '👁️‍🗨️';
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
        }
    });
});
</script>

</body>
</html>
