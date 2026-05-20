<?php
// ============================================================
//  upload.php  POST  (multipart/form-data)
//  Fields: doc_type (string), file (binary)
//  Returns: { success, doc_type, file_path, file_name, status }
//
//  STUDENT doc types (8):
//    transcript          — Official academic transcript
//    passport            — Passport or national ID
//    recommendation_1    — First recommendation letter
//    recommendation_2    — Second recommendation letter
//    personal_statement  — Personal statement / motivation letter
//    cv                  — CV / résumé
//    language_cert       — Language certificate (IELTS, TOEFL, DELF…)
//    birth_certificate   — Birth certificate
//
//  UNIVERSITY doc types (3):
//    logo                — Institution logo
//    accreditation       — Accreditation certificate
//    brochure            — Programs brochure / prospectus
// ============================================================
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$auth     = require_auth();
$uid      = $auth['id'];
$doc_type = clean($conn, $_POST['doc_type'] ?? '');

// ── Allowed types per role ───────────────────────────────────
$student_types    = [
    'transcript',
    'passport',
    'recommendation_1',
    'recommendation_2',
    'personal_statement',
    'cv',
    'language_cert',
    'birth_certificate',
];
$university_types = ['logo', 'accreditation', 'brochure'];

$allowed_types = $auth['role'] === 'student' ? $student_types : $university_types;

if (!$doc_type)                          fail('Document type is required.');
if (!in_array($doc_type, $allowed_types)) fail('Invalid document type for your role.');
if (empty($_FILES['file']))               fail('No file uploaded.');

$file     = $_FILES['file'];
$max_size = 5 * 1024 * 1024;   // 5 MB hard limit

// ── Validate upload ──────────────────────────────────────────
if ($file['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
    ];
    fail($codes[$file['error']] ?? 'Upload error: code ' . $file['error']);
}

if ($file['size'] > $max_size) fail('File too large. Maximum 5 MB per document.');
if ($file['size'] === 0)        fail('Uploaded file is empty.');

// ── MIME validation (server-side, not just extension) ────────
$mime          = mime_content_type($file['tmp_name']);
$allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

if (!in_array($mime, $allowed_mimes)) {
    fail('Invalid file type. Accepted: PDF, JPG, PNG, WEBP.');
}

// Logo must be an image
if ($doc_type === 'logo' && $mime === 'application/pdf') {
    fail('Logo must be an image file (JPG, PNG, WEBP).');
}

// ── Build storage path ───────────────────────────────────────
$role_folder = $auth['role'] === 'student' ? 'students' : 'universities';
$project_root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$dir         = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $role_folder . DIRECTORY_SEPARATOR . $uid . DIRECTORY_SEPARATOR;

if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    fail('Could not create upload directory.', 500);
}

// Sanitise extension from MIME (trust MIME over client filename)
$mime_ext_map = [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/jpg'       => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
];
$ext       = $mime_ext_map[$mime];
$safe_name = "{$doc_type}_{$uid}.{$ext}";
$full_path = $dir . $safe_name;
$db_path   = "uploads/{$role_folder}/{$uid}/{$safe_name}";

if (!move_uploaded_file($file['tmp_name'], $full_path)) {
    fail('Failed to save file on server.', 500);
}

// ── Upsert into documents table ──────────────────────────────
// Requires UNIQUE KEY unique_user_doc (user_id, doc_type) — see schema_migration.sql
$stmt = $conn->prepare(
    'INSERT INTO documents (user_id, doc_type, file_name, file_path, file_size, mime_type, status)
     VALUES (?, ?, ?, ?, ?, ?, "pending")
     ON DUPLICATE KEY UPDATE
       file_name   = VALUES(file_name),
       file_path   = VALUES(file_path),
       file_size   = VALUES(file_size),
       mime_type   = VALUES(mime_type),
       status      = "pending",
       uploaded_at = NOW()'
);
$stmt->bind_param('isssis', $uid, $doc_type, $safe_name, $db_path,
                             $file['size'], $mime);

if (!$stmt->execute()) fail('Failed to record document: ' . $conn->error, 500);
$stmt->close();

respond([
    'success'   => true,
    'doc_type'  => $doc_type,
    'file_path' => $db_path,
    'file_name' => $safe_name,
    'status'    => 'pending',
    'size'      => $file['size'],
]);
