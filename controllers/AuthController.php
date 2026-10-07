<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Zeppelin Suites - Authentication Controller
 * Handles login presentations, credential verification, session lifecycle, and role dispatching.
 * Zero direct SQL or presentation markup permitted.
 */
class AuthController extends Controller {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Display the login interface.
     */
    public function showLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If user is already authenticated, redirect to their role-specific dashboard
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
            $dashboardUrl = $this->getDashboardUrl((string)$_SESSION['role']);
            $this->redirect($dashboardUrl);
            return;
        }

        // Check for error flash message or error GET parameter
        $errorMessage = $this->getFlash('error_message');
        if (!$errorMessage && isset($_GET['error'])) {
            if ($_GET['error'] === 'no_user') {
                $errorMessage = 'No account found with this email.';
            } elseif ($_GET['error'] === 'wrong_password') {
                $errorMessage = 'Incorrect password. Try again.';
            }
        }

        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        $this->render('auth/login', [
            'pageTitle'    => 'Zeppelin Suites - Login',
            'errorMessage' => $errorMessage,
            'actionUrl'    => "{$baseUrl}/login",
            'baseUrl'      => $baseUrl,
        ], 'auth');
    }

    /**
     * Handle login form submission.
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
        $loginUrl = "{$baseUrl}/login";

        if (!$this->isPost()) {
            $this->setFlash('error_message', 'Invalid request method.');
            $this->redirect($loginUrl);
            return;
        }

        $email = filter_var(trim((string)$this->getPost('email', '')), FILTER_SANITIZE_EMAIL);
        $password = (string)$this->getPost('password', '');

        if (empty($email) || empty($password)) {
            $errorMsg = 'Please enter both email and password.';
            if ($this->isAjax()) {
                $this->json(['status' => 'error', 'message' => $errorMsg], 400);
            }
            $this->setFlash('error_message', $errorMsg);
            $this->redirect($loginUrl);
            return;
        }

        // Delegate credential verification to the User Model
        $result = $this->userModel->authenticate($email, $password);

        if (!$result['success']) {
            if ($this->isAjax()) {
                $this->json(['status' => 'error', 'message' => $result['error']], 401);
            }
            $this->setFlash('error_message', $result['error']);
            $this->redirect($loginUrl);
            return;
        }

        $user = $result['user'];

        // Securely initialize session
        session_regenerate_id(true);

        $role = Middleware::normalizeRole((string)($user['user_role'] ?? ''));
        $displayInfo = $this->userModel->getUserDisplayInfo((int)$user['user_id'], $role);

        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['role']      = $role;
        $_SESSION['full_name'] = $displayInfo['full_name'];
        $_SESSION['initial']   = $displayInfo['initial'];

        // Determine destination dashboard
        $destination = $this->getDashboardUrl($role);

        if ($this->isAjax()) {
            $this->json([
                'status'   => 'success',
                'redirect' => $destination,
                'user'     => [
                    'id'        => $_SESSION['user_id'],
                    'role'      => $_SESSION['role'],
                    'full_name' => $_SESSION['full_name'],
                ],
            ]);
        }

        $this->redirect($destination);
    }

    /**
     * Handle user logout and session destruction.
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session storage
        session_destroy();

        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
        $this->redirect("{$baseUrl}/login");
    }

    /**
     * Helper to compute dashboard URL for a user role.
     */
    private function getDashboardUrl(string $role): string {
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');

        // During incremental migration, link to existing portal paths
        switch (Middleware::normalizeRole($role)) {
            case 'admin':
                return "{$baseUrl}/adminPages/homeAdmin.php";
            case 'unit owner':
                return "{$baseUrl}/unitOwnerPages/overview.php";
            case 'tenant':
                return "{$baseUrl}/tenantPages/homeTenant.php";
            default:
                return "{$baseUrl}/login";
        }
    }
}
