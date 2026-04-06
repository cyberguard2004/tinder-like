<?php
// api/register.php

// Enable full error reporting but do not display errors to clients.
// Log errors to a file so API JSON responses remain clean while we can
// inspect server-side problems in the logs.
error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/../logs/api_errors.log");

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

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/User.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON body"]);
    exit();
}

// ----- Validate required fields -----
$required = ["nom", "email", "sexe", "date_naissance", "ville"];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            "success" => false,
            "error" => "Le champ '$field' est requis.",
        ]);
        exit();
    }
}

$nom = trim($input["nom"]);
$email = trim(strtolower($input["email"]));
$sexe = $input["sexe"];
$date_naissance = $input["date_naissance"];
$ville = trim($input["ville"]);
$telephone = trim($input["telephone"] ?? "");
$bio = trim($input["bio"] ?? "");
$face_vector = "";
$interets = $input["interets"] ?? [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "error" => "Adresse email invalide.",
    ]);
    exit();
}

if (!in_array($sexe, ["homme", "femme", "autre"], true)) {
    echo json_encode(["success" => false, "error" => "Sexe invalide."]);
    exit();
}

// ----- DB connection -----
$database = new Database();
$db = $database->getConnection();

$userModel = new User($db);

// ----- Check email uniqueness -----
if ($userModel->findByEmail($email)) {
    echo json_encode([
        "success" => false,
        "error" => "Cet email est déjà utilisé.",
    ]);
    exit();
}

// ----- Handle face_vector -----
if (!empty($input["face_vector"])) {
    $fv = $input["face_vector"];
    $face_vector = is_array($fv) ? json_encode($fv) : (string) $fv;
}

// ----- Handle photo (base64 JPEG) -----
$photo_url = "";
if (!empty($input["photo"])) {
    $base64Data = $input["photo"];
    // Strip data URI prefix if present
    if (strpos($base64Data, ",") !== false) {
        $base64Data = explode(",", $base64Data)[1];
    }
    $imageData = base64_decode($base64Data);
    if ($imageData !== false && strlen($imageData) > 0) {
        $uploadsDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $filename = "user_" . uniqid("", true) . ".jpg";
        $filepath = $uploadsDir . $filename;
        if (file_put_contents($filepath, $imageData) !== false) {
            $photo_url = "/tinder/uploads/" . $filename;
        }
    }
}

// ----- Hash password -----
$password_hash = "";
if (!empty($input["password"])) {
    $password_hash = password_hash($input["password"], PASSWORD_BCRYPT);
}

// ----- Populate User model -----
$userModel->nom = $nom;
$userModel->email = $email;
$userModel->password_hash = $password_hash;
$userModel->telephone = $telephone;
$userModel->sexe = $sexe;
$userModel->date_naissance = $date_naissance;
$userModel->bio = $bio;
$userModel->face_vector = $face_vector;
$userModel->photo_url = $photo_url;
$userModel->ville = $ville;

$user_id = $userModel->create();

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "error" => "Erreur lors de la création du compte.",
    ]);
    exit();
}

// ----- Insert user interests -----
if (!empty($interets) && is_array($interets)) {
    $stmtInt = $db->prepare(
        "INSERT IGNORE INTO user_interets (user_id, interet_id) VALUES (:uid, :iid)",
    );
    foreach ($interets as $interet_id) {
        $stmtInt->execute([":uid" => $user_id, ":iid" => (int) $interet_id]);
    }
}

echo json_encode([
    "success" => true,
    "user_id" => $user_id,
    "message" => "Compte créé avec succès.",
]);
?>
