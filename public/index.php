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

// Admin Portal Routes (Guarded by 'admin' role middleware)
$router->get('/admin', [AdminController::class, 'dashboard'], ['admin']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard'], ['admin']);
$router->get('/admin/home', [AdminController::class, 'home'], ['admin']);
$router->get('/admin/pending-actions', [AdminController::class, 'pendingActions'], ['admin']);
$router->get('/admin/account', [AdminController::class, 'account'], ['admin']);
$router->get('/admin/analytics', [AdminController::class, 'analytics'], ['admin']);

$router->get('/admin/inquiries', [InquiryController::class, 'index'], ['admin']);
$router->post('/admin/inquiries/status', [InquiryController::class, 'updateStatus'], ['admin']);
$router->get('/admin/inquiries/reply', [InquiryController::class, 'replyForm'], ['admin']);
$router->post('/admin/inquiries/reply', [InquiryController::class, 'sendReply'], ['admin']);

$router->get('/admin/reservations', [ReservationController::class, 'index'], ['admin']);
$router->get('/admin/reservations/view', [ReservationController::class, 'show'], ['admin']);
$router->post('/admin/reservations/handover', [ReservationController::class, 'handover'], ['admin']);

$router->get('/admin/residents', [ResidentController::class, 'index'], ['admin']);
$router->get('/admin/residents/view', [ResidentController::class, 'show'], ['admin']);
$router->post('/admin/residents', [ResidentController::class, 'store'], ['admin']);
$router->post('/admin/residents/update', [ResidentController::class, 'update'], ['admin']);
$router->post('/admin/residents/toggle-status', [ResidentController::class, 'toggleStatus'], ['admin']);
$router->post('/admin/residents/profile-action', [ResidentController::class, 'profileAction'], ['admin']);

$router->get('/admin/units', [UnitController::class, 'index'], ['admin']);
$router->get('/admin/units/view', [UnitController::class, 'show'], ['admin']);
$router->get('/admin/units/next-number', [UnitController::class, 'getNextNumber'], ['admin']);
$router->post('/admin/units', [UnitController::class, 'store'], ['admin']);
$router->post('/admin/units/create', [UnitController::class, 'store'], ['admin']);
$router->post('/admin/units/update', [UnitController::class, 'update'], ['admin']);

$router->get('/admin/maintenance', [MaintenanceController::class, 'index'], ['admin']);
$router->post('/admin/maintenance', [MaintenanceController::class, 'store'], ['admin']);
$router->post('/admin/maintenance/create', [MaintenanceController::class, 'store'], ['admin']);
$router->post('/admin/maintenance/update', [MaintenanceController::class, 'update'], ['admin']);

$router->get('/admin/booking-calendar', [BookingCalendarController::class, 'index'], ['admin']);
$router->get('/admin/booking-calendar/data', [BookingCalendarController::class, 'getData'], ['admin']);
$router->post('/admin/booking-calendar/block', [BookingCalendarController::class, 'saveBlockedDate'], ['admin']);
$router->post('/admin/booking-calendar/unblock', [BookingCalendarController::class, 'deleteBlockedDate'], ['admin']);

// Tenant Portal Routes (Guarded by 'tenant' role middleware)
$router->get('/tenant', [TenantController::class, 'home'], ['tenant']);
$router->get('/tenant/home', [TenantController::class, 'home'], ['tenant']);
$router->get('/tenant/account', [TenantController::class, 'account'], ['tenant']);
$router->post('/tenant/account', [TenantController::class, 'account'], ['tenant']);
$router->get('/tenant/maintenance', [TenantController::class, 'maintenance'], ['tenant']);
$router->post('/tenant/maintenance', [TenantController::class, 'storeMaintenance'], ['tenant']);

// Unit Owner Portal Routes (Guarded by 'unit owner' role middleware)
$router->get('/owner', [UnitOwnerController::class, 'overview'], ['unit owner']);
$router->get('/owner/overview', [UnitOwnerController::class, 'overview'], ['unit owner']);
$router->get('/owner/units', [UnitOwnerController::class, 'units'], ['unit owner']);
$router->get('/owner/units/view', [UnitOwnerController::class, 'showUnit'], ['unit owner']);
$router->get('/owner/inquiries', [UnitOwnerController::class, 'inquiries'], ['unit owner']);
$router->get('/owner/reservations', [UnitOwnerController::class, 'reservations'], ['unit owner']);
$router->get('/owner/reservations/view', [UnitOwnerController::class, 'showReservation'], ['unit owner']);
$router->get('/owner/booking-calendar', [UnitOwnerController::class, 'calendar'], ['unit owner']);
$router->get('/owner/booking-calendar/data', [UnitOwnerController::class, 'calendarData'], ['unit owner']);
$router->get('/owner/tenants', [UnitOwnerController::class, 'tenants'], ['unit owner']);
$router->get('/owner/maintenance', [UnitOwnerController::class, 'maintenance'], ['unit owner']);
$router->get('/owner/account', [UnitOwnerController::class, 'account'], ['unit owner']);
$router->post('/owner/account', [UnitOwnerController::class, 'account'], ['unit owner']);

// 7. Dispatch incoming HTTP request
$router->dispatch();


