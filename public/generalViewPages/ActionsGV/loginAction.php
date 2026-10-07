<?php
/**
 * Legacy Login Action Bridge
 * Delegates legacy form POST submissions directly to the MVC AuthController.
 */
require_once dirname(__DIR__, 3) . '/config/env.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/core/Model.php';
require_once dirname(__DIR__, 3) . '/core/Controller.php';
require_once dirname(__DIR__, 3) . '/core/Middleware.php';
require_once dirname(__DIR__, 3) . '/models/User.php';
require_once dirname(__DIR__, 3) . '/controllers/AuthController.php';

$authController = new AuthController();
$authController->login();