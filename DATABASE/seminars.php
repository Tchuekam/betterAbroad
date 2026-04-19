<?php
require_once __DIR__ . '/db.php';

function seminar_table_exists($conn, $table) {
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = $conn->prepare(
        'SELECT 1
           FROM information_schema.tables
          WHERE table_schema = DATABASE()
            AND table_name = ?
          LIMIT 1'
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $cache[$table] = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $cache[$table];
}

function seminar_has_column($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $conn->prepare(
        'SELECT 1
           FROM information_schema.columns
          WHERE table_schema = DATABASE()
            AND table_name = ?
            AND column_name = ?
          LIMIT 1'
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $cache[$key];
}

function seminar_schema($conn) {
    return [
        'scheduled_at' => seminar_has_column($conn, 'seminars', 'scheduled_at'),
        'event_date' => seminar_has_column($conn, 'seminars', 'event_date'),
        'event_time' => seminar_has_column($conn, 'seminars', 'event_time'),
        'max_participants' => seminar_has_column($conn, 'seminars', 'max_participants'),
        'max_students' => seminar_has_column($conn, 'seminars', 'max_students'),
        'registered_count' => seminar_has_column($conn, 'seminars', 'registered_count'),
        'target_majors' => seminar_has_column($conn, 'seminars', 'target_majors'),
        'target_intake' => seminar_has_column($conn, 'seminars', 'target_intake'),
        'price_fcfa' => seminar_has_column($conn, 'seminars', 'price_fcfa'),
        'tier' => seminar_has_column($conn, 'seminars', 'tier'),
        'status' => seminar_has_column($conn, 'seminars', 'status'),
        'created_by' => seminar_has_column($conn, 'seminars', 'created_by'),
        'topic' => seminar_has_column($conn, 'seminars', 'topic'),
        'registrations_table' => seminar_table_exists($conn, 'seminar_registrations'),
    ];
}

function seminar_bind_params($stmt, $types, &$params) {
    if ($types === '') {
        return;
    }
    $refs = [$types];
    foreach ($params as $index => &$value) {
        $refs[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function seminar_build_select($conn, $role, $uid, $schema) {
    $select = [
        's.id',
        's.university_id',
        's.title',
        's.description',
        's.meet_link',
        'COALESCE(up.uni_name, uu.email) AS university_name',
        'COALESCE(up.uni_name, uu.email) AS uni_name',
    ];

    if ($schema['scheduled_at']) {
        $select[] = 's.scheduled_at';
    } elseif ($schema['event_date'] && $schema['event_time']) {
        $select[] = 'TIMESTAMP(s.event_date, s.event_time) AS scheduled_at';
        $select[] = 's.event_date';
        $select[] = 's.event_time';
    } elseif ($schema['event_date']) {
        $select[] = 's.event_date';
    }

    if ($schema['max_participants']) {
        $select[] = 's.max_participants';
    } elseif ($schema['max_students']) {
        $select[] = 's.max_students AS max_participants';
        $select[] = 's.max_students';
    } else {
        $select[] = '0 AS max_participants';
    }

    if ($schema['registered_count']) {
        $select[] = 's.registered_count';
    } elseif ($schema['registrations_table']) {
        $select[] = '(SELECT COUNT(*) FROM seminar_registrations sr WHERE sr.seminar_id = s.id) AS registered_count';
    } else {
        $select[] = '0 AS registered_count';
    }

    if ($schema['target_majors']) {
        $select[] = 's.target_majors';
    } elseif ($schema['topic']) {
        $select[] = 's.topic AS target_majors';
    } else {
        $select[] = 'NULL AS target_majors';
    }

    if ($schema['target_intake']) {
        $select[] = 's.target_intake';
    } else {
        $select[] = 'NULL AS target_intake';
    }

    if ($schema['tier']) {
        $select[] = 's.tier';
    } else {
        $select[] = '\'standard\' AS tier';
    }

    if ($schema['status']) {
        $select[] = 's.status';
    } else {
        $select[] = '\'scheduled\' AS status';
    }

    if ($schema['price_fcfa']) {
        $select[] = 's.price_fcfa';
    } else {
        $select[] = 'NULL AS price_fcfa';
    }

    if ($role === 'student' && $schema['registrations_table']) {
        $select[] = '(SELECT COUNT(*) FROM seminar_registrations sr WHERE sr.seminar_id = s.id AND sr.student_id = ' . (int)$uid . ') > 0 AS is_registered';
    }

    return implode(",\n                ", $select);
}

function seminar_order_clause($schema) {
    if ($schema['scheduled_at']) {
        return 'ORDER BY s.scheduled_at DESC, s.created_at DESC';
    }
    if ($schema['event_date'] && $schema['event_time']) {
        return 'ORDER BY s.event_date DESC, s.event_time DESC, s.created_at DESC';
    }
    if ($schema['event_date']) {
        return 'ORDER BY s.event_date DESC, s.created_at DESC';
    }
    return 'ORDER BY s.created_at DESC';
}

function seminar_fetch_rows($conn, $role, $uid, $schema, $seminar_id = 0, $university_id = 0) {
    $select = seminar_build_select($conn, $role, $uid, $schema);
    $sql = '
        SELECT
                ' . $select . '
          FROM seminars s
          JOIN users uu ON uu.id = s.university_id
          LEFT JOIN university_profiles up ON up.user_id = s.university_id
    ';

    $where = [];
    $types = '';
    $params = [];

    if ($seminar_id > 0) {
        $where[] = 's.id = ?';
        $types .= 'i';
        $params[] = $seminar_id;
    }

    if ($role === 'university') {
        $where[] = 's.university_id = ?';
        $types .= 'i';
        $params[] = $uid;
    } elseif ($role === 'student') {
        if ($schema['status']) {
            $where[] = "s.status <> 'cancelled'";
        }
    } elseif ($university_id > 0) {
        $where[] = 's.university_id = ?';
        $types .= 'i';
        $params[] = $university_id;
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ' . seminar_order_clause($schema);

    $stmt = $conn->prepare($sql);
    seminar_bind_params($stmt, $types, $params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['university_id'] = (int)$row['university_id'];
        $row['registered_count'] = (int)($row['registered_count'] ?? 0);
        $row['max_participants'] = (int)($row['max_participants'] ?? 0);
        $row['is_registered'] = !empty($row['is_registered']);
    }
    unset($row);

    return $rows;
}

function seminar_fetch_one($conn, $role, $uid, $schema, $seminar_id) {
    $rows = seminar_fetch_rows($conn, $role, $uid, $schema, $seminar_id);
    return $rows[0] ?? null;
}

function seminar_registered_students($conn, $seminar_id) {
    if (!seminar_table_exists($conn, 'seminar_registrations')) {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT
            sr.id,
            sr.student_id,
            sr.registered_at,
            u.email,
            sp.full_name,
            sp.major,
            sp.nationality,
            sp.intake,
            sp.verified
         FROM seminar_registrations sr
         JOIN users u ON u.id = sr.student_id
         LEFT JOIN student_profiles sp ON sp.user_id = sr.student_id
         WHERE sr.seminar_id = ?
         ORDER BY sr.registered_at DESC'
    );
    $stmt->bind_param('i', $seminar_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['student_id'] = (int)$row['student_id'];
    }
    unset($row);

    return $rows;
}

function seminar_max_seats_from_tier($tier) {
    return match (strtolower(trim((string)$tier))) {
        'basic' => 50,
        'premium' => 200,
        default => 100,
    };
}

function seminar_insert($conn, $schema, $data, $auth) {
    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $target_majors = trim((string)($data['target_majors'] ?? $data['topic'] ?? ''));
    $target_intake = trim((string)($data['target_intake'] ?? ''));
    $meet_link = trim((string)($data['meet_link'] ?? ''));
    $tier = strtolower(trim((string)($data['tier'] ?? 'standard')));
    $price_fcfa = (int)($data['price_fcfa'] ?? 0);
    $university_id = $auth['role'] === 'admin'
        ? (int)($data['university_id'] ?? 0)
        : (int)$auth['id'];
    $max_participants = (int)($data['max_participants'] ?? $data['maxStudents'] ?? $data['max_students'] ?? 0);
    $scheduled_at = trim((string)($data['scheduled_at'] ?? ''));
    $event_date = trim((string)($data['date'] ?? $data['event_date'] ?? ''));
    $event_time = trim((string)($data['time'] ?? $data['event_time'] ?? ''));

    if ($title === '') fail('title required.');
    if (!$university_id) fail('university_id required.');
    if ($meet_link === '') fail('meet_link required.');

    if ($scheduled_at === '' && $event_date !== '' && $event_time !== '') {
        $scheduled_at = $event_date . ' ' . $event_time;
    }

    if ($scheduled_at === '') {
        fail('scheduled_at required.');
    }

    if ($max_participants <= 0) {
        $max_participants = seminar_max_seats_from_tier($tier);
    }

    if ($price_fcfa <= 0) {
        $price_fcfa = $tier === 'basic' ? 150000 : ($tier === 'premium' ? 400000 : 250000);
    }

    $columns = ['university_id', 'title', 'description', 'meet_link'];
    $types = 'isss';
    $params = [$university_id, $title, $description, $meet_link];

    if ($schema['target_majors']) {
        $columns[] = 'target_majors';
        $types .= 's';
        $params[] = $target_majors;
    } elseif ($schema['topic']) {
        $columns[] = 'topic';
        $types .= 's';
        $params[] = $target_majors;
    }

    if ($schema['target_intake']) {
        $columns[] = 'target_intake';
        $types .= 's';
        $params[] = $target_intake;
    }

    if ($schema['scheduled_at']) {
        $columns[] = 'scheduled_at';
        $types .= 's';
        $params[] = date('Y-m-d H:i:s', strtotime($scheduled_at));
    } else {
        $dt = date_create($scheduled_at);
        if (!$dt) fail('Invalid scheduled_at.');
        if ($schema['event_date']) {
            $columns[] = 'event_date';
            $types .= 's';
            $params[] = $dt->format('Y-m-d');
        }
        if ($schema['event_time']) {
            $columns[] = 'event_time';
            $types .= 's';
            $params[] = $dt->format('H:i:s');
        }
    }

    if ($schema['max_participants']) {
        $columns[] = 'max_participants';
        $types .= 'i';
        $params[] = $max_participants;
    } elseif ($schema['max_students']) {
        $columns[] = 'max_students';
        $types .= 'i';
        $params[] = $max_participants;
    }

    if ($schema['registered_count']) {
        $columns[] = 'registered_count';
        $types .= 'i';
        $params[] = 0;
    }

    if ($schema['price_fcfa']) {
        $columns[] = 'price_fcfa';
        $types .= 'i';
        $params[] = $price_fcfa;
    }

    if ($schema['tier']) {
        $columns[] = 'tier';
        $types .= 's';
        $params[] = $tier;
    }

    if ($schema['status']) {
        $columns[] = 'status';
        $types .= 's';
        $params[] = 'scheduled';
    }

    if ($schema['created_by']) {
        $columns[] = 'created_by';
        $types .= 'i';
        $params[] = (int)$auth['id'];
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO seminars (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';
    $stmt = $conn->prepare($sql);
    seminar_bind_params($stmt, $types, $params);
    if (!$stmt->execute()) {
        $error = $conn->error;
        $stmt->close();
        fail('Failed to create seminar: ' . $error, 500);
    }
    $seminar_id = (int)$stmt->insert_id;
    $stmt->close();

    return $seminar_id;
}

function seminar_register($conn, $schema, $seminar_id, $student_id) {
    if (!seminar_table_exists($conn, 'seminar_registrations')) {
        fail('Seminar registration is not configured.', 500);
    }

    $seminar = seminar_fetch_one($conn, 'student', $student_id, $schema, $seminar_id);
    if (!$seminar) {
        fail('Seminar not found.', 404);
    }

    if (($seminar['status'] ?? 'scheduled') === 'cancelled') {
        fail('This seminar is no longer available.', 400);
    }

    $capacity = (int)($seminar['max_participants'] ?? 0);
    if ($capacity > 0 && (int)$seminar['registered_count'] >= $capacity) {
        fail('Seminar is full.', 400);
    }

    $stmt = $conn->prepare(
        'INSERT INTO seminar_registrations (seminar_id, student_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE registered_at = registered_at'
    );
    $stmt->bind_param('ii', $seminar_id, $student_id);
    if (!$stmt->execute()) {
        $error = $conn->error;
        $stmt->close();
        fail('Registration failed: ' . $error, 500);
    }
    $inserted = $stmt->affected_rows > 0;
    $stmt->close();

    if ($schema['registered_count']) {
        $stmt = $conn->prepare(
            'UPDATE seminars
                SET registered_count = (SELECT COUNT(*) FROM seminar_registrations WHERE seminar_id = ?)
              WHERE id = ?'
        );
        $stmt->bind_param('ii', $seminar_id, $seminar_id);
        $stmt->execute();
        $stmt->close();
    }

    return $inserted;
}

$auth = require_auth();
$role = $auth['role'];
$uid = (int)$auth['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = strtolower(trim((string)($_GET['action'] ?? 'list')));

if (!seminar_table_exists($conn, 'seminars')) {
    fail('Seminars table not found.', 500);
}

$schema = seminar_schema($conn);

if ($method === 'GET') {
    if ($action === 'detail') {
        if ($role !== 'admin' && $role !== 'university') {
            fail('Only universities and admins can access seminar attendees.', 403);
        }

        $seminar_id = (int)($_GET['id'] ?? 0);
        if (!$seminar_id) fail('id required.');

        $seminar = seminar_fetch_one($conn, $role, $uid, $schema, $seminar_id);
        if (!$seminar) {
            fail('Seminar not found.', 404);
        }

        respond([
            'success' => true,
            'seminar' => $seminar,
            'registered_students' => seminar_registered_students($conn, $seminar_id),
        ]);
    }

    if (!in_array($role, ['admin', 'university', 'student'], true)) {
        fail('Unsupported role.', 403);
    }

    $university_id = (int)($_GET['university_id'] ?? 0);
    $seminars = seminar_fetch_rows($conn, $role, $uid, $schema, 0, $university_id);
    respond(['success' => true, 'seminars' => $seminars]);
}

if ($method === 'POST') {
    $data = get_body();
    $action = strtolower(trim((string)($data['action'] ?? $action)));

    if ($action === 'register') {
        if ($role !== 'student') {
            fail('Only students can register for seminars.', 403);
        }
        $seminar_id = (int)($data['seminar_id'] ?? $data['id'] ?? 0);
        if (!$seminar_id) fail('seminar_id required.');
        seminar_register($conn, $schema, $seminar_id, $uid);
        respond(['success' => true, 'seminar_id' => $seminar_id]);
    }

    if ($action === 'create' || $action === '') {
        if ($role !== 'admin' && $role !== 'university') {
            fail('Only universities and admins can create seminars.', 403);
        }
        $seminar_id = seminar_insert($conn, $schema, $data, $auth);
        $seminar = seminar_fetch_one($conn, $role, $uid, $schema, $seminar_id);
        respond(['success' => true, 'seminar_id' => $seminar_id, 'seminar' => $seminar]);
    }

    fail('Unsupported action.', 400);
}

if ($method === 'DELETE') {
    if ($role !== 'admin' && $role !== 'university') {
        fail('Only universities and admins can cancel seminars.', 403);
    }

    $data = get_body();
    $seminar_id = (int)($data['id'] ?? 0);
    if (!$seminar_id) fail('id required.');

    if ($schema['status']) {
        if ($role === 'admin') {
            $stmt = $conn->prepare("UPDATE seminars SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param('i', $seminar_id);
        } else {
            $stmt = $conn->prepare("UPDATE seminars SET status = 'cancelled' WHERE id = ? AND university_id = ?");
            $stmt->bind_param('ii', $seminar_id, $uid);
        }
        if (!$stmt->execute()) {
            $error = $conn->error;
            $stmt->close();
            fail('Failed to cancel seminar: ' . $error, 500);
        }
        if ($stmt->affected_rows < 1) {
            $stmt->close();
            fail('Seminar not found.', 404);
        }
        $stmt->close();
        respond(['success' => true]);
    }

    if ($role === 'admin') {
        $stmt = $conn->prepare('DELETE FROM seminars WHERE id = ?');
        $stmt->bind_param('i', $seminar_id);
    } else {
        $stmt = $conn->prepare('DELETE FROM seminars WHERE id = ? AND university_id = ?');
        $stmt->bind_param('ii', $seminar_id, $uid);
    }
    if (!$stmt->execute()) {
        $error = $conn->error;
        $stmt->close();
        fail('Failed to cancel seminar: ' . $error, 500);
    }
    if ($stmt->affected_rows < 1) {
        $stmt->close();
        fail('Seminar not found.', 404);
    }
    $stmt->close();

    respond(['success' => true]);
}

fail('Method not allowed.', 405);
