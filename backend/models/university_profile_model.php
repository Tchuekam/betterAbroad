<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class UniversityProfileModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM university_profiles WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        return $profile ?: null;
    }

    public function create(int $userId): void
    {
        $stmt = $this->db->prepare('INSERT INTO university_profiles (user_id) VALUES (?)');
        $stmt->execute([$userId]);
    }
}
