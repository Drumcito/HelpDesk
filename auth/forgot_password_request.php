<?php
session_start();
require_once __DIR__ . '/../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Correo inválido.']);
    exit;
}

$pdo = Database::getConnection();

// 1) Guardar solicitud en BD
try {
    $st = $pdo->prepare("INSERT INTO password_recovery_requests (requester_email) VALUES (?)");
    $st->execute([$email]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'No se pudo registrar la solicitud.']);
    exit;
}

// 2) Enviar correo a SA
$to = "sa_helpdesk@outlook.mx";
$subject = "Solicitud de recuperación de contraseña - HelpDesk EQF";

$msg =
"Buen día,\n\n".
"Se recibió una solicitud de recuperación de contraseña.\n\n".
"Usuario (correo): {$email}\n\n".
"Favor de atenderla desde el dashboard de Super Admin.\n\n".
"Servicio de soporte\n".
"HelpDesk EQF\n";

$headers = "From: no-reply@eqf.mx\r\n";
@mail($to, $subject, $msg, $headers);

// 3) Respuesta UI
echo json_encode([
  'ok' => true,
  'message' => 'Enviado con éxito, en un momento tendrás tu contraseña.'
]);
