<?php
require_once __DIR__ . '/../php_files/auth.php';

$user = requireRole($conn, ['unit owner']);
$ownerId = (int)$user['user_id'];

function e($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_date_short($date) {
    if (empty($date)) return '—';
    return date('M d, Y', strtotime($date));
}

// Check optional columns in users_table
$colCheckDob = $conn->query("SHOW COLUMNS FROM users_table LIKE 'date_of_birth'");
$hasDobCol = $colCheckDob && $colCheckDob->num_rows > 0;

$colCheckPhone = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_contact'");
$hasAddPhoneCol = $colCheckPhone && $colCheckPhone->num_rows > 0;

$colCheckEmail = $conn->query("SHOW COLUMNS FROM users_table LIKE 'additional_email'");
$hasAddEmailCol = $colCheckEmail && $colCheckEmail->num_rows > 0;

// Check and add gcash_QR column if missing
$colCheckQr = $conn->query("SHOW COLUMNS FROM users_table LIKE 'gcash_QR'");
$hasQrCol = $colCheckQr && $colCheckQr->num_rows > 0;
if (!$hasQrCol) {
    @$conn->query("ALTER TABLE users_table ADD COLUMN gcash_QR VARCHAR(255) NULL DEFAULT NULL AFTER resident_status");
    $colCheckQr = $conn->query("SHOW COLUMNS FROM users_table LIKE 'gcash_QR'");
    $hasQrCol = $colCheckQr && $colCheckQr->num_rows > 0;
}

// Active tab determination
$activeTab = $_GET['tab'] ?? 'profile';

// Handle Form Submissions (Personal Info Update, Password Change, GCash QR)
$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_gcash_qr') {
        $activeTab = 'payment';
        if (!isset($_FILES['gcash_qr_image']) || $_FILES['gcash_qr_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $toast = ['type' => 'error', 'msg' => 'Please select a GCash QR image file to upload.'];
        } elseif ($_FILES['gcash_qr_image']['error'] !== UPLOAD_ERR_OK) {
            $toast = ['type' => 'error', 'msg' => 'Image upload failed. Please try again.'];
        } elseif ($_FILES['gcash_qr_image']['size'] > 5 * 1024 * 1024) {
            $toast = ['type' => 'error', 'msg' => 'QR image must be 5MB or below.'];
        } else {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $origName = $_FILES['gcash_qr_image']['name'];
            $tmpName = $_FILES['gcash_qr_image']['tmp_name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt)) {
                $toast = ['type' => 'error', 'msg' => 'Only JPG, JPEG, PNG, and WEBP image files are allowed.'];
            } else {
                $uploadDir = __DIR__ . '/../uploads/payment_qr/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }

                $newName = 'gcash_qr_' . $ownerId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $newName;
                $dbPath = 'uploads/payment_qr/' . $newName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    // Remove old QR image file if it exists
                    $oldStmt = $conn->prepare("SELECT gcash_QR FROM users_table WHERE user_id = ? LIMIT 1");
                    if ($oldStmt) {
                        $oldStmt->bind_param('i', $ownerId);
                        $oldStmt->execute();
                        $oldRes = $oldStmt->get_result();
                        $oldRow = $oldRes ? $oldRes->fetch_assoc() : null;
                        if (!empty($oldRow['gcash_QR'])) {
                            $oldFile = __DIR__ . '/../' . ltrim($oldRow['gcash_QR'], '/');
                            if (file_exists($oldFile) && is_file($oldFile)) {
                                @unlink($oldFile);
                            }
                        }
                        $oldStmt->close();
                    }

                    $stmt = $conn->prepare("UPDATE users_table SET gcash_QR = ? WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param('si', $dbPath, $ownerId);
                        if ($stmt->execute()) {
                            $toast = ['type' => 'success', 'msg' => 'Your GCash QR code has been saved successfully!'];
                        } else {
                            $toast = ['type' => 'error', 'msg' => 'Failed to save QR code in database: ' . $stmt->error];
                        }
                        $stmt->close();
                    }
                } else {
                    $toast = ['type' => 'error', 'msg' => 'Failed to save the uploaded image file.'];
                }
            }
        }
    } elseif ($action === 'delete_gcash_qr') {
        $activeTab = 'payment';
        $oldStmt = $conn->prepare("SELECT gcash_QR FROM users_table WHERE user_id = ? LIMIT 1");
        if ($oldStmt) {
            $oldStmt->bind_param('i', $ownerId);
            $oldStmt->execute();
            $oldRes = $oldStmt->get_result();
            $oldRow = $oldRes ? $oldRes->fetch_assoc() : null;
            if (!empty($oldRow['gcash_QR'])) {
                $oldFile = __DIR__ . '/../' . ltrim($oldRow['gcash_QR'], '/');
                if (file_exists($oldFile) && is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $oldStmt->close();
        }

        $stmt = $conn->prepare("UPDATE users_table SET gcash_QR = NULL WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $ownerId);
            if ($stmt->execute()) {
                $toast = ['type' => 'success', 'msg' => 'Your GCash QR code has been removed.'];
            } else {
                $toast = ['type' => 'error', 'msg' => 'Failed to remove QR code: ' . $stmt->error];
            }
            $stmt->close();
        }
    } elseif ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $addContact = !empty($_POST['additional_contact']) ? trim($_POST['additional_contact']) : null;
        $addEmail = !empty($_POST['additional_email']) ? trim($_POST['additional_email']) : null;
        $newPassword = $_POST['new_password'] ?? '';

        if ($fullName === '') {
            $toast = ['type' => 'error', 'msg' => 'Full name cannot be empty.'];
        } else {
            $updates = ["full_name = ?", "contact = ?"];
            $types = "ss";
            $params = [$fullName, $contact];

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
                $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            $types .= "i";
            $params[] = $ownerId;

            $sql = "UPDATE users_table SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $toast = ['type' => 'success', 'msg' => 'Your profile has been updated successfully!'];
                } else {
                    $toast = ['type' => 'error', 'msg' => 'Failed to update profile: ' . $stmt->error];
                }
                $stmt->close();
            }
        }
    }
}

// Fetch fresh owner data
$stmt = $conn->prepare("SELECT * FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$res = $stmt->get_result();
$owner = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$owner) {
    header('Location: overview.php');
    exit;
}

$initials = strtoupper(substr($owner['full_name'] ?? 'U', 0, 1));

// Calculate Age and Format DOB
$dobFormatted = '—';
if (!empty($owner['date_of_birth'])) {
    try {
        $dobDate = new DateTime($owner['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
        $dobFormatted = $dobDate->format('M d, Y') . " ($age yrs old)";
    } catch (Exception $e) {
        $dobFormatted = $owner['date_of_birth'];
    }
}

$additionalPhone = !empty($owner['additional_contact']) ? $owner['additional_contact'] : '—';
$additionalEmail = !empty($owner['additional_email']) ? $owner['additional_email'] : '—';

// 1. Fetch Units Owned with current active tenant
$units = [];
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
    $u_stmt->bind_param('is', $ownerId, $owner['email']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    while ($r = $u_res->fetch_assoc()) {
        $units[] = $r;
    }
    $u_stmt->close();
}

// 2. Fetch Maintenance Requests
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
    $m_stmt->bind_param('iii', $ownerId, $ownerId, $ownerId);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result();
    if ($m_res) {
        while ($m_row = $m_res->fetch_assoc()) {
            $maintenance[] = $m_row;
        }
    }
    $m_stmt->close();
}

$unitsCount = count($units);
$requestsCount = count($maintenance);
$pendingRequests = count(array_filter($maintenance, fn($item) => strtolower($item['status'] ?? '') === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - My Account</title>
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
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }

.profile-tab { position:relative; color:#64748b; transition:all 0.2s ease; }
.profile-tab:hover { color:#0f172a; }
.profile-tab.active { color:#0f172a; font-weight:700; }
.profile-tab.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:2px; background:#0f172a; border-radius:2px; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
    <a href="overview.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm tracking-tight\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest text-slate-500\'>SUITES</span></span>'">
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
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Inquiries</span>
    </a>
    <a href="ownersUnitReservations.php" data-tooltip="Lease Management" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      <span class="nav-label">Lease Management</span>
    </a>
    <a href="ownersBookingCalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
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
            <p class="text-sm font-semibold text-slate-800 leading-none"><?= htmlspecialchars($owner['full_name'] ?? 'Unit Owner') ?></p>
            <p class="text-xs text-slate-400 mt-0.5">Unit Owner</p>
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

  <!-- LOGOUT MODAL -->
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
          <h1 class="text-xl font-bold text-slate-900">My Account</h1>
          <p class="text-xs text-slate-400 mt-0.5">Manage your personal information, owned units, and maintenance records.</p>
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
              <h2 class="mt-3.5 text-lg font-bold text-slate-900 leading-tight"><?= e($owner['full_name']) ?></h2>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                  Unit Owner
                </span>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <?= e(ucfirst($owner['resident_status'] ?: 'Active')) ?>
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-2" style="font-family:'DM Mono',monospace">Joined <?= format_date_short($owner['created_at']) ?></p>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 space-y-3.5 text-sm">
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="truncate font-medium"><?= e($owner['email']) ?></span>
              </div>
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="font-medium" style="font-family:'DM Mono',monospace"><?= e($owner['contact'] ?: 'No phone provided') ?></span>
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

        <!-- RIGHT COLUMN: Detailed Tabs -->
        <div class="space-y-6">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="flex items-center gap-6 border-b border-slate-100 pb-3 overflow-x-auto">
              <button type="button" id="tabBtn-profile" onclick="setProfileTab('profile', this)" class="profile-tab <?= $activeTab === 'profile' ? 'active' : '' ?> text-sm font-semibold pb-3 whitespace-nowrap">Profile</button>
              <button type="button" id="tabBtn-units" onclick="setProfileTab('units', this)" class="profile-tab <?= $activeTab === 'units' ? 'active' : '' ?> text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Owned Units
                <span class="text-[11px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full"><?= $unitsCount ?></span>
              </button>
              <button type="button" id="tabBtn-request" onclick="setProfileTab('request', this)" class="profile-tab <?= $activeTab === 'request' ? 'active' : '' ?> text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Maintenance
                <span class="text-[11px] font-bold <?= $pendingRequests > 0 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700' ?> px-2 py-0.5 rounded-full"><?= $requestsCount ?></span>
              </button>
              <button type="button" id="tabBtn-payment" onclick="setProfileTab('payment', this)" class="profile-tab <?= $activeTab === 'payment' ? 'active' : '' ?> text-sm font-semibold pb-3 flex items-center gap-2 whitespace-nowrap">
                Payment QR
                <?php if (!empty($owner['gcash_QR'])): ?>
                  <span class="text-[11px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                <?php else: ?>
                  <span class="text-[11px] font-medium bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">Not Set</span>
                <?php endif; ?>
              </button>
            </div>

            <!-- TAB 1: Profile Details -->
            <div id="tab-profile" class="pt-6 max-w-lg <?= $activeTab === 'profile' ? '' : 'hidden' ?>">
              <h3 class="text-base font-bold text-slate-900 mb-6">Personal Information</h3>
              <dl class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-6 py-1">
                  <dt class="text-slate-400 font-medium w-44 shrink-0">Full Name:</dt>
                  <dd class="font-semibold text-slate-800 flex-1"><?= e($owner['full_name']) ?></dd>
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
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono"><?= $unitsCount ?> <?= $unitsCount === 1 ? 'Unit' : 'Units' ?></span>
              </div>
              <?php if (empty($units)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No units currently registered under your account.</p>
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
                  <?= $requestsCount ?> <?= $requestsCount === 1 ? 'Request' : 'Requests' ?>
                </span>
              </div>
              <?php if (empty($maintenance)): ?>
                <div class="p-8 text-center border border-dashed border-slate-200 rounded-2xl">
                  <p class="text-sm text-slate-500">No maintenance requests found for your units.</p>
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

            <!-- TAB 4: GCash / Payment QR Code -->
            <div id="tab-payment" class="pt-6 <?= $activeTab === 'payment' ? '' : 'hidden' ?> space-y-6">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span>GCash Payment QR Code</span>
                    <?php if (!empty($owner['gcash_QR'])): ?>
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active Static QR
                      </span>
                    <?php endif; ?>
                  </h3>
                  <p class="text-xs text-slate-400 mt-0.5">Upload your static GCash QR code for downpayment and monthly rent collection only.</p>
                </div>
              </div>

              <?php if (!empty($owner['gcash_QR'])): ?>
                <!-- CURRENT QR CODE CARD -->
                <div class="p-6 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                  <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-6 items-center">
                    <div class="flex flex-col items-center justify-center p-4 bg-white rounded-2xl border border-slate-200 shadow-sm text-center">
                      <div class="relative group cursor-pointer overflow-hidden rounded-xl bg-slate-100 border border-slate-100" onclick="openQrLightbox('../<?= e($owner['gcash_QR']) ?>')">
                        <img src="../<?= e($owner['gcash_QR']) ?>" alt="GCash QR Code" class="w-48 h-48 object-contain rounded-xl transition-transform duration-200 group-hover:scale-105">
                        <div class="absolute inset-0 bg-slate-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-xs font-semibold rounded-xl gap-1">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                          Click to Enlarge
                        </div>
                      </div>
                      <span class="mt-3 text-xs font-semibold text-slate-700 font-mono">Current GCash QR</span>
                    </div>

                    <div class="space-y-4">
                      <div class="p-4 bg-blue-50/80 border border-blue-100/90 rounded-2xl space-y-1 text-sm text-blue-950">
                        <div class="flex items-center gap-2 font-bold text-blue-900">
                          <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          How This Static QR Works
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                          Your uploaded GCash QR code remains saved in your profile and is used for downpayment or rent collection only. When prospective tenants reserve your unit, this static QR code is presented to them on their reservation form.
                        </p>
                      </div>

                      <div class="flex flex-wrap items-center gap-3 pt-1">
                        <button type="button" onclick="toggleReplaceSection()" class="btn-press flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-all shadow-sm">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                          Replace QR Code
                        </button>

                        <a href="../<?= e($owner['gcash_QR']) ?>" download="GCash_QR_<?= e($owner['full_name']) ?>" class="btn-press flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
                          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                          Download QR
                        </a>

                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove your GCash QR code?')" class="inline">
                          <input type="hidden" name="action" value="delete_gcash_qr">
                          <button type="submit" class="btn-press flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-red-600 hover:bg-red-50 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Remove
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <!-- UPLOAD FORM (Visible if no QR exists or if Replace button clicked) -->
              <div id="replaceQrSection" class="<?= !empty($owner['gcash_QR']) ? 'hidden' : '' ?> space-y-4">
                <?php if (!empty($owner['gcash_QR'])): ?>
                  <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <h4 class="text-sm font-bold text-slate-900">Upload Replacement QR Code</h4>
                    <button type="button" onclick="toggleReplaceSection()" class="text-xs text-slate-400 hover:text-slate-600 font-semibold">Cancel</button>
                  </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                  <input type="hidden" name="action" value="upload_gcash_qr">
                  
                  <div class="border-2 border-dashed border-slate-200 hover:border-slate-400 bg-slate-50/50 hover:bg-slate-50 rounded-2xl p-6 transition-all text-center cursor-pointer relative" onclick="document.getElementById('gcashQrInput').click()">
                    <input type="file" id="gcashQrInput" name="gcash_qr_image" accept=".jpg,.jpeg,.png,.webp" required class="hidden" onchange="handleQrSelect(event)">
                    
                    <div id="qrUploadPlaceholder" class="space-y-2.5 py-4">
                      <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      </div>
                      <div>
                        <p class="text-sm font-bold text-slate-800">Click to upload your GCash QR code</p>
                        <p class="text-xs text-slate-400 mt-1">Supports PNG, JPG, or WEBP (Max 5MB)</p>
                      </div>
                    </div>

                    <!-- PREVIEW CONTAINER -->
                    <div id="qrPreviewContainer" class="hidden flex-col items-center justify-center space-y-3 py-2">
                      <div class="w-48 h-48 rounded-xl border border-slate-200 bg-white p-2 shadow-sm overflow-hidden flex items-center justify-center">
                        <img id="qrPreviewImg" src="#" alt="QR Preview" class="max-w-full max-h-full object-contain rounded-lg">
                      </div>
                      <div class="text-center">
                        <p id="qrPreviewName" class="text-xs font-semibold text-slate-700 truncate max-w-xs"></p>
                        <p id="qrPreviewSize" class="text-[11px] text-slate-400 font-mono"></p>
                        <p class="text-[11px] text-blue-600 font-semibold mt-1">Click to select a different image</p>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="submit" id="saveQrBtn" class="btn-press flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 shadow-md">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                      Save GCash QR Code
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div id="editResidentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-slate-900">Edit Account Profile</h3>
      <button type="button" onclick="closeEditModal()" class="btn-press p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update_profile">
      
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Full Name</label>
        <input type="text" name="full_name" required value="<?= e($owner['full_name']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Contact Number</label>
        <input type="text" name="contact" value="<?= e($owner['contact']) ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Date of Birth</label>
          <input type="date" name="date_of_birth" value="<?= e($owner['date_of_birth'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Phone</label>
          <input type="text" name="additional_contact" value="<?= e($owner['additional_contact'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium font-mono" placeholder="Optional">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Additional Email</label>
        <input type="email" name="additional_email" value="<?= e($owner['additional_email'] ?? '') ?>" class="zep-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium" placeholder="Optional">
      </div>

      <div class="pt-3 border-t border-slate-100">
        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Change Password</label>
        <div class="relative">
          <input type="password" id="editOwnerPassword" name="new_password" placeholder="Leave blank to keep current password" class="zep-input w-full pl-4 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium">
          <button type="button" onclick="togglePasswordVisibility('editOwnerPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors p-1" title="Toggle password visibility">
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

<!-- QR CODE LIGHTBOX MODAL -->
<div id="qrLightboxModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100] hidden items-center justify-center p-4" onclick="closeQrLightbox()">
  <div class="relative bg-white rounded-3xl p-5 max-w-md w-full shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-150" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <h3 class="text-base font-bold text-slate-900">GCash QR Code</h3>
      <button type="button" onclick="closeQrLightbox()" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="flex items-center justify-center p-3 bg-slate-50 rounded-2xl">
      <img id="lightboxQrImg" src="#" alt="GCash QR Code" class="max-h-[60vh] max-w-full object-contain rounded-xl shadow-sm">
    </div>
    <div class="pt-4 flex justify-end">
      <button type="button" onclick="closeQrLightbox()" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">Close</button>
    </div>
  </div>
</div>

<script>
  function toggleCollapse() {
    const s = document.getElementById('sidebar');
    const w = document.getElementById('mainWrapper');
    s.classList.toggle('collapsed');
    w.classList.toggle('sidebar-collapsed');
    localStorage.setItem('uop_sidebar_collapsed', s.classList.contains('collapsed'));
  }
  (function() {
    if (localStorage.getItem('uop_sidebar_collapsed') === 'true') {
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
    c.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
  }
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#profileBtn') && !e.target.closest('#profileDropdown')) {
      const d = document.getElementById('profileDropdown');
      if (d && !d.classList.contains('hidden')) {
        d.classList.add('hidden');
        document.getElementById('profileChevron').style.transform = 'rotate(0deg)';
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

  function setProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
    if (btn) btn.classList.add('active');
    ['profile','units','request','payment'].forEach(t => {
      const el = document.getElementById('tab-' + t);
      if (el) el.classList.toggle('hidden', t !== tab);
    });
  }

  function toggleReplaceSection() {
    const s = document.getElementById('replaceQrSection');
    if (s) s.classList.toggle('hidden');
  }

  function handleQrSelect(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('qrPreviewImg').src = e.target.result;
        document.getElementById('qrPreviewName').textContent = file.name;
        document.getElementById('qrPreviewSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        document.getElementById('qrUploadPlaceholder').classList.add('hidden');
        document.getElementById('qrPreviewContainer').classList.remove('hidden');
        document.getElementById('qrPreviewContainer').classList.add('flex');
      };
      reader.readAsDataURL(file);
    }
  }

  function openQrLightbox(imgSrc) {
    document.getElementById('lightboxQrImg').src = imgSrc;
    const m = document.getElementById('qrLightboxModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function closeQrLightbox() {
    const m = document.getElementById('qrLightboxModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
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
