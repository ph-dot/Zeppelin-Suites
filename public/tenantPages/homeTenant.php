<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Bridge
 * Permanently redirects legacy homeTenant.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$target = "{$baseUrl}/tenant/home";

header('HTTP/1.1 301 Moved Permanently');
header("Location: {$target}");
exit;
