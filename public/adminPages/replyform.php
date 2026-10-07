<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Script Bridge
 * Permanently redirects legacy replyform.php requests to the MVC clean route.
 */
require_once dirname(__DIR__, 2) . '/config/env.php';

$baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$inqId = isset($_GET['inq_id']) ? (int)$_GET['inq_id'] : 0;
$target = $inqId > 0 ? "{$baseUrl}/admin/inquiries/reply?inq_id={$inqId}" : "{$baseUrl}/admin/inquiries";

header('HTTP/1.1 301 Moved Permanently');
header("Location: {$target}");
exit;