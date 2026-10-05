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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || $role !== 'unit owner') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Only unit owners can verify payments for their units.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$owner_id = (int)$_SESSION['user_id'];
$owner_name = $_SESSION['full_name'] ?? 'Unit Owner';
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
    if ($stmt) {
        $stmt->bind_param("iisss", $reservationId, $adminId, $adminName, $action, $remarks);
        $stmt->execute();
        $stmt->close();
    }
}

function sendReservationEmail($toEmail, $toName, $subject, $bodyHtml, $bodyText) {
    try {
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
    } catch (Exception $e) {
        error_log("Failed to send reservation payment email: " . $e->getMessage());
    }
}

$conn->begin_transaction();

try {
    // Verify that the reservation belongs to a unit owned by this unit owner
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
            u.unit_owner_id,
            u.unit_current_status,

            owner.full_name AS owner_name,
            owner.email AS owner_email
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
        WHERE r.reservation_id = ? 
          AND (u.unit_owner_id = ? OR u.unit_owner_id IN (SELECT user_id FROM users_table WHERE email = ?))
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $ownerEmail = $_SESSION['email'] ?? '';
    $stmt->bind_param("iis", $reservation_id, $owner_id, $ownerEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found or you do not have permission to verify payments for this unit.");
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

    $clientName = $reservation['client_name'];
    $clientEmail = $reservation['client_email'];
    $unitName = trim(($reservation['unit_type'] ?? '') . ' Unit ' . ($reservation['unit_number'] ?? ''));
    $requiredAmount = '₱' . number_format((float)$reservation['required_amount'], 2);
    $ownerDisplayName = $reservation['owner_name'] ?: $owner_name;

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

        $formattedRemarks = !empty($remarks) ? "Verified by Unit Owner: " . $remarks : "Verified by Unit Owner";
        $stmt->bind_param(
            "sssi",
            $formattedRemarks,
            $clientCancelToken,
            $clientCancelTokenExpiresAt,
            $reservation_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to verify payment.");
        }
        $stmt->close();

        seedReservationDocuments($conn, $reservation_id);

        $subject = "Reservation Payment Verified by Unit Owner - Next Steps";
        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
                <h2>Reservation Payment Verified</h2>
                <p>Dear " . htmlspecialchars($clientName) . ",</p>
                <p>
                    We are pleased to inform you that your reservation downpayment for 
                    <strong>" . htmlspecialchars($unitName) . "</strong> has been confirmed and verified by the unit owner (<strong>" . htmlspecialchars($ownerDisplayName) . "</strong>).
                </p>
                <p>Your reservation is now moving to the next step: completion of reservation requirements.</p>
                <p><strong>Required documents for tracking:</strong></p>
                <ul>
                    <li>Photocopy of 2 valid IDs</li>
                    <li>TIN number</li>
                    <li>Reservation agreement</li>
                </ul>
                <p>
                    Please coordinate with your unit owner or Zeppelin Suites administration
                    regarding the signing/notary process and submission of the required documents.
                </p>
                <p>
                    <strong>Unit Owner:</strong> " . htmlspecialchars($ownerDisplayName) . "<br>
                    <strong>Owner Email:</strong> " . htmlspecialchars($reservation['owner_email'] ?: 'N/A') . "
                </p>
                <div style='margin-top:20px;padding:14px;border:1px solid #fecaca;background:#fef2f2;border-radius:10px;'>
                    <p style='margin:0 0 10px 0;color:#991b1b;font-weight:bold;'>Need to request cancellation?</p>
                    <p style='margin:0 0 12px 0;color:#7f1d1d;font-size:14px;'>
                        If you wish to request cancellation of this reservation, use the secure link below.
                    </p>
                    <a href='" . htmlspecialchars($clientCancelLink) . "' 
                       style='display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:bold;'>
                       Request Cancellation
                    </a>
                </div>
            </div>
        ";
        $bodyText = "Dear {$clientName},\n\nYour reservation payment for {$unitName} has been verified by the unit owner ({$ownerDisplayName}). Next step: complete your reservation requirements.";

        sendReservationEmail($clientEmail, $clientName, $subject, $bodyHtml, $bodyText);
        logPaymentVerification($conn, $reservation_id, $owner_id, $ownerDisplayName . ' (Unit Owner)', 'verify', $remarks);

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment has been successfully verified! Requirement tracking is now unlocked.'
        ]);
        exit;

    } elseif ($action === 'flag') {
        $updateSql = "
            UPDATE reservation_table
            SET payment_status = 'flagged',
                admin_payment_remarks = ?
            WHERE reservation_id = ?
        ";

        $stmt = $conn->prepare($updateSql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $formattedRemarks = "Flagged by Unit Owner: " . $remarks;
        $stmt->bind_param("si", $formattedRemarks, $reservation_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to flag payment.");
        }
        $stmt->close();

        $subject = "Update Needed: Reservation Payment Flagged by Unit Owner";
        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
                <h2>Payment Under Review</h2>
                <p>Dear " . htmlspecialchars($clientName) . ",</p>
                <p>The unit owner (<strong>" . htmlspecialchars($ownerDisplayName) . "</strong>) reviewed your reservation downpayment for <strong>" . htmlspecialchars($unitName) . "</strong> and flagged it for clarification.</p>
                <p><strong>Note from Unit Owner:</strong><br>" . nl2br(htmlspecialchars($remarks)) . "</p>
                <p>Please contact your unit owner or Zeppelin Suites administration to clarify your payment details.</p>
            </div>
        ";
        $bodyText = "Dear {$clientName},\n\nYour reservation payment for {$unitName} was flagged by the unit owner ({$ownerDisplayName}). Reason: {$remarks}";

        sendReservationEmail($clientEmail, $clientName, $subject, $bodyHtml, $bodyText);
        logPaymentVerification($conn, $reservation_id, $owner_id, $ownerDisplayName . ' (Unit Owner)', 'flag', $remarks);

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment has been flagged. The tenant has been notified.'
        ]);
        exit;

    } elseif ($action === 'reject') {
        $updateSql = "
            UPDATE reservation_table
            SET payment_status = 'rejected',
                reservation_status = 'rejected',
                payment_rejected_at = NOW(),
                admin_payment_remarks = ?
            WHERE reservation_id = ?
        ";

        $stmt = $conn->prepare($updateSql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $formattedRemarks = "Rejected by Unit Owner: " . $remarks;
        $stmt->bind_param("si", $formattedRemarks, $reservation_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to reject payment.");
        }
        $stmt->close();

        $subject = "Reservation Payment Declined";
        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;color:#111827'>
                <h2>Reservation Payment Declined</h2>
                <p>Dear " . htmlspecialchars($clientName) . ",</p>
                <p>The unit owner (<strong>" . htmlspecialchars($ownerDisplayName) . "</strong>) was unable to verify the reservation downpayment for <strong>" . htmlspecialchars($unitName) . "</strong>.</p>
                <p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($remarks)) . "</p>
                <p>This reservation has been marked as rejected. If you believe this is an error, please contact the unit owner directly.</p>
            </div>
        ";
        $bodyText = "Dear {$clientName},\n\nYour reservation payment for {$unitName} was rejected by the unit owner. Reason: {$remarks}";

        sendReservationEmail($clientEmail, $clientName, $subject, $bodyHtml, $bodyText);
        logPaymentVerification($conn, $reservation_id, $owner_id, $ownerDisplayName . ' (Unit Owner)', 'reject', $remarks);

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment has been rejected. The reservation has been closed.'
        ]);
        exit;
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
