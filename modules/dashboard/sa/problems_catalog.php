<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'catalog_problems';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}
if ((int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php');
  exit;
}

$pdo = Database::getConnection();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Alerts */
$alerts = [];
if (isset($_GET['created'])) $alerts[] = ['type'=>'success','icon'=>'capsulin_add.png','text'=>'PROBLEMA REGISTRADO'];
if (isset($_GET['updated'])) $alerts[] = ['type'=>'info','icon'=>'capsulin_update.png','text'=>'PROBLEMA ACTUALIZADO'];
if (isset($_GET['toggled'])) $alerts[] = ['type'=>'info','icon'=>'capsulin_update.png','text'=>'PROBLEMA ACTUALIZADO'];
if (isset($_GET['error']))   $alerts[] = ['type'=>'danger','icon'=>'capsulin_delete.png','text'=>'OCURRIÓ UN ERROR'];

/* Áreas activas (si no existe el catálogo de áreas, fallback TI/SAP/MKT) */
$areas = [];
try {
  $stA = $pdo->prepare("SELECT code, label FROM catalog_areas WHERE active=1 ORDER BY sort_order ASC, id ASC");
  $stA->execute();
  $areas = $stA->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $areas = [
    ['code'=>'TI','label'=>'TI'],
    ['code'=>'SAP','label'=>'SAP'],
    ['code'=>'MKT','label'=>'MKT'],
  ];
}

/* Filtro por área (opcional) */
$areaFilter = trim($_GET['area'] ?? 'ALL');

$params = [];
$where = "";
if ($areaFilter !== '' && $areaFilter !== 'ALL') {
  $where = "WHERE area_code = ?";
  $params[] = $areaFilter;
}

$st = $pdo->prepare("
  SELECT id, area_code, code, label, active, sort_order, created_at
  FROM catalog_problems
  $where
  ORDER BY area_code ASC, sort_order ASC, id ASC
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<?php if (!empty($alerts)): $a = $alerts[0]; ?>
  <div id="eqf-alert-container">
    <div class="eqf-alert eqf-alert-<?=h($a['type'])?>">
      <img class="eqf-alert-icon"
           src="/HelpDesk_EQF/assets/img/icons/<?=h($a['icon'])?>"
           alt="alert">
      <div class="eqf-alert-text"><?=h($a['text'])?></div>
    </div>
  </div>
<?php endif; ?>

<main class="user-main sa-panel">

  <header class="panel-top">
    <div class="panel-top-left">
      <span>Catálogo </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
      <p class="panel-subtitle">Problemas por Área (Super Admin)</p>
    </div>
  </header>

  <section class="panel-card">
    <div class="panel-card-head">
      <h2>Problemas registrados</h2>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <!-- filtro por área -->
        <form method="GET" style="display:flex; gap:8px; align-items:center;">
          <select name="area" class="ticket-status-select" style="color:#111827; background:#f3f4f6;">
            <option value="ALL" <?= $areaFilter==='ALL'?'selected':''; ?>>Todas</option>
            <?php foreach($areas as $ar): ?>
              <option value="<?=h($ar['code'])?>" <?= $areaFilter===$ar['code']?'selected':''; ?>>
                <?=h($ar['label'])?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-login" style="width:auto; padding:8px 14px;">Filtrar</button>
        </form>

        <div class="directory-actions" style="gap:10px;">
          <button type="button" class="action-btn action-add" title="Agregar" onclick="openModal('modal-create')">
            <img src="/HelpDesk_EQF/assets/img/icons/icon_add.png" class="action-icon" alt="Agregar">
          </button>
          <button type="button" class="action-btn action-edit" title="Editar" onclick="openEditModal()">
            <img src="/HelpDesk_EQF/assets/img/icons/icon_update.png" class="action-icon" alt="Editar">
          </button>
          <button type="button" class="action-btn action-delete" title="Activar/Desactivar" onclick="toggleProblem()">
            <img src="/HelpDesk_EQF/assets/img/icons/icon_delete.png" class="action-icon" alt="Toggle">
          </button>
        </div>
      </div>
    </div>

    <div class="panel-table-wrap" style="margin-top:12px;">
      <table class="panel-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Área</th>
            <th>Code</th>
            <th>Problema</th>
            <th>Orden</th>
            <th>Activo</th>
            <th>Creado</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="7" class="panel-empty">No hay problemas.</td></tr>
        <?php else: foreach($rows as $r): ?>
          <tr class="catalog-row"
              data-id="<?= (int)$r['id'] ?>"
              data-area="<?= h($r['area_code']) ?>"
              data-code="<?= h($r['code']) ?>"
              data-label="<?= h($r['label']) ?>"
              data-sort="<?= (int)$r['sort_order'] ?>"
              data-active="<?= (int)$r['active'] ?>"
          >
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= h($r['area_code']) ?></td>
            <td><?= h($r['code']) ?></td>
            <td><?= h($r['label']) ?></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td><?= ((int)$r['active']===1) ? 'Sí' : 'No' ?></td>
            <td><?= h($r['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <p class="panel-help" style="margin-top:10px;">
      Selecciona una fila para editar o activar/desactivar.
    </p>
  </section>

  <!-- MODAL CREATE -->
  <div class="modal-backdrop" id="modal-create">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Agregar problema</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-create')">✕</button>
      </div>
      <p class="modal-description">
        Se recomienda que <b>code</b> sea corto (sin espacios). Ej: <b>impresora</b>, <b>sap_login</b>.
      </p>

      <form class="modal-form" method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/problems_catalog_actions.php">
        <input type="hidden" name="action" value="create">

        <div class="modal-grid">
          <div class="form-group">
            <label>Área</label>
            <select name="area_code" required>
              <option value="">Selecciona...</option>
              <?php foreach($areas as $ar): ?>
                <option value="<?=h($ar['code'])?>"><?=h($ar['label'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Code</label>
            <input type="text" name="code" placeholder="impresora" required>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Problema (texto)</label>
            <input type="text" name="label" placeholder="Falla de impresora" required>
          </div>

          <div class="form-group">
            <label>Orden</label>
            <input type="number" name="sort_order" value="50" min="0" required>
          </div>

          <div class="form-group">
            <label>Activo</label>
            <select name="active" required>
              <option value="1" selected>Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modal-create')">Cancelar</button>
          <button type="submit" class="btn-login" style="width:auto;">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL EDIT -->
  <div class="modal-backdrop" id="modal-edit">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Editar problema</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-edit')">✕</button>
      </div>
      <p class="modal-description">Edita el problema seleccionado.</p>

      <form class="modal-form" method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/problems_catalog_actions.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit_id">

        <div class="modal-grid">
          <div class="form-group">
            <label>Área</label>
            <select name="area_code" id="edit_area" required>
              <?php foreach($areas as $ar): ?>
                <option value="<?=h($ar['code'])?>"><?=h($ar['label'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Code</label>
            <input type="text" name="code" id="edit_code" required>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Problema (texto)</label>
            <input type="text" name="label" id="edit_label" required>
          </div>

          <div class="form-group">
            <label>Orden</label>
            <input type="number" name="sort_order" id="edit_sort" min="0" required>
          </div>

          <div class="form-group">
            <label>Activo</label>
            <select name="active" id="edit_active" required>
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modal-edit')">Cancelar</button>
          <button type="submit" class="btn-login" style="width:auto;">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TOGGLE FORM (hidden) -->
  <form id="toggleForm" method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/problems_catalog_actions.php" style="display:none;">
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="id" id="toggle_id">
  </form>

</main>

<script>
function openModal(id){ document.getElementById(id)?.classList.add('show'); }
function closeModal(id){ document.getElementById(id)?.classList.remove('show'); }

let selected = null;
document.querySelectorAll('.catalog-row').forEach(tr=>{
  tr.addEventListener('click', ()=>{
    document.querySelectorAll('.catalog-row').forEach(x=>x.classList.remove('row-selected'));
    tr.classList.add('row-selected');
    selected = tr;
  });
});

function needRow(){
  if(!selected){ alert('Selecciona una fila primero.'); return false; }
  return true;
}

function openEditModal(){
  if(!needRow()) return;

  document.getElementById('edit_id').value = selected.dataset.id;
  document.getElementById('edit_area').value = selected.dataset.area;
  document.getElementById('edit_code').value = selected.dataset.code;
  document.getElementById('edit_label').value = selected.dataset.label;
  document.getElementById('edit_sort').value = selected.dataset.sort;
  document.getElementById('edit_active').value = selected.dataset.active;

  openModal('modal-edit');
}

function toggleProblem(){
  if(!needRow()) return;
  document.getElementById('toggle_id').value = selected.dataset.id;

  const isActive = selected.dataset.active === '1';
  const msg = isActive ? '¿Desactivar este problema? (no se borra)' : '¿Activar este problema?';
  if(confirm(msg)) document.getElementById('toggleForm').submit();
}
</script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
