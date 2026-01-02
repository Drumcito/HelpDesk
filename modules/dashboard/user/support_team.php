<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 4) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();

$userName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userArea = trim((string)($_SESSION['user_area'] ?? ''));

// Áreas para filtro
$areas = [];
try {
    $stmt = $pdo->query("SELECT nombre FROM catalog_areas ORDER BY nombre ASC");
    $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

/* Endpoint */
$apiUrl = "/HelpDesk_EQF/modules/dashboard/user/get_support_team.php";

/* Layout */
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>

<main class="content">
    <div class="support-page">

        <div class="support-header">
            <div>
                <h1>Equipo de soporte</h1>
                <p>Consulta quién te puede apoyar por área y su disponibilidad.</p>
            </div>

            <div class="support-filters">
                <label for="areaSelect">Área</label>
                <select id="areaSelect">
                    <?php if ($userArea): ?>
                        <option value="<?= htmlspecialchars($userArea) ?>" selected><?= htmlspecialchars($userArea) ?></option>
                    <?php endif; ?>

                    <option value="">Todas</option>

                    <?php
                    $printed = [];
                    if ($userArea) $printed[strtolower($userArea)] = true;

                    foreach ($areas as $a) {
                        $key = strtolower($a);
                        if (isset($printed[$key])) continue;
                        echo '<option value="'.htmlspecialchars($a).'">'.htmlspecialchars($a).'</option>';
                    }
                    ?>
                </select>

                <button type="button" id="btnRefresh" class="btn btn-secondary">Actualizar</button>
            </div>
        </div>

        <div class="support-card">
            <div class="support-info">
                <div>
                    <span class="support-kicker">Usuario</span>
                    <span class="support-value"><?= htmlspecialchars($userName) ?></span>
                </div>
                <div>
                    <span class="support-kicker">Área</span>
                    <span class="support-value"><?= htmlspecialchars($userArea ?: '—') ?></span>
                </div>
                <div>
                    <span class="support-kicker">Disponibilidad</span>
                    <span class="support-value">Disponible / No disponible</span>
                </div>
            </div>
        </div>

        <div id="loading" class="support-loading" style="display:none;">
            <div class="support-spinner"></div>
            <span>Cargando equipo...</span>
        </div>

        <div id="errorBox" class="support-alert support-alert-danger" style="display:none;"></div>

        <section class="support-section">
            <h2>ADMIN</h2>
            <div id="adminContainer" class="support-grid"></div>
            <div id="adminEmpty" class="support-empty" style="display:none;">No hay admin asignado.</div>
        </section>

        <section class="support-section">
            <h2>ANALISTAS</h2>
            <div id="analystsContainer" class="support-grid"></div>
            <div id="analystsEmpty" class="support-empty" style="display:none;">No hay analistas disponibles.</div>
        </section>

    </div>
</main>

<script>
(() => {
    const apiUrl = <?= json_encode($apiUrl) ?>;

    const area = document.getElementById('areaSelect');
    const refresh = document.getElementById('btnRefresh');

    const adminBox = document.getElementById('adminContainer');
    const analystBox = document.getElementById('analystsContainer');

    const adminEmpty = document.getElementById('adminEmpty');
    const analystEmpty = document.getElementById('analystsEmpty');

    const loading = document.getElementById('loading');
    const errorBox = document.getElementById('errorBox');

    const esc = s => String(s ?? '').replace(/[&<>"']/g,m=>({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    })[m]);

    const initials = n => {
        const p = String(n).trim().split(' ');
        return (p[0][0] + (p[1]?.[0]||'')).toUpperCase();
    };

    const badge = d =>
        d === 'Disponible'
            ? '<span class="support-badge ok">Disponible</span>'
            : '<span class="support-badge no">No disponible</span>';

    const card = u => `
        <div class="support-person-card">
            <div class="support-avatar">${initials(u.nombre)}</div>
            <div>
                <strong>${esc(u.nombre)}</strong>
                ${badge(u.disponibilidad)}
                <div><a href="mailto:${esc(u.email)}">${esc(u.email)}</a></div>
                <div><a href="tel:${esc(u.phone)}">${esc(u.phone)}</a></div>
            </div>
        </div>
    `;

    async function load() {
        loading.style.display = '';
        errorBox.style.display = 'none';

        adminBox.innerHTML = analystBox.innerHTML = '';
        adminEmpty.style.display = analystEmpty.style.display = 'none';

        try {
            const url = new URL(apiUrl, location.origin);
            url.searchParams.set('area', area.value);

            const res = await fetch(url);
            const json = await res.json();

            if (!json.ok) throw new Error(json.msg || 'Error');

            if (json.admin.length)
                adminBox.innerHTML = json.admin.map(card).join('');
            else adminEmpty.style.display = '';

            if (json.analistas.length)
                analystBox.innerHTML = json.analistas.map(card).join('');
            else analystEmpty.style.display = '';

        } catch (e) {
            errorBox.textContent = e.message;
            errorBox.style.display = '';
        } finally {
            loading.style.display = 'none';
        }
    }

    refresh.onclick = load;
    area.onchange = load;
    load();
})();
</script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
