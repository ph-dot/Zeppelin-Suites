<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Inquiry.php';
require_once __DIR__ . '/../models/Analytics.php';

/**
 * Zeppelin Suites - Inquiry Controller
 * Manages customer inquiries, follow-ups, and email reply workflow.
 */
class InquiryController extends Controller {
    private Inquiry $inquiryModel;
    private Analytics $analyticsModel;

    public function __construct() {
        $this->inquiryModel = new Inquiry();
        $this->analyticsModel = new Analytics();
    }

    /**
     * Display main inquiries list.
     */
    public function index(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $inquiries = $this->inquiryModel->getAllInquiries();
        $stats = $this->inquiryModel->getInquiryStats();
        $pendingCounts = $this->analyticsModel->getPendingCounts();

        $this->render('admin/inquiries', [
            'pageTitle'           => 'Zeppelin Suites - Inquiries',
            'activeTab'           => 'inquiry',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'inquiries'           => $inquiries,
            'stats'               => $stats,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * Update inquiry status via AJAX POST.
     */
    public function updateStatus(): void {
        Middleware::requireRole(['admin']);

        $inqId = (int)$this->getPost('inq_id', 0);
        $newStatus = (string)$this->getPost('new_status', 'pending');

        if ($inqId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid inquiry ID.'], 400);
            return;
        }

        $ok = $this->inquiryModel->updateStatus($inqId, $newStatus);
        $this->json(['success' => $ok]);
    }

    /**
     * Display inquiry reply composition form.
     */
    public function replyForm(): void {
        $userSession = Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $inqId = (int)$this->getQuery('inq_id', 0);
        if ($inqId <= 0) {
            $this->redirect("{$baseUrl}/admin/inquiries");
            return;
        }

        $inquiry = $this->inquiryModel->getById($inqId);
        if (!$inquiry) {
            $_SESSION['error_message'] = 'Inquiry not found.';
            $this->redirect("{$baseUrl}/admin/inquiries");
            return;
        }

        $pendingCounts = $this->analyticsModel->getPendingCounts();

        $this->render('admin/reply_form', [
            'pageTitle'           => 'Zeppelin Suites - Reply to Inquiry',
            'activeTab'           => 'inquiry',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'inquiry'             => $inquiry,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * Handle inquiry email reply submission.
     */
    public function sendReply(): void {
        Middleware::requireRole(['admin']);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $inqId = (int)$this->getPost('inq_id', 0);
        $replyTo = trim((string)$this->getPost('reply_to', ''));
        $subject = trim((string)$this->getPost('reply_subject', ''));
        $emailBody = trim((string)$this->getPost('email_body', ''));

        if ($inqId <= 0 || $replyTo === '' || $subject === '' || $emailBody === '') {
            $_SESSION['error_message'] = 'Please complete all email fields.';
            $this->redirect("{$baseUrl}/admin/inquiries/reply?inq_id={$inqId}");
            return;
        }

        if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = 'Invalid recipient email address.';
            $this->redirect("{$baseUrl}/admin/inquiries/reply?inq_id={$inqId}");
            return;
        }

        $result = $this->inquiryModel->sendReply($inqId, $replyTo, $subject, $emailBody);

        if ($result['success']) {
            $_SESSION['success_message'] = 'Reply email sent successfully. Inquiry status updated to Responded.';
        } else {
            $_SESSION['error_message'] = 'Failed to send email: ' . ($result['error'] ?? 'Unknown error');
        }

        $this->redirect("{$baseUrl}/admin/inquiries/reply?inq_id={$inqId}");
    }
}
