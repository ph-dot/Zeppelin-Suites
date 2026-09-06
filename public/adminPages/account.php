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

// Check optional columns in users_table
$colCheckDob = $conn->query("SHOW COLUMNS FROM users_table LIKE 'date_of_birth'");
$hasDobCol = $colCheckDob && $colCheckDob->num_rows > 0;

$colCheckPhone = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_contact'");
$hasAddPhoneCol = $colCheckPhone && $colCheckPhone->num_rows > 0;

$colCheckEmail = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_email'");
$hasAddEmailCol = $colCheckEmail && $colCheckEmail->num_rows > 0;

// Handle Form Submissions (Personal Info Update & Password Change)
$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $addContact = !empty($_POST['additional_contact']) ? trim($_POST['additional_contact']) : null;
        $addEmail = !empty($_POST['additional_email']) ? trim($_POST['additional_email']) : null;
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($fullName === '') {
            $toast = ['type' => 'error', 'msg' => 'Full name cannot be empty.'];
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $toast = ['type' => 'error', 'msg' => 'A valid email address is required.'];
        } else {
            // Check for email collision with other accounts
            $dup_stmt = $conn->prepare("SELECT user_id FROM users_table WHERE email = ? AND user_id <> ? LIMIT 1");
            $dup_stmt->bind_param('si', $email, $adminId);
            $dup_stmt->execute();
            $dup_res = $dup_stmt->get_result();
            if ($dup_res && $dup_res->num_rows > 0) {
                $toast = ['type' => 'error', 'msg' => 'This email address is already in use by another account.'];
            } else {
                $updates = ["full_name = ?", "email = ?", "contact = ?"];
                $types = "sss";
                $params = [$fullName, $email, $contact];

                if ($hasDobCol) {
                    $updates[] = "date_of_birth = ?";
                    $types .= "s";
                    $params[] = $dob;
                }
                if ($hasAddPhoneCol) {
                    $updates[] = "additional_contact = ?";
                    $types .= "s";
                    $params[] = $addContact;
                }
                if ($hasAddEmailCol) {
                    $updates[] = "additional_email = ?";
                    $types .= "s";
                    $params[] = $addEmail;
                }
                if (!empty($newPassword)) {
                    $updates[] = "password = ?";
                    $types .= "s";
                    $params[] = $newPassword;
                }

                $types .= "i";
                $params[] = $adminId;

                $sql = "UPDATE users_table SET " . implode(', ', $updates) . " WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        $_SESSION['full_name'] = $fullName;
                        $toast = ['type' => 'success', 'msg' => 'Your admin profile has been updated successfully!'];
                    } else {
                        $toast = ['type' => 'error', 'msg' => 'Failed to update profile: ' . $stmt->error];
                    }
                    $stmt->close();
                }
            }
            $dup_stmt->close();
        }
    }
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

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-[73px]">
    <a href="homeAdmin.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm tracking-tight\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest text-slate-500\'>SUITES</span></span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="homeAdmin.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="inquiry.php" data-tooltip="Inquiry" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
    </a>
    <a href="reservation.php" data-tooltip="Lease Management" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Lease Management</span>
    </a>
    <a href="bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18M17 14h-6M13 18H7M7 14h.01M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="residents.php" data-tooltip="Residents" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
    <a href="account.php" data-tooltip="Account" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="nav-label">Account</span>
    </a>
  </nav>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR -->
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" placeholder="Search..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <!-- Profile Menu -->
      <div class="relative">
        <button onclick="toggleProfileDropdown()" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" id="profileBtn">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold ring-2 ring-slate-200">
            <?= htmlspecialchars($user['initial'] ?? $initials) ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none"><?= htmlspecialchars($admin['full_name'] ?? 'Administrator') ?></p>
            <p class="text-xs text-slate-400 mt-0.5">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <!-- Dropdown -->
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-900 bg-slate-50 rounded-lg mx-1">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Account Settings
          </a>
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign out
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- LOGOUT MODAL -->
  <div id="logoutModal" onclick="if(event.target===this) hideModal()" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout from admin account?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm hover:bg-slate-50 rounded-lg">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg">Logout</button>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Toast message -->
      <?php if ($toast): ?>
        <div class="p-4 rounded-2xl border <?= $toast['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200' ?> text-sm font-medium flex items-center justify-between shadow-sm">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $toast['type'] === 'success' ? 'M5 13l4 4L19 7' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' ?>"/>
            </svg>
            <span><?= e($toast['msg']) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-xs font-bold hover:opacity-70">&times;</button>
        </div>
      <?php endif; ?>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Admin Account</h1>
          <p class="text-xs text-slate-400 mt-0.5">Manage your administrator personal info, contact details, and credentials.</p>
        </div>
        <button type="button" onclick="openEditModal()" class="btn-press flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-all shadow-sm active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Profile
        </button>
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

            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 font-mono">
              <span>USER ID</span>
              <span class="font-semibold text-slate-600">#ADM-<?= str_pad((string)$adminId, 4, '0', STR_PAD_LEFT) ?></span>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Details & Credentials -->
        <div class="space-y-6">

          <!-- CARD 1: Personal Information -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
              <div>
                <h3 class="text-base font-bold text-slate-900">Personal Information</h3>
                <p class="text-xs text-slate-400 mt-0.5">Admin identity and direct contact information.</p>
              </div>
              <button type="button" onclick="openEditModal()" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Edit
              </button>
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
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
              <div>
                <h3 class="text-base font-bold text-slate-900">Security & Permissions</h3>
                <p class="text-xs text-slate-400 mt-0.5">Account role privileges and access security.</p>
              </div>
              <button type="button" onclick="openEditModal()" class="text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition-colors">
                Change Password
              </button>
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

<!-- EDIT PROFILE MODAL -->
<div id="editAdminModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-slate-900">Edit Admin Profile</h3>
        <p class="text-xs text-slate-400">Update your administrator information and password.</p>
      </div>
      <button type="button" onclick="closeEditModal()" class="btn-press p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update_profile">
      
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Full Name <span class="text-red-500">*</span></label>
        <input type="text" name="full_name" required value="<?= e($admin['full_name']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Email Address <span class="text-red-500">*</span></label>
        <input type="email" name="email" required value="<?= e($admin['email']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Primary Contact Phone</label>
        <input type="text" name="contact" value="<?= e($admin['contact']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono" placeholder="e.g. 09123456789">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Date of Birth</label>
          <input type="date" name="date_of_birth" value="<?= e($admin['date_of_birth'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Phone</label>
          <input type="text" name="additional_contact" value="<?= e($admin['additional_contact'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono" placeholder="Optional">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Email</label>
        <input type="email" name="additional_email" value="<?= e($admin['additional_email'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium" placeholder="Optional">
      </div>

      <div class="pt-3 border-t border-slate-100">
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Change Password <span class="text-slate-400 font-normal normal-case">(leave blank to keep current)</span></label>
        <div class="relative">
          <input type="password" id="editAdminPassword" name="new_password" placeholder="••••••••" class="zep-input w-full pl-4 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
          <button type="button" onclick="togglePasswordVisibility('editAdminPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors p-1" title="Toggle password visibility">
            <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg class="w-4 h-4 eye-slash-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
          </button>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="btn-press px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
        <button type="submit" class="btn-press px-5 py-2.5 text-sm font-semibold bg-slate-900 text-white rounded-xl hover:bg-slate-800 shadow-md">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
  function toggleCollapse() {
    const s = document.getElementById('sidebar');
    const w = document.getElementById('mainWrapper');
    s.classList.toggle('collapsed');
    w.classList.toggle('sidebar-collapsed');
    localStorage.setItem('admin_sidebar_collapsed', s.classList.contains('collapsed'));
  }
  (function() {
    if (localStorage.getItem('admin_sidebar_collapsed') === 'true') {
      document.getElementById('sidebar').classList.add('collapsed');
      document.getElementById('mainWrapper').classList.add('sidebar-collapsed');
    }
  })();

  function openMobileSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('overlay').classList.add('show');
  }
  function closeMobileSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
  }

  function toggleProfileDropdown() {
    const d = document.getElementById('profileDropdown');
    const c = document.getElementById('profileChevron');
    const isHidden = d.classList.contains('hidden');
    d.classList.toggle('hidden');
    if (c) c.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
  }
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#profileBtn') && !e.target.closest('#profileDropdown')) {
      const d = document.getElementById('profileDropdown');
      if (d && !d.classList.contains('hidden')) {
        d.classList.add('hidden');
        const c = document.getElementById('profileChevron');
        if (c) c.style.transform = 'rotate(0deg)';
      }
    }
  });

  function confirmLogout() {
    document.getElementById('logoutModal').classList.remove('hidden');
  }
  function hideModal() {
    document.getElementById('logoutModal').classList.add('hidden');
  }
  function doLogout() {
    window.location.href = '../php_files/logout_session.php';
  }

  function openEditModal() {
    const m = document.getElementById('editAdminModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }
  function closeEditModal() {
    const m = document.getElementById('editAdminModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

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
