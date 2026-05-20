<?php
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    fail('Method not allowed.', 405);
}

function stats_scalar($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        fail('Failed to load intake stats.', 500);
    }
    $row = $result->fetch_assoc();
    $result->free();
    return (int)array_values($row)[0];
}

function stats_rows($conn, $sql, $label_key) {
    $result = $conn->query($sql);
    if (!$result) {
        fail('Failed to load intake stats.', 500);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['count'] = (int)$row['count'];
        $row[$label_key] = (string)$row[$label_key];
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

$total_students = stats_scalar(
    $conn,
    "SELECT COUNT(*) AS total
       FROM users
      WHERE role = 'student' AND is_active = 1"
);

$verified_count = stats_scalar(
    $conn,
    "SELECT COUNT(*) AS total
       FROM student_profiles
      WHERE verified = 'verified'"
);

$docs_complete_count = stats_scalar(
    $conn,
    "SELECT COUNT(*) AS total
       FROM (
            SELECT d.user_id
              FROM documents d
             WHERE d.user_id IN (
                    SELECT id
                      FROM users
                     WHERE role = 'student' AND is_active = 1
             )
             GROUP BY d.user_id
            HAVING COUNT(*) >= 4
       ) AS completed_docs"
);

$by_nationality = stats_rows(
    $conn,
    "SELECT nationality, COUNT(*) AS count
       FROM student_profiles
      WHERE nationality IS NOT NULL AND nationality != ''
      GROUP BY nationality
      ORDER BY count DESC
      LIMIT 10",
    'nationality'
);

$by_major = stats_rows(
    $conn,
    "SELECT major, COUNT(*) AS count
       FROM student_profiles
      WHERE major IS NOT NULL AND major != ''
      GROUP BY major
      ORDER BY count DESC
      LIMIT 10",
    'major'
);

$by_budget = stats_rows(
    $conn,
    "SELECT budget, COUNT(*) AS count
       FROM student_profiles
      WHERE budget IS NOT NULL AND budget != ''
      GROUP BY budget
      ORDER BY count DESC",
    'budget'
);

respond([
    'success' => true,
    'total_students' => $total_students,
    'verified_count' => $verified_count,
    'docs_complete_count' => $docs_complete_count,
    'by_nationality' => $by_nationality,
    'by_major' => $by_major,
    'by_budget' => $by_budget,
]);
