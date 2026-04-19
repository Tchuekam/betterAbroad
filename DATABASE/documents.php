<?php
// ============================================================
//  documents/list.php  GET /api/documents/list.php
//  Get all documents for authenticated user
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);

$auth = require_auth();

$stmt = $conn->prepare(
    'SELECT id, doc_type, file_name, file_path, status, uploaded_at FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC'
);
$stmt->bind_param('i', $auth['id']);
$stmt->execute();
$documents = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $documents[] = $row;
}
$stmt->close();

respond(['success' => true, 'documents' => $documents]);

?>
