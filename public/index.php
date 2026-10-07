<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - MVC Front Controller & Single Web Entrypoint
 */

// 1. Initialize environment configuration
require_once dirname(__DIR__) . '/config/env.php';

// 2. Configure error reporting based on environment
if (env('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 3. Initialize secure session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// 4. Class autoloader for core, controllers, models, and config
spl_autoload_register(function (string $className): void {
    $root = dirname(__DIR__);
    $directories = [
        $root . '/core/',
        $root . '/config/',
        $root . '/controllers/',
        $root . '/models/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . str_replace('\\', '/', $className) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 5. Initialize the Central MVC Router
$appUrl = (string)env('APP_URL', '/Zeppelin-Suites/public');
$basePath = parse_url($appUrl, PHP_URL_PATH) ?? '';
$router = new Router($basePath);

// 6. Define Application Routes
// Landing / Default Route
$router->get('/', function () {
    header('Location: generalViewPages/index.html');
    exit;
});

// Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// 7. Dispatch incoming HTTP request
$router->dispatch();
