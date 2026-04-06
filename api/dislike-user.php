<?php
// api/dislike-user.php

session_start();

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ----- Auth check -----
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['to_user_id'])) {
    echo json_encode(['success' => false, 'error' => 'to_user_id requis.']);
    exit;
}

$from_user_id = (int)$_SESSION['user_id'];
$to_user_id   = (int)$input['to_user_id'];

if ($from_user_id === $to_user_id) {
    echo json_encode(['success' => false, 'error' => 'Action invalide.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db       = $database->getConnection();

try {
    $stmt = $db->prepare(
        "INSERT IGNORE INTO likes (from_user_id, to_user_id, type, created_at)
         VALUES (:from, :to, 'dislike', NOW())"
    );
    $stmt->execute([':from' => $from_user_id, ':to' => $to_user_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données.']);
    exit;
}

echo json_encode(['success' => true]);
?>
