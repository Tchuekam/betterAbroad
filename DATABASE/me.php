<?php
// ============================================================
//  auth/me.php  GET /api/auth/me.php
//  Returns current user + full profile (used on page load)
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

function me_users_supports_tos_accepted($conn) {
    static $supports = null;
    if ($supports !== null) {
        return $supports;
    }

    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'tos_accepted'");
    $supports = $result && (bool)$result->fetch_assoc();
    if ($result) {
        $result->free();
    }

    return $supports;
}

$auth = require_auth();   // exits with 401 if not logged in

$profile = [];
$tosAccepted = false;
if ($auth['role'] === 'student') {
    $stmt = $conn->prepare('SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $auth['id']);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();

    // Attach document statuses
    $stmt = $conn->prepare(
        'SELECT doc_type, status FROM documents WHERE user_id = ?'
    );
    $stmt->bind_param('i', $auth['id']);
    $stmt->execute();
    $docs = [];
    $res  = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $docs[$row['doc_type']] = $row['status'];
    $profile['documents'] = $docs;
    $stmt->close();

} elseif ($auth['role'] === 'university') {
    $stmt = $conn->prepare('SELECT * FROM university_profiles WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $auth['id']);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT doc_type, status FROM documents WHERE user_id = ?'
    );
    $stmt->bind_param('i', $auth['id']);
    $stmt->execute();
    $docs = [];
    $res  = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $docs[$row['doc_type']] = $row['status'];
    $profile['documents'] = $docs;
    $stmt->close();

    if (me_users_supports_tos_accepted($conn)) {
        $stmt = $conn->prepare('SELECT tos_accepted FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $auth['id']);
        $stmt->execute();
        $tosAccepted = (bool)($stmt->get_result()->fetch_assoc()['tos_accepted'] ?? 0);
        $stmt->close();
    }
}

// Unread message count
$stmt = $conn->prepare(
    'SELECT COUNT(*) AS cnt FROM messages WHERE to_user_id = ? AND is_read = 0'
);
$stmt->bind_param('i', $auth['id']);
$stmt->execute();
$unread = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

respond([
    'success'       => true,
    'userId'        => $auth['id'],
    'email'         => $auth['email'],
    'role'          => $auth['role'],
    'verified'      => $profile['verified'] ?? null,
    'tosAccepted'   => $tosAccepted,
    'user'          => [
        'id' => $auth['id'],
        'email' => $auth['email'],
        'role' => $auth['role'],
        'verified' => $profile['verified'] ?? null,
        'tosAccepted' => $tosAccepted,
    ],
    'profile'       => $profile,
    'unread_count'  => (int)$unread,
]);
