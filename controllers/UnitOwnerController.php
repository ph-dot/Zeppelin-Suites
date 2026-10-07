<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/UnitOwner.php';

/**
 * Zeppelin Suites - Unit Owner Controller
 * Manages unit owner portal workflows: properties overview, units, leases, tenants, maintenance, and calendar.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class UnitOwnerController extends Controller {
    private UnitOwner $ownerModel;

    public function __construct() {
        $this->ownerModel = new UnitOwner();
    }

    /**
     * Display Unit Owner Overview Dashboard.
     */
    public function overview(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $overview = $this->ownerModel->getOverviewData($ownerId);

        $this->render('owner/overview', [
            'pageTitle'           => 'Zeppelin Suites — Unit Owner Overview',
            'activeTab'           => 'overview',
            'baseUrl'             => $baseUrl,
            'ownerName'           => $userSession['full_name'],
            'ownerInitial'        => $userSession['initial'],
            'ownedUnits'          => $overview['ownedUnits'],
            'occupiedUnits'       => $overview['occupiedUnits'],
            'availableUnits'      => $overview['availableUnits'],
            'reservedUnits'       => $overview['reservedUnits'],
            'recentTenants'       => $overview['recentTenants'],
            'maintenanceRequests' => $overview['maintenanceRequests'],
            'reservationRequests' => $overview['reservationRequests'],
        ]);
    }

    /**
     * Display Owned Units List.
     */
    public function units(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $units = $this->ownerModel->getOwnerUnits($ownerId);

        $this->render('owner/units', [
            'pageTitle'    => 'Zeppelin Suites — My Units',
            'activeTab'    => 'units',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
            'units'        => $units,
        ]);
    }

    /**
     * Display Single Owned Unit Details.
     */
    public function showUnit(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];
        $unitId = (int)($this->getQuery('id', 0) ?: $this->getQuery('unit_id', 0));

        if ($unitId <= 0) {
            $this->redirect("{$baseUrl}/owner/units");
            return;
        }

        $details = $this->ownerModel->getUnitDetails($ownerId, $unitId);
        if (!$details) {
            $this->redirect("{$baseUrl}/owner/units");
            return;
        }

        $this->render('owner/unit_details', [
            'pageTitle'     => 'Zeppelin Suites — Unit #' . ($details['unit']['unit_number'] ?? ''),
            'activeTab'     => 'units',
            'baseUrl'       => $baseUrl,
            'ownerName'     => $userSession['full_name'],
            'ownerInitial'  => $userSession['initial'],
            'unit'          => $details['unit'],
            'activeTenant'  => $details['activeTenant'],
            'pastTenants'   => $details['pastTenants'],
            'leasesList'    => $details['leasesList'],
            'latestMoveOut' => $details['latestMoveOut'],
        ]);
    }

    /**
     * Display Owner Inquiries / Approval Requests.
     */
    public function inquiries(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $inquiries = $this->ownerModel->getOwnerInquiries($ownerId);

        $this->render('owner/inquiries', [
            'pageTitle'    => 'Zeppelin Suites — Inquiries',
            'activeTab'    => 'inquiries',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
            'inquiries'    => $inquiries,
        ]);
    }

    /**
     * Display Owner Leases & Reservations.
     */
    public function reservations(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $reservations = $this->ownerModel->getOwnerReservations($ownerId);

        $this->render('owner/reservations', [
            'pageTitle'    => 'Zeppelin Suites — Lease Management',
            'activeTab'    => 'reservations',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
            'reservations' => $reservations,
        ]);
    }

    /**
     * Display Single Reservation Detail for Owner.
     */
    public function showReservation(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];
        $resId = (int)($this->getQuery('reservation_id', 0) ?: $this->getQuery('id', 0));

        if ($resId <= 0) {
            $this->redirect("{$baseUrl}/owner/reservations");
            return;
        }

        $res = $this->ownerModel->getReservationDetails($ownerId, $resId);
        if (!$res) {
            $this->redirect("{$baseUrl}/owner/reservations");
            return;
        }

        $this->render('owner/view_reservation', [
            'pageTitle'    => 'Zeppelin Suites — View Reservation #' . $resId,
            'activeTab'    => 'reservations',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
            'reservation'  => $res,
        ]);
    }

    /**
     * Display Owner Booking Calendar.
     */
    public function calendar(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('owner/booking_calendar', [
            'pageTitle'    => 'Zeppelin Suites — Booking Calendar',
            'activeTab'    => 'bookingcalendar',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
        ]);
    }

    /**
     * JSON Endpoint for Owner Booking Calendar.
     */
    public function calendarData(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $ownerId = (int)$userSession['user_id'];

        try {
            $data = $this->ownerModel->getCalendarData($ownerId);
            $this->json(array_merge(['success' => true], $data), 200);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display Owner Tenants Directory.
     */
    public function tenants(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $tenants = $this->ownerModel->getOwnerTenants($ownerId);

        $this->render('owner/tenants', [
            'pageTitle'    => 'Zeppelin Suites — Tenants',
            'activeTab'    => 'tenants',
            'baseUrl'      => $baseUrl,
            'ownerName'    => $userSession['full_name'],
            'ownerInitial' => $userSession['initial'],
            'tenants'      => $tenants,
        ]);
    }

    /**
     * Display Owner Maintenance Tickets.
     */
    public function maintenance(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $maintData = $this->ownerModel->getOwnerMaintenance($ownerId);

        $this->render('owner/maintenance', [
            'pageTitle'          => 'Zeppelin Suites — Maintenance',
            'activeTab'          => 'maintenance',
            'baseUrl'            => $baseUrl,
            'ownerName'          => $userSession['full_name'],
            'ownerInitial'       => $userSession['initial'],
            'unitTypeOptions'    => $maintData['unitTypeOptions'],
            'ownerUnitsList'     => $maintData['ownerUnitsList'],
            'tickets'            => $maintData['tickets'],
            'activeTickets'      => $maintData['activeTickets'],
            'unassignedTickets'  => $maintData['unassignedTickets'],
            'closedTickets'      => $maintData['closedTickets'],
            'totalTicketsCount'  => $maintData['totalTicketsCount'],
            'activeCount'        => $maintData['activeCount'],
            'unassignedCount'    => $maintData['unassignedCount'],
            'closedCount'        => $maintData['closedCount'],
        ]);
    }

    /**
     * Display and Update Owner Account Settings.
     */
    public function account(): void {
        $userSession = Middleware::requireRole(['unit owner']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $ownerId = (int)$userSession['user_id'];

        $toast = null;
        $activeSubTab = $this->getQuery('tab', 'profile');

        if ($this->isPost()) {
            $action = (string)$this->getPost('action', '');
            if ($action === 'update_profile') {
                $result = $this->ownerModel->updateProfile($ownerId, $_POST);
                $toast = [
                    'type' => $result['success'] ? 'success' : 'error',
                    'msg'  => $result['message'],
                ];
                if ($result['success'] && !empty($_POST['full_name'])) {
                    $_SESSION['full_name'] = trim((string)$_POST['full_name']);
                }
            } elseif ($action === 'upload_gcash_qr') {
                $activeSubTab = 'payment';
                $result = $this->ownerModel->uploadGcashQr($ownerId, $_FILES['gcash_qr_image'] ?? []);
                $toast = [
                    'type' => $result['success'] ? 'success' : 'error',
                    'msg'  => $result['message'],
                ];
            } elseif ($action === 'delete_gcash_qr') {
                $activeSubTab = 'payment';
                $result = $this->ownerModel->deleteGcashQr($ownerId);
                $toast = [
                    'type' => $result['success'] ? 'success' : 'error',
                    'msg'  => $result['message'],
                ];
            }
        }

        $accountData = $this->ownerModel->getAccountData($ownerId);
        if (!$accountData) {
            $this->redirect("{$baseUrl}/owner/overview");
            return;
        }

        $this->render('owner/account', [
            'pageTitle'       => 'Zeppelin Suites — Account Settings',
            'activeTab'       => 'account',
            'activeSubTab'    => $activeSubTab,
            'baseUrl'         => $baseUrl,
            'ownerName'       => (string)($accountData['owner']['full_name'] ?? $userSession['full_name']),
            'ownerInitial'    => $userSession['initial'],
            'owner'           => $accountData['owner'],
            'hasDobCol'       => $accountData['hasDobCol'],
            'hasAddPhoneCol'  => $accountData['hasAddPhoneCol'],
            'hasAddEmailCol'  => $accountData['hasAddEmailCol'],
            'hasQrCol'        => $accountData['hasQrCol'],
            'units'           => $accountData['units'],
            'maintenance'     => $accountData['maintenance'],
            'unitsCount'      => $accountData['unitsCount'],
            'requestsCount'   => $accountData['requestsCount'],
            'pendingRequests' => $accountData['pendingRequests'],
            'toast'           => $toast,
        ]);
    }
}
