<?php
// ============================================================
//  db.php — Database connection + shared helpers
//  Include this at the top of every API file
// ============================================================

// 🛡️ FIX: Buffer ALL output so stray PHP warnings never corrupt JSON responses
ob_start();

// ── CORS (allow your frontend origin) ──────────────────────
$allowed_origins = [
    'http://localhost',
    'http://localhost:80',
    'http://localhost:8000',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://127.0.0.1',
    'http://127.0.0.1:5173',
    'http://192.168.1.100',  // Local network access
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (
    in_array($origin, $allowed_origins, true) ||
    preg_match('/^https?:\/\/localhost(:\d+)?$/', $origin) ||
    preg_match('/^https?:\/\/127\.0\.0\.1(:\d+)?$/', $origin)
) {
    header("Access-Control-Allow-Origin: $origin");
}

// 🛡️ FIX: Never print errors to the response — log them to Apache error log instead
if (php_sapi_name() !== 'cli') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── SESSION ─────────────────────────────────────────────────
session_name('BA_SESSION');
session_start();

// ── DATABASE CONFIG ─────────────────────────────────────────
// Load .env if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'betterabroad';

// ── CONNECT ─────────────────────────────────────────────────
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error'   => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// ── HELPERS ─────────────────────────────────────────────────

/** Send JSON response and exit */
function respond($data, $code = 200) {
    ob_end_clean(); // 🛡️ FIX: discard any buffered warnings before sending clean JSON
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/** Send error and exit */
function fail($message, $code = 400) {
    respond(['success' => false, 'error' => $message], $code);
}

/** Get JSON request body */
function get_body() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/** Require authenticated session — returns current user array */
function require_auth() {
    if (empty($_SESSION['user_id'])) {
        fail('Unauthenticated. Please log in.', 401);
    }
    return [
        'id'    => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

/**
 * Require admin role.
 */
function require_admin() {
    $auth = require_auth();
    if ($auth['role'] !== 'admin') {
        fail('Forbidden. Admin access required.', 403);
    }
    return $auth;
}

/** Sanitize string */
function clean($conn, $val) {
    return $conn->real_escape_string(trim($val ?? ''));
}

/** Log admin action */
function log_action($conn, $admin_id, $action, $target_type = null, $target_id = null, $details = null) {
    $ip          = clean($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    $action      = clean($conn, $action);
    $target_type = clean($conn, $target_type);
    $stmt = $conn->prepare(
        'INSERT INTO admin_log (admin_id, action, target_type, target_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ississ', $admin_id, $action, $target_type, $target_id, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
