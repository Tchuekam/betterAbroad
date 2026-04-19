<?php
// ============================================================
//  users.php  GET
//  ?role=student|university|all_documents
//  &status=pending|verified|rejected
//  &search=...
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);
require_admin();

$role   = clean($conn, $_GET['role']   ?? '');
$status = clean($conn, $_GET['status'] ?? '');
$search = clean($conn, $_GET['search'] ?? '');

// ── All documents view (admin documents panel) ────────────────
if ($role === 'all_documents') {
    $stmt = $conn->prepare(
        'SELECT d.id, d.user_id, d.doc_type, d.file_name, d.file_path,
                d.status, d.uploaded_at,
                u.role AS user_role,
                COALESCE(sp.full_name, up.uni_name, u.email) AS owner_name,
                u.email
         FROM documents d
         JOIN users u ON u.id = d.user_id
         LEFT JOIN student_profiles    sp ON sp.user_id = d.user_id
         LEFT JOIN university_profiles up ON up.user_id = d.user_id
         ORDER BY d.uploaded_at DESC
         LIMIT 300'
    );
    $stmt->execute();
    $docs = [];
    $res  = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $docs[] = $row;
    $stmt->close();
    respond(['success' => true, 'documents' => $docs]);
}

// ── Students ──────────────────────────────────────────────────
if ($role === 'student' || !$role) {
    $where = ['u.role = \'student\'', 'u.is_active = 1'];
    $params = []; $types = '';

    if ($status) { $where[] = 'sp.verified = ?'; $params[] = $status; $types .= 's'; }
    if ($search) {
        $like = '%'.$search.'%';
        $where[] = '(sp.full_name LIKE ? OR u.email LIKE ?)';
        $params = array_merge($params, [$like, $like]);
        $types .= 'ss';
    }

    $sql = 'SELECT u.id, u.email, u.created_at, u.last_login,
                sp.full_name, sp.nationality, sp.gpa, sp.major, sp.intake,
                sp.budget, sp.verified, sp.completion_pct, sp.verification_note,
                (SELECT COUNT(*) FROM documents d WHERE d.user_id = u.id) AS doc_count
            FROM users u
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            WHERE '.implode(' AND ', $where).'
            ORDER BY u.created_at DESC LIMIT 200';

    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $students[] = $row;
    $stmt->close();

    if ($role === 'student') {
        respond(['success' => true, 'students' => $students]);
    }
}

// ── Universities ──────────────────────────────────────────────
if ($role === 'university' || !$role) {
    $where = ['u.role = \'university\'', 'u.is_active = 1'];
    $params = []; $types = '';

    if ($status) { $where[] = 'up.verified = ?'; $params[] = $status; $types .= 's'; }
    if ($search) {
        $like = '%'.$search.'%';
        $where[] = '(up.uni_name LIKE ? OR u.email LIKE ?)';
        $params = array_merge($params, [$like, $like]);
        $types .= 'ss';
    }

    $sql = 'SELECT u.id, u.email, u.created_at, u.last_login,
                up.uni_name, up.country, up.website, up.programs,
                up.intake_periods, up.verified, up.verification_note,
                (SELECT COUNT(*) FROM documents d WHERE d.user_id = u.id) AS doc_count,
                (SELECT COUNT(*) FROM applications a WHERE a.university_id = u.id) AS app_count
            FROM users u
            LEFT JOIN university_profiles up ON up.user_id = u.id
            WHERE '.implode(' AND ', $where).'
            ORDER BY u.created_at DESC LIMIT 200';

    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $universities = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $universities[] = $row;
    $stmt->close();

    if ($role === 'university') {
        respond(['success' => true, 'universities' => $universities]);
    }
}

// Both roles (no ?role param)
respond([
    'success'      => true,
    'students'     => $students     ?? [],
    'universities' => $universities ?? [],
]);
