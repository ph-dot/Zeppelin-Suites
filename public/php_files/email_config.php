<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'maddysilvano@gmail.com');
define('SMTP_PASSWORD', 'hmzcljytxhwcqubl');
define('SMTP_PORT', 587);

define('MAIL_FROM_EMAIL', 'maddysilvano@gmail.com');
define('MAIL_FROM_NAME', 'Zeppelin Suites email sample');

// Used in owner notification emails as the link to log in and respond.
// NOTE: update this to your real domain when deploying - matches the
// same localhost pattern already used in sendInquiryReply.php.
define('OWNER_PORTAL_LOGIN_URL', 'http://localhost/Zeppelin-Suites/public/generalViewPages/login.php');
?>