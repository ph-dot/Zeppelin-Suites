<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

$userData = requireRole($conn, ['admin']);

$ownerOptions = [];

$ownerSql = "SELECT user_id, full_name, email, user_role 
             FROM users_table 
             WHERE user_role IN ('tenant', 'unit owner')
             ORDER BY full_name ASC";

$ownerResult = $conn->query($ownerSql);

if ($ownerResult && $ownerResult->num_rows > 0) {
    while ($owner = $ownerResult->fetch_assoc()) {
        $ownerOptions[] = $owner;
    }
}

// Get total count of units
$totalUnitsRes = $conn->query("SELECT COUNT(*) AS total FROM units_table");
$totalUnitsCount = $totalUnitsRes ? (int)$totalUnitsRes->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin - Units</title>
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
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
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
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
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
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" id="topSearchInput" onkeyup="syncSearch(this.value)" placeholder="Search units, floor, owner..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    
    <div class="flex items-center gap-2 ml-auto">
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">
            <?= htmlspecialchars($_SESSION['initial'] ?? 'A') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 truncate" id="userName">
              <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>
            </p>
            <p class="text-xs text-slate-400">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Logout Confirmation Modal -->
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

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- TOP CONTROL & FILTER BAR (Inspired by Reference Design) -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
        
        <!-- Header Title & Counter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900">Units</h1>
              <span id="totalUnitsBadge" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                Total <?= $totalUnitsCount ?> Units
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Categorized by building floors with unit occupancy & lease rates.</p>
          </div>

          <!-- Add Unit Button -->
          <div class="flex items-center gap-2.5">
            <button 
                type="button"
                id="openAddUnitModal"
                class="btn-press inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2.5 rounded-full transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add new unit</span>
            </button>
          </div>
        </div>

        <!-- Filter Row -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
          
          <!-- Floor Filter -->
          <div class="relative min-w-[140px]">
            <select id="filterFloor" onchange="applyFilters()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="">All Floors</option>
              <option value="1">1st Floor</option>
              <option value="2">2nd Floor</option>
              <option value="3">3rd Floor</option>
              <option value="4">4th Floor</option>
              <option value="5">5th Floor</option>
              <option value="6">6th Floor</option>
              <option value="7">7th Floor</option>
              <option value="8">8th Floor</option>
              <option value="9">9th Floor</option>
              <option value="10">10th Floor (Penthouse)</option>
            </select>
          </div>

          <!-- Unit Type Filter -->
          <div class="relative min-w-[150px]">
            <select id="filterType" onchange="applyFilters()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="">All Types</option>
              <option value="Studio Type A">Studio Type A</option>
              <option value="Studio Type B">Studio Type B</option>
              <option value="One Bedroom">One Bedroom</option>
              <option value="Two Bedroom">Two Bedroom</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="relative min-w-[160px]">
            <select id="filterStatus" onchange="applyFilters()" class="zep-select w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 cursor-pointer focus:border-slate-900 focus:outline-none">
              <option value="">All Statuses</option>
              <option value="Ready for Occupancy">Ready for Occupancy</option>
              <option value="Resale">Resale</option>
              <option value="On Hold">On Hold</option>
              <option value="Reserved">Reserved</option>
              <option value="Occupied">Occupied</option>
              <option value="Under maintenance">Under maintenance</option>
            </select>
          </div>

          <!-- Search Bar -->
          <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search ID, floor, owner, status..." class="zep-input w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 transition-all">
          </div>

          <!-- Clear Button -->
          <button type="button" onclick="clearFilters()" id="clearFiltersBtn" class="hidden text-xs font-semibold text-slate-500 hover:text-slate-900 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
            Reset
          </button>
        </div>
      </div>

      <!-- FLOOR-CATEGORIZED UNITS CONTAINER -->
      <div id="floorsContainer" class="space-y-6">
        <?php include 'ActionsAP/getUnits.php'; ?>
      </div>

      <!-- Empty State for Filter Matching -->
      <div id="noUnitsMatching" class="hidden bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800">No units match your filter</h3>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search query, floor selection, or status filters.</p>
        <button type="button" onclick="clearFilters()" class="mt-4 px-4 py-2 text-xs font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700 transition-all">
          Reset Filters
        </button>
      </div>

    </div>
  </main>
</div>

<!-- ========================================== -->
<!-- ADD UNIT MODAL -->
<!-- ========================================== -->
<div id="addUnitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col">
        
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-900 text-white">
        <div>
          <h2 class="text-lg font-bold">Add New Unit</h2>
          <p class="text-xs text-slate-300">Assign floor and unit specifications</p>
        </div>
        <button type="button" id="closeAddUnitModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
            ✕
        </button>
    </div>

    <form action="ActionsAP/addUnit.php" method="POST" class="p-6 space-y-4 overflow-y-auto">
        
        <!-- Floor Number Selection -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Building Floor <span class="text-red-500">*</span></label>
            <select 
                name="floor_number" 
                id="addFloorNumber"
                required
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="1">1st Floor (First Floor)</option>
                <option value="2">2nd Floor</option>
                <option value="3">3rd Floor</option>
                <option value="4">4th Floor</option>
                <option value="5">5th Floor</option>
                <option value="6">6th Floor</option>
                <option value="7">7th Floor</option>
                <option value="8">8th Floor</option>
                <option value="9">9th Floor</option>
                <option value="10">10th Floor (Penthouse)</option>
            </select>
        </div>

        <!-- Unit Type -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Type <span class="text-red-500">*</span></label>
            <select 
                name="unit_type" 
                id="unitType"
                required
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="">Select unit type</option>
                <option value="Studio Type A">Studio Type A</option>
                <option value="Studio Type B">Studio Type B</option>
                <option value="One Bedroom">One Bedroom</option>
                <option value="Two Bedroom">Two Bedroom</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">
                Unit number will be generated automatically based on type.
            </p>
        </div>

        <!-- Generated Unit Number -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Generated Unit Number</label>
          <input 
              type="text" 
              id="generatedUnitNumber"
              name="generated_unit_number"
              readonly
              placeholder="Select unit type first"
              class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm bg-slate-50 text-slate-600 font-mono font-bold cursor-not-allowed focus:outline-none">
        </div>

        <!-- Owner Assignment -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Owner Assignment</label>
          <select 
              name="owner_assignment" 
              id="ownerAssignment"
              required
              class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
              <option value="none">No owner yet</option>
              <option value="existing">Select existing user</option>
              <option value="new">Create new unit owner</option>
          </select>
        </div>

        <!-- Existing Owner Box -->
        <div id="existingOwnerBox" class="hidden">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Select Existing Unit Owner</label>
            <select 
                name="existing_owner_id" 
                id="existingOwnerId"
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="">Select unit owner</option>
                <?php foreach ($ownerOptions as $owner): ?>
                    <option value="<?php echo htmlspecialchars($owner['user_id']); ?>">
                        <?php 
                            echo htmlspecialchars($owner['full_name']) . 
                            ' (' . htmlspecialchars($owner['email']) . ')'; 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- New Owner Box -->
        <div id="newOwnerBox" class="hidden space-y-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
            <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Unit Owner Details</p>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
                <input 
                    type="text" 
                    name="new_owner_name" 
                    id="newOwnerName"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input 
                    type="email" 
                    name="new_owner_email" 
                    id="newOwnerEmail"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Contact</label>
                <input 
                    type="text" 
                    name="new_owner_contact" 
                    id="newOwnerContact"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>
        </div>

        <!-- Unit Status -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Status</label>
          <select name="unit_current_status" required
              class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
              <option value="Ready for Occupancy">Ready for Occupancy</option>
              <option value="Resale">Resale</option>
              <option value="On Hold">On Hold</option>
              <option value="Reserved">Reserved</option>
              <option value="Occupied">Occupied</option>
              <option value="Under maintenance">Under maintenance</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
                type="button"
                id="cancelAddUnit"
                class="px-4 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Cancel
            </button>

            <button 
                type="submit"
                class="px-5 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
                Save Unit
            </button>
        </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- EDIT UNIT MODAL -->
<!-- ========================================== -->
<div id="editUnitModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col">
    
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-900 text-white">
      <div>
        <h2 class="text-lg font-bold">Edit Unit</h2>
        <p class="text-xs text-slate-300">Modify unit details, floor, and rates</p>
      </div>
      <button type="button" id="closeEditUnitModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">✕</button>
    </div>

    <form id="editUnitForm" action="ActionsAP/editUnit.php" method="POST" class="p-6 space-y-4 overflow-y-auto">

      <input type="hidden" name="unit_id" id="editUnitId">

      <!-- Floor Selection -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Building Floor</label>
        <select 
          name="floor_number" 
          id="editFloorNumber"
          required
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
          <option value="1">1st Floor (First Floor)</option>
          <option value="2">2nd Floor</option>
          <option value="3">3rd Floor</option>
          <option value="4">4th Floor</option>
          <option value="5">5th Floor</option>
          <option value="6">6th Floor</option>
          <option value="7">7th Floor</option>
          <option value="8">8th Floor</option>
          <option value="9">9th Floor</option>
          <option value="10">10th Floor (Penthouse)</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Type</label>
        <input type="text" id="editUnitType" name="unit_type" readonly
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm bg-slate-100 text-slate-500 cursor-not-allowed focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Number</label>
        <input type="text" id="editUnitNumber" name="unit_number" readonly
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm bg-slate-100 text-slate-500 font-mono font-bold cursor-not-allowed focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Lease Rate (₱)</label>
        <input type="number" step="0.01" id="editLeaseRate" name="lease_rate" placeholder="Optional"
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
        <select id="editStatus" name="unit_current_status" required
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
            <option value="Ready for Occupancy">Ready for Occupancy</option>
            <option value="Resale">Resale</option>
            <option value="On Hold">On Hold</option>
            <option value="Reserved">Reserved</option>
            <option value="Occupied">Occupied</option>
            <option value="Under maintenance">Under maintenance</option>
        </select>
      </div>

      <!-- Unit Owner Assignment -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Owner</label>
        <select name="unit_owner_id" id="editUnitOwnerId"
          class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
          <option value="">No owner</option>
          <option value="new">+ Create new unit owner</option>
          <?php foreach ($ownerOptions as $owner): ?>
            <option value="<?php echo htmlspecialchars($owner['user_id']); ?>">
              <?php echo htmlspecialchars($owner['full_name']) . ' (' . htmlspecialchars($owner['email']) . ')'; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="editNewOwnerBox" class="hidden space-y-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Unit Owner Details</p>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
            <input type="text" id="editNewOwnerName" name="new_owner_name"
                class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
            <input type="email" id="editNewOwnerEmail" name="new_owner_email"
                class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Contact</label>
            <input type="text" id="editNewOwnerContact" name="new_owner_contact"
                class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
      </div>

      <div class="flex items-center justify-between gap-2 pt-4 border-t border-slate-100">
        <button type="button" name="delete_unit" id="deleteUnitBtn"
          class="px-4 py-2 rounded-full bg-red-50 text-red-600 border border-red-200 text-sm font-semibold hover:bg-red-100 transition-colors">
          Delete Unit
        </button>

        <div class="flex items-center gap-2">
          <button type="button" id="cancelEditUnit"
            class="px-4 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            Cancel
          </button>
          <button type="submit" name="update_unit"
            class="px-5 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
            Save Changes
          </button>
        </div>
      </div>

    </form>
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

// Sync top search bar with main filter search
function syncSearch(val) {
  const mainSearch = document.getElementById('searchInput');
  if (mainSearch) {
    mainSearch.value = val;
    applyFilters();
  }
}

// ----------------------------------------------------
// LIVE FILTERING LOGIC
// ----------------------------------------------------
function applyFilters() {
  const floorVal = document.getElementById('filterFloor')?.value || '';
  const typeVal = (document.getElementById('filterType')?.value || '').toLowerCase();
  const statusVal = (document.getElementById('filterStatus')?.value || '').toLowerCase();
  const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();

  const clearBtn = document.getElementById('clearFiltersBtn');
  if (clearBtn) {
    if (floorVal || typeVal || statusVal || query) {
      clearBtn.classList.remove('hidden');
    } else {
      clearBtn.classList.add('hidden');
    }
  }

  let totalVisibleUnits = 0;
  const floorSections = document.querySelectorAll('.floor-section');

  floorSections.forEach(section => {
    const sectionFloor = section.dataset.floor;
    const rows = section.querySelectorAll('.unit-row');
    let visibleInThisFloor = 0;

    // If floor filter is set and doesn't match this floor section, hide all rows in it
    const floorMatches = !floorVal || sectionFloor === floorVal;

    rows.forEach(row => {
      if (!floorMatches) {
        row.style.display = 'none';
        return;
      }

      const rowType = (row.dataset.unitType || '').toLowerCase();
      const rowStatus = (row.dataset.unitCurrentStatus || '').toLowerCase();
      const searchText = row.dataset.searchText || '';

      const typeMatches = !typeVal || rowType === typeVal;
      const statusMatches = !statusVal || rowStatus === statusVal;
      const queryMatches = !query || searchText.includes(query);

      if (typeMatches && statusMatches && queryMatches) {
        row.style.display = '';
        visibleInThisFloor++;
        totalVisibleUnits++;
      } else {
        row.style.display = 'none';
      }
    });

    // Show or hide the entire floor section
    if (floorMatches && visibleInThisFloor > 0) {
      section.style.display = '';
      const badge = section.querySelector('.floor-unit-badge');
      if (badge) {
        badge.textContent = `${visibleInThisFloor} ${visibleInThisFloor === 1 ? 'unit' : 'units'}`;
      }
    } else {
      section.style.display = 'none';
    }
  });

  // Empty state handling
  const noUnitsEl = document.getElementById('noUnitsMatching');
  if (noUnitsEl) {
    if (totalVisibleUnits === 0) {
      noUnitsEl.classList.remove('hidden');
    } else {
      noUnitsEl.classList.add('hidden');
    }
  }
}

function clearFilters() {
  const fFloor = document.getElementById('filterFloor');
  const fType = document.getElementById('filterType');
  const fStatus = document.getElementById('filterStatus');
  const search = document.getElementById('searchInput');
  const topSearch = document.getElementById('topSearchInput');

  if (fFloor) fFloor.value = '';
  if (fType) fType.value = '';
  if (fStatus) fStatus.value = '';
  if (search) search.value = '';
  if (topSearch) topSearch.value = '';

  applyFilters();
}

// ----------------------------------------------------
// ADD & EDIT UNIT MODALS LOGIC
// ----------------------------------------------------
const addUnitModal = document.getElementById('addUnitModal');
const openAddUnitModal = document.getElementById('openAddUnitModal');
const closeAddUnitModal = document.getElementById('closeAddUnitModal');
const cancelAddUnit = document.getElementById('cancelAddUnit');

const unitType = document.getElementById('unitType');
const generatedUnitNumber = document.getElementById('generatedUnitNumber');

openAddUnitModal?.addEventListener('click', () => {
    addUnitModal?.classList.remove('hidden');
    addUnitModal?.classList.add('flex');
});

function closeAddModal() {
    addUnitModal?.classList.add('hidden');
    addUnitModal?.classList.remove('flex');
}

closeAddUnitModal?.addEventListener('click', closeAddModal);
cancelAddUnit?.addEventListener('click', closeAddModal);

unitType?.addEventListener('change', async () => {
    if (!unitType.value) {
        if (generatedUnitNumber) {
          generatedUnitNumber.value = '';
          generatedUnitNumber.placeholder = 'Select unit type first';
        }
        return;
    }

    if (generatedUnitNumber) generatedUnitNumber.value = 'Generating...';

    try {
        const response = await fetch(`ActionsAP/addUnit.php?action=get_next&unit_type=${encodeURIComponent(unitType.value)}`);
        const data = await response.json();

        if (data.success && generatedUnitNumber) {
            generatedUnitNumber.value = data.unit_number;
        } else if (generatedUnitNumber) {
            generatedUnitNumber.value = '';
            generatedUnitNumber.placeholder = 'Unable to generate unit number';
        }
    } catch (error) {
        if (generatedUnitNumber) {
          generatedUnitNumber.value = '';
          generatedUnitNumber.placeholder = 'Error generating unit number';
        }
    }
});

// Owner assignment toggle for Add modal
const ownerAssignment = document.getElementById('ownerAssignment');
const existingOwnerBox = document.getElementById('existingOwnerBox');
const existingOwnerId = document.getElementById('existingOwnerId');
const newOwnerBox = document.getElementById('newOwnerBox');
const newOwnerName = document.getElementById('newOwnerName');
const newOwnerEmail = document.getElementById('newOwnerEmail');
const newOwnerContact = document.getElementById('newOwnerContact');

ownerAssignment?.addEventListener('change', () => {
    existingOwnerBox?.classList.add('hidden');
    newOwnerBox?.classList.add('hidden');

    if (existingOwnerId) existingOwnerId.required = false;
    if (newOwnerName) newOwnerName.required = false;
    if (newOwnerEmail) newOwnerEmail.required = false;
    if (newOwnerContact) newOwnerContact.required = false;

    if (ownerAssignment.value === 'existing') {
        existingOwnerBox?.classList.remove('hidden');
        if (existingOwnerId) existingOwnerId.required = true;
    }

    if (ownerAssignment.value === 'new') {
        newOwnerBox?.classList.remove('hidden');
        if (newOwnerName) newOwnerName.required = true;
        if (newOwnerEmail) newOwnerEmail.required = true;
        if (newOwnerContact) newOwnerContact.required = true;
    }
});

// Open Edit modal from row
function openEditModalFromRow(row) {
    if (!row) return;

    const editUnitId = document.getElementById('editUnitId');
    const editUnitType = document.getElementById('editUnitType');
    const editUnitNumber = document.getElementById('editUnitNumber');
    const editFloorNumber = document.getElementById('editFloorNumber');
    const editLeaseRate = document.getElementById('editLeaseRate');
    const editStatus = document.getElementById('editStatus');
    const editUnitOwnerId = document.getElementById('editUnitOwnerId');

    if (editUnitId) editUnitId.value = row.dataset.unitId || '';
    if (editUnitType) editUnitType.value = row.dataset.unitType || '';
    if (editUnitNumber) editUnitNumber.value = row.dataset.unitNumber || '';
    if (editFloorNumber) editFloorNumber.value = row.dataset.floorNumber || '1';
    if (editLeaseRate) editLeaseRate.value = (row.dataset.leaseRate || '').replace(/[₱,]/g,'');
    if (editStatus) editStatus.value = row.dataset.unitCurrentStatus || 'Ready for Occupancy';
    if (editUnitOwnerId) editUnitOwnerId.value = row.dataset.unitOwnerId || '';

    const editNewOwnerBox = document.getElementById('editNewOwnerBox');
    const editNewOwnerName = document.getElementById('editNewOwnerName');
    const editNewOwnerEmail = document.getElementById('editNewOwnerEmail');
    const editNewOwnerContact = document.getElementById('editNewOwnerContact');

    editNewOwnerBox?.classList.add('hidden');
    if (editNewOwnerName) editNewOwnerName.required = false;
    if (editNewOwnerEmail) editNewOwnerEmail.required = false;
    if (editNewOwnerContact) editNewOwnerContact.required = false;

    const editModal = document.getElementById('editUnitModal');
    editModal?.classList.remove('hidden');
    editModal?.classList.add('flex');
}

// Cancel / Close Edit Modal
document.getElementById('cancelEditUnit')?.addEventListener('click', () => {
    const m = document.getElementById('editUnitModal');
    m?.classList.add('hidden');
    m?.classList.remove('flex');
});
document.getElementById('closeEditUnitModal')?.addEventListener('click', () => {
    const m = document.getElementById('editUnitModal');
    m?.classList.add('hidden');
    m?.classList.remove('flex');
});

// Delete button confirmation
document.getElementById('deleteUnitBtn')?.addEventListener('click', () => {
    if (confirm('Are you sure you want to delete this unit?')) {
        const formData = new FormData(document.getElementById('editUnitForm'));
        formData.append('action_type', 'delete');
        
        fetch('ActionsAP/editUnit.php', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                window.location.href = 'units.php?deleted=1';
            }
        }).catch(err => {
            console.error(err);
            window.location.href = 'units.php';
        });
    }
});

const editUnitOwnerSelect = document.getElementById('editUnitOwnerId');
const editNewOwnerBox = document.getElementById('editNewOwnerBox');
const editNewOwnerName = document.getElementById('editNewOwnerName');
const editNewOwnerEmail = document.getElementById('editNewOwnerEmail');
const editNewOwnerContact = document.getElementById('editNewOwnerContact');

editUnitOwnerSelect?.addEventListener('change', () => {
    if (editUnitOwnerSelect.value === 'new') {
        editNewOwnerBox?.classList.remove('hidden');
        if (editNewOwnerName) editNewOwnerName.required = true;
        if (editNewOwnerEmail) editNewOwnerEmail.required = true;
        if (editNewOwnerContact) editNewOwnerContact.required = true;
    } else {
        editNewOwnerBox?.classList.add('hidden');
        if (editNewOwnerName) editNewOwnerName.required = false;
        if (editNewOwnerEmail) editNewOwnerEmail.required = false;
        if (editNewOwnerContact) editNewOwnerContact.required = false;
    }
});
</script>
</body>
</html>