<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Admin Home Overview View
 * Pure presentation: strictly NO SQL queries or database connections.
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$totalUnits = (int)($homeStats['total_units'] ?? 0);
$occupiedUnits = (int)($homeStats['occupied_units'] ?? 0);
$availableUnits = (int)($homeStats['available_units'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Zeppelin Suites - Home') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width: 256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background: rgba(255,255,255,0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; } .sidebar.open { transform: translateX(0); } }
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,.sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content: attr(data-tooltip); position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%); background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px; border-radius: 8px; white-space: nowrap; z-index: 999; box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none; }
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.content-area { height: calc(100vh - 65px); display: flex; overflow: hidden; }
.col-scroll { overflow-y: auto; height: 100%; }
@media (max-width: 1023px) {
  .content-area { display: block; height: auto; overflow: visible; }
  .col-scroll { overflow-y: visible; height: auto; }
  .mobile-scroll-wrap { overflow-y: auto; height: calc(100vh - 65px); }
}
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navSearchId = 'topSearchInput';
  $navSearchPlaceholder = 'Search overview...';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- CONTENT AREA -->
  <div class="content-area flex-1 max-w-screen-2xl mx-auto w-full mobile-scroll-wrap" id="contentArea">
    <div class="col-scroll flex-1 min-w-0 p-4 md:p-6 space-y-6">

      <!-- Overview Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Overview</h1>
          <p class="text-xs text-slate-400 mt-0.5">Your portfolio at a glance.</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/analytics" class="btn-press active:scale-95 text-sm border border-slate-200 rounded-full px-4 py-1.5 bg-white text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-1.5 shadow-sm">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          View Analytics
        </a>
      </div>

      <!-- Summary Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-slate-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M13 21V9l6 3v9M9 9h.01M9 13h.01M9 17h.01"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Total Units</span>
          </div>
          <p class="text-2xl font-bold text-slate-900" style="font-family:'DM Mono',monospace"><?= e($totalUnits) ?></p>
          <p class="text-xs text-slate-400 font-normal mt-1">Across all buildings</p>
        </div>

        <div class="stat-card bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Occupied</span>
          </div>
          <p class="text-2xl font-bold text-slate-900" style="font-family:'DM Mono',monospace"><?= e($occupiedUnits) ?></p>
          <p class="text-xs text-slate-400 font-normal mt-1">Currently tenanted</p>
        </div>

        <div class="stat-card bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Available</span>
          </div>
          <p class="text-2xl font-bold text-slate-900" style="font-family:'DM Mono',monospace"><?= e($availableUnits) ?></p>
          <p class="text-xs text-slate-400 font-normal mt-1">Ready, resale, or on hold</p>
        </div>
      </div>

      <!-- Pending Admin Actions (Auto-refreshed via polling) -->
      <div id="pendingActionsWrap">
        <?php include __DIR__ . '/pending_actions.php'; ?>
      </div>

    </div>
  </div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const PENDING_ACTIONS_POLL_MS = 20000;

async function refreshPendingActions() {
  try {
    const res = await fetch(BASE_URL + '/admin/pending-actions', { credentials: 'same-origin', cache: 'no-store' });
    if (!res.ok) return;
    const html = await res.text();
    if (!html.includes('Pending Admin Actions')) return;
    document.getElementById('pendingActionsWrap').innerHTML = html;
  } catch (err) {
    console.warn('Pending actions refresh failed:', err);
  }
}

setInterval(refreshPendingActions, PENDING_ACTIONS_POLL_MS);
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') refreshPendingActions();
});
</script>
</body>
</html>
