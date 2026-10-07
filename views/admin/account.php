<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Admin Account Profile View
 * Pure presentation: strictly NO SQL queries or database connections.
 */
if (!function_exists('e')) {
    function e($val): string {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$initials = strtoupper(substr((string)($admin['full_name'] ?? 'A'), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Zeppelin Suites - Admin Account') ?></title>
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
.sidebar { width: 256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background: rgba(255,255,255,0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; } .sidebar.open { transform: translateX(0); } }
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label, .sidebar.collapsed .nav-badge, .sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content: attr(data-tooltip); position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%); background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px; border-radius: 8px; white-space: nowrap; z-index: 999; box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none; }
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.glass-header { background: rgba(255,255,255,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
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
  $navSearchPlaceholder = 'Search account...';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <!-- CONTENT AREA -->
  <div class="content-area flex-1 max-w-screen-2xl mx-auto w-full mobile-scroll-wrap" id="contentArea">
    <div class="col-scroll flex-1 min-w-0 p-4 md:p-6 space-y-6">

      <!-- Breadcrumb / Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Administrator Profile</h1>
          <p class="text-xs text-slate-400 mt-0.5">Manage your personal credentials, contact points, and permissions.</p>
        </div>
      </div>

      <!-- Main Profile Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT COLUMN: Profile Card -->
        <div class="space-y-6">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 text-center">
            <div class="w-24 h-24 rounded-full bg-slate-900 flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4 ring-4 ring-slate-100 shadow-inner">
              <?= e($initials) ?>
            </div>
            <h2 class="text-lg font-bold text-slate-900"><?= e($admin['full_name']) ?></h2>
            <p class="text-xs text-slate-500 font-mono mt-0.5"><?= e($admin['email']) ?></p>
            <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <?= e(ucfirst((string)($admin['user_role'] ?? 'Admin'))) ?>
            </div>

            <div class="border-t border-slate-100 mt-6 pt-5 grid grid-cols-2 gap-4 text-left">
              <div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Account Status</span>
                <p class="text-xs font-bold text-emerald-600 mt-0.5">Active</p>
              </div>
              <div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">System ID</span>
                <p class="text-xs font-bold text-slate-700 font-mono mt-0.5">#<?= e(str_pad((string)$admin['user_id'], 4, '0', STR_PAD_LEFT)) ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Details & Permissions -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Personal Information Card -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="border-b border-slate-100 pb-4 mb-6">
              <h3 class="text-base font-bold text-slate-900">Personal Information</h3>
              <p class="text-xs text-slate-400 mt-0.5">Admin identity and direct contact information.</p>
            </div>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8 text-sm">
              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Full Name</dt>
                <dd class="mt-1 font-semibold text-slate-800 text-base"><?= e($admin['full_name']) ?></dd>
              </div>

              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Email Address</dt>
                <dd class="mt-1 font-semibold text-slate-800 text-base"><?= e($admin['email']) ?></dd>
              </div>

              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Primary Phone</dt>
                <dd class="mt-1 font-semibold text-slate-800 font-mono text-base"><?= e($admin['contact'] ?: '—') ?></dd>
              </div>

              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Date of Birth</dt>
                <dd class="mt-1 font-semibold text-slate-800 font-mono text-base"><?= e($dobFormatted) ?></dd>
              </div>

              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Additional Phone</dt>
                <dd class="mt-1 font-semibold text-slate-800 font-mono text-base"><?= e($additionalPhone) ?></dd>
              </div>

              <div class="py-1">
                <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Additional Email</dt>
                <dd class="mt-1 font-semibold text-slate-800 text-base"><?= e($additionalEmail) ?></dd>
              </div>
            </dl>
          </div>

          <!-- Security & Permissions Card -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="border-b border-slate-100 pb-4 mb-6">
              <h3 class="text-base font-bold text-slate-900">Security & Permissions</h3>
              <p class="text-xs text-slate-400 mt-0.5">Account role privileges and access security.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Assigned Role</p>
                <p class="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <span>System Administrator</span>
                  <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800">Superuser</span>
                </p>
                <p class="text-xs text-slate-500 mt-1">Full control over residents, reservations, inquiries, units, and system settings.</p>
              </div>

              <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Account Password</p>
                <p class="text-sm font-bold text-slate-900 font-mono tracking-wider">••••••••••••</p>
                <p class="text-xs text-slate-500 mt-1">Keep your password confidential and update it periodically.</p>
              </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Admin Privileges Included</p>
              <div class="flex flex-wrap gap-2 text-xs">
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Manage Residents & Accounts</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Handle Inquiries & Replies</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Confirm Reservations & Bookings</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Units & Pricing Management</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Maintenance Tracking & Updates</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Analytics & Reports</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
