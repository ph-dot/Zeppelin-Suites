<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = requireRole($conn, ['tenant']);
$tenantId = (int)$user['user_id'];

if (!function_exists('clean')) {
    function clean($val) {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getFloorTitle')) {
    function getFloorTitle($floorNum) {
        $floorNum = (int)$floorNum;
        $titles = [
            1 => 'First floor',
            2 => '2nd floor',
            3 => '3rd floor',
            4 => '4th floor',
            5 => '5th floor',
            6 => '6th floor',
            7 => '7th floor',
            8 => '8th floor',
            9 => '9th floor',
            10 => '10th floor (Penthouse)'
        ];
        return $titles[$floorNum] ?? "Floor {$floorNum}";
    }
}

// Fetch Tenant user info
$stmt = $conn->prepare("SELECT * FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$tenantUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tenantName = $tenantUser['full_name'] ?? $user['full_name'];
$tenantEmail = $tenantUser['email'] ?? '';
$tenantInitials = strtoupper(substr(trim($tenantName ?: 'T'), 0, 1));

// Fetch units assigned to this tenant for the Create Maintenance modal
$tenantUnitsSql = "
    SELECT DISTINCT u.unit_id, u.unit_number, u.unit_type, u.floor_number, u.unit_owner_id,
           owner.full_name AS owner_name
    FROM units_table u
    INNER JOIN reservation_table r ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    WHERE r.client_email = ? OR r.client_name = ?
    ORDER BY u.unit_number ASC
";
$tuStmt = $conn->prepare($tenantUnitsSql);
$tenantUnitsList = [];
if ($tuStmt) {
    $tuStmt->bind_param("ss", $tenantEmail, $tenantName);
    $tuStmt->execute();
    $tuRes = $tuStmt->get_result();
    while ($u = $tuRes->fetch_assoc()) {
        $tenantUnitsList[] = $u;
    }
    $tuStmt->close();
}

// If no direct reservation units found, allow fallback or check any available unit
if (empty($tenantUnitsList)) {
    // Check if user has unit from reservations regardless of status
    $resCheck = $conn->prepare("SELECT u.unit_id, u.unit_number, u.unit_type, u.floor_number, u.unit_owner_id, owner.full_name AS owner_name FROM units_table u LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id LIMIT 1");
    if ($resCheck) {
        $resCheck->execute();
        $rc = $resCheck->get_result()->fetch_assoc();
        if ($rc) {
            $tenantUnitsList[] = $rc;
        }
        $resCheck->close();
    }
}

// Fetch maintenance tickets strictly submitted by this tenant
$ticketsSql = "
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
        ? AS tenant_name
    FROM maintenance_requests m
    INNER JOIN units_table u ON m.unit_id = u.unit_id
    LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
    WHERE m.submitted_by_user_id = ?
    ORDER BY m.submitted_at DESC
";
$tStmt = $conn->prepare($ticketsSql);
$activeTickets = [];
$unassignedTickets = [];
$closedTickets = [];
$totalTicketsCount = 0;

if ($tStmt) {
    $tStmt->bind_param("si", $tenantName, $tenantId);
    $tStmt->execute();
    $ticketsRes = $tStmt->get_result();

    if ($ticketsRes && $ticketsRes->num_rows > 0) {
        while ($row = $ticketsRes->fetch_assoc()) {
            $totalTicketsCount++;
            $st = strtolower(trim($row['status'] ?? 'pending'));
            if ($st === 'in progress') {
                $activeTickets[] = $row;
            } elseif ($st === 'pending') {
                $unassignedTickets[] = $row;
            } else {
                $closedTickets[] = $row;
            }
        }
    }
    $tStmt->close();
}

$activeCount = count($activeTickets);
$unassignedCount = count($unassignedTickets);
$closedCount = count($closedTickets);

if (!function_exists('renderTenantTicketCard')) {
    function renderTenantTicketCard($row) {
        $maintenanceId = (int)$row['maintenance_id'];
        $mrNumber = 'MR-' . str_pad($maintenanceId, 4, '0', STR_PAD_LEFT);
        $unitNumber = $row['unit_number'] ?? '—';
        $unitType = $row['unit_type'] ?? '';
        $floorNumber = $row['floor_number'] ? (int)$row['floor_number'] : 1;
        $floorTitle = getFloorTitle($floorNumber);

        $unitHeading = $unitNumber !== '—' ? ("Unit {$unitNumber}" . ($unitType ? " — {$unitType}" : "")) : "Unit Not Assigned";
        $floorSubtitle = $floorTitle;

        $ownerName = $row['owner_name'] ?: 'Zeppelin Suites Management';
        $ownerEmail = $row['owner_email'] ?: '—';
        $personName = $row['tenant_name'] ?: 'Tenant';
        $requestedByRole = 'Tenant';

        $subject = $row['subject'] ?: 'Maintenance Issue';
        $category = $row['category'] ?: 'General';
        $priority = strtolower(trim($row['priority'] ?: 'normal'));
        $status = strtolower(trim($row['status'] ?: 'pending'));
        $description = $row['description'] ?: '';
        $remarks = $row['admin_remarks'] ?: '';

        $submittedRaw = $row['submitted_at'] ?? '';
        $submittedDateFormatted = !empty($submittedRaw) ? date('d M, Y', strtotime($submittedRaw)) : '—';
        $submittedFullFormatted = !empty($submittedRaw) ? date('M d, Y h:i A', strtotime($submittedRaw)) : '—';
        $resolvedDateFormatted = !empty($row['resolved_at']) ? date('M d, Y h:i A', strtotime($row['resolved_at'])) : 'Not yet resolved';

        // Priority Styling
        if ($priority === 'urgent' || $priority === 'high') {
            $priorityBadgeClass = 'bg-rose-50 text-rose-700 border border-rose-200/80';
            $priorityFlagColor = 'text-rose-600';
            $priorityLabel = 'High';
        } elseif ($priority === 'normal' || $priority === 'medium') {
            $priorityBadgeClass = 'bg-amber-50 text-amber-700 border border-amber-200/80';
            $priorityFlagColor = 'text-amber-600';
            $priorityLabel = 'Medium';
        } else {
            $priorityBadgeClass = 'bg-slate-100 text-slate-700 border border-slate-200/80';
            $priorityFlagColor = 'text-slate-500';
            $priorityLabel = 'Low';
        }

        // Photo URLs
        $photoPaths = [];
        if (!empty($row['photo_paths'])) {
            $savedPhotos = explode(',', $row['photo_paths']);
            foreach ($savedPhotos as $photo) {
                $photo = str_replace('\\/', '/', $photo);
                $photo = trim($photo, " \t\n\r\0\x0B[]\"'");
                if (strpos($photo, 'uploads/maintenance/') === 0) {
                    $photoPaths[] = '../' . ltrim($photo, '/');
                }
            }
        }
        $photoData = clean(implode('|', $photoPaths));

        $colGroup = 'closed';
        if ($status === 'in progress') $colGroup = 'active';
        elseif ($status === 'pending') $colGroup = 'unassigned';

        echo '
        <div class="ticket-card bg-white rounded-xl border border-slate-200/90 p-4 sm:p-5 shadow-xs transition-all duration-200 cursor-pointer space-y-3 hover:border-slate-400 hover:shadow-sm"
             onclick="openMaintenanceModalFromCard(this)"
             data-maintenance-id="' . $maintenanceId . '"
             data-mr="' . clean($mrNumber) . '"
             data-unit-id="' . clean($row['unit_id']) . '"
             data-unit-number="' . clean($unitNumber) . '"
             data-unit-type="' . clean($unitType) . '"
             data-unit="' . clean($unitHeading) . '"
             data-floor="' . $floorNumber . '"
             data-floor-title="' . clean($floorTitle) . '"
             data-owner-name="' . clean($ownerName) . '"
             data-owner-email="' . clean($ownerEmail) . '"
             data-tenant-name="' . clean($personName) . '"
             data-requested-by="' . clean($requestedByRole) . '"
             data-person-name="' . clean($personName) . '"
             data-subject="' . clean($subject) . '"
             data-category="' . clean($category) . '"
             data-priority="' . clean($priority) . '"
             data-description="' . clean($description) . '"
             data-status="' . clean($status) . '"
             data-col-group="' . $colGroup . '"
             data-admin-remarks="' . clean($remarks) . '"
             data-submitted-at="' . clean($submittedFullFormatted) . '"
             data-submitted-raw="' . clean($submittedRaw) . '"
             data-resolved-at="' . clean($resolvedDateFormatted) . '"
             data-photos="' . $photoData . '"
             data-search-text="' . strtolower("{$mrNumber} {$unitNumber} {$unitType} {$floorTitle} {$subject} {$category} {$priority} {$requestedByRole} {$personName} {$ownerName} {$status}") . '">
            
            <!-- Unit Header -->
            <div>
                <h3 class="font-bold text-slate-900 text-sm leading-snug">' . clean($unitHeading) . '</h3>
                <p class="text-xs text-slate-400 font-normal mt-0.5">' . clean($floorSubtitle) . '</p>
            </div>

            <!-- Priority Flag & Date Line -->
            <div class="flex items-center justify-between gap-2 pt-0.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-semibold ' . $priorityBadgeClass . '">
                    <svg class="w-3.5 h-3.5 ' . $priorityFlagColor . '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/></svg>
                    ' . $priorityLabel . '
                </span>

                <span class="text-xs text-slate-400 font-normal flex items-center gap-1 shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ' . $submittedDateFormatted . '
                </span>
            </div>

            <!-- Inner Summary Box -->
            <div class="bg-slate-50/90 rounded-xl p-3.5 border border-slate-100 space-y-2">
                <p class="text-xs font-semibold text-slate-800 leading-snug line-clamp-2">
                    ' . clean($subject) . '
                </p>

                <div class="space-y-1.5 text-xs pt-1">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Ticket ID</span>
                        <span class="font-mono font-medium text-slate-700">' . clean($mrNumber) . '</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Category</span>
                        <span class="font-medium text-slate-700">' . clean($category) . '</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Status</span>
                        <span class="font-medium text-slate-800 capitalize">' . clean($status) . '</span>
                    </div>
                </div>
            </div>

            <!-- Card Bottom (Remark Indicator & See Details) -->
            <div class="flex items-center justify-between pt-0.5 min-h-[22px]">
                <div>
                    ' . (!empty(trim((string)$remarks)) ? '
                    <div class="inline-flex items-center gap-1.5 text-slate-400 font-medium" title="Admin remarks: ' . clean($remarks) . '">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h4m8-2a9 9 0 11-18 0 9 9 0 0118 0c0 1.508.372 2.93 1.026 4.175L3 21l4.825-1.026A8.962 8.962 0 0012 21z"/>
                        </svg>
                        <span class="text-[11px] font-semibold text-slate-500 font-mono">1</span>
                    </div>' : '') . '
                </div>

                <button type="button" 
                        onclick="event.stopPropagation(); openMaintenanceModalFromCard(this.closest(\'.ticket-card\'))" 
                        class="text-xs font-medium text-slate-500 hover:text-slate-900 group-hover:text-blue-600 transition-colors inline-flex items-center gap-1">
                    <span>See details</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

        </div>';
    }
}
?>
