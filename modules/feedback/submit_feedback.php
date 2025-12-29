<?php
require_once __DIR__ . '/../../config/connectionBD.php';
$pdo = Database::getConnection();

$token = trim((string)($_POST['token'] ?? ''));

$q1 = (int)($_POST['q1'] ?? 0); // 1..3
$q2 = (int)($_POST['q2'] ?? 0); // 1..2
$q3 = (int)($_POST['q3'] ?? 0); // 1..3
$comment = trim((string)($_POST['comment'] ?? ''));

$valid =
    $token !== '' &&
    in_array($q1, [1, 2, 3], true) &&
    in_array($q2, [1, 2], true) &&
    in_array($q3, [1, 2, 3], true) &&
    mb_strlen($comment, 'UTF-8') <= 500;

function render_end(string $title, string $msg, bool $ok): void {
    header('Content-Type: text/html; charset=utf-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMsg   = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');

    // si ok => cerrar modal/iframe y recargar user.php
    // si no ok => no cerrar, solo mostrar mensaje (y que el usuario cierre o regrese)
    $script = $ok ? "
      (function(){
        try {
          if (window.parent && typeof window.parent.closeFeedbackIframe === 'function') {
            window.parent.closeFeedbackIframe();
            window.parent.location.reload();
            return;
          }
        } catch(e) {}
        window.location.href = '/HelpDesk_EQF/modules/dashboard/user/user.php';
      })();
    " : "";

    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>{$safeTitle}</title>
    <style>
      body{font-family:system-ui,Segoe UI,Roboto,Arial; padding:18px; background:#fff; color:#111;}
      .box{max-width:520px; margin:40px auto; border:1px solid #e5e7eb; border-radius:14px; padding:16px;}
      .ok{color:#166534; font-weight:800;}
      .bad{color:#b91c1c; font-weight:800;}
      .muted{opacity:.75; font-size:13px; margin-top:8px;}
      button{margin-top:12px; padding:10px 14px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; cursor:pointer;}
    </style></head><body>
      <div class='box'>
        <div class='".($ok?'ok':'bad')."'>".$safeTitle."</div>
        <div style='margin-top:8px;'>".$safeMsg."</div>
        ".($ok ? "<div class='muted'>Cerrando…</div>" : "<button onclick='history.back()'>Regresar</button>")."
      </div>
      <script>{$script}</script>
    </body></html>";
    exit;
}

if (!$valid) {
    render_end('Datos inválidos', 'Revisa tus respuestas e inténtalo de nuevo.', false);
}

try {
    $stmt = $pdo->prepare("
        UPDATE ticket_feedback
        SET q1_attention = ?,
            q2_resolved  = ?,
            q3_time      = ?,
            comment      = ?,
            answered_at  = NOW()
        WHERE token = ?
          AND answered_at IS NULL
    ");
    $stmt->execute([$q1, $q2, $q3, $comment, $token]);

    if ($stmt->rowCount() === 0) {
        render_end('Encuesta no disponible', 'La encuesta ya fue respondida o el token no es válido.', false);
    }

    render_end('¡Gracias!', 'Tu feedback fue registrado correctamente.', true);

} catch (Throwable $e) {
    error_log('submit_feedback.php error: ' . $e->getMessage());
    render_end('Error interno', 'No se pudo guardar tu respuesta. Intenta de nuevo.', false);
}
