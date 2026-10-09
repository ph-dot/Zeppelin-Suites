<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Unit.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Unit Management Controller
 * Handles listing, adding, viewing, and owner assignment for building units.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class UnitController extends Controller {
    private Unit $unitModel;

    public function __construct() {
        $this->unitModel = new Unit();
    }

    /**
     * Display building units categorized by floor with filter controls.
     */
    public function index(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $unitsByFloor = $this->unitModel->getAllGroupedByFloor();
        $ownerOptions = $this->unitModel->getOwnerOptions();
        $totalUnitsCount = $this->unitModel->getTotalUnitsCount();

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $successMessage = $this->getFlash('success_message');
        $errorMessage = $this->getFlash('error_message');

        $this->render('admin/units', [
            'pageTitle'           => 'Zeppelin Suites Admin - Units',
            'activeTab'           => 'units',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'unitsByFloor'        => $unitsByFloor,
            'ownerOptions'        => $ownerOptions,
            'totalUnitsCount'     => $totalUnitsCount,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
            'successMessage'      => $successMessage,
            'errorMessage'        => $errorMessage,
        ]);
    }

    /**
     * Display full details, active tenant, and ownership history for a unit.
     */
    public function show(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $unitId = (int)($this->getQuery('id', 0) ?: $this->getQuery('unit_id', 0));
        if ($unitId <= 0) {
            $this->redirect("{$baseUrl}/admin/units");
            return;
        }

        $unit = $this->unitModel->getUnitDetails($unitId);
        if (!$unit) {
            $this->setFlash('error_message', 'Unit not found.');
            $this->redirect("{$baseUrl}/admin/units");
            return;
        }

        $activeTenant = $this->unitModel->getActiveTenant($unitId);
        $nextAvail = $this->unitModel->getNextAvailability($unitId, (string)$unit['unit_current_status']);
        $currentOwnerId = $unit['unit_owner_id'] !== null ? (int)$unit['unit_owner_id'] : null;
        $currentOwnerStartDate = $this->unitModel->getCurrentOwnerStartDate($unitId, $currentOwnerId, $unit['created_at'] ?? null);
        $pastOwners = $this->unitModel->getPastOwners($unitId, $currentOwnerId);
        $allTenantsList = $this->unitModel->getAllTenants($unitId, $unit['owner_name'] ?? null);
        $ownerOptions = $this->unitModel->getOwnerOptions();

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $this->render('admin/unit_details', [
            'pageTitle'              => 'Zeppelin Suites Admin - Unit ' . ($unit['unit_number'] ?? '#' . $unitId),
            'activeTab'              => 'units',
            'adminName'              => $userSession['full_name'],
            'adminInitial'           => $userSession['initial'],
            'baseUrl'                => $baseUrl,
            'unit'                   => $unit,
            'unitId'                 => $unitId,
            'activeTenant'           => $activeTenant,
            'nextAvailability'       => $nextAvail,
            'currentOwnerStartDate'  => $currentOwnerStartDate,
            'pastOwners'             => $pastOwners,
            'allTenantsList'         => $allTenantsList,
            'ownerOptions'           => $ownerOptions,
            'pendingInquiries'       => $pendingCounts['pending_inquiries'],
            'pendingReservations'    => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * AJAX endpoint to generate preview of next unit number.
     */
    public function getNextNumber(): void {
        Middleware::requireRole(['admin']);

        $unitType = trim((string)$this->getQuery('unit_type', ''));
        $info = $this->unitModel->getNextUnitNumber($unitType);

        if (!$info) {
            $this->json(['success' => false, 'message' => 'Invalid unit type.'], 400);
            return;
        }

        $this->json([
            'success'     => true,
            'unit_number' => $info['unit_number'],
            'sqm'         => $info['sqm'],
        ]);
    }

    /**
     * Handle creation of a new unit.
     */
    public function store(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/admin/units");
            return;
        }

        try {
            $unitId = $this->unitModel->create($_POST);
            $this->setFlash('success_message', 'Unit added successfully.');
            $this->redirect("{$baseUrl}/admin/units/view?id={$unitId}");
            return;
        } catch (Throwable $e) {
            $this->setFlash('error_message', $e->getMessage());
            $this->redirect("{$baseUrl}/admin/units");
        }
    }

    /**
     * Handle AJAX update of unit ownership and details.
     */
    public function update(): void {
        Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
            return;
        }

        $unitId = (int)$this->getPost('unit_id', 0);
        if ($unitId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid unit ID.'], 400);
            return;
        }

        try {
            $result = $this->unitModel->updateOwnership($unitId, $_POST);
            $this->json([
                'success' => true,
                'message' => 'Unit details and owner assignment updated successfully!',
                'data'    => $result,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
