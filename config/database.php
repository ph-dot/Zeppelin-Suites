<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Zeppelin Suites - PDO Database Connection Manager
 * Provides a shared singleton PDO connection reading strictly from environment variables.
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * Get the active PDO database connection instance.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host    = (string)env('DB_HOST', '127.0.0.1');
            $port    = (string)env('DB_PORT', '3306');
            $dbName  = (string)env('DB_NAME', 'zepellin_test');
            $user    = (string)env('DB_USER', 'root');
            $pass    = (string)env('DB_PASS', '');
            $charset = (string)env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());

                // In debug mode, show descriptive error; in production, show generic error
                if (env('APP_DEBUG', false)) {
                    die("Database Connection Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
                }

                die(json_encode([
                    'status' => 'error',
                    'message' => 'Unable to connect to the database service. Please contact system administrator.'
                ]));
            }
        }

        return self::$instance;
    }

    /**
     * Prevent direct cloning or instantiation.
     */
    private function __construct() {}
    private function __clone() {}
}
