<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo       = Database::getConnection();
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$areaAdmin = $_SESSION['user_area'] ?? '';

// -----------------------------
// Flash messages (PRG)
// -----------------------------
$mensajeExito = $_SESSION['flash_ok'] ?? '';
$mensajeError = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

// -----------------------------
// POST actions (Asignar / Estado / Canalizar)
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
    $stmtCheck = $pdo->prepare("SELECT id FROM tickets WHERE id = :id AND area = :area LIMIT 1");
    $stmtCheck->execute([':id' => $ticketId, ':area' => $areaAdmin]);
    if (!$stmtCheck->fetchColumn()) {
        $_SESSION['flash_err'] = "No tienes permiso para modificar este ticket.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // 1) Asignar / Reasignar
    if ($accion === 'asignar') {
        $analystId = (int)($_POST['analyst_id'] ?? 0);
        if ($analystId <= 0) {
            $_SESSION['flash_err'] = "Selecciona un analista válido.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        // Verifica que ese analista sea rol=3 y del área del admin
        $stmtA = $pdo->prepare("SELECT id FROM users WHERE id = :id AND rol = 3 AND area = :area LIMIT 1");
        $stmtA->execute([':id' => $analystId, ':area' => $areaAdmin]);
        if (!$stmtA->fetchColumn()) {
            $_SESSION['flash_err'] = "Ese analista no pertenece a tu área.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE tickets SET asignado_a = :aid WHERE id = :tid AND area = :area");
        $stmtUp->execute([':aid' => $analystId, ':tid' => $ticketId, ':area' => $areaAdmin]);

        $_SESSION['flash_ok'] = "Ticket #{$ticketId} asignado correctamente.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // 2) Cambiar estado
    if ($accion === 'estado') {
        $estado = $_POST['estado'] ?? '';
        $permitidos = ['abierto','en_proceso','en_espera','vencido','cerrado'];

        if (!in_array($estado, $permitidos, true)) {
            $_SESSION['flash_err'] = "Estado inválido.";
            header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE tickets SET estado = :estado WHERE id = :tid AND area = :area");
        $stmtUp->execute([':estado' => $estado, ':tid' => $ticketId, ':area' => $areaAdmin]);

        $_SESSION['flash_ok'] = "Estado del ticket #{$ticketId} actualizado.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // 3) Canalizar (mover ticket de área)
    if ($accion === 'canalizar') {
        $nuevaArea = trim($_POST['nueva_area'] ?? '');

        // Ajusta a lo que realmente guardas en BD
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

        $stmtUp = $pdo->prepare("
            UPDATE tickets
            SET area = :nueva_area,
                asignado_a = NULL,
                estado = 'abierto'
            WHERE id = :tid
              AND area = :area_actual
        ");
        $stmtUp->execute([
            ':nueva_area'  => $nuevaArea,
            ':tid'         => $ticketId,
            ':area_actual' => $areaAdmin
        ]);

        $_SESSION['flash_ok'] = "Ticket #{$ticketId} canalizado a {$nuevaArea}.";
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/tickets_area.php');
        exit;
    }

    // Si llegó aquí, no coincidió ninguna acción
    $_SESSION['flash_err'] = "Acción no válida.";
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
$analysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

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

// -----------------------------
// Helpers
// -----------------------------
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
        'alta'      => 'Alta',
        'media'     => 'Media',
        'baja'      => 'Baja',
        'critica', 'crítica' => 'Crítica',
        default     => ucfirst($p),
    };
}
function estadoLabel(?string $e): string {
    $e = strtolower($e ?? '');
    return match ($e) {
        'abierto'      => 'Abierto',
        'en_proceso'   => 'En proceso',
        'en_espera'    => 'En espera',
        'vencido'      => 'Vencido',
        'cerrado'      => 'Cerrado',
        default        => ucfirst($e),
    };
}

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
                    Tickets de mi área – <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </header>

        <section class="user-main-content">

            <?php if ($mensajeExito): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></div>
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
                            'cerrado'    => 'Cerrado',
                        ];
                        foreach ($estados as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if ($estadoFiltro === $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prioridad">Prioridad</label>
                    <select name="prioridad" id="prioridad">
                        <?php
                        $prioridades = [
                            'todas'   => 'Todas',
                            'media'   => 'Media',
                            'alta'    => 'Alta',
                        ];
                        foreach ($prioridades as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if ($prioridadFiltro === $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
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
                        <tr>
                            <td><?php echo (int)$t['id']; ?></td>
                            <td><?php echo htmlspecialchars($t['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php echo htmlspecialchars(trim(($t['sap'] ?? '') . ' ' . ($t['nombre'] ?? '')), ENT_QUOTES, 'UTF-8'); ?><br>
                                <small><?php echo htmlspecialchars($t['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(prioridadLabel($t['prioridad']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(estadoLabel($t['estado']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php
                                if (!empty($t['analyst_name'])) {
                                    echo htmlspecialchars($t['analyst_name'] . ' ' . $t['analyst_last'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo 'Sin asignar';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn-main-combined"
                                    onclick="openAssignModal(<?php echo (int)$t['id']; ?>, <?php echo (int)($t['asignado_a'] ?? 0); ?>)" style="width: 80px; height: 35px;">
                                    Asignar
                                </button>

                                <button type="button" class="btn-main-combined"
                                    onclick="openStateModal(<?php echo (int)$t['id']; ?>, '<?php echo htmlspecialchars($t['estado'] ?? 'abierto', ENT_QUOTES, 'UTF-8'); ?>')" style="width: 80px; height: 35px;">
                                    Estado
                                </button>

                                <button type="button" class="btn-main-combined"
                                    onclick="openCanalizarModal(<?php echo (int)$t['id']; ?>)" style="width: 90px; height: 35px;">
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
      <h2>Asignar ticket</h2>
      <button type="button" class="user-modal-close" onclick="closeAssignModal()">✕</button>
    </header>

    <form method="POST">
      <input type="hidden" name="accion" value="asignar">
      <input type="hidden" name="ticket_id" id="assign_ticket_id" value="">

      <div class="form-group">
        <label for="assign_analyst_id">Analista</label>
        <select name="analyst_id" id="assign_analyst_id" required>
          <option value="">Selecciona un analista</option>
          <?php foreach ($analysts as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>">
              <?php echo htmlspecialchars($a['name'].' '.$a['last_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
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
          <option value="en_espera">En espera</option>
          <option value="vencido">Vencido</option>
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

  const sel = document.getElementById('assign_analyst_id');
  sel.value = (analystId && analystId > 0) ? String(analystId) : "";

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
