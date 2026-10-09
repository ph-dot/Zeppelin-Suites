<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Admin Analytics View
 * Pure presentation: strictly NO SQL queries or database connections.
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Zeppelin Suites - Analytics') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
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
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,
.sidebar.collapsed .logo-text,.sidebar.collapsed .notice-section { display: none; }
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

/* ── Stat card hover ───────────────────────────────────── */
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }

/* ── Chart card hover ──────────────────────────────────── */
.chart-card { transition: box-shadow 0.22s ease, border-color 0.22s ease; }
.chart-card:hover { box-shadow: 0 12px 30px rgba(0,0,0,0.08); }

/* ── Table rows ────────────────────────────────────────── */
.tbl-row { transition: background 0.15s ease; }
.tbl-row:hover { background: #f8fafc; }

/* ── Scrollbar ─────────────────────────────────────────── */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

/* ── Buttons / inputs ──────────────────────────────────── */
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }

/* ── Glass header ──────────────────────────────────────── */
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }

/* ── Funnel bars ───────────────────────────────────────── */
.funnel-bar { transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }

/* ── Filter bar ────────────────────────────────────────── */
.filter-bar { background: rgba(255,255,255,0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

/* ── Independent column scroll ─────────────────────────── */
.content-area {
  height: calc(100vh - 65px);
  display: flex;
  overflow: hidden;
}
.col-scroll {
  overflow-y: auto;
  height: 100%;
}
@media (max-width: 1023px) {
  .content-area { display: block; height: auto; overflow: visible; }
  .col-scroll { overflow-y: visible; height: auto; }
  .mobile-scroll-wrap { overflow-y: auto; height: calc(100vh - 65px); }
}

/* ── Trend badges ──────────────────────────────────────── */
.trend-up   { color: #10b981; }
.trend-down { color: #ef4444; }
.trend-neu  { color: #6b7280; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Sidebar Navigation -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- Top Navbar -->
  <?php 
  $navSearchId = 'topSearchInput';
  $navSearchPlaceholder = 'Search analytics...';
  $navExtraRight = '
    <button class="btn-press hidden sm:flex items-center gap-2 text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 rounded-full transition-all active:scale-95" onclick="window.print()">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      Export
    </button>
  ';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- CONTENT AREA -->
  <div class="content-area flex-1 max-w-screen-2xl mx-auto w-full mobile-scroll-wrap" id="contentArea">
    <div class="col-scroll flex-1 min-w-0 p-4 md:p-6 space-y-5">

      <!-- PAGE HEADER -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Analytics</h1>
          <p class="text-xs text-slate-400 mt-0.5">Property performance overview for Zeppelin Suites</p>
        </div>
        <span class="text-xs font-semibold text-slate-400 border border-slate-200 bg-white rounded-full px-3 py-1.5" style="font-family:'DM Mono',monospace;" id="lastUpdatedLabel">Last updated: <?= e($selectedMeta['generatedAt']) ?></span>
      </div>

      <!-- FILTER BAR -->
      <div class="filter-bar border border-slate-200/80 rounded-2xl px-4 py-3 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Filters</span>
        </div>
        <div class="w-px h-5 bg-slate-200"></div>
        <!-- Month -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Month</label>
          <select id="filterMonth" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($months as $index => $monthName): ?>
              <option value="<?= $index ?>" <?= $index === $selectedMonthIndex ? 'selected' : '' ?>><?= e($monthName) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Year -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Year</label>
          <select id="filterYear" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($yearOptions as $yearOption): ?>
              <option value="<?= $yearOption ?>" <?= (int)$yearOption === (int)$selectedYear ? 'selected' : '' ?>><?= $yearOption ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Unit Type -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Unit Type</label>
          <select id="filterUnit" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($unitTypeOptions as $unitKey => $unitLabelOption): ?>
              <option value="<?= e($unitKey) ?>" <?= $unitKey === $selectedUnitKey ? 'selected' : '' ?>><?= e($unitLabelOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Active filter chips -->
        <div class="ml-auto flex items-center gap-2">
          <span id="activeFilterChip" class="text-xs font-semibold bg-slate-900 text-white rounded-full px-3 py-1"><?= e($selectedMonthName) ?> <?= e($selectedYear) ?> · <?= e($selectedUnitLabel) ?></span>
          <button onclick="resetFilters()" class="btn-press text-xs font-medium text-slate-400 hover:text-slate-700 border border-slate-200 bg-white rounded-full px-3 py-1 transition-all active:scale-95">Reset</button>
        </div>
      </div>

      <!-- KPI CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <!-- Occupancy Rate -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Occupancy Rate</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiOccupancy"><?= e($analyticsData['kpi']['occupancy']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold trend-neu">Current unit status</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['occBar']) ?>%" id="barOccupancy"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="occupancyDetail"><?= e($analyticsData['kpi']['occText']) ?></p>
        </div>

        <!-- Inquiry Conversion -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Inquiry Conversion</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiConversion"><?= e($analyticsData['kpi']['conversion']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold <?= e($analyticsData['kpi']['conversionTrendClass']) ?>" id="conversionTrend"><?= e($analyticsData['kpi']['conversionTrendText']) ?></span> <span class="text-slate-400">vs last month</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['convBar']) ?>%" id="barConversion"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="conversionDetail"><?= e($analyticsData['kpi']['convText']) ?></p>
        </div>

        <!-- Sales Revenue -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-amber-50 rounded-xl flex items-center justify-center shrink-0">
              <span class="text-amber-600 font-bold text-sm" style="font-family:'DM Mono',monospace">₱</span>
            </div>
            <span class="text-sm font-semibold text-slate-600">Sales Revenue</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiSales"><?= e($analyticsData['kpi']['sales']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold <?= e($analyticsData['kpi']['salesTrendClass']) ?>" id="salesTrend"><?= e($analyticsData['kpi']['salesTrendText']) ?></span> <span class="text-slate-400">vs last month</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['salesBar']) ?>%" id="barSales"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="salesDetail"><?= e($analyticsData['kpi']['salesText']) ?></p>
        </div>

        <!-- Rooms with Maintenance -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-rose-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Maintenance Load</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiRoomsMaintenance"><?= e($analyticsData['kpi']['roomsMaintenance']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold text-rose-600">Active rooms</span> <span class="text-slate-400">with issues</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-rose-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['roomsMaintenanceBar']) ?>%" id="barRoomsMaintenance"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="roomsMaintenanceDetail"><?= e($analyticsData['kpi']['roomsMaintenanceText']) ?></p>
        </div>

      </div>

      <!-- CHARTS ROW 1: Funnel & Donut -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Conversion Funnel -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100 lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Lead Conversion Funnel</h2>
              <p class="text-xs text-slate-400 mt-0.5">Drop-off progression from initial inquiry to confirmed lease</p>
            </div>
          </div>
          <div class="h-64 relative">
            <canvas id="funnelChart"></canvas>
          </div>
        </div>

        <!-- Reservation Outcomes Donut -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Reservation Status</h2>
              <p class="text-xs text-slate-400 mt-0.5">Active vs Cancelled leases</p>
            </div>
          </div>
          <div class="h-64 relative flex items-center justify-center">
            <canvas id="donutChart"></canvas>
          </div>
        </div>

      </div>

      <!-- CHARTS ROW 2: Category Demand & Maintenance Trends -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Category Demand Bar Chart -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Demand by Unit Type</h2>
              <p class="text-xs text-slate-400 mt-0.5">Total Inquiries vs Confirmed Bookings</p>
            </div>
          </div>
          <div class="h-72 relative">
            <canvas id="barChart"></canvas>
          </div>
        </div>

        <!-- Sales Collection Line Chart -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Verified Revenue Trend</h2>
              <p class="text-xs text-slate-400 mt-0.5">Daily verified payment distribution</p>
            </div>
          </div>
          <div class="h-72 relative">
            <canvas id="salesChart"></canvas>
          </div>
        </div>

      </div>

      <!-- CHARTS ROW 3: Maintenance Trends Line & Room Maintenance Horizontal Bar -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Maintenance Ticket Volume -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Daily Maintenance Requests</h2>
              <p class="text-xs text-slate-400 mt-0.5">Submitted vs Completed daily ticket activity</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Submitted</span>
              <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Resolved</span>
            </div>
          </div>
          <div class="h-72 relative">
            <canvas id="lineChart"></canvas>
          </div>
        </div>

        <!-- Room Maintenance Hotspots -->
        <div class="chart-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Room Maintenance Hotspots</h2>
              <p class="text-xs text-slate-400 mt-0.5">Top units sorted by ticket volume and open issues</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>Total</span>
              <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>Open</span>
            </div>
          </div>
          <div class="h-72 relative">
            <canvas id="roomMaintenanceChart"></canvas>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<script>
// Data payloads injected from MVC Controller
const DATASET = {
  current: <?= json_encode($analyticsData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
const SELECTED = <?= json_encode($selectedMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

let funnelInst = null;
let donutInst = null;
let barInst = null;
let lineInst = null;
let salesInst = null;
let roomMaintenanceInst = null;

/* ─── Funnel Chart ────────────────────────────────────────── */
function renderFunnel(data) {
  const canvas = document.getElementById('funnelChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (funnelInst) funnelInst.destroy();
  funnelInst = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Inquiries', 'HOA Checked', 'Owner Approved', 'Form Submitted', 'Officially Booked'],
      datasets: [{
        label: 'Leads',
        data: data,
        backgroundColor: ['#0f172a', '#334155', '#475569', '#64748b', '#10b981'],
        borderRadius: 8,
        barPercentage: 0.6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 11 }, color: '#64748b' } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', precision: 0 }, beginAtZero: true }
      }
    }
  });
}

/* ─── Donut Chart ─────────────────────────────────────────── */
function renderDonut(data) {
  const canvas = document.getElementById('donutChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (donutInst) donutInst.destroy();
  donutInst = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Active Leases', 'Cancelled'],
      datasets: [{
        data: data,
        backgroundColor: ['#10b981', '#f87171'],
        borderWidth: 0,
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'DM Sans', size: 12 } } }
      },
      cutout: '70%'
    }
  });
}

/* ─── Category Bar Chart ──────────────────────────────────── */
function renderBar(barData) {
  const canvas = document.getElementById('barChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (barInst) barInst.destroy();
  barInst = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: barData.labels,
      datasets: [
        { label: 'Inquiries', data: barData.inquiries, backgroundColor: '#cbd5e1', borderRadius: 6, barPercentage: 0.6 },
        { label: 'Confirmed', data: barData.confirmed, backgroundColor: '#0f172a', borderRadius: 6, barPercentage: 0.6 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', align: 'end', labels: { boxWidth: 12, font: { family: 'DM Sans', size: 11 } } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 10 }, color: '#64748b' } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', precision: 0 }, beginAtZero: true }
      }
    }
  });
}

/* ─── Daily Maintenance Line Chart ────────────────────────── */
function renderLine(lineData) {
  const canvas = document.getElementById('lineChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (lineInst) lineInst.destroy();
  lineInst = new Chart(ctx, {
    type: 'line',
    data: {
      labels: lineData.labels,
      datasets: [
        { label: 'Submitted', data: lineData.requests, borderColor: '#fbbf24', backgroundColor: 'rgba(251,191,36,0.08)', tension: 0.35, fill: true, pointRadius: 2, borderWidth: 2 },
        { label: 'Resolved', data: lineData.completed, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.35, fill: true, pointRadius: 2, borderWidth: 2 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', precision: 0 }, beginAtZero: true }
      }
    }
  });
}

/* ─── Sales Chart ─────────────────────────────────────────── */
function moneyLabel(val) {
  const n = Number(val) || 0;
  if (n >= 1000000) return '₱' + (n / 1000000).toFixed(1) + 'M';
  if (n >= 1000) return '₱' + Math.round(n / 1000) + 'k';
  return '₱' + n.toLocaleString();
}

function renderSalesChart(salesData) {
  const canvas = document.getElementById('salesChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (salesInst) salesInst.destroy();
  salesInst = new Chart(ctx, {
    type: 'line',
    data: {
      labels: salesData.labels,
      datasets: [
        { label: 'Verified Sales', data: salesData.collected, borderColor: '#0f172a', backgroundColor: 'rgba(15,23,42,0.06)', tension: 0.35, fill: true, pointRadius: 2, borderWidth: 2 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => `Sales: ${moneyLabel(ctx.raw)}` } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', callback: v => moneyLabel(v) }, beginAtZero: true }
      }
    }
  });
}

/* ─── Room Maintenance Chart ───────────────────────────────── */
function renderRoomMaintenanceChart(roomData) {
  const canvas = document.getElementById('roomMaintenanceChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (roomMaintenanceInst) roomMaintenanceInst.destroy();

  const labels = roomData.labels && roomData.labels.length ? roomData.labels : ['No data'];
  const total = roomData.total && roomData.total.length ? roomData.total : [0];
  const open = roomData.open && roomData.open.length ? roomData.open : [0];

  roomMaintenanceInst = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Total Requests', data: total, backgroundColor: '#0f172a', borderRadius: 6, barPercentage: 0.55 },
        { label: 'Open Requests', data: open, backgroundColor: '#f87171', borderRadius: 6, barPercentage: 0.55 }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.raw}` } }
      },
      scales: {
        x: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Mono', size: 10 }, color: '#94a3b8', precision: 0 }, beginAtZero: true },
        y: { grid: { display: false }, ticks: { font: { family: 'DM Mono', size: 11 }, color: '#64748b' } }
      }
    }
  });
}

/* ─── Filter Form Dispatcher ──────────────────────────────── */
function submitAnalyticsFilters() {
  const params = new URLSearchParams();
  params.set('month', document.getElementById('filterMonth').value);
  params.set('year', document.getElementById('filterYear').value);
  params.set('unit', document.getElementById('filterUnit').value);
  window.location.href = window.location.pathname + '?' + params.toString();
}

function resetFilters() {
  window.location.href = window.location.pathname;
}

/* ─── Initial Page Load ───────────────────────────────────── */
window.addEventListener('DOMContentLoaded', () => {
  const d = DATASET.current;
  renderFunnel(d.funnel);
  renderDonut(d.donut);
  renderBar(d.bar);
  renderLine(d.line);
  renderSalesChart(d.sales);
  renderRoomMaintenanceChart(d.roomMaintenance);
});
</script>
</body>
</html>
