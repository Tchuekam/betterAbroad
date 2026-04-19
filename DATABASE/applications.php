<?php
// ============================================================
//  applications.php
//  POST { university_id }         — student submits application
//  POST { id, status, admin_note } — university/admin updates status
//  GET                             — fetch applications for current user
// ============================================================
require_once __DIR__ . '/db.php';

$auth = require_auth();
$uid  = $auth['id'];

// ── POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = get_body();

    // ── Route A: Status update (university or admin) ──────────
    // Detected by presence of 'id' + 'status' in body
    if (!empty($data['id']) && !empty($data['status'])) {
        $app_id     = (int)$data['id'];
        $new_status = clean($conn, $data['status']);
        $admin_note = clean($conn, $data['admin_note'] ?? '');

        $allowed = ['new','review','interview','offer','rejected','withdrawn','enrolled'];
        if (!in_array($new_status, $allowed)) fail('Invalid status.');

        if ($auth['role'] === 'admin') {
            $stmt = $conn->prepare(
                'UPDATE applications
                 SET status=?, admin_note=?, updated_at=NOW()
                 WHERE id=?'
            );
            $stmt->bind_param('ssi', $new_status, $admin_note, $app_id);

        } elseif ($auth['role'] === 'university') {
            $stmt = $conn->prepare(
                'UPDATE applications
                 SET status=?, admin_note=?, updated_at=NOW()
                 WHERE id=? AND university_id=?'
            );
            $stmt->bind_param('ssii', $new_status, $admin_note, $app_id, $uid);

        } else {
            fail('Not authorised to update application status.', 403);
        }

        if (!$stmt->execute() || $stmt->affected_rows === 0) {
            fail('Update failed or application not found.', 404);
        }
        $stmt->close();

        // Notify the student
        $s = $conn->prepare('SELECT student_id FROM applications WHERE id=? LIMIT 1');
        $s->bind_param('i', $app_id);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        $s->close();

        if ($row) {
            $titles = [
                'review'    => 'Your application is under review',
                'interview' => 'Interview invitation!',
                'offer'     => 'Congratulations — you have an offer!',
                'rejected'  => 'Application update',
                'enrolled'  => 'Enrollment confirmed!',
            ];
            $nt = $titles[$new_status] ?? 'Application status updated';
            $nb = $admin_note ?: "Your application status changed to: {$new_status}.";
            $s2 = $conn->prepare(
                'INSERT INTO notifications (user_id, type, title, body)
                 VALUES (?, \'application_update\', ?, ?)'
            );
            $s2->bind_param('iss', $row['student_id'], $nt, $nb);
            $s2->execute();
            $s2->close();
        }

        respond(['success' => true, 'status' => $new_status]);
    }

    // ── Route B: New application (student only) ───────────────
    if ($auth['role'] !== 'student') fail('Only students can apply.', 403);

    $university_id = (int)($data['university_id']   ?? 0);
    $personal_stmt = clean($conn, $data['personal_statement'] ?? '');

    if (!$university_id) fail('university_id required.');

    // Verify university exists and is verified
    $stmt = $conn->prepare(
        'SELECT u.id FROM users u
         JOIN university_profiles up ON up.user_id = u.id
         WHERE u.id = ? AND up.verified = \'verified\' LIMIT 1'
    );
    $stmt->bind_param('i', $university_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) fail('University not found or not yet verified.', 404);
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO applications (student_id, university_id, personal_stmt)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE personal_stmt = VALUES(personal_stmt), status = \'new\''
    );
    $stmt->bind_param('iis', $uid, $university_id, $personal_stmt);
    if (!$stmt->execute()) fail('Application failed: ' . $conn->error, 500);
    $app_id = $stmt->insert_id;
    $stmt->close();

    // Notify university
    $stmt = $conn->prepare(
        'SELECT COALESCE(sp.full_name, u.email) AS name
         FROM users u LEFT JOIN student_profiles sp ON sp.user_id = u.id
         WHERE u.id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $student_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'A student';
    $stmt->close();

    $title = 'New application received';
    $body  = "{$student_name} has applied to your institution.";
    $stmt  = $conn->prepare(
        'INSERT INTO notifications (user_id, type, title, body)
         VALUES (?, \'application\', ?, ?)'
    );
    $stmt->bind_param('iss', $university_id, $title, $body);
    $stmt->execute();
    $stmt->close();

    respond(['success' => true, 'application_id' => $app_id]);
}

// ── GET: Fetch applications ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($auth['role'] === 'student') {
        $stmt = $conn->prepare('
            SELECT a.*,
                up.uni_name, up.country,
                u.email AS uni_email
            FROM applications a
            JOIN users u ON u.id = a.university_id
            LEFT JOIN university_profiles up ON up.user_id = u.id
            WHERE a.student_id = ?
            ORDER BY a.applied_at DESC
        ');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $apps = [];
        $res  = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $apps[] = $row;
        $stmt->close();
        respond(['success' => true, 'applications' => $apps]);

    } elseif ($auth['role'] === 'university') {
        $stmt = $conn->prepare('
            SELECT a.*,
                sp.full_name, sp.nationality, sp.gpa, sp.major, sp.intake,
                u.email AS student_email
            FROM applications a
            JOIN users u ON u.id = a.student_id
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            WHERE a.university_id = ?
            ORDER BY a.applied_at DESC
        ');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $apps = [];
        $res  = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $apps[] = $row;
        $stmt->close();
        respond(['success' => true, 'applications' => $apps]);

    } elseif ($auth['role'] === 'admin') {
        $stmt = $conn->prepare('
            SELECT a.*,
                sp.full_name, sp.nationality, sp.gpa, sp.major,
                u_s.email AS student_email,
                up.uni_name, up.country,
                u_u.email AS uni_email
            FROM applications a
            JOIN users u_s ON u_s.id = a.student_id
            LEFT JOIN student_profiles sp ON sp.user_id = a.student_id
            JOIN users u_u ON u_u.id = a.university_id
            LEFT JOIN university_profiles up ON up.user_id = a.university_id
            ORDER BY a.applied_at DESC
            LIMIT 500
        ');
        $stmt->execute();
        $apps = [];
        $res  = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $apps[] = $row;
        $stmt->close();
        respond(['success' => true, 'applications' => $apps]);
    }
}

fail('Method not allowed', 405);
