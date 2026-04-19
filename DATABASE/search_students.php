<?php
// ============================================================
//  search/students.php  GET /api/search/students.php
//  Search for students with filters
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$search = clean($conn, $_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$query = 'SELECT u.id, s.full_name, s.nationality, s.gpa, s.major, s.completion_pct, s.verified FROM users u JOIN student_profiles s ON u.id = s.user_id WHERE u.role = "student" AND s.verified = "verified"';

if ($search) {
    $query .= " AND (s.full_name LIKE '%$search%' OR s.nationality LIKE '%$search%' OR s.major LIKE '%$search%')";
}

$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

respond(['success' => true, 'students' => $students]);

?>
