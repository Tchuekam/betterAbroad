<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}

function certificate_safe_text($value) {
    $text = trim((string)($value ?? ''));
    return preg_replace('/[\x00-\x1F\x7F]/', '', $text);
}

function certificate_date($value) {
    $timestamp = strtotime((string)$value);
    if (!$timestamp) {
        return '';
    }
    return date('F j, Y g:i A', $timestamp);
}

function certificate_tcpdf_path() {
    $paths = [
        'C:/xampp/htdocs/tcpdf/tcpdf.php',
        __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php',
        dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function certificate_text_file($absolute_path, $fields) {
    $content = [];
    $content[] = 'BETTERABROAD';
    $content[] = 'ENROLLMENT INTRODUCTION CERTIFICATE';
    $content[] = '';
    $content[] = 'This certificate confirms that BetterAbroad introduced the following student';
    $content[] = 'to the following institution through its verified marketplace platform.';
    $content[] = '';
    $content[] = 'Student';
    $content[] = 'Full Name: ' . $fields['student_name'];
    $content[] = 'Nationality: ' . $fields['nationality'];
    $content[] = 'Academic Major: ' . $fields['major'];
    $content[] = 'GPA: ' . $fields['gpa'];
    $content[] = 'Email: ' . $fields['student_email'];
    $content[] = '';
    $content[] = 'Institution';
    $content[] = 'Institution: ' . $fields['uni_name'];
    $content[] = 'Country: ' . $fields['country'];
    $content[] = 'Website: ' . $fields['website'];
    $content[] = 'Contact: ' . $fields['uni_email'];
    $content[] = '';
    $content[] = 'Transaction Info';
    $content[] = 'Certificate ID: ' . $fields['certificate_id'];
    $content[] = 'Application ID: #' . $fields['application_id'];
    $content[] = 'Date of Introduction: ' . $fields['applied_at'];
    $content[] = 'Certificate Issued: ' . $fields['issued_at'];
    $content[] = 'Platform: BetterAbroad Marketplace';
    $content[] = '';
    $content[] = 'This document constitutes legal proof of introduction for commission collection';
    $content[] = 'under the BetterAbroad Service Agreement.';

    if (file_put_contents($absolute_path, implode(PHP_EOL, $content)) === false) {
        fail('Failed to write certificate file.', 500);
    }
}

function certificate_pdf_file($tcpdf_path, $absolute_path, $fields) {
    require_once $tcpdf_path;

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('BetterAbroad');
    $pdf->SetAuthor('BetterAbroad');
    $pdf->SetTitle('Enrollment Certificate');
    $pdf->SetMargins(16, 18, 16);
    $pdf->SetAutoPageBreak(true, 16);
    $pdf->AddPage();

    $html = '
        <style>
            h1 { font-size: 18px; color: #0f2d56; letter-spacing: 1px; }
            h2 { font-size: 12px; color: #0f2d56; margin: 12px 0 6px; }
            p, li { font-size: 10px; line-height: 1.6; color: #24364d; }
            .brand { font-size: 16px; font-weight: bold; color: #0f2d56; }
            .tagline { font-size: 9px; color: #5a6d86; }
            .box { border: 1px solid #d9e2ef; padding: 10px; margin: 8px 0 12px; }
            .small { font-size: 9px; color: #5a6d86; }
        </style>
        <div class="brand">BetterAbroad</div>
        <div class="tagline">Verified global student recruitment marketplace</div>
        <br>
        <h1>ENROLLMENT INTRODUCTION CERTIFICATE</h1>
        <p>
            This certificate confirms that BetterAbroad introduced the following student to the
            following institution through its verified student marketplace platform.
        </p>
        <div class="box">
            <h2>Student</h2>
            <p><strong>Full Name:</strong> ' . htmlspecialchars($fields['student_name']) . '</p>
            <p><strong>Nationality:</strong> ' . htmlspecialchars($fields['nationality']) . '</p>
            <p><strong>Academic Major:</strong> ' . htmlspecialchars($fields['major']) . '</p>
            <p><strong>GPA:</strong> ' . htmlspecialchars($fields['gpa']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($fields['student_email']) . '</p>
        </div>
        <div class="box">
            <h2>Institution</h2>
            <p><strong>Institution:</strong> ' . htmlspecialchars($fields['uni_name']) . '</p>
            <p><strong>Country:</strong> ' . htmlspecialchars($fields['country']) . '</p>
            <p><strong>Website:</strong> ' . htmlspecialchars($fields['website']) . '</p>
            <p><strong>Contact:</strong> ' . htmlspecialchars($fields['uni_email']) . '</p>
        </div>
        <div class="box">
            <h2>Transaction Info</h2>
            <p><strong>Certificate ID:</strong> ' . htmlspecialchars($fields['certificate_id']) . '</p>
            <p><strong>Application ID:</strong> #' . htmlspecialchars($fields['application_id']) . '</p>
            <p><strong>Date of Introduction:</strong> ' . htmlspecialchars($fields['applied_at']) . '</p>
            <p><strong>Certificate Issued:</strong> ' . htmlspecialchars($fields['issued_at']) . '</p>
            <p><strong>Platform:</strong> BetterAbroad Marketplace</p>
        </div>
        <p class="small">
            This document constitutes legal proof of introduction for the purposes of commission
            collection under the BetterAbroad Service Agreement. Any enrollment resulting from this
            introduction is subject to the agreed commission rate.
        </p>
        <br><br>
        <p>Signature: ________________________</p>
        <p>BetterAbroad Founder | Yaounde, Cameroon</p>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($absolute_path, 'F');
}

$admin = require_admin();
$data = get_body();
$application_id = (int)($data['application_id'] ?? 0);

if (!$application_id) {
    fail('application_id required.');
}

$stmt = $conn->prepare(
    'SELECT
        a.id,
        a.student_id,
        a.university_id,
        a.applied_at,
        a.status,
        COALESCE(sp.full_name, u_s.email) AS student_name,
        COALESCE(sp.nationality, \'\') AS nationality,
        COALESCE(sp.major, \'\') AS major,
        COALESCE(CAST(sp.gpa AS CHAR), \'\') AS gpa,
        u_s.email AS student_email,
        COALESCE(up.uni_name, u_u.email) AS uni_name,
        COALESCE(up.country, \'\') AS country,
        COALESCE(up.website, \'\') AS website,
        u_u.email AS uni_email
     FROM applications a
     JOIN users u_s ON u_s.id = a.student_id
     JOIN users u_u ON u_u.id = a.university_id
     LEFT JOIN student_profiles sp ON sp.user_id = a.student_id
     LEFT JOIN university_profiles up ON up.user_id = a.university_id
     WHERE a.id = ?
     LIMIT 1'
);
$stmt->bind_param('i', $application_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    fail('Application not found.', 404);
}

if (!in_array($application['status'], ['offer', 'review'], true)) {
    fail('Application must be in offer or review status.', 400);
}

$stmt = $conn->prepare(
    'SELECT certificate_id, file_path
       FROM enrollment_certificates
      WHERE application_id = ?
      LIMIT 1'
);
$stmt->bind_param('i', $application_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$project_root = dirname(__DIR__);
if ($existing && !empty($existing['file_path'])) {
    $existing_path = $project_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $existing['file_path']);
    if (file_exists($existing_path)) {
        respond([
            'success' => true,
            'certificate_id' => $existing['certificate_id'],
            'file_url' => $existing['file_path'],
            'student_name' => $application['student_name'],
            'uni_name' => $application['uni_name'],
        ]);
    }
}

$certificate_id = $existing['certificate_id'] ?? (
    'CERT-' . $application_id . '-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)$application_id, true)), 0, 6))
);

$fields = [
    'certificate_id' => certificate_safe_text($certificate_id),
    'application_id' => (string)$application_id,
    'student_name' => certificate_safe_text($application['student_name']),
    'nationality' => certificate_safe_text($application['nationality']),
    'major' => certificate_safe_text($application['major']),
    'gpa' => certificate_safe_text($application['gpa']),
    'student_email' => certificate_safe_text($application['student_email']),
    'uni_name' => certificate_safe_text($application['uni_name']),
    'country' => certificate_safe_text($application['country']),
    'website' => certificate_safe_text($application['website']),
    'uni_email' => certificate_safe_text($application['uni_email']),
    'applied_at' => certificate_safe_text(certificate_date($application['applied_at'])),
    'issued_at' => certificate_safe_text(date('F j, Y g:i A')),
];

$relative_dir = 'uploads/certificates/';
$absolute_dir = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR;
if (!is_dir($absolute_dir) && !mkdir($absolute_dir, 0755, true) && !is_dir($absolute_dir)) {
    fail('Failed to create certificates directory.', 500);
}

$tcpdf_path = certificate_tcpdf_path();
$extension = $tcpdf_path ? 'pdf' : 'txt';
$filename = 'cert_' . $application_id . '.' . $extension;
$relative_path = $relative_dir . $filename;
$absolute_path = $absolute_dir . $filename;

if ($tcpdf_path) {
    certificate_pdf_file($tcpdf_path, $absolute_path, $fields);
} else {
    certificate_text_file($absolute_path, $fields);
}

$stmt = $conn->prepare(
    'INSERT INTO enrollment_certificates (application_id, certificate_id, file_path)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
        certificate_id = VALUES(certificate_id),
        file_path = VALUES(file_path)'
);
$stmt->bind_param('iss', $application_id, $certificate_id, $relative_path);
if (!$stmt->execute()) {
    $stmt->close();
    fail('Failed to save certificate record.', 500);
}
$stmt->close();

$student_title = 'Enrollment Certificate Issued';
$student_body = 'Your enrollment certificate for ' . $fields['uni_name'] . ' has been generated.';
$stmt = $conn->prepare(
    'INSERT INTO notifications (user_id, type, title, body)
     VALUES (?, \'certificate\', ?, ?)'
);
$student_id = (int)$application['student_id'];
$stmt->bind_param('iss', $student_id, $student_title, $student_body);
$stmt->execute();
$stmt->close();

$university_title = 'Enrollment Certificate Issued';
$university_body = $fields['student_name'] . ' enrollment with your institution has been certified.';
$stmt = $conn->prepare(
    'INSERT INTO notifications (user_id, type, title, body)
     VALUES (?, \'certificate\', ?, ?)'
);
$university_id = (int)$application['university_id'];
$stmt->bind_param('iss', $university_id, $university_title, $university_body);
$stmt->execute();
$stmt->close();

log_action(
    $conn,
    (int)$admin['id'],
    'certificate_issued',
    'application',
    $application_id,
    $certificate_id
);

respond([
    'success' => true,
    'certificate_id' => $certificate_id,
    'file_url' => $relative_path,
    'student_name' => $fields['student_name'],
    'uni_name' => $fields['uni_name'],
]);
