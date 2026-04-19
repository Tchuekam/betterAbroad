<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}

$auth = require_auth();
if (!in_array($auth['role'], ['student', 'university'], true)) {
    fail('Unsupported role.', 403);
}

$data = get_body();
$raw = trim((string)($data['raw_description'] ?? ''));
if ($raw === '') {
    fail('raw_description required.');
}

$clean = preg_replace('/\s+/', ' ', $raw);
$clean = trim($clean);

if ($auth['role'] === 'student') {
    $prefix = 'Profile summary: ';
    if (stripos($clean, $prefix) !== 0) {
        $clean = $prefix . $clean;
    }
}

respond([
    'success' => true,
    'ai_description' => $clean,
]);
