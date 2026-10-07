<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Inquiries Management View
 * Pure presentation: strictly NO SQL queries or database connections.
 */
if (!function_exists('e')) {
    function e($val): string {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$statusMap = [
    'pending' => ['Pending', 'bg-amber-50 text-amber-700 border border-amber-200'],
    'onhold'  => ['On Hold', 'bg-orange-50 text-orange-700 border border-orange-200'],
    'responded' => ['Responded', 'bg-emerald-50 text-emerald-700 border border-emerald-200'],
    'declined'  => ['Declined', 'bg-red-50 text-red-700 border border-red-200'],
    'reservation submitted' => ['Reservation Submitted', 'bg-blue-50 text-blue-700 border border-blue-200'],
    'officially booked'     => ['Officially Booked', 'bg-purple-50 text-purple-700 border border-purple-200'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Zeppelin Suites - Inquiries') ?></title>
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
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-logo { transition:opacity 0.2s ease,width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.sidebar-link { position:relative; transition:all 0.18s ease; white-space:nowrap; overflow:hidden; }
.sidebar-link.active { background:#0f172a; color:#fff; }
.sidebar-link.active .nav-icon { color:#60a5fa; }
.sidebar-link:not(.active):hover { background:#eff6ff; color:#1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color:#3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,.sidebar.collapsed .notice-section { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
.stat-card { transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.inq-row { transition:background 0.15s ease; cursor:pointer; }
.inq-row:hover { background:#f8fafc; }
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.25s ease,visibility 0.25s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(16px) scale(0.97); transition:transform 0.25s cubic-bezier(0.4,0,0.2,1); }
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
  $navSearchId = 'inquirySearchInput';
  $navSearchPlaceholder = 'Search inquiries...';
  $navSearchHandler = 'oninput="handleInquirySearch(this.value)"';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Page header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Inquiries</h1>
          <p class="text-xs text-slate-400 mt-0.5">Track and manage client messages, preferred units, and leasing inquiries.</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex bg-slate-100 rounded-full p-1 gap-0.5 text-xs font-semibold">
            <button class="filter-btn active px-3.5 py-1.5 rounded-full bg-white text-slate-700 shadow-sm active:scale-95 transition-all" 
                    data-filter="pending"
                    onclick="setFilter('pending')">
                Pending
            </button>
            <button class="filter-btn px-3.5 py-1.5 rounded-full text-slate-500 hover:bg-white/70 active:scale-95 transition-all" 
                    data-filter="responded"
                    onclick="setFilter('responded')">
                Responded
            </button>
            <button class="filter-btn px-3.5 py-1.5 rounded-full text-slate-500 hover:bg-white/70 active:scale-95 transition-all" 
                    data-filter="submitted"
                    onclick="setFilter('submitted')">
                Submitted
            </button>
            <button class="filter-btn px-3.5 py-1.5 rounded-full text-slate-500 hover:bg-white/70 active:scale-95 transition-all" 
                    data-filter="all"
                    onclick="setFilter('all')">
                All
            </button>
          </div>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm" onclick="setFilter('pending')">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">New today</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="newTodayCount"><?= e($stats['newToday'] ?? 0) ?></p>
          <p class="text-xs text-amber-500 font-semibold mt-1">↑ <span id="newTodayChange" class="text-slate-400 font-normal">today</span></p>
        </div>
        
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm" onclick="setFilter('pending')">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Pending</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="pendingCount"><?= e($stats['pending'] ?? 0) ?></p>
          <p class="text-xs text-blue-500 font-semibold mt-1">↑ <span class="text-slate-400 font-normal">awaiting reply</span></p>
        </div>
        
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm" onclick="setFilter('responded')">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Responded</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="respondedCount"><?= e($stats['responded'] ?? 0) ?></p>
          <p class="text-xs text-emerald-500 font-semibold mt-1">↑ <span class="text-slate-400 font-normal">overall</span></p>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="inqTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Date Submitted</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquirer</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquiry Type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Unit Preference</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide align-middle">Message Preview</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="inqTableBody">
              <?php if (empty($inquiries)): ?>
                <tr><td colspan="6" class="text-center px-5 py-8 text-slate-400 text-sm">No inquiries found.</td></tr>
              <?php else: ?>
                <?php foreach ($inquiries as $row): 
                  $sender_name = $row['sender_name'] ?? '';
                  $sender_email = $row['sender_email'] ?? '';
                  $sender_contact = $row['sender_contact'] ?? '';
                  $inquiry_type = $row['inquiry_type'] ?? '';
                  $preferred_unit_id = $row['Preferred_unit_id'] ?? '';
                  $preferred_move_in_time = $row['preferred_move_in_time'] ?? '';
                  $lease_duration = $row['lease_duration'] ?? '';
                  $message = $row['message'] ?? '';
                  $dateOnly = $row['date_only'] ?? '';
                  $status = $row['status'] ?? 'pending';
                  $approval_status = $row['approval_status'] ?? 'not_requested';
                  $approved_unit_number = $row['approved_unit_number'] ?? '';
                  $approval_approved_at = $row['approval_approved_at_display'] ?? '';
                  $pendingRequestCount = (int)($row['pending_request_count'] ?? 0);
                  $requestsJson = json_encode($row['requests'] ?? []);

                  $status_lower = strtolower(trim((string)$status));
                  $approval_lower = strtolower(trim((string)$approval_status));

                  if (isset($statusMap[$status_lower])) {
                      [$displayStatus, $status_class] = $statusMap[$status_lower];
                  } else {
                      $displayStatus = 'Pending';
                      $status_class = 'bg-amber-50 text-amber-700 border border-amber-200';
                  }

                  $updateBadge = "";
                  if ($status_lower === 'pending' || $status_lower === 'onhold') {
                      if ($approval_lower === 'approved') {
                          $approvedUnitInfo = !empty($approved_unit_number) ? " - Unit " . $approved_unit_number : "";
                          $updateBadge = "<span class='group relative inline-flex items-center cursor-help' title='Owner has approved{$approvedUnitInfo}'>
                                  <span class='inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-300 shadow-2xs hover:bg-emerald-200 transition-all'>
                                      <svg class='w-2.5 h-2.5 text-emerald-700' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M5 13l4 4L19 7'/></svg>
                                  </span>
                                  <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                                      <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                          Owner has approved{$approvedUnitInfo}
                                      </span>
                                      <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                                  </span>
                              </span>";
                      } elseif ($approval_lower === 'requested' || $pendingRequestCount > 0) {
                          $updateBadge = "<span class='group relative inline-flex items-center cursor-help' title='Request is still pending'>
                                  <span class='inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold border border-amber-300 shadow-2xs hover:bg-amber-200 transition-all'>
                                      !
                                  </span>
                                  <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                                      <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                          Request is still pending
                                      </span>
                                      <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                                  </span>
                              </span>";
                      } elseif ($approval_lower === 'declined') {
                          $updateBadge = "<span class='group relative inline-flex items-center cursor-help' title='Owner declined request'>
                                  <span class='inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-100 text-red-700 text-[10px] font-bold border border-red-300 shadow-2xs hover:bg-red-200 transition-all'>
                                      ✕
                                  </span>
                                  <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                                      <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                          Owner declined request
                                      </span>
                                      <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                                  </span>
                              </span>";
                      }
                  }

                  $displayUnitPref = !empty($preferred_unit_id) ? $preferred_unit_id : '—';
                  $displayMessage = !empty($message) ? $message : '—';
                ?>
                  <tr class="inq-row cursor-pointer hover:bg-slate-50/80 transition-colors" 
                      data-inq-id="<?= e($row['inq_id']) ?>"
                      data-status="<?= e($status) ?>"
                      data-approval-status="<?= e($approval_status) ?>"
                      data-approved-unit="<?= e($approved_unit_number) ?>"
                      data-approved-at="<?= e($approval_approved_at) ?>"
                      data-owner-remarks="<?= e($row['owner_remarks'] ?? '') ?>"
                      data-requests="<?= e($requestsJson) ?>"
                      data-pending-count="<?= e($pendingRequestCount) ?>"
                      data-name="<?= e($sender_name) ?>"
                      data-email="<?= e($sender_email) ?>"
                      data-contact="<?= e($sender_contact) ?>"
                      data-inquiry-type="<?= e($inquiry_type) ?>"
                      data-unitpref="<?= e($preferred_unit_id) ?>"
                      data-move-in-time="<?= e($preferred_move_in_time) ?>"
                      data-lease-duration="<?= e($lease_duration) ?>"
                      data-message="<?= e($message) ?>"
                      onclick="openModal(this)">
                      <td class="px-5 py-3.5 text-left align-middle text-slate-500 whitespace-nowrap text-xs font-medium" style="font-family:'DM Mono',monospace">
                          <?= e($dateOnly) ?>
                      </td>
                      <td class="px-4 py-3.5 text-left align-middle">
                          <div class="min-w-[180px]">
                              <p class="text-sm font-bold text-slate-900 leading-tight">
                                  <?= e($sender_name) ?>
                              </p>
                              <p class="text-xs text-slate-400 mt-1 leading-tight">
                                  <?= e($sender_email) ?>
                              </p>
                          </div>
                      </td>
                      <td class="px-4 py-3.5 text-left align-middle whitespace-nowrap">
                          <span class="text-xs font-semibold text-slate-800"><?= e($inquiry_type) ?></span>
                      </td>
                      <td class="px-4 py-3.5 text-center align-middle text-slate-700 text-xs font-medium whitespace-nowrap"><?= e($displayUnitPref) ?></td>
                      <td class="px-4 py-3.5 text-left align-middle text-slate-400 text-xs max-w-xs truncate"><?= e($displayMessage) ?></td>
                      <td class="px-5 py-3.5 text-left align-middle whitespace-nowrap">
                          <div class="inline-flex items-center gap-1.5">
                              <span class="status-badge <?= $status_class ?> text-xs font-semibold px-2.5 py-1 rounded-full inline-flex items-center justify-center leading-normal">
                                  <?= e($displayStatus) ?>
                              </span>
                              <?= $updateBadge ?>
                          </div>
                      </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Dynamic Pagination --> 
        <div class="flex items-center justify-between flex-wrap gap-3 px-5 py-3.5 border-t border-slate-100" id="inqPaginationContainer">
          <p class="text-xs text-slate-500" id="inqPaginationInfo">
              Showing <span class="font-semibold text-slate-700" id="inqShowingStart">0</span>–<span class="font-semibold text-slate-700" id="inqShowingEnd">0</span> of 
              <span class="font-semibold text-slate-700" id="inqTotalCount"><?= count($inquiries) ?></span> inquiries
          </p>
          <div class="flex items-center gap-1" id="inqPaginationControls">
              <!-- Dynamically populated by JS -->
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

<!-- ── CUSTOM POPUP / ALERT / CONFIRMATION MODAL ── -->
<div id="zepAlertModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-[100] hidden items-center justify-center p-4 transition-all">
  <div id="zepAlertCard" class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-sm w-full p-6 text-center transform transition-all scale-95 opacity-0 duration-200">
    <div id="zepAlertIconBox" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border shadow-xs"></div>
    <h3 id="zepAlertTitle" class="text-base font-bold text-slate-900 mb-1.5">Notification</h3>
    <p id="zepAlertMessage" class="text-xs text-slate-500 leading-relaxed mb-6">Message content goes here.</p>
    <div id="zepAlertActions" class="flex items-center gap-2.5 justify-center"></div>
  </div>
</div>

<!-- ── VIEW / MESSAGE DETAIL MODAL ────────────────────── -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[60] flex items-center justify-center p-4 hidden" 
     id="modalBackdrop" 
     onclick="handleBackdropClick(event)">

  <div class="modal-card bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-slate-100 overflow-hidden">
    <!-- MODAL HEADER -->
    <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
      <div>
        <h2 class="text-base font-bold text-white" id="modalSubject">Inquiry Type</h2>
        <p class="text-xs text-slate-300 mt-0.5">View inquiry details and reservation approval status</p>
      </div>
      <button onclick="closeModal()" class="btn-press p-1.5 rounded-lg hover:bg-white/10 transition-colors active:scale-95">
        <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    
    <!-- MODAL BODY -->
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
      <!-- Sender Info -->
      <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="w-11 h-11 rounded-xl bg-slate-900 flex items-center justify-center text-white text-sm font-bold shrink-0" id="modalAvatar">?</div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">From</p>
          <p class="text-sm font-semibold text-slate-800 truncate" id="modalName">—</p>
          <p class="text-xs text-slate-500 truncate" id="modalEmail">—</p>
          <p class="text-xs text-slate-500 truncate" id="modalContact">—</p>
        </div>
        <div id="unitSection" class="text-right shrink-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Selected Unit</p>
          <p class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full" id="modalUnitPref">—</p>
        </div>
      </div>

      <!-- Move-In Time -->
      <div id="moveInTimeSection" class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Preferred Move-In Time</p>
          <p class="text-sm font-semibold text-slate-800 truncate" id="modalMoveInTime">—</p>
        </div>
      </div>

      <!-- Lease Duration -->
      <div id="leaseDurationSection" class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Lease Duration</p>
          <p class="text-sm font-semibold text-slate-800 truncate" id="modalLeaseDuration">—</p>
        </div>
      </div>

      <!-- Reservation Approval Workflow -->
      <div id="approvalSection" class="pb-5 border-b border-slate-100 space-y-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-1">Reservation Approval</p>
            <p class="text-sm font-semibold text-slate-800" id="approvalStatusText">Not yet requested</p>
            <p class="text-xs text-slate-500 mt-1" id="approvalSubText">Check available units first, then send approval requests to unit owners.</p>
          </div>
          <span id="approvalStatusBadge" class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shrink-0">Not Requested</span>
        </div>

        <div id="sentRequestsBox" class="hidden bg-white border border-slate-100 rounded-2xl p-3 space-y-1.5">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Sent To</p>
          <div id="sentRequestsList" class="space-y-1.5"></div>
        </div>

        <div id="availableUnitsBox" class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-1">Available Units</p>
              <p class="text-sm font-semibold text-slate-800" id="availableUnitsCount">Not checked yet</p>
            </div>
            <button type="button" id="checkUnitsBtn" onclick="checkAvailableUnits()" class="btn-press text-xs font-semibold px-3 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-700 transition-all active:scale-95">Check Units</button>
          </div>

          <div id="selectAllRow" class="hidden flex items-center justify-between px-1">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none">
              <input type="checkbox" id="selectAllUnitsCheckbox" onchange="toggleSelectAllUnits(this.checked)" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
              Select all
            </label>
            <span id="selectedUnitsCount" class="text-xs text-slate-400">0 selected</span>
          </div>

          <div id="availableUnitsList" class="hidden space-y-2"></div>
        </div>

        <div class="space-y-3">
          <button type="button" id="sendApprovalBtn" onclick="sendApprovalToOwners()" disabled class="hidden btn-press w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
            Send Request to Selected Unit Owner(s)
          </button>

          <div id="waitingApprovalBox" class="hidden bg-amber-50 border border-amber-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-amber-800">Waiting for Approval</p>
                <p class="text-xs text-amber-700 mt-0.5">The first owner who approves will get the reservation. You can still send the request to other available owners, or cancel a pending request below, while you wait.</p>
              </div>
            </div>
          </div>

          <div id="approvedApprovalBox" class="hidden bg-emerald-50 border border-emerald-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-emerald-800">Reservation Approved</p>
                <p class="text-xs text-emerald-700 mt-0.5" id="approvedUnitText">Assigned unit: —</p>
                <p class="text-xs text-emerald-900 mt-1 font-medium hidden" id="approvedOwnerRemarksRow">
                  <span class="text-emerald-700 font-semibold">Remarks:</span> <span class="italic font-normal text-emerald-950" id="approvedOwnerRemarksText">—</span>
                </p>
              </div>
            </div>
          </div>

          <div id="declinedApprovalBox" class="hidden bg-red-50 border border-red-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-red-800">No Approval Received</p>
                <p class="text-xs text-red-700 mt-0.5">All unit owners declined or the request was closed.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Message -->
      <div>
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Message</p>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <p class="text-sm text-slate-700 leading-relaxed" id="modalMessage">—</p>
        </div>
      </div>
    </div>
    
    <!-- MODAL FOOTER -->
    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60">
      <button type="button" onclick="closeModal()" class="btn-press px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all active:scale-95">Close</button>
      <button type="button" id="replyBtn" onclick="sendReply()" class="btn-press bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-6 py-2 rounded-xl transition-all active:scale-95 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        Reply
      </button>
    </div>
  </div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
let currentRow = null;
let currentFilter = 'pending';
let currentSearchQuery = '';

let currentInquiryId = null;
let currentUnitPreference = null;
let checkedAvailableUnits = [];
let selectedUnitIds = new Set();
let currentSentRequests = [];

let currentPage = 1;
const itemsPerPage = 10;

function setFilter(status) {
  currentFilter = status;
  currentPage = 1;
  document.querySelectorAll('.filter-btn').forEach(btn => {
    const isTarget = (btn.dataset.filter === status) || (btn.textContent.trim().toLowerCase() === status);
    if (isTarget) {
      btn.classList.add('active', 'bg-white', 'text-slate-700', 'shadow-sm');
      btn.classList.remove('text-slate-500', 'hover:bg-white/70');
    } else {
      btn.classList.remove('active', 'bg-white', 'text-slate-700', 'shadow-sm');
      btn.classList.add('text-slate-500', 'hover:bg-white/70');
    }
  });
  applyFiltersAndSearch();
}

function handleInquirySearch(query) {
  currentSearchQuery = (query || '').toLowerCase().trim();
  currentPage = 1;
  applyFiltersAndSearch();
}

function goToPage(page) {
  currentPage = page;
  applyFiltersAndSearch();
}

function applyFiltersAndSearch() {
  const rows = Array.from(document.querySelectorAll('#inqTableBody tr.inq-row'));
  const matchingRows = [];

  rows.forEach(row => {
    const rawStatus = (row.dataset.status || 'pending').toLowerCase().trim();
    const name = (row.dataset.name || '').toLowerCase();
    const email = (row.dataset.email || '').toLowerCase();
    const contact = (row.dataset.contact || '').toLowerCase();
    const type = (row.dataset.inquiryType || '').toLowerCase();
    const unit = (row.dataset.unitpref || '').toLowerCase();
    const message = (row.dataset.message || '').toLowerCase();

    let matchesStatus = false;
    if (currentFilter === 'all') {
      matchesStatus = true;
    } else if (currentFilter === 'pending') {
      matchesStatus = (rawStatus === 'pending' || rawStatus === 'onhold');
    } else if (currentFilter === 'responded') {
      matchesStatus = (rawStatus === 'responded');
    } else if (currentFilter === 'submitted') {
      matchesStatus = (rawStatus === 'reservation submitted' || rawStatus === 'officially booked');
    } else {
      matchesStatus = (rawStatus === currentFilter);
    }

    let matchesSearch = true;
    if (currentSearchQuery !== '') {
      matchesSearch = name.includes(currentSearchQuery) ||
                      email.includes(currentSearchQuery) ||
                      contact.includes(currentSearchQuery) ||
                      type.includes(currentSearchQuery) ||
                      unit.includes(currentSearchQuery) ||
                      message.includes(currentSearchQuery) ||
                      rawStatus.includes(currentSearchQuery);
    }

    if (matchesStatus && matchesSearch) {
      matchingRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const totalMatching = matchingRows.length;
  const totalPages = Math.max(1, Math.ceil(totalMatching / itemsPerPage));

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = Math.min(startIndex + itemsPerPage, totalMatching);

  matchingRows.forEach((row, index) => {
    row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
  });

  const showingStartEl = document.getElementById('inqShowingStart');
  const showingEndEl = document.getElementById('inqShowingEnd');
  const totalCountEl = document.getElementById('inqTotalCount');

  if (showingStartEl && showingEndEl && totalCountEl) {
    showingStartEl.textContent = totalMatching === 0 ? 0 : startIndex + 1;
    showingEndEl.textContent = endIndex;
    totalCountEl.textContent = totalMatching;
  }

  renderPaginationControls(totalPages, totalMatching);
}

function renderPaginationControls(totalPages, totalMatching) {
  const container = document.getElementById('inqPaginationControls');
  if (!container) return;

  if (totalMatching === 0 || totalPages <= 1) {
    container.innerHTML = `<span class="px-2 py-1 text-xs font-semibold text-slate-400">1 / 1</span>`;
    return;
  }

  let html = '';
  const prevDisabled = currentPage <= 1;
  html += `
    <button type="button" onclick="goToPage(${currentPage - 1})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 transition-all active:scale-95 ${prevDisabled ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-500 hover:bg-slate-50 cursor-pointer'}" ${prevDisabled ? 'disabled' : ''} title="Previous">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
  `;

  for (let p = 1; p <= totalPages; p++) {
    const isCurrent = p === currentPage;
    html += `
      <button type="button" onclick="goToPage(${p})" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-semibold transition-all active:scale-95 ${isCurrent ? 'bg-slate-900 text-white shadow-xs' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'}">${p}</button>
    `;
  }

  const nextDisabled = currentPage >= totalPages;
  html += `
    <button type="button" onclick="goToPage(${currentPage + 1})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 transition-all active:scale-95 ${nextDisabled ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-500 hover:bg-slate-50 cursor-pointer'}" ${nextDisabled ? 'disabled' : ''} title="Next">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
  `;

  container.innerHTML = html;
}

function openModal(row) {
  currentRow = row;
  currentInquiryId = row.dataset.inqId || null;
  currentUnitPreference = row.dataset.unitpref || '';

  resetApprovalUI();

  document.getElementById('modalSubject').textContent = row.dataset.inquiryType || '—';
  document.getElementById('modalName').textContent = row.dataset.name || '—';
  document.getElementById('modalContact').textContent = row.dataset.contact || '—';
  document.getElementById('modalEmail').textContent = row.dataset.email || '—';
  document.getElementById('modalUnitPref').textContent = row.dataset.unitpref || '—';
  document.getElementById('modalMoveInTime').textContent = row.dataset.moveInTime || '—';
  document.getElementById('modalLeaseDuration').textContent = row.dataset.leaseDuration || '—';
  document.getElementById('modalMessage').textContent = row.dataset.message || '—';

  const approvalStatus = row.dataset.approvalStatus || 'not_requested';
  const approvedUnit = row.dataset.approvedUnit || '';
  const approvedAt = row.dataset.approvedAt || '';
  const pendingCount = parseInt(row.dataset.pendingCount || '0', 10);

  let sentRequests = [];
  try {
    sentRequests = JSON.parse(row.dataset.requests || '[]');
  } catch (e) {
    sentRequests = [];
  }

  renderSentRequests(sentRequests);
  currentSentRequests = sentRequests;

  const declinedCount = sentRequests.filter(r => r.request_status === 'declined').length;

  if (approvalStatus === 'approved') {
    document.getElementById('approvalStatusText').textContent = 'Owner approved';
    document.getElementById('approvalSubText').textContent = approvedAt ? 'Approved on ' + approvedAt : 'A unit owner approved this reservation request.';
    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Approved';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0';

    document.getElementById('sentRequestsBox').classList.add('hidden');
    document.getElementById('availableUnitsBox').classList.add('hidden');
    document.getElementById('checkUnitsBtn').classList.add('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
    document.getElementById('approvedApprovalBox').classList.remove('hidden');

    document.getElementById('approvedUnitText').textContent = 'Assigned unit: ' + approvedUnit + (approvedAt ? ' • ' + approvedAt : '');
  } else if (approvalStatus === 'requested' && pendingCount > 0) {
    document.getElementById('approvalStatusText').textContent = 'Waiting for owner approval';
    document.getElementById('approvalSubText').textContent = declinedCount > 0
      ? `${declinedCount} owner(s) declined - still waiting on ${pendingCount} more.`
      : `Request was sent to ${sentRequests.length} unit owner(s).`;

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'On Hold';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 shrink-0';

    document.getElementById('waitingApprovalBox').classList.remove('hidden');
    document.getElementById('checkUnitsBtn').classList.remove('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

  const type = (row.dataset.inquiryType || '').toLowerCase();
  const hideGeneral = type.includes('general') || type.includes('other');
  const showLeaseDetails = type.includes('lease') || type.includes('rental') || type.includes('reservation');

  document.getElementById('unitSection').style.display = hideGeneral ? 'none' : '';
  document.getElementById('approvalSection').style.display = hideGeneral ? 'none' : '';
  document.getElementById('moveInTimeSection').style.display = showLeaseDetails ? '' : 'none';
  document.getElementById('leaseDurationSection').style.display = showLeaseDetails ? '' : 'none';

  const modal = document.getElementById('modalBackdrop');
  modal.classList.remove('hidden');
  requestAnimationFrame(() => modal.classList.add('open'));
}

function renderSentRequests(requests) {
  const box = document.getElementById('sentRequestsBox');
  const list = document.getElementById('sentRequestsList');
  if (!requests || requests.length === 0) {
    box.classList.add('hidden');
    list.innerHTML = '';
    return;
  }
  list.innerHTML = requests.map(r => `
    <div class="flex items-start justify-between gap-3 text-xs py-1.5 border-b border-slate-100/70 last:border-0">
      <span class="text-slate-800 font-semibold">${escapeHtml(r.unit_number)} — ${escapeHtml(r.owner_name)}</span>
      <span class="font-semibold px-2 py-0.5 rounded-full border text-[11px]">${escapeHtml(r.request_status)}</span>
    </div>
  `).join('');
  box.classList.remove('hidden');
}

function closeModal() {
  const modal = document.getElementById('modalBackdrop');
  if (!modal) return;
  modal.classList.remove('open');
  modal.classList.add('hidden');
  currentRow = null;
  currentInquiryId = null;
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('modalBackdrop')) closeModal();
}

function sendReply() {
  if (!currentRow || !currentInquiryId) {
    alert('Please select an inquiry first.');
    return;
  }
  window.location.href = BASE_URL + "/admin/inquiries/reply?inq_id=" + encodeURIComponent(currentInquiryId);
}

function resetApprovalUI() {
  checkedAvailableUnits = [];
  selectedUnitIds = new Set();
  document.getElementById("sentRequestsBox").classList.add("hidden");
  document.getElementById("availableUnitsBox").classList.remove("hidden");
  document.getElementById("availableUnitsCount").textContent = "Not checked yet";
  document.getElementById("availableUnitsList").innerHTML = "";
  document.getElementById("availableUnitsList").classList.add("hidden");
  document.getElementById("selectAllRow").classList.add("hidden");

  const checkBtn = document.getElementById("checkUnitsBtn");
  checkBtn.classList.remove("hidden");
  checkBtn.disabled = false;
  checkBtn.innerHTML = "Check Units";

  document.getElementById("sendApprovalBtn").classList.add("hidden");
  document.getElementById("waitingApprovalBox").classList.add("hidden");
  document.getElementById("approvedApprovalBox").classList.add("hidden");
  document.getElementById("declinedApprovalBox").classList.add("hidden");

  document.getElementById("approvalStatusText").textContent = "Not yet requested";
  document.getElementById("approvalSubText").textContent = "Check available units first, then send approval requests to unit owners.";
}

function escapeHtml(str) {
  return (str || '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  setFilter('pending');
});
</script>
</body>
</html>
