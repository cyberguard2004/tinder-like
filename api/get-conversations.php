<?php
// api/get-conversations.php

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

$database = new Database();
$db       = $database->getConnection();

$user_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT
        m.id AS match_id,
        CASE WHEN m.user1_id = :uid1 THEN m.user2_id ELSE m.user1_id END AS other_user_id,
        u.nom        AS user_name,
        u.photo_url  AS user_photo,
        lm.message   AS last_message,
        lm.date_envoi AS last_message_date,
        (
            SELECT COUNT(*) FROM messages
            WHERE match_id = m.id
              AND sender_id != :uid2
              AND lu = 0
        ) AS unread_count
    FROM matches m
    JOIN users u ON u.id = CASE WHEN m.user1_id = :uid3 THEN m.user2_id ELSE m.user1_id END
    LEFT JOIN messages lm ON lm.id = (
        SELECT id FROM messages
        WHERE match_id = m.id
        ORDER BY date_envoi DESC
        LIMIT 1
    )
    WHERE m.user1_id = :uid4 OR m.user2_id = :uid5
    ORDER BY COALESCE(lm.date_envoi, m.created_at) DESC
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':uid1' => $user_id,
    ':uid2' => $user_id,
    ':uid3' => $user_id,
    ':uid4' => $user_id,
    ':uid5' => $user_id,
]);
$rows = $stmt->fetchAll();

$defaultAvatar = '/tinder/assets/images/default-avatar.jpg';
$conversations = [];

foreach ($rows as $row) {
    $conversations[] = [
        'match_id'          => (int)$row['match_id'],
        'user_id'           => (int)$row['other_user_id'],
        'user_name'         => $row['user_name'],
        'user_photo'        => !empty($row['user_photo']) ? $row['user_photo'] : $defaultAvatar,
        'last_message'      => $row['last_message'] ?? '',
        'last_message_date' => $row['last_message_date'] ?? '',
        'unread_count'      => (int)$row['unread_count'],
    ];
}

echo json_encode([
    'success'       => true,
    'conversations' => $conversations,
]);
?>
