<?php
// ============================================================
//  messages/unread.php  GET — returns unread count + notifications
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);
$auth = require_auth();
$uid  = $auth['id'];

$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM messages WHERE to_user_id = ? AND is_read = 0');
$stmt->bind_param('i', $uid);
$stmt->execute();
$unread_messages = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare(
    'SELECT id, type, title, body, created_at
     FROM notifications WHERE user_id = ? AND is_read = 0
     ORDER BY created_at DESC LIMIT 10'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$notifs = [];
$res    = $stmt->get_result();
while ($row = $res->fetch_assoc()) $notifs[] = $row;
$stmt->close();

respond([
    'success'         => true,
    'unread_messages' => $unread_messages,
    'notifications'   => $notifs,
]);
