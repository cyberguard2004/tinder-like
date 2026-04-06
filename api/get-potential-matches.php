<?php
// api/get-potential-matches.php

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
require_once __DIR__ . '/../models/User.php';

$database  = new Database();
$db        = $database->getConnection();
$userModel = new User($db);

$limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$matches = $userModel->getPotentialMatches((int)$_SESSION['user_id'], $limit);

$defaultAvatar = '/tinder/assets/images/default-avatar.jpg';

$result = [];
foreach ($matches as $match) {
    $result[] = [
        'id'               => (int)$match['id'],
        'nom'              => $match['nom'],
        'date_naissance'   => $match['date_naissance'],
        'bio'              => $match['bio'] ?? '',
        'photo_url'        => !empty($match['photo_url']) ? $match['photo_url'] : $defaultAvatar,
        'ville'            => $match['ville'] ?? '',
        'interets_communs' => (int)($match['interets_communs'] ?? 0),
        'liste_interets'   => !empty($match['liste_interets'])
            ? explode(',', $match['liste_interets'])
            : [],
    ];
}

echo json_encode([
    'success' => true,
    'matches' => $result,
]);
?>
