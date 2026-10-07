<?php
/**
 * Zeppelin Suites - Unit Owner Maintenance Requests View
 * Pure MVC presentation template.
 */

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

if (!function_exists('renderTicketCard')) {
    function renderTicketCard($row, $baseUrl = '') {
        $maintenanceId = (int)$row['maintenance_id'];
        $mrNumber = 'MR-' . str_pad((string)$maintenanceId, 4, '0', STR_PAD_LEFT);
        $unitNumber = $row['unit_number'] ?? '—';
        $unitType = $row['unit_type'] ?? '';
        $floorNumber = $row['floor_number'] ? (int)$row['floor_number'] : 1;
        $floorTitle = getFloorTitle($floorNumber);

        $unitHeading = $unitNumber !== '—' ? ("Unit {$unitNumber}" . ($unitType ? " — {$unitType}" : "")) : "Unit Not Assigned";
        $floorSubtitle = $floorTitle;

        $ownerName = $row['owner_name'] ?: 'No Owner';
        $ownerEmail = $row['owner_email'] ?: '—';
        $tenantName = $row['tenant_name'] ?: '';

        $submittedRole = strtolower(trim($row['submitted_by_role'] ?? ''));
        if ($submittedRole === 'unit owner' || $submittedRole === 'owner') {
            $requestedByRole = 'Unit Owner';
            $personName = $ownerName;
        } elseif ($submittedRole === 'tenant') {
            $requestedByRole = 'Tenant';
            $personName = $tenantName ?: 'Tenant';
        } else {
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

        $photoPaths = [];
        if (!empty($row['photo_paths'])) {
            $savedPhotos = explode(',', $row['photo_paths']);
            foreach ($savedPhotos as $photo) {
                $photo = str_replace('\\/', '/', $photo);
                $photo = trim($photo, " \t\n\r\0\x0B[]\"'");
                if (strpos($photo, 'images/maintenance/') === 0 || strpos($photo, 'uploads/maintenance/') === 0) {
                    $photoPaths[] = rtrim($baseUrl, '/') . '/' . ltrim($photo, '/');
                }
            }
        }
        $photoData = clean(implode('|', $photoPaths));

        $colGroup = 'closed';
        if ($status === 'in progress') $colGroup = 'active';
        elseif ($status === 'pending') $colGroup = 'unassigned';
        ?>
        <div class="ticket-card bg-white rounded-xl border border-slate-200/90 p-4 sm:p-5 shadow-xs transition-all duration-200 cursor-pointer space-y-3 hover:border-slate-400 hover:shadow-sm"
             onclick="openMaintenanceModalFromCard(this)"
             data-maintenance-id="<?= $maintenanceId ?>"
             data-mr="<?= clean($mrNumber) ?>"
             data-unit-id="<?= clean($row['unit_id'] ?? '') ?>"
             data-unit-number="<?= clean($unitNumber) ?>"
             data-unit-type="<?= clean($unitType) ?>"
             data-unit="<?= clean($unitHeading) ?>"
             data-floor="<?= $floorNumber ?>"
             data-floor-title="<?= clean($floorTitle) ?>"
             data-owner-name="<?= clean($ownerName) ?>"
             data-owner-email="<?= clean($ownerEmail) ?>"
             data-tenant-name="<?= clean($tenantName) ?>"
             data-requested-by="<?= clean($requestedByRole) ?>"
             data-person-name="<?= clean($personName) ?>"
             data-subject="<?= clean($subject) ?>"
             data-category="<?= clean($category) ?>"
             data-priority="<?= clean($priority) ?>"
             data-description="<?= clean($description) ?>"
             data-status="<?= clean($status) ?>"
             data-col-group="<?= $colGroup ?>"
             data-admin-remarks="<?= clean($remarks) ?>"
             data-submitted-at="<?= clean($submittedFullFormatted) ?>"
             data-submitted-raw="<?= clean($submittedRaw) ?>"
             data-resolved-at="<?= clean($resolvedDateFormatted) ?>"
             data-photos="<?= $photoData ?>"
             data-search-text="<?= strtolower("{$mrNumber} {$unitNumber} {$unitType} {$floorTitle} {$subject} {$category} {$priority} {$requestedByRole} {$personName} {$ownerName} {$tenantName} {$status}") ?>">
            
            <div>
                <h3 class="font-bold text-slate-900 text-sm leading-snug"><?= clean($unitHeading) ?></h3>
                <p class="text-xs text-slate-400 font-normal mt-0.5"><?= clean($floorSubtitle) ?></p>
            </div>

            <div class="flex items-center justify-between gap-2 pt-0.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-semibold <?= $priorityBadgeClass ?>">
                    <svg class="w-3.5 h-3.5 <?= $priorityFlagColor ?>" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/></svg>
                    <?= $priorityLabel ?>
                </span>

                <span class="text-xs text-slate-400 font-normal flex items-center gap-1 shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= $submittedDateFormatted ?>
                </span>
            </div>

            <div class="bg-slate-50/90 rounded-xl p-3.5 border border-slate-100 space-y-2">
                <p class="text-xs font-semibold text-slate-800 leading-snug line-clamp-2">
                    <?= clean($subject) ?>
                </p>

                <div class="space-y-1.5 text-xs pt-1">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Ticket ID</span>
                        <span class="font-mono font-medium text-slate-700"><?= clean($mrNumber) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Requested by</span>
                        <span class="font-medium text-slate-700 truncate max-w-[130px] text-right"><?= clean($requestedByRole) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-normal">Person</span>
                        <span class="underline underline-offset-2 decoration-slate-300 font-medium text-slate-800 truncate max-w-[130px] text-right hover:text-blue-600 transition-colors"><?= clean($personName) ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-0.5 min-h-[22px]">
                <div>
                    <?php if (!empty(trim((string)$remarks))): ?>
                    <div class="inline-flex items-center gap-1.5 text-slate-400 font-medium" title="Admin remarks: <?= clean($remarks) ?>">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h4m8-2a9 9 0 11-18 0 9 9 0 0118 0c0 1.508.372 2.93 1.026 4.175L3 21l4.825-1.026A8.962 8.962 0 0012 21z"/>
                        </svg>
                        <span class="text-[11px] font-semibold text-slate-500 font-mono">1</span>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="button" 
                        onclick="event.stopPropagation(); openMaintenanceModalFromCard(this.closest('.ticket-card'))" 
                        class="text-xs font-medium text-slate-500 hover:text-slate-900 group-hover:text-blue-600 transition-colors inline-flex items-center gap-1">
                    <span>See details</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Owner Maintenance') ?></title>
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

.sidebar { width: 256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background: rgba(255,255,255,0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) {
  .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; }
  .sidebar.open { transform: translateX(0); }
}
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }

.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label, .sidebar.collapsed .nav-badge, .sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after {
  content: attr(data-tooltip);
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
  background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px;
  border-radius: 8px; white-space: nowrap; z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none;
}
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }

.profile-dropdown { opacity: 0; visibility: hidden; transform: translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity: 1; visibility: visible; transform: translateY(0); }

.modal-backdrop { opacity: 0; visibility: hidden; transition: opacity 0.22s ease, visibility 0.22s ease; }
.modal-backdrop.open { opacity: 1; visibility: visible; }
.modal-card { transform: translateY(12px) scale(0.98); transition: transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform: translateY(0) scale(1); }

.zep-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }

::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.96); }
.glass-header { background: rgba(255,255,255,0.88); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }

.ticket-card {
  transition: transform 0.18s cubic-bezier(0.4,0,0.2,1), box-shadow 0.18s cubic-bezier(0.4,0,0.2,1), border-color 0.18s ease;
}
.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
}
</style>
</head>
<body class="bg-slate-50/70 text-slate-800 overflow-hidden">

<!-- Sidebar -->
<?php include __DIR__ . '/../components/owner_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- Navbar -->
  <?php include __DIR__ . '/../components/owner_navbar.php'; ?>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 lg:p-8 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Success & Error Alert Messages -->
      <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 flex items-center gap-2 shadow-xs">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800 flex items-center gap-2 shadow-xs">
          <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
          <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>

      <!-- PAGE TITLE & CREATE TICKET ACTION -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Maintenance Requests</h1>
          <p class="text-xs text-slate-500 mt-0.5">Track, submit, and inspect maintenance tickets for your assigned units.</p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="openCreateModal()" class="btn-press px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all shadow-xs inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create Ticket</span>
          </button>
        </div>
      </div>

      <!-- STATUS TABS & LIVE COUNTERS -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-200/80">
        <button type="button" onclick="setStatusTab('all')" id="tabAll" class="status-tab active px-4 py-2 rounded-full text-xs font-semibold bg-slate-900 text-white transition-all shadow-xs flex items-center gap-2 shrink-0">
          <span>All</span>
          <span id="tabBadgeAll" class="px-1.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-mono font-bold"><?= $totalTicketsCount ?></span>
        </button>

        <button type="button" onclick="setStatusTab('active')" id="tabActive" class="status-tab px-4 py-2 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2 shrink-0">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
          <span>Active</span>
          <span id="tabBadgeActive" class="px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-mono font-bold"><?= $activeCount ?></span>
        </button>

        <button type="button" onclick="setStatusTab('unassigned')" id="tabUnassigned" class="status-tab px-4 py-2 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2 shrink-0">
          <span class="w-2 h-2 rounded-full bg-amber-500"></span>
          <span>Unassigned</span>
          <span id="tabBadgeUnassigned" class="px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-mono font-bold"><?= $unassignedCount ?></span>
        </button>

        <button type="button" onclick="setStatusTab('closed')" id="tabClosed" class="status-tab px-4 py-2 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2 shrink-0">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>Closed</span>
          <span id="tabBadgeClosed" class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-mono font-bold"><?= $closedCount ?></span>
        </button>
      </div>

      <!-- FILTER & CONTROL BAR -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-3.5 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
          <div class="relative min-w-[170px]">
            <select id="filterType" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="">All Unit Types</option>
              <?php foreach ($unitTypeOptions as $ut): ?>
                <option value="<?= clean($ut) ?>"><?= clean($ut) ?></option>
              <?php endforeach; ?>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <div class="relative min-w-[150px]">
            <select id="filterPriority" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="">All Priorities</option>
              <option value="high">High / Urgent</option>
              <option value="medium">Medium / Normal</option>
              <option value="low">Low</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <button type="button" onclick="clearAllFilters()" id="clearFiltersBtn" class="hidden px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors">
            Reset
          </button>
        </div>

        <div class="flex items-center gap-2.5">
          <div class="relative min-w-[150px]">
            <select id="sortDate" onchange="applySort()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="newest">Sort by: Newest</option>
              <option value="oldest">Sort by: Oldest</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" placeholder="Filter tickets..." oninput="applyFilters()" class="zep-input pl-8 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs w-44 sm:w-56 transition-all">
          </div>
        </div>

      </div>

      <!-- KANBAN BOARD 3-COLUMN LAYOUT -->
      <div id="kanbanBoardGrid" class="<?= ($totalTicketsCount === 0) ? 'hidden' : '' ?> grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: ACTIVE -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colActiveWrap" data-col-status="active">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-600 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Active</h2>
              <span id="colCountActive" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$activeCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <div class="space-y-3 flex-1 min-h-[140px]" id="activeCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No active tickets in progress.
            </div>
            <?php 
            foreach ($activeTickets as $ticket):
              renderTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 2: UNASSIGNED -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colUnassignedWrap" data-col-status="unassigned">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Unassigned</h2>
              <span id="colCountUnassigned" class="w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$unassignedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <div class="space-y-3 flex-1 min-h-[140px]" id="unassignedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No unassigned / pending tickets.
            </div>
            <?php 
            foreach ($unassignedTickets as $ticket):
              renderTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 3: CLOSED -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colClosedWrap" data-col-status="closed">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Closed</h2>
              <span id="colCountClosed" class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$closedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <div class="space-y-3 flex-1 min-h-[140px]" id="closedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No closed tickets.
            </div>
            <?php 
            foreach ($closedTickets as $ticket):
              renderTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

      </div>

      <!-- EMPTY STATE CARD -->
      <div id="noTicketsMatching" class="<?= ($totalTicketsCount === 0) ? '' : 'hidden' ?> bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-sm space-y-3">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800" id="emptyStateHeading">No tickets in progress</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto" id="emptyStateSubtext">There are currently no maintenance tickets matching your filters.</p>
        <div class="pt-2 flex items-center justify-center gap-3">
          <button type="button" onclick="clearAllFilters()" class="px-5 py-2 text-xs font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700 transition-all shadow-xs btn-press">
            Reset Filters
          </button>
          <button type="button" onclick="openCreateModal()" class="px-5 py-2 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all shadow-xs btn-press">
            + Submit Ticket
          </button>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ISSUE DETAILS MODAL -->
<div id="maintenanceModal" class="modal-backdrop fixed inset-0 z-[80] bg-black/40 backdrop-blur-xs flex items-center justify-center p-4" onclick="handleModalBackdropClick(event, 'maintenanceModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-hidden flex flex-col border border-slate-100">
    
    <div class="bg-white px-6 sm:px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-2xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <div>
          <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">Issue Details</h2>
          <p class="text-xs text-slate-400 font-normal mt-0.5">View and track maintenance issue</p>
        </div>
      </div>

      <button type="button" onclick="closeMaintenanceModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="overflow-y-auto p-6 sm:p-8 space-y-4">
      <input type="hidden" id="modalMaintenanceId">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">BUILDING UNIT</p>
            <p id="modalUnit" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">UNIT OWNER</p>
            <p id="modalOwner" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
            <p id="modalOwnerEmail" class="text-xs text-slate-400 font-mono mt-0.5 truncate">-</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">CATEGORY / PRIORITY</p>
            <p id="modalCategoryPriority" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">REQUESTED BY / PERSON</p>
            <p id="modalTenant" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SUBMITTED AT</p>
            <p id="modalSubmittedAt" class="text-sm font-bold text-slate-900 mt-0.5 font-mono truncate">-</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">RESOLVED AT</p>
            <p id="modalResolvedAt" class="text-sm font-bold text-slate-900 mt-0.5 truncate">Not yet resolved</p>
          </div>
        </div>

      </div>

      <div class="bg-white border border-slate-200/90 border-l-4 border-l-blue-600 rounded-2xl p-5 space-y-4 shadow-xs">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SUBJECT</p>
            <p id="modalSubject" class="text-sm font-bold text-slate-900 mt-0.5">-</p>
          </div>
        </div>

        <div class="border-t border-slate-100 pt-3.5 space-y-1.5">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">ISSUE DESCRIPTION</p>
          <p id="modalDescription" class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">-</p>
        </div>
      </div>

      <div class="bg-white border border-slate-200/90 rounded-2xl p-5 space-y-3 shadow-xs">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">ATTACHED PHOTOS</p>
        <div id="modalPhotos" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <p class="text-xs text-slate-400">No photos uploaded.</p>
        </div>
      </div>

      <div id="modalAdminRemarksContainer" class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-5 space-y-2">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">MANAGEMENT REMARKS &amp; UPDATES</p>
        <p id="modalAdminRemarksText" class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">No admin feedback provided yet.</p>
      </div>

    </div>

    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-white shrink-0">
      <button type="button" onclick="closeMaintenanceModal()" class="btn-press px-6 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
        Close
      </button>
    </div>
  </div>
</div>

<!-- CREATE MAINTENANCE REQUEST MODAL -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-[80] flex items-center justify-center p-4" id="createModal" onclick="handleModalBackdropClick(event,'createModal')">
  <form 
    action="<?= htmlspecialchars($baseUrl) ?>/unitOwnerPages/ActionsUOP/submitMaintenanceRequest.php" 
    method="POST" 
    enctype="multipart/form-data"
    class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]"
    onclick="event.stopPropagation()">

    <div class="bg-white px-6 sm:px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </div>
        <div>
          <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">Create Maintenance Request</h2>
          <p class="text-xs text-slate-400 mt-0.5">Submit a concern for your assigned unit</p>
        </div>
      </div>

      <button type="button" onclick="closeCreateModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="overflow-y-auto p-6 sm:p-8 space-y-4">
      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
          Select Affected Unit <span class="text-rose-500">*</span>
        </label>
        <select 
          name="unit_id" 
          id="crUnit"
          required
          class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 cursor-pointer transition-all">
          <option value="">Select your unit</option>
          <?php foreach ($ownerUnitsList as $ou): ?>
            <option value="<?= (int)$ou['unit_id'] ?>">
              Unit <?= clean($ou['unit_number']) ?> — <?= clean($ou['unit_type']) ?> (<?= getFloorTitle($ou['floor_number'] ?? 1) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
          Subject / Brief Issue <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          name="subject" 
          id="crSubject" 
          required
          placeholder="e.g. Leaking faucet in master bath" 
          class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 transition-all">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Category <span class="text-rose-500">*</span>
          </label>
          <select 
            name="category" 
            id="crCategory" 
            required
            class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 cursor-pointer transition-all">
            <option value="">Select category</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Electrical">Electrical</option>
            <option value="Cleaning">Cleaning</option>
            <option value="Fixture">Fixture</option>
            <option value="Structural">Structural</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Priority <span class="text-rose-500">*</span>
          </label>
          <select 
            name="priority" 
            id="crPriority" 
            required
            class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 cursor-pointer transition-all">
            <option value="normal">Medium / Normal</option>
            <option value="low">Low</option>
            <option value="urgent">High / Urgent</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
          Detailed Description <span class="text-rose-500">*</span>
        </label>
        <textarea 
          name="description" 
          id="crDesc" 
          rows="3" 
          required
          placeholder="Please describe the maintenance concern in detail..." 
          class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 transition-all resize-none"></textarea>
      </div>

      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
          Upload Photos (Optional)
        </label>
        <label 
          for="maintenancePhotos" 
          class="w-full min-h-[120px] rounded-2xl flex flex-col items-center justify-center gap-2 cursor-pointer border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100/80 transition-all p-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
          </svg>
          <div class="text-center">
            <p class="text-xs font-bold text-slate-800">Click to upload photos</p>
            <p class="text-[11px] text-slate-400 mt-0.5">JPG, PNG, WEBP up to 5MB (Max 5 photos)</p>
          </div>
          <span id="maintenanceUploadFileName" class="text-xs text-emerald-600 font-semibold hidden"></span>
          <input 
            type="file" 
            name="maintenance_photos[]" 
            id="maintenancePhotos" 
            accept=".jpg,.jpeg,.png,.webp" 
            multiple 
            class="hidden" 
            onchange="handleMaintenanceUpload(this)">
        </label>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-white shrink-0">
      <button type="button" onclick="closeCreateModal()" class="btn-press px-5 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
        Cancel
      </button>

      <button type="submit" class="btn-press px-6 py-2.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-all shadow-xs inline-flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Submit Request
      </button>
    </div>

  </form>
</div>

<script>
function openCreateModal() {
  const m = document.getElementById('createModal');
  m?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
  const m = document.getElementById('createModal');
  m?.classList.remove('open');
  document.body.style.overflow = '';
}

function handleMaintenanceUpload(input) {
  const label = document.getElementById('maintenanceUploadFileName');
  if (!label) return;

  if (input.files && input.files.length > 0) {
    if (input.files.length === 1) {
      label.textContent = `Selected: ${input.files[0].name}`;
    } else {
      label.textContent = `Selected ${input.files.length} photos`;
    }
    label.classList.remove('hidden');
  } else {
    label.textContent = '';
    label.classList.add('hidden');
  }
}

let currentTab = 'all';

function setStatusTab(tabName) {
  currentTab = tabName;

  const tabs = [
    { id: 'tabAll', name: 'all' },
    { id: 'tabActive', name: 'active' },
    { id: 'tabUnassigned', name: 'unassigned' },
    { id: 'tabClosed', name: 'closed' }
  ];

  tabs.forEach(t => {
    const el = document.getElementById(t.id);
    if (!el) return;

    if (t.name === tabName) {
      el.className = 'status-tab active px-4 py-2 rounded-full text-xs font-semibold bg-slate-900 text-white transition-all shadow-xs flex items-center gap-2 shrink-0';
      const badge = el.querySelector('span:last-child');
      if (badge) badge.className = 'px-1.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-mono font-bold';
    } else {
      el.className = 'status-tab px-4 py-2 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2 shrink-0';
      const badge = el.querySelector('span:last-child');
      if (badge) {
        if (t.name === 'active') badge.className = 'px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-mono font-bold';
        else if (t.name === 'unassigned') badge.className = 'px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-mono font-bold';
        else if (t.name === 'closed') badge.className = 'px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-mono font-bold';
      }
    }
  });

  applyFilters();
}

function applyFilters() {
  const typeVal = (document.getElementById('filterType')?.value || '').toLowerCase().trim();
  const priorityVal = (document.getElementById('filterPriority')?.value || '').toLowerCase().trim();
  const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();

  const clearBtn = document.getElementById('clearFiltersBtn');
  if (clearBtn) {
    if (typeVal || priorityVal || query || currentTab !== 'all') {
      clearBtn.classList.remove('hidden');
    } else {
      clearBtn.classList.add('hidden');
    }
  }

  const columns = document.querySelectorAll('.kanban-col');
  let activeVisible = 0;
  let unassignedVisible = 0;
  let closedVisible = 0;

  columns.forEach(col => {
    const colStatus = col.dataset.colStatus;
    const isColVisibleTab = (currentTab === 'all' || currentTab === colStatus);

    if (!isColVisibleTab) {
      col.style.display = 'none';
    } else {
      col.style.display = '';
    }

    const cards = col.querySelectorAll('.ticket-card');
    let colVisibleCount = 0;

    cards.forEach(card => {
      const cardType = (card.dataset.unitType || '').toLowerCase();
      const cardPriority = (card.dataset.priority || '').toLowerCase();
      const searchText = card.dataset.searchText || '';

      const typeMatches = !typeVal || cardType === typeVal || cardType.includes(typeVal);
      
      let priorityMatches = true;
      if (priorityVal) {
        if (priorityVal === 'urgent' || priorityVal === 'high') {
          priorityMatches = (cardPriority === 'urgent' || cardPriority === 'high');
        } else if (priorityVal === 'normal' || priorityVal === 'medium') {
          priorityMatches = (cardPriority === 'normal' || cardPriority === 'medium');
        } else if (priorityVal === 'low') {
          priorityMatches = (cardPriority === 'low');
        }
      }

      const queryMatches = !query || searchText.includes(query);

      if (typeMatches && priorityMatches && queryMatches) {
        card.style.display = '';
        colVisibleCount++;
        if (colStatus === 'active') activeVisible++;
        else if (colStatus === 'unassigned') unassignedVisible++;
        else if (colStatus === 'closed') closedVisible++;
      } else {
        card.style.display = 'none';
      }
    });

    const countEl = document.getElementById(`colCount${colStatus.charAt(0).toUpperCase() + colStatus.slice(1)}`);
    if (countEl) {
      countEl.textContent = String(colVisibleCount).padStart(2, '0');
    }

    const emptyMsg = col.querySelector('.empty-col-msg');
    if (emptyMsg) {
      emptyMsg.classList.toggle('hidden', colVisibleCount > 0);
    }
  });

  const totalVisible = activeVisible + unassignedVisible + closedVisible;
  const tabBadgeAll = document.getElementById('tabBadgeAll');
  const tabBadgeActive = document.getElementById('tabBadgeActive');
  const tabBadgeUnassigned = document.getElementById('tabBadgeUnassigned');
  const tabBadgeClosed = document.getElementById('tabBadgeClosed');

  if (tabBadgeAll) tabBadgeAll.textContent = totalVisible;
  if (tabBadgeActive) tabBadgeActive.textContent = activeVisible;
  if (tabBadgeUnassigned) tabBadgeUnassigned.textContent = unassignedVisible;
  if (tabBadgeClosed) tabBadgeClosed.textContent = closedVisible;

  const boardGrid = document.getElementById('kanbanBoardGrid');
  const emptyStateCard = document.getElementById('noTicketsMatching');

  const isCurrentTabEmpty = (currentTab === 'all' && totalVisible === 0) ||
                            (currentTab === 'active' && activeVisible === 0) ||
                            (currentTab === 'unassigned' && unassignedVisible === 0) ||
                            (currentTab === 'closed' && closedVisible === 0);

  if (isCurrentTabEmpty) {
    if (boardGrid) boardGrid.classList.add('hidden');
    if (emptyStateCard) {
      emptyStateCard.classList.remove('hidden');

      const emptyTitle = document.getElementById('emptyStateHeading');
      const emptySub = document.getElementById('emptyStateSubtext');

      if (typeVal || priorityVal || query) {
        if (emptyTitle) emptyTitle.textContent = "No tickets match your filter";
        if (emptySub) emptySub.textContent = "Try clearing filters or changing your unit type and priority settings.";
      } else if (currentTab === 'active') {
        if (emptyTitle) emptyTitle.textContent = "No active tickets in progress";
        if (emptySub) emptySub.textContent = "You have no maintenance requests currently marked as in progress.";
      } else if (currentTab === 'unassigned') {
        if (emptyTitle) emptyTitle.textContent = "No unassigned tickets";
        if (emptySub) emptySub.textContent = "You have no pending maintenance requests awaiting assignment.";
      } else if (currentTab === 'closed') {
        if (emptyTitle) emptyTitle.textContent = "No closed tickets";
        if (emptySub) emptySub.textContent = "You have no completed or closed maintenance requests.";
      } else {
        if (emptyTitle) emptyTitle.textContent = "No tickets in progress";
        if (emptySub) emptySub.textContent = "There are currently no maintenance tickets for your assigned units.";
      }
    }
  } else {
    if (boardGrid) boardGrid.classList.remove('hidden');
    if (emptyStateCard) emptyStateCard.classList.add('hidden');
  }
}

function applySort() {
  const sortVal = document.getElementById('sortDate')?.value || 'newest';
  const containers = ['activeCardsContainer', 'unassignedCardsContainer', 'closedCardsContainer'];

  containers.forEach(contId => {
    const container = document.getElementById(contId);
    if (!container) return;

    const cards = Array.from(container.querySelectorAll('.ticket-card'));
    cards.sort((a, b) => {
      const dateA = new Date(a.dataset.submittedRaw || 0).getTime();
      const dateB = new Date(b.dataset.submittedRaw || 0).getTime();
      return sortVal === 'newest' ? dateB - dateA : dateA - dateB;
    });

    cards.forEach(card => container.appendChild(card));
  });
}

function clearAllFilters() {
  const fType = document.getElementById('filterType');
  const fPriority = document.getElementById('filterPriority');
  const search = document.getElementById('searchInput');

  if (fType) fType.value = '';
  if (fPriority) fPriority.value = '';
  if (search) search.value = '';

  setStatusTab('all');
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value || '-';
}

function openMaintenanceModalFromCard(card) {
  if (!card) return;

  const mId = card.dataset.maintenanceId || '';
  const unit = card.dataset.unit || '-';
  const ownerName = card.dataset.ownerName || '-';
  const ownerEmail = card.dataset.ownerEmail || '-';
  const category = card.dataset.category || '-';
  const priority = (card.dataset.priority || 'normal').toUpperCase();
  const submittedAt = card.dataset.submittedAt || '-';
  const resolvedAt = card.dataset.resolvedAt || 'Not yet resolved';
  const subject = card.dataset.subject || '-';
  const description = card.dataset.description || '-';
  const remarks = card.dataset.adminRemarks || '';

  const reqBy = card.dataset.requestedBy || 'Unit Owner';
  const person = card.dataset.personName || (card.dataset.ownerName || '-');

  document.getElementById('modalMaintenanceId').value = mId;
  setText('modalUnit', unit);
  setText('modalOwner', ownerName);
  setText('modalOwnerEmail', ownerEmail);
  setText('modalTenant', `${person} (${reqBy})`);
  setText('modalCategoryPriority', `${category} / ${priority}`);
  setText('modalSubmittedAt', submittedAt);
  setText('modalResolvedAt', resolvedAt);
  setText('modalSubject', subject);
  setText('modalDescription', description);

  const remarksText = document.getElementById('modalAdminRemarksText');
  if (remarksText) {
    if (remarks && remarks.trim() !== '') {
      remarksText.textContent = remarks;
      remarksText.className = 'text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line font-medium';
    } else {
      remarksText.textContent = 'No management feedback provided yet.';
      remarksText.className = 'text-xs text-slate-400 italic';
    }
  }

  const photoBox = document.getElementById('modalPhotos');
  photoBox.innerHTML = '';

  const photos = (card.dataset.photos || '')
    .split('|')
    .map(p => p.trim())
    .filter(p => p !== '');

  if (!photos.length) {
    photoBox.innerHTML = "<p class='text-xs text-slate-400'>No photos uploaded.</p>";
  } else {
    photos.forEach(src => {
      const link = document.createElement('a');
      link.href = src;
      link.target = '_blank';
      link.className = 'block rounded-xl overflow-hidden border border-slate-200 hover:opacity-90 transition-all shadow-xs';
      link.innerHTML = `<img src="${src}" class="w-full h-24 sm:h-28 object-cover" alt="Maintenance photo">`;
      photoBox.appendChild(link);
    });
  }

  const modal = document.getElementById('maintenanceModal');
  modal?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeMaintenanceModal() {
  const modal = document.getElementById('maintenanceModal');
  modal?.classList.remove('open');
  document.body.style.overflow = '';
}

function handleModalBackdropClick(e, modalId) {
  if (e.target === document.getElementById(modalId)) {
    if (modalId === 'maintenanceModal') closeMaintenanceModal();
    if (modalId === 'createModal') closeCreateModal();
  }
}
</script>
</body>
</html>
