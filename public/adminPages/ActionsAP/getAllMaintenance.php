<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

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

// Fetch all distinct unit types for filter dropdown
$unitTypesSql = "SELECT DISTINCT unit_type FROM units_table WHERE unit_type IS NOT NULL AND TRIM(unit_type) != '' ORDER BY unit_type ASC";
$unitTypesRes = $conn->query($unitTypesSql);
$unitTypeOptions = [];
if ($unitTypesRes && $unitTypesRes->num_rows > 0) {
    while ($ut = $unitTypesRes->fetch_assoc()) {
        $cleanType = trim($ut['unit_type']);
        if ($cleanType !== '') {
            $unitTypeOptions[] = $cleanType;
        }
    }
}

// Fetch all maintenance tickets
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
        (SELECT r.client_name 
         FROM reservation_table r 
         WHERE r.unit_id = u.unit_id 
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL) 
         ORDER BY r.reservation_id DESC LIMIT 1) AS tenant_name
    FROM maintenance_requests m
    LEFT JOIN units_table u ON m.unit_id = u.unit_id
    LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
    ORDER BY m.submitted_at DESC
";
$ticketsRes = $conn->query($ticketsSql);

$activeTickets = [];
$unassignedTickets = [];
$closedTickets = [];
$totalTicketsCount = 0;

if ($ticketsRes && $ticketsRes->num_rows > 0) {
    while ($row = $ticketsRes->fetch_assoc()) {
        $totalTicketsCount++;
        $st = strtolower(trim($row['status'] ?? 'pending'));
        if ($st === 'in progress') {
            $activeTickets[] = $row;
        } elseif ($st === 'pending') {
            $unassignedTickets[] = $row;
        } else {
            // resolved or cancelled
            $closedTickets[] = $row;
        }
    }
}

$activeCount = count($activeTickets);
$unassignedCount = count($unassignedTickets);
$closedCount = count($closedTickets);

if (!function_exists('renderTicketCard')) {
    function renderTicketCard($row) {
        $maintenanceId = (int)$row['maintenance_id'];
        $mrNumber = 'MR-' . str_pad($maintenanceId, 4, '0', STR_PAD_LEFT);
        $unitNumber = $row['unit_number'] ?? '—';
        $unitType = $row['unit_type'] ?? '';
        $floorNumber = $row['floor_number'] ? (int)$row['floor_number'] : 1;
        $floorTitle = getFloorTitle($floorNumber);

        // Display unit name and floor subtitle
        $unitHeading = $unitNumber !== '—' ? ("Unit {$unitNumber}" . ($unitType ? " — {$unitType}" : "")) : "Unit Not Assigned";
        $floorSubtitle = $floorTitle;

        $ownerName = $row['owner_name'] ?: 'No Owner';
        $ownerEmail = $row['owner_email'] ?: '—';
        $tenantName = $row['tenant_name'] ?: '';

        // Requested by: Tenant or Unit Owner
        $submittedRole = strtolower(trim($row['submitted_by_role'] ?? ''));
        if ($submittedRole === 'unit owner' || $submittedRole === 'owner') {
            $requestedByRole = 'Unit Owner';
            $personName = $ownerName;
        } elseif ($submittedRole === 'tenant') {
            $requestedByRole = 'Tenant';
            $personName = $tenantName ?: 'Tenant';
        } else {
            // fallback
            if (!empty($row['unit_owner_id']) && !empty($row['submitted_by_user_id']) && $row['submitted_by_user_id'] == $row['unit_owner_id']) {
                $requestedByRole = 'Unit Owner';
                $personName = $ownerName;
            } elseif (!empty($tenantName)) {
                $requestedByRole = 'Tenant';
                $personName = $tenantName;
            } else {
                $requestedByRole = 'Unit Owner';
                $personName = $ownerName;
            }
        }

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

        // Priority Styling (Matching Image with flag icon)
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
             data-tenant-name="' . clean($tenantName) . '"
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
             data-search-text="' . strtolower("{$mrNumber} {$unitNumber} {$unitType} {$floorTitle} {$subject} {$category} {$priority} {$requestedByRole} {$personName} {$ownerName} {$tenantName} {$status}") . '">
            
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
                        <span class="text-slate-400 font-normal">Requested by</span>
                        <span class="font-medium text-slate-700 truncate max-w-[130px] text-right">' . clean($requestedByRole) . '</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Person</span>
                        <span class="underline underline-offset-2 decoration-slate-300 font-medium text-slate-800 truncate max-w-[130px] text-right hover:text-blue-600 transition-colors">' . clean($personName) . '</span>
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
