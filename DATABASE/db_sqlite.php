<?php
// SQLite connection
try {
    $dbPath = __DIR__ . '/betterabroad.sqlite';
    $conn = new PDO("sqlite:" . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Initialize tables if needed
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student',
        is_active INTEGER DEFAULT 1
    )");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
