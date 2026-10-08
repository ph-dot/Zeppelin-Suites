<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Inquiry Email Reply Form View
 * Pure presentation template. No SQL queries or DB connections.
 */
if (!function_exists('e')) {
    function e($val): string {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('replyPeso')) {
    function replyPeso($value): string {
        if ($value === null || $value === '') return '—';
        return '₱' . number_format((float)$value, 2);
    }
}

$inq_id = (int)($inquiry['inq_id'] ?? 0);
$sender_name = (string)($inquiry['sender_name'] ?? 'Client');
$sender_email = (string)($inquiry['sender_email'] ?? '');
$sender_contact = (string)($inquiry['sender_contact'] ?? '');
$inquiry_type = (string)($inquiry['inquiry_type'] ?? 'Inquiry');
$preferred_unit = (string)($inquiry['Preferred_unit_id'] ?? '—');
$preferred_move_in_time = (string)($inquiry['preferred_move_in_time'] ?? '—');
$lease_duration = (string)($inquiry['lease_duration'] ?? '—');
$message = (string)($inquiry['message'] ?? '—');

$status = strtolower((string)($inquiry['status'] ?? 'pending'));
$approval_status = strtolower((string)($inquiry['approval_status'] ?? 'not_requested'));

$approved_units = $inquiry['approved_units'] ?? [];
$approved_count = count($approved_units);

$formatContact = function(array $unit): string {
    $parts = [];
    if (!empty($unit['owner_contact'])) {
        $parts[] = trim((string)$unit['owner_contact']);
    }
    if (!empty($unit['owner_email'])) {
        $parts[] = trim((string)$unit['owner_email']);
    }
    return !empty($parts) ? implode(' • ', $parts) : 'Contact via management';
};

$formatRate = function($val): string {
    if ($val === null || $val === '' || (float)$val <= 0) return 'To be confirmed';
    return replyPeso($val);
};

$formatFloor = function($floorNum, ?string $unitNumber = null): string {
    $num = (int)$floorNum;
    if ($num <= 0 && $unitNumber) {
        if (preg_match('/[A-Za-z]?(\d)/', $unitNumber, $m)) {
            $num = (int)$m[1];
        }
    }
    if ($num <= 0) return 'Standard Floor';
    $titles = [
        1 => '1st Floor',
        2 => '2nd Floor',
        3 => '3rd Floor',
        4 => '4th Floor',
        5 => '5th Floor',
        6 => '6th Floor',
        7 => '7th Floor',
        8 => '8th Floor',
        9 => '9th Floor',
        10 => '10th Floor (Penthouse)'
    ];
    return $titles[$num] ?? "Floor {$num}";
};

$formatSqm = function($sqm): string {
    $val = (float)$sqm;
    if ($val <= 0) return 'Standard Area';
    return number_format($val, 2) . ' SQM';
};

$reservation_token = (string)($inquiry['reservation_token'] ?? '');
$reservation_link = $reservation_token ? "{$baseUrl}/reservation?token=" . urlencode($reservation_token) : "{$baseUrl}/reservation";

$approved_unit_number = (string)($inquiry['approved_unit_number'] ?? '');
$approved_unit_type = (string)($inquiry['approved_unit_type'] ?? '');
$approved_owner_name = (string)($inquiry['approved_owner_name'] ?? '');
$approved_rate = $inquiry['approved_lease_rate'] ?? null;
$approved_at = (string)($inquiry['approved_at_display'] ?? '');

$avatar = strtoupper(substr(trim($sender_name), 0, 1)) ?: '?';

if ($approved_count > 1) {
    $unit_nums = array_map(fn($u) => 'Unit ' . ($u['unit_number'] ?? ''), $approved_units);
    $unit_display = implode(', ', $unit_nums) . " ({$approved_count} Approved Units)";
} elseif ($approved_count === 1) {
    $single = $approved_units[0];
    $unit_display = "Unit {$single['unit_number']} ({$single['unit_type']})";
} elseif ($approved_unit_number) {
    $unit_display = "Unit {$approved_unit_number} ({$approved_unit_type})";
} else {
    $unit_display = ($preferred_unit ?: '—');
}

$owner_display = $approved_owner_name ?: 'Pending Assignment';
$rate_display = $approved_rate !== null ? replyPeso($approved_rate) : 'To be confirmed';
$lease_display = $lease_duration ?: 'Standard';

$type_lower = strtolower($inquiry_type);
$is_general = str_contains($type_lower, 'general') || str_contains($type_lower, 'other');
$is_lease_flow = str_contains($type_lower, 'lease') || str_contains($type_lower, 'rental') || str_contains($type_lower, 'reservation');

$reply_subject = "Update on your inquiry with Zeppelin Suites - " . ($sender_name ?: 'Valued Client');

// Pre-filled email body
$email_body = "Dear " . ($sender_name ?: 'Client') . ",\n\n";
$email_body .= "Thank you for contacting Zeppelin Suites. We have received your inquiry regarding our properties.\n\n";

if ($approval_status === 'approved' && $approved_count > 1) {
    $email_body .= "Great news! The following units have been approved by their owners and match your inquiry:\n\n";
    $email_body .= "Available Units:\n";
    foreach ($approved_units as $idx => $u) {
        $optNum = $idx + 1;
        $uNum = $u['unit_number'] ?? 'Unit';
        $uType = $u['unit_type'] ?? '';
        $uTitle = "Unit {$uNum}" . ($uType ? " ({$uType})" : '');
        $uRate = $formatRate($u['lease_rate'] ?? null);
        $uFloor = $formatFloor($u['floor_number'] ?? null, $uNum);
        $uSqm = $formatSqm($u['sqm'] ?? null);
        $uOwner = !empty($u['owner_name']) ? $u['owner_name'] : 'Assigned Owner';
        $uContact = $formatContact($u);

        $email_body .= "Option {$optNum}: {$uTitle}\n";
        $email_body .= "• Floor: {$uFloor}\n";
        $email_body .= "• Floor Area: {$uSqm}\n";
        $email_body .= "• Owner: {$uOwner}\n";
        $email_body .= "• Owner Contact: {$uContact}\n";
        $email_body .= "• Monthly Rate: {$uRate}\n\n";
    }

    $email_body .= "Inquiry Details:\n";
    if ($preferred_move_in_time && $preferred_move_in_time !== '—') {
        $email_body .= "• Move-in Date: " . $preferred_move_in_time . "\n";
    }
    if ($lease_duration && $lease_duration !== '—') {
        $email_body .= "• Lease Term: " . $lease_duration . "\n";
    }
    $email_body .= "\n";

    $email_body .= "These are all the available units that match your inquiry. If you want to reserve, the link is open. Please proceed to finalize your reservation at the link below:\n";
    $email_body .= "{$reservation_link}\n\n";
} elseif ($approval_status === 'approved' || $approved_count === 1) {
    $single = !empty($approved_units) ? $approved_units[0] : [
        'unit_number' => $approved_unit_number,
        'unit_type' => $approved_unit_type,
        'lease_rate' => $approved_rate,
        'sqm' => $inquiry['approved_sqm'] ?? null,
        'floor_number' => $inquiry['approved_floor_number'] ?? null,
        'owner_name' => $approved_owner_name,
        'owner_contact' => '',
        'owner_email' => '',
    ];

    $uNum = $single['unit_number'] ?? ($approved_unit_number ?: 'Unit');
    $uType = $single['unit_type'] ?? ($approved_unit_type ?: '');
    $uTitle = "Unit {$uNum}" . ($uType ? " ({$uType})" : '');
    $uRate = $formatRate($single['lease_rate'] ?? $approved_rate);
    $uFloor = $formatFloor($single['floor_number'] ?? ($inquiry['approved_floor_number'] ?? null), $uNum);
    $uSqm = $formatSqm($single['sqm'] ?? ($inquiry['approved_sqm'] ?? null));
    $uOwner = !empty($single['owner_name']) ? $single['owner_name'] : ($approved_owner_name ?: 'Unit Owner');
    $uContact = $formatContact($single);

    $email_body .= "Great news! Your request for {$uTitle} has been approved by the unit owner.\n\n";
    $email_body .= "Unit Summary:\n";
    $email_body .= "• Unit: {$uTitle}\n";
    $email_body .= "• Floor: {$uFloor}\n";
    $email_body .= "• Floor Area: {$uSqm}\n";
    $email_body .= "• Owner: {$uOwner}\n";
    $email_body .= "• Owner Contact: {$uContact}\n";
    $email_body .= "• Monthly Rate: {$uRate}\n";
    if ($preferred_move_in_time && $preferred_move_in_time !== '—') {
        $email_body .= "• Move-in Date: " . $preferred_move_in_time . "\n";
    }
    if ($lease_duration && $lease_duration !== '—') {
        $email_body .= "• Lease Term: " . $lease_duration . "\n";
    }
    $email_body .= "\n";

    $email_body .= "This is the available unit that matches your inquiry. If you want to reserve, the link is open. Please proceed to finalize your reservation at the link below:\n";
    $email_body .= "{$reservation_link}\n\n";
} else {
    $email_body .= "We are currently reviewing your request for " . ($unit_display ?: 'our suites') . " and our team is actively coordinating availability.\n\n";
}

$email_body .= "If you have any questions or require additional details, feel free to reply directly to this email.\n\n";
$email_body .= "Warm regards,\nZeppelin Suites Leasing Management\nadmin@zeppelinsuites.com";

$flashSuccess = $_SESSION['success_message'] ?? null;
$flashError = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Zeppelin Suites - Reply to Inquiry') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.sidebar { width: 256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background: rgba(255,255,255,0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; } .sidebar.open { transform: translateX(0); } }
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,.sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content: attr(data-tooltip); position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%); background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px; border-radius: 8px; white-space: nowrap; z-index: 999; box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none; }
.glass-header { background: rgba(255,255,255,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.main-scroll { height: calc(100vh - 65px); overflow-y: auto; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php 
  $navHideSearch = true;
  $navBreadcrumb = '
    <div class="flex items-center gap-2 text-xs">
      <a href="' . htmlspecialchars($baseUrl) . '/admin/inquiries" class="text-slate-400 hover:text-slate-700 transition-colors font-medium">Inquiries</a>
      <span class="text-slate-300">/</span>
      <span class="font-bold text-slate-800">Reply</span>
    </div>
  ';
  include dirname(__DIR__) . '/components/admin_navbar.php'; 
  ?>

  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Breadcrumbs & Header -->
      <div class="flex items-center justify-between">
        <div>
          <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors mb-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Back to Inquiries</span>
          </a>
          <h1 class="text-xl font-bold text-slate-900">Email Reply Composer</h1>
          <p class="text-xs text-slate-400 mt-0.5">Prepare and send a direct response to this client inquiry.</p>
        </div>
      </div>

      <!-- Inquirer Profile Summary Card -->
      <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
        <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
          <div class="w-12 h-12 rounded-xl bg-slate-900 flex items-center justify-center text-white text-base font-bold shrink-0">
            <?= e($avatar) ?>
          </div>
          <div class="flex-1 min-w-0">
            <h2 class="text-base font-bold text-slate-900 truncate"><?= e($sender_name) ?></h2>
            <p class="text-xs text-slate-500 truncate mt-0.5 font-mono"><?= e($sender_email) ?> · <?= e($sender_contact ?: 'No contact number') ?></p>
          </div>
          <div class="text-right shrink-0">
            <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $approval_status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
              <?= e(ucfirst($approval_status === 'approved' ? 'Approved' : $status)) ?>
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <span class="text-slate-400 uppercase font-semibold">Inquiry Type</span>
            <p class="font-bold text-slate-800 mt-1"><?= e($inquiry_type) ?></p>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-semibold">Unit Preference</span>
            <p class="font-bold text-slate-800 mt-1"><?= e($unit_display) ?></p>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-semibold">Lease Preference</span>
            <p class="font-bold text-slate-800 mt-1"><?= e($lease_display) ?></p>
          </div>
        </div>

        <?php if (!empty($approved_units)): ?>
          <div class="pt-3 border-t border-slate-100">
            <div class="flex items-center justify-between mb-2.5">
              <span class="text-xs text-slate-400 uppercase font-semibold">
                <?= count($approved_units) > 1 ? 'Approved Units Matching Inquiry (' . count($approved_units) . ')' : 'Approved Unit Details' ?>
              </span>
              <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                <?= count($approved_units) > 1 ? count($approved_units) . ' Owners Approved' : 'Owner Approved' ?>
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 <?= count($approved_units) > 2 ? 'lg:grid-cols-3' : '' ?> gap-3">
              <?php foreach ($approved_units as $unit): 
                $cStr = $formatContact($unit);
              ?>
                <div class="bg-emerald-50/40 border border-emerald-200/80 rounded-xl p-3.5 space-y-1.5 shadow-2xs">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-slate-900 truncate">
                      Unit <?= e($unit['unit_number']) ?> (<?= e($unit['unit_type']) ?>)
                    </span>
                    <span class="text-xs font-bold text-emerald-700 shrink-0">
                      <?= $formatRate($unit['lease_rate']) ?>/mo
                    </span>
                  </div>
                  <div class="flex items-center gap-1.5 text-[11px] text-slate-600 font-medium">
                    <span class="px-2 py-0.5 rounded-md bg-white border border-emerald-100"><?= e($formatFloor($unit['floor_number'] ?? null, $unit['unit_number'])) ?></span>
                    <span class="px-2 py-0.5 rounded-md bg-white border border-emerald-100"><?= e($formatSqm($unit['sqm'] ?? null)) ?></span>
                  </div>
                  <p class="text-xs text-slate-600"><span class="font-medium text-slate-400">Owner:</span> <?= e($unit['owner_name'] ?: 'Unit Owner') ?></p>
                  <p class="text-[11px] text-slate-500 font-mono"><span class="font-medium text-slate-400 font-sans">Contact:</span> <?= e($cStr) ?></p>
                  <?php if (!empty($unit['owner_remarks'])): ?>
                    <p class="text-[11px] text-slate-600 italic bg-white/80 rounded px-2 py-0.5 border border-emerald-100 mt-1">"<?= e($unit['owner_remarks']) ?>"</p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div>
          <span class="text-xs text-slate-400 uppercase font-semibold">Client Inquiry Message</span>
          <div class="mt-1.5 p-3.5 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-700 leading-relaxed">
            <?= nl2br(e($message)) ?>
          </div>
        </div>
      </div>

      <!-- Email Reply Form -->
      <form id="replyForm" action="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries/reply" method="POST" onsubmit="handleReplySubmit(event)" class="bg-white rounded-2xl border border-slate-100 p-6 space-y-5 shadow-sm">
        <input type="hidden" name="inq_id" value="<?= e($inq_id) ?>">

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Recipient Email</label>
            <input type="email" id="replyToEmail" name="reply_to" value="<?= e($sender_email) ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white transition-colors">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Subject</label>
            <input type="text" id="replySubject" name="reply_subject" value="<?= e($reply_subject) ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white transition-colors">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Message Content</label>
              <button type="button" onclick="copyReplyText()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">Copy message text</button>
            </div>
            <textarea id="emailBody" name="email_body" rows="15" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 leading-relaxed bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white transition-colors font-sans"><?= e($email_body) ?></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries" class="btn-press px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</a>
          <button type="submit" id="sendReplyBtn" class="btn-press inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-all active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            <span>Send Email Reply</span>
          </button>
        </div>
      </form>

    </div>
  </main>
</div>

<!-- Sending Loading Overlay -->
<div id="sendingLoadingOverlay" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 p-6 text-center animate-in fade-in zoom-in-95 duration-150">
    <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
      <svg class="w-7 h-7 text-slate-800 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>
    <h2 class="text-base font-bold text-slate-900 mb-1">Sending Response...</h2>
    <p class="text-xs text-slate-500">Please wait while your email is being delivered.</p>
  </div>
</div>

<!-- Flash Message Notifications -->
<?php if (!empty($flashSuccess)): ?>
<div id="flashSuccessModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 p-6 text-center">
    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h3 class="text-base font-bold text-slate-900 mb-1">Email Delivered</h3>
    <p class="text-xs text-slate-500 mb-5 leading-relaxed"><?= e($flashSuccess) ?></p>
    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries" class="btn-press block w-full py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-700 transition-colors">Return to Inquiries</a>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
<div id="flashErrorModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 p-6 text-center">
    <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-3">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <h3 class="text-base font-bold text-slate-900 mb-1">Delivery Failed</h3>
    <p class="text-xs text-slate-500 mb-5 leading-relaxed"><?= e($flashError) ?></p>
    <button onclick="document.getElementById('flashErrorModal').remove()" class="btn-press block w-full py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-700 transition-colors">Dismiss</button>
  </div>
</div>
<?php endif; ?>

<script>
function handleReplySubmit(e) {
  document.getElementById('sendingLoadingOverlay').classList.remove('hidden');
  document.getElementById('sendingLoadingOverlay').classList.add('flex');
}

function copyReplyText() {
  const text = document.getElementById('emailBody').value;
  navigator.clipboard.writeText(text).then(() => {
    alert('Email body copied to clipboard!');
  });
}
</script>
</body>
</html>
