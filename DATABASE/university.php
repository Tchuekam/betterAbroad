<?php
// ============================================================
//  profiles/university.php  GET /api/profiles/university/{id}
//  Get a specific university's public profile
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$university_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$university_id) fail('University ID required', 400);

$stmt = $conn->prepare(
    'SELECT u.id, u.email, v.uni_name, v.country, v.website, v.programs, v.intake_periods, v.description, v.verified
     FROM users u
     JOIN university_profiles v ON u.id = v.user_id
     WHERE u.id = ? AND u.role = "university" LIMIT 1'
);
$stmt->bind_param('i', $university_id);
$stmt->execute();
$university = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$university) fail('University not found', 404);

respond(['success' => true, 'university' => $university]);

?>
