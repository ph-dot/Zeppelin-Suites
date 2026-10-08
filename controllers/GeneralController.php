<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Inquiry.php';
require_once __DIR__ . '/../models/Reservation.php';

/**
 * Zeppelin Suites - General / Public Controller
 * Handles visitor presentations, unit catalogs, contact/inquiry submission,
 * and client reservation workflows.
 * Pure MVC: Zero SQL in controller, all queries handled by Inquiry and Reservation models.
 */
class GeneralController extends Controller {
    private Inquiry $inquiryModel;
    private Reservation $reservationModel;

    public function __construct() {
        $this->inquiryModel = new Inquiry();
        $this->reservationModel = new Reservation();
    }

    /**
     * Public landing / home page.
     */
    public function index(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/home', [
            'pageTitle'  => 'Zeppelin Suites — Luxury Living in Angeles City',
            'baseUrl'    => $baseUrl,
            'activePage' => 'home',
        ]);
    }

    /**
     * About Us page.
     */
    public function about(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/about', [
            'pageTitle'  => 'Zeppelin Suites — About Us',
            'baseUrl'    => $baseUrl,
            'activePage' => 'about',
        ]);
    }

    /**
     * FAQ page.
     */
    public function faq(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/faq', [
            'pageTitle'  => 'Zeppelin Suites — Frequently Asked Questions',
            'baseUrl'    => $baseUrl,
            'activePage' => 'faq',
        ]);
    }

    /**
     * Virtual 360 Tour page.
     */
    public function tour(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/tour', [
            'pageTitle'  => 'Zeppelin Suites — Virtual 360 Tour',
            'baseUrl'    => $baseUrl,
            'activePage' => 'tour',
        ]);
    }

    /**
     * Studio Type A unit details page.
     */
    public function studioTypeA(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/studio_type_a', [
            'pageTitle'  => 'Zeppelin Suites — Studio Type A',
            'baseUrl'    => $baseUrl,
            'activePage' => 'studio_a',
        ]);
    }

    /**
     * Studio Type B unit details page.
     */
    public function studioTypeB(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/studio_type_b', [
            'pageTitle'  => 'Zeppelin Suites — Studio Type B',
            'baseUrl'    => $baseUrl,
            'activePage' => 'studio_b',
        ]);
    }

    /**
     * One Bedroom unit details page.
     */
    public function oneBedroom(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/one_bedroom', [
            'pageTitle'  => 'Zeppelin Suites — One Bedroom Suite',
            'baseUrl'    => $baseUrl,
            'activePage' => 'one_bed',
        ]);
    }

    /**
     * Two Bedroom unit details page.
     */
    public function twoBedroom(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/two_bedroom', [
            'pageTitle'  => 'Zeppelin Suites — Two Bedroom Suite',
            'baseUrl'    => $baseUrl,
            'activePage' => 'two_bed',
        ]);
    }

    /**
     * Privacy Policy page.
     */
    public function privacyPolicy(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/privacy_policy', [
            'pageTitle'  => 'Zeppelin Suites — Privacy Policy',
            'baseUrl'    => $baseUrl,
            'activePage' => 'privacy',
        ]);
    }

    /**
     * Terms of Service page.
     */
    public function termsOfService(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/terms_of_service', [
            'pageTitle'  => 'Zeppelin Suites — Terms of Service',
            'baseUrl'    => $baseUrl,
            'activePage' => 'terms',
        ]);
    }

    /**
     * Contact / Inquiry submission page.
     */
    public function contact(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $errorMessage = $this->getFlash('error_message');

        $this->render('general/contact', [
            'pageTitle'    => 'Zeppelin Suites — Contact & Inquiries',
            'baseUrl'      => $baseUrl,
            'activePage'   => 'contact',
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Handle public inquiry form POST submission.
     */
    public function submitInquiry(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/contact");
            return;
        }

        $result = $this->inquiryModel->createPublicInquiry($_POST);
        if ($result['success']) {
            $this->redirect("{$baseUrl}/inquiry-confirmation");
            return;
        }

        $this->setFlash('error_message', $result['error'] ?? 'Submission failed.');
        $this->redirect("{$baseUrl}/contact");
    }

    /**
     * Inquiry submission confirmation page.
     */
    public function inquiryConfirmation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/inquiry_confirmation', [
            'pageTitle'  => 'Inquiry Received — Zeppelin Suites',
            'baseUrl'    => $baseUrl,
            'activePage' => 'contact',
        ]);
    }

    /**
     * Client Condominium Reservation Form (loaded by secure token from inquiry email).
     */
    public function reservationForm(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $token = (string)$this->getQuery('token', '');

        if ($token === '') {
            $this->renderError('Invalid Reservation Link', 'The reservation link you followed is missing a valid token.', 400);
            return;
        }

        $res = $this->reservationModel->getReservationFormData($token);
        if ($res['status'] === 'already_submitted') {
            $this->redirect("{$baseUrl}/reservation-confirmation?token=" . urlencode($token));
            return;
        }

        if ($res['status'] !== 'ok') {
            $this->renderError('Unable to Load Reservation', $res['message'] ?? 'This reservation link is invalid or expired.', 404);
            return;
        }

        $isLease = (bool)($res['is_lease'] ?? true);
        $pageTitle = $isLease ? 'Zeppelin Suites — Unit Lease Reservation' : 'Zeppelin Suites — Unit Resale Reservation';

        $this->render('general/reservation_form', array_merge($res, [
            'pageTitle'  => $pageTitle,
            'baseUrl'    => $baseUrl,
            'activePage' => 'reservation',
        ]));
    }

    /**
     * Handle client reservation form POST submission.
     */
    public function submitReservation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/");
            return;
        }

        $res = $this->reservationModel->submitPublicReservation($_POST, $_FILES);
        if ($res['success']) {
            $token = $res['token'];
            $this->redirect("{$baseUrl}/reservation-confirmation?token=" . urlencode($token));
            return;
        }

        $this->renderError('Reservation Submission Failed', $res['error'] ?? 'There was an issue processing your reservation.', 422);
    }

    /**
     * Reservation submission confirmation page.
     */
    public function reservationConfirmation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $token = (string)$this->getQuery('token', '');

        $this->render('general/reservation_confirmation', [
            'pageTitle'  => 'Reservation Submitted — Zeppelin Suites',
            'baseUrl'    => $baseUrl,
            'token'      => $token,
            'activePage' => 'reservation',
        ]);
    }

    /**
     * Client cancellation request page (loaded by cancellation token from email).
     */
    public function cancelReservation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $token = (string)$this->getQuery('token', '');

        if ($token === '') {
            $this->renderError('Invalid Cancellation Link', 'The cancellation link is missing a valid token.', 400);
            return;
        }

        $res = $this->reservationModel->getReservationForClientCancellation($token);
        if ($res['status'] !== 'ok') {
            $this->renderError('Cancellation Unavailable', $res['message'] ?? 'This cancellation link is invalid or expired.', 404);
            return;
        }

        $this->render('general/cancel_reservation', [
            'pageTitle'   => 'Cancel Reservation — Zeppelin Suites',
            'baseUrl'     => $baseUrl,
            'reservation' => $res['reservation'],
            'token'       => $token,
            'activePage'  => 'reservation',
        ]);
    }

    /**
     * Handle client cancellation request POST submission.
     */
    public function submitCancellation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        if (!$this->isPost()) {
            $this->redirect("{$baseUrl}/");
            return;
        }

        $token = trim((string)$this->getPost('token', ''));
        $reason = trim((string)$this->getPost('reason', ''));

        $res = $this->reservationModel->submitClientCancellationRequest($token, $reason);
        if ($res['success']) {
            $this->redirect("{$baseUrl}/cancellation-confirmation");
            return;
        }

        $this->renderError('Unable to Submit Cancellation', $res['error'] ?? 'An error occurred submitting your cancellation request.', 422);
    }

    /**
     * Cancellation confirmation page.
     */
    public function cancellationConfirmation(): void {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/cancellation_confirmation', [
            'pageTitle'  => 'Cancellation Request Submitted — Zeppelin Suites',
            'baseUrl'    => $baseUrl,
            'activePage' => 'reservation',
        ]);
    }

    /**
     * Email domain validation endpoint for forms.
     */
    public function checkEmailDomain(): void {
        $email = trim((string)$this->getQuery('email', ''));

        if ($email === '') {
            $this->json(['valid' => false, 'message' => 'Email address is required.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['valid' => false, 'message' => 'Please enter a valid email address format.']);
            return;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            $this->json(['valid' => false, 'message' => 'Please enter a valid email address format.']);
            return;
        }

        $domain = strtolower($parts[1]);
        $typoMap = [
            'gmaidla.com' => 'gmail.com',
            'gmaild.com'  => 'gmail.com',
            'gamil.com'   => 'gmail.com',
            'gmial.com'   => 'gmail.com',
            'gmaill.com'  => 'gmail.com',
            'gmai.com'    => 'gmail.com',
            'gmal.com'    => 'gmail.com',
            'gmeil.com'   => 'gmail.com',
            'gmaio.com'   => 'gmail.com',
            'gmail.co'    => 'gmail.com',
            'gmaill.co'   => 'gmail.com',
            'yaho.com'    => 'yahoo.com',
            'yahooo.com'  => 'yahoo.com',
            'yaho.co'     => 'yahoo.com',
            'ymail.co'    => 'yahoo.com',
            'outlok.com'  => 'outlook.com',
            'outloo.com'  => 'outlook.com',
            'hotmial.com' => 'hotmail.com',
            'hotmai.com'  => 'hotmail.com',
            'iclou.com'   => 'icloud.com',
            'icld.com'    => 'icloud.com',
        ];

        if (isset($typoMap[$domain])) {
            $this->json([
                'valid'   => false,
                'message' => 'Please enter a valid email domain provider (e.g. name@gmail.com).'
            ]);
            return;
        }

        $majorDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
        foreach ($majorDomains as $major) {
            if ($domain !== $major && levenshtein($domain, $major) <= 2) {
                $this->json([
                    'valid'      => false,
                    'suggestion' => $major,
                    'message'    => "Did you mean @{$major}?"
                ]);
                return;
            }
        }

        $this->json(['valid' => true]);
    }

    /**
     * Render a clean public user-friendly error page.
     */
    private function renderError(string $title, string $message, int $statusCode = 400): void {
        http_response_code($statusCode);
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
        $this->render('general/error', [
            'pageTitle'    => "Error: {$title} — Zeppelin Suites",
            'errorTitle'   => $title,
            'errorMessage' => $message,
            'baseUrl'      => $baseUrl,
            'activePage'   => '',
        ]);
    }
}
