<?php
// models/User.php

class User {
    private PDO $db;

    // Public properties for create()
    public string $nom          = '';
    public string $email        = '';
    public string $password_hash = '';
    public string $telephone    = '';
    public string $sexe         = 'autre';
    public string $date_naissance = '';
    public string $bio          = '';
    public string $face_vector  = '';
    public string $photo_url    = '';
    public string $ville        = '';

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ------------------------------------------------------------------
    // INSERT a new user, return lastInsertId or false
    // ------------------------------------------------------------------
    public function create(): int|false {
        $sql = "INSERT INTO users
                    (nom, email, password_hash, telephone, sexe,
                     date_naissance, bio, face_vector, photo_url, ville, actif)
                VALUES
                    (:nom, :email, :password_hash, :telephone, :sexe,
                     :date_naissance, :bio, :face_vector, :photo_url, :ville, 1)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom'            => $this->nom,
                ':email'          => $this->email,
                ':password_hash'  => $this->password_hash,
                ':telephone'      => $this->telephone,
                ':sexe'           => $this->sexe,
                ':date_naissance' => $this->date_naissance,
                ':bio'            => $this->bio,
                ':face_vector'    => $this->face_vector,
                ':photo_url'      => $this->photo_url,
                ':ville'          => $this->ville,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Find by email
    // ------------------------------------------------------------------
    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    // ------------------------------------------------------------------
    // Find by ID
    // ------------------------------------------------------------------
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    // ------------------------------------------------------------------
    // Find by face vector (Euclidean nearest-neighbour)
    // ------------------------------------------------------------------
    public function findByFaceVector(array $face_vector_array, float $threshold = 0.55): array|null {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE face_vector IS NOT NULL AND face_vector != '' AND actif = 1"
        );
        $stmt->execute();
        $users = $stmt->fetchAll();

        $bestUser = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            $stored = json_decode($user['face_vector'], true);
            if (!is_array($stored) || count($stored) === 0) {
                continue;
            }
            $dist = $this->calculateFaceDistance($face_vector_array, $stored);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestUser = $user;
            }
        }

        if ($bestUser !== null && $bestDist < $threshold) {
            return $bestUser;
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Euclidean distance between two equal-length float arrays
    // ------------------------------------------------------------------
    public function calculateFaceDistance(array $v1, array $v2): float {
        $len  = min(count($v1), count($v2));
        $sum  = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $diff = (float)$v1[$i] - (float)$v2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    // ------------------------------------------------------------------
    // Potential matches (exclude already-liked / self, rank by shared interests)
    // ------------------------------------------------------------------
    public function getPotentialMatches(int $user_id, int $limit = 20): array {
        $sql = "
            SELECT
                u.id,
                u.nom,
                u.date_naissance,
                u.bio,
                u.photo_url,
                u.ville,
                COUNT(DISTINCT ui_common.interet_id) AS interets_communs,
                GROUP_CONCAT(DISTINCT i.nom ORDER BY i.nom SEPARATOR ',') AS liste_interets
            FROM users u
            -- All interests of the candidate
            LEFT JOIN user_interets ui_common
                   ON ui_common.user_id = u.id
                  AND ui_common.interet_id IN (
                        SELECT interet_id FROM user_interets WHERE user_id = :uid1
                      )
            LEFT JOIN interets i ON i.id = ui_common.interet_id
            WHERE u.id != :uid2
              AND u.actif = 1
              AND u.id NOT IN (
                    SELECT to_user_id FROM likes WHERE from_user_id = :uid3
              )
            GROUP BY u.id, u.nom, u.date_naissance, u.bio, u.photo_url, u.ville
            ORDER BY interets_communs DESC, RAND()
            LIMIT :lim
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid1', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid3', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim',  $limit,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // Update profile fields
    // ------------------------------------------------------------------
    public function updateProfile(int $user_id, array $data): bool {
        $allowed = ['nom', 'bio', 'ville', 'photo_url'];
        $sets    = [];
        $params  = [':id' => $user_id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]         = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        if (empty($sets)) {
            return false;
        }
        $sql  = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ------------------------------------------------------------------
    // Update password
    // ------------------------------------------------------------------
    public function updatePassword(int $user_id, string $password_hash): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = :ph WHERE id = :id"
        );
        return $stmt->execute([':ph' => $password_hash, ':id' => $user_id]);
    }
}
?>
