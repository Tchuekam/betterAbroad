<?php
require_once __DIR__ . '/db.php';

function notifications_supports_read_at($conn) {
    static $supports = null;

    if ($supports !== null) {
        return $supports;
    }

    $result = $conn->query(
        "SELECT 1
           FROM information_schema.columns
          WHERE table_schema = DATABASE()
            AND table_name = 'notifications'
            AND column_name = 'read_at'
          LIMIT 1"
    );

    $supports = $result && $result->num_rows > 0;
    if ($result) {
        $result->free();
    }

    return $supports;
}

$auth = require_auth();
$uid = (int)$auth['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare(
        'SELECT id, type, title, body, is_read, created_at
           FROM notifications
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 30'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($notifications as &$notification) {
        $notification['id'] = (int)$notification['id'];
        $notification['is_read'] = (int)$notification['is_read'];
    }
    unset($notification);

    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS unread_count
           FROM notifications
          WHERE user_id = ? AND is_read = 0'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $unread_count = (int)($stmt->get_result()->fetch_assoc()['unread_count'] ?? 0);
    $stmt->close();

    respond([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
    ]);
}

if ($method === 'POST') {
    $data = get_body();
    $use_read_at = notifications_supports_read_at($conn);

    $target_user_id = (int)($data['target_user_id'] ?? 0);
    $type = trim((string)($data['type'] ?? ''));
    $title = trim((string)($data['title'] ?? ''));
    $message = trim((string)($data['message'] ?? $data['body'] ?? ''));

    if ($type !== '' && ($target_user_id > 0 || $type === 'admin_alert')) {
        if ($title === '') {
            $title = match ($type) {
                'profile_viewed' => 'Your profile was viewed',
                'admin_alert' => 'Admin alert',
                default => ucwords(str_replace('_', ' ', $type)),
            };
        }

        if ($message === '') {
            $message = $title;
        }

        if ($type === 'admin_alert') {
            $admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
            if (!$admins) {
                fail('Failed to load admin users.', 500);
            }

            $stmt = $conn->prepare(
                'INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)'
            );
            while ($admin_row = $admins->fetch_assoc()) {
                $admin_id = (int)$admin_row['id'];
                $stmt->bind_param('isss', $admin_id, $type, $title, $message);
                if (!$stmt->execute()) {
                    $stmt->close();
                    fail('Failed to create admin notification.', 500);
                }
            }
            $stmt->close();

            respond(['success' => true]);
        }

        if (!$target_user_id) {
            fail('target_user_id required.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isss', $target_user_id, $type, $title, $message);
        if (!$stmt->execute()) {
            $stmt->close();
            fail('Failed to create notification.', 500);
        }
        $stmt->close();

        respond(['success' => true]);
    }

    if (!empty($data['mark_all'])) {
        $sql = $use_read_at
            ? 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ?'
            : 'UPDATE notifications SET is_read = 1 WHERE user_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $uid);
        if (!$stmt->execute()) {
            $stmt->close();
            fail('Failed to mark notifications as read.', 500);
        }
        $stmt->close();
        respond(['success' => true]);
    }

    $notification_id = (int)($data['notification_id'] ?? 0);
    if (!$notification_id) {
        fail('notification_id or mark_all required.');
    }

    $sql = $use_read_at
        ? 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?'
        : 'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $notification_id, $uid);
    if (!$stmt->execute()) {
        $stmt->close();
        fail('Failed to update notification.', 500);
    }
    $stmt->close();

    respond(['success' => true]);
}

fail('Method not allowed.', 405);
