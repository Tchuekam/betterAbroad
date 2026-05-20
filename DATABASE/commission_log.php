<?php
require_once __DIR__ . '/db.php';

function commission_status($value) {
    $status = strtolower(trim((string)$value));
    return in_array($status, ['pending', 'invoiced', 'paid', 'disputed'], true)
        ? $status
        : 'pending';
}

function commission_nullable_text($value) {
    $value = trim((string)($value ?? ''));
    return $value === '' ? null : $value;
}

function commission_fetch_record($conn, $record_id) {
    $stmt = $conn->prepare(
        'SELECT
            cl.*,
            COALESCE(cl.certificate_id, ec.certificate_id) AS effective_certificate_id,
            a.status AS application_status,
            COALESCE(sp.full_name, su.email) AS student_name,
            COALESCE(up.uni_name, uu.email) AS uni_name
         FROM commission_log cl
         JOIN applications a ON a.id = cl.application_id
         JOIN users su ON su.id = a.student_id
         JOIN users uu ON uu.id = a.university_id
         LEFT JOIN student_profiles sp ON sp.user_id = a.student_id
         LEFT JOIN university_profiles up ON up.user_id = a.university_id
         LEFT JOIN enrollment_certificates ec ON ec.application_id = cl.application_id
         WHERE cl.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $record_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record) {
        return null;
    }

    $record['id'] = (int)$record['id'];
    $record['application_id'] = (int)$record['application_id'];
    $record['amount_fcfa'] = $record['amount_fcfa'] !== null ? (float)$record['amount_fcfa'] : null;
    $record['certificate_id'] = $record['effective_certificate_id'];
    unset($record['effective_certificate_id']);

    return $record;
}

$admin = require_admin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = commission_nullable_text($_GET['status'] ?? '');

    if ($status !== null) {
        $status = commission_status($status);
        $stmt = $conn->prepare(
            'SELECT
                cl.id
             FROM commission_log cl
             WHERE cl.status = ?
             ORDER BY cl.created_at DESC, cl.id DESC'
        );
        $stmt->bind_param('s', $status);
    } else {
        $stmt = $conn->prepare(
            'SELECT cl.id
               FROM commission_log cl
              ORDER BY cl.created_at DESC, cl.id DESC'
        );
    }

    $stmt->execute();
    $ids = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $records = [];
    foreach ($ids as $row) {
        $record = commission_fetch_record($conn, (int)$row['id']);
        if ($record) {
            $records[] = $record;
        }
    }

    respond(['success' => true, 'records' => $records]);
}

if ($method === 'POST') {
    $data = get_body();
    $record_id = (int)($data['id'] ?? 0);
    $application_id = (int)($data['application_id'] ?? 0);
    $amount_fcfa = commission_nullable_text($data['amount_fcfa'] ?? null);
    $status = commission_status($data['status'] ?? 'pending');
    $invoice_date = commission_nullable_text($data['invoice_date'] ?? null);
    $payment_date = commission_nullable_text($data['payment_date'] ?? null);
    $payment_method = commission_nullable_text($data['payment_method'] ?? null);
    $notes = commission_nullable_text($data['notes'] ?? null);

    if (!$application_id) {
        fail('application_id required.');
    }

    if ($amount_fcfa !== null && !is_numeric($amount_fcfa)) {
        fail('amount_fcfa must be numeric.');
    }

    $stmt = $conn->prepare('SELECT id FROM applications WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $application_id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$application) {
        fail('Application not found.', 404);
    }

    $stmt = $conn->prepare(
        'SELECT certificate_id
           FROM enrollment_certificates
          WHERE application_id = ?
          LIMIT 1'
    );
    $stmt->bind_param('i', $application_id);
    $stmt->execute();
    $certificate_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $certificate_id = $certificate_row['certificate_id'] ?? null;

    if ($record_id > 0) {
        $stmt = $conn->prepare(
            'UPDATE commission_log
                SET application_id = ?,
                    certificate_id = ?,
                    amount_fcfa = ?,
                    status = ?,
                    invoice_date = ?,
                    payment_date = ?,
                    payment_method = ?,
                    notes = ?
              WHERE id = ?'
        );
        $stmt->bind_param(
            'isssssssi',
            $application_id,
            $certificate_id,
            $amount_fcfa,
            $status,
            $invoice_date,
            $payment_date,
            $payment_method,
            $notes,
            $record_id
        );
        if (!$stmt->execute()) {
            $stmt->close();
            fail('Failed to update commission record.', 500);
        }
        $stmt->close();
        $action = 'commission_record_updated';
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO commission_log
                (application_id, certificate_id, amount_fcfa, status, invoice_date, payment_date, payment_method, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'isssssss',
            $application_id,
            $certificate_id,
            $amount_fcfa,
            $status,
            $invoice_date,
            $payment_date,
            $payment_method,
            $notes
        );
        if (!$stmt->execute()) {
            $stmt->close();
            fail('Failed to create commission record.', 500);
        }
        $record_id = (int)$stmt->insert_id;
        $stmt->close();
        $action = 'commission_record_created';
    }

    $record = commission_fetch_record($conn, $record_id);
    if (!$record) {
        fail('Failed to load saved commission record.', 500);
    }

    log_action(
        $conn,
        (int)$admin['id'],
        $action,
        'commission_log',
        $record_id,
        'application_id=' . $application_id
    );

    respond(['success' => true, 'record' => $record]);
}

fail('Method not allowed.', 405);
