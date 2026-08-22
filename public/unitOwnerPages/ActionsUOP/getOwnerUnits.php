<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/sync_unit_status.php';

syncExpiredUnitStatuses($conn);

if (!isset($ownerId)) {
    $user = requireRole($conn, ['unit owner']);
    $ownerId = (int)$user['user_id'];
}

function clean($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function peso($value, $isLease = false) {
    if ($value === null || $value === '' || ($isLease && (float)$value === 0)) {
        return $isLease ? '—' : '₱' . number_format(0, 2);
    }
    return '₱' . number_format((float)$value, 2);
}

function fmtDate($value) {
    if (empty($value) || $value === '0000-00-00') return '—';
    $ts = strtotime((string)$value);
    return $ts ? date('M j, Y', $ts) : '—';
}

function getFloorTitle($floorNum) {
    $floorNum = (int)$floorNum;
    $titles = [
        1 => 'First Floor',
        2 => '2nd Floor',
        3 => '3rd Floor',
        4 => '4th Floor',
        5 => '5th Floor',
        6 => '6th Floor',
        7 => '7th Floor',
        8 => '8th Floor',
        9 => '9th Floor',
        10 => '10th Floor (Penthouse)'
    ];
    return $titles[$floorNum] ?? "Floor {$floorNum}";
}

function getFloorIconBg($floorNum) {
    $bgs = [
        1 => 'bg-emerald-500/10 text-emerald-600 border-emerald-200',
        2 => 'bg-blue-500/10 text-blue-600 border-blue-200',
        3 => 'bg-indigo-500/10 text-indigo-600 border-indigo-200',
        4 => 'bg-violet-500/10 text-violet-600 border-violet-200',
        5 => 'bg-purple-500/10 text-purple-600 border-purple-200',
        6 => 'bg-amber-500/10 text-amber-600 border-amber-200',
        7 => 'bg-orange-500/10 text-orange-600 border-orange-200',
        8 => 'bg-cyan-500/10 text-cyan-600 border-cyan-200',
        9 => 'bg-teal-500/10 text-teal-600 border-teal-200',
        10 => 'bg-rose-500/10 text-rose-600 border-rose-200'
    ];
    return $bgs[$floorNum] ?? 'bg-slate-100 text-slate-700 border-slate-200';
}

// Fetch only units assigned to this owner ordered by floor and unit number
$stmt = $conn->prepare("
    SELECT 
        u.unit_id,
        u.unit_number,
        u.unit_type,
        u.floor_number,
        u.lease_rate,
        u.unit_owner_id,
        u.unit_current_status,
        u.created_at,
        uo.full_name AS unit_owner_name,
        uo.email AS unit_owner_email,
        (SELECT r.client_name
         FROM reservation_table r
         WHERE r.unit_id = u.unit_id
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL)
         ORDER BY r.reservation_id DESC
         LIMIT 1) AS tenant_name,
        (SELECT r.client_contact
         FROM reservation_table r
         WHERE r.unit_id = u.unit_id
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL)
         ORDER BY r.reservation_id DESC
         LIMIT 1) AS tenant_contact,
        (SELECT r.client_email
         FROM reservation_table r
         WHERE r.unit_id = u.unit_id
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL)
         ORDER BY r.reservation_id DESC
         LIMIT 1) AS tenant_email,
        (SELECT r.move_in_date
         FROM reservation_table r
         WHERE r.unit_id = u.unit_id
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL)
         ORDER BY r.reservation_id DESC
         LIMIT 1) AS move_in_date,
        (SELECT r.move_out_date
         FROM reservation_table r
         WHERE r.unit_id = u.unit_id
           AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL)
         ORDER BY r.reservation_id DESC
         LIMIT 1) AS move_out_date
    FROM units_table u
    LEFT JOIN users_table uo ON u.unit_owner_id = uo.user_id
    WHERE u.unit_owner_id = ?
    ORDER BY u.floor_number ASC, u.unit_number ASC
");

$unitsByFloor = [];
$totalUnitsCount = 0;

if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $floor = (int)($row['floor_number'] ?: 1);
        if (!isset($unitsByFloor[$floor])) {
            $unitsByFloor[$floor] = [];
        }
        $unitsByFloor[$floor][] = $row;
        $totalUnitsCount++;
    }
    $stmt->close();
}

if (empty($unitsByFloor)) {
    echo "
    <div class='bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm'>
        <div class='w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-400'>
            <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4'/></svg>
        </div>
        <h3 class='text-base font-bold text-slate-800'>No units found</h3>
        <p class='text-xs text-slate-400 mt-1'>No units are currently registered or assigned under your account.</p>
    </div>";
    return;
}

// Render each floor section as a card/block
foreach ($unitsByFloor as $floorNum => $units) {
    $floorTitle = getFloorTitle($floorNum);
    $floorCount = count($units);
    $badgeStyle = getFloorIconBg($floorNum);

    // Compute floor summary metadata
    $typesMap = [];
    $occupiedCount = 0;
    $availableCount = 0;
    $resaleCount = 0;
    $reservedCount = 0;

    foreach ($units as $u) {
        $typeName = $u['unit_type'] ?: 'Unit';
        $typesMap[$typeName] = ($typesMap[$typeName] ?? 0) + 1;

        $st = strtolower(trim($u['unit_current_status']));
        if ($st === 'occupied') $occupiedCount++;
        elseif ($st === 'ready for occupancy') $availableCount++;
        elseif ($st === 'resale') $resaleCount++;
        elseif ($st === 'reserved') $reservedCount++;
    }

    $typeSummaryList = [];
    foreach ($typesMap as $tName => $cnt) {
        $typeSummaryList[] = "{$cnt} {$tName}";
    }
    $typeSummaryString = implode(' • ', $typeSummaryList);
    ?>

    <div class="floor-section bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md mb-6" data-floor="<?= $floorNum ?>">
        
        <!-- Floor Header -->
        <div class="floor-header px-6 py-4 border-b border-slate-100/90 bg-gradient-to-r from-slate-50/90 via-white to-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl <?= $badgeStyle ?> flex items-center justify-center font-bold text-base shadow-sm shrink-0 border">
                    <?= $floorNum ?>F
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-base font-bold text-slate-900 leading-tight"><?= clean($floorTitle) ?></h2>
                        <span class="floor-unit-badge text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-mono">
                            <?= $floorCount ?> <?= $floorCount === 1 ? 'unit' : 'units' ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                        <span><?= clean($typeSummaryString) ?></span>
                        <?php if ($availableCount > 0): ?>
                            <span class="text-slate-300">•</span>
                            <span class="text-emerald-600 font-medium"><?= $availableCount ?> Available</span>
                        <?php endif; ?>
                        <?php if ($occupiedCount > 0): ?>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-600 font-medium"><?= $occupiedCount ?> Occupied</span>
                        <?php endif; ?>
                        <?php if ($resaleCount > 0): ?>
                            <span class="text-slate-300">•</span>
                            <span class="text-blue-600 font-medium"><?= $resaleCount ?> Resale</span>
                        <?php endif; ?>
                        <?php if ($reservedCount > 0): ?>
                            <span class="text-slate-300">•</span>
                            <span class="text-amber-600 font-medium"><?= $reservedCount ?> Reserved</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-slate-400 font-mono hidden md:inline-block">Floor #<?= str_pad($floorNum, 2, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Floor Units Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="text-left px-5 py-3 whitespace-nowrap">Unit</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Tenant</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Lease Rate</th>
                        <th class="text-right px-5 py-3 w-[100px] whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($units as $row): 
                        $unit_id = clean($row['unit_id']);
                        $unit_number = clean($row['unit_number']);
                        $unit_type = clean($row['unit_type']);
                        $unit_current_status = clean($row['unit_current_status']);
                        $tenant_name = clean($row['tenant_name'] ?: 'No Tenant');
                        $tenant_contact = clean($row['tenant_contact'] ?: '—');
                        $tenant_email = clean($row['tenant_email'] ?: '—');
                        $move_in_date = fmtDate($row['move_in_date']);
                        $move_out_date = fmtDate($row['move_out_date']);

                        // Status Badge Colors
                        $status_lower = strtolower(trim($unit_current_status));
                        if ($status_lower === 'ready for occupancy') {
                            $status_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $dot_class = 'bg-emerald-500';
                        } elseif ($status_lower === 'on hold') {
                            $status_class = 'bg-amber-50 text-amber-700 border-amber-200';
                            $dot_class = 'bg-amber-500';
                        } elseif ($status_lower === 'resale') {
                            $status_class = 'bg-blue-50 text-blue-700 border-blue-200';
                            $dot_class = 'bg-blue-500';
                        } elseif ($status_lower === 'reserved') {
                            $status_class = 'bg-red-50 text-red-700 border-red-200';
                            $dot_class = 'bg-red-500';
                        } elseif ($status_lower === 'occupied') {
                            $status_class = 'bg-rose-50 text-rose-700 border-rose-200';
                            $dot_class = 'bg-rose-500';
                        } elseif ($status_lower === 'under maintenance') {
                            $status_class = 'bg-orange-50 text-orange-700 border-orange-200';
                            $dot_class = 'bg-orange-500';
                        } else {
                            $status_class = 'bg-slate-50 text-slate-700 border-slate-200';
                            $dot_class = 'bg-slate-400';
                        }

                        $price_value = peso($row['lease_rate'], true);
                    ?>
                    <tr class="unit-row hover:bg-slate-50/80 transition-colors"
                        data-unit-id="<?= $unit_id ?>"
                        data-unit-number="<?= $unit_number ?>"
                        data-unit-type="<?= $unit_type ?>"
                        data-floor-number="<?= $floorNum ?>"
                        data-floor-title="<?= clean($floorTitle) ?>"
                        data-lease-rate="<?= peso($row['lease_rate']) ?>"
                        data-unit-current-status="<?= $unit_current_status ?>"
                        data-status-class="<?= $status_class ?>"
                        data-dot-class="<?= $dot_class ?>"
                        data-tenant-name="<?= $tenant_name ?>"
                        data-tenant-contact="<?= $tenant_contact ?>"
                        data-tenant-email="<?= $tenant_email ?>"
                        data-move-in="<?= $move_in_date ?>"
                        data-move-out="<?= $move_out_date ?>"
                        data-search-text="<?= strtolower("{$unit_number} {$unit_type} {$floorTitle} Floor {$floorNum} {$unit_current_status} {$tenant_name}") ?>">
                        
                        <!-- Unit Number & Type (Clean without duplicate unit badge) -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div>
                                <p class="unit-num font-semibold text-slate-900 text-sm leading-tight"><?= $unit_number ?></p>
                                <p class="text-xs text-slate-400 mt-0.5"><?= $unit_type ?></p>
                            </div>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border <?= $status_class ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $dot_class ?>"></span>
                                <?= $unit_current_status ?>
                            </span>
                        </td>

                        <!-- Tenant -->
                        <td class="px-4 py-3.5 whitespace-nowrap text-xs font-medium <?= $tenant_name === 'No Tenant' ? 'text-slate-400' : 'text-slate-700' ?>">
                            <?= $tenant_name ?>
                        </td>

                        <!-- Lease Rate -->
                        <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap font-mono text-xs font-medium">
                            <?= $price_value ?>
                        </td>

                        <!-- Action -->
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <button 
                                type="button"
                                class="view-btn btn-press inline-flex items-center justify-center text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 px-3 py-1.5 rounded-lg active:scale-95 transition-all shadow-xs"
                                onclick="openUnitModalFromRow(this.closest('tr'))">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>
