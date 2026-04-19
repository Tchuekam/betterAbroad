<?php
require_once __DIR__ . '/db.php';

function enrollments_supports_enrolled_status($conn) {
    static $supports = null;
    if ($supports !== null) {
        return $supports;
    }

    $result = $conn->query("SHOW COLUMNS FROM applications LIKE 'status'");
    $row = $result ? $result->fetch_assoc() : null;
    if ($result) {
        $result->free();
    }

    $supports = $row && strpos((string)$row['Type'], "'enrolled'") !== false;
    return $supports;
}

function enrollments_ensure_enrolled_status($conn) {
    if (enrollments_supports_enrolled_status($conn)) {
        return;
    }

    $sql = "ALTER TABLE applications
            MODIFY status ENUM('new','review','interview','offer','enrolled','rejected','withdrawn')
            NOT NULL DEFAULT 'new'";
    if (!$conn->query($sql)) {
        fail('Failed to enable enrolled status: ' . $conn->error, 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}

$auth = require_auth();
if ($auth['role'] !== 'student') {
    fail('Only students can report enrollment.', 403);
}

$data = get_body();
$university_id = (int)($data['university_id'] ?? 0);
if (!$university_id) {
    fail('university_id required.');
}

enrollments_ensure_enrolled_status($conn);

$stmt = $conn->prepare(
    'SELECT id
       FROM applications
      WHERE student_id = ? AND university_id = ?
      LIMIT 1'
);
$stmt->bind_param('ii', $auth['id'], $university_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    fail('Application not found.', 404);
}

$application_id = (int)$application['id'];
$stmt = $conn->prepare(
    "UPDATE applications
        SET status = 'enrolled', updated_at = NOW()
      WHERE id = ? AND student_id = ?"
);
$stmt->bind_param('ii', $application_id, $auth['id']);
if (!$stmt->execute()) {
    $stmt->close();
    fail('Failed to report enrollment.', 500);
}
$stmt->close();

$stmt = $conn->prepare(
    'SELECT COALESCE(up.uni_name, u.email) AS uni_name
       FROM users u
       LEFT JOIN university_profiles up ON up.user_id = u.id
      WHERE u.id = ?
      LIMIT 1'
);
$stmt->bind_param('i', $university_id);
$stmt->execute();
$uni = $stmt->get_result()->fetch_assoc();
$stmt->close();

$uni_name = $uni['uni_name'] ?? 'the selected university';

$stmt = $conn->prepare(
    'INSERT INTO notifications (user_id, type, title, body)
     VALUES (?, ?, ?, ?)'
);
$type = 'application_update';
$title = 'Enrollment reported';
$body = 'A student reported enrollment at ' . $uni_name . '.';
$stmt->bind_param('isss', $university_id, $type, $title, $body);
$stmt->execute();
$stmt->close();

$admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
if ($admins) {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (user_id, type, title, body)
         VALUES (?, ?, ?, ?)'
    );
    while ($admin_row = $admins->fetch_assoc()) {
        $admin_id = (int)$admin_row['id'];
        $admin_type = 'admin_alert';
        $admin_title = 'Commission invoice issued';
        $admin_body = 'Student self-reported enrollment for application #' . $application_id . '.';
        $stmt->bind_param('isss', $admin_id, $admin_type, $admin_title, $admin_body);
        $stmt->execute();
    }
    $stmt->close();
}

respond([
    'success' => true,
    'application_id' => $application_id,
    'status' => 'enrolled',
]);
