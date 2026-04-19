<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, email, password, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$email, $passwordHash, $role]);
        return (int) $this->db->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
