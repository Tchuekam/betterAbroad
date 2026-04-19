<?php
// ============================================================
//  verify.php  POST
//  Body A: { document_id, action: 'approved'|'rejected'|'pending' }
//  Body B: { user_id, type: 'student'|'university',
//            action: 'verified'|'rejected', note }
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$admin = require_admin();
$data  = get_body();

$doc_id  = (int)($data['document_id'] ?? 0);
$user_id = (int)($data['user_id']     ?? 0);
$action  = clean($conn, $data['action'] ?? '');
$note    = clean($conn, $data['note']   ?? '');

// ── Branch A: Document approval ──────────────────────────────
if ($doc_id) {
    $allowed = ['approved','rejected','pending'];
    if (!in_array($action, $allowed)) {
        fail('Invalid document action. Use approved, rejected, or pending.');
    }

    $stmt = $conn->prepare('UPDATE documents SET status=? WHERE id=?');
    $stmt->bind_param('si', $action, $doc_id);
    if (!$stmt->execute() || $stmt->affected_rows === 0) {
        fail('Document update failed or not found.', 404);
    }
    $stmt->close();

    log_action($conn, $admin['id'], 'document_' . $action, 'document', $doc_id, $note);
    respond(['success' => true, 'action' => $action, 'document_id' => $doc_id]);
}

// ── Branch B: Profile verification ───────────────────────────
$type = clean($conn, $data['type'] ?? '');

if (!$user_id)                                     fail('user_id required.');
if (!in_array($type, ['student','university']))    fail('Invalid type.');
if (!in_array($action, ['verified','rejected']))   fail('Invalid action. Use verified or rejected.');

$table = $type === 'student' ? 'student_profiles' : 'university_profiles';

$stmt = $conn->prepare(
    "UPDATE {$table}
     SET verified = ?, verified_at = NOW(), verified_by = ?, verification_note = ?
     WHERE user_id = ?"
);
$stmt->bind_param('sisi', $action, $admin['id'], $note, $user_id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    $stmt->close();
    fail('Update failed or profile not found.', 404);
}
$stmt->close();

// Notify the user
$notif_type  = $action === 'verified' ? 'verified' : 'rejected';
$notif_title = $action === 'verified'
    ? 'Your profile has been verified! ✓'
    : 'Your profile verification was not approved.';
$notif_body  = $note ?: ($action === 'verified'
    ? 'Congratulations! Your profile is now live on the marketplace.'
    : 'Please check the note from our team and resubmit your documents.');

$stmt = $conn->prepare(
    'INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('isss', $user_id, $notif_type, $notif_title, $notif_body);
$stmt->execute();
$stmt->close();

log_action($conn, $admin['id'], $action, $type, $user_id, $note);

respond([
    'success' => true,
    'action'  => $action,
    'user_id' => $user_id,
    'message' => "Profile {$action} and user notified.",
]);
