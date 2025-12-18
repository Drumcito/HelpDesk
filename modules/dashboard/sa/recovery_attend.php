<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/../../../config/audit.php';
require_once __DIR__ . '/../../../helpers/Mailer.php';

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
    $subject = "Recuperación de contraseña - HELP DESK EQF";
    $msg =
"Buen día,\n\n".
"Se realizó la recuperación de contraseña del sistema Help Desk. Recuerde que al iniciar sesión por primera vez deberá cambiarla.\n\n".
"👤: {$to}\n".
"🔒: 12345a\n\n".
"Excelente día\n\n".
"Servicio de soporte\n".
"HelpDesk EQF\n";

    return sendMailEQF([$to => $to], $subject, $msg);
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

    audit_log($pdo, 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', $id, [
      'requester_email' => $email
      // Si NO quieres guardar la temporal por seguridad, déjalo así.
      // 'temp_password' => '12345a'
    ]);

    $pdo->commit();

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendRecoveryEmailToUser($email);
    }


} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
exit;
