<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();

$adminArea = trim($_SESSION['user_area'] ?? '');

// Trae analistas del área + su schedule + override
$stmt = $pdo->prepare("
  SELECT
    u.id,
    u.name,
    u.last_name,
    u.email,
    u.area,
    COALESCE(s.shift, '8_1730') AS shift,
    COALESCE(s.sat_pattern, '1y3') AS sat_pattern,
    COALESCE(o.status, 'AUTO') AS ov_status,
    o.until_at AS ov_until
  FROM users u
  LEFT JOIN analyst_schedules s ON s.user_id = u.id
  LEFT JOIN analyst_status_overrides o ON o.user_id = u.id
  WHERE u.rol = 3 AND u.area = :area
  ORDER BY u.last_name ASC, u.name ASC
");
$stmt->execute([':area' => $adminArea]);
$analysts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Helpers (AUTO disponibilidad) ----------
date_default_timezone_set('America/Mexico_City');

function monthHasFiveSaturdays(DateTime $dt): bool {
  $y = (int)$dt->format('Y');
  $m = (int)$dt->format('m');
  $first = new DateTime("$y-$m-01");
  // sábado = 6 (1=lunes..7=domingo en ISO), en PHP: N (1-7)
  $firstSat = clone $first;
  while ((int)$firstSat->format('N') !== 6) $firstSat->modify('+1 day');

  $sats = 0;
  $cur = clone $firstSat;
  while ((int)$cur->format('m') === $m) {
    $sats++;
    $cur->modify('+7 day');
  }
  return $sats >= 5;
}

function nthSaturdayOfMonth(DateTime $dt): int {
  // si no es sábado, devuelve 0
  if ((int)$dt->format('N') !== 6) return 0;

  $y = (int)$dt->format('Y');
  $m = (int)$dt->format('m');
  $first = new DateTime("$y-$m-01");
  $firstSat = clone $first;
  while ((int)$firstSat->format('N') !== 6) $firstSat->modify('+1 day');

  $diffDays = (int)$firstSat->diff($dt)->days;
  return (int)floor($diffDays / 7) + 1; // 1..5
}

function isWorkingNow(array $a, DateTime $now): bool {
  $shift = $a['shift'] ?? '8_1730';
  $satPattern = $a['sat_pattern'] ?? '1y3';

  // horarios
  if ($shift === '9_1830') {
    $start = '09:00';
    $end   = '18:30';
  } else {
    $start = '08:00';
    $end   = '17:30';
  }

  $todayN = (int)$now->format('N'); // 1..7
  $time   = $now->format('H:i');

  // Lun-Vie
  if ($todayN >= 1 && $todayN <= 5) {
    return ($time >= $start && $time <= $end);
  }

  // Sábado
  if ($todayN === 6) {
    // si el mes tiene 5 sábados -> trabajan todos (regla)
    if (monthHasFiveSaturdays($now)) {
      return ($time >= $start && $time <= $end);
    }

    $nth = nthSaturdayOfMonth($now); // 1..4 normalmente
    $ok = false;

    if ($satPattern === 'todos') $ok = true;
    elseif ($satPattern === 'ninguno') $ok = false;
    elseif ($satPattern === '1y3') $ok = in_array($nth, [1,3], true);
    elseif ($satPattern === '2y4') $ok = in_array($nth, [2,4], true);

    return $ok && ($time >= $start && $time <= $end);
  }

  // Domingo nunca
  return false;
}

function resolveAvailability(array $a, DateTime $now): array {
  // Override válido?
  $ov = $a['ov_status'] ?? 'AUTO';
  $until = $a['ov_until'] ?? null;

  if ($ov !== 'AUTO') {
    if ($until) {
      $u = new DateTime($until);
      if ($now <= $u) {
        return ['mode' => 'OVERRIDE', 'status' => $ov];
      }
      // expiró override -> caer a AUTO
    } else {
      return ['mode' => 'OVERRIDE', 'status' => $ov];
    }
  }

  $auto = isWorkingNow($a, $now) ? 'DISPONIBLE' : 'FUERA_DE_HORARIO';
  return ['mode' => 'AUTO', 'status' => $auto];
}

// ---------- KPIs por analista (activos/abiertos/en_proceso) ----------
$ids = array_map(fn($x) => (int)$x['id'], $analysts);
$kpis = [];
if ($ids) {
  $in = implode(',', array_fill(0, count($ids), '?'));

  $stmtK = $pdo->prepare("
    SELECT
      asignado_a AS analyst_id,
      SUM(estado='abierto') AS abiertos,
      SUM(estado='en_proceso') AS en_proceso,
      SUM(estado IN ('abierto','en_proceso')) AS activos
    FROM tickets
    WHERE asignado_a IN ($in)
    GROUP BY asignado_a
  ");
  $stmtK->execute($ids);
  foreach ($stmtK->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $kpis[(int)$r['analyst_id']] = $r;
  }
}

$now = new DateTime();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Analistas | Admin</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
  <style>
    .analyst-cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:14px;}
    .analyst-card{background:#fff;border-radius:18px;padding:14px 14px;box-shadow:0 12px 28px rgba(0,0,0,.08);}
    .analyst-card-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;}
    .analyst-name{font-weight:800;font-size:15px;line-height:1.1}
    .analyst-mail{font-size:12px;opacity:.75;margin-top:4px}
    .analyst-badge{font-size:12px;font-weight:800;padding:6px 10px;border-radius:999px;white-space:nowrap}
    .b-ok{background:#eaf7ef;color:#166534}
    .b-off{background:#eef2ff;color:#1e3a8a}
    .b-warn{background:#fff7ed;color:#9a3412}
    .b-busy{background:#fee2e2;color:#991b1b}
    .analyst-kpis{display:flex;gap:10px;margin-top:12px}
    .analyst-kpi{flex:1;background:#f5f6fb;border-radius:14px;padding:10px;text-align:center}
    .analyst-kpi .v{font-size:18px;font-weight:900}
    .analyst-kpi .l{font-size:11px;opacity:.75}
    .analyst-meta{margin-top:10px;font-size:12px;opacity:.8}
    .analyst-actions{display:flex;gap:10px;margin-top:12px}
    .analyst-actions button{flex:1}
  </style>
</head>
<body class="user-body">

<main class="user-main">
  <section class="user-main-inner">
    <header class="user-main-header">
      <div>
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">Analistas del área – <?php echo htmlspecialchars($adminArea, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
    </header>

    <section class="user-main-content">
      <div class="user-info-card">
        <h2>Disponibilidad y carga</h2>
        <p>Vista rápida por analista (AUTO por horario + override manual).</p>
      </div>

      <div class="analyst-cards-grid">
        <?php foreach ($analysts as $a):
          $id = (int)$a['id'];
          $av = resolveAvailability($a, $now);

          $k = $kpis[$id] ?? ['abiertos'=>0,'en_proceso'=>0,'activos'=>0];

          $badgeClass = 'b-off';
          $badgeText  = 'Fuera de horario';

          if ($av['status'] === 'DISPONIBLE') { $badgeClass='b-ok'; $badgeText='Disponible'; }
          elseif ($av['status'] === 'OCUPADO') { $badgeClass='b-busy'; $badgeText='Ocupado'; }
          elseif ($av['status'] === 'AUSENTE') { $badgeClass='b-warn'; $badgeText='Ausente'; }
          elseif ($av['status'] === 'VACACIONES') { $badgeClass='b-warn'; $badgeText='Vacaciones'; }
          elseif ($av['status'] === 'FUERA_DE_HORARIO') { $badgeClass='b-off'; $badgeText='Fuera de horario'; }

          $shiftLabel = ($a['shift']==='9_1830') ? '09:00–18:30' : '08:00–17:30';
          $satLabel = match($a['sat_pattern']){
            '1y3' => 'Sábados 1 y 3',
            '2y4' => 'Sábados 2 y 4',
            'todos' => 'Todos los sábados',
            'ninguno' => 'Sin sábados',
            default => 'Sábados'
          };
        ?>
          <div class="analyst-card">
            <div class="analyst-card-top">
              <div>
                <div class="analyst-name"><?php echo htmlspecialchars($a['name'].' '.$a['last_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="analyst-mail"><?php echo htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <span class="analyst-badge <?php echo $badgeClass; ?>">
                <?php echo $badgeText; ?>
              </span>
            </div>

            <div class="analyst-kpis">
              <div class="analyst-kpi">
                <div class="v"><?php echo (int)$k['activos']; ?></div>
                <div class="l">Activos</div>
              </div>
              <div class="analyst-kpi">
                <div class="v"><?php echo (int)$k['abiertos']; ?></div>
                <div class="l">Abiertos</div>
              </div>
              <div class="analyst-kpi">
                <div class="v"><?php echo (int)$k['en_proceso']; ?></div>
                <div class="l">En proceso</div>
              </div>
            </div>

            <div class="analyst-meta">
              Turno: <strong><?php echo $shiftLabel; ?></strong><br>
              <?php echo $satLabel; ?> (si hay 5 sábados: todos).
            </div>

            <div class="analyst-actions">
              <button class="btn-main-combined" type="button" onclick="openScheduleModal(<?php echo $id; ?>,'<?php echo $a['shift']; ?>','<?php echo $a['sat_pattern']; ?>')">Horario</button>
              <button class="btn-main-combined" type="button" onclick="openOverrideModal(<?php echo $id; ?>,'<?php echo $a['ov_status']; ?>')">Override</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </section>
  </section>
</main>

<!-- MODAL: Horario -->
<div class="user-modal-backdrop" id="scheduleModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Editar horario</h2>
      <button type="button" class="user-modal-close" onclick="closeScheduleModal()">✕</button>
    </header>

    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/admin/save_schedule.php">
      <input type="hidden" name="user_id" id="sch_user_id" value="">

      <div class="form-group">
        <label>Turno</label>
        <select name="shift" id="sch_shift" required>
          <option value="8_1730">08:00 – 17:30</option>
          <option value="9_1830">09:00 – 18:30</option>
        </select>
      </div>

      <div class="form-group">
        <label>Patrón de sábados</label>
        <select name="sat_pattern" id="sch_sat" required>
          <option value="1y3">1º y 3º</option>
          <option value="2y4">2º y 4º</option>
          <option value="todos">Todos</option>
          <option value="ninguno">Ninguno</option>
        </select>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeScheduleModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Override -->
<div class="user-modal-backdrop" id="overrideModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Override de disponibilidad</h2>
      <button type="button" class="user-modal-close" onclick="closeOverrideModal()">✕</button>
    </header>

    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/admin/save_override.php">
      <input type="hidden" name="user_id" id="ov_user_id" value="">

      <div class="form-group">
        <label>Estado</label>
        <select name="status" id="ov_status" required>
          <option value="AUTO">AUTO (por horario)</option>
          <option value="DISPONIBLE">Disponible</option>
          <option value="OCUPADO">Ocupado</option>
          <option value="AUSENTE">Ausente</option>
          <option value="SUCURSAL">Sucursal</option>
          <option value="VACACIONES">Vacaciones</option>
        </select>
      </div>

      <div class="form-group">
        <label>Hasta (opcional)</label>
        <input type="datetime-local" name="until_at" id="ov_until">
      </div>

      <div class="form-group">
        <label>Nota (opcional)</label>
        <input type="text" name="note" maxlength="255" placeholder="Ej: comida, visita, incapacidad...">
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeOverrideModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function openScheduleModal(userId, shift, sat){
  document.getElementById('sch_user_id').value = userId;
  document.getElementById('sch_shift').value = shift || '8_1730';
  document.getElementById('sch_sat').value = sat || '1y3';
  document.getElementById('scheduleModal').style.display = 'flex';
  document.body.style.overflow='hidden';
}
function closeScheduleModal(){
  document.getElementById('scheduleModal').style.display = 'none';
  document.body.style.overflow='';
}

function openOverrideModal(userId, status){
  document.getElementById('ov_user_id').value = userId;
  document.getElementById('ov_status').value = status || 'AUTO';
  document.getElementById('overrideModal').style.display = 'flex';
  document.body.style.overflow='hidden';
}
function closeOverrideModal(){
  document.getElementById('overrideModal').style.display = 'none';
  document.body.style.overflow='';
}
</script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
</body>
</html>
