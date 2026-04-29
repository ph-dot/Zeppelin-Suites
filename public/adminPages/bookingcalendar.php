<!DOCTYPE html>
<html lang="en">
<head>
    <head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin — Employees</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width:256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
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
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown.open { opacity:1; visibility:visible; transform:translateY(0); }
.stat-card { background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.emp-row { transition:background 0.15s ease; }
.emp-row:hover { background:#f1f5f9; }
.emp-row .emp-name { transition:color 0.15s ease; }
.emp-row:hover .emp-name { color:#1d4ed8; }
.view-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; }
.emp-row:hover .view-btn { opacity:1; transform:translateX(0); }
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

.calendar-grid {border-left: 1px solid #e2e8f0;}
.day {min-height: 115px; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 10px;background: white; cursor: pointer;}

.day:hover { background: #f8fafc;}

.day-number {font-size: 14px;  font-weight: 600; color: #334155; margin-bottom: 8px; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">
    <div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"> </div>
    
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
      <span class="nav-badge ml-auto bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center shrink-0" style="font-size:10px;font-family:'DM Mono',monospace;">3</span>
    </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
      <span class="nav-badge ml-auto bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center shrink-0" style="font-size:10px;font-family:'DM Mono',monospace;">3</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/roomsAdmin.php" data-tooltip="Rooms" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M7 6h10a2 2 0 012 2v8a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
      <span class="nav-label">Rooms</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/employees.php" data-tooltip="Employees" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Employees</span>
    </a>
    <a href="../adminPages/settingsAdmin.php" data-tooltip="Settings" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Settings</span>
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
      <button class="relative p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">A</div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none">Admin Name</p>
            <p class="text-xs text-slate-400 mt-0.5">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-2xl py-2 z-50" id="profileDropdown">
          <a href="../adminPages/myProfileAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>My Profile</a>
          <a href="../adminPages/settingsAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Settings</a>
          <div class="border-t border-slate-100 my-1 mx-3"></div>
          <a href="public/generalViewPages/login.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Sign out</a>
        </div>
      </div>
    </div>
  </header>

  <div class="main-scroll p-6">
  <div class="app">
    <div class="glass-header border-b border-slate-100/80 px-4 py-5 mb-6 rounded-2xl">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 mb-1">Condo Booking Calendar</h1>
          <p class="text-slate-500 text-sm">Monitor and add bookings by unit type and room number.</p>
        </div>

        <button
          class="btn-press px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95"
          onclick="openBookingModal()"
        >
          + Add Booking
        </button>
      </div>
    </div>

    <div class="layout flex flex-col lg:flex-row gap-6">
      <!-- Filters Panel -->
      <aside class="panel bg-white/80 backdrop-blur-xl border border-slate-100/80 rounded-2xl p-6 shadow-xl lg:w-80 xl:w-96 shrink-0">
        <div class="filter-group mb-6">
          <h2 class="text-lg font-semibold text-slate-900 mb-4">Show Unit Types</h2>

          <div class="space-y-2">
            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-xl cursor-pointer transition-all">
              <input type="checkbox" class="type-toggle w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900 focus:ring-2" value="Studio A" checked />
              <span class="text-sm font-medium text-slate-700">Studio A</span>
            </label>

            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-xl cursor-pointer transition-all">
              <input type="checkbox" class="type-toggle w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900 focus:ring-2" value="Studio B" checked />
              <span class="text-sm font-medium text-slate-700">Studio B</span>
            </label>

            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-xl cursor-pointer transition-all">
              <input type="checkbox" class="type-toggle w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900 focus:ring-2" value="One Bedroom" checked />
              <span class="text-sm font-medium text-slate-700">One Bedroom</span>
            </label>

            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-xl cursor-pointer transition-all">
              <input type="checkbox" class="type-toggle w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900 focus:ring-2" value="Two Bedroom" checked />
              <span class="text-sm font-medium text-slate-700">Two Bedroom</span>
            </label>
          </div>
        </div>

        <div class="filter-group mb-6">
          <h2 class="text-lg font-semibold text-slate-900 mb-4">Room / Bedroom Filter</h2>

          <select
            id="roomFilter"
            onchange="renderCalendar()"
            class="zep-select w-full px-4 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:border-slate-900"
          >
            <option value="all">All 5 Rooms</option>
            <option value="1">Room 1</option>
            <option value="2">Room 2</option>
            <option value="3">Room 3</option>
            <option value="4">Room 4</option>
            <option value="5">Room 5</option>
          </select>
        </div>

        <div class="filter-group mb-8">
          <h2 class="text-lg font-semibold text-slate-900 mb-4">Legend</h2>

          <div class="space-y-2">
            <div class="flex items-center gap-3 py-2 px-3 bg-slate-50/50 rounded-xl">
              <span class="w-4 h-4 rounded-full studio-a block shrink-0"></span>
              <span class="text-sm font-medium text-slate-700">Studio A</span>
            </div>

            <div class="flex items-center gap-3 py-2 px-3 bg-slate-50/50 rounded-xl">
              <span class="w-4 h-4 rounded-full studio-b block shrink-0"></span>
              <span class="text-sm font-medium text-slate-700">Studio B</span>
            </div>

            <div class="flex items-center gap-3 py-2 px-3 bg-slate-50/50 rounded-xl">
              <span class="w-4 h-4 rounded-full one-bedroom block shrink-0"></span>
              <span class="text-sm font-medium text-slate-700">One Bedroom</span>
            </div>

            <div class="flex items-center gap-3 py-2 px-3 bg-slate-50/50 rounded-xl">
              <span class="w-4 h-4 rounded-full two-bedroom block shrink-0"></span>
              <span class="text-sm font-medium text-slate-700">Two Bedroom</span>
            </div>
          </div>
        </div>

        <button
          class="w-full btn-press px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95"
          onclick="clearBookings()"
        >
          Clear Demo Bookings
        </button>
      </aside>

     <!-- Calendar Main -->
<main class="calendar-card flex-1 bg-white/90 border border-slate-100/80 rounded-3xl overflow-hidden flex flex-col min-h-[calc(100vh-150px)]">
  <div class="calendar-header px-8 pt-8 pb-6 border-b border-slate-100/50 shrink-0">
    <div class="flex items-center justify-between">
      <h2 id="monthTitle" class="text-3xl font-bold text-slate-900"></h2>

      <div class="calendar-actions flex items-center gap-2">
        <button
          class="btn-press p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all active:scale-95"
          onclick="changeMonth(-1)"
          title="Previous Month"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <button
          class="btn-press px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95"
          onclick="goToday()"
        >
          Today
        </button>

        <button
          class="btn-press p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all active:scale-95"
          onclick="changeMonth(1)"
          title="Next Month"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <div class="weekdays grid grid-cols-7 bg-slate-50/80 border-b border-slate-100/50 shrink-0">
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Sun</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Mon</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Tue</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Wed</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Thu</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Fri</div>
    <div class="text-center text-sm font-semibold text-slate-600 py-4 font-mono">Sat</div>
  </div>

  <div id="calendarGrid" class="calendar-grid flex-1 grid grid-cols-7"></div>
</main>

 <!--add bookinf form-->
<div
  id="bookingModal"
  class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden"
  onclick="closeBookingModal(event)"
>
  <div
    class="modal bg-white backdrop-blur-xl border border-slate-200 rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"
    onclick="event.stopPropagation()"
  >
    <div class="p-8 pb-6 border-b border-slate-100">
      <h2 class="text-2xl font-bold text-slate-900 mb-1">Add Booking</h2>
      <p class="text-slate-500 text-sm">Fill out the details for the new booking.</p>
    </div>

   
    <form id="bookingForm" class="p-8 space-y-5">
      <div class="field space-y-1.5">
        <label for="guestName" class="text-sm font-semibold text-slate-700 block">Guest / Resident Name</label>
        <input id="guestName" type="text" placeholder="Example: Juan Dela Cruz" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
      </div>

      <div class="field space-y-1.5">
        <label for="unitType" class="text-sm font-semibold text-slate-700 block">Unit Type</label>
        <select id="unitType" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required>
          <option>Studio A</option>
          <option>Studio B</option>
          <option>One Bedroom</option>
          <option>Two Bedroom</option>
        </select>
      </div>

      <div class="field space-y-1.5">
        <label for="roomNumber" class="text-sm font-semibold text-slate-700 block">Room / Bedroom Number</label>
        <select id="roomNumber" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required>
          <option value="1">Room 1</option>
          <option value="2">Room 2</option>
          <option value="3">Room 3</option>
          <option value="4">Room 4</option>
          <option value="5">Room 5</option>
        </select>
      </div>

      <div class="field space-y-1.5">
        <label for="startDate" class="text-sm font-semibold text-slate-700 block">Check-in / Start Date</label>
        <input id="startDate" type="date" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
      </div>

      <div class="field space-y-1.5">
        <label for="endDate" class="text-sm font-semibold text-slate-700 block">End Date / Last Covered Date</label>
        <input id="endDate" type="date" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
      </div>

      <div class="field space-y-1.5">
        <label for="status" class="text-sm font-semibold text-slate-700 block">Status</label>
        <select id="status" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white">
          <option>Confirmed</option>
          <option>Pending</option>
          <option>Paid</option>
          <option>Cancelled</option>
        </select>
      </div>
    </form>

    <div class="px-8 pb-8 pt-2 border-t border-slate-100 flex items-center gap-3 justify-end">
      <button
        class="btn-press px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95"
        onclick="closeBookingModal()"
      >
        Cancel
      </button>

      <button
        class="btn-press px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95"
        onclick="saveBooking()"
      >
        Save Booking
      </button>
    </div>
  </div>
</div>

  <!-- Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden" onclick="closeBookingModal(event)">
        <div class="modal bg-white backdrop-blur-xl border border-slate-200 rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="p-8 pb-6 border-b border-slate-100">
            <h2 class="text-2xl font-bold text-slate-900 mb-1">Add Booking</h2>
            <p class="text-slate-500 text-sm">Fill out the details for the new booking.</p>
        </div>
        
        <form id="bookingForm" class="p-8 space-y-5">
            <div class="field space-y-1.5">
            <label for="guestName" class="text-sm font-semibold text-slate-700 block">Guest / Resident Name</label>
            <input id="guestName" type="text" placeholder="Example: Juan Dela Cruz" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
            </div>

            <div class="field space-y-1.5">
            <label for="unitType" class="text-sm font-semibold text-slate-700 block">Unit Type</label>
            <select id="unitType" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required>
                <option>Studio A</option>
                <option>Studio B</option>
                <option>One Bedroom</option>
                <option>Two Bedroom</option>
            </select>
            </div>

            <div class="field space-y-1.5">
            <label for="roomNumber" class="text-sm font-semibold text-slate-700 block">Room / Bedroom Number</label>
            <select id="roomNumber" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required>
                <option value="1">Room 1</option>
                <option value="2">Room 2</option>
                <option value="3">Room 3</option>
                <option value="4">Room 4</option>
                <option value="5">Room 5</option>
            </select>
            </div>

            <div class="field space-y-1.5">
            <label for="startDate" class="text-sm font-semibold text-slate-700 block">Check-in / Start Date</label>
            <input id="startDate" type="date" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
            </div>

            <div class="field space-y-1.5">
            <label for="endDate" class="text-sm font-semibold text-slate-700 block">End Date / Last Covered Date</label>
            <input id="endDate" type="date" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white" required />
            </div>

            <div class="field space-y-1.5">
            <label for="status" class="text-sm font-semibold text-slate-700 block">Status</label>
            <select id="status" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium bg-slate-50/50 focus:bg-white">
                <option>Confirmed</option>
                <option>Pending</option>
                <option>Paid</option>
                <option>Cancelled</option>
            </select>
            </div>
        </form>

        <div class="px-8 pb-8 pt-2 border-t border-slate-100 flex items-center gap-3 justify-end">
            <button class="btn-press px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95" onclick="closeBookingModal()">
            Cancel
            </button>
            <button class="btn-press px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow-lg hover:shadow-xl transition-all active:scale-95" onclick="saveBooking()">
            Save Booking
            </button>
        </div>
        </div>
    </div>
    </div>

  <script>
    const unitClassMap = {
      "Studio A": "studio-a",
      "Studio B": "studio-b",
      "One Bedroom": "one-bedroom",
      "Two Bedroom": "two-bedroom"
    };

    let currentDate = new Date();

    let bookings = JSON.parse(localStorage.getItem("condoBookings")) || [
      {
        id: 1,
        guestName: "Maria Santos",
        unitType: "Studio A",
        roomNumber: "1",
        startDate: getDateOffset(1),
        endDate: getDateOffset(3),
        status: "Confirmed"
      },
      {
        id: 2,
        guestName: "John Reyes",
        unitType: "One Bedroom",
        roomNumber: "3",
        startDate: getDateOffset(5),
        endDate: getDateOffset(7),
        status: "Pending"
      }
    ];

    function getDateOffset(days) {
      const date = new Date();
      date.setDate(date.getDate() + days);
      return date.toISOString().split("T")[0];
    }

    function saveToStorage() {
      localStorage.setItem("condoBookings", JSON.stringify(bookings));
    }

    function getVisibleTypes() {
      return Array.from(document.querySelectorAll(".type-toggle:checked")).map(input => input.value);
    }

    function renderCalendar() {
      const grid = document.getElementById("calendarGrid");
      const monthTitle = document.getElementById("monthTitle");
      const roomFilter = document.getElementById("roomFilter").value;

      grid.innerHTML = "";

      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();

      monthTitle.textContent = currentDate.toLocaleString("default", {
        month: "long",
        year: "numeric"
      });

      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const startDay = firstDay.getDay();
      const totalDays = lastDay.getDate();

      for (let i = 0; i < startDay; i++) {
        const emptyCell = document.createElement("div");
        emptyCell.className = "day muted";
        grid.appendChild(emptyCell);
      }

      for (let day = 1; day <= totalDays; day++) {
        const dateString = formatDate(year, month, day);
        const dayCell = document.createElement("div");
        dayCell.className = "day";
        dayCell.onclick = () => openBookingModal(dateString);

        const dayNumber = document.createElement("div");
        dayNumber.className = "day-number";
        dayNumber.textContent = day;
        dayCell.appendChild(dayNumber);

        const visibleTypes = getVisibleTypes();
        const dayBookings = bookings.filter(booking => {
          const isVisibleType = visibleTypes.includes(booking.unitType);
          const isVisibleRoom = roomFilter === "all" || booking.roomNumber === roomFilter;
          const isInDateRange = dateString >= booking.startDate && dateString <= booking.endDate;
          return isVisibleType && isVisibleRoom && isInDateRange;
        });

        dayBookings.forEach(booking => {
          const item = document.createElement("div");
          item.className = `booking ${unitClassMap[booking.unitType]}`;
          item.title = `${booking.guestName} - ${booking.unitType} Room ${booking.roomNumber}`;
          item.innerHTML = `${booking.unitType} R${booking.roomNumber}<br><small>${booking.guestName}</small>`;
          dayCell.appendChild(item);
        });

        grid.appendChild(dayCell);
      }
    }

    function formatDate(year, month, day) {
      const date = new Date(year, month, day);
      return date.toISOString().split("T")[0];
    }

    function changeMonth(direction) {
      currentDate.setMonth(currentDate.getMonth() + direction);
      renderCalendar();
    }

    function goToday() {
      currentDate = new Date();
      renderCalendar();
    }

    function openBookingModal(selectedDate = "") {
      document.getElementById("bookingModal").classList.add("show");

      if (selectedDate) {
        document.getElementById("startDate").value = selectedDate;

        const nextDay = new Date(selectedDate);
        nextDay.setDate(nextDay.getDate() + 1);
        document.getElementById("endDate").value = nextDay.toISOString().split("T")[0];
      }
    }

    function closeBookingModal() {
      document.getElementById("bookingModal").classList.remove("show");
      document.getElementById("bookingForm").reset();
    }

    function saveBooking() {
      const guestName = document.getElementById("guestName").value.trim();
      const unitType = document.getElementById("unitType").value;
      const roomNumber = document.getElementById("roomNumber").value;
      const startDate = document.getElementById("startDate").value;
      const endDate = document.getElementById("endDate").value;
      const status = document.getElementById("status").value;

      if (!guestName || !startDate || !endDate) {
        alert("Please complete all required fields.");
        return;
      }

      if (endDate < startDate) {
        alert("End date must be the same as or after the start date.");
        return;
      }

      const hasConflict = bookings.some(booking => {
        const sameRoom = booking.unitType === unitType && booking.roomNumber === roomNumber;
        const dateOverlaps = startDate <= booking.endDate && endDate >= booking.startDate;
        const activeBooking = booking.status !== "Cancelled";
        return sameRoom && dateOverlaps && activeBooking;
      });

      if (hasConflict) {
        alert("This room already has a booking for the selected dates.");
        return;
      }

      bookings.push({
        id: Date.now(),
        guestName,
        unitType,
        roomNumber,
        startDate,
        endDate,
        status
      });

      saveToStorage();
      closeBookingModal();
      renderCalendar();
    }

    function clearBookings() {
      if (confirm("Clear all bookings?")) {
        bookings = [];
        saveToStorage();
        renderCalendar();
      }
    }

    document.querySelectorAll(".type-toggle").forEach(toggle => {
      toggle.addEventListener("change", renderCalendar);
    });

    renderCalendar();
  </script>

    
</body>
</html>