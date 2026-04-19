<?php
// ============================================================
//  send.php  POST /api/send.php
//  Body: { to_user_id, body }
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$auth = require_auth();
$data = get_body();

$to_user_id = (int)($data['to_user_id'] ?? 0);
$body       = trim($data['body']        ?? '');

if (!$to_user_id)         fail('Recipient is required.');
if (!$body)               fail('Message body is required.');
if (strlen($body) > 5000) fail('Message too long. Max 5000 characters.');
if ($to_user_id === $auth['id']) fail('Cannot message yourself.');

// ── ANTI-BYPASS FILTER (Task A2) ─────────────────────────────
/**
 * Detect contact information in a message body.
 * Blocks email addresses, phone numbers, WhatsApp, and Telegram links.
 */
function contactInfoDetected(string $text): bool {
    $patterns = [
        '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', // email
        '/(?:\+|00)?\d[\d\s\-\.]{7,}\d/',                       // phone
        '/wa\.me\//i',                                           // WhatsApp link
        '/whatsapp/i',                                           // WhatsApp mention
        '/t\.me\//i',                                            // Telegram
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $text)) return true;
    }
    return false;
}

if (contactInfoDetected($body)) {
    fail('Messages cannot contain contact information (email, phone, or links). Please use the platform for all communication.', 422);
}
// ─────────────────────────────────────────────────────────────

// Verify recipient exists
$stmt = $conn->prepare('SELECT id, role FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('i', $to_user_id);
$stmt->execute();
$recipient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$recipient) fail('Recipient not found.', 404);

// Insert message
$stmt = $conn->prepare(
    'INSERT INTO messages (from_user_id, to_user_id, body) VALUES (?, ?, ?)'
);
$stmt->bind_param('iis', $auth['id'], $to_user_id, $body);
if (!$stmt->execute()) fail('Failed to send message.', 500);
$msg_id = $stmt->insert_id;
$stmt->close();

// Create notification for recipient
$notif_title = 'New message received';
$notif_body  = substr($body, 0, 100) . (strlen($body) > 100 ? '...' : '');
$stmt = $conn->prepare(
    'INSERT INTO notifications (user_id, type, title, body) VALUES (?, \'new_message\', ?, ?)'
);
$stmt->bind_param('iss', $to_user_id, $notif_title, $notif_body);
$stmt->execute();
$stmt->close();

respond([
    'success'    => true,
    'message_id' => $msg_id,
    'created_at' => date('Y-m-d H:i:s'),
]);
