<?php
require_once __DIR__ . '/../php_files/admin_auth.php';
require_once __DIR__ . '/../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_with_message($type, $message) {
    $_SESSION[$type] = $message;
    header('Location: residents.php');
    exit();
}

function bind_params_if_needed($stmt, $types, &$params) {
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
}

function email_exists($conn, $email, $exclude_user_id = 0) {
    $sql = "SELECT user_id FROM users_table WHERE email = ? AND user_id <> ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param('si', $email, $exclude_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}

function format_role($role) {
    if ($role === 'unit owner') {
        return 'Unit Owner';
    }
    return ucfirst((string)$role);
}

function status_badge($status) {
    if ($status === 'Active') {
        return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>';
    }
    return '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>';
}

function format_date_short($date) {
    if (empty($date)) {
        return '—';
    }
    return date('M d, Y', strtotime($date));
}

function render_resident_row($resident) {
    ob_start();
    $userId = (int)$resident['user_id'];
    ?>
    <tr class="emp-row cursor-pointer" data-status="<?= e(strtolower($resident['resident_status'])) ?>" onclick="window.location.href='viewResident.php?id=<?= $userId ?>'">
        <td class="px-4 py-3.5" onclick="event.stopPropagation()"><input type="checkbox" class="row-check rounded border-slate-300 w-4 h-4 cursor-pointer"></td>
        <td class="px-4 py-3.5 font-semibold emp-name text-slate-800 whitespace-nowrap"><?= e($resident['full_name']) ?></td>
        <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap"><?= e($resident['email']) ?></td>
        <td class="px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= e($resident['contact'] ?: '—') ?></td>
        <td class="px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap"><?= e(format_role($resident['user_role'])) ?></td>
        <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap" style="font-family:'DM Mono',monospace"><?= e(format_date_short($resident['created_at'])) ?></td>
        <td class="px-4 py-3.5"><?= status_badge($resident['resident_status']) ?></td>
        <td class="px-4 py-3.5 text-right whitespace-nowrap">
            <a
                href="viewResident.php?id=<?= $userId ?>"
                class="view-btn btn-press inline-block text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all"
                onclick="event.stopPropagation()">View</a>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

$allowed_roles = ['unit owner', 'tenant'];
$allowed_statuses = ['Active', 'Inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_resident') {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $user_role = trim($_POST['user_role'] ?? 'tenant');
            $resident_status = trim($_POST['resident_status'] ?? 'Active');

            if ($full_name === '' || $email === '' || $password === '') {
                throw new Exception('Name, email, and password are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            if (!in_array($user_role, $allowed_roles, true)) {
                throw new Exception('Invalid resident role.');
            }
            if (!in_array($resident_status, $allowed_statuses, true)) {
                throw new Exception('Invalid resident status.');
            }
            if (email_exists($conn, $email)) {
                throw new Exception('That email address is already used by another account.');
            }

            // Your current users_table.sql stores plain text passwords, so this follows the current login pattern.
            // For better security later, update your login to use password_verify() and save password_hash() here.
            $sql = "INSERT INTO users_table (full_name, email, password, contact, user_role, resident_status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ssssss', $full_name, $email, $password, $contact, $user_role, $resident_status);
            $stmt->execute();

            redirect_with_message('success_message', 'Resident account added successfully.');
        }

        if ($action === 'update_resident') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');
            $user_role = trim($_POST['user_role'] ?? 'tenant');
            $resident_status = trim($_POST['resident_status'] ?? 'Active');

            if ($user_id <= 0) {
                throw new Exception('Invalid resident account.');
            }
            if ($full_name === '' || $email === '') {
                throw new Exception('Name and email are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            if (!in_array($user_role, $allowed_roles, true)) {
                throw new Exception('Invalid resident role.');
            }
            if (!in_array($resident_status, $allowed_statuses, true)) {
                throw new Exception('Invalid resident status.');
            }
            if (email_exists($conn, $email, $user_id)) {
                throw new Exception('That email address is already used by another account.');
            }

            if ($new_password !== '') {
                $sql = "UPDATE users_table SET full_name = ?, email = ?, contact = ?, user_role = ?, resident_status = ?, password = ? WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param('ssssssi', $full_name, $email, $contact, $user_role, $resident_status, $new_password, $user_id);
            } else {
                $sql = "UPDATE users_table SET full_name = ?, email = ?, contact = ?, user_role = ?, resident_status = ? WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param('sssssi', $full_name, $email, $contact, $user_role, $resident_status, $user_id);
            }
            $stmt->execute();

            redirect_with_message('success_message', 'Resident account updated successfully.');
        }

        if ($action === 'toggle_status') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $resident_status = trim($_POST['resident_status'] ?? 'Inactive');

            if ($user_id <= 0 || !in_array($resident_status, $allowed_statuses, true)) {
                throw new Exception('Invalid status update.');
            }

            $sql = "UPDATE users_table SET resident_status = ? WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('si', $resident_status, $user_id);
            $stmt->execute();

            redirect_with_message('success_message', 'Resident status updated successfully.');
        }

        throw new Exception('Invalid action.');
    } catch (Exception $ex) {
        redirect_with_message('error_message', $ex->getMessage());
    }
}

$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$where = ["user_role IN ('unit owner', 'tenant')"];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR contact LIKE ? OR CAST(user_id AS CHAR) LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'ssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (in_array($role_filter, $allowed_roles, true)) {
    $where[] = "user_role = ?";
    $types .= 's';
    $params[] = $role_filter;
}

if (in_array($status_filter, $allowed_statuses, true)) {
    $where[] = "resident_status = ?";
    $types .= 's';
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where);

// Stats always reflect the WHOLE table, independent of search/filter state.
$stats_sql = "
    SELECT
      COUNT(*) AS total_residents,
      COALESCE(SUM(resident_status = 'Active'), 0) AS active_residents,
      COALESCE(SUM(resident_status = 'Inactive'), 0) AS inactive_residents,
      COALESCE(SUM(user_role = 'unit owner'), 0) AS unit_owners,
      COALESCE(SUM(user_role = 'tenant'), 0) AS tenants
    FROM users_table
    WHERE user_role IN ('unit owner', 'tenant')
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_residents' => 0,
    'active_residents' => 0,
    'inactive_residents' => 0,
    'unit_owners' => 0,
    'tenants' => 0
];

$residents = [];
$list_sql = "
    SELECT user_id, full_name, email, contact, user_role, created_at, resident_status
    FROM users_table
    WHERE $where_sql
    ORDER BY created_at DESC, user_id DESC
";
$list_stmt = $conn->prepare($list_sql);
if (!$list_stmt) {
    $_SESSION['error_message'] = 'Database error: ' . $conn->error;
} else {
    bind_params_if_needed($list_stmt, $types, $params);
    $list_stmt->execute();
    $list_result = $list_stmt->get_result();
    while ($row = $list_result->fetch_assoc()) {
        $residents[] = $row;
    }
}

// AJAX endpoint: returns just the table rows as HTML, used for live search/filter.
// Reuses everything above (search/role/status parsing + the $residents query) —
// only the response and exit differ from a normal page load.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: text/html; charset=UTF-8');
    if (empty($residents)) {
        echo '<tr><td colspan="8" class="px-4 py-10 text-center text-slate-500 text-sm">No residents found.</td></tr>';
    } else {
        foreach ($residents as $resident) {
            echo render_resident_row($resident);
        }
    }
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites Admin - Residents</title>
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
.stat-card { background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); transition:transform 0.22s ease,box-shadow 0.22s ease,border-color 0.22s ease; cursor:pointer; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(0,0,0,0.10); border-color:#0f172a; }
.emp-row { transition:background 0.15s ease; }
.emp-row:hover { background:#f1f5f9; }
.emp-row .emp-name { transition:color 0.15s ease; }
.emp-row:hover .emp-name { color:#1d4ed8; }
.view-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; }
.emp-row:hover .view-btn { opacity:1; transform:translateX(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
.search-spinner { display:none; }
.search-spinner.show { display:inline-block; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

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
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
     </a>
    <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg  class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-range-icon lucide-calendar-range"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
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
    <a href="../adminPages/residents.php" data-tooltip="Residents" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
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
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" id="headerSearchInput" placeholder="Search residents..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <button class="relative p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">A</div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none" id="userName">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-2xl py-2 z-50 hidden" id="profileDropdown">
          <div class="border-t border-slate-100 my-1 mx-3"></div>
          <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Sign out</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Simple Modal -->
  <div id="logoutModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
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

      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Residents</h1>
          <p class="text-sm text-slate-500 mt-1">Manage unit owner and tenant accounts from users_table.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-2 flex-wrap" id="filterBar">
            <div class="relative">
              <input type="text" id="searchInput" value="<?= e($search) ?>" placeholder="Search name, email, contact..." class="zep-input px-4 py-2 text-sm border border-slate-200 rounded-full bg-white min-w-56">
              <svg id="searchSpinner" class="search-spinner absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
            </div>
            <select id="roleFilter" class="zep-select px-3 py-2 text-sm border border-slate-200 rounded-full bg-white">
              <option value="">All roles</option>
              <option value="unit owner" <?= $role_filter === 'unit owner' ? 'selected' : '' ?>>Unit Owner</option>
              <option value="tenant" <?= $role_filter === 'tenant' ? 'selected' : '' ?>>Tenant</option>
            </select>
            <select id="statusFilter" class="zep-select px-3 py-2 text-sm border border-slate-200 rounded-full bg-white">
              <option value="">All statuses</option>
              <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
              <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <a href="residents.php" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-500 rounded-full hover:bg-slate-50 transition-all active:scale-95">Reset</a>
          </div>
          <button type="button" onclick="openAddResidentModal()" class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-semibold px-4 py-2 rounded-full transition-all">
            + Add Resident
          </button>
        </div>
      </div>

      <?php if ($success_message): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
          <?= e($success_message) ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
          <?= e($error_message) ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Residents</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['total_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</p>
          <p class="text-3xl font-bold text-emerald-700 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['active_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inactive</p>
          <p class="text-3xl font-bold text-slate-600 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['inactive_residents'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unit Owners</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['unit_owners'] ?></p>
        </div>
        <div class="stat-card border border-slate-100 rounded-2xl p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tenants</p>
          <p class="text-3xl font-bold text-slate-900 mt-2" style="font-family:'DM Mono',monospace"><?= (int)$stats['tenants'] ?></p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="empTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="px-4 py-3 w-10"><input type="checkbox" class="rounded border-slate-300 w-4 h-4 cursor-pointer" id="selectAll" onchange="toggleAll(this)"></th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide cursor-pointer hover:text-slate-700 select-none" onclick="sortTable(1)">Name ↕</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Email</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Contact</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap cursor-pointer hover:text-slate-700 select-none" onclick="sortTable(4)">Role ↕</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap cursor-pointer hover:text-slate-700 select-none" onclick="sortTable(5)">Date Created ↕</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 w-24"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="empBody">
              <?php if (empty($residents)): ?>
                <tr>
                  <td colspan="8" class="px-4 py-10 text-center text-slate-500 text-sm">No residents found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($residents as $resident): ?>
                  <?= render_resident_row($resident) ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 flex-wrap gap-3">
          <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700" id="resultCount"><?= count($residents) ?></span>
            of <span class="font-semibold text-slate-700"><?= (int)$stats['total_residents'] ?></span> residents
          </p>
          <p class="text-xs text-slate-400">Use View to edit account details or change Active/Inactive status.</p>
        </div>
      </div>

    </div>
  </main>
</div>

<div id="addResidentModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 px-4">
  <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Add Resident</h2>
        <p class="text-xs text-slate-500 mt-1">Creates a new unit owner or tenant account.</p>
      </div>
      <button type="button" onclick="closeAddResidentModal()" class="btn-press w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500">✕</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="add_resident">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Full Name</label>
          <input type="text" name="full_name" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Email</label>
          <input type="email" name="email" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Contact</label>
          <input type="text" name="contact" class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Temporary Password</label>
          <input type="text" name="password" required class="zep-input w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Role</label>
          <select name="user_role" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
            <option value="tenant">Tenant</option>
            <option value="unit owner">Unit Owner</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Status</label>
          <select name="resident_status" class="zep-select w-full px-4 py-3 border border-slate-200 rounded-xl text-sm">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeAddResidentModal()" class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 rounded-full text-slate-600 hover:bg-slate-50">Cancel</button>
        <button type="submit" class="btn-press px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-full hover:bg-slate-700">Save Resident</button>
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
  function toggleNotice() {
    const panel = document.getElementById('noticePanel');
    const chevron = document.getElementById('noticeChevron');
    if (panel) panel.classList.toggle('open');
    if (chevron) chevron.classList.toggle('rotated');
  }
  function toggleProfile() {
    const dd = document.getElementById('profileDropdown'), ch = document.getElementById('profileChevron');
    dd.classList.toggle('hidden');
    ch.style.transform = dd.classList.contains('hidden') ? '' : 'rotate(180deg)';
  }
  document.addEventListener('click', e => {
    const w = document.getElementById('profileWrapper');
    if (w && !w.contains(e.target)) { document.getElementById('profileDropdown').classList.add('hidden'); document.getElementById('profileChevron').style.transform = ''; }
  });

  document.addEventListener('DOMContentLoaded', function() {
    const userName = '<?php echo htmlspecialchars($_SESSION["full_name"] ?? "Admin"); ?>';
    const initials = '<?php echo $_SESSION["initial"] ?? "A"; ?>';
    document.getElementById('userName').textContent = userName;
    document.getElementById('userInitials').textContent = initials;
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
  function toggleAll(cb) { document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked); }
  function setView(v) {
    const t = document.getElementById('viewTable'), g = document.getElementById('viewGrid');
    if (!t || !g) return;
    if (v === 'table') { t.classList.add('bg-white','text-slate-700','shadow-sm'); t.classList.remove('text-slate-500'); g.classList.remove('bg-white','shadow-sm'); g.classList.add('text-slate-500'); }
    else { g.classList.add('bg-white','text-slate-700','shadow-sm'); g.classList.remove('text-slate-500'); t.classList.remove('bg-white','shadow-sm'); t.classList.add('text-slate-500'); }
  }
  let sortDir = {};
  function sortTable(col) {
    const tbody = document.getElementById('empBody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > col);
    sortDir[col] = !sortDir[col];
    rows.sort((a,b) => {
      const va = a.cells[col]?.textContent.trim() || '';
      const vb = b.cells[col]?.textContent.trim() || '';
      const na = parseFloat(va), nb = parseFloat(vb);
      if (!isNaN(na) && !isNaN(nb)) return sortDir[col] ? na-nb : nb-na;
      return sortDir[col] ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));
  }
  function openAddResidentModal() {
    document.getElementById('addResidentModal').classList.remove('hidden');
    document.getElementById('addResidentModal').classList.add('flex');
  }
  function closeAddResidentModal() {
    document.getElementById('addResidentModal').classList.add('hidden');
    document.getElementById('addResidentModal').classList.remove('flex');
  }

  // ---- Live search / filter (AJAX, no page reload) ----
  const searchInput = document.getElementById('searchInput');
  const headerSearchInput = document.getElementById('headerSearchInput');
  const roleFilter = document.getElementById('roleFilter');
  const statusFilter = document.getElementById('statusFilter');
  const empBody = document.getElementById('empBody');
  const resultCount = document.getElementById('resultCount');
  const searchSpinner = document.getElementById('searchSpinner');

  let searchTimer = null;
  let activeRequest = null;

  function scheduleSearch(delay) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, delay);
  }

  function runSearch() {
    const search = searchInput.value.trim();
    const role = roleFilter.value;
    const status = statusFilter.value;

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (role) params.set('role', role);
    if (status) params.set('status', status);
    params.set('ajax', '1');

    if (activeRequest) activeRequest.abort();
    const controller = new AbortController();
    activeRequest = controller;

    searchSpinner.classList.add('show');

    fetch('residents.php?' + params.toString(), { signal: controller.signal })
      .then(res => res.text())
      .then(html => {
        empBody.innerHTML = html;
        const rowCount = empBody.querySelectorAll('tr[data-status]').length;
        resultCount.textContent = rowCount;

        // Keep the URL (and back/refresh behavior) in sync without reloading.
        const displayParams = new URLSearchParams();
        if (search) displayParams.set('search', search);
        if (role) displayParams.set('role', role);
        if (status) displayParams.set('status', status);
        const qs = displayParams.toString();
        history.replaceState(null, '', 'residents.php' + (qs ? '?' + qs : ''));
      })
      .catch(err => {
        if (err.name !== 'AbortError') console.error('Search failed:', err);
      })
      .finally(() => {
        searchSpinner.classList.remove('show');
      });
  }

  searchInput.addEventListener('input', () => scheduleSearch(300));
  roleFilter.addEventListener('change', () => scheduleSearch(0));
  statusFilter.addEventListener('change', () => scheduleSearch(0));

  // Header search bar mirrors the main search field and drives the same live search.
  if (headerSearchInput) {
    headerSearchInput.addEventListener('input', () => {
      searchInput.value = headerSearchInput.value;
      scheduleSearch(300);
    });
  }
</script>
</body>
</html>