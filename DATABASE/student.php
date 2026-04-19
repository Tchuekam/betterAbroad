<?php
// ============================================================
//  profiles/student.php  GET /api/profiles/student/{id}
//  Get a specific student's public profile
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$student_id) fail('Student ID required', 400);

$stmt = $conn->prepare(
    'SELECT u.id, u.email, s.full_name, s.nationality, s.gpa, s.major, NULL AS intake, s.budget, s.description, s.completion_pct, s.verified
     FROM users u
     JOIN student_profiles s ON u.id = s.user_id
     WHERE u.id = ? AND u.role = "student" LIMIT 1'
);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) fail('Student not found', 404);

respond(['success' => true, 'student' => $student]);

?>
