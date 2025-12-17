<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'catalogs';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ((int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php'); exit;
}

$pdo = Database::getConnection();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$tab = $_GET['tab'] ?? 'areas';
$allowedTabs = ['areas','status','priorities','problems'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'areas';

/* alerts */
$alerts = [];
if (isset($_GET['created'])) $alerts[] = ['type'=>'success','icon'=>'capsulin_add.png','text'=>'REGISTRO CREADO'];
if (isset($_GET['updated'])) $alerts[] = ['type'=>'info','icon'=>'capsulin_update.png','text'=>'REGISTRO ACTUALIZADO'];
if (isset($_GET['toggled'])) $alerts[] = ['type'=>'info','icon'=>'capsulin_update.png','text'=>'ESTADO ACTUALIZADO'];
if (isset($_GET['error']))   $alerts[] = ['type'=>'danger','icon'=>'capsulin_delete.png','text'=>'OCURRIÓ UN ERROR'];

/* ===== Data fetch según tab ===== */
$rows = [];
$areas = []; // para el tab de problems

try {
  // áreas activas para selects
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

try {
  if ($tab === 'areas') {
    $st = $pdo->prepare("SELECT id, code, label, sort_order, active, created_at
                         FROM catalog_areas ORDER BY sort_order ASC, id ASC");
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }
  elseif ($tab === 'status') {
    $st = $pdo->prepare("SELECT id, code, label, sort_order, active, created_at
                         FROM catalog_status ORDER BY sort_order ASC, id ASC");
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }
  elseif ($tab === 'priorities') {
    $st = $pdo->prepare("SELECT id, code, label, sla_hours, sort_order, active, created_at
                         FROM catalog_priorities ORDER BY sort_order ASC, id ASC");
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }
  elseif ($tab === 'problems') {
    $st = $pdo->prepare("SELECT id, area_code, code, label, sort_order, active, created_at
                         FROM catalog_problems ORDER BY area_code ASC, sort_order ASC, id ASC");
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }
} catch(Throwable $e) {
  $rows = [];
  $alerts[] = ['type'=>'danger','icon'=>'capsulin_delete.png','text'=>'NO SE PUDO LEER EL CATÁLOGO (REVISA TABLA)'];
}

/* helpers UI */
function tabClass($current, $t){ return $current===$t ? 'chip-filter chip-active' : 'chip-filter'; }
?>
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<?php if (!empty($alerts)): $a = $alerts[0]; ?>
  <div id="eqf-alert-container">
    <div class="eqf-alert eqf-alert-<?=h($a['type'])?>">
      <img class="eqf-alert-icon" src="/HelpDesk_EQF/assets/img/icons/<?=h($a['icon'])?>" alt="alert">
      <div class="eqf-alert-text"><?=h($a['text'])?></div>
    </div>
  </div>
<?php endif; ?>

<main class="user-main sa-panel">

  <header class="panel-top">
    <div class="panel-top-left">
      <span>Catálogos </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
      <p class="panel-subtitle">Centro de catálogos (Super Admin)</p>
    </div>
  </header>

  <!-- Tabs -->
  <section class="panel-card" style="margin-bottom:16px;">
    <div class="directory-filter-chips" style="margin:0;">
      <a class="<?=h(tabClass($tab,'areas'))?>" href="?tab=areas">Áreas</a>
      <a class="<?=h(tabClass($tab,'status'))?>" href="?tab=status">Estatus</a>
      <a class="<?=h(tabClass($tab,'priorities'))?>" href="?tab=priorities">Prioridades</a>
      <a class="<?=h(tabClass($tab,'problems'))?>" href="?tab=problems">Problemas</a>
    </div>
  </section>

  <!-- Table + actions -->
  <section class="panel-card">
    <div class="panel-card-head">
      <h2>
        <?php
          echo match($tab){
            'areas' => 'Catálogo de Áreas',
            'status' => 'Catálogo de Estatus',
            'priorities' => 'Catálogo de Prioridades',
            'problems' => 'Catálogo de Problemas',
            default => 'Catálogo'
          };
        ?>
      </h2>

      <div class="directory-actions" style="gap:10px;">
        <button type="button" class="action-btn action-add" title="Agregar" onclick="openCreate()">
          <img src="/HelpDesk_EQF/assets/img/icons/icon_add.png" class="action-icon" alt="Agregar">
        </button>
        <button type="button" class="action-btn action-edit" title="Editar" onclick="openEdit()">
          <img src="/HelpDesk_EQF/assets/img/icons/icon_update.png" class="action-icon" alt="Editar">
        </button>
        <button type="button" class="action-btn action-delete" title="Activar/Desactivar" onclick="toggleRow()">
          <img src="/HelpDesk_EQF/assets/img/icons/icon_delete.png" class="action-icon" alt="Toggle">
        </button>
      </div>
    </div>

    <div class="panel-table-wrap" style="margin-top:12px;">
      <table class="panel-table">
        <thead>
          <tr>
            <?php if($tab==='problems'): ?>
              <th>ID</th><th>Área</th><th>Code</th><th>Nombre</th><th>Orden</th><th>Activo</th><th>Creado</th>
            <?php elseif($tab==='priorities'): ?>
              <th>ID</th><th>Code</th><th>Nombre</th><th>SLA (hrs)</th><th>Orden</th><th>Activo</th><th>Creado</th>
            <?php else: ?>
              <th>ID</th><th>Code</th><th>Nombre</th><th>Orden</th><th>Activo</th><th>Creado</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="7" class="panel-empty">Sin registros.</td></tr>
        <?php else: foreach($rows as $r): ?>
          <tr class="catalog-row"
              data-id="<?= (int)$r['id'] ?>"
              data-tab="<?= h($tab) ?>"
              data-code="<?= h($r['code'] ?? '') ?>"
              data-label="<?= h($r['label'] ?? '') ?>"
              data-sort="<?= (int)($r['sort_order'] ?? 0) ?>"
              data-active="<?= (int)($r['active'] ?? 1) ?>"
              data-area="<?= h($r['area_code'] ?? '') ?>"
              data-sla="<?= h($r['sla_hours'] ?? '') ?>"
          >
            <?php if($tab==='problems'): ?>
              <td>#<?= (int)$r['id'] ?></td>
              <td><?= h($r['area_code']) ?></td>
              <td><?= h($r['code']) ?></td>
              <td><?= h($r['label']) ?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td><?= ((int)$r['active']===1)?'Sí':'No' ?></td>
              <td><?= h($r['created_at']) ?></td>
            <?php elseif($tab==='priorities'): ?>
              <td>#<?= (int)$r['id'] ?></td>
              <td><?= h($r['code']) ?></td>
              <td><?= h($r['label']) ?></td>
              <td><?= h($r['sla_hours']) ?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td><?= ((int)$r['active']===1)?'Sí':'No' ?></td>
              <td><?= h($r['created_at']) ?></td>
            <?php else: ?>
              <td>#<?= (int)$r['id'] ?></td>
              <td><?= h($r['code']) ?></td>
              <td><?= h($r['label']) ?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td><?= ((int)$r['active']===1)?'Sí':'No' ?></td>
              <td><?= h($r['created_at']) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- MODAL (Create/Edit) único -->
  <div class="modal-backdrop" id="modal-cat">
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="modalTitle">Nuevo registro</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-cat')">✕</button>
      </div>

      <form class="modal-form" method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/catalogs_actions.php">
        <input type="hidden" name="action" id="f_action" value="create">
        <input type="hidden" name="tab" value="<?=h($tab)?>">
        <input type="hidden" name="id" id="f_id" value="">

        <div class="modal-grid">
          <?php if($tab==='problems'): ?>
            <div class="form-group">
              <label>Área</label>
              <select name="area_code" id="f_area" required>
                <option value="">Selecciona...</option>
                <?php foreach($areas as $ar): ?>
                  <option value="<?=h($ar['code'])?>"><?=h($ar['label'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Code</label>
            <input type="text" name="code" id="f_code" required>
          </div>

          <div class="form-group" <?= $tab==='problems' ? 'style="grid-column:1/-1;"':'' ?>>
            <label>Nombre</label>
            <input type="text" name="label" id="f_label" required>
          </div>

          <?php if($tab==='priorities'): ?>
            <div class="form-group">
              <label>SLA (horas)</label>
              <input type="number" name="sla_hours" id="f_sla" min="0" step="1" required>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Orden</label>
            <input type="number" name="sort_order" id="f_sort" min="0" required>
          </div>

          <div class="form-group">
            <label>Activo</label>
            <select name="active" id="f_active" required>
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modal-cat')">Cancelar</button>
          <button type="submit" class="btn-login" style="width:auto;">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TOGGLE hidden -->
  <form id="toggleForm" method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/catalogs_actions.php" style="display:none;">
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="tab" value="<?=h($tab)?>">
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

function openCreate(){
  document.getElementById('modalTitle').textContent = 'Nuevo registro';
  document.getElementById('f_action').value = 'create';
  document.getElementById('f_id').value = '';
  document.getElementById('f_code').value = '';
  document.getElementById('f_label').value = '';
  document.getElementById('f_sort').value = 50;
  document.getElementById('f_active').value = 1;

  const fArea = document.getElementById('f_area');
  if (fArea) fArea.value = '';

  const fSla = document.getElementById('f_sla');
  if (fSla) fSla.value = 0;

  openModal('modal-cat');
}

function openEdit(){
  if(!selected){ alert('Selecciona una fila primero.'); return; }

  document.getElementById('modalTitle').textContent = 'Editar registro';
  document.getElementById('f_action').value = 'update';
  document.getElementById('f_id').value = selected.dataset.id;

  document.getElementById('f_code').value = selected.dataset.code || '';
  document.getElementById('f_label').value = selected.dataset.label || '';
  document.getElementById('f_sort').value = selected.dataset.sort || 0;
  document.getElementById('f_active').value = selected.dataset.active || 1;

  const fArea = document.getElementById('f_area');
  if (fArea) fArea.value = selected.dataset.area || '';

  const fSla = document.getElementById('f_sla');
  if (fSla) fSla.value = selected.dataset.sla || 0;

  openModal('modal-cat');
}

function toggleRow(){
  if(!selected){ alert('Selecciona una fila primero.'); return; }
  document.getElementById('toggle_id').value = selected.dataset.id;
  if(confirm('¿Activar/Desactivar este registro?')) {
    document.getElementById('toggleForm').submit();
  }
}
</script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
