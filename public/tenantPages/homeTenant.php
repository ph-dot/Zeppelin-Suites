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

<?php include __DIR__ . '/sidebar.php'; ?>

<!-- ── MAIN WRAPPER ─────────────────────────────────────── -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <?php include __DIR__ . '/navbar.php'; ?>

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

</body>
</html>
