<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = Database::getConnection();

    $q = trim((string)($_GET['q'] ?? ''));
    if ($q === '') {
        echo json_encode(['ok'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE);
        exit;
    }

$like = '%' . $q . '%';

    // POSICIONALES: 4 "?" => 4 valores en execute()
    $sql = "
        SELECT id, number_sap, name, last_name, email, area
        FROM users
        WHERE email      LIKE ?
           OR name       LIKE ?
           OR last_name  LIKE ?
           OR number_sap LIKE ?
        ORDER BY email ASC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like, $like, $like]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'=>false,
        'msg'=>'Error interno',
        'debug'=>$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
