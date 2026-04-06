<?php
// api/get-messages.php

session_start();

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ----- Auth check -----
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

if (empty($_GET['match_id'])) {
    echo json_encode(['success' => false, 'error' => 'match_id requis.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Message.php';

$database = new Database();
$db       = $database->getConnection();

$user_id  = (int)$_SESSION['user_id'];
$match_id = (int)$_GET['match_id'];

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

// ----- Mark messages as read -----
$messageModel->markAsRead($match_id, $user_id);

// ----- Fetch messages -----
$rows = $messageModel->getByMatch($match_id);

$messages = [];
foreach ($rows as $row) {
    $messages[] = [
        'id'           => (int)$row['id'],
        'sender_id'    => (int)$row['sender_id'],
        'sender_name'  => $row['sender_name'],
        'sender_photo' => $row['sender_photo'] ?? '',
        'message'      => $row['message'],
        'date_envoi'   => $row['date_envoi'],
        'lu'           => (bool)$row['lu'],
    ];
}

echo json_encode([
    'success'  => true,
    'messages' => $messages,
]);
?>
