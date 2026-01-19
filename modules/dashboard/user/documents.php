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

$pdo = Database::getConnection();

function h($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Normalizamos "audiencia"
$userAreaRaw = trim((string)($_SESSION['user_area'] ?? ''));
$audience = (stripos($userAreaRaw, 'sucursal') !== false) ? 'SUCURSAL' : 'CORPORATIVO';

// Opcional: si en algunos usuarios guardas TI/SAP/MKT como área real
$userAreaUpper = strtoupper($userAreaRaw);
$extraVisibility = null;
if (in_array($userAreaUpper, ['TI','SAP','MKT'], true)) {
  $extraVisibility = $userAreaUpper;
}

// Construye lista de visibilities permitidas
$allowed = ['ALL', $audience];
if ($extraVisibility) $allowed[] = $extraVisibility;

// Armamos placeholders para IN (...)
$in = implode(',', array_fill(0, count($allowed), '?'));

$stmt = $pdo->prepare("
  SELECT id, display_name, stored_name, original_name, mime_type, size_bytes, visibility, uploaded_by, created_at
  FROM documents
  WHERE visibility IN ($in)
  ORDER BY created_at DESC
  LIMIT 500
");
$stmt->execute($allowed);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Documentos | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
<style>
  /* Hacer el buscador de DataTables ancho completo */
  #docsTable_filter{
    width: 100%;
    margin: 8px 0 14px 0;
  }
  #docsTable_filter label{
    display: block;
    width: 100%;
  }
  #docsTable_filter input{
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
    height: 44px;
    border-radius: 999px;
    padding: 0 18px;
    font-weight: 700;
  }
</style>

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body class="user-body">

<main class="user-main">
  <section class="user-main-inner">

    <header class="user-topbar">
      <div class="user-topbar-left">
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">Documentos</p>
      </div>

    </header>

    <section class="user-main-content">
      <div class="user-info-card">
        <h2>Documentos disponibles</h2>

        <div style="margin-top:12px;">
          <table id="docsTable" class="display" style="width:100%;">
            <thead>
              <tr>
                <th>Documento</th>
                <th style="width:220px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($docs as $d): ?>
                <?php
                  $id = (int)$d['id'];
                  $name = trim((string)($d['display_name'] ?? ''));
                  if ($name === '') $name = trim((string)($d['original_name'] ?? ''));
                  if ($name === '') $name = 'Documento #' . $id;
                ?>
                <tr>
                  <td><?php echo h($name); ?></td>
                  <td>
        <!--  BOTON VER PENDIENTE HASTA SUBIR A HOSTING
                  
                  <a class="task-link-blue"
                       target="_blank"
                       rel="noopener"
                       href="/HelpDesk_EQF/modules/dashboard/user/view_document.php?id=<?php echo $id; ?>">
                      Ver
                    </a>
              -->       
                    <a class="task-link-blue"
                       style="margin-left:12px;"
                       href="/HelpDesk_EQF/modules/dashboard/user/download_document.php?id=<?php echo $id; ?>">
                      Descargar
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </section>

  </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  const table = $('#docsTable').DataTable({
    pageLength: 10,
    lengthChange: false,
    ordering: false,
    language: {
      search: "Buscar:",
      zeroRecords: "No se encontraron documentos",
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Mostrando 0 a 0 de 0",
      paginate: { previous: "Anterior", next: "Siguiente" }
    }
  });

  // búsqueda en vivo al escribir
  $('#docsTable_filter input')
    .off('keyup')
    .on('input', function(){
      table.search(this.value).draw();
    });
});
</script>

</body>
</html>
