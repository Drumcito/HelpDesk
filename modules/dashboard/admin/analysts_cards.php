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

date_default_timezone_set('America/Mexico_City');

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function shiftLabel(string $shift): string {
    return match ($shift) {
        '8_1730' => '08:00 – 17:30',
        '9_1830' => '09:00 – 18:30',
        default  => $shift,
    };
}

function satPatternLabel(string $p): string {
    return match ($p) {
        '1y3' => '1° y 3° sábado',
        '2y4' => '2° y 4° sábado',
        'all' => 'Todos',
        default => $p,
    };
}

function getWeekShiftTimes(string $shift): array {
    return match ($shift) {
        '9_1830' => ['09:00:00', '18:30:00'],
        default  => ['08:00:00', '17:30:00'],
    };
}

function getSaturdayTimes(): array {
    return ['08:00:00', '14:00:00'];
}

function nthSaturdayOfMonth(DateTime $d): int {
    $day = (int)$d->format('j');
    return (int)floor(($day - 1) / 7) + 1;
}

function isSaturdayWorking(DateTime $now, string $satPattern): bool {
    if ($now->format('N') != 6) return false;

    $nth = nthSaturdayOfMonth($now);
    if ($nth === 5) return true;

    return match ($satPattern) {
        '1y3' => in_array($nth, [1,3], true),
        '2y4' => in_array($nth, [2,4], true),
        'all' => true,
        default => in_array($nth, [1,3], true),
    };
}

function isWorkingNow(array $a, DateTime $now): bool {
    $dow = (int)$now->format('N'); // 1..7
    if ($dow === 7) return false;

    $t = $now->format('H:i:s');

    if ($dow >= 1 && $dow <= 5) {
        [$start, $end] = getWeekShiftTimes((string)$a['shift']);
        return ($t >= $start && $t <= $end);
    }

    if ($dow === 6) {
        if (!isSaturdayWorking($now, (string)$a['sat_pattern'])) return false;
        [$start, $end] = getSaturdayTimes();
        return ($t >= $start && $t <= $end);
    }

    return false;
}

function isLunchNow(array $a, DateTime $now): bool {
    if ((int)$now->format('N') === 6) return false;

    $ls = $a['lunch_start'] ?? null;
    $le = $a['lunch_end'] ?? null;
    if (!$ls || !$le) return false;

    $t = $now->format('H:i:s');

    if (!isWorkingNow($a, $now)) return false;

    return ($t >= $ls && $t <= $le);
}


function resolveAvailability(array $a, DateTime $now): array {
    // 1) Override por rango
    $ovStatus = strtoupper(trim((string)($a['ov_status'] ?? 'AUTO')));
    $ovStart  = $a['ov_start'] ?? null;
    $ovEnd    = $a['ov_end'] ?? null;

    if ($ovStatus !== '' && $ovStatus !== 'AUTO' && $ovStart && $ovEnd) {
        try {
            $s = new DateTime($ovStart);
            $e = new DateTime($ovEnd);
            if ($now >= $s && $now <= $e) {
                return ['mode' => 'OVERRIDE', 'status' => $ovStatus, 'start' => $ovStart, 'end' => $ovEnd];
            }
        } catch (Throwable $e) {}
    }

    // 2) AUTO por horario
    if (!isWorkingNow($a, $now)) {
        return ['mode' => 'AUTO', 'status' => 'FUERA_DE_HORARIO'];
    }
    if (isLunchNow($a, $now)) {
        return ['mode' => 'AUTO', 'status' => 'EN_COMIDA'];
    }
    return ['mode' => 'AUTO', 'status' => 'DISPONIBLE'];
}

// =====================
// Query analistas del área + schedule + override
// OJO: la tabla correcta es analyst_status_override (singular)
// =====================
$sql = "
SELECT
  u.id,
  u.name,
  u.last_name,
  u.email,
  u.area,

  COALESCE(s.shift, '8_1730')      AS shift,
  COALESCE(s.sat_pattern, '1y3')  AS sat_pattern,
  s.lunch_start,
  s.lunch_end,

  COALESCE(o.status, 'AUTO')      AS ov_status,
  o.starts_at                     AS ov_start,
  o.ends_at                       AS ov_end

FROM users u
LEFT JOIN analyst_schedules s
  ON s.user_id = u.id
LEFT JOIN analyst_status_overrides o
  ON o.user_id = u.id
WHERE u.rol = 3
  AND u.area = :area
ORDER BY u.last_name ASC, u.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':area' => $adminArea]);
$analysts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Analistas | Admin EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">

  <style>
    .cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:14px}
    .analyst-card{background:#fff;border-radius:18px;padding:14px 14px 12px;box-shadow:0 10px 25px rgba(0,0,0,.08)}
    .analyst-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .analyst-name{font-weight:800}
    .analyst-email{font-size:12px;opacity:.75}
    .analyst-meta{margin-top:10px;font-size:13px;line-height:1.35}
    .badge{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800}
    .b-ok{background:#e8fff1}
    .b-warn{background:#fff4db}
    .b-bad{background:#ffe6e6}
    .b-gray{background:#eff2f6}
    .btn-row{display:flex;gap:8px;margin-top:12px}
    .btn-mini{border:0;border-radius:12px;padding:9px 10px;font-weight:800;cursor:pointer}
    .btn-mini.primary{background:var(--eqf-combined);color:#fff}
    .btn-mini.secondary{background:#eff2f6}
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:9999}
    .modal-card{width:min(560px,92vw);background:#fff;border-radius:18px;padding:14px}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .modal-close{border:0;background:transparent;font-size:20px;cursor:pointer}
    .form-row{display:flex;gap:10px}
    .field{flex:1}
    .field label{display:block;font-weight:800;font-size:12px;margin-bottom:6px}
    .field input,.field select{width:100%;padding:10px 12px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#111}
    .field select option{color:#111}
    .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:12px}
    .hint{font-size:12px;opacity:.75;margin-top:6px}
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
        <p class="user-main-subtitle">Analistas – Área: <?php echo h($adminArea); ?></p>
      </div>
    </header>

    <section class="user-main-content">

      <div class="cards-grid">
        <?php foreach ($analysts as $a): ?>
          <?php
            $av = resolveAvailability($a, $now);

            $badgeClass = 'b-gray';
            $badgeText  = 'Auto';
            $extraTxt   = '';

            // Estado final (badge)
            if ($av['status'] === 'DISPONIBLE') {
                $badgeClass='b-ok'; $badgeText='Disponible';
            } elseif ($av['status'] === 'EN_COMIDA') {
                $badgeClass='b-warn'; $badgeText='En comida';
            } elseif ($av['status'] === 'FUERA_DE_HORARIO') {
                $badgeClass='b-gray'; $badgeText='Fuera de horario';
            } else {
                // OVERRIDE (vacaciones, sucursal, etc.)
                $badgeClass='b-bad';
                $map = [
                    'VACACIONES' => 'Vacaciones',
                    'INCAPACIDAD'=> 'Incapacidad',
                    'PERMISO'    => 'Permiso',
                    'SUCURSAL'   => 'Sucursal (campo)',
                    'NO_DISPONIBLE' => 'No disponible',
                    'DISPONIBLE' => 'Disponible (manual)',
                ];
                $badgeText = $map[$av['status']] ?? ucfirst(strtolower($av['status']));

                if (!empty($av['start']) && !empty($av['end'])) {
                    $extraTxt = 'Desde: ' . h($av['start']) . '<br>Hasta: ' . h($av['end']);
                }
            }

            $shiftLbl = shiftLabel((string)$a['shift']);
            $satLbl   = satPatternLabel((string)$a['sat_pattern']);

            $lunchTxt = '';
            if (!empty($a['lunch_start']) && !empty($a['lunch_end'])) {
                $lunchTxt = 'Horario de comida: <strong>' . h(substr($a['lunch_start'],0,5)) . '–' . h(substr($a['lunch_end'],0,5)) . '</strong><br>';
            }
          ?>
          <div class="analyst-card" data-user-id="<?php echo (int)$a['id']; ?>">
            <div class="analyst-top">
              <div>
                <div class="analyst-name"><?php echo h($a['name'].' '.$a['last_name']); ?></div>
                <div class="analyst-email"><?php echo h($a['email']); ?></div>
              </div>
              <div class="badge <?php echo $badgeClass; ?>" id="badge-<?php echo (int)$a['id']; ?>">
                <?php echo h($badgeText); ?>
              </div>
            </div>

            <div class="analyst-meta">
              <?php echo $lunchTxt; ?>
              <br>Turno (L-V): <strong><?php echo h($shiftLbl); ?></strong><br><br>
              Sábados: <strong><?php echo h($satLbl); ?></strong><br>

              <div id="extra-<?php echo (int)$a['id']; ?>" style="opacity:.9;margin-top:6px; <?php echo $extraTxt ? '' : 'display:none;'; ?>">
                <?php echo $extraTxt; ?>
              </div>
            </div>

            <div class="btn-row">
              <button class="btn-mini secondary"
                type="button"
                onclick="openScheduleModal(
                  <?php echo (int)$a['id']; ?>,
                  '<?php echo h($a['shift']); ?>',
                  '<?php echo h($a['sat_pattern']); ?>',
                  '<?php echo h($a['lunch_start'] ?? ''); ?>',
                  '<?php echo h($a['lunch_end'] ?? ''); ?>'
                )"
              >
                Editar horario
              </button>

              <button class="btn-mini primary"
                type="button"
                onclick="openOverrideModal(
                  <?php echo (int)$a['id']; ?>,
                  '<?php echo h($a['name'].' '.$a['last_name']); ?>'
                )"
              >
                Ausencia
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </section>
  </section>
</main>

<!-- MODAL: HORARIO -->
<div class="modal-backdrop" id="scheduleModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>Editar horario</h3>
      <button class="modal-close" type="button" onclick="closeScheduleModal()">✕</button>
    </div>

    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/admin/save_schedule.php">
      <input type="hidden" name="user_id" id="sch_user_id">

      <div class="form-row">
        <div class="field">
          <label>Turno (L-V)</label>
          <select name="shift" id="sch_shift" required>
            <option value="8_1730">08:00 – 17:30</option>
            <option value="9_1830">09:00 – 18:30</option>
          </select>
          <div class="hint">Sábado siempre es 08:00–14:00</div>
        </div>

        <div class="field">
          <label>Sábados</label>
          <select name="sat_pattern" id="sch_sat" required>
            <option value="1y3">1° y 3° sábado</option>
            <option value="2y4">2° y 4° sábado</option>
            <option value="all">Todos (incluye 5°)</option>
          </select>
        </div>
      </div>

      <div class="form-row" style="margin-top:10px;">
        <div class="field">
          <label>Comida inicio</label>
          <input type="time" name="lunch_start" id="sch_lunch_start">
        </div>
        <div class="field">
          <label>Comida fin</label>
          <input type="time" name="lunch_end" id="sch_lunch_end">
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn-mini secondary" type="button" onclick="closeScheduleModal()">Cancelar</button>
        <button class="btn-mini primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: OVERRIDE / AUSENCIA -->
<div class="modal-backdrop" id="overrideModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="ov_title">Ausencia</h3>
      <button class="modal-close" type="button" onclick="closeOverrideModal()">✕</button>
    </div>

    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/admin/save_override.php">
      <input type="hidden" name="user_id" id="ov_user_id">

      <div class="form-row">
        <div class="field">
          <label>Estado</label>
          <select name="status" id="ov_status" required>
            <option value="DISPONIBLE">Disponible</option>
            <option value="NO_DISPONIBLE">No disponible</option>
            <option value="VACACIONES">Vacaciones</option>
            <option value="INCAPACIDAD">Incapacidad</option>
            <option value="PERMISO">Permiso</option>
            <option value="SUCURSAL">Sucursal</option>
          </select>
        </div>

        <div class="field">
          <label>Desde</label>
          <input type="datetime-local" name="starts_at" id="ov_start" required>
        </div>

        <div class="field">
          <label>Hasta</label>
          <input type="datetime-local" name="ends_at" id="ov_end" required>
      </div>
      </div>

      <div class="modal-actions">
        <button class="btn-secondary" type="button" onclick="closeOverrideModal()">Cancelar</button>
        <button class="btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function openScheduleModal(userId, shift, sat, lunchStart, lunchEnd){
  document.getElementById('sch_user_id').value = userId;
  document.getElementById('sch_shift').value = shift || '8_1730';
  document.getElementById('sch_sat').value = sat || '1y3';

  document.getElementById('sch_lunch_start').value = (lunchStart || '').toString().slice(0,5);
  document.getElementById('sch_lunch_end').value   = (lunchEnd || '').toString().slice(0,5);

  document.getElementById('scheduleModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeScheduleModal(){
  document.getElementById('scheduleModal').style.display = 'none';
  document.body.style.overflow = '';
}

function openOverrideModal(userId, fullName){
  document.getElementById('ov_user_id').value = userId;
  document.getElementById('ov_title').textContent = 'Ausencia / Estado – ' + (fullName || '');
  document.getElementById('ov_status').value = 'AUTO';

  const now = new Date();
  const end = new Date(Date.now() + 4*60*60*1000);
  const pad = n => String(n).padStart(2,'0');
  const toLocal = (d) => (
    d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes())
  );

  document.getElementById('ov_start').value = toLocal(now);
  document.getElementById('ov_end').value   = toLocal(end);

  document.getElementById('overrideModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeOverrideModal(){
  document.getElementById('overrideModal').style.display = 'none';
  document.body.style.overflow = '';
}

// click afuera
document.addEventListener('click', (e) => {
  const s = document.getElementById('scheduleModal');
  const o = document.getElementById('overrideModal');
  if (s && e.target === s) closeScheduleModal();
  if (o && e.target === o) closeOverrideModal();
});
</script>

<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>
<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script>
function updateAvailabilityUI(items){
  if (!Array.isArray(items)) return;

  items.forEach(it => {
    const badge = document.getElementById('badge-' + it.id);
    const extra = document.getElementById('extra-' + it.id);

    if (badge) {
      badge.classList.remove('b-ok','b-warn','b-gray','b-bad');
      if (it.badge_class) badge.classList.add(it.badge_class);
      badge.textContent = it.badge_text || '';
    }

    if (extra) {
      if (it.extra_html && it.extra_html.trim() !== '') {
        extra.innerHTML = it.extra_html;
        extra.style.display = '';
      } else {
        extra.innerHTML = '';
        extra.style.display = 'none';
      }
    }
  });
}

function pollAvailability(){
  fetch('/HelpDesk_EQF/modules/dashboard/admin/availability_data.php', { cache: 'no-store' })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.ok) return;
      updateAvailabilityUI(data.data);
    })
    .catch(err => console.error('availability poll error:', err));
}

document.addEventListener('DOMContentLoaded', () => {
  pollAvailability();
  setInterval(pollAvailability, 10000); // 10s
});
</script>

</body>
</html>
