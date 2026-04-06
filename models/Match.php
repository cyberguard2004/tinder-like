<?php
// models/Match.php
// NOTE: Class is named MatchModel because `match` is a reserved keyword in PHP 8+

class MatchModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ------------------------------------------------------------------
    // Create a new match, returns new id or false
    // ------------------------------------------------------------------
    public function create(int $user1_id, int $user2_id): int|false {
        // Always store with lower id first for consistency
        $u1 = min($user1_id, $user2_id);
        $u2 = max($user1_id, $user2_id);
        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO matches (user1_id, user2_id, created_at)
                 VALUES (:u1, :u2, NOW())"
            );
            $stmt->execute([':u1' => $u1, ':u2' => $u2]);
            $id = (int) $this->db->lastInsertId();
            if ($id > 0) {
                return $id;
            }
            // IGNORE fired — fetch existing row
            $existing = $this->findByUsers($u1, $u2);
            return $existing ? (int)$existing['id'] : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Find a match by both user IDs (order-independent)
    // ------------------------------------------------------------------
    public function findByUsers(int $u1, int $u2): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM matches
             WHERE (user1_id = :u1a AND user2_id = :u2a)
                OR (user1_id = :u2b AND user2_id = :u1b)
             LIMIT 1"
        );
        $stmt->execute([':u1a' => $u1, ':u2a' => $u2, ':u2b' => $u2, ':u1b' => $u1]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    // ------------------------------------------------------------------
    // Get all matches for a user with last message info
    // ------------------------------------------------------------------
    public function getMatchesForUser(int $user_id): array {
        $sql = "
            SELECT
                m.id              AS match_id,
                m.created_at,
                m.last_interaction,
                CASE WHEN m.user1_id = :uid1 THEN m.user2_id ELSE m.user1_id END AS other_user_id,
                u.nom             AS user_nom,
                u.photo_url       AS user_photo,
                u.ville           AS user_ville,
                u.date_naissance  AS user_date_naissance,
                lm.message        AS last_message,
                lm.date_envoi     AS last_message_date,
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
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid1' => $user_id,
            ':uid2' => $user_id,
            ':uid3' => $user_id,
            ':uid4' => $user_id,
            ':uid5' => $user_id,
        ]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // Count total unread messages for a user across all matches
    // ------------------------------------------------------------------
    public function getUnreadCount(int $user_id): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt
             FROM messages msg
             JOIN matches m ON m.id = msg.match_id
             WHERE (m.user1_id = :uid1 OR m.user2_id = :uid2)
               AND msg.sender_id != :uid3
               AND msg.lu = 0"
        );
        $stmt->execute([':uid1' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
?>
