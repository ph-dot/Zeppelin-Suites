<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Owner: Lease Management (Reservations) View
 * Pure MVC presentation template. Zero direct SQL queries.
 */
if (!function_exists('clean')) {
    function clean($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($amount): string {
        if ($amount === null || $amount === '') return '—';
        return '₱' . number_format((float)$amount, 2);
    }
}

if (!function_exists('badge')) {
    function badge($value): string {
        $status = strtolower(trim((string)$value));
        if (in_array($status, ['verified', 'reserved', 'requirements completed'], true)) {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>" . clean(ucwords($value)) . "</span>";
        }
        if (in_array($status, ['pending review', 'submitted', 'under review', 'requirements pending'], true)) {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>" . clean(ucwords($value)) . "</span>";
        }
        if (in_array($status, ['rejected', 'cancelled'], true)) {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200'>" . clean(ucwords($value)) . "</span>";
        }
        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-50 text-slate-600 border border-slate-200'>" . clean(ucwords($value)) . "</span>";
    }
}

if (!function_exists('format_timeline_datetime')) {
    function format_timeline_datetime($value): string {
        if (empty($value) || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return '';
        }
        $time = strtotime((string)$value);
        return $time ? date('M d, Y \• h:i A', $time) : '';
    }
}

$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$reservations = $reservations ?? [];
$totalCount = count($reservations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? 'Zeppelin Suites — Lease Management') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
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
.res-row { transition:background 0.15s ease; }
.res-row:hover { background:#f1f5f9; }
.res-row .res-name { transition:color 0.15s ease; }
.res-row:hover .res-name { color:#1d4ed8; }
.view-btn { opacity:0; transform:translateX(6px); transition:opacity 0.18s ease,transform 0.18s ease; }
.res-row:hover .view-btn { opacity:1; transform:translateX(0); }
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">
<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/../components/owner_sidebar.php'; ?>

<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/../components/owner_navbar.php'; ?>

  <!-- ACTIVITY TIMELINE SUMMARY MODAL -->
  <div id="activityTimelineModal" onclick="if(event.target===this) closeActivityTimelineModal()" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-xl sm:max-w-2xl w-full p-6 sm:p-7 relative max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      
      <!-- Top Header -->
      <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" stroke-width="2"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"/>
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 leading-tight">Activity Timeline</h3>
            <p class="text-xs text-slate-400 mt-0.5">Track the progress of your reservation.</p>
          </div>
        </div>
        <button type="button" onclick="closeActivityTimelineModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors" title="Close">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Client Info Card at Top -->
      <div class="mt-4 mb-3 bg-slate-50 border border-slate-100 rounded-xl p-4 flex items-center justify-between gap-4 shrink-0">
        <div class="min-w-0">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Client Name</p>
          <p id="timelineClientName" class="text-sm sm:text-base font-bold text-slate-900 leading-snug break-words">—</p>
          <p id="timelineClientEmail" class="text-xs text-slate-500 break-all mt-0.5">—</p>
        </div>
        <div class="text-right shrink-0 pl-3">
          <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Assigned Unit</span>
          <span id="timelineUnitDisplay" class="text-xs sm:text-sm font-bold text-slate-800 font-mono block mt-0.5">—</span>
        </div>
      </div>

      <!-- Timeline Scrollable Steps -->
      <div class="overflow-y-auto pr-1 flex-1 py-1 space-y-0" id="timelineStepsContainer">
        <!-- Injected via JavaScript -->
      </div>

      <!-- Modal Footer -->
      <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
        <button type="button" onclick="closeActivityTimelineModal()" class="btn-press px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
          Close
        </button>
        <a id="timelineViewDetailsBtn" href="#" class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-all active:scale-95">
          <span>View Details</span>
          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      </div>

    </div>
  </div>

  <main class="main-scroll p-4 md:p-6 space-y-6">
    <div class="max-w-screen-2xl mx-auto space-y-6">

      <!-- Page header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Lease Management</h1>
          <p class="text-xs text-slate-500 mt-0.5">Reservations and active leases across your owned units</p>
        </div>
        <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-mono">
          Total <?= $totalCount ?> <?= $totalCount === 1 ? 'Lease' : 'Leases' ?>
        </span>
      </div>

      <!-- Table Card -->
      <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm" id="resTable">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/60">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Res. #</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Client</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Unit</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Transaction</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Amount</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Payment</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Reservation</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Submitted</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="resBody">
              <?php if (empty($reservations)): ?>
                <tr>
                  <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">
                    No reservations found for your units.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($reservations as $row): 
                    $resIdInt = (int)$row['reservation_id'];
                    $reservationId = str_pad((string)$resIdInt, 3, '0', STR_PAD_LEFT);
                    $unitDisplay = trim(($row['unit_type'] ?? '') . ' Unit ' . ($row['unit_number'] ?? ''));
                    $submittedDate = !empty($row['created_at']) ? date('Y-m-d', strtotime((string)$row['created_at'])) : '—';
                    $resStatus = strtolower(trim((string)($row['reservation_status'] ?? '')));
                    $isMovedIn = in_array($resStatus, ['handover', 'moved in', 'active', 'completed'], true);

                    $timelineData = [
                        'reservation_id'          => $resIdInt,
                        'formatted_id'            => $reservationId,
                        'client_name'             => $row['client_name'] ?? 'Client',
                        'client_email'            => $row['client_email'] ?? '',
                        'unit_display'            => $unitDisplay,
                        'created_at'              => format_timeline_datetime($row['created_at'] ?? null),
                        'payment_status'          => strtolower(trim((string)($row['payment_status'] ?? 'pending review'))),
                        'payment_verified_at'     => format_timeline_datetime($row['payment_verified_at'] ?? null),
                        'payment_rejected_at'     => format_timeline_datetime($row['payment_rejected_at'] ?? null),
                        'reservation_status'      => $resStatus,
                        'officially_booked_at'    => format_timeline_datetime($row['officially_booked_at'] ?? null),
                        'requirements_updated_at' => format_timeline_datetime($row['requirements_updated_at'] ?? null),
                        'move_in_date'            => !empty($row['move_in_date']) && $row['move_in_date'] !== '0000-00-00' ? date('M d, Y', strtotime((string)$row['move_in_date'])) : '',
                        'total_docs'              => (int)($row['total_docs'] ?? 0),
                        'completed_docs'          => (int)($row['completed_docs'] ?? 0),
                        'is_moved_in'             => $isMovedIn,
                        'view_url'                => $baseUrl . '/owner/reservations/view?reservation_id=' . $resIdInt
                    ];
                    $timelineJson = htmlspecialchars(json_encode($timelineData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                ?>
                  <tr class="res-row cursor-pointer hover:bg-slate-50/80 transition-colors group"
                      tabindex="0"
                      role="button"
                      title="Click to view activity timeline for Res. #<?= clean($reservationId) ?>"
                      data-timeline="<?= $timelineJson ?>"
                      onclick="openActivityTimelineModal(this)">

                      <td class="px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap font-mono group-hover:text-blue-600 transition-colors">
                          <?= clean($reservationId) ?>
                      </td>

                      <td class="px-4 py-3.5 whitespace-nowrap">
                          <p class="font-semibold res-name text-slate-800 group-hover:text-blue-600 transition-colors"><?= clean($row['client_name']) ?></p>
                          <p class="text-xs text-slate-400"><?= clean($row['client_email']) ?></p>
                      </td>

                      <td class="px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap">
                          <?= clean($unitDisplay) ?>
                      </td>

                      <td class="px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap">
                          <?= clean($row['transaction_type'] ?? 'For Lease') ?>
                      </td>

                      <td class="px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap font-mono">
                          <?= peso($row['required_amount'] ?? 0) ?>
                      </td>

                      <td class="px-4 py-3.5 whitespace-nowrap">
                          <?= badge($row['payment_status'] ?? 'pending review') ?>
                      </td>

                      <td class="px-4 py-3.5 whitespace-nowrap">
                          <?= badge($row['reservation_status'] ?? 'submitted') ?>
                      </td>

                      <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap font-mono">
                          <?= clean($submittedDate) ?>
                      </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
function openActivityTimelineModal(source) {
  let data = null;
  if (typeof source === 'string') {
    try { data = JSON.parse(source); } catch (e) { console.error(e); }
  } else if (source && source.dataset && source.dataset.timeline) {
    try { data = JSON.parse(source.dataset.timeline); } catch (e) { console.error(e); }
  } else if (typeof source === 'object' && source !== null && !source.dataset) {
    data = source;
  }

  if (!data) return;

  const name = data.client_name || 'Client';
  document.getElementById('timelineClientName').textContent = name;
  document.getElementById('timelineClientEmail').textContent = data.client_email || 'No email provided';
  document.getElementById('timelineUnitDisplay').textContent = data.unit_display || 'Unit';
  document.getElementById('timelineViewDetailsBtn').href = data.view_url || ('<?= htmlspecialchars($baseUrl) ?>/owner/reservations/view?reservation_id=' + data.reservation_id);

  renderActivityTimelineSteps(data);

  const modal = document.getElementById('activityTimelineModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeActivityTimelineModal() {
  const modal = document.getElementById('activityTimelineModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function renderActivityTimelineSteps(data) {
  const container = document.getElementById('timelineStepsContainer');
  if (!container) return;

  const paymentStatus = (data.payment_status || 'pending review').toLowerCase();
  const isPaymentVerified = paymentStatus === 'verified';
  const isPaymentRejected = paymentStatus === 'rejected';

  const resStatus = (data.reservation_status || '').toLowerCase();
  const isOfficiallyBooked = ['officially booked', 'reserved', 'handover', 'moved in', 'active', 'completed'].includes(resStatus) || !!data.officially_booked_at;
  const isHandedOver = ['handover', 'moved in', 'active', 'completed'].includes(resStatus) || !!data.is_moved_in;
  const areDocsComplete = (data.total_docs > 0 && data.completed_docs >= data.total_docs) || isOfficiallyBooked;
  const areDocsInProgress = (data.completed_docs > 0) || ['reserved', 'lease signing'].includes(resStatus);

  const step1 = {
    num: 1,
    state: 'completed',
    title: 'Reservation Form Submitted',
    badge: null,
    timestamp: data.created_at || 'Submitted',
    desc: 'Reservation has been submitted by client'
  };

  let step2 = {};
  if (isPaymentVerified) {
    step2 = {
      num: 2,
      state: 'completed',
      title: 'Payment Verified',
      badge: null,
      timestamp: data.payment_verified_at || 'Payment confirmed',
      desc: 'Payment has been verified by unit owner'
    };
  } else if (isPaymentRejected) {
    step2 = {
      num: 2,
      state: 'rejected',
      title: 'Payment Rejected',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">Rejected</span>',
      timestamp: data.payment_rejected_at || 'Payment not accepted',
      desc: 'Payment was rejected by unit owner.'
    };
  } else {
    step2 = {
      num: 2,
      state: 'in_progress',
      title: 'Payment Verification',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: 'Awaiting payment verification',
      desc: 'Payment is pending verification by unit owner.'
    };
  }

  let step3 = {};
  if (!isPaymentVerified) {
    step3 = {
      num: 3,
      state: 'pending',
      title: 'Lease Signing',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'The lease agreement will be prepared once payment is verified.'
    };
  } else if (isOfficiallyBooked) {
    step3 = {
      num: 3,
      state: 'completed',
      title: 'Lease Signing',
      badge: null,
      timestamp: data.officially_booked_at || data.requirements_updated_at || 'Lease agreement signed',
      desc: 'The lease agreement has been signed'
    };
  } else {
    step3 = {
      num: 3,
      state: 'in_progress',
      title: 'Lease Signing',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: data.payment_verified_at || '',
      desc: 'The lease agreement is ready for signing.'
    };
  }

  let step4 = {};
  if (!isPaymentVerified) {
    step4 = {
      num: 4,
      state: 'pending',
      title: 'Documents',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Complete and submit the required documents.'
    };
  } else if (areDocsComplete) {
    step4 = {
      num: 4,
      state: 'completed',
      title: 'Documents',
      badge: null,
      timestamp: data.requirements_updated_at || '',
      desc: 'All required documents has been submitted and verified.'
    };
  } else if (areDocsInProgress) {
    step4 = {
      num: 4,
      state: 'in_progress',
      title: 'Documents',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>In Progress</span>',
      timestamp: data.total_docs ? `${data.completed_docs} of ${data.total_docs} complete` : '',
      desc: 'Complete and submit the required documents.'
    };
  } else {
    step4 = {
      num: 4,
      state: 'pending',
      title: 'Documents',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Complete and submit the required documents.'
    };
  }

  let step5 = {};
  if (isHandedOver) {
    step5 = {
      num: 5,
      state: 'completed',
      title: 'Handover',
      badge: null,
      timestamp: data.move_in_date ? 'Move-in: ' + data.move_in_date : 'Turnover completed',
      desc: 'Unit turnover and key release completed.'
    };
  } else if (isOfficiallyBooked) {
    step5 = {
      num: 5,
      state: 'in_progress',
      title: 'Handover',
      badge: '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>Ready</span>',
      timestamp: data.move_in_date ? 'Scheduled for ' + data.move_in_date : 'Ready for scheduling',
      desc: 'Unit turnover and key release is ready to be scheduled.'
    };
  } else {
    step5 = {
      num: 5,
      state: 'pending',
      title: 'Handover',
      badge: '<span class="inline-flex items-center text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Pending</span>',
      timestamp: '',
      desc: 'Unit turnover and key release will be scheduled after lease signing.'
    };
  }

  const steps = [step1, step2, step3, step4, step5];

  let html = '';
  for (let i = 0; i < steps.length; i++) {
    const step = steps[i];
    const isLast = (i === steps.length - 1);
    const nextStep = !isLast ? steps[i + 1] : null;

    let circleHtml = '';
    if (step.state === 'completed') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 z-10 shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
      `;
    } else if (step.state === 'in_progress') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 z-10 shadow-sm ring-4 ring-blue-50">
          ${step.num}
        </div>
      `;
    } else if (step.state === 'rejected') {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-red-500 text-white font-bold text-xs flex items-center justify-center shrink-0 z-10 shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
      `;
    } else {
      circleHtml = `
        <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-500 font-bold text-xs flex items-center justify-center shrink-0 z-10">
          ${step.num}
        </div>
      `;
    }

    let lineClass = 'bg-slate-200';
    if (step.state === 'completed' && nextStep && (nextStep.state === 'completed' || nextStep.state === 'in_progress')) {
      lineClass = 'bg-emerald-500';
    }

    html += `
      <div class="flex gap-3 relative ${isLast ? 'pb-1' : 'pb-5'}">
        <div class="flex flex-col items-center">
          ${circleHtml}
          ${!isLast ? `<div class="w-0.5 flex-1 ${lineClass} my-1"></div>` : ''}
        </div>
        <div class="flex-1 pt-0.5">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <h4 class="text-xs font-bold text-slate-900">${step.title}</h4>
              ${step.badge || ''}
            </div>
            ${step.timestamp ? `<span class="text-[10px] text-slate-400 font-mono">${step.timestamp}</span>` : ''}
          </div>
          <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">${step.desc}</p>
        </div>
      </div>
    `;
  }

  container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.res-row').forEach(row => {
    row.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openActivityTimelineModal(row);
      }
    });
  });
});
</script>
</body>
</html>
