<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// evitar que notices rompan JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user_id'])) {
  json_out(['ok' => false, 'msg' => 'No autenticado'], 200);
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if (!in_array($rol, [2, 3], true)) { // Admin o Analista
  json_out(['ok' => false, 'msg' => 'Sin permisos'], 200);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['ok' => false, 'msg' => 'Método no permitido'], 200);
}

/**
 * Aceptar input como:
 * - JSON (fetch con application/json)
 * - FormData (multipart/form-data)
 */
$ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$in = [];

if (str_contains($ct, 'application/json')) {
  $raw = file_get_contents('php://input');
  $in = json_decode($raw, true) ?: [];
} else {
  // FormData normal
  $in = $_POST ?: [];
}

$title = trim((string)($in['title'] ?? ''));
$body  = trim((string)($in['body'] ?? ''));
$target_area = trim((string)($in['target_area'] ?? 'ALL'));      // ALL / Sucursal / Corporativo
$rawLevel = trim((string)($in['level'] ?? 'INFO'));
$lv = strtoupper($rawLevel);

// Mapea variantes típicas del frontend a tu ENUM real
$map = [
  'INFO' => 'INFO', 'INFORMATION' => 'INFO', 'LOW' => 'INFO', 'BAJA' => 'INFO',

  'WARN' => 'WARN', 'WARNING' => 'WARN', 'MED' => 'WARN', 'MEDIUM' => 'WARN', 'MEDIA' => 'WARN',

  'CRITICAL' => 'CRITICAL', 'DANGER' => 'CRITICAL', 'HIGH' => 'CRITICAL', 'ALTA' => 'CRITICAL',
];

$level = $map[$lv] ?? 'INFO';

if ($title === '' || $body === '') {
  json_out(['ok' => false, 'msg' => 'Faltan campos obligatorios'], 200);
}

$starts_at = trim((string)($in['starts_at'] ?? ''));
$ends_at   = trim((string)($in['ends_at'] ?? ''));

try {
  $pdo = Database::getConnection();

  // OJO: TU TABLA ES: created_by_user_id y created_by_area (no "created_by")
  $sql = "
    INSERT INTO announcements
      (title, body, level, target_area, starts_at, ends_at, is_active, created_at, created_by_user_id, created_by_area)
    VALUES
      (:title, :body, :level, :target_area,
       NULLIF(:starts_at,''), NULLIF(:ends_at,''), 1, NOW(), :created_by_user_id, :created_by_area)
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':title'              => $title,
    ':body'               => $body,
    ':level'              => $level,
    ':target_area'        => $target_area,
    ':starts_at'          => $starts_at,
    ':ends_at'            => $ends_at,
    ':created_by_user_id' => (int)$_SESSION['user_id'],
    ':created_by_area'    => trim((string)($_SESSION['user_area'] ?? '')),
  ]);

  $newId = (int)$pdo->lastInsertId();
  json_out(['ok' => true, 'id' => $newId], 200);

} catch (Throwable $e) {
  // no 500 para no romper tu frontend
  json_out(['ok' => false, 'msg' => 'Error al guardar anuncio'], 200);
}
