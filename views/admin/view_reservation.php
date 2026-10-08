<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - View Reservation / Lease Details
 * Pure MVC presentation template. Zero DB connections or direct queries.
 */
if (!function_exists('e')) {
  function e($value): string
  {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('peso')) {
  function peso($amount): string
  {
    if ($amount === null || $amount === '') {
      return '-';
    }
    return '₱' . number_format((float) $amount, 2);
  }
}

if (!function_exists('format_datetime_text')) {
  function format_datetime_text($value): string
  {
    if (empty($value) || $value === '0000-00-00 00:00:00') {
      return '-';
    }
    $time = strtotime((string) $value);
    return $time ? date('M d, Y h:i A', $time) : '-';
  }
}

if (!function_exists('calculate_lease_duration')) {
  function calculate_lease_duration($start, $end, $fallback = '1 year'): string
  {
    if (empty($start) || empty($end) || $start === '0000-00-00' || $end === '0000-00-00') {
      return $fallback;
    }
    try {
      $d1 = new DateTime((string) $start);
      $d2 = new DateTime((string) $end);
      $diff = $d1->diff($d2);
      $parts = [];
      if ($diff->y > 0)
        $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'year' : 'years');
      if ($diff->m > 0)
        $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'month' : 'months');
      if ($diff->d > 0 && empty($parts))
        $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
      return !empty($parts) ? implode(' ', $parts) : $fallback;
    } catch (Exception $e) {
      return $fallback;
    }
  }
}

$baseUrl = $baseUrl ?? rtrim((string) env('APP_URL', '/Zeppelin-Suites'), '/');
$res = $res ?? [];

$formattedResId = str_pad((string) $res['reservation_id'], 3, '0', STR_PAD_LEFT);

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

$cancellationRequestedBy = 'Unit Owner';
if (($res['cancellation_requested_by_role'] ?? '') === 'client') {
  $cancellationRequestedBy = 'Client';
} elseif (!empty($res['cancellation_requested_by_name'])) {
  $cancellationRequestedBy = $res['cancellation_requested_by_name'];
}

$paymentStatusLower = strtolower($res['payment_status'] ?? 'pending review');
$resStatusLower = strtolower($res['reservation_status'] ?? 'submitted');
$cancellationStatusLower = strtolower($res['cancellation_status'] ?? 'none');

// Determine Transaction Type (Resale vs Lease)
$transactionType = (string) ($res['transaction_type'] ?? '');
$isResale = (strcasecmp($transactionType, 'Unit Resale') === 0)
  || (!empty($res['inquiry_type']) && stripos((string) $res['inquiry_type'], 'resale') !== false)
  || (!empty($res['listing_type']) && stripos((string) $res['listing_type'], 'resell') !== false);

$clientSex = !empty($res['client_sex']) ? $res['client_sex'] : (!empty($res['gender']) ? $res['gender'] : '—');
$clientNationality = !empty($res['client_nationality']) ? $res['client_nationality'] : (!empty($res['nationality']) ? $res['nationality'] : 'Filipino');
$clientFurnishing = !empty($res['furnishing']) ? $res['furnishing'] : 'Fully Furnished';

$unitNumberClean = !empty($res['unit_number']) ? $res['unit_number'] : 'A101';
$unitTypeClean = !empty($res['unit_type']) ? strtolower((string) $res['unit_type']) : 'studio type';
$unitSqm = (float) ($res['sqm'] ?? 0);
$unitSqmDisplay = $unitSqm > 0 ? number_format($unitSqm, 2) . ' SQM' : '—';
$unitSpecificationText = $unitNumberClean . ' - ' . $unitTypeClean;

$floorDisplay = !empty($res['floor_number']) ? (string) $res['floor_number'] : '1';
$listingDisplay = !empty($res['listing_type'])
  ? (strtolower((string) $res['listing_type']) === 'for lease' ? 'For Lease' : (stripos((string) $res['listing_type'], 'resell') !== false ? 'For Reselling' : $res['listing_type']))
  : ($isResale ? 'For Reselling' : 'For Lease');

$leaseRateDisplay = peso($res['lease_rate'] ?? $res['price_basis']) . ' /mo';
$resalePriceDisplay = peso($res['reselling_price'] ?? $res['price_basis']);
$leaseTermDuration = !empty($res['stay_category']) ? $res['stay_category'] : (!empty($res['inq_lease_duration']) ? $res['inq_lease_duration'] : 'Long Term');

$moveInDisplay = !empty($res['move_in_date']) && $res['move_in_date'] !== '0000-00-00'
  ? strtolower(date('F j, Y', strtotime((string) $res['move_in_date'])))
  : '—';

$moveOutDisplay = !empty($res['move_out_date']) && $res['move_out_date'] !== '0000-00-00'
  ? strtolower(date('F j, Y', strtotime((string) $res['move_out_date'])))
  : '—';

$computedLeaseDuration = calculate_lease_duration(
  $res['move_in_date'] ?? '',
  $res['move_out_date'] ?? '',
  !empty($res['inq_lease_duration']) ? (string) $res['inq_lease_duration'] : '1 year'
);

$ownerNameDisplay = !empty($res['owner_name']) ? $res['owner_name'] : 'John Doe';
$ownerEmailDisplay = !empty($res['owner_email']) ? $res['owner_email'] : 'johndoe@gmail.com';
$ownerPhoneDisplay = !empty($res['owner_contact']) ? $res['owner_contact'] : '0912 345 7890';

$isFlexibleSigning = !empty($res['is_flexible_signing']) && (int) $res['is_flexible_signing'] === 1;
$confirmedSigningDate = !empty($res['confirmed_signing_date']) && $res['confirmed_signing_date'] !== '0000-00-00'
  ? (string) $res['confirmed_signing_date']
  : null;

// Parse applicant's candidate/preferred dates
$preferredDatesList = [];
if (!empty($res['lease_signing_date']) && $res['lease_signing_date'] !== '0000-00-00') {
  $rawDates = explode(',', (string) $res['lease_signing_date']);
  foreach ($rawDates as $rawD) {
    $trimmed = trim($rawD);
    if (!empty($trimmed) && $trimmed !== '0000-00-00') {
      $ts = strtotime($trimmed);
      if ($ts) {
        $preferredDatesList[] = [
          'date' => date('Y-m-d', $ts),
          'day_name' => date('D', $ts),
          'day_full' => date('l', $ts),
          'month_day' => date('M j', $ts),
          'year' => date('Y', $ts),
          'full_text' => date('F j, Y', $ts),
        ];
      }
    }
  }
}

$hasConfirmedSchedule = !empty($confirmedSigningDate);
$confirmedDateTs = $hasConfirmedSchedule ? strtotime($confirmedSigningDate) : null;
$confirmedDateDisplay = $confirmedDateTs ? date('F j, Y', $confirmedDateTs) : null;

$signingStatus = !empty($res['lease_signing_status']) ? $res['lease_signing_status'] : 'Pending Signing';
$isSigningCompleted = strtolower((string) $signingStatus) === 'completed';
$paymentMethod = !empty($res['payment_method']) ? $res['payment_method'] : 'GCash QR';
$isInHousePayment = strtolower((string) $paymentMethod) === 'in-house';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zeppelin Suites - <?= $isResale ? 'Resale' : 'Lease' ?> #<?= e($formattedResId) ?></title>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap"
    rel="stylesheet">
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
    * {
      font-family: 'DM Sans', sans-serif;
    }

    .sidebar {
      width: 256px;
      background: rgba(255, 255, 255, 0.92);
    }

    .sidebar.collapsed {
      width: 68px;
    }

    @media (max-width:767px) {
      .sidebar {
        position: fixed;
        z-index: 50;
        height: 100vh;
        width: 256px !important;
      }
    }

    .main-wrapper {
      margin-left: 256px;
    }

    .main-wrapper.sidebar-collapsed {
      margin-left: 68px;
    }

    @media (max-width:767px) {
      .main-wrapper {
        margin-left: 0 !important;
      }
    }

    .overlay {
      display: none;
    }

    .overlay.show {
      display: block;
    }

    .sidebar.collapsed .nav-label,
    .sidebar.collapsed .nav-badge,
    .sidebar.collapsed .notice-section {
      display: none;
    }

    .sidebar.collapsed .sidebar-link {
      justify-content: center;
      padding-left: 0;
      padding-right: 0;
    }

    .sidebar.collapsed .collapse-icon {
      transform: rotate(180deg);
    }

    .sidebar-link.active {
      background: #0f172a;
      color: #fff;
    }

    .sidebar-link.active .nav-icon {
      color: #60a5fa;
    }

    .sidebar-link:not(.active):hover {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .sidebar-link:not(.active):hover .nav-icon {
      color: #3b82f6;
    }

    .zep-input:focus {
      outline: none;
      border-color: #0f172a;
    }

    .main-scroll {
      height: calc(100vh - 65px);
      overflow-y: auto;
    }

    .modal-backdrop {
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .modal-backdrop.open {
      opacity: 1;
      visibility: visible;
    }

    .modal-card {
      transform: translateY(16px) scale(0.97);
      transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modal-backdrop.open .modal-card {
      transform: translateY(0) scale(1);
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-800 overflow-hidden">

  <!-- Overlay and Sidebar -->
  <?php include dirname(__DIR__) . '/components/admin_sidebar.php'; ?>

  <!-- MAIN WRAPPER -->
  <div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
    <!-- TOP BAR / NAVBAR -->
    <?php
    $navBreadcrumb = '<div class="flex items-center gap-2 text-sm text-slate-500">
    <a href="' . htmlspecialchars($baseUrl) . '/admin/reservations" class="hover:text-slate-900 transition-colors font-medium">Reservations</a>
    <span>/</span>
    <span class="text-slate-900 font-semibold">' . ($isResale ? 'Resale' : 'Lease') . ' #' . htmlspecialchars((string) ($formattedResId ?? '')) . '</span>
  </div>';
    include dirname(__DIR__) . '/components/admin_navbar.php';
    ?>

    <!-- MAIN SCROLLABLE CONTENT -->
    <main class="main-scroll p-4 md:p-8 space-y-6">
      <div class="max-w-6xl mx-auto space-y-6">

        <!-- Breadcrumbs & Action Top Bar -->
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-3">
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/reservations"
              class="btn-press inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Reservations
            </a>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">Submitted:
              <?= e(format_datetime_text($res['created_at'])) ?></span>
          </div>
        </div>
        <!-- Top Reservation ID Card (Matching Screenshot) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservation no. ID</p>
              <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 font-mono">REQ-<?= e($formattedResId) ?></h2>
            </div>
            <div>
              <?php
              $pStatus = strtolower(trim((string) ($res['payment_status'] ?? '')));
              $rStatus = strtolower(trim((string) ($res['reservation_status'] ?? 'submitted')));
              if (in_array($rStatus, ['reserved', 'requirements completed', 'officially booked', 'active', 'handover', 'moved in'], true)) {
                $displayResStatus = 'Reserved';
                $resBadgeClass = 'bg-emerald-100 text-emerald-700';
                $dotClass = 'bg-emerald-500';
              } elseif ($pStatus === 'verified' || in_array($rStatus, ['pending', 'requirements pending', 'in progress', 'under review', 'pending review'], true)) {
                $displayResStatus = 'Pending';
                $resBadgeClass = 'bg-amber-100 text-amber-700';
                $dotClass = 'bg-amber-500';
              } elseif (in_array($rStatus, ['rejected', 'cancelled'], true)) {
                $displayResStatus = ucwords($rStatus);
                $resBadgeClass = 'bg-red-100 text-red-700';
                $dotClass = 'bg-red-500';
              } else {
                $displayResStatus = 'Submitted';
                $resBadgeClass = 'bg-amber-100 text-amber-700';
                $dotClass = 'bg-amber-500';
              }
              ?>
              <span
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold <?= $resBadgeClass ?>">
                <span class="w-2 h-2 rounded-full <?= $dotClass ?>"></span>
                <?= e($displayResStatus) ?>
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
              <button type="button" onclick="switchReservationTab('lease')" id="tabBtn-lease"
                class="tab-nav-btn pb-3 text-sm font-bold text-slate-900 border-b-2 border-slate-900 shrink-0">
                <?= $isResale ? 'Resale' : 'Lease' ?>
              </button>
              <button type="button" onclick="switchReservationTab('payment')" id="tabBtn-payment"
                class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent shrink-0">
                Payment
              </button>
              <button type="button" onclick="switchReservationTab('lease-signing')" id="tabBtn-lease-signing"
                class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent shrink-0">
                <?= $isResale ? 'Contract Signing' : 'Lease Signing' ?>
              </button>
              <button type="button" onclick="switchReservationTab('documents')" id="tabBtn-documents"
                class="tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent shrink-0">
                Documents
              </button>
            </div>
          </div>

          <!-- TAB 1: LEASE / RESALE (Matching Screenshot) -->
          <div id="tabContent-lease" class="tab-panel space-y-6">

            <!-- Client Information Section -->
            <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 sm:p-6">
              <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-200/60">
                <div
                  class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100/80 text-blue-600 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.015-8 4.5V20h16v-1.5c0-2.485-3.582-4.5-8-4.5z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-bold text-slate-900">Client Information</h2>
                  <p class="text-xs text-slate-400">Personal &amp; contact details of the applicant</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
                <div>
                  <p class="text-xs font-normal text-slate-400">Full Name</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= e($res['client_name']) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Email</p>
                  <p class="text-sm font-bold text-slate-900 mt-1">
                    <a href="mailto:<?= e($res['client_email']) ?>"
                      class="underline hover:text-blue-600 transition-colors"><?= e($res['client_email']) ?></a>
                  </p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Phone Number</p>
                  <p class="text-sm font-bold text-slate-900 mt-1 font-mono">
                    <?= e($res['client_contact'] ?: ($res['client_user_contact'] ?? '0912 345 7890')) ?></p>
                </div>

                <div>
                  <p class="text-xs font-normal text-slate-400">Sex</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientSex) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Nationality</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientNationality) ?></p>
                </div>
              </div>
            </div>

            <!-- Unit and Lease/Resale specification Section -->
            <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 sm:p-6">
              <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-200/60">
                <div
                  class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100/80 text-blue-600 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-bold text-slate-900">
                    <?= $isResale ? 'Unit and Resale specification' : 'Unit and Lease specification' ?></h2>
                  <p class="text-xs text-slate-400">
                    <?= $isResale ? 'Assigned unit details and resale specifications' : 'Assigned unit details and lease specifications' ?>
                  </p>
                </div>
              </div>

              <!-- Unit Owner Subheading -->
              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Unit Owner</h3>
              <div
                class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6 mb-6 pb-6 border-b border-slate-200/60">
                <div>
                  <p class="text-xs font-normal text-slate-400">Full Name</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= e($ownerNameDisplay) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Email</p>
                  <p class="text-sm font-bold text-slate-900 mt-1">
                    <a href="mailto:<?= e($ownerEmailDisplay) ?>"
                      class="underline hover:text-blue-600 transition-colors"><?= e($ownerEmailDisplay) ?></a>
                  </p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Phone Number</p>
                  <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($ownerPhoneDisplay) ?></p>
                </div>
              </div>

              <!-- Unit Specifications -->
              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Unit Specifications</h3>
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
                <?php if ($isResale): ?>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Resale Price</p>
                    <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($resalePriceDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Furnishing</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= e($clientFurnishing) ?></p>
                  </div>
                <?php else: ?>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Lease Rate</p>
                    <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= e($leaseRateDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Lease term duration</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= e($leaseTermDuration) ?></p>
                  </div>
                <?php endif; ?>
              </div>

              <?php if ($isResale): ?>
                <!-- View Inquiry Action Button/Link for Resale -->
                <div class="flex justify-end mt-6 pt-4 border-t border-slate-200/60">
                  <?php if (!empty($res['inq_id'])): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries?inq_id=<?= (int) $res['inq_id'] ?>"
                      class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>View Inquiry</span>
                    </a>
                  <?php else: ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries"
                      class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>View Inquiries</span>
                    </a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!$isResale): ?>
              <!-- Schedule Section (Lease Commencement and Expiration) -->
              <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 sm:p-6">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-200/60">
                  <div
                    class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100/80 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-slate-900">Lease Commencement and Expiration</h2>
                    <p class="text-xs text-slate-400">Move-in, move-out schedule and stay duration</p>
                  </div>
                </div>

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

                <!-- Client Remarks / Message under Schedule -->
                <div class="mt-5 pt-4 border-t border-slate-200/60">
                  <p class="text-xs font-normal text-slate-400">Remarks / Client Message</p>
                  <div
                    class="mt-1.5 p-4 bg-white border border-slate-200/80 rounded-xl text-xs sm:text-sm text-slate-700 leading-relaxed shadow-2xs">
                    <?php if (!empty($res['client_remarks'])): ?>
                      <p class="font-medium text-slate-800"><?= nl2br(e($res['client_remarks'])) ?></p>
                    <?php else: ?>
                      <p class="text-slate-400 italic">No special remarks or requests submitted by applicant.</p>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- View Inquiry Action Button/Link -->
                <div class="flex justify-end mt-6 pt-4 border-t border-slate-200/60">
                  <?php if (!empty($res['inq_id'])): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries?inq_id=<?= (int) $res['inq_id'] ?>"
                      class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>View Inquiry</span>
                    </a>
                  <?php else: ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries"
                      class="btn-press inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>View Inquiries</span>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- CANCELLATION REQUEST SECTION (IF APPLICABLE) -->
            <?php if ($cancellationStatusLower === 'requested'): ?>
              <section id="cancellationRequestSection"
                class="mt-6 bg-red-50 border border-red-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-red-200/60">
                  <div
                    class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-red-800 uppercase tracking-wider">Cancellation Request Pending</h2>
                    <p class="text-xs text-red-600 mt-0.5">A cancellation request was submitted by
                      <?= e($cancellationRequestedBy) ?>. Admin review and approval is required.</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                  <div class="bg-white border border-red-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested At</p>
                    <p class="text-sm font-bold text-red-800 mt-0.5 font-mono">
                      <?= e(format_datetime_text($res['cancellation_requested_at'])) ?></p>
                  </div>
                  <div class="bg-white border border-red-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Requested By</p>
                    <p class="text-sm font-bold text-red-800 mt-0.5"><?= e($cancellationRequestedBy) ?></p>
                  </div>
                </div>

                <div class="bg-white border border-red-100 rounded-xl p-4 mb-5">
                  <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Reason for Cancellation</p>
                  <p id="cancel_reason_display" class="text-sm text-red-900 mt-1 leading-relaxed">
                    <?= e($res['cancellation_reason'] ?: 'No reason provided.') ?></p>
                </div>

                <button type="button" id="btnApproveCancellation"
                  class="btn-press w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
                  Approve Cancellation &amp; Release Unit
                </button>
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h4v6h-4m0-6v6" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Payment Information</h2>
                    <p class="text-xs text-slate-500">Applicant downpayment details and admin verification status</p>
                  </div>
                </div>
              </div>

              <!-- Proof & Verification Status Cards -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if ($isInHousePayment): ?>
                  <!-- In-House Payment Notice Card -->
                  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                      <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Payment Method</span>
                        <span
                          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300/80">Pay
                          In-House</span>
                      </div>
                      <h4 class="text-xs font-bold text-slate-900 uppercase">In-House Settlement Notice</h4>
                      <p class="text-xs text-slate-700 mt-2 leading-relaxed">
                        The applicant selected <strong>Pay In-House</strong>. No electronic proof upload was required. The
                        downpayment
                        (<strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong>)
                        will be collected directly in cash or manager's check during the scheduled lease signing
                        appointment.
                      </p>
                    </div>

                    <div
                      class="mt-4 pt-3 border-t border-amber-200/80 text-[11px] text-amber-800 font-medium flex items-center gap-1.5">
                      <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>Settlement to be confirmed in person during lease signing.</span>
                    </div>
                  </div>
                <?php else: ?>
                  <!-- Uploaded Proof Card (GCash QR) -->
                  <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                      <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Uploaded Proof of
                          Payment</span>
                        <span
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">GCash
                          QR</span>
                      </div>
                      <div class="mt-2 flex items-center gap-2">
                        <?php if (!empty($proofUrl)): ?>
                          <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100/80 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Proof Attached
                          </span>
                        <?php else: ?>
                          <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                            No Proof Uploaded
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200/60">
                      <?php if (!empty($proofUrl)): ?>
                        <a href="<?= e($proofUrl) ?>" target="_blank" rel="noopener noreferrer"
                          class="btn-press inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
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
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Verification Status
                    </p>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                      <?php if ($paymentStatusLower === 'verified'): ?>
                        <span
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                          Payment Complete
                        </span>
                        <?php if ($res['payment_verified_at']): ?>
                          <span class="text-xs text-slate-500 font-mono">(Verified:
                            <?= e(format_datetime_text($res['payment_verified_at'])) ?>)</span>
                        <?php endif; ?>
                      <?php elseif ($paymentStatusLower === 'rejected'): ?>
                        <span
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                          Payment Not Received
                        </span>
                        <?php if ($res['payment_rejected_at']): ?>
                          <span class="text-xs text-slate-500 font-mono">(Rejected:
                            <?= e(format_datetime_text($res['payment_rejected_at'])) ?>)</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                          Payment Incomplete
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="mt-4 pt-3 border-t border-slate-200/60 text-xs text-slate-500">
                    <?php if ($paymentStatusLower === 'verified'): ?>
                      <span class="text-emerald-700 font-semibold">✓
                        <?= $isInHousePayment ? 'In-House Payment Received &amp; Verified' : 'Verified &amp; Confirmed' ?></span>
                    <?php elseif ($paymentStatusLower === 'rejected'): ?>
                      <span class="text-red-700 font-semibold">✕ Payment not received — unit released</span>
                    <?php else: ?>
                      <span class="text-amber-700 font-semibold">●
                        <?= $isInHousePayment ? 'Payment incomplete — awaiting in-house settlement' : 'Payment incomplete — awaiting unit owner verification' ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Payment Decision Banner (if verified or rejected) -->
              <?php if ($paymentStatusLower === 'verified'): ?>
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
                  <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment Complete</p>
                    <p class="text-sm font-medium text-emerald-900 mt-0.5">The downpayment has been verified. The client
                      was notified, and document tracking is active.</p>
                    <?php if (!empty($res['admin_payment_remarks'])): ?>
                      <p class="text-xs text-emerald-800 mt-1.5 italic">Remarks: <?= e($res['admin_payment_remarks']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php elseif ($paymentStatusLower === 'rejected'): ?>
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-start gap-3">
                  <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-red-700">Payment Not Received / Rejected</p>
                    <p class="text-sm font-medium text-red-900 mt-0.5">This reservation was closed because payment was not
                      received. The unit has been released back to availability.</p>
                    <?php if (!empty($res['admin_payment_remarks'])): ?>
                      <p class="text-xs text-red-800 mt-1.5 italic">Reason: <?= e($res['admin_payment_remarks']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php elseif ($isInHousePayment): ?>
                <div
                  class="mt-5 p-5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                  <div>
                    <h4 class="text-sm font-bold text-slate-900">In-House Downpayment Settlement</h4>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Downpayment
                      (<strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong>)
                      to be collected in cash or check.
                    </p>
                  </div>
                  <div class="flex items-center gap-3">
                    <button type="button" id="btnCompleteInHousePayment"
                      class="btn-press px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                      Payment Received — Complete
                    </button>
                    <button type="button" id="btnRejectInHousePayment"
                      class="btn-press px-5 py-2.5 bg-red-600 hover:bg-red-700 active:scale-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Not Received / Reject
                    </button>
                  </div>
                </div>
              <?php else: ?>
                <!-- Awaiting Unit Owner Verification Banner -->
                <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50/90 px-5 py-4 flex items-start gap-3">
                  <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Awaiting Unit Owner Verification
                    </p>
                    <p class="text-sm font-medium text-blue-950 mt-0.5">
                      Downpayments are sent directly to the assigned unit owner's GCash QR code. The assigned unit owner
                      (<strong><?= e($res['owner_name'] ?: 'Unit Owner') ?></strong>) is responsible for checking their
                      account and verifying this payment in their portal.
                    </p>
                    <?php if (!empty($res['admin_payment_remarks'])): ?>
                      <p class="text-xs text-blue-800 mt-1.5 italic">Current note: <?= e($res['admin_payment_remarks']) ?>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Secondary Admin Override Actions -->
                <div class="mt-4 pt-3 border-t border-slate-200">
                  <details class="text-xs text-slate-500">
                    <summary
                      class="cursor-pointer font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5 py-1">
                      <span>Administrative Override Options</span>
                      <span class="text-[10px] text-slate-400">(use only if unit owner requests admin assistance)</span>
                    </summary>
                    <div class="mt-3 flex flex-col sm:flex-row gap-3">
                      <button type="button" id="btnVerifyPayment"
                        class="btn-press flex-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Admin Override: Complete
                      </button>

                      <button type="button" id="btnRejectPayment"
                        class="btn-press flex-1 bg-red-600 hover:bg-red-700 active:scale-95 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Admin Override: Reject
                      </button>
                    </div>
                  </details>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200/70 px-4 py-3">
                  <p class="text-xs text-slate-600 leading-relaxed">
                    Reservation fee is non-refundable once verified and processed. If the payment does not match the
                    required amount, the reservation may be rejected by the admin before requirement tracking.
                  </p>
                </div>
              <?php endif; ?>
            </section>
          </div>

          <!-- TAB 3: LEASE / CONTRACT SIGNING -->
          <div id="tabContent-lease-signing" class="tab-panel space-y-6 hidden">
            <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
              <div class="flex items-center justify-between flex-wrap gap-4 mb-6 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100/80 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-slate-900">
                      <?= $isResale ? 'Contract Signing' : 'Lease Contract Signing' ?></h2>
                    <p class="text-xs text-slate-400">
                      <?= $isResale ? 'Appointment schedule, buyer preferences, and contract execution' : 'Appointment schedule, tenant preferences, and contract execution' ?>
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <?php if ($isSigningCompleted): ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      Signing Completed
                    </span>
                  <?php elseif ($hasConfirmedSchedule): ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                      Schedule Confirmed
                    </span>
                  <?php else: ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                      Date Pending Confirmation
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Schedule & Details Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

                <!-- Left Box: Chosen Signing Date (Read-Only for Admin) -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
                  <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">
                      <?= $isResale ? 'Chosen Signing Date' : 'Chosen Lease Signing Date' ?></p>
                    <?php if ($hasConfirmedSchedule): ?>
                      <span
                        class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                        Confirmed
                      </span>
                    <?php else: ?>
                      <span
                        class="text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        Pending Owner Confirmation
                      </span>
                    <?php endif; ?>
                  </div>

                  <?php if ($hasConfirmedSchedule): ?>
                    <!-- Confirmed View -->
                    <div>
                      <h3 class="text-xl sm:text-2xl font-bold text-slate-900 font-mono tracking-tight">
                        <?= e($confirmedDateDisplay) ?>
                      </h3>
                      <p class="text-xs text-slate-500 mt-1">
                        <?= $isResale ? 'Confirmed contract signing appointment with buyer.' : 'Confirmed lease signing appointment with tenant.' ?>
                        <?php if (!empty($res['confirmed_signing_by_name'])): ?>
                          <span class="text-slate-400 block mt-0.5">Confirmed by
                            <strong><?= e($res['confirmed_signing_by_name']) ?></strong><?php if (!empty($res['confirmed_signing_at'])): ?>
                              on <?= date('M j, Y g:i A', strtotime($res['confirmed_signing_at'])) ?><?php endif; ?></span>
                        <?php endif; ?>
                      </p>
                    </div>
                  <?php else: ?>
                    <!-- Pending Owner Confirmation View -->
                    <div class="space-y-2">
                      <h3 class="text-sm sm:text-base font-bold text-slate-800">
                        Awaiting Unit Owner Confirmation
                      </h3>
                      <p class="text-xs text-slate-500 leading-relaxed">
                        The unit owner has not yet confirmed the final signing appointment date.
                      </p>
                      <?php if (!empty($preferredDatesList)): ?>
                        <div class="pt-1.5 text-xs text-slate-600">
                          <span class="text-slate-400 font-medium block mb-1">Applicant's preferred dates:</span>
                          <div class="flex items-center gap-1.5 flex-wrap">
                            <?php foreach ($preferredDatesList as $p): ?>
                              <span
                                class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 font-semibold text-xs shadow-2xs">
                                <?= $p['month_day'] ?> (<?= $p['day_name'] ?>)
                              </span>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      <?php elseif ($isFlexibleSigning): ?>
                        <p class="text-xs text-slate-500 italic">
                          <?= $isResale ? 'Buyer requested flexible schedule before move-in.' : 'Tenant requested flexible schedule before move-in.' ?>
                        </p>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="pt-3 border-t border-slate-200/70 text-xs">
                    <span
                      class="text-slate-400 block mb-0.5"><?= $isResale ? 'Target Move-in Date:' : 'Move-in Date:' ?></span>
                    <span class="font-bold text-slate-900"><?= e($moveInDisplay) ?></span>
                  </div>
                </div>

                <!-- Right Box: Signer & Unit Details -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Signer &amp; Unit Details</p>
                  <div class="space-y-2.5 text-xs sm:text-sm">
                    <div class="flex items-center justify-between gap-2">
                      <span
                        class="text-slate-400 font-medium"><?= $isResale ? 'Buyer / Applicant:' : 'Tenant / Applicant:' ?></span>
                      <span class="font-bold text-slate-900 text-right"><?= e($res['client_name']) ?></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 font-medium">Contact Number:</span>
                      <span
                        class="font-bold text-slate-900 font-mono text-right"><?= e($res['client_contact'] ?: ($res['client_user_contact'] ?? '—')) ?></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 font-medium">Assigned Unit:</span>
                      <span class="font-bold text-slate-900 text-right"><?= e($unitSpecificationText) ?></span>
                    </div>
                    <?php if ($isInHousePayment): ?>
                      <div
                        class="pt-2 text-xs text-amber-800 bg-amber-50/80 border border-amber-200/80 rounded-lg p-2.5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor"
                          viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>In-House Payment: Collect
                          <strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong>
                          upon signing.</span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

              </div>

              <!-- Signing Status & Action Banner -->
              <?php if ($isSigningCompleted): ?>
                <!-- Completed State -->
                <div
                  class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div class="flex items-start gap-3.5">
                    <div
                      class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-emerald-950">Contract Signed &amp; Completed</h4>
                      <p class="text-xs text-emerald-700 mt-0.5">
                        Completed on <strong><?= e(format_datetime_text($res['lease_signed_at'])) ?></strong>
                        <?php if (!empty($res['lease_signed_by_name'])): ?>
                          by <strong><?= e($res['lease_signed_by_name']) ?></strong>
                        <?php endif; ?>
                      </p>
                    </div>
                  </div>

                  <button type="button" onclick="openSigningModal('reset')"
                    class="btn-press text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl px-4 py-2 shadow-xs transition-all shrink-0">
                    Reset Status
                  </button>
                </div>
              <?php else: ?>
                <!-- Pending State -->
                <div
                  class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div>
                    <h4 class="text-sm font-bold text-slate-900">Execution Confirmation</h4>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Confirm lease contract execution once formally signed by both tenant and unit owner.
                    </p>
                  </div>

                  <button type="button" onclick="openSigningModal('complete')"
                    class="btn-press px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-2 transition-all shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Complete Lease Signing</span>
                  </button>
                </div>
              <?php endif; ?>

            </section>
          </div>

          <!-- TAB 4: DOCUMENTS (Matching Image 1) -->
          <div id="tabContent-documents" class="tab-panel space-y-6 hidden">
            <section id="requirementTrackingSection"
              class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
              <div class="flex items-center justify-between flex-wrap gap-4 mb-5 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5h6m-7 4h8m-8 4h5m-6 8h10a2 2 0 002-2V7.8a2 2 0 00-.6-1.4l-3.8-3.8A2 2 0 0013.2 2H7a2 2 0 00-2 2v15a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Document Tracking</h2>
                    <p class="text-xs text-slate-500">View applicant requirement submissions (managed by Unit Owner)</p>
                  </div>
                </div>
              </div>

              <input type="hidden" id="process_reservation_id" value="<?= e($res['reservation_id']) ?>">

              <!-- Officially Booked Banner (Matching Image 1) -->
              <div id="requirementDecisionDisplay" class="hidden mb-5 rounded-xl border px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wide mb-1" id="requirementDecisionLabel">Requirement
                  Status</p>
                <p class="text-sm font-semibold" id="requirementDecisionText">-</p>
              </div>

              <!-- Documents Table (Matching Image 1) -->
              <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                      <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Document</th>
                      <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Status</th>
                      <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Storage</th>
                      <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Link
                      </th>
                    </tr>
                  </thead>
                  <tbody id="documentsTableBody" class="divide-y divide-slate-100">
                    <tr>
                      <td colspan="4" class="px-4 py-8 text-center text-xs text-slate-400">Loading documents...</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <button type="button" id="btnOfficiallyBooked"
                class="hidden mt-3 w-full bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
                Mark as Officially Booked
              </button>

              <?php
              $isHandedOver = in_array($resStatusLower, ['handover', 'moved in', 'active'], true);
              if (!$isHandedOver && $resStatusLower !== 'cancelled' && $resStatusLower !== 'rejected'):
                ?>
                <button type="button" id="btnHandoverDetail" onclick="openHandoverModal()"
                  class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Complete Unit Handover &amp; Move In Tenant
                </button>
              <?php elseif ($isHandedOver): ?>
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-center">
                  <p
                    class="text-xs font-bold text-emerald-800 uppercase tracking-wide flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Unit Handover Completed — Tenant Moved In
                  </p>
                </div>
              <?php endif; ?>

              <div
                class="mt-5 bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Last Updated By</p>
                  <p id="requirements_updated_by_display" class="text-sm font-semibold text-slate-800 mt-0.5">
                    <?= e($res['requirements_updated_by_name'] ?: 'Not updated yet') ?>
                    (<?= e($res['requirements_updated_by_role'] ?: '-') ?>)
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

  <!-- VERIFY PAYMENT CONFIRMATION MODAL -->
  <div id="verifyPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
      <div class="bg-emerald-600 px-6 py-4">
        <h2 class="text-lg font-bold text-white">Verify Payment?</h2>
        <p class="text-sm text-emerald-50 mt-1">Please confirm before proceeding.</p>
      </div>

      <div class="p-6 space-y-4">
        <p class="text-sm text-slate-700 leading-relaxed">
          Are you sure you want to verify this payment based on the uploaded proof?
        </p>

        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
          <p class="text-xs text-emerald-700 leading-relaxed">
            This will mark the payment as verified and notify the client to proceed with the reservation requirements.
          </p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Notes (optional)</label>
          <textarea id="verifyPaymentRemarks" rows="2" placeholder="e.g. Payment verified against transaction slip."
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 resize-none focus:outline-none focus:border-slate-900"></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" onclick="closePaymentConfirmModal('verifyPaymentModal')"
          class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
          Cancel
        </button>

        <button type="button" onclick="confirmPaymentAction('verify')"
          class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">
          Yes, Verify Payment
        </button>
      </div>
    </div>
  </div>

  <!-- REJECT PAYMENT CONFIRMATION MODAL -->
  <div id="rejectPaymentModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
      <div class="bg-red-600 px-6 py-4">
        <h2 class="text-lg font-bold text-white">Reject Payment?</h2>
        <p class="text-sm text-red-50 mt-1">Please confirm before proceeding.</p>
      </div>

      <div class="p-6">
        <p class="text-sm text-slate-700 leading-relaxed">
          Are you sure the uploaded payment proof does not match the required reservation amount?
        </p>

        <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
          <p class="text-xs text-red-700 leading-relaxed">
            This will reject the reservation request, notify the client, and release the unit back to available status.
          </p>
        </div>

        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
          Reason / Admin Remarks <span class="text-red-500">*</span>
        </label>
        <textarea id="rejectPaymentRemarks" rows="3"
          placeholder="Example: Submitted amount does not match the required reservation amount."
          class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')"
          class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
          Cancel
        </button>

        <button type="button" onclick="confirmPaymentAction('reject')"
          class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">
          Yes, Reject Payment
        </button>
      </div>
    </div>
  </div>

  <!-- HANDOVER MODAL -->
  <div id="handoverModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4"
    onclick="if(event.target===this) closeHandoverModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-4 flex items-center justify-between text-white">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h2 class="text-base font-bold">Unit Handover & Move In</h2>
            <p class="text-xs text-emerald-100">Activate tenant account and occupy unit</p>
          </div>
        </div>
        <button type="button" onclick="closeHandoverModal()"
          class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form id="handoverForm" onsubmit="submitHandover(event)" class="p-6 space-y-4">
        <input type="hidden" name="reservation_id" value="<?= e($res['reservation_id']) ?>">

        <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 space-y-2 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">Client:</span>
            <span class="font-bold text-slate-800"><?= e($res['client_name']) ?></span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">Email:</span>
            <span class="font-semibold text-slate-700"><?= e($res['client_email']) ?></span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">Assigned Unit:</span>
            <span class="font-bold text-emerald-700"><?= e($unitDisplay) ?></span>
          </div>
        </div>

        <div
          class="rounded-xl bg-emerald-50/80 border border-emerald-200 p-4 text-xs text-emerald-900 leading-relaxed space-y-1.5">
          <p class="font-bold text-emerald-900 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Official Tenant Account Activation
          </p>
          <p class="text-emerald-800">
            The client will have an official tenant account under Zeppelin Suites. An activation message with their
            default login password (<code
              class="bg-emerald-100 font-mono font-bold text-emerald-950 px-1.5 py-0.5 rounded text-[11px]">tenantzepellinsuites</code>)
            will be sent to <strong><?= e($res['client_email']) ?></strong>. They can log in and change their password
            anytime.
          </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button type="button" onclick="closeHandoverModal()"
            class="btn-press px-4 py-2 text-sm font-semibold border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50">
            Cancel
          </button>
          <button type="submit" id="btnConfirmHandover"
            class="btn-press px-5 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>Complete Handover</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const currentReservationId = <?= json_encode((int) $res['reservation_id']) ?>;
    const currentReservationStatus = <?= json_encode($res['reservation_status'] ?? '') ?>;

    // --- Payment Confirmation Modals ---
    function openPaymentConfirmModal(action) {
      if (action === 'verify') {
        const remarksBox = document.getElementById('verifyPaymentRemarks');
        if (remarksBox) remarksBox.value = '';
        document.getElementById('verifyPaymentModal').classList.remove('hidden');
        document.getElementById('verifyPaymentModal').classList.add('flex');
      }
      if (action === 'reject') {
        const remarksBox = document.getElementById('rejectPaymentRemarks');
        if (remarksBox) remarksBox.value = '';
        document.getElementById('rejectPaymentModal').classList.remove('hidden');
        document.getElementById('rejectPaymentModal').classList.add('flex');
      }
    }

    function closePaymentConfirmModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }

    let shouldReloadOnStatusModalClose = false;

    function showReservationStatusModal(isSuccess, title, message, reloadOnClose = false) {
      shouldReloadOnStatusModalClose = reloadOnClose;
      const modal = document.getElementById('reservationStatusModal');
      const iconContainer = document.getElementById('reservationStatusIcon');
      const titleEl = document.getElementById('reservationStatusTitle');
      const msgEl = document.getElementById('reservationStatusMessage');
      const btn = document.getElementById('reservationStatusBtn');

      if (titleEl) titleEl.textContent = title;
      if (msgEl) msgEl.textContent = message;

      if (iconContainer) {
        if (isSuccess) {
          iconContainer.className = 'w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-xs';
          iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        } else {
          iconContainer.className = 'w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-xs';
          iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
        }
      }

      if (btn) {
        btn.textContent = isSuccess ? 'Continue' : 'Dismiss';
      }

      if (modal) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
          modal.classList.add('open');
        });
      }
    }

    function closeReservationStatusModal() {
      const modal = document.getElementById('reservationStatusModal');
      if (!modal || !modal.classList.contains('open')) return;
      modal.classList.remove('open');
      setTimeout(() => {
        if (!modal.classList.contains('open')) {
          modal.classList.add('hidden');
        }
        if (shouldReloadOnStatusModalClose) {
          window.location.reload();
        }
      }, 250);
    }

    function handleReservationStatusConfirm() {
      closeReservationStatusModal();
    }

    function handleReservationStatusModalBackdrop(e) {
      if (e.target === document.getElementById('reservationStatusModal')) {
        closeReservationStatusModal();
      }
    }

    function confirmPaymentAction(action) {
      let remarks = '';

      if (action === 'verify') {
        remarks = document.getElementById('verifyPaymentRemarks')?.value.trim() || '';
        closePaymentConfirmModal('verifyPaymentModal');
      }
      if (action === 'reject') {
        remarks = document.getElementById('rejectPaymentRemarks')?.value.trim() || '';
        if (remarks === '') {
          showReservationStatusModal(false, 'Missing Reason', 'Please enter a reason for rejecting the payment.');
          return;
        }
        closePaymentConfirmModal('rejectPaymentModal');
      }

      const formData = new FormData();
      formData.append('reservation_id', currentReservationId);
      formData.append('action', action);
      formData.append('remarks', remarks);

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/update-payment', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            let title = 'Payment Updated';
            if (action === 'verify') title = 'Payment Verified';
            else if (action === 'reject') title = 'Payment Rejected';
            else if (action === 'flag') title = 'Payment Flagged';
            showReservationStatusModal(true, title, data.message || 'Payment status updated successfully.', true);
          } else {
            showReservationStatusModal(false, 'Update Failed', data.message || 'Unable to update payment status.');
          }
        })
        .catch(error => {
          console.error(error);
          showReservationStatusModal(false, 'Network Error', 'Something went wrong while updating payment status.');
        });
    }

    document.getElementById('btnVerifyPayment')?.addEventListener('click', () => openPaymentConfirmModal('verify'));
    document.getElementById('btnRejectPayment')?.addEventListener('click', () => openPaymentConfirmModal('reject'));
    document.getElementById('btnRejectInHousePayment')?.addEventListener('click', () => openPaymentConfirmModal('reject'));

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

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/update-payment', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showReservationStatusModal(true, 'Payment Completed', data.message || 'In-House payment collected and verified successfully.', true);
          } else {
            showReservationStatusModal(false, 'Error', data.message || 'Unable to complete in-house payment.');
          }
        })
        .catch(error => {
          console.error(error);
          showReservationStatusModal(false, 'Network Error', 'Something went wrong while completing in-house payment.');
        });
    }

    document.getElementById('btnCompleteInHousePayment')?.addEventListener('click', openInHousePaymentModal);

    // --- Document Tracking Logic (Admin Read-Only) ---
    let currentDocuments = [];

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

    function updateRequirementDecisionUI(reservationStatus) {
      const status = (reservationStatus || '').toLowerCase();
      const display = document.getElementById('requirementDecisionDisplay');
      const label = document.getElementById('requirementDecisionLabel');
      const text = document.getElementById('requirementDecisionText');

      if (!display || !label || !text) return;

      display.className = 'hidden mb-5 rounded-xl border px-4 py-3';

      if (status === 'requirements completed') {
        display.classList.remove('hidden');
        display.classList.add('bg-emerald-50', 'border-emerald-200');

        label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
        text.className = 'text-sm font-semibold text-emerald-800';

        label.textContent = 'Requirements Completed';
        text.textContent = 'All reservation documents have been completed. You may now mark this reservation as officially booked.';
        return;
      }

      if (status === 'reserved') {
        display.classList.remove('hidden');
        display.classList.add('bg-emerald-50', 'border-emerald-200');

        label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
        text.className = 'text-sm font-semibold text-emerald-800';

        label.textContent = 'Officially Booked';
        text.textContent = 'This reservation is already officially booked.';
        return;
      }

      display.classList.add('hidden');
    }

    function refreshOfficialButton(reservationStatus, allCompleted) {
      const btn = document.getElementById('btnOfficiallyBooked');
      if (!btn) return;

      const status = (reservationStatus || '').toLowerCase();
      if (status === 'reserved') {
        btn.classList.add('hidden');
        return;
      }

      if (allCompleted) {
        btn.classList.remove('hidden');
      } else {
        btn.classList.add('hidden');
      }
    }

    function loadDocuments(reservationId) {
      const tbody = document.getElementById('documentsTableBody');
      if (!tbody) return;

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/documents?reservation_id=' + encodeURIComponent(reservationId))
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">' + escapeHtml(data.message || 'Failed to load documents.') + '</td></tr>';
            return;
          }

          currentDocuments = data.documents || [];
          renderDocumentsTable();
          refreshOfficialButton(currentReservationStatus, data.all_completed);
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
        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No documents found for this reservation.</td></tr>';
        return;
      }

      tbody.innerHTML = currentDocuments.map(doc => {
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
      }).join('');
    }

    function markOfficiallyBooked() {
      if (!confirm('Mark this reservation as officially booked? This will set the unit status to Reserved.')) {
        return;
      }

      const formData = new FormData();
      formData.append('reservation_id', currentReservationId);

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/officially-book', {
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
          alert('Something went wrong while officially booking this reservation.');
        });
    }

    document.getElementById('btnOfficiallyBooked')?.addEventListener('click', markOfficiallyBooked);

    // --- Cancellation Approval ---
    function approveCancellationRequest() {
      const reason = document.getElementById('cancel_reason_display')?.textContent.trim();
      if (!confirm('Approve this cancellation request? This will cancel the reservation and release the unit back to availability.')) {
        return;
      }

      const formData = new FormData();
      formData.append('reservation_id', currentReservationId);
      formData.append('remarks', reason || 'Approved cancellation request from unit owner.');

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/cancel', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          alert(data.message);
          if (data.success) {
            window.location.href = '<?= htmlspecialchars($baseUrl) ?>/admin/reservations';
          }
        })
        .catch(error => {
          console.error(error);
          alert('Something went wrong while approving cancellation.');
        });
    }

    document.getElementById('btnApproveCancellation')?.addEventListener('click', approveCancellationRequest);

    // --- Handover Handling ---
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

    function openHandoverModal() {
      const modal = document.getElementById('handoverModal');
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }
    }

    function closeHandoverModal() {
      const modal = document.getElementById('handoverModal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }

    function submitHandover(e) {
      e.preventDefault();
      const btn = document.getElementById('btnConfirmHandover');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Processing Handover &amp; Sending Email...</span>';

      const formData = new FormData(document.getElementById('handoverForm'));

      fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/handover', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = originalText;

          if (data.success) {
            closeHandoverModal();
            alert('✓ Handover Successful!\n\n' + data.message + '\n\nTenant Account Summary:\n• Email: ' + data.tenant.email + '\n• Initial Password: ' + data.tenant.password + '\n\nThe login credentials have been dispatched to the tenant.');
            window.location.reload();
          } else {
            alert('Error: ' + (data.message || 'Unable to process handover.'));
          }
        })
        .catch(err => {
          btn.disabled = false;
          btn.innerHTML = originalText;
          console.error(err);
          alert('Network error while processing handover. Please try again.');
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
          btn.className = 'tab-nav-btn pb-3 text-sm font-bold text-slate-900 border-b-2 border-slate-900 shrink-0';
          panel.classList.remove('hidden');
        } else {
          btn.className = 'tab-nav-btn pb-3 text-sm font-medium text-slate-400 hover:text-slate-800 border-b-2 border-transparent shrink-0';
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

      actionInput.value = action;

      if (action === 'complete') {
        title.textContent = 'Complete Lease Signing';
        desc.textContent = 'Are you sure you want to mark this lease signing as completed? This confirms that the contract has been formally signed and finalized.';
        btn.textContent = 'Complete Signing';
        btn.className = 'px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold';
      } else {
        title.textContent = 'Reset Lease Signing Status';
        desc.textContent = 'Are you sure you want to reset the lease signing status back to Pending Signing?';
        btn.textContent = 'Reset Status';
        btn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold';
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
      const reservationId = currentReservationId;
      const btn = document.getElementById('btnConfirmSigning');

      btn.disabled = true;
      btn.textContent = 'Saving...';

      try {
        const formData = new FormData();
        formData.append('reservation_id', reservationId);
        formData.append('action', action);
        formData.append('remarks', '');

        const res = await fetch('<?= htmlspecialchars($baseUrl) ?>/admin/reservations/lease-signing', {
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
    document.addEventListener('DOMContentLoaded', function () {
      updateRequirementDecisionUI(currentReservationStatus);
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
  <div id="completeInHousePaymentModal"
    class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
      <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Complete In-House Payment
        </h3>
        <button type="button" onclick="closeInHousePaymentModal()"
          class="p-1 rounded-lg hover:bg-emerald-700 text-emerald-100 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-4">
        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed">
          Are you sure you want to mark this in-house payment as completed? Confirm that you have received the required
          downpayment of
          <strong><?= peso($res['required_amount'] ?: ($res['price_basis'] * $res['payment_percentage'])) ?></strong> in
          cash or check.
        </p>

        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800">
          ✓ This will mark the downpayment as received &amp; verified.
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Notes (optional)</label>
          <textarea id="inHousePaymentRemarks" rows="2"
            placeholder="e.g. Cash downpayment received in full at the office."
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 resize-none focus:outline-none focus:border-slate-900"></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" onclick="closeInHousePaymentModal()"
          class="px-4 py-2 border border-slate-200 hover:bg-slate-100 rounded-xl text-xs font-semibold text-slate-700">Cancel</button>
        <button type="button" id="btnConfirmInHousePayment" onclick="submitInHousePaymentComplete()"
          class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">Confirm
          &amp; Complete</button>
      </div>
    </div>
  </div>

  <!-- STYLIZED RESERVATION STATUS NOTIFICATION MODAL -->
  <div id="reservationStatusModal"
    class="modal-backdrop fixed inset-0 z-[100] bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 hidden"
    onclick="handleReservationStatusModalBackdrop(event)">
    <div
      class="modal-card bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-7 max-w-sm w-full text-center"
      onclick="event.stopPropagation()">
      <!-- Icon Container -->
      <div id="reservationStatusIcon"
        class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border transition-all">
      </div>

      <!-- Title -->
      <h3 class="text-base sm:text-lg font-bold text-slate-900 mb-1" id="reservationStatusTitle">Notification</h3>

      <!-- Message Body -->
      <p class="text-xs text-slate-500 mb-6 leading-relaxed" id="reservationStatusMessage"></p>

      <!-- Action Button -->
      <button type="button" id="reservationStatusBtn" onclick="handleReservationStatusConfirm()"
        class="btn-press w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-all shadow-sm active:scale-95">
        Continue
      </button>
    </div>
  </div>

  <!-- Lease Signing Action Modal -->
  <div id="leaseSigningModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-100">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-900" id="signingModalTitle">Confirm Lease Signing Completion</h3>
        <button type="button" onclick="closeSigningModal()"
          class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <p class="text-xs text-slate-600 leading-relaxed" id="signingModalDesc">
        Are you sure you want to mark this lease signing as completed? This confirms that the lease contract was signed
        and finalized by all parties.
      </p>

      <input type="hidden" id="signingActionInput" value="complete">

      <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeSigningModal()"
          class="px-4 py-2 border border-slate-200 hover:bg-slate-50 rounded-xl text-xs font-semibold text-slate-700">Cancel</button>
        <button type="button" id="btnConfirmSigning" onclick="submitLeaseSigningAction()"
          class="px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold">Confirm</button>
      </div>
    </div>
  </div>

</body>

</html>