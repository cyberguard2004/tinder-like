<?php
// api/update-profile.php

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
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Corps JSON invalide.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$database  = new Database();
$db        = $database->getConnection();
$userModel = new User($db);

$user_id  = (int)$_SESSION['user_id'];
$data     = [];
$photo_url = null;

// ----- Collect text fields -----
if (isset($input['nom']))   $data['nom']   = trim($input['nom']);
if (isset($input['bio']))   $data['bio']   = trim($input['bio']);
if (isset($input['ville'])) $data['ville'] = trim($input['ville']);

// ----- Handle photo upload -----
if (!empty($input['photo'])) {
    $base64Data = $input['photo'];
    if (strpos($base64Data, ',') !== false) {
        $base64Data = explode(',', $base64Data)[1];
    }
    $imageData = base64_decode($base64Data);
    if ($imageData !== false && strlen($imageData) > 0) {
        $uploadsDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $filename  = 'user_' . $user_id . '_' . uniqid('', true) . '.jpg';
        $filepath  = $uploadsDir . $filename;
        if (file_put_contents($filepath, $imageData) !== false) {
            $photo_url        = '/tinder/uploads/' . $filename;
            $data['photo_url'] = $photo_url;
        }
    }
}

// ----- Update profile -----
if (!empty($data)) {
    $userModel->updateProfile($user_id, $data);
}

// ----- Update interests -----
if (isset($input['interets']) && is_array($input['interets'])) {
    // Delete existing
    $db->prepare("DELETE FROM user_interets WHERE user_id = ?")->execute([$user_id]);
    // Insert new
    $stmtInt = $db->prepare(
        "INSERT IGNORE INTO user_interets (user_id, interet_id) VALUES (?, ?)"
    );
    foreach ($input['interets'] as $interet_id) {
        $stmtInt->execute([$user_id, (int)$interet_id]);
    }
}

// ----- Update session name if changed -----
if (!empty($data['nom'])) {
    $_SESSION['user_nom'] = $data['nom'];
}
if ($photo_url) {
    $_SESSION['user_photo'] = $photo_url;
}

$response = ['success' => true];
if ($photo_url) {
    $response['photo_url'] = $photo_url;
}

echo json_encode($response);
?>
