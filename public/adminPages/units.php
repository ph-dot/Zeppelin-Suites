<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';
require_once __DIR__ . '/../php_files/sync_unit_status.php';

$userData = requireRole($conn, ['admin']);

syncExpiredUnitStatuses($conn);

$ownerOptions = [];

$ownerSql = "SELECT user_id, full_name, email, user_role 
             FROM users_table 
             WHERE user_role IN ('tenant', 'unit owner')
             ORDER BY full_name ASC";

$ownerResult = $conn->query($ownerSql);

if ($ownerResult && $ownerResult->num_rows > 0) {
    while ($owner = $ownerResult->fetch_assoc()) {
        $ownerOptions[] = $owner;
    }
}

// Get total count of units
$totalUnitsRes = $conn->query("SELECT COUNT(*) AS total FROM units_table");
$totalUnitsCount = $totalUnitsRes ? (int)$totalUnitsRes->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin - Units</title>
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
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition:max-height 0.3s ease,opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition:transform 0.3s ease; }
.notice-chevron.rotated { transform:rotate(180deg); }
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
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- TOP CONTROL & FILTER BAR (Inspired by Reference Design) -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
        
        <!-- Header Title & Counter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900">Units</h1>
            </div>
          </div>

          <!-- Add Unit Button -->
          <div class="flex items-center gap-2.5">
            <button 
                type="button"
                id="openAddUnitModal"
                class="btn-press inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2.5 rounded-full transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add new unit</span>
            </button>
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
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search ID, floor, owner, status..." class="zep-input w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 transition-all">
          </div>

          <!-- Clear Button -->
          <button type="button" onclick="clearFilters()" id="clearFiltersBtn" class="hidden text-xs font-semibold text-slate-500 hover:text-slate-900 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
            Reset
          </button>
        </div>
      </div>

      <!-- FLOOR-CATEGORIZED UNITS CONTAINER -->
      <div id="floorsContainer" class="space-y-6">
        <?php include 'ActionsAP/getUnits.php'; ?>
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

<!-- ========================================== -->
<!-- ADD UNIT MODAL -->
<!-- ========================================== -->
<div id="addUnitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col">
        
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-900 text-white">
        <div>
          <h2 class="text-lg font-bold">Add New Unit</h2>
          <p class="text-xs text-slate-300">Assign floor and unit specifications</p>
        </div>
        <button type="button" id="closeAddUnitModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
            ✕
        </button>
    </div>

    <form action="ActionsAP/addUnit.php" method="POST" class="p-6 space-y-4 overflow-y-auto">
        
        <!-- Floor Number Selection -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Building Floor <span class="text-red-500">*</span></label>
            <select 
                name="floor_number" 
                id="addFloorNumber"
                required
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="1">1st Floor (First Floor)</option>
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

        <!-- Unit Type -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Type <span class="text-red-500">*</span></label>
            <select 
                name="unit_type" 
                id="unitType"
                required
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="">Select unit type</option>
                <option value="Studio Type A">Studio Type A</option>
                <option value="Studio Type B">Studio Type B</option>
                <option value="One Bedroom">One Bedroom</option>
                <option value="Two Bedroom">Two Bedroom</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">
                Unit number will be generated automatically based on type.
            </p>
        </div>

        <!-- Generated Unit Number & Floor Area -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Generated Unit Number</label>
            <input 
                type="text" 
                id="generatedUnitNumber"
                name="generated_unit_number"
                readonly
                placeholder="Select type first"
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm bg-slate-50 text-slate-600 font-mono font-bold cursor-not-allowed focus:outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Floor Area (SQM)</label>
            <input 
                type="text" 
                id="assignedSqm"
                readonly
                placeholder="—"
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm bg-slate-50 text-slate-700 font-mono font-bold cursor-not-allowed focus:outline-none">
          </div>
        </div>

        <!-- Owner Assignment -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Owner Assignment</label>
          <select 
              name="owner_assignment" 
              id="ownerAssignment"
              required
              class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
              <option value="none">No owner yet</option>
              <option value="existing">Select existing user</option>
              <option value="new">Create new unit owner</option>
          </select>
        </div>

        <!-- Existing Owner Box -->
        <div id="existingOwnerBox" class="hidden">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Select Existing Unit Owner</label>
            <select 
                name="existing_owner_id" 
                id="existingOwnerId"
                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                <option value="">Select unit owner</option>
                <?php foreach ($ownerOptions as $owner): ?>
                    <option value="<?php echo htmlspecialchars($owner['user_id']); ?>">
                        <?php 
                            echo htmlspecialchars($owner['full_name']) . 
                            ' (' . htmlspecialchars($owner['email']) . ')'; 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- New Owner Box -->
        <div id="newOwnerBox" class="hidden space-y-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
            <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Unit Owner Details</p>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
                <input 
                    type="text" 
                    name="new_owner_name" 
                    id="newOwnerName"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input 
                    type="email" 
                    name="new_owner_email" 
                    id="newOwnerEmail"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Contact</label>
                <input 
                    type="text" 
                    name="new_owner_contact" 
                    id="newOwnerContact"
                    class="w-full border border-slate-200 rounded-xl px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>
        </div>

        <!-- Unit Status -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Status</label>
          <select name="unit_current_status" required
              class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
              <option value="Ready for Occupancy">Ready for Occupancy</option>
              <option value="Resale">Resale</option>
              <option value="On Hold">On Hold</option>
              <option value="Reserved">Reserved</option>
              <option value="Occupied">Occupied</option>
              <option value="Under maintenance">Under maintenance</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
                type="button"
                id="cancelAddUnit"
                class="px-4 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Cancel
            </button>

            <button 
                type="submit"
                class="px-5 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
                Save Unit
            </button>
        </div>
    </form>
  </div>
</div>

<script>
// Sync top search bar with main filter search
function syncSearch(val) {
  const mainSearch = document.getElementById('searchInput');
  if (mainSearch) {
    mainSearch.value = val;
    applyFilters();
  }
}

// ----------------------------------------------------
// LIVE FILTERING LOGIC
// ----------------------------------------------------
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

    // If floor filter is set and doesn't match this floor section, hide all rows in it
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

    // Show or hide the entire floor section
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

  // Empty state handling
  const noUnitsEl = document.getElementById('noUnitsMatching');
  if (noUnitsEl) {
    if (totalVisibleUnits === 0) {
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

// ----------------------------------------------------
// ADD & EDIT UNIT MODALS LOGIC
// ----------------------------------------------------
const addUnitModal = document.getElementById('addUnitModal');
const openAddUnitModal = document.getElementById('openAddUnitModal');
const closeAddUnitModal = document.getElementById('closeAddUnitModal');
const cancelAddUnit = document.getElementById('cancelAddUnit');

const unitType = document.getElementById('unitType');
const generatedUnitNumber = document.getElementById('generatedUnitNumber');

openAddUnitModal?.addEventListener('click', () => {
    addUnitModal?.classList.remove('hidden');
    addUnitModal?.classList.add('flex');
});

function closeAddModal() {
    addUnitModal?.classList.add('hidden');
    addUnitModal?.classList.remove('flex');
}

closeAddUnitModal?.addEventListener('click', closeAddModal);
cancelAddUnit?.addEventListener('click', closeAddModal);

unitType?.addEventListener('change', async () => {
    if (!unitType.value) {
        if (generatedUnitNumber) {
          generatedUnitNumber.value = '';
          generatedUnitNumber.placeholder = 'Select unit type first';
        }
        return;
    }

    if (generatedUnitNumber) generatedUnitNumber.value = 'Generating...';

    try {
        const response = await fetch(`ActionsAP/addUnit.php?action=get_next&unit_type=${encodeURIComponent(unitType.value)}`);
        const data = await response.json();

        if (data.success && generatedUnitNumber) {
            generatedUnitNumber.value = data.unit_number;
            const assignedSqm = document.getElementById('assignedSqm');
            if (assignedSqm && data.sqm) {
                assignedSqm.value = `${parseFloat(data.sqm).toFixed(2)} SQM`;
            }
        } else if (generatedUnitNumber) {
            generatedUnitNumber.value = '';
            generatedUnitNumber.placeholder = 'Unable to generate unit number';
            const assignedSqm = document.getElementById('assignedSqm');
            if (assignedSqm) assignedSqm.value = '';
        }
    } catch (error) {
        if (generatedUnitNumber) {
          generatedUnitNumber.value = '';
          generatedUnitNumber.placeholder = 'Error generating unit number';
          const assignedSqm = document.getElementById('assignedSqm');
          if (assignedSqm) assignedSqm.value = '';
        }
    }
});

// Owner assignment toggle for Add modal
const ownerAssignment = document.getElementById('ownerAssignment');
const existingOwnerBox = document.getElementById('existingOwnerBox');
const existingOwnerId = document.getElementById('existingOwnerId');
const newOwnerBox = document.getElementById('newOwnerBox');
const newOwnerName = document.getElementById('newOwnerName');
const newOwnerEmail = document.getElementById('newOwnerEmail');
const newOwnerContact = document.getElementById('newOwnerContact');

ownerAssignment?.addEventListener('change', () => {
    existingOwnerBox?.classList.add('hidden');
    newOwnerBox?.classList.add('hidden');

    if (existingOwnerId) existingOwnerId.required = false;
    if (newOwnerName) newOwnerName.required = false;
    if (newOwnerEmail) newOwnerEmail.required = false;
    if (newOwnerContact) newOwnerContact.required = false;

    if (ownerAssignment.value === 'existing') {
        existingOwnerBox?.classList.remove('hidden');
        if (existingOwnerId) existingOwnerId.required = true;
    }

    if (ownerAssignment.value === 'new') {
        newOwnerBox?.classList.remove('hidden');
        if (newOwnerName) newOwnerName.required = true;
        if (newOwnerEmail) newOwnerEmail.required = true;
        if (newOwnerContact) newOwnerContact.required = true;
    }
});
</script>
</body>
</html>