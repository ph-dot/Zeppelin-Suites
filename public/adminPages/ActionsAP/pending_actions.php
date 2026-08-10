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
?>

<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 border-b border-slate-100">
    <div>
      <h2 class="font-bold text-slate-900">Pending Admin Actions</h2>
      <p class="text-xs text-slate-400 mt-0.5">Items that still need admin review or follow-up.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="bg-slate-900 text-white text-xs font-semibold px-4 py-2 rounded-full">
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
</div>