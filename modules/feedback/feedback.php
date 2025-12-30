<?php
require_once __DIR__ . '/../../config/connectionBD.php';
$pdo = Database::getConnection();

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("
    SELECT f.id, f.ticket_id, t.problema
    FROM ticket_feedback f
    JOIN tickets t ON t.id = f.ticket_id
    WHERE f.token = ?
      AND f.answered_at IS NULL
");
$stmt->execute([$token]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$feedback) {
    exit('Encuesta inválida o ya respondida.');
}

$ticketId = (int)($feedback['ticket_id'] ?? 0);
$problema = (string)($feedback['problema'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Encuesta de satisfacción</title>

<!-- Si ya tienes style.css global y quieres que aplique -->
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">

<style>
  :root{
    /* fallback por si no carga tus vars */
    --eqf-red:#C8002D;
    --eqf-yellow:#F2C94C;
    --eqf-green:#1E8A4F;
    --eqf-border:#e5e7eb;
  }

  body{
    margin:0;
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
    background: transparent;
  }

  .fb-wrap{
    padding: 18px;
  }

  .fb-card{
    background:#fff;
    border:1px solid var(--eqf-border);
    border-radius:18px;
    padding:18px;
  }

  h2{ margin:0 0 6px 0; font-size:18px; }
  .fb-prob{ opacity:.75; font-size:13px; margin-bottom:14px; }

  .fb-q{
    margin:16px 0 8px;
    font-weight:900;
    font-size:14px;
    color:#111827;
  }

  .rating-group{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
    margin: 10px 0 18px;
  }

  .fb-btn{
    position:relative;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    width:92px;
    height:84px;
    border-radius:18px;
    border:2px solid var(--eqf-border);
    background:#fff;
    cursor:pointer;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background-color .15s ease;
    user-select:none;
  }
  .fb-btn input{ display:none; }

  /* Icono tintado con mask */
  .fb-icon{
    width:48px;
    height:48px;
    background-color: var(--tone);
    -webkit-mask-image: var(--icon);
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-position: center;
    -webkit-mask-size: contain;

    mask-image: var(--icon);
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: contain;
    margin-bottom:6px;
  }

  .fb-label{
    display: none;
  }

  .fb-btn:hover{
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(15,23,42,.10);
    border-color: rgba(0,0,0,.12);
  }

  .fb-btn.is-checked{
    border-color: var(--tone);
    background: color-mix(in srgb, var(--tone) 12%, white);
    box-shadow: 0 12px 24px rgba(15,23,42,.14);
  }

  .fb-comment{
    width:100%;
    min-height:84px;
    border:1px solid var(--eqf-border);
    border-radius:14px;
    padding:12px;
    outline:none;
    resize:vertical;
  }

  .fb-actions{
    display:flex;
    gap:10px;
    justify-content:flex-end;
    margin-top:14px;
  }

  .fb-submit{
    border:none;
    border-radius:14px;
    padding:10px 16px;
    font-weight:900;
    cursor:pointer;
    background: var(--eqf-combined, #6e1c5c);
    color:#fff;
  }
</style>
</head>
<body>

<div class="fb-wrap">
  <div class="fb-card">
    <h2>Encuesta de satisfacción</h2>
    <div class="fb-prob"><?php echo htmlspecialchars($problema, ENT_QUOTES, 'UTF-8'); ?></div>

    <form id="feedbackForm" method="POST" action="/HelpDesk_EQF/modules/feedback/submit_feedback.php">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="fb-q">¿Cómo calificas la atención recibida?</div>
      <div class="rating-group">
        <label class="fb-btn" style="--tone: var(--eqf-red); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_bad.png');">
          <input type="radio" name="q1" value="1" required>
          <span class="fb-icon"></span>
          <span class="fb-label">Malo</span>
        </label>

        <label class="fb-btn" style="--tone: var(--eqf-yellow); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_reg.png');">
          <input type="radio" name="q1" value="2">
          <span class="fb-icon"></span>
          <span class="fb-label">Regular</span>
        </label>

        <label class="fb-btn" style="--tone: var(--eqf-green); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_good.png');">
          <input type="radio" name="q1" value="3">
          <span class="fb-icon"></span>
          <span class="fb-label">Bueno</span>
        </label>
      </div>

      <div class="fb-q">¿El problema fue resuelto completamente?</div>
      <div class="rating-group">
        <label class="fb-btn" style="--tone: var(--eqf-red); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_no.png');">
          <input type="radio" name="q2" value="1" required>
          <span class="fb-icon"></span>
          <span class="fb-label">No</span>
        </label>

        <label class="fb-btn" style="--tone: var(--eqf-green); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_yes.png');">
          <input type="radio" name="q2" value="2">
          <span class="fb-icon"></span>
          <span class="fb-label">Sí</span>
        </label>
      </div>

      <div class="fb-q">¿Cómo calificas el tiempo de respuesta?</div>
      <div class="rating-group">
        <label class="fb-btn" style="--tone: var(--eqf-red); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_bad.png');">
          <input type="radio" name="q3" value="1" required>
          <span class="fb-icon"></span>
          <span class="fb-label">Malo</span>
        </label>

        <label class="fb-btn" style="--tone: var(--eqf-yellow); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_reg.png');">
          <input type="radio" name="q3" value="2">
          <span class="fb-icon"></span>
          <span class="fb-label">Regular</span>
        </label>

        <label class="fb-btn" style="--tone: var(--eqf-green); --icon: url('/HelpDesk_EQF/assets/img/feedback/feedback_good.png');">
          <input type="radio" name="q3" value="3">
          <span class="fb-icon"></span>
          <span class="fb-label">Bueno</span>
        </label>
      </div>

      <div class="fb-q">Comentarios (opcional)</div>
      <textarea class="fb-comment" name="comment" maxlength="500" placeholder="Escribe un comentario breve..."></textarea>

      <div class="fb-actions">
        <button type="submit" class="fb-submit">Enviar encuesta</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('change', (e) => {
  const input = e.target.closest('.fb-btn input[type="radio"]');
  if (!input) return;

  document.querySelectorAll(`input[name="${input.name}"]`)
    .forEach(r => r.closest('.fb-btn')?.classList.remove('is-checked'));

  input.closest('.fb-btn')?.classList.add('is-checked');
});
</script>
<script>
(() => {
  const form = document.getElementById('feedbackForm');
  const msg  = document.getElementById('fbMsg');
  if (!form) return;

  function show(text, ok=false){
    if (!msg) return;
    msg.style.display = 'block';
    msg.style.color = ok ? '#065f46' : '#9a3412';
    msg.textContent = text || '';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Enviando...'; }

    try {
      const fd = new FormData(form);
const r = await fetch(form.action, {
  method: 'POST',
  body: fd,
  cache: 'no-store',
  headers: { 'Accept': 'application/json' }
});
      const raw = await r.text();
      let j = null; try { j = JSON.parse(raw); } catch(e){}

      if (!r.ok || !j) {
        console.error('submit_feedback bad response', r.status, raw);
        show('No se pudo enviar la encuesta.', false);
        return;
      }

      if (!j.ok) {
        show(j.msg || 'No se pudo enviar la encuesta.', false);
        return;
      }

      // ✅ Éxito: cerrar modal del padre y refrescar lista
      show('¡Gracias! Encuesta enviada.', true);

      if (window.parent) {
        // refresca el listado de "tickets de apoyo" si lo expones (abajo te digo)
        if (typeof window.parent.refreshMyTI === 'function') window.parent.refreshMyTI();
        if (typeof window.parent.closeFeedbackModal === 'function') window.parent.closeFeedbackModal();
      }

    } catch (err) {
      console.error(err);
      show('Error enviando la encuesta.', false);
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Enviar encuesta'; }
    }
  });
})();
</script>

</body>
</html>
