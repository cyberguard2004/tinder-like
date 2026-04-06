<?php
// api/login-face.php

// session_start() MUST come before any output
session_start();

error_reporting(0);
ini_set("display_errors", "0");

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit();
}

require_once "/tinder/config/database.php";
require_once "/tinder/models/User.php";

$input = json_decode(file_get_contents("php://input"), true);
if (
    !$input ||
    empty($input["face_vector"]) ||
    !is_array($input["face_vector"])
) {
    echo json_encode([
        "success" => false,
        "error" => "Vecteur facial manquant ou invalide.",
    ]);
    exit();
}

$face_vector_array = $input["face_vector"];
$threshold = isset($input["threshold"]) ? (float) $input["threshold"] : 0.55;

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$user = $userModel->findByFaceVector($face_vector_array, $threshold);

if (!$user) {
    echo json_encode([
        "success" => false,
        "error" => "Visage non reconnu. Veuillez vous inscrire.",
    ]);
    exit();
}

// ----- Set session -----
$_SESSION["user_id"] = (int) $user["id"];
$_SESSION["user_nom"] = $user["nom"];
$_SESSION["user_photo"] = $user["photo_url"] ?? "";

// ----- Update dernier_login -----
$db->prepare("UPDATE users SET dernier_login = NOW() WHERE id = ?")->execute([
    $user["id"],
]);

echo json_encode([
    "success" => true,
    "user" => [
        "id" => (int) $user["id"],
        "nom" => $user["nom"],
        "photo_url" => $user["photo_url"] ?? "",
    ],
    "redirect" => "/tinder/pages/dashboard.php",
]);
?>
