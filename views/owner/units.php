<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Owner: My Units View
 * Pure MVC presentation template. Zero direct SQL queries.
 */
if (!function_exists('clean')) {
    function clean($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($value, bool $isLease = false): string {
        if ($value === null || $value === '' || ($isLease && (float)$value === 0.0)) {
            return $isLease ? '—' : '₱0.00';
        }
        return '₱' . number_format((float)$value, 2);
    }
}

if (!function_exists('fmtDate')) {
    function fmtDate($value): string {
        if (empty($value) || $value === '0000-00-00') return '—';
        $ts = strtotime((string)$value);
        return $ts ? date('M j, Y', $ts) : '—';
    }
}

if (!function_exists('getFloorTitle')) {
    function getFloorTitle($floorNum): string {
        $floorNum = (int)$floorNum;
        $titles = [
            1 => 'First Floor',
            2 => '2nd Floor',
            3 => '3rd Floor',
            4 => '4th Floor',
            5 => '5th Floor',
            6 => '6th Floor',
            7 => '7th Floor',
            8 => '8th Floor',
            9 => '9th Floor',
            10 => '10th Floor (Penthouse)'
        ];
        return $titles[$floorNum] ?? "Floor {$floorNum}";
    }
}

if (!function_exists('getFloorIconBg')) {
    function getFloorIconBg($floorNum): string {
        $bgs = [
            1 => 'bg-emerald-500/10 text-emerald-600 border-emerald-200',
            2 => 'bg-blue-500/10 text-blue-600 border-blue-200',
            3 => 'bg-indigo-500/10 text-indigo-600 border-indigo-200',
            4 => 'bg-violet-500/10 text-violet-600 border-violet-200',
            5 => 'bg-purple-500/10 text-purple-600 border-purple-200',
            6 => 'bg-amber-500/10 text-amber-600 border-amber-200',
            7 => 'bg-orange-500/10 text-orange-600 border-orange-200',
            8 => 'bg-cyan-500/10 text-cyan-600 border-cyan-200',
            9 => 'bg-teal-500/10 text-teal-600 border-teal-200',
            10 => 'bg-rose-500/10 text-rose-600 border-rose-200'
        ];
        return $bgs[$floorNum] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    }
}

$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$units = $units ?? [];
$totalUnitsCount = count($units);

// Group units by floor
$unitsByFloor = [];
foreach ($units as $u) {
    $floor = (int)($u['floor_number'] ?: 1);
    $unitsByFloor[$floor][] = $u;
}
ksort($unitsByFloor);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? 'Zeppelin Suites — My Units') ?></title>
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
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">
<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/../components/owner_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/../components/owner_navbar.php'; ?>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- TOP CONTROL & FILTER BAR -->
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
        <?php if (empty($unitsByFloor)): ?>
          <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No units found</h3>
            <p class="text-xs text-slate-400 mt-1">No units are currently registered or assigned under your account.</p>
          </div>
        <?php else: ?>
          <?php foreach ($unitsByFloor as $floorNum => $floorUnits): 
              $floorTitle = getFloorTitle($floorNum);
              $badgeStyle = getFloorIconBg($floorNum);
          ?>
            <div class="floor-section bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md" data-floor="<?= $floorNum ?>">
              <!-- Floor Header -->
              <div class="floor-header px-6 py-4 border-b border-slate-100/90 bg-gradient-to-r from-slate-50/90 via-white to-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                  <div class="w-10 h-10 rounded-xl <?= $badgeStyle ?> flex items-center justify-center font-bold text-sm shadow-sm shrink-0 border">
                    <?= $floorNum ?>F
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-slate-900 leading-tight"><?= clean($floorTitle) ?></h2>
                    <span class="floor-unit-badge text-xs text-slate-500 font-medium"><?= count($floorUnits) ?> <?= count($floorUnits) === 1 ? 'unit' : 'units' ?></span>
                  </div>
                </div>
              </div>

              <!-- Floor Units Table -->
              <div class="overflow-x-auto">
                <table class="w-full text-sm table-fixed min-w-[850px]">
                  <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                      <th class="text-left px-5 py-3.5 whitespace-nowrap w-[22%] min-w-[180px]">UNIT</th>
                      <th class="text-left px-4 py-3.5 whitespace-nowrap w-[14%] min-w-[120px]">LISTING</th>
                      <th class="text-left px-4 py-3.5 whitespace-nowrap w-[22%] min-w-[180px]">STATUS</th>
                      <th class="text-left px-4 py-3.5 whitespace-nowrap w-[20%] min-w-[160px]">TENANT</th>
                      <th class="text-left px-4 py-3.5 whitespace-nowrap w-[12%] min-w-[110px]">RATE</th>
                      <th class="text-right px-5 py-3.5 whitespace-nowrap w-[10%] min-w-[90px]">ACTIONS</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-50">
                    <?php foreach ($floorUnits as $row): 
                        $unit_id = (int)($row['unit_id'] ?? 0);
                        $unit_number = clean($row['unit_number'] ?? '');
                        $unit_type = clean($row['unit_type'] ?? '');
                        $sqm = (float)($row['sqm'] ?? 0);
                        $sqm_formatted = number_format($sqm, 2);
                        $unit_current_status = clean($row['unit_current_status'] ?? 'Ready for Occupancy');
                        $tenant_name = clean($row['tenant_name'] ?: 'No Tenant');
                        $tenant_contact = clean($row['tenant_contact'] ?: '—');
                        $tenant_email = clean($row['tenant_email'] ?: '—');
                        $move_in_date = fmtDate($row['move_in_date'] ?? null);
                        $move_out_date = fmtDate($row['move_out_date'] ?? null);

                        // Status Badge Colors
                        $status_lower = strtolower(trim($unit_current_status));
                        if ($status_lower === 'ready for occupancy') {
                            $status_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $dot_class = 'bg-emerald-500';
                        } elseif ($status_lower === 'on hold') {
                            $status_class = 'bg-amber-50 text-amber-700 border-amber-200';
                            $dot_class = 'bg-amber-500';
                        } elseif ($status_lower === 'resale') {
                            $status_class = 'bg-blue-50 text-blue-700 border-blue-200';
                            $dot_class = 'bg-blue-500';
                        } elseif ($status_lower === 'reserved') {
                            $status_class = 'bg-red-50 text-red-700 border-red-200';
                            $dot_class = 'bg-red-500';
                        } elseif ($status_lower === 'occupied') {
                            $status_class = 'bg-rose-50 text-rose-700 border-rose-200';
                            $dot_class = 'bg-rose-500';
                        } elseif ($status_lower === 'under maintenance') {
                            $status_class = 'bg-orange-50 text-orange-700 border-orange-200';
                            $dot_class = 'bg-orange-500';
                        } else {
                            $status_class = 'bg-slate-50 text-slate-700 border-slate-200';
                            $dot_class = 'bg-slate-400';
                        }

                        $hasTenant = (!empty($row['tenant_name']) && $row['tenant_name'] !== 'No Tenant');
                        $price_value = peso($row['lease_rate'] ?? 0, true);

                        $listing_type = strtolower(trim($row['listing_type'] ?? 'for lease'));
                        if ($listing_type === 'resale' || $status_lower === 'resale') {
                            $listing_badge_html = '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200">Resale</span>';
                        } else {
                            $listing_badge_html = '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200">For Lease</span>';
                        }
                    ?>
                      <tr class="unit-row hover:bg-slate-50/80 transition-colors"
                          data-unit-id="<?= $unit_id ?>"
                          data-unit-number="<?= $unit_number ?>"
                          data-unit-type="<?= $unit_type ?>"
                          data-sqm="<?= $sqm_formatted ?>"
                          data-floor-number="<?= $floorNum ?>"
                          data-floor-title="<?= clean($floorTitle) ?>"
                          data-lease-rate="<?= peso($row['lease_rate'] ?? 0) ?>"
                          data-unit-current-status="<?= $unit_current_status ?>"
                          data-listing-type="<?= $listing_type ?>"
                          data-tenant-name="<?= $tenant_name ?>"
                          data-search-text="<?= strtolower("{$unit_number} {$unit_type} {$sqm_formatted} sqm {$floorTitle} Floor {$floorNum} {$listing_type} {$unit_current_status} {$tenant_name}") ?>">
                          
                          <!-- UNIT -->
                          <td class="px-5 py-3.5 whitespace-nowrap align-middle">
                              <div>
                                  <p class="unit-num font-bold text-slate-900 text-sm leading-tight"><?= $unit_number ?></p>
                                  <p class="text-xs text-slate-500 mt-0.5"><?= $unit_type ?></p>
                              </div>
                          </td>

                          <!-- LISTING -->
                          <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                              <?= $listing_badge_html ?>
                          </td>

                          <!-- STATUS -->
                          <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border <?= $status_class ?>">
                                  <span class="w-1.5 h-1.5 rounded-full <?= $dot_class ?>"></span>
                                  <?= $unit_current_status ?>
                              </span>
                          </td>

                          <!-- TENANT -->
                          <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                              <?php if ($hasTenant): ?>
                                  <p class="font-bold text-slate-900 text-sm leading-snug"><?= $tenant_name ?></p>
                              <?php else: ?>
                                  <p class="italic text-sm text-slate-400 font-medium leading-tight">No active tenant</p>
                              <?php endif; ?>
                          </td>

                          <!-- RATE -->
                          <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                              <p class="font-bold text-slate-900 font-mono text-sm leading-tight"><?= $price_value ?></p>
                          </td>

                          <!-- ACTIONS -->
                          <td class="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                              <a 
                                  href="<?= htmlspecialchars($baseUrl) ?>/owner/units/view?id=<?= $unit_id ?>"
                                  class="btn-press inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-800 bg-white border border-slate-300 hover:bg-slate-50 px-3.5 py-1.5 rounded-lg active:scale-95 transition-all shadow-xs">
                                  <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                  <span>View</span>
                              </a>
                          </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
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

<script>
function syncSearch(val) {
  const mainSearch = document.getElementById('searchInput');
  if (mainSearch) {
    mainSearch.value = val;
    applyFilters();
  }
}

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
</script>
</body>
</html>
