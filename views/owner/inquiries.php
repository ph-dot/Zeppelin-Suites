<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Owner: Inquiries / Approval Requests View
 * Pure MVC presentation template. Zero direct SQL queries.
 */
if (!function_exists('clean')) {
    function clean($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($value): string {
        return '₱' . number_format((float)$value, 2);
    }
}

if (!function_exists('titleStatus')) {
    function titleStatus($value): string {
        $value = trim((string)$value);
        if ($value === '') return 'Pending';
        return ucwords(str_replace('_', ' ', $value));
    }
}

if (!function_exists('getInquiryStatusDisplay')) {
    function getInquiryStatusDisplay($inquiry_status, $approval_status): array {
        $inquiry_status = strtolower(trim((string)$inquiry_status));
        $approval_status = strtolower(trim((string)$approval_status));

        if ($inquiry_status === 'officially booked') {
            return ['Officially Booked', 'bg-purple-50 text-purple-700 border-purple-100'];
        }
        if ($inquiry_status === 'reservation submitted') {
            return ['Reservation Submitted', 'bg-blue-50 text-blue-700 border-blue-100'];
        }
        if ($inquiry_status === 'responded') {
            return ['Responded', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
        }
        if ($inquiry_status === 'declined' || $approval_status === 'declined') {
            return ['Declined', 'bg-red-50 text-red-700 border-red-100'];
        }
        if ($approval_status === 'approved_email_sent') {
            return ['Approval Email Sent', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
        }
        if ($approval_status === 'approved') {
            return ['Owner Approved', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
        }
        if ($approval_status === 'requested') {
            return ['Awaiting Owner Approval', 'bg-amber-50 text-amber-700 border-amber-100'];
        }
        return [titleStatus($inquiry_status), 'bg-slate-50 text-slate-700 border-slate-100'];
    }
}

if (!function_exists('getOwnerDecisionDisplay')) {
    function getOwnerDecisionDisplay($request_status): array {
        $status = strtolower(trim((string)$request_status));
        if ($status === 'pending') {
            return ['Pending', 'bg-amber-50 text-amber-700 border-amber-100'];
        }
        if ($status === 'approved') {
            return ['Approved', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
        }
        if ($status === 'declined') {
            return ['Declined', 'bg-red-50 text-red-700 border-red-100'];
        }
        if ($status === 'expired') {
            return ['Expired', 'bg-slate-50 text-slate-700 border-slate-100'];
        }
        return [titleStatus($status), 'bg-slate-50 text-slate-700 border-slate-100'];
    }
}

if (!function_exists('getInquiryTypeBadgeDisplay')) {
    function getInquiryTypeBadgeDisplay($type): string {
        $t = strtolower(trim((string)$type));
        if (strpos($t, 'buy') !== false || strpos($t, 'purchase') !== false || strpos($t, 'resale') !== false) {
            return 'bg-blue-50 text-blue-700 border-blue-100';
        }
        if (strpos($t, 'lease') !== false || strpos($t, 'rental') !== false || strpos($t, 'reservation') !== false) {
            return 'bg-purple-50 text-purple-700 border-purple-100';
        }
        return 'bg-slate-50 text-slate-700 border-slate-100';
    }
}

if (!function_exists('formatDurationStr')) {
    function formatDurationStr($startDateStr, $endDateStr): string {
        if (empty($startDateStr) || empty($endDateStr)) return '';
        try {
            $start = new DateTime((string)$startDateStr);
            $end = new DateTime((string)$endDateStr);
            $diff = $start->diff($end);
            $parts = [];
            if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y > 1 ? 'yrs' : 'yr');
            if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m > 1 ? 'mos' : 'mo');
            if (empty($parts) && $diff->d > 0) $parts[] = $diff->d . ' ' . ($diff->d > 1 ? 'days' : 'day');
            return implode(' ', $parts);
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('computeUnitAvailability')) {
    function computeUnitAvailability($unitStatus, $activeMoveIn, $activeMoveOut, $preferredMoveIn, $leaseDuration): array {
        $today = new DateTime();
        $isOccupied = false;
        $occupiedDisplay = '';
        $occupiedDuration = '';
        $occupiedUntil = '';

        $statusLower = strtolower(trim((string)$unitStatus));
        $hasActiveLease = !empty($activeMoveOut) && $activeMoveOut >= date('Y-m-d');

        if ($statusLower === 'occupied' || ($hasActiveLease && in_array($statusLower, ['occupied', 'reserved', 'on hold'], true))) {
            $isOccupied = true;
        }

        if ($hasActiveLease) {
            $moveOutDate = new DateTime((string)$activeMoveOut);
            $occupiedUntil = $moveOutDate->format('M d, Y');
            $duration = formatDurationStr($activeMoveIn, $activeMoveOut);
            $occupiedDuration = $duration;

            if (!empty($activeMoveIn)) {
                $moveInDate = new DateTime((string)$activeMoveIn);
                $startFmt = $moveInDate->format('M d, Y');
                $occupiedDisplay = "{$startFmt} – {$occupiedUntil}" . ($duration ? " ({$duration})" : '');
            } else {
                $occupiedDisplay = "Active until {$occupiedUntil}" . ($duration ? " ({$duration})" : '');
            }
        } elseif ($isOccupied) {
            $occupiedDisplay = "Currently Occupied";
            $occupiedDuration = "Active";
            $occupiedUntil = "Active Lease";
        }

        if ($hasActiveLease) {
            $startDate = (new DateTime((string)$activeMoveOut))->modify('+1 day');
        } else {
            $startDate = clone $today;
            $prefTime = trim((string)$preferredMoveIn);
            $prefDate = strtotime($prefTime);
            if ($prefDate && $prefDate > time() && !in_array(strtolower($prefTime), ['immediately', 'not sure yet'], true)) {
                $startDate = new DateTime(date('Y-m-d', $prefDate));
            }
        }

        $durStr = strtolower(trim((string)$leaseDuration));
        $months = 0;
        if (preg_match('/(\d+)\s*(?:year|yr)/', $durStr, $m)) {
            $months = (int)$m[1] * 12;
        } elseif (preg_match('/(\d+)\s*(?:month|mo)/', $durStr, $m)) {
            $months = (int)$m[1];
        }

        $startFormatted = $startDate->format('M d, Y');
        $twoYearsDate = (clone $startDate)->modify('+2 years');
        $twoYearsFormatted = $twoYearsDate->format('M d, Y');

        if ($months > 0 && $months < 24) {
            $reqEndDate = (clone $startDate)->modify("+{$months} months");
            $reqEndFormatted = $reqEndDate->format('M d, Y');
            $display = "{$startFormatted} – {$reqEndFormatted}";
            $label = "Duration: {$months} mos (up to 2 yrs)";
        } else {
            $display = "{$startFormatted} – {$twoYearsFormatted}";
            $label = "Duration: 2 Years";
        }

        return [
            'is_occupied'       => $isOccupied,
            'occupied_display'  => $occupiedDisplay,
            'occupied_duration' => $occupiedDuration,
            'occupied_until'    => $occupiedUntil,
            'display'           => $display,
            'label'             => $label,
            'start'             => $startFormatted,
            'end'               => ($months > 0 && $months < 24) ? $reqEndFormatted : $twoYearsFormatted
        ];
    }
}

$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$inquiries = $inquiries ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? 'Zeppelin Suites — Inquiries') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width:256px; transition:width 0.3s cubic-bezier(0.4,0,0.2,1),transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition:margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.sidebar-logo { transition:opacity 0.2s ease,width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-link { position:relative; transition:all 0.18s ease; white-space:nowrap; overflow:hidden; }
.sidebar-link.active { background:#0f172a; color:#fff; }
.sidebar-link.active .nav-icon { color:#60a5fa; }
.sidebar-link:not(.active):hover { background:#eff6ff; color:#1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color:#3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .notice-section { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
.collapse-icon { transition:transform 0.3s ease; }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
.data-row { transition:background 0.15s ease; }
.data-row:hover { background:#f1f5f9; }
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.22s ease,visibility 0.22s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(12px) scale(0.98); transition:transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/../components/owner_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/../components/owner_navbar.php'; ?>
 
 <div class="main-scroll p-4 md:p-6 space-y-6">
  <div class="max-w-screen-xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-slate-900">Inquiries</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
      <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm font-semibold">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
      <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-3 rounded-xl text-sm font-semibold">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- WHITE CARD START -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm" id="resTable">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/60">
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Res #</th>
              <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquirer</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Contact</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquiry Type</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Status</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Owner Decision</th>
            </tr>
          </thead>

          <tbody id="resBody">
            <?php if (empty($inquiries)): ?>
              <tr>
                <td colspan="6" class="px-4 py-14 text-center">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center">
                      <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No reservation request yet</p>
                    <p class="text-xs text-slate-400 max-w-sm">Approval requests from the admin will appear here once a client inquiry is matched with one of your units.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inquiries as $row): 
                  [$inquiryStatusText, $inquiryStatusClass] = getInquiryStatusDisplay($row['inquiry_status'] ?? '', $row['approval_status'] ?? '');
                  [$ownerStatusText, $ownerStatusClass] = getOwnerDecisionDisplay($row['request_status'] ?? 'pending');
                  $inquiryTypeClass = getInquiryTypeBadgeDisplay($row['inquiry_type'] ?? '');
                  $availInfo = computeUnitAvailability(
                      $row['unit_current_status'] ?? '',
                      $row['active_move_in'] ?? null,
                      $row['active_move_out'] ?? null,
                      $row['preferred_move_in_time'] ?? '',
                      $row['lease_duration'] ?? ''
                  );
                  $requestCode = 'REQ-' . str_pad((string)$row['request_id'], 3, '0', STR_PAD_LEFT);
              ?>
                <tr class="group cursor-pointer transition-colors hover:bg-slate-50/70 approval-row"
                    tabindex="0"
                    role="button"
                    title="Click to view details for <?= clean($requestCode) ?>"
                    data-request-id="<?= clean($row['request_id']) ?>"
                    data-request-code="<?= clean($requestCode) ?>"
                    data-name="<?= clean($row['sender_name']) ?>"
                    data-email="<?= clean($row['sender_email']) ?>"
                    data-contact="<?= clean($row['sender_contact']) ?>"
                    data-type="<?= clean($row['inquiry_type']) ?>"
                    data-unit="<?= clean($row['unit_number']) ?>"
                    data-floor="<?= clean($row['floor_number'] ?? '') ?>"
                    data-unit-type="<?= clean($row['unit_type']) ?>"
                    data-unit-status="<?= clean($row['unit_current_status'] ?? 'Ready for Occupancy') ?>"
                    data-fee="<?= clean(peso($row['lease_rate'] ?? 0)) ?>"
                    data-lease="<?= clean($row['lease_duration'] ?: '—') ?>"
                    data-move-in="<?= clean($row['preferred_move_in_time'] ?: '—') ?>"
                    data-message="<?= clean($row['message']) ?>"
                    data-status="<?= clean($ownerStatusText) ?>"
                    data-remarks="<?= clean($row['owner_remarks'] ?? '') ?>"
                    data-is-occupied="<?= $availInfo['is_occupied'] ? '1' : '0' ?>"
                    data-occupied-display="<?= clean($availInfo['occupied_display']) ?>"
                    data-occupied-duration="<?= clean($availInfo['occupied_duration']) ?>"
                    data-occupied-until="<?= clean($availInfo['occupied_until']) ?>"
                    data-avail-display="<?= clean($availInfo['display']) ?>"
                    data-avail-label="<?= clean($availInfo['label']) ?>"
                    data-avail-start="<?= clean($availInfo['start']) ?>"
                    data-avail-end="<?= clean($availInfo['end']) ?>"
                    data-inquiry-status="<?= clean($inquiryStatusText) ?>"
                    onclick="openResModal(this)">

                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap text-center align-middle font-mono">
                        <?= clean($requestCode) ?>
                    </td>

                    <td class="px-5 py-3.5 border-b border-slate-100/50 whitespace-nowrap text-left align-middle">
                        <div class="min-w-[160px]">
                            <p class="text-sm font-bold text-slate-900 leading-tight group-hover:text-blue-600 transition-colors"><?= clean($row['sender_name']) ?></p>
                            <p class="text-xs text-slate-400 mt-1 leading-tight"><?= clean($row['sender_email']) ?></p>
                        </div>
                    </td>

                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap text-center align-middle font-mono">
                        <?= clean($row['sender_contact']) ?>
                    </td>

                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-center align-middle whitespace-nowrap">
                        <span class="inline-flex items-center justify-center <?= $inquiryTypeClass ?> text-xs font-semibold px-3 py-1 rounded-full border whitespace-nowrap shadow-2xs">
                            <?= clean($row['inquiry_type']) ?>
                        </span>
                    </td>

                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap text-center align-middle">
                        <span class="<?= $inquiryStatusClass ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border inline-flex items-center justify-center whitespace-nowrap">
                            <?= clean($inquiryStatusText) ?>
                        </span>
                    </td>

                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap text-center align-middle">
                        <span class="<?= $ownerStatusClass ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border inline-flex items-center justify-center whitespace-nowrap">
                            <?= clean($ownerStatusText) ?>
                        </span>
                    </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- WHITE CARD END -->

  </div>
</div>

<!-- INQUIRY DETAIL MODAL -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[60] flex items-center justify-center p-4" id="resModal" onclick="handleBackdropClick(event,'resModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
    
    <!-- Modal Header -->
    <div class="bg-white px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
      <div>
        <h2 class="text-base font-bold text-slate-900 tracking-tight uppercase">Inquiry Details</h2>
        <p class="text-xs text-slate-400 mt-0.5 font-mono" id="mResNum">—</p>
      </div>
      <button 
          type="button"
          onclick="event.stopPropagation(); closeModal('resModal')" 
          class="btn-press p-2 rounded-xl hover:bg-slate-100 transition-colors active:scale-95 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
      </button>
    </div>

    <!-- Modal Content: Scrollable Area -->
    <div class="p-6 space-y-5 overflow-y-auto flex-1">
      <div class="bg-slate-50/90 border border-slate-200/80 rounded-2xl p-5 space-y-4 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">inquirer name</p>
            <p class="text-sm font-bold text-slate-900 leading-snug break-words" id="mResName">—</p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">email</p>
            <p class="text-sm font-medium text-slate-700 leading-snug break-all" id="mResEmail">—</p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">contact number</p>
            <p class="text-sm font-medium text-slate-800 font-mono leading-snug" id="mResContact">—</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">inquiry type</p>
            <p class="text-sm font-semibold text-slate-800 leading-snug" id="mResType">—</p>
          </div>
          <div id="mResMoveInRow">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">preferred move-in time</p>
            <p class="text-sm font-medium text-slate-800 leading-snug" id="mResMoveIn">—</p>
          </div>
          <div id="mResLeaseRow">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">lease duration</p>
            <p class="text-sm font-medium text-slate-800 leading-snug" id="mResLease">—</p>
          </div>
        </div>

        <div class="pt-1">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">message</p>
          <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
            <p class="text-xs text-slate-700 leading-relaxed max-h-28 overflow-y-auto whitespace-pre-line" id="mResMessage">—</p>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
              <span>Unit Available:</span>
              <span class="text-slate-400 font-medium text-[11px]" id="mResFloorDisplay">Floor —</span>
            </label>
            <div class="h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 flex items-center shadow-2xs font-mono">
              <span id="mResUnitDisplay">Unit —</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
              <span>Lease Rate</span>
              <span class="text-slate-400 font-normal text-[11px]">Monthly</span>
            </label>
            <div class="h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 flex items-center shadow-2xs font-mono" id="mResFee">
              —
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
              <span>Current Status</span>
              <span class="text-slate-400 font-normal text-[11px] truncate max-w-[90px]" id="mResUnitType">—</span>
            </label>
            <div class="h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 flex items-center justify-between shadow-2xs">
              <span id="mResUnitStatusText">Ready for Occupancy</span>
              <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" id="mResUnitStatusDot"></span>
            </div>
          </div>
        </div>

        <div id="mResOccupiedBanner" class="p-3.5 bg-amber-50/90 border border-amber-200/80 rounded-xl flex flex-wrap items-center justify-between gap-2 shadow-2xs hidden">
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Occupied / Current Lease:</span>
              <span class="text-xs font-bold text-amber-950 font-mono" id="mResOccupiedDuration">—</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-amber-800 bg-amber-100/90 border border-amber-200 px-2.5 py-0.5 rounded-full" id="mResOccupiedUntilBadge">Occupied</span>
          </div>
        </div>

        <div class="p-3.5 bg-slate-50/90 border border-slate-200/80 rounded-xl flex flex-wrap items-center justify-between gap-2 shadow-2xs">
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider" id="mResAvailHeaderLabel">Unit Availability:</span>
              <span class="text-xs font-bold text-slate-900 font-mono" id="mResAvailability">—</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full" id="mResAvailDurationLabel">—</span>
          </div>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="mOwnerRemarks" class="block text-xs font-bold text-slate-700">Unit Owner Remarks:</label>
          <span class="text-[11px] text-slate-400 italic" id="mOwnerRemarksHint">Optional note for this inquiry</span>
        </div>
        <textarea 
          id="mOwnerRemarks" 
          rows="3" 
          placeholder="Add a note for this inquiry..." 
          class="w-full bg-white border border-slate-200 rounded-xl p-3.5 text-xs text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900 focus:outline-none transition-all resize-none shadow-2xs"></textarea>
      </div>
    </div>

    <!-- MODAL FOOTER -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/70 shrink-0">
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400 font-medium">Status:</span>
        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border shrink-0" id="mResStatus">—</span>
      </div>

      <div class="flex items-center gap-2.5">
        <button 
          type="button"
          id="declineRequestBtn"
          onclick="handleDecline()" 
          class="btn-press px-6 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-sm active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
          Decline
        </button>

        <button 
          type="button"
          id="approveRequestBtn"
          onclick="handleApprove()" 
          class="btn-press px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-sm active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
          Approve
        </button>
      </div>
    </div>

  </div>
</div>

<!-- APPROVE CONFIRMATION POP-UP MODAL -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[80] flex items-center justify-center p-4" id="approveConfirmModal" onclick="handleBackdropClick(event,'approveConfirmModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-md border border-slate-100 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
    <div class="p-6 pb-2">
      <div class="flex items-start justify-between">
        <h3 class="text-lg font-bold text-slate-900">Are you sure?</h3>
        <button 
          type="button" 
          onclick="closeModal('approveConfirmModal')" 
          class="btn-press p-1.5 -mr-1 -mt-1 rounded-xl hover:bg-slate-100 transition-colors active:scale-95 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-xs text-slate-500 mt-1 leading-relaxed">
        Are you sure you want to approve this inquiry request for <span class="font-bold text-slate-800" id="confirmClientName">the client</span>?
      </p>
    </div>
    <div class="px-6 py-3">
      <p class="text-xs text-slate-500 leading-relaxed">
        <span class="font-semibold text-slate-700">Note:</span> Your information will be disclosed to the client upon approval, including your <span class="font-medium text-slate-700">unit details (<span id="confirmUnitNumber" class="font-semibold text-slate-900">your unit</span>)</span> and your contact information.
      </p>
    </div>
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
      <button type="button" onclick="closeModal('approveConfirmModal')" class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-2xs">Cancel</button>
      <button type="button" id="confirmApproveSubmitBtn" onclick="submitApprovedRequest()" class="btn-press inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-sm active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>Yes, Approve Request</span>
      </button>
    </div>
  </div>
</div>

<!-- DECLINE CONFIRMATION POP-UP MODAL -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[80] flex items-center justify-center p-4" id="declineConfirmModal" onclick="handleBackdropClick(event,'declineConfirmModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-md border border-slate-100 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
    <div class="p-6 pb-2">
      <div class="flex items-start justify-between">
        <h3 class="text-lg font-bold text-slate-900">Are you sure?</h3>
        <button type="button" onclick="closeModal('declineConfirmModal')" class="btn-press p-1.5 -mr-1 -mt-1 rounded-xl hover:bg-slate-100 transition-colors active:scale-95 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-xs text-slate-500 mt-1 leading-relaxed">
        Are you sure you want to decline this inquiry request for <span class="font-bold text-slate-800" id="declineClientName">the client</span>?
      </p>
    </div>
    <div class="px-6 py-3">
      <p class="text-xs text-slate-500 leading-relaxed">
        <span class="font-semibold text-slate-700">Note:</span> This inquiry request will be marked as declined and any remarks provided will be recorded.
      </p>
    </div>
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
      <button type="button" onclick="closeModal('declineConfirmModal')" class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-2xs">Cancel</button>
      <button type="button" id="confirmDeclineSubmitBtn" onclick="submitDeclinedRequest()" class="btn-press inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-sm active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span>Yes, Decline Request</span>
      </button>
    </div>
  </div>
</div>

<script>
  let currentResId = null;
  let currentClientName = '';
  let currentUnitNumber = '';
  let currentUnitType = '';
  let currentRequestCode = '';

  function openResModal(row) {
    currentResId = row.dataset.requestId;

    const name = row.dataset.name || '—';
    const unitNumber = row.dataset.unit || '—';
    const floorNumber = row.dataset.floor ? `Floor ${row.dataset.floor}` : 'Floor —';
    const unitType = row.dataset.unitType || '—';
    const unitStatus = row.dataset.unitStatus || 'Ready for Occupancy';
    const remarks = row.dataset.remarks || '';
    const status = row.dataset.status || 'Pending';

    currentClientName = name;
    currentUnitNumber = unitNumber;
    currentUnitType = unitType;
    currentRequestCode = row.dataset.requestCode || '—';

    document.getElementById('mResNum').textContent = row.dataset.requestCode || '—';
    document.getElementById('mResName').textContent = name;
    document.getElementById('mResEmail').textContent = row.dataset.email || '—';
    document.getElementById('mResContact').textContent = row.dataset.contact || '—';

    const unitDisp = document.getElementById('mResUnitDisplay');
    if (unitDisp) unitDisp.textContent = unitNumber !== '—' ? `Unit ${unitNumber}` : 'Unit —';
    
    const floorDisp = document.getElementById('mResFloorDisplay');
    if (floorDisp) floorDisp.textContent = floorNumber;

    const unitTypeEl = document.getElementById('mResUnitType');
    if (unitTypeEl) unitTypeEl.textContent = unitType;
    
    const isOccupied = row.dataset.isOccupied === '1';
    const occupiedDisplay = row.dataset.occupiedDisplay || '';
    const occupiedDuration = row.dataset.occupiedDuration || '';
    const occupiedUntil = row.dataset.occupiedUntil || '';

    const statusText = document.getElementById('mResUnitStatusText');
    if (statusText) {
      if (isOccupied && occupiedUntil) {
        statusText.textContent = `Occupied (until ${occupiedUntil})`;
      } else {
        statusText.textContent = unitStatus;
      }
    }

    const statusDot = document.getElementById('mResUnitStatusDot');
    if (statusDot) {
      if (isOccupied || unitStatus.toLowerCase().includes('occupied') || unitStatus.toLowerCase().includes('maintenance')) {
        statusDot.className = 'w-2 h-2 rounded-full bg-red-500 shrink-0';
      } else if (unitStatus.toLowerCase().includes('reserved') || unitStatus.toLowerCase().includes('hold')) {
        statusDot.className = 'w-2 h-2 rounded-full bg-amber-500 shrink-0';
      } else {
        statusDot.className = 'w-2 h-2 rounded-full bg-emerald-500 shrink-0';
      }
    }

    const occupiedBanner = document.getElementById('mResOccupiedBanner');
    const occupiedDurEl = document.getElementById('mResOccupiedDuration');
    const occupiedUntilBadge = document.getElementById('mResOccupiedUntilBadge');
    const availHeaderLabel = document.getElementById('mResAvailHeaderLabel');

    if (occupiedBanner) {
      if (isOccupied && occupiedDisplay) {
        occupiedBanner.classList.remove('hidden');
        if (occupiedDurEl) occupiedDurEl.textContent = occupiedDisplay;
        if (occupiedUntilBadge) {
          occupiedUntilBadge.textContent = occupiedUntil ? `Until ${occupiedUntil}` : (occupiedDuration ? `Lease: ${occupiedDuration}` : 'Occupied');
        }
        if (availHeaderLabel) availHeaderLabel.textContent = 'Next Availability:';
      } else {
        occupiedBanner.classList.add('hidden');
        if (availHeaderLabel) availHeaderLabel.textContent = 'Unit Availability:';
      }
    }

    document.getElementById('mResType').textContent = row.dataset.type || '—';
    document.getElementById('mResFee').textContent = row.dataset.fee || '—';
    document.getElementById('mResMoveIn').textContent = row.dataset.moveIn || '—';
    document.getElementById('mResLease').textContent = row.dataset.lease || '—';
    document.getElementById('mResMessage').textContent = row.dataset.message || '—';

    const availEl = document.getElementById('mResAvailability');
    if (availEl) availEl.textContent = row.dataset.availDisplay || '—';

    const availLabelEl = document.getElementById('mResAvailDurationLabel');
    if (availLabelEl) availLabelEl.textContent = row.dataset.availLabel || '—';

    const inqType = (row.dataset.type || '').toLowerCase().trim();
    const isResale = inqType.includes('resale');
    const isLeaseOrReservation = (inqType.includes('reservation') || inqType.includes('lease')) && !isResale;

    const moveInRow = document.getElementById('mResMoveInRow');
    const leaseRow = document.getElementById('mResLeaseRow');
    if (moveInRow) moveInRow.style.display = isLeaseOrReservation ? 'block' : 'none';
    if (leaseRow) leaseRow.style.display = isLeaseOrReservation ? 'block' : 'none';

    const remarksBox = document.getElementById('mOwnerRemarks');
    const remarksHint = document.getElementById('mOwnerRemarksHint');
    if (remarksBox) {
      remarksBox.value = remarks;
      if (status !== 'Pending') {
        remarksBox.readOnly = true;
        remarksBox.classList.add('bg-slate-50', 'text-slate-500');
        if (remarksHint) remarksHint.textContent = remarks ? 'Remarks recorded' : 'No remarks provided';
      } else {
        remarksBox.readOnly = false;
        remarksBox.classList.remove('bg-slate-50', 'text-slate-500');
        if (remarksHint) remarksHint.textContent = 'Optional note for this inquiry';
      }
    }

    const badge = document.getElementById('mResStatus');
    badge.textContent = status;

    const approveBtn = document.getElementById('approveRequestBtn');
    const declineBtn = document.getElementById('declineRequestBtn');

    if (status === 'Pending') {
      approveBtn.disabled = false;
      declineBtn.disabled = false;
      approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
      declineBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
      approveBtn.disabled = true;
      declineBtn.disabled = true;
      approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
      declineBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    const colors = {
      'Pending': 'bg-amber-50 text-amber-700 border-amber-200',
      'Approved': 'bg-emerald-50 text-emerald-700 border-emerald-200',
      'Declined': 'bg-red-50 text-red-600 border-red-200',
      'Expired': 'bg-slate-100 text-slate-500 border-slate-200'
    };

    badge.className = 'text-xs font-semibold px-2.5 py-0.5 rounded-full border shrink-0 ' +
      (colors[status] || 'bg-slate-100 text-slate-500 border-slate-200');

    document.getElementById('resModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('open');
    }
    const openModals = document.querySelectorAll('.modal-backdrop.open');
    if (!openModals || openModals.length === 0) {
      document.body.style.overflow = '';
    } else {
      document.body.style.overflow = 'hidden';
    }
  }

  function handleBackdropClick(e, id) {
    const modal = document.getElementById(id);
    if (e.target === modal) {
      closeModal(id);
    }
  }

  function handleApprove() {
    if (!currentResId) return;

    const clientNameEl = document.getElementById('confirmClientName');
    if (clientNameEl) clientNameEl.textContent = currentClientName;

    const unitEl = document.getElementById('confirmUnitNumber');
    if (unitEl) unitEl.textContent = currentUnitNumber !== '—' && currentUnitNumber ? `Unit ${currentUnitNumber}` : 'your unit';

    openModal('approveConfirmModal');
  }

  function handleDecline() {
    if (!currentResId) return;

    const clientNameEl = document.getElementById('declineClientName');
    if (clientNameEl) clientNameEl.textContent = currentClientName;

    openModal('declineConfirmModal');
  }

  function submitApprovedRequest() {
    executeDecisionForm('approve');
  }

  function submitDeclinedRequest() {
    executeDecisionForm('decline');
  }

  function executeDecisionForm(action) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "<?= htmlspecialchars($baseUrl) ?>/owner/inquiries/respond";

    const requestInput = document.createElement("input");
    requestInput.type = "hidden";
    requestInput.name = "request_id";
    requestInput.value = currentResId;

    const actionInput = document.createElement("input");
    actionInput.type = "hidden";
    actionInput.name = "action";
    actionInput.value = action;

    const remarksInput = document.createElement("input");
    remarksInput.type = "hidden";
    remarksInput.name = "remarks";
    remarksInput.value = document.getElementById("mOwnerRemarks")?.value.trim() || "";

    form.appendChild(requestInput);
    form.appendChild(actionInput);
    form.appendChild(remarksInput);

    document.body.appendChild(form);
    form.submit();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.approval-row').forEach(row => {
      row.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openResModal(row);
        }
      });
    });
  });
</script>
</body>
</html>
