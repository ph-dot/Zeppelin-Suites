<?php 
require_once '../php_files/auth.php'; 
require_once '../php_files/db.php'; 
$userData = requireRole($conn, ['admin']); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin — Inquiry</title>
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
.stat-card { transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.inq-row { transition:background 0.15s ease; cursor:pointer; }
.inq-row:hover { background:#f8fafc; }
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
    <a href="../adminPages/inquiry.php" data-tooltip="Inquiry" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
    </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
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
    <input type="text" placeholder="Search inquiries..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
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
    <h1 class="text-xl font-bold text-slate-900">Inquiries</h1>
    <div class="flex items-center gap-2">
        <div class="flex bg-slate-100 rounded-full p-1 gap-0.5 text-xs font-semibold">
            <button class="filter-btn active px-3 py-1.5 rounded-full bg-white text-slate-700 shadow-sm active:scale-95 transition-all" 
                    onclick="setFilter('all')">
                All
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full text-slate-500 hover:bg-white/70 active:scale-95 transition-all" 
                    onclick="setFilter('pending')">
                Pending
            </button>
            <button class="filter-btn px-3 py-1.5 rounded-full text-slate-500 hover:bg-white/70 active:scale-95 transition-all" 
                    onclick="setFilter('responded')">
                Responded
            </button>
        </div>
    </div>
</div>

   <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-slate-600">New today</span>
            </div>
            <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="newTodayCount">0</p>
            <p class="text-xs text-amber-500 font-semibold mt-1">↑ <span id="newTodayChange" class="text-slate-400 font-normal">calculating...</span></p>
        </div>
        
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-slate-600">Pending</span>
            </div>
            <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="pendingCount">0</p>
            <p class="text-xs text-blue-500 font-semibold mt-1">↑ <span class="text-slate-400 font-normal">awaiting reply</span></p>
        </div>
        
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-slate-600">Responded</span>
            </div>
            <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="respondedCount">0</p>
            <p class="text-xs text-emerald-500 font-semibold mt-1">↑ <span class="text-slate-400 font-normal">this week</span></p>
        </div>
    </div>
      <!-- Table -->
      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="inqTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Inquirer</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">inquiry Type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit Preference</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Message Preview</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Date Submitted</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 w-16"></th>
              </tr>
            </thead>
             <tbody class="divide-y divide-slate-50" id="inqTableBody">
                    <?php include 'ActionsAP/getInquiry.php'; ?>
                </tbody>
          </table>
        </div>

        <!-- Pagination --> 
        <div class="flex items-center justify-between flex-wrap gap-3 px-5 py-3.5 border-t border-slate-100">
          <p class="text-xs text-slate-500">
              Showing <span class="font-semibold text-slate-700">
                  <?php echo ($offset + 1); ?>–<?php echo $endItem; ?>
              </span> of 
              <span class="font-semibold text-slate-700"><?php echo $totalItems; ?></span> inquiries
          </p>
          <div class="flex items-center gap-1">
              <?php if($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?>" 
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"
                title="Previous">
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
              </a>
              <?php endif; ?>
              
              <span class="px-3 py-1 text-sm font-semibold text-slate-700">
                  <?php echo $page; ?> / <?php echo $totalPages; ?>
              </span>
              
              <?php if($page < $totalPages): ?>
              <a href="?page=<?php echo $page + 1; ?>" 
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"
                title="Next">
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                  </svg>
              </a>
              <?php endif; ?>
          </div>
      </div>

    </div>
  </main>
</div><!-- /main-wrapper -->

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
        <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    
    <!-- MODAL BODY -->
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

      <!-- SECTION 1: Sender Info + Unit -->
      <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="w-11 h-11 rounded-xl bg-slate-900 flex items-center justify-center text-white text-sm font-bold shrink-0" id="modalAvatar">
          ?
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">From</p>
          <p class="text-sm font-semibold text-slate-800 truncate" id="modalName">—</p>
          <p class="text-xs text-slate-500 truncate" id="modalEmail">—</p>
          <p class="text-xs text-slate-500 truncate" id="modalContact">—</p>
        </div>

        <!-- UNIT SECTION -->
        <div id="unitSection" class="text-right shrink-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Selected Unit</p>
          <p class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full" id="modalUnitPref">
            —
          </p>
        </div>
      </div>

      <!-- SECTION 2: Lease Duration -->
      <div id="leaseDurationSection" class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Lease Duration</p>
          <p class="text-sm font-semibold text-slate-800 truncate" id="modalLeaseDuration">—</p>
        </div>
      </div>

      <!-- SECTION 4: Reservation Approval Workflow -->
      <div id="approvalSection" class="pb-5 border-b border-slate-100 space-y-4">

        <!-- Approval Status Header -->
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-1">
              Reservation Approval
            </p>
            <p class="text-sm font-semibold text-slate-800" id="approvalStatusText">
              Not yet requested
            </p>
            <p class="text-xs text-slate-500 mt-1" id="approvalSubText">
              Check available units first, then send approval requests to unit owners.
            </p>
          </div>

          <span id="approvalStatusBadge" 
                class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
            Not Requested
          </span>
        </div>

        <!-- Available Units Box -->
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">

          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-1">
                Available Units
              </p>
              <p class="text-sm font-semibold text-slate-800" id="availableUnitsCount">
                Not checked yet
              </p>
            </div>

            <button 
              type="button"
              id="checkUnitsBtn"
              onclick="checkAvailableUnits()"
              class="btn-press text-xs font-semibold px-3 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-700 transition-all active:scale-95">
              Check Units
            </button>
          </div>

          <!-- Unit List: JS will insert available units here -->
          <div id="availableUnitsList" class="hidden space-y-2">
            <!-- Example item generated by JS:

            <div class="flex items-center justify-between bg-white border border-slate-100 rounded-xl px-3 py-2">
              <div>
                <p class="text-sm font-semibold text-slate-800">Unit 302</p>
                <p class="text-xs text-slate-500">Owner: Maria Santos</p>
              </div>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                Available
              </span>
            </div>

            -->
          </div>
        </div>

        <!-- Approval Action Buttons -->
        <div class="space-y-3">

          <button 
            type="button"
            id="sendApprovalBtn"
            onclick="sendApprovalToOwners()"
            class="hidden btn-press w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-all active:scale-95">
            Send Request to All Available Unit Owners
          </button>

          <!-- Waiting for approval -->
          <div id="waitingApprovalBox" class="hidden bg-amber-50 border border-amber-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>

              <div>
                <p class="text-sm font-semibold text-amber-800">
                  Waiting for Approval
                </p>
                <p class="text-xs text-amber-700 mt-0.5">
                  Request was sent to all available unit owners. The first owner who approves will get the reservation.
                </p>
              </div>
            </div>
          </div>

          <!-- Approved -->
          <div id="approvedApprovalBox" class="hidden bg-emerald-50 border border-emerald-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>

              <div>
                <p class="text-sm font-semibold text-emerald-800">
                  Reservation Approved
                </p>
                <p class="text-xs text-emerald-700 mt-0.5" id="approvedUnitText">
                  Assigned unit: —
                </p>
              </div>
            </div>
          </div>

          <!-- Declined / No approval -->
          <div id="declinedApprovalBox" class="hidden bg-red-50 border border-red-100 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </div>

              <div>
                <p class="text-sm font-semibold text-red-800">
                  No Approval Received
                </p>
                <p class="text-xs text-red-700 mt-0.5">
                  All unit owners declined or the request was closed.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- SECTION 5: Message -->
      <div>
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Message</p>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <p class="text-sm text-slate-700 leading-relaxed" id="modalMessage">—</p>
        </div>
      </div>

    </div>
    
    <!-- MODAL FOOTER -->
    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60">
      <button 
        type="button"
        onclick="closeModal()" 
        class="btn-press px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all active:scale-95">
        Close
      </button>

      <button 
        type="button"
        id="replyBtn"
        onclick="sendReply()" 
        class="btn-press bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-6 py-2 rounded-xl transition-all active:scale-95 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
        Reply
      </button>
    </div>

  </div>
</div>

<script>
let sidebarCollapsed = false;
let currentRow = null;
let currentFilter = 'all';

let currentInquiryId = null;
let currentUnitPreference = null;
let checkedAvailableUnits = [];

// Your existing sidebar functions (unchanged)
function toggleCollapse() {
  sidebarCollapsed = !sidebarCollapsed;
  document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
  document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
}
function openMobileSidebar() { 
  document.getElementById('sidebar').classList.add('open'); 
  document.getElementById('overlay').classList.add('show'); 
}
function closeMobileSidebar() { 
  document.getElementById('sidebar').classList.remove('open'); 
  document.getElementById('overlay').classList.remove('show'); 
}
function toggleNotice() { 
  document.getElementById('noticePanel').classList.toggle('open'); 
  document.getElementById('noticeChevron').classList.toggle('rotated'); 
}
function toggleProfile() {
  const dd = document.getElementById('profileDropdown'), ch = document.getElementById('profileChevron');
  const open = dd.classList.toggle('open'); 
  ch.style.transform = open ? 'rotate(180deg)' : '';
}
document.addEventListener('click', e => {
  const w = document.getElementById('profileWrapper');
  if (w && !w.contains(e.target)) { 
    document.getElementById('profileDropdown').classList.remove('open'); 
    document.getElementById('profileChevron').style.transform = ''; 
  }
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
  window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php'; // Your logout file
}


// Filter function
function setFilter(status) {
  currentFilter = status;
  
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.classList.remove('active', 'bg-white', 'text-slate-700', 'shadow-sm');
    btn.classList.add('text-slate-500', 'hover:bg-white/70');
  });
  
  const activeBtn = Array.from(document.querySelectorAll('.filter-btn')).find(btn => 
    btn.textContent.trim().toLowerCase() === status
  );
  if (activeBtn) {
    activeBtn.classList.add('active', 'bg-white', 'text-slate-700', 'shadow-sm');
    activeBtn.classList.remove('text-slate-500', 'hover:bg-white/70');
  }
  
  document.querySelectorAll('#inqTableBody tr.inq-row').forEach(row => {
    const rowStatus = row.dataset.status || 'pending';
    row.style.display = (status === 'all' || rowStatus === status) ? '' : 'none';
  });
}

// Stats updater - FIXED to work reliably
function updateStats() {
  console.log('🔄 Updating stats...');
  if (window.statsData) {
    document.getElementById('newTodayCount').textContent = window.statsData.newToday || 0;
    document.getElementById('pendingCount').textContent = window.statsData.pending || 0;
    document.getElementById('respondedCount').textContent = window.statsData.responded || 0;
    console.log('✅ Stats updated:', window.statsData);
  } else {
    console.log('❌ No statsData found');
  }
}

// SINGLE DOMContentLoaded - FIXED (removed duplicate)
document.addEventListener('DOMContentLoaded', function() {
  console.log('🎉 DOM loaded');
  
  // Update stats immediately
  updateStats();
  
  // Set default filter
  setFilter('all');
  
  // Wait a bit more for table data, then check again
  setTimeout(() => {
    updateStats();
    setFilter('all');
  }, 100);
  
  // Add modal CSS
  const style = document.createElement('style');
  style.textContent = `
    .modal-backdrop:not(.open) { display: none !important; }
    .modal-backdrop.open { display: flex !important; }
  `;
  document.head.appendChild(style);
});

// POLL FOR STATS - More reliable approach
let statsCheckInterval = setInterval(() => {
  if (window.statsData) {
    clearInterval(statsCheckInterval);
    updateStats();
    console.log('✅ Stats polling complete');
  }
}, 100);

// Stop polling after 5 seconds to prevent infinite loop
setTimeout(() => {
  clearInterval(statsCheckInterval);
  updateStats();
}, 5000);

// Rest of your functions (modal, reply, etc.) - unchanged
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
  document.getElementById('modalLeaseDuration').textContent = row.dataset.leaseDuration || '—';
  document.getElementById('modalMessage').textContent = row.dataset.message || '—';

  // ✅ PUT APPROVAL STATUS DISPLAY HERE
  const approvalStatus = row.dataset.approvalStatus || 'not_requested';
  const approvedUnit = row.dataset.approvedUnit || '';
  const approvedAt = row.dataset.approvedAt || '';

  if (approvalStatus === 'approved') {
    document.getElementById('approvalStatusText').textContent = 'Owner approved';
    document.getElementById('approvalSubText').textContent = approvedAt
      ? 'Approved on ' + approvedAt
      : 'A unit owner approved this reservation request.';

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Approved';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0';

    document.getElementById('availableUnitsCount').textContent = approvedUnit
      ? 'Approved unit: ' + approvedUnit
      : 'Approved unit selected';

    document.getElementById('checkUnitsBtn').classList.add('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
    document.getElementById('approvedApprovalBox').classList.remove('hidden');

    document.getElementById('approvedUnitText').textContent = approvedAt
      ? 'Assigned unit: ' + approvedUnit + ' • Approved on ' + approvedAt
      : 'Assigned unit: ' + approvedUnit;
  }

  else if (approvalStatus === 'requested') {
    document.getElementById('approvalStatusText').textContent = 'Waiting for owner approval';
    document.getElementById('approvalSubText').textContent = 'Request was sent to available unit owners.';

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'On Hold';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 shrink-0';

    document.getElementById('waitingApprovalBox').classList.remove('hidden');
    document.getElementById('checkUnitsBtn').classList.add('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

  else if (approvalStatus === 'declined') {
    document.getElementById('approvalStatusText').textContent = 'Owner declined';
    document.getElementById('approvalSubText').textContent = 'No unit owner approved this request.';

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Declined';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 shrink-0';

    document.getElementById('declinedApprovalBox').classList.remove('hidden');
    document.getElementById('checkUnitsBtn').classList.remove('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

  const type = (row.dataset.inquiryType || '').toLowerCase();
  const hideGeneral = type.includes('general') || type.includes('other');

  if (hideGeneral) {
    document.getElementById('unitSection').style.display = 'none';
    document.getElementById('leaseDurationSection').style.display = 'none';
    document.getElementById('approvalSection').style.display = 'none';
  } else {
    document.getElementById('unitSection').style.display = '';
    document.getElementById('leaseDurationSection').style.display = '';
    document.getElementById('approvalSection').style.display = '';
  }

  const modal = document.getElementById('modalBackdrop');
  modal.classList.remove('hidden');
  modal.classList.add('open');
}

function closeModal() {
  const modal = document.getElementById('modalBackdrop');

  if (!modal) return;

  modal.classList.remove('open');
  modal.classList.add('hidden');

  currentRow = null;
  currentInquiryId = null;
  currentUnitPreference = '';
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('modalBackdrop')) {
    closeModal();
  }
}

function sendReply() {
  if (!currentRow) {
    alert("No inquiry selected.");
    return;
  }

  const inqId = currentRow.dataset.inqId;

  if (!inqId) {
    alert("Inquiry ID is missing.");
    return;
  }

  window.location.href = "replyform.php?inq_id=" + encodeURIComponent(inqId);
}

function resetApprovalUI() {
  checkedAvailableUnits = [];

  document.getElementById("availableUnitsCount").textContent = "Not checked yet";
  document.getElementById("availableUnitsList").innerHTML = "";
  document.getElementById("availableUnitsList").classList.add("hidden");

  document.getElementById("checkUnitsBtn").classList.remove("hidden");

  document.getElementById("sendApprovalBtn").classList.add("hidden");
  document.getElementById("waitingApprovalBox").classList.add("hidden");
  document.getElementById("approvedApprovalBox").classList.add("hidden");
  document.getElementById("declinedApprovalBox").classList.add("hidden");

  document.getElementById("approvalStatusText").textContent = "Not yet requested";
  document.getElementById("approvalSubText").textContent = "Check available units first, then send approval requests to unit owners.";

  const badge = document.getElementById("approvalStatusBadge");
  badge.textContent = "Not Requested";
  badge.className = "text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shrink-0";
}

function checkAvailableUnits() {
  if (!currentInquiryId) {
    alert("No inquiry selected.");
    return;
  }

  if (!currentUnitPreference) {
    alert("No unit preference found for this inquiry.");
    return;
  }

  const countText = document.getElementById("availableUnitsCount");
  const list = document.getElementById("availableUnitsList");
  const sendBtn = document.getElementById("sendApprovalBtn");

  countText.textContent = "Checking...";
  list.innerHTML = "";
  list.classList.add("hidden");
  sendBtn.classList.add("hidden");

  fetch("ActionsAP/checkAvailableUnits.php?unit_type=" + encodeURIComponent(currentUnitPreference))
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        countText.textContent = "Unable to check units";
        alert(data.message || "Something went wrong.");
        return;
      }

      checkedAvailableUnits = data.units || [];

      if (checkedAvailableUnits.length === 0) {
        countText.textContent = "No available units found";
        list.innerHTML = `
          <div class="bg-white border border-slate-100 rounded-xl px-3 py-3">
            <p class="text-sm font-semibold text-slate-700">No units available</p>
            <p class="text-xs text-slate-500 mt-0.5">
              No ready units with assigned owners were found for ${currentUnitPreference}.
            </p>
          </div>
        `;
        list.classList.remove("hidden");
        return;
      }

      countText.textContent = checkedAvailableUnits.length + " available unit(s) found";

      list.innerHTML = checkedAvailableUnits.map(unit => {
        const rate = Number(unit.lease_rate || 0).toLocaleString("en-PH", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });

        return `
          <div class="flex items-center justify-between bg-white border border-slate-100 rounded-xl px-3 py-2">
            <div>
              <p class="text-sm font-semibold text-slate-800">
                ${unit.unit_number} — ${unit.unit_type}
              </p>
              <p class="text-xs text-slate-500">
                Owner: ${unit.owner_name || "No owner"} · ₱${rate}
              </p>
            </div>

            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
              Available
            </span>
          </div>
        `;
      }).join("");

      list.classList.remove("hidden");
      sendBtn.classList.remove("hidden");
    })
    .catch(error => {
      console.error(error);
      countText.textContent = "Error checking units";
      alert("Error checking available units.");
    });
}
function sendApprovalToOwners() {
  if (!currentInquiryId) {
    alert("No inquiry selected.");
    return;
  }

  if (checkedAvailableUnits.length === 0) {
    alert("Please check available units first.");
    return;
  }

  const unitIds = checkedAvailableUnits.map(unit => unit.unit_id);

  const formData = new FormData();
  formData.append("inq_id", currentInquiryId);
  formData.append("unit_ids", JSON.stringify(unitIds));

  fetch("ActionsAP/sendApprovalRequests.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || "Unable to send approval requests.");
        return;
      }

      document.getElementById("approvalStatusText").textContent = "Approval requested";
      document.getElementById("approvalSubText").textContent = "Request was sent to all available unit owners.";

      const badge = document.getElementById("approvalStatusBadge");
      badge.textContent = "Requested";
      badge.className = "text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 shrink-0";

      document.getElementById("sendApprovalBtn").classList.add("hidden");
      document.getElementById("waitingApprovalBox").classList.remove("hidden");

      alert("Approval requests sent successfully.");
    })
    .catch(error => {
      console.error(error);
      alert("Error sending approval requests.");
    });
}

</script>
</body>
</html>