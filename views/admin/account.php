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

        <!-- LEFT COLUMN: Profile Card & Database Backup Card -->
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

          <!-- Database Backup & Restore Card (Aligned with Admin User panel) -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3">
              <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                  </svg>
                  Database Backup
                </h3>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                  <?= e($dbStats['database_name'] ?? 'zepellin_test') ?>
                </span>
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">Manage SQL dumps and data restoration.</p>
            </div>

            <!-- Database summary stats -->
            <div class="grid grid-cols-2 gap-2 text-left bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs">
              <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Tables</span>
                <p class="font-bold text-slate-800 text-xs mt-0.5"><?= e((string)($dbStats['table_count'] ?? 0)) ?> tables</p>
              </div>
              <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Total Records</span>
                <p class="font-bold text-slate-800 text-xs mt-0.5"><?= number_format((int)($dbStats['total_rows'] ?? 0)) ?> rows</p>
              </div>
            </div>

            <!-- Export / Backup Panel -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Export Backup</span>
                <span class="text-[10px] font-medium text-slate-400">~<?= e((string)($dbStats['size_mb'] ?? '0.0')) ?> MB</span>
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">Download full SQL database dump file.</p>
              <a href="<?= htmlspecialchars($baseUrl) ?>/admin/backup/download" class="btn-press inline-flex items-center justify-center gap-2 w-full px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download Backup (.sql)</span>
              </a>
            </div>

            <!-- Restore Panel -->
            <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-900">Restore Backup</span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">.sql only</span>
              </div>
              <p class="text-[11px] text-amber-900/80 leading-relaxed">Upload and execute SQL backup file.</p>
              <form id="restoreForm" onsubmit="handleRestoreSubmit(event)" class="space-y-2">
                <input 
                  type="file" 
                  id="backupFileInput" 
                  name="backup_file" 
                  accept=".sql" 
                  required 
                  class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-white file:text-slate-700 hover:file:bg-slate-100 file:cursor-pointer border border-amber-200 rounded-lg bg-white/90 focus:outline-none">
                <button 
                  type="submit" 
                  id="btnOpenRestoreModal" 
                  class="btn-press inline-flex items-center justify-center gap-1.5 w-full px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                  <svg class="w-3.5 h-3.5 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                  </svg>
                  <span>Restore Database</span>
                </button>
              </form>
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
              <h3 class="text-base font-bold text-slate-900">Security &amp; Permissions</h3>
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
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Manage Residents &amp; Accounts</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Handle Inquiries &amp; Replies</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Confirm Reservations &amp; Bookings</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Units &amp; Pricing Management</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Maintenance Tracking &amp; Updates</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Analytics &amp; Reports</span>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-medium">✓ Database Backup &amp; Restore</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<!-- RESTORE CONFIRMATION MODAL -->
<div id="restoreConfirmModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
    <div class="bg-amber-600 px-6 py-4 flex items-center justify-between">
      <h3 class="text-base font-bold text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        Confirm Database Restore
      </h3>
      <button type="button" onclick="closeRestoreModal()" class="p-1 rounded-lg hover:bg-amber-700 text-amber-100 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-6 space-y-4">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure you want to restore the database from <strong id="modalFileName" class="text-slate-900 font-mono text-xs">selected file</strong>?
      </p>

      <div class="rounded-xl bg-amber-50 border border-amber-200 p-3.5 text-xs text-amber-800 leading-relaxed space-y-1">
        <p class="font-bold text-amber-900">⚠️ Warning:</p>
        <p>This operation will execute the uploaded SQL script, which will overwrite tables and data. Existing data not present in the backup will be replaced.</p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closeRestoreModal()" class="btn-press px-4 py-2 border border-slate-200 hover:bg-slate-100 rounded-xl text-xs font-semibold text-slate-700">
        Cancel
      </button>
      <button type="button" id="btnExecuteRestore" onclick="executeDatabaseRestore()" class="btn-press px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm flex items-center gap-2">
        <span>Yes, Restore Database</span>
      </button>
    </div>
  </div>
</div>

<script>
function handleRestoreSubmit(event) {
  event.preventDefault();
  const fileInput = document.getElementById('backupFileInput');
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    alert('Please select a .sql file to restore.');
    return;
  }

  const file = fileInput.files[0];
  if (!file.name.toLowerCase().endsWith('.sql')) {
    alert('Please select a valid .sql backup file.');
    return;
  }

  document.getElementById('modalFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  const modal = document.getElementById('restoreConfirmModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeRestoreModal() {
  const modal = document.getElementById('restoreConfirmModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

function executeDatabaseRestore() {
  const fileInput = document.getElementById('backupFileInput');
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    closeRestoreModal();
    return;
  }

  const btn = document.getElementById('btnExecuteRestore');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = 'Restoring database...';

  const formData = new FormData();
  formData.append('backup_file', fileInput.files[0]);

  fetch('<?= htmlspecialchars($baseUrl) ?>/admin/backup/restore', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = originalText;
    closeRestoreModal();

    if (data.success) {
      alert('Success: ' + (data.message || 'Database restored successfully!'));
      window.location.reload();
    } else {
      alert('Error: ' + (data.message || 'Failed to restore database.'));
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = originalText;
    closeRestoreModal();
    console.error(err);
    alert('Network or server error while restoring database.');
  });
}
</script>

</body>
</html>
