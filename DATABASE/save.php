<?php
// ============================================================
//  profiles/save.php  POST /api/profiles/save.php
//  Body: profile fields (role determined from session)
//  Returns: { success, profile, completion_pct }
// ============================================================

// 📝 EDIT: Buffer output immediately — prevents any PHP warning/notice
// from being printed as HTML before headers are sent, which would
// corrupt the JSON response and break r.json() on the frontend.
ob_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$auth = require_auth();
$data = get_body();
$uid  = $auth['id'];

function save_users_supports_tos_accepted($conn) {
    static $supports = null;
    if ($supports !== null) {
        return $supports;
    }

    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'tos_accepted'");
    $supports = $result && (bool)$result->fetch_assoc();
    if ($result) {
        $result->free();
    }

    return $supports;
}

function save_ensure_tos_accepted_column($conn) {
    if (save_users_supports_tos_accepted($conn)) {
        return;
    }

    $sql = "ALTER TABLE users
            ADD COLUMN tos_accepted TINYINT(1) NOT NULL DEFAULT 0
            AFTER is_active";
    if (!$conn->query($sql)) {
        fail('Failed to enable Terms acceptance tracking: ' . $conn->error, 500);
    }
}

function save_persist_tos_acceptance($conn, $uid, $accepted) {
    save_ensure_tos_accepted_column($conn);
    $flag = $accepted ? 1 : 0;
    $stmt = $conn->prepare('UPDATE users SET tos_accepted = ? WHERE id = ?');
    $stmt->bind_param('ii', $flag, $uid);
    if (!$stmt->execute()) {
        $stmt->close();
        fail('Failed to save Terms acceptance: ' . $conn->error, 500);
    }
    $stmt->close();
}

if ($auth['role'] === 'university' && array_key_exists('tos_accepted', $data)) {
    $tos_accepted = !empty($data['tos_accepted']);
    save_persist_tos_acceptance($conn, $uid, $tos_accepted);

    $profileFields = array_diff_key($data, ['tos_accepted' => true]);
    if (!$profileFields) {
        respond(['success' => true, 'tosAccepted' => $tos_accepted]);
    }
}

// ── STUDENT PROFILE ──────────────────────────────────────────
if ($auth['role'] === 'student') {
    $full_name   = clean($conn, $data['full_name']    ?? '');
    $phone       = clean($conn, $data['phone']        ?? '');
    $dob         = clean($conn, $data['dob']         ?? '');
    $nationality = clean($conn, $data['nationality'] ?? '');
    $major       = clean($conn, $data['major']       ?? '');
    $budget      = clean($conn, $data['budget']      ?? '');
    $description = clean($conn, $data['description'] ?? '');

    // 📝 EDIT: Safely handle GPA — use NULL if not provided or 0/empty,
    // avoids bind_param('d', null) warning which outputs HTML and breaks JSON
    $gpa_raw = $data['gpa'] ?? '';
    $gpa     = ($gpa_raw !== '' && $gpa_raw !== null) ? (float)$gpa_raw : null;

    // Validate GPA range
    if ($gpa !== null && ($gpa < 0 || $gpa > 5)) fail('GPA must be between 0.0 and 5.0.');

    // Calculate completion %
    $pct = 20;
    if ($full_name)   $pct += 15;
    if ($phone)       $pct += 5;
    if ($gpa)         $pct += 15;
    if ($major)       $pct += 10;
    if ($budget)      $pct += 10;
    if ($nationality) $pct += 10;
    if ($dob)         $pct += 10;
    $pct = min($pct, 100);

    // 📝 EDIT: Use separate statement paths for NULL vs float GPA.
    // bind_param('d') with null causes a PHP warning that prints HTML,
    // corrupting the JSON output. NULL GPA uses 's' type safely instead.
    if ($gpa !== null) {
        $stmt = $conn->prepare(
            'INSERT INTO student_profiles
               (user_id, full_name, phone, dob, nationality, gpa, major, budget, description, completion_pct)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               full_name=VALUES(full_name), phone=VALUES(phone), dob=VALUES(dob),
               nationality=VALUES(nationality), gpa=VALUES(gpa),
               major=VALUES(major), budget=VALUES(budget), description=VALUES(description),
               completion_pct=VALUES(completion_pct)'
        );
        $stmt->bind_param('issssdsssi', $uid, $full_name, $phone, $dob, $nationality, $gpa,
                                       $major, $budget, $description, $pct);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO student_profiles
               (user_id, full_name, phone, dob, nationality, gpa, major, budget, description, completion_pct)
             VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               full_name=VALUES(full_name), phone=VALUES(phone), dob=VALUES(dob),
               nationality=VALUES(nationality), gpa=NULL,
               major=VALUES(major), budget=VALUES(budget), description=VALUES(description),
               completion_pct=VALUES(completion_pct)'
        );
        $stmt->bind_param('isisssssi', $uid, $full_name, $phone, $dob, $nationality,
                                      $major, $budget, $description, $pct);
    }
    if (!$stmt->execute()) fail('Failed to save profile: ' . $conn->error, 500);
    $stmt->close();

    // Fetch back
    $stmt = $conn->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    respond(['success' => true, 'profile' => $profile, 'completion_pct' => $pct]);

// ── UNIVERSITY PROFILE ───────────────────────────────────────
} elseif ($auth['role'] === 'university') {
    $uni_name       = clean($conn, $data['uniName']       ?? '');
    $country        = clean($conn, $data['country']       ?? '');
    $website        = clean($conn, $data['website']       ?? '');
    $programs       = clean($conn, $data['programs']      ?? '');
    $intake_periods = clean($conn, $data['intakePeriods'] ?? '');
    $description    = clean($conn, $data['description']   ?? '');

    $stmt = $conn->prepare(
        'INSERT INTO university_profiles
           (user_id, uni_name, country, website, programs, intake_periods, description)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           uni_name=VALUES(uni_name), country=VALUES(country),
           website=VALUES(website), programs=VALUES(programs),
           intake_periods=VALUES(intake_periods), description=VALUES(description)'
    );
    $stmt->bind_param('issssss', $uid, $uni_name, $country, $website, $programs, $intake_periods, $description);
    if (!$stmt->execute()) fail('Failed to save profile: ' . $conn->error, 500);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM university_profiles WHERE user_id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    respond(['success' => true, 'profile' => $profile]);

} else {
    fail('Cannot save profile for this role.', 403);
}
