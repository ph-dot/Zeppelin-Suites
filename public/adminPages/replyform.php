<?php require_once __DIR__ . '/ActionsAP/getReplyData.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Rooms</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }

/* ── Sidebar ───────────────────────────────────────────── */
.sidebar {
  width: 256px;
  transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
}
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) {
  .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; }
  .sidebar.open { transform: translateX(0); }
}
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }

/* Overlay — zero dimming */
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

/* ── Sidebar links ─────────────────────────────────────── */
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,
.sidebar.collapsed .logo-text,.sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after {
  content: attr(data-tooltip);
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
  background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px;
  border-radius: 8px; white-space: nowrap; z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none;
}
.nav-label,.logo-text { transition: opacity 0.2s ease; }
.collapse-icon { transition: transform 0.3s ease; }

/* 1. Add transition for smooth disappearing */
.sidebar-logo { 
  transition: opacity 0.2s ease, width 0.2s ease; 
}

/* 2. Hide the logo completely when .collapsed class is present on the sidebar */
.sidebar.collapsed .sidebar-logo { 
  opacity: 0; 
  width: 0; 
  overflow: hidden; 
  pointer-events: none; 
}

/* ── Dropdowns ─────────────────────────────────────────── */
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition: transform 0.3s ease; }
.notice-chevron.rotated { transform: rotate(180deg); }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown.open { opacity:1; visibility:visible; transform:translateY(0); }

/* ── Stat card hover (reused) ──────────────────────────── */
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }

/* ── Room table rows ───────────────────────────────────── */
.room-row { transition: background 0.15s ease; }
.room-row:hover { background: #f8fafc; }

/* ── Scrollbar ─────────────────────────────────────────── */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

/* ── Buttons / inputs ──────────────────────────────────── */
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }

/* ── Glass header ──────────────────────────────────────── */
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }

/* ── Upload zone ───────────────────────────────────────── */
.upload-zone { border: 2px dashed #cbd5e1; transition: border-color 0.2s, background 0.2s; }
.upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }

/* ── Facilities panel ──────────────────────────────────── */
.facilities-panel { max-height:0; overflow:hidden; opacity:0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.facilities-panel.open { max-height:200px; opacity:1; }
.fac-chevron { transition: transform 0.3s ease; }
.fac-chevron.rotated { transform: rotate(180deg); }

/* ── INDEPENDENT COLUMN SCROLL ─────────────────────────── */
.content-area {
  height: calc(100vh - 65px);
  display: flex;
  overflow: hidden;
}
.col-scroll {
  overflow-y: auto;
  height: 100%;
}
@media (max-width: 1023px) {
  .content-area { display: block; height: auto; overflow: visible; }
  .col-scroll { overflow-y: visible; height: auto; }
  .mobile-scroll-wrap { overflow-y: auto; height: calc(100vh - 65px); }
}
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay — zero dimming -->
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
    <a href="../adminPages/settingsAdmin.html" data-tooltip="Settings" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
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



<!-- ── MAIN WRAPPER ─────────────────────────────────────── -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- TOP BAR — sticky -->
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

  <!-- MAIN CONTENT AREA - Single column with scroll -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6">
    
        <!-- Inquiry Details Section -->
    <div class="w-full max-w-4xl mx-auto p-6 space-y-4 bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
    <p class="section-header text-lg font-bold text-slate-900 mb-0.5"> <?php echo replyClean($inquiry_type); ?> </p>

    <!-- Sender Info + Unit Info -->
    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
  <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white text-sm font-bold shrink-0" id="modalAvatar"> <?php echo replyClean($avatar); ?> </div>
        <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-800 truncate" id="modalName"> <?php echo replyClean($sender_name); ?> </p>
        <p class="text-xs text-slate-500 truncate" id="modalEmail"> <?php echo replyClean($sender_email); ?> </p>
        <p class="text-xs text-slate-500 truncate" id="modalContact"> <?php echo replyClean($sender_contact); ?> </p>
        </div>
        <div id="unitSection" class="text-right shrink-0" <?php echo $is_general ? 'style="display:none;"' : ''; ?>>
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Selected Unit</p>
        <p class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full" id="modalUnitPref"> <?php echo replyClean($preferred_unit); ?> </p>
        </div>
    </div>

    <!-- Lease Duration -->
    <div id="leaseDurationSection" class="flex items-center gap-3 pb-4 border-b border-slate-100" <?php echo $is_general ? 'style="display:none;"' : ''; ?>>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Lease Duration</p>
        <p class="text-sm font-semibold text-slate-800 truncate" id="modalLeaseDuration"> <?php echo replyClean($lease_duration ?: '—'); ?> </p>
      </div>
    </div>
    <!-- Message Box Section -->
    <div class="pb-4 border-b border-slate-100">
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Message</p>
        <div class="p-4 bg-gray-100 rounded-lg border border-slate-200">
       <p class="text-sm text-slate-800" id="modalMessage"><?php echo replyClean($message); ?> </p>
    </div>
    </div>
  </div>

    

    <!-- Email Reply Section -->
    <form 
      action="ActionsAP/sendInquiryReply.php" 
      method="POST"
      class="w-full max-w-4xl mx-auto p-6 space-y-6 bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm"
    >

  <input type="hidden" name="inq_id" value="<?php echo (int)$inq_id; ?>">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">Auto-Generated Email Reply</h2>
          <p class="text-sm text-slate-500 mt-1">
            Review and edit the prepared response before sending it to the inquirer.
          </p>
        </div>
       <span class="text-xs font-semibold <?php echo replyClean($status_badge_class); ?> border px-3 py-1 rounded-full">
        <?php echo replyClean($status_badge_text); ?>
      </span>
      </div>

      <!-- Email Details - Single column -->
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
            To
          </label>
        <input 
            type="email" 
            id="replyToEmail"
            name="reply_to"
            value="<?php echo replyClean($sender_email); ?>"
            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
            Subject
          </label>
          <input 
            type="text" 
            id="replySubject"
            name="reply_subject"
            value="<?php echo replyClean($reply_subject); ?>"
            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white"
          >
        </div>
      </div>

      <!-- Selected Unit Summary - Single row wrap -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
          <p class="text-xs text-blue-500 uppercase font-semibold mb-1">
            <?php echo $approval_status === 'approved' ? 'Approved Unit' : 'Preferred Unit'; ?>
          </p>
          <p class="text-sm font-bold text-blue-900">
            <?php echo replyClean($unit_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Owner</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($owner_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Rate</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($rate_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Lease Preference</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($lease_display); ?>
          </p>
        </div>
      </div>

      <!-- Big Email Body -->
      <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
          Email Message
        </label>
        <textarea 
            id="emailBody"
            name="email_body"
            rows="18"
            class="w-full min-h-[420px] border border-slate-200 rounded-2xl px-5 py-4 text-sm text-slate-700 leading-7 bg-slate-50 resize-vertical focus:outline-none focus:border-slate-900 focus:bg-white"
          ><?php echo replyClean($email_body); ?>
        </textarea>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4">
        <button 
          type="button"
          onclick="history.back()"
          class="flex-1 sm:flex-none px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all">
          Cancel
        </button>

        <div class="flex items-center gap-3">
          <button 
            type="button"
            onclick="copyEmailReply()"
            class="px-5 py-2.5 text-sm font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all">
            Copy Reply
          </button>
         <button 
            type="submit"
            class="bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-all">
            Send Reply
          </button>
        </div>
      </div>
    </form>
  </main>
</div>

<!-- Email Sent Confirmation Modal -->
<?php if (!empty($_SESSION['success_message'])): ?>
<div id="emailSentModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
    
    <div class="p-6 text-center">
      <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <h2 class="text-lg font-bold text-slate-900 mb-1">
        Email Sent
      </h2>

      <p class="text-sm text-slate-500">
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
      </p>
    </div>

    <div class="px-6 pb-6">
      <button 
        type="button"
        onclick="closeEmailSentModal()"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-all active:scale-95">
        Okay
      </button>
    </div>

  </div>
</div>

<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<!-- error modal -->
<?php if (!empty($_SESSION['error_message'])): ?>
<div id="emailErrorModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
    
    <div class="p-6 text-center">
      <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>

      <h2 class="text-lg font-bold text-slate-900 mb-1">
        Email Not Sent
      </h2>

      <p class="text-sm text-slate-500">
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
      </p>
    </div>

    <div class="px-6 pb-6">
      <button 
        type="button"
        onclick="closeEmailErrorModal()"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-all active:scale-95">
        Okay
      </button>
    </div>

  </div>
</div>

<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<script>
function copyEmailReply() {
  const body = document.getElementById("emailBody");

  body.select();
  body.setSelectionRange(0, 99999);

  document.execCommand("copy");
  alert("Reply copied.");
}

function closeEmailSentModal() {
  const modal = document.getElementById("emailSentModal");
  if (modal) {
    modal.remove();
  }
}
function closeEmailErrorModal() {
  const modal = document.getElementById("emailErrorModal");
  if (modal) {
    modal.remove();
  }
}

let sidebarCollapsed = false;

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
</script>
</body>
</html>