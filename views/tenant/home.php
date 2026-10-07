<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Tenant Home View
 * Pure MVC presentation template. Zero direct SQL or DB connections.
 */
$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$activeTab = 'home';

if (!function_exists('clean')) {
    function clean($val): string {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_date_nice')) {
    function format_date_nice($date): string {
        if (empty($date) || $date === '0000-00-00') return '—';
        $ts = strtotime((string)$date);
        return $ts ? date('F j, Y', $ts) : '—';
    }
}

$unitNumber = $leaseInfo['unit_number'] ?? '—';
$unitType = $leaseInfo['unit_type'] ?? 'Standard Suite';
$unitOwnerName = $leaseInfo['owner_name'] ?? 'Zeppelin Suites Management';
$moveInDate = $leaseInfo['move_in_date'] ?? null;
$moveOutDate = $leaseInfo['move_out_date'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Home') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<?php include dirname(__DIR__) . '/components/tenant_sidebar.php'; ?>

<!-- ── MAIN WRAPPER ─────────────────────────────────────── -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <?php 
  $navBreadcrumb = '<div class="flex items-center gap-2 text-sm text-slate-500 font-medium">
    <span class="text-slate-900 font-semibold">Tenant Portal</span>
    <span class="text-slate-300">/</span>
    <span class="text-slate-600">Home</span>
  </div>';
  include dirname(__DIR__) . '/components/tenant_navbar.php'; 
  ?>

<!-- CONTENT AREA -->
<div class="main-scroll p-4 md:p-6 space-y-6">
  <div class="max-w-6xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Home</h1>
        <p class="text-xs text-slate-400 mt-0.5">Welcome back, <?= clean($tenantName) ?>!</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          Active Resident
        </span>
      </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">

      <!-- Card 1: Assigned Residence -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
        <div>
          <!-- Header -->
          <div class="flex items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900">Assigned Residence</h2>
                <p class="text-xs text-slate-400"><?= clean($unitType) ?><?= !empty($leaseInfo['floor_number']) ? ' • Floor ' . clean($leaseInfo['floor_number']) : '' ?></p>
              </div>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
              <?= clean($unitNumber !== '—' ? 'Active Lease' : 'Pending') ?>
            </span>
          </div>

          <!-- Unit Display -->
          <div class="mb-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Unit</p>
            <h3 class="text-3xl font-bold text-slate-900 font-mono tracking-tight">
              <?= clean($unitNumber !== '—' ? 'Unit ' . $unitNumber : 'Not Assigned') ?>
            </h3>
          </div>

          <!-- Key Details -->
          <div class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-xs">
            <div>
              <span class="text-slate-400 block mb-0.5">Move-in Date</span>
              <span class="font-bold text-slate-800 font-mono text-sm"><?= clean(format_date_nice($moveInDate)) ?></span>
            </div>
            <div>
              <span class="text-slate-400 block mb-0.5">Turnover Date</span>
              <span class="font-bold text-slate-800 font-mono text-sm"><?= clean(format_date_nice($moveOutDate)) ?></span>
            </div>
            <div class="col-span-2 pt-2 border-t border-slate-100 flex items-center justify-between">
              <span class="text-slate-400">Unit Owner</span>
              <div class="text-right">
                <span class="font-bold text-slate-800"><?= clean($unitOwnerName) ?></span>
                <?php if (!empty($leaseInfo['owner_contact'])): ?>
                  <span class="text-slate-400 font-mono ml-1.5">(<?= clean($leaseInfo['owner_contact']) ?>)</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Link -->
        <div class="mt-5 pt-4 border-t border-slate-100">
          <a href="<?= htmlspecialchars($baseUrl) ?>/tenant/account" class="btn-press flex items-center justify-between text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 rounded-xl px-4 py-2.5 transition-all">
            <span>View Lease Details</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

      <!-- Card 2: Active Maintenance -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
        <div>
          <!-- Header -->
          <div class="flex items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 border border-amber-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900">Maintenance</h2>
                <p class="text-xs text-slate-400">Unit repair and service requests</p>
              </div>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0 <?= $activeMaintenanceCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
              <?= $activeMaintenanceCount > 0 ? $activeMaintenanceCount . ' In Progress' : 'All Clear' ?>
            </span>
          </div>

          <!-- Active Tickets Counter -->
          <div class="mb-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Active Requests</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-3xl font-bold text-slate-900 font-mono tracking-tight"><?= $activeMaintenanceCount ?></h3>
              <span class="text-xs text-slate-400">open ticket<?= $activeMaintenanceCount === 1 ? '' : 's' ?></span>
            </div>
          </div>

          <!-- Status Note -->
          <div class="pt-4 border-t border-slate-100">
            <div class="bg-slate-50/70 border border-slate-100 rounded-xl p-3.5">
              <p class="text-xs text-slate-600 leading-relaxed">
                <?= $activeMaintenanceCount > 0 
                  ? 'Your open maintenance ticket is currently being handled by management.' 
                  : 'Everything in your unit is in good order. You can submit repair requests anytime.' ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Action Link -->
        <div class="mt-5 pt-4 border-t border-slate-100">
          <a href="<?= htmlspecialchars($baseUrl) ?>/tenant/maintenance" class="btn-press flex items-center justify-between text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 rounded-xl px-4 py-2.5 transition-all">
            <span><?= $activeMaintenanceCount > 0 ? 'Track Active Requests' : 'View Maintenance' ?></span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

    </div>

  </div>
</div>

</div><!-- /main-wrapper -->

</body>
</html>
