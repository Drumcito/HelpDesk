<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Conteo de encuestas pendientes (para bloquear botón)
$stmtPending = $pdo->prepare("
  SELECT COUNT(*)
  FROM ticket_feedback
  WHERE user_id = ? AND answered_at IS NULL
");
$stmtPending->execute([$userId]);
$pendingCount = (int)$stmtPending->fetchColumn();

// Tickets a mostrar: abiertos/en_proceso + cerrado con encuesta pendiente
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.problema,
        COALESCE(cp.label, t.problema) AS problema_label,
        t.fecha_envio,
        t.estado,
        f.token AS feedback_token
    FROM tickets t
    LEFT JOIN ticket_feedback f
        ON f.ticket_id = t.id
       AND f.answered_at IS NULL
    LEFT JOIN catalog_problems cp
        ON cp.code = t.problema
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

echo json_encode([
    'ok' => true,
    'pendingCount' => $pendingCount,
    'tickets' => array_map(function($t){
        return [
            'id' => (int)$t['id'],
            'problema_label' => (string)$t['problema_label'],
            'fecha_envio' => (string)$t['fecha_envio'],
            'estado' => (string)$t['estado'],
            'feedback_token' => $t['feedback_token'] ? (string)$t['feedback_token'] : ''
        ];
    }, $tickets)
], JSON_UNESCAPED_UNICODE);
