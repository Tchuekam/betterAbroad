<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/auth_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['user_id'])) {
        Response::error('Unauthenticated.', 401);
    }

    Response::success([
        'userId' => (int) $_SESSION['user_id'],
        'email'  => (string) ($_SESSION['email'] ?? ''),
        'role'   => (string) ($_SESSION['role'] ?? ''),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $payload = Request::json();
    $authService = new AuthService();
    $authService->login($payload);
} catch (Throwable $e) {
    Response::error('Login failed.', 500, [
        'code' => 'AUTH_LOGIN_ERROR',
        'detail' => $e->getMessage(),
    ]);
}
