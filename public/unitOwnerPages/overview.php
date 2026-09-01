<?php
require_once __DIR__ . '/../php_files/auth.php';

$user = requireRole($conn, ['unit owner']);
$ownerId = (int)$user['user_id'];

/* ── Live data for this page ────────────────────────────────
   Front-end/layout is unchanged — just wiring real numbers in
   place of the old placeholders ("#", hardcoded rows). */
function ov_e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function ov_count(mysqli $conn, string $sql, int $ownerId): int {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();
    return (int)($row[0] ?? 0);
}

$ownedUnits     = ov_count($conn, "SELECT COUNT(*) FROM units_table WHERE unit_owner_id = ?", $ownerId);
$occupiedUnits  = ov_count($conn, "SELECT COUNT(*) FROM units_table WHERE unit_owner_id = ? AND unit_current_status = 'Occupied'", $ownerId);
$availableUnits = ov_count($conn, "SELECT COUNT(*) FROM units_table WHERE unit_owner_id = ? AND unit_current_status = 'Ready for Occupancy'", $ownerId);
$reservedUnits  = ov_count($conn, "SELECT COUNT(*) FROM units_table WHERE unit_owner_id = ? AND unit_current_status = 'Reserved'", $ownerId);

// Recent tenants — reservations officially booked into units this owner owns
$recentTenants = [];
$stmt = $conn->prepare("
    SELECT r.client_name, r.client_contact, r.move_in_date, u.unit_number
    FROM reservation_table r
    JOIN units_table u ON u.unit_id = r.unit_id
    WHERE u.unit_owner_id = ? AND r.officially_booked_at IS NOT NULL
    ORDER BY r.officially_booked_at DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $recentTenants[] = $row; }
    $stmt->close();
}

// Maintenance requests logged against this owner's units
$maintenanceRequests = [];
$stmt = $conn->prepare("
    SELECT m.maintenance_id, m.status, u.unit_number
    FROM maintenance_requests m
    LEFT JOIN units_table u ON u.unit_id = m.unit_id
    WHERE m.unit_owner_id = ?
    ORDER BY m.submitted_at DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $maintenanceRequests[] = $row; }
    $stmt->close();
}

// Pending reservation requests waiting on this owner's approval
$reservationRequests = [];
$stmt = $conn->prepare("
    SELECT oar.request_id, i.sender_name, un.unit_number
    FROM owner_approval_requests oar
    LEFT JOIN inquiry_table i ON i.inq_id = oar.inq_id
    LEFT JOIN units_table un ON un.unit_id = oar.unit_id
    WHERE oar.unit_owner_id = ? AND oar.request_status = 'pending'
    ORDER BY oar.requested_at DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $reservationRequests[] = $row; }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Unit Owner Overview</title>
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
.sidebar-logo { transition:opacity 0.2s ease,width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
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
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
.stat-card { transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.action-card { transition:all 0.22s ease; cursor:pointer; }
.action-card:hover { transform:translateY(-3px); box-shadow:0 16px 32px rgba(0,0,0,0.08); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
    <a href="overview.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm tracking-tight\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest text-slate-500\'>SUITES</span></span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="overview.php" data-tooltip="Overview" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Overview</span>
    </a>
    <a href="ownersInquiries.php" data-tooltip="Inquiries" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Inquiries</span>
    </a>
     <a href="ownersUnitReservations.php" data-tooltip="Reservations" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservations</span>
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

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR -->
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" placeholder="Search..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <button class="relative p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">
            <?= htmlspecialchars($user['initial'] ?? 'U') ?>
          </div>

          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none">
              <?= htmlspecialchars($user['full_name'] ?? 'Unit Owner') ?>
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

  <!-- Simple Modal -->
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

  <!-- CONTENT -->
  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Welcome -->
      <div>
        <h1 class="text-xl font-bold text-slate-900">Welcome Back, <span class="text-slate-500 font-normal"><?= ov_e($user['full_name'] ?? 'Unit Owner') ?></span></h1>
        <p class="text-xs text-slate-400 mt-0.5">Here's what's happening with your properties today.</p>
      </div>

      <!-- Units Information Section -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-bold text-slate-900">Units Information</h2>
          <a href="ownersUnit.php" class="btn-press text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors active:scale-95">View all units →</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Owned -->
          <div class="stat-card bg-blue-50 rounded-2xl p-4 border border-blue-100">
            <p class="text-3xl font-bold text-blue-700 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($ownedUnits) ?></p>
            <p class="text-sm font-semibold text-blue-600">Owned</p>
            <p class="text-xs text-blue-400 mt-1">Total units owned</p>
          </div>
          <!-- Occupied -->
          <div class="stat-card bg-orange-50 rounded-2xl p-4 border border-orange-100">
            <p class="text-3xl font-bold text-orange-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($occupiedUnits) ?></p>
            <p class="text-sm font-semibold text-orange-500">Occupied</p>
            <p class="text-xs text-orange-400 mt-1">Currently tenanted</p>
          </div>
          <!-- Available -->
          <div class="stat-card bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
            <p class="text-3xl font-bold text-emerald-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($availableUnits) ?></p>
            <p class="text-sm font-semibold text-emerald-600">Available</p>
            <p class="text-xs text-emerald-400 mt-1">Ready for occupancy</p>
          </div>
          <!-- Reserved -->
          <div class="stat-card bg-yellow-50 rounded-2xl p-4 border border-yellow-100">
            <p class="text-3xl font-bold text-yellow-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($reservedUnits) ?></p>
            <p class="text-sm font-semibold text-yellow-600">Reserved</p>
            <p class="text-xs text-yellow-400 mt-1">Awaiting move-in</p>
          </div>
        </div>
      </div>

      <!-- Recent Tenant -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Recent Tenant</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Name</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Contact</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-in Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <?php if (empty($recentTenants)): ?>
                <tr>
                  <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">No tenants yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentTenants as $tenant): ?>
                  <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap"><?= ov_e($tenant['client_name']) ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-sm" style="font-family:'DM Mono',monospace"><?= ov_e($tenant['client_contact'] ?: '—') ?></td>
                    <td class="px-4 py-3.5"><span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100">Unit <?= ov_e($tenant['unit_number']) ?></span></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= ov_e($tenant['move_in_date'] ? date('M d, Y', strtotime($tenant['move_in_date'])) : '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="flex justify-end px-5 py-3 border-t border-slate-100">
          <a href="tenants.php" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">view your tenants →</a>
        </div>
      </div>

      <!-- Bottom cards: Maintenance + Reservations -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Maintenance Requests -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Maintenance Requests</h2>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm">
              <tbody class="divide-y divide-slate-50">
                <?php if (empty($maintenanceRequests)): ?>
                  <tr>
                    <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-400">No maintenance requests.</td>
                  </tr>
                <?php else: ?>
                  <?php
                  $maintStatusClasses = [
                      'pending'     => 'bg-amber-50 text-amber-700 border-amber-100',
                      'in progress' => 'bg-blue-50 text-blue-700 border-blue-100',
                      'resolved'    => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                      'cancelled'   => 'bg-red-50 text-red-700 border-red-100',
                  ];
                  ?>
                  <?php foreach ($maintenanceRequests as $req): ?>
                    <?php $statusClass = $maintStatusClasses[strtolower($req['status'])] ?? 'bg-slate-50 text-slate-700 border-slate-100'; ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                      <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= ov_e($req['maintenance_id']) ?></td>
                      <td class="px-4 py-3.5"><span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100">Unit <?= ov_e($req['unit_number'] ?? 'N/A') ?></span></td>
                      <td class="px-4 py-3.5"><span class="<?= ov_e($statusClass) ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border"><?= ov_e(ucwords($req['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="flex justify-end px-5 py-3 border-t border-slate-100 mt-auto">
            <a href="ownersMaintenance.php" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">View maintenance →</a>
          </div>
        </div>

        <!-- Reservation Request -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Reservation Request</h2>
          </div>
          <?php if (empty($reservationRequests)): ?>
            <div class="flex-1 flex items-center justify-center py-8 px-5">
              <div class="text-center">
                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm text-slate-400">No pending reservation requests</p>
              </div>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto flex-1">
              <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-50">
                  <?php foreach ($reservationRequests as $reqItem): ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                      <td class="px-5 py-3.5">
                        <p class="font-semibold text-slate-800 whitespace-nowrap"><?= ov_e($reqItem['sender_name'] ?? 'Unknown') ?></p>
                        <p class="text-xs text-slate-400">Unit <?= ov_e($reqItem['unit_number'] ?? 'N/A') ?></p>
                      </td>
                      <td class="px-4 py-3.5 text-right">
                        <a href="ownersInquiries.php" class="btn-press inline-flex items-center justify-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full active:scale-95 transition-all">Respond</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <div class="flex justify-end px-5 py-3 border-t border-slate-100 mt-auto">
            <a href="ownersInquiries.php" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">view inquiry requests →</a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  let sidebarCollapsed = false;
  function toggleCollapse() {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
    document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
  function openMobileSidebar() { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); }
  function closeMobileSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }
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

// Close dropdown on outside click
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
</script>
</body>
</html>