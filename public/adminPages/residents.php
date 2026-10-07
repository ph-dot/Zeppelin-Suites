<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Deprecation Bridge
 * Direct file execution has been refactored into Pure MVC.
 * Seamlessly forwards to the central MVC route: /admin/residents
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

header("Location: {$baseUrl}/admin/residents{$queryString}", true, 301);
exit;