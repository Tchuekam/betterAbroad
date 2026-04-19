<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

final class AuthMiddleware
{
    public static function requireAuth(): array
    {
        if (empty($_SESSION['user_id'])) {
            Response::error('Unauthenticated. Please log in.', 401);
        }

        return [
            'id'    => (int) $_SESSION['user_id'],
            'email' => (string) ($_SESSION['email'] ?? ''),
            'role'  => (string) ($_SESSION['role'] ?? ''),
        ];
    }

    public static function requireRole(string $role): array
    {
        $user = self::requireAuth();
        $pdo  = Database::getConnection();

        $stmt = $pdo->prepare('SELECT id, email, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $freshUser = $stmt->fetch();

        if (!$freshUser || (string) $freshUser['role'] !== $role || (int) $freshUser['is_active'] !== 1) {
            Response::error('Forbidden.', 403);
        }

        return [
            'id'    => (int) $freshUser['id'],
            'email' => (string) $freshUser['email'],
            'role'  => (string) $freshUser['role'],
        ];
    }
}
