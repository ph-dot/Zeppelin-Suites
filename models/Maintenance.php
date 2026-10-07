<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Maintenance Model
 * Pure MVC database layer for building maintenance requests and ticket statuses.
 * Strictly PDO prepared statements. Zero HTML output.
 */
class Maintenance extends Model {
    /**
     * Fetch all maintenance tickets with unit, owner, and tenant relations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllWithRelations(): array {
        $stmt = $this->db->prepare("
            SELECT 
                m.maintenance_id,
                m.unit_id,
                m.unit_owner_id,
                m.submitted_by_user_id,
                m.submitted_by_role,
                m.subject,
                m.category,
                m.description,
                m.priority,
                m.status,
                m.photo_paths,
                m.admin_remarks,
                m.submitted_at,
                m.updated_at,
                m.resolved_at,
                u.unit_number,
                u.unit_type,
                u.floor_number,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                (SELECT r.client_name 
                 FROM reservation_table r 
                 WHERE r.unit_id = u.unit_id 
                   AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL) 
                 ORDER BY r.reservation_id DESC LIMIT 1) AS tenant_name
            FROM maintenance_requests m
            LEFT JOIN units_table u ON m.unit_id = u.unit_id
            LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
            ORDER BY m.submitted_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get grouped tickets for Kanban columns (active, unassigned, closed) and counts.
     *
     * @return array{active: array, unassigned: array, closed: array, total: int, active_count: int, unassigned_count: int, closed_count: int}
     */
    public function getGroupedTickets(): array {
        $tickets = $this->getAllWithRelations();
        $active = [];
        $unassigned = [];
        $closed = [];

        foreach ($tickets as $row) {
            $st = strtolower(trim((string)($row['status'] ?? 'pending')));
            if ($st === 'in progress') {
                $active[] = $row;
            } elseif ($st === 'pending') {
                $unassigned[] = $row;
            } else {
                $closed[] = $row;
            }
        }

        return [
            'active'           => $active,
            'unassigned'       => $unassigned,
            'closed'           => $closed,
            'total'            => count($tickets),
            'active_count'     => count($active),
            'unassigned_count' => count($unassigned),
            'closed_count'     => count($closed),
        ];
    }

    /**
     * Fetch all distinct unit types for filter dropdown.
     *
     * @return array<int, string>
     */
    public function getUnitTypeOptions(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT unit_type 
            FROM units_table 
            WHERE unit_type IS NOT NULL AND TRIM(unit_type) != '' 
            ORDER BY unit_type ASC
        ");
        $types = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = trim((string)$row['unit_type']);
            if ($val !== '') {
                $types[] = $val;
            }
        }
        return $types;
    }

    /**
     * Fetch all units for ticket creation selection dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUnitOptions(): array {
        $stmt = $this->db->query("
            SELECT unit_id, unit_number, unit_type, floor_number 
            FROM units_table 
            ORDER BY floor_number ASC, unit_number ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update ticket status and admin remarks.
     */
    public function updateStatus(int $maintenanceId, string $status, string $adminRemarks): array {
        $allowedStatuses = ['pending', 'in progress', 'resolved', 'cancelled'];
        $status = strtolower(trim($status));
        $adminRemarks = trim($adminRemarks);

        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid maintenance status.');
        }

        if (strlen($adminRemarks) > 2000) {
            throw new InvalidArgumentException('Admin remarks must not exceed 2,000 characters.');
        }

        $this->db->beginTransaction();

        try {
            $checkStmt = $this->db->prepare("
                SELECT m.maintenance_id, m.subject, owner.full_name AS owner_name, owner.email AS owner_email
                FROM maintenance_requests m
                LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
                WHERE m.maintenance_id = ?
                FOR UPDATE
            ");
            $checkStmt->execute([$maintenanceId]);
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Maintenance request not found.'];
            }

            $updateStmt = $this->db->prepare("
                UPDATE maintenance_requests
                SET status = ?,
                    admin_remarks = ?,
                    updated_at = NOW(),
                    resolved_at = CASE
                        WHEN ? = 'resolved' THEN COALESCE(resolved_at, NOW())
                        ELSE NULL
                    END
                WHERE maintenance_id = ?
            ");
            $updateStmt->execute([$status, $adminRemarks, $status, $maintenanceId]);

            $this->db->commit();

            // Notify unit owner if notification helper exists
            $notifyFile = dirname(__DIR__) . '/php_files/owner_notifications.php';
            if (file_exists($notifyFile)) {
                require_once $notifyFile;
                if (function_exists('notifyOwnerOfMaintenanceFeedback')) {
                    try {
                        notifyOwnerOfMaintenanceFeedback(
                            (string)($row['owner_email'] ?? ''),
                            (string)($row['owner_name'] ?? 'Unit Owner'),
                            (string)($row['subject'] ?? 'your maintenance request'),
                            $status,
                            $adminRemarks
                        );
                    } catch (Throwable $e) {
                        error_log('Notification error: ' . $e->getMessage());
                    }
                }
            }

            return ['success' => true, 'message' => 'Maintenance ticket updated successfully.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Create a new maintenance ticket with optional photo attachments.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    public function create(array $data, array $files, int $adminUserId): int {
        $unitId = (int)($data['unit_id'] ?? 0);
        $subject = trim((string)($data['subject'] ?? ''));
        $category = trim((string)($data['category'] ?? 'Other'));
        $priority = strtolower(trim((string)($data['priority'] ?? 'normal')));
        $description = trim((string)($data['description'] ?? ''));

        $allowedCategories = ['Plumbing', 'Electrical', 'Cleaning', 'Fixture', 'Structural', 'Other'];
        $allowedPriorities = ['low', 'normal', 'medium', 'urgent', 'high'];

        if ($unitId <= 0 || $subject === '' || $description === '') {
            throw new InvalidArgumentException('Please complete all required fields.');
        }

        if (!in_array($category, $allowedCategories, true)) {
            $category = 'Other';
        }
        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'normal';
        }
        if ($priority === 'medium') $priority = 'normal';
        if ($priority === 'high') $priority = 'urgent';

        $this->db->beginTransaction();

        try {
            $unitStmt = $this->db->prepare("SELECT unit_id, unit_owner_id FROM units_table WHERE unit_id = ? LIMIT 1");
            $unitStmt->execute([$unitId]);
            $unitRow = $unitStmt->fetch(PDO::FETCH_ASSOC);

            if (!$unitRow) {
                throw new InvalidArgumentException('Invalid unit selected.');
            }

            $ownerId = (int)($unitRow['unit_owner_id'] ?? 0);

            // Handle file uploads
            $photoPaths = [];
            if (!empty($files['maintenance_photos']['name'][0])) {
                $uploadDir = dirname(__DIR__) . '/public/uploads/maintenance/';
                $dbDir = 'uploads/maintenance/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $maxFiles = 5;
                $maxSize = 5 * 1024 * 1024;
                $fileCount = count($files['maintenance_photos']['name']);

                if ($fileCount > $maxFiles) {
                    throw new InvalidArgumentException('You may upload up to 5 photos only.');
                }

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['maintenance_photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($files['maintenance_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('One of the uploaded photos failed.');
                    }
                    if ($files['maintenance_photos']['size'][$i] > $maxSize) {
                        throw new InvalidArgumentException('Each photo must be 5MB or below.');
                    }

                    $originalName = $files['maintenance_photos']['name'][$i];
                    $tmpName = $files['maintenance_photos']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowedExt, true)) {
                        throw new InvalidArgumentException('Only JPG, PNG, and WEBP files are allowed.');
                    }

                    $newName = 'maintenance_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetPath = $uploadDir . $newName;
                    $dbPath = $dbDir . $newName;

                    if (!move_uploaded_file($tmpName, $targetPath)) {
                        throw new RuntimeException('Failed to upload photo.');
                    }

                    $photoPaths[] = $dbPath;
                }
            }

            $photoPathsValue = !empty($photoPaths) ? implode(',', $photoPaths) : null;

            $stmt = $this->db->prepare("
                INSERT INTO maintenance_requests (
                    submitted_by_user_id,
                    submitted_by_role,
                    unit_owner_id,
                    unit_id,
                    subject,
                    category,
                    description,
                    priority,
                    status,
                    photo_paths,
                    submitted_at
                ) VALUES (?, 'admin', ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $adminUserId,
                $ownerId > 0 ? $ownerId : null,
                $unitId,
                $subject,
                $category,
                $description,
                $priority,
                $photoPathsValue,
            ]);

            $ticketId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $ticketId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
