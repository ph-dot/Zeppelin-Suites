<?php 
require_once '../php_files/auth.php'; 
require_once '../php_files/db.php'; 

$userData = requireRole($conn, ['admin']); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin - Reservations</title>
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
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-18.25">
    <a href="../adminPages/homeAdmin.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="../adminPages/homeAdmin.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="../adminPages/inquiry.php" data-tooltip="Inquiry" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
    </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
   </a>
   <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg  class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-range-icon lucide-calendar-range"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/residents.php" data-tooltip="Employees" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="../adminPages/analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
  </nav>
  <div class="notice-section px-2 py-4 border-t border-slate-100 shrink-0">
    <button onclick="toggleNotice()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-50 transition-all btn-press active:scale-95">
      <span class="nav-label">Notice</span>
      <svg class="notice-chevron w-3.5 h-3.5 text-slate-400 shrink-0" id="noticeChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="notice-panel open px-2 pt-1 space-y-0.5" id="noticePanel">
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Summer Vacation</span></a>
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Employment Notice</span></a>
    </div>
  </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
  <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  
  <div class="relative flex-1 max-w-sm">
    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" placeholder="Search reservations..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
  </div>
  
  <div class="flex items-center gap-2 ml-auto">
    <button class="p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95 relative">
      <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
    </button>
    
     <div class="relative">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">A</div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 truncate" id="userName">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        
        <!-- Simple Dropdown -->
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <div id="logoutModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm hover:bg-slate-50 rounded-lg">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg">Logout</button>
      </div>
    </div>
  </div>


  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Page header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold text-slate-900">Reservations</h1>
        <div class="flex items-center gap-2">
          <button class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-600 rounded-full hover:bg-slate-50 transition-all active:scale-95">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Filter
          </button>
          <a href="inquiry.php"
            class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
            View Inquiries
          </a>
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
              <?php include 'ActionsAP/getReservation.php'; ?>
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
</div><!-- /main-wrapper -->

<!-- ── EDIT / VIEW RESERVATION MODAL ──────────────────── -->
<!-- VIEW RESERVATION MODAL -->
<div id="editModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden">

    <!-- HEADER -->
    <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-white">Reservation Details</h2>
        <p id="editModalMeta" class="text-sm text-slate-300 mt-1">Reservation information</p>
      </div>

      <button 
        type="button"
        onclick="closeEditModal()"
        class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
        ×
      </button>
    </div>

    <!-- BODY -->
    <div class="p-6 overflow-y-auto max-h-[70vh] space-y-6">

      <div class="p-6 overflow-y-auto max-h-[70vh] space-y-6 bg-slate-50">

  <!-- TOP SUMMARY -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
      <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Reservation</p>
        <h3 id="view_client_name" class="text-xl font-bold text-slate-900">Client Name</h3>
        <p id="view_unit" class="text-sm text-slate-500 mt-1">Unit</p>
      </div>

      <div class="flex flex-wrap gap-2">
        <span id="view_payment_status" class="text-xs font-bold px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
          Payment Status
        </span>

        <span id="view_reservation_status" class="text-xs font-bold px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
          Reservation Status
        </span>
      </div>
    </div>
  </div>

  <!-- CLIENT + UNIT -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <!-- CLIENT CARD -->
    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center gap-2 mb-4">
        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.015-8 4.5V20h16v-1.5c0-2.485-3.582-4.5-8-4.5z"/>
          </svg>
        </div>
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Client Information</h3>
      </div>

      <div class="space-y-3">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Full Name</p>
          <p id="ef_name" class="text-sm font-semibold text-slate-800 mt-1">-</p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Email</p>
          <p id="ef_email" class="text-sm text-slate-700 mt-1 break-all">-</p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Contact</p>
          <p id="ef_contact" class="text-sm text-slate-700 mt-1">-</p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Inquiry Type</p>
          <p id="ef_inquiry_type" class="text-sm text-slate-700 mt-1">-</p>
        </div>
      </div>
    </section>

    <!-- UNIT CARD -->
    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center gap-2 mb-4">
        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
          </svg>
        </div>
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Unit Information</h3>
      </div>

      <div class="space-y-3">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit</p>
          <p id="ef_unit" class="text-sm font-semibold text-slate-800 mt-1">-</p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit Owner</p>
          <p id="ef_owner" class="text-sm text-slate-700 mt-1">-</p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Owner Email</p>
          <p id="ef_owner_email" class="text-sm text-slate-700 mt-1 break-all">-</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Transaction</p>
            <p id="ef_transaction_type" class="text-sm text-slate-700 mt-1">-</p>
          </div>

          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservation Type</p>
            <p id="ef_reservation_type" class="text-sm text-slate-700 mt-1">-</p>
          </div>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Resident Type</p>
          <p id="ef_resident_type" class="text-sm text-slate-700 mt-1">-</p>
        </div>
      </div>
    </section>

  </div>

  <!-- PAYMENT INFO + PAYMENT VERIFICATION -->
  <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between gap-4 mb-5">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h4v6h-4m0-6v6"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Payment Information</h3>
          <p class="text-xs text-slate-500 mt-0.5">Verify payment first before requirement tracking.</p>
        </div>
      </div>

      <a 
        id="ef_payment_proof" 
        href="#" 
        target="_blank"
        class="inline-flex items-center justify-center px-4 py-2 bg-blue-50 border border-blue-200 rounded-xl text-xs font-bold text-blue-700 hover:bg-blue-100 transition-all">
        View Proof
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Price Basis</p>
        <p id="ef_price_basis" class="text-base font-bold text-slate-900 mt-1">-</p>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Selected</p>
        <p id="ef_payment_percentage" class="text-base font-bold text-slate-900 mt-1">-</p>
      </div>

      <div class="bg-slate-900 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Required Amount</p>
        <p id="ef_required_amount" class="text-lg font-bold text-white mt-1">-</p>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Declared by Client</p>
        <p id="ef_declared_amount" class="text-base font-bold text-slate-900 mt-1">-</p>
        <p id="ef_amount_match_badge" class="text-xs font-bold mt-1"></p>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Method</p>
        <p id="ef_payment_method" class="text-base font-bold text-slate-900 mt-1">-</p>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">GCash Reference Number</p>
        <p id="ef_payment_reference" class="text-sm font-semibold text-slate-800 mt-1" style="font-family:'DM Mono',monospace">-</p>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Status</p>
        <p id="verify_payment_status" class="text-sm font-bold text-amber-700 mt-1">Pending Review</p>
      </div>
    </div>

    <div id="paymentDecisionDisplay" class="hidden mt-5 rounded-xl border px-4 py-3">
      <p class="text-xs font-bold uppercase tracking-wide mb-1" id="paymentDecisionLabel">
        Payment Decision
      </p>
      <p class="text-sm font-semibold" id="paymentDecisionText">
        -
      </p>
    </div>

    <div id="paymentActionButtons" class="mt-5 flex flex-col sm:flex-row gap-3">
      <button 
        type="button"
        id="btnVerifyPayment"
        class="btn-press flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
        Payment Matches — Verify
      </button>

      <button 
        type="button"
        id="btnFlagPayment"
        class="btn-press flex-1 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
        Amount Unclear — Flag for Review
      </button>

      <button 
        type="button"
        id="btnRejectPayment"
        class="btn-press flex-1 bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
        Payment Does Not Match — Reject
      </button>
    </div>

    <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
      <p class="text-xs text-amber-700 leading-relaxed">
        Reservation fee is non-refundable once verified and processed. If the payment does not match the required amount, the reservation may be rejected before requirement tracking.
      </p>
    </div>
  </section>

  <!-- SCHEDULE -->
  <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center gap-2 mb-4">
      <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Reservation Schedule</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-in / Turnover Date</p>
        <p id="ef_movein" class="text-sm font-semibold text-slate-800 mt-1">-</p>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-out Date</p>
        <p id="ef_moveout" class="text-sm font-semibold text-slate-800 mt-1">-</p>
      </div>
    </div>
  </section>

  <!-- DOCUMENT TRACKING -->
  <section id="requirementTrackingSection" class="bg-slate-50 border border-slate-200 rounded-2xl p-5 hidden">
    <div class="flex items-center justify-between gap-2 mb-5">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center">
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-7 4h8m-8 4h5m-6 8h10a2 2 0 002-2V7.8a2 2 0 00-.6-1.4l-3.8-3.8A2 2 0 0013.2 2H7a2 2 0 00-2 2v15a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Document Tracking</h3>
          <p class="text-xs text-slate-500 mt-0.5">Only available after payment is verified.</p>
        </div>
      </div>

      <button
        type="button"
        id="btnEditDocuments"
        class="hidden text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full active:scale-95 transition-all">
        Edit Documents
      </button>
    </div>

    <input type="hidden" id="process_reservation_id">

    <div id="requirementDecisionDisplay" class="hidden mb-5 rounded-xl border px-4 py-3">
      <p class="text-xs font-bold uppercase tracking-wide mb-1" id="requirementDecisionLabel">
        Requirement Status
      </p>
      <p class="text-sm font-semibold" id="requirementDecisionText">
        -
      </p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200">
            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document</th>
            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Storage</th>
            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Link</th>
          </tr>
        </thead>
        <tbody id="documentsTableBody">
          <tr>
            <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">Loading documents...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <button
      type="button"
      id="btnSaveDocuments"
      class="hidden mt-5 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
      Save Documents
    </button>

    <button 
      type="button"
      id="btnOfficiallyBooked"
      class="hidden mt-3 w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
      Mark as Officially Booked
    </button>

    <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Last Updated By</p>
      <p id="requirements_updated_by_display" class="text-sm font-semibold text-slate-800 mt-1">Not updated yet</p>
      <p id="requirements_updated_at_display" class="text-xs text-slate-500 mt-1">-</p>
    </div>
  </section>

  <!-- CANCELLATION REQUEST -->
<section id="cancellationRequestSection" class="hidden bg-red-50 border border-red-200 rounded-2xl p-5">
  <div class="flex items-center gap-2 mb-4">
    <div class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center">
      <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
    </div>

    <div>
      <h3 class="text-sm font-bold text-red-800 uppercase tracking-wide">Cancellation Request</h3>
     <p class="text-xs text-red-600 mt-0.5">A cancellation request was submitted. Admin approval is required.</p>
    </div>
  </div>

  <div class="bg-white border border-red-100 rounded-xl p-4">
    <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested At</p>
    <p id="cancel_requested_at_display" class="text-sm font-semibold text-red-800 mt-1">-</p>
  </div>

  <div class="mt-4 bg-white border border-red-100 rounded-xl p-4">
    <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Reason</p>
    <p id="cancel_reason_display" class="text-sm text-red-800 mt-1 leading-relaxed">-</p>
  </div>

  <button 
    type="button"
    id="btnApproveCancellation"
    class="mt-5 w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all">
    Approve Cancellation
  </button>
</section>

  <!-- FOOTER -->
  <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60">
    <button 
      type="button"
      onclick="closeEditModal()" 
      class="btn-press px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all active:scale-95">
      Close
    </button>
  </div>
</div>
</div>

<!-- VERIFY PAYMENT CONFIRMATION MODAL -->
<div id="verifyPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-emerald-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Verify Payment?</h2>
      <p class="text-sm text-emerald-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure the uploaded payment proof matches the required reservation amount?
      </p>

      <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
        <p class="text-xs text-emerald-700 leading-relaxed">
          This will mark the payment as verified and notify the client to proceed with the reservation requirements.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Admin Remarks / Notes
      </label>
      <textarea id="verifyPaymentRemarks" rows="3" placeholder="Optional notes..." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button 
        type="button" 
        onclick="closePaymentConfirmModal('verifyPaymentModal')" 
        class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button 
        type="button" 
        onclick="confirmPaymentAction('verify')" 
        class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl">
        Yes, Verify Payment
      </button>
    </div>
  </div>
</div>


<!-- REJECT PAYMENT CONFIRMATION MODAL -->
<div id="rejectPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-red-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Reject Payment?</h2>
      <p class="text-sm text-red-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure the uploaded payment proof does not match the required reservation amount?
      </p>

      <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
        <p class="text-xs text-red-700 leading-relaxed">
          This will reject the reservation request, notify the client, and release the unit back to available status.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Reason / Admin Remarks <span class="text-red-500">*</span>
      </label>
      <textarea id="rejectPaymentRemarks" rows="3" placeholder="Example: Submitted amount does not match the required reservation amount." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')" class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button type="button" onclick="confirmPaymentAction('reject')" class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl">
        Yes, Reject Payment
      </button>
    </div>
  </div>
</div>


<!-- FLAG PAYMENT FOR REVIEW MODAL -->
<div id="flagPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-amber-500 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Flag Payment for Review?</h2>
      <p class="text-sm text-amber-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Use this when the declared amount is close but not exact, or you need to follow up with the client before deciding.
      </p>

      <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
        <p class="text-xs text-amber-700 leading-relaxed">
          This will hold the reservation as "Flagged for Review" — the unit stays on hold and no email is sent automatically. Nothing else changes until you verify or reject it later.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Reason / Follow-up Notes <span class="text-red-500">*</span>
      </label>
      <textarea id="flagPaymentRemarks" rows="3" placeholder="Example: Declared amount is ₱200 short of the required amount, following up with client." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closePaymentConfirmModal('flagPaymentModal')" class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button type="button" onclick="confirmPaymentAction('flag')" class="px-5 py-2 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl">
        Yes, Flag for Review
      </button>
    </div>
  </div>
</div>
  </div>
</div>


<script>
  let sidebarCollapsed = false;
  let editRow = null;

  function toggleCollapse() {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
    document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
  function openMobileSidebar() { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); }
  function closeMobileSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }
  function toggleNotice() { document.getElementById('noticePanel').classList.toggle('open'); document.getElementById('noticeChevron').classList.toggle('rotated'); }
  function toggleProfile() {
    const dd = document.getElementById('profileDropdown'), ch = document.getElementById('profileChevron');
    const open = dd.classList.toggle('open'); ch.style.transform = open ? 'rotate(180deg)' : '';
  }
  document.addEventListener('click', e => {
    const w = document.getElementById('profileWrapper');
    if (w && !w.contains(e.target)) { document.getElementById('profileDropdown').classList.remove('open'); document.getElementById('profileChevron').style.transform = ''; }
  });

  document.addEventListener('DOMContentLoaded', function() {
  const userName = '<?php echo htmlspecialchars($_SESSION["full_name"] ?? "Admin"); ?>';
  const initials = '<?php echo $_SESSION["initial"] ?? "A"; ?>';
  
  document.getElementById('userName').textContent = userName;
  document.getElementById('userInitials').textContent = initials;
});

let profileOpen = false;

function toggleProfile() {
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  
  dropdown.classList.toggle('hidden');  // Toggle hidden class
  chevron.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
  const profileBtn = e.target.closest('button[onclick="toggleProfile()"]');
  const profileWrapper = document.querySelector('.relative'); // Your profile container
  
  if (!profileWrapper.contains(e.target) && !profileBtn) {
    document.getElementById('profileDropdown').classList.add('hidden');
    document.getElementById('profileChevron').style.transform = 'rotate(0deg)';
  }
});

function confirmLogout() {
  document.getElementById('logoutModal').classList.remove('hidden');
}

function hideModal() {
  document.getElementById('logoutModal').classList.add('hidden');
}

function doLogout() {
  window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php';  // Your logout file
}

  

  function filterSearch() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#resBody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
  }

function getCellText(row, idx) { return (row.cells[idx] ? row.cells[idx].textContent.trim() : ''); }

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value || '-';
  }
}
function openEditModal(row) {
  editRow = row;

  const modal = document.getElementById('editModalBackdrop');

  document.getElementById('editModalMeta').textContent =
    `Reservation #${row.dataset.reservationId || '-'} • Inquiry #${row.dataset.inquiryId || '-'}`;

  setText('view_client_name', row.dataset.clientName);
  setText('view_unit', row.dataset.unit);

  setText('ef_name', row.dataset.clientName);
  setText('ef_email', row.dataset.clientEmail);
  setText('ef_contact', row.dataset.clientContact);
  setText('ef_inquiry_type', row.dataset.inquiryType);

  setText('ef_unit', row.dataset.unit);
  setText('ef_owner', row.dataset.ownerName);
  setText('ef_owner_email', row.dataset.ownerEmail);
  setText('ef_transaction_type', row.dataset.transactionType);
  setText('ef_reservation_type', row.dataset.reservationType);
  setText('ef_resident_type', row.dataset.residentType);

  setText('ef_movein', row.dataset.moveIn);
  setText('ef_moveout', row.dataset.moveOut || 'N/A');

  setText('ef_price_basis', row.dataset.priceBasis);
  setText('ef_payment_percentage', row.dataset.paymentPercentage);
  setText('ef_required_amount', row.dataset.requiredAmount);
  setText('ef_declared_amount', row.dataset.declaredAmount || '-');
  setText('ef_payment_method', row.dataset.paymentMethod);
  setText('ef_payment_reference', row.dataset.paymentReference);

  const matchBadge = document.getElementById('ef_amount_match_badge');
  if (matchBadge) {
    const matchStatus = row.dataset.amountMatchStatus || '';
    if (matchStatus === 'match') {
      matchBadge.textContent = 'Matches required amount';
      matchBadge.className = 'text-xs font-bold mt-1 text-emerald-600';
    } else if (matchStatus === 'short') {
      matchBadge.textContent = 'Short of required amount';
      matchBadge.className = 'text-xs font-bold mt-1 text-red-600';
    } else if (matchStatus === 'over') {
      matchBadge.textContent = 'Over required amount';
      matchBadge.className = 'text-xs font-bold mt-1 text-amber-600';
    } else {
      matchBadge.textContent = '';
      matchBadge.className = 'text-xs font-bold mt-1';
    }
  }

  setText('view_payment_status', row.dataset.paymentStatus || 'Payment Status');
  setText('view_reservation_status', row.dataset.reservationStatus || 'Reservation Status');
  setText('verify_payment_status', row.dataset.paymentStatus || 'Pending Review');

  const proofLink = document.getElementById('ef_payment_proof');

  if (proofLink) {
    if (row.dataset.paymentProof) {
      proofLink.href = row.dataset.paymentProof;
      proofLink.textContent = 'View Proof';
      proofLink.classList.remove('pointer-events-none', 'opacity-50');
    } else {
      proofLink.href = '#';
      proofLink.textContent = 'No Proof Uploaded';
      proofLink.classList.add('pointer-events-none', 'opacity-50');
    }
  }

  const processId = document.getElementById('process_reservation_id');
  if (processId) {
    processId.value = row.dataset.reservationId || '';
  }

  const reservationIdForDocs = row.dataset.reservationId || '';
  documentsEditMode = false;
  currentDocuments = [];

  setText(
    'requirements_updated_by_display',
    `${row.dataset.requirementsUpdatedByName || 'Not updated yet'} (${row.dataset.requirementsUpdatedByRole || '-'})`
  );

  setText(
    'requirements_updated_at_display',
    row.dataset.requirementsUpdatedAt || '-'
  );

  updatePaymentDecisionUI(row.dataset.paymentStatus);
  updateRequirementDecisionUI(row.dataset.reservationStatus);

  if ((row.dataset.paymentStatus || '').toLowerCase() === 'verified' && reservationIdForDocs) {
    loadDocuments(reservationIdForDocs);
  }

  const cancellationSection = document.getElementById('cancellationRequestSection');
  const approveCancelBtn = document.getElementById('btnApproveCancellation');
  const cancellationStatus = (row.dataset.cancellationStatus || 'none').toLowerCase();

  setText('cancel_requested_at_display', row.dataset.cancellationRequestedAt || '-');
  setText('cancel_reason_display', row.dataset.cancellationReason || '-');

  if (cancellationSection) {
    if (cancellationStatus === 'requested') {
      cancellationSection.classList.remove('hidden');
    } else {
      cancellationSection.classList.add('hidden');
    }
  }

  if (approveCancelBtn) {
    if (cancellationStatus === 'requested') {
      approveCancelBtn.classList.remove('hidden');
    } else {
      approveCancelBtn.classList.add('hidden');
    }
  }

  modal.classList.remove('hidden');
  modal.classList.add('flex');
}



function closeEditModal() {
  const modal = document.getElementById('editModalBackdrop');

  modal.classList.add('hidden');
  modal.classList.remove('flex');

  editRow = null;
}

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeEditModal();
  }
});

document.getElementById('editModalBackdrop')?.addEventListener('click', function(event) {
  if (event.target === this) {
    closeEditModal();
  }
});

function updatePaymentDecisionUI(paymentStatus) {
  const status = (paymentStatus || '').toLowerCase();

  const buttons = document.getElementById('paymentActionButtons');
  const display = document.getElementById('paymentDecisionDisplay');
  const label = document.getElementById('paymentDecisionLabel');
  const text = document.getElementById('paymentDecisionText');
  const requirementSection = document.getElementById('requirementTrackingSection');

  if (!buttons || !display || !label || !text) return;

  display.className = 'hidden mt-5 rounded-xl border px-4 py-3';

  if (status === 'verified') {
    buttons.classList.add('hidden');

    display.classList.remove('hidden');
    display.classList.add('bg-emerald-50', 'border-emerald-200');

    label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
    text.className = 'text-sm font-semibold text-emerald-800';

    label.textContent = 'Payment Verified';
    text.textContent = 'This payment has been verified. The client was notified and requirement tracking is now available.';

    if (requirementSection) {
      requirementSection.classList.remove('hidden');
    }

    return;
  }

  if (status === 'rejected') {
    buttons.classList.add('hidden');

    display.classList.remove('hidden');
    display.classList.add('bg-red-50', 'border-red-200');

    label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-red-700';
    text.className = 'text-sm font-semibold text-red-800';

    label.textContent = 'Payment Rejected';
    text.textContent = 'This payment was rejected. The client was notified and this reservation can no longer proceed.';

    if (requirementSection) {
      requirementSection.classList.add('hidden');
    }

    return;
  }

  buttons.classList.remove('hidden');
  display.classList.add('hidden');

  if (requirementSection) {
    requirementSection.classList.add('hidden');
  }
}

function updateRequirementDecisionUI(reservationStatus) {
  const status = (reservationStatus || '').toLowerCase();

  const display = document.getElementById('requirementDecisionDisplay');
  const label = document.getElementById('requirementDecisionLabel');
  const text = document.getElementById('requirementDecisionText');
  const editBtn = document.getElementById('btnEditDocuments');
  const saveBtn = document.getElementById('btnSaveDocuments');

  if (!display || !label || !text || !editBtn || !saveBtn) return;

  display.className = 'hidden mb-5 rounded-xl border px-4 py-3';

  if (status === 'requirements completed') {
    documentsEditMode = false;
    editBtn.classList.remove('hidden');
    saveBtn.classList.add('hidden');

    display.classList.remove('hidden');
    display.classList.add('bg-emerald-50', 'border-emerald-200');

    label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
    text.className = 'text-sm font-semibold text-emerald-800';

    label.textContent = 'Requirements Completed';
    text.textContent = 'All reservation documents have been completed. You may now mark this reservation as officially booked.';

    return;
  }

  if (status === 'reserved') {
    documentsEditMode = false;
    editBtn.classList.add('hidden');
    saveBtn.classList.add('hidden');

    display.classList.remove('hidden');
    display.classList.add('bg-emerald-50', 'border-emerald-200');

    label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
    text.className = 'text-sm font-semibold text-emerald-800';

    label.textContent = 'Officially Booked';
    text.textContent = 'This reservation has already been officially booked.';

    return;
  }

  // requirements pending / default state: table starts editable
  documentsEditMode = true;
  editBtn.classList.add('hidden');
  saveBtn.classList.remove('hidden');
  display.classList.add('hidden');
}

function refreshOfficialButton(reservationStatus, allCompleted) {
  const btn = document.getElementById('btnOfficiallyBooked');
  if (!btn) return;

  const status = (reservationStatus || '').toLowerCase();

  if (status === 'reserved') {
    btn.classList.add('hidden');
    return;
  }

  if (allCompleted) {
    btn.classList.remove('hidden');
  } else {
    btn.classList.add('hidden');
  }
}

let currentDocuments = [];
let documentsEditMode = false;

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function escapeHtmlAttr(value) {
  return escapeHtml(value).replace(/"/g, '&quot;');
}

function storageDisplayLabel(doc) {
  if (doc.storage === 'dropbox') return 'Dropbox';
  if (doc.storage === 'gdrive') return 'Google Drive';
  if (doc.storage === 'other') return doc.storage_other_label || 'Other';
  return '-';
}

function loadDocuments(reservationId) {
  const tbody = document.getElementById('documentsTableBody');

  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">Loading documents...</td></tr>';
  }

  fetch('ActionsAP/getReservationDocuments.php?reservation_id=' + encodeURIComponent(reservationId))
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        if (tbody) {
          tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">' + escapeHtml(data.message || 'Failed to load documents.') + '</td></tr>';
        }
        return;
      }

      currentDocuments = data.documents || [];
      renderDocumentsTable();
      refreshOfficialButton(editRow?.dataset?.reservationStatus, data.all_completed);
    })
    .catch(error => {
      console.error(error);
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">Something went wrong while loading documents.</td></tr>';
      }
    });
}

function renderDocumentsTable() {
  const tbody = document.getElementById('documentsTableBody');
  if (!tbody) return;

  if (!currentDocuments.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No documents found.</td></tr>';
    return;
  }

  tbody.innerHTML = currentDocuments.map(doc => {
    if (!documentsEditMode) {
      const statusBadge = doc.status === 'complete'
        ? "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>Complete</span>"
        : "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>Pending</span>";

      const linkCell = doc.document_link
        ? `<a href="${escapeHtmlAttr(doc.document_link)}" target="_blank" rel="noopener" class="text-xs font-semibold text-blue-600 hover:underline">View</a>`
        : "<span class='text-xs text-slate-400'>-</span>";

      return `
        <tr class="border-b border-slate-100 last:border-0">
          <td class="px-4 py-3 font-medium text-slate-700">${escapeHtml(doc.document_name)}</td>
          <td class="px-4 py-3">${statusBadge}</td>
          <td class="px-4 py-3 text-slate-600">${escapeHtml(storageDisplayLabel(doc))}</td>
          <td class="px-4 py-3">${linkCell}</td>
        </tr>
      `;
    }

    const isOther = doc.storage === 'other';

    return `
      <tr class="border-b border-slate-100 last:border-0" data-document-id="${doc.document_id}">
        <td class="px-4 py-3 font-medium text-slate-700">${escapeHtml(doc.document_name)}</td>
        <td class="px-4 py-3">
          <select class="doc-status-input text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white">
            <option value="pending" ${doc.status !== 'complete' ? 'selected' : ''}>Pending</option>
            <option value="complete" ${doc.status === 'complete' ? 'selected' : ''}>Complete</option>
          </select>
        </td>
        <td class="px-4 py-3">
          <div class="flex flex-col gap-1.5">
            <select class="doc-storage-input text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white">
              <option value="" ${!doc.storage ? 'selected' : ''}>Select storage</option>
              <option value="dropbox" ${doc.storage === 'dropbox' ? 'selected' : ''}>Dropbox</option>
              <option value="gdrive" ${doc.storage === 'gdrive' ? 'selected' : ''}>Google Drive</option>
              <option value="other" ${isOther ? 'selected' : ''}>Other</option>
            </select>
            <input type="text" class="doc-storage-other-input text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white ${isOther ? '' : 'hidden'}" placeholder="Storage name" value="${escapeHtmlAttr(doc.storage_other_label || '')}">
          </div>
        </td>
        <td class="px-4 py-3">
          <input type="url" class="doc-link-input w-full text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white" placeholder="https://..." value="${escapeHtmlAttr(doc.document_link || '')}">
        </td>
      </tr>
    `;
  }).join('');

  if (documentsEditMode) {
    tbody.querySelectorAll('.doc-storage-input').forEach(select => {
      select.addEventListener('change', function() {
        const otherInput = this.closest('td').querySelector('.doc-storage-other-input');
        if (!otherInput) return;

        if (this.value === 'other') {
          otherInput.classList.remove('hidden');
        } else {
          otherInput.classList.add('hidden');
          otherInput.value = '';
        }
      });
    });
  }
}

document.getElementById('btnEditDocuments')?.addEventListener('click', function() {
  documentsEditMode = true;
  renderDocumentsTable();
  document.getElementById('btnEditDocuments')?.classList.add('hidden');
  document.getElementById('btnSaveDocuments')?.classList.remove('hidden');
});

function openPaymentConfirmModal(action) {
  if (action === 'verify') {
    document.getElementById('verifyPaymentRemarks').value = '';
    document.getElementById('verifyPaymentModal').classList.remove('hidden');
    document.getElementById('verifyPaymentModal').classList.add('flex');
  }

  if (action === 'flag') {
    document.getElementById('flagPaymentRemarks').value = '';
    document.getElementById('flagPaymentModal').classList.remove('hidden');
    document.getElementById('flagPaymentModal').classList.add('flex');
  }

  if (action === 'reject') {
    document.getElementById('rejectPaymentRemarks').value = '';
    document.getElementById('rejectPaymentModal').classList.remove('hidden');
    document.getElementById('rejectPaymentModal').classList.add('flex');
  }
}

function closePaymentConfirmModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function confirmPaymentAction(action) {
  const reservationId = document.getElementById('process_reservation_id')?.value;

  if (!reservationId) {
    alert('Reservation ID not found.');
    return;
  }

  let remarks = '';

  if (action === 'verify') {
    remarks = document.getElementById('verifyPaymentRemarks').value.trim();
    closePaymentConfirmModal('verifyPaymentModal');
  }

  if (action === 'flag') {
    remarks = document.getElementById('flagPaymentRemarks').value.trim();

    if (remarks === '') {
      alert('Please enter a reason for flagging this payment.');
      return;
    }

    closePaymentConfirmModal('flagPaymentModal');
  }

  if (action === 'reject') {
    remarks = document.getElementById('rejectPaymentRemarks').value.trim();

    if (remarks === '') {
      alert('Please enter a reason for rejecting the payment.');
      return;
    }

    closePaymentConfirmModal('rejectPaymentModal');
  }

  const formData = new FormData();
  formData.append('reservation_id', reservationId);
  formData.append('action', action);
  formData.append('remarks', remarks);

  fetch('ActionsAP/updatePaymentStatus.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);

    if (data.success) {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error(error);
    alert('Something went wrong while updating payment status.');
  });
}

document.getElementById('btnVerifyPayment')?.addEventListener('click', function() {
  openPaymentConfirmModal('verify');
});

document.getElementById('btnFlagPayment')?.addEventListener('click', function() {
  openPaymentConfirmModal('flag');
});

document.getElementById('btnRejectPayment')?.addEventListener('click', function() {
  openPaymentConfirmModal('reject');
});

function collectDocumentsPayload() {
  const rows = document.querySelectorAll('#documentsTableBody tr[data-document-id]');
  const payload = [];

  rows.forEach(row => {
    payload.push({
      document_id: row.dataset.documentId,
      status: row.querySelector('.doc-status-input')?.value || 'pending',
      storage: row.querySelector('.doc-storage-input')?.value || '',
      storage_other_label: row.querySelector('.doc-storage-other-input')?.value || '',
      document_link: row.querySelector('.doc-link-input')?.value || ''
    });
  });

  return payload;
}

function saveDocuments() {
  const reservationId = document.getElementById('process_reservation_id')?.value;

  if (!reservationId) {
    alert('Reservation ID not found.');
    return;
  }

  const documents = collectDocumentsPayload();

  if (!documents.length) {
    alert('No documents to save.');
    return;
  }

  const formData = new FormData();
  formData.append('reservation_id', reservationId);
  formData.append('documents', JSON.stringify(documents));

  fetch('ActionsAP/updateReservationDocuments.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);

    if (data.success) {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error(error);
    alert('Something went wrong while saving document tracking.');
  });
}

document.getElementById('btnSaveDocuments')?.addEventListener('click', saveDocuments);

function markOfficiallyBooked() {
  const reservationId = document.getElementById('process_reservation_id')?.value;

  if (!reservationId) {
    alert('Reservation ID not found.');
    return;
  }

  if (!confirm('Mark this reservation as officially booked? This will set the unit status to Reserved.')) {
    return;
  }

  const formData = new FormData();
  formData.append('reservation_id', reservationId);

  fetch('ActionsAP/markOfficiallyBooked.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);

    if (data.success) {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error(error);
    alert('Something went wrong while officially booking this reservation.');
  });
}

document.getElementById('btnOfficiallyBooked')?.addEventListener('click', markOfficiallyBooked);




function approveCancellationRequest() {
  const reservationId = document.getElementById('process_reservation_id')?.value;
  const reason = document.getElementById('cancel_reason_display')?.textContent.trim();

  if (!reservationId) {
    alert('Reservation ID not found.');
    return;
  }

  if (!confirm('Approve this cancellation request? This will cancel the reservation and release the unit.')) {
    return;
  }

  const formData = new FormData();
  formData.append('reservation_id', reservationId);
  formData.append('remarks', reason || 'Approved cancellation request from unit owner.');

  fetch('ActionsAP/cancelReservation.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);

    if (data.success) {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error(error);
    alert('Something went wrong while approving cancellation.');
  });
}

document.getElementById('btnApproveCancellation')?.addEventListener('click', approveCancellationRequest);
</script>
</body>
</html>