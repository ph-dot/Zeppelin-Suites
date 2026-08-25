<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

$userData = requireRole($conn, ['admin']);

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
    header("Location: reservation.php");
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
        r.declared_amount,
        r.amount_match_status,
        r.payment_proof,
        r.payment_status,
        r.reservation_status,
        r.admin_remarks,
        r.created_at,
        r.payment_verified_at,
        r.payment_rejected_at,
        r.admin_payment_remarks,
        r.requirements_updated_by,
        r.requirements_updated_by_role,
        r.requirements_updated_at,
        r.officially_booked_at,
        r.officially_booked_by,
        r.officially_booked_by_role,
        r.cancelled_at,
        r.cancelled_by,
        r.cancelled_by_role,
        r.admin_cancel_remarks,
        r.cancellation_status,
        r.cancellation_reason,
        r.cancellation_requested_by,
        r.cancellation_requested_at,
        r.cancellation_requested_by_role,

        u.unit_number,
        u.unit_type,
        u.unit_current_status,

        owner.full_name AS owner_name,
        owner.email AS owner_email,
        updater.full_name AS requirements_updated_by_name,
        official_user.full_name AS officially_booked_by_name,
        cancelled_user.full_name AS cancelled_by_name,
        cancel_requester.full_name AS cancellation_requested_by_name
    FROM reservation_table r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
    LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
    LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
    LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
    WHERE r.reservation_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database query error: " . $conn->error);
}
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$res = $result->fetch_assoc();
$stmt->close();

if (!$res) {
    header("Location: reservation.php");
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

$cancellationRequestedBy = 'Unit Owner';
if (($res['cancellation_requested_by_role'] ?? '') === 'client') {
    $cancellationRequestedBy = 'Client';
} elseif (!empty($res['cancellation_requested_by_name'])) {
    $cancellationRequestedBy = $res['cancellation_requested_by_name'];
}

$paymentStatusLower = strtolower($res['payment_status'] ?? 'pending review');
$resStatusLower = strtolower($res['reservation_status'] ?? 'submitted');
$cancellationStatusLower = strtolower($res['cancellation_status'] ?? 'none');
$amountMatchStatus = $res['amount_match_status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Reservation #<?= e($formattedResId) ?></title>
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
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-18.25">
    <a href="../adminPages/homeAdmin.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="../adminPages/homeAdmin.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="../adminPages/inquiry.php" data-tooltip="Inquiry" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
    </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
    </a>
    <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M3 10h18M8 2v4M17 14h-6M13 18H7M7 14h.01M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/residents.php" data-tooltip="Residents" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="../adminPages/analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
  </nav>
  <div class="notice-section px-2 py-4 border-t border-slate-100 shrink-0">
    <button onclick="toggleNotice()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-50 transition-all btn-press active:scale-95">
      <span class="nav-label">Notice</span>
      <svg class="notice-chevron w-3.5 h-3.5 text-slate-400 shrink-0" id="noticeChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="notice-panel open px-2 pt-1 space-y-0.5" id="noticePanel">
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Summer Vacation</span></a>
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Employment Notice</span></a>
    </div>
  </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="flex items-center gap-2 text-sm text-slate-500">
      <a href="reservation.php" class="hover:text-slate-900 transition-colors font-medium">Reservations</a>
      <span>/</span>
      <span class="text-slate-900 font-semibold">Reservation #<?= e($formattedResId) ?></span>
    </div>

    <div class="flex items-center gap-2 ml-auto">
      <button class="p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95 relative">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>

      <div class="relative">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">
            <?= e($userData['initial'] ?? 'A') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 truncate" id="userName">
              <?= e($userData['full_name'] ?? 'Admin') ?>
            </p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm font-medium hover:bg-slate-50 rounded-xl">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl">Logout</button>
      </div>
    </div>
  </div>

  <!-- MAIN SCROLLABLE CONTENT -->
  <main class="main-scroll p-4 md:p-8 space-y-6">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Breadcrumbs & Action Top Bar -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <a href="reservation.php" class="btn-press inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Reservations
          </a>
          <span class="text-xs text-slate-400 font-mono">ID: #<?= e($formattedResId) ?></span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500 font-medium">Submitted: <?= e(format_datetime_text($res['created_at'])) ?></span>
        </div>
      </div>

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

      <!-- PAYMENT INFORMATION & VERIFICATION -->
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
              <p class="text-xs text-slate-500">Verify client payment details before proceeding with document tracking.</p>
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

        <!-- Payment Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
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
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Declared Amount</p>
            <p class="text-base font-bold text-slate-900 mt-1 font-mono"><?= e(peso($res['declared_amount'])) ?></p>
            <?php if ($amountMatchStatus === 'match'): ?>
              <p class="text-xs font-bold mt-1 text-emerald-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Matches required
              </p>
            <?php elseif ($amountMatchStatus === 'short'): ?>
              <p class="text-xs font-bold mt-1 text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Short of required
              </p>
            <?php elseif ($amountMatchStatus === 'over'): ?>
              <p class="text-xs font-bold mt-1 text-amber-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Over required
              </p>
            <?php endif; ?>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Method</p>
            <p class="text-base font-bold text-slate-900 mt-1"><?= e($res['payment_method'] ?: '-') ?></p>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">GCash / Payment Reference</p>
            <p class="text-sm font-semibold text-slate-800 mt-1 font-mono tracking-wider"><?= e($res['payment_reference'] ?: '-') ?></p>
          </div>

          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Current Payment Status</p>
            <div class="mt-1 flex items-center gap-2">
              <?= status_badge($res['payment_status'] ?? 'Pending Review') ?>
              <?php if ($res['payment_verified_at']): ?>
                <span class="text-xs text-slate-500 font-mono">(Verified: <?= e(format_datetime_text($res['payment_verified_at'])) ?>)</span>
              <?php elseif ($res['payment_rejected_at']): ?>
                <span class="text-xs text-slate-500 font-mono">(Rejected: <?= e(format_datetime_text($res['payment_rejected_at'])) ?>)</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Payment Decision Banner (if verified or rejected) -->
        <?php if ($paymentStatusLower === 'verified'): ?>
          <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment Verified</p>
              <p class="text-sm font-medium text-emerald-900 mt-0.5">This payment has been verified. The client was notified, and document tracking is active below.</p>
              <?php if (!empty($res['admin_payment_remarks'])): ?>
                <p class="text-xs text-emerald-800 mt-1.5 italic">Remarks: <?= e($res['admin_payment_remarks']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php elseif ($paymentStatusLower === 'rejected'): ?>
          <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-red-700">Payment Rejected</p>
              <p class="text-sm font-medium text-red-900 mt-0.5">This payment was rejected. The client was notified and this reservation cannot proceed.</p>
              <?php if (!empty($res['admin_payment_remarks'])): ?>
                <p class="text-xs text-red-800 mt-1.5 italic">Reason: <?= e($res['admin_payment_remarks']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <!-- Payment Action Buttons (Pending or Flagged) -->
          <div class="mt-5 flex flex-col sm:flex-row gap-3">
            <button 
              type="button"
              id="btnVerifyPayment"
              class="btn-press flex-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Payment Matches — Verify
            </button>

            <button 
              type="button"
              id="btnFlagPayment"
              class="btn-press flex-1 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              Amount Unclear — Flag for Review
            </button>

            <button 
              type="button"
              id="btnRejectPayment"
              class="btn-press flex-1 bg-red-600 hover:bg-red-700 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Payment Does Not Match — Reject
            </button>
          </div>
        <?php endif; ?>

        <div class="mt-4 rounded-xl bg-amber-50/80 border border-amber-200/70 px-4 py-3">
          <p class="text-xs text-amber-800 leading-relaxed flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Reservation fee is non-refundable once verified and processed. If the payment does not match the required amount, the reservation may be rejected before requirement tracking.</span>
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

          <button
            type="button"
            id="btnSaveDocuments"
            class="hidden mt-5 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
            Save Documents
          </button>

          <button 
            type="button"
            id="btnOfficiallyBooked"
            class="hidden mt-3 w-full bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
            Mark as Officially Booked
          </button>

          <?php 
            $isHandedOver = in_array($resStatusLower, ['handover', 'moved in', 'active'], true);
            if (!$isHandedOver && $resStatusLower !== 'cancelled' && $resStatusLower !== 'rejected'): 
          ?>
            <button 
              type="button"
              id="btnHandoverDetail"
              onclick="openHandoverModal()"
              class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Complete Unit Handover & Move In Tenant
            </button>
          <?php elseif ($isHandedOver): ?>
            <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-center">
              <p class="text-xs font-bold text-emerald-800 uppercase tracking-wide flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Unit Handover Completed — Tenant Moved In
              </p>
            </div>
          <?php endif; ?>

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
          <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Document requirements will become available for tracking after the client payment is verified.</p>
        </section>
      <?php endif; ?>

      <!-- CANCELLATION REQUEST SECTION -->
      <?php if ($cancellationStatusLower === 'requested'): ?>
        <section id="cancellationRequestSection" class="bg-red-50 border border-red-200 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-red-200/60">
            <div class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center text-red-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-red-800 uppercase tracking-wider">Cancellation Request Pending</h2>
              <p class="text-xs text-red-600 mt-0.5">A cancellation request was submitted by <?= e($cancellationRequestedBy) ?>. Admin review and approval is required.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="bg-white border border-red-100 rounded-xl p-4">
              <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested At</p>
              <p class="text-sm font-bold text-red-800 mt-0.5 font-mono"><?= e(format_datetime_text($res['cancellation_requested_at'])) ?></p>
            </div>
            <div class="bg-white border border-red-100 rounded-xl p-4">
              <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested By</p>
              <p class="text-sm font-bold text-red-800 mt-0.5"><?= e($cancellationRequestedBy) ?></p>
            </div>
          </div>

          <div class="bg-white border border-red-100 rounded-xl p-4 mb-5">
            <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Reason for Cancellation</p>
            <p id="cancel_reason_display" class="text-sm text-red-900 mt-1 leading-relaxed"><?= e($res['cancellation_reason'] ?: 'No reason provided.') ?></p>
          </div>

          <button 
            type="button"
            id="btnApproveCancellation"
            class="btn-press w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
            Approve Cancellation & Release Unit
          </button>
        </section>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- VERIFY PAYMENT CONFIRMATION MODAL -->
<div id="verifyPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-emerald-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Verify Payment?</h2>
      <p class="text-sm text-emerald-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure the uploaded payment proof matches the required reservation amount?
      </p>

      <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
        <p class="text-xs text-emerald-700 leading-relaxed">
          This will mark the payment as verified and notify the client to proceed with the reservation requirements.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Admin Remarks / Notes
      </label>
      <textarea id="verifyPaymentRemarks" rows="3" placeholder="Optional notes..." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button 
        type="button" 
        onclick="closePaymentConfirmModal('verifyPaymentModal')" 
        class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button 
        type="button" 
        onclick="confirmPaymentAction('verify')" 
        class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">
        Yes, Verify Payment
      </button>
    </div>
  </div>
</div>

<!-- REJECT PAYMENT CONFIRMATION MODAL -->
<div id="rejectPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-red-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Reject Payment?</h2>
      <p class="text-sm text-red-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure the uploaded payment proof does not match the required reservation amount?
      </p>

      <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
        <p class="text-xs text-red-700 leading-relaxed">
          This will reject the reservation request, notify the client, and release the unit back to available status.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Reason / Admin Remarks <span class="text-red-500">*</span>
      </label>
      <textarea id="rejectPaymentRemarks" rows="3" placeholder="Example: Submitted amount does not match the required reservation amount." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')" class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button type="button" onclick="confirmPaymentAction('reject')" class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">
        Yes, Reject Payment
      </button>
    </div>
  </div>
</div>

<!-- FLAG PAYMENT FOR REVIEW MODAL -->
<div id="flagPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-amber-500 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Flag Payment for Review?</h2>
      <p class="text-sm text-amber-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Use this when the declared amount is close but not exact, or you need to follow up with the client before deciding.
      </p>

      <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
        <p class="text-xs text-amber-700 leading-relaxed">
          This will hold the reservation as "Flagged for Review" — the unit stays on hold and no email is sent automatically. Nothing else changes until you verify or reject it later.
        </p>
      </div>

      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
        Reason / Follow-up Notes <span class="text-red-500">*</span>
      </label>
      <textarea id="flagPaymentRemarks" rows="3" placeholder="Example: Declared amount is ₱200 short of the required amount, following up with client." class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closePaymentConfirmModal('flagPaymentModal')" class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Cancel
      </button>

      <button type="button" onclick="confirmPaymentAction('flag')" class="px-5 py-2 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-sm">
        Yes, Flag for Review
      </button>
    </div>
  </div>
</div>

<!-- HANDOVER MODAL -->
<div id="handoverModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" onclick="if(event.target===this) closeHandoverModal()">
  <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 flex items-center justify-between text-white">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <h2 class="text-base font-bold">Unit Handover & Move In</h2>
          <p class="text-xs text-emerald-100">Activate tenant account and occupy unit</p>
        </div>
      </div>
      <button type="button" onclick="closeHandoverModal()" class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="handoverForm" onsubmit="submitHandover(event)" class="p-6 space-y-4">
      <input type="hidden" name="reservation_id" value="<?= e($res['reservation_id']) ?>">

      <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 space-y-2 text-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 font-medium">Client:</span>
          <span class="font-bold text-slate-800"><?= e($res['client_name']) ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 font-medium">Email:</span>
          <span class="font-semibold text-slate-700"><?= e($res['client_email']) ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 font-medium">Assigned Unit:</span>
          <span class="font-bold text-emerald-700"><?= e($unitDisplay) ?></span>
        </div>
      </div>

      <div class="rounded-xl bg-emerald-50/80 border border-emerald-200 p-4 space-y-1.5 text-xs text-emerald-900">
        <p class="font-bold flex items-center gap-1.5 text-emerald-800">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          What happens upon handover confirmation:
        </p>
        <ul class="list-disc list-inside space-y-1 text-emerald-800/90 pl-1">
          <li>Reservation status updates to <strong>Moved In</strong> (Active).</li>
          <li>Unit status automatically changes to <strong>Occupied</strong>.</li>
          <li>A <strong>Tenant</strong> account is provisioned in <code class="bg-emerald-100/80 px-1 py-0.5 rounded text-[11px]">users_table</code> with <strong>Active</strong> status.</li>
          <li>The resident will be immediately visible in <a href="residents.php" target="_blank" class="underline font-semibold hover:text-emerald-950">Residents</a> and can log in to the Tenant Portal.</li>
        </ul>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">
          Tenant Initial Password <span class="font-normal text-slate-400 normal-case">(optional, defaults to "password123")</span>
        </label>
        <div class="relative">
          <input type="password" id="handoverPassword" name="password" placeholder="password123" class="zep-input w-full pl-4 pr-11 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium">
          <button type="button" onclick="togglePasswordVisibility('handoverPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors p-1" title="Toggle password visibility">
            <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg class="w-4 h-4 eye-slash-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeHandoverModal()" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50">
          Cancel
        </button>
        <button type="submit" id="btnConfirmHandover" class="btn-press px-5 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md flex items-center gap-2">
          <span>Complete Handover</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  let sidebarCollapsed = false;
  const currentReservationId = <?= json_encode((int)$res['reservation_id']) ?>;
  const currentReservationStatus = <?= json_encode($res['reservation_status'] ?? '') ?>;
  const currentPaymentStatus = <?= json_encode($res['payment_status'] ?? '') ?>;

  function toggleCollapse() {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
    document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
  function openMobileSidebar() { 
    document.getElementById('sidebar').classList.add('open'); 
    document.getElementById('overlay').classList.add('show'); 
  }
  function closeMobileSidebar() { 
    document.getElementById('sidebar').classList.remove('open'); 
    document.getElementById('overlay').classList.remove('show'); 
  }
  function toggleNotice() { 
    document.getElementById('noticePanel').classList.toggle('open'); 
    document.getElementById('noticeChevron').classList.toggle('rotated'); 
  }
  function toggleProfile() {
    const dropdown = document.getElementById('profileDropdown');
    const chevron = document.getElementById('profileChevron');
    dropdown.classList.toggle('hidden');
    chevron.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
  }
  document.addEventListener('click', function(e) {
    const profileBtn = e.target.closest('button[onclick="toggleProfile()"]');
    const profileDropdown = document.getElementById('profileDropdown');
    if (!profileDropdown.contains(e.target) && !profileBtn) {
      profileDropdown.classList.add('hidden');
      document.getElementById('profileChevron').style.transform = 'rotate(0deg)';
    }
  });

  function confirmLogout() {
    document.getElementById('logoutModal').classList.remove('hidden');
  }
  function hideModal() {
    document.getElementById('logoutModal').classList.add('hidden');
  }
  function doLogout() {
    window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php';
  }

  // --- Payment Confirmation Modals ---
  function openPaymentConfirmModal(action) {
    if (action === 'verify') {
      document.getElementById('verifyPaymentRemarks').value = '';
      document.getElementById('verifyPaymentModal').classList.remove('hidden');
      document.getElementById('verifyPaymentModal').classList.add('flex');
    }
    if (action === 'flag') {
      document.getElementById('flagPaymentRemarks').value = '';
      document.getElementById('flagPaymentModal').classList.remove('hidden');
      document.getElementById('flagPaymentModal').classList.add('flex');
    }
    if (action === 'reject') {
      document.getElementById('rejectPaymentRemarks').value = '';
      document.getElementById('rejectPaymentModal').classList.remove('hidden');
      document.getElementById('rejectPaymentModal').classList.add('flex');
    }
  }

  function closePaymentConfirmModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  }

  function confirmPaymentAction(action) {
    let remarks = '';

    if (action === 'verify') {
      remarks = document.getElementById('verifyPaymentRemarks').value.trim();
      closePaymentConfirmModal('verifyPaymentModal');
    }
    if (action === 'flag') {
      remarks = document.getElementById('flagPaymentRemarks').value.trim();
      if (remarks === '') {
        alert('Please enter a reason for flagging this payment.');
        return;
      }
      closePaymentConfirmModal('flagPaymentModal');
    }
    if (action === 'reject') {
      remarks = document.getElementById('rejectPaymentRemarks').value.trim();
      if (remarks === '') {
        alert('Please enter a reason for rejecting the payment.');
        return;
      }
      closePaymentConfirmModal('rejectPaymentModal');
    }

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);
    formData.append('action', action);
    formData.append('remarks', remarks);

    fetch('ActionsAP/updatePaymentStatus.php', {
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
      alert('Something went wrong while updating payment status.');
    });
  }

  document.getElementById('btnVerifyPayment')?.addEventListener('click', () => openPaymentConfirmModal('verify'));
  document.getElementById('btnFlagPayment')?.addEventListener('click', () => openPaymentConfirmModal('flag'));
  document.getElementById('btnRejectPayment')?.addEventListener('click', () => openPaymentConfirmModal('reject'));

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

  function updateRequirementDecisionUI(reservationStatus) {
    const status = (reservationStatus || '').toLowerCase();
    const display = document.getElementById('requirementDecisionDisplay');
    const label = document.getElementById('requirementDecisionLabel');
    const text = document.getElementById('requirementDecisionText');
    const editBtn = document.getElementById('btnEditDocuments');
    const saveBtn = document.getElementById('btnSaveDocuments');

    if (!display || !label || !text || !editBtn || !saveBtn) return;

    display.className = 'hidden mb-5 rounded-xl border px-4 py-3';

    if (status === 'requirements completed') {
      documentsEditMode = false;
      editBtn.classList.remove('hidden');
      saveBtn.classList.add('hidden');

      display.classList.remove('hidden');
      display.classList.add('bg-emerald-50', 'border-emerald-200');

      label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
      text.className = 'text-sm font-semibold text-emerald-800';

      label.textContent = 'Requirements Completed';
      text.textContent = 'All reservation documents have been completed. You may now mark this reservation as officially booked.';
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
      text.textContent = 'This reservation has already been officially booked.';
      return;
    }

    // Default / requirements pending: editable
    documentsEditMode = true;
    editBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    display.classList.add('hidden');
  }

  function refreshOfficialButton(reservationStatus, allCompleted) {
    const btn = document.getElementById('btnOfficiallyBooked');
    if (!btn) return;

    const status = (reservationStatus || '').toLowerCase();
    if (status === 'reserved') {
      btn.classList.add('hidden');
      return;
    }

    if (allCompleted) {
      btn.classList.remove('hidden');
    } else {
      btn.classList.add('hidden');
    }
  }

  function loadDocuments(reservationId) {
    const tbody = document.getElementById('documentsTableBody');
    if (!tbody) return;

    fetch('ActionsAP/getReservationDocuments.php?reservation_id=' + encodeURIComponent(reservationId))
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">' + escapeHtml(data.message || 'Failed to load documents.') + '</td></tr>';
          return;
        }

        currentDocuments = data.documents || [];
        renderDocumentsTable();
        refreshOfficialButton(currentReservationStatus, data.all_completed);
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
      tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No documents found for this reservation.</td></tr>';
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
              <input type="text" class="doc-storage-other-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white ${isOther ? '' : 'hidden'}" placeholder="Storage name / drive label" value="${escapeHtmlAttr(doc.storage_other_label || '')}">
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

    fetch('ActionsAP/updateReservationDocuments.php', {
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

  function markOfficiallyBooked() {
    if (!confirm('Mark this reservation as officially booked? This will set the unit status to Reserved.')) {
      return;
    }

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);

    fetch('ActionsAP/markOfficiallyBooked.php', {
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
      alert('Something went wrong while officially booking this reservation.');
    });
  }

  document.getElementById('btnOfficiallyBooked')?.addEventListener('click', markOfficiallyBooked);

  // --- Cancellation Approval ---
  function approveCancellationRequest() {
    const reason = document.getElementById('cancel_reason_display')?.textContent.trim();
    if (!confirm('Approve this cancellation request? This will cancel the reservation and release the unit back to availability.')) {
      return;
    }

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);
    formData.append('remarks', reason || 'Approved cancellation request from unit owner.');

    fetch('ActionsAP/cancelReservation.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        window.location.href = 'reservation.php';
      }
    })
    .catch(error => {
      console.error(error);
      alert('Something went wrong while approving cancellation.');
    });
  }

  document.getElementById('btnApproveCancellation')?.addEventListener('click', approveCancellationRequest);

  // --- Handover Handling ---
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

  function openHandoverModal() {
    const modal = document.getElementById('handoverModal');
    if (modal) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
  }

  function closeHandoverModal() {
    const modal = document.getElementById('handoverModal');
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  }

  function submitHandover(e) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmHandover');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing Handover...';

    const formData = new FormData(document.getElementById('handoverForm'));

    fetch('ActionsAP/handoverReservation.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = originalText;

      if (data.success) {
        closeHandoverModal();
        alert('Success: ' + data.message + '\n\nTenant Login Credentials:\nEmail: ' + data.tenant.email + '\nPassword: ' + data.tenant.password);
        window.location.reload();
      } else {
        alert('Error: ' + (data.message || 'Unable to process handover.'));
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = originalText;
      console.error(err);
      alert('Network error while processing handover. Please try again.');
    });
  }

  // Initialize page states on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    updateRequirementDecisionUI(currentReservationStatus);
    if (currentPaymentStatus.toLowerCase() === 'verified') {
      loadDocuments(currentReservationId);
    }
  });
</script>
</body>
</html>
