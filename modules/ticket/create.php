<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php');
    exit;
}

$pdo = Database::getConnection();

try {

    // Iniciamos transacción
    $pdo->beginTransaction();

    // Datos del formulario
    $user_id    = $_POST['user_id'] ?? null;
    $sap        = $_POST['sap'] ?? '';
    $nombre     = $_POST['nombre'] ?? '';

    // Área del usuario (Sucursal / Corporativo, etc.)
    $user_area    = $_POST['area'] ?? '';
    // Área de soporte elegida en el formulario (TI / SAP / MKT)
    $area_soporte = $_POST['area_soporte'] ?? '';
    // Usamos el área de soporte, si viene; si no, el área del usuario
    $area         = $area_soporte !== '' ? $area_soporte : $user_area;

    $email        = $_POST['email'] ?? '';
    $problema     = $_POST['problema'] ?? '';
    $descripcion  = $_POST['descripcion'] ?? '';
    $prioridad    = $_POST['prioridad'] ?? 'media';

    // Fecha y hora de envío (servidor)
    $fecha_envio = date('Y-m-d H:i:s');

    // IMPORTANTE:
    // En la BD la columna debe llamarse "prioridad" (no "priotidad").
    $sql = "INSERT INTO tickets (
                user_id,
                sap,
                nombre,
                area,
                email,
                problema,
                prioridad,
                descripcion,
                fecha_envio
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $sap,
        $nombre,
        $area,
        $email,
        $problema,
        $prioridad,
        $descripcion,
        $fecha_envio
    ]);

    $ticket_id = $pdo->lastInsertId();

    // MANEJO DE ADJUNTOS (opcional)
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

    // Todo OK → commit
    $pdo->commit();

    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php?created=1');
    exit;

} catch (Exception $e) {

    // Si hay transacción activa, la revertimos
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Opcional: log para depurar
    error_log('Error al crear ticket: ' . $e->getMessage());

    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php?deleted=1');
    exit;
}
