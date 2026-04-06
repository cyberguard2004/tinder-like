<?php
// api/get-interets.php

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

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db       = $database->getConnection();

// ----- Ensure table exists with emoji column -----
$db->exec("
    CREATE TABLE IF NOT EXISTS interets (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        nom       VARCHAR(50)  NOT NULL UNIQUE,
        categorie VARCHAR(50),
        emoji     VARCHAR(10)  DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Add emoji column if it's missing (upgrade path)
try {
    $db->exec("ALTER TABLE interets ADD COLUMN emoji VARCHAR(10) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists — ignore
}

// ----- Seed if table is empty -----
$count = (int)$db->query("SELECT COUNT(*) FROM interets")->fetchColumn();

if ($count === 0) {
    $interests = [
        // Sport
        ['Football',              'Sport',     '⚽'],
        ['Basketball',            'Sport',     '🏀'],
        ['Tennis',                'Sport',     '🎾'],
        ['Natation',              'Sport',     '🏊'],
        ['Cyclisme',              'Sport',     '🚴'],
        ['Running',               'Sport',     '🏃'],
        ['Volleyball',            'Sport',     '🏐'],
        ['Boxe',                  'Sport',     '🥊'],
        ['Surf',                  'Sport',     '🏄'],
        ['Ski',                   'Sport',     '⛷️'],
        // Culture
        ['Cinéma',                'Culture',   '🎬'],
        ['Lecture',               'Culture',   '📚'],
        ['Théâtre',               'Culture',   '🎭'],
        ['Musique',               'Culture',   '🎵'],
        ['Art',                   'Culture',   '🎨'],
        ['Exposition',            'Culture',   '🖼️'],
        ['Poésie',                'Culture',   '📝'],
        ['Podcast',               'Culture',   '🎙️'],
        // Loisirs
        ['Jeux vidéo',            'Loisirs',   '🎮'],
        ['Animaux',               'Loisirs',   '🐕'],
        ['Voyage',                'Loisirs',   '✈️'],
        ['Photographie',          'Loisirs',   '📸'],
        ['Jardinage',             'Loisirs',   '🌿'],
        ['Bricolage',             'Loisirs',   '🔧'],
        // Arts
        ['Danse',                 'Arts',      '💃'],
        ['Guitare',               'Arts',      '🎸'],
        ['Piano',                 'Arts',      '🎹'],
        ['Écriture',              'Arts',      '✍️'],
        ['Chant',                 'Arts',      '🎤'],
        ['Dessin',                'Arts',      '✏️'],
        // Bien-être
        ['Yoga',                  'Bien-être', '🧘'],
        ['Escalade',              'Bien-être', '🧗'],
        ['Randonnée',             'Bien-être', '🏕️'],
        ['Méditation',            'Bien-être', '🌸'],
        ['Pilates',               'Bien-être', '🤸'],
        // Sciences
        ['Technologie',           'Sciences',  '💻'],
        ['Sciences',              'Sciences',  '🔬'],
        ['Astronomie',            'Sciences',  '🚀'],
        ['Intelligence Artificielle', 'Sciences', '🤖'],
        // Cuisine
        ['Cuisine',               'Cuisine',   '👨‍🍳'],
        ['Œnologie',              'Cuisine',   '🍷'],
        ['Pâtisserie',            'Cuisine',   '🍰'],
        ['Street Food',           'Cuisine',   '🍜'],
    ];

    $stmt = $db->prepare(
        "INSERT IGNORE INTO interets (nom, categorie, emoji) VALUES (?, ?, ?)"
    );
    foreach ($interests as [$nom, $cat, $emoji]) {
        $stmt->execute([$nom, $cat, $emoji]);
    }
}

// ----- Fetch all interests -----
$stmt = $db->query(
    "SELECT id, nom, categorie, emoji FROM interets ORDER BY categorie, nom"
);
$rows = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data'    => $rows,
]);
?>
