<?php
// ============================================================
//  admin_log.php  GET — Retrieve admin activity logs
// ============================================================
// Returns recent admin actions for the Activity Log dashboard tab
// Usage: GET /DATABASE/admin_log.php
// Returns: { success: true, logs: [{action, target_type, details, ip_address, created_at}, ...] }

require_once __DIR__ . '/db.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    // 📋 Fetch recent admin logs
    $sql = "SELECT 
                id,
                admin_id,
                action,
                target_type,
                target_id,
                details,
                ip_address,
                created_at
            FROM admin_log
            ORDER BY created_at DESC
            LIMIT 100";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = [
            'id'          => (int) $row['id'],
            'admin_id'    => (int) $row['admin_id'],
            'action'      => $row['action'],
            'target_type' => $row['target_type'],
            'target_id'   => $row['target_id'] ? (int) $row['target_id'] : null,
            'details'     => $row['details'],
            'ip_address'  => $row['ip_address'],
            'created_at'  => $row['created_at'],
        ];
    }
    
    respond([
        'success' => true,
        'logs'    => $logs,
        'count'   => count($logs),
    ]);
    
} catch (Exception $e) {
    respond(['success' => false, 'error' => $e->getMessage()], 500);
}
