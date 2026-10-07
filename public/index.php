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
// Public & General Marketing Routes
$router->get('/', [GeneralController::class, 'index']);
$router->get('/home', [GeneralController::class, 'index']);
$router->get('/about', [GeneralController::class, 'about']);
$router->get('/about-us', [GeneralController::class, 'about']);
$router->get('/faq', [GeneralController::class, 'faq']);
$router->get('/tour', [GeneralController::class, 'tour']);
$router->get('/virtual-tour', [GeneralController::class, 'tour']);

// Unit Showcase Routes
$router->get('/units/studio-type-a', [GeneralController::class, 'studioTypeA']);
$router->get('/units/studio-type-b', [GeneralController::class, 'studioTypeB']);
$router->get('/units/one-bedroom', [GeneralController::class, 'oneBedroom']);
$router->get('/units/two-bedroom', [GeneralController::class, 'twoBedroom']);

// Policy & Legal Routes
$router->get('/privacy-policy', [GeneralController::class, 'privacyPolicy']);
$router->get('/terms-of-service', [GeneralController::class, 'termsOfService']);

// Contact & Inquiry Submission Routes
$router->get('/contact', [GeneralController::class, 'contact']);
$router->post('/contact', [GeneralController::class, 'submitInquiry']);
$router->post('/inquiry/submit', [GeneralController::class, 'submitInquiry']);
$router->get('/inquiry-confirmation', [GeneralController::class, 'inquiryConfirmation']);

// Public Reservation Routes
$router->get('/reservation', [GeneralController::class, 'reservationForm']);
$router->post('/reservation', [GeneralController::class, 'submitReservation']);
$router->post('/reservation/submit', [GeneralController::class, 'submitReservation']);
$router->get('/reservation-confirmation', [GeneralController::class, 'reservationConfirmation']);

// Client Cancellation Request Routes
$router->get('/cancel-reservation', [GeneralController::class, 'cancelReservation']);
$router->post('/cancel-reservation', [GeneralController::class, 'submitCancellation']);
$router->post('/cancel-reservation/submit', [GeneralController::class, 'submitCancellation']);
$router->get('/cancellation-confirmation', [GeneralController::class, 'cancellationConfirmation']);

// Public API Utility Routes
$router->get('/api/check-email-domain', [GeneralController::class, 'checkEmailDomain']);

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

// 7. Legacy Infrastructure 301 Fallback Redirections
$legacyRouteMap = [
    // Admin legacy paths
    '/adminPages/analytics.php'       => '/admin/analytics',
    '/admin/analytics.php'            => '/admin/analytics',
    '/adminPages/homeAdmin.php'       => '/admin/home',
    '/adminPages/account.php'         => '/admin/account',
    '/adminPages/inquiry.php'         => '/admin/inquiries',
    '/adminPages/replyform.php'       => '/admin/inquiries/reply',
    '/adminPages/reservation.php'     => '/admin/reservations',
    '/adminPages/viewReservation.php' => '/admin/reservations/view',
    '/adminPages/residents.php'       => '/admin/residents',
    '/adminPages/viewResident.php'    => '/admin/residents/view',
    '/adminPages/units.php'           => '/admin/units',
    '/adminPages/unitDetails.php'     => '/admin/units/view',
    '/adminPages/maintenance.php'     => '/admin/maintenance',
    '/adminPages/bookingcalendar.php' => '/admin/booking-calendar',

    // Tenant legacy paths
    '/tenantPages/homeTenant.php'        => '/tenant/home',
    '/tenantPages/account.php'           => '/tenant/account',
    '/tenantPages/maintenanceTenant.php' => '/tenant/maintenance',

    // Unit Owner legacy paths
    '/unitOwnerPages/overview.php'              => '/owner/overview',
    '/unitOwnerPages/ownersUnit.php'            => '/owner/units',
    '/unitOwnerPages/unitDetails.php'           => '/owner/units/view',
    '/unitOwnerPages/ownersInquiries.php'       => '/owner/inquiries',
    '/unitOwnerPages/ownersReservations.php'    => '/owner/reservations',
    '/unitOwnerPages/ownersUnitReservations.php'=> '/owner/reservations',
    '/unitOwnerPages/ownersViewReservation.php' => '/owner/reservations/view',
    '/unitOwnerPages/ownersBookingCalendar.php' => '/owner/booking-calendar',
    '/unitOwnerPages/tenants.php'               => '/owner/tenants',
    '/unitOwnerPages/ownersMaintenance.php'     => '/owner/maintenance',
    '/unitOwnerPages/account.php'               => '/owner/account',

    // General / Public legacy paths
    '/generalViewPages/index.html'               => '/',
    '/generalViewPages/aboutUs.html'             => '/about',
    '/generalViewPages/faq.html'                 => '/faq',
    '/generalViewPages/tour.html'                => '/tour',
    '/generalViewPages/studioTypeA.html'         => '/units/studio-type-a',
    '/generalViewPages/studioTypeB.html'         => '/units/studio-type-b',
    '/generalViewPages/oneBedroom.html'          => '/units/one-bedroom',
    '/generalViewPages/twoBedroom.html'          => '/units/two-bedroom',
    '/generalViewPages/privacy-policy.html'      => '/privacy-policy',
    '/generalViewPages/terms-of-service.htm'     => '/terms-of-service',
    '/generalViewPages/contact.php'              => '/contact',
    '/generalViewPages/inquiryConfirmation.html' => '/inquiry-confirmation',
    '/generalViewPages/reservationform.php'      => '/reservation',
    '/generalViewPages/reservationConfirmation.html' => '/reservation-confirmation',
    '/generalViewPages/cancelReservation.php'    => '/cancel-reservation',
    '/generalViewPages/cancellationConfirmation.html'=> '/cancellation-confirmation',
    '/generalViewPages/login.php'                => '/login',
];

foreach ($legacyRouteMap as $legacyPath => $cleanTarget) {
    $router->any($legacyPath, function () use ($appUrl, $cleanTarget) {
        $baseUrl = rtrim($appUrl, '/');
        $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('HTTP/1.1 301 Moved Permanently');
        header("Location: {$baseUrl}{$cleanTarget}{$qs}");
        exit;
    });
}

// 8. Dispatch incoming HTTP request
$router->dispatch();


