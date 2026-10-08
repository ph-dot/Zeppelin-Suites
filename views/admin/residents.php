<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Admin Residents View
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
<title><?= e($pageTitle ?? 'Zeppelin Suites Admin - Residents') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width:256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
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
.stat-card { background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.emp-row { transition:background 0.15s ease; }
.emp-row:hover { background:#f1f5f9; }
.emp-row .emp-name { transition:color 0.15s ease; }
.emp-row:hover .emp-name { color:#1d4ed8; }
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
.search-spinner { display:none; }
.search-spinner.show { display:inline-block; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navSearchId = 'headerSearchInput';
  $navSearchPlaceholder = 'Search residents...';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- MAIN CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Header Title & Filter Bar -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Residents</h1>
          <p class="text-xs text-slate-400 mt-0.5">Manage unit owner and tenant accounts.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-2 flex-wrap" id="filterBar">
            <div class="relative">
              <input type="text" id="searchInput" value="<?= e($search) ?>" placeholder="Search name, email, contact..." class="zep-input px-4 py-2 text-sm border border-slate-200 rounded-full bg-white min-w-56">
              <svg id="searchSpinner" class="search-spinner absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
            </div>
            <select id="roleFilter" class="zep-select px-3 py-2 text-sm border border-slate-200 rounded-full bg-white">
              <option value="">All roles</option>
              <option value="unit owner" <?= $roleFilter === 'unit owner' ? 'selected' : '' ?>>Unit Owner</option>
              <option value="tenant" <?= $roleFilter === 'tenant' ? 'selected' : '' ?>>Tenant</option>
            </select>
            <select id="statusFilter" class="zep-select px-3 py-2 text-sm border border-slate-200 rounded-full bg-white">
              <option value="">All statuses</option>
              <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
              <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/residents" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-500 rounded-full hover:bg-slate-50 transition-all active:scale-95">Reset</a>
          </div>
          <button type="button" onclick="openAddResidentModal()" class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
            + Add Resident
          </button>
        </div>
      </div>

      <!-- Flash Notification Alerts -->
      <?php if (!empty($successMessage)): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 animate-in fade-in duration-200">
          <?= e($successMessage) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errorMessage)): ?>
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 animate-in fade-in duration-200">
          <?= e($errorMessage) ?>
        </div>
      <?php endif; ?>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Residents</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['total_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</p>
          <p class="text-3xl font-bold text-emerald-700 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['active_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inactive</p>
          <p class="text-3xl font-bold text-slate-600 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['inactive_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unit Owners</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['unit_owners'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tenants</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['tenants'] ?></p>
        </div>
      </div>

      <!-- Residents Directory Table -->
      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="empTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide cursor-pointer hover:text-slate-700 select-none align-middle" onclick="sortTable(0)">Name ↕</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Email</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle">Contact</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap cursor-pointer hover:text-slate-700 select-none align-middle" onclick="sortTable(3)">Role ↕</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap cursor-pointer hover:text-slate-700 select-none align-middle" onclick="sortTable(4)">Date Created ↕</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap align-middle cursor-pointer hover:text-slate-700 select-none" onclick="sortTable(5)">Status ↕</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="empBody">
              <?php if (empty($residents)): ?>
                <tr>
                  <td colspan="6" class="px-4 py-10 text-center text-slate-500 text-sm">No residents found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($residents as $resident): ?>
                  <?php include __DIR__ . '/residents_rows.php'; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 flex-wrap gap-3">
          <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700" id="resultCount"><?= count($residents) ?></span>
            of <span class="font-semibold text-slate-700"><?= (int)$stats['total_residents'] ?></span> residents
          </p>
          <p class="text-xs text-slate-400">Click any row to view resident details or manage account status.</p>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ADD RESIDENT MODAL -->
<div id="addResidentModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 px-4" onclick="if(event.target===this) closeAddResidentModal()">
  <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Add Resident</h2>
        <p class="text-xs text-slate-500 mt-1">Creates a new unit owner or tenant account.</p>
      </div>
      <button type="button" onclick="closeAddResidentModal()" class="btn-press w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500">✕</button>
    </div>
    <form action="<?= htmlspecialchars($baseUrl) ?>/admin/residents" method="POST" class="p-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Full Name</label>
          <input type="text" name="full_name" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Email</label>
          <input type="email" name="email" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Contact</label>
          <input type="text" name="contact" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Temporary Password</label>
          <input type="text" name="password" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Role</label>
          <select name="user_role" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
            <option value="tenant">Tenant</option>
            <option value="unit owner">Unit Owner</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Status</label>
          <select name="resident_status" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeAddResidentModal()" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 rounded-full text-slate-600 hover:bg-slate-50">Cancel</button>
        <button type="submit" class="btn-press px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700">Save Resident</button>
      </div>
    </form>
  </div>
</div>

<script>
  const BASE_URL = <?= json_encode($baseUrl) ?>;
  let sortDir = {};

  function sortTable(col) {
    const tbody = document.getElementById('empBody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > col);
    sortDir[col] = !sortDir[col];
    rows.sort((a,b) => {
      const va = a.cells[col]?.textContent.trim() || '';
      const vb = b.cells[col]?.textContent.trim() || '';
      const na = parseFloat(va), nb = parseFloat(vb);
      if (!isNaN(na) && !isNaN(nb)) return sortDir[col] ? na-nb : nb-na;
      return sortDir[col] ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));
  }

  function openAddResidentModal() {
    document.getElementById('addResidentModal').classList.remove('hidden');
    document.getElementById('addResidentModal').classList.add('flex');
  }

  function closeAddResidentModal() {
    document.getElementById('addResidentModal').classList.add('hidden');
    document.getElementById('addResidentModal').classList.remove('flex');
  }

  // ---- Live search / filter (AJAX, no page reload) ----
  const searchInput = document.getElementById('searchInput');
  const headerSearchInput = document.getElementById('headerSearchInput');
  const roleFilter = document.getElementById('roleFilter');
  const statusFilter = document.getElementById('statusFilter');
  const empBody = document.getElementById('empBody');
  const resultCount = document.getElementById('resultCount');
  const searchSpinner = document.getElementById('searchSpinner');

  let searchTimer = null;
  let activeRequest = null;

  function scheduleSearch(delay) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, delay);
  }

  function runSearch() {
    const search = searchInput.value.trim();
    const role = roleFilter.value;
    const status = statusFilter.value;

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (role) params.set('role', role);
    if (status) params.set('status', status);
    params.set('ajax', '1');

    if (activeRequest) activeRequest.abort();
    const controller = new AbortController();
    activeRequest = controller;

    searchSpinner.classList.add('show');

    fetch(BASE_URL + '/admin/residents?' + params.toString(), { signal: controller.signal })
      .then(res => res.text())
      .then(html => {
        empBody.innerHTML = html;
        const rowCount = empBody.querySelectorAll('tr[data-status]').length;
        resultCount.textContent = rowCount;

        // Keep browser URL synchronized
        const displayParams = new URLSearchParams();
        if (search) displayParams.set('search', search);
        if (role) displayParams.set('role', role);
        if (status) displayParams.set('status', status);
        const qs = displayParams.toString();
        history.replaceState(null, '', BASE_URL + '/admin/residents' + (qs ? '?' + qs : ''));
      })
      .catch(err => {
        if (err.name !== 'AbortError') console.error('Search failed:', err);
      })
      .finally(() => {
        searchSpinner.classList.remove('show');
      });
  }

  searchInput.addEventListener('input', () => scheduleSearch(300));
  roleFilter.addEventListener('change', () => scheduleSearch(0));
  statusFilter.addEventListener('change', () => scheduleSearch(0));

  if (headerSearchInput) {
    headerSearchInput.addEventListener('input', () => {
      searchInput.value = headerSearchInput.value;
      scheduleSearch(300);
    });
  }
</script>
</body>
</html>
