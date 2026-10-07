<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Centralized MVC Router
 * Maps incoming HTTP requests to corresponding Controller actions with parameter extraction and middleware guards.
 */
class Router {
    private array $routes = [];
    private string $basePath = '';

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, array|callable $handler, array $middlewares = []): self {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, array|callable $handler, array $middlewares = []): self {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Register a route responding to any HTTP method.
     */
    public function any(string $path, array|callable $handler, array $middlewares = []): self {
        return $this->addRoute('ANY', $path, $handler, $middlewares);
    }

    /**
     * Internal helper to register routes.
     */
    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): self {
        $cleanPath = '/' . trim($path, '/');
        if ($cleanPath === '//') {
            $cleanPath = '/';
        }

        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => $cleanPath,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];

        return $this;
    }

    /**
     * Resolve and dispatch the current request.
     */
    public function dispatch(?string $requestUri = null, ?string $requestMethod = null): void {
        $method = strtoupper($requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');

        // Extract path component, discarding query string
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Check if route was supplied via explicit query parameter fallback (e.g. ?route=/login or ?r=/login)
        if (isset($_GET['route']) && trim($_GET['route']) !== '') {
            $path = '/' . trim($_GET['route'], '/');
        } elseif (isset($_GET['r']) && trim($_GET['r']) !== '') {
            $path = '/' . trim($_GET['r'], '/');
        } else {
            // Strip basePath (e.g. /Zeppelin-Suites/public) from path if present
            if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
                $path = substr($path, strlen($this->basePath));
            }

            // Also strip script filename if directly accessed (e.g. /index.php/login)
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
                $path = substr($path, strlen($scriptName));
            } elseif ($scriptName !== '' && str_starts_with($path, dirname($scriptName))) {
                $subDir = rtrim(dirname($scriptName), '/\\');
                if ($subDir !== '' && str_starts_with($path, $subDir)) {
                    $path = substr($path, strlen($subDir));
                }
            }
        }

        $cleanPath = '/' . trim($path, '/');
        if ($cleanPath === '//' || $cleanPath === '') {
            $cleanPath = '/';
        }

        // Match against registered routes
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            $matches = $this->matchPath($route['path'], $cleanPath);
            if ($matches !== null) {
                // Execute route middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        $middleware();
                    } elseif (is_string($middleware)) {
                        // Check if role name or middleware method
                        if (in_array(strtolower($middleware), ['admin', 'unit owner', 'tenant'], true)) {
                            Middleware::requireRole([$middleware]);
                        } elseif ($middleware === 'guest') {
                            Middleware::requireGuest();
                        } elseif (method_exists(Middleware::class, $middleware)) {
                            Middleware::$middleware();
                        }
                    } elseif (is_array($middleware)) {
                        Middleware::requireRole($middleware);
                    }
                }

                // Invoke route handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func_array($handler, $matches);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $actionMethod] = $handler;

                    if (!class_exists($controllerClass)) {
                        throw new RuntimeException("Controller class {$controllerClass} does not exist.");
                    }

                    $controllerInstance = new $controllerClass();

                    if (!method_exists($controllerInstance, $actionMethod)) {
                        throw new RuntimeException("Action method {$actionMethod} not found in {$controllerClass}.");
                    }

                    call_user_func_array([$controllerInstance, $actionMethod], $matches);
                    return;
                }
            }
        }

        // No route matched: 404 Not Found
        $this->handleNotFound($cleanPath);
    }

    /**
     * Check if route pattern matches request path and extract route parameters.
     */
    private function matchPath(string $routePattern, string $requestPath): ?array {
        // Convert route pattern with {param} into regex
        $patternRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
        $patternRegex = '#^' . $patternRegex . '$#';

        if (preg_match($patternRegex, $requestPath, $matches)) {
            // Keep only named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    /**
     * Render or output 404 response.
     */
    private function handleNotFound(string $path): void {
        http_response_code(404);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => "Route '{$path}' not found.",
            ]);
            exit;
        }

        $viewsBaseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $notFoundView = $viewsBaseDir . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';

        if (file_exists($notFoundView)) {
            include $notFoundView;
            exit;
        }

        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>404 Not Found</title>";
        echo "<script src='https://cdn.tailwindcss.com'></script></head>";
        echo "<body class='h-screen flex items-center justify-center bg-slate-50 text-slate-800 font-sans'>";
        echo "<div class='text-center p-8 bg-white rounded-2xl shadow-sm border border-slate-100 max-w-md w-full'>";
        echo "<h1 class='text-4xl font-bold text-slate-900 mb-2'>404</h1>";
        echo "<p class='text-slate-600 mb-6'>Page not found: <code class='bg-slate-100 px-2 py-1 rounded text-sm text-slate-800'>" . htmlspecialchars($path) . "</code></p>";
        echo "<a href='" . htmlspecialchars(rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/')) . "/login' class='inline-block px-5 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors'>Return to Login</a>";
        echo "</div></body></html>";
        exit;
    }
}
