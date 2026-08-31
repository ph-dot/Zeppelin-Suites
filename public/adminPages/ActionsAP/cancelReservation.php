<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/email_config.php';

require_once __DIR__ . '/../../phpmailer/src/Exception.php';
require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$remarks = trim($_POST['remarks'] ?? '');

if ($reservation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID.']);
    exit;
}

if ($remarks === '') {
    echo json_encode(['success' => false, 'message' => 'Cancellation reason is required.']);
    exit;
}

$admin_id = (int)($_SESSION['user_id'] ?? 0);
$admin_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'admin';

function sendCancellationEmail($toEmail, $toName, $unitName, $remarks) {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $subject = "Reservation Cancellation Notice";

    $bodyHtml = "
        <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
            <h2>Reservation Cancellation Notice</h2>

            <p>Dear " . htmlspecialchars($toName) . ",</p>

            <p>
                We regret to inform you that your reservation request for 
                <strong>" . htmlspecialchars($unitName) . "</strong> has been cancelled.
            </p>

            <p>
                <strong>Reason for cancellation:</strong><br>
                " . nl2br(htmlspecialchars($remarks)) . "
            </p>

            <p>
                The unit has been released back to availability. For further clarification,
                you may contact Zeppelin Suites administration.
            </p>

            <p>
                Thank you,<br>
                <strong>Zeppelin Suites Administration</strong>
            </p>
        </div>
    ";

    $bodyText = "
Dear {$toName},

We regret to inform you that your reservation request for {$unitName} has been cancelled.

Reason for cancellation:
{$remarks}

The unit has been released back to availability. For further clarification, you may contact Zeppelin Suites administration.

Thank you,
Zeppelin Suites Administration
    ";

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyText;

    $mail->send();
}

$conn->begin_transaction();

try {
    $sql = "
        SELECT 
            r.reservation_id,
            r.unit_id,
            r.inquiry_type,
            r.reservation_status,
            r.payment_status,
            r.client_name,
            r.client_email,
            r.cancellation_status,
            r.cancellation_reason,
            u.unit_current_status,
            u.unit_type,
            u.unit_number
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found.");
    }
    if ($reservation['cancellation_status'] !== 'requested') {
    throw new Exception("This reservation has no pending cancellation request from the unit owner.");
    }
    if ($reservation['reservation_status'] === 'reserved') {
        throw new Exception("This reservation is already officially booked. Use a separate void process if needed.");
    }

    if ($reservation['reservation_status'] === 'cancelled') {
        throw new Exception("This reservation is already cancelled.");
    }

    if ($reservation['reservation_status'] === 'rejected') {
        throw new Exception("This reservation is already rejected.");
    }

    $inquiryType = strtolower(trim($reservation['inquiry_type']));
    $releasedStatus = ($inquiryType === 'resale inquiry') ? 'Resale' : 'Ready for Occupancy';

    $updateReservationSql = "
        UPDATE reservation_table
        SET reservation_status = 'cancelled',
            cancellation_status = 'approved',
            cancelled_at = NOW(),
            cancelled_by = ?,
            cancelled_by_role = ?,
            admin_cancel_remarks = ?
        WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateReservationSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("issi", $admin_id, $admin_role, $remarks, $reservation_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to cancel reservation.");
    }

    $stmt->close();

    $updateUnitSql = "
        UPDATE units_table
        SET unit_current_status = ?
        WHERE unit_id = ?
    ";

    $stmt = $conn->prepare($updateUnitSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $releasedStatus, $reservation['unit_id']);

    if (!$stmt->execute()) {
        throw new Exception("Failed to release unit.");
    }

    $stmt->close();

    $unitName = trim(($reservation['unit_type'] ?? '') . ' Unit ' . ($reservation['unit_number'] ?? ''));

    sendCancellationEmail(
        $reservation['client_email'],
        $reservation['client_name'],
        $unitName,
        $remarks
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation cancelled successfully. Email notification was sent to the client.'
    ]);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>