<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

$res = [
    'php_version' => PHP_VERSION,
    'mysqli_ext' => extension_loaded('mysqli'),
    'pdo_mysql_ext' => extension_loaded('pdo_mysql'),
    'env_file_exists' => file_exists(__DIR__ . '/../.env'),
    'db' => [
        'host' => getenv('DB_HOST'),
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
    ]
];

try {
    $conn = new mysqli(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: '',
        getenv('DB_NAME') ?: 'betterabroad'
    );
    
    if ($conn->connect_error) {
        $res['db_status'] = 'Connection failed: ' . $conn->connect_error;
    } else {
        $res['db_status'] = 'Connected successfully';
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while($row = $result->fetch_row()) $tables[] = $row[0];
        $res['tables'] = $tables;
        $conn->close();
    }
} catch (Exception $e) {
    $res['db_status'] = 'Error: ' . $e->getMessage();
}

echo json_encode($res, JSON_PRETTY_PRINT);
