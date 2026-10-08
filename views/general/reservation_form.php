<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Reservation Form View
 *
 * @var array  $data
 * @var bool   $owner_has_qr
 * @var string $owner_qr_path
 * @var bool   $is_lease
 * @var float  $price_basis
 * @var string $price_label
 * @var string $transaction_type
 * @var string $resident_type
 * @var string $reservation_type
 * @var int    $lease_months
 * @var string $max_signing_date
 * @var array  $blocked_ranges
 * @var string $token
 * @var string $baseUrl
 * @var string $pageTitle
 * @var string $activePage
 */

// Format unit numbers and values safely from controller payload
$selectedUnitData = !empty($data['data']) && is_array($data['data']) ? $data['data'] : (isset($data['unit']) && is_array($data['unit']) ? $data['unit'] : (is_array($data) ? $data : []));
$is_lease = isset($is_lease) 
    ? (bool)$is_lease 
    : (!empty($data['is_lease']) 
        ? (bool)$data['is_lease'] 
        : (strpos(strtolower((string)($selectedUnitData['listing_type'] ?? '')), 'resale') === false && strpos(strtolower((string)($selectedUnitData['inquiry_type'] ?? '')), 'resale') === false));
$approvedUnitsList = !empty($approved_units) ? $approved_units : (!empty($data['approved_units']) ? $data['approved_units'] : []);
$selectedUnitId = (int)($selectedUnitData['unit_id'] ?? ($selectedUnitData['approved_unit_id'] ?? 0));

if (empty($approvedUnitsList)) {
    $approvedUnitsList[] = [
        'unit_id' => $selectedUnitId,
        'unit_number' => (string)($selectedUnitData['unit_number'] ?? '—'),
        'unit_type' => (string)($selectedUnitData['unit_type'] ?? 'Studio Type'),
        'floor_number' => (string)($selectedUnitData['floor_number'] ?? '1'),
        'sqm' => (string)($selectedUnitData['sqm'] ?? '37'),
        'furnishing' => (string)($selectedUnitData['furnishing'] ?? 'Fully Furnished'),
        'listing_type' => (string)($selectedUnitData['listing_type'] ?? 'For Lease'),
        'owner_name' => (string)($selectedUnitData['owner_name'] ?? 'Unit Owner'),
        'owner_email' => (string)($selectedUnitData['owner_email'] ?? ''),
        'owner_contact' => (string)($selectedUnitData['owner_contact'] ?? '—'),
        'price_basis' => (float)$price_basis,
        'price_label' => (string)$price_label,
        'formatted_price_basis' => '₱' . number_format((float)$price_basis, 2),
        'dp_amount_35' => number_format((float)$price_basis * 0.35, 2),
        'dp_amount_50' => number_format((float)$price_basis * 0.50, 2),
        'dp_amount_75' => number_format((float)$price_basis * 0.75, 2),
        'owner_has_qr' => (bool)$owner_has_qr,
        'owner_qr_path' => (string)$owner_qr_path,
        'dropdown_label' => ($selectedUnitData['unit_number'] ?? 'Unit') . ' (' . ($selectedUnitData['unit_type'] ?? 'Unit') . ') - ₱' . number_format((float)$price_basis, 0) . ' (' . ($selectedUnitData['owner_name'] ?? 'Owner') . ')',
    ];
}

$unitNum = !empty($selectedUnitData['unit_number']) ? htmlspecialchars((string)$selectedUnitData['unit_number']) : '—';
$unitTypeUpper = !empty($selectedUnitData['unit_type']) ? strtoupper(htmlspecialchars((string)$selectedUnitData['unit_type'])) : 'STUDIO TYPE';
$floorNum = !empty($selectedUnitData['floor_number']) ? htmlspecialchars((string)$selectedUnitData['floor_number']) : '1';
$sqmVal = !empty($selectedUnitData['sqm']) ? htmlspecialchars((string)$selectedUnitData['sqm']) : '37';
$furnishingVal = !empty($selectedUnitData['furnishing']) ? htmlspecialchars((string)$selectedUnitData['furnishing']) : 'Fully Furnished.';
$listingVal = !empty($selectedUnitData['listing_type']) ? htmlspecialchars((string)$selectedUnitData['listing_type']) : 'For Lease';
$ownerName = !empty($selectedUnitData['owner_name']) ? htmlspecialchars((string)$selectedUnitData['owner_name']) : 'No owner assigned';
$ownerEmail = !empty($selectedUnitData['owner_email']) ? htmlspecialchars((string)$selectedUnitData['owner_email']) : '';
$ownerContact = !empty($selectedUnitData['owner_contact']) ? htmlspecialchars((string)$selectedUnitData['owner_contact']) : '—';

// Format client information
$clientName = !empty($selectedUnitData['sender_name']) ? (string)$selectedUnitData['sender_name'] : (!empty($client_name) ? (string)$client_name : (string)($data['client_name'] ?? ''));
$clientEmail = !empty($selectedUnitData['sender_email']) ? (string)$selectedUnitData['sender_email'] : (!empty($client_email) ? (string)$client_email : (string)($data['client_email'] ?? ''));
$clientContact = !empty($selectedUnitData['sender_contact']) ? (string)$selectedUnitData['sender_contact'] : (!empty($client_contact) ? (string)$client_contact : (string)($data['client_contact'] ?? ''));

// Format lease duration display
$inqLeaseDuration = !empty($selectedUnitData['lease_duration']) ? htmlspecialchars((string)$selectedUnitData['lease_duration']) : '1 year';
if (stripos($inqLeaseDuration, 'longer') !== false || stripos($inqLeaseDuration, '3 year') !== false) {
    $inqLeaseDuration = '1 year';
}
$inqPreferredMoveIn = !empty($selectedUnitData['preferred_move_in_time']) ? htmlspecialchars((string)$selectedUnitData['preferred_move_in_time']) : 'Immediately';

// Expiry date for date limits and move-in lead time (3-day buffer)
$maxSigningDate = !empty($max_signing_date) ? $max_signing_date : date('Y-m-d', strtotime('+30 days'));
$move_in_min = date('Y-m-d', strtotime('+3 days'));
$move_in_max = $maxSigningDate;
$signing_min = !$is_lease ? date('Y-m-d', strtotime('+3 days')) : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? ($is_lease ? 'Zeppelin Suites — Unit Lease Reservation' : 'Zeppelin Suites — Unit Resale Reservation')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['DM Sans','sans-serif'],
        mono: ['DM Mono','monospace']
      },
      boxShadow: {
        soft: '0 22px 70px rgba(15, 23, 42, 0.08)'
      },
      colors: {
        navy: {
          900: '#0f172a',
          950: '#090d16'
        }
      }
    }
  }
}
</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.97); }

.zep-hero {
  background:
    radial-gradient(circle at 85% 25%, rgba(59,130,246,0.08), transparent 26%),
    linear-gradient(90deg, #ffffff 0%, #ffffff 58%, #f8fbff 100%);
}

.building-mark {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 230px;
  height: 110px;
  opacity: .09;
  background:
    linear-gradient(90deg, transparent 0 8px, #0f172a 8px 12px, transparent 12px 24px) 0 0/24px 100%,
    linear-gradient(0deg, transparent 0 12px, #0f172a 12px 15px, transparent 15px 28px) 0 0/100% 28px;
  clip-path: polygon(18% 100%, 18% 24%, 42% 24%, 42% 4%, 67% 4%, 67% 42%, 91% 42%, 91% 100%);
}

.form-card-header {
  background:
    radial-gradient(circle at 100% 0%, rgba(29,78,216,.22), transparent 28%),
    linear-gradient(135deg, #091329 0%, #1e293b 100%);
}

input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.6;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
}

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
</style>
</head>

<body class="bg-slate-50 text-slate-900 min-h-screen">

<!-- 1. HERO HEADER -->
<header class="zep-hero relative overflow-hidden border-b border-slate-200 bg-white">
  <div class="building-mark hidden md:block"></div>

  <div class="max-w-[1180px] mx-auto px-5 py-7 md:py-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative">
    <div class="flex items-center gap-7">
      <div class="flex items-center justify-center">
        <img src="<?= htmlspecialchars($baseUrl) ?>/images/condo_photos/zeppelin-logo.png" alt="Zeppelin Suites" style="height:60px;" onerror="this.outerHTML='<span class=\'font-bold text-xl tracking-tight text-zinc-900\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest\'>SUITES</span></span>'">
      </div>

      <div class="hidden sm:block w-px h-16 bg-slate-200"></div>

      <div>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-950">
          <?= $is_lease ? 'Unit Lease Reservation' : 'Unit Resale Reservation' ?>
        </h1>
        <p class="mt-2 text-base md:text-lg text-slate-600">
          Reserve your preferred unit for 30 days.
        </p>
      </div>
    </div>

    <div class="hidden md:flex items-center gap-3 bg-white/70 backdrop-blur-xs border border-slate-200/80 rounded-2xl px-4 py-3 shadow-xs">
      <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
        </svg>
      </div>
      <div>
        <p class="font-bold text-slate-900 text-sm leading-tight">Secure &amp; Confidential</p>
        <p class="text-xs text-slate-500 mt-0.5">Your information is safe with us.</p>
      </div>
    </div>
  </div>
</header>

<!-- MAIN CONTENT CONTAINER -->
<main class="max-w-[1180px] mx-auto px-5 py-8 md:py-10">

  <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,720px)_360px] gap-9 items-start">

    <!-- LEFT CONTENT: STATUS BANNER & FORM CARD -->
    <div class="space-y-8">

      <!-- STATUS BANNER -->
      <div class="status-banner rounded-xl border border-amber-200 bg-amber-50/80 shadow-sm px-5 sm:px-7 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-5" id="statusBanner">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-white border border-amber-200 flex items-center justify-center shadow-sm shrink-0" id="statusIconWrap">
            <div id="countdownRing" class="relative w-11 h-11">
              <svg class="w-11 h-11 -rotate-90" viewBox="0 0 44 44">
                <circle cx="22" cy="22" r="17" fill="none" stroke="#f1e1bd" stroke-width="4"/>
                <circle id="ringProgress" cx="22" cy="22" r="17" fill="none" stroke="#b7791f" stroke-width="4"
                  stroke-dasharray="106.8" stroke-dashoffset="0" stroke-linecap="round"/>
              </svg>
              <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-amber-700" id="ringLabel" style="font-family:'DM Mono',monospace">30</span>
            </div>
          </div>
          <div>
            <p class="font-bold text-amber-800" id="statusTitle">Reservation Pending</p>
            <p class="text-sm text-slate-600 mt-1" id="statusMsg">Please complete the form and submit before the timer expires.</p>
          </div>
        </div>

        <!-- TIME AND HOUR COUNTDOWN BOX -->
        <div class="rounded-xl border border-amber-200/90 bg-white/95 px-4 py-2.5 text-center shrink-0 shadow-xs self-start sm:self-auto" id="statusTimerBox">
          <div class="flex items-center justify-center gap-2" id="timerGrid">
            <!-- Days Column -->
            <div class="flex flex-col items-center min-w-[32px]">
              <span class="font-mono text-xl font-extrabold text-orange-600 leading-none" id="statusDays">30</span>
              <span class="text-[9px] font-bold text-orange-400 tracking-wider uppercase mt-1">Days</span>
            </div>

            <!-- Separator Colon -->
            <span class="font-mono text-lg font-bold text-orange-300 leading-none -mt-3.5 select-none">:</span>

            <!-- Hours Column -->
            <div class="flex flex-col items-center min-w-[32px]">
              <span class="font-mono text-xl font-extrabold text-orange-600 leading-none" id="statusHours">00</span>
              <span class="text-[9px] font-bold text-orange-400 tracking-wider uppercase mt-1">Hours</span>
            </div>
          </div>
          <div id="statusExpiredText" class="hidden font-mono text-xs font-bold text-red-600 uppercase tracking-wider py-1 px-1">
            Expired
          </div>
          <span id="statusMinutes" class="hidden"></span>
          <p class="hidden text-xs font-semibold mt-1" id="statusCountdown" style="font-family:'DM Mono',monospace"></p>
        </div>
      </div>

      <!-- FORM CARD: REDESIGNED FILLOUT FORM -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-soft overflow-hidden" id="formCard">

        <!-- Form Card Header -->
        <div class="form-card-header px-7 py-6 text-white">
          <h2 class="text-xl sm:text-2xl font-bold tracking-tight" id="formCardTitle"><?= $is_lease ? 'Unit Lease Reservation Form' : 'Unit Resale Reservation Form' ?></h2>
          <p class="text-slate-300 text-xs sm:text-sm mt-1">Zeppelin Suites — Please fill in all required fields</p>
        </div>

        <!-- Form Body with exact requested layout -->
        <div class="p-6 sm:p-7 space-y-7" id="formBody">
          <form id="reservationForm" action="<?= htmlspecialchars($baseUrl) ?>/reservation/submit" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event)">
            <input type="hidden" name="reservation_token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" id="paymentMethodInput" name="payment_method" value="GCash QR">
            <input type="hidden" id="moveOutDate" name="move_out_date">
            <input type="hidden" id="declaredAmountInput" name="declared_amount" value="<?= (float)$price_basis * 0.35 ?>">
            <input type="hidden" name="payment_reference" value="N/A">
            <input type="hidden" id="selectedUnitId" name="selected_unit_id" value="<?= $selectedUnitId ?>">

            <!-- 1. UNIT DETAILS BOX -->
            <div class="border border-slate-200 rounded-2xl p-6 sm:p-7 bg-white shadow-xs mb-8">
              <!-- Header Row: Unit Details on Left, Approved Units on Right -->
              <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-5">
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Unit Details</h3>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  <?= count($approvedUnitsList) ?> Approved <?= count($approvedUnitsList) === 1 ? 'Unit' : 'Units' ?>
                </span>
              </div>

              <!-- Full-Width Unit Selection Dropdown (Cover Side to Side) -->
              <div class="mb-6">
                <label for="approvedUnitSelect" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                  Choose Unit:
                </label>
                <div class="relative w-full">
                  <select id="approvedUnitSelect" onchange="handleUnitSelectionChange(this.value)" class="w-full text-xs sm:text-sm font-semibold text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-300 rounded-xl py-2.5 pl-3.5 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white cursor-pointer shadow-xs transition-all appearance-none">
                    <?php foreach ($approvedUnitsList as $au): ?>
                      <option value="<?= (int)$au['unit_id'] ?>" <?= ((int)$au['unit_id'] === $selectedUnitId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($au['dropdown_label'] ?? ($au['unit_number'] . ' - ' . $au['unit_type'])) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                  </div>
                </div>
              </div>

              <!-- 3-Column Unit Details Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-7 gap-x-8 sm:gap-x-12 pt-5 border-t border-slate-100">
                <!-- Col 1 -->
                <div class="space-y-6">
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Unit</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispUnitNum"><?= $unitNum ?> - <?= $unitTypeUpper ?></p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Furnishing</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispFurnishing"><?= $furnishingVal ?></p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Unit owner</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispOwnerName"><?= $ownerName ?></p>
                  </div>
                </div>

                <!-- Col 2 -->
                <div class="space-y-6">
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Floor</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispFloorNum"><?= $floorNum ?></p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider" id="dispPriceLabel"><?= htmlspecialchars($price_label) ?></p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 font-mono tracking-tight leading-snug" id="dispPriceBasis">₱<?= number_format($price_basis, 0) ?> php</p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Email</p>
                    <div id="dispOwnerEmailWrapper">
                      <?php if (!empty($ownerEmail)): ?>
                        <a href="mailto:<?= htmlspecialchars($ownerEmail) ?>" class="text-sm sm:text-base font-bold text-slate-900 underline hover:text-blue-600 truncate block leading-snug" id="dispOwnerEmailLink"><?= htmlspecialchars($ownerEmail) ?></a>
                      <?php else: ?>
                        <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispOwnerEmailText">—</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Col 3 -->
                <div class="space-y-6">
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">SQM</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispSqmVal"><?= $sqmVal ?></p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Listing</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug" id="dispListingVal"><?= $listingVal ?></p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Contact</p>
                    <p class="text-sm sm:text-base font-bold text-slate-900 font-mono tracking-tight leading-snug" id="dispOwnerContact"><?= $ownerContact ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. CLIENT INFORMATION -->
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">CLIENT INFORMATION</h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Left: Auto-input Contact Details -->
                <div class="space-y-3.5">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="client_name" value="<?= htmlspecialchars($clientName) ?>" placeholder="John Doe" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none">
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                    <input type="email" name="client_email" value="<?= htmlspecialchars($clientEmail) ?>" placeholder="johndoe@gmail.com" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none">
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Phone Number</label>
                    <input type="tel" name="client_contact" value="<?= htmlspecialchars($clientContact) ?>" placeholder="1234 123 1234" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 font-mono focus:outline-none">
                  </div>
                </div>

                <!-- Right: Sex, Nationality -->
                <div class="space-y-3.5">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sex <span class="text-red-500">*</span></label>
                    <select name="client_sex" required class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-slate-900">
                      <option value="" disabled selected>Dropdown</option>
                      <option value="Female">Female</option>
                      <option value="Male">Male</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nationality <span class="text-red-500">*</span></label>
                    <select name="client_nationality" required class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-slate-900">
                      <option value="" disabled>Dropdown</option>
                      <option value="Filipino" selected>Filipino</option>
                      <option value="Foreign">Foreign</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <?php if ($is_lease): ?>
            <!-- 3. LEASE TERM DETAILS -->
            <div id="leaseTermDetailsSection" class="mb-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">LEASE TERM DETAILS</h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-600 mb-1">Preferred move-in time</label>
                  <input type="text" name="preferred_move_in_time" value="<?= $inqPreferredMoveIn ?>" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-600 mb-1">Lease Duration</label>
                  <input type="text" id="leaseDurationDisplay" name="lease_duration" value="<?= $inqLeaseDuration ?>" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-none">
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 mb-1">Move-in Date <span class="text-red-500">*</span></label>
                  <input type="date" id="moveInDate" name="move_in_date" min="<?= $move_in_min ?>" max="<?= $move_in_max ?>" required class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-slate-900" onchange="handleMoveInChange(this.value)">
                  <p class="text-[10px] text-slate-400 mt-1">Requires a 3-day lead time for contract execution and building admin clearance.</p>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-600 mb-1">Move-out Date</label>
                  <input type="text" id="moveOutDateDisplay" name="move_out_date_display" placeholder="auto calculated" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 font-mono focus:outline-none">
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- 4. PAYMENT SECTION -->
            <div class="mt-8 mb-6 pt-6 border-t border-slate-100">
              <div class="grid grid-cols-1 md:grid-cols-[1fr_250px] gap-5 items-start">
                
                <!-- Left Column: Header, Tabs, Panels -->
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-slate-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">DOWN PAYMENT</h3>
                  </div>

                  <!-- Payment Options Tabs -->
                  <div class="flex border border-slate-200 rounded-lg overflow-hidden mb-4 max-w-sm">
                    <button type="button" id="tabGcash" onclick="switchPaymentTab('GCash QR')" class="flex-1 py-2 px-3 text-xs font-bold transition-all bg-[#0f172a] text-white">
                      Pay using GCASH QR
                    </button>
                    <button type="button" id="tabInHouse" onclick="switchPaymentTab('In-House')" class="flex-1 py-2 px-3 text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">
                      Pay In-House
                    </button>
                  </div>

                  <!-- Panels -->
                  <div>
                    <!-- Panel 1: GCash QR -->
                    <div id="panelGcash" class="flex flex-col sm:flex-row items-start gap-4">
                      <!-- QR Code Box -->
                      <div class="w-32 h-32 border border-slate-200 rounded-xl p-2 bg-slate-50 flex items-center justify-center shrink-0 cursor-pointer hover:border-slate-400 transition-all text-center group relative overflow-hidden" onclick="openQRModal()">
                        <div id="qrImageWrap" class="<?= $owner_has_qr ? '' : 'hidden' ?> w-full h-full relative">
                          <img id="qrImgDisplay" src="<?= htmlspecialchars($baseUrl) ?>/<?= htmlspecialchars($owner_qr_path) ?>" alt="Owner GCash QR" class="w-full h-full object-contain rounded-lg">
                          <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold rounded-lg">
                            Click to Enlarge
                          </div>
                        </div>
                        <div id="qrPlaceholderWrap" class="<?= $owner_has_qr ? 'hidden' : '' ?> space-y-1">
                          <svg class="w-6 h-6 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                          <span class="text-[10px] font-bold text-slate-700 tracking-wide block leading-tight">GCASH QR<br>PLACEHOLDER</span>
                        </div>
                      </div>

                      <!-- Right text & file upload -->
                      <div class="flex-1 space-y-2 text-xs text-slate-600">
                        <p class="leading-relaxed">Use the GCash app to scan and pay directly to the unit owner's GCash account.</p>
                        <p class="font-medium text-slate-500">Click QR code to view full size.</p>
                        
                        <div>
                          <label class="block text-xs font-bold text-slate-800 mb-1">Upload Proof of Payment <span class="text-red-500">*</span></label>
                          <input type="file" id="proofUpload" name="payment_proof" accept=".jpg,.jpeg,.png,.webp" required class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                          <p class="text-[10px] text-slate-400 mt-1">Note: Only JPG, PNG, and WEBP files are accepted.</p>
                        </div>
                      </div>
                    </div>

                    <!-- Panel 2: Pay In-House (Hidden by default) -->
                    <div id="panelInHouse" class="hidden p-4 border border-slate-200 rounded-xl bg-slate-50 space-y-2">
                      <div class="flex items-center gap-2 text-slate-900">
                        <svg class="w-4 h-4 text-slate-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <h4 class="text-xs font-bold uppercase">Pay In-House During <?= $is_lease ? 'Lease' : 'Contract' ?> Signing</h4>
                      </div>
                      <p class="text-xs text-slate-700 leading-relaxed">
                        Please prepare the payment amount (cash or manager's check). Payment will be settled in person during your scheduled <?= $is_lease ? 'lease' : 'contract' ?> signing appointment.
                      </p>
                      <p class="text-[11px] text-slate-500 italic">No online proof of payment is required for in-house payment.</p>
                    </div>
                  </div>
                </div>

                <!-- Right Column: PAYMENT BREAKDOWN -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/80 space-y-2.5">
                  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">PAYMENT BREAKDOWN</p>
                  <div>
                    <p class="text-[10px] text-slate-500 mb-0.5" id="breakdownPriceLabel"><?= htmlspecialchars($price_label) ?></p>
                    <p class="text-base font-bold text-slate-900 font-mono" id="breakdownPriceVal">₱<?= number_format($price_basis, 2) ?></p>
                  </div>
                  <div>
                    <p class="text-[10px] font-semibold text-slate-600 mb-1">Down Payment Option</p>
                    <select id="dpOption" name="payment_percentage" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:border-slate-900" onchange="calculateBreakdown()">
                      <option value="0.35" selected>35% Down Payment</option>
                      <option value="0.50">50% Down Payment</option>
                      <option value="0.75">75% Down Payment</option>
                    </select>
                  </div>
                  <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200/80">
                    <span class="text-slate-500 font-medium">Required Amount</span>
                    <span class="font-bold text-slate-900 font-mono" id="dpAmount">₱<?= number_format($price_basis * 0.35, 2) ?></span>
                  </div>
                </div>

              </div>
            </div>

            <!-- 5. LEASE SIGNING DATE -->
            <div class="mt-8 mb-6 pt-6 border-t border-slate-100">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div>
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide"><?= $is_lease ? 'LEASE SIGNING DATE' : 'CONTRACT SIGNING DATE' ?></h3>
                  </div>
                  <p class="text-xs text-slate-500 mt-1" id="signingDateNote">
                    <?= $is_lease 
                      ? 'Select one or multiple dates you are available for lease signing (must be on or before your move-in date).' 
                      : 'Select one or multiple dates you are available for contract signing (requires a 3-day buffer for document preparation).' ?>
                  </p>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                  <input type="checkbox" id="imFlexible" name="is_flexible_signing" value="1" class="w-4 h-4 rounded text-slate-900 accent-slate-900" onchange="handleFlexibleSigning(this)">
                  <span class="text-xs font-semibold text-slate-700">Im Flexible</span>
                </label>
              </div>

              <!-- Multi-date picker calendar container -->
              <div id="signingCalendarWrapper" class="border border-slate-200 rounded-xl bg-slate-50/60 p-4 transition-all">
                <input type="hidden" id="leaseSigningDate" name="lease_signing_date" value="">

                <div class="grid grid-cols-1 md:grid-cols-[250px_1fr] gap-4 items-stretch">
                  
                  <!-- Left: Calendar Card -->
                  <div id="calCard" class="bg-white border border-slate-200 rounded-xl p-3 shadow-xs">
                    <!-- Calendar Header: Month Nav -->
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                      <span id="calMonthYear" class="text-xs font-bold text-slate-900 tracking-wide uppercase">October 2026</span>
                      <div class="flex items-center gap-1">
                        <button type="button" id="calPrevBtn" onclick="navSigningCal(-1)" class="w-6 h-6 rounded border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-all text-xs font-bold disabled:opacity-25 disabled:cursor-not-allowed">‹</button>
                        <button type="button" id="calNextBtn" onclick="navSigningCal(1)" class="w-6 h-6 rounded border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-all text-xs font-bold disabled:opacity-25 disabled:cursor-not-allowed">›</button>
                      </div>
                    </div>

                    <!-- Day Names -->
                    <div class="grid grid-cols-7 gap-1 text-center mb-1 text-[10px] font-semibold text-slate-400">
                      <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>

                    <!-- Days Grid -->
                    <div id="calDaysGrid" class="grid grid-cols-7 gap-1 text-center text-xs">
                      <!-- Populated dynamically via JS -->
                    </div>
                  </div>

                  <!-- Right: Selected Dates Panel -->
                  <div id="chipsSection" class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex flex-col justify-between self-stretch min-h-[200px]">
                    <div>
                      <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-100">
                        <span class="font-bold text-slate-700 text-[11px] uppercase tracking-wide">
                          Selected Dates (<span id="selectedDatesCount">0</span>)
                        </span>
                        <button type="button" id="btnClearDates" onclick="clearSelectedDates()" class="text-[11px] text-slate-400 hover:text-red-600 hidden font-medium transition-colors">Clear all</button>
                      </div>

                      <div id="selectedDatesChips" class="flex flex-wrap items-center gap-1.5 min-h-[36px]">
                        <span class="text-xs text-slate-400 italic" id="emptyDatesMsg">Click any date(s) on the calendar to select multiple dates.</span>
                      </div>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-400 flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span>Selected dates will be saved for scheduling.</span>
                    </div>
                  </div>

                </div>

                <!-- Flexible banner -->
                <div id="flexibleNotice" class="hidden mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-center gap-2">
                  <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>You marked yourself as <strong>Flexible</strong>. A signing schedule will be coordinated with you within the validity window.</span>
                </div>

              </div>
            </div>

            <!-- 6. REMARKS / SPECIAL REQUESTS -->
            <div class="mb-6 space-y-1.5">
              <label class="block text-xs font-semibold text-slate-700">Remarks / Special Requests (optional)</label>
              <textarea id="resRemarks" name="remarks" rows="3" maxlength="500" placeholder="Add any special requests or notes here..." class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 resize-none focus:outline-none focus:border-slate-900" oninput="updateRemarksCount(this)"></textarea>
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-1 text-[10px] text-slate-400">
                <p>Important Note: Special requests are subject to availability and property policies. Zeppelin Suites will make every reasonable effort to accommodate your request, but fulfillment is not guaranteed.</p>
                <span class="shrink-0 font-mono text-slate-500" id="remarksCount">0 / 500</span>
              </div>
            </div>

            <!-- 7. TERMS & CONDITIONS AND SUBMISSION -->
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-4">
              <label class="flex items-start gap-2.5 cursor-pointer select-none text-xs text-slate-600 leading-relaxed">
                <input type="checkbox" id="agreeTerms" required class="w-4 h-4 mt-0.5 rounded text-slate-900 accent-slate-900 shrink-0">
                <span>I agree to the <a href="<?= htmlspecialchars($baseUrl) ?>/terms-of-service" target="_blank" class="font-semibold text-slate-900 underline">Terms and Conditions</a> and <a href="<?= htmlspecialchars($baseUrl) ?>/privacy-policy" target="_blank" class="font-semibold text-slate-900 underline">Privacy Policy</a> of Zeppelin Suites. I confirm that all information provided is accurate and complete.</span>
              </label>

              <div class="flex items-center justify-end pt-2">
                <button type="submit" id="btnSubmitReservation" class="btn-press px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold uppercase tracking-wider rounded-xl transition-all shadow-md hover:shadow-lg whitespace-nowrap">
                  SUBMIT RESERVATION
                </button>
              </div>
            </div>

          </form>
        </div><!-- /formBody -->

        <!-- Expired overlay -->
        <div id="expiredOverlay" class="hidden p-10 text-center">
          <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p class="text-xl font-bold text-slate-900 mb-2">Reservation Link Expired</p>
          <p class="text-sm text-slate-500 mb-6">This reservation link is no longer valid. Please submit a new inquiry to get a fresh reservation link.</p>
          <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="btn-press inline-block bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-8 py-3 rounded-xl tracking-wide transition-all">Submit New Inquiry</a>
        </div>

      </div><!-- /form card -->
    </div><!-- /left content -->

    <!-- 2. RIGHT SIDEBAR -->
    <aside class="space-y-6 lg:sticky lg:top-6">

      <div class="bg-white rounded-xl border border-slate-200 shadow-soft p-7">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 3h6a2 2 0 012 2v1h1a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2h1V5a2 2 0 012-2z"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold uppercase tracking-wide text-slate-900">Reservation Guidelines</h3>
        </div>

        <p class="text-base leading-8 text-slate-800 mb-6">
          A condominium unit may be reserved for thirty days by presenting a Reservation Fee per unit, and the following documents:
        </p>

        <div class="space-y-5 mb-7">
          <div class="flex gap-4 items-start">
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v8a2 2 0 002 2h5m4-12h5a2 2 0 012 2v8a2 2 0 01-2 2h-5M8 11h2m-2 4h2m5-4h1m-1 4h1"/>
              </svg>
            </div>
            <div>
              <p class="font-bold text-slate-900">Photocopy of two (2) valid IDs</p>
              <p class="text-sm text-slate-600 mt-1">i.e. passport, driver's license</p>
            </div>
          </div>

          <div class="flex gap-4 items-start">
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7V3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v6h5M10 14h6M10 18h6"/>
              </svg>
            </div>
            <div>
              <p class="font-bold text-slate-900">Tax Identification Number</p>
              <p class="text-sm text-slate-600 mt-1">(TIN)</p>
            </div>
          </div>

          <div class="flex gap-4 items-start">
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L9.75 16.902 6 18l1.098-3.75L18.55 2.799"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6"/>
              </svg>
            </div>
            <div>
              <p class="font-bold text-slate-900">Reservation Agreement</p>
              <p class="text-sm text-slate-600 mt-1">signed by buyer / tenant</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-5">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">i</div>
            <p class="text-blue-700 font-bold uppercase tracking-wide">Important</p>
          </div>
          <p class="text-sm leading-7 text-slate-800">
            Please submit the fee and the specified documents within <span class="font-bold">thirty (30) days</span>;
            otherwise your reservation may be cancelled and the fee may be forfeited.
          </p>
          <p class="text-sm leading-7 text-slate-800 mt-4">
            For assistance and clarification, please contact our Sales Department.
          </p>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-5 mt-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">!</div>
            <p class="text-blue-700 font-bold uppercase tracking-wide">Please Note</p>
          </div>
          <p class="text-sm leading-relaxed text-slate-800">
            Submitting this form reserves your unit. We will notify you when your agreement is ready to sign. Afterwards, meet with the owner or representative to submit your signed agreement and valid IDs.
          </p>
        </div>
      </div>

      <!-- Need Help Card -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-soft p-7">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 1.91-2 3.522-2 2.071 0 3.75 1.343 3.75 3 0 1.318-1.06 2.438-2.534 2.84-.815.222-1.216.81-1.216 1.41V15m0 4h.01"/>
            </svg>
          </div>
          <h3 class="text-lg font-bold uppercase tracking-wide">Need Help?</h3>
        </div>

        <p class="text-sm text-slate-600 mb-5 ml-11">Our Sales Team is here to assist you.</p>

        <div class="space-y-4 text-sm text-slate-800">
          <div class="flex items-center gap-4">
            <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2l2 5-2 1c1 2 3 4 5 5l1-2 5 2v2a2 2 0 01-2 2h-1C8.373 18 3 12.627 3 6V5z"/>
            </svg>
            <span>+63 917 123 4567</span>
          </div>

          <div class="flex items-center gap-4">
            <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span>sales@zeppelinsuites.com</span>
          </div>

          <div class="flex items-center gap-4">
            <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Mon – Sat&nbsp;&nbsp; | &nbsp;&nbsp;9:00 AM – 6:00 PM</span>
          </div>
        </div>
      </div>

    </aside>
  </div>
</main>

<!-- 3. FOOTER -->
<footer class="bg-slate-950 text-slate-300 mt-12">
  <div class="max-w-[1180px] mx-auto px-5 py-5 flex flex-col md:flex-row justify-between gap-4 text-sm">
    <div class="flex items-start gap-3">
      <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"/>
      </svg>
      <div>
        <p class="font-bold text-white">Your privacy is important to us.</p>
        <p class="text-slate-500">All information collected is used solely for reservation purposes.</p>
      </div>
    </div>
    <p class="text-slate-500">© <?= date('Y') ?> Zeppelin Suites. All rights reserved.</p>
  </div>
</footer>

<!-- 4. MODALS -->
<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
    <h3 class="text-lg font-bold text-slate-900">Confirm Reservation Submission</h3>
    <p class="text-xs text-slate-600 leading-relaxed">
      Please review your submitted information before continuing. Once submitted, your reservation will be forwarded to the administration and unit owner for review.
    </p>
    <div class="flex justify-end gap-2.5 pt-2">
      <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 rounded-lg text-xs font-semibold text-slate-700">Go Back</button>
      <button type="button" onclick="proceedSubmit()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">Submit</button>
    </div>
  </div>
</div>

<!-- QR Enlarged Lightbox Modal -->
<div id="qrModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4" onclick="closeQRModal()">
  <div class="bg-white rounded-3xl p-5 max-w-sm w-full shadow-2xl border border-slate-100 text-center" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
      <h3 class="text-sm font-bold text-slate-900" id="qrModalTitle"><?= $ownerName ?>'s GCash QR</h3>
      <button type="button" onclick="closeQRModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-3 bg-slate-50 rounded-2xl flex items-center justify-center min-h-[220px]" id="qrModalContent">
      <img id="qrModalImg" src="<?= htmlspecialchars($baseUrl) ?>/<?= htmlspecialchars($owner_qr_path) ?>" alt="GCash QR" class="<?= $owner_has_qr ? '' : 'hidden' ?> max-h-[60vh] max-w-full object-contain rounded-xl shadow-sm">
      <div id="qrModalPlaceholder" class="<?= $owner_has_qr ? 'hidden' : '' ?> text-center py-6">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        <p class="text-xs font-semibold text-slate-600">No GCash QR uploaded by this unit owner</p>
        <p class="text-[11px] text-slate-400 mt-1">Please coordinate or pay in-house during lease signing</p>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Scan with GCash or any supported e-wallet</p>
    <div class="pt-3 flex justify-end">
      <button type="button" onclick="closeQRModal()" class="px-4 py-1.5 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">Close</button>
    </div>
  </div>
</div>

<!-- 5. JAVASCRIPT -->
<script>
// ======= Timer configuration =======
const expirationSeconds = 30 * 24 * 60 * 60; // 30 days
let reservationStatus = 'pending';

<?php
  $expiresAtMs = !empty($data['reservation_token_expires_at'])
      ? strtotime((string)$data['reservation_token_expires_at']) * 1000
      : (time() + 30 * 24 * 60 * 60) * 1000;
?>
const expiresAt = <?= (int)$expiresAtMs ?>;
const circumference = 2 * Math.PI * 17;

function updateStatus() {
  const now = new Date().getTime();
  const banner = document.getElementById('statusBanner');
  const title = document.getElementById('statusTitle');
  const msg = document.getElementById('statusMsg');
  const countdown = document.getElementById('statusCountdown');
  const minutesBox = document.getElementById('statusMinutes');
  const daysBox = document.getElementById('statusDays');
  const hoursBox = document.getElementById('statusHours');
  const timerGrid = document.getElementById('timerGrid');
  const expiredText = document.getElementById('statusExpiredText');
  const timerCard = document.getElementById('statusTimerBox');
  const ring = document.getElementById('ringProgress');
  const ringLabel = document.getElementById('ringLabel');
  const formBody = document.getElementById('formBody');
  const expiredOverlay = document.getElementById('expiredOverlay');
  const submitBtn = document.getElementById('btnSubmitReservation');

  if (now > expiresAt) {
    reservationStatus = 'expired';
    banner.className = 'status-banner rounded-xl border px-5 sm:px-7 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-5 bg-red-50 border-red-200 shadow-sm';
    document.getElementById('countdownRing').innerHTML = '<svg class="w-11 h-11 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    title.textContent = 'Reservation Link Expired';
    title.className = 'font-bold text-red-800';
    msg.textContent = 'This reservation link has expired. Please submit a new inquiry.';
    msg.className = 'text-sm mt-1 text-red-700';
    if (timerGrid) timerGrid.classList.add('hidden');
    if (expiredText) expiredText.classList.remove('hidden');
    if (timerCard) timerCard.className = 'rounded-xl border border-red-200 bg-red-50/90 px-4 py-2.5 text-center shrink-0 shadow-xs self-start sm:self-auto';
    if (minutesBox) minutesBox.textContent = 'EXPIRED';
    if (countdown) countdown.textContent = '';
    formBody.classList.add('hidden');
    expiredOverlay.classList.remove('hidden');
    if (submitBtn) submitBtn.disabled = true;
    return;
  }

  const remaining = Math.max(0, Math.ceil((expiresAt - now) / 1000));
  const days = Math.floor(remaining / 86400);
  const hours = Math.floor((remaining % 86400) / 3600);
  const fraction = remaining / expirationSeconds;
  const offset = circumference * (1 - fraction);
  const isUrgent = remaining <= 24 * 60 * 60; // last day

  title.textContent = 'Reservation Pending';
  title.className = 'font-bold text-amber-800';
  msg.textContent = 'Please complete the form and submit before the reservation link expires.';
  msg.className = 'text-sm text-slate-600 mt-1';

  if (timerGrid) timerGrid.classList.remove('hidden');
  if (expiredText) expiredText.classList.add('hidden');
  if (daysBox) daysBox.textContent = String(days).padStart(2, '0');
  if (hoursBox) hoursBox.textContent = String(hours).padStart(2, '0');
  if (minutesBox) minutesBox.textContent = `${String(days).padStart(2, '0')} : ${String(hours).padStart(2, '0')}`;
  if (countdown) countdown.textContent = `Time remaining: ${days} day${days !== 1 ? 's' : ''}, ${hours} hour${hours !== 1 ? 's' : ''}`;

  if (ring) {
    ring.style.strokeDasharray = circumference;
    ring.style.strokeDashoffset = offset;
    ring.setAttribute('stroke', isUrgent ? '#ef4444' : '#b7791f');
  }

  if (ringLabel) {
    ringLabel.textContent = days > 0 ? days : hours;
    ringLabel.className = `absolute inset-0 flex items-center justify-center text-[10px] font-bold ${isUrgent ? 'text-red-500' : 'text-amber-700'}`;
  }
}

setInterval(updateStatus, 1000);
updateStatus();

// ======= Dynamic Units and Move-out date calculation =======
const approvedUnitsData = <?= json_encode($approvedUnitsList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const baseUrlStr = <?= json_encode($baseUrl) ?>;
const isLease = <?= json_encode((bool)$is_lease) ?>;
let currentPriceBasis = <?= (float)$price_basis ?>;
let currentUnitId = <?= $selectedUnitId ?>;
const leaseDurationStr = <?= json_encode($inqLeaseDuration) ?>;

function parseDurationToMonths(str) {
  if (!str) return 12;
  const s = str.toLowerCase();
  if (s.includes('longer') || s.includes('not sure')) {
    return 12;
  }
  if (s.includes('year')) {
    const match = s.match(/(\d+)/);
    const yrs = match ? parseInt(match[1], 10) : 1;
    return yrs >= 3 ? 12 : yrs * 12;
  }
  if (s.includes('month')) {
    const match = s.match(/(\d+)/);
    return match ? parseInt(match[1], 10) : 1;
  }
  return 12;
}

function addMonthsToDate(dateStr, months) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-').map(Number);
  const target = new Date(y, m - 1 + months, d);
  
  const expectedMonth = (m - 1 + months) % 12;
  if (target.getMonth() !== expectedMonth) {
    target.setDate(0);
  }

  const yOut = target.getFullYear();
  const mOut = String(target.getMonth() + 1).padStart(2, '0');
  const dOut = String(target.getDate()).padStart(2, '0');
  return `${yOut}-${mOut}-${dOut}`;
}

function formatDisplayDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function handleMoveInChange(moveInVal) {
  const months = parseDurationToMonths(leaseDurationStr);
  const moveOutVal = addMonthsToDate(moveInVal, months);
  const moveOutInput = document.getElementById('moveOutDate');
  const moveOutDisplay = document.getElementById('moveOutDateDisplay');
  if (moveOutInput) moveOutInput.value = moveOutVal;
  if (moveOutDisplay) moveOutDisplay.value = moveOutVal ? formatDisplayDate(moveOutVal) : '';

  updateSigningCalendarLimit(moveInVal);
}

// ======= Multi-Date Lease Signing Calendar =======
const minSigningDate = '<?= $signing_min ?>';
const absoluteMaxSigningDate = '<?= $maxSigningDate ?>';
const selectedSigningDates = new Set();

function getEffectiveMaxSigningDate() {
  const moveInInput = document.getElementById('moveInDate');
  if (isLease && moveInInput && moveInInput.value && moveInInput.value.trim() !== '') {
    return moveInInput.value < absoluteMaxSigningDate ? moveInInput.value : absoluteMaxSigningDate;
  }
  return absoluteMaxSigningDate;
}

function updateSigningCalendarLimit(moveInVal) {
  const effectiveMax = getEffectiveMaxSigningDate();
  let pruned = false;
  selectedSigningDates.forEach(dateStr => {
    if (dateStr > effectiveMax) {
      selectedSigningDates.delete(dateStr);
      pruned = true;
    }
  });
  if (pruned) {
    syncSigningInput();
  }

  // Ensure calendar view does not stay on a month beyond effective max
  const [maxYear, maxMonth] = effectiveMax.split('-').map(Number);
  const maxMonth0 = maxMonth - 1;
  if (calCurrentYear > maxYear || (calCurrentYear === maxYear && calCurrentMonth > maxMonth0)) {
    calCurrentYear = maxYear;
    calCurrentMonth = maxMonth0;
  }

  renderSigningCalendar();
}

const [startCalYear, startCalMonth] = '<?= date('Y-m') ?>'.split('-').map(Number);
let calCurrentYear = startCalYear;
let calCurrentMonth = startCalMonth - 1; // 0-indexed

const calMonthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

function renderSigningCalendar() {
  const monthYearLabel = document.getElementById('calMonthYear');
  const daysGrid = document.getElementById('calDaysGrid');
  const prevBtn = document.getElementById('calPrevBtn');
  const nextBtn = document.getElementById('calNextBtn');

  if (!daysGrid) return;

  if (monthYearLabel) {
    monthYearLabel.textContent = `${calMonthNames[calCurrentMonth]} ${calCurrentYear}`;
  }

  const effectiveMaxSigningDate = getEffectiveMaxSigningDate();

  const firstDayIndex = new Date(calCurrentYear, calCurrentMonth, 1).getDay();
  const totalDays = new Date(calCurrentYear, calCurrentMonth + 1, 0).getDate();

  const [minY, minM] = minSigningDate.split('-').map(Number);
  const [maxY, maxM] = effectiveMaxSigningDate.split('-').map(Number);

  const prevMonthYear = calCurrentMonth === 0 ? calCurrentYear - 1 : calCurrentYear;
  const prevMonthNum = calCurrentMonth === 0 ? 12 : calCurrentMonth;

  const nextMonthYear = calCurrentMonth === 11 ? calCurrentYear + 1 : calCurrentYear;
  const nextMonthNum = calCurrentMonth === 11 ? 1 : calCurrentMonth + 2;

  if (prevBtn) {
    prevBtn.disabled = (prevMonthYear < minY) || (prevMonthYear === minY && prevMonthNum < minM);
  }
  if (nextBtn) {
    nextBtn.disabled = (nextMonthYear > maxY) || (nextMonthYear === maxY && nextMonthNum > maxM);
  }

  let html = '';

  for (let i = 0; i < firstDayIndex; i++) {
    html += '<span class="py-1 text-transparent select-none">.</span>';
  }

  for (let d = 1; d <= totalDays; d++) {
    const dayStr = String(d).padStart(2, '0');
    const monthStr = String(calCurrentMonth + 1).padStart(2, '0');
    const dateStr = `${calCurrentYear}-${monthStr}-${dayStr}`;

    const isBeforeMin = dateStr < minSigningDate;
    const isAfterMax = dateStr > effectiveMaxSigningDate;
    const isOutOfRange = isBeforeMin || isAfterMax;
    const isSelected = selectedSigningDates.has(dateStr);

    if (isOutOfRange) {
      const tooltip = isAfterMax ? 'Signing appointment must be on or before move-in date' : 'Date unavailable';
      html += `<span class="py-1 text-slate-300 cursor-not-allowed select-none text-[11px]" title="${tooltip}">${d}</span>`;
    } else if (isSelected) {
      html += `<button type="button" onclick="toggleSigningDate('${dateStr}')" class="py-1 bg-[#0f172a] text-white font-bold rounded-lg shadow-xs hover:bg-slate-800 transition-colors text-[11px]" title="Click to remove">${d}</button>`;
    } else {
      html += `<button type="button" onclick="toggleSigningDate('${dateStr}')" class="py-1 text-slate-700 hover:bg-blue-50 hover:text-blue-700 font-medium rounded-lg transition-colors text-[11px]">${d}</button>`;
    }
  }

  daysGrid.innerHTML = html;
  renderSelectedChips();
}

function navSigningCal(direction) {
  calCurrentMonth += direction;
  if (calCurrentMonth < 0) {
    calCurrentMonth = 11;
    calCurrentYear -= 1;
  } else if (calCurrentMonth > 11) {
    calCurrentMonth = 0;
    calCurrentYear += 1;
  }
  renderSigningCalendar();
}

function toggleSigningDate(dateStr) {
  if (document.getElementById('imFlexible').checked) return;

  if (dateStr < minSigningDate) {
    alert("Signing date must be at least 3 days from today (" + minSigningDate + ") to allow for document preparation.");
    return;
  }

  const effectiveMax = getEffectiveMaxSigningDate();
  if (dateStr > effectiveMax) {
    const moveInInput = document.getElementById('moveInDate');
    const moveInMsg = (moveInInput && moveInInput.value) ? `your move-in date (${moveInInput.value})` : effectiveMax;
    alert(`Lease signing date must be scheduled on or before ${moveInMsg}.`);
    return;
  }

  if (selectedSigningDates.has(dateStr)) {
    selectedSigningDates.delete(dateStr);
  } else {
    selectedSigningDates.add(dateStr);
  }
  syncSigningInput();
  renderSigningCalendar();
}

function removeSigningDate(dateStr) {
  selectedSigningDates.delete(dateStr);
  syncSigningInput();
  renderSigningCalendar();
}

function clearSelectedDates() {
  selectedSigningDates.clear();
  syncSigningInput();
  renderSigningCalendar();
}

function syncSigningInput() {
  const hiddenInput = document.getElementById('leaseSigningDate');
  const arr = Array.from(selectedSigningDates).sort();
  if (hiddenInput) {
    hiddenInput.value = arr.join(', ');
  }
}

function renderSelectedChips() {
  const countEl = document.getElementById('selectedDatesCount');
  const container = document.getElementById('selectedDatesChips');
  const clearBtn = document.getElementById('btnClearDates');

  const arr = Array.from(selectedSigningDates).sort();
  if (countEl) countEl.textContent = arr.length;

  if (clearBtn) {
    if (arr.length > 0) clearBtn.classList.remove('hidden');
    else clearBtn.classList.add('hidden');
  }

  if (!container) return;

  if (arr.length === 0) {
    container.innerHTML = '<span class="text-xs text-slate-400 italic" id="emptyDatesMsg">Click any date(s) on the calendar to select multiple dates.</span>';
    return;
  }

  let chipsHtml = '';
  arr.forEach(d => {
    const formatted = formatDisplayDate(d);
    chipsHtml += `
      <span class="inline-flex items-center gap-1.5 bg-slate-900 text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full shadow-xs">
        <span>${formatted}</span>
        <button type="button" onclick="removeSigningDate('${d}')" class="text-slate-300 hover:text-white transition-colors" title="Remove date">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </span>
    `;
  });

  container.innerHTML = chipsHtml;
}

// ======= Flexible signing checkbox handler =======
function handleFlexibleSigning(cb) {
  const calCard = document.getElementById('calCard');
  const chipsSection = document.getElementById('chipsSection');
  const flexNotice = document.getElementById('flexibleNotice');
  const hiddenInput = document.getElementById('leaseSigningDate');

  if (cb.checked) {
    if (calCard) calCard.classList.add('opacity-40', 'pointer-events-none');
    if (chipsSection) chipsSection.classList.add('opacity-40', 'pointer-events-none');
    if (flexNotice) flexNotice.classList.remove('hidden');
    if (hiddenInput) hiddenInput.value = '';
  } else {
    if (calCard) calCard.classList.remove('opacity-40', 'pointer-events-none');
    if (chipsSection) chipsSection.classList.remove('opacity-40', 'pointer-events-none');
    if (flexNotice) flexNotice.classList.add('hidden');
    syncSigningInput();
  }
}

// Initialize calendar
renderSigningCalendar();

// ======= Payment tab switcher =======
function switchPaymentTab(type) {
  const tabGcash = document.getElementById('tabGcash');
  const tabInHouse = document.getElementById('tabInHouse');
  const panelGcash = document.getElementById('panelGcash');
  const panelInHouse = document.getElementById('panelInHouse');
  const paymentMethodInput = document.getElementById('paymentMethodInput');
  const proofUpload = document.getElementById('proofUpload');

  paymentMethodInput.value = type;

  if (type === 'GCash QR') {
    tabGcash.className = 'flex-1 py-2 px-3 text-xs font-bold transition-all bg-[#0f172a] text-white';
    tabInHouse.className = 'flex-1 py-2 px-3 text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200';
    panelGcash.classList.remove('hidden');
    panelInHouse.classList.add('hidden');
    proofUpload.required = true;
  } else {
    tabGcash.className = 'flex-1 py-2 px-3 text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200';
    tabInHouse.className = 'flex-1 py-2 px-3 text-xs font-bold transition-all bg-[#0f172a] text-white';
    panelGcash.classList.add('hidden');
    panelInHouse.classList.remove('hidden');
    proofUpload.required = false;
    proofUpload.value = '';
  }
}

// ======= Payment breakdown calculation =======
function calculateBreakdown() {
  const dpSelect = document.getElementById('dpOption');
  const pct = parseFloat(dpSelect.value);
  const reqAmount = currentPriceBasis * pct;

  const formatted = '₱' + reqAmount.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  const dpAmountEl = document.getElementById('dpAmount');
  if (dpAmountEl) dpAmountEl.textContent = formatted;
  const declaredInput = document.getElementById('declaredAmountInput');
  if (declaredInput) declaredInput.value = reqAmount;
}

// ======= Dynamic Unit Selection Handler =======
function handleUnitSelectionChange(unitIdStr) {
  const unitId = parseInt(unitIdStr, 10);
  if (!unitId || !Array.isArray(approvedUnitsData)) return;

  const unit = approvedUnitsData.find(u => parseInt(u.unit_id, 10) === unitId);
  if (!unit) return;

  currentUnitId = unitId;
  currentPriceBasis = parseFloat(unit.price_basis) || 0;

  // Sync hidden input
  const hiddenUnitInput = document.getElementById('selectedUnitId');
  if (hiddenUnitInput) hiddenUnitInput.value = unitId;

  // 1. Update Unit Details First Panel
  const dispUnitNum = document.getElementById('dispUnitNum');
  if (dispUnitNum) {
    const typeUpper = (unit.unit_type || 'STUDIO TYPE').toUpperCase();
    dispUnitNum.textContent = `${unit.unit_number || '—'} - ${typeUpper}`;
  }

  const dispFurnishing = document.getElementById('dispFurnishing');
  if (dispFurnishing) dispFurnishing.textContent = unit.furnishing || 'Fully Furnished';

  const dispOwnerName = document.getElementById('dispOwnerName');
  if (dispOwnerName) dispOwnerName.textContent = unit.owner_name || 'No owner assigned';

  const dispFloorNum = document.getElementById('dispFloorNum');
  if (dispFloorNum) dispFloorNum.textContent = unit.floor_number || '1';

  const dispPriceLabel = document.getElementById('dispPriceLabel');
  if (dispPriceLabel) dispPriceLabel.textContent = unit.price_label || 'Monthly Rate';

  const dispPriceBasis = document.getElementById('dispPriceBasis');
  if (dispPriceBasis) {
    dispPriceBasis.textContent = `₱${Math.round(currentPriceBasis).toLocaleString('en-US')} php`;
  }

  const emailWrapper = document.getElementById('dispOwnerEmailWrapper');
  if (emailWrapper) {
    if (unit.owner_email && unit.owner_email.trim() !== '') {
      emailWrapper.innerHTML = `<a href="mailto:${escapeHtml(unit.owner_email)}" class="font-bold text-slate-900 underline hover:text-blue-600 truncate block" id="dispOwnerEmailLink">${escapeHtml(unit.owner_email)}</a>`;
    } else {
      emailWrapper.innerHTML = `<p class="font-bold text-slate-900" id="dispOwnerEmailText">—</p>`;
    }
  }

  const dispSqmVal = document.getElementById('dispSqmVal');
  if (dispSqmVal) dispSqmVal.textContent = unit.sqm || '37';

  const dispListingVal = document.getElementById('dispListingVal');
  if (dispListingVal) dispListingVal.textContent = unit.listing_type || 'For Lease';

  const dispOwnerContact = document.getElementById('dispOwnerContact');
  if (dispOwnerContact) dispOwnerContact.textContent = unit.owner_contact || '—';

  // 2. Update Payment Breakdown Box
  const breakdownPriceLabel = document.getElementById('breakdownPriceLabel');
  if (breakdownPriceLabel) breakdownPriceLabel.textContent = unit.price_label || 'Monthly Rate';

  const breakdownPriceVal = document.getElementById('breakdownPriceVal');
  if (breakdownPriceVal) {
    breakdownPriceVal.textContent = '₱' + currentPriceBasis.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  calculateBreakdown();

  // 3. Update GCash QR Display & Modal
  const qrImageWrap = document.getElementById('qrImageWrap');
  const qrPlaceholderWrap = document.getElementById('qrPlaceholderWrap');
  const qrImgDisplay = document.getElementById('qrImgDisplay');
  const qrModalTitle = document.getElementById('qrModalTitle');
  const qrModalImg = document.getElementById('qrModalImg');
  const qrModalPlaceholder = document.getElementById('qrModalPlaceholder');

  const hasQr = Boolean(unit.owner_has_qr && unit.owner_qr_path);
  const qrSrc = hasQr ? `${baseUrlStr}/${unit.owner_qr_path}` : '';

  if (qrModalTitle) {
    qrModalTitle.textContent = `${unit.owner_name || 'Owner'}'s GCash QR`;
  }

  if (hasQr) {
    if (qrImgDisplay) qrImgDisplay.src = qrSrc;
    if (qrModalImg) {
      qrModalImg.src = qrSrc;
      qrModalImg.classList.remove('hidden');
    }
    if (qrModalPlaceholder) qrModalPlaceholder.classList.add('hidden');
    if (qrImageWrap) qrImageWrap.classList.remove('hidden');
    if (qrPlaceholderWrap) qrPlaceholderWrap.classList.add('hidden');
  } else {
    if (qrModalImg) qrModalImg.classList.add('hidden');
    if (qrModalPlaceholder) qrModalPlaceholder.classList.remove('hidden');
    if (qrImageWrap) qrImageWrap.classList.add('hidden');
    if (qrPlaceholderWrap) qrPlaceholderWrap.classList.remove('hidden');
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// ======= Remarks live counter =======
function updateRemarksCount(textarea) {
  document.getElementById('remarksCount').textContent = `${textarea.value.length} / 500`;
}

// ======= QR lightbox modal =======
function openQRModal() {
  const modal = document.getElementById('qrModal');
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}
function closeQRModal() {
  const modal = document.getElementById('qrModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

// ======= Form submit and validation =======
function handleFormSubmit(e) {
  e.preventDefault();

  const agreeTerms = document.getElementById('agreeTerms');
  if (!agreeTerms.checked) {
    alert("Please check and agree to the Terms and Conditions before submitting your reservation.");
    agreeTerms.focus();
    return false;
  }

  const moveIn = document.getElementById('moveInDate');
  const minMoveIn = '<?= $move_in_min ?>';
  if (isLease && (!moveIn || !moveIn.value)) {
    alert("Please select your Move-in Date.");
    if (moveIn) moveIn.focus();
    return false;
  }
  if (isLease && moveIn && moveIn.value < minMoveIn) {
    alert("Move-in date must be at least 3 days from today (" + minMoveIn + ") to allow for contract execution and building administration clearance.");
    moveIn.focus();
    return false;
  }

  // Validate that signing appointment is not scheduled after move-in date
  if (isLease && moveIn && moveIn.value && !document.getElementById('imFlexible').checked) {
    const effectiveMax = getEffectiveMaxSigningDate();
    for (const sDate of selectedSigningDates) {
      if (sDate > effectiveMax) {
        alert("Your lease signing appointment (" + sDate + ") cannot be scheduled after your move-in date (" + moveIn.value + "). Please select signing dates on or before your move-in date.");
        return false;
      }
    }
  }

  // Validate that resale contract signing is at least 3 days in advance
  if (!isLease && !document.getElementById('imFlexible').checked) {
    for (const sDate of selectedSigningDates) {
      if (sDate < minSigningDate) {
        alert("Contract signing date (" + sDate + ") must be at least 3 days from today (" + minSigningDate + ") to allow for document preparation.");
        return false;
      }
    }
  }

  const paymentMethod = document.getElementById('paymentMethodInput').value;
  const proof = document.getElementById('proofUpload');
  if (paymentMethod === 'GCash QR' && (!proof.files || proof.files.length === 0)) {
    alert("Please upload your proof of payment for the GCash QR payment option.");
    proof.focus();
    return false;
  }

  // Open confirmation modal
  const modal = document.getElementById('confirmModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  return false;
}

function closeConfirmModal() {
  const modal = document.getElementById('confirmModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function proceedSubmit() {
  const btn = document.getElementById('btnSubmitReservation');
  btn.disabled = true;
  btn.textContent = 'SUBMITTING...';
  document.getElementById('reservationForm').submit();
}
</script>

</body>
</html>
