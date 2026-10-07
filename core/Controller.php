<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Base Controller
 * All controllers inherit request handling, view rendering, and JSON formatting from this class.
 * NO raw SQL or database queries are permitted in controllers.
 */
abstract class Controller {

    /**
     * Render a view file with extracted data and an optional layout wrapper.
     *
     * @param string $viewPath Relative path within views/ (e.g., 'auth/login' or 'admin/dashboard')
     * @param array $data Associative array of data variables to pass to the view
     * @param string|null $layout Layout name within views/layouts/ (e.g., 'main', 'admin', 'owner', 'tenant'), or null for no layout
     */
    protected function render(string $viewPath, array $data = [], ?string $layout = null): void {
        // Extract variables into local scope for the view
        extract($data, EXTR_SKIP);

        $viewsBaseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $viewFile = $viewsBaseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        // Capture view output
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // If a layout is specified, wrap the view output in that layout
        if ($layout !== null) {
            $layoutFile = $viewsBaseDir . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("Layout file not found: {$layoutFile}");
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Send a JSON response with appropriate headers and HTTP status code.
     */
    protected function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirect the user to a different URL or application route.
     */
    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    /**
     * Check if current request method is POST.
     */
    protected function isPost(): bool {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Check if current request method is GET.
     */
    protected function isGet(): bool {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }

    /**
     * Check if request was sent via AJAX / fetch.
     */
    protected function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }

    /**
     * Retrieve sanitized POST input parameter.
     */
    protected function getPost(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Retrieve sanitized GET query parameter.
     */
    protected function getQuery(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Set a flash message in the session.
     */
    protected function setFlash(string $key, mixed $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Retrieve and clear a flash message from the session.
     */
    protected function getFlash(string $key, mixed $default = null): mixed {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return $default;
    }
}
