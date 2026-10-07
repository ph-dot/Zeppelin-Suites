<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Details View
 * Pure MVC presentation template. Zero SQL queries or DB connections.
 */
if (!function_exists('clean')) {
    function clean($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($value, bool $isLease = false): string {
        if ($value === null || $value === '' || ($isLease && (float)$value === 0.0)) {
            return $isLease ? '—' : '₱0.00';
        }
        return '₱' . number_format((float)$value, 2);
    }
}

if (!function_exists('fmtDate')) {
    function fmtDate($value): string {
        if (empty($value) || $value === '0000-00-00') return '—';
        $ts = strtotime((string)$value);
        return $ts ? date('M j, Y', $ts) : '—';
    }
}

if (!function_exists('getFloorTitle')) {
    function getFloorTitle($floorNum): string {
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

if (!function_exists('getDurationText')) {
    function getDurationText($start, $end): string {
        if (empty($start) || empty($end) || $start === '0000-00-00' || $end === '0000-00-00') {
            return '—';
        }
        try {
            $d1 = new DateTime((string)$start);
            $d2 = new DateTime((string)$end);
            $diff = $d1->diff($d2);

            $parts = [];
            if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'yr' : 'yrs');
            if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'mo' : 'mos');
            if ($diff->d > 0 && empty($parts)) $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
            return !empty($parts) ? implode(' ', $parts) : '1 day';
        } catch (Throwable $e) {
            return '—';
        }
    }
}

$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$unit = $unit ?? [];
$unitId = (int)($unitId ?? $unit['unit_id'] ?? 0);
$floorNumber = (int)($unit['floor_number'] ?: 1);
$floorTitle = getFloorTitle($floorNumber);
$currentStatus = trim((string)($unit['unit_current_status'] ?? 'Ready for Occupancy'));
$listingType = trim((string)($unit['listing_type'] ?? 'For Lease'));
$stayCategory = trim((string)($unit['stay_category'] ?? 'Long term'));
$leaseRate = (float)($unit['lease_rate'] ?? 0);
$resellingPrice = (float)($unit['resellling_price'] ?? $unit['reselling_price'] ?? 0);
$currentOwnerId = $unit['unit_owner_id'] !== null ? (int)$unit['unit_owner_id'] : null;

$statusLower = strtolower($currentStatus);
if ($statusLower === 'ready for occupancy') {
    $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
    $statusDot = 'bg-emerald-500';
} elseif ($statusLower === 'reserved') {
    $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
    $statusDot = 'bg-amber-500';
} elseif ($statusLower === 'occupied') {
    $statusClass = 'bg-rose-50 text-rose-700 border-rose-200';
    $statusDot = 'bg-rose-500';
} elseif ($statusLower === 'resale') {
    $statusClass = 'bg-blue-50 text-blue-700 border-blue-200';
    $statusDot = 'bg-blue-500';
} elseif ($statusLower === 'on hold') {
    $statusClass = 'bg-purple-50 text-purple-700 border-purple-200';
    $statusDot = 'bg-purple-500';
} else {
    $statusClass = 'bg-slate-50 text-slate-700 border-slate-200';
    $statusDot = 'bg-slate-400';
}

$activeTenant = $activeTenant ?? null;
$nextAvailability = $nextAvailability ?? ['text' => 'Immediately Available', 'sub' => 'Ready for new move-in', 'is_occupied' => false];
$isCurrentlyOccupied = (bool)($nextAvailability['is_occupied'] ?? false);
$nextAvailabilityText = (string)($nextAvailability['text'] ?? 'Immediately Available');
$nextAvailabilitySub = (string)($nextAvailability['sub'] ?? 'Ready for new move-in');

$pastOwners = $pastOwners ?? [];
$allTenantsList = $allTenantsList ?? [];
$ownerOptions = $ownerOptions ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin — Unit <?= clean($unit['unit_number']) ?> Details</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['DM Sans', 'sans-serif'],
        mono: ['DM Mono', 'monospace']
      }
    }
  }
}
</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width:256px; transition:width 0.3s cubic-bezier(0.4,0,0.2,1),transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition:margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-logo { transition:opacity 0.2s ease,width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.sidebar-link { position:relative; transition:all 0.18s ease; white-space:nowrap; overflow:hidden; }
.sidebar-link.active { background:#0f172a; color:#fff; }
.sidebar-link.active .nav-icon { color:#60a5fa; }
.sidebar-link:not(.active):hover { background:#eff6ff; color:#1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color:#3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
.collapse-icon { transition:transform 0.3s ease; }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.96); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }

.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.22s ease,visibility 0.22s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(12px) scale(0.98); transition:transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navBreadcrumb = '
  <div class="flex items-center gap-2 text-xs md:text-sm">
    <a href="' . htmlspecialchars($baseUrl) . '/admin/units" class="text-slate-500 hover:text-slate-900 transition-colors font-medium">Units</a>
    <span class="text-slate-300">/</span>
    <span class="font-bold text-slate-900">Unit ' . clean($unit['unit_number']) . '</span>
  </div>';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Back Navigation & Top Actions Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <a href="<?= htmlspecialchars($baseUrl) ?>/admin/units" class="btn-press inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 transition-all shadow-xs" title="Back to Units">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          </a>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-xl md:text-2xl font-bold text-slate-900 leading-tight">
                Unit <?= clean($unit['unit_number']) ?>
              </h1>
              <span id="displayStatusBadge" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $statusClass ?>">
                <span id="displayStatusDot" class="w-1.5 h-1.5 rounded-full <?= $statusDot ?>"></span>
                <span id="displayStatusText"><?= clean($currentStatus) ?></span>
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
              <span><?= clean($unit['unit_type']) ?></span>
              <span class="text-slate-300">•</span>
              <span class="font-semibold text-slate-700"><?= number_format((float)($unit['sqm'] ?? 0), 2) ?> SQM</span>
              <span class="text-slate-300">•</span>
              <span><?= clean($floorTitle) ?> (Floor <?= $floorNumber ?>)</span>
            </p>
          </div>
        </div>

        <!-- Edit Unit Button (Admin) -->
        <div class="flex items-center gap-2.5">
          <button 
            type="button"
            onclick="openEditModal()"
            class="btn-press inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition-all">
            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Manage Unit Owner
          </button>
        </div>
      </div>

      <!-- UNIT SPECIFICATION & KPI OVERVIEW CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        
        <!-- Card 1: Listing Type -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 md:p-6 shadow-xs transition-all hover:shadow-sm flex flex-col justify-between min-h-[145px]">
          <div>
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Listing Mode</p>
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">Configured</span>
                <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
              </div>
            </div>
            <div class="mt-3">
              <p class="text-lg md:text-xl font-bold text-slate-900 leading-tight" id="displayListingType"><?= clean($listingType) ?></p>
            </div>
          </div>
          <p class="text-xs text-slate-500 mt-3" id="displayListingSub">
            <?= $listingType === 'Resale' ? 'Listed for purchase / sale' : 'Offered for lease' ?>
          </p>
        </div>

        <!-- Card 2: Lease / Resale Price -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 md:p-6 shadow-xs transition-all hover:shadow-sm flex flex-col justify-between min-h-[145px]">
          <div>
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider" id="cardRateTitle"><?= $listingType === 'Resale' ? 'Reselling Price' : 'Lease Rate' ?></p>
              <span class="w-7 h-7 rounded-lg <?= $listingType === 'Resale' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center font-bold text-xs shrink-0" id="cardRateIcon">
                ₱
              </span>
            </div>
            <div class="mt-3">
              <p class="text-lg md:text-xl font-bold text-slate-900 font-mono leading-tight" id="displayLeaseRate">
                <?= $listingType === 'Resale' ? peso($resellingPrice > 0 ? $resellingPrice : $leaseRate, true) : peso($leaseRate, true) ?>
              </p>
            </div>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-500 mt-3 flex-wrap gap-1">
            <span id="displayRateSub"><?= $listingType === 'Resale' ? 'Outright unit selling price' : 'Per month standard rate' ?></span>
            <?php if ($listingType !== 'Resale' && $resellingPrice > 0): ?>
              <span class="text-[11px] text-slate-400 font-mono">Resale: <?= peso($resellingPrice) ?></span>
            <?php elseif ($listingType === 'Resale' && $leaseRate > 0): ?>
              <span class="text-[11px] text-slate-400 font-mono">Lease: <?= peso($leaseRate) ?>/mo</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Card 3: Term Type / Category -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 md:p-6 shadow-xs transition-all hover:shadow-sm flex flex-col justify-between min-h-[145px]">
          <div>
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Term Category</p>
              <div class="flex items-center gap-1.5">
                <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
              </div>
            </div>
            <div class="mt-3">
              <p class="text-lg md:text-xl font-bold text-slate-900 leading-tight" id="displayStayCategory"><?= clean($stayCategory) ?></p>
            </div>
          </div>
          <p class="text-xs text-slate-500 mt-3" id="displayStaySub">
            <?= strtolower($stayCategory) === 'short term' ? 'Flexible short stay' : 'Standard 6-12+ mos lease' ?>
          </p>
        </div>

        <!-- Card 4: Next Availability -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 md:p-6 shadow-xs transition-all hover:shadow-sm flex flex-col justify-between min-h-[145px]">
          <div>
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Next Availability</p>
              <span class="w-7 h-7 rounded-lg <?= $isCurrentlyOccupied ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              </span>
            </div>
            <div class="mt-3">
              <p class="text-lg md:text-xl font-bold text-slate-900 font-mono leading-tight"><?= clean($nextAvailabilityText) ?></p>
            </div>
          </div>
          <p class="text-xs text-slate-500 mt-3"><?= clean($nextAvailabilitySub) ?></p>
        </div>

      </div>

      <!-- CURRENT UNIT OWNER CARD -->
      <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
          <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <div>
              <h2 class="text-sm font-bold text-slate-900">Current Unit Owner</h2>
              <p class="text-xs text-slate-500">Active property owner registered for Unit <?= clean($unit['unit_number']) ?></p>
            </div>
          </div>
          <button 
            type="button" 
            onclick="openEditModal()"
            class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
            Manage / Transfer Owner
          </button>
        </div>

        <div class="p-6" id="currentOwnerCardContent">
          <?php if (!empty($unit['owner_name'])): ?>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 p-5 bg-slate-50/80 border border-slate-200/80 rounded-2xl">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-base shadow-xs shrink-0" id="ownerAvatarLetter">
                  <?= strtoupper(substr(trim((string)$unit['owner_name']), 0, 1)) ?>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-slate-900" id="currentOwnerNameDisplay">
                      <a href="<?= htmlspecialchars($baseUrl) ?>/admin/residents/view?id=<?= (int)$unit['unit_owner_id'] ?>" class="hover:underline">
                        <?= clean($unit['owner_name']) ?>
                      </a>
                    </h3>
                    <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Active Owner</span>
                  </div>
                  <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                    <span class="flex items-center gap-1.5" id="currentOwnerEmailDisplay">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                      <?= clean($unit['owner_email'] ?: 'No email on record') ?>
                    </span>
                    <?php if (!empty($unit['owner_contact'])): ?>
                      <span class="text-slate-300">•</span>
                      <span class="flex items-center gap-1.5 font-mono" id="currentOwnerContactDisplay">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?= clean($unit['owner_contact']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="text-center py-6">
              <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              </div>
              <h4 class="text-sm font-bold text-slate-900">No Unit Owner Assigned</h4>
              <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">This unit is currently developer-held or unassigned.</p>
              <button onclick="openEditModal()" class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all shadow-xs">
                <span>+ Assign an Owner</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- PAST UNIT OWNERS HISTORY SECTION -->
      <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-sm font-bold text-slate-900">Past Unit Owners & Ownership History</h2>
              <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-slate-900 text-white">
                <?= count($pastOwners) ?> <?= count($pastOwners) === 1 ? 'past owner' : 'past owners' ?>
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Chronological record of previous owners and ownership transfers for this unit</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                <th class="text-left px-6 py-3.5 whitespace-nowrap align-middle">Previous Owner</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Ownership Period</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Duration</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Transfer Type / Status</th>
                <th class="text-left px-6 py-3.5 whitespace-nowrap align-middle">Notes & Remarks</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <?php if (empty($pastOwners)): ?>
                <tr>
                  <td colspan="5" class="px-6 py-10 text-center align-middle">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-800">No Past Owners Recorded</p>
                    <p class="text-xs text-slate-400 mt-1">The current owner is the sole or original registered owner on record.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($pastOwners as $po): 
                  $pName = clean($po['owner_name']);
                  $pEmail = clean($po['owner_email']);
                  $pContact = clean($po['owner_contact'] ?: '—');
                  $sDate = fmtDate($po['start_date']);
                  $eDate = !empty($po['end_date']) ? fmtDate($po['end_date']) : 'Transferred';
                  $pDur = getDurationText($po['start_date'], $po['end_date'] ?: date('Y-m-d'));
                  $transType = clean($po['transfer_type'] ?: 'Ownership Transfer');
                  $remarks = clean($po['remarks'] ?: 'Previous registered owner');
                ?>
                <tr class="hover:bg-slate-50/80 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap align-middle">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                        <?= strtoupper(substr($pName, 0, 1)) ?>
                      </div>
                      <div>
                        <p class="font-semibold text-slate-900 text-xs sm:text-sm leading-tight"><?= $pName ?></p>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono"><?= $pContact !== '—' ? $pContact : $pEmail ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-slate-800 align-middle">
                    <span><?= $sDate ?></span>
                    <span class="text-slate-400 mx-1">→</span>
                    <span class="font-medium"><?= $eDate ?></span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-xs font-medium text-slate-600 align-middle">
                    <?= $pDur ?>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap align-middle">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                      <?= $transType ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 align-middle">
                    <?= $remarks ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CURRENT TENANT OCCUPANCY SUMMARY -->
      <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
          <div>
            <h2 class="text-sm font-bold text-slate-900">Current Occupancy Summary</h2>
            <p class="text-xs text-slate-500">Information regarding the currently registered tenant and active stay</p>
          </div>
          <?php if ($activeTenant): ?>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Occupied
            </span>
          <?php else: ?>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border bg-slate-100 text-slate-600 border-slate-200">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
              Vacant / Ready
            </span>
          <?php endif; ?>
        </div>

        <div class="p-6">
          <?php if ($activeTenant): 
            $tMoveIn = fmtDate($activeTenant['move_in_date']);
            $tMoveOut = fmtDate($activeTenant['move_out_date']);
            $duration = getDurationText($activeTenant['move_in_date'], $activeTenant['move_out_date']);
          ?>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 p-5 bg-slate-50/70 border border-slate-100 rounded-xl">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                  <?= strtoupper(substr(trim((string)($activeTenant['client_name'] ?: 'T')), 0, 1)) ?>
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 leading-tight"><?= clean($activeTenant['client_name']) ?></h3>
                  <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                    <?php if (!empty($activeTenant['client_contact'])): ?>
                      <span class="flex items-center gap-1 font-mono">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?= clean($activeTenant['client_contact']) ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($activeTenant['client_email'])): ?>
                      <span class="text-slate-300 hidden sm:inline">•</span>
                      <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?= clean($activeTenant['client_email']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Occupancy Dates and Action -->
              <div class="flex flex-wrap items-center gap-4 pt-3 md:pt-0 border-t md:border-t-0 border-slate-200/80">
                <div class="text-left md:text-right">
                  <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Occupied Up To</p>
                  <p class="text-sm font-bold text-slate-900 font-mono mt-0.5"><?= $tMoveOut ?></p>
                  <p class="text-xs text-slate-500 mt-0.5">Move-in: <span class="font-mono"><?= $tMoveIn ?></span> (<?= $duration ?>)</p>
                </div>
                <a 
                  href="<?= htmlspecialchars($baseUrl) ?>/admin/reservations/view?reservation_id=<?= (int)$activeTenant['reservation_id'] ?>" 
                  class="btn-press inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-xs transition-all">
                  <span>View Active Lease</span>
                  <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="text-center py-8">
              <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <h4 class="text-sm font-bold text-slate-800">No Current Active Tenant</h4>
              <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">This unit is currently unoccupied and ready for new prospective tenants or buyers.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ALL TENANTS & LEASE HISTORY (ACROSS ALL UNIT OWNERS) -->
      <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-sm font-bold text-slate-900">All Tenants & Lease History</h2>
              <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-slate-900 text-white">
                <?= count($allTenantsList) ?> <?= count($allTenantsList) === 1 ? 'record' : 'records' ?>
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Comprehensive list of all tenants, occupants, and bookings across all unit owners</p>
          </div>

          <!-- Table Filter Tabs -->
          <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200/60 text-xs">
            <button type="button" onclick="filterTenants('all')" id="tab-all" class="tenant-tab px-3 py-1 font-semibold rounded-lg bg-white text-slate-900 shadow-xs transition-all">All Tenants</button>
            <button type="button" onclick="filterTenants('active')" id="tab-active" class="tenant-tab px-3 py-1 font-semibold rounded-lg text-slate-500 hover:text-slate-900 transition-all">Current & Upcoming</button>
            <button type="button" onclick="filterTenants('past')" id="tab-past" class="tenant-tab px-3 py-1 font-semibold rounded-lg text-slate-500 hover:text-slate-900 transition-all">Past Tenants</button>
            <button type="button" onclick="filterTenants('cancelled')" id="tab-cancelled" class="tenant-tab px-3 py-1 font-semibold rounded-lg text-slate-500 hover:text-slate-900 transition-all">Cancelled</button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                <th class="text-left px-6 py-3.5 whitespace-nowrap align-middle">Tenant / Client</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Unit Owner at Stay</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Type / Stay</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Move-in Date</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Move-out Date</th>
                <th class="text-left px-4 py-3.5 whitespace-nowrap align-middle">Duration</th>
                <th class="text-left px-6 py-3.5 whitespace-nowrap align-middle">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="tenantsTableBody">
              <?php if (empty($allTenantsList)): ?>
                <tr>
                  <td colspan="7" class="px-6 py-12 text-center align-middle">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-800">No Tenant Records Found</p>
                    <p class="text-xs text-slate-400 mt-1">There are no historical or upcoming tenant reservations recorded for this unit.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($allTenantsList as $l): 
                  $resId = (int)$l['reservation_id'];
                  $cName = clean($l['client_name'] ?: 'Prospective Client');
                  $cEmail = clean($l['client_email'] ?: '—');
                  $cContact = clean($l['client_contact'] ?: '—');
                  $mIn = fmtDate($l['move_in_date']);
                  $mOut = fmtDate($l['move_out_date']);
                  $durationText = getDurationText($l['move_in_date'], $l['move_out_date']);
                  $resStatus = strtolower(trim((string)($l['reservation_status'] ?? '')));
                  $transType = clean($l['transaction_type'] ?: ($l['inquiry_type'] ?: 'Unit Leasing'));
                  $ownerDuringStay = clean($l['owner_during_stay']);

                  $today = date('Y-m-d');
                  $isCancelled = in_array($resStatus, ['cancelled', 'rejected'], true);
                  $isCurrent = (!$isCancelled && $l['move_in_date'] <= $today && $l['move_out_date'] >= $today);
                  $isUpcoming = (!$isCancelled && $l['move_in_date'] > $today);
                  $isPast = (!$isCancelled && $l['move_out_date'] < $today);

                  $rowGroup = 'past';
                  if ($isCancelled) {
                    $rowGroup = 'cancelled';
                  } elseif ($isCurrent || $isUpcoming) {
                    $rowGroup = 'active';
                  }

                  if ($isCancelled) {
                    $badgeHtml = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Cancelled</span>';
                  } elseif ($isCurrent) {
                    $badgeHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Active Occupant</span>';
                  } elseif ($isUpcoming) {
                    $badgeHtml = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Upcoming Client</span>';
                  } else {
                    $badgeHtml = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">Past Tenant</span>';
                  }
                ?>
                <tr class="tenant-row hover:bg-slate-50/80 transition-colors cursor-pointer" data-group="<?= $rowGroup ?>" onclick="window.location.href='<?= htmlspecialchars($baseUrl) ?>/admin/reservations/view?reservation_id=<?= $resId ?>'">
                  
                  <!-- Tenant / Client -->
                  <td class="px-6 py-4 whitespace-nowrap align-middle">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                        <?= strtoupper(substr($cName, 0, 1)) ?>
                      </div>
                      <div>
                        <p class="font-semibold text-slate-900 text-xs sm:text-sm leading-tight"><?= $cName ?></p>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono"><?= $cContact !== '—' ? $cContact : $cEmail ?></p>
                      </div>
                    </div>
                  </td>

                  <!-- Unit Owner at Stay -->
                  <td class="px-4 py-4 whitespace-nowrap align-middle">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200/80">
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                      <?= $ownerDuringStay ?>
                    </span>
                  </td>

                  <!-- Type / Resident -->
                  <td class="px-4 py-4 whitespace-nowrap align-middle">
                    <p class="text-xs font-medium text-slate-800"><?= $transType ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5"><?= clean($l['resident_type'] ?: 'Standard Resident') ?></p>
                  </td>

                  <!-- Move In -->
                  <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-slate-700 align-middle">
                    <?= $mIn ?>
                  </td>

                  <!-- Move Out -->
                  <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-medium text-slate-900 align-middle">
                    <?= $mOut ?>
                  </td>

                  <!-- Duration -->
                  <td class="px-4 py-4 whitespace-nowrap text-xs font-medium text-slate-600 align-middle">
                    <?= $durationText ?>
                  </td>

                  <!-- Status -->
                  <td class="px-6 py-4 whitespace-nowrap align-middle">
                    <?= $badgeHtml ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ========================================== -->
<!-- EDIT UNIT SETTINGS MODAL (ADMIN) -->
<!-- ========================================== -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-[60] flex items-center justify-center p-4" id="editUnitModal" onclick="handleBackdropClick(event, 'editUnitModal')">
  <div class="modal-card bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
    
    <!-- Modal Header -->
    <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white shrink-0">
      <div>
        <h2 class="text-base font-bold text-white flex items-center gap-2">
          <span>Edit Unit Settings & Owner</span>
          <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-white/20 text-white">
            Unit <?= clean($unit['unit_number']) ?>
          </span>
        </h2>
        <p class="text-xs text-slate-300 mt-0.5">Manage unit ownership assignment (lease settings are managed by the unit owner)</p>
      </div>
      <button 
        type="button" 
        onclick="closeEditModal()" 
        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
        ✕
      </button>
    </div>

    <!-- Modal Form -->
    <form id="editUnitForm" onsubmit="handleUnitUpdate(event)" class="p-6 space-y-4 overflow-y-auto">
      <input type="hidden" name="unit_id" value="<?= $unitId ?>">

      <!-- Unit Static Identity Info -->
      <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Unit Number</span>
          <span class="font-mono font-bold text-slate-900"><?= clean($unit['unit_number']) ?></span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Unit Type & Area</span>
          <span class="font-semibold text-slate-800"><?= clean($unit['unit_type']) ?> (<?= number_format((float)($unit['sqm'] ?? 0), 2) ?> SQM)</span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Building Location</span>
          <span class="font-semibold text-slate-800"><?= clean($floorTitle) ?> (Floor <?= $floorNumber ?>)</span>
        </div>
      </div>

      <!-- Owner Assignment / Transfer Section -->
      <div class="pt-4 border-t border-slate-100 space-y-3">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
          Unit Owner Assignment
        </label>
        
        <select 
          name="owner_action" 
          id="ownerActionSelect" 
          onchange="toggleOwnerFields()"
          class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-slate-900 transition-all">
          <option value="keep" selected>Keep current owner (<?= !empty($unit['owner_name']) ? clean($unit['owner_name']) : 'No Owner Assigned' ?>)</option>
          <option value="assign">Transfer to existing user</option>
          <option value="new">+ Create and assign new unit owner</option>
          <option value="remove">Remove owner (Set unassigned)</option>
        </select>

        <!-- Existing Owner Selection -->
        <div id="existingOwnerWrap" class="hidden space-y-1">
          <label class="block text-xs font-semibold text-slate-600">Select Existing User</label>
          <select 
            name="existing_owner_id" 
            id="existingOwnerSelect"
            class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-slate-900">
            <option value="">Choose owner...</option>
            <?php foreach ($ownerOptions as $ow): ?>
              <option value="<?= $ow['user_id'] ?>" <?= $currentOwnerId == $ow['user_id'] ? 'selected' : '' ?>>
                <?= clean($ow['full_name']) ?> (<?= clean($ow['email']) ?>) - <?= clean($ow['user_role']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- New Owner Fields -->
        <div id="newOwnerWrap" class="hidden space-y-2.5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
          <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Unit Owner Information</p>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="new_owner_name" id="newOwnerName" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-slate-900">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="new_owner_email" id="newOwnerEmail" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-slate-900">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Number</label>
            <input type="text" name="new_owner_contact" id="newOwnerContact" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-slate-900">
          </div>
        </div>

        <!-- Transfer Reason -->
        <div id="transferReasonWrap" class="hidden space-y-1">
          <label class="block text-xs font-semibold text-slate-600">Transfer Reason / Note</label>
          <input 
            type="text" 
            name="transfer_reason" 
            id="transferReasonInput" 
            placeholder="e.g. Resale Transfer, Deed of Sale, Title Transfer" 
            class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-slate-900">
        </div>
      </div>

      <!-- Error Notice -->
      <div id="editErrorNotice" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium"></div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 shrink-0">
        <button 
          type="button" 
          onclick="closeEditModal()" 
          class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
          Cancel
        </button>
        <button 
          type="submit" 
          id="btnSaveUnit" 
          class="btn-press px-5 py-2 text-xs font-semibold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-all shadow-xs flex items-center gap-2">
          <span>Save Changes</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Toast notification element -->
<div id="toastNotification" class="fixed bottom-6 right-6 z-[100] transform translate-y-20 opacity-0 pointer-events-none transition-all duration-300 flex items-center gap-3 px-4 py-3 bg-slate-900 text-white text-xs font-semibold rounded-2xl shadow-xl border border-slate-700">
  <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
  <span id="toastMsg">Changes saved successfully!</span>
</div>

<script>
function openEditModal() {
  const modal = document.getElementById('editUnitModal');
  modal?.classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('editErrorNotice')?.classList.add('hidden');
}

function closeEditModal() {
  const modal = document.getElementById('editUnitModal');
  modal?.classList.remove('open');
  document.body.style.overflow = '';
}

function handleBackdropClick(e, id) {
  if (e.target === document.getElementById(id)) {
    closeEditModal();
  }
}

function toggleOwnerFields() {
  const action = document.getElementById('ownerActionSelect')?.value;
  const existingWrap = document.getElementById('existingOwnerWrap');
  const newWrap = document.getElementById('newOwnerWrap');
  const reasonWrap = document.getElementById('transferReasonWrap');

  existingWrap?.classList.add('hidden');
  newWrap?.classList.add('hidden');
  reasonWrap?.classList.add('hidden');

  if (action === 'assign') {
    existingWrap?.classList.remove('hidden');
    reasonWrap?.classList.remove('hidden');
  } else if (action === 'new') {
    newWrap?.classList.remove('hidden');
    reasonWrap?.classList.remove('hidden');
  } else if (action === 'remove') {
    reasonWrap?.classList.remove('hidden');
  }
}

function filterTenants(type) {
  document.querySelectorAll('.tenant-tab').forEach(b => {
    b.classList.remove('bg-white', 'text-slate-900', 'shadow-xs');
    b.classList.add('text-slate-500');
  });
  const currentTab = document.getElementById('tab-' + type);
  if (currentTab) {
    currentTab.classList.add('bg-white', 'text-slate-900', 'shadow-xs');
    currentTab.classList.remove('text-slate-500');
  }

  const rows = document.querySelectorAll('.tenant-row');
  rows.forEach(r => {
    const group = r.dataset.group;
    if (type === 'all') {
      r.style.display = '';
    } else if (type === 'active') {
      r.style.display = (group === 'active') ? '' : 'none';
    } else if (type === 'past') {
      r.style.display = (group === 'past') ? '' : 'none';
    } else if (type === 'cancelled') {
      r.style.display = (group === 'cancelled') ? '' : 'none';
    }
  });
}

function showToast(msg) {
  const toast = document.getElementById('toastNotification');
  const msgEl = document.getElementById('toastMsg');
  if (toast && msgEl) {
    msgEl.textContent = msg;
    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
    toast.classList.add('translate-y-0', 'opacity-100');
    setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100');
      toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
    }, 3200);
  }
}

async function handleUnitUpdate(e) {
  e.preventDefault();
  const form = document.getElementById('editUnitForm');
  const btn = document.getElementById('btnSaveUnit');
  const errNotice = document.getElementById('editErrorNotice');

  errNotice.classList.add('hidden');
  btn.disabled = true;
  btn.innerHTML = `
    <svg class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    Saving...
  `;

  try {
    const formData = new FormData(form);
    const response = await fetch('<?= htmlspecialchars($baseUrl) ?>/admin/units/update', {
      method: 'POST',
      body: formData
    });
    const res = await response.json();

    if (res.success) {
      showToast(res.message || 'Unit details updated successfully!');
      setTimeout(() => {
        window.location.reload();
      }, 700);
    } else {
      errNotice.textContent = res.message || 'Failed to update unit details.';
      errNotice.classList.remove('hidden');
    }
  } catch (err) {
    errNotice.textContent = 'A network error occurred. Please try again.';
    errNotice.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Save Changes';
  }
}
</script>
</body>
</html>
