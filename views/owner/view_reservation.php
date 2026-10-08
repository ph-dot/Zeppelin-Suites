<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Unit Owner: View Reservation Detail View
 * Pure MVC presentation template. Zero SQL queries.
 */
if (!function_exists('clean')) {
  function clean($value): string
  {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('peso')) {
  function peso($amount): string
  {
    if ($amount === null || $amount === '')
      return '—';
    return '₱' . number_format((float) $amount, 2);
  }
}

if (!function_exists('format_datetime_text')) {
  function format_datetime_text($value): string
  {
    if (empty($value) || $value === '0000-00-00 00:00:00')
      return '—';
    $time = strtotime((string) $value);
    return $time ? date('M d, Y h:i A', $time) : '—';
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
    } catch (Throwable $e) {
      return $fallback;
    }
  }
}

$baseUrl = $baseUrl ?? rtrim((string) env('APP_URL', '/Zeppelin-Suites'), '/');
$res = $reservation ?? [];

$formattedResId = str_pad((string) ($res['reservation_id'] ?? 0), 3, '0', STR_PAD_LEFT);

$proofUrl = '';
if (!empty($res['payment_proof'])) {
  $proofUrl = $baseUrl . '/' . ltrim((string) $res['payment_proof'], '/');
}

$paymentStatusLower = strtolower(trim((string) ($res['payment_status'] ?? 'pending review')));
$resStatusLower = strtolower(trim((string) ($res['reservation_status'] ?? 'submitted')));
$cancellationStatusLower = strtolower(trim((string) ($res['cancellation_status'] ?? 'none')));

$canRequestCancellation = !in_array($resStatusLower, ['cancelled', 'rejected', 'reserved'], true)
  && $paymentStatusLower !== 'rejected'
  && $cancellationStatusLower !== 'requested'
  && $cancellationStatusLower !== 'approved';

// Determine Transaction Type (Resale vs Lease)
$transactionType = (string) ($res['transaction_type'] ?? '');
$isResale = (strcasecmp($transactionType, 'Unit Resale') === 0)
  || (!empty($res['inquiry_type']) && stripos((string) $res['inquiry_type'], 'resale') !== false)
  || (!empty($res['listing_type']) && stripos((string) $res['listing_type'], 'resell') !== false);

$clientSex = !empty($res['client_sex']) ? $res['client_sex'] : (!empty($res['gender']) ? $res['gender'] : '—');
$clientNationality = !empty($res['client_nationality']) ? $res['client_nationality'] : (!empty($res['nationality']) ? $res['nationality'] : 'Filipino');
$clientFurnishing = !empty($res['furnishing']) ? $res['furnishing'] : 'Fully Furnished';

$unitNumberClean = !empty($res['unit_number']) ? $res['unit_number'] : '—';
$unitTypeClean = !empty($res['unit_type']) ? strtolower((string) $res['unit_type']) : 'studio type';
$unitSqm = (float) ($res['sqm'] ?? 0);
$unitSqmDisplay = $unitSqm > 0 ? number_format($unitSqm, 2) . ' SQM' : '—';
$unitSpecificationText = $unitNumberClean . ' - ' . $unitTypeClean;

$floorDisplay = !empty($res['floor_number']) ? (string) $res['floor_number'] : '1';
$listingDisplay = !empty($res['listing_type'])
  ? (strtolower((string) $res['listing_type']) === 'for lease' ? 'For Lease' : (stripos((string) $res['listing_type'], 'resell') !== false ? 'For Reselling' : $res['listing_type']))
  : ($isResale ? 'For Reselling' : 'For Lease');

$leaseRateDisplay = peso($res['lease_rate'] ?? $res['price_basis'] ?? 0) . ' /mo';
$resalePriceDisplay = peso($res['reselling_price'] ?? $res['price_basis'] ?? 0);
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

$ownerNameDisplay = !empty($res['owner_name']) ? $res['owner_name'] : $ownerName;
$ownerEmailDisplay = !empty($res['owner_email']) ? $res['owner_email'] : '—';
$ownerPhoneDisplay = !empty($res['owner_contact']) ? $res['owner_contact'] : '—';

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
$downpaymentAmount = (float) ($res['required_amount'] ?? 0) > 0
  ? (float) $res['required_amount']
  : ((float) ($res['price_basis'] ?? 0) * (float) ($res['payment_percentage'] ?? 0.10));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean($pageTitle ?? ('Zeppelin Suites — Lease #' . $formattedResId)) ?></title>
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
  <?php include __DIR__ . '/../components/owner_sidebar.php'; ?>

  <!-- MAIN WRAPPER -->
  <div class="main-wrapper h-screen flex flex-col" id="mainWrapper">
    <!-- TOP BAR / NAVBAR -->
    <?php include __DIR__ . '/../components/owner_navbar.php'; ?>

    <!-- MAIN SCROLLABLE CONTENT -->
    <main class="main-scroll p-4 md:p-8 space-y-6">
      <div class="max-w-6xl mx-auto space-y-6">

        <!-- Breadcrumbs & Top Bar -->
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-3">
            <a href="<?= htmlspecialchars($baseUrl) ?>/owner/reservations"
              class="btn-press inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Lease Management
            </a>
            <span class="text-xs text-slate-400 font-mono">ID: #<?= clean($formattedResId) ?></span>
          </div>

          <div class="flex items-center gap-2">
            <?php if ($canRequestCancellation): ?>
              <button type="button" onclick="openOwnerCancelRequestModal()"
                class="btn-press inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Request Cancellation
              </button>
            <?php endif; ?>
            <span class="text-xs text-slate-500 font-medium ml-2">Submitted:
              <?= clean(format_datetime_text($res['created_at'] ?? '')) ?></span>
          </div>
        </div>

        <!-- Pending Cancellation Alert Banner -->
        <?php if ($cancellationStatusLower === 'requested'): ?>
          <div class="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm flex items-start gap-3.5">
            <div
              class="w-9 h-9 rounded-xl bg-white border border-red-200 flex items-center justify-center text-red-600 shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              </svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">Cancellation Request Under Review</h3>
                <span
                  class="text-xs text-red-600 font-mono"><?= clean(format_datetime_text($res['cancellation_requested_at'] ?? '')) ?></span>
              </div>
              <p class="text-xs text-red-700 mt-1 leading-relaxed">
                Your cancellation request is pending review and approval by the Zeppelin Suites administration.
              </p>
              <?php if (!empty($res['cancellation_reason'])): ?>
                <p class="text-xs text-red-800 mt-2 bg-white/70 border border-red-100 rounded-lg p-2.5">
                  <strong>Reason:</strong> <?= clean($res['cancellation_reason']) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Top Reservation ID Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservation no. ID</p>
              <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 font-mono">REQ-<?= clean($formattedResId) ?>
              </h2>
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
                <?= clean($displayResStatus) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- MAIN CONTAINER: Reservation form Details -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-sm">
          <h1 class="text-xl font-bold text-slate-900 mb-5">Reservation form Details</h1>

          <!-- TAB NAVIGATION BAR -->
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

          <!-- TAB 1: LEASE / RESALE -->
          <div id="tabContent-lease" class="tab-panel space-y-6">
            <!-- Client Information Section -->
            <div>
              <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
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
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($res['client_name']) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Email</p>
                  <p class="text-sm font-bold text-slate-900 mt-1">
                    <a href="mailto:<?= clean($res['client_email']) ?>"
                      class="underline hover:text-blue-600 transition-colors"><?= clean($res['client_email']) ?></a>
                  </p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Phone Number</p>
                  <p class="text-sm font-bold text-slate-900 mt-1 font-mono">
                    <?= clean($res['client_contact'] ?: ($res['client_user_contact'] ?? '—')) ?></p>
                </div>

                <div>
                  <p class="text-xs font-normal text-slate-400">Sex</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($clientSex) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Nationality</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($clientNationality) ?></p>
                </div>
              </div>
            </div>

            <div class="border-t border-slate-100 my-6"></div>

            <!-- Unit and Lease/Resale specification Section -->
            <div>
              <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
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

              <h3 class="text-sm font-bold text-slate-900 mb-3">Unit Owner</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6 mb-6">
                <div>
                  <p class="text-xs font-normal text-slate-400">Full Name</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($ownerNameDisplay) ?> <span
                      class="text-xs font-semibold text-blue-600">(You)</span></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Email</p>
                  <p class="text-sm font-bold text-slate-900 mt-1">
                    <a href="mailto:<?= clean($ownerEmailDisplay) ?>"
                      class="underline hover:text-blue-600 transition-colors"><?= clean($ownerEmailDisplay) ?></a>
                  </p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Phone Number</p>
                  <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= clean($ownerPhoneDisplay) ?></p>
                </div>
              </div>

              <!-- Unit Specifications -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
                <div>
                  <p class="text-xs font-normal text-slate-400">Unit</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($unitSpecificationText) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Floor</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($floorDisplay) ?></p>
                </div>
                <div>
                  <p class="text-xs font-normal text-slate-400">Floor Area</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($unitSqmDisplay) ?></p>
                </div>

                <div>
                  <p class="text-xs font-normal text-slate-400">Listing</p>
                  <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($listingDisplay) ?></p>
                </div>
                <?php if ($isResale): ?>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Resale Price</p>
                    <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= clean($resalePriceDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Furnishing</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($clientFurnishing) ?></p>
                  </div>
                <?php else: ?>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Lease Rate</p>
                    <p class="text-sm font-bold text-slate-900 mt-1 font-mono"><?= clean($leaseRateDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Lease term duration</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($leaseTermDuration) ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!$isResale): ?>
              <div class="border-t border-slate-100 my-6"></div>

              <!-- Schedule Section (Lease Commencement and Expiration) -->
              <div>
                <h3 class="text-sm font-bold text-slate-900 mb-3">Lease commencement and Expiration</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 sm:gap-y-5 gap-x-6">
                  <div>
                    <p class="text-xs font-normal text-slate-400">Move in Date</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($moveInDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Move out Date</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($moveOutDisplay) ?></p>
                  </div>
                  <div>
                    <p class="text-xs font-normal text-slate-400">Lease Duration</p>
                    <p class="text-sm font-bold text-slate-900 mt-1"><?= clean($computedLeaseDuration) ?></p>
                  </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100">
                  <p class="text-xs font-normal text-slate-400">Remarks / Client Message</p>
                  <div
                    class="mt-1.5 p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-xs sm:text-sm text-slate-700 leading-relaxed">
                    <?php if (!empty($res['client_remarks'])): ?>
                      <p class="font-medium text-slate-800"><?= nl2br(clean($res['client_remarks'])) ?></p>
                    <?php else: ?>
                      <p class="text-slate-400 italic">No special remarks or requests submitted by applicant.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- TAB 2: PAYMENT -->
          <div id="tabContent-payment" class="tab-panel space-y-6 hidden">
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
                    <p class="text-xs text-slate-500">Applicant downpayment details and owner verification status</p>
                  </div>
                </div>
              </div>

              <!-- Proof & Status Cards -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if ($isInHousePayment): ?>
                  <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                      <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Payment Method</span>
                        <span
                          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">In-House
                          Down Payment</span>
                      </div>
                      <div class="mt-2">
                        <p class="text-xs text-slate-500">Downpayment Required</p>
                        <p class="text-base font-bold text-slate-900 font-mono mt-0.5"><?= peso($downpaymentAmount) ?></p>
                      </div>
                      <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        The applicant selected <strong>Pay In-House</strong>. No electronic proof upload is required. The
                        down payment is to be settled directly in cash or check.
                      </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-200/60 text-xs text-slate-500">
                      Settlement Method: <span class="font-semibold text-slate-700">Cash / Check (Direct)</span>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                      <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Uploaded Proof of
                          Payment</span>
                        <span
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"><?= clean($paymentMethod) ?></span>
                      </div>
                      <div class="mt-2">
                        <p class="text-xs text-slate-500">Downpayment Required</p>
                        <p class="text-base font-bold text-slate-900 font-mono mt-0.5"><?= peso($downpaymentAmount) ?></p>
                      </div>
                      <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Check your personal GCash account to verify receipt of this down payment before confirming.
                      </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between gap-2">
                      <?php if (!empty($proofUrl)): ?>
                        <a href="<?= clean($proofUrl) ?>" target="_blank" rel="noopener noreferrer"
                          class="btn-press inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <?php if (!empty($res['payment_verified_at'])): ?>
                          <span class="text-xs text-slate-500 font-mono">(Verified:
                            <?= clean(format_datetime_text($res['payment_verified_at'])) ?>)</span>
                        <?php endif; ?>
                      <?php elseif ($paymentStatusLower === 'rejected'): ?>
                        <span
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                          Payment Not Received
                        </span>
                        <?php if (!empty($res['payment_rejected_at'])): ?>
                          <span class="text-xs text-slate-500 font-mono">(Rejected:
                            <?= clean(format_datetime_text($res['payment_rejected_at'])) ?>)</span>
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
                      <span class="text-emerald-700 font-semibold">✓ Payment complete and confirmed in your account</span>
                    <?php elseif ($paymentStatusLower === 'rejected'): ?>
                      <span class="text-red-700 font-semibold">✕ Payment not received — unit released</span>
                    <?php else: ?>
                      <span class="text-amber-700 font-semibold">●
                        <?= $isInHousePayment ? 'Payment incomplete — awaiting in-house settlement' : 'Payment incomplete — awaiting receipt in your GCash account' ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Action Buttons for Verification -->
              <?php if ($paymentStatusLower === 'verified'): ?>
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
                  <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment Complete</p>
                    <p class="text-sm font-medium text-emerald-900 mt-0.5">
                      <?= $isInHousePayment
                        ? 'You have marked the in-house down payment of ' . peso($downpaymentAmount) . ' as received. Requirement tracking is active in the Documents tab.'
                        : 'You have verified receipt of the GCash down payment of ' . peso($downpaymentAmount) . ' in your account. Requirement tracking is active in the Documents tab.' ?>
                    </p>
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
                      <p class="text-xs text-red-800 mt-1 italic">Remarks: <?= clean($res['admin_payment_remarks']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                  <button type="button" id="btnVerifyPayment"
                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-3 rounded-xl shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Payment Received — Complete
                  </button>
                  <button type="button" id="btnRejectPayment"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-3 rounded-xl shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Not Received / Reject
                  </button>
                </div>
              <?php endif; ?>
            </section>
          </div>

          <!-- TAB 3: LEASE SIGNING -->
          <div id="tabContent-lease-signing" class="tab-panel space-y-6 hidden">
            <section class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
              <div class="flex items-center justify-between flex-wrap gap-4 mb-6 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Lease Signing</h2>
                    <p class="text-xs text-slate-500">Contract execution schedule and completion status</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <?php if ($isSigningCompleted): ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                      <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                      Signing Completed
                    </span>
                  <?php elseif ($hasConfirmedSchedule): ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                      <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                      Date Confirmed
                    </span>
                  <?php else: ?>
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                      <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                      Pending Signing
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Schedule & Details -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

                <!-- Left Box: Chosen Lease Signing Date -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
                  <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Chosen Lease Signing Date
                    </p>
                    <?php if ($hasConfirmedSchedule): ?>
                      <span
                        class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                        Confirmed
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Confirmed View -->
                  <div id="confirmedDateView" class="<?= $hasConfirmedSchedule ? '' : 'hidden' ?>">
                    <div class="flex items-baseline gap-2 flex-wrap">
                      <h3 class="text-xl sm:text-2xl font-bold text-slate-900 font-mono tracking-tight">
                        <?= clean($confirmedDateDisplay) ?>
                      </h3>
                      <button type="button" onclick="showDateChooser(true)"
                        class="text-xs font-semibold text-blue-600 hover:underline">
                        Change
                      </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Confirmed signing date with tenant.</p>
                  </div>

                  <!-- Select Date View (if not confirmed or changing) -->
                  <div id="selectDateView" class="<?= $hasConfirmedSchedule ? 'hidden' : '' ?> space-y-2.5">
                    <label class="block text-xs font-medium text-slate-600">
                      <?= !empty($preferredDatesList) && count($preferredDatesList) > 1 ? "Select signing date from tenant's options:" : "Choose lease signing date:" ?>
                    </label>

                    <div class="flex items-center gap-2 flex-wrap">
                      <?php if (!empty($preferredDatesList)): ?>
                        <select id="signingDateSelect" onchange="onDateSelectChanged(this.value)"
                          class="text-xs sm:text-sm font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900">
                          <?php foreach ($preferredDatesList as $p): ?>
                            <option value="<?= $p['date'] ?>" <?= ($p['date'] === ($confirmedSigningDate ?? '')) ? 'selected' : '' ?>>
                              <?= $p['full_text'] ?> (<?= $p['day_name'] ?>)
                            </option>
                          <?php endforeach; ?>
                          <option value="custom">Other date...</option>
                        </select>
                      <?php endif; ?>

                      <input type="date" id="customSigningDateInput" min="<?= date('Y-m-d') ?>"
                        <?= !empty($res['move_in_date']) && $res['move_in_date'] !== '0000-00-00' ? 'max="' . htmlspecialchars($res['move_in_date']) . '"' : '' ?>
                        value="<?= htmlspecialchars($confirmedSigningDate ?? ($preferredDatesList[0]['date'] ?? '')) ?>"
                        class="<?= !empty($preferredDatesList) ? 'hidden ' : '' ?>text-xs sm:text-sm font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900">

                      <button type="button" id="btnSaveSimpleDate" onclick="saveSimpleSigningDate()"
                        class="btn-press px-4 py-2 bg-[#0f172a] hover:bg-[#1e293b] active:scale-95 text-white text-xs font-bold rounded-xl transition-all shrink-0">
                        Save Date
                      </button>

                      <?php if ($hasConfirmedSchedule): ?>
                        <button type="button" onclick="showDateChooser(false)"
                          class="text-xs text-slate-400 hover:text-slate-600 px-1">
                          Cancel
                        </button>
                      <?php endif; ?>
                    </div>

                    <p class="text-[11px] text-slate-400">
                      <?= $isFlexibleSigning ? 'Tenant requested flexible signing before move-in.' : 'Must be scheduled on or before move-in date.' ?>
                    </p>
                  </div>

                  <div class="pt-3 border-t border-slate-200/70 text-xs">
                    <span class="text-slate-400 block mb-0.5">Move-in Date:</span>
                    <span class="font-bold text-slate-900"><?= clean($moveInDisplay) ?></span>
                  </div>
                </div>

                <!-- Right Box: Signer & Unit Details -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 space-y-4">
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Signer &amp; Unit Details</p>
                  <div class="space-y-2.5 text-xs sm:text-sm">
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 font-medium">Tenant / Applicant:</span>
                      <span class="font-bold text-slate-900 text-right"><?= clean($res['client_name']) ?></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 font-medium">Contact Number:</span>
                      <span
                        class="font-bold text-slate-900 font-mono text-right"><?= clean($res['client_contact'] ?: ($res['client_user_contact'] ?? '—')) ?></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-slate-400 font-medium">Assigned Unit:</span>
                      <span class="font-bold text-slate-900 text-right"><?= clean($unitSpecificationText) ?></span>
                    </div>
                  </div>
                </div>

              </div>

              <!-- Signing Action Bar -->
              <?php if ($isSigningCompleted): ?>
                <div
                  class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div class="flex items-start gap-3.5">
                    <div
                      class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-emerald-900">Lease Signing Completed &amp; Executed</h4>
                      <p class="text-xs text-emerald-700 mt-0.5">
                        Lease contract was marked as completed on
                        <strong><?= clean(format_datetime_text($res['lease_signed_at'] ?? '')) ?></strong>.
                      </p>
                    </div>
                  </div>
                  <button type="button" onclick="openSigningModal('reset')"
                    class="btn-press text-xs font-semibold text-slate-500 hover:text-slate-800 bg-white border border-slate-200 rounded-xl px-4 py-2 hover:bg-slate-50 shadow-2xs transition-all shrink-0">
                    Reset Status
                  </button>
                </div>
              <?php else: ?>
                <div
                  class="rounded-xl border border-slate-200 bg-slate-50 p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900">Finalize &amp; Complete Lease Signing</h4>
                    <p class="text-xs text-slate-500">
                      Once the lease contract agreement has been formally signed, click below to mark the signing
                      appointment as complete.
                    </p>
                  </div>
                  <button type="button" onclick="openSigningModal('complete')"
                    class="btn-press px-5 py-2.5 bg-[#0f172a] hover:bg-[#1e293b] active:scale-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md flex items-center gap-2 transition-all shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Complete Lease Signing
                  </button>
                </div>
              <?php endif; ?>
            </section>
          </div>

          <!-- TAB 4: DOCUMENTS -->
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
                    <p class="text-xs text-slate-500">Track and update applicant requirement submissions</p>
                  </div>
                </div>
                <button type="button" id="btnEditDocuments"
                  class="hidden btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3.5 py-1.5 rounded-full active:scale-95 transition-all">
                  Edit Documents
                </button>
              </div>

              <input type="hidden" id="process_reservation_id" value="<?= clean($res['reservation_id']) ?>">

              <div id="requirementDecisionDisplay" class="hidden mb-5 rounded-xl border px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wide mb-1" id="requirementDecisionLabel">Requirement
                  Status</p>
                <p class="text-sm font-semibold" id="requirementDecisionText">—</p>
              </div>

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

              <button type="button" id="btnSaveDocuments"
                class="hidden mt-5 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-sm active:scale-98">
                Save Documents
              </button>
            </section>
          </div>

        </div>
      </div>
    </main>
  </div>

  <!-- OWNER CANCELLATION REQUEST MODAL -->
  <div id="ownerCancelRequestModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
      <div class="bg-red-600 px-6 py-4">
        <h2 class="text-lg font-bold text-white">Request Cancellation?</h2>
        <p class="text-sm text-red-50 mt-1">Admin approval is required.</p>
      </div>
      <div class="p-6">
        <p class="text-sm text-slate-700 leading-relaxed">
          This will not cancel the reservation immediately. It will send a formal cancellation request to the admin for
          review.
        </p>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1.5">
          Reason for Cancellation <span class="text-red-500">*</span>
        </label>
        <textarea id="ownerCancelReason" rows="4" placeholder="Enter reason for requesting cancellation..."
          class="zep-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 resize-none"></textarea>
      </div>
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" onclick="closeOwnerCancelRequestModal()"
          class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">Close</button>
        <button type="button" onclick="submitOwnerCancelRequest()"
          class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">Submit
          Request</button>
      </div>
    </div>
  </div>

  <!-- PAYMENT COMPLETION MODAL -->
  <div id="verifyPaymentModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div
      class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
          Confirm Payment Completion
        </h3>
        <button type="button" onclick="closePaymentConfirmModal('verifyPaymentModal')"
          class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">✕</button>
      </div>
      <div class="p-6">
        <p class="text-sm text-slate-600 leading-relaxed">
          <?php if ($isInHousePayment): ?>
            Confirm that you have collected and received the in-house down payment of
            <strong><?= peso($downpaymentAmount) ?></strong> from the applicant in cash or check.
          <?php else: ?>
            Confirm that you have checked your personal GCash account and verified receipt of the down payment of
            <strong><?= peso($downpaymentAmount) ?></strong>.
          <?php endif; ?>
        </p>
      </div>
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closePaymentConfirmModal('verifyPaymentModal')"
          class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl">Cancel</button>
        <button type="button" onclick="confirmPaymentAction('verify')"
          class="btn-press px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">Confirm
          &amp; Complete</button>
      </div>
    </div>
  </div>

  <!-- PAYMENT NOT RECEIVED / REJECT MODAL -->
  <div id="rejectPaymentModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div
      class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
          Payment Not Received / Reject
        </h3>
        <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')"
          class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">✕</button>
      </div>
      <div class="p-6 space-y-4">
        <p class="text-sm text-slate-600 leading-relaxed">
          <?php if ($isInHousePayment): ?>
            Are you sure the in-house down payment was not settled? This will close the reservation and release the unit
            back to availability so other clients can inquire.
          <?php else: ?>
            Are you sure this GCash payment was not received in your account? This will close the reservation and release
            the unit back to availability.
          <?php endif; ?>
        </p>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Reason for Rejection
            <span class="text-red-500">*</span></label>
          <textarea id="rejectPaymentRemarks" rows="3"
            placeholder="<?= $isInHousePayment ? 'e.g. Client did not settle payment within the reservation window.' : 'e.g. Reference number invalid and no payment was received in GCash.' ?>"
            class="zep-input w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" required></textarea>
        </div>
      </div>
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closePaymentConfirmModal('rejectPaymentModal')"
          class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl">Cancel</button>
        <button type="button" onclick="confirmPaymentAction('reject')"
          class="btn-press px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">Confirm
          Rejection</button>
      </div>
    </div>
  </div>

  <!-- Lease Signing Action Modal -->
  <div id="leaseSigningModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-100">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-900" id="signingModalTitle">Confirm Lease Signing Completion</h3>
        <button type="button" onclick="closeSigningModal()" class="text-slate-400 hover:text-slate-700">✕</button>
      </div>
      <p class="text-xs text-slate-600 leading-relaxed" id="signingModalDesc">
        Are you sure you want to mark this lease signing as completed?
      </p>
      <input type="hidden" id="signingActionInput" value="complete">
      <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeSigningModal()"
          class="px-4 py-2 border border-slate-200 hover:bg-slate-50 rounded-xl text-xs font-semibold text-slate-700">Cancel</button>
        <button type="button" id="btnConfirmSigning" onclick="submitLeaseSigningAction()"
          class="px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold transition-all">Confirm</button>
      </div>
    </div>
  </div>

  <script>
    const currentReservationId = <?= json_encode((int) ($res['reservation_id'] ?? 0)) ?>;
    const currentReservationStatus = <?= json_encode($res['reservation_status'] ?? '') ?>;
    const currentPaymentStatus = <?= json_encode($res['payment_status'] ?? '') ?>;
    const appBaseUrl = <?= json_encode($baseUrl) ?>;

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

    function openPaymentConfirmModal(action) {
      if (action === 'verify') {
        const m = document.getElementById('verifyPaymentModal');
        m?.classList.remove('hidden');
        m?.classList.add('flex');
      }
      if (action === 'reject') {
        const box = document.getElementById('rejectPaymentRemarks');
        if (box) box.value = '';
        const m = document.getElementById('rejectPaymentModal');
        m?.classList.remove('hidden');
        m?.classList.add('flex');
      }
    }

    function closePaymentConfirmModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }

    function confirmPaymentAction(action) {
      let remarks = '';
      if (action === 'verify') {
        closePaymentConfirmModal('verifyPaymentModal');
      }
      if (action === 'reject') {
        let shouldReloadOnOwnerStatusModalClose = false;

        function showOwnerReservationStatusModal(isSuccess, title, message, reloadOnClose = false) {
          shouldReloadOnOwnerStatusModalClose = reloadOnClose;
          const modal = document.getElementById('ownerReservationStatusModal');
          const iconContainer = document.getElementById('ownerReservationStatusIcon');
          const titleEl = document.getElementById('ownerReservationStatusTitle');
          const msgEl = document.getElementById('ownerReservationStatusMessage');
          const btn = document.getElementById('ownerReservationStatusBtn');

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

        function closeOwnerReservationStatusModal() {
          const modal = document.getElementById('ownerReservationStatusModal');
          if (!modal || !modal.classList.contains('open')) return;
          modal.classList.remove('open');
          setTimeout(() => {
            if (!modal.classList.contains('open')) {
              modal.classList.add('hidden');
            }
            if (shouldReloadOnOwnerStatusModalClose) {
              window.location.reload();
            }
          }, 250);
        }

        function handleOwnerReservationStatusConfirm() {
          closeOwnerReservationStatusModal();
        }

        function handleOwnerReservationStatusModalBackdrop(e) {
          if (e.target === document.getElementById('ownerReservationStatusModal')) {
            closeOwnerReservationStatusModal();
          }
        }

        function confirmPaymentAction(action) {
          let remarks = '';

          if (action === 'verify') {
            remarks = document.getElementById('verifyPaymentRemarks')?.value.trim() || '';
            closePaymentConfirmModal('verifyPaymentModal');
          }
          if (action === 'flag') {
            remarks = document.getElementById('flagPaymentRemarks')?.value.trim() || '';
            if (remarks === '') {
              showOwnerReservationStatusModal(false, 'Missing Reason', 'Please enter a reason for flagging this payment.');
              return;
            }
            closePaymentConfirmModal('flagPaymentModal');
          }
          if (action === 'reject') {
            remarks = document.getElementById('rejectPaymentRemarks')?.value.trim() || '';
            if (remarks === '') {
              showOwnerReservationStatusModal(false, 'Missing Reason', 'Please enter a reason for rejecting the payment.');
              return;
            }
            closePaymentConfirmModal('rejectPaymentModal');
          }

          const formData = new FormData();
          formData.append('reservation_id', currentReservationId);
          formData.append('action', action);
          formData.append('remarks', remarks);

          fetch(appBaseUrl + '/owner/reservations/verify-payment', {
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
                showOwnerReservationStatusModal(true, title, data.message || 'Payment status updated successfully.', true);
              } else {
                showOwnerReservationStatusModal(false, 'Update Failed', data.message || 'Unable to update payment status.');
              }
            })
            .catch(error => {
              console.error(error);
              showOwnerReservationStatusModal(false, 'Network Error', 'Something went wrong while updating payment status.');
            });
        }

        document.getElementById('btnVerifyPayment')?.addEventListener('click', () => openPaymentConfirmModal('verify'));
        document.getElementById('btnRejectPayment')?.addEventListener('click', () => openPaymentConfirmModal('reject'));

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

          fetch(appBaseUrl + '/unitOwnerPages/ActionsUOP/verifyOwnerPayment.php', {
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
              alert('Something went wrong while completing in-house payment.');
            });
        }

        document.getElementById('btnCompleteInHousePayment')?.addEventListener('click', openInHousePaymentModal);

        // Document Tracking
        let currentDocuments = [];
        let documentsEditMode = false;

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
          return '—';
        }

        function updateRequirementSectionUI(paymentStatus, reservationStatus) {
          const payment = (paymentStatus || '').toLowerCase();
          const status = (reservationStatus || '').toLowerCase();

          const section = document.getElementById('requirementTrackingSection');
          const display = document.getElementById('requirementDecisionDisplay');
          const label = document.getElementById('requirementDecisionLabel');
          const text = document.getElementById('requirementDecisionText');
          const editBtn = document.getElementById('btnEditDocuments');
          const saveBtn = document.getElementById('btnSaveDocuments');

          if (!section || !display || !label || !text || !editBtn || !saveBtn) return;

          display.className = 'hidden mb-5 rounded-xl border px-4 py-3';
          section.classList.remove('hidden');

          if (status === 'requirements completed') {
            documentsEditMode = false;
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            display.classList.remove('hidden');
            display.classList.add('bg-emerald-50', 'border-emerald-200');
            label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
            text.className = 'text-sm font-semibold text-emerald-800';
            label.textContent = 'Requirements Completed';
            text.textContent = 'All reservation documents have been completed. You may edit tracking if needed.';
            return;
          }

          if (status === 'reserved') {
            documentsEditMode = false;
            editBtn.classList.add('hidden');
            saveBtn.classList.add('hidden');
            display.classList.remove('hidden');
            display.classList.add('bg-emerald-50', 'border-emerald-200');
            label.className = 'text-xs font-bold uppercase tracking-wide mb-1 text-emerald-700';
            text.className = 'text-sm font-semibold text-emerald-800';
            label.textContent = 'Officially Booked';
            text.textContent = 'This reservation is already officially booked.';
            return;
          }

          documentsEditMode = true;
          editBtn.classList.add('hidden');
          saveBtn.classList.remove('hidden');
          display.classList.add('hidden');
        }

        function loadDocuments(reservationId) {
          const tbody = document.getElementById('documentsTableBody');
          if (!tbody) return;

          fetch(appBaseUrl + '/owner/reservations/documents?reservation_id=' + encodeURIComponent(reservationId))
            .then(response => response.json())
            .then(data => {
              if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-red-500">' + escapeHtml(data.message || 'Failed to load documents.') + '</td></tr>';
                return;
              }

              currentDocuments = data.documents || [];
              renderDocumentsTable();
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
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No documents found.</td></tr>';
            return;
          }

          tbody.innerHTML = currentDocuments.map(doc => {
            const hasLink = (doc.document_link && doc.document_link.trim().length > 0);
            const statusBadge = hasLink
              ? "<span class='doc-row-status inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200'>Complete</span>"
              : "<span class='doc-row-status inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200'>Pending</span>";

            if (!documentsEditMode) {
              const linkCell = hasLink
                ? `<a href="${escapeHtmlAttr(doc.document_link)}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-600 hover:underline inline-flex items-center gap-1"><span>View Link</span><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`
                : "<span class='text-xs text-slate-400'>—</span>";

              return `
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="px-4 py-3.5 font-medium text-slate-800">${escapeHtml(doc.document_name)}</td>
            <td class="px-4 py-3.5 doc-status-cell">${statusBadge}</td>
            <td class="px-4 py-3.5 text-slate-600">${escapeHtml(storageDisplayLabel(doc))}</td>
            <td class="px-4 py-3.5">${linkCell}</td>
          </tr>
        `;
            }

            const isOther = doc.storage === 'other';

            return `
        <tr data-document-id="${doc.document_id}" class="hover:bg-slate-50/70 transition-colors">
          <td class="px-4 py-3.5 font-medium text-slate-800">${escapeHtml(doc.document_name)}</td>
          <td class="px-4 py-3.5 doc-status-cell">
            ${statusBadge}
          </td>
          <td class="px-4 py-3.5">
            <div class="flex flex-col gap-1.5">
              <select class="doc-storage-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
                <option value="gdrive" ${(!doc.storage || doc.storage === 'gdrive') ? 'selected' : ''}>Google Drive</option>
                <option value="dropbox" ${doc.storage === 'dropbox' ? 'selected' : ''}>Dropbox</option>
                <option value="other" ${isOther ? 'selected' : ''}>Other</option>
              </select>
              <input type="text" class="doc-storage-other-input text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white ${isOther ? '' : 'hidden'}" placeholder="Storage name" value="${escapeHtmlAttr(doc.storage_other_label || '')}">
            </div>
          </td>
          <td class="px-4 py-3.5">
            <input type="url" class="doc-link-input w-full text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white font-mono placeholder:font-sans placeholder:text-slate-400 focus:border-slate-900 focus:outline-none" placeholder="https://..." value="${escapeHtmlAttr(doc.document_link || '')}">
          </td>
        </tr>
      `;
          }).join('');

          if (documentsEditMode) {
            tbody.querySelectorAll('.doc-storage-input').forEach(select => {
              select.addEventListener('change', function () {
                const otherInput = this.closest('td').querySelector('.doc-storage-other-input');
                if (!otherInput) return;
                if (this.value === 'other') {
                  otherInput.classList.remove('hidden');
                } else {
                  otherInput.classList.add('hidden');
                  otherInput.value = '';
                }
              });
            });

            // Reactive update of Status badge based on link typing
            tbody.querySelectorAll('.doc-link-input').forEach(input => {
              const updateRowBadge = () => {
                const row = input.closest('tr');
                const statusCell = row?.querySelector('.doc-status-cell');
                if (!statusCell) return;
                const val = input.value.trim();
                if (val.length > 0) {
                  statusCell.innerHTML = "<span class='doc-row-status inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200'>Complete</span>";
                } else {
                  statusCell.innerHTML = "<span class='doc-row-status inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200'>Pending</span>";
                }
              };

              input.addEventListener('input', updateRowBadge);
              input.addEventListener('change', updateRowBadge);
            });
          }
        }

        document.getElementById('btnEditDocuments')?.addEventListener('click', function () {
          documentsEditMode = true;
          renderDocumentsTable();
          document.getElementById('btnEditDocuments')?.classList.add('hidden');
          document.getElementById('btnSaveDocuments')?.classList.remove('hidden');
        });

        function collectDocumentsPayload() {
          const rows = document.querySelectorAll('#documentsTableBody tr[data-document-id]');
          const payload = [];
          rows.forEach(row => {
            const linkVal = row.querySelector('.doc-link-input')?.value.trim() || '';
            payload.push({
              document_id: row.dataset.documentId,
              status: (linkVal.length > 0) ? 'complete' : 'pending',
              storage: row.querySelector('.doc-storage-input')?.value || '',
              storage_other_label: row.querySelector('.doc-storage-other-input')?.value || '',
              document_link: linkVal
            });
          });
          return payload;
        }

        function saveDocuments() {
          const documents = collectDocumentsPayload();
          if (!documents.length) {
            showOwnerReservationStatusModal(false, 'No Documents', 'No documents to save.');
            return;
          }

          const formData = new FormData();
          formData.append('reservation_id', currentReservationId);
          formData.append('documents', JSON.stringify(documents));

          fetch(appBaseUrl + '/owner/reservations/documents', {
            method: 'POST',
            body: formData
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                const title = data.all_completed ? 'Documents Completed' : 'Documents Updated';
                const msg = data.message || (data.all_completed
                  ? 'All reservation documents have been completed successfully. You may now mark this reservation as officially booked.'
                  : 'Document tracking updated successfully.');
                showOwnerReservationStatusModal(true, title, msg, true);
              } else {
                showOwnerReservationStatusModal(false, 'Update Failed', data.message || 'Unable to save document tracking.');
              }
            })
            .catch(error => {
              console.error(error);
              showOwnerReservationStatusModal(false, 'Network Error', 'Something went wrong while saving document tracking.');
            });
        }

        document.getElementById('btnSaveDocuments')?.addEventListener('click', saveDocuments);

        function openOwnerCancelRequestModal() {
          const reasonBox = document.getElementById('ownerCancelReason');
          const modal = document.getElementById('ownerCancelRequestModal');
          if (reasonBox) reasonBox.value = '';
          modal?.classList.remove('hidden');
          modal?.classList.add('flex');
        }

        function closeOwnerCancelRequestModal() {
          const modal = document.getElementById('ownerCancelRequestModal');
          modal?.classList.add('hidden');
          modal?.classList.remove('flex');
        }

        function submitOwnerCancelRequest() {
          const reason = document.getElementById('ownerCancelReason')?.value.trim();
          if (!reason) {
            alert('Cancellation reason is required.');
            return;
          }

          const formData = new FormData();
          formData.append('reservation_id', currentReservationId);
          formData.append('reason', reason);

          fetch(appBaseUrl + '/owner/reservations/request-cancellation', {
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
              alert('Something went wrong while requesting cancellation.');
            });
        }

        function showDateChooser(show) {
          const confirmedView = document.getElementById('confirmedDateView');
          const selectView = document.getElementById('selectDateView');
          if (show) {
            if (confirmedView) confirmedView.classList.add('hidden');
            if (selectView) selectView.classList.remove('hidden');
          } else {
            if (confirmedView) confirmedView.classList.remove('hidden');
            if (selectView) selectView.classList.add('hidden');
          }
        }

        function onDateSelectChanged(val) {
          const customInput = document.getElementById('customSigningDateInput');
          if (!customInput) return;
          if (val === 'custom') {
            customInput.classList.remove('hidden');
            customInput.focus();
          } else {
            customInput.classList.add('hidden');
            customInput.value = val;
          }
        }

        async function saveSimpleSigningDate() {
          const select = document.getElementById('signingDateSelect');
          const customInput = document.getElementById('customSigningDateInput');
          let chosenDate = select ? select.value : '';
          if (chosenDate === 'custom' || !select) {
            chosenDate = customInput ? customInput.value : '';
          }

          if (!chosenDate) {
            alert('Please select a signing date.');
            return;
          }

          const btn = document.getElementById('btnSaveSimpleDate');
          if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving...';
          }

          try {
            const formData = new FormData();
            formData.append('reservation_id', currentReservationId);
            formData.append('confirmed_date', chosenDate);

            const res = await fetch(appBaseUrl + '/owner/reservations/confirm-signing-date', {
              method: 'POST',
              body: formData
            });
            const data = await res.json();
            if (data.success) {
              window.location.reload();
            } else {
              alert(data.message || 'Failed to save date.');
              if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Date';
              }
            }
          } catch (err) {
            console.error(err);
            alert('Network error while saving signing date.');
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Save Date';
            }
          }
        }

        function openSigningModal(action) {
          const modal = document.getElementById('leaseSigningModal');
          const actionInput = document.getElementById('signingActionInput');
          const title = document.getElementById('signingModalTitle');
          const desc = document.getElementById('signingModalDesc');
          const btn = document.getElementById('btnConfirmSigning');

          actionInput.value = action;

          if (action === 'complete') {
            title.textContent = 'Complete Lease Signing';
            desc.textContent = 'Are you sure you want to mark this lease signing as completed?';
            btn.textContent = 'Complete Signing';
            btn.className = 'px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-xl text-xs font-bold transition-all';
          } else {
            title.textContent = 'Reset Lease Signing Status';
            desc.textContent = 'Are you sure you want to reset the lease signing status back to Pending Signing?';
            btn.textContent = 'Reset Status';
            btn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-all';
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

            const res = await fetch(appBaseUrl + '/owner/reservations/lease-signing', {
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

        document.addEventListener('DOMContentLoaded', function () {
          updateRequirementSectionUI(currentPaymentStatus, currentReservationStatus);
          loadDocuments(currentReservationId);

          const hash = (window.location.hash || '').replace('#', '').toLowerCase();
          if (['lease', 'payment', 'lease-signing', 'documents'].includes(hash)) {
            switchReservationTab(hash);
          } else {
            switchReservationTab('lease');
          }
          document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
              const statusModal = document.getElementById('ownerReservationStatusModal');
              if (statusModal && statusModal.classList.contains('open')) {
                closeOwnerReservationStatusModal();
              }
            }
          });
  </script>

  <!-- STYLIZED OWNER RESERVATION STATUS NOTIFICATION MODAL -->
  <div id="ownerReservationStatusModal"
    class="modal-backdrop fixed inset-0 z-[100] bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 hidden"
    onclick="handleOwnerReservationStatusModalBackdrop(event)">
    <div
      class="modal-card bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-7 max-w-sm w-full text-center"
      onclick="event.stopPropagation()">
      <!-- Icon Container -->
      <div id="ownerReservationStatusIcon"
        class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 border transition-all">
      </div>

      <!-- Title -->
      <h3 class="text-base sm:text-lg font-bold text-slate-900 mb-1" id="ownerReservationStatusTitle">Notification</h3>

      <!-- Message Body -->
      <p class="text-xs text-slate-500 mb-6 leading-relaxed" id="ownerReservationStatusMessage"></p>

      <!-- Action Button -->
      <button type="button" id="ownerReservationStatusBtn" onclick="handleOwnerReservationStatusConfirm()"
        class="btn-press w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-all shadow-sm active:scale-95">
        Continue
      </button>
    </div>
  </div>
</body>

</html>