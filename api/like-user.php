<?php
// api/like-user.php

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
$type         = in_array($input['type'] ?? 'like', ['like', 'superlike'], true)
                ? $input['type']
                : 'like';

if ($from_user_id === $to_user_id) {
    echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas vous liker vous-même.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Match.php';

$database   = new Database();
$db         = $database->getConnection();
$matchModel = new MatchModel($db);

// ----- Insert like (ignore duplicate) -----
try {
    $stmt = $db->prepare(
        "INSERT IGNORE INTO likes (from_user_id, to_user_id, type, created_at)
         VALUES (:from, :to, :type, NOW())"
    );
    $stmt->execute([':from' => $from_user_id, ':to' => $to_user_id, ':type' => $type]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données.']);
    exit;
}

// ----- Check mutual like -----
$stmtCheck = $db->prepare(
    "SELECT id FROM likes
     WHERE from_user_id = :from AND to_user_id = :to
       AND type IN ('like', 'superlike')
     LIMIT 1"
);
$stmtCheck->execute([':from' => $to_user_id, ':to' => $from_user_id]);
$mutualLike = $stmtCheck->fetch();

if ($mutualLike) {
    // Create match
    $match_id = $matchModel->create($from_user_id, $to_user_id);
    echo json_encode([
        'success'  => true,
        'match'    => true,
        'match_id' => $match_id,
        'message'  => "C'est un match ! 🎉",
    ]);
} else {
    echo json_encode([
        'success' => true,
        'match'   => false,
    ]);
}
?>
