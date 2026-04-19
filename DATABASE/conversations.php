<?php
// ============================================================
//  messages/conversations.php  GET /api/messages/conversations.php
//  Returns list of unique conversations with last message + unread count
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$auth = require_auth();
$uid  = $auth['id'];

// Get all unique conversation partners with last message
$sql = '
SELECT
    other_user.id         AS contact_id,
    other_user.email      AS contact_email,
    other_user.role       AS contact_role,
    COALESCE(sp.full_name, up.uni_name, other_user.email) AS contact_name,
    last_msg.body         AS last_message,
    last_msg.created_at   AS last_message_at,
    last_msg.from_user_id AS last_from,
    SUM(CASE WHEN m.to_user_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
FROM messages m
JOIN users other_user ON other_user.id = CASE
    WHEN m.from_user_id = ? THEN m.to_user_id
    ELSE m.from_user_id
END
LEFT JOIN student_profiles    sp ON sp.user_id    = other_user.id
LEFT JOIN university_profiles up ON up.user_id    = other_user.id
JOIN (
    SELECT
        LEAST(from_user_id, to_user_id)    AS u1,
        GREATEST(from_user_id, to_user_id) AS u2,
        MAX(id)                            AS max_id
    FROM messages
    WHERE from_user_id = ? OR to_user_id = ?
    GROUP BY u1, u2
) latest ON latest.max_id = m.id
JOIN messages last_msg ON last_msg.id = latest.max_id
WHERE m.from_user_id = ? OR m.to_user_id = ?
GROUP BY other_user.id
ORDER BY last_msg.created_at DESC
LIMIT 50';

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiiiii', $uid, $uid, $uid, $uid, $uid, $uid);
$stmt->execute();
$result        = $stmt->get_result();
$conversations = [];
while ($row = $result->fetch_assoc()) $conversations[] = $row;
$stmt->close();

respond(['success' => true, 'conversations' => $conversations]);
