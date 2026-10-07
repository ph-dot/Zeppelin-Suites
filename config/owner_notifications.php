<?php
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Send a plain-text notification email to a unit owner.
 *
 * @return bool true if the email was sent, false otherwise (check error_log for details)
 */
function sendOwnerNotificationEmail(string $ownerEmail, string $ownerName, string $subject, string $body): bool
{
    if (trim($ownerEmail) === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("sendOwnerNotificationEmail: skipped, invalid/missing owner email ('{$ownerEmail}')");
        return false;
    }

    $username = defined('SMTP_USERNAME') ? SMTP_USERNAME : (string)env('SMTP_USERNAME', '');
    $password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : (string)env('SMTP_PASSWORD', '');

    // If SMTP credentials are not configured in local environment, log simulation and succeed
    if (empty($username) || empty($password)) {
        error_log("[Zeppelin Suites Dev Mail] Simulating owner notification to {$ownerEmail} ({$ownerName}) with subject '{$subject}'. SMTP credentials not set in .env.");
        return true;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = (SMTP_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($ownerEmail, $ownerName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        error_log("sendOwnerNotificationEmail failed for {$ownerEmail}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notify a unit owner that a new reservation request (from an admin
 * approving/forwarding an inquiry) is waiting on them.
 */
function notifyOwnerOfApprovalRequest(string $ownerEmail, string $ownerName, string $unitNumber): bool
{
    $subject = "Zeppelin Suites - New Reservation Request for Unit {$unitNumber}";

    $body = "Hi {$ownerName},\n\n"
        . "A prospective tenant has inquired about your unit ({$unitNumber}), and our admin team would like your approval to proceed with a reservation.\n\n"
        . "Please log in to your unit owner portal to review and respond (approve or decline) at your earliest convenience:\n"
        . OWNER_PORTAL_LOGIN_URL . "\n\n"
        . "If another owner's unit is approved first, this request will automatically close.\n\n"
        . "Thank you,\nZeppelin Suites";

    return sendOwnerNotificationEmail($ownerEmail, $ownerName, $subject, $body);
}

/**
 * Notify a unit owner that their unit has just been reserved by a tenant/buyer.
 */
function notifyOwnerOfNewReservation(string $ownerEmail, string $ownerName, string $unitNumber, string $clientName, string $moveInDate): bool
{
    $subject = "Zeppelin Suites - Unit {$unitNumber} Has Been Reserved";

    $body = "Hi {$ownerName},\n\n"
        . "Your unit ({$unitNumber}) has just been reserved by {$clientName}.\n"
        . "Requested move-in / appointment date: {$moveInDate}\n\n"
        . "The payment proof has been submitted and is pending review by our admin team. "
        . "You can check the reservation details anytime in your unit owner portal:\n"
        . OWNER_PORTAL_LOGIN_URL . "\n\n"
        . "Thank you,\nZeppelin Suites";

    return sendOwnerNotificationEmail($ownerEmail, $ownerName, $subject, $body);
}

/**
 * Notify a unit owner that the admin left feedback/an update on a
 * maintenance request they submitted.
 */
function notifyOwnerOfMaintenanceFeedback(string $ownerEmail, string $ownerName, string $subjectLine, string $status, string $adminRemarks): bool
{
    $subject = "Zeppelin Suites - Update on Your Maintenance Request";

    $body = "Hi {$ownerName},\n\n"
        . "There's an update on your maintenance request \"{$subjectLine}\".\n\n"
        . "Status: " . ucwords($status) . "\n"
        . ($adminRemarks !== '' ? "Admin remarks:\n{$adminRemarks}\n\n" : "\n")
        . "You can view the full details in your unit owner portal:\n"
        . OWNER_PORTAL_LOGIN_URL . "\n\n"
        . "Thank you,\nZeppelin Suites";

    return sendOwnerNotificationEmail($ownerEmail, $ownerName, $subject, $body);
}
