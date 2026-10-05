<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = requireRole($conn, ['tenant']);
$tenantId = (int)$user['user_id'];

if (!function_exists('clean')) {
    function clean($val) {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_date_short')) {
    function format_date_short($date) {
        if (empty($date) || $date === '0000-00-00') return '—';
        $ts = strtotime((string)$date);
        return $ts ? date('M d, Y', $ts) : '—';
    }
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
            $params[] = $tenantId;

            $sql = "UPDATE users_table SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $toast = ['type' => 'success', 'msg' => 'Your profile has been updated successfully!'];
                    $_SESSION['full_name'] = $fullName;
                } else {
                    $toast = ['type' => 'error', 'msg' => 'Failed to update profile: ' . $stmt->error];
                }
                $stmt->close();
            }
        }
    }
}

// Fetch fresh Tenant profile data
$stmt = $conn->prepare("SELECT * FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $tenantId);
$stmt->execute();
$res = $stmt->get_result();
$tenant = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$tenant) {
    header('Location: homeTenant.php');
    exit;
}

$initials = strtoupper(substr($tenant['full_name'] ?? 'T', 0, 1));

// Calculate Age and Format DOB
$dobFormatted = '—';
if (!empty($tenant['date_of_birth']) && $tenant['date_of_birth'] !== '0000-00-00') {
    try {
        $dobDate = new DateTime($tenant['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
        $dobFormatted = $dobDate->format('M d, Y') . " ($age yrs old)";
    } catch (Exception $e) {
        $dobFormatted = $tenant['date_of_birth'];
    }
}

$additionalPhone = !empty($tenant['additional_contact']) ? $tenant['additional_contact'] : '—';
$additionalEmail = !empty($tenant['additional_email']) ? $tenant['additional_email'] : '—';

// Fetch Leases for this Tenant (from reservation_table & units_table)
$leases = [];
$l_sql = "
    SELECT 
        r.reservation_id,
        r.unit_id,
        r.client_name,
        r.client_email,
        r.client_contact,
        r.move_in_date,
        r.move_out_date,
        r.price_basis,
        r.required_amount,
        r.declared_amount,
        r.payment_status,
        r.reservation_status,
        r.officially_booked_at,
        r.created_at,
        u.unit_number,
        u.unit_type,
        u.floor_number,
        u.unit_current_status,
        u.lease_rate,
        owner.user_id AS owner_id,
        owner.full_name AS owner_name,
        owner.email AS owner_email,
        owner.contact AS owner_contact
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    WHERE r.client_email = ? OR r.client_name = ?
    ORDER BY r.created_at DESC
";
$l_stmt = $conn->prepare($l_sql);
if ($l_stmt) {
    $l_stmt->bind_param('ss', $tenant['email'], $tenant['full_name']);
    $l_stmt->execute();
    $l_res = $l_stmt->get_result();
    while ($row = $l_res->fetch_assoc()) {
        $leases[] = $row;
    }
    $l_stmt->close();
}

// Fetch Maintenance Requests for this Tenant
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
    ORDER BY m.submitted_at DESC LIMIT 50
";
$m_stmt = $conn->prepare($m_sql);
if ($m_stmt) {
    $m_stmt->bind_param('i', $tenantId);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result();
    if ($m_res) {
        while ($m_row = $m_res->fetch_assoc()) {
            $maintenance[] = $m_row;
        }
    }
    $m_stmt->close();
}

$leasesCount = count($leases);
$requestsCount = count($maintenance);
$pendingRequests = count(array_filter($maintenance, fn($item) => strtolower($item['status'] ?? '') === 'pending'));
?>
