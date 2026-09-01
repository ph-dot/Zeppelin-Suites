<?php 
require_once __DIR__ . '/../php_files/auth.php'; 
require_once __DIR__ . '/../php_files/db.php'; 

$userData = requireRole($conn, ['admin']); 
require_once __DIR__ . '/ActionsAP/getAllMaintenance.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Maintenance Tickets</title>
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

/* Sidebar */
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

/* Profile Dropdown */
.profile-dropdown { opacity: 0; visibility: hidden; transform: translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity: 1; visibility: visible; transform: translateY(0); }

/* Notice */
.notice-panel { max-height: 0; overflow: hidden; opacity: 0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.notice-panel.open { max-height: 120px; opacity: 1; }
.notice-chevron { transition: transform 0.3s ease; }
.notice-chevron.rotated { transform: rotate(180deg); }

/* Scrollbars */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.96); }
.zep-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background: rgba(255,255,255,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }

/* Main Scroll Area */
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }

/* Kanban Card Styling */
.ticket-card {
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
  border-color: #cbd5e1;
}

/* Modal Transitions */
.modal-backdrop { opacity: 0; visibility: hidden; transition: opacity 0.22s ease, visibility 0.22s ease; }
.modal-backdrop.open { opacity: 1; visibility: visible; }
.modal-card { transform: translateY(12px) scale(0.98); transition: transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform: translateY(0) scale(1); }
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
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
    </a>
    <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/residents.php" data-tooltip="Residents" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="../adminPages/analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
  </nav>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- TOP BAR -->
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" id="topSearchInput" onkeyup="syncSearch(this.value)" placeholder="Search tickets, units, issues..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>

    <div class="flex items-center gap-2 ml-auto">
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">
            <?= htmlspecialchars($_SESSION['initial'] ?? 'A') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none">
              <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin User') ?>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-50 hidden" id="profileDropdown">
          <div class="border-t border-slate-100 my-1 mx-3"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-xl mx-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign out
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout from admin portal?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl">Logout</button>
      </div>
    </div>
  </div>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 lg:p-8 space-y-6">
    <div class="max-w-[1520px] mx-auto space-y-6">

      <!-- TOP CONTROL & FILTER BAR -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
        
        <!-- Header Title & Counter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900">Tickets</h1>
              <span id="totalTicketsBadge" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                Total <?= $totalTicketsCount ?> Tickets
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Review, monitor, and update building maintenance tickets submitted by unit owners and residents.</p>
          </div>

          <!-- Status Tabs -->
          <div class="flex items-center gap-1.5 overflow-x-auto bg-slate-50 p-1.5 rounded-xl border border-slate-200/80">
            <button type="button" onclick="setStatusTab('all')" id="tabAll" class="status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-900 text-white shadow-xs">
              All <span id="tabBadgeAll" class="ml-1 opacity-90"><?= $totalTicketsCount ?></span>
            </button>

            <button type="button" onclick="setStatusTab('active')" id="tabActive" class="status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:text-slate-900 transition-all">
              Active <span id="tabBadgeActive" class="ml-1 px-1.5 py-0.2 rounded-md bg-indigo-50 text-indigo-700 font-mono text-[11px] font-bold"><?= $activeCount ?></span>
            </button>

            <button type="button" onclick="setStatusTab('unassigned')" id="tabUnassigned" class="status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:text-slate-900 transition-all">
              Unassigned <span id="tabBadgeUnassigned" class="ml-1 px-1.5 py-0.2 rounded-md bg-amber-50 text-amber-700 font-mono text-[11px] font-bold"><?= $unassignedCount ?></span>
            </button>

            <button type="button" onclick="setStatusTab('closed')" id="tabClosed" class="status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:text-slate-900 transition-all">
              Closed <span id="tabBadgeClosed" class="ml-1 px-1.5 py-0.2 rounded-md bg-emerald-50 text-emerald-700 font-mono text-[11px] font-bold"><?= $closedCount ?></span>
            </button>
          </div>
        </div>

        <!-- Filter Row -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
          
          <!-- Unit Type Selector -->
          <div class="relative min-w-[160px]">
            <select id="filterType" onchange="applyFilters()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="">All Unit Types</option>
              <?php foreach ($unitTypeOptions as $ut): ?>
                <option value="<?= clean($ut) ?>"><?= clean($ut) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Priority Filter -->
          <div class="relative min-w-[150px]">
            <select id="filterPriority" onchange="applyFilters()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="">All Priorities</option>
              <option value="urgent">High / Urgent</option>
              <option value="normal">Medium / Normal</option>
              <option value="low">Low</option>
            </select>
          </div>

          <!-- Sort by Date -->
          <div class="relative min-w-[140px]">
            <select id="sortDate" onchange="applySort()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="newest">Newest first</option>
              <option value="oldest">Oldest first</option>
            </select>
          </div>

          <!-- Live Search Bar -->
          <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search ID, unit, location, resident, subject..." class="zep-input w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 transition-all">
          </div>

          <!-- Reset Filter Button -->
          <button type="button" onclick="clearAllFilters()" id="clearFiltersBtn" class="hidden text-xs font-semibold text-slate-500 hover:text-slate-900 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
            Reset
          </button>
        </div>

      </div>

      <!-- KANBAN BOARD 3-COLUMN LAYOUT -->
      <div id="kanbanBoardGrid" class="<?= ($totalTicketsCount === 0) ? 'hidden' : '' ?> grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: ACTIVE (In Progress) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colActiveWrap" data-col-status="active">
          <!-- Column Header inside the Box -->
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-600 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Active</h2>
              <span id="colCountActive" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad($activeCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="activeCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No active tickets in progress.
            </div>
            <?php 
            foreach ($activeTickets as $ticket):
              renderTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 2: UNASSIGNED (Pending) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colUnassignedWrap" data-col-status="unassigned">
          <!-- Column Header inside the Box -->
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Unassigned</h2>
              <span id="colCountUnassigned" class="w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad($unassignedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="unassignedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No unassigned / pending tickets.
            </div>
            <?php 
            foreach ($unassignedTickets as $ticket):
              renderTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 3: CLOSED (Resolved / Cancelled) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colClosedWrap" data-col-status="closed">
          <!-- Column Header inside the Box -->
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Closed</h2>
              <span id="colCountClosed" class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad($closedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
            <div class="flex items-center gap-2 text-slate-400">
              <span class="text-sm font-semibold cursor-default hover:text-slate-600 leading-none">+</span>
              <span class="text-xs font-bold tracking-widest cursor-default hover:text-slate-600 leading-none">···</span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="closedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No closed tickets.
            </div>
            <?php 
            foreach ($closedTickets as $ticket):
              renderTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

      </div>

      <!-- FULL-WIDTH EMPTY STATE CARD (When no tickets in progress or no filter match) -->
      <div id="noTicketsMatching" class="<?= ($totalTicketsCount === 0) ? '' : 'hidden' ?> bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-sm space-y-3">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800" id="emptyStateHeading">No tickets in progress</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto" id="emptyStateSubtext">There are currently no maintenance tickets matching your filters.</p>
        <div class="pt-2">
          <button type="button" onclick="clearAllFilters()" class="px-5 py-2 text-xs font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700 transition-all shadow-xs btn-press">
            Reset Filters
          </button>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ========================================== -->
<!-- DETAILS POPUP WINDOW MODAL (Matching Reference Image) -->
<!-- ========================================== -->
<div id="maintenanceModal" class="modal-backdrop fixed inset-0 z-[80] bg-black/40 backdrop-blur-xs flex items-center justify-center p-4" onclick="handleModalBackdropClick(event, 'maintenanceModal')">
  <div class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-hidden flex flex-col border border-slate-100">
    
    <!-- Modal Header -->
    <div class="bg-white px-6 sm:px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-2xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <div>
          <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">Issue Details</h2>
          <p class="text-xs text-slate-400 font-normal mt-0.5">View and manage maintenance issue</p>
        </div>
      </div>

      <button type="button" onclick="closeMaintenanceModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="overflow-y-auto p-6 sm:p-8 space-y-4">
      <input type="hidden" id="modalMaintenanceId">

      <!-- 2-COLUMN 3-ROW INFO GRID -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        
        <!-- 1. Building Unit -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">BUILDING UNIT</p>
            <p id="modalUnit" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <!-- 2. Unit Owner -->
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

        <!-- 3. Category / Priority -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">CATEGORY / PRIORITY</p>
            <p id="modalCategoryPriority" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <!-- 4. Requested by / Person -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">REQUESTED BY / PERSON</p>
            <p id="modalTenant" class="text-sm font-bold text-slate-900 mt-0.5 truncate">-</p>
          </div>
        </div>

        <!-- 5. Submitted At -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SUBMITTED AT</p>
            <p id="modalSubmittedAt" class="text-sm font-bold text-slate-900 mt-0.5 font-mono truncate">-</p>
          </div>
        </div>

        <!-- 6. Resolved At -->
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

      <!-- SUBJECT & ISSUE DESCRIPTION (Left Blue Accent Highlight) -->
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

      <!-- Uploaded Photos -->
      <div class="bg-white border border-slate-200/90 rounded-2xl p-5 space-y-3 shadow-xs">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">ATTACHED PHOTOS</p>
        <div id="modalPhotos" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <p class="text-xs text-slate-400">No photos uploaded.</p>
        </div>
      </div>

      <!-- Admin Status & Remarks Form -->
      <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-5 space-y-3.5">
        <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Update Ticket Status</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
            <select id="modalStatus" class="zep-select w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="pending">Pending (Unassigned)</option>
              <option value="in progress">In Progress (Active)</option>
              <option value="resolved">Resolved (Closed)</option>
              <option value="cancelled">Cancelled (Closed)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Admin Remarks</label>
            <textarea id="modalRemarks" rows="2" class="zep-input w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs resize-none" placeholder="Add notes, technician assignment or resolution feedback..."></textarea>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-white shrink-0">
      <button type="button" onclick="closeMaintenanceModal()" class="btn-press px-5 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
        Close
      </button>

      <button type="button" onclick="saveMaintenanceUpdate()" class="btn-press px-6 py-2.5 text-xs font-bold text-white bg-[#0f172a] hover:bg-slate-800 rounded-xl transition-all shadow-xs inline-flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
        Save Update
      </button>
    </div>
  </div>
</div>

<script>
let sidebarCollapsed = false;

function toggleCollapse() {
  sidebarCollapsed = !sidebarCollapsed;
  document.getElementById('sidebar')?.classList.toggle('collapsed', sidebarCollapsed);
  document.getElementById('mainWrapper')?.classList.toggle('sidebar-collapsed', sidebarCollapsed);
}
function openMobileSidebar() { document.getElementById('sidebar')?.classList.add('open'); document.getElementById('overlay')?.classList.add('show'); }
function closeMobileSidebar() { document.getElementById('sidebar')?.classList.remove('open'); document.getElementById('overlay')?.classList.remove('show'); }


function toggleProfile() {
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  dropdown?.classList.toggle('hidden');
  chevron?.style.setProperty('transform', dropdown?.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)');
}

document.addEventListener('click', function(e) {
  const profileBtn = e.target.closest('button[onclick="toggleProfile()"]');
  const profileWrapper = document.getElementById('profileWrapper');
  if (profileWrapper && !profileWrapper.contains(e.target) && !profileBtn) {
    document.getElementById('profileDropdown')?.classList.add('hidden');
    const chevron = document.getElementById('profileChevron');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  }
});

function confirmLogout() {
  document.getElementById('logoutModal')?.classList.remove('hidden');
}
function hideModal() {
  document.getElementById('logoutModal')?.classList.add('hidden');
}
function doLogout() {
  window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php';
}

function syncSearch(val) {
  const mainSearch = document.getElementById('searchInput');
  if (mainSearch) {
    mainSearch.value = val;
    applyFilters();
  }
}

// ----------------------------------------------------
// TABS FILTERING
// ----------------------------------------------------
let currentTab = 'all';

function setStatusTab(tab) {
  currentTab = tab;
  
  // Style tabs
  const tabs = ['all', 'active', 'unassigned', 'closed'];
  tabs.forEach(t => {
    const btn = document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`);
    if (btn) {
      if (t === tab) {
        btn.className = 'status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-900 text-white shadow-xs';
      } else {
        btn.className = 'status-tab btn-press px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:text-slate-900 transition-all';
      }
    }
  });

  applyFilters();
}

// ----------------------------------------------------
// FILTER & SORT LOGIC
// ----------------------------------------------------
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
  let targetTabVisible = 0;

  columns.forEach(col => {
    const colStatus = col.dataset.colStatus; // 'active', 'unassigned', 'closed'
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

        if (isColVisibleTab) {
          targetTabVisible++;
        }
      } else {
        card.style.display = 'none';
      }
    });

    // Update column badge counter
    const countEl = document.getElementById(`colCount${colStatus.charAt(0).toUpperCase() + colStatus.slice(1)}`);
    if (countEl) {
      countEl.textContent = String(colVisibleCount).padStart(2, '0');
    }

    // Toggle empty message in individual column if some columns have items
    const emptyMsg = col.querySelector('.empty-col-msg');
    if (emptyMsg) {
      emptyMsg.classList.toggle('hidden', colVisibleCount > 0);
    }
  });

  // Update tab badge counters
  const totalVisible = activeVisible + unassignedVisible + closedVisible;
  const tabBadgeAll = document.getElementById('tabBadgeAll');
  const tabBadgeActive = document.getElementById('tabBadgeActive');
  const tabBadgeUnassigned = document.getElementById('tabBadgeUnassigned');
  const tabBadgeClosed = document.getElementById('tabBadgeClosed');

  if (tabBadgeAll) tabBadgeAll.textContent = totalVisible;
  if (tabBadgeActive) tabBadgeActive.textContent = activeVisible;
  if (tabBadgeUnassigned) tabBadgeUnassigned.textContent = unassignedVisible;
  if (tabBadgeClosed) tabBadgeClosed.textContent = closedVisible;

  // Toggle Full-Width Empty State Card
  const boardGrid = document.getElementById('kanbanBoardGrid');
  const emptyStateCard = document.getElementById('noTicketsMatching');
  const emptyTitle = document.getElementById('emptyStateHeading');
  const emptySub = document.getElementById('emptyStateSubtext');

  const showEmpty = (currentTab === 'all' && totalVisible === 0) || 
                   (currentTab === 'active' && activeVisible === 0) ||
                   (currentTab === 'unassigned' && unassignedVisible === 0) ||
                   (currentTab === 'closed' && closedVisible === 0);

  if (showEmpty) {
    if (boardGrid) boardGrid.classList.add('hidden');
    if (emptyStateCard) {
      emptyStateCard.classList.remove('hidden');

      if (typeVal || priorityVal || query) {
        if (emptyTitle) emptyTitle.textContent = "No tickets match your filter";
        if (emptySub) emptySub.textContent = "Try adjusting your unit type, priority filter, or search keywords.";
      } else if (currentTab === 'active') {
        if (emptyTitle) emptyTitle.textContent = "No active tickets in progress";
        if (emptySub) emptySub.textContent = "There are currently no tickets in progress.";
      } else if (currentTab === 'unassigned') {
        if (emptyTitle) emptyTitle.textContent = "No unassigned tickets";
        if (emptySub) emptySub.textContent = "There are no pending tickets waiting for action.";
      } else if (currentTab === 'closed') {
        if (emptyTitle) emptyTitle.textContent = "No closed tickets";
        if (emptySub) emptySub.textContent = "There are no resolved or cancelled tickets.";
      } else {
        if (emptyTitle) emptyTitle.textContent = "No tickets in progress";
        if (emptySub) emptySub.textContent = "There are currently no maintenance tickets matching your filters.";
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
  const topSearch = document.getElementById('topSearchInput');

  if (fType) fType.value = '';
  if (fPriority) fPriority.value = '';
  if (search) search.value = '';
  if (topSearch) topSearch.value = '';

  setStatusTab('all');
}

// ----------------------------------------------------
// MAINTENANCE DETAILS POPUP MODAL
// ----------------------------------------------------
function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value || '-';
}

function openMaintenanceModalFromCard(card) {
  if (!card) return;

  const mId = card.dataset.maintenanceId || '';
  const mr = card.dataset.mr || 'Maintenance Request';
  const unit = card.dataset.unit || '-';
  const ownerName = card.dataset.ownerName || '-';
  const ownerEmail = card.dataset.ownerEmail || '-';
  const tenantName = card.dataset.tenantName || '-';
  const category = card.dataset.category || '-';
  const priority = (card.dataset.priority || 'normal').toUpperCase();
  const submittedAt = card.dataset.submittedAt || '-';
  const resolvedAt = card.dataset.resolvedAt || 'Not yet resolved';
  const subject = card.dataset.subject || '-';
  const description = card.dataset.description || '-';
  const status = (card.dataset.status || 'pending').toLowerCase();
  const remarks = card.dataset.adminRemarks || '';

  const reqBy = card.dataset.requestedBy || 'Unit Owner';
  const person = card.dataset.personName || (card.dataset.ownerName || '-');

  document.getElementById('modalMaintenanceId').value = mId;
  setText('modalMrTitle', mr);
  setText('modalMeta', `${unit} • ${category}`);
  setText('modalUnit', unit);
  setText('modalOwner', ownerName);
  setText('modalOwnerEmail', ownerEmail);
  setText('modalTenant', `${person} (${reqBy})`);
  setText('modalCategoryPriority', `${category} / ${priority}`);
  setText('modalSubmittedAt', submittedAt);
  setText('modalResolvedAt', resolvedAt);
  setText('modalSubject', subject);
  setText('modalDescription', description);

  // Priority Badge
  const pBadge = document.getElementById('modalPriorityBadge');
  if (pBadge) {
    pBadge.textContent = priority;
    if (priority === 'URGENT' || priority === 'HIGH') {
      pBadge.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-500 text-white';
    } else if (priority === 'NORMAL' || priority === 'MEDIUM') {
      pBadge.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500 text-white';
    } else {
      pBadge.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-500 text-white';
    }
  }

  document.getElementById('modalStatus').value = status;
  document.getElementById('modalRemarks').value = remarks;

  // Photos
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
  }
}

function saveMaintenanceUpdate() {
  const maintenanceId = document.getElementById('modalMaintenanceId').value;
  const status = document.getElementById('modalStatus').value;
  const remarks = document.getElementById('modalRemarks').value.trim();

  if (!maintenanceId) {
    alert('Maintenance ID not found.');
    return;
  }

  const formData = new FormData();
  formData.append('maintenance_id', maintenanceId);
  formData.append('status', status);
  formData.append('admin_remarks', remarks);

  fetch('ActionsAP/updateMaintenance.php', {
    method: 'POST',
    body: formData
  })
  .then(async response => {
    const message = await response.text();
    if (!response.ok) {
      throw new Error(message || 'Unable to update maintenance request.');
    }
    alert(message);
    window.location.reload();
  })
  .catch(error => {
    console.error(error);
    alert(error.message || 'Something went wrong while updating maintenance request.');
  });
}
</script>
</body>
</html>
