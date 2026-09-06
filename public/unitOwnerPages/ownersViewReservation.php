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

function calculate_lease_duration($start, $end, $fallback = '1 year') {
    if (empty($start) || empty($end) || $start === '0000-00-00' || $end === '0000-00-00') {
        return $fallback;
    }
    try {
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $diff = $d1->diff($d2);
        $parts = [];
        if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'year' : 'years');
        if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'month' : 'months');
        if ($diff->d > 0 && empty($parts)) $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
        return !empty($parts) ? implode(' ', $parts) : $fallback;
    } catch (Exception $e) {
        return $fallback;
    }
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($reservation_id <= 0) {
    header("Location: ownersUnitReservations.php");
    exit;
}

$sql = "
    SELECT 
        r.*,
        u.unit_number,
        u.unit_type,
        u.sqm,
        u.floor_number,
        u.lease_rate,
        u.listing_type,
        u.stay_category,
        u.unit_current_status,
        u.unit_owner_id,

        owner.user_id AS owner_id,
        owner.full_name AS owner_name,
        owner.email AS owner_email,
        owner.contact AS owner_contact,
        owner.additional_contact AS owner_additional_contact,
        owner.additional_email AS owner_additional_email,

        client_user.user_id AS client_user_id,
        client_user.resident_status AS client_resident_status,
        client_user.contact AS client_user_contact,
        client_user.date_of_birth AS client_dob,

        updater.full_name AS requirements_updated_by_name,
        official_user.full_name AS officially_booked_by_name,
        cancelled_user.full_name AS cancelled_by_name,
        cancel_requester.full_name AS cancellation_requested_by_name,
        signer.full_name AS lease_signed_by_name,
        inq.lease_duration AS inq_lease_duration
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
    LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
    LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
    LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
    LEFT JOIN users_table client_user ON r.client_email = client_user.email
    LEFT JOIN users_table signer ON r.lease_signed_by = signer.user_id
    LEFT JOIN inquiry_table inq ON r.inq_id = inq.inq_id
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
$cancellationRequestedBy = $res['cancellation_requested_by_name'] ?? 'Unit Owner';

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

// Specific fields formatted for the Lease view
// Specific fields formatted for the Lease view
$clientAge = !empty($res['client_age']) ? (string)$res['client_age'] : '';
if (empty($clientAge) && !empty($res['client_dob']) && $res['client_dob'] !== '0000-00-00') {
    try {
        $d1 = new DateTime($res['client_dob']);
        $today = new DateTime('today');
        $clientAge = (string)$d1->diff($today)->y;
    } catch (Exception $e) {
        $clientAge = '—';
    }
}
if (empty($clientAge)) {
    $clientAge = '—';
}

$clientSex = !empty($res['client_sex']) ? $res['client_sex'] : (!empty($res['gender']) ? $res['gender'] : '—');
$clientNationality = !empty($res['client_nationality']) ? $res['client_nationality'] : (!empty($res['nationality']) ? $res['nationality'] : 'Filipino');
$clientFurnishing = !empty($res['furnishing']) ? $res['furnishing'] : 'Fully Furnished';

$unitNumberClean = !empty($res['unit_number']) ? $res['unit_number'] : 'A101';
$unitTypeClean = !empty($res['unit_type']) ? strtolower($res['unit_type']) : 'studio type';
$unitSqm = (float)($res['sqm'] ?? 0);
$unitSqmDisplay = $unitSqm > 0 ? number_format($unitSqm, 2) . ' SQM' : '—';
$unitSpecificationText = $unitNumberClean . ' - ' . $unitTypeClean;

$floorDisplay = !empty($res['floor_number']) ? (string)$res['floor_number'] : '1';
$listingDisplay = !empty($res['listing_type']) ? (strtolower($res['listing_type']) === 'for lease' ? 'for Lease' : $res['listing_type']) : 'for Lease';
$leaseRateDisplay = peso($res['lease_rate'] ?? $res['price_basis']) . ' /mo';
$leaseTermDuration = !empty($res['stay_category']) ? $res['stay_category'] : (!empty($res['inq_lease_duration']) ? $res['inq_lease_duration'] : 'Long Term');

$moveInDisplay = !empty($res['move_in_date']) && $res['move_in_date'] !== '0000-00-00'
    ? strtolower(date('F j, Y', strtotime($res['move_in_date'])))
    : 'september 1, 2026';

$moveOutDisplay = !empty($res['move_out_date']) && $res['move_out_date'] !== '0000-00-00'
    ? strtolower(date('F j, Y', strtotime($res['move_out_date'])))
    : 'september 1, 2027';

$computedLeaseDuration = calculate_lease_duration(
    $res['move_in_date'] ?? '',
    $res['move_out_date'] ?? '',
    !empty($res['inq_lease_duration']) ? $res['inq_lease_duration'] : '1 year'
);

$ownerNameDisplay = !empty($res['owner_name']) ? $res['owner_name'] : 'John Doe';
$ownerEmailDisplay = !empty($res['owner_email']) ? $res['owner_email'] : 'johndoe@gmail.com';
$ownerPhoneDisplay = !empty($res['owner_contact']) ? $res['owner_contact'] : '0912 345 7890';

$isFlexibleSigning = !empty($res['is_flexible_signing']) && $res['is_flexible_signing'] == 1;
$signingDateDisplay = 'Not Specified';
if ($isFlexibleSigning) {
    $signingDateDisplay = "I'm Flexible (Within validity window)";
} elseif (!empty($res['lease_signing_date']) && $res['lease_signing_date'] !== '0000-00-00') {
    $signingDateDisplay = date('F j, Y', strtotime($res['lease_signing_date']));
}

$signingStatus = !empty($res['lease_signing_status']) ? $res['lease_signing_status'] : 'Pending Signing';
$isSigningCompleted = strtolower($signingStatus) === 'completed';
$paymentMethod = !empty($res['payment_method']) ? $res['payment_method'] : 'GCash QR';
$isInHousePayment = strtolower($paymentMethod) === 'in-house';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Lease #<?= e($formattedResId) ?></title>
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
    <a href="ownersUnitReservations.php" data-tooltip="Lease Management" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Lease Management</span>
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
      <a href="ownersUnitReservations.php" class="hover:text-slate-900 transition-colors font-medium">Lease Management</a>
      <span>/</span>
      <span class="text-slate-900 font-semibold">Lease #<?= e($formattedResId) ?></span>
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
            Back to Lease Management
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

      <!-- Top Reservation ID Card (Matching Screenshot) -->
      <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservation no. ID</p>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 font-mono">REQ-<?= e($formattedResId) ?></h2>
          </div>
          <div>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <?= e(ucwords($res['reservation_status'] ?? 'In progress')) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- MAIN CONTAINER: Reservation form Details -->
      <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900 mb-5">Reservation form Details</h1>

        <!-- TAB NAVIGATION BAR (Underline style matching screenshot) -->
        <div class="border-b border-slate-200 mb-6">
          <div class="flex items-center gap-6 sm:gap-8 overflow-x-auto no-scrollbar -mb-px">
            <button 
              type="button" 
              onclick="switchReservationTab('lease')" 
              id="tabBtn-lease" 
              class="tab-nav-btn pb-3 text-sm font-bold text-slate-900 border-b-2 border-slate-900 transition-all shrink-0">
              Lease
            </button>
            <button 
              type="button" 
              onclick="switchReservationTab('payment')" 
              id="tabBtn-payment" 
              class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent transition-all shrink-0">
              payment
            </button>
            <button 
              type="button" 
              onclick="switchReservationTab('lease-signing')" 
              id="tabBtn-lease-signing" 
              class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent transition-all shrink-0">
              Lease Signing
            </button>
            <button 
              type="button" 
              onclick="switchReservationTab('documents')" 
              id="tabBtn-documents" 
              class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent transition-all shrink-0">
              Documents
            </button>
          </div>
        </div>

        <!-- TAB 1: LEASE (Matching Screenshot) -->
        <div id="tabContent-lease" class="tab-panel space-y-6">

          <!-- Client Information Section -->
          <div>
            <div class="flex items-center gap-3 mb-5">
              <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.015-8 4.5V20h16v-1.5c0-2.485-3.582-4.5-8-4.5z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900">Client Information</h2>
                <p class="text-xs text-slate-400">Personal &amp; contact details of the applicant</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
              <div>
                <p class="text-xs font-normal text-slate-400">Full Name</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($res['client_name']) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Email</p>
                <p class="text-sm font-bold text-slate-900 mt-1">
                  <a href="mailto:<?= e($res['client_email']) ?>" class="underline hover:text-blue-600 transition-colors"><?= e($res['client_email']) ?></a>
                </p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Phone Number</p>
                <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($res['client_contact'] ?: ($res['client_user_contact'] ?? '0912 345 7890')) ?></p>
              </div>

              <div>
                <p class="text-xs font-normal text-slate-400">Sex</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientSex) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Age</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientAge) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Nationality</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientNationality) ?></p>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-100 my-6"></div>

          <!-- Unit and Lease specification Section -->
          <div>
            <div class="flex items-center gap-3 mb-5">
              <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900">Unit and Lease specification</h2>
                <p class="text-xs text-slate-400">Assigned unit details and lease specifications</p>
              </div>
            </div>

            <!-- Unit Owner Subheading -->
            <h3 class="text-sm font-bold text-slate-900 mb-3">Unit Owner</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6 mb-6">
              <div>
                <p class="text-xs font-normal text-slate-400">Full Name</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($ownerNameDisplay) ?> <span class="text-xs font-semibold text-blue-600">(You)</span></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Email</p>
                <p class="text-sm font-bold text-slate-900 mt-1">
                  <a href="mailto:<?= e($ownerEmailDisplay) ?>" class="underline hover:text-blue-600 transition-colors"><?= e($ownerEmailDisplay) ?></a>
                </p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Phone Number</p>
                <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($ownerPhoneDisplay) ?></p>
              </div>
            </div>

            <!-- Unit Specifications -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
              <div>
                <p class="text-xs font-normal text-slate-400">Unit</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($unitSpecificationText) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Floor</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($floorDisplay) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Floor Area</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($unitSqmDisplay) ?></p>
              </div>

              <div>
                <p class="text-xs font-normal text-slate-400">Listing</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($listingDisplay) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Lease Rate</p>
                <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($leaseRateDisplay) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Lease term duration</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($leaseTermDuration) ?></p>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-100 my-6"></div>

          <!-- Lease commencement and Expiration Section -->
          <div>
            <h3 class="text-sm font-bold text-slate-900 mb-3">Lease commencement and Expiration</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
              <div>
                <p class="text-xs font-normal text-slate-400">Move in Date</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($moveInDisplay) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Move out Date</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($moveOutDisplay) ?></p>
              </div>
              <div>
                <p class="text-xs font-normal text-slate-400">Lease Duration</p>
                <p class="text-sm font-bold text-slate-900 mt-1"><?= e($computedLeaseDuration) ?></p>
              </div>
            </div>

            <!-- Client Remarks / Message under Lease Commencement -->
            <div class="mt-5 pt-4 border-t border-slate-100">
              <p class="text-xs font-normal text-slate-400">Remarks / Client Message</p>
              <div class="mt-1.5 p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-xs sm:text-sm text-slate-700 leading-relaxed">
                <?php if (!empty($res['client_remarks'])): ?>
                  <p class="font-medium text-slate-800"><?= nl2br(e($res['client_remarks'])) ?></p>
                <?php else: ?>
                  <p class="text-slate-400 italic">No special remarks or requests submitted by applicant.</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- View Inquiry Action Button/Link -->
            <div class="flex justify-end mt-6">
              <a href="ownersUnitReservations.php" class="text-sm font-bold text-slate-900 hover:text-blue-600 transition-colors inline-flex items-center gap-1.5">
                <span>View Inquiry</span>
              </a>
            </div>
          </div>

          <!-- PENDING CANCELLATION ALERT (IF APPLICABLE) -->
          <?php if ($cancellationStatusLower === 'requested'): ?>
            <section class="mt-6 bg-red-50 border border-red-200 rounded-2xl p-6 shadow-sm">
              <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-red-200/60">
                <div class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center text-red-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div>
                  <h2 class="text-sm font-bold text-red-800 uppercase tracking-wider">Cancellation Request Pending Review</h2>
                  <p class="text-xs text-red-600 mt-0.5">Your cancellation request is pending review and approval by Zeppelin Suites administration.</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-red-100 rounded-xl p-4">
                  <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested At</p>
                  <p class="text-sm font-bold text-red-800 mt-0.5 font-mono"><?= e(format_datetime_text($res['cancellation_requested_at'])) ?></p>
                </div>
                <div class="bg-white border border-red-100 rounded-xl p-4">
                  <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Status</p>
                  <p class="text-sm font-bold text-red-800 mt-0.5">Awaiting Admin Decision</p>
                </div>
              </div>

              <div class="bg-white border border-red-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Reason for Cancellation</p>
                <p class="text-sm text-red-900 mt-1 leading-relaxed"><?= e($res['cancellation_reason'] ?: 'No reason provided.') ?></p>
              </div>
            </section>
          <?php endif; ?>
        </div>

      <!-- TAB 2: PAYMENT (Matching Image 2) -->
      <div id="tabContent-payment" class="tab-panel space-y-6 hidden">
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
                <p class="text-xs text-slate-500">Applicant downpayment details and owner verification status</p>
              </div>
            </div>

            <?php if ($isInHousePayment): ?>
              <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-50 text-amber-800 rounded-xl text-xs font-bold border border-amber-200">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Pay In-House (At Lease Signing)
              </span>
            <?php elseif (!empty($proofUrl)): ?>
              <a href="<?= e($proofUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-press inline-flex items-center gap-2 px-3.5 py-1.5 bg-blue-50 border border-blue-200 rounded-xl text-xs font-bold text-blue-700 hover:bg-blue-100 transition-all shadow-sm">
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

          <!-- Proof & Verification Status Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if ($isInHousePayment): ?>
              <!-- In-House Payment Notice Card -->
              <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Payment Method</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300/80">Pay In-House</span>
                  </div>
                  <h4 class="text-xs font-bold text-slate-900 uppercase">In-House Settlement Notice</h4>
                  <p class="text-xs text-slate-700 mt-2 leading-relaxed">
                    The applicant selected <strong>Pay In-House</strong>. No electronic proof upload was required. The downpayment (<strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong>) will be collected directly in cash or manager's check during the scheduled lease signing appointment.
                  </p>
                </div>

                <div class="mt-4 pt-3 border-t border-amber-200/80 text-[11px] text-amber-800 font-medium flex items-center gap-1.5">
                  <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Collect &amp; acknowledge payment during lease signing appointment.</span>
                </div>
              </div>
            <?php else: ?>
              <!-- Uploaded Proof Card (GCash QR) -->
              <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Uploaded Proof of Payment</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">GCash QR</span>
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <?php if (!empty($proofUrl)): ?>
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100/80 text-emerald-800 border border-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Proof Attached
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                        No Proof Uploaded
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-200/60">
                  <?php if (!empty($proofUrl)): ?>
                    <a href="<?= e($proofUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-press inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      View Uploaded Proof
                    </a>
                  <?php else: ?>
                    <span class="text-xs text-slate-400 italic">No document file on record</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Payment Verification Status Card -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col justify-between">
              <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Verification Status</p>
                <div class="mt-2 flex items-center gap-2 flex-wrap">
                  <?= status_badge($res['payment_status'] ?? 'Pending Review') ?>
                  <?php if (!empty($res['payment_verified_at'])): ?>
                    <span class="text-xs text-slate-500 font-mono">(Verified: <?= e(format_datetime_text($res['payment_verified_at'])) ?>)</span>
                  <?php elseif (!empty($res['payment_rejected_at'])): ?>
                    <span class="text-xs text-slate-500 font-mono">(Rejected: <?= e(format_datetime_text($res['payment_rejected_at'])) ?>)</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-200/60 text-xs text-slate-500">
                <?php if ($paymentStatusLower === 'verified'): ?>
                  <span class="text-emerald-700 font-semibold">✓ <?= $isInHousePayment ? 'In-House Payment Received &amp; Verified' : 'Verified &amp; Confirmed' ?></span>
                <?php elseif ($paymentStatusLower === 'rejected'): ?>
                  <span class="text-red-700 font-semibold">✕ Payment Rejected</span>
                <?php else: ?>
                  <span class="text-amber-700 font-semibold">● <?= $isInHousePayment ? 'Awaiting payment collection at lease signing' : 'Awaiting your verification' ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Status Notice / Action Buttons (Matching Image 2) -->
          <?php if ($paymentStatusLower === 'verified'): ?>
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
              <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment Verified by You</p>
                <p class="text-sm font-medium text-emerald-900 mt-0.5">You have verified this downpayment in your account. Requirement tracking is active in the Documents tab.</p>
                <?php if (!empty($res['admin_payment_remarks'])): ?>
                  <p class="text-xs text-emerald-800 mt-1.5 italic">Remarks: <?= e($res['admin_payment_remarks']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php elseif ($paymentStatusLower === 'rejected'): ?>
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-start gap-3">
              <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-red-700">Payment Rejected by You</p>
                <p class="text-sm font-medium text-red-900 mt-0.5">You have rejected this payment. This reservation cannot proceed.</p>
                <?php if (!empty($res['admin_payment_remarks'])): ?>
                  <p class="text-xs text-red-800 mt-1.5 italic">Reason: <?= e($res['admin_payment_remarks']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php elseif ($isInHousePayment): ?>
            <?php if ($paymentStatusLower === 'verified'): ?>
              <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">In-House Payment Completed</p>
                  <p class="text-sm font-medium text-emerald-900 mt-0.5">The downpayment has been received and confirmed in-house.</p>
                  <?php if (!empty($res['admin_payment_remarks'])): ?>
                    <p class="text-xs text-emerald-800 mt-1.5 italic">Remarks: <?= e($res['admin_payment_remarks']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            <?php else: ?>
              <div class="mt-5 flex items-center justify-between flex-wrap gap-4 p-5 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                  <h4 class="text-sm font-bold text-slate-900">In-House Downpayment Settlement</h4>
                  <p class="text-xs text-slate-500 mt-0.5">
                    Downpayment (<strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong>) to be collected in cash or check.
                  </p>
                </div>
                <button 
                  type="button" 
                  id="btnCompleteInHousePayment"
                  class="btn-press px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  Payment Completed
                </button>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-start gap-3">
              <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Payment Pending Your Verification</p>
                <p class="text-sm font-medium text-amber-900 mt-0.5">Please check the uploaded payment proof above and confirm receipt in your GCash account. Document tracking will be unlocked once you verify this payment.</p>
                <?php if (!empty($res['admin_payment_remarks'])): ?>
                  <p class="text-xs text-amber-800 mt-1.5 italic">Current note: <?= e($res['admin_payment_remarks']) ?></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Unit Owner Payment Action Buttons (Matching Image 2) -->
            <div class="mt-5 flex flex-col sm:flex-row gap-3">
              <button 
                type="button" 
                id="btnVerifyPayment"
                class="btn-press flex-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Payment Received — Verify
              </button>

              <button 
                type="button" 
                id="btnFlagPayment"
                class="btn-press flex-1 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Amount Unclear — Flag
              </button>

              <button 
                type="button" 
                id="btnRejectPayment"
                class="btn-press flex-1 bg-red-600 hover:bg-red-700 active:scale-95 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Not Received / Reject
              </button>
            </div>

            <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200/70 px-4 py-3">
              <p class="text-xs text-slate-600 leading-relaxed">
                Reservation fee is non-refundable once verified and processed. If the payment does not match the required amount, the reservation may be rejected before requirement tracking.
              </p>
            </div>
          <?php endif; ?>
        </section>
      </div>

      <!-- TAB 3: LEASE SIGNING -->
      <div id="tabContent-lease-signing" class="tab-panel space-y-6 hidden">
        <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between flex-wrap gap-4 mb-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Lease Signing</h2>
                <p class="text-xs text-slate-500">Contract execution schedule and completion status</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <?php if ($isSigningCompleted): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  Signing Completed
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  Pending Signing
                </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Schedule & Tenant Overview Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            
            <!-- Card 1: Chosen Lease Signing Date -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
              <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Chosen Lease Signing Date</p>
                <?php if ($isFlexibleSigning): ?>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Flexible Schedule</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-800 border border-violet-200">Fixed Date</span>
                <?php endif; ?>
              </div>

              <div>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 font-mono tracking-tight"><?= e($signingDateDisplay) ?></h3>
                <?php if ($isFlexibleSigning): ?>
                  <p class="text-xs text-slate-500 mt-1">Applicant selected "I'm Flexible". Appointment can be scheduled anytime within the form validity period before move-in.</p>
                <?php else: ?>
                  <p class="text-xs text-slate-500 mt-1">Applicant selected this specific date during form submission.</p>
                <?php endif; ?>
              </div>

              <div class="pt-3 border-t border-slate-200/70 grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-slate-400 block mb-0.5">Move-in Date:</span>
                  <span class="font-bold text-slate-900"><?= e($moveInDisplay) ?></span>
                </div>
                <div>
                  <span class="text-slate-400 block mb-0.5">Payment Method:</span>
                  <span class="font-bold text-slate-900"><?= e($paymentMethod) ?></span>
                </div>
              </div>
            </div>

            <!-- Card 2: Applicant & Unit Details -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Signer &amp; Unit Details</p>

              <div class="space-y-2.5 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-slate-400 font-medium">Tenant / Applicant:</span>
                  <span class="font-bold text-slate-900 text-right"><?= e($res['client_name']) ?></span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <span class="text-slate-400 font-medium">Contact Number:</span>
                  <span class="font-bold text-slate-900 font-mono text-right"><?= e($res['client_contact'] ?: ($res['client_user_contact'] ?? '0912 345 7890')) ?></span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <span class="text-slate-400 font-medium">Email:</span>
                  <span class="font-semibold text-slate-800 text-right truncate max-w-[200px]"><?= e($res['client_email']) ?></span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <span class="text-slate-400 font-medium">Assigned Unit:</span>
                  <span class="font-bold text-slate-900 text-right"><?= e($unitSpecificationText) ?></span>
                </div>
              </div>

              <?php if ($isInHousePayment): ?>
                <div class="pt-3 border-t border-amber-200 bg-amber-50/60 -mx-5 -mb-5 p-3 rounded-b-xl text-[11px] text-amber-800 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  <span>Reminder: Collect <strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong> downpayment in cash/check during this signing appointment.</span>
                </div>
              <?php endif; ?>
            </div>

          </div>

          <!-- Signing Status Banner & Action Buttons -->
          <?php if ($isSigningCompleted): ?>
            <!-- Completed State -->
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-emerald-900">Lease Signing Completed &amp; Executed</h4>
                  <p class="text-xs text-emerald-700 mt-0.5">
                    Lease contract was marked as completed on <strong><?= e(format_datetime_text($res['lease_signed_at'])) ?></strong>
                    <?php if (!empty($res['lease_signed_by_name'])): ?>
                      by <strong><?= e($res['lease_signed_by_name']) ?></strong>.
                    <?php endif; ?>
                  </p>
                  <?php if (!empty($res['lease_signing_remarks'])): ?>
                    <p class="text-xs text-emerald-900/90 mt-2 italic bg-white/70 px-3 py-1.5 rounded-lg border border-emerald-200/60 inline-block">
                      Remarks: <?= e($res['lease_signing_remarks']) ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>

              <button 
                type="button" 
                onclick="openSigningModal('reset')"
                class="btn-press text-xs font-semibold text-slate-500 hover:text-slate-800 bg-white border border-slate-200 rounded-xl px-4 py-2 hover:bg-slate-50 shadow-2xs transition-all">
                Reset Status
              </button>
            </div>
          <?php else: ?>
            <!-- Action Bar: Complete Lease Signing -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div class="space-y-1">
                <h4 class="text-sm font-bold text-slate-900">Finalize &amp; Complete Lease Signing</h4>
                <p class="text-xs text-slate-500">
                  Once the lease contract agreement has been formally signed by both the tenant and yourself, click below to mark the signing appointment as complete.
                </p>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <button 
                  type="button" 
                  onclick="openSigningModal('complete')"
                  class="btn-press px-5 py-2.5 bg-[#0f172a] hover:bg-[#1e293b] active:scale-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md flex items-center gap-2 transition-all">
                  <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  Complete Lease Signing
                </button>
              </div>
            </div>
          <?php endif; ?>

        </section>
      </div>

      <!-- TAB 4: DOCUMENTS (Matching Image 1) -->
      <div id="tabContent-documents" class="tab-panel space-y-6 hidden">
        <section id="requirementTrackingSection" class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between flex-wrap gap-4 mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5 fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

          <!-- Officially Booked Banner (Matching Image 1) -->
          <div id="requirementDecisionDisplay" class="hidden mb-5 rounded-xl border px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide mb-1" id="requirementDecisionLabel">Requirement Status</p>
            <p class="text-sm font-semibold" id="requirementDecisionText">-</p>
          </div>

          <!-- Documents Table (Matching Image 1) -->
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
      </div>
      <!-- END RESERVATION FORM DETAILS -->
    </div>
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

<!-- PAYMENT VERIFICATION MODAL -->
<div id="verifyPaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
        Confirm Payment Verification
      </h3>
      <button type="button" onclick="closePaymentConfirmModal('verifyPaymentModal')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <p class="text-sm text-slate-600 leading-relaxed">
        Are you sure you want to verify this payment? Confirm that you have reviewed the uploaded payment proof and verified receipt in your account.
      </p>
      <div class="p-3.5 bg-emerald-50 border border-emerald-100 rounded-xl text-xs text-emerald-800">
        ✓ This will unlock document tracking for this reservation and notify the client to submit requirements.
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Remarks (Optional)</label>
        <textarea id="verifyPaymentRemarks" rows="2" placeholder="e.g. Received via GCash, amounts match." class="zep-input w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm"></textarea>
      </div>
    </div>
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
      <button type="button" onclick="closePaymentConfirmModal('verifyPaymentModal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl">Cancel</button>
      <button type="button" onclick="confirmPaymentAction('verify')" class="btn-press px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">Confirm &amp; Verify</button>
    </div>
  </div>
</div>

<!-- PAYMENT FLAG MODAL -->
<div id="flagPaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
        Flag Payment for Review
      </h3>
      <button type="button" onclick="closePaymentConfirmModal('flagPaymentModal')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <p class="text-sm text-slate-600 leading-relaxed">
        Flag this payment if the transaction amount is short, unclear, or if you need clarification from the client before verifying.
      </p>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Note to Tenant <span class="text-red-500">*</span></label>
        <textarea id="flagPaymentRemarks" rows="3" placeholder="e.g. Sent amount is ₱5,000 short of required ₱10,000 downpayment. Please clarify." class="zep-input w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" required></textarea>
      </div>
    </div>
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
      <button type="button" onclick="closePaymentConfirmModal('flagPaymentModal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl">Cancel</button>
      <button type="button" onclick="confirmPaymentAction('flag')" class="btn-press px-5 py-2.5 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-sm">Flag Payment</button>
    </div>
  </div>
</div>

<!-- PAYMENT REJECT MODAL -->
<div id="rejectPaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
        Reject Payment
      </h3>
      <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <p class="text-sm text-slate-600 leading-relaxed">
        Are you sure you want to reject this payment? This will close the reservation and notify the client that their payment was not received or accepted.
      </p>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Reason for Rejection <span class="text-red-500">*</span></label>
        <textarea id="rejectPaymentRemarks" rows="3" placeholder="e.g. Reference number invalid and no payment was received in GCash." class="zep-input w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" required></textarea>
      </div>
    </div>
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
      <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl">Cancel</button>
      <button type="button" onclick="confirmPaymentAction('reject')" class="btn-press px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">Confirm Rejection</button>
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

  // --- Payment Verification Actions & Modals ---
  function openPaymentConfirmModal(action) {
    if (action === 'verify') {
      const box = document.getElementById('verifyPaymentRemarks');
      if (box) box.value = '';
      const m = document.getElementById('verifyPaymentModal');
      m?.classList.remove('hidden');
      m?.classList.add('flex');
    }
    if (action === 'flag') {
      const box = document.getElementById('flagPaymentRemarks');
      if (box) box.value = '';
      const m = document.getElementById('flagPaymentModal');
      m?.classList.remove('hidden');
      m?.classList.add('flex');
    }
    if (action === 'reject') {
      const box = document.getElementById('rejectPaymentRemarks');
      if (box) box.value = '';
      const m = document.getElementById('rejectPaymentModal');
      m?.classList.remove('hidden');
      m?.classList.add('flex');
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
      remarks = document.getElementById('verifyPaymentRemarks')?.value.trim() || '';
      closePaymentConfirmModal('verifyPaymentModal');
    }
    if (action === 'flag') {
      remarks = document.getElementById('flagPaymentRemarks')?.value.trim() || '';
      if (remarks === '') {
        alert('Please enter a note/reason for flagging this payment.');
        return;
      }
      closePaymentConfirmModal('flagPaymentModal');
    }
    if (action === 'reject') {
      remarks = document.getElementById('rejectPaymentRemarks')?.value.trim() || '';
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

    fetch('ActionsUOP/verifyOwnerPayment.php', {
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

  // --- In-House Payment Completion ---
  function openInHousePaymentModal() {
    const box = document.getElementById('inHousePaymentRemarks');
    if (box) box.value = '';
    const m = document.getElementById('completeInHousePaymentModal');
    m?.classList.remove('hidden');
    m?.classList.add('flex');
  }

  function closeInHousePaymentModal() {
    const m = document.getElementById('completeInHousePaymentModal');
    m?.classList.add('hidden');
    m?.classList.remove('flex');
  }

  function submitInHousePaymentComplete() {
    const remarks = document.getElementById('inHousePaymentRemarks')?.value.trim() || 'In-House payment collected & completed.';
    closeInHousePaymentModal();

    const formData = new FormData();
    formData.append('reservation_id', currentReservationId);
    formData.append('action', 'verify');
    formData.append('remarks', remarks);

    fetch('ActionsUOP/verifyOwnerPayment.php', {
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
      alert('Something went wrong while completing in-house payment.');
    });
  }

  document.getElementById('btnCompleteInHousePayment')?.addEventListener('click', openInHousePaymentModal);

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
    section.classList.remove('hidden');

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

  // --- Reservation Tabs Navigation ---
  function switchReservationTab(tabName) {
    const tabs = ['lease', 'payment', 'lease-signing', 'documents'];
    if (!tabs.includes(tabName)) tabName = 'lease';

    tabs.forEach(t => {
      const btn = document.getElementById('tabBtn-' + t);
      const panel = document.getElementById('tabContent-' + t);
      if (!btn || !panel) return;

      if (t === tabName) {
        btn.className = 'tab-nav-btn pb-3 text-sm font-bold text-slate-900 border-b-2 border-slate-900 transition-all shrink-0';
        panel.classList.remove('hidden');
      } else {
        btn.className = 'tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent transition-all shrink-0';
        panel.classList.add('hidden');
      }
    });

    if (tabName === 'documents' && typeof loadDocuments === 'function') {
      const resId = document.getElementById('process_reservation_id')?.value || currentReservationId;
      if (resId && (!currentDocuments || currentDocuments.length === 0)) {
        loadDocuments(resId);
      }
    }

    if (history.replaceState) {
      history.replaceState(null, null, '#' + tabName);
    } else {
      location.hash = '#' + tabName;
    }
  }

  // --- Lease Signing Actions ---
  function openSigningModal(action) {
    const modal = document.getElementById('leaseSigningModal');
    const actionInput = document.getElementById('signingActionInput');
    const title = document.getElementById('signingModalTitle');
    const desc = document.getElementById('signingModalDesc');
    const btn = document.getElementById('btnConfirmSigning');
    const remarks = document.getElementById('signingRemarksInput');

    actionInput.value = action;
    remarks.value = '';

    if (action === 'complete') {
      title.textContent = 'Complete Lease Signing';
      desc.textContent = 'Are you sure you want to mark this lease signing as completed? This confirms that the contract has been formally signed and finalized.';
      btn.textContent = 'Complete Signing';
      btn.className = 'px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold transition-all';
    } else {
      title.textContent = 'Reset Lease Signing Status';
      desc.textContent = 'Are you sure you want to reset the lease signing status back to Pending Signing?';
      btn.textContent = 'Reset Status';
      btn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-all';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeSigningModal() {
    const modal = document.getElementById('leaseSigningModal');
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  }

  async function submitLeaseSigningAction() {
    const action = document.getElementById('signingActionInput').value;
    const remarks = document.getElementById('signingRemarksInput').value;
    const reservationId = currentReservationId;
    const btn = document.getElementById('btnConfirmSigning');

    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
      const formData = new FormData();
      formData.append('reservation_id', reservationId);
      formData.append('action', action);
      formData.append('remarks', remarks);

      const res = await fetch('ActionsUOP/completeOwnerLeaseSigning.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      } else {
        alert(data.message || 'Failed to update lease signing status.');
        btn.disabled = false;
        btn.textContent = 'Confirm';
      }
    } catch (err) {
      console.error(err);
      alert('Network error while updating lease signing.');
      btn.disabled = false;
      btn.textContent = 'Confirm';
    }
  }

  // Initialize page states on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    updateRequirementSectionUI(currentPaymentStatus, currentReservationStatus);
    loadDocuments(currentReservationId);

    const hash = (window.location.hash || '').replace('#', '').toLowerCase();
    if (['lease', 'payment', 'lease-signing', 'documents'].includes(hash)) {
      switchReservationTab(hash);
    } else {
      switchReservationTab('lease');
    }
  });
</script>

<!-- Complete In-House Payment Modal -->
<div id="completeInHousePaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-xs px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
    <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between">
      <h3 class="text-base font-bold text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Complete In-House Payment
      </h3>
      <button type="button" onclick="closeInHousePaymentModal()" class="p-1 rounded-lg hover:bg-emerald-700 text-emerald-100 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-6 space-y-4">
      <p class="text-xs sm:text-sm text-slate-700 leading-relaxed">
        Are you sure you want to mark this in-house payment as completed? Confirm that you have received the required downpayment of <strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong> in cash or check.
      </p>

      <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800">
        ✓ This will mark the downpayment as received &amp; verified.
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Notes (optional)</label>
        <textarea id="inHousePaymentRemarks" rows="2" placeholder="e.g. Cash downpayment received in full at the management office." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 resize-none focus:outline-none focus:border-slate-900"></textarea>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button type="button" onclick="closeInHousePaymentModal()" class="px-4 py-2 border border-slate-200 hover:bg-slate-100 rounded-xl text-xs font-semibold text-slate-700">Cancel</button>
      <button type="button" id="btnConfirmInHousePayment" onclick="submitInHousePaymentComplete()" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">Confirm &amp; Complete</button>
    </div>
  </div>
</div>

<!-- Lease Signing Action Modal -->
<div id="leaseSigningModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-100">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h3 class="text-base font-bold text-slate-900" id="signingModalTitle">Confirm Lease Signing Completion</h3>
      <button type="button" onclick="closeSigningModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <p class="text-xs text-slate-600 leading-relaxed" id="signingModalDesc">
      Are you sure you want to mark this lease signing as completed? This confirms that the lease contract was signed and finalized by all parties.
    </p>

    <input type="hidden" id="signingActionInput" value="complete">

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Signing Notes (optional)</label>
      <textarea id="signingRemarksInput" rows="3" placeholder="e.g. Contract signed in person at management office..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 resize-none focus:outline-none focus:border-slate-900"></textarea>
    </div>

    <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
      <button type="button" onclick="closeSigningModal()" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 rounded-xl text-xs font-semibold text-slate-700">Cancel</button>
      <button type="button" id="btnConfirmSigning" onclick="submitLeaseSigningAction()" class="px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold transition-all">Confirm</button>
    </div>
  </div>
</div>

</body>
</html>
