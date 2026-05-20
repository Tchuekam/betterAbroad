<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Load environment values first (.env at project root)
        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        loadEnv($envPath);

        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'betterabroad';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        self::$connection = new PDO($dsn, $user, $pass, $options);

        return self::$connection;
    }
}
