<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

$userData = requireRole($conn, ['unit owner']);
$owner_id = (int)$userData['user_id'];

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function peso($amount) {
    if ($amount === null || $amount === '') {
        return '-';
    }
    return '₱' . number_format((float)$amount, 2);
}

function percent_text($value) {
    if ($value === null || $value === '') {
        return '-';
    }
    $number = (float)$value;
    if ($number <= 1) {
        $number *= 100;
    }
    return rtrim(rtrim(number_format($number, 2), '0'), '.') . '%';
}

function format_date_only($value) {
    if (empty($value) || $value === '0000-00-00') {
        return '-';
    }
    $time = strtotime($value);
    return $time ? date('M d, Y', $time) : '-';
}

function format_datetime_text($value) {
    if (empty($value) || $value === '0000-00-00 00:00:00') {
        return '-';
    }
    $time = strtotime($value);
    return $time ? date('M d, Y h:i A', $time) : '-';
}

function status_badge($value) {
    $text = trim((string)($value ?? ''));
    $status = strtolower($text);

    if (in_array($status, ['verified', 'reserved', 'requirements completed', 'complete'], true)) {
        return "<span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200'>" . e(ucwords($text)) . "</span>";
    }

    if (in_array($status, ['pending review', 'submitted', 'under review', 'requirements pending', 'requested', 'flagged for review', 'pending'], true)) {
        return "<span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200'>" . e(ucwords($text)) . "</span>";
    }

    if (in_array($status, ['rejected', 'cancelled', 'declined'], true)) {
        return "<span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200'>" . e(ucwords($text)) . "</span>";
    }

    if ($status === 'approved') {
        return "<span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200'>" . e(ucwords($text)) . "</span>";
    }

    return "<span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200'>" . e($text !== '' ? ucwords($text) : '-') . "</span>";
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($reservation_id <= 0) {
    header("Location: ownersUnitReservations.php");
    exit;
}

$sql = "
    SELECT 
        r.reservation_id,
        r.inq_id,
        r.unit_id,
        r.client_name,
        r.client_email,
        r.client_contact,
        r.inquiry_type,
        r.resident_type,
        r.transaction_type,
        r.reservation_type,
        r.move_in_date,
        r.move_out_date,
        r.price_basis,
        r.payment_percentage,
        r.required_amount,
        r.payment_method,
        r.payment_reference,
        r.payment_proof,
        r.payment_status,
        r.reservation_status,
        r.cancellation_status,
        r.cancellation_reason,
        r.cancellation_requested_at,
        r.created_at,
        r.requirements_updated_by,
        r.requirements_updated_by_role,
        r.requirements_updated_at,
        updater.full_name AS requirements_updated_by_name,

        u.unit_number,
        u.unit_type,
        u.unit_owner_id,

        owner.full_name AS owner_name,
        owner.email AS owner_email
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
    WHERE r.reservation_id = ? AND u.unit_owner_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database query error: " . $conn->error);
}
$stmt->bind_param("ii", $reservation_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$res = $result->fetch_assoc();
$stmt->close();

if (!$res) {
    header("Location: ownersUnitReservations.php");
    exit;
}

$formattedResId = str_pad((string)$res['reservation_id'], 3, '0', STR_PAD_LEFT);
$formattedInqId = !empty($res['inq_id']) ? str_pad((string)$res['inq_id'], 3, '0', STR_PAD_LEFT) : '-';

$unitParts = array_filter([
    $res['unit_type'] ?? '',
    !empty($res['unit_number']) ? 'Unit ' . $res['unit_number'] : ''
]);
$unitDisplay = trim(implode(' ', $unitParts));
if ($unitDisplay === '') {
    $unitDisplay = 'Unit not assigned';
}

$proofUrl = '';
if (!empty($res['payment_proof'])) {
    $proofUrl = '../' . ltrim($res['payment_proof'], '/');
}

$paymentStatusLower = strtolower($res['payment_status'] ?? 'pending review');
$resStatusLower = strtolower($res['reservation_status'] ?? 'submitted');
$cancellationStatusLower = strtolower($res['cancellation_status'] ?? 'none');

$canRequestCancellation = !in_array($resStatusLower, ['cancelled', 'rejected', 'reserved'], true) 
    && $paymentStatusLower !== 'rejected' 
    && $cancellationStatusLower !== 'requested' 
    && $cancellationStatusLower !== 'approved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Reservation #<?= e($formattedResId) ?></title>
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
::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.96); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-black/20 z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
    <a href="overview.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm\'>ZEPPELIN SUITES</span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="overview.php" data-tooltip="Overview" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Overview</span>
    </a>
    <a href="ownersInquiries.php" data-tooltip="Inquiries" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiries</span>
    </a>
    <a href="ownersUnitReservations.php" data-tooltip="Reservations" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservations</span>
    </a>
    <a href="ownersBookingCalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M3 10h18M8 2v4M17 14h-6M13 18H7M7 14h.01M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="ownersUnit.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="tenants.php" data-tooltip="Tenants" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Tenants</span>
    </a>
    <a href="ownersMaintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="account.php" data-tooltip="Account" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="nav-label">Account</span>
    </a>
  </nav>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="flex items-center gap-2 text-sm text-slate-500">
      <a href="ownersUnitReservations.php" class="hover:text-slate-900 transition-colors font-medium">Reservations</a>
      <span>/</span>
      <span class="text-slate-900 font-semibold">Reservation #<?= e($formattedResId) ?></span>
    </div>

    <div class="flex items-center gap-2 ml-auto">
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">
            <?= e($userData['initial'] ?? 'U') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 truncate" id="userName">
              <?= e($userData['full_name'] ?? 'Unit Owner') ?>
            </p>
            <p class="text-xs text-slate-400">Unit Owner</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account Settings</a>
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Simple Modal -->
  <div id="logoutModal" onclick="if(event.target===this) hideModal()" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm hover:bg-slate-50 rounded-lg">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg">Logout</button>
      </div>
    </div>
  </div>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-8 space-y-6">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Breadcrumbs & Top Bar -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <a href="ownersUnitReservations.php" class="btn-press inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Reservations
          </a>
          <span class="text-xs text-slate-400 font-mono">ID: #<?= e($formattedResId) ?></span>
        </div>

        <div class="flex items-center gap-2">
          <?php if ($canRequestCancellation): ?>
            <button 
              type="button" 
              onclick="openOwnerCancelRequestModal()"
              class="btn-press inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-all shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              Request Cancellation
            </button>
          <?php endif; ?>
          <span class="text-xs text-slate-500 font-medium ml-2">Submitted: <?= e(format_datetime_text($res['created_at'])) ?></span>
        </div>
      </div>

      <!-- Pending Cancellation Alert Banner (if requested) -->
      <?php if ($cancellationStatusLower === 'requested'): ?>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center text-red-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          </div>
          <div class="flex-1">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">Cancellation Request Under Review</h3>
              <span class="text-xs text-red-600 font-mono"><?= e(format_datetime_text($res['cancellation_requested_at'])) ?></span>
            </div>
            <p class="text-xs text-red-700 mt-1 leading-relaxed">
              Your cancellation request is pending review and approval by the Zeppelin Suites administration.
            </p>
            <?php if (!empty($res['cancellation_reason'])): ?>
              <p class="text-xs text-red-800 mt-2 bg-white/70 border border-red-100 rounded-lg p-2.5">
                <strong>Reason:</strong> <?= e($res['cancellation_reason']) ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Top Summary Card -->
      <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">Reservation #<?= e($formattedResId) ?></span>
              <span class="text-slate-300">•</span>
              <span class="text-xs font-medium text-slate-500 font-mono">Inquiry #<?= e($formattedInqId) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($res['client_name']) ?></h1>
            <p class="text-sm font-medium text-slate-500 mt-1 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/></svg>
              <?= e($unitDisplay) ?>
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2.5">
            <div class="flex flex-col items-end gap-1">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Payment</span>
              <?= status_badge($res['payment_status'] ?? 'Pending Review') ?>
            </div>
            <div class="h-8 w-px bg-slate-200 mx-1 hidden sm:block"></div>
            <div class="flex flex-col items-end gap-1">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Reservation</span>
              <?= status_badge($res['reservation_status'] ?? 'Submitted') ?>
            </div>
          </div>
        </div>
      </div>

      <!-- CLIENT & UNIT INFORMATION GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- CLIENT CARD -->
        <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.015-8 4.5V20h16v-1.5c0-2.485-3.582-4.5-8-4.5z"/>
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Client Information</h2>
              <p class="text-xs text-slate-400">Personal & contact details of the applicant</p>
            </div>
          </div>

          <div class="space-y-4">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Full Name</p>
              <p class="text-sm font-bold text-slate-900 mt-0.5"><?= e($res['client_name']) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Email</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5 break-all"><?= e($res['client_email']) ?></p>
              </div>

              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Contact Number</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5 font-mono"><?= e($res['client_contact']) ?></p>
              </div>
            </div>

            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Inquiry Type</p>
              <p class="text-sm font-medium text-slate-800 mt-0.5"><?= e($res['inquiry_type'] ?: '-') ?></p>
            </div>
          </div>
        </section>

        <!-- UNIT CARD -->
        <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Unit & Lease Information</h2>
              <p class="text-xs text-slate-400">Assigned unit details and lease specifications</p>
            </div>
          </div>

          <div class="space-y-4">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit</p>
              <p class="text-sm font-bold text-slate-900 mt-0.5"><?= e($unitDisplay) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Unit Owner</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5"><?= e($res['owner_name'] ?: 'No owner assigned') ?></p>
              </div>

              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Owner Email</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5 break-all"><?= e($res['owner_email'] ?: '-') ?></p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Transaction</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5"><?= e($res['transaction_type'] ?: '-') ?></p>
              </div>

              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservation Type</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5"><?= e($res['reservation_type'] ?: '-') ?></p>
              </div>

              <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Resident Type</p>
                <p class="text-sm font-medium text-slate-800 mt-0.5"><?= e($res['resident_type'] ?: '-') ?></p>
              </div>
            </div>
          </div>
        </section>

      </div>

      <!-- PAYMENT INFORMATION -->
      <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6 pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h4v6h-4m0-6v6"/>
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Payment Information</h2>
              <p class="text-xs text-slate-500">Applicant downpayment details and admin verification status</p>
            </div>
          </div>

          <?php if (!empty($proofUrl)): ?>
            <a href="<?= e($proofUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-press inline-flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-200 rounded-xl text-xs font-bold text-blue-700 hover:bg-blue-100 transition-all shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              View Uploaded Proof
            </a>
          <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-400 rounded-xl text-xs font-medium border border-slate-200 cursor-not-allowed">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              No Proof Uploaded
            </span>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Price Basis</p>
            <p class="text-base font-bold text-slate-900 mt-1 font-mono"><?= e(peso($res['price_basis'])) ?></p>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Downpayment %</p>
            <p class="text-base font-bold text-slate-900 mt-1 font-mono"><?= e(percent_text($res['payment_percentage'])) ?></p>
          </div>

          <div class="bg-slate-900 rounded-xl p-4 text-white shadow-md">
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide">Required Amount</p>
            <p class="text-lg font-bold text-white mt-1 font-mono"><?= e(peso($res['required_amount'])) ?></p>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Method</p>
            <p class="text-base font-bold text-slate-900 mt-1"><?= e($res['payment_method'] ?: '-') ?></p>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">GCash / Payment Reference</p>
            <p class="text-sm font-semibold text-slate-800 mt-1 font-mono tracking-wider"><?= e($res['payment_reference'] ?: '-') ?></p>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Verification Status</p>
            <div class="mt-1 flex items-center gap-2">
              <?= status_badge($res['payment_status'] ?? 'Pending Review') ?>
            </div>
          </div>
        </div>

        <!-- Status Notice -->
        <?php if ($paymentStatusLower === 'verified'): ?>
          <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment Verified</p>
              <p class="text-sm font-medium text-emerald-900 mt-0.5">HOA/Admin has verified the payment. Requirement tracking is active below.</p>
            </div>
          </div>
        <?php elseif ($paymentStatusLower === 'rejected'): ?>
          <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-red-700">Payment Rejected</p>
              <p class="text-sm font-medium text-red-900 mt-0.5">HOA/Admin has rejected this payment. Requirement tracking is not available.</p>
            </div>
          </div>
        <?php else: ?>
          <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Payment Pending Review</p>
              <p class="text-sm font-medium text-amber-900 mt-0.5">HOA/Admin has not verified the payment yet. Document tracking will be unlocked upon admin verification.</p>
            </div>
          </div>
        <?php endif; ?>

        <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200/70 px-4 py-3">
          <p class="text-xs text-slate-600 leading-relaxed">
            Reservation fee is non-refundable once verified and processed. If the payment does not match the required amount, the reservation may be rejected by the admin before requirement tracking.
          </p>
        </div>
      </section>

      <!-- RESERVATION SCHEDULE -->
      <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-slate-100">
          <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Reservation Schedule</h2>
            <p class="text-xs text-slate-400">Target move-in/turnover and scheduled move-out timelines</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-in / Turnover Date</p>
            <p class="text-base font-bold text-slate-800 mt-1 font-mono"><?= e(format_date_only($res['move_in_date'])) ?></p>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Move-out Date</p>
            <p class="text-base font-bold text-slate-800 mt-1 font-mono"><?= e(format_date_only($res['move_out_date'])) ?></p>
          </div>
        </div>
      </section>

      <!-- DOCUMENT TRACKING -->
      <?php if ($paymentStatusLower === 'verified'): ?>
        <section id="requirementTrackingSection" class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between flex-wrap gap-4 mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-7 4h8m-8 4h5m-6 8h10a2 2 0 002-2V7.8a2 2 0 00-.6-1.4l-3.8-3.8A2 2 0 0013.2 2H7a2 2 0 00-2 2v15a2 2 0 002 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Document Tracking</h2>
                <p class="text-xs text-slate-500">Track and update applicant requirement submissions</p>
              </div>
            </div>

            <button
              type="button"
              id="btnEditDocuments"
              class="hidden btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3.5 py-1.5 rounded-full active:scale-95 transition-all">
              Edit Documents
            </button>
          </div>

          <input type="hidden" id="process_reservation_id" value="<?= e($res['reservation_id']) ?>">

          <div id="requirementDecisionDisplay" class="hidden mb-5 rounded-xl border px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide mb-1" id="requirementDecisionLabel">Requirement Status</p>
            <p class="text-sm font-semibold" id="requirementDecisionText">-</p>
          </div>

          <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                  <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Storage</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Link</th>
                </tr>
              </thead>
              <tbody id="documentsTableBody" class="divide-y divide-slate-100">
                <tr>
                  <td colspan="4" class="px-4 py-8 text-center text-xs text-slate-400">Loading documents...</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-5 rounded-xl bg-blue-50 border border-blue-200 px-4 py-3">
            <p class="text-xs text-blue-700 leading-relaxed">
              Only the admin can mark this reservation as officially booked, once all documents are complete.
            </p>
          </div>

          <button
            type="button"
            id="btnSaveDocuments"
            class="hidden mt-5 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
            Save Documents
          </button>

          <div class="mt-5 bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Last Updated By</p>
              <p id="requirements_updated_by_display" class="text-sm font-semibold text-slate-800 mt-0.5">
                <?= e($res['requirements_updated_by_name'] ?: 'Not updated yet') ?> (<?= e($res['requirements_updated_by_role'] ?: '-') ?>)
              </p>
            </div>
            <div class="sm:text-right">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Timestamp</p>
              <p id="requirements_updated_at_display" class="text-xs text-slate-500 font-mono mt-0.5">
                <?= e(format_datetime_text($res['requirements_updated_at'])) ?>
              </p>
            </div>
          </div>
        </section>
      <?php else: ?>
        <section class="bg-slate-100/70 border border-slate-200 rounded-2xl p-6 text-center">
          <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <h3 class="text-sm font-bold text-slate-800">Document Tracking Locked</h3>
          <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Document requirements will become available for tracking after the client payment is verified by the admin.</p>
        </section>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- OWNER CANCELLATION REQUEST MODAL -->
<div id="ownerCancelRequestModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-red-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Request Cancellation?</h2>
      <p class="text-sm text-red-50 mt-1">Admin approval is required.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        This will not cancel the reservation immediately. It will send a formal cancellation request to the admin for review.
      </p>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Reason for Cancellation <span class="text-red-500">*</span>
      </label>

      <textarea 
        id="ownerCancelReason"
        rows="4"
        placeholder="Enter reason for requesting cancellation..."
        class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button 
        type="button" 
        onclick="closeOwnerCancelRequestModal()" 
        class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Close
      </button>

      <button 
        type="button" 
        onclick="submitOwnerCancelRequest()" 
        class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">
        Submit Request
      </button>
    </div>
  </div>
</div>

<script>
  let sidebarCollapsed = false;
  const currentReservationId = <?= json_encode((int)$res['reservation_id']) ?>;
  const currentReservationStatus = <?= json_encode($res['reservation_status'] ?? '') ?>;
  const currentPaymentStatus = <?= json_encode($res['payment_status'] ?? '') ?>;

  function toggleCollapse() {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar')?.classList.toggle('collapsed', sidebarCollapsed);
    document.getElementById('mainWrapper')?.classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
  function openMobileSidebar() { 
    document.getElementById('sidebar')?.classList.add('open'); 
    document.getElementById('overlay')?.classList.add('show'); 
  }
  function closeMobileSidebar() { 
    document.getElementById('sidebar')?.classList.remove('open'); 
    document.getElementById('overlay')?.classList.remove('show'); 
  }
  function toggleProfile(e) {
    if (e) e.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    const chevron = document.getElementById('profileChevron');
    if (!dropdown) return;
    const isHidden = dropdown.classList.contains('hidden');
    dropdown.classList.toggle('hidden', !isHidden);
    if (chevron) {
      chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    }
  }
  document.addEventListener('click', function(e) {
    const profileWrapper = document.getElementById('profileWrapper');
    const dropdown = document.getElementById('profileDropdown');
    const chevron = document.getElementById('profileChevron');
    if (dropdown && !dropdown.classList.contains('hidden')) {
      if (!profileWrapper || !profileWrapper.contains(e.target)) {
        dropdown.classList.add('hidden');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
      }
    }
  });

  function confirmLogout() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.classList.remove('hidden');
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.classList.add('hidden');
    const chevron = document.getElementById('profileChevron');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  }
  function hideModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.classList.add('hidden');
  }
  function hideLogoutModal() {
    hideModal();
  }
  function doLogout() {
    window.location.href = '../php_files/logout_session.php';
  }

  // --- Document Tracking Logic ---
  let currentDocuments = [];
  let documentsEditMode = false;

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  }

  function escapeHtmlAttr(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
  }

  function storageDisplayLabel(doc) {
    if (doc.storage === 'dropbox') return 'Dropbox';
    if (doc.storage === 'gdrive') return 'Google Drive';
    if (doc.storage === 'other') return doc.storage_other_label || 'Other';
    return '-';
  }

  function updateRequirementSectionUI(paymentStatus, reservationStatus) {
    const payment = (paymentStatus || '').toLowerCase();
    const status = (reservationStatus || '').toLowerCase();

    const section = document.getElementById('requirementTrackingSection');
    const display = document.getElementById('requirementDecisionDisplay');
    const label = document.getElementById('requirementDecisionLabel');
    const text = document.getElementById('requirementDecisionText');
    const editBtn = document.getElementById('btnEditDocuments');
    const saveBtn = document.getElementById('btnSaveDocuments');

    if (!section || !display || !label || !text || !editBtn || !saveBtn) return;

    display.className = 'hidden mb-5 rounded-xl border px-4 py-3';

    if (payment !== 'verified') {
      section.classList.add('hidden');
      return;
    }

    section.classList.remove('hidden');

    if (status === 'requirements completed') {
      documentsEditMode = false;
      editBtn.classList.remove('hidden');
      saveBtn.classList.add('hidden');

      display.classList.remove('hidden');
      display.classList.add('bg-emerald-50', 'border-emerald-200');

      label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
      text.className = 'text-sm font-semibold text-emerald-800';

      label.textContent = 'Requirements Completed';
      text.textContent = 'All reservation documents have been completed. You may edit tracking if needed.';
      return;
    }

    if (status === 'reserved') {
      documentsEditMode = false;
      editBtn.classList.add('hidden');
      saveBtn.classList.add('hidden');

      display.classList.remove('hidden');
      display.classList.add('bg-emerald-50', 'border-emerald-200');

      label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
      text.className = 'text-sm font-semibold text-emerald-800';

      label.textContent = 'Officially Booked';
      text.textContent = 'This reservation is already officially booked.';
      return;
    }

    documentsEditMode = true;
    editBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    display.classList.add('hidden');
  }

  function loadDocuments(reservationId) {
    const tbody = document.getElementById('documentsTableBody');
    if (!tbody) return;

    fetch('ActionsUOP/getOwnerReservationDocuments.php?reservation_id=' + encodeURIComponent(reservationId))
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">' + escapeHtml(data.message || 'Failed to load documents.') + '</td></tr>';
          return;
        }

        currentDocuments = data.documents || [];
        renderDocumentsTable();
      })
      .catch(error => {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">Something went wrong while loading documents.</td></tr>';
      });
  }

  function renderDocumentsTable() {
    const tbody = document.getElementById('documentsTableBody');
    if (!tbody) return;

    if (!currentDocuments.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No documents found.</td></tr>';
      return;
    }

    tbody.innerHTML = currentDocuments.map(doc => {
      if (!documentsEditMode) {
        const statusBadge = doc.status === 'complete'
          ? "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>Complete</span>"
          : "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>Pending</span>";

        const linkCell = doc.document_link
          ? `<a href="${escapeHtmlAttr(doc.document_link)}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-600 hover:underline inline-flex items-center gap-1"><span>View Link</span><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`
          : "<span class='text-xs text-slate-400'>-</span>";

        return `
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="px-4 py-3.5 font-medium text-slate-800">${escapeHtml(doc.document_name)}</td>
            <td class="px-4 py-3.5">${statusBadge}</td>
            <td class="px-4 py-3.5 text-slate-600">${escapeHtml(storageDisplayLabel(doc))}</td>
            <td class="px-4 py-3.5">${linkCell}</td>
          </tr>
        `;
      }

      const isOther = doc.storage === 'other';

      return `
        <tr data-document-id="${doc.document_id}" class="hover:bg-slate-50/70 transition-colors">
          <td class="px-4 py-3.5 font-medium text-slate-800">${escapeHtml(doc.document_name)}</td>
          <td class="px-4 py-3.5">
            <select class="doc-status-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
              <option value="pending" ${doc.status !== 'complete' ? 'selected' : ''}>Pending</option>
              <option value="complete" ${doc.status === 'complete' ? 'selected' : ''}>Complete</option>
            </select>
          </td>
          <td class="px-4 py-3.5">
            <div class="flex flex-col gap-1.5">
              <select class="doc-storage-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
                <option value="" ${!doc.storage ? 'selected' : ''}>Select storage</option>
                <option value="dropbox" ${doc.storage === 'dropbox' ? 'selected' : ''}>Dropbox</option>
                <option value="gdrive" ${doc.storage === 'gdrive' ? 'selected' : ''}>Google Drive</option>
                <option value="other" ${isOther ? 'selected' : ''}>Other</option>
              </select>
              <input type="text" class="doc-storage-other-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white ${isOther ? '' : 'hidden'}" placeholder="Storage name" value="${escapeHtmlAttr(doc.storage_other_label || '')}">
            </div>
          </td>
          <td class="px-4 py-3.5">
            <input type="url" class="doc-link-input w-full text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white font-mono placeholder:font-sans placeholder:text-slate-400 focus:border-slate-900 focus:outline-none" placeholder="https://..." value="${escapeHtmlAttr(doc.document_link || '')}">
          </td>
        </tr>
      `;
    }).join('');

    if (documentsEditMode) {
      tbody.querySelectorAll('.doc-storage-input').forEach(select => {
        select.addEventListener('change', function() {
          const otherInput = this.closest('td').querySelector('.doc-storage-other-input');
          if (!otherInput) return;
          if (this.value === 'other') {
            otherInput.classList.remove('hidden');
          } else {
            otherInput.classList.add('hidden');
            otherInput.value = '';
          }
        });
      });
    }
  }

  document.getElementById('btnEditDocuments')?.addEventListener('click', function() {
    documentsEditMode = true;
    renderDocumentsTable();
    document.getElementById('btnEditDocuments')?.classList.add('hidden');
    document.getElementById('btnSaveDocuments')?.classList.remove('hidden');
  });

  function collectDocumentsPayload() {
    const rows = document.querySelectorAll('#documentsTableBody tr[data-document-id]');
    const payload = [];
    rows.forEach(row => {
      payload.push({
        document_id: row.dataset.documentId,
        status: row.querySelector('.doc-status-input')?.value || 'pending',
        storage: row.querySelector('.doc-storage-input')?.value || '',
        storage_other_label: row.querySelector('.doc-storage-other-input')?.value || '',
        document_link: row.querySelector('.doc-link-input')?.value || ''
      });
    });
    return payload;
  }

  function saveDocuments() {
    const documents = collectDocumentsPayload();
    if (!documents.length) {
      alert('No documents to save.');
      return;
    }

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);
    formData.append('documents', JSON.stringify(documents));

    fetch('ActionsUOP/updateOwnerReservationDocuments.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        window.location.reload();
      }
    })
    .catch(error => {
      console.error(error);
      alert('Something went wrong while saving document tracking.');
    });
  }

  document.getElementById('btnSaveDocuments')?.addEventListener('click', saveDocuments);

  // --- Owner Cancellation Request Modal ---
  function openOwnerCancelRequestModal() {
    const reasonBox = document.getElementById('ownerCancelReason');
    const modal = document.getElementById('ownerCancelRequestModal');
    if (reasonBox) reasonBox.value = '';
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
  }

  function closeOwnerCancelRequestModal() {
    const modal = document.getElementById('ownerCancelRequestModal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
  }

  function submitOwnerCancelRequest() {
    const reason = document.getElementById('ownerCancelReason')?.value.trim();
    if (!reason) {
      alert('Cancellation reason is required.');
      return;
    }

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);
    formData.append('reason', reason);

    fetch('ActionsUOP/requestCancellation.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        window.location.reload();
      }
    })
    .catch(error => {
      console.error(error);
      alert('Something went wrong while requesting cancellation.');
    });
  }

  // Initialize page states on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    updateRequirementSectionUI(currentPaymentStatus, currentReservationStatus);
    if (currentPaymentStatus.toLowerCase() === 'verified') {
      loadDocuments(currentReservationId);
    }
  });
</script>
</body>
</html>
