<?php
session_start();
require_once __DIR__.'/../../../config/connectionBD.php';

if ($_SESSION['user_rol'] != 3) {
    exit("No autorizado");
}

$pdo = Database::getConnection();

$id = $_POST['id'];
$estado = $_POST['estado'];

$stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
$stmt->execute([$estado, $id]);

header("Location: tickets.php?updated=1");
exit;
