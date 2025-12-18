<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'auditoria';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ((int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php'); exit;
}

$pdo = Database::getConnection();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$action = trim($_GET['action'] ?? '');
$entity = trim($_GET['entity'] ?? '');
$q      = trim($_GET['q'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

$where = [];
$params = [];

if ($action !== '') { $where[] = "action = ?"; $params[] = $action; }
if ($entity !== '') { $where[] = "entity = ?"; $params[] = $entity; }

if ($q !== '') {
  $where[] = "(actor_name LIKE ? OR actor_email LIKE ? OR ip_address LIKE ? OR CAST(entity_id AS CHAR) LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}

if ($from !== '') { $where[] = "created_at >= ?"; $params[] = $from . " 00:00:00"; }
if ($to !== '')   { $where[] = "created_at <= ?"; $params[] = $to . " 23:59:59"; }

$sql = "SELECT id, created_at, actor_name, actor_email, actor_rol, actor_area,
               action, entity, entity_id, ip_address, user_agent, details
        FROM audit_log";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY id DESC LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// combos para filtros (opcional pero útil)
$actions = $pdo->query("SELECT action, COUNT(*) c FROM audit_log GROUP BY action ORDER BY action")->fetchAll(PDO::FETCH_ASSOC);
$entities = $pdo->query("SELECT entity, COUNT(*) c FROM audit_log WHERE entity IS NOT NULL GROUP BY entity ORDER BY entity")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<main class="user-main sa-panel">
  <header class="panel-top">
    <div class="panel-top-left">
      <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
      <p class="panel-subtitle">Auditoría</p>
    </div>
  </header>

  <section class="panel-card" style="margin-bottom:18px;">
    <div class="panel-card-head">
      <h2>Filtros</h2>
      <span class="panel-date">Últimos 200 eventos</span>
    </div>

    <form method="GET" style="display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:10px; align-items:end;">
      <div>
        <label style="font-size:.8rem;color:#6b7280;font-weight:600;">Acción</label>
        <select name="action" style="width:100%; border:1px solid var(--eqf-border); border-radius:12px; padding:10px;">
          <option value="">Todas</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?=h($a['action'])?>" <?= $action===$a['action']?'selected':'' ?>>
              <?=h($a['action'])?> (<?= (int)$a['c'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size:.8rem;color:#6b7280;font-weight:600;">Entidad</label>
        <select name="entity" style="width:100%; border:1px solid var(--eqf-border); border-radius:12px; padding:10px;">
          <option value="">Todas</option>
          <?php foreach ($entities as $e): ?>
            <option value="<?=h($e['entity'])?>" <?= $entity===$e['entity']?'selected':'' ?>>
              <?=h($e['entity'])?> (<?= (int)$e['c'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size:.8rem;color:#6b7280;font-weight:600;">Buscar</label>
        <input name="q" value="<?=h($q)?>" placeholder="Nombre / correo / IP / ID"
               style="width:100%; border:1px solid var(--eqf-border); border-radius:12px; padding:10px;">
      </div>

      <div>
        <label style="font-size:.8rem;color:#6b7280;font-weight:600;">Desde</label>
        <input type="date" name="from" value="<?=h($from)?>"
               style="width:100%; border:1px solid var(--eqf-border); border-radius:12px; padding:10px;">
      </div>

      <div>
        <label style="font-size:.8rem;color:#6b7280;font-weight:600;">Hasta</label>
        <input type="date" name="to" value="<?=h($to)?>"
               style="width:100%; border:1px solid var(--eqf-border); border-radius:12px; padding:10px;">
      </div>

      <div style="display:flex; gap:8px;">
        <button class="btn-login" type="submit" style="width:auto;">Aplicar</button>
        <a class="btn-secondary" href="auditoria.php" style="width:auto; text-decoration:none; display:inline-flex; align-items:center;">Limpiar</a>
      </div>
    </form>
  </section>

  <section class="panel-card">
    <div class="panel-card-head">
      <h2>Eventos</h2>
      <span class="panel-date"><?= count($rows) ?> mostrados</span>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Actor</th>
            <th>Acción</th>
            <th>Entidad</th>
            <th>ID</th>
            <th>IP</th>
            <th>Detalles</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="8" class="panel-empty">Sin eventos (todavía).</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h($r['created_at']) ?></td>
                <td>
                  <div style="font-weight:700;"><?= h($r['actor_name'] ?? '-') ?></div>
                  <div class="panel-muted" style="font-size:.8rem;">
                    <?= h($r['actor_email'] ?? '-') ?> • Rol <?= h($r['actor_rol'] ?? '-') ?> • <?= h($r['actor_area'] ?? '-') ?>
                  </div>
                </td>
                <td style="font-weight:700;"><?= h($r['action']) ?></td>
                <td><?= h($r['entity'] ?? '-') ?></td>
                <td><?= h($r['entity_id'] ?? '-') ?></td>
                <td><?= h($r['ip_address'] ?? '-') ?></td>
                <td>
                  <?php
                    $d = $r['details'];
                    if ($d) {
                      $pretty = json_encode(json_decode($d, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                      echo '<pre style="margin:0; font-size:.75rem; white-space:pre-wrap;">'.h($pretty).'</pre>';
                    } else {
                      echo '<span class="panel-muted">-</span>';
                    }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
