<?php
require_once __DIR__ . '/ActionsTnt/getTenantOverview.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Home</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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

.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }

.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

/* ── Sidebar links ─────────────────────────────────────── */
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,
.sidebar.collapsed .logo-text { display: none; }
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

/* ── Dropdowns ─────────────────────────────────────────── */
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }

/* ── Stat cards ────────────────────────────────────────── */
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }

/* ── Glass header ──────────────────────────────────────── */
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }

/* ── Scroll Area ───────────────────────────────────────── */
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }

::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

/* ── Buttons / inputs ──────────────────────────────────── */
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay -->
<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-[73px]">
    <a href="homeTenant.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm\'>ZEPPELIN SUITES</span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <!-- Home -->
    <a href="homeTenant.php" data-tooltip="Home" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <!-- Maintenance -->
    <a href="maintenanceTenant.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <!-- Account -->
    <a href="account.php" data-tooltip="Account" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="nav-label">Account</span>
    </a>
  </nav>
</aside>

<!-- ── MAIN WRAPPER ─────────────────────────────────────── -->
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
    <!-- Profile -->
    <div class="relative" id="profileWrapper">
      <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
        <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">
          <?= clean($tenantInitials) ?>
        </div>
        <div class="hidden sm:block text-left">
          <p class="text-sm font-semibold text-slate-800 truncate"><?= clean($tenantName) ?></p>
          <p class="text-xs text-slate-400 font-medium">Tenant</p>
        </div>
        <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      
      <!-- Profile Dropdown -->
      <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
        <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account</a>
        <div class="border-t border-slate-100 my-1"></div>
        <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
      </div>
    </div>
  </div>
</header>

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4" onclick="if(event.target===this) hideModal()">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
    <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
    <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout from your account?</p>
    <div class="flex gap-3 justify-end">
      <button onclick="hideModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-xl border border-slate-200 transition-all btn-press">Cancel</button>
      <button onclick="doLogout()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all btn-press">Logout</button>
    </div>
  </div>
</div>

<!-- CONTENT AREA -->
<div class="main-scroll p-4 md:p-6 space-y-6">
  <div class="max-w-6xl mx-auto space-y-6">

    <!-- Page Header (Home Title - Daily/Monthly Filter Removed) -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Home</h1>
        <p class="text-xs text-slate-400 mt-0.5">Welcome back, <?= clean($tenantName) ?>! Here is an overview of your stay.</p>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- Rent Due -->
      <div class="stat-card bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm font-bold text-slate-700">Rent Due this month</span>
          </div>
          <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
            <?= $monthlyRate > 0 ? 'Active Lease' : 'No Active Due' ?>
          </span>
        </div>
        <p class="text-4xl font-bold text-slate-900 tracking-tight" style="font-family:'DM Mono',monospace">
          ₱<?= number_format($monthlyRate, 2) ?>
        </p>
        <p class="text-xs text-emerald-600 font-semibold mt-2">Due Date: <span class="text-slate-500 font-normal"><?= clean($rentDueDate) ?></span></p>
        <div class="mt-4 pt-4 border-t border-slate-50 flex items-center gap-2">
          <a href="account.php" class="btn-press flex-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-all text-center">View Lease Details</a>
        </div>
      </div>

      <!-- Active Maintenance -->
      <div class="stat-card bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center shrink-0 border border-amber-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <span class="text-sm font-bold text-slate-700">Active Maintenance</span>
          </div>
          <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $activeMaintenanceCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-500' ?>">
            <?= $activeMaintenanceCount ?> In Progress
          </span>
        </div>
        <p class="text-4xl font-bold text-slate-900 tracking-tight" style="font-family:'DM Mono',monospace"><?= $activeMaintenanceCount ?></p>
        <p class="text-xs text-amber-600 font-semibold mt-2">
          <?= $activeMaintenanceCount > 0 ? 'Open service tickets' : 'No active issues reported' ?>
        </p>
        <div class="mt-4 pt-4 border-t border-slate-50 flex items-center gap-2">
          <a href="maintenanceTenant.php" class="btn-press flex items-center justify-center w-full bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition-all border border-slate-200">
            View Requests
          </a>
        </div>
      </div>

    </div>

    <!-- Unit Information -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <!-- Header -->
      <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-white">Unit &amp; Lease Information</h2>
          <p class="text-xs text-slate-400">Details of your currently assigned residence</p>
        </div>
        <a href="account.php" class="btn-press text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-all">
          Manage Account &rarr;
        </a>
      </div>
      <!-- Content -->
      <div class="p-6 space-y-4">
        <div class="flex flex-wrap gap-x-8 gap-y-3">
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Unit Number</p>
            <p class="text-base font-bold text-slate-900" style="font-family:'DM Mono',monospace">
              <?= clean($unitNumber !== '—' ? 'Unit ' . $unitNumber : 'Not Assigned') ?>
            </p>
          </div>
          <div class="w-px bg-slate-200 self-stretch hidden sm:block"></div>
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Unit Type</p>
            <p class="text-base font-bold text-slate-900"><?= clean($unitType) ?></p>
          </div>
          <?php if (!empty($leaseInfo['floor_number'])): ?>
          <div class="w-px bg-slate-200 self-stretch hidden sm:block"></div>
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Floor</p>
            <p class="text-base font-bold text-slate-900" style="font-family:'DM Mono',monospace"><?= clean($leaseInfo['floor_number']) ?>F</p>
          </div>
          <?php endif; ?>
        </div>

        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Tenant Name</p>
            <p class="text-sm text-slate-900 font-bold"><?= clean($tenantName) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= clean($tenantEmail) ?></p>
          </div>

          <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Unit Owner</p>
            <p class="text-sm text-slate-900 font-bold"><?= clean($unitOwnerName) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= clean($leaseInfo['owner_contact'] ?? 'Contact via Management') ?></p>
          </div>

          <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Move-in Date (Lease Start)</p>
            <p class="text-sm text-slate-900 font-semibold" style="font-family:'DM Mono',monospace">
              <?= clean(format_date_nice($moveInDate)) ?>
            </p>
          </div>

          <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Turnover Date (Lease End)</p>
            <p class="text-sm text-slate-900 font-semibold" style="font-family:'DM Mono',monospace">
              <?= clean(format_date_nice($moveOutDate)) ?>
            </p>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

</div><!-- /main-wrapper -->

<script>
let sidebarCollapsed = false;
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

function toggleProfile() {
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  const isHidden = dropdown.classList.toggle('hidden');
  chevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
}

document.addEventListener('click', function(e) {
  const profileWrapper = document.getElementById('profileWrapper');
  if (profileWrapper && !profileWrapper.contains(e.target)) {
    document.getElementById('profileDropdown')?.classList.add('hidden');
    const chevron = document.getElementById('profileChevron');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  }
});

function confirmLogout() {
  document.getElementById('logoutModal').classList.remove('hidden');
}

function hideModal() {
  document.getElementById('logoutModal').classList.add('hidden');
}

function doLogout() {
  window.location.href = '../php_files/logout_session.php';
}
</script>
</body>
</html>
