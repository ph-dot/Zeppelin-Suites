<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/email_config.php';

require_once __DIR__ . '/../../phpmailer/src/Exception.php';
require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../inquiry.php");
    exit();
}

$inq_id = isset($_POST['inq_id']) ? (int)$_POST['inq_id'] : 0;
$reply_to = trim($_POST['reply_to'] ?? '');
$reply_subject = trim($_POST['reply_subject'] ?? '');
$email_body = trim($_POST['email_body'] ?? '');

if ($inq_id <= 0 || empty($reply_to) || empty($reply_subject) || empty($email_body)) {
    $_SESSION['error_message'] = "Please complete all email fields.";
    header("Location: ../replyform.php?inq_id=" . $inq_id);
    exit();
}

if (!filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Invalid recipient email.";
    header("Location: ../replyform.php?inq_id=" . $inq_id);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get inquiry first
|--------------------------------------------------------------------------
| This is needed because your old code uses $inquiry but never defined it.
*/

$inquirySql = "
    SELECT 
        inq_id,
        sender_email,
        status,
        approval_status,
        reservation_token
    FROM inquiry_table
    WHERE inq_id = ?
    LIMIT 1
";

$inquiryStmt = $conn->prepare($inquirySql);

if (!$inquiryStmt) {
    $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
    header("Location: ../replyform.php?inq_id=" . $inq_id);
    exit();
}

$inquiryStmt->bind_param("i", $inq_id);
$inquiryStmt->execute();
$inquiryResult = $inquiryStmt->get_result();
$inquiry = $inquiryResult->fetch_assoc();
$inquiryStmt->close();

if (!$inquiry) {
    $_SESSION['error_message'] = "Inquiry not found.";
    header("Location: ../inquiry.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Optional: add reservation link if approved
|--------------------------------------------------------------------------
| Only add it if it is not already inside the email body.
*/

if (
    strtolower($inquiry['approval_status'] ?? '') === 'approved' &&
    !empty($inquiry['reservation_token'])
) {
    $reservation_link = "http://localhost/Zeppelin-Suites/public/generalViewPages/reservationform.php?token=" . urlencode($inquiry['reservation_token']);

    if (strpos($email_body, $reservation_link) === false) {
        $email_body .= "\n\nReservation Form Link:\n" . $reservation_link;
    }
}

/*
|--------------------------------------------------------------------------
| Send email
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($reply_to);

    $mail->isHTML(false);
    $mail->Subject = $reply_subject;
    $mail->Body = $email_body;

    $mail->send();

    /*
    |--------------------------------------------------------------------------
    | Update inquiry status after successful email
    |--------------------------------------------------------------------------
    */

    $updateSql = "
        UPDATE inquiry_table
        SET status = 'responded',
            reservation_link_sent_at = CASE
                WHEN approval_status = 'approved' AND reservation_token IS NOT NULL
                THEN NOW()
                ELSE reservation_link_sent_at
            END
        WHERE inq_id = ?
    ";

    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {
        throw new Exception("Status update prepare failed: " . $conn->error);
    }

    $updateStmt->bind_param("i", $inq_id);

    if (!$updateStmt->execute()) {
        throw new Exception("Status update failed: " . $updateStmt->error);
    }

    if ($updateStmt->affected_rows <= 0) {
        throw new Exception("Email sent, but inquiry status was not updated. Check inquiry ID.");
    }

    $updateStmt->close();

    $_SESSION['success_message'] = "Reply email sent successfully. Inquiry status updated to Responded.";
    header("Location: ../replyform.php?inq_id=" . $inq_id);
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Email could not be sent or status was not updated. Error: " . $e->getMessage();
    header("Location: ../replyform.php?inq_id=" . $inq_id);
    exit();
}
?>