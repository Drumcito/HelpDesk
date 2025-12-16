<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 1) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
    exit;
}

$pdo = Database::getConnection();

function sendRecoveryEmailToUser(string $to): bool {
    // EXACTAMENTE con tu formato (solo corregí “solicito” -> “se realizó” para que tenga sentido)
    $subject = "Recuperacion de contraseña - HELP DESK EQF";

    $msg =
"Buen día,\n\n".
"Se realizó la recuperación de contraseña del sistema Help Desk. Recuerde que al iniciar sesión por primera vez deberá cambiarla.\n\n".
"User: {$to}\n".
"Contraseña: 12345a\n\n".
"Excelente día\n\n".
"Servicio de soporte\n".
"HelpDesk EQF\n";

    // OJO: para que realmente “salga desde sa@eqf.mx” necesitas SMTP real.
    // Con mail() ponemos From, pero depende del servidor.
    $headers = "From: sa_helpdesk@outlook.mx\r\n";
    return @mail($to, $subject, $msg, $headers);
}

try {
    $pdo->beginTransaction();

    // Bloquear registro para evitar doble click
    $st = $pdo->prepare("SELECT requester_email, status FROM password_recovery_requests WHERE id=? FOR UPDATE");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row || ($row['status'] ?? '') !== 'PENDIENTE') {
        $pdo->rollBack();
        header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
        exit;
    }

    $email = trim((string)$row['requester_email']);

    // marcar atendido
    $stU = $pdo->prepare("
      UPDATE password_recovery_requests
      SET status='ATENDIDO', attended_at=NOW(), attended_by=?
      WHERE id=?
    ");
    $stU->execute([(int)$_SESSION['user_id'], $id]);

    $pdo->commit();

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendRecoveryEmailToUser($email);
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
exit;
