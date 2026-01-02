<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Sesión inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    // Tickets que el usuario debe ver:
    // - abiertos/en_proceso
    // - cerrados con encuesta pendiente (token)
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.estado,
            t.fecha_envio,
            t.problema AS problema_raw,
            COALESCE(cp.label, t.problema) AS problema_label,
            f.token AS feedback_token,

            t.asignado_a AS analyst_id,
            u.name AS analyst_name,
            u.last_name AS analyst_last

        FROM tickets t
        LEFT JOIN catalog_problems cp
               ON cp.code = t.problema
        LEFT JOIN ticket_feedback f
               ON f.ticket_id = t.id
              AND f.answered_at IS NULL
        LEFT JOIN users u
               ON u.id = t.asignado_a

        WHERE t.user_id = :uid
          AND (
                t.estado IN ('abierto','en_proceso')
             OR (t.estado = 'cerrado' AND f.id IS NOT NULL)
          )
        ORDER BY t.fecha_envio DESC
        LIMIT 10
    ");
    $stmt->execute([':uid' => $userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pendientes de feedback (para bloquear botón "Crear ticket")
    $stmtPending = $pdo->prepare("
        SELECT COUNT(*)
        FROM ticket_feedback
        WHERE user_id = :uid AND answered_at IS NULL
    ");
    $stmtPending->execute([':uid' => $userId]);
    $pendingCount = (int)$stmtPending->fetchColumn();

    echo json_encode([
        'ok' => true,
        'pending_feedback_count' => $pendingCount,
        'tickets' => array_map(function ($t) {
                $full = trim((($t['analyst_name'] ?? '') . ' ' . ($t['analyst_last'] ?? '')));
            return [
                'id' => (int)$t['id'],
                'estado' => (string)$t['estado'],
                'fecha_envio' => (string)$t['fecha_envio'],
                'problema_raw' => (string)$t['problema_raw'],
                'problema_label' => (string)$t['problema_label'],
                'feedback_token' => $t['feedback_token'] ? (string)$t['feedback_token'] : null,

                'analyst_id' => isset($t['analyst_id']) ? (int)$t['analyst_id'] : 0,
        'analyst_full' => $full, // '' si no hay asignado
    ];
}, $tickets)
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('user_snapshot.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
