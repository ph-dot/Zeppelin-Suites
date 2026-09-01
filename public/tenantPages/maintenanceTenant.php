<?php
require_once __DIR__ . '/ActionsTnt/getTenantMaintenance.php';

$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Maintenance</title>
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
.sidebar.collapsed .nav-label, .sidebar.collapsed .nav-badge { display: none; }
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

/* Modals */
.modal-backdrop { opacity: 0; visibility: hidden; transition: opacity 0.22s ease, visibility 0.22s ease; }
.modal-backdrop.open { opacity: 1; visibility: visible; }
.modal-card { transform: translateY(12px) scale(0.98); transition: transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform: translateY(0) scale(1); }

/* Form inputs */
.zep-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.zep-textarea:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }

::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.96); }
.glass-header { background: rgba(255,255,255,0.88); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }

/* Kanban Card Hover */
.ticket-card {
  transition: transform 0.18s cubic-bezier(0.4,0,0.2,1), box-shadow 0.18s cubic-bezier(0.4,0,0.2,1), border-color 0.18s ease;
}
.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
}
</style>
</head>
<body class="bg-slate-50/70 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-black/20 z-40 backdrop-blur-xs" id="overlay" onclick="closeMobileSidebar()"></div>

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

    <a href="maintenanceTenant.php" data-tooltip="Maintenance" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
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
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-blue-500 rounded-full ring-2 ring-white"></span>
      </button>

      <!-- Profile Menu -->
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold ring-2 ring-slate-200">
            <?= clean($tenantInitials) ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none"><?= clean($tenantName) ?></p>
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

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 flex-1">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Toast Feedback -->
      <?php if ($successMessage): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm animate-in fade-in duration-200">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?= clean($successMessage) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold text-emerald-600 hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm animate-in fade-in duration-200">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?= clean($errorMessage) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold text-rose-600 hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <!-- PAGE TITLE & CREATE BUTTON -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Maintenance Requests</h1>
          <p class="text-xs text-slate-400 mt-0.5">Submit service requests and track real-time resolution progress.</p>
        </div>

        <button type="button" onclick="openCreateModal()" class="btn-press inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition-all active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>Create Request</span>
        </button>
      </div>

      <!-- FILTER & CONTROL BAR -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        
        <!-- Left: Filters -->
        <div class="flex flex-wrap items-center gap-2.5">
          
          <!-- Category Filter -->
          <div class="relative min-w-[140px]">
            <select id="filterCategory" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="all">All Categories</option>
              <option value="plumbing">Plumbing</option>
              <option value="electrical">Electrical</option>
              <option value="cleaning">Cleaning</option>
              <option value="fixture">Fixture</option>
              <option value="structural">Structural</option>
              <option value="other">Other</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Priority Filter -->
          <div class="relative min-w-[130px]">
            <select id="filterPriority" onchange="applyFilters()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="all">All Priority</option>
              <option value="urgent">Urgent / High</option>
              <option value="normal">Medium / Normal</option>
              <option value="low">Low</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Reset Filter Button -->
          <button type="button" onclick="clearAllFilters()" id="clearFiltersBtn" class="hidden px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors">
            Reset
          </button>
        </div>

        <!-- Right: Sort by Date & Quick Search -->
        <div class="flex items-center gap-2.5">
          <div class="relative min-w-[140px]">
            <select id="sortDate" onchange="applySort()" class="zep-select w-full pl-3.5 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 cursor-pointer appearance-none">
              <option value="newest">Sort by: Newest</option>
              <option value="oldest">Sort by: Oldest</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>

          <!-- Search Box -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" placeholder="Filter tickets..." oninput="applyFilters()" class="zep-input pl-8 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs w-44 sm:w-56 transition-all">
          </div>
        </div>

      </div>

      <!-- KANBAN BOARD 3-COLUMN LAYOUT -->
      <div id="kanbanBoardGrid" class="<?= ($totalTicketsCount === 0) ? 'hidden' : '' ?> grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: ACTIVE (In Progress) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colActiveWrap" data-col-status="active">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-600 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Active</h2>
              <span id="colCountActive" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$activeCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="activeCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No active tickets in progress.
            </div>
            <?php 
            foreach ($activeTickets as $ticket):
              renderTenantTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 2: UNASSIGNED (Pending) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colUnassignedWrap" data-col-status="unassigned">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Pending Review</h2>
              <span id="colCountUnassigned" class="w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$unassignedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="unassignedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No pending tickets.
            </div>
            <?php 
            foreach ($unassignedTickets as $ticket):
              renderTenantTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

        <!-- COLUMN 3: CLOSED (Resolved / Cancelled) -->
        <div class="kanban-col bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col space-y-4" id="colClosedWrap" data-col-status="closed">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
              <h2 class="font-bold text-slate-900 text-sm tracking-tight">Resolved / Closed</h2>
              <span id="colCountClosed" class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-bold inline-flex items-center justify-center font-mono shrink-0">
                <?= str_pad((string)$closedCount, 2, '0', STR_PAD_LEFT) ?>
              </span>
            </div>
          </div>

          <!-- Cards List -->
          <div class="space-y-3 flex-1 min-h-[140px]" id="closedCardsContainer">
            <div class="empty-col-msg hidden bg-slate-50/70 rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-medium">
              No resolved tickets yet.
            </div>
            <?php 
            foreach ($closedTickets as $ticket):
              renderTenantTicketCard($ticket);
            endforeach;
            ?>
          </div>
        </div>

      </div>

      <!-- FULL-WIDTH EMPTY STATE CARD -->
      <div id="noTicketsMatching" class="<?= ($totalTicketsCount === 0) ? '' : 'hidden' ?> bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-sm space-y-3">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800" id="emptyStateHeading">No maintenance tickets found</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto" id="emptyStateSubtext">You currently have no service tickets logged. Click Create Request to submit an issue.</p>
        <div class="pt-2 flex items-center justify-center gap-3">
          <button type="button" onclick="clearAllFilters()" class="px-5 py-2 text-xs font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700 transition-all shadow-xs btn-press">
            Reset Filters
          </button>
          <button type="button" onclick="openCreateModal()" class="px-5 py-2 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all shadow-xs btn-press">
            + Create Request
          </button>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ========================================== -->
<!-- DETAILS POPUP WINDOW MODAL -->
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
          <p class="text-xs text-slate-400 font-normal mt-0.5">View maintenance issue progress and updates</p>
        </div>
      </div>

      <button type="button" onclick="closeMaintenanceModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="overflow-y-auto p-6 sm:p-8 space-y-4">
      <input type="hidden" id="modalMaintenanceId">

      <!-- 2-COLUMN INFO GRID -->
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

        <!-- 2. Unit Owner / Property Management -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-11 h-11 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">UNIT OWNER / MGT</p>
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
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SUBMITTED BY</p>
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

      <!-- SUBJECT & ISSUE DESCRIPTION -->
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

      <!-- Management / Admin Feedback -->
      <div id="modalAdminRemarksContainer" class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-5 space-y-2">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">MANAGEMENT REMARKS &amp; UPDATES</p>
        <p id="modalAdminRemarksText" class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">No admin feedback provided yet.</p>
      </div>

    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-white shrink-0">
      <button type="button" onclick="closeMaintenanceModal()" class="btn-press px-6 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
        Close
      </button>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- CREATE MAINTENANCE REQUEST MODAL -->
<!-- ========================================== -->
<div class="modal-backdrop fixed inset-0 bg-black/40 backdrop-blur-xs z-[80] flex items-center justify-center p-4" id="createModal" onclick="handleModalBackdropClick(event,'createModal')">
  <form 
    action="ActionsTnt/submitTenantMaintenance.php" 
    method="POST" 
    enctype="multipart/form-data"
    class="modal-card bg-white rounded-3xl shadow-2xl w-full max-w-xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]"
    onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="px-6 sm:px-8 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
      <div>
        <h3 class="text-base sm:text-lg font-bold text-slate-900">Create Maintenance Request</h3>
        <p class="text-xs text-slate-400 mt-0.5">Submit an issue for repair or service in your unit</p>
      </div>
      <button type="button" onclick="closeCreateModal()" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div class="p-6 sm:p-8 space-y-4 overflow-y-auto flex-1">
      
      <!-- Unit Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Select Unit <span class="text-rose-500">*</span></label>
        <div class="relative">
          <select name="unit_id" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
            <?php foreach ($tenantUnitsList as $u): ?>
              <option value="<?= (int)$u['unit_id'] ?>">
                Unit <?= clean($u['unit_number']) ?><?= !empty($u['unit_type']) ? ' — ' . clean($u['unit_type']) : '' ?> (<?= clean(getFloorTitle((int)$u['floor_number'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <!-- Subject -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Subject / Title <span class="text-rose-500">*</span></label>
        <input type="text" name="subject" required placeholder="e.g. Leaking bathroom faucet, Aircon not cooling" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium placeholder:text-slate-400">
      </div>

      <!-- Category & Priority Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Category <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select name="category" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
              <option value="" disabled selected>Select category</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Electrical">Electrical</option>
              <option value="Cleaning">Cleaning</option>
              <option value="Fixture">Fixture</option>
              <option value="Structural">Structural</option>
              <option value="Other">Other</option>
            </select>
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Priority <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select name="priority" required class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 appearance-none">
              <option value="normal" selected>Medium / Normal</option>
              <option value="low">Low</option>
              <option value="urgent">High / Urgent</option>
            </select>
            <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Issue Description <span class="text-rose-500">*</span></label>
        <textarea name="description" rows="3" required placeholder="Please describe the issue in detail..." class="zep-textarea w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium placeholder:text-slate-400 resize-none"></textarea>
      </div>

      <!-- Upload Photos -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Upload Photos (Optional, Max 5)</label>
        <input type="file" name="maintenance_photos[]" multiple accept=".jpg,.jpeg,.png,.webp" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
        <p class="text-[11px] text-slate-400 mt-1">Supported formats: JPG, PNG, WEBP (Max 5MB each)</p>
      </div>

    </div>

    <!-- Footer -->
    <div class="px-6 sm:px-8 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
      <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl transition-all btn-press">
        Cancel
      </button>
      <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white rounded-xl shadow-sm transition-all btn-press active:scale-95">
        Submit Request
      </button>
    </div>

  </form>
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

/* Modals */
function handleModalBackdropClick(event, modalId) {
  if (event.target === document.getElementById(modalId)) {
    if (modalId === 'maintenanceModal') closeMaintenanceModal();
    if (modalId === 'createModal') closeCreateModal();
  }
}

function openCreateModal() {
  document.getElementById('createModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeMaintenanceModal() {
  document.getElementById('maintenanceModal').classList.remove('open');
  document.body.style.overflow = '';
}

function openMaintenanceModalFromCard(card) {
  if (!card) return;
  
  document.getElementById('modalMaintenanceId').value = card.dataset.maintenanceId || '';
  document.getElementById('modalUnit').textContent = card.dataset.unit || '-';
  document.getElementById('modalOwner').textContent = card.dataset.ownerName || '-';
  document.getElementById('modalOwnerEmail').textContent = card.dataset.ownerEmail || '';
  
  const cat = card.dataset.category || 'General';
  const prio = (card.dataset.priority || 'Normal').toUpperCase();
  document.getElementById('modalCategoryPriority').textContent = `${cat} • ${prio}`;
  
  document.getElementById('modalTenant').textContent = card.dataset.tenantName || 'Tenant';
  document.getElementById('modalSubmittedAt').textContent = card.dataset.submittedAt || '-';
  document.getElementById('modalResolvedAt').textContent = card.dataset.resolvedAt || 'Not yet resolved';
  document.getElementById('modalSubject').textContent = card.dataset.subject || '-';
  document.getElementById('modalDescription').textContent = card.dataset.description || '-';
  
  const remarks = card.dataset.adminRemarks || '';
  const remarksContainer = document.getElementById('modalAdminRemarksContainer');
  const remarksText = document.getElementById('modalAdminRemarksText');
  if (remarks.trim()) {
    remarksText.textContent = remarks;
    remarksContainer.classList.remove('hidden');
  } else {
    remarksText.textContent = 'No admin feedback provided yet.';
  }

  // Photos Gallery
  const photosRaw = card.dataset.photos || '';
  const photosContainer = document.getElementById('modalPhotos');
  photosContainer.innerHTML = '';
  if (photosRaw) {
    const photos = photosRaw.split('|').filter(Boolean);
    photos.forEach(src => {
      const a = document.createElement('a');
      a.href = src;
      a.target = '_blank';
      a.className = 'block rounded-xl overflow-hidden border border-slate-200 aspect-video hover:opacity-90 transition-opacity';
      a.innerHTML = `<img src="${src}" class="w-full h-full object-cover" alt="Photo">`;
      photosContainer.appendChild(a);
    });
  } else {
    photosContainer.innerHTML = '<p class="text-xs text-slate-400">No photos attached.</p>';
  }

  document.getElementById('maintenanceModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

/* Filtering and Sorting */
function applyFilters() {
  const cat = document.getElementById('filterCategory').value.toLowerCase();
  const prio = document.getElementById('filterPriority').value.toLowerCase();
  const search = document.getElementById('searchInput').value.toLowerCase().trim();

  const isFiltered = (cat !== 'all' || prio !== 'all' || search !== '');
  document.getElementById('clearFiltersBtn').classList.toggle('hidden', !isFiltered);

  const cards = document.querySelectorAll('.ticket-card');
  let visibleCount = 0;

  cards.forEach(card => {
    const cardCat = (card.dataset.category || '').toLowerCase();
    const cardPrio = (card.dataset.priority || '').toLowerCase();
    const cardSearch = (card.dataset.searchText || '');

    const matchesCat = (cat === 'all' || cardCat === cat);
    const matchesPrio = (prio === 'all' || cardPrio === prio || (prio === 'urgent' && (cardPrio === 'high' || cardPrio === 'urgent')));
    const matchesSearch = (!search || cardSearch.includes(search));

    if (matchesCat && matchesPrio && matchesSearch) {
      card.classList.remove('hidden');
      visibleCount++;
    } else {
      card.classList.add('hidden');
    }
  });

  // Update Column Counters
  updateColCount('active', '#activeCardsContainer');
  updateColCount('unassigned', '#unassignedCardsContainer');
  updateColCount('closed', '#closedCardsContainer');

  // Toggle Global Empty State
  const board = document.getElementById('kanbanBoardGrid');
  const emptyState = document.getElementById('noTicketsMatching');
  if (visibleCount === 0) {
    board.classList.add('hidden');
    emptyState.classList.remove('hidden');
  } else {
    board.classList.remove('hidden');
    emptyState.classList.add('hidden');
  }
}

function updateColCount(type, containerSelector) {
  const container = document.querySelector(containerSelector);
  if (!container) return;
  const visible = container.querySelectorAll('.ticket-card:not(.hidden)').length;
  const counter = document.getElementById(type === 'active' ? 'colCountActive' : (type === 'unassigned' ? 'colCountUnassigned' : 'colCountClosed'));
  if (counter) counter.textContent = String(visible).padStart(2, '0');

  const emptyMsg = container.querySelector('.empty-col-msg');
  if (emptyMsg) {
    emptyMsg.classList.toggle('hidden', visible > 0);
  }
}

function clearAllFilters() {
  document.getElementById('filterCategory').value = 'all';
  document.getElementById('filterPriority').value = 'all';
  document.getElementById('searchInput').value = '';
  applyFilters();
}

function applySort() {
  const order = document.getElementById('sortDate').value;
  ['#activeCardsContainer', '#unassignedCardsContainer', '#closedCardsContainer'].forEach(selector => {
    const container = document.querySelector(selector);
    if (!container) return;
    const cards = Array.from(container.querySelectorAll('.ticket-card'));
    cards.sort((a, b) => {
      const dateA = new Date(a.dataset.submittedRaw || 0).getTime();
      const dateB = new Date(b.dataset.submittedRaw || 0).getTime();
      return order === 'newest' ? (dateB - dateA) : (dateA - dateB);
    });
    cards.forEach(c => container.appendChild(c));
  });
}
</script>
</body>
</html>
