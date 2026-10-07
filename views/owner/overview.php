<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Owner Overview View
 * Pure MVC presentation template. Zero direct SQL or DB connections.
 */
$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$activeTab = 'overview';

if (!function_exists('ov_e')) {
    function ov_e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Unit Owner Overview') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.action-card { transition:all 0.22s ease; cursor:pointer; }
.action-card:hover { transform:translateY(-3px); box-shadow:0 16px 32px rgba(0,0,0,0.08); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/owner_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navBreadcrumb = '<div class="flex items-center gap-2 text-sm text-slate-500 font-medium">
    <span class="text-slate-900 font-semibold">Unit Owner Portal</span>
    <span class="text-slate-300">/</span>
    <span class="text-slate-600">Overview</span>
  </div>';
  include dirname(__DIR__) . '/components/owner_navbar.php'; 
  ?>

  <!-- CONTENT -->
  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Welcome -->
      <div>
        <h1 class="text-xl font-bold text-slate-900">Welcome Back, <span class="text-slate-500 font-normal"><?= ov_e($ownerName ?? 'Unit Owner') ?></span></h1>
        <p class="text-xs text-slate-400 mt-0.5">Here's what's happening with your properties today.</p>
      </div>

      <!-- Units Information Section -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-bold text-slate-900">Units Information</h2>
          <a href="<?= htmlspecialchars($baseUrl) ?>/owner/units" class="btn-press text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors active:scale-95">View all units →</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Owned -->
          <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100">
            <p class="text-3xl font-bold text-blue-700 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($ownedUnits ?? 0) ?></p>
            <p class="text-sm font-semibold text-blue-600">Owned</p>
            <p class="text-xs text-blue-400 mt-1">Total units owned</p>
          </div>
          <!-- Occupied -->
          <div class="bg-orange-50 rounded-2xl p-4 border border-orange-100">
            <p class="text-3xl font-bold text-orange-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($occupiedUnits ?? 0) ?></p>
            <p class="text-sm font-semibold text-orange-500">Occupied</p>
            <p class="text-xs text-orange-400 mt-1">Currently tenanted</p>
          </div>
          <!-- Available -->
          <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
            <p class="text-3xl font-bold text-emerald-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($availableUnits ?? 0) ?></p>
            <p class="text-sm font-semibold text-emerald-600">Available</p>
            <p class="text-xs text-emerald-400 mt-1">Ready for occupancy</p>
          </div>
          <!-- Reserved -->
          <div class="bg-yellow-50 rounded-2xl p-4 border border-yellow-100">
            <p class="text-3xl font-bold text-yellow-600 mb-1" style="font-family:'DM Mono',monospace"><?= ov_e($reservedUnits ?? 0) ?></p>
            <p class="text-sm font-semibold text-yellow-600">Reserved</p>
            <p class="text-xs text-yellow-400 mt-1">Awaiting move-in</p>
          </div>
        </div>
      </div>

      <!-- Recent Tenant -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Recent Tenant</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Name</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Contact</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-in Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <?php if (empty($recentTenants)): ?>
                <tr>
                  <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">No tenants yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentTenants as $tenant): ?>
                  <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap"><?= ov_e($tenant['client_name'] ?? '') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-sm" style="font-family:'DM Mono',monospace"><?= ov_e($tenant['client_contact'] ?: '—') ?></td>
                    <td class="px-4 py-3.5"><span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100">Unit <?= ov_e($tenant['unit_number'] ?? '') ?></span></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= ov_e(!empty($tenant['move_in_date']) ? date('M d, Y', strtotime((string)$tenant['move_in_date'])) : '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="flex justify-end px-5 py-3 border-t border-slate-100">
          <a href="<?= htmlspecialchars($baseUrl) ?>/owner/tenants" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">view your tenants →</a>
        </div>
      </div>

      <!-- Bottom cards: Maintenance + Reservations -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Maintenance Requests -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Maintenance Requests</h2>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm">
              <tbody class="divide-y divide-slate-50">
                <?php if (empty($maintenanceRequests)): ?>
                  <tr>
                    <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-400">No maintenance requests.</td>
                  </tr>
                <?php else: ?>
                  <?php
                  $maintStatusClasses = [
                      'pending'     => 'bg-amber-50 text-amber-700 border-amber-100',
                      'in progress' => 'bg-blue-50 text-blue-700 border-blue-100',
                      'resolved'    => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                      'cancelled'   => 'bg-red-50 text-red-700 border-red-100',
                  ];
                  ?>
                  <?php foreach ($maintenanceRequests as $req): ?>
                    <?php 
                    $st = strtolower((string)($req['status'] ?? ''));
                    $statusClass = $maintStatusClasses[$st] ?? 'bg-slate-50 text-slate-700 border-slate-100'; 
                    ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                      <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap" style="font-family:'DM Mono',monospace">#<?= ov_e($req['maintenance_id']) ?></td>
                      <td class="px-4 py-3.5"><span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100">Unit <?= ov_e($req['unit_number'] ?? 'N/A') ?></span></td>
                      <td class="px-4 py-3.5"><span class="<?= ov_e($statusClass) ?> text-xs font-semibold px-2.5 py-0.5 rounded-full border"><?= ov_e(ucwords((string)$req['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="flex justify-end px-5 py-3 border-t border-slate-100 mt-auto">
            <a href="<?= htmlspecialchars($baseUrl) ?>/owner/maintenance" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">View maintenance →</a>
          </div>
        </div>

        <!-- Reservation Request -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Reservation Request</h2>
          </div>
          <?php if (empty($reservationRequests)): ?>
            <div class="flex-1 flex items-center justify-center py-8 px-5">
              <div class="text-center">
                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm text-slate-400">No pending reservation requests</p>
              </div>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto flex-1">
              <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-50">
                  <?php foreach ($reservationRequests as $reqItem): ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                      <td class="px-5 py-3.5">
                        <p class="font-semibold text-slate-800 whitespace-nowrap"><?= ov_e($reqItem['sender_name'] ?? 'Unknown') ?></p>
                        <p class="text-xs text-slate-400">Unit <?= ov_e($reqItem['unit_number'] ?? 'N/A') ?></p>
                      </td>
                      <td class="px-4 py-3.5 text-right">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/owner/inquiries" class="btn-press inline-flex items-center justify-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full active:scale-95 transition-all">Respond</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <div class="flex justify-end px-5 py-3 border-t border-slate-100 mt-auto">
            <a href="<?= htmlspecialchars($baseUrl) ?>/owner/inquiries" class="btn-press text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors active:scale-95">view inquiry requests →</a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>
