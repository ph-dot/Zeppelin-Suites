<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../php_files/db.php';
}

if (!function_exists('pa_e')) {
    function pa_e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pa_count')) {
    function pa_count(mysqli $conn, string $sql): int {
        $result = $conn->query($sql);
        if (!$result) {
            return 0;
        }
        $row = $result->fetch_row();
        return (int)($row[0] ?? 0);
    }
}

if (!function_exists('pa_format_date')) {
    function pa_format_date($dateValue): string {
        if (empty($dateValue) || $dateValue === '0000-00-00 00:00:00') {
            return 'No date';
        }

        $timestamp = strtotime((string)$dateValue);
        if ($timestamp === false) {
            return (string)$dateValue;
        }

        return date('M d, Y h:i A', $timestamp);
    }
}

if (!function_exists('pa_status_class')) {
    function pa_status_class($status): string {
        $status = strtolower(trim((string)$status));

        $classes = [
            'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
            'pending review' => 'bg-amber-50 text-amber-700 border-amber-100',
            'submitted' => 'bg-blue-50 text-blue-700 border-blue-100',
            'under review' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'requirements pending' => 'bg-purple-50 text-purple-700 border-purple-100',
            'requirements completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'requested' => 'bg-orange-50 text-orange-700 border-orange-100',
            'in progress' => 'bg-sky-50 text-sky-700 border-sky-100',
            'urgent' => 'bg-red-50 text-red-700 border-red-100',
        ];

        return $classes[$status] ?? 'bg-slate-50 text-slate-700 border-slate-100';
    }
}

if (!function_exists('pa_type_class')) {
    function pa_type_class($type): string {
        $type = strtolower(trim((string)$type));

        $classes = [
            'inquiry' => 'bg-amber-50 text-amber-700 border-amber-100',
            'payment review' => 'bg-blue-50 text-blue-700 border-blue-100',
            'reservation' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'cancellation' => 'bg-orange-50 text-orange-700 border-orange-100',
            'maintenance' => 'bg-red-50 text-red-700 border-red-100',
        ];

        return $classes[$type] ?? 'bg-slate-50 text-slate-700 border-slate-100';
    }
}

$pendingInquiryCount = pa_count($conn, "
    SELECT COUNT(*)
    FROM inquiry_table
    WHERE status = 'pending' OR status = '' OR status IS NULL
");

$pendingReservationCount = pa_count($conn, "
    SELECT COUNT(*)
    FROM reservation_table
    WHERE payment_status = 'pending review'
       OR reservation_status IN ('submitted', 'under review', 'requirements pending', 'requirements completed')
       OR cancellation_status = 'requested'
");

$pendingMaintenanceCount = pa_count($conn, "
    SELECT COUNT(*)
    FROM maintenance_requests
    WHERE status IN ('pending', 'in progress')
");

$totalPendingActions = $pendingInquiryCount + $pendingReservationCount + $pendingMaintenanceCount;

$pendingActionsSql = "
    SELECT
        'Inquiry' AS action_type,
        CAST(i.inq_id AS CHAR) AS item_id,
        CONCAT(i.sender_name, ' - ', i.inquiry_type) AS action_title,
        CONCAT('New inquiry from ', i.sender_name, ' about ', i.inquiry_type) AS action_details,
        COALESCE(NULLIF(i.status, ''), 'pending') AS action_status,
        i.timestamp AS action_date,
        'inquiry.php' AS action_url,
        'Review' AS action_label,
        5 AS sort_priority
    FROM inquiry_table i
    WHERE i.status = 'pending' OR i.status = '' OR i.status IS NULL

    UNION ALL

    SELECT
        'Payment Review' AS action_type,
        CAST(r.reservation_id AS CHAR) AS item_id,
        CONCAT(r.client_name, ' - Unit ', COALESCE(u.unit_number, 'N/A')) AS action_title,
        CONCAT('Payment proof needs checking for ', r.inquiry_type) AS action_details,
        r.payment_status AS action_status,
        r.created_at AS action_date,
        'reservation.php' AS action_url,
        'Review' AS action_label,
        2 AS sort_priority
    FROM reservation_table r
    LEFT JOIN units_table u ON u.unit_id = r.unit_id
    WHERE r.payment_status = 'pending review'

    UNION ALL

    SELECT
        'Reservation' AS action_type,
        CAST(r.reservation_id AS CHAR) AS item_id,
        CONCAT(r.client_name, ' - Unit ', COALESCE(u.unit_number, 'N/A')) AS action_title,
        CONCAT('Reservation is currently ', r.reservation_status) AS action_details,
        r.reservation_status AS action_status,
        r.created_at AS action_date,
        'reservation.php' AS action_url,
        'Review' AS action_label,
        CASE
            WHEN r.reservation_status = 'requirements completed' THEN 1
            WHEN r.reservation_status = 'submitted' THEN 3
            WHEN r.reservation_status = 'under review' THEN 4
            ELSE 6
        END AS sort_priority
    FROM reservation_table r
    LEFT JOIN units_table u ON u.unit_id = r.unit_id
    WHERE r.reservation_status IN ('submitted', 'under review', 'requirements pending', 'requirements completed')

    UNION ALL

    SELECT
        'Cancellation' AS action_type,
        CAST(r.reservation_id AS CHAR) AS item_id,
        CONCAT(r.client_name, ' - Unit ', COALESCE(u.unit_number, 'N/A')) AS action_title,
        COALESCE(NULLIF(r.cancellation_reason, ''), 'Client requested cancellation review') AS action_details,
        r.cancellation_status AS action_status,
        COALESCE(r.cancellation_requested_at, r.created_at) AS action_date,
        'reservation.php' AS action_url,
        'Review' AS action_label,
        1 AS sort_priority
    FROM reservation_table r
    LEFT JOIN units_table u ON u.unit_id = r.unit_id
    WHERE r.cancellation_status = 'requested'

    UNION ALL

    SELECT
        'Maintenance' AS action_type,
        CAST(m.maintenance_id AS CHAR) AS item_id,
        CONCAT(m.subject, ' - Unit ', COALESCE(u.unit_number, 'N/A')) AS action_title,
        CONCAT(UPPER(m.priority), ' priority / ', m.category) AS action_details,
        m.status AS action_status,
        m.submitted_at AS action_date,
        'maintenance.php' AS action_url,
        'Review' AS action_label,
        CASE
            WHEN m.priority = 'urgent' THEN 1
            WHEN m.status = 'pending' THEN 3
            ELSE 7
        END AS sort_priority
    FROM maintenance_requests m
    LEFT JOIN units_table u ON u.unit_id = m.unit_id
    WHERE m.status IN ('pending', 'in progress')

    ORDER BY sort_priority ASC, action_date DESC
    LIMIT 10
";

$pendingActions = [];
$pendingActionsResult = $conn->query($pendingActionsSql);
$pendingActionsError = '';

if ($pendingActionsResult) {
    while ($row = $pendingActionsResult->fetch_assoc()) {
        $pendingActions[] = $row;
    }
} else {
    $pendingActionsError = $conn->error;
}
?>

<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 border-b border-slate-100">
    <div>
      <h2 class="font-bold text-slate-900">Pending Admin Actions</h2>
      <p class="text-xs text-slate-400 mt-0.5">Items that still need admin review or follow-up.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="bg-slate-900 text-white text-xs font-bold px-3 py-1.5 rounded-full" style="font-family:'DM Mono',monospace;">
        <?php echo pa_e($totalPendingActions); ?> total
      </span>
      <a href="inquiry.php" class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-xs font-semibold px-4 py-2 rounded-full transition-all">
        View Pages
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/40">
    <div class="bg-white border border-slate-100 rounded-xl px-4 py-3">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Pending Inquiries</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?php echo pa_e($pendingInquiryCount); ?></p>
    </div>
    <div class="bg-white border border-slate-100 rounded-xl px-4 py-3">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservations</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?php echo pa_e($pendingReservationCount); ?></p>
    </div>
    <div class="bg-white border border-slate-100 rounded-xl px-4 py-3">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Maintenance</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?php echo pa_e($pendingMaintenanceCount); ?></p>
    </div>
  </div>

  <?php if (!empty($pendingActionsError)): ?>
    <div class="px-5 py-6 text-sm text-red-600 bg-red-50 border-b border-red-100">
      Pending actions could not load: <?php echo pa_e($pendingActionsError); ?>
    </div>
  <?php endif; ?>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-slate-100 bg-slate-50/60">
          <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Type</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Details</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Status</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Date</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php if (empty($pendingActions)): ?>
          <tr>
            <td colspan="5" class="px-5 py-10 text-center">
              <div class="mx-auto w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              <p class="font-semibold text-slate-700">No pending actions</p>
              <p class="text-xs text-slate-400 mt-1">Everything is currently reviewed.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pendingActions as $action): ?>
            <tr class="room-row">
              <td class="px-5 py-3.5 whitespace-nowrap">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full border <?php echo pa_e(pa_type_class($action['action_type'])); ?>">
                  <?php echo pa_e($action['action_type']); ?>
                </span>
              </td>
              <td class="px-4 py-3.5 min-w-[260px]">
                <p class="font-semibold text-slate-800 line-clamp-1"><?php echo pa_e($action['action_title']); ?></p>
                <p class="text-xs text-slate-400 mt-0.5 line-clamp-1"><?php echo pa_e($action['action_details']); ?></p>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full border <?php echo pa_e(pa_status_class($action['action_status'])); ?>">
                  <?php echo pa_e(ucwords((string)$action['action_status'])); ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap" style="font-family:'DM Mono',monospace;font-size:11px;">
                <?php echo pa_e(pa_format_date($action['action_date'])); ?>
              </td>
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <a href="<?php echo pa_e($action['action_url']); ?>" class="btn-press inline-flex items-center justify-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full active:scale-95 transition-all">
                  <?php echo pa_e($action['action_label']); ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
