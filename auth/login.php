<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

$pdo = Database::getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Ya no validamos rol porque viene desde la BD
    if ($email === '' || $password === '') {
        $error = 'Por favor, completa todos los campos.';
    } else {
        $stmt = $pdo->prepare('SELECT id, number_sap, name, last_name, email, password, rol, area
                               FROM users
                               WHERE email = :email
                               LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Opcional pero recomendado: regenerar ID de sesión
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_last']  = $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_rol']   = $user['rol'];
            $_SESSION['user_area']  = $user['area'];
            $_SESSION['number_sap'] = $user['number_sap'];

            // Redirección automática según el rol guardado en la BD
            switch ((int)$user['rol']) {
                case 1:
                    header('Location: ../modules/dashboard/sa.php');
                    break;
                case 2:
                    header('Location: ../modules/dashboard/admin.php');
                    break;
                case 3:
                    header('Location: ../modules/dashboard/analist.php');
                    break;
                case 4:
                default:
                    header('Location: ../modules/dashboard/user.php');
                    break;
            }
            exit;
        } else {
            $error = 'Credenciales incorrectas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">

            <div class="login-avatar">
                <img src="../assets/img/capsulin_login.png" alt="Capsulin LOGIN">
            </div>

            <div class="login-title">
                <p class="login-subtitle">BIENVENIDO A TU MESA DE AYUDA</p>
                <p class="login-brand">
                    <span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" class="login-form" autocomplete="off">
                <!-- Usuario (email) -->
                <div class="form-group input-line">
                    <span class="input-icon">&#128100;</span>
                    <input
                        type="email"
                        name="email"
                        placeholder="Usuario"
                        required
                    >
                </div>

                <!-- Contraseña -->
                <div class="form-group input-line">
                    <span class="input-icon">&#128274;</span>
                    <input
                        type="password"
                        name="password"
                        placeholder="Contraseña"
                        required
                    >
                </div>

                <label for="session_open" class="remember-label">
                    <input type="checkbox" id="session_open" name="session_open" value="1">
                    Mantener sesión iniciada
                </label>

                <!-- Botón -->
                <button type="submit" class="btn-login">
                    Inicia sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
