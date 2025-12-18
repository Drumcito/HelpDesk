<?php
// /HelpDesk_EQF/helpers/Mailer.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía correo usando la configuración en /config/mailer.php
 * $to ejemplo: ["correo@dominio.com" => "Nombre opcional", "otro@x.com" => ""]
 */
function sendMailEQF(array $to, string $subject, string $bodyText): bool
{
    $cfgPath = __DIR__ . '/../config/mailer.php';
    if (!file_exists($cfgPath)) {
        error_log("MAIL ERROR: No existe config/mailer.php");
        return false;
    }

    $cfg = require $cfgPath;

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = (string)($cfg['host'] ?? '');
        $mail->SMTPAuth   = true;
        $mail->Username   = (string)($cfg['username'] ?? '');
        $mail->Password   = (string)($cfg['password'] ?? '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($cfg['port'] ?? 587);

        $fromEmail = (string)($cfg['from_email'] ?? $mail->Username);
        $fromName  = (string)($cfg['from_name']  ?? 'HelpDesk');

        $mail->setFrom($fromEmail, $fromName);

        $added = 0;
        foreach ($to as $email => $name) {
            $email = trim((string)$email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email, $name ?: $email);
                $added++;
            }
        }

        if ($added === 0) {
            error_log("MAIL ERROR: destinatarios inválidos");
            return false;
        }

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    } catch (Throwable $e) {
        error_log("MAIL ERROR Throwable: " . $e->getMessage());
        return false;
    }
}
