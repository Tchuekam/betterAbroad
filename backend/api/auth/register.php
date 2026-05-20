<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/auth_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $payload     = Request::json();
    $authService = new AuthService();
    $authService->register($payload);
} catch (Throwable $e) {
    Response::error('Registration failed.', 500, [
        'code'   => 'AUTH_REGISTER_ERROR',
        'detail' => $e->getMessage(),
    ]);
}
