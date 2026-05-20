<?php
declare(strict_types=1);

/**
 * Lightweight .env loader.
 * Reads key=value pairs and populates getenv()/$_ENV when not already set.
 */
function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $data = parse_ini_file($path, false, INI_SCANNER_TYPED);
    if ($data === false) {
        return;
    }

    foreach ($data as $key => $value) {
        if (getenv((string) $key) !== false) {
            continue;
        }
        putenv(sprintf('%s=%s', $key, $value));
        $_ENV[$key] = $value;
    }
}
