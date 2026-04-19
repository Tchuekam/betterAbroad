<?php
// ============================================================
//  dossier.php - Student application dossier PDF generator
//  GET dossier.php -> downloads a formatted PDF for the current student
// ============================================================
require_once __DIR__ . '/db.php';

$auth = require_auth();
$uid  = $auth['id'];

if ($auth['role'] !== 'student') fail('Only students can generate a dossier.', 403);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'log_download') {
    $admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
    if ($admins) {
        $stmt = $conn->prepare(
            'INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)'
        );
        while ($admin_row = $admins->fetch_assoc()) {
            $admin_id = (int)$admin_row['id'];
            $type = 'admin_alert';
            $title = 'Dossier download';
            $body = 'A student generated a dossier PDF.';
            $stmt->bind_param('isss', $admin_id, $type, $title, $body);
            $stmt->execute();
        }
        $stmt->close();
    }
    respond(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed.', 405);

$stmt = $conn->prepare(
    'SELECT sp.*, u.email
       FROM student_profiles sp
       JOIN users u ON u.id = sp.user_id
      WHERE sp.user_id = ?
      LIMIT 1'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) fail('Student profile not found. Please complete your profile first.');
if (($profile['verified'] ?? 'pending') !== 'verified') {
    fail('Profile must be verified before generating dossier.', 403);
}

$stmt = $conn->prepare(
    'SELECT doc_type, file_name, status, uploaded_at
       FROM documents
      WHERE user_id = ?
      ORDER BY uploaded_at ASC'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$expected_docs = [
    'transcript'         => 'Academic Transcript',
    'passport'           => 'Passport / ID',
    'personal_statement' => 'Personal Statement',
    'cv'                 => 'Curriculum Vitae',
    'recommendation_1'   => 'Recommendation Letter #1',
    'recommendation_2'   => 'Recommendation Letter #2',
    'language_cert'      => 'Language Certificate',
    'birth_certificate'  => 'Birth Certificate',
];

$tcpdf_candidates = [
    __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php',
    dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php',
    dirname(dirname(__DIR__)) . '/vendor/tecnickcom/tcpdf/tcpdf.php',
    dirname(dirname(dirname(__DIR__))) . '/phpMyAdmin/vendor/tecnickcom/tcpdf/tcpdf.php',
];

$tcpdf_path = null;
foreach ($tcpdf_candidates as $candidate) {
    if (is_file($candidate)) {
        $tcpdf_path = $candidate;
        break;
    }
}

if (!$tcpdf_path) {
    fail('PDF library not found on server.', 500);
}
require_once $tcpdf_path;

function safe_text($value) {
    $text = trim((string)($value ?? ''));
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
}

function format_date_or_default($value, $format = 'F j, Y', $default = '') {
    if (!$value) return $default;
    $timestamp = strtotime((string)$value);
    return $timestamp ? date($format, $timestamp) : $default;
}

class BetterAbroadPDF extends TCPDF {
    public function Header() {
        $this->SetFillColor(13, 30, 53);
        $this->Rect(0, 0, 210, 18, 'F');
        $this->SetFont('dejavusans', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 4);
        $this->Cell(0, 10, 'BetterAbroad', 0, 0, 'L');

        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(160, 190, 220);
        $this->SetXY(10, 10);
        $this->Cell(0, 6, 'Student Application Dossier', 0, 0, 'L');
        $this->Ln(12);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->SetTextColor(160, 160, 160);
        $this->Cell(0, 10, 'BetterAbroad  |  Confidential  |  Page ' . $this->getAliasNumPage(), 0, 0, 'C');
    }

    public function SectionTitle($title) {
        $this->SetFillColor(13, 30, 53);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('dejavusans', 'B', 11);
        $this->Cell(0, 9, '  ' . safe_text($title), 0, 1, 'L', true);
        $this->SetTextColor(30, 30, 30);
        $this->Ln(3);
    }

    public function KVRow($label, $value, $fill = false) {
        $this->SetFillColor(240, 245, 252);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(60, 8, safe_text($label), 'B', 0, 'L', $fill);
        $this->SetFont('dejavusans', '', 10);
        $this->SetTextColor(20, 20, 20);
        $this->Cell(0, 8, safe_text($value), 'B', 1, 'L', $fill);
    }
}

$ref_number = 'BA-DOSSIER-' . $uid . '-' . date('Ymd');
$full_name  = safe_text($profile['full_name'] ?? 'Student');
$intake     = safe_text($profile['target_intake'] ?? ($profile['intake'] ?? 'Not specified'));

$pdf = new BetterAbroadPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('BetterAbroad');
$pdf->SetAuthor('BetterAbroad');
$pdf->SetTitle('Application Dossier - ' . $full_name);
$pdf->SetMargins(20, 22, 20);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetFontSubsetting(true);

// Page 1 - cover
$pdf->AddPage();
$pdf->Ln(20);

$pdf->SetFillColor(13, 30, 53);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 22);
$pdf->SetX(20);
$pdf->Cell(170, 16, 'APPLICATION DOSSIER', 0, 1, 'C', true);
$pdf->SetFont('dejavusans', '', 13);
$pdf->SetX(20);
$pdf->Cell(170, 12, $full_name, 0, 1, 'C', true);
$pdf->SetTextColor(20, 20, 20);

$pdf->Ln(14);
$pdf->SetFillColor(240, 245, 252);
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetX(20);
$pdf->Cell(85, 9, 'Reference Number:', 0, 0, 'L', true);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(85, 9, $ref_number, 0, 1, 'L', true);

$pdf->SetFont('dejavusans', '', 10);
$pdf->SetX(20);
$pdf->Cell(85, 9, 'Date Generated:', 0, 0, 'L', true);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(85, 9, date('F j, Y'), 0, 1, 'L', true);

$pdf->SetFont('dejavusans', '', 10);
$pdf->SetX(20);
$pdf->Cell(85, 9, 'Email:', 0, 0, 'L', true);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(85, 9, safe_text($profile['email'] ?? ''), 0, 1, 'L', true);

$pdf->Ln(16);
$pdf->SetFillColor(0, 160, 100);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetX(20);
$pdf->Cell(170, 10, 'PROFILE VERIFIED BY BETTERABROAD ADMIN', 0, 1, 'C', true);
$pdf->SetTextColor(20, 20, 20);

$pdf->Ln(10);
$pdf->SetFont('dejavusans', 'I', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX(20);
$pdf->MultiCell(
    170,
    5,
    'This document has been generated by BetterAbroad on behalf of the above-named student. ' .
    'The student profile and submitted documents have been reviewed by the BetterAbroad team. ' .
    'This dossier is intended for university admissions purposes only and is confidential.',
    0,
    'C'
);

// Page 2 - profile summary
$pdf->AddPage();
$pdf->Ln(4);
$pdf->SectionTitle('Student Profile Summary');

$rows = [
    ['Full Name',           $full_name],
    ['Email Address',       safe_text($profile['email'] ?? '')],
    ['Nationality',         safe_text($profile['nationality'] ?? '')],
    ['Date of Birth',       format_date_or_default($profile['dob'] ?? null, 'F j, Y')],
    ['Phone',               safe_text($profile['phone'] ?? '')],
    ['Field of Study',      safe_text($profile['major'] ?? '')],
    ['GPA',                 $profile['gpa'] ? number_format((float)$profile['gpa'], 2) . ' / 5.00' : ''],
    ['Budget (Annual)',     safe_text($profile['budget'] ?? '')],
    ['Target Intake',       $intake],
    ['Profile Completion',  (int)($profile['completion_pct'] ?? 0) . '%'],
    ['Verification Status', 'Verified'],
];

$fill = false;
foreach ($rows as $row) {
    $pdf->KVRow($row[0], $row[1], $fill);
    $fill = !$fill;
}

// Page 3 - statement
$pdf->AddPage();
$pdf->Ln(4);
$pdf->SectionTitle('Statement of Purpose');

$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 7, date('F j, Y'), 0, 1, 'R');
$pdf->Ln(2);

$pdf->SetTextColor(20, 20, 20);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 8, 'To the Admissions Committee,', 0, 1, 'L');
$pdf->Ln(3);

$description = safe_text($profile['description'] ?? 'No statement of purpose provided.');
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->MultiCell(0, 6, $description, 0, 'J');

$pdf->Ln(10);
$pdf->SetFont('dejavusans', 'I', 10);
$pdf->SetTextColor(60, 60, 60);
$pdf->Cell(0, 7, 'Respectfully,', 0, 1, 'L');
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(0, 7, $full_name, 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, safe_text($profile['email'] ?? ''), 0, 1, 'L');

// Page 4 - documents checklist
$pdf->AddPage();
$pdf->Ln(4);
$pdf->SectionTitle('Documents Checklist');

$submitted = [];
foreach ($documents as $doc) {
    $submitted[$doc['doc_type']] = $doc;
}

$pdf->SetFillColor(13, 30, 53);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(80, 9, 'Document', 0, 0, 'L', true);
$pdf->Cell(40, 9, 'Status', 0, 0, 'C', true);
$pdf->Cell(60, 9, 'Uploaded', 0, 1, 'C', true);
$pdf->SetTextColor(20, 20, 20);

$fill = false;
foreach ($expected_docs as $key => $label) {
    $doc = $submitted[$key] ?? null;
    $pdf->SetFillColor($fill ? 240 : 255, $fill ? 245 : 255, $fill ? 252 : 255);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(80, 8, safe_text($label), 0, 0, 'L', true);

    if ($doc) {
        $status_colors = [
            'approved' => [0, 160, 100],
            'pending'  => [200, 120, 0],
            'rejected' => [200, 40, 40],
        ];
        $c = $status_colors[$doc['status']] ?? [100, 100, 100];
        $pdf->SetTextColor($c[0], $c[1], $c[2]);
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 8, ucfirst((string)$doc['status']), 0, 0, 'C', true);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(60, 8, format_date_or_default($doc['uploaded_at'] ?? null, 'M j, Y', '-'), 0, 1, 'C', true);
    } else {
        $pdf->SetTextColor(180, 60, 60);
        $pdf->SetFont('dejavusans', 'I', 10);
        $pdf->Cell(40, 8, 'Not Submitted', 0, 0, 'C', true);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell(60, 8, '-', 0, 1, 'C', true);
    }

    $fill = !$fill;
}

$pdf->Ln(8);
$pdf->SetFont('dejavusans', 'I', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(
    0,
    5,
    'Documents marked "Approved" have been individually reviewed and validated by the BetterAbroad team. ' .
    '"Pending" documents are under review. Missing documents should be uploaded through your BetterAbroad profile.',
    0,
    'L'
);

if (ob_get_level() > 0) ob_end_clean();
header_remove('Content-Type');

$safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $full_name ?: 'Student');
$pdf->Output('BetterAbroad_Dossier_' . $safe_name . '.pdf', 'D');
exit();
