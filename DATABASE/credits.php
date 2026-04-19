<?php
// ============================================================
//  credits.php — Credit system endpoint
//  GET  credits.php           → balance + recent transactions
//  POST credits.php           → earn or spend credits
// ============================================================
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$auth   = require_auth();
$uid    = $auth['id'];

// ── GET — Return balance and last 10 transactions ────────────
if ($method === 'GET') {

    // Fetch or default balance
    $stmt = $conn->prepare('SELECT balance FROM credits WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row     = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $balance = $row ? (int)$row['balance'] : 0;

    // Last 10 transactions
    $stmt = $conn->prepare(
        'SELECT id, amount, type, reason, reference_id, created_at
           FROM credit_transactions
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 10'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    respond([
        'success'             => true,
        'balance'             => $balance,
        'recent_transactions' => $transactions,
    ]);
}

// ── POST — earn or spend ─────────────────────────────────────
if ($method === 'POST') {
    $data   = get_body();
    $action = trim($data['action']       ?? '');
    $reason = trim($data['reason']       ?? '');
    $amount = (int)($data['amount']      ?? 0);
    $ref_id = isset($data['reference_id']) ? (int)$data['reference_id'] : null;

    if (!in_array($action, ['earn', 'spend'])) fail('Invalid action. Must be earn or spend.');
    if ($amount <= 0)                          fail('Amount must be a positive integer.');
    if (!$reason)                              fail('Reason is required.');

    // ── EARN ─────────────────────────────────────────────────
    if ($action === 'earn') {

        // Record transaction (positive amount)
        $stmt = $conn->prepare(
            'INSERT INTO credit_transactions (user_id, amount, type, reason, reference_id)
             VALUES (?, ?, \'earn\', ?, ?)'
        );
        $stmt->bind_param('iisi', $uid, $amount, $reason, $ref_id);
        if (!$stmt->execute()) fail('Failed to record transaction.', 500);
        $stmt->close();

        // Upsert balance
        $stmt = $conn->prepare(
            'INSERT INTO credits (user_id, balance) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE balance = balance + ?'
        );
        $stmt->bind_param('iii', $uid, $amount, $amount);
        if (!$stmt->execute()) fail('Failed to update balance.', 500);
        $stmt->close();

        // Fetch new balance
        $stmt = $conn->prepare('SELECT balance FROM credits WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $new_balance = (int)$stmt->get_result()->fetch_assoc()['balance'];
        $stmt->close();

        respond(['success' => true, 'new_balance' => $new_balance]);
    }

    // ── SPEND ────────────────────────────────────────────────
    if ($action === 'spend') {

        // Check current balance
        $stmt = $conn->prepare('SELECT balance FROM credits WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $current = $row ? (int)$row['balance'] : 0;

        if ($current < $amount) fail('Insufficient credits.', 402);

        // Record transaction (negative amount)
        $neg_amount = -$amount;
        $stmt = $conn->prepare(
            'INSERT INTO credit_transactions (user_id, amount, type, reason, reference_id)
             VALUES (?, ?, \'spend\', ?, ?)'
        );
        $stmt->bind_param('iisi', $uid, $neg_amount, $reason, $ref_id);
        if (!$stmt->execute()) fail('Failed to record transaction.', 500);
        $stmt->close();

        // Deduct from balance
        $stmt = $conn->prepare(
            'UPDATE credits SET balance = balance - ? WHERE user_id = ?'
        );
        $stmt->bind_param('ii', $amount, $uid);
        if (!$stmt->execute()) fail('Failed to update balance.', 500);
        $stmt->close();

        // Fetch new balance
        $stmt = $conn->prepare('SELECT balance FROM credits WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $new_balance = (int)$stmt->get_result()->fetch_assoc()['balance'];
        $stmt->close();

        respond(['success' => true, 'new_balance' => $new_balance]);
    }
}

fail('Method not allowed.', 405);
