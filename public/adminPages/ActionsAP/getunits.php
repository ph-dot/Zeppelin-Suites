<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/sync_unit_status.php';

syncExpiredUnitStatuses($conn);

if (!function_exists('clean')) {
    function clean($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($value, $isLease = false) {
        if ($value === null || $value === '' || ($isLease && (float)$value === 0)) {
            return $isLease ? '—' : '₱0.00';
        }
        return '₱' . number_format((float)$value, 2);
    }
}

if (!function_exists('fmtDate')) {
    function fmtDate($value) {
        if (empty($value) || $value === '0000-00-00') return '—';
        $ts = strtotime((string)$value);
        return $ts ? date('M j, Y', $ts) : '—';
    }
}

if (!function_exists('getFloorTitle')) {
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
}

if (!function_exists('getFloorIconBg')) {
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
}

if (!function_exists('getUnitAvailabilityInfo')) {
    function getUnitAvailabilityInfo($conn, $unitId, $unitCurrentStatus, $currentMoveOutDate) {
        $statusLower = strtolower(trim((string)$unitCurrentStatus));
        if ($statusLower === 'resale') {
            return [
                'range' => 'Available for Resale',
                'duration' => 'Ready for purchase'
            ];
        }
        if ($statusLower === 'under maintenance') {
            return [
                'range' => 'Under Maintenance',
                'duration' => 'Temporarily unavailable'
            ];
        }
        if ($statusLower === 'on hold') {
            return [
                'range' => 'On Hold',
                'duration' => 'Listing paused'
            ];
        }

        $todayTs = strtotime('today');
        $startDate = null;

        if (!empty($currentMoveOutDate) && $currentMoveOutDate !== '0000-00-00' && strtotime($currentMoveOutDate) >= $todayTs) {
            $startDate = new DateTime($currentMoveOutDate);
            $startDate->modify('+1 day');
            $startStr = $startDate->format('M j, Y');
        } else {
            $startDate = new DateTime('today');
            $startStr = 'Now';
        }

        $startDateFormatted = $startDate->format('M j, Y');
        $maxEndDate = (clone $startDate)->modify('+2 years');
        $effectiveEndDate = $maxEndDate;
        $durationLabel = 'Duration: 2 Years (Latest)';

        $startDateSql = $startDate->format('Y-m-d');
        $stmtNext = $conn->prepare("
            SELECT move_in_date 
            FROM reservation_table 
            WHERE unit_id = ? 
              AND move_in_date >= ? 
              AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY move_in_date ASC 
            LIMIT 1
        ");
        if ($stmtNext) {
            $stmtNext->bind_param('is', $unitId, $startDateSql);
            $stmtNext->execute();
            $resNext = $stmtNext->get_result();
            if ($resNext && $nextRow = $resNext->fetch_assoc()) {
                $nextMoveIn = new DateTime($nextRow['move_in_date']);
                if ($nextMoveIn < $maxEndDate) {
                    $effectiveEndDate = $nextMoveIn;
                    $diff = $startDate->diff($nextMoveIn);
                    $parts = [];
                    if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'yr' : 'yrs');
                    if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'mo' : 'mos');
                    if ($diff->d > 0 && empty($parts)) $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
                    $durationLabel = 'Duration: ' . (!empty($parts) ? implode(' ', $parts) : '1 mo');
                }
            }
            $stmtNext->close();
        }

        $endStr = $effectiveEndDate->format('M j, Y');
        $rangeText = "Avail: {$startStr} – {$endStr}";

        return [
            'range' => $rangeText,
            'duration' => $durationLabel,
            'start_date' => $startDateFormatted,
            'end_date' => $endStr
        ];
    }
}

if (!function_exists('getStayDurationText')) {
    function getStayDurationText($start, $end) {
        if (empty($start) || empty($end) || $start === '0000-00-00' || $end === '0000-00-00') {
            return '';
        }
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $diff = $d1->diff($d2);

        $parts = [];
        if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'yr' : 'yrs');
        if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'mo' : 'mos');
        if ($diff->d > 0 && empty($parts)) $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
        return !empty($parts) ? implode(' ', $parts) : '1 mo';
    }
}

// Fetch all units ordered by floor and unit number
$sql = "SELECT 
            u.unit_id,
            u.unit_number,
            u.unit_type,
            u.sqm,
            u.floor_number,
            u.lease_rate,
            u.listing_type,
            u.stay_category,
            u.unit_owner_id,
            u.unit_current_status,
            u.created_at,
            uo.full_name AS unit_owner_name,
            uo.email AS unit_owner_email,
            (SELECT r.client_name
             FROM reservation_table r
             WHERE r.unit_id = u.unit_id
               AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
             ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
             LIMIT 1) AS tenant_name,
            (SELECT r.client_contact
             FROM reservation_table r
             WHERE r.unit_id = u.unit_id
               AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
             ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
             LIMIT 1) AS tenant_contact,
            (SELECT r.client_email
             FROM reservation_table r
             WHERE r.unit_id = u.unit_id
               AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
             ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
             LIMIT 1) AS tenant_email,
            (SELECT r.move_in_date
             FROM reservation_table r
             WHERE r.unit_id = u.unit_id
               AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
             ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
             LIMIT 1) AS move_in_date,
            (SELECT r.move_out_date
             FROM reservation_table r
             WHERE r.unit_id = u.unit_id
               AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
             ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
             LIMIT 1) AS move_out_date
        FROM units_table u
        LEFT JOIN users_table uo ON u.unit_owner_id = uo.user_id
        ORDER BY u.floor_number ASC, u.unit_number ASC";

$result = $conn->query($sql);

$unitsByFloor = [];
$totalUnitsCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $floor = (int)($row['floor_number'] ?: 1);
        if (!isset($unitsByFloor[$floor])) {
            $unitsByFloor[$floor] = [];
        }
        $unitsByFloor[$floor][] = $row;
        $totalUnitsCount++;
    }
}

if (empty($unitsByFloor)) {
    echo "
    <div class='bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm'>
        <div class='w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-400'>
            <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4'/></svg>
        </div>
        <h3 class='text-base font-bold text-slate-800'>No units found</h3>
        <p class='text-xs text-slate-400 mt-1'>Click '+ Add a Unit' to register the first unit in the building.</p>
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
                    <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="text-left px-5 py-3.5 whitespace-nowrap">UNIT</th>
                        <th class="text-left px-4 py-3.5 whitespace-nowrap">LISTING</th>
                        <th class="text-left px-4 py-3.5 whitespace-nowrap">STATUS</th>
                        <th class="text-left px-4 py-3.5 whitespace-nowrap">TENANT & STAY</th>
                        <th class="text-left px-4 py-3.5 whitespace-nowrap">RATE & TERM</th>
                        <th class="text-right px-5 py-3.5 whitespace-nowrap">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($units as $row): 
                        $unit_id = clean($row['unit_id']);
                        $unit_number = clean($row['unit_number']);
                        $unit_type = clean($row['unit_type']);
                        $sqm = (float)($row['sqm'] ?? 0);
                        $sqm_formatted = number_format($sqm, 2);
                        $unit_owner_id = clean($row['unit_owner_id']);
                        $unit_current_status = clean($row['unit_current_status']);
                        $unit_owner_name = clean($row['unit_owner_name'] ?: 'No owner');
                        $unit_owner_email = clean($row['unit_owner_email'] ?? '');
                        $tenant_name = clean($row['tenant_name'] ?: 'No Tenant');
                        $tenant_contact = clean($row['tenant_contact'] ?: '—');
                        $tenant_email = clean($row['tenant_email'] ?: '—');
                        $move_in_date = fmtDate($row['move_in_date'] ?? '');
                        $move_out_date = fmtDate($row['move_out_date'] ?? '');

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

                        // Availability calculation with 2-year duration horizon
                        $avail_info = getUnitAvailabilityInfo($conn, (int)$row['unit_id'], $unit_current_status, $row['move_out_date'] ?? null);

                        // Tenant & Stay
                        $hasTenant = (!empty($row['tenant_name']) && $row['tenant_name'] !== 'No Tenant');
                        $stay_dates_text = '';
                        if ($hasTenant) {
                            if (!empty($row['move_in_date']) && !empty($row['move_out_date']) && $row['move_in_date'] !== '0000-00-00' && $row['move_out_date'] !== '0000-00-00') {
                                $inTs = strtotime($row['move_in_date']);
                                $outTs = strtotime($row['move_out_date']);
                                $durStr = getStayDurationText($row['move_in_date'], $row['move_out_date']);
                                $stay_dates_text = date('M j', $inTs) . ' – ' . date('M j, Y', $outTs) . ($durStr ? " ({$durStr})" : "");
                            } else {
                                $stay_dates_text = '— Active stay';
                            }
                        }

                        // Rate & Term
                        $price_value = peso($row['lease_rate'], true);
                        $stay_cat = strtolower(trim($row['stay_category'] ?? 'long term'));
                        if ($stay_cat === 'short term') {
                            $term_badge_label = 'Flexible term';
                            $term_badge_class = 'bg-purple-100 text-purple-700 border-purple-200';
                        } else {
                            $term_badge_label = 'Long term';
                            $term_badge_class = 'bg-sky-100 text-sky-700 border-sky-200';
                        }

                        // Listing badge
                        $listing_type = strtolower(trim($row['listing_type'] ?? 'for lease'));
                        if ($listing_type === 'resale' || $status_lower === 'resale') {
                            $listing_badge_html = '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200">Resale</span>';
                        } else {
                            $listing_badge_html = '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200">For Lease</span>';
                        }
                    ?>
                    <tr class="unit-row hover:bg-slate-50/80 transition-colors"
                        data-unit-id="<?= $unit_id ?>"
                        data-unit-number="<?= $unit_number ?>"
                        data-unit-type="<?= $unit_type ?>"
                        data-sqm="<?= $sqm_formatted ?>"
                        data-floor-number="<?= $floorNum ?>"
                        data-floor-title="<?= clean($floorTitle) ?>"
                        data-lease-rate="<?= peso($row['lease_rate']) ?>"
                        data-unit-current-status="<?= $unit_current_status ?>"
                        data-listing-type="<?= $listing_type ?>"
                        data-tenant-name="<?= $tenant_name ?>"
                        data-tenant-contact="<?= $tenant_contact ?>"
                        data-tenant-email="<?= $tenant_email ?>"
                        data-unit-owner-id="<?= $unit_owner_id ?>"
                        data-owner-name="<?= $unit_owner_name ?>"
                        data-search-text="<?= strtolower("{$unit_number} {$unit_type} {$sqm_formatted} sqm {$floorTitle} Floor {$floorNum} {$listing_type} {$unit_current_status} {$unit_owner_name} {$unit_owner_email} {$tenant_name}") ?>">
                        
                        <!-- 1. UNIT -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div>
                                <p class="unit-num font-bold text-slate-900 text-base leading-tight"><?= $unit_number ?></p>
                                <p class="text-xs text-slate-500 mt-0.5"><?= $unit_type ?> <span class="text-slate-300">•</span> <span class="font-semibold text-slate-700"><?= $sqm_formatted ?> SQM</span></p>
                            </div>
                        </td>

                        <!-- 2. LISTING -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?= $listing_badge_html ?>
                        </td>

                        <!-- 3. STATUS -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div>
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border <?= $status_class ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $dot_class ?>"></span>
                                    <?= $unit_current_status ?>
                                </span>
                                <p class="text-xs text-slate-700 mt-1.5 font-medium leading-tight"><?= clean($avail_info['range']) ?></p>
                                <p class="text-[11px] text-slate-400 mt-0.5"><?= clean($avail_info['duration']) ?></p>
                            </div>
                        </td>

                        <!-- 4. TENANT & STAY -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div>
                                <?php if ($hasTenant): ?>
                                    <p class="font-bold text-slate-900 text-sm leading-snug"><?= $tenant_name ?></p>
                                    <p class="text-xs text-slate-500 mt-1 font-normal"><?= clean($stay_dates_text) ?></p>
                                <?php else: ?>
                                    <p class="italic text-sm text-slate-500 font-medium leading-tight">No active tenant</p>
                                    <p class="text-xs text-slate-400 mt-1">— Vacant</p>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 5. RATE & TERM -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div>
                                <p class="font-bold text-slate-900 font-mono text-sm leading-tight">
                                    <?= $price_value ?> <span class="font-sans text-xs font-normal text-slate-500">/mo</span>
                                </p>
                                <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-md border <?= $term_badge_class ?> mt-1.5">
                                    <?= $term_badge_label ?>
                                </span>
                            </div>
                        </td>

                        <!-- 6. ACTIONS -->
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <a 
                                href="unitDetails.php?unit_id=<?= $unit_id ?>"
                                class="view-btn btn-press inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-800 bg-white border border-slate-300 hover:bg-slate-50 px-3.5 py-1.5 rounded-lg active:scale-95 transition-all shadow-xs">
                                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>View</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>