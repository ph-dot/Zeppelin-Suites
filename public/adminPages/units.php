<?php
require_once '../php_files/admin_auth.php';
require_once '../php_files/db.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin — Units</title>
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
.unit-row { transition:background 0.15s ease; cursor:pointer; }
.unit-row:hover { background:#f1f5f9; }
.unit-row .unit-num { transition:color 0.15s ease; }
.unit-row:hover .unit-num { color:#1d4ed8; }
.view-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; }
.unit-row:hover .view-btn { opacity:1; transform:translateX(0); }
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
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/employees.php" data-tooltip="Employees" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
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
  </header>

  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Page header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold text-slate-900">Units</h1>
        <div class="flex items-center gap-2">
          <!-- Status legend pills -->
          <button 
              type="button"
              id="openAddUnitModal"
              class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
              + Add a Unit
          </button>
        </div>
      </div>

      <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="unitTable">
                <thead>
                  <tr class="border-b border-slate-100 bg-slate-50/60">
                      <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit Number</th>
                      <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit Type</th>
                      <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Base Rate</th>
                      <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit Owner</th>
                      <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Lease Rate / Resale Price</th>
                      <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Status</th>
                      <th class="px-3 py-3 w-[90px] min-w-[90px] text-center"></th>
                  </tr>
              </thead>

                <tbody class="divide-y divide-slate-50" id="unitBody">
                    <?php include 'ActionsAP/getUnits.php'; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between flex-wrap gap-3 px-5 py-3.5 border-t border-slate-100">
            <p class="text-xs text-slate-500">
                Showing 
                <span class="font-semibold text-slate-700">
                    <?php echo $startItem; ?>–<?php echo $endItem; ?>
                </span> 
                of 
                <span class="font-semibold text-slate-700">
                    <?php echo $totalItems; ?>
                </span> 
                units
            </p>

            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
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

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" 
                      class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"
                      title="Next">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Add Unit Modal -->
  <div id="addUnitModal" class="fixed inset-0 z-50 hidden items-start justify-center bg-black/40 px-4 py-6 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col">
          
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 class="text-lg font-bold text-slate-900">Add Unit</h2>
              <button type="button" id="closeAddUnitModal" class="text-slate-400 hover:text-slate-700">
                  ✕
              </button>
          </div>

          <form action="ActionsAP/addUnit.php" method="POST" class="p-6 space-y-4 overflow-y-auto">
              
              <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Type</label>
                  <select 
                      name="unit_type" 
                      id="unitType"
                      required
                      class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                      <option value="">Select unit type</option>
                      <option value="Studio Type A">Studio Type A</option>
                      <option value="Studio Type B">Studio Type B</option>
                      <option value="One Bedroom">One Bedroom</option>
                      <option value="Two Bedroom">Two Bedroom</option>
                  </select>
                  <p class="text-xs text-slate-500 mt-1">
                      Unit number will be generated automatically.
                  </p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Generated Unit Number</label>
                <input 
                    type="text" 
                    id="generatedUnitNumber"
                    name="generated_unit_number"
                    readonly
                    placeholder="Select unit type first"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 text-slate-500 cursor-not-allowed focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">
                    This is automatically generated and cannot be edited.
                </p>
            </div>

              <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1">Base Rate</label>
                  <input 
                      type="number" 
                      name="base_rate" 
                      id="baseRate"
                      step="0.01"
                      required
                      class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Owner Assignment</label>
                <select 
                    name="owner_assignment" 
                    id="ownerAssignment"
                    required
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    <option value="none">No owner yet</option>
                    <option value="existing">Select existing user</option>
                    <option value="new">Create new unit owner</option>
                </select>
            </div>

            <div id="existingOwnerBox" class="hidden">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Select Existing Unit Owner</label>
                <select 
                    name="existing_owner_id" 
                    id="existingOwnerId"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">

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

            <div id="newOwnerBox" class="hidden space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                    <input 
                        type="text" 
                        name="new_owner_name" 
                        id="newOwnerName"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                    <input 
                        type="email" 
                        name="new_owner_email" 
                        id="newOwnerEmail"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Contact</label>
                    <input 
                        type="text" 
                        name="new_owner_contact" 
                        id="newOwnerContact"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

              <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1">Lease Rate</label>
                  <input 
                      type="number" 
                      name="lease_rate" 
                      step="0.01"
                      placeholder="Optional"
                      class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
              </div>

             <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Status</label>
                <select name="unit_current_status" required
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    <option value="Ready for Occupancy">Ready for Occupancy</option>
                    <option value="Resale">Resale</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Under maintenance">Under maintenance</option>
                </select>
            </div>

              <div class="flex items-center justify-end gap-2 pt-4">
                  <button 
                      type="button"
                      id="cancelAddUnit"
                      class="px-4 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                      Cancel
                  </button>

                  <button 
                      type="submit"
                      class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700">
                      Save Unit
                  </button>
              </div>
          </form>
      </div>
  </div>

      <!-- Edit Unit Modal -->
<div id="editUnitModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col">
    
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <h2 class="text-lg font-bold text-slate-900">Edit Unit</h2>
      <button type="button" id="closeEditUnitModal" class="text-slate-400 hover:text-slate-700">✕</button>
    </div>

    <form id="editUnitForm" action="ActionsAP/editUnit.php" method="POST" class="p-6 space-y-4 overflow-y-auto">

      <input type="hidden" name="unit_id" id="editUnitId">
      

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Type</label>
        <input type="text" id="editUnitType" name="unit_type" readonly
          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-100 text-slate-500 cursor-not-allowed focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Number</label>
        <input type="text" id="editUnitNumber" name="unit_number" readonly
          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-100 text-slate-500 cursor-not-allowed focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Base Rate</label>
        <input type="number" step="0.01" id="editBaseRate" name="base_rate"
          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Lease Rate</label>
        <input type="number" step="0.01" id="editLeaseRate" name="lease_rate" placeholder="Optional"
          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
      </div>

      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
        <select id="editStatus" name="unit_current_status" required>
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
          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
          <option value="new">Create new unit owner</option>
          <option value="">No owner</option>
          <?php foreach ($ownerOptions as $owner): ?>
            <option value="<?php echo htmlspecialchars($owner['user_id']); ?>">
              <?php echo htmlspecialchars($owner['full_name']) . ' (' . htmlspecialchars($owner['email']) . ')'; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="editNewOwnerBox" class="hidden space-y-3">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
            <input type="text" id="editNewOwnerName" name="new_owner_name"
                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
            <input type="email" id="editNewOwnerEmail" name="new_owner_email"
                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Contact</label>
            <input type="text" id="editNewOwnerContact" name="new_owner_contact"
                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>
    </div>

      <div class="flex items-center justify-end gap-2 pt-4">
        <button type="button" id="cancelEditUnit"
          class="px-4 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
          Cancel
        </button>
        <button type="submit" name="update_unit"
          class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700">
          Update
        </button>
        <button type="button" name= "delete_unit" id="deleteUnitBtn"
          class="px-4 py-2 rounded-full bg-red-600 text-white text-sm font-semibold hover:bg-red-700">
          Delete
        </button>
      </div>

    </form>
  </div>
</div>

  </main>
</div><!-- /main-wrapper -->

<script>
  let sidebarCollapsed = false;

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

  function filterSearch() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#unitBody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
  }
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


//units table

document.addEventListener('DOMContentLoaded', function () {
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            // Prevent the button click from triggering other actions like row selection
            event.stopPropagation();
            
            const unitRow = button.closest('tr'); // Get the closest row
            const unitId = unitRow.dataset.unitId;
            alert('View details for unit ID: ' + unitId);
            // You can open a modal or redirect the user to a detailed view page
        });
    });
});

const addUnitModal = document.getElementById('addUnitModal');
const openAddUnitModal = document.getElementById('openAddUnitModal');
const closeAddUnitModal = document.getElementById('closeAddUnitModal');
const cancelAddUnit = document.getElementById('cancelAddUnit');

const unitType = document.getElementById('unitType');
const baseRate = document.getElementById('baseRate');
const generatedUnitNumber = document.getElementById('generatedUnitNumber');

openAddUnitModal.addEventListener('click', () => {
    addUnitModal.classList.remove('hidden');
    addUnitModal.classList.add('flex');
});

function closeModal() {
    addUnitModal.classList.add('hidden');
    addUnitModal.classList.remove('flex');
}

closeAddUnitModal.addEventListener('click', closeModal);
cancelAddUnit.addEventListener('click', closeModal);

unitType.addEventListener('change', async () => {
    const rates = {
        'Studio Type A': 35000,
        'Studio Type B': 35000,
        'One Bedroom': 45000,
        'Two Bedroom': 80000
    };

    baseRate.value = rates[unitType.value] || '';

    if (!unitType.value) {
        generatedUnitNumber.value = '';
        generatedUnitNumber.placeholder = 'Select unit type first';
        return;
    }

    generatedUnitNumber.value = 'Generating...';

    try {
        const response = await fetch(`ActionsAP/addUnit.php?action=get_next&unit_type=${encodeURIComponent(unitType.value)}`);
        const data = await response.json();

        if (data.success) {
            generatedUnitNumber.value = data.unit_number;
        } else {
            generatedUnitNumber.value = '';
            generatedUnitNumber.placeholder = 'Unable to generate unit number';
        }
    } catch (error) {
        generatedUnitNumber.value = '';
        generatedUnitNumber.placeholder = 'Error generating unit number';
    }
});
// Owner assignment logic
const ownerAssignment = document.getElementById('ownerAssignment');

const existingOwnerBox = document.getElementById('existingOwnerBox');
const existingOwnerId = document.getElementById('existingOwnerId');

const newOwnerBox = document.getElementById('newOwnerBox');
const newOwnerName = document.getElementById('newOwnerName');
const newOwnerEmail = document.getElementById('newOwnerEmail');
const newOwnerContact = document.getElementById('newOwnerContact');

ownerAssignment.addEventListener('change', () => {
    existingOwnerBox.classList.add('hidden');
    newOwnerBox.classList.add('hidden');

    existingOwnerId.required = false;
    newOwnerName.required = false;
    newOwnerEmail.required = false;
    newOwnerContact.required = false;

    if (ownerAssignment.value === 'existing') {
        existingOwnerBox.classList.remove('hidden');
        existingOwnerId.required = true;
    }

    if (ownerAssignment.value === 'new') {
        newOwnerBox.classList.remove('hidden');
        newOwnerName.required = true;
        newOwnerEmail.required = true;
        newOwnerContact.required = true;
    }
});

// Open modal and fill values when Edit button clicked
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = btn.closest('tr');

        document.getElementById('editUnitId').value = row.dataset.unitId;
        document.getElementById('editUnitType').value = row.dataset.unitType;
        document.getElementById('editUnitNumber').value = row.dataset.unitNumber;
        document.getElementById('editBaseRate').value = row.dataset.baseRate.replace(/[₱,]/g,'');
        document.getElementById('editLeaseRate').value = row.dataset.leaseRate.replace(/[₱,]/g,'');
        document.getElementById('editStatus').value = row.dataset.unitCurrentStatus;
        document.getElementById('editUnitOwnerId').value = row.dataset.unitOwnerId || '';

        // Always hide new owner box initially
        editNewOwnerBox.classList.add('hidden');
        editNewOwnerName.required = false;
        editNewOwnerEmail.required = false;
        editNewOwnerContact.required = false;

        document.getElementById('editUnitModal').classList.remove('hidden');
    });
});

// Cancel modal
document.getElementById('cancelEditUnit').addEventListener('click', () => {
    document.getElementById('editUnitModal').classList.add('hidden');
});
document.getElementById('closeEditUnitModal').addEventListener('click', () => {
    document.getElementById('editUnitModal').classList.add('hidden');
});

// Delete button confirmation
document.getElementById('deleteUnitBtn').addEventListener('click', () => {
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
                window.location.href = '../units.php?deleted=1';
            }
        });
    }
});

const editUnitOwnerSelect = document.getElementById('editUnitOwnerId');
const editNewOwnerBox = document.getElementById('editNewOwnerBox');
const editNewOwnerName = document.getElementById('editNewOwnerName');
const editNewOwnerEmail = document.getElementById('editNewOwnerEmail');
const editNewOwnerContact = document.getElementById('editNewOwnerContact');

editUnitOwnerSelect.addEventListener('change', () => {
    if (editUnitOwnerSelect.value === 'new') {
        editNewOwnerBox.classList.remove('hidden');
        editNewOwnerName.required = true;
        editNewOwnerEmail.required = true;
        editNewOwnerContact.required = true;
    } else {
        editNewOwnerBox.classList.add('hidden');
        editNewOwnerName.required = false;
        editNewOwnerEmail.required = false;
        editNewOwnerContact.required = false;
    }
});
</script>
</body>
</html>