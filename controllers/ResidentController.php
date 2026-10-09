<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Resident.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Resident Controller
 * Handles listing, live AJAX filtering, creation, updating, and status toggles for residents.
 * Strictly NO SQL in controller.
 */
class ResidentController extends Controller {
    private Resident $residentModel;

    public function __construct() {
        $this->residentModel = new Resident();
    }

    /**
     * Display residents list or return HTML table rows for live search AJAX.
     */
    public function index(): void {
        $userSession = Middleware::requireRole(['admin']);

        $search = trim((string)$this->getQuery('search', ''));
        $role = trim((string)$this->getQuery('role', ''));
        $status = trim((string)$this->getQuery('status', ''));
        $isAjax = $this->getQuery('ajax') === '1';

        $residents = $this->residentModel->getAll($search, $role, $status);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        // Live AJAX search requests return only rendered table rows
        if ($isAjax) {
            header('Content-Type: text/html; charset=UTF-8');
            if (empty($residents)) {
                echo '<tr><td colspan="7" class="px-4 py-10 text-center text-slate-500 text-sm">No residents found.</td></tr>';
            } else {
                foreach ($residents as $resident) {
                    $this->render('admin/residents_rows', [
                        'resident' => $resident,
                        'baseUrl'  => $baseUrl,
                    ]);
                }
            }
            exit;
        }

        $stats = $this->residentModel->getStats();
        $successMessage = $this->getFlash('success_message');
        $errorMessage = $this->getFlash('error_message');

        $this->render('admin/residents', [
            'pageTitle'      => 'Zeppelin Suites Admin - Residents',
            'activeTab'      => 'residents',
            'adminName'      => $userSession['full_name'],
            'adminInitial'   => $userSession['initial'],
            'baseUrl'        => $baseUrl,
            'stats'          => $stats,
            'residents'      => $residents,
            'search'         => $search,
            'roleFilter'     => $role,
            'statusFilter'   => $status,
            'successMessage' => $successMessage,
            'errorMessage'   => $errorMessage,
        ]);
    }

    /**
     * Handle resident registration.
     */
    public function store(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        try {
            $this->residentModel->create($_POST);
            $this->setFlash('success_message', 'Resident account added successfully.');
        } catch (Throwable $e) {
            $this->setFlash('error_message', $e->getMessage());
        }

        $this->redirect("{$baseUrl}/admin/residents");
    }

    /**
     * Handle resident profile update.
     */
    public function update(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        $userId = (int)$this->getPost('user_id', 0);

        try {
            $this->residentModel->update($userId, $_POST);
            $this->setFlash('success_message', 'Resident account updated successfully.');
        } catch (Throwable $e) {
            $this->setFlash('error_message', $e->getMessage());
        }

        $this->redirect("{$baseUrl}/admin/residents");
    }

    /**
     * Handle resident status toggle ('Active' | 'Inactive').
     */
    public function toggleStatus(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        $userId = (int)$this->getPost('user_id', 0);
        $status = trim((string)$this->getPost('resident_status', 'Inactive'));

        try {
            $this->residentModel->toggleStatus($userId, $status);
            $this->setFlash('success_message', 'Resident status updated successfully.');
        } catch (Throwable $e) {
            $this->setFlash('error_message', $e->getMessage());
        }

        $this->redirect("{$baseUrl}/admin/residents");
    }

    /**
     * Display detailed resident view with owned and rented units.
     */
    public function show(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $userId = (int)($this->getQuery('id', 0) ?: $this->getQuery('user_id', 0));
        if ($userId <= 0) {
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        $resident = $this->residentModel->getResidentDetails($userId);
        if (!$resident) {
            $this->setFlash('error_message', 'Resident not found.');
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $this->render('admin/view_resident', [
            'pageTitle'           => 'Zeppelin Suites - View Resident #' . $userId,
            'activeTab'           => 'residents',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'resident'            => $resident,
            'user_id'             => $userId,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * Handle actions on resident detail page (toggle status or update profile).
     */
    public function profileAction(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $userId = (int)($this->getPost('user_id', 0) ?: $this->getQuery('id', 0));
        if ($userId <= 0) {
            $this->redirect("{$baseUrl}/admin/residents");
            return;
        }

        $action = trim((string)$this->getPost('action', ''));

        if ($action === 'toggle_status') {
            $newStatus = trim((string)$this->getPost('resident_status', 'Active')) === 'Active' ? 'Active' : 'Inactive';
            try {
                $this->residentModel->toggleStatus($userId, $newStatus);
                $this->setFlash('success_message', "Resident status updated to {$newStatus}.");
            } catch (Throwable $e) {
                $this->setFlash('error_message', $e->getMessage());
            }
        } elseif ($action === 'update_profile') {
            $ok = $this->residentModel->updateResidentProfile($userId, $_POST);
            if ($ok) {
                $this->setFlash('success_message', 'Resident profile updated successfully.');
            } else {
                $this->setFlash('error_message', 'Failed to update profile. Please verify all fields.');
            }
        }

        $this->redirect("{$baseUrl}/admin/residents/view?id={$userId}");
    }
}
