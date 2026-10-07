<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/BookingCalendar.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Booking Calendar Controller
 * Handles calendar timeline views, unit schedules, and date blockings.
 * Pure MVC: strictly NO SQL queries in this controller.
 */
class BookingCalendarController extends Controller {
    private BookingCalendar $calendarModel;

    public function __construct() {
        $this->calendarModel = new BookingCalendar();
    }

    /**
     * Display the visual booking calendar timeline page.
     */
    public function index(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $analyticsModel = new Analytics();
        $pendingCounts = $analyticsModel->getPendingCounts();

        $this->render('admin/booking_calendar', [
            'pageTitle'           => 'Zeppelin Suites - Booking Calendar',
            'activeTab'           => 'calendar',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * AJAX JSON endpoint delivering units, active bookings, and blocked dates.
     */
    public function getData(): void {
        Middleware::requireRole(['admin']);

        try {
            $data = $this->calendarModel->getCalendarData();
            $this->json(array_merge(['success' => true], $data), 200);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX POST endpoint to block unit dates.
     */
    public function saveBlockedDate(): void {
        $userSession = Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
            return;
        }

        $unitId = (int)$this->getPost('unit_id', 0);
        $startDate = (string)$this->getPost('start_date', '');
        $endDate = (string)$this->getPost('end_date', '');
        $blockType = (string)$this->getPost('block_type', 'Not Available');
        $remarks = (string)$this->getPost('remarks', '');
        $userId = (int)($userSession['user_id'] ?? 0);

        $result = $this->calendarModel->saveBlockedDate(
            $unitId,
            $startDate,
            $endDate,
            $blockType,
            $remarks,
            $userId,
            'admin'
        );

        $status = $result['success'] ? 200 : 422;
        $this->json($result, $status);
    }

    /**
     * AJAX POST endpoint to unblock unit dates.
     */
    public function deleteBlockedDate(): void {
        Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
            return;
        }

        $blockId = (int)$this->getPost('block_id', 0);
        $result = $this->calendarModel->deleteBlockedDate($blockId);

        $status = $result['success'] ? 200 : 422;
        $this->json($result, $status);
    }
}
