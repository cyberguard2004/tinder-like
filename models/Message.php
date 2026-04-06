<?php
// models/Message.php

class Message {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ------------------------------------------------------------------
    // Insert a message, return new id or false
    // ------------------------------------------------------------------
    public function send(int $match_id, int $sender_id, string $message): int|false {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO messages (match_id, sender_id, message, date_envoi, lu)
                 VALUES (:mid, :sid, :msg, NOW(), 0)"
            );
            $stmt->execute([
                ':mid' => $match_id,
                ':sid' => $sender_id,
                ':msg' => $message,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Get all messages for a match, ordered chronologically
    // ------------------------------------------------------------------
    public function getByMatch(int $match_id): array {
        $stmt = $this->db->prepare(
            "SELECT m.*,
                    u.nom        AS sender_name,
                    u.photo_url  AS sender_photo
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.match_id = ?
             ORDER BY m.date_envoi ASC"
        );
        $stmt->execute([$match_id]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // Mark all messages from the OTHER user in this match as read
    // ------------------------------------------------------------------
    public function markAsRead(int $match_id, int $user_id): bool {
        $stmt = $this->db->prepare(
            "UPDATE messages
             SET lu = 1
             WHERE match_id = ? AND sender_id != ?"
        );
        return $stmt->execute([$match_id, $user_id]);
    }

    // ------------------------------------------------------------------
    // Count unread messages sent by the other user in a match
    // ------------------------------------------------------------------
    public function getUnreadCount(int $match_id, int $user_id): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt
             FROM messages
             WHERE match_id = ? AND sender_id != ? AND lu = 0"
        );
        $stmt->execute([$match_id, $user_id]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
?>
