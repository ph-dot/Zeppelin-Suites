<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Analytics Model
 * Handles database operations and KPI data aggregations across inquiries, units, reservations, and maintenance.
 * Zero direct output or presentation markup. Prepared statements via PDO.
 */
class Analytics extends Model {

    /**
     * Count inquiries received within a date window, optionally filtered by unit type.
     */
    public function countInquiries(string $start, string $end, ?string $unitType = null): int {
        $sql = "
            SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
            $params[] = $unitType;
            $params[] = $unitType;
        }

        $row = $this->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Count HOA checked inquiries within a date window.
     */
    public function countHoaChecked(string $start, string $end, ?string $unitType = null): int {
        $sql = "
            SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?
              AND (i.status IN ('responded', 'onhold') OR i.approval_status IN ('requested', 'approved', 'declined'))
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
            $params[] = $unitType;
            $params[] = $unitType;
        }

        $row = $this->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Count inquiries approved by unit owner within a date window.
     */
    public function countOwnerApproved(string $start, string $end, ?string $unitType = null): int {
        $sql = "
            SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?
              AND i.approval_status = 'approved'
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
            $params[] = $unitType;
            $params[] = $unitType;
        }

        $row = $this->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Calculate portfolio occupancy counts and percentage rate.
     */
    public function getOccupancyStats(?string $unitType = null): array {
        $sql = "
            SELECT
                COUNT(*) AS total_units,
                SUM(CASE WHEN unit_current_status IN ('Occupied', 'Reserved') THEN 1 ELSE 0 END) AS active_units
            FROM units_table
            WHERE 1 = 1
        ";
        $params = [];

        if ($unitType !== null) {
            $sql .= " AND unit_type = ?";
            $params[] = $unitType;
        }

        $row = $this->fetchOne($sql, $params);
        $totalUnits = (int)($row['total_units'] ?? 0);
        $activeUnits = (int)($row['active_units'] ?? 0);
        $occupancyRate = $totalUnits > 0 ? round(($activeUnits / $totalUnits) * 100, 1) : 0.0;

        return [
            'total_units'    => $totalUnits,
            'active_units'   => $activeUnits,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    /**
     * Get daily maintenance requests or resolved counts array.
     */
    public function getDailyMaintenance(string $start, string $end, int $daysInMonth, ?string $unitType = null, bool $resolvedOnly = false): array {
        $counts = array_fill(1, $daysInMonth, 0);

        if ($resolvedOnly) {
            $dateExpr = "COALESCE(m.updated_at, m.submitted_at)";
            $sql = "
                SELECT DAY($dateExpr) AS day_num, COUNT(*) AS total
                FROM maintenance_requests m
                INNER JOIN units_table u ON m.unit_id = u.unit_id
                WHERE m.status = 'resolved'
                  AND $dateExpr >= ? AND $dateExpr < ?
            ";
        } else {
            $dateExpr = "m.submitted_at";
            $sql = "
                SELECT DAY($dateExpr) AS day_num, COUNT(*) AS total
                FROM maintenance_requests m
                INNER JOIN units_table u ON m.unit_id = u.unit_id
                WHERE $dateExpr >= ? AND $dateExpr < ?
            ";
        }
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        $sql .= " GROUP BY DAY($dateExpr) ORDER BY day_num";

        $rows = $this->fetchAll($sql, $params);
        foreach ($rows as $row) {
            $day = (int)$row['day_num'];
            if ($day >= 1 && $day <= $daysInMonth) {
                $counts[$day] = (int)$row['total'];
            }
        }

        return array_values($counts);
    }

    /**
     * Get maintenance trend metrics broken down per unit room.
     */
    public function getRoomMaintenanceTrends(string $start, string $end, ?string $unitType = null, int $limit = 8): array {
        $sql = "
            SELECT
                u.unit_id,
                u.unit_number,
                u.unit_type,
                u.unit_current_status,
                COALESCE(owner.full_name, 'No owner assigned') AS owner_name,
                COUNT(m.maintenance_id) AS total_requests,
                SUM(CASE WHEN m.status IN ('pending', 'in progress') THEN 1 ELSE 0 END) AS open_requests,
                SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
                SUM(CASE WHEN m.status = 'in progress' THEN 1 ELSE 0 END) AS in_progress_requests,
                SUM(CASE WHEN m.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_requests,
                SUM(CASE WHEN m.priority = 'urgent' THEN 1 ELSE 0 END) AS urgent_requests,
                MAX(m.submitted_at) AS latest_submitted_at,
                SUBSTRING_INDEX(GROUP_CONCAT(m.subject ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_issue,
                SUBSTRING_INDEX(GROUP_CONCAT(m.category ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_category,
                SUBSTRING_INDEX(GROUP_CONCAT(m.status ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_status
            FROM maintenance_requests m
            INNER JOIN units_table u ON m.unit_id = u.unit_id
            LEFT JOIN users_table owner ON owner.user_id = u.unit_owner_id
            WHERE m.submitted_at >= ? AND m.submitted_at < ?
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        $sql .= "
            GROUP BY u.unit_id, u.unit_number, u.unit_type, u.unit_current_status, owner.full_name
            ORDER BY open_requests DESC, urgent_requests DESC, total_requests DESC, latest_submitted_at DESC
            LIMIT " . (int)$limit;

        $raw = $this->fetchAll($sql, $params);
        $rows = [];

        foreach ($raw as $row) {
            $latestDate = '';
            if (!empty($row['latest_submitted_at'])) {
                $latestDate = date('M j, Y', strtotime((string)$row['latest_submitted_at']));
            }

            $openRequests = (int)($row['open_requests'] ?? 0);
            $urgentRequests = (int)($row['urgent_requests'] ?? 0);
            $attentionLevel = 'Normal';
            $attentionClass = 'bg-slate-50 text-slate-600 border-slate-100';

            if ($urgentRequests > 0) {
                $attentionLevel = 'Urgent';
                $attentionClass = 'bg-red-50 text-red-700 border-red-100';
            } elseif ($openRequests > 0) {
                $attentionLevel = 'Needs Review';
                $attentionClass = 'bg-amber-50 text-amber-700 border-amber-100';
            } elseif ((int)($row['resolved_requests'] ?? 0) > 0) {
                $attentionLevel = 'Resolved';
                $attentionClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
            }

            $rows[] = [
                'unit'           => $row['unit_number'] ?? '',
                'type'           => $row['unit_type'] ?? '',
                'owner'          => $row['owner_name'] ?? 'No owner assigned',
                'unitStatus'     => $row['unit_current_status'] ?? '',
                'total'          => (int)($row['total_requests'] ?? 0),
                'open'           => $openRequests,
                'pending'        => (int)($row['pending_requests'] ?? 0),
                'inProgress'     => (int)($row['in_progress_requests'] ?? 0),
                'resolved'       => (int)($row['resolved_requests'] ?? 0),
                'urgent'         => $urgentRequests,
                'latestIssue'    => $row['latest_issue'] ?: 'No issue recorded',
                'latestCategory' => $row['latest_category'] ?: 'N/A',
                'latestStatus'   => $row['latest_status'] ?: 'N/A',
                'latestDate'     => $latestDate,
                'attentionLevel' => $attentionLevel,
                'attentionClass' => $attentionClass,
            ];
        }

        return $rows;
    }

    /**
     * Fetch pending badges count for admin navigation.
     */
    public function getPendingCounts(): array {
        $inqSql = "SELECT COUNT(*) AS total FROM inquiry_table WHERE status = 'pending' OR approval_status = 'requested'";
        $inqRow = $this->fetchOne($inqSql);

        $resSql = "
            SELECT COUNT(*) AS total
            FROM reservation_table
            WHERE payment_status = 'pending review'
               OR reservation_status IN ('submitted', 'under review', 'requirements pending')
        ";
        $resRow = $this->fetchOne($resSql);

        return [
            'pending_inquiries'    => (int)($inqRow['total'] ?? 0),
            'pending_reservations' => (int)($resRow['total'] ?? 0),
        ];
    }

    /**
     * Fetch snapshot counts and pending admin actions for Admin Home Overview.
     */
    public function getHomeStats(): array {
        $unitsSql = "
            SELECT
                COUNT(*) AS total_units,
                COALESCE(SUM(CASE WHEN unit_current_status = 'Occupied' THEN 1 ELSE 0 END), 0) AS occupied_units,
                COALESCE(SUM(CASE WHEN unit_current_status IN ('Ready for Occupancy', 'Resale', 'On Hold') THEN 1 ELSE 0 END), 0) AS available_units
            FROM units_table
        ";
        $unitsRow = $this->fetchOne($unitsSql) ?: ['total_units' => 0, 'occupied_units' => 0, 'available_units' => 0];

        $inqSql = "SELECT COUNT(*) AS total FROM inquiry_table WHERE status = 'pending' OR status = '' OR status IS NULL";
        $inqRow = $this->fetchOne($inqSql);

        $resSql = "
            SELECT COUNT(*) AS total
            FROM reservation_table
            WHERE payment_status = 'pending review'
               OR reservation_status IN ('submitted', 'under review', 'requirements pending', 'requirements completed')
               OR cancellation_status = 'requested'
        ";
        $resRow = $this->fetchOne($resSql);

        $maintSql = "SELECT COUNT(*) AS total FROM maintenance_requests WHERE status IN ('pending', 'in progress')";
        $maintRow = $this->fetchOne($maintSql);

        $pendingInquiries = (int)($inqRow['total'] ?? 0);
        $pendingReservations = (int)($resRow['total'] ?? 0);
        $pendingMaintenance = (int)($maintRow['total'] ?? 0);

        return [
            'total_units'          => (int)($unitsRow['total_units'] ?? 0),
            'occupied_units'       => (int)($unitsRow['occupied_units'] ?? 0),
            'available_units'      => (int)($unitsRow['available_units'] ?? 0),
            'pending_inquiries'    => $pendingInquiries,
            'pending_reservations' => $pendingReservations,
            'pending_maintenance'  => $pendingMaintenance,
            'total_pending'        => $pendingInquiries + $pendingReservations + $pendingMaintenance,
        ];
    }
}
