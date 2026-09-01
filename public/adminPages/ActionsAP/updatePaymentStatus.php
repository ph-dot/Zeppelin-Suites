<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/email_config.php';
require_once __DIR__ . '/../../php_files/document_requirements.php';

require_once __DIR__ . '/../../phpmailer/src/Exception.php';
require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$action = trim($_POST['action'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

if (!in_array($action, ['verify', 'reject', 'flag'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);
    exit;
}

function logPaymentVerification(mysqli $conn, int $reservationId, ?int $adminId, ?string $adminName, string $action, string $remarks): void {
    $sql = "
        INSERT INTO payment_verification_log (reservation_id, admin_id, admin_name, action, remarks)
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log('logPaymentVerification failed: ' . $conn->error);
        return;
    }

    $stmt->bind_param("iisss", $reservationId, $adminId, $adminName, $action, $remarks);
    $stmt->execute();
    $stmt->close();
}

function sendReservationEmail($toEmail, $toName, $subject, $bodyHtml, $bodyText) {
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
            r.inq_id,
            r.unit_id,
            r.client_name,
            r.client_email,
            r.client_contact,
            r.inquiry_type,
            r.required_amount,
            r.payment_reference,
            r.payment_status,
            r.reservation_status,

            u.unit_type,
            u.unit_number,
            u.unit_current_status,

            owner.full_name AS owner_name,
            owner.email AS owner_email
        FROM reservation_table r
        LEFT JOIN units_table u ON r.unit_id = u.unit_id
        LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
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

    if ($reservation['payment_status'] === 'verified' && $action === 'verify') {
        throw new Exception("Payment is already verified.");
    }

    if ($reservation['payment_status'] === 'rejected' && $action === 'reject') {
        throw new Exception("Payment is already rejected.");
    }

    if (in_array($reservation['payment_status'], ['verified', 'rejected'], true) && $action === 'flag') {
        throw new Exception("This payment has already been " . $reservation['payment_status'] . " and can no longer be flagged.");
    }

    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $adminName = $_SESSION['full_name'] ?? 'Admin';

    $clientName = $reservation['client_name'];
    $clientEmail = $reservation['client_email'];
    $unitName = trim(($reservation['unit_type'] ?? '') . ' Unit ' . ($reservation['unit_number'] ?? ''));
    $requiredAmount = '₱' . number_format((float)$reservation['required_amount'], 2);
    $ownerName = $reservation['owner_name'] ?: 'the assigned unit owner';
    $ownerEmail = $reservation['owner_email'] ?: 'N/A';

    if ($action === 'verify') {

        $clientCancelToken = bin2hex(random_bytes(32));
        $clientCancelTokenExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $baseUrl = "http://localhost/Zeppelin-Suites/public/generalViewPages/cancelReservation.php";
        $clientCancelLink = $baseUrl . "?token=" . urlencode($clientCancelToken);

        $updateSql = "
            UPDATE reservation_table
            SET payment_status = 'verified',
                reservation_status = 'requirements pending',
                payment_verified_at = NOW(),
                admin_payment_remarks = ?,
                client_cancel_token = ?,
                client_cancel_token_expires_at = ?
            WHERE reservation_id = ?
        ";

        $stmt = $conn->prepare($updateSql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sssi",
            $remarks,
            $clientCancelToken,
            $clientCancelTokenExpiresAt,
            $reservation_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to verify payment.");
        }

        $stmt->close();

        seedReservationDocuments($conn, $reservation_id);

        $subject = "Reservation Payment Verified - Next Steps";

        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
                <h2>Reservation Payment Verified</h2>

                <p>Dear " . htmlspecialchars($clientName) . ",</p>

                <p>
                    We are pleased to inform you that your reservation payment for 
                    <strong>" . htmlspecialchars($unitName) . "</strong> has been verified.
                </p>

                <p>Your reservation is now moving to the next step: completion of reservation requirements.</p>

                <p><strong>Required documents for tracking:</strong></p>
                <ul>
                    <li>Photocopy of 2 valid IDs</li>
                    <li>TIN number</li>
                    <li>Reservation agreement</li>
                </ul>

                <p>
                    Please coordinate with the assigned unit owner or Zeppelin Suites administration
                    regarding the signing/notary process and submission of the required documents.
                </p>

                <p>
                    <strong>Unit Owner:</strong> " . htmlspecialchars($ownerName) . "<br>
                    <strong>Owner Email:</strong> " . htmlspecialchars($ownerEmail) . "
                </p>

                <p>
                    Please note that your reservation is not yet officially booked until all required
                    documents have been completed and confirmed by the admin.
                </p>

                <div style='margin-top:20px;padding:14px;border:1px solid #fecaca;background:#fef2f2;border-radius:10px;'>
                    <p style='margin:0 0 10px 0;color:#991b1b;font-weight:bold;'>
                        Need to request cancellation?
                    </p>

                    <p style='margin:0 0 12px 0;color:#7f1d1d;font-size:14px;'>
                        If you wish to request cancellation of this reservation, use the secure link below.
                        Cancellation is subject to admin review and approval.
                    </p>

                    <a href='" . htmlspecialchars($clientCancelLink) . "' 
                       style='display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:bold;'>
                       Request Cancellation
                    </a>

                    <p style='margin:12px 0 0 0;font-size:12px;color:#7f1d1d;'>
                        This link is valid for 30 days.
                    </p>
                </div>

                <p>Thank you,<br><strong>Zeppelin Suites Administration</strong></p>
            </div>
        ";

        $bodyText = "
Dear {$clientName},

Your reservation payment for {$unitName} has been verified.

Your reservation is now moving to the next step: completion of reservation requirements.

Required documents for tracking:
- Photocopy of 2 valid IDs
- TIN number
- Reservation agreement

Please coordinate with the assigned unit owner or Zeppelin Suites administration regarding the signing/notary process and submission of the required documents.

Unit Owner: {$ownerName}
Owner Email: {$ownerEmail}

Please note that your reservation is not yet officially booked until all required documents have been completed and confirmed by the admin.

Cancellation Request Link:
{$clientCancelLink}

This link is valid for 30 days and can only be used to submit a cancellation request. Cancellation is subject to admin review and approval.

Thank you,
Zeppelin Suites Administration
        ";

        sendReservationEmail($clientEmail, $clientName, $subject, $bodyHtml, $bodyText);

        logPaymentVerification($conn, $reservation_id, $adminId, $adminName, 'verify', $remarks);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully. Email notification sent to client.',
            'payment_status' => 'verified',
            'reservation_status' => 'requirements pending'
        ]);
        exit;
    }

    if ($action === 'flag') {
        $updateFlagSql = "
            UPDATE reservation_table
            SET payment_status = 'flagged for review',
                admin_payment_remarks = ?
            WHERE reservation_id = ?
        ";

        $stmt = $conn->prepare($updateFlagSql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $remarks, $reservation_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to flag payment for review.");
        }

        $stmt->close();

        logPaymentVerification($conn, $reservation_id, $adminId, $adminName, 'flag', $remarks);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Payment flagged for review. The reservation is on hold until you verify or reject it.',
            'payment_status' => 'flagged for review',
            'reservation_status' => $reservation['reservation_status']
        ]);
        exit;
    }

    if ($action === 'reject') {
        $inquiryType = strtolower(trim($reservation['inquiry_type']));

        if ($inquiryType === 'resale inquiry') {
            $releasedStatus = 'Resale';
        } else {
            $releasedStatus = 'Ready for Occupancy';
        }

        $updateReservationSql = "
            UPDATE reservation_table
            SET payment_status = 'rejected',
                reservation_status = 'rejected',
                payment_rejected_at = NOW(),
                admin_payment_remarks = ?
            WHERE reservation_id = ?
        ";

        $stmt = $conn->prepare($updateReservationSql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $remarks, $reservation_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to reject payment.");
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

        $subject = "Reservation Payment Review Update";

        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
                <h2>Reservation Payment Review Update</h2>

                <p>Dear " . htmlspecialchars($clientName) . ",</p>

                <p>
                    After reviewing your submitted payment proof for 
                    <strong>" . htmlspecialchars($unitName) . "</strong>, we are unable to proceed
                    with your reservation request because the payment amount does not match the required
                    reservation amount shown in the reservation form.
                </p>

                <p>
                    <strong>Required Amount:</strong> " . htmlspecialchars($requiredAmount) . "<br>
                    <strong>GCash Reference:</strong> " . htmlspecialchars($reservation['payment_reference']) . "
                </p>

                <p>
                    As stated in the reservation policy, reservation fees are non-refundable once verified
                    and processed. Since the submitted payment did not meet the required amount, your
                    reservation request has been rejected.
                </p>

                " . (!empty($remarks) ? "<p><strong>Admin Remarks:</strong> " . nl2br(htmlspecialchars($remarks)) . "</p>" : "") . "

                <p>For further clarification, you may contact Zeppelin Suites administration.</p>

                <p>Thank you,<br><strong>Zeppelin Suites Administration</strong></p>
            </div>
        ";

        $bodyText = "
Dear {$clientName},

After reviewing your submitted payment proof for {$unitName}, we are unable to proceed with your reservation request because the payment amount does not match the required reservation amount shown in the reservation form.

Required Amount: {$requiredAmount}
GCash Reference: {$reservation['payment_reference']}

As stated in the reservation policy, reservation fees are non-refundable once verified and processed. Since the submitted payment did not meet the required amount, your reservation request has been rejected.

Admin Remarks: {$remarks}

For further clarification, you may contact Zeppelin Suites administration.

Thank you,
Zeppelin Suites Administration
        ";

        sendReservationEmail($clientEmail, $clientName, $subject, $bodyHtml, $bodyText);

        logPaymentVerification($conn, $reservation_id, $adminId, $adminName, 'reject', $remarks);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Payment rejected successfully. Email notification sent to client.',
            'payment_status' => 'rejected',
            'reservation_status' => 'rejected'
        ]);
        exit;
    }

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>