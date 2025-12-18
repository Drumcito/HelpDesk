<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit('ID inválido.');

$st = $pdo->prepare("SELECT stored_name, original_name, display_name, mime_type FROM documents WHERE id=? LIMIT 1");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) exit('Documento no encontrado.');

$stored  = (string)$row['stored_name'];
$orig    = (string)$row['original_name'];
$display = trim((string)$row['display_name']);
$mime    = (string)$row['mime_type'];

$path = __DIR__ . '/../../../uploads/docs/' . $stored;
if (!is_file($path)) exit('Archivo no encontrado en servidor.');

// ---------- nombre de descarga = display_name + extensión original ----------
$ext = pathinfo($orig, PATHINFO_EXTENSION);
$base = $display !== '' ? $display : pathinfo($orig, PATHINFO_FILENAME);

// limpia caracteres raros para Windows
$base = preg_replace('/[\/\\\\:*?"<>|]+/', ' ', $base);
$base = trim(preg_replace('/\s+/', ' ', $base));

$downloadName = $base . ($ext ? "." . $ext : "");

// ---------- headers ----------
header('Content-Description: File Transfer');
header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
header('Pragma: public');

readfile($path);
exit;
