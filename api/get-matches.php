<?php
// api/get-matches.php

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Match.php';

$database   = new Database();
$db         = $database->getConnection();
$matchModel = new MatchModel($db);

$user_id = (int)$_SESSION['user_id'];
$rows    = $matchModel->getMatchesForUser($user_id);

$defaultAvatar = '/tinder/assets/images/default-avatar.jpg';
$matches       = [];

foreach ($rows as $row) {
    // Calculate age
    $age = 0;
    if (!empty($row['user_date_naissance'])) {
        try {
            $dob = new DateTime($row['user_date_naissance']);
            $now = new DateTime();
            $age = (int)$now->diff($dob)->y;
        } catch (Exception $e) {
            $age = 0;
        }
    }

    $matches[] = [
        'match_id'          => (int)$row['match_id'],
        'user_id'           => (int)$row['other_user_id'],
        'user_name'         => $row['user_nom'],
        'user_photo'        => !empty($row['user_photo']) ? $row['user_photo'] : $defaultAvatar,
        'user_age'          => $age,
        'user_ville'        => $row['user_ville'] ?? '',
        'created_at'        => $row['created_at'],
        'last_message'      => $row['last_message'] ?? '',
        'last_message_date' => $row['last_message_date'] ?? '',
        'unread_count'      => (int)$row['unread_count'],
    ];
}

echo json_encode([
    'success' => true,
    'matches' => $matches,
    'count'   => count($matches),
]);
?>
