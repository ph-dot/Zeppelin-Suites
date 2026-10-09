<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Reservation & Lease Management Controller
 * Handles reservation records, handover workflows, and tenant activation.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class ReservationController extends Controller
{
    private Reservation $reservationModel;

    public function __construct()
    {
        $this->reservationModel = new Reservation();
    }

    /**
     * Display the Lease & Reservation Management dashboard.
     */
    public function index(): void
    {
        $userSession = Middleware::requireRole(['admin']);

        // Synchronize expired leases
        $this->reservationModel->syncExpiredUnitStatuses();

        // Fetch all reservations with full relations
        $reservations = $this->reservationModel->getAllWithDetails();

        $baseUrl = rtrim((string) env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('admin/reservations', [
            'pageTitle' => 'Zeppelin Suites Admin - Lease Management',
            'activeTab' => 'reservation',
            'adminName' => $userSession['full_name'],
            'adminInitial' => $userSession['initial'],
            'baseUrl' => $baseUrl,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Process unit handover and tenant account activation (AJAX / POST).
     */
    public function handover(): void
    {
        Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        $password = trim((string) $this->getPost('password', ''));

        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->handover($reservationId, $password !== '' ? $password : null);

        if (!$result['success']) {
            $this->json($result, 422);
            return;
        }

        $this->json($result, 200);
    }

    /**
     * Display comprehensive reservation details page.
     */
    public function show(): void
    {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string) env('APP_URL', '/Zeppelin-Suites'), '/');

        $reservationId = (int) ($this->getQuery('reservation_id', 0) ?: $this->getQuery('id', 0));
        if ($reservationId <= 0) {
            $this->redirect("{$baseUrl}/admin/reservations");
            return;
        }

        $reservation = $this->reservationModel->getDetailsById($reservationId);
        if (!$reservation) {
            $this->redirect("{$baseUrl}/admin/reservations");
            return;
        }

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $this->render('admin/view_reservation', [
            'pageTitle' => 'Zeppelin Suites - View Reservation #' . $reservationId,
            'activeTab' => 'reservation',
            'adminName' => $userSession['full_name'],
            'adminInitial' => $userSession['initial'],
            'baseUrl' => $baseUrl,
            'res' => $reservation,
            'reservation_id' => $reservationId,
            'pendingInquiries' => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * AJAX endpoint to confirm or set agreed lease signing date (Admin).
     */
    public function confirmSigningDate(): void
    {
        Middleware::requireRole(['admin']);
        $this->json([
            'success' => false,
            'message' => 'Only the unit owner is authorized to select and confirm the lease signing appointment date.'
        ], 403);
    }

    /**
     * AJAX endpoint to complete or reset lease signing status (Admin).
     */
    public function updateLeaseSigning(): void
    {
        $userSession = Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        $action = trim((string) $this->getPost('action', 'complete'));
        $remarks = trim((string) $this->getPost('remarks', ''));

        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->updateLeaseSigningStatus(
            $reservationId,
            $action,
            $remarks,
            (int) $userSession['user_id'],
            'admin'
        );

        $this->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX endpoint to verify, flag, or reject reservation payment.
     */
    public function updatePayment(): void
    {
        $userSession = Middleware::requireRole(['admin', 'unit owner']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        $action = trim((string) $this->getPost('action', 'verify'));
        $remarks = trim((string) $this->getPost('remarks', ''));

        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->updatePaymentStatus(
            $reservationId,
            $action,
            $remarks,
            (int) $userSession['user_id'],
            (string) $userSession['role']
        );

        $this->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX endpoint to fetch reservation documents checklist.
     */
    public function getDocuments(): void
    {
        Middleware::requireRole(['admin', 'unit owner']);

        $reservationId = (int) ($this->getQuery('reservation_id', 0) ?: $this->getPost('reservation_id', 0));
        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->getReservationDocuments($reservationId);
        $this->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX endpoint to update reservation documents tracking.
     */
    public function updateDocuments(): void
    {
        $userSession = Middleware::requireRole(['admin', 'unit owner']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        $docsRaw = $this->getPost('documents');
        $documents = is_array($docsRaw) ? $docsRaw : json_decode((string) $docsRaw, true);

        if ($reservationId <= 0 || !is_array($documents)) {
            $this->json(['success' => false, 'message' => 'Invalid request data.'], 400);
            return;
        }

        $result = $this->reservationModel->updateReservationDocuments(
            $reservationId,
            $documents,
            (int) $userSession['user_id'],
            (string) $userSession['role']
        );

        $this->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX endpoint to mark reservation as officially booked.
     */
    public function markOfficiallyBooked(): void
    {
        $userSession = Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->markOfficiallyBooked(
            $reservationId,
            (int) $userSession['user_id'],
            'admin'
        );

        $this->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX endpoint to cancel a reservation.
     */
    public function cancelReservation(): void
    {
        $userSession = Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        $reservationId = (int) $this->getPost('reservation_id', 0);
        $remarks = trim((string) $this->getPost('remarks', ''));

        if ($reservationId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
            return;
        }

        $result = $this->reservationModel->cancelReservation(
            $reservationId,
            $remarks,
            (int) $userSession['user_id'],
            'admin'
        );

        $this->json($result, $result['success'] ? 200 : 422);
    }

}
