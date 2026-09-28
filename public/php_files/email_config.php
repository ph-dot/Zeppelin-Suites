<?php
/**
 * Email Configuration Module (TASK-006)
 *
 * Credentials are loaded from an untracked local file (email_config.local.php)
 * or environment variables. Version control only stores dummy fallbacks.
 */

$localConfigFile = __DIR__ . '/email_config.local.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];
if (!is_array($localConfig)) {
    $localConfig = [];
}

// SMTP Server Host
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: ($localConfig['SMTP_HOST'] ?? 'smtp.gmail.com'));
}

// SMTP Authentication Username / Email
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: ($localConfig['SMTP_USERNAME'] ?? 'your_email@gmail.com'));
}

// SMTP Authentication Password / App Password
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: ($localConfig['SMTP_PASSWORD'] ?? 'your_smtp_app_password'));
}

// SMTP Port (587 TLS / 465 SSL)
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: ($localConfig['SMTP_PORT'] ?? 587)));
}

// Sender Email Address
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: ($localConfig['MAIL_FROM_EMAIL'] ?? 'your_email@gmail.com'));
}

// Sender Display Name
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: ($localConfig['MAIL_FROM_NAME'] ?? 'Zeppelin Suites'));
}

// Used in owner notification emails as the link to log in and respond.
if (!defined('OWNER_PORTAL_LOGIN_URL')) {
    define('OWNER_PORTAL_LOGIN_URL', getenv('OWNER_PORTAL_LOGIN_URL') ?: ($localConfig['OWNER_PORTAL_LOGIN_URL'] ?? 'http://localhost/Zeppelin-Suites/public/generalViewPages/login.php'));
}
?>