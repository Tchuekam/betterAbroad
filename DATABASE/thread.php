<?php
// ============================================================
//  messages/thread.php
//  GET  ?with=USER_ID   — fetch thread, marks received messages as read
//  POST { to_user_id, body } — same as send.php (convenience alias)
// ============================================================
require_once __DIR__ . '/db.php';

$auth = require_auth();
$uid  = $auth['id'];

// ── GET: Fetch thread ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $with = (int)($_GET['with'] ?? 0);
    if (!$with) fail('Missing ?with=USER_ID');

    // Fetch messages
    $stmt = $conn->prepare('
        SELECT
            m.id,
            m.from_user_id,
            m.to_user_id,
            m.body,
            m.is_read,
            m.created_at,
            COALESCE(sp.full_name, up.uni_name, u.email) AS sender_name
        FROM messages m
        JOIN users u ON u.id = m.from_user_id
        LEFT JOIN student_profiles    sp ON sp.user_id = m.from_user_id
        LEFT JOIN university_profiles up ON up.user_id = m.from_user_id
        WHERE (m.from_user_id = ? AND m.to_user_id = ?)
           OR (m.from_user_id = ? AND m.to_user_id = ?)
        ORDER BY m.created_at ASC
        LIMIT 200
    ');
    $stmt->bind_param('iiii', $uid, $with, $with, $uid);
    $stmt->execute();
    $result   = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) $messages[] = $row;
    $stmt->close();

    // Mark received messages as read
    $stmt = $conn->prepare('
        UPDATE messages
        SET is_read = 1, read_at = NOW()
        WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0
    ');
    $stmt->bind_param('ii', $uid, $with);
    $stmt->execute();
    $stmt->close();

    // Fetch contact info
    $stmt = $conn->prepare('
        SELECT u.id, u.email, u.role,
            COALESCE(sp.full_name, up.uni_name, u.email) AS name
        FROM users u
        LEFT JOIN student_profiles    sp ON sp.user_id = u.id
        LEFT JOIN university_profiles up ON up.user_id = u.id
        WHERE u.id = ?
    ');
    $stmt->bind_param('i', $with);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    respond(['success' => true, 'messages' => $messages, 'contact' => $contact]);

// ── POST: Send in thread ─────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data       = get_body();
    $to_user_id = (int)($data['to_user_id'] ?? 0);
    $body       = trim($data['body']        ?? '');

    if (!$to_user_id) fail('to_user_id required.');
    if (!$body)       fail('body required.');
    if ($to_user_id === $uid) fail('Cannot message yourself.');

    $stmt = $conn->prepare('INSERT INTO messages (from_user_id, to_user_id, body) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $uid, $to_user_id, $body);
    if (!$stmt->execute()) fail('Send failed.', 500);
    $msg_id = $stmt->insert_id;
    $stmt->close();

    // Notification
    $notif_body = substr($body, 0, 100);
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, type, title, body) VALUES (?, \'new_message\', \'New message\', ?)');
    $stmt->bind_param('is', $to_user_id, $notif_body);
    $stmt->execute();
    $stmt->close();

    respond([
        'success'    => true,
        'message_id' => $msg_id,
        'from'       => $uid,
        'body'       => $body,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} else {
    fail('Method not allowed', 405);
}
