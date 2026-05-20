<?php
// ============================================================
//  marketplace/universities.php  GET
//  Query params: search, country, type, sort
//  Returns list of verified universities (for student users)
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$auth = require_auth();
if ($auth['role'] !== 'student' && $auth['role'] !== 'admin') {
    fail('Only students can browse universities.', 403);
}

$search   = clean($conn, $_GET['search']   ?? '');
$country  = clean($conn, $_GET['country']  ?? '');
$verified = clean($conn, $_GET['verified'] ?? '');
$sort     = clean($conn, $_GET['sort']     ?? 'name_asc');

$where  = ['up.uni_name IS NOT NULL'];
$params = [];
$types  = '';

if ($search) {
    $like    = '%' . $search . '%';
    $where[] = '(up.uni_name LIKE ? OR up.country LIKE ? OR up.programs LIKE ?)';
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= 'sss';
}
if ($country) {
    $where[]  = 'up.country = ?';
    $params[] = $country;
    $types   .= 's';
}
if (in_array($verified, ['pending', 'verified', 'rejected'], true)) {
    $where[]  = 'up.verified = ?';
    $params[] = $verified;
    $types   .= 's';
}

$order = match($sort) {
    'name_asc'  => 'up.uni_name ASC',
    'name_desc' => 'up.uni_name DESC',
    'newest'    => 'u.created_at DESC',
    default     => 'up.uni_name ASC',
};

$sql = 'SELECT
    u.id,
    u.email,
    u.created_at AS registered_at,
    up.uni_name,
    up.country,
    up.website,
    up.programs,
    up.intake_periods,
    up.description,
    up.verified,
    (SELECT COUNT(*) FROM applications a WHERE a.university_id = u.id) AS application_count
  FROM users u
  INNER JOIN university_profiles up ON up.user_id = u.id
  WHERE u.role = \'university\'
    AND u.is_active = 1
    AND ' . implode(' AND ', $where) . '
  ORDER BY ' . $order . '
  LIMIT 100';

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result        = $stmt->get_result();
$universities  = [];
while ($row = $result->fetch_assoc()) $universities[] = $row;
$stmt->close();

respond(['success' => true, 'universities' => $universities, 'total' => count($universities)]);
