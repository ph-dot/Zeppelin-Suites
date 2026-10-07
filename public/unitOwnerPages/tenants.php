<?php
require_once __DIR__ . '/../php_files/auth.php';

$user = requireRole($conn, ['unit owner']);
$ownerId = (int)$user['user_id'];

/* ── Live data ──────────────────────────────────────────────
   Front-end/layout unchanged — just listing whoever is
   currently booked (Occupied/Reserved) into one of this
   owner's units. */
function tn_e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tn_date($value) {
    if (empty($value) || $value === '0000-00-00') return '—';
    $ts = strtotime((string)$value);
    return $ts ? date('M j, Y', $ts) : '—';
}

$tenants = [];
$stmt = $conn->prepare("
    SELECT r.unit_id, r.client_name, r.client_email, r.client_contact, r.move_in_date, r.move_out_date,
           u.unit_number, u.unit_type, u.unit_current_status
    FROM reservation_table r
    JOIN units_table u ON u.unit_id = r.unit_id
    WHERE u.unit_owner_id = ?
      AND r.officially_booked_at IS NOT NULL
      AND u.unit_current_status IN ('Occupied', 'Reserved')
    ORDER BY r.officially_booked_at DESC
");
if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Ordered latest-first, so the first row seen per unit is the current tenant
        if (!isset($tenants[$row['unit_id']])) {
            $tenants[$row['unit_id']] = $row;
        }
    }
    $stmt->close();
}
$tenants = array_values($tenants);

$unitBadgeClasses = [
    'studio type a' => 'bg-purple-50 text-purple-700 border-purple-100',
    'studio type b' => 'bg-rose-50 text-rose-700 border-rose-100',
    'one bedroom'   => 'bg-blue-50 text-blue-700 border-blue-100',
    'two bedroom'   => 'bg-amber-50 text-amber-700 border-amber-100',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — My Tenants</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width:256px; transition:width 0.3s cubic-bezier(0.4,0,0.2,1),transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition:margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.sidebar-logo { transition:opacity 0.2s ease,width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-link { position:relative; transition:all 0.18s ease; white-space:nowrap; overflow:hidden; }
.sidebar-link.active { background:#0f172a; color:#fff; }
.sidebar-link.active .nav-icon { color:#60a5fa; }
.sidebar-link:not(.active):hover { background:#eff6ff; color:#1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color:#3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .notice-section { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
.collapse-icon { transition:transform 0.3s ease; }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
/* row hover handled by Tailwind group/group-hover */
.modal-backdrop { opacity:0; visibility:hidden; transition:opacity 0.22s ease,visibility 0.22s ease; }
.modal-backdrop.open { opacity:1; visibility:visible; }
.modal-card { transform:translateY(12px) scale(0.98); transition:transform 0.22s cubic-bezier(0.4,0,0.2,1); }
.modal-backdrop.open .modal-card { transform:translateY(0) scale(1); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
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

  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-xl mx-auto space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold text-slate-900">My Tenants</h1>
      </div>

      <!-- Table Card -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="tenantsTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Tenant Name</th>
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit No.</th>
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Move-in</th>
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Lease-End</th>
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Contact</th>
                <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Status</th>
                <th class="px-4 py-3.5 w-20"></th>
              </tr>
            </thead>
            <tbody id="tenantsBody">
              <?php if (empty($tenants)): ?>
                <tr>
                  <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">No tenants under your units yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($tenants as $tenant): ?>
                  <?php
                    $isActive = strtolower($tenant['unit_current_status']) === 'occupied';
                    $statusLabel = $isActive ? 'Active' : 'Reserved';
                    $statusClass = $isActive
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                        : 'bg-yellow-50 text-yellow-700 border-yellow-100';
                    $typeClass = $unitBadgeClasses[strtolower($tenant['unit_type'])] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                    $modalData = [
                        'name' => $tenant['client_name'],
                        'unit' => $tenant['unit_number'],
                        'type' => $tenant['unit_type'],
                        'moveIn' => tn_date($tenant['move_in_date']),
                        'leaseEnd' => tn_date($tenant['move_out_date']),
                        'contact' => $tenant['client_contact'] ?: '—',
                        'email' => $tenant['client_email'] ?: '—',
                        'status' => $statusLabel,
                    ];
                    $modalJson = htmlspecialchars(json_encode($modalData), ENT_QUOTES, 'UTF-8');
                  ?>
                  <tr class="group cursor-pointer transition-colors hover:bg-slate-50/50" onclick="openTenantModal(<?= $modalJson ?>)">
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm font-semibold text-slate-800 whitespace-nowrap"><?= tn_e($tenant['client_name']) ?></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600"><span class="<?= tn_e($typeClass) ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border"><?= tn_e($tenant['unit_number']) ?></span></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= tn_e(tn_date($tenant['move_in_date'])) ?></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= tn_e(tn_date($tenant['move_out_date'])) ?></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= tn_e($tenant['client_contact'] ?: '—') ?></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600"><span class="<?= tn_e($statusClass) ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border"><?= tn_e($statusLabel) ?></span></td>
                    <td class="px-4 py-3.5 border-b border-slate-100/50 text-right"><button class="btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all opacity-0 group-hover:opacity-100 whitespace-nowrap" onclick="event.stopPropagation();openTenantModal(<?= $modalJson ?>)">View</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 flex-wrap gap-3">
          <p class="text-xs text-slate-500">Showing <span class="font-semibold text-slate-700"><?= count($tenants) > 0 ? '1–' . count($tenants) : '0' ?></span> of <span class="font-semibold text-slate-700"><?= tn_e(count($tenants)) ?></span> tenant<?= count($tenants) === 1 ? '' : 's' ?></p>
          <div class="flex items-center gap-1">
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"><svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border bg-slate-900 border-slate-900 text-white text-xs font-bold active:scale-95">1</button>
            <button class="btn-press w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"><svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
          </div>
        </div>
      </div>

    </div>
  </div>

<!-- TENANT DETAIL MODAL -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[60] flex items-center justify-center p-4" id="tenantModal" onclick="handleBackdropClick(event,'tenantModal')">
  <div class="modal-card bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100 overflow-hidden">
    <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
      <h2 class="text-base font-bold text-white">Tenant Details</h2>
      <button onclick="closeModal('tenantModal')" class="btn-press p-1.5 rounded-lg hover:bg-white/10 transition-colors active:scale-95">
        <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-slate-900 flex items-center justify-center text-white font-bold text-lg shrink-0" id="mTenantAvatar">?</div>
        <div>
          <p class="font-bold text-slate-900 text-base" id="mTenantName">—</p>
          <p class="text-xs text-slate-400" id="mTenantEmail">—</p>
        </div>
        <span class="ml-auto text-xs font-semibold px-2.5 py-1 rounded-full border" id="mTenantStatusBadge">—</span>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div><p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Unit No.</p><p class="text-sm font-bold text-slate-900" id="mTenantUnit" style="font-family:'DM Mono',monospace">—</p></div>
        <div><p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Unit Type</p><p class="text-sm text-slate-700" id="mTenantType">—</p></div>
        <div><p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Contact</p><p class="text-sm text-slate-700" id="mTenantContact" style="font-family:'DM Mono',monospace">—</p></div>
        <div></div>
        <div><p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Move-in</p><p class="text-sm text-slate-600" id="mTenantMoveIn" style="font-family:'DM Mono',monospace">—</p></div>
        <div><p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Lease End</p><p class="text-sm text-slate-600" id="mTenantLeaseEnd" style="font-family:'DM Mono',monospace">—</p></div>
      </div>
    </div>
    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/60">
      <button onclick="closeModal('tenantModal')" class="btn-press px-4 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all active:scale-95">Close</button>
      <button class="btn-press bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all active:scale-95">Contact Tenant</button>
    </div>
  </div>
</div>

<script>
  function openTenantModal(d) {
    const initials = d.name.split(' ').map(n => n[0]).join('').toUpperCase();
    document.getElementById('mTenantAvatar').textContent = initials;
    document.getElementById('mTenantName').textContent = d.name;
    document.getElementById('mTenantEmail').textContent = d.email;
    document.getElementById('mTenantUnit').textContent = d.unit;
    document.getElementById('mTenantType').textContent = d.type;
    document.getElementById('mTenantContact').textContent = d.contact;
    document.getElementById('mTenantMoveIn').textContent = d.moveIn;
    document.getElementById('mTenantLeaseEnd').textContent = d.leaseEnd;
    const badge = document.getElementById('mTenantStatusBadge');
    badge.textContent = d.status;
    badge.className = d.status === 'Active'
      ? 'ml-auto text-xs font-semibold px-2.5 py-1 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200'
      : 'ml-auto text-xs font-semibold px-2.5 py-1 rounded-full border bg-yellow-50 text-yellow-700 border-yellow-200';
    document.getElementById('tenantModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }
  function handleBackdropClick(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }
  function filterTable(query) {
    const q = (typeof query === 'string' ? query : (document.getElementById('searchInput')?.value || '')).toLowerCase().trim();
    document.querySelectorAll('#tenantsBody tr').forEach(r => {
      if (r.querySelector('td[colspan]')) return;
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }
</script>
</body>
</html>