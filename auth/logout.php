<?php
session_start();

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

/* 4) Redirigir al login */
header("Location: /HelpDesk_EQF/auth/login.php");
exit;
