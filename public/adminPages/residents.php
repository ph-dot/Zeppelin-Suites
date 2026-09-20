<?php
require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

$userData = requireRole($conn, ['admin']);

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

<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>


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