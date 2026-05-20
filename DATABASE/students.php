<?php
// ============================================================
//  marketplace/students.php  GET
//  Query params: search, major, nationality, budget, verified, sort
//  Returns list of verified students (for university users)
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$auth = require_auth();
if ($auth['role'] !== 'university' && $auth['role'] !== 'admin') {
    fail('Only universities can browse students.', 403);
}

// ── Filters ──────────────────────────────────────────────────
$search      = clean($conn, $_GET['search']      ?? '');
$major       = clean($conn, $_GET['major']       ?? '');
$nationality = clean($conn, $_GET['nationality'] ?? '');
$budget      = clean($conn, $_GET['budget']      ?? '');
$verified    = clean($conn, $_GET['verified']    ?? '');   // 'verified' | 'pending'
$sort        = clean($conn, $_GET['sort']        ?? 'gpa_desc');

// ── Build query ──────────────────────────────────────────────
$where  = ['sp.full_name IS NOT NULL'];
$params = [];
$types  = '';

if ($search) {
    $like    = '%' . $search . '%';
    $where[] = '(sp.full_name LIKE ? OR sp.major LIKE ? OR sp.nationality LIKE ? OR u.email LIKE ?)';
    $params  = array_merge($params, [$like, $like, $like, $like]);
    $types  .= 'ssss';
}
if ($major) {
    $where[]  = 'sp.major LIKE ?';
    $params[] = '%' . $major . '%';
    $types   .= 's';
}
if ($nationality) {
    $where[]  = 'sp.nationality = ?';
    $params[] = $nationality;
    $types   .= 's';
}
if ($budget) {
    $where[]  = 'sp.budget = ?';
    $params[] = $budget;
    $types   .= 's';
}
if (in_array($verified, ['pending', 'verified', 'rejected'], true)) {
    $where[]  = 'sp.verified = ?';
    $params[] = $verified;
    $types   .= 's';
} elseif ($auth['role'] !== 'admin') {
    $where[] = 'sp.verified = \'verified\'';
}

$order = match($sort) {
    'gpa_desc'  => 'sp.gpa DESC',
    'gpa_asc'   => 'sp.gpa ASC',
    'name_asc'  => 'sp.full_name ASC',
    'newest'    => 'u.created_at DESC',
    default     => 'sp.gpa DESC',
};

$sql = 'SELECT
    u.id,
    u.email,
    u.created_at AS registered_at,
    sp.full_name,
    sp.dob,
    sp.nationality,
    sp.gpa,
    sp.major,
    sp.budget,
    sp.description,
    sp.verified,
    sp.completion_pct,
    (SELECT COUNT(*) FROM documents d WHERE d.user_id = u.id) AS doc_count
  FROM users u
  INNER JOIN student_profiles sp ON sp.user_id = u.id
  WHERE u.role = \'student\'
    AND u.is_active = 1
    AND ' . implode(' AND ', $where) . '
  ORDER BY ' . $order . '
  LIMIT 100';

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result   = $stmt->get_result();
$students = [];

while ($row = $result->fetch_assoc()) {
    // Don't expose email to universities unless they're verified
    if ($auth['role'] !== 'admin') unset($row['email']);
    $students[] = $row;
}
$stmt->close();

respond(['success' => true, 'students' => $students, 'total' => count($students)]);
