<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

$userData = requireRole($conn, ['admin']);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_role($role) {
    if (strtolower((string)$role) === 'unit owner') {
        return 'Unit Owner';
    }
    return ucfirst((string)$role);
}

function format_date_short($date) {
    if (empty($date)) return '—';
    return date('M d, Y', strtotime($date));
}

function status_badge($status) {
    if (strtolower((string)$status) === 'active') {
        return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>';
    }
    return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>';
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);

if ($user_id <= 0) {
    header('Location: residents.php');
    exit;
}

// Check if optional columns exist in users_table (date_of_birth, additional_contact, additional_email)
$colCheckDob = $conn->query("SHOW COLUMNS FROM users_table LIKE 'date_of_birth'");
$hasDobCol = $colCheckDob && $colCheckDob->num_rows > 0;

$colCheckPhone = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_contact'");
$hasAddPhoneCol = $colCheckPhone && $colCheckPhone->num_rows > 0;

$colCheckEmail = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_email'");
$hasAddEmailCol = $colCheckEmail && $colCheckEmail->num_rows > 0;

// Handle updates directly on this view page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $new_status = ($_POST['resident_status'] ?? '') === 'Active' ? 'Active' : 'Inactive';
        $stmt = $conn->prepare("UPDATE users_table SET resident_status = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $new_status, $user_id);
            $stmt->execute();
            $_SESSION['success_message'] = "Resident status updated to {$new_status}.";
        }
        header("Location: viewResident.php?id=" . $user_id);
        exit;
    }
    
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $user_role = trim($_POST['user_role'] ?? 'tenant');
        $resident_status = trim($_POST['resident_status'] ?? 'Active');
        $new_password = trim($_POST['new_password'] ?? '');
        $dob_val = trim($_POST['date_of_birth'] ?? '');
        $add_phone = trim($_POST['additional_contact'] ?? '');
        $add_email = trim($_POST['additional_email'] ?? '');

        if ($full_name !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check for duplicate email on other users
            $dup_stmt = $conn->prepare("SELECT user_id FROM users_table WHERE email = ? AND user_id <> ? LIMIT 1");
            $dup_stmt->bind_param('si', $email, $user_id);
            $dup_stmt->execute();
            $dup_res = $dup_stmt->get_result();
            if ($dup_res && $dup_res->num_rows > 0) {
                $_SESSION['error_message'] = "Email address is already in use by another account.";
            } else {
                $updateFields = ["full_name = ?", "email = ?", "user_role = ?", "resident_status = ?"];
                $types = "ssss";
                $params = [$full_name, $email, $user_role, $resident_status];

                if ($new_password !== '') {
                    $updateFields[] = "password = ?";
                    $types .= "s";
                    $params[] = $new_password;
                }
                if ($hasDobCol) {
                    $updateFields[] = "date_of_birth = ?";
                    $types .= "s";
                    $params[] = !empty($dob_val) ? $dob_val : null;
                }
                if ($hasAddPhoneCol) {
                    $updateFields[] = "additional_contact = ?";
                    $types .= "s";
                    $params[] = !empty($add_phone) ? $add_phone : null;
                }
                if ($hasAddEmailCol) {
                    $updateFields[] = "additional_email = ?";
                    $types .= "s";
                    $params[] = !empty($add_email) ? $add_email : null;
                }

                $types .= "i";
                $params[] = $user_id;

                $sql = "UPDATE users_table SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Resident profile updated successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to update profile: " . $conn->error;
                    }
                }
            }
        } else {
            $_SESSION['error_message'] = "Valid name and email are required.";
        }
        header("Location: viewResident.php?id=" . $user_id);
        exit;
    }
}

// Fetch Resident Record with optional extra columns
$extraCols = [];
if ($hasDobCol) $extraCols[] = 'date_of_birth';
if ($hasAddPhoneCol) $extraCols[] = 'additional_contact';
if ($hasAddEmailCol) $extraCols[] = 'additional_email';

$selectFields = "user_id, full_name, email, contact, user_role, resident_status, created_at" . (!empty($extraCols) ? ", " . implode(", ", $extraCols) : "");
$stmt = $conn->prepare("SELECT $selectFields FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$resident = $res ? $res->fetch_assoc() : null;

if (!$resident) {
    $_SESSION['error_message'] = 'Resident not found.';
    header('Location: residents.php');
    exit;
}

$initials = strtoupper(substr(trim($resident['full_name'] ?: 'U'), 0, 1));
$isOwner = strtolower($resident['user_role']) === 'unit owner';

// Parse First Name and Last Name
$nameParts = explode(' ', trim($resident['full_name'] ?? ''));
if (count($nameParts) > 1) {
    $lastName = array_pop($nameParts);
    $firstName = implode(' ', $nameParts);
} else {
    $firstName = $resident['full_name'] ?? '—';
    $lastName = '—';
}

// Date of birth format
$dobFormatted = '—';
$dobRaw = $resident['date_of_birth'] ?? '';
if (!empty($dobRaw) && $dobRaw !== '0000-00-00') {
    $dobTime = strtotime($dobRaw);
    if ($dobTime) {
        $dobDateObj = new DateTime($dobRaw);
        $age = $dobDateObj->diff(new DateTime())->y;
        $dobFormatted = date('M d, Y', $dobTime) . " | {$age} y.o";
    }
}

// Additional Phone and Email
$additionalPhone = !empty($resident['additional_contact']) ? $resident['additional_contact'] : '—';
$additionalEmail = !empty($resident['additional_email']) ? $resident['additional_email'] : '—';

// 1. Fetch Units from units_table with their current tenant
$units = [];
if ($isOwner) {
    $u_sql = "
        SELECT 
            u.unit_id, 
            u.unit_number, 
            u.unit_type, 
            u.floor_number, 
            u.unit_current_status, 
            u.lease_rate, 
            u.created_at,
            (
                SELECT r.client_name 
                FROM reservation_table r 
                WHERE r.unit_id = u.unit_id 
                  AND (r.officially_booked_at IS NOT NULL OR r.reservation_status IN ('Approved', 'Completed', 'Confirmed', 'Active', 'reserved', 'moved in'))
                ORDER BY r.created_at DESC 
                LIMIT 1
            ) AS current_tenant_name
        FROM units_table u 
        WHERE u.unit_owner_id = ? 
           OR u.unit_owner_id IN (SELECT user_id FROM users_table WHERE email = ?)
        ORDER BY u.unit_number ASC
    ";
    $u_stmt = $conn->prepare($u_sql);
    if ($u_stmt) {
        $u_stmt->bind_param('is', $user_id, $resident['email']);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        while ($r = $u_res->fetch_assoc()) {
            $units[] = $r;
        }
    }
} else {
    // If tenant, fetch the units they are renting/staying in with their name as current tenant
    $u_sql = "
        SELECT DISTINCT
            u.unit_id, 
            u.unit_number, 
            u.unit_type, 
            u.floor_number, 
            u.unit_current_status, 
            u.lease_rate,
            COALESCE(
                (
                    SELECT r2.client_name 
                    FROM reservation_table r2 
                    WHERE r2.unit_id = u.unit_id 
                      AND (r2.officially_booked_at IS NOT NULL OR r2.reservation_status IN ('Approved', 'Completed', 'Confirmed', 'Active', 'reserved', 'moved in'))
                    ORDER BY r2.created_at DESC 
                    LIMIT 1
                ),
                r.client_name
            ) AS current_tenant_name
        FROM reservation_table r
        JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.client_email = ? OR r.client_name = ?
        ORDER BY u.unit_number ASC
    ";
    $u_stmt = $conn->prepare($u_sql);
    if ($u_stmt) {
        $u_stmt->bind_param('ss', $resident['email'], $resident['full_name']);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        while ($r = $u_res->fetch_assoc()) {
            $units[] = $r;
        }
    }
}

// 2. Fetch Reservations / Stays / Leases
$reservations = [];
$r_sql = "
    SELECT r.*, u.unit_number, u.unit_type, u.floor_number 
    FROM reservation_table r 
    LEFT JOIN units_table u ON r.unit_id = u.unit_id 
    WHERE r.client_email = ? OR r.client_name = ? OR u.unit_owner_id = ? 
    ORDER BY r.created_at DESC LIMIT 50
";
$r_stmt = $conn->prepare($r_sql);
if ($r_stmt) {
    $r_stmt->bind_param('ssi', $resident['email'], $resident['full_name'], $user_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($row = $r_res->fetch_assoc()) {
        $reservations[] = $row;
    }
}

// 3. Fetch Maintenance Requests
$maintenance = [];
$m_sql = "
    SELECT 
        m.maintenance_id,
        m.unit_id,
        m.unit_owner_id,
        m.submitted_by_user_id,
        COALESCE(m.subject, m.category, 'Maintenance Request') AS issue_title,
        m.category,
        m.description,
        m.priority,
        m.status,
        m.submitted_at,
        u.unit_number, 
        u.unit_type 
    FROM maintenance_requests m 
    LEFT JOIN units_table u ON m.unit_id = u.unit_id 
    WHERE m.submitted_by_user_id = ? 
       OR m.unit_owner_id = ? 
       OR u.unit_owner_id = ? 
    ORDER BY m.submitted_at DESC LIMIT 50
";
$m_stmt = $conn->prepare($m_sql);
if ($m_stmt) {
    $m_stmt->bind_param('iii', $user_id, $user_id, $user_id);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result();
    if ($m_res) {
        while ($m_row = $m_res->fetch_assoc()) {
            $maintenance[] = $m_row;
        }
    }
}

// Compute dynamic counts
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

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-18.25">
    <a href="homeAdmin.php" class="sidebar-logo shrink-0 flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-slate-900 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/></svg>
      </div>
      <span class="font-bold text-slate-900 text-sm leading-tight">Zeppelin<br>Suites</span>
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
      <svg class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
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
    <a href="residents.php" data-tooltip="Residents" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
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
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" placeholder="Search..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">
            <?= htmlspecialchars($_SESSION['initial'] ?? 'A') ?>
          </div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></p>
            <p class="text-xs text-slate-400 mt-0.5">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <!-- Dropdown -->
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
          <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account Settings</a>
          <div class="border-t border-slate-100 my-1"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Logout Confirmation Modal -->
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
          <a href="residents.php" class="flex items-center gap-1.5 text-slate-400 hover:text-slate-600 font-medium transition-colors">
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
              <form method="POST" onsubmit="return confirm('Change status for this resident?');">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="resident_status" value="<?= $resident['resident_status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                <button type="submit" class="btn-press w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl transition-all active:scale-95 <?= $resident['resident_status'] === 'Active' ? 'text-red-600 bg-red-50 hover:bg-red-100 border border-red-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' ?>">
                  <?= $resident['resident_status'] === 'Active' ? 'Deactivate Account' : 'Activate Account' ?>
                </button>
              </form>
            </div>
          </div>


        </div>

        <!-- RIGHT COLUMN: Detailed Tabs -->
        <div class="space-y-6">

          <!-- Tabs card -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="flex items-center gap-6 border-b border-slate-100 pb-3 overflow-x-auto">
              <button type="button" onclick="setProfileTab('profile', this)" class="profile-tab active text-sm font-semibold pb-3 whitespace-nowrap">Profile</button>
              <button type="button" onclick="setProfileTab('units', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Owned Units
                <span class="text-[11px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full"><?= count($units) ?></span>
              </button>
              <button type="button" onclick="setProfileTab('request', this)" class="profile-tab text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Maintenance
                <span class="text-[11px] font-bold <?= $pendingRequests > 0 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700' ?> px-2 py-0.5 rounded-full"><?= $requestsCount ?></span>
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

            <!-- TAB 2: Owned Units -->
            <div id="tab-units" class="pt-6 hidden">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Owned Units</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono"><?= count($units) ?> <?= count($units) === 1 ? 'Unit' : 'Units' ?></span>
              </div>
              <?php if (empty($units)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No units currently assigned or associated with this resident.</p>
                </div>
              <?php else: ?>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-slate-50/60 border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wide text-left">
                        <th class="px-4 py-3">Unit Number</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Floor</th>
                        <th class="px-4 py-3">Current Tenant</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <?php foreach ($units as $u): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                          <td class="px-4 py-3.5 font-semibold text-slate-900" style="font-family:'DM Mono',monospace">Unit <?= e($u['unit_number']) ?></td>
                          <td class="px-4 py-3.5 text-slate-600 font-medium"><?= e($u['unit_type'] ?: 'Standard') ?></td>
                          <td class="px-4 py-3.5 text-slate-500" style="font-family:'DM Mono',monospace"><?= e($u['floor_number'] ?: '—') ?></td>
                          <td class="px-4 py-3.5 text-slate-800">
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
                        <th class="px-4 py-3">Unit Number</th>
                        <th class="px-4 py-3">Issue</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
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
                        <tr class="hover:bg-slate-50/50 transition-colors">
                          <td class="px-4 py-3.5 font-semibold text-slate-900" style="font-family:'DM Mono',monospace"><?= e($m['unit_number'] ? 'Unit ' . $m['unit_number'] : 'General') ?></td>
                          <td class="px-4 py-3.5 font-medium text-slate-800"><?= e($m['issue_title'] ?? 'Maintenance Request') ?></td>
                          <td class="px-4 py-3.5"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mPriorityClass ?>"><?= e(ucfirst($mPriority)) ?></span></td>
                          <td class="px-4 py-3.5"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $mStatusClass ?>"><?= e(ucfirst($mStatus)) ?></span></td>
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
    <form method="POST" class="p-6 space-y-4">
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

<script>
  let sidebarCollapsed = false;
  function toggleCollapse() {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
    document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
  function openMobileSidebar() { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); }
  function closeMobileSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }

  function toggleProfile() {
    const dd = document.getElementById('profileDropdown'), ch = document.getElementById('profileChevron');
    dd.classList.toggle('hidden');
    ch.style.transform = dd.classList.contains('hidden') ? '' : 'rotate(180deg)';
  }
  document.addEventListener('click', e => {
    const w = document.getElementById('profileWrapper');
    if (w && !w.contains(e.target)) { document.getElementById('profileDropdown').classList.add('hidden'); document.getElementById('profileChevron').style.transform = ''; }
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
