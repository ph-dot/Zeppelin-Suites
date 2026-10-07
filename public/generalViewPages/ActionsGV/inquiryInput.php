<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Legacy Action Bridge
 * Delegates legacy inquiry POST submissions directly to GeneralController.
 */
require_once dirname(__DIR__, 3) . '/config/env.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/core/Model.php';
require_once dirname(__DIR__, 3) . '/core/Controller.php';
require_once dirname(__DIR__, 3) . '/models/Inquiry.php';
require_once dirname(__DIR__, 3) . '/models/Reservation.php';
require_once dirname(__DIR__, 3) . '/controllers/GeneralController.php';

$generalController = new GeneralController();
$generalController->submitInquiry();