<?php
require_once __DIR__ . '/ActionsTnt/getTenantAccount.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Account</title>
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
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
.collapse-icon { transition:transform 0.3s ease; }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }

.profile-tab { position:relative; color:#64748b; transition:all 0.2s ease; }
.profile-tab:hover { color:#0f172a; }
.profile-tab.active { color:#0f172a; font-weight:700; }
.profile-tab.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:2px; background:#0f172a; border-radius:2px; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

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
    <a href="homeTenant.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="maintenanceTenant.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="account.php" data-tooltip="Account" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
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
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-blue-500 rounded-full ring-2 ring-white"></span>
      </button>

      <!-- Profile Menu -->
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold ring-2 ring-slate-200">
            <?= clean($initials) ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none"><?= clean($tenant['full_name'] ?? 'Tenant') ?></p>
            <p class="text-xs text-slate-400 mt-0.5 font-medium">Tenant</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <!-- Dropdown -->
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account</a>
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- LOGOUT MODAL -->
  <div id="logoutModal" onclick="if(event.target===this) hideModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-xl border border-slate-200 transition-all btn-press">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all btn-press">Logout</button>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Toast message -->
      <?php if ($toast): ?>
        <div class="p-4 rounded-2xl border <?= $toast['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200' ?> text-sm font-medium flex items-center justify-between shadow-sm">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $toast['type'] === 'success' ? 'M5 13l4 4L19 7' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' ?>"/>
            </svg>
            <span><?= clean($toast['msg']) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Account</h1>
          <p class="text-xs text-slate-400 mt-0.5">Manage your personal information, lease terms, and service records.</p>
        </div>
        <button type="button" onclick="openEditModal()" class="btn-press flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-all shadow-sm active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Profile
        </button>
      </div>

      <!-- MAIN GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 items-start">

        <!-- LEFT COLUMN: Profile Card -->
        <div class="space-y-4">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex flex-col items-center text-center">
              <div class="w-20 h-20 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-bold ring-4 ring-slate-100 shadow-md">
                <?= clean($initials) ?>
              </div>
              <h2 class="mt-3.5 text-lg font-bold text-slate-900 leading-tight"><?= clean($tenant['full_name']) ?></h2>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                  Tenant
                </span>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <?= clean(ucfirst($tenant['resident_status'] ?: 'Active')) ?>
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-2" style="font-family:'DM Mono',monospace">Resident since <?= format_date_short($tenant['created_at']) ?></p>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 space-y-3.5 text-sm">
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="truncate font-medium text-xs"><?= clean($tenant['email']) ?></span>
              </div>
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="font-medium text-xs" style="font-family:'DM Mono',monospace"><?= clean($tenant['contact'] ?: 'No contact number') ?></span>
              </div>
              <?php if ($additionalPhone !== '—'): ?>
                <div class="flex items-center gap-3 text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span class="font-medium text-xs" style="font-family:'DM Mono',monospace"><?= clean($additionalPhone) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($additionalEmail !== '—'): ?>
                <div class="flex items-center gap-3 text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                  <span class="truncate font-medium text-xs"><?= clean($additionalEmail) ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Detailed Tabs (Profile, Lease, Maintenance) -->
        <div class="space-y-6">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="flex items-center gap-6 border-b border-slate-100 pb-3 overflow-x-auto">
              <button type="button" onclick="setProfileTab('profile', this)" class="profile-tab active text-sm font-semibold pb-3 whitespace-nowrap">Profile</button>
              <button type="button" onclick="setProfileTab('lease', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Lease Information
                <span class="text-[11px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full"><?= $leasesCount ?></span>
              </button>
              <button type="button" onclick="setProfileTab('request', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Maintenance
                <span class="text-[11px] font-bold <?= $pendingRequests > 0 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700' ?> px-2 py-0.5 rounded-full"><?= $requestsCount ?></span>
              </button>
            </div>

            <!-- TAB 1: Profile Details -->
            <div id="tab-profile" class="pt-6 max-w-lg">
              <h3 class="text-base font-bold text-slate-900 mb-6">Personal Information</h3>
              <dl class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Full Name:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= clean($tenant['full_name']) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Email Address:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= clean($tenant['email']) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Contact Number:</dt>
                  <dd class="font-semibold text-slate-800 flex-1" style="font-family:'DM Mono',monospace"><?= clean($tenant['contact'] ?: '—') ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Date of Birth:</dt>
                  <dd class="font-semibold text-slate-800 flex-1" style="font-family:'DM Mono',monospace"><?= clean($dobFormatted) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Additional Phone:</dt>
                  <dd class="font-semibold text-slate-800 flex-1" style="font-family:'DM Mono',monospace"><?= clean($additionalPhone) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Additional Email:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= clean($additionalEmail) ?></dd>
                </div>
              </dl>
            </div>

            <!-- TAB 2: Lease Details (Showing Move-in and Turnover Dates) -->
            <div id="tab-lease" class="pt-6 hidden">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Lease Contracts &amp; Stay Details</h3>
                  <p class="text-xs text-slate-400">View move-in and turnover schedule for your units</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono">
                  <?= $leasesCount ?> <?= $leasesCount === 1 ? 'Record' : 'Records' ?>
                </span>
              </div>
              <?php if (empty($leases)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No active lease contracts found under your account.</p>
                </div>
              <?php else: ?>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-slate-50/60 border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wide text-left">
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Unit Owner</th>
                        <th class="px-4 py-3">Move-In Date</th>
                        <th class="px-4 py-3">Turnover Date</th>
                        <th class="px-4 py-3">Monthly Rent</th>
                        <th class="px-4 py-3">Status</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <?php foreach ($leases as $l): ?>
                        <?php
                          $rate = (float)($l['lease_rate'] ?? $l['required_amount'] ?? 0);
                          $status = $l['reservation_status'] ?? 'Active';
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                          <td class="px-4 py-3.5 font-semibold text-slate-900">
                            <span style="font-family:'DM Mono',monospace">Unit <?= clean($l['unit_number']) ?></span>
                            <span class="block text-xs font-normal text-slate-400"><?= clean($l['unit_type'] ?: 'Standard') ?></span>
                          </td>
                          <td class="px-4 py-3.5 text-slate-800">
                            <span class="font-medium"><?= clean($l['owner_name'] ?: 'Zeppelin Suites Management') ?></span>
                            <span class="block text-xs text-slate-400"><?= clean($l['owner_contact'] ?: '—') ?></span>
                          </td>
                          <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap font-medium" style="font-family:'DM Mono',monospace">
                            <?= clean(format_date_short($l['move_in_date'])) ?>
                          </td>
                          <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap font-medium" style="font-family:'DM Mono',monospace">
                            <?= clean(format_date_short($l['move_out_date'])) ?>
                          </td>
                          <td class="px-4 py-3.5 font-semibold text-slate-900" style="font-family:'DM Mono',monospace">
                            ₱<?= number_format($rate, 2) ?>
                          </td>
                          <td class="px-4 py-3.5">
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 capitalize">
                              <?= clean($status) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- TAB 3: Maintenance Requests -->
            <div id="tab-request" class="pt-6 hidden">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Your Maintenance History</h3>
                  <p class="text-xs text-slate-400">Review tickets logged by your account</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $pendingRequests > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?> font-mono">
                  <?= $requestsCount ?> <?= $requestsCount === 1 ? 'Request' : 'Requests' ?>
                </span>
              </div>
              <?php if (empty($maintenance)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No maintenance requests found.</p>
                </div>
              <?php else: ?>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-slate-50/60 border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wide text-left">
                        <th class="px-4 py-3">Unit Number</th>
                        <th class="px-4 py-3">Issue</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <?php foreach ($maintenance as $m): ?>
                        <?php
                          $mStatus = strtolower($m['status'] ?? 'pending');
                          $mStatusClass = match($mStatus) {
                              'completed', 'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                              'in progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                              'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200',
                              default => 'bg-amber-50 text-amber-700 border-amber-200'
                          };
                          $mPriority = strtolower($m['priority'] ?? 'medium');
                          $mPriorityClass = match($mPriority) {
                              'urgent', 'high' => 'bg-red-50 text-red-700 border-red-200',
                              'low' => 'bg-slate-100 text-slate-600 border-slate-200',
                              default => 'bg-yellow-50 text-yellow-700 border-yellow-200'
                          };
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                          <td class="px-4 py-3.5 font-semibold text-slate-900" style="font-family:'DM Mono',monospace"><?= clean($m['unit_number'] ? 'Unit ' . $m['unit_number'] : 'General') ?></td>
                          <td class="px-4 py-3.5 font-medium text-slate-800"><?= clean($m['issue_title'] ?? 'Maintenance Request') ?></td>
                          <td class="px-4 py-3.5"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mPriorityClass ?>"><?= clean(ucfirst($mPriority)) ?></span></td>
                          <td class="px-4 py-3.5"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mStatusClass ?>"><?= clean(ucfirst($mStatus)) ?></span></td>
                          <td class="px-4 py-3.5 text-xs text-slate-400 font-mono"><?= format_date_short($m['submitted_at']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div id="editResidentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-slate-900">Edit Account Profile</h3>
      <button type="button" onclick="closeEditModal()" class="btn-press p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update_profile">
      
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Full Name</label>
        <input type="text" name="full_name" required value="<?= clean($tenant['full_name']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Contact Number</label>
        <input type="text" name="contact" value="<?= clean($tenant['contact']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Date of Birth</label>
          <input type="date" name="date_of_birth" value="<?= clean($tenant['date_of_birth'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Phone</label>
          <input type="text" name="additional_contact" value="<?= clean($tenant['additional_contact'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono" placeholder="Optional">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Email</label>
        <input type="email" name="additional_email" value="<?= clean($tenant['additional_email'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium" placeholder="Optional">
      </div>

      <div class="pt-2 border-t border-slate-100">
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">New Password (Leave blank to keep current)</label>
        <input type="password" name="new_password" placeholder="••••••••" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
      </div>

      <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
        <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all btn-press">Cancel</button>
        <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white rounded-xl shadow-sm transition-all btn-press active:scale-95">Save Changes</button>
      </div>
    </form>
  </div>
</div>

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

function setProfileTab(tabName, btn) {
  document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  document.getElementById('tab-profile').classList.add('hidden');
  document.getElementById('tab-lease').classList.add('hidden');
  document.getElementById('tab-request').classList.add('hidden');

  if (tabName === 'profile') document.getElementById('tab-profile').classList.remove('hidden');
  if (tabName === 'lease') document.getElementById('tab-lease').classList.remove('hidden');
  if (tabName === 'request') document.getElementById('tab-request').classList.remove('hidden');
}

function openEditModal() {
  const modal = document.getElementById('editResidentModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeEditModal() {
  const modal = document.getElementById('editResidentModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>
</body>
</html>
