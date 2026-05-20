<?php
declare(strict_types=1);

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        ob_end_clean();
        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::json([
            'success' => true,
            'status' => 'success',
            'data'   => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $meta = []): void
    {
        self::json([
            'success' => false,
            'status'  => 'error',
            'message' => $message,
            'meta'    => $meta,
        ], $status);
    }
}
