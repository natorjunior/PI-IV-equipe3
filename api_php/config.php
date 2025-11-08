<?php
// config.php - ajuste as credenciais se necessário
$DB_HOST = 'mysql';
$DB_NAME = 'peneirada';
$DB_USER = 'peneirauser';
$DB_PASS = 'peneira123';

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'DB connection error: ' . $e->getMessage()]);
    exit;
}
?>
