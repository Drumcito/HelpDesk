<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if (!in_array($rol, [1,2,3], true)) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo  = Database::getConnection();
$area = trim($_SESSION['user_area'] ?? '');

// Cargar catálogo de problemas dinámico
if (in_array($rol, [2,3], true) && $area !== '') {
    $stmt = $pdo->prepare("
        SELECT id, area_code, code, label
        FROM catalog_problems
        WHERE active = 1 AND area_code = :area
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([':area' => $area]);
} else {
    // SA ve todos
    $stmt = $pdo->query("
        SELECT id, area_code, code, label
        FROM catalog_problems
        WHERE active = 1
        ORDER BY area_code ASC, sort_order ASC, id ASC
    ");
}
$catalog = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Notas internas | HelpDesk EQF</title>
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
        <p class="user-main-subtitle">Base de conocimiento (Notas internas)</p>
      </div>
    </header>

    <section class="user-main-content">
      <div class="user-info-card">
        <h2>Buscar soluciones</h2>
        <p>Filtra por problema o busca por palabras clave dentro de la descripción.</p>

        <div class="user-filters-row" style="gap:12px;">
          <div class="form-group">
            <label for="fProblema">Problema</label>
            <select id="fProblema">
              <option value="todos">Todos</option>
              <?php foreach($catalog as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>">
                  <?php
                    $txt = $p['label'];
                    if ($rol === 1) $txt = ($p['area_code'] ?? '') . ' · ' . $txt;
                    echo htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
                  ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="min-width:280px;">
            <label for="fQuery">Palabras clave (en descripción)</label>
            <input id="fQuery" type="text" placeholder="Ej: cierre, error 0349, impresora, etc.">
          </div>

          <div class="form-group form-group-inline" style="align-self:flex-end;">
            <button class="btn-primary" type="button" onclick="reloadNotes()">Buscar</button>
          </div>
        </div>
      </div>

      <div class="user-tickets-table-wrapper">
        <table id="notesTable" class="data-table display" style="width:100%;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Problema</th>
              <th>Descripción</th>
              <th>Nota interna</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </section>
  </section>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
let dt = null;

function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function fetchNotes(){
  const problema = document.getElementById('fProblema').value; // id o "todos"
  const q = document.getElementById('fQuery').value.trim();

  const url = '/HelpDesk_EQF/modules/ticket/internal_notes_data.php'
    + '?problema=' + encodeURIComponent(problema)
    + '&q=' + encodeURIComponent(q);

  return fetch(url).then(r => r.json());
}

function reloadNotes(){
  fetchNotes().then(data => {
    if (!data.ok) {
      alert(data.msg || 'No se pudo cargar.');
      return;
    }

    const rows = Array.isArray(data.rows) ? data.rows : [];
    const tbody = document.querySelector('#notesTable tbody');
    tbody.innerHTML = '';

    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>#${escapeHtml(r.id)}</td>
        <td>${escapeHtml(r.nota_fecha || '')}</td>
        <td>${escapeHtml(r.problema_label || '')}</td>
        <td style="max-width:420px; white-space:normal;">${escapeHtml(r.descripcion || '')}</td>
        <td style="max-width:520px; white-space:normal;">${escapeHtml(r.nota_interna || '')}</td>
      `;
      tbody.appendChild(tr);
    });

    if (dt) {
      dt.destroy();
      dt = null;
    }

    dt = $('#notesTable').DataTable({
      pageLength: 10,
      order: [[1, 'desc']]
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  reloadNotes();

  document.getElementById('fQuery').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') reloadNotes();
  });
});
</script>

<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>
<?php include __DIR__ . '/../../../template/footer.php'; ?>
</body>
</html>
