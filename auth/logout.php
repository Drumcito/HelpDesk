<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';
require_once __DIR__ . '/../config/audit.php';

$pdo = Database::getConnection();
audit_log($pdo, 'AUTH_LOGOUT', 'auth');
$_SESSION = [];

if (session_id()) {
    session_destroy();
}

/* 3) Eliminar la cookie de sesión */
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

header("Location: /HelpDesk_EQF/auth/login.php");
exit;
