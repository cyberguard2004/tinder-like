<?php
// api/login-password.php

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Corps JSON invalide.']);
    exit;
}

$email    = trim(strtolower($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis.']);
    exit;
}

$database  = new Database();
$db        = $database->getConnection();
$userModel = new User($db);

$user = $userModel->findByEmail($email);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
    exit;
}

if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
    exit;
}

if (!$user['actif']) {
    echo json_encode(['success' => false, 'error' => 'Compte désactivé.']);
    exit;
}

// ----- Set session -----
$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['user_nom']   = $user['nom'];
$_SESSION['user_photo'] = $user['photo_url'] ?? '';

// ----- Update dernier_login -----
$db->prepare("UPDATE users SET dernier_login = NOW() WHERE id = ?")
   ->execute([$user['id']]);

echo json_encode([
    'success'  => true,
    'user'     => [
        'id'        => (int)$user['id'],
        'nom'       => $user['nom'],
        'photo_url' => $user['photo_url'] ?? '',
    ],
    'redirect' => '/tinder/pages/dashboard.php',
]);
?>
