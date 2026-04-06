<?php
// api/send-message.php

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
if (!$input || empty($input['match_id']) || !isset($input['message'])) {
    echo json_encode(['success' => false, 'error' => 'match_id et message requis.']);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$match_id = (int)$input['match_id'];
$message  = trim(htmlspecialchars(strip_tags((string)$input['message']), ENT_QUOTES, 'UTF-8'));

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Le message ne peut pas être vide.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Message.php';

$database = new Database();
$db       = $database->getConnection();

// ----- Verify user is part of this match -----
$stmtCheck = $db->prepare(
    "SELECT id FROM matches
     WHERE id = ? AND (user1_id = ? OR user2_id = ?)
     LIMIT 1"
);
$stmtCheck->execute([$match_id, $user_id, $user_id]);
if (!$stmtCheck->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé à cette conversation.']);
    exit;
}

$messageModel = new Message($db);
$message_id   = $messageModel->send($match_id, $user_id, $message);

if (!$message_id) {
    echo json_encode(['success' => false, 'error' => "Erreur lors de l'envoi du message."]);
    exit;
}

// ----- Update last_interaction on match -----
$db->prepare("UPDATE matches SET last_interaction = NOW() WHERE id = ?")
   ->execute([$match_id]);

// ----- Return the inserted message timestamp -----
$stmt = $db->prepare("SELECT date_envoi FROM messages WHERE id = ? LIMIT 1");
$stmt->execute([$message_id]);
$row = $stmt->fetch();

echo json_encode([
    'success'    => true,
    'message_id' => $message_id,
    'date_envoi' => $row['date_envoi'] ?? date('Y-m-d H:i:s'),
]);
?>
