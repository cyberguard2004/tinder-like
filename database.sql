-- MatchFace Database Schema
-- Created for tinder_like database

CREATE DATABASE IF NOT EXISTS tinder_like CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tinder_like;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    telephone VARCHAR(20),
    sexe ENUM('homme','femme','autre') NOT NULL,
    date_naissance DATE NOT NULL,
    bio TEXT,
    face_vector LONGTEXT,
    photo_url VARCHAR(255),
    ville VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    actif TINYINT(1) DEFAULT 1,
    dernier_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interests table
CREATE TABLE IF NOT EXISTS interets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    categorie VARCHAR(50),
    emoji VARCHAR(10) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User interests pivot
CREATE TABLE IF NOT EXISTS user_interets (
    user_id INT NOT NULL,
    interet_id INT NOT NULL,
    PRIMARY KEY (user_id, interet_id),
    CONSTRAINT fk_ui_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ui_interet FOREIGN KEY (interet_id) REFERENCES interets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    type ENUM('like','dislike','superlike') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (from_user_id, to_user_id),
    CONSTRAINT fk_like_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_like_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches table
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_interaction DATETIME,
    UNIQUE KEY unique_match (user1_id, user2_id),
    CONSTRAINT fk_match_u1 FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_u2 FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_msg_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA: Interests (30+ with emojis)
-- =============================================
INSERT IGNORE INTO interets (nom, categorie, emoji) VALUES
-- Sport
('Football', 'Sport', '⚽'),
('Basketball', 'Sport', '🏀'),
('Tennis', 'Sport', '🎾'),
('Natation', 'Sport', '🏊'),
('Cyclisme', 'Sport', '🚴'),
('Running', 'Sport', '🏃'),
('Volleyball', 'Sport', '🏐'),
('Boxe', 'Sport', '🥊'),
-- Culture
('Cinéma', 'Culture', '🎬'),
('Lecture', 'Culture', '📚'),
('Théâtre', 'Culture', '🎭'),
('Musique', 'Culture', '🎵'),
('Art', 'Culture', '🎨'),
('Exposition', 'Culture', '🖼️'),
('Poésie', 'Culture', '📝'),
-- Loisirs
('Jeux vidéo', 'Loisirs', '🎮'),
('Animaux', 'Loisirs', '🐕'),
('Voyage', 'Loisirs', '✈️'),
('Photographie', 'Loisirs', '📸'),
('Jardinage', 'Loisirs', '🌿'),
('Bricolage', 'Loisirs', '🔧'),
-- Arts
('Danse', 'Arts', '💃'),
('Guitare', 'Arts', '🎸'),
('Piano', 'Arts', '🎹'),
('Écriture', 'Arts', '✍️'),
('Chant', 'Arts', '🎤'),
('Dessin', 'Arts', '✏️'),
-- Bien-être
('Yoga', 'Bien-être', '🧘'),
('Escalade', 'Bien-être', '🧗'),
('Randonnée', 'Bien-être', '🏕️'),
('Méditation', 'Bien-être', '🌸'),
('Pilates', 'Bien-être', '🤸'),
-- Sciences
('Technologie', 'Sciences', '💻'),
('Sciences', 'Sciences', '🔬'),
('Astronomie', 'Sciences', '🚀'),
('Intelligence Artificielle', 'Sciences', '🤖'),
-- Cuisine
('Cuisine', 'Cuisine', '👨‍🍳'),
('Œnologie', 'Cuisine', '🍷'),
('Pâtisserie', 'Cuisine', '🍰'),
('Street Food', 'Cuisine', '🍜'),
-- Voyage (extra)
('Surf', 'Sport', '🏄'),
('Ski', 'Sport', '⛷️'),
('Podcast', 'Culture', '🎙️');
