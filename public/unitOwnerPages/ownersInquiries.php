<?php
require_once __DIR__ . '/../php_files/auth.php';

$user = requireRole($conn, ['unit owner']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Inquiries</title>
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
.reveal-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; pointer-events:none; }
.data-row:hover .reveal-btn { opacity:1; transform:translateX(0); pointer-events:auto; }
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.22s ease,visibility 0.22s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(12px) scale(0.98); transition:transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>
 
 <div class="main-scroll p-4 md:p-6 space-y-6">
  <div class="max-w-screen-xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-slate-900">Inquiries</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
      <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm font-semibold">
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
      <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-3 rounded-xl text-sm font-semibold">
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
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
              <th class="text-center px-5 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquirer</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Contact</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquiry Type</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Status</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Owner Decision</th>
              <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide w-20 align-middle whitespace-nowrap">Action</th>
            </tr>
          </thead>

          <tbody id="resBody">
            <?php include 'ActionsUOP/getOwnerApprovalRequests.php'; ?>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100">
        <p class="text-xs text-slate-400">Showing Inquiries</p>

        <div class="flex items-center gap-1">
          <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border bg-slate-900 border-slate-900 text-white text-xs font-bold active:scale-95">
            1
          </button>

          <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
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
      
      <!-- TOP CARD: Inquirer & Inquiry Info (3-column layout matching mockup) -->
      <div class="bg-slate-50/90 border border-slate-200/80 rounded-2xl p-5 space-y-4 shadow-2xs">
        <!-- Row 1: Inquirer Details -->
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

        <!-- Row 2: Inquiry Parameters -->
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

        <!-- Row 3: Message -->
        <div class="pt-1">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">message</p>
          <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
            <p class="text-xs text-slate-700 leading-relaxed max-h-28 overflow-y-auto whitespace-pre-line" id="mResMessage">—</p>
          </div>
        </div>
      </div>

      <!-- MIDDLE ROW: Unit Information Row & Availability -->
      <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <!-- Col 1: Unit & Floor -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
              <span>Unit Available:</span>
              <span class="text-slate-400 font-medium text-[11px]" id="mResFloorDisplay">Floor —</span>
            </label>
            <div class="h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 flex items-center shadow-2xs font-mono">
              <span id="mResUnitDisplay">Unit —</span>
            </div>
          </div>

          <!-- Col 2: Lease Rate -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
              <span>Lease Rate</span>
              <span class="text-slate-400 font-normal text-[11px]">Monthly</span>
            </label>
            <div class="h-10 px-3.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 flex items-center shadow-2xs font-mono" id="mResFee">
              —
            </div>
          </div>

          <!-- Col 3: Current Status & Type -->
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

        <!-- Occupied / Active Lease Duration Banner (Visible when unit is occupied) -->
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
            <span class="text-[11px] font-semibold text-amber-800 bg-amber-100/90 border border-amber-200 px-2.5 py-0.5 rounded-full" id="mResOccupiedUntilBadge">
              Occupied
            </span>
          </div>
        </div>

        <!-- Availability Period Banner -->
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

      <!-- BOTTOM ROW: Unit Owner Remarks -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="mOwnerRemarks" class="block text-xs font-bold text-slate-700">
            Unit Owner Remarks:
          </label>
          <span class="text-[11px] text-slate-400 italic" id="mOwnerRemarksHint">Optional note for this inquiry</span>
        </div>
        <textarea 
          id="mOwnerRemarks" 
          rows="3" 
          placeholder="Add a note for this inquiry..." 
          class="w-full bg-white border border-slate-200 rounded-xl p-3.5 text-xs text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900 focus:outline-none transition-all resize-none shadow-2xs"></textarea>
      </div>

    </div>

    <!-- MODAL FOOTER: Decline & Approve (matching mockup) -->
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
    
    <!-- Header -->
    <div class="p-6 pb-2">
      <div class="flex items-start justify-between">
        <h3 class="text-lg font-bold text-slate-900">Are you sure?</h3>
        <button 
          type="button" 
          onclick="closeModal('approveConfirmModal')" 
          class="btn-press p-1.5 -mr-1 -mt-1 rounded-xl hover:bg-slate-100 transition-colors active:scale-95 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <p class="text-xs text-slate-500 mt-1 leading-relaxed">
        Are you sure you want to approve this inquiry request for <span class="font-bold text-slate-800" id="confirmClientName">the client</span>?
      </p>
    </div>

    <!-- Note -->
    <div class="px-6 py-3">
      <p class="text-xs text-slate-500 leading-relaxed">
        <span class="font-semibold text-slate-700">Note:</span> Your information will be disclosed to the client upon approval, including your <span class="font-medium text-slate-700">unit details (<span id="confirmUnitNumber" class="font-semibold text-slate-900">your unit</span>)</span> and your <span class="font-medium text-slate-700">contact information</span> (full name, phone number, and email).
      </p>
    </div>

    <!-- Modal Footer Actions -->
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
      <button 
        type="button" 
        onclick="closeModal('approveConfirmModal')" 
        class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-2xs">
        Cancel
      </button>
      <button 
        type="button" 
        id="confirmApproveSubmitBtn"
        onclick="submitApprovedRequest()" 
        class="btn-press inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-sm active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
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
        <button 
          type="button" 
          onclick="closeModal('declineConfirmModal')" 
          class="btn-press p-1.5 -mr-1 -mt-1 rounded-xl hover:bg-slate-100 transition-colors active:scale-95 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
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
      <button 
        type="button" 
        onclick="closeModal('declineConfirmModal')" 
        class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-2xs">
        Cancel
      </button>
      <button 
        type="button" 
        id="confirmDeclineSubmitBtn"
        onclick="submitDeclinedRequest()" 
        class="btn-press inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-sm active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span>Yes, Decline Request</span>
      </button>
    </div>

  </div>
</div>

<script>
  let sidebarCollapsed = false;
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

    // Occupied / Current Lease Banner toggle and content
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
        if (availHeaderLabel) {
          availHeaderLabel.textContent = 'Next Availability:';
        }
      } else {
        occupiedBanner.classList.add('hidden');
        if (availHeaderLabel) {
          availHeaderLabel.textContent = 'Unit Availability:';
        }
      }
    }

    document.getElementById('mResType').textContent = row.dataset.type || '—';
    document.getElementById('mResFee').textContent = row.dataset.fee || '—';
    document.getElementById('mResMoveIn').textContent = row.dataset.moveIn || '—';
    document.getElementById('mResLease').textContent = row.dataset.lease || '—';
    document.getElementById('mResMessage').textContent = row.dataset.message || '—';

    // Unit Availability display
    let availDisplay = row.dataset.availDisplay;
    let availLabel = row.dataset.availLabel;

    if (!availDisplay) {
      const moveInVal = (row.dataset.moveIn || '').trim();
      const leaseVal = (row.dataset.lease || '').trim().toLowerCase();
      
      const now = new Date();
      let startDate = new Date();
      
      const parsedDate = Date.parse(moveInVal);
      if (!isNaN(parsedDate) && parsedDate > now.getTime() && !['immediately', 'not sure yet'].includes(moveInVal.toLowerCase())) {
        startDate = new Date(parsedDate);
      }

      let months = 0;
      const yrMatch = leaseVal.match(/(\d+)\s*(?:year|yr)/);
      const moMatch = leaseVal.match(/(\d+)\s*(?:month|mo)/);
      if (yrMatch) {
        months = parseInt(yrMatch[1], 10) * 12;
      } else if (moMatch) {
        months = parseInt(moMatch[1], 10);
      }

      const formatDate = (d) => d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
      const startStr = formatDate(startDate);

      const twoYrDate = new Date(startDate);
      twoYrDate.setFullYear(twoYrDate.getFullYear() + 2);
      const twoYrStr = formatDate(twoYrDate);

      if (months > 0 && months < 24) {
        const endReqDate = new Date(startDate);
        endReqDate.setMonth(endReqDate.getMonth() + months);
        availDisplay = `${startStr} – ${formatDate(endReqDate)}`;
        availLabel = `Duration: ${months} mos (up to 2 yrs)`;
      } else {
        availDisplay = `${startStr} – ${twoYrStr}`;
        availLabel = `Duration: 2 Years`;
      }
    }

    const availEl = document.getElementById('mResAvailability');
    if (availEl) availEl.textContent = availDisplay || '—';

    const availLabelEl = document.getElementById('mResAvailDurationLabel');
    if (availLabelEl) availLabelEl.textContent = availLabel || '—';

    const inqType = (row.dataset.type || '').toLowerCase().trim();
    const isResale = inqType.includes('resale');
    const isLeaseOrReservation = (inqType.includes('reservation') || inqType.includes('lease')) && !isResale;

    const moveInRow = document.getElementById('mResMoveInRow');
    const leaseRow = document.getElementById('mResLeaseRow');

    if (moveInRow) {
      moveInRow.style.display = isLeaseOrReservation ? 'block' : 'none';
    }
    if (leaseRow) {
      leaseRow.style.display = isLeaseOrReservation ? 'block' : 'none';
    }

    // Handle remarks box
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

      approveBtn.textContent = 'Approve';
      declineBtn.textContent = 'Decline';
    } else {
      approveBtn.disabled = true;
      declineBtn.disabled = true;

      approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
      declineBtn.classList.add('opacity-50', 'cursor-not-allowed');

      approveBtn.textContent = status === 'Approved' ? 'Approved' : 'Approve';
      declineBtn.textContent = status === 'Declined' ? 'Declined' : 'Decline';
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
    if (!currentResId) {
      alert("No request selected.");
      return;
    }

    const clientNameEl = document.getElementById('confirmClientName');
    if (clientNameEl) clientNameEl.textContent = currentClientName;

    const unitEl = document.getElementById('confirmUnitNumber');
    if (unitEl) unitEl.textContent = currentUnitNumber !== '—' && currentUnitNumber ? `Unit ${currentUnitNumber}` : 'your unit';

    const btn = document.getElementById('confirmApproveSubmitBtn');
    if (btn) {
      btn.disabled = false;
      btn.classList.remove('opacity-75', 'cursor-not-allowed');
      btn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Yes, Approve Request</span>
      `;
    }

    openModal('approveConfirmModal');
  }

  function handleDecline() {
    if (!currentResId) {
      alert("No request selected.");
      return;
    }

    const clientNameEl = document.getElementById('declineClientName');
    if (clientNameEl) clientNameEl.textContent = currentClientName;

    const btn = document.getElementById('confirmDeclineSubmitBtn');
    if (btn) {
      btn.disabled = false;
      btn.classList.remove('opacity-75', 'cursor-not-allowed');
      btn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span>Yes, Decline Request</span>
      `;
    }

    openModal('declineConfirmModal');
  }

  function submitApprovedRequest() {
    if (!currentResId) return;

    const btn = document.getElementById('confirmApproveSubmitBtn');
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-75', 'cursor-not-allowed');
      btn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        Approving...
      `;
    }

    executeDecisionForm('approve');
  }

  function submitDeclinedRequest() {
    if (!currentResId) return;

    const btn = document.getElementById('confirmDeclineSubmitBtn');
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-75', 'cursor-not-allowed');
      btn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        Declining...
      `;
    }

    executeDecisionForm('decline');
  }

  function executeDecisionForm(action) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "ActionsUOP/respondApprovalRequest.php";

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
</script>
</body>
</html>
