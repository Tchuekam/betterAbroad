<?php
session_name('BA_SESSION');
session_start();
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'user_id'    => $_SESSION['user_id'] ?? 'NONE',
    'role'       => $_SESSION['role']    ?? 'NONE',
    'email'      => $_SESSION['email']   ?? 'NONE',
]);