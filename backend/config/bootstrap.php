<?php
declare(strict_types=1);

// Global bootstrap for API entrypoints
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

ob_start();

// Basic CORS (expand as needed)
$allowedOrigins = [
    'http://localhost',
    'http://localhost:80',
    'http://localhost:8000',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://127.0.0.1',
    'http://127.0.0.1:5173',
    'http://192.168.1.100',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (
    in_array($origin, $allowedOrigins, true) ||
    preg_match('/^https?:\\/\\/localhost(:\\d+)?$/', $origin) === 1 ||
    preg_match('/^https?:\\/\\/127\\.0\\.0\\.1(:\\d+)?$/', $origin) === 1
) {
    header("Access-Control-Allow-Origin: {$origin}");
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sessions
if (session_status() === PHP_SESSION_NONE) {
    session_name('BA_SESSION');
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/request.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';
