<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Maintenance.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Maintenance Controller
 * Handles tickets, kanban workflows, status updates, and ticket creation.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class MaintenanceController extends Controller {
    private Maintenance $maintenanceModel;

    public function __construct() {
        $this->maintenanceModel = new Maintenance();
    }

    /**
     * Display the Kanban Maintenance Tickets board.
     */
    public function index(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $grouped = $this->maintenanceModel->getGroupedTickets();
        $unitTypes = $this->maintenanceModel->getUnitTypeOptions();
        $unitOptions = $this->maintenanceModel->getUnitOptions();

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $successMessage = $this->getFlash('success_message');
        $errorMessage = $this->getFlash('error_message');

        $this->render('admin/maintenance', [
            'pageTitle'           => 'Zeppelin Suites - Maintenance Tickets',
            'activeTab'           => 'maintenance',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'activeTickets'       => $grouped['active'],
            'unassignedTickets'   => $grouped['unassigned'],
            'closedTickets'       => $grouped['closed'],
            'totalTicketsCount'   => $grouped['total'],
            'activeCount'         => $grouped['active_count'],
            'unassignedCount'     => $grouped['unassigned_count'],
            'closedCount'         => $grouped['closed_count'],
            'unitTypeOptions'     => $unitTypes,
            'unitOptions'         => $unitOptions,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
            'successMessage'      => $successMessage,
            'errorMessage'        => $errorMessage,
        ]);
    }

    /**
     * Handle updating ticket status and admin remarks (AJAX / POST).
     */
    public function update(): void {
        Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $maintenanceId = (int)$this->getPost('maintenance_id', 0);
        $status = (string)$this->getPost('status', '');
        $adminRemarks = (string)$this->getPost('admin_remarks', '');

        if ($maintenanceId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid maintenance request ID.'], 422);
            return;
        }

        try {
            $result = $this->maintenanceModel->updateStatus($maintenanceId, $status, $adminRemarks);
            if (!$result['success']) {
                $this->json($result, 404);
                return;
            }
            $this->json($result, 200);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Handle creating a new maintenance ticket.
     */
    public function store(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/admin/maintenance");
            return;
        }

        try {
            $adminUserId = (int)($userSession['user_id'] ?? 0);
            $this->maintenanceModel->create($_POST, $_FILES, $adminUserId);
            $this->setFlash('success_message', 'Maintenance ticket created successfully.');
        } catch (Throwable $e) {
            $this->setFlash('error_message', $e->getMessage());
        }

        $this->redirect("{$baseUrl}/admin/maintenance");
    }
}
