<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Resident Detail View
 * Pure MVC presentation template. Zero SQL queries or DB connections.
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_role')) {
    function format_role($role): string {
        if (strtolower((string)$role) === 'unit owner') {
            return 'Unit Owner';
        }
        return ucfirst((string)$role);
    }
}

if (!function_exists('format_date_short')) {
    function format_date_short($date): string {
        if (empty($date) || $date === '0000-00-00') return '—';
        $time = strtotime((string)$date);
        return $time ? date('M d, Y', $time) : '—';
    }
}

if (!function_exists('status_badge')) {
    function status_badge($status): string {
        if (strtolower((string)$status) === 'active') {
            return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>';
        }
        return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>';
    }
}

$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites/public'), '/');
$resident = $resident ?? [];
$user_id = (int)($resident['user_id'] ?? 0);

$initials = strtoupper(substr(trim((string)($resident['full_name'] ?: 'U')), 0, 1));
$isOwner = strtolower((string)($resident['user_role'] ?? '')) === 'unit owner';

$nameParts = explode(' ', trim((string)($resident['full_name'] ?? '')));
if (count($nameParts) > 1) {
    $lastName = array_pop($nameParts);
    $firstName = implode(' ', $nameParts);
} else {
    $firstName = $resident['full_name'] ?? '—';
    $lastName = '—';
}

$dobFormatted = '—';
$dobRaw = (string)($resident['date_of_birth'] ?? '');
if (!empty($dobRaw) && $dobRaw !== '0000-00-00') {
    $dobTime = strtotime($dobRaw);
    if ($dobTime) {
        try {
            $dobDateObj = new DateTime($dobRaw);
            $age = $dobDateObj->diff(new DateTime())->y;
            $dobFormatted = date('M d, Y', $dobTime) . " | {$age} y.o";
        } catch (Exception $e) {
            $dobFormatted = date('M d, Y', $dobTime);
        }
    }
}

$additionalPhone = !empty($resident['additional_contact']) ? $resident['additional_contact'] : '—';
$additionalEmail = !empty($resident['additional_email']) ? $resident['additional_email'] : '—';

$units = $resident['units'] ?? [];
$reservations = $resident['reservations'] ?? [];
$maintenance = $resident['maintenance'] ?? [];

$unitsCount = count($units);
$leasesCount = count($reservations);
$requestsCount = count($maintenance);
$pendingRequests = count(array_filter($maintenance, fn($item) => strtolower($item['status'] ?? '') === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin - <?= e($resident['full_name']) ?></title>
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
.collapse-icon { transition:transform 0.3s ease; }
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition:max-height 0.3s ease,opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition:transform 0.3s ease; }
.notice-chevron.rotated { transform:rotate(180deg); }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
.stat-card { background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
.profile-tab { position:relative; transition:all 0.18s ease; color:#94a3b8; }
.profile-tab.active { color:#0f172a; }
.profile-tab.active::after { content:''; position:absolute; left:0; right:0; bottom:-13px; height:2px; background:#0f172a; border-radius:2px; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navBreadcrumb = '<div class="flex items-center gap-2 text-sm text-slate-500">
    <a href="' . htmlspecialchars($baseUrl) . '/admin/residents" class="hover:text-slate-900 transition-colors font-medium">Residents</a>
    <span>/</span>
    <span class="text-slate-900 font-semibold">' . htmlspecialchars((string)($resident['full_name'] ?? 'Resident')) . '</span>
  </div>';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- MAIN CONTENT -->
  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Alert feedback -->
      <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl shadow-xs">
          <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <p class="font-medium"><?= e($_SESSION['success_message']) ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-2xl shadow-xs">
          <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p class="font-medium"><?= e($_SESSION['error_message']) ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>

      <!-- Breadcrumb + Actions -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2 text-sm">
          <a href="<?= htmlspecialchars($baseUrl) ?>/admin/residents" class="flex items-center gap-1.5 text-slate-400 hover:text-slate-600 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Residents
          </a>
          <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          <span class="text-slate-900 font-semibold"><?= e($resident['full_name']) ?></span>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" onclick="openEditModal()" class="btn-press flex items-center gap-2 bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Profile
          </button>
        </div>
      </div>

      <!-- MAIN GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 items-start">

        <!-- LEFT COLUMN: Profile Card & Quick Stats -->
        <div class="space-y-4">
          <!-- Profile summary card -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex flex-col items-center text-center">
              <div class="w-20 h-20 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-bold ring-4 ring-slate-100 shadow-md">
                <?= $initials ?>
              </div>
              <h2 class="mt-3.5 text-lg font-bold text-slate-900 leading-tight"><?= e($resident['full_name']) ?></h2>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $isOwner ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                  <?= e(format_role($resident['user_role'])) ?>
                </span>
                <?= status_badge($resident['resident_status']) ?>
              </div>
              <p class="text-xs text-slate-400 mt-2" style="font-family:'DM Mono',monospace">Joined <?= format_date_short($resident['created_at']) ?></p>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 space-y-3.5 text-sm">
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="truncate font-medium"><?= e($resident['email']) ?></span>
              </div>
              <?php if ($additionalPhone !== '—'): ?>
                <div class="flex items-center gap-3 text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span class="font-medium" style="font-family:'DM Mono',monospace"><?= e($additionalPhone) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($additionalEmail !== '—'): ?>
                <div class="flex items-center gap-3 text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                  <span class="truncate font-medium"><?= e($additionalEmail) ?></span>
                </div>
              <?php endif; ?>
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="font-medium" style="font-family:'DM Mono',monospace"><?= e($resident['contact'] ?: 'No phone provided') ?></span>
              </div>
            </div>

            <div class="mt-6 flex flex-col gap-2">
              <button type="button" onclick="openStatusModal()" class="btn-press w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl transition-all active:scale-95 <?= $resident['resident_status'] === 'Active' ? 'text-red-600 bg-red-50 hover:bg-red-100 border border-red-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' ?>">
                <?php if ($resident['resident_status'] === 'Active'): ?>
                  <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                  Deactivate Account
                <?php else: ?>
                  <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Activate Account
                <?php endif; ?>
              </button>
            </div>
          </div>


        </div>

        <!-- RIGHT COLUMN: Detailed Tabs -->
        <div class="space-y-6">

          <!-- Tabs card -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="flex items-center gap-6 border-b border-slate-100 pb-3">
              <button type="button" onclick="setProfileTab('profile', this)" class="profile-tab active text-sm font-semibold pb-3 whitespace-nowrap">Profile</button>
              <button type="button" onclick="setProfileTab('units', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Unit
              </button>
              <button type="button" onclick="setProfileTab('request', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Maintenance
              </button>
            </div>

            <!-- TAB 1: Profile Details (Matching Design Image) -->
            <div id="tab-profile" class="pt-6 max-w-lg">
              <h3 class="text-base font-bold text-slate-900 mb-6">Personal Information</h3>
              <dl class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Full Name:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= e($resident['full_name']) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Date of birth:</dt>
                  <dd class="font-semibold text-slate-800 flex-1" style="font-family:'DM Mono',monospace"><?= e($dobFormatted) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Additional phone 1:</dt>
                  <dd class="font-semibold text-slate-800 flex-1" style="font-family:'DM Mono',monospace"><?= e($additionalPhone) ?></dd>
                </div>
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Additional Email 1:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= e($additionalEmail) ?></dd>
                </div>
              </dl>
            </div>

            <!-- TAB 2: Units -->
            <div id="tab-units" class="pt-6 hidden">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Total Units</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono"><?= count($units) ?> <?= count($units) === 1 ? 'Unit' : 'Units' ?></span>
              </div>
              <?php if (empty($units)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No units currently assigned or associated with this resident.</p>
                </div>
              <?php else: ?>
                <div class="rounded-2xl border border-slate-100 overflow-hidden">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-slate-50/60 border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wide text-left">
                        <th class="px-5 py-3 align-middle">Unit Number</th>
                        <th class="px-4 py-3 align-middle">Type</th>
                        <th class="px-4 py-3 align-middle">Floor</th>
                        <th class="px-4 py-3 align-middle">Ownership</th>
                        <th class="px-5 py-3 align-middle">Current Tenant</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <?php foreach ($units as $u): ?>
                        <?php 
                          $isUnitOwned = (!empty($u['ownership_type']) && strtolower($u['ownership_type']) === 'owned')
                              || (!empty($u['unit_owner_id']) && (int)$u['unit_owner_id'] === (int)$resident['user_id'])
                              || ($isOwner && empty($u['ownership_type']));
                          $ownershipText = $isUnitOwned ? 'Owned' : 'Leased';
                          $ownershipBadgeClass = $isUnitOwned 
                              ? 'bg-indigo-50 text-indigo-700 border-indigo-200' 
                              : 'bg-blue-50 text-blue-700 border-blue-200';
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer" onclick="window.location.href='<?= htmlspecialchars($baseUrl) ?>/admin/units/view?id=<?= (int)$u['unit_id'] ?>'">
                          <td class="px-5 py-3.5 font-semibold text-slate-900 align-middle" style="font-family:'DM Mono',monospace">Unit <?= e($u['unit_number']) ?></td>
                          <td class="px-4 py-3.5 text-slate-600 font-medium align-middle"><?= e($u['unit_type'] ?: 'Standard') ?></td>
                          <td class="px-4 py-3.5 text-slate-500 align-middle" style="font-family:'DM Mono',monospace"><?= e($u['floor_number'] ?: '—') ?></td>
                          <td class="px-4 py-3.5 align-middle">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $ownershipBadgeClass ?>">
                              <?= $ownershipText ?>
                            </span>
                          </td>
                          <td class="px-5 py-3.5 text-slate-800 align-middle">
                            <?php if (!empty($u['current_tenant_name'])): ?>
                              <span class="font-semibold text-slate-900"><?= e($u['current_tenant_name']) ?></span>
                            <?php else: ?>
                              <span class="text-xs text-slate-400 italic">None</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- TAB 3: Maintenance Requests -->
            <div id="tab-request" class="pt-6 hidden">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Maintenance Requests</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $pendingRequests > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?> font-mono">
                  <?= count($maintenance) ?> <?= count($maintenance) === 1 ? 'Request' : 'Requests' ?>
                </span>
              </div>
              <?php if (empty($maintenance)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No maintenance requests logged for this resident.</p>
                </div>
              <?php else: ?>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-slate-50/60 border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wide text-left">
                        <th class="px-5 py-3 align-middle">Unit Number</th>
                        <th class="px-4 py-3 align-middle">Issue</th>
                        <th class="px-4 py-3 align-middle">Priority</th>
                        <th class="px-5 py-3 align-middle">Status</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <?php foreach ($maintenance as $m): ?>
                        <?php
                          $mStatus = strtolower($m['status'] ?? 'pending');
                          $mStatusClass = match($mStatus) {
                              'completed', 'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                              'in progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                              'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200',
                              default => 'bg-amber-50 text-amber-700 border-amber-200'
                          };
                          $mPriority = strtolower($m['priority'] ?? 'medium');
                          $mPriorityClass = match($mPriority) {
                              'urgent', 'high' => 'bg-red-50 text-red-700 border-red-200',
                              'low' => 'bg-slate-100 text-slate-600 border-slate-200',
                              default => 'bg-yellow-50 text-yellow-700 border-yellow-200'
                          };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer" onclick="window.location.href='<?= htmlspecialchars($baseUrl) ?>/admin/maintenance'">
                          <td class="px-5 py-3.5 font-semibold text-slate-900 align-middle" style="font-family:'DM Mono',monospace"><?= e($m['unit_number'] ? 'Unit ' . $m['unit_number'] : 'General') ?></td>
                          <td class="px-4 py-3.5 font-medium text-slate-800 align-middle"><?= e($m['issue_title'] ?? 'Maintenance Request') ?></td>
                          <td class="px-4 py-3.5 align-middle"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mPriorityClass ?>"><?= e(ucfirst($mPriority)) ?></span></td>
                          <td class="px-5 py-3.5 align-middle"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mStatusClass ?>"><?= e(ucfirst($mStatus)) ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<!-- Edit Resident Modal -->
<div id="editResidentModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4" onclick="if(event.target===this) closeEditModal()">
  <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Edit Resident Profile</h2>
        <p class="text-xs text-slate-500 mt-0.5">Update details for <?= e($resident['full_name']) ?></p>
      </div>
      <button type="button" onclick="closeEditModal()" class="btn-press w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center">✕</button>
    </div>
    <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/admin/residents/profile-action" class="p-6 space-y-4">
      <input type="hidden" name="user_id" value="<?= $user_id ?>">
      <input type="hidden" name="action" value="update_profile">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Full Name</label>
          <input type="text" name="full_name" value="<?= e($resident['full_name']) ?>" required class="zep-input w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Primary Email</label>
          <input type="email" name="email" value="<?= e($resident['email']) ?>" required class="zep-input w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Date of Birth</label>
          <input type="date" name="date_of_birth" value="<?= e($dobRaw !== '0000-00-00' ? $dobRaw : '') ?>" class="zep-input w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Additional Phone 1</label>
          <input type="text" name="additional_contact" value="<?= e($additionalPhone !== '—' ? $additionalPhone : '') ?>" placeholder="e.g. 0917-1234567" class="zep-input w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Additional Email 1</label>
          <input type="email" name="additional_email" value="<?= e($additionalEmail !== '—' ? $additionalEmail : '') ?>" placeholder="e.g. alt@example.com" class="zep-input w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Role</label>
          <select name="user_role" class="zep-select w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
            <option value="tenant" <?= strtolower($resident['user_role']) === 'tenant' ? 'selected' : '' ?>>Tenant</option>
            <option value="unit owner" <?= strtolower($resident['user_role']) === 'unit owner' ? 'selected' : '' ?>>Unit Owner</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Status</label>
          <select name="resident_status" class="zep-select w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
            <option value="Active" <?= $resident['resident_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $resident['resident_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">New Password <span class="normal-case font-normal text-slate-400">(leave blank to keep current password)</span></label>
          <div class="relative">
            <input type="password" id="editResidentPassword" name="new_password" placeholder="••••••••" class="zep-input w-full pl-4 pr-11 py-2.5 border border-slate-200 rounded-xl text-sm">
            <button type="button" onclick="togglePasswordVisibility('editResidentPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors p-1" title="Toggle password visibility">
              <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg class="w-4 h-4 eye-slash-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
        <button type="button" onclick="closeEditModal()" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 rounded-full text-slate-600 hover:bg-slate-50">Cancel</button>
        <button type="submit" class="btn-press px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Status Confirmation Modal -->
<div id="statusConfirmModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4" onclick="if(event.target===this) closeStatusModal()">
  <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
    <div class="p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 <?= $resident['resident_status'] === 'Active' ? 'bg-red-50 text-red-600 ring-8 ring-red-50/50' : 'bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50' ?>">
          <?php if ($resident['resident_status'] === 'Active'): ?>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <?php else: ?>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php endif; ?>
        </div>
        <button type="button" onclick="closeStatusModal()" class="btn-press w-9 h-9 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center" aria-label="Close dialog">✕</button>
      </div>

      <h3 class="text-lg font-bold text-slate-900 mb-1.5">
        <?= $resident['resident_status'] === 'Active' ? 'Deactivate Resident Account?' : 'Activate Resident Account?' ?>
      </h3>

      <p class="text-xs text-slate-500 leading-relaxed mb-5">
        <?php if ($resident['resident_status'] === 'Active'): ?>
          Are you sure you want to deactivate the account for <strong class="text-slate-800"><?= e($resident['full_name']) ?></strong>? This resident will immediately lose access to their resident portal and won't be able to log in until reactivated.
        <?php else: ?>
          Are you sure you want to activate the account for <strong class="text-slate-800"><?= e($resident['full_name']) ?></strong>? This will restore portal login access and active resident privileges for this user.
        <?php endif; ?>
      </p>

      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs mb-6">
        <span class="text-slate-500 font-medium">Resident Role</span>
        <span class="font-semibold text-slate-800"><?= e(format_role($resident['user_role'])) ?></span>
      </div>

      <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/admin/residents/profile-action">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="resident_status" value="<?= $resident['resident_status'] === 'Active' ? 'Inactive' : 'Active' ?>">

        <div class="flex items-center justify-end gap-2.5">
          <button type="button" onclick="closeStatusModal()" class="btn-press px-4 py-2.5 text-xs font-semibold border border-slate-200 rounded-full text-slate-600 hover:bg-slate-50">
            Cancel
          </button>
          <button type="submit" class="btn-press px-5 py-2.5 text-xs font-semibold rounded-full text-white transition-all shadow-sm <?= $resident['resident_status'] === 'Active' ? 'bg-red-600 hover:bg-red-700 shadow-red-600/20' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20' ?>">
            <?= $resident['resident_status'] === 'Active' ? 'Yes, Deactivate Account' : 'Yes, Activate Account' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function setProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    ['profile','units','request'].forEach(t => {
      const el = document.getElementById('tab-' + t);
      if (el) el.classList.toggle('hidden', t !== tab);
    });
  }

  function openEditModal() {
    const m = document.getElementById('editResidentModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }
  function closeEditModal() {
    const m = document.getElementById('editResidentModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  function openStatusModal() {
    const m = document.getElementById('statusConfirmModal');
    if (m) {
      m.classList.remove('hidden');
      m.classList.add('flex');
    }
  }
  function closeStatusModal() {
    const m = document.getElementById('statusConfirmModal');
    if (m) {
      m.classList.add('hidden');
      m.classList.remove('flex');
    }
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeStatusModal();
      closeEditModal();
    }
  });

  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const eyeIcon = btn.querySelector('.eye-icon');
    const eyeSlashIcon = btn.querySelector('.eye-slash-icon');
    if (input.type === 'password') {
      input.type = 'text';
      if (eyeIcon) eyeIcon.classList.add('hidden');
      if (eyeSlashIcon) eyeSlashIcon.classList.remove('hidden');
    } else {
      input.type = 'password';
      if (eyeIcon) eyeIcon.classList.remove('hidden');
      if (eyeSlashIcon) eyeSlashIcon.classList.add('hidden');
    }
  }
</script>
</body>
</html>
