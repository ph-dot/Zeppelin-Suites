<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Tenant Maintenance View
 * Pure MVC presentation template. Zero direct SQL or DB connections.
 */
$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$activeTab = 'maintenance';
$tenantUnitsList = $tenantUnits ?? [];

if (!function_exists('clean')) {
    function clean($val): string {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getFloorTitle')) {
    function getFloorTitle($floorNum): string {
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

// Group tickets for Kanban
$activeTickets = [];
$unassignedTickets = [];
$closedTickets = [];
$totalTicketsCount = 0;

foreach ($tickets ?? [] as $row) {
    $totalTicketsCount++;
    $st = strtolower(trim((string)($row['status'] ?? 'pending')));
    if ($st === 'in progress') {
        $activeTickets[] = $row;
    } elseif (in_array($st, ['pending', 'submitted', 'under review'], true)) {
        $unassignedTickets[] = $row;
    } else {
        $closedTickets[] = $row;
    }
}

$activeCount = count($activeTickets);
$unassignedCount = count($unassignedTickets);
$closedCount = count($closedTickets);

if (!function_exists('renderTenantTicketCard')) {
    function renderTenantTicketCard(array $row, string $baseUrl = ''): void {
        $maintenanceId = (int)$row['maintenance_id'];
        $mrNumber = 'MR-' . str_pad((string)$maintenanceId, 4, '0', STR_PAD_LEFT);
        $unitNumber = $row['unit_number'] ?? '—';
        $unitType = $row['unit_type'] ?? '';
        $floorNumber = !empty($row['floor_number']) ? (int)$row['floor_number'] : 1;
        $floorTitle = getFloorTitle($floorNumber);

        $unitHeading = $unitNumber !== '—' ? ("Unit {$unitNumber}" . ($unitType ? " — {$unitType}" : "")) : "Unit Not Assigned";
        $floorSubtitle = $floorTitle;

        $ownerName = !empty($row['owner_name']) ? (string)$row['owner_name'] : 'Zeppelin Suites Management';
        $ownerEmail = !empty($row['owner_email']) ? (string)$row['owner_email'] : '—';
        $personName = !empty($row['tenant_name']) ? (string)$row['tenant_name'] : 'Tenant';
        $requestedByRole = 'Tenant';

        $subject = !empty($row['subject']) ? (string)$row['subject'] : 'Maintenance Issue';
        $category = !empty($row['category']) ? (string)$row['category'] : 'General';
        $priority = strtolower(trim((string)($row['priority'] ?? 'normal')));
        $status = strtolower(trim((string)($row['status'] ?? 'pending')));
        $description = (string)($row['description'] ?? '');
        $remarks = (string)($row['admin_remarks'] ?? '');

        $submittedRaw = (string)($row['submitted_at'] ?? '');
        $submittedDateFormatted = !empty($submittedRaw) ? date('d M, Y', strtotime($submittedRaw)) : '—';
        $submittedFullFormatted = !empty($submittedRaw) ? date('M d, Y h:i A', strtotime($submittedRaw)) : '—';
        $resolvedDateFormatted = !empty($row['resolved_at']) ? date('M d, Y h:i A', strtotime((string)$row['resolved_at'])) : 'Not yet resolved';

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
            $savedPhotos = explode(',', (string)$row['photo_paths']);
            foreach ($savedPhotos as $photo) {
                $photo = str_replace('\\/', '/', $photo);
                $photo = trim($photo, " \t\n\r\0\x0B[]\"'");
                if (strpos($photo, 'uploads/maintenance/') === 0) {
                    $photoPaths[] = rtrim($baseUrl, '/') . '/' . ltrim($photo, '/');
                }
            }
        }
        $photoData = clean(implode('|', $photoPaths));

        $colGroup = 'closed';
        if ($status === 'in progress') $colGroup = 'active';
        elseif (in_array($status, ['pending', 'submitted', 'under review'], true)) $colGroup = 'unassigned';

        echo '
        <div class="ticket-card bg-white rounded-xl border border-slate-200/90 p-4 sm:p-5 shadow-xs transition-all duration-200 cursor-pointer space-y-3 hover:border-slate-400 hover:shadow-sm"
             onclick="openMaintenanceModalFromCard(this)"
             data-maintenance-id="' . $maintenanceId . '"
             data-mr="' . clean($mrNumber) . '"
             data-unit-id="' . clean($row['unit_id'] ?? '') . '"
             data-unit-number="' . clean((string)$unitNumber) . '"
             data-unit-type="' . clean((string)$unitType) . '"
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
            
            <div>
                <h3 class="font-bold text-slate-900 text-sm leading-snug">' . clean($unitHeading) . '</h3>
                <p class="text-xs text-slate-400 font-normal mt-0.5">' . clean($floorSubtitle) . '</p>
            </div>

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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Maintenance') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.zep-input:focus, .zep-select:focus, .zep-textarea:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.96); }
.glass-header { background: rgba(255,255,255,0.88); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }
.ticket-card { transition: transform 0.18s cubic-bezier(0.4,0,0.2,1), box-shadow 0.18s cubic-bezier(0.4,0,0.2,1), border-color 0.18s ease; }
.ticket-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08); }
.modal-backdrop { display: none; }
.modal-backdrop.open { display: flex; }
</style>
</head>
<body class="bg-slate-50/70 text-slate-800 overflow-hidden">

<?php include dirname(__DIR__) . '/components/tenant_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <?php 
  $navBreadcrumb = '<div class="flex items-center gap-2 text-sm text-slate-500 font-medium">
    <span class="text-slate-900 font-semibold">Tenant Portal</span>
    <span class="text-slate-300">/</span>
    <span class="text-slate-600">Maintenance</span>
  </div>';
  include dirname(__DIR__) . '/components/tenant_navbar.php'; 
  ?>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 flex-1">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Toast Feedback -->
      <?php if (!empty($successMessage)): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-xs">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?= clean($successMessage) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold text-emerald-600 hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($errorMessage)): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-xs">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?= clean($errorMessage) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold text-rose-600 hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <!-- PAGE TITLE & CREATE BUTTON -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Maintenance Requests</h1>
          <p class="text-xs text-slate-400 mt-0.5">Submit service requests and track real-time resolution progress.</p>
        </div>

        <button type="button" onclick="openCreateModal()" class="btn-press inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>Create Request</span>
        </button>
      </div>

      <!-- FILTER & CONTROL BAR -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        
        <!-- Left: Filters -->
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Category Filter -->
          <div class="relative min-w-35">
            <select id="filterCategory" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="all">All Categories</option>
              <option value="plumbing">Plumbing</option>
              <option value="electrical">Electrical</option>
              <option value="cleaning">Cleaning</option>
              <option value="fixture">Fixture</option>
              <option value="structural">Structural</option>
              <option value="other">Other</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Priority Filter -->
          <div class="relative min-w-32.5">
            <select id="filterPriority" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="all">All Priority</option>
              <option value="urgent">Urgent / High</option>
              <option value="normal">Medium / Normal</option>
              <option value="low">Low</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Reset Filter Button -->
          <button type="button" onclick="clearAllFilters()" id="clearFiltersBtn" class="hidden px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors">
            Reset
          </button>
        </div>

        <!-- Right: Sort by Date & Quick Search -->
        <div class="flex items-center gap-2.5">
          <div class="relative min-w-35">
            <select id="sortDate" onchange="applySort()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="newest">Sort by: Newest</option>
              <option value="oldest">Sort by: Oldest</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Search Box -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" placeholder="Filter tickets..." oninput="applyFilters()" class="zep-input pl-8 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs w-44 sm:w-56 transition-all">
          </div>
        </div>

      </div>

      <!-- KANBAN BOARD 3-COLUMN LAYOUT -->
      <div id="kanbanBoardGrid" class="<?= ($totalTicketsCount === 0) ? 'hidden' : '' ?> grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: ACTIVE (In Progress) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colActiveWrap" data-col-status="active">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-600 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Active</h2>
              <span id="colCountActive" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$activeCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-35" id="activeCardsContainer">
            <div class="empty-col-msg <?= ($activeCount > 0) ? 'hidden' : '' ?> bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No active tickets in progress.
            </div>
            <?php 
            foreach ($activeTickets as $ticket):
              renderTenantTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 2: UNASSIGNED (Pending) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colUnassignedWrap" data-col-status="unassigned">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Pending Review</h2>
              <span id="colCountUnassigned" class="w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$unassignedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-35" id="unassignedCardsContainer">
            <div class="empty-col-msg <?= ($unassignedCount > 0) ? 'hidden' : '' ?> bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No pending tickets.
            </div>
            <?php 
            foreach ($unassignedTickets as $ticket):
              renderTenantTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 3: RESOLVED / CLOSED -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colClosedWrap" data-col-status="closed">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Resolved / Closed</h2>
              <span id="colCountClosed" class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$closedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-35" id="closedCardsContainer">
            <div class="empty-col-msg <?= ($closedCount > 0) ? 'hidden' : '' ?> bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No resolved tickets yet.
            </div>
            <?php 
            foreach ($closedTickets as $ticket):
              renderTenantTicketCard($ticket, $baseUrl);
            endforeach;
            ?>
          </div>
        </div>

      </div>

      <!-- EMPTY STATE WHEN ZERO TICKETS LOGGED -->
      <?php if ($totalTicketsCount === 0): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-lg mx-auto shadow-xs">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <h3 class="text-lg font-bold text-slate-900 mb-1">No Maintenance Requests Yet</h3>
          <p class="text-xs text-slate-500 mb-6">Whenever something needs fixing or service in your unit, submit a request and track its progress right here.</p>
          <button type="button" onclick="openCreateModal()" class="btn-press px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create First Request</span>
          </button>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- ========================================== -->
<!-- VIEW MAINTENANCE DETAILS MODAL -->
<!-- ========================================== -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-50 items-center justify-center p-4" id="maintenanceModal" onclick="handleModalBackdropClick(event,'maintenanceModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
    
    <!-- Modal Header -->
    <div class="px-6 sm:px-8 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
      <div>
        <h3 class="text-base sm:text-lg font-bold text-slate-900" id="modalSubject">Maintenance Details</h3>
        <p class="text-xs text-slate-400 mt-0.5" id="modalCategoryPriority">—</p>
      </div>
      <button type="button" onclick="closeMaintenanceModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
      
      <!-- Unit & Owner Information Box -->
      <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
          <span class="text-slate-400 block mb-1">Unit</span>
          <span class="font-bold text-slate-800 text-sm" id="modalUnit">—</span>
        </div>
        <div>
          <span class="text-slate-400 block mb-1">Unit Owner</span>
          <span class="font-bold text-slate-800 text-sm" id="modalOwner">—</span>
          <span class="text-slate-400 block mt-0.5 font-mono" id="modalOwnerEmail"></span>
        </div>
        <div>
          <span class="text-slate-400 block mb-1">Requested By</span>
          <span class="font-bold text-slate-800 text-sm" id="modalTenant">—</span>
        </div>
        <div>
          <span class="text-slate-400 block mb-1">Submitted On</span>
          <span class="font-bold text-slate-800 text-sm font-mono" id="modalSubmittedAt">—</span>
        </div>
      </div>

      <!-- Description -->
      <div>
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Description</h4>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 text-xs text-slate-700 leading-relaxed whitespace-pre-line" id="modalDescription">
          —
        </div>
      </div>

      <!-- Admin Feedback / Remarks -->
      <div id="modalAdminRemarksContainer" class="hidden">
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Management Notes</h4>
        <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-4 text-xs text-blue-900 leading-relaxed whitespace-pre-line" id="modalAdminRemarksText">
          —
        </div>
      </div>

      <!-- Attached Photos -->
      <div>
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Attached Photos</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="modalPhotos">
          <!-- Injected via JS -->
        </div>
      </div>

    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-white shrink-0">
      <button type="button" onclick="closeMaintenanceModal()" class="btn-press px-6 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
        Close
      </button>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- CREATE MAINTENANCE REQUEST MODAL -->
<!-- ========================================== -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-50 items-center justify-center p-4" id="createModal" onclick="handleModalBackdropClick(event,'createModal')">
  <form 
    action="<?= htmlspecialchars($baseUrl) ?>/tenant/maintenance" 
    method="POST" 
    enctype="multipart/form-data"
    class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]"
    onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="px-6 sm:px-8 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
      <div>
        <h3 class="text-base sm:text-lg font-bold text-slate-900">Create Maintenance Request</h3>
        <p class="text-xs text-slate-400 mt-0.5">Submit an issue for repair or service in your unit</p>
      </div>
      <button type="button" onclick="closeCreateModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div class="p-6 sm:p-8 space-y-4 overflow-y-auto flex-1">
      
      <!-- Unit Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Select Leased Unit <span class="text-rose-500">*</span></label>
        <?php if (!empty($tenantUnitsList)): ?>
          <div class="relative">
            <select name="unit_id" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
              <?php foreach ($tenantUnitsList as $u): ?>
                <option value="<?= (int)$u['unit_id'] ?>" <?= count($tenantUnitsList) === 1 ? 'selected' : '' ?>>
                  Unit <?= clean($u['unit_number']) ?><?= !empty($u['unit_type']) ? ' — ' . clean($u['unit_type']) : '' ?> (<?= clean(getFloorTitle((int)$u['floor_number'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <p class="text-[11px] text-slate-400 mt-1">Maintenance requests can only be filed for your verified leased unit.</p>
        <?php else: ?>
          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            <strong>No Leased Unit Found:</strong> You do not currently have an active or approved lease. Maintenance tickets can only be submitted for units you are actively renting.
          </div>
        <?php endif; ?>
      </div>

      <!-- Subject -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Subject / Title <span class="text-rose-500">*</span></label>
        <input type="text" name="subject" required placeholder="e.g. Leaking bathroom faucet, Aircon not cooling" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium placeholder:text-slate-400">
      </div>

      <!-- Category & Priority Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Category <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select name="category" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
              <option value="" disabled selected>Select category</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Electrical">Electrical</option>
              <option value="Cleaning">Cleaning</option>
              <option value="Fixture">Fixture</option>
              <option value="Structural">Structural</option>
              <option value="Other">Other</option>
            </select>
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Priority <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select name="priority" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
              <option value="normal" selected>Medium / Normal</option>
              <option value="low">Low</option>
              <option value="urgent">High / Urgent</option>
            </select>
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Issue Description <span class="text-rose-500">*</span></label>
        <textarea name="description" rows="3" required placeholder="Please describe the issue in detail..." class="zep-textarea w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium placeholder:text-slate-400 resize-none"></textarea>
      </div>

      <!-- Upload Photos -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Photos (Optional, up to 5 photos, max 5MB each)</label>
        <input type="file" name="maintenance_photos[]" multiple accept="image/jpeg,image/png,image/webp" class="zep-input w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
      </div>

    </div>

    <!-- Footer -->
    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-slate-50 shrink-0">
      <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl transition-all btn-press">
        Cancel
      </button>
      <button type="submit" <?= empty($tenantUnitsList) ? 'disabled' : '' ?> class="px-6 py-2.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl shadow-xs transition-all btn-press active:scale-95">
        Submit Request
      </button>
    </div>

  </form>
</div>

<script>
function handleModalBackdropClick(event, modalId) {
  if (event.target === document.getElementById(modalId)) {
    if (modalId === 'maintenanceModal') closeMaintenanceModal();
    if (modalId === 'createModal') closeCreateModal();
  }
}

function openCreateModal() {
  document.getElementById('createModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeMaintenanceModal() {
  document.getElementById('maintenanceModal').classList.remove('open');
  document.body.style.overflow = '';
}

function openMaintenanceModalFromCard(card) {
  if (!card) return;
  
  document.getElementById('modalUnit').textContent = card.dataset.unit || '-';
  document.getElementById('modalOwner').textContent = card.dataset.ownerName || '-';
  document.getElementById('modalOwnerEmail').textContent = card.dataset.ownerEmail || '';
  
  const cat = card.dataset.category || 'General';
  const prio = (card.dataset.priority || 'Normal').toUpperCase();
  document.getElementById('modalCategoryPriority').textContent = `${cat} • ${prio}`;
  
  document.getElementById('modalTenant').textContent = card.dataset.tenantName || 'Tenant';
  document.getElementById('modalSubmittedAt').textContent = card.dataset.submittedAt || '-';
  document.getElementById('modalSubject').textContent = card.dataset.subject || '-';
  document.getElementById('modalDescription').textContent = card.dataset.description || '-';
  
  const remarks = card.dataset.adminRemarks || '';
  const remarksContainer = document.getElementById('modalAdminRemarksContainer');
  const remarksText = document.getElementById('modalAdminRemarksText');
  if (remarks.trim()) {
    remarksText.textContent = remarks;
    remarksContainer.classList.remove('hidden');
  } else {
    remarksText.textContent = 'No admin feedback provided yet.';
    remarksContainer.classList.add('hidden');
  }

  // Photos Gallery
  const photosRaw = card.dataset.photos || '';
  const photosContainer = document.getElementById('modalPhotos');
  photosContainer.innerHTML = '';
  if (photosRaw) {
    const photos = photosRaw.split('|').filter(Boolean);
    photos.forEach(src => {
      const a = document.createElement('a');
      a.href = src;
      a.target = '_blank';
      a.className = 'block rounded-xl overflow-hidden border border-slate-200 aspect-video hover:opacity-90 transition-opacity';
      a.innerHTML = `<img src="${src}" class="w-full h-full object-cover" alt="Photo">`;
      photosContainer.appendChild(a);
    });
  } else {
    photosContainer.innerHTML = '<p class="text-xs text-slate-400">No photos attached.</p>';
  }

  document.getElementById('maintenanceModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function applyFilters() {
  const cat = document.getElementById('filterCategory').value.toLowerCase();
  const prio = document.getElementById('filterPriority').value.toLowerCase();
  const search = document.getElementById('searchInput').value.toLowerCase().trim();

  const isFiltered = (cat !== 'all' || prio !== 'all' || search !== '');
  document.getElementById('clearFiltersBtn').classList.toggle('hidden', !isFiltered);

  const cards = document.querySelectorAll('.ticket-card');
  let visibleCount = 0;

  cards.forEach(card => {
    const cardCat = (card.dataset.category || '').toLowerCase();
    const cardPrio = (card.dataset.priority || '').toLowerCase();
    const cardSearch = (card.dataset.searchText || '');

    let matchCat = (cat === 'all' || cardCat === cat);
    let matchPrio = (prio === 'all' || cardPrio === prio);
    let matchSearch = (search === '' || cardSearch.includes(search));

    if (matchCat && matchPrio && matchSearch) {
      card.classList.remove('hidden');
      visibleCount++;
    } else {
      card.classList.add('hidden');
    }
  });

  updateColumnCounters();
}

function clearAllFilters() {
  document.getElementById('filterCategory').value = 'all';
  document.getElementById('filterPriority').value = 'all';
  document.getElementById('searchInput').value = '';
  applyFilters();
}

function updateColumnCounters() {
  ['active', 'unassigned', 'closed'].forEach(col => {
    const container = document.getElementById(col + 'CardsContainer');
    if (!container) return;
    const cards = container.querySelectorAll('.ticket-card:not(.hidden)');
    const countEl = document.getElementById('colCount' + col.charAt(0).toUpperCase() + col.slice(1));
    if (countEl) countEl.textContent = String(cards.length).padStart(2, '0');
    const emptyMsg = container.querySelector('.empty-col-msg');
    if (emptyMsg) emptyMsg.classList.toggle('hidden', cards.length > 0);
  });
}

function applySort() {
  const sort = document.getElementById('sortDate').value;
  ['activeCardsContainer', 'unassignedCardsContainer', 'closedCardsContainer'].forEach(cId => {
    const container = document.getElementById(cId);
    if (!container) return;
    const cards = Array.from(container.querySelectorAll('.ticket-card'));
    cards.sort((a, b) => {
      const dateA = new Date(a.dataset.submittedRaw || 0).getTime();
      const dateB = new Date(b.dataset.submittedRaw || 0).getTime();
      return sort === 'newest' ? (dateB - dateA) : (dateA - dateB);
    });
    cards.forEach(c => container.appendChild(c));
  });
}
</script>
</body>
</html>
