<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Bridge
 * Permanently redirects legacy ownersUnit.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$target = "{$baseUrl}/owner/units";
$qs = $_SERVER['QUERY_STRING'] ?? '';
if ($qs !== '') {
    $target .= '?' . $qs;
}

header('HTTP/1.1 301 Moved Permanently');
header("Location: {$target}");
exit;