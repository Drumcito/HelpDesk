<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo       = Database::getConnection();
$userId    = (int)($_SESSION['user_id'] ?? 0);
$areaAdmin = trim($_SESSION['user_area'] ?? '');

// -----------------------------
// Flash messages (PRG)
// -----------------------------
$mensajeExito = $_SESSION['flash_ok'] ?? '';
$mensajeError = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

// -----------------------------
// Helpers
// -----------------------------
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'  => 'Cierre del día',
        'no_legado'   => 'Sin acceso a legado/legacy',
        'no_internet' => 'Sin internet',
        'no_checador' => 'No funciona checador',
        'rastreo'     => 'Rastreo de checada',
        'otro'        => 'Otro',
        default       => $p,
    };
}
function prioridadLabel(?string $p): string {
    $p = strtolower($p ?? '');
    return match ($p) {
        'alta'  => 'Alta',
        'media' => 'Media',
        'baja'  => 'Baja',
        default => ($p !== '' ? ucfirst($p) : '—'),
    };
}
function estadoLabel(?string $e): string {
    $e = strtolower($e ?? '');
    return match ($e) {
        'abierto'    => 'Abierto',
        'en_proceso' => 'En proceso',
        'resuelto'   => 'Resuelto',
        'cerrado'    => 'Cerrado',
        default      => ($e !== '' ? ucfirst($e) : '—'),
    };
}

// -----------------------------
// POST actions (Asignar / Estado / Canalizar PRO)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion   = $_POST['accion'] ?? '';
    $ticketId = (int)($_POST['ticket_id'] ?? 0);

    if ($ticketId <= 0) {
        $_SESSION['flash_err'] = "Ticket inválido.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // Verifica que el ticket sea del área del admin
    $stmtCheck = $pdo->prepare("SELECT id, area, asignado_a, problema, estado FROM tickets WHERE id = :id AND area = :area LIMIT 1");
    $stmtCheck->execute([':id' => $ticketId, ':area' => $areaAdmin]);
    $ticketBase = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$ticketBase) {
        $_SESSION['flash_err'] = "No tienes permiso para modificar este ticket.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // 1) Asignar / Reasignar
    if ($accion === 'asignar') {
        $analystId = (int)($_POST['analyst_id'] ?? 0);
        $motivo    = trim($_POST['motivo'] ?? '');
        if ($analystId <= 0) {
            $_SESSION['flash_err'] = "Selecciona un analista válido.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        // No permitir asignar si ya está cerrado (opcional)
        if (in_array(($ticketBase['estado'] ?? ''), ['cerrado'], true)) {
            $_SESSION['flash_err'] = "No puedes asignar/reasignar un ticket cerrado.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        // Analista rol=3 y misma área
        $stmtA = $pdo->prepare("SELECT id, name, last_name FROM users WHERE id = :id AND rol = 3 AND area = :area LIMIT 1");
        $stmtA->execute([':id' => $analystId, ':area' => $areaAdmin]);
        $analista = $stmtA->fetch(PDO::FETCH_ASSOC);

        if (!$analista) {
            $_SESSION['flash_err'] = "Ese analista no pertenece a tu área.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        $fromAnalyst = (int)($ticketBase['asignado_a'] ?? 0);

        try {
            $pdo->beginTransaction();

            // Update ticket
            $stmtUp = $pdo->prepare("
                UPDATE tickets
                SET asignado_a = :aid,
                    fecha_asignacion = COALESCE(fecha_asignacion, NOW())
                WHERE id = :tid AND area = :area
            ");
            $stmtUp->execute([':aid' => $analystId, ':tid' => $ticketId, ':area' => $areaAdmin]);

            // Log (si ya creaste ticket_assignments_log)
            $stmtLog = $pdo->prepare("
                INSERT INTO ticket_assignments_log (ticket_id, from_analyst_id, to_analyst_id, admin_id, motivo)
                VALUES (:tid, :from_id, :to_id, :admin_id, :motivo)
            ");
            $stmtLog->execute([
                ':tid'      => $ticketId,
                ':from_id'  => ($fromAnalyst > 0 ? $fromAnalyst : null),
                ':to_id'    => $analystId,
                ':admin_id' => $userId,
                ':motivo'   => ($motivo !== '' ? mb_substr($motivo, 0, 255) : null),
            ]);

            // Notificación al analista destino
            $title = ($fromAnalyst > 0) ? "Ticket reasignado #{$ticketId}" : "Nuevo ticket asignado #{$ticketId}";
            $body  = "Problema: " . (string)($ticketBase['problema'] ?? '');
            if ($motivo !== '') $body .= " | Motivo: {$motivo}";
            $link  = "/HelpDesk_EQF/modules/dashboard/analyst/analyst.php?open_ticket={$ticketId}";

            $stmtNotif = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, body, link, is_read)
                VALUES (:user_id, :type, :title, :body, :link, 0)
            ");
            $stmtNotif->execute([
                ':user_id' => $analystId,
                ':type'    => 'ticket_assigned',
                ':title'   => $title,
                ':body'    => mb_substr($body, 0, 255),
                ':link'    => $link
            ]);

            $pdo->commit();

            $nombreA = trim(($analista['name'] ?? '').' '.($analista['last_name'] ?? ''));
            $_SESSION['flash_ok'] = ($fromAnalyst > 0)
                ? "Ticket #{$ticketId} reasignado a {$nombreA}."
                : "Ticket #{$ticketId} asignado a {$nombreA}.";

            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_err'] = "Error al asignar: " . $e->getMessage();
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }
    }

    // 2) Cambiar estado
    if ($accion === 'estado') {
        $estado = $_POST['estado'] ?? '';
        $permitidos = ['abierto','en_proceso','resuelto','cerrado'];

        if (!in_array($estado, $permitidos, true)) {
            $_SESSION['flash_err'] = "Estado inválido.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        // Si lo cierras, marca fecha_resolucion (opcional)
        if ($estado === 'resuelto' || $estado === 'cerrado') {
            $stmtUp = $pdo->prepare("
                UPDATE tickets
                SET estado = :estado,
                    fecha_resolucion = COALESCE(fecha_resolucion, NOW())
                WHERE id = :tid AND area = :area
            ");
        } else {
            $stmtUp = $pdo->prepare("
                UPDATE tickets
                SET estado = :estado
                WHERE id = :tid AND area = :area
            ");
        }

        $stmtUp->execute([':estado' => $estado, ':tid' => $ticketId, ':area' => $areaAdmin]);

        $_SESSION['flash_ok'] = "Estado del ticket #{$ticketId} actualizado.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // 3) Canalizar PRO
    if ($accion === 'canalizar') {
        $nuevaArea = trim($_POST['nueva_area'] ?? '');
        $motivo    = trim($_POST['motivo'] ?? '');
        $copiarAdj = isset($_POST['copiar_adjuntos']);

        $areasPermitidas = ['TI','SAP','MKT'];

        if (!in_array($nuevaArea, $areasPermitidas, true)) {
            $_SESSION['flash_err'] = "Área destino inválida.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }
        if ($nuevaArea === $areaAdmin) {
            $_SESSION['flash_err'] = "El ticket ya pertenece a tu área.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }
        if ($motivo !== '' && mb_strlen($motivo) > 255) {
            $motivo = mb_substr($motivo, 0, 255);
        }

        try {
            $pdo->beginTransaction();

            // A) Registro de transferencia
            $stmtIns = $pdo->prepare("
                INSERT INTO ticket_transfers (ticket_id, from_area, to_area, admin_id, motivo, created_at)
                VALUES (:ticket_id, :from_area, :to_area, :admin_id, :motivo, NOW())
            ");
            $stmtIns->execute([
                ':ticket_id' => $ticketId,
                ':from_area' => $areaAdmin,
                ':to_area'   => $nuevaArea,
                ':admin_id'  => $userId,
                ':motivo'    => ($motivo !== '' ? $motivo : null),
            ]);
            $transferId = (int)$pdo->lastInsertId();

            // B) Copiar historial del chat a ticket_transfer_messages
            $stmtMsg = $pdo->prepare("
                SELECT tm.sender_role,
                       CONCAT(COALESCE(u.name,''),' ',COALESCE(u.last_name,'')) AS sender_name,
                       tm.mensaje AS message,
                       tm.created_at
                FROM ticket_messages tm
                LEFT JOIN users u ON u.id = tm.sender_id
                WHERE tm.ticket_id = :ticket_id
                ORDER BY tm.created_at ASC
            ");
            $stmtMsg->execute([':ticket_id' => $ticketId]);
            $msgs = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($msgs)) {
                $stmtMsgIns = $pdo->prepare("
                    INSERT INTO ticket_transfer_messages
                    (transfer_id, ticket_id, sender_role, sender_name, message, created_at)
                    VALUES
                    (:transfer_id, :ticket_id, :sender_role, :sender_name, :message, :created_at)
                ");
                foreach ($msgs as $m) {
                    $stmtMsgIns->execute([
                        ':transfer_id' => $transferId,
                        ':ticket_id'   => $ticketId,
                        ':sender_role' => $m['sender_role'] ?? 'usuario',
                        ':sender_name' => (trim((string)($m['sender_name'] ?? '')) !== '' ? trim($m['sender_name']) : null),
                        ':message'     => $m['message'] ?? null,
                        ':created_at'  => $m['created_at'] ?? null,
                    ]);
                }
            }

            // C) Copiar adjuntos (ticket_attachments) -> ticket_transfer_files
            if ($copiarAdj) {
                $stmtFiles = $pdo->prepare("
                    SELECT nombre_archivo, ruta_archivo, tipo, subido_en
                    FROM ticket_attachments
                    WHERE ticket_id = :ticket_id
                    ORDER BY subido_en ASC
                ");
                $stmtFiles->execute([':ticket_id' => $ticketId]);
                $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($files)) {
                    $stmtFileIns = $pdo->prepare("
                        INSERT INTO ticket_transfer_files
                        (transfer_id, ticket_id, file_name, file_path, mime_type, created_at)
                        VALUES
                        (:transfer_id, :ticket_id, :file_name, :file_path, :mime_type, :created_at)
                    ");
                    foreach ($files as $f) {
                        $stmtFileIns->execute([
                            ':transfer_id' => $transferId,
                            ':ticket_id'   => $ticketId,
                            ':file_name'   => $f['nombre_archivo'] ?? 'archivo',
                            ':file_path'   => $f['ruta_archivo'] ?? '',
                            ':mime_type'   => $f['tipo'] ?? null,
                            ':created_at'  => $f['subido_en'] ?? date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            // D) Actualizar ticket (mover área + trazabilidad en tickets)
            $stmtUp = $pdo->prepare("
                UPDATE tickets
                SET area = :nueva_area,
                    asignado_a = NULL,
                    estado = 'abierto',
                    transferred_from_area = :from_area,
                    transferred_by = :by_admin,
                    transferred_at = NOW()
                WHERE id = :tid
                  AND area = :area_actual
            ");
            $stmtUp->execute([
                ':nueva_area'  => $nuevaArea,
                ':from_area'   => $areaAdmin,
                ':by_admin'    => $userId,
                ':tid'         => $ticketId,
                ':area_actual' => $areaAdmin,
            ]);

            // E) NOTIFICAR a Admin + Analistas del área destino (rol 2 y 3)
            $stmtUsers = $pdo->prepare("
                SELECT id
                FROM users
                WHERE area = :area
                  AND rol IN (2,3)
            ");
            $stmtUsers->execute([':area' => $nuevaArea]);
            $destinatarios = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

            if ($destinatarios) {
                $link  = '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto';
                $title = 'Ticket canalizado';
                $body  = "Ticket #{$ticketId} canalizado a tu área ({$nuevaArea}).";
                if (!empty($motivo)) $body .= " Motivo: {$motivo}";

                $stmtNotif = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, body, link, is_read)
                    VALUES (:user_id, :type, :title, :body, :link, 0)
                ");

                foreach ($destinatarios as $uid) {
                    $stmtNotif->execute([
                        ':user_id' => (int)$uid,
                        ':type'    => 'ticket_transfer',
                        ':title'   => $title,
                        ':body'    => mb_substr($body, 0, 255),
                        ':link'    => $link
                    ]);
                }
            }

            $pdo->commit();

            $_SESSION['flash_ok'] = "Ticket #{$ticketId} canalizado a {$nuevaArea} (PRO) y se guardó trazabilidad.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_err'] = "Error al canalizar: " . $e->getMessage();
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }
    }

    // Acción desconocida
    $_SESSION['flash_err'] = "Acción inválida.";
    header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
    exit;
}

// -----------------------------
// Analistas del área (para asignar)
// -----------------------------
$stmtAnalysts = $pdo->prepare("
  SELECT id, name, last_name
  FROM users
  WHERE rol = 3 AND area = :area
  ORDER BY last_name ASC, name ASC
");
$stmtAnalysts->execute([':area' => $areaAdmin]);
$areaAnalysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------
// Filtros (GET)
// -----------------------------
$estadoFiltro    = $_GET['estado']    ?? 'todos';
$prioridadFiltro = $_GET['prioridad'] ?? 'todas';
$soloSinAnalista = isset($_GET['sin_asignar']);

// Query base
$sql = "
    SELECT 
        t.id,
        t.sap,
        t.nombre,
        t.email,
        t.problema,
        t.descripcion,
        t.fecha_envio,
        t.estado,
        t.prioridad,
        t.asignado_a,
        a.name      AS analyst_name,
        a.last_name AS analyst_last
    FROM tickets t
    LEFT JOIN users a ON a.id = t.asignado_a AND a.rol = 3
    WHERE t.area = :areaX
";
$params = [':areaX' => $areaAdmin];

if ($estadoFiltro !== '' && $estadoFiltro !== 'todos') {
    $sql .= " AND t.estado = :estadoX";
    $params[':estadoX'] = $estadoFiltro;
}

if ($prioridadFiltro !== '' && $prioridadFiltro !== 'todas') {
    $sql .= " AND t.prioridad = :prioridadX";
    $params[':prioridadX'] = $prioridadFiltro;
}

if ($soloSinAnalista) {
    $sql .= " AND (t.asignado_a IS NULL OR t.asignado_a = 0)";
}

$sql .= " ORDER BY t.fecha_envio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tickets | Mesa de Ayuda EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body class="user-body">

<main class="user-main">
  <section class="user-main-inner">
    <header class="user-main-header">
      <div>
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">
          Tickets de mi área – <?php echo h($areaAdmin); ?>
        </p>
      </div>
    </header>

    <section class="user-main-content">

      <?php if ($mensajeExito): ?>
        <div class="alert alert-success"><?php echo h($mensajeExito); ?></div>
      <?php endif; ?>
      <?php if ($mensajeError): ?>
        <div class="alert alert-danger"><?php echo h($mensajeError); ?></div>
      <?php endif; ?>

      <form method="get" class="user-filters-row">
        <div class="form-group">
          <label for="estado">Estado</label>
          <select name="estado" id="estado">
            <?php
            $estados = [
              'todos'      => 'Todos',
              'abierto'    => 'Abierto',
              'en_proceso' => 'En proceso',
              'resuelto'   => 'Resuelto',
              'cerrado'    => 'Cerrado',
            ];
            foreach ($estados as $value => $label): ?>
              <option value="<?php echo h($value); ?>" <?php if ($estadoFiltro === $value) echo 'selected'; ?>>
                <?php echo h($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="prioridad">Prioridad</label>
          <select name="prioridad" id="prioridad">
            <?php
            $prioridades = [
              'todas' => 'Todas',
              'baja'  => 'Baja',
              'media' => 'Media',
              'alta'  => 'Alta',
            ];
            foreach ($prioridades as $value => $label): ?>
              <option value="<?php echo h($value); ?>" <?php if ($prioridadFiltro === $value) echo 'selected'; ?>>
                <?php echo h($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group form-group-inline">
          <label>
            <input type="checkbox" name="sin_asignar" value="1" <?php if ($soloSinAnalista) echo 'checked'; ?>>
            Sin analista
          </label>
        </div>

        <button type="submit" class="btn-primary">Aplicar</button>
      </form>

      <div class="user-tickets-table-wrapper">
        <table id="adminTicketsAreaTable" class="data-table display">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Usuario</th>
              <th>Problema</th>
              <th>Prioridad</th>
              <th>Estatus</th>
              <th>Analista</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tickets as $t): ?>
            <?php $hasAnalyst = ((int)($t['asignado_a'] ?? 0) > 0); ?>
            <tr data-ticket-id="<?php echo (int)$t['id']; ?>" data-analyst-id="<?php echo (int)($t['asignado_a'] ?? 0); ?>">
              <td><?php echo (int)$t['id']; ?></td>
              <td><?php echo h($t['fecha_envio'] ?? ''); ?></td>
              <td>
                <?php echo h(trim(($t['sap'] ?? '') . ' ' . ($t['nombre'] ?? ''))); ?><br>
                <small><?php echo h($t['email'] ?? ''); ?></small>
              </td>
              <td><?php echo h(problemaLabel((string)$t['problema'])); ?></td>
              <td><?php echo h(prioridadLabel($t['prioridad'] ?? null)); ?></td>
              <td><?php echo h(estadoLabel($t['estado'] ?? null)); ?></td>
              <td class="td-analyst">
                <?php
                if (!empty($t['analyst_name'])) {
                  echo h($t['analyst_name'] . ' ' . $t['analyst_last']);
                } else {
                  echo 'Sin asignar';
                }
                ?>
              </td>
              <td style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="btn-main-combined"
                  onclick="openAssignModal(<?php echo (int)$t['id']; ?>, <?php echo (int)($t['asignado_a'] ?? 0); ?>)"
                  style="width:95px; height:35px;">
                  <?php echo $hasAnalyst ? 'Reasignar' : 'Asignar'; ?>
                </button>

                <button type="button" class="btn-main-combined"
                  onclick="openStateModal(<?php echo (int)$t['id']; ?>, '<?php echo h($t['estado'] ?? 'abierto'); ?>')"
                  style="width:90px; height:35px;">
                  Estado
                </button>

                <button type="button" class="btn-main-combined"
                  onclick="openCanalizarModal(<?php echo (int)$t['id']; ?>)"
                  style="width:95px; height:35px;">
                  Canalizar
                </button>

                <a class="btn-main-combined"
                   href="/HelpDesk_EQF/modules/dashboard/admin/ticket_pdf.php?id=<?php echo (int)$t['id']; ?>"
                   target="_blank"
                   style="display:inline-flex; align-items:center; justify-content:center; width:70px; height:35px;">
                  PDF
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </section>
  </section>
</main>

<!-- MODAL: Asignar -->
<div class="user-modal-backdrop" id="assignModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2 id="assignModalTitle">Asignar ticket</h2>
      <button type="button" class="user-modal-close" onclick="closeAssignModal()">✕</button>
    </header>

    <form method="POST">
      <input type="hidden" name="accion" value="asignar">
      <input type="hidden" name="ticket_id" id="assign_ticket_id" value="">

      <div class="form-group">
        <label for="assign_analyst_id">Analista</label>
        <select name="analyst_id" id="assign_analyst_id" required>
          <option value="">Selecciona un analista</option>
          <?php foreach ($areaAnalysts as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>">
              <?php echo h(($a['last_name'] ?? '') . ' ' . ($a['name'] ?? '')); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="assign_motivo">Motivo (opcional)</label>
        <input type="text" name="motivo" id="assign_motivo" maxlength="255" placeholder="Ej: carga alta, especialista, seguimiento...">
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; align-items: stretch; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeAssignModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Estado -->
<div class="user-modal-backdrop" id="stateModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Cambiar estado</h2>
      <button type="button" class="user-modal-close" onclick="closeStateModal()">✕</button>
    </header>

    <form method="POST">
      <input type="hidden" name="accion" value="estado">
      <input type="hidden" name="ticket_id" id="state_ticket_id" value="">

      <div class="form-group">
        <label for="state_value">Estado</label>
        <select name="estado" id="state_value" required>
          <option value="abierto">Abierto</option>
          <option value="en_proceso">En proceso</option>
          <option value="resuelto">Resuelto</option>
          <option value="cerrado">Cerrado</option>
        </select>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeStateModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Canalizar -->
<div class="user-modal-backdrop" id="canalizarModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Canalizar ticket</h2>
      <button type="button" class="user-modal-close" onclick="closeCanalizarModal()">✕</button>
    </header>

    <form method="POST">
      <input type="hidden" name="accion" value="canalizar">
      <input type="hidden" name="ticket_id" id="canalizar_ticket_id" value="">

      <div class="form-group">
        <label for="nueva_area">Enviar a área</label>
        <select name="nueva_area" id="nueva_area" required>
          <option value="">Selecciona un área</option>
          <option value="TI">TI</option>
          <option value="SAP">SAP</option>
          <option value="MKT">MKT</option>
        </select>
      </div>

      <div class="form-group">
        <label for="motivo">Motivo (opcional)</label>
        <textarea name="motivo" id="motivo" rows="3" maxlength="255"
          placeholder="Ej: Se requiere apoyo de SAP por error de cierre del día."></textarea>
      </div>

      <div class="form-group form-group-inline">
        <label>
          <input type="checkbox" name="copiar_adjuntos" value="1" checked>
          Copiar adjuntos al traspaso
        </label>
      </div>

      <p class="admin-task-meta" style="margin-top:10px;">
        Al canalizar: el ticket se mueve al área destino, se limpia el analista y se reabre como “abierto”.
      </p>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeCanalizarModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Canalizar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
  $('#adminTicketsAreaTable').DataTable({
    pageLength: 10,
    order: [[1, 'desc']]
  });
});

// Assign modal
function openAssignModal(ticketId, analystId){
  document.getElementById('assign_ticket_id').value = ticketId;
  document.getElementById('assign_analyst_id').value = (analystId && analystId > 0) ? String(analystId) : "";
  document.getElementById('assign_motivo').value = "";

  const title = document.getElementById('assignModalTitle');
  title.textContent = (analystId && analystId > 0) ? ('Reasignar ticket #' + ticketId) : ('Asignar ticket #' + ticketId);

  document.getElementById('assignModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeAssignModal(){
  document.getElementById('assignModal').style.display = 'none';
  document.body.style.overflow = '';
}

// State modal
function openStateModal(ticketId, estado){
  document.getElementById('state_ticket_id').value = ticketId;
  document.getElementById('state_value').value = estado || 'abierto';
  document.getElementById('stateModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeStateModal(){
  document.getElementById('stateModal').style.display = 'none';
  document.body.style.overflow = '';
}

// Canalizar modal
function openCanalizarModal(ticketId){
  document.getElementById('canalizar_ticket_id').value = ticketId;
  document.getElementById('nueva_area').value = "";
  document.getElementById('motivo').value = "";
  document.getElementById('canalizarModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeCanalizarModal(){
  document.getElementById('canalizarModal').style.display = 'none';
  document.body.style.overflow = '';
}

// cerrar al click fuera
document.addEventListener('click', function(e){
  const a = document.getElementById('assignModal');
  const s = document.getElementById('stateModal');
  const c = document.getElementById('canalizarModal');

  if (a && e.target === a) closeAssignModal();
  if (s && e.target === s) closeStateModal();
  if (c && e.target === c) closeCanalizarModal();
});
</script>

<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>
<?php include __DIR__ . '/../../../template/footer.php'; ?>
</body>
</html>
