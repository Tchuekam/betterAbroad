<?php
// ============================================================
//  search/universities.php  GET /api/search/universities.php
//  Search for universities with filters
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$search = clean($conn, $_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$query = 'SELECT u.id, v.uni_name, v.country, v.programs, v.verified FROM users u JOIN university_profiles v ON u.id = v.user_id WHERE u.role = "university" AND v.verified = "verified"';

if ($search) {
    $query .= " AND (v.uni_name LIKE '%$search%' OR v.country LIKE '%$search%' OR v.programs LIKE '%$search%')";
}

$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$universities = [];
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}

respond(['success' => true, 'universities' => $universities]);

?>
