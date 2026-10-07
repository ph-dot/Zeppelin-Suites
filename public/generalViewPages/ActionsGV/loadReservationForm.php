<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Action Bridge
 * Redirects legacy loadReservationForm.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 3) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('HTTP/1.1 301 Moved Permanently');
header("Location: {$baseUrl}/reservation{$qs}");
exit;