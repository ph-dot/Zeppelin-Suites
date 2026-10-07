<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Bridge
 * Permanently redirects legacy unitDetails.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$target = $id > 0 ? "{$baseUrl}/admin/units/view?id={$id}" : "{$baseUrl}/admin/units";

header('HTTP/1.1 301 Moved Permanently');
header("Location: {$target}");
exit;
