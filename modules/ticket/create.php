<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/user.php');
    exit;
}

$pdo = Database::getConnection();

try{

// Datos del formulario
$user_id    = $_POST['user_id'] ?? null;
$sap        = $_POST['sap'] ?? '';
$nombre     = $_POST['nombre'] ?? '';
$area       = $_POST['area'] ?? '';
$email      = $_POST['email'] ?? '';
$problema   = $_POST['problema'] ?? '';
$descripcion= $_POST['descripcion'] ?? '';

// Fecha y hora de envío (servidor)
$fecha_envio = date('Y-m-d H:i:s');

// Insert principal del ticket
$sql = "INSERT INTO tickets (
            user_id,
            sap,
            nombre,
            area,
            email,
            problema,
            descripcion,
            fecha_envio
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $user_id,
    $sap,
    $nombre,
    $area,
    $email,
    $problema,
    $descripcion,
    $fecha_envio
]);

$ticket_id = $pdo->lastInsertId();

// MANEJO DE ADJUNTOS (opcional, si quieres guardar archivos)
if (!empty($_FILES['adjuntos']['name'][0])) {

    $uploadDir = __DIR__ . '/../../uploads/tickets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    foreach ($_FILES['adjuntos']['name'] as $index => $fileName) {
        if ($_FILES['adjuntos']['error'][$index] === UPLOAD_ERR_OK) {

            $tmpName   = $_FILES['adjuntos']['tmp_name'][$index];
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            // Nombre único para evitar colisiones
            $newName = uniqid('ticket_' . $ticket_id . '_') . '.' . $extension;
            $destino = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $destino)) {
                // Guardar info del archivo en una tabla de adjuntos (ejemplo)
                $stmtAdj = $pdo->prepare("
                    INSERT INTO ticket_attachments (ticket_id, nombre_archivo, ruta_archivo)
                    VALUES (?, ?, ?)
                ");
                $stmtAdj->execute([
                    $ticket_id,
                    $fileName,
                    'uploads/tickets/' . $newName  // ruta relativa
                ]);
            }
        }
    }
}

 header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php?created=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();

    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php?deleted=1');
    exit;
}