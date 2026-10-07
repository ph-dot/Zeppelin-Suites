<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

// Ensure user is an admin
$user = requireRole($conn, ['admin']);
$adminId = (int)$user['user_id'];

function e($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_date_short($date) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') return '—';
    $ts = strtotime((string)$date);
    return $ts ? date('M d, Y', $ts) : '—';
}



// Fetch fresh admin data
$stmt = $conn->prepare("SELECT * FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$admin) {
    header('Location: homeAdmin.php');
    exit;
}

$initials = strtoupper(substr($admin['full_name'] ?? 'A', 0, 1));

// Calculate Age and Format DOB
$dobFormatted = '—';
if (!empty($admin['date_of_birth'])) {
    try {
        $dobDate = new DateTime($admin['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
        $dobFormatted = $dobDate->format('M d, Y') . " ($age yrs old)";
    } catch (Exception $ex) {
        $dobFormatted = $admin['date_of_birth'];
    }
}

$additionalPhone = !empty($admin['additional_contact']) ? $admin['additional_contact'] : '—';
$additionalEmail = !empty($admin['additional_email']) ? $admin['additional_email'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Admin Account</title>
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
.collapse-icon { transition: transform 0.3s ease; }
.profile-dropdown { opacity: 0; visibility: hidden; transform: translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity: 1; visibility: visible; transform: translateY(0); }
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background: rgba(255,255,255,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- CONTENT -->
  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Administrator Account</h1>
          <p class="text-xs text-slate-400 mt-0.5">Administrator personal info, contact details, and credentials.</p>
        </div>
      </div>

      <!-- MAIN GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 items-start">

        <!-- LEFT COLUMN: Profile Card -->
        <div class="space-y-4">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex flex-col items-center text-center">
              <div class="w-20 h-20 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-bold ring-4 ring-slate-100 shadow-md">
                <?= $initials ?>
              </div>
              <h2 class="mt-3.5 text-lg font-bold text-slate-900 leading-tight"><?= e($admin['full_name']) ?></h2>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                  Administrator
                </span>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <?= e(ucfirst($admin['resident_status'] ?: 'Active')) ?>
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-2" style="font-family:'DM Mono',monospace">Joined <?= format_date_short($admin['created_at']) ?></p>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 space-y-3.5 text-sm">
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="truncate font-medium"><?= e($admin['email']) ?></span>
              </div>
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="font-medium" style="font-family:'DM Mono',monospace"><?= e($admin['contact'] ?: 'No phone provided') ?></span>
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
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Details & Credentials -->
        <div class="space-y-6">

          <!-- CARD 1: Personal Information -->
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

          <!-- CARD 2: Security & Permissions -->
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
