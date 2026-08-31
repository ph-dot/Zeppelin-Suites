<?php 
require_once '../php_files/auth.php'; 
require_once '../php_files/db.php'; 
$userData = requireRole($conn, ['admin']); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Inquiry</title>
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
    <input type="text" id="inquirySearchInput" placeholder="Search inquiries..." oninput="handleInquirySearch(this.value)" class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
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
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquirer</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Inquiry Type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Unit Preference</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide align-middle">Message Preview</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Date Submitted</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Status</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide w-20 align-middle">Action</th>
              </tr>
            </thead>
             <tbody class="divide-y divide-slate-50" id="inqTableBody">
                    <?php include 'ActionsAP/getInquiry.php'; ?>
                </tbody>
          </table>
        </div>

        <!-- Dynamic Pagination --> 
        <div class="flex items-center justify-between flex-wrap gap-3 px-5 py-3.5 border-t border-slate-100" id="inqPaginationContainer">
          <p class="text-xs text-slate-500" id="inqPaginationInfo">
              Showing <span class="font-semibold text-slate-700" id="inqShowingStart">0</span>–<span class="font-semibold text-slate-700" id="inqShowingEnd">0</span> of 
              <span class="font-semibold text-slate-700" id="inqTotalCount">0</span> inquiries
          </p>
          <div class="flex items-center gap-1" id="inqPaginationControls">
              <!-- Dynamically populated by JS -->
          </div>
        </div>

    </div>
  </main>
</div><!-- /main-wrapper -->

<!-- ── CUSTOM POPUP / ALERT / CONFIRMATION MODAL ── -->
<div id="zepAlertModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-[100] hidden items-center justify-center p-4 transition-all">
  <div id="zepAlertCard" class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-sm w-full p-6 text-center transform transition-all scale-95 opacity-0 duration-200">
    <!-- Icon Box -->
    <div id="zepAlertIconBox" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border shadow-xs"></div>
    
    <!-- Title & Text -->
    <h3 id="zepAlertTitle" class="text-base font-bold text-slate-900 mb-1.5">Notification</h3>
    <p id="zepAlertMessage" class="text-xs text-slate-500 leading-relaxed mb-6">Message content goes here.</p>
    
    <!-- Action Buttons -->
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

      <!-- SECTION 2: Move-In Time -->
      <div
        id="moveInTimeSection"
        class="flex items-center gap-3 pb-5 border-b border-slate-100">

        <div class="flex-1 min-w-0">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">
            Preferred Move-In Time
          </p>

          <p
            class="text-sm font-semibold text-slate-800 truncate"
            id="modalMoveInTime">
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

        <!-- Who the request was actually sent to -->
        <div id="sentRequestsBox" class="hidden bg-white border border-slate-100 rounded-2xl p-3 space-y-1.5">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Sent To</p>
          <div id="sentRequestsList" class="space-y-1.5"></div>
        </div>

        <!-- Available Units Box -->
        <div id="availableUnitsBox" class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">

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

          <!-- Select all / none toggle -->
          <div id="selectAllRow" class="hidden flex items-center justify-between px-1">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none">
              <input 
                type="checkbox" 
                id="selectAllUnitsCheckbox" 
                onchange="toggleSelectAllUnits(this.checked)"
                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
              Select all
            </label>
            <span id="selectedUnitsCount" class="text-xs text-slate-400">0 selected</span>
          </div>

          <!-- Unit List: JS will insert available units here -->
          <div id="availableUnitsList" class="hidden space-y-2">
            <!-- Example item generated by JS:

            <div class="flex items-center gap-3 bg-white border border-slate-100 rounded-xl px-3 py-2">
              <input type="checkbox" class="unit-select-checkbox" data-unit-id="12">
              <div class="flex-1 flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold text-slate-800">Unit 302</p>
                  <p class="text-xs text-slate-500">Owner: Maria Santos</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                  Available
                </span>
              </div>
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
            disabled
            class="hidden btn-press w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
            Send Request to Selected Unit Owner(s)
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
                  The first owner who approves will get the reservation. You can still send the request to other available owners, or cancel a pending request below, while you wait.
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
let currentFilter = 'pending';
let currentSearchQuery = '';

let currentInquiryId = null;
let currentUnitPreference = null;
let checkedAvailableUnits = [];
let selectedUnitIds = new Set();
let currentSentRequests = [];

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

let currentPage = 1;
const itemsPerPage = 10;

function doLogout() {
  window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php'; // Your logout file
}


// Filter function
function setFilter(status) {
  currentFilter = status;
  currentPage = 1; // Reset to page 1 on filter change
  
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
  currentPage = 1; // Reset to page 1 on search change
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

    // Status matching
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

    // Search query matching
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

  // Bounds check
  if (currentPage > totalPages) {
    currentPage = totalPages;
  }
  if (currentPage < 1) {
    currentPage = 1;
  }

  // Paginate matching rows
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = Math.min(startIndex + itemsPerPage, totalMatching);

  matchingRows.forEach((row, index) => {
    if (index >= startIndex && index < endIndex) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Handle dynamic empty state row if everything is filtered out
  let emptyRow = document.getElementById('inqNoResultsRow');
  if (totalMatching === 0 && rows.length > 0) {
    if (!emptyRow) {
      emptyRow = document.createElement('tr');
      emptyRow.id = 'inqNoResultsRow';
      emptyRow.innerHTML = '<td colspan="7" class="text-center px-5 py-8 text-slate-400 text-sm">No inquiries match the selected filter.</td>';
      document.getElementById('inqTableBody').appendChild(emptyRow);
    } else {
      emptyRow.style.display = '';
    }
  } else if (emptyRow) {
    emptyRow.style.display = 'none';
  }

  // Update Pagination Info
  const showingStartEl = document.getElementById('inqShowingStart');
  const showingEndEl = document.getElementById('inqShowingEnd');
  const totalCountEl = document.getElementById('inqTotalCount');

  if (showingStartEl && showingEndEl && totalCountEl) {
    showingStartEl.textContent = totalMatching === 0 ? 0 : startIndex + 1;
    showingEndEl.textContent = endIndex;
    totalCountEl.textContent = totalMatching;
  }

  // Render pagination controls
  renderPaginationControls(totalPages, totalMatching);
}

function renderPaginationControls(totalPages, totalMatching) {
  const container = document.getElementById('inqPaginationControls');
  if (!container) return;

  if (totalMatching === 0 || totalPages <= 1) {
    container.innerHTML = `
      <span class="px-2 py-1 text-xs font-semibold text-slate-400">1 / 1</span>
    `;
    return;
  }

  let html = '';

  // Previous button
  const prevDisabled = currentPage <= 1;
  html += `
    <button type="button" 
            onclick="goToPage(${currentPage - 1})"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 transition-all active:scale-95 ${prevDisabled ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-500 hover:bg-slate-50 cursor-pointer'}"
            ${prevDisabled ? 'disabled' : ''}
            title="Previous">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>
  `;

  // Page number buttons
  if (totalPages <= 7) {
    for (let p = 1; p <= totalPages; p++) {
      const isActive = p === currentPage;
      html += `
        <button type="button"
                onclick="goToPage(${p})"
                class="min-w-[32px] h-8 px-2.5 flex items-center justify-center rounded-lg text-xs font-semibold transition-all active:scale-95 ${isActive ? 'bg-slate-900 text-white shadow-xs' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'}">
          ${p}
        </button>
      `;
    }
  } else {
    // Current page / Total pages format with buttons
    for (let p = 1; p <= totalPages; p++) {
      if (p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)) {
        const isActive = p === currentPage;
        html += `
          <button type="button"
                  onclick="goToPage(${p})"
                  class="min-w-[32px] h-8 px-2.5 flex items-center justify-center rounded-lg text-xs font-semibold transition-all active:scale-95 ${isActive ? 'bg-slate-900 text-white shadow-xs' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'}">
            ${p}
          </button>
        `;
      } else if (p === currentPage - 2 || p === currentPage + 2) {
        html += `<span class="w-6 text-center text-xs text-slate-400">...</span>`;
      }
    }
  }

  // Next button
  const nextDisabled = currentPage >= totalPages;
  html += `
    <button type="button" 
            onclick="goToPage(${currentPage + 1})"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 transition-all active:scale-95 ${nextDisabled ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-500 hover:bg-slate-50 cursor-pointer'}"
            ${nextDisabled ? 'disabled' : ''}
            title="Next">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
  `;

  container.innerHTML = html;
}

// Stats updater
function updateStats() {
  if (window.statsData) {
    document.getElementById('newTodayCount').textContent = window.statsData.newToday || 0;
    document.getElementById('pendingCount').textContent = window.statsData.pending || 0;
    document.getElementById('respondedCount').textContent = window.statsData.responded || 0;
  }
}

// DOMContentLoaded setup
document.addEventListener('DOMContentLoaded', function() {
  // Update stats immediately
  updateStats();
  
  // Set default filter to 'pending'
  setFilter('pending');
  
  // Wait a bit more for table data, then refresh filter state
  setTimeout(() => {
    updateStats();
    setFilter('pending');
  }, 100);
  
  // Add modal CSS
  const style = document.createElement('style');
  style.textContent = `
    .modal-backdrop:not(.open) { display: none !important; }
    .modal-backdrop.open { display: flex !important; }
  `;
  document.head.appendChild(style);
});

// POLL FOR STATS
let statsCheckInterval = setInterval(() => {
  if (window.statsData) {
    clearInterval(statsCheckInterval);
    updateStats();
  }
}, 100);

// Stop polling after 5 seconds
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
  document.getElementById('modalMoveInTime').textContent = row.dataset.moveInTime || '—';
  document.getElementById('modalLeaseDuration').textContent = row.dataset.leaseDuration || '—';
  document.getElementById('modalMessage').textContent = row.dataset.message || '—';

  // ✅ PUT APPROVAL STATUS DISPLAY HERE
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
    document.getElementById('approvalSubText').textContent = approvedAt
      ? 'Approved on ' + approvedAt
      : 'A unit owner approved this reservation request.';

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Approved';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0';

    // The green confirmation box below already says which unit and when -
    // no need to repeat that in a "Sent To" list and an "Available Units"
    // box too.
    document.getElementById('sentRequestsBox').classList.add('hidden');
    document.getElementById('availableUnitsBox').classList.add('hidden');
    document.getElementById('checkUnitsBtn').classList.add('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
    document.getElementById('approvedApprovalBox').classList.remove('hidden');

    document.getElementById('approvedUnitText').textContent = buildApprovedText(approvedUnit, approvedAt, sentRequests);
  }

  else if (approvalStatus === 'requested' && pendingCount > 0) {
    // Still has owner(s) who haven't responded yet.
    document.getElementById('approvalStatusText').textContent = 'Waiting for owner approval';
    document.getElementById('approvalSubText').textContent = declinedCount > 0
      ? `${declinedCount} owner(s) already declined - still waiting on ${pendingCount} more.`
      : `Request was sent to ${sentRequests.length} unit owner(s).`;

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'On Hold';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 shrink-0';

    document.getElementById('waitingApprovalBox').classList.remove('hidden');
    document.getElementById('checkUnitsBtn').classList.remove('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

  else if (approvalStatus === 'requested' && pendingCount === 0) {
    // Every owner contacted so far declined, but the backend determined
    // other owners/units are still available - so this is NOT closed.
    // Let the admin check units again and send to someone else.
    document.getElementById('approvalStatusText').textContent = 'Owner(s) declined - other units available';
    document.getElementById('approvalSubText').textContent = `${declinedCount} owner(s) declined. Check units to send the request to other available owners.`;

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Needs Follow-up';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 shrink-0';

    document.getElementById('checkUnitsBtn').classList.remove('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

  else if (approvalStatus === 'declined') {
    document.getElementById('approvalStatusText').textContent = 'Owner declined';
    document.getElementById('approvalSubText').textContent = `All ${sentRequests.length} contacted owner(s) declined and no other units were available.`;

    const badge = document.getElementById('approvalStatusBadge');
    badge.textContent = 'Declined';
    badge.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 shrink-0';

    document.getElementById('declinedApprovalBox').classList.remove('hidden');
    document.getElementById('checkUnitsBtn').classList.remove('hidden');
    document.getElementById('sendApprovalBtn').classList.add('hidden');
  }

 const type = (row.dataset.inquiryType || '').toLowerCase();

  const hideGeneral =
    type.includes('general') ||
    type.includes('other');

  const showLeaseDetails =
    type === 'unit reservation' ||
    type === 'lease inquiry';

  document.getElementById('unitSection').style.display =
    hideGeneral ? 'none' : '';

  document.getElementById('approvalSection').style.display =
    hideGeneral ? 'none' : '';

  document.getElementById('moveInTimeSection').style.display =
    showLeaseDetails ? '' : 'none';

  document.getElementById('leaseDurationSection').style.display =
    showLeaseDetails ? '' : 'none';

  const modal = document.getElementById('modalBackdrop');

  modal.classList.remove('hidden');

  requestAnimationFrame(() => {
    modal.classList.add('open');
  });
}

function buildApprovedText(approvedUnit, approvedAt, sentRequests) {
  const approvedRequest = (sentRequests || []).find(r => r.request_status === 'approved');
  const ownerName = approvedRequest ? approvedRequest.owner_name : '';

  let text = 'Assigned unit: ' + approvedUnit;
  if (ownerName) {
    text += ' • Owner: ' + ownerName;
  }
  if (approvedAt) {
    text += ' • Approved on ' + approvedAt;
  }
  return text;
}

function renderSentRequests(requests) {
  const box = document.getElementById('sentRequestsBox');
  const list = document.getElementById('sentRequestsList');

  if (!requests || requests.length === 0) {
    box.classList.add('hidden');
    list.innerHTML = '';
    return;
  }

  const statusStyles = {
    pending:  ['Pending',  'bg-amber-50 text-amber-700 border-amber-200'],
    approved: ['Approved', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    declined: ['Declined', 'bg-red-50 text-red-700 border-red-200'],
    expired:  ['Expired',  'bg-slate-100 text-slate-500 border-slate-200']
  };

  list.innerHTML = requests.map(r => {
    const [label, cls] = statusStyles[r.request_status] || [r.request_status, 'bg-slate-100 text-slate-500 border-slate-200'];

    const cancelBtn = (r.request_status === 'pending' && r.request_id)
      ? `<button type="button" onclick="cancelSentRequest(${r.request_id})" class="btn-press text-[10px] font-semibold text-slate-500 border border-slate-200 bg-white hover:bg-red-50 hover:text-red-600 hover:border-red-200 px-2 py-0.5 rounded-full active:scale-95 transition-all">Cancel</button>`
      : '';

    return `
      <div class="flex items-center justify-between gap-3 text-xs">
        <span class="text-slate-700 font-medium">${escapeHtml(r.unit_number)} — ${escapeHtml(r.owner_name)}</span>
        <span class="flex items-center gap-1.5 shrink-0">
          <span class="font-semibold px-2 py-0.5 rounded-full border ${cls}">${label}</span>
          ${cancelBtn}
        </span>
      </div>
    `;
  }).join('');

  box.classList.remove('hidden');
}

let zepAlertCallback = null;
let zepCancelCallback = null;

function showZepAlert({
  type = 'success',
  title = 'Notification',
  message = '',
  btnText = 'Got it',
  onClose = null
}) {
  zepAlertCallback = onClose;
  zepCancelCallback = null;

  const modal = document.getElementById('zepAlertModal');
  const card = document.getElementById('zepAlertCard');
  const iconBox = document.getElementById('zepAlertIconBox');
  const titleEl = document.getElementById('zepAlertTitle');
  const messageEl = document.getElementById('zepAlertMessage');
  const actionsEl = document.getElementById('zepAlertActions');

  titleEl.textContent = title;
  messageEl.textContent = message;

  if (type === 'success') {
    iconBox.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-emerald-50 text-emerald-600 border-emerald-100 shadow-xs';
    iconBox.innerHTML = `
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
      </svg>
    `;
  } else if (type === 'error') {
    iconBox.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-red-50 text-red-600 border-red-100 shadow-xs';
    iconBox.innerHTML = `
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    `;
  } else if (type === 'warning') {
    iconBox.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-amber-50 text-amber-600 border-amber-100 shadow-xs';
    iconBox.innerHTML = `
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
    `;
  } else {
    iconBox.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-blue-50 text-blue-600 border-blue-100 shadow-xs';
    iconBox.innerHTML = `
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    `;
  }

  actionsEl.innerHTML = `
    <button type="button" onclick="closeZepAlert()" class="btn-press w-full py-2.5 px-4 rounded-xl bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800 transition-all active:scale-95 shadow-sm">
      ${escapeHtml(btnText)}
    </button>
  `;

  modal.classList.remove('hidden');
  modal.classList.add('flex');

  requestAnimationFrame(() => {
    card.classList.remove('scale-95', 'opacity-0');
    card.classList.add('scale-100', 'opacity-100');
  });
}

function showZepConfirm({
  title = 'Are you sure?',
  message = '',
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  isDestructive = false,
  onConfirm = null,
  onCancel = null
}) {
  zepAlertCallback = onConfirm;
  zepCancelCallback = onCancel;

  const modal = document.getElementById('zepAlertModal');
  const card = document.getElementById('zepAlertCard');
  const iconBox = document.getElementById('zepAlertIconBox');
  const titleEl = document.getElementById('zepAlertTitle');
  const messageEl = document.getElementById('zepAlertMessage');
  const actionsEl = document.getElementById('zepAlertActions');

  titleEl.textContent = title;
  messageEl.textContent = message;

  iconBox.className = isDestructive
    ? 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-red-50 text-red-600 border-red-100 shadow-xs'
    : 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border bg-amber-50 text-amber-600 border-amber-100 shadow-xs';

  iconBox.innerHTML = `
    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
  `;

  const confirmBtnClass = isDestructive
    ? 'bg-red-600 hover:bg-red-700 text-white'
    : 'bg-slate-900 hover:bg-slate-800 text-white';

  actionsEl.innerHTML = `
    <button type="button" onclick="cancelZepAlert()" class="btn-press flex-1 py-2.5 px-4 rounded-xl border border-slate-200 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-all active:scale-95">
      ${escapeHtml(cancelText)}
    </button>
    <button type="button" onclick="confirmZepAlert()" class="btn-press flex-1 py-2.5 px-4 rounded-xl ${confirmBtnClass} font-semibold text-xs transition-all active:scale-95 shadow-sm">
      ${escapeHtml(confirmText)}
    </button>
  `;

  modal.classList.remove('hidden');
  modal.classList.add('flex');

  requestAnimationFrame(() => {
    card.classList.remove('scale-95', 'opacity-0');
    card.classList.add('scale-100', 'opacity-100');
  });
}

function closeZepAlert() {
  const modal = document.getElementById('zepAlertModal');
  const card = document.getElementById('zepAlertCard');
  if (!modal || !card) return;

  card.classList.remove('scale-100', 'opacity-100');
  card.classList.add('scale-95', 'opacity-0');

  setTimeout(() => {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    if (typeof zepAlertCallback === 'function') {
      const cb = zepAlertCallback;
      zepAlertCallback = null;
      cb();
    }
  }, 150);
}

function confirmZepAlert() {
  closeZepAlert();
}

function cancelZepAlert() {
  const modal = document.getElementById('zepAlertModal');
  const card = document.getElementById('zepAlertCard');
  if (!modal || !card) return;

  card.classList.remove('scale-100', 'opacity-100');
  card.classList.add('scale-95', 'opacity-0');

  setTimeout(() => {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    zepAlertCallback = null;
    if (typeof zepCancelCallback === 'function') {
      const cb = zepCancelCallback;
      zepCancelCallback = null;
      cb();
    }
  }, 150);
}

function cancelSentRequest(requestId) {
  if (!currentInquiryId) {
    showZepAlert({ type: 'warning', title: 'Notice', message: 'No inquiry selected.' });
    return;
  }

  showZepConfirm({
    title: 'Cancel Approval Request?',
    message: 'Cancel this pending request? The unit owner will no longer be able to respond to it.',
    confirmText: 'Yes, Cancel Request',
    cancelText: 'Keep Request',
    isDestructive: true,
    onConfirm: () => {
      const formData = new FormData();
      formData.append("request_id", requestId);
      formData.append("inq_id", currentInquiryId);

      fetch("ActionsAP/cancelApprovalRequest.php", {
        method: "POST",
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            showZepAlert({ type: 'error', title: 'Cancellation Failed', message: data.message || 'Unable to cancel this request.' });
            return;
          }

          // Reflect the cancellation locally without a full page reload.
          currentSentRequests = currentSentRequests.filter(r => r.request_id !== requestId);
          renderSentRequests(currentSentRequests);

          if (currentRow) {
            currentRow.dataset.requests = JSON.stringify(currentSentRequests);
            currentRow.dataset.pendingCount = data.pending_count;
          }

          const pendingCount = data.pending_count;
          const declinedCount = currentSentRequests.filter(r => r.request_status === 'declined').length;

          if (pendingCount > 0) {
            document.getElementById('approvalSubText').textContent = declinedCount > 0
              ? `${declinedCount} owner(s) already declined - still waiting on ${pendingCount} more.`
              : `Request was sent to ${currentSentRequests.length} unit owner(s).`;
          } else {
            // No more pending requests - let the admin check units again.
            document.getElementById('waitingApprovalBox').classList.add('hidden');
            document.getElementById('checkUnitsBtn').classList.remove('hidden');
            document.getElementById('approvalStatusText').textContent = currentSentRequests.length > 0
              ? 'Owner(s) declined - other units available'
              : 'Not yet requested';
            document.getElementById('approvalSubText').textContent = currentSentRequests.length > 0
              ? `${declinedCount} owner(s) declined. Check units to send the request to other available owners.`
              : 'Check available units first, then send approval requests to unit owners.';

            const badge = document.getElementById('approvalStatusBadge');
            badge.textContent = currentSentRequests.length > 0 ? 'Needs Follow-up' : 'Not Requested';
            badge.className = currentSentRequests.length > 0
              ? 'text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 shrink-0'
              : 'text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shrink-0';
          }

          showZepAlert({ type: 'success', title: 'Request Cancelled', message: 'The approval request has been cancelled.' });
        })
        .catch(error => {
          console.error(error);
          showZepAlert({ type: 'error', title: 'Error', message: 'Error cancelling request.' });
        });
    }
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
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
    showZepAlert({ type: 'warning', title: 'Notice', message: 'No inquiry selected.' });
    return;
  }

  const inqId = currentRow.dataset.inqId;

  if (!inqId) {
    showZepAlert({ type: 'error', title: 'Error', message: 'Inquiry ID is missing.' });
    return;
  }

  window.location.href = "replyform.php?inq_id=" + encodeURIComponent(inqId);
}

function resetApprovalUI() {
  checkedAvailableUnits = [];
  selectedUnitIds = new Set();

  document.getElementById("sentRequestsBox").classList.add("hidden");
  document.getElementById("sentRequestsList").innerHTML = "";

  document.getElementById("availableUnitsBox").classList.remove("hidden");
  document.getElementById("availableUnitsCount").textContent = "Not checked yet";
  document.getElementById("availableUnitsList").innerHTML = "";
  document.getElementById("availableUnitsList").classList.add("hidden");

  document.getElementById("selectAllRow").classList.add("hidden");

  const checkBtn = document.getElementById("checkUnitsBtn");
  checkBtn.classList.remove("hidden");
  checkBtn.disabled = false;
  checkBtn.innerHTML = "Check Units";

  const sendBtn = document.getElementById("sendApprovalBtn");
  sendBtn.classList.add("hidden");
  sendBtn.disabled = true;

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
    showZepAlert({ type: 'warning', title: 'Notice', message: 'No inquiry selected.' });
    return;
  }

  if (!currentUnitPreference) {
    showZepAlert({ type: 'warning', title: 'Notice', message: 'No unit preference found for this inquiry.' });
    return;
  }

  const countText = document.getElementById("availableUnitsCount");
  const list = document.getElementById("availableUnitsList");
  const sendBtn = document.getElementById("sendApprovalBtn");
  const checkBtn = document.getElementById("checkUnitsBtn");

  countText.textContent = "Checking...";
  list.innerHTML = "";
  list.classList.add("hidden");
  sendBtn.classList.add("hidden");

  // Show loading spinner on Check Units button
  checkBtn.disabled = true;
  checkBtn.innerHTML = `
    <span class="inline-flex items-center gap-1.5">
      <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span>Checking...</span>
    </span>
  `;

  fetch(
    "ActionsAP/checkAvailableUnits.php?unit_type="
    + encodeURIComponent(currentUnitPreference)
    + "&inq_id="
    + encodeURIComponent(currentInquiryId)
    )
    .then(response => response.json())
    .then(data => {
      checkBtn.disabled = false;
      checkBtn.innerHTML = "Check Units";

      if (!data.success) {
        countText.textContent = "Unable to check units";
        showZepAlert({ type: 'error', title: 'Check Failed', message: data.message || 'Something went wrong while checking units.' });
        return;
      }

      checkedAvailableUnits = data.units || [];
      selectedUnitIds = new Set();

      const selectAllRow = document.getElementById("selectAllRow");

      if (checkedAvailableUnits.length === 0) {
        countText.textContent = "No available units found";
        const isResaleInq = data.is_resale || (currentRow && (currentRow.dataset.inquiryType || '').toLowerCase() === 'resale inquiry');
        const emptyMsg = isResaleInq
          ? `No units currently listed for Resale with assigned owners were found for ${currentUnitPreference}.`
          : `No ready units with assigned owners were found for ${currentUnitPreference}.`;
        list.innerHTML = `
          <div class="bg-white border border-slate-100 rounded-xl px-3 py-3">
            <p class="text-sm font-semibold text-slate-700">No units available</p>
            <p class="text-xs text-slate-500 mt-0.5">
              ${emptyMsg}
            </p>
          </div>
        `;
        list.classList.remove("hidden");
        selectAllRow.classList.add("hidden");
        return;
      }

      countText.textContent = checkedAvailableUnits.length + " available unit(s) found";

      list.innerHTML = checkedAvailableUnits.map(unit => {
        const hasRate = unit.lease_rate && Number(unit.lease_rate) > 0;
        const rate = hasRate
          ? Number(unit.lease_rate).toLocaleString("en-PH", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            })
          : null;

        const isResale = unit.is_resale || (currentRow && (currentRow.dataset.inquiryType || '').toLowerCase() === 'resale inquiry');

        const limitedNote = unit.limited_availability
          ? `<p class="text-xs text-amber-600 mt-1">
               ⚠ Already booked starting ${unit.next_booking_date} — only free until then.
             </p>`
          : "";

        const badge = isResale
          ? `<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100 shrink-0">
               Resale
             </span>`
          : (unit.limited_availability
            ? `<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 shrink-0">
                 Limited
               </span>`
            : `<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 shrink-0">
                 Available
               </span>`);

        const availabilityText = isResale
          ? `<p class="text-xs text-slate-500 mt-1">
               Status: <span class="font-medium text-slate-700">Listed for Resale</span>
             </p>`
          : `<p class="text-xs text-slate-500 mt-1">
               Availability:
               ${unit.availability_start}
               -
               ${unit.availability_end}
             </p>`;

        const rateText = rate ? ` · ₱${rate}` : '';

        return `
          <div class="flex items-center gap-3 bg-white border border-slate-100 rounded-xl px-3 py-2">
            <input 
              type="checkbox" 
              class="unit-select-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 shrink-0"
              data-unit-id="${unit.unit_id}"
              onchange="toggleUnitSelection(${unit.unit_id}, this.checked)">

            <div class="flex-1 flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-slate-800">
                  ${unit.unit_number} — ${unit.unit_type}
                </p>
                <p class="text-xs text-slate-500">
                  Owner: ${unit.owner_name || "No owner"}${rateText}
                </p>

                ${availabilityText}
                ${limitedNote}
              </div>

              ${badge}
            </div>
          </div>
        `;
      }).join("");

      list.classList.remove("hidden");
      selectAllRow.classList.remove("hidden");
      document.getElementById("selectAllUnitsCheckbox").checked = false;

      sendBtn.classList.remove("hidden");
      updateSelectedUnitsUI();
    })
    .catch(error => {
      console.error(error);
      checkBtn.disabled = false;
      checkBtn.innerHTML = "Check Units";
      countText.textContent = "Error checking units";
      showZepAlert({ type: 'error', title: 'Error', message: 'Error checking available units.' });
    });
}

function toggleUnitSelection(unitId, checked) {
  if (checked) {
    selectedUnitIds.add(unitId);
  } else {
    selectedUnitIds.delete(unitId);
  }

  // Keep "select all" checkbox in sync
  const selectAllCheckbox = document.getElementById("selectAllUnitsCheckbox");
  selectAllCheckbox.checked = selectedUnitIds.size === checkedAvailableUnits.length;

  updateSelectedUnitsUI();
}

function toggleSelectAllUnits(checked) {
  selectedUnitIds = new Set();

  document.querySelectorAll(".unit-select-checkbox").forEach(cb => {
    cb.checked = checked;
    if (checked) {
      selectedUnitIds.add(Number(cb.dataset.unitId));
    }
  });

  updateSelectedUnitsUI();
}

function updateSelectedUnitsUI() {
  const count = selectedUnitIds.size;
  document.getElementById("selectedUnitsCount").textContent = count + " selected";

  const sendBtn = document.getElementById("sendApprovalBtn");
  sendBtn.disabled = count === 0;
  sendBtn.innerHTML = count === 0
    ? "Select at least one unit"
    : `Send Request to ${count} Selected Unit Owner${count > 1 ? "s" : ""}`;
}

function sendApprovalToOwners() {
  if (!currentInquiryId) {
    showZepAlert({ type: 'warning', title: 'Notice', message: 'No inquiry selected.' });
    return;
  }

  if (checkedAvailableUnits.length === 0) {
    showZepAlert({ type: 'warning', title: 'Notice', message: 'Please check available units first.' });
    return;
  }

  if (selectedUnitIds.size === 0) {
    showZepAlert({ type: 'warning', title: 'Notice', message: 'Please select at least one unit to send a request for.' });
    return;
  }

  const unitIds = Array.from(selectedUnitIds);
  const count = unitIds.length;

  const sendBtn = document.getElementById("sendApprovalBtn");
  sendBtn.disabled = true;
  sendBtn.innerHTML = `
    <span class="inline-flex items-center justify-center gap-2">
      <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span>Sending Request to ${count} Unit Owner${count > 1 ? 's' : ''}...</span>
    </span>
  `;

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
        updateSelectedUnitsUI();
        showZepAlert({ type: 'error', title: 'Failed to Send', message: data.message || 'Unable to send approval requests.' });
        return;
      }

      // Add the newly-sent units to the local "Sent To" list right away,
      // so the admin sees them without waiting for a full page reload.
      const newlySent = checkedAvailableUnits
        .filter(u => selectedUnitIds.has(u.unit_id))
        .map(u => ({
          request_id: null,
          unit_number: u.unit_number,
          owner_name: u.owner_name || "No owner",
          request_status: "pending",
          requested_at: null,
          responded_at: null
        }));

      currentSentRequests = currentSentRequests
        .filter(r => r.request_status !== "pending" || !newlySent.some(n => n.unit_number === r.unit_number))
        .concat(newlySent);

      renderSentRequests(currentSentRequests);

      if (currentRow) {
        currentRow.dataset.requests = JSON.stringify(currentSentRequests);
        currentRow.dataset.approvalStatus = "requested";
        currentRow.dataset.pendingCount =
          currentSentRequests.filter(r => r.request_status === "pending").length;
      }

      document.getElementById("approvalStatusText").textContent = "Waiting for owner approval";
      document.getElementById("approvalSubText").textContent =
        `Request was sent to ${count} additional unit owner${count > 1 ? "s" : ""}.`;

      const badge = document.getElementById("approvalStatusBadge");
      badge.textContent = "On Hold";
      badge.className = "text-xs font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 shrink-0";

      document.getElementById("sendApprovalBtn").classList.add("hidden");
      document.getElementById("waitingApprovalBox").classList.remove("hidden");

      showZepAlert({
        type: 'success',
        title: 'Approval Requests Sent',
        message: `Approval request${count > 1 ? 's have' : ' has'} been successfully sent to ${count} unit owner${count > 1 ? 's' : ''}. The inquiry status is updated to On Hold.`,
        btnText: 'Great, got it'
      });
    })
    .catch(error => {
      console.error(error);
      updateSelectedUnitsUI();
      showZepAlert({ type: 'error', title: 'Error', message: 'Error sending approval requests.' });
    });
}

</script>
</body>
</html>