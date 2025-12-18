<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'documents';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if ($rol !== 1) {
    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php');
    exit;
}

$pdo = Database::getConnection();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$visList = [
  'ALL' => 'Todos',
  'TI' => 'TI',
  'SAP' => 'SAP',
  'MKT' => 'MKT',
  'SUCURSAL' => 'Sucursales',
  'CORPORATIVO' => 'Corporativo'
];

// filtros
$q = trim($_GET['q'] ?? '');
$v = strtoupper(trim($_GET['v'] ?? 'ALL'));
if (!isset($visList[$v])) $v = 'ALL';

$params = [];
$where = [];

if ($q !== '') {
    $where[] = "(display_name LIKE :q OR original_name LIKE :q)";
    $params[':q'] = "%{$q}%";
}

if ($v !== 'ALL') {
    $where[] = "visibility = :v";
    $params[':v'] = $v;
}

$sql = "SELECT id, display_name, original_name, mime_type, size_bytes, visibility, created_at
        FROM documents";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);
$docs = $st->fetchAll(PDO::FETCH_ASSOC);

$flash = trim($_GET['ok'] ?? '');
$err   = trim($_GET['err'] ?? '');
?>

<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<main class="user-main sa-panel">
  <header class="panel-top">
    <div class="panel-top-left">
      <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
      <p class="panel-subtitle">Documentos</p>
    </div>
  </header>

  <?php if ($flash): ?>
    <section class="panel-card panel-alert" style="border-left-color: var(--eqf-green); background: rgba(30,138,79,.08);">
      <strong><?= h($flash) ?></strong>
    </section>
    <div style="height:12px;"></div>
  <?php endif; ?>

  <?php if ($err): ?>
    <section class="panel-card panel-alert" style="border-left-color: var(--eqf-red); background: rgba(200,0,45,.08);">
      <strong><?= h($err) ?></strong>
    </section>
    <div style="height:12px;"></div>
  <?php endif; ?>

  <section class="panel-card">
    <div class="panel-card-head" style="gap:12px; flex-wrap:wrap;">
      <h2>Gestión de documentos</h2>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
          <input
            type="text"
            name="q"
            value="<?= h($q) ?>"
            placeholder="Buscar documento..."
            style="border:1px solid var(--eqf-border); border-radius:999px; padding:8px 12px; background:#f9fafb; outline:none; font-size:.9rem;"
          >

          <select
            name="v"
            style="border:1px solid var(--eqf-border); border-radius:12px; padding:8px 10px; background:#f9fafb; outline:none; font-size:.9rem;"
          >
            <?php foreach($visList as $key=>$label): ?>
              <option value="<?= h($key) ?>" <?= $v===$key?'selected':'' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="btn-primary" style="padding:8px 16px;">Filtrar</button>
        </form>

        <button type="button" class="btn-primary" style="padding:8px 16px;" onclick="openModal('modal-upload-doc')">
          Subir archivo
        </button>
      </div>
    </div>

    <div class="panel-table-wrap" style="margin-top:12px;">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Visibilidad</th>
            <th>Tipo</th>
            <th>Tamaño</th>
            <th>Fecha</th>
            <th class="ta-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$docs): ?>
            <tr><td colspan="6" class="panel-empty">No hay documentos todavía.</td></tr>
          <?php else: ?>
            <?php foreach($docs as $d): ?>
              <tr>
                <td>
                  <div style="font-weight:700;"><?= h($d['display_name']) ?></div>
                  <div class="panel-muted" style="font-size:.8rem;"><?= h($d['original_name']) ?></div>
                </td>
                <td><?= h($visList[$d['visibility']] ?? $d['visibility']) ?></td>
                <td><?= h($d['mime_type']) ?></td>
                <td><?= h(number_format(((int)$d['size_bytes'])/1024, 1)) ?> KB</td>
                <td><?= h($d['created_at']) ?></td>
                <td class="ta-right"
    style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">

    <a href="/HelpDesk_EQF/modules/dashboard/sa/documents_download.php?id=<?= (int)$d['id'] ?>"
       class="action-link action-download">
        📁
    </a>

    <span style="color:#cbd5e1;">|</span>

    <form method="POST"
          action="/HelpDesk_EQF/modules/dashboard/sa/documents_action.php"
          style="display:inline;"
          onsubmit="return confirm('¿Eliminar este documento?');">

        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">

        <button type="submit" class="action-link action-delete">
            🗑️
        </button> </form>

                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </section>

  <!-- MODAL SUBIR DOC -->
  <div class="modal-backdrop" id="modal-upload-doc">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Subir documento</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-upload-doc')">✕</button>
      </div>



      <form method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/documents_action.php"
            class="modal-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">

        <div class="modal-grid">
          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Seleccionar archivo</label>
            <input type="file" name="file" required>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Nombre por el que aparecerá</label>
            <input type="text" name="display_name" maxlength="180" placeholder="Ej. Manual de cierres SAP" required>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>¿Quién podrá verlo?</label>
            <select name="visibility" required>
              <?php foreach($visList as $key=>$label): ?>
                <option value="<?= h($key) ?>"><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modal-upload-doc')">Cancelar</button>
          <button type="submit" class="btn-login" style="width:auto;">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
