<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Inquiry Model
 * Handles all database operations for inquiry_table and associated owner approval requests.
 * Uses PDO prepared statements. Zero direct HTML output.
 */
class Inquiry extends Model {

    /**
     * Get snapshot stats for Inquiries.
     */
    public function getInquiryStats(): array {
        $newTodayRow = $this->fetchOne("SELECT COUNT(*) as count FROM inquiry_table WHERE DATE(timestamp) = CURDATE()");
        $pendingRow = $this->fetchOne("SELECT COUNT(*) as count FROM inquiry_table WHERE status = 'pending' OR status = '' OR status IS NULL");
        $respondedRow = $this->fetchOne("SELECT COUNT(*) as count FROM inquiry_table WHERE status = 'responded'");

        return [
            'newToday'  => (int)($newTodayRow['count'] ?? 0),
            'pending'   => (int)($pendingRow['count'] ?? 0),
            'responded' => (int)($respondedRow['count'] ?? 0),
        ];
    }

    /**
     * Fetch all inquiries with joined units and associated owner requests.
     */
    public function getAllInquiries(): array {
        $sql = "
            SELECT 
                i.inq_id,
                i.sender_name,
                i.sender_email,
                i.sender_contact,
                i.inquiry_type,
                i.Preferred_unit_id,
                i.preferred_move_in_time,
                i.message,
                DATE(i.timestamp) AS date_only,
                i.timestamp,
                i.status,
                i.approval_status,
                i.approved_unit_id,
                i.owner_remarks,
                i.approval_approved_at,
                DATE_FORMAT(i.approval_approved_at, '%b %d, %Y %h:%i %p') AS approval_approved_at_display,
                i.lease_duration,
                u.unit_number AS approved_unit_number
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            ORDER BY i.timestamp DESC, i.inq_id DESC
        ";

        $rows = $this->fetchAll($sql);

        // Batch load owner approval requests
        $reqSql = "
            SELECT
                r.request_id,
                r.inq_id,
                r.unit_id,
                r.request_status,
                r.owner_remarks,
                r.requested_at,
                r.responded_at,
                u.unit_number,
                owner.full_name AS owner_name
            FROM owner_approval_requests r
            LEFT JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON r.unit_owner_id = owner.user_id
            ORDER BY r.requested_at ASC
        ";
        $allRequests = $this->fetchAll($reqSql);

        $requestsByInquiry = [];
        foreach ($allRequests as $req) {
            $inqId = (int)$req['inq_id'];
            if (!isset($requestsByInquiry[$inqId])) {
                $requestsByInquiry[$inqId] = [];
            }
            $requestsByInquiry[$inqId][] = [
                'request_id'     => (int)$req['request_id'],
                'unit_number'    => $req['unit_number'] ?? 'Unknown unit',
                'owner_name'     => $req['owner_name'] ?? 'Unknown owner',
                'request_status' => (string)($req['request_status'] ?? ''),
                'owner_remarks'  => (string)($req['owner_remarks'] ?? ''),
                'requested_at'   => (string)($req['requested_at'] ?? ''),
                'responded_at'   => (string)($req['responded_at'] ?? ''),
            ];
        }

        foreach ($rows as &$row) {
            $inqId = (int)$row['inq_id'];
            $requests = $requestsByInquiry[$inqId] ?? [];
            $pendingCount = 0;
            foreach ($requests as $r) {
                if (strtolower($r['request_status']) === 'pending') {
                    $pendingCount++;
                }
            }
            $row['requests'] = $requests;
            $row['pending_request_count'] = $pendingCount;
        }
        unset($row);

        return $rows;
    }

    /**
     * Get single inquiry by ID with joined unit, approved owner, and approval request info.
     */
    public function getById(int $inqId): ?array {
        $sql = "
            SELECT 
                i.inq_id,
                i.sender_name,
                i.sender_email,
                i.sender_contact,
                i.inquiry_type,
                i.Preferred_unit_id,
                i.preferred_move_in_time,
                i.lease_duration,
                i.message,
                i.status,
                i.approval_status,
                i.approved_unit_id,
                i.approval_approved_at,
                i.reservation_token,
                i.timestamp,
                DATE_FORMAT(i.approval_approved_at, '%b %d, %Y %h:%i %p') AS approved_at_display,
                u.unit_number AS approved_unit_number,
                u.unit_type AS approved_unit_type,
                u.lease_rate AS approved_lease_rate,
                owner.full_name AS approved_owner_name
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            LEFT JOIN owner_approval_requests r
                ON r.inq_id = i.inq_id
                AND r.unit_id = i.approved_unit_id
                AND r.request_status = 'approved'
            LEFT JOIN users_table owner ON r.unit_owner_id = owner.user_id
            WHERE i.inq_id = ?
            LIMIT 1
        ";

        return $this->fetchOne($sql, [$inqId]);
    }

    /**
     * Update an inquiry's status directly.
     */
    public function updateStatus(int $inqId, string $status): bool {
        $allowed = ['pending', 'responded', 'onhold', 'declined', 'reservation submitted', 'officially booked'];
        $cleanStatus = in_array(strtolower($status), $allowed, true) ? strtolower($status) : 'pending';

        $sql = "UPDATE inquiry_table SET status = ? WHERE inq_id = ?";
        return $this->execute($sql, [$cleanStatus, $inqId]);
    }

    /**
     * Send email reply to an inquiry and record status update.
     */
    public function sendReply(int $inqId, string $replyTo, string $subject, string $emailBody): array {
        $inquiry = $this->getById($inqId);
        if (!$inquiry) {
            return ['success' => false, 'error' => 'Inquiry not found.'];
        }

        // Auto-append reservation token link if approved
        if (
            strtolower((string)($inquiry['approval_status'] ?? '')) === 'approved' &&
            !empty($inquiry['reservation_token'])
        ) {
            $baseUrl = rtrim((string)env('APP_URL', 'http://localhost/Zeppelin-Suites'), '/');
            $reservationLink = "{$baseUrl}/reservation?token=" . urlencode((string)$inquiry['reservation_token']);

            if (strpos($emailBody, $reservationLink) === false) {
                $emailBody .= "\n\nReservation Form Link:\n" . $reservationLink;
            }
        }

        // Require email config and PHPMailer
        $emailConfigFile = dirname(__DIR__) . '/config/email_config.php';
        if (file_exists($emailConfigFile)) {
            require_once $emailConfigFile;
        }

        $mailerDir = dirname(__DIR__) . '/phpmailer/src';
        if (file_exists($mailerDir . '/PHPMailer.php')) {
            require_once $mailerDir . '/Exception.php';
            require_once $mailerDir . '/PHPMailer.php';
            require_once $mailerDir . '/SMTP.php';
        }

        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return ['success' => false, 'error' => 'PHPMailer library unavailable.'];
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : (string)env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : (string)env('MAIL_USERNAME', '');
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : (string)env('MAIL_PASSWORD', '');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : (int)env('MAIL_PORT', 587);

            $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : (string)env('MAIL_FROM_ADDRESS', 'noreply@zeppelinsuites.com');
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (string)env('MAIL_FROM_NAME', 'Zeppelin Suites');

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($replyTo);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $emailBody;

            $mail->send();

            // Update status and timestamp
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
            $this->execute($updateSql, [$inqId]);

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new public visitor inquiry submitted from the contact form.
     */
    public function createPublicInquiry(array $data): array {
        $senderName = trim((string)($data['sender_name'] ?? ''));
        $senderEmail = trim((string)($data['sender_email'] ?? ''));
        $senderContact = trim((string)($data['sender_contact'] ?? ''));
        $inquiryType = trim((string)($data['inquiry_type'] ?? ''));
        $preferredUnit = !empty($data['Preferred_unit_id']) ? trim((string)$data['Preferred_unit_id']) : null;
        $preferredMoveIn = !empty($data['preferred_move_in_time']) ? trim((string)$data['preferred_move_in_time']) : null;
        $leaseDuration = !empty($data['lease_duration']) ? trim((string)$data['lease_duration']) : null;
        $message = trim((string)($data['Message'] ?? $data['message'] ?? ''));

        if ($senderName === '' || $senderEmail === '' || $senderContact === '' || $inquiryType === '') {
            return ['success' => false, 'error' => 'Please fill in all required fields.'];
        }

        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please enter a valid email address.'];
        }

        if ($preferredMoveIn !== null) {
            $preferredMoveIn = str_replace(['–', '—', '?"', 'â€“'], '-', $preferredMoveIn);
        }

        $sql = "
            INSERT INTO inquiry_table (
                sender_name,
                sender_email,
                sender_contact,
                inquiry_type,
                Preferred_unit_id,
                preferred_move_in_time,
                lease_duration,
                message,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";

        try {
            $this->execute($sql, [
                $senderName,
                $senderEmail,
                $senderContact,
                $inquiryType,
                $preferredUnit,
                $preferredMoveIn,
                $leaseDuration,
                $message
            ]);

            return ['success' => true, 'inq_id' => (int)$this->db->lastInsertId()];
        } catch (\Throwable $e) {
            error_log('createPublicInquiry error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error submitting inquiry. Please try again.'];
        }
    }
}

