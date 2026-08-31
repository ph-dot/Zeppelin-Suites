<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';
require_once __DIR__ . '/../php_files/sync_unit_status.php';

$user = requireRole($conn, ['unit owner']);
$ownerId = (int)$user['user_id'];

syncExpiredUnitStatuses($conn);

// Total count of units owned by this owner
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM units_table WHERE unit_owner_id = ?");
$totalUnitsCount = 0;
if ($stmtTotal) {
    $stmtTotal->bind_param('i', $ownerId);
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result();
    $totalUnitsCount = $resTotal ? (int)$resTotal->fetch_assoc()['total'] : 0;
    $stmtTotal->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — My Units</title>
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

/* Modal animations */
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.22s ease,visibility 0.22s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(12px) scale(0.98); transition:transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-18.25">
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
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiries</span>
    </a>
    <a href="ownersUnitReservations.php" data-tooltip="Reservations" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservations</span>
    </a>
    <a href="ownersBookingCalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="ownersUnit.php" data-tooltip="Units" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
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
  <div class="notice-section px-2 py-4 border-t border-slate-100 shrink-0">
    <button onclick="toggleNotice()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-50 transition-all btn-press active:scale-95">
      <span class="nav-label">Notice</span>
      <svg class="notice-chevron w-3.5 h-3.5 text-slate-400 shrink-0" id="noticeChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="notice-panel open px-2 pt-1 space-y-0.5" id="noticePanel">
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Building Guidelines</span></a>
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Owner Advisory</span></a>
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
      <input type="text" id="topSearchInput" onkeyup="syncSearch(this.value)" placeholder="Search units, floor, status..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    
    <div class="flex items-center gap-2 ml-auto">
      <button class="p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95 relative">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">
            <?= htmlspecialchars($user['initial'] ?? 'U') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 truncate" id="userName">
              <?= htmlspecialchars($user['full_name'] ?? 'Unit Owner') ?>
            </p>
            <p class="text-xs text-slate-400">Unit Owner</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account Settings</a>
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Logout Confirmation Modal -->
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

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- TOP CONTROL & FILTER BAR (Matching Admin Design) -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
        
        <!-- Header Title & Counter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900">My Units</h1>
              <span id="totalUnitsBadge" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                Total <?= $totalUnitsCount ?> Units
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Categorized by building floors with unit occupancy & lease rates.</p>
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
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search ID, floor, status, tenant..." class="zep-input w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 transition-all">
          </div>

          <!-- Clear Button -->
          <button type="button" onclick="clearFilters()" id="clearFiltersBtn" class="hidden text-xs font-semibold text-slate-500 hover:text-slate-900 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
            Reset
          </button>
        </div>
      </div>

      <!-- FLOOR-CATEGORIZED UNITS CONTAINER -->
      <div id="floorsContainer" class="space-y-6">
        <?php include __DIR__ . '/ActionsUOP/getOwnerUnits.php'; ?>
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
<!-- UNIT DETAIL MODAL (Matching Admin High-end Look) -->
<!-- ========================================== -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-[60] flex items-center justify-center p-4" id="unitDetailModal" onclick="handleBackdropClick(event,'unitDetailModal')">
  <div class="modal-card bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
    <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white shrink-0">
      <div>
        <h2 class="text-base font-bold text-white flex items-center gap-2">
          <span>Unit Details</span>
          <span id="modalUnitBadge" class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-white/20 text-white"></span>
        </h2>
        <p class="text-xs text-slate-300 mt-0.5">Specifications and current occupancy</p>
      </div>
      <button onclick="closeModal('unitDetailModal')" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
        ✕
      </button>
    </div>

    <div class="p-6 space-y-5 overflow-y-auto">
      <!-- Top Badges Summary -->
      <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
        <div>
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Unit Type</p>
          <p class="text-sm font-bold text-slate-900 mt-0.5" id="mUnitType">—</p>
        </div>
        <div class="text-right">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Status</p>
          <span id="mUnitStatusBadge" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border mt-0.5">
            <span id="mUnitStatusDot" class="w-1.5 h-1.5 rounded-full"></span>
            <span id="mUnitStatusText">—</span>
          </span>
        </div>
      </div>

      <!-- Unit Specs Grid -->
      <div>
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Unit Information</h3>
        <div class="grid grid-cols-2 gap-3.5 text-sm">
          <div class="p-3 bg-white rounded-xl border border-slate-100">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Building Floor</p>
            <p class="text-sm font-semibold text-slate-800 mt-0.5" id="mUnitFloor">—</p>
          </div>
          <div class="p-3 bg-white rounded-xl border border-slate-100">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Lease Rate</p>
            <p class="text-sm font-bold text-slate-900 font-mono mt-0.5" id="mUnitLeaseRate">—</p>
          </div>
        </div>
      </div>

      <!-- Occupancy / Tenant Grid -->
      <div>
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Current Tenant Information</h3>
        <div class="space-y-3">
          <div class="p-3 bg-white rounded-xl border border-slate-100 flex items-center justify-between">
            <div>
              <p class="text-[11px] font-semibold text-slate-400 uppercase">Tenant Name</p>
              <p class="text-sm font-semibold text-slate-800 mt-0.5" id="mTenantName">—</p>
            </div>
            <div class="text-right">
              <p class="text-[11px] font-semibold text-slate-400 uppercase">Contact Number</p>
              <p class="text-sm text-slate-700 mt-0.5 font-mono" id="mTenantContact">—</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3.5 text-sm">
            <div class="p-3 bg-white rounded-xl border border-slate-100">
              <p class="text-[11px] font-semibold text-slate-400 uppercase">Move-In Date</p>
              <p class="text-sm text-slate-700 font-mono mt-0.5" id="mMoveIn">—</p>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-100">
              <p class="text-[11px] font-semibold text-slate-400 uppercase">Move-Out / Lease End</p>
              <p class="text-sm text-slate-700 font-mono mt-0.5" id="mMoveOut">—</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
      <button onclick="closeModal('unitDetailModal')" class="btn-press px-5 py-2 text-xs font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700 transition-all shadow-xs">
        Close
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
function toggleNotice() { document.getElementById('noticePanel')?.classList.toggle('open'); document.getElementById('noticeChevron')?.classList.toggle('rotated'); }

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

// Sync top search bar with main filter search
function syncSearch(val) {
  const mainSearch = document.getElementById('searchInput');
  if (mainSearch) {
    mainSearch.value = val;
    applyFilters();
  }
}

// ----------------------------------------------------
// LIVE FILTERING LOGIC (Matching Admin page)
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
    if (totalVisibleUnits === 0 && floorSections.length > 0) {
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
// UNIT DETAIL MODAL
// ----------------------------------------------------
function openUnitModalFromRow(row) {
  if (!row) return;

  const unitNo = row.dataset.unitNumber || '—';
  const unitType = row.dataset.unitType || '—';
  const floorTitle = row.dataset.floorTitle || `Floor ${row.dataset.floorNumber || '1'}`;
  const leaseRate = row.dataset.leaseRate || '—';
  const status = row.dataset.unitCurrentStatus || '—';
  const statusClass = row.dataset.statusClass || 'bg-slate-50 text-slate-700 border-slate-200';
  const dotClass = row.dataset.dotClass || 'bg-slate-400';
  const tenantName = row.dataset.tenantName || 'No Tenant';
  const tenantContact = row.dataset.tenantContact || '—';
  const moveIn = row.dataset.moveIn || '—';
  const moveOut = row.dataset.moveOut || '—';

  document.getElementById('modalUnitBadge').textContent = unitNo;
  document.getElementById('mUnitType').textContent = unitType;
  document.getElementById('mUnitFloor').textContent = `${floorTitle} (Floor ${row.dataset.floorNumber || '1'})`;
  document.getElementById('mUnitLeaseRate').textContent = leaseRate;
  document.getElementById('mTenantName').textContent = tenantName;
  document.getElementById('mTenantContact').textContent = tenantContact;
  document.getElementById('mMoveIn').textContent = moveIn;
  document.getElementById('mMoveOut').textContent = moveOut;

  const badgeEl = document.getElementById('mUnitStatusBadge');
  const dotEl = document.getElementById('mUnitStatusDot');
  const textEl = document.getElementById('mUnitStatusText');

  if (badgeEl && dotEl && textEl) {
    badgeEl.className = `inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border mt-0.5 ${statusClass}`;
    dotEl.className = `w-1.5 h-1.5 rounded-full ${dotClass}`;
    textEl.textContent = status;
  }

  const modal = document.getElementById('unitDetailModal');
  modal?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  const modal = document.getElementById(id);
  modal?.classList.remove('open');
  document.body.style.overflow = '';
}

function handleBackdropClick(e, id) {
  if (e.target === document.getElementById(id)) {
    closeModal(id);
  }
}
</script>
</body>
</html>