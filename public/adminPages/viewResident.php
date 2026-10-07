<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Bridge
 * Permanently redirects legacy viewResident.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
$target = $id > 0 ? "{$baseUrl}/admin/residents/view?id={$id}" : "{$baseUrl}/admin/residents";

header('HTTP/1.1 301 Moved Permanently');
header("Location: {$target}");
exit;
