<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

$pdo = Database::getConnection();

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$areaAdmin = $_SESSION['user_area'] ?? '';

$mensajeExito = '';
$mensajeError = '';

// =============================
// 2) Crear tarea (prioridad ALTA) para analista
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_tarea') {
    $analystId   = (int)($_POST['analyst_id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fechaLimite = $_POST['fecha_limite'] ?? null;

    // Convertir datetime-local (YYYY-MM-DDTHH:MM) a formato de BD
    $fechaLimiteDB = null;
    if (!empty($fechaLimite)) {
        // Ej: 2025-12-09T12:30 => 2025-12-09 12:30:00
        $fechaLimiteDB = str_replace('T', ' ', $fechaLimite) . ':00';
    }

    // Archivo opcional
    $archivoRuta = null;
    if (!empty($_FILES['archivo_tarea']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/tasks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $nombreOriginal = $_FILES['archivo_tarea']['name'];
        $ext            = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreSeguro   = uniqid('tarea_') . '.' . $ext;
        $destino        = $uploadDir . $nombreSeguro;

        if (move_uploaded_file($_FILES['archivo_tarea']['tmp_name'], $destino)) {
            // Ruta relativa para usar en el href
            $archivoRuta = 'uploads/tasks/' . $nombreSeguro;
        }
    }

    if ($analystId > 0 && $titulo !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO analyst_tasks (
                area, admin_id, analyst_id,
                titulo, descripcion, fecha_limite, archivo_ruta
            ) VALUES (
                :area, :admin_id, :analyst_id,
                :titulo, :descripcion, :fecha_limite, :archivo_ruta
            )
        ");
        $stmt->execute([
            ':area'         => $areaAdmin,
            ':admin_id'     => $userId,
            ':analyst_id'   => $analystId,
            ':titulo'       => $titulo,
            ':descripcion'  => $descripcion,
            ':fecha_limite' => $fechaLimiteDB,
            ':archivo_ruta' => $archivoRuta,
        ]);

        $mensajeExito = "Tarea creada (prioridad alta) y asignada al analista.";
    } else {
        $mensajeError = "Selecciona un analista y escribe un título.";
    }
}

// =============================
// 3) Analistas del área
// =============================
$stmtAnalysts = $pdo->prepare("
    SELECT id, name, last_name
    FROM users
    WHERE rol = 3
      AND area = :area
    ORDER BY last_name ASC, name ASC
");
$stmtAnalysts->execute([':area' => $areaAdmin]);
$analysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

// =============================
// 4) KPIs del área
// Estados asumidos en la BD: 'abierto','en_proceso','en_espera','vencido','cerrado'
// =============================

// Total de tickets del área
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
");
$stmtTotal->execute([':area' => $areaAdmin]);
$totalTickets = (int)$stmtTotal->fetchColumn();

// Tickets abiertos (abierto, en_proceso, en_espera)
$stmtAbiertos = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado IN ('abierto','en_proceso','en_espera')
");
$stmtAbiertos->execute([':area' => $areaAdmin]);
$totalAbiertos = (int)$stmtAbiertos->fetchColumn();

// Tickets vencidos (SLA violado: estado = 'vencido')
$stmtVencidos = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado = 'vencido'
");
$stmtVencidos->execute([':area' => $areaAdmin]);
$totalVencidos = (int)$stmtVencidos->fetchColumn();

// Porcentaje de tickets cerrados (simple, sin SLA detallado)
$stmtCerrados = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado = 'cerrado'
");
$stmtCerrados->execute([':area' => $areaAdmin]);
$totalCerrados = (int)$stmtCerrados->fetchColumn();

$porcSLA = 0;
if ($totalTickets > 0) {
    $porcSLA = round(($totalCerrados / $totalTickets) * 100, 1);
}

// =============================
// 5) Últimas tareas creadas por este Admin
// =============================
$stmtTareas = $pdo->prepare("
    SELECT t.*, u.name, u.last_name
    FROM analyst_tasks t
    JOIN users u ON u.id = t.analyst_id
    WHERE t.area = :area
      AND t.admin_id = :admin_id
    ORDER BY t.created_at DESC
    LIMIT 8
");
$stmtTareas->execute([
    ':area'     => $areaAdmin,
    ':admin_id' => $userId
]);
$tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

// =============================
// 6) Resumen rápido de tickets del área
// =============================
$stmtTickets = $pdo->prepare("
    SELECT id, problema, estado, prioridad, fecha_envio
    FROM tickets
    WHERE area = :area
    ORDER BY fecha_envio DESC
    LIMIT 8
");
$stmtTickets->execute([':area' => $areaAdmin]);
$ticketsArea = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

// =============================
// 7) Render: misma estructura que user.php / analyst.php
// =============================

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
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
                    Panel de Admin – Área <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?><br>
                    <small>Bienvenid@, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></small>
                </p>
            </div>
        </header>

        <section class="user-main-content">

            <?php if ($mensajeExito): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($mensajeError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Tarjeta intro -->
            <div class="user-info-card">
                <h2>Resumen del área</h2>
                <p>
                    Desde este panel puedes ver un resumen general de los tickets de tu área,
                    asignar tareas urgentes a los analistas y revisar actividades recientes.
                </p>
            </div>

            <!-- KPIs -->
            <section class="admin-kpi-grid">
                <div class="admin-kpi-card">
                    <span class="admin-kpi-label">Total de tickets del área</span>
                    <span class="admin-kpi-value"><?php echo $totalTickets; ?></span>
                </div>
                <div class="admin-kpi-card">
                    <span class="admin-kpi-label">Tickets abiertos / en proceso / en espera</span>
                    <span class="admin-kpi-value"><?php echo $totalAbiertos; ?></span>
                </div>
                <div class="admin-kpi-card admin-kpi-danger">
                    <span class="admin-kpi-label">Tickets vencidos (SLA)</span>
                    <span class="admin-kpi-value"><?php echo $totalVencidos; ?></span>
                </div>
                <div class="admin-kpi-card admin-kpi-success">
                    <span class="admin-kpi-label">Cumplimiento (cerrados / total)</span>
                    <span class="admin-kpi-value"><?php echo $porcSLA; ?>%</span>
                </div>
            </section>

            <!-- Crear tarea urgente para analista -->
            <section class="admin-card">
                <h2>Crear tarea urgente para analista (prioridad alta)</h2>

                <form method="POST" enctype="multipart/form-data" class="admin-task-form">
                    <input type="hidden" name="accion" value="crear_tarea">

                    <div class="form-group">
                        <label for="analyst_id">Asignar a analista</label>
                        <select name="analyst_id" id="analyst_id" required>
                            <option value="">Selecciona un analista</option>
                            <?php foreach ($analysts as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>">
                                    <?php echo htmlspecialchars($a['name'] . ' ' . $a['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título de la tarea</label>
                        <input type="text" name="titulo" id="titulo" required maxlength="150">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_limite">Fecha y hora límite (opcional)</label>
                            <input type="datetime-local" name="fecha_limite" id="fecha_limite">
                        </div>
                        <div class="form-group">
                            <label for="archivo_tarea">Archivo adjunto (opcional)</label>
                            <input type="file" name="archivo_tarea" id="archivo_tarea">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        Crear tarea
                    </button>
                </form>
            </section>

            <!-- Tareas recientes -->
            <section class="admin-card">
                <h2>Tareas recientes a analistas</h2>
                <?php if (empty($tareas)): ?>
                    <p class="admin-empty">No has creado tareas todavía.</p>
                <?php else: ?>
                    <div class="admin-task-list">
                        <?php foreach ($tareas as $t): ?>
                            <article class="admin-task-card">
                                <header class="admin-task-header">
                                    <h3><?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="badge badge-prioridad-alta">Alta</span>
                                </header>

                                <p class="admin-task-meta">
                                    Para: <?php echo htmlspecialchars($t['name'] . ' ' . $t['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    · Estado: <?php echo htmlspecialchars($t['estado'] ?? 'pendiente', ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <?php if (!empty($t['fecha_limite'])): ?>
                                    <p class="admin-task-meta">
                                        Fecha límite: <?php echo htmlspecialchars($t['fecha_limite'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($t['descripcion'])): ?>
                                    <p class="admin-task-desc">
                                        <?php echo nl2br(htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($t['archivo_ruta'])): ?>
                                    <p class="admin-task-meta">
                                        <a href="/HelpDesk_EQF/<?php echo htmlspecialchars($t['archivo_ruta'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                            Ver archivo adjunto
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Resumen rápido de tickets del área -->
            <section class="admin-card">
                <h2>Resumen rápido de tickets del área</h2>
                <?php if (empty($ticketsArea)): ?>
                    <p class="admin-empty">No hay tickets registrados para esta área.</p>
                <?php else: ?>
                    <div class="admin-ticket-list">
                        <?php foreach ($ticketsArea as $tk): ?>
                            <div class="admin-ticket-row">
                                <strong>
                                    #<?php echo (int)$tk['id']; ?>
                                    - <?php echo htmlspecialchars($tk['problema'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                                <div class="admin-ticket-meta">
                                    Estado:
                                    <?php echo htmlspecialchars($tk['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    · Prioridad:
                                    <?php echo htmlspecialchars($tk['prioridad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    · Fecha:
                                    <?php echo htmlspecialchars($tk['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </section>
    </section>
</main>

</body>
</html>
