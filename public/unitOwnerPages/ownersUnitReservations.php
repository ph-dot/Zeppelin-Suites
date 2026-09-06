<?php 
require_once '../php_files/auth.php'; 
require_once '../php_files/db.php'; 

$userData = requireRole($conn, ['unit owner']); 
$owner_id = (int)$userData['user_id']; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Lease Management</title>
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
.collapse-icon { transition:transform 0.3s ease; }
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition:max-height 0.3s ease,opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition:transform 0.3s ease; }
.notice-chevron.rotated { transform:rotate(180deg); }
.profile-dropdown { 
  opacity:0; 
  visibility:hidden; 
  transform:translateY(-6px); 
  transition: all 0.2s cubic-bezier(0.4,0,0.2,1); 
}
.profile-dropdown:not(.hidden) { 
  opacity:1; 
  visibility:visible; 
  transform:translateY(0); 
}
.stat-card { background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.res-row { transition:background 0.15s ease; }
.res-row:hover { background:#f1f5f9; }
.res-row .res-name { transition:color 0.15s ease; }
.res-row:hover .res-name { color:#1d4ed8; }
.view-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; }
.res-row:hover .view-btn { opacity:1; transform:translateX(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
/* Modal */
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.25s ease,visibility 0.25s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(16px) scale(0.97); transition:transform 0.25s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
    <a href="overview.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm\'>ZEPPELIN SUITES</span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
  <a href="overview.php" data-tooltip="Overview" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Overview</span>
    </a>
    <a href="ownersInquiries.php" data-tooltip="Inquiries" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Inquiries</span>
    </a>
     <a href="ownersUnitReservations.php" data-tooltip="Lease Management" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Lease Management</span>
    </a>
    <a href="ownersBookingCalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>

    <a href="ownersUnit.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="tenants.php" data-tooltip="Tenants" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Tenants</span>
    </a>
    <a href="ownersMaintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="account.php" data-tooltip="Account" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="nav-label">Account</span>
    </a>
  </nav>
</aside>

<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
   <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" id="searchInput" onkeyup="filterSearch()" placeholder="Search leases..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">
            <?= htmlspecialchars($userData['initial'] ?? 'U') ?>
          </div>

          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none" id="userName">
              <?= htmlspecialchars($userData['full_name'] ?? 'Unit Owner') ?>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">Unit Owner</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <!-- Simple Dropdown -->
          <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
            <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account Settings</a>
            <div class="border-t border-slate-100 my-1"></div>
            <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
          </div>
      </div>
    </div>
  </header>

  <div id="logoutModal" onclick="if(event.target===this) hideModal()" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm hover:bg-slate-50 rounded-lg">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg">Logout</button>
      </div>
    </div>
  </div>

  <!-- ACTIVITY TIMELINE SUMMARY MODAL -->
  <div id="activityTimelineModal" onclick="if(event.target===this) closeActivityTimelineModal()" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-xl sm:max-w-2xl w-full p-6 sm:p-7 relative max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      
      <!-- Top Header -->
      <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" stroke-width="2"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"/>
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 leading-tight">Activity Timeline</h3>
            <p class="text-xs text-slate-400 mt-0.5">Track the progress of your reservation.</p>
          </div>
        </div>
        <button type="button" onclick="closeActivityTimelineModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors" title="Close">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Client Info Card at Top (User requested: "include client name on the top") -->
      <div class="mt-4 mb-3 bg-slate-50 border border-slate-100 rounded-xl p-4 flex items-center justify-between gap-4 shrink-0">
        <div class="min-w-0">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Client Name</p>
          <p id="timelineClientName" class="text-sm sm:text-base font-bold text-slate-900 leading-snug break-words">—</p>
          <p id="timelineClientEmail" class="text-xs text-slate-500 break-all mt-0.5">—</p>
        </div>
        <div class="text-right shrink-0 pl-3">
          <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Assigned Unit</span>
          <span id="timelineUnitDisplay" class="text-xs sm:text-sm font-bold text-slate-800 font-mono block mt-0.5">—</span>
        </div>
      </div>

      <!-- Timeline Scrollable Steps -->
      <div class="overflow-y-auto pr-1 flex-1 py-1 space-y-0" id="timelineStepsContainer">
        <!-- Injected via JavaScript -->
      </div>

      <!-- Modal Footer -->
      <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
        <button type="button" onclick="closeActivityTimelineModal()" class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
          Close
        </button>
        <a id="timelineViewDetailsBtn" href="#" class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-all active:scale-95">
          <span>View Details</span>
          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      </div>

    </div>
  </div>


  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Page header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold text-slate-900">Lease Management</h1>
        <div class="flex items-center gap-2">
          <button class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-600 rounded-full hover:bg-slate-50 transition-all active:scale-95">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Filter
          </button>
          <button class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
            + Add Reservation
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="resTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Res. #</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Client</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Transaction</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Amount</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Payment</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Reservation</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Submitted</th>
                <th class="px-4 py-3 w-16"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="resBody">
              <?php require_once __DIR__ . '/ActionsUOP/getOwnersUnitReservations.php'; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 flex-wrap gap-3">
          <p class="text-xs text-slate-500">Showing <span class="font-semibold text-slate-700">1–4</span> of <span class="font-semibold text-slate-700">4</span> reservations</p>
          <div class="flex items-center gap-1">
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"><svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border bg-slate-900 border-slate-900 text-white text-xs font-bold active:scale-95">1</button>
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"><svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
          </div>
        </div>
      </div>

    </div>
  </main>
</div><!-- /main-wrapper --><script>
let sidebarCollapsed = false;

function toggleCollapse() {
  sidebarCollapsed = !sidebarCollapsed;

  const sidebar = document.getElementById('sidebar');
  const mainWrapper = document.getElementById('mainWrapper');

  if (sidebar) sidebar.classList.toggle('collapsed', sidebarCollapsed);
  if (mainWrapper) mainWrapper.classList.toggle('sidebar-collapsed', sidebarCollapsed);
}

function openMobileSidebar() {
  document.getElementById('sidebar')?.classList.add('open');
  document.getElementById('overlay')?.classList.add('show');
}

function closeMobileSidebar() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('overlay')?.classList.remove('show');
}



function toggleProfile(e) {
  if (e) e.stopPropagation();
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  if (!dropdown) return;
  const isHidden = dropdown.classList.contains('hidden');
  dropdown.classList.toggle('hidden', !isHidden);
  if (chevron) {
    chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
  }
}

document.addEventListener('click', function(e) {
  const profileWrapper = document.getElementById('profileWrapper');
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  if (dropdown && !dropdown.classList.contains('hidden')) {
    if (!profileWrapper || !profileWrapper.contains(e.target)) {
      dropdown.classList.add('hidden');
      if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
  }
});

function confirmLogout() {
  const modal = document.getElementById('logoutModal');
  if (modal) modal.classList.remove('hidden');
  const dropdown = document.getElementById('profileDropdown');
  if (dropdown) dropdown.classList.add('hidden');
  const chevron = document.getElementById('profileChevron');
  if (chevron) chevron.style.transform = 'rotate(0deg)';
}

function hideModal() {
  const modal = document.getElementById('logoutModal');
  if (modal) modal.classList.add('hidden');
}

function hideLogoutModal() {
  hideModal();
}

function doLogout() {
  window.location.href = '../php_files/logout_session.php';
}

function filterSearch() {
  const input = document.getElementById('searchInput');
  const q = input ? input.value.toLowerCase() : '';

  document.querySelectorAll('#resBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// --- ACTIVITY TIMELINE MODAL FUNCTIONS ---
function openActivityTimelineModal(source) {
  let data = null;
  if (typeof source === 'string') {
    try { data = JSON.parse(source); } catch (e) { console.error(e); }
  } else if (source && source.dataset && source.dataset.timeline) {
    try { data = JSON.parse(source.dataset.timeline); } catch (e) { console.error(e); }
  } else if (typeof source === 'object' && source !== null && !source.dataset) {
    data = source;
  }

  if (!data) return;

  // Set Top Client & Unit details (User request: "include client name on the top")
  const name = data.client_name || 'Client';
  document.getElementById('timelineClientName').textContent = name;
  document.getElementById('timelineClientEmail').textContent = data.client_email || 'No email provided';
  document.getElementById('timelineUnitDisplay').textContent = data.unit_display || 'Unit';

  // View Details Button link
  document.getElementById('timelineViewDetailsBtn').href = data.view_url || ('ownersViewReservation.php?reservation_id=' + data.reservation_id);

  // Build the 5 Timeline steps
  renderActivityTimelineSteps(data);

  const modal = document.getElementById('activityTimelineModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeActivityTimelineModal() {
  const modal = document.getElementById('activityTimelineModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function renderActivityTimelineSteps(data) {
  const container = document.getElementById('timelineStepsContainer');
  if (!container) return;

  const paymentStatus = (data.payment_status || 'pending review').toLowerCase();
  const isPaymentVerified = paymentStatus === 'verified';
  const isPaymentRejected = paymentStatus === 'rejected';

  const resStatus = (data.reservation_status || '').toLowerCase();
  const isOfficiallyBooked = ['officially booked', 'reserved', 'handover', 'moved in', 'active', 'completed'].includes(resStatus) || !!data.officially_booked_at;
  const isHandedOver = ['handover', 'moved in', 'active', 'completed'].includes(resStatus) || !!data.is_moved_in;
  const areDocsComplete = (data.total_docs > 0 && data.completed_docs >= data.total_docs) || isOfficiallyBooked;
  const areDocsInProgress = (data.completed_docs > 0) || ['reserved', 'lease signing'].includes(resStatus);

  // 1. Step 1: Reservation Form Submitted (Always completed for placed reservations)
  const step1 = {
    num: 1,
    state: 'completed',
    title: 'Reservation Form Submitted',
    badge: null,
    timestamp: data.created_at || 'Submitted',
    desc: 'Reservation has been submitted by client'
  };

  // 2. Step 2: Payment Verified
  let step2 = {};
  if (isPaymentVerified) {
    step2 = {
      num: 2,
      state: 'completed',
      title: 'Payment Verified',
      badge: null,
      timestamp: data.payment_verified_at || 'Payment confirmed',
      desc: 'Payment has been verified by unit owner'
    };
  } else if (isPaymentRejected) {
    step2 = {
      num: 2,
      state: 'rejected',
      title: 'Payment Rejected',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">Rejected</span>',
      timestamp: data.payment_rejected_at || 'Payment not accepted',
      desc: 'Payment was rejected by unit owner.'
    };
  } else {
    step2 = {
      num: 2,
      state: 'in_progress',
      title: 'Payment Verification',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: 'Awaiting payment verification',
      desc: 'Payment is pending verification by unit owner.'
    };
  }

  // 3. Step 3: Lease Signing
  let step3 = {};
  if (!isPaymentVerified) {
    step3 = {
      num: 3,
      state: 'pending',
      title: 'Lease Signing',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'The lease agreement will be prepared once payment is verified.'
    };
  } else if (isOfficiallyBooked) {
    step3 = {
      num: 3,
      state: 'completed',
      title: 'Lease Signing',
      badge: null,
      timestamp: data.officially_booked_at || data.requirements_updated_at || 'Lease agreement signed',
      desc: 'The lease agreement has been signed'
    };
  } else {
    step3 = {
      num: 3,
      state: 'in_progress',
      title: 'Lease Signing',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: data.payment_verified_at || '',
      desc: 'The lease agreement is ready for signing.'
    };
  }

  // 4. Step 4: Documents
  let step4 = {};
  if (!isPaymentVerified) {
    step4 = {
      num: 4,
      state: 'pending',
      title: 'Documents',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Complete and submit the required documents.'
    };
  } else if (areDocsComplete) {
    step4 = {
      num: 4,
      state: 'completed',
      title: 'Documents',
      badge: null,
      timestamp: data.requirements_updated_at || '',
      desc: 'All required documents has been submitted and verified.'
    };
  } else if (areDocsInProgress) {
    step4 = {
      num: 4,
      state: 'in_progress',
      title: 'Documents',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: data.total_docs ? `${data.completed_docs} of ${data.total_docs} complete` : '',
      desc: 'Complete and submit the required documents.'
    };
  } else {
    step4 = {
      num: 4,
      state: 'pending',
      title: 'Documents',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Complete and submit the required documents.'
    };
  }

  // 5. Step 5: Handover
  let step5 = {};
  if (isHandedOver) {
    step5 = {
      num: 5,
      state: 'completed',
      title: 'Handover',
      badge: null,
      timestamp: data.move_in_date ? 'Move-in: ' + data.move_in_date : 'Turnover completed',
      desc: 'Unit turnover and key release completed.'
    };
  } else if (isOfficiallyBooked) {
    step5 = {
      num: 5,
      state: 'in_progress',
      title: 'Handover',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>Ready</span>',
      timestamp: data.move_in_date ? 'Scheduled for ' + data.move_in_date : 'Ready for scheduling',
      desc: 'Unit turnover and key release is ready to be scheduled.'
    };
  } else {
    step5 = {
      num: 5,
      state: 'pending',
      title: 'Handover',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Unit turnover and key release will be scheduled after lease signing.'
    };
  }

  const steps = [step1, step2, step3, step4, step5];

  let html = '';
  for (let i = 0; i < steps.length; i++) {
    const step = steps[i];
    const isLast = (i === steps.length - 1);
    const nextStep = !isLast ? steps[i + 1] : null;

    // Circle icon based on state
    let circleHtml = '';
    if (step.state === 'completed') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 z-10 shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
      `;
    } else if (step.state === 'in_progress') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 z-10 shadow-sm ring-4 ring-blue-50">
          ${step.num}
        </div>
      `;
    } else if (step.state === 'rejected') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-red-500 text-white font-bold text-xs flex items-center justify-center shrink-0 z-10 shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
      `;
    } else {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-500 font-bold text-xs flex items-center justify-center shrink-0 z-10">
          ${step.num}
        </div>
      `;
    }

    // Connecting line color
    let lineClass = 'bg-slate-200';
    if (step.state === 'completed' && nextStep && (nextStep.state === 'completed' || nextStep.state === 'in_progress')) {
      lineClass = 'bg-emerald-500';
    }

    html += `
      <div class="flex gap-3 relative ${isLast ? 'pb-1' : 'pb-5'}">
        <div class="flex flex-col items-center">
          ${circleHtml}
          ${!isLast ? `<div class="w-0.5 grow min-h-[28px] mt-1 ${lineClass}"></div>` : ''}
        </div>

        <div class="flex-1 min-w-0 pt-0.5">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <h4 class="text-sm font-bold text-slate-800 leading-tight">${step.title}</h4>
            ${step.badge || ''}
          </div>
          ${step.timestamp ? `<p class="text-xs text-slate-400 font-medium mt-0.5">${step.timestamp}</p>` : ''}
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">${step.desc}</p>
        </div>
      </div>
    `;
  }

  container.innerHTML = html;
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeActivityTimelineModal();
  }
});
</script>
</body>
</html>