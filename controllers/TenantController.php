<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Tenant.php';

/**
 * Zeppelin Suites - Tenant Controller
 * Manages tenant portal workflows: overview dashboard, profile/account settings, and maintenance tickets.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class TenantController extends Controller {
    private Tenant $tenantModel;

    public function __construct() {
        $this->tenantModel = new Tenant();
    }

    /**
     * Display Tenant Home Overview.
     */
    public function home(): void {
        $userSession = Middleware::requireRole(['tenant']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $tenantId = (int)$userSession['user_id'];
        $email = (string)($userSession['email'] ?? '');
        $name = (string)$userSession['full_name'];

        $overview = $this->tenantModel->getOverviewData($tenantId, $email, $name);

        $this->render('tenant/home', [
            'pageTitle'              => 'Zeppelin Suites — Tenant Home',
            'activeTab'              => 'home',
            'baseUrl'                => $baseUrl,
            'tenantUser'             => $overview['user'],
            'leaseInfo'              => $overview['lease'],
            'activeMaintenanceCount' => $overview['activeMaintenanceCount'],
            'recentTickets'          => $overview['recentTickets'],
            'tenantName'             => $name,
            'tenantEmail'            => $email,
            'tenantInitials'         => $userSession['initial'],
        ]);
    }

    /**
     * Display and handle Tenant Account/Profile updates.
     */
    public function account(): void {
        $userSession = Middleware::requireRole(['tenant']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $tenantId = (int)$userSession['user_id'];
        $toast = null;

        if ($this->isPost()) {
            $action = (string)$this->getPost('action', '');
            if ($action === 'update_profile') {
                $result = $this->tenantModel->updateProfile($tenantId, $_POST);
                $toast = [
                    'type' => $result['success'] ? 'success' : 'error',
                    'msg'  => $result['message'],
                ];
                if ($result['success'] && !empty($_POST['full_name'])) {
                    $_SESSION['full_name'] = trim((string)$_POST['full_name']);
                }
            }
        }

        $accountData = $this->tenantModel->getAccountData($tenantId);
        $tenant = $accountData['tenant'] ?? null;
        $leases = $accountData['leases'] ?? [];
        $maintenance = $accountData['maintenance'] ?? [];

        $name = (string)($tenant['full_name'] ?? $userSession['full_name']);
        $initials = strtoupper(substr(trim($name ?: 'T'), 0, 1));

        $this->render('tenant/account', [
            'pageTitle'      => 'Zeppelin Suites — Account Settings',
            'activeTab'      => 'account',
            'baseUrl'        => $baseUrl,
            'tenant'         => $tenant,
            'leases'         => $leases,
            'maintenance'    => $maintenance,
            'tenantName'     => $name,
            'tenantEmail'    => (string)($tenant['email'] ?? $userSession['email'] ?? ''),
            'tenantInitials' => $initials,
            'toast'          => $toast,
        ]);
    }

    /**
     * Display Tenant Maintenance Ticket List & Request Form.
     */
    public function maintenance(): void {
        $userSession = Middleware::requireRole(['tenant']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $tenantId = (int)$userSession['user_id'];
        $email = (string)($userSession['email'] ?? '');
        $name = (string)$userSession['full_name'];

        $units = $this->tenantModel->getTenantUnits($email, $name);
        $tickets = $this->tenantModel->getMaintenanceTickets($tenantId, $name);

        $successMessage = $_SESSION['success_message'] ?? null;
        $errorMessage = $_SESSION['error_message'] ?? null;
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        $this->render('tenant/maintenance', [
            'pageTitle'       => 'Zeppelin Suites — Maintenance',
            'activeTab'       => 'maintenance',
            'baseUrl'         => $baseUrl,
            'tenantName'      => $name,
            'tenantEmail'     => $email,
            'tenantInitials'  => $userSession['initial'],
            'tenantUnits'     => $units,
            'tickets'         => $tickets,
            'successMessage'  => $successMessage,
            'errorMessage'    => $errorMessage,
        ]);
    }

    /**
     * Process Tenant Maintenance Request Submission (POST).
     */
    public function storeMaintenance(): void {
        $userSession = Middleware::requireRole(['tenant']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/tenant/maintenance");
            return;
        }

        $tenantId = (int)$userSession['user_id'];
        $email = (string)($userSession['email'] ?? '');
        $name = (string)$userSession['full_name'];

        $result = $this->tenantModel->createMaintenanceRequest($tenantId, $email, $name, $_POST, $_FILES);

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }

        $this->redirect("{$baseUrl}/tenant/maintenance");
    }
}
