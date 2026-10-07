<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Lightweight .env Environment Loader
 */
if (!function_exists('loadEnv')) {
    function loadEnv(string $filePath): void {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split on the first '=' character
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            // Handle quoted strings
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Convert boolean and null strings
            $lowerVal = strtolower($value);
            if ($lowerVal === 'true') {
                $parsedVal = true;
            } elseif ($lowerVal === 'false') {
                $parsedVal = false;
            } elseif ($lowerVal === 'null') {
                $parsedVal = null;
            } else {
                $parsedVal = $value;
            }

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $parsedVal;
            }
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $parsedVal;
            }
            putenv("{$key}={$value}");
        }
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable with optional fallback.
     */
    function env(string $key, mixed $default = null): mixed {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}

// Automatically load .env from the project root if it exists
$rootDir = dirname(__DIR__);
loadEnv($rootDir . DIRECTORY_SEPARATOR . '.env');
