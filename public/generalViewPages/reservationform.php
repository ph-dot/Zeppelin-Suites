<?php 
require_once __DIR__ . '/ActionsGV/loadReservationForm.php'; 

// Format unit numbers and values
$unitNum = !empty($data['unit_number']) ? htmlspecialchars($data['unit_number']) : '—';
$unitTypeUpper = !empty($data['unit_type']) ? strtoupper(htmlspecialchars($data['unit_type'])) : 'STUDIO TYPE';
$floorNum = !empty($data['floor_number']) ? htmlspecialchars($data['floor_number']) : '1';
$sqmVal = !empty($data['sqm']) ? htmlspecialchars($data['sqm']) : '37';
$furnishingVal = !empty($data['furnishing']) ? htmlspecialchars($data['furnishing']) : 'Fully Furnished.';
$listingVal = !empty($data['listing_type']) ? htmlspecialchars($data['listing_type']) : 'For Lease';
$ownerName = !empty($data['owner_name']) ? htmlspecialchars($data['owner_name']) : 'No owner assigned';
$ownerEmail = !empty($data['owner_email']) ? htmlspecialchars($data['owner_email']) : '';
$ownerContact = !empty($data['owner_contact']) ? htmlspecialchars($data['owner_contact']) : '—';

// Format lease duration display
$inqLeaseDuration = !empty($data['lease_duration']) ? htmlspecialchars($data['lease_duration']) : '1 year';
$inqPreferredMoveIn = !empty($data['preferred_move_in_time']) ? htmlspecialchars($data['preferred_move_in_time']) : 'Immediately';

// Expiry date for date limits
$maxSigningDate = !empty($token_expires_date) ? $token_expires_date : date('Y-m-d', strtotime('+30 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Condominium Reservation</title>
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

<!-- 1. HERO HEADER (KEPT AS REQUESTED) -->
<header class="zep-hero relative overflow-hidden border-b border-slate-200">
  <div class="building-mark hidden md:block"></div>

  <div class="max-w-[1180px] mx-auto px-5 py-8 md:py-9 flex items-center justify-between gap-6 relative">
    <div class="flex items-center gap-7">
      <div class="w-[112px] flex flex-col items-center justify-center">
        <div class="flex items-end justify-center gap-1.5 h-14 mb-2">
          <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" style="height:60px;" onerror="this.outerHTML='<span class=\'font-bold text-xl tracking-tight text-zinc-900\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest\'>SUITES</span></span>'">
        </div>
        <div class="text-[11px] tracking-[0.35em] leading-tight text-slate-700 text-center font-semibold">
          ZEPPELIN<br>SUITES
        </div>
      </div>

      <div class="hidden sm:block w-px h-16 bg-slate-200"></div>

      <div>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-950">
          Condominium Reservation
        </h1>
        <p class="mt-3 text-base md:text-lg text-slate-600">
          Reserve your preferred unit for 30 days.
        </p>
      </div>
    </div>

    <div class="hidden md:flex items-start gap-3 pr-8">
      <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
        </svg>
      </div>
      <div>
        <p class="font-bold text-slate-900">Secure &amp; Confidential</p>
        <p class="text-sm text-slate-500">Your information is safe with us.</p>
      </div>
    </div>
  </div>
</header>

<!-- MAIN CONTENT CONTAINER -->
<main class="max-w-[1180px] mx-auto px-5 py-8 md:py-10">

  <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,720px)_360px] gap-9 items-start">

    <!-- LEFT CONTENT: STATUS BANNER & FORM CARD -->
    <div class="space-y-8">

      <!-- STATUS BANNER (KEPT AS REQUESTED) -->
      <div class="status-banner rounded-xl border border-amber-200 bg-amber-50/80 shadow-sm px-7 py-5 flex items-center justify-between gap-5" id="statusBanner">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-white border border-amber-200 flex items-center justify-center shadow-sm" id="statusIconWrap">
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

        <div class="rounded-xl border border-amber-200 bg-white/80 px-4 py-3 text-center min-w-[92px]">
          <p class="font-mono text-xl font-bold text-orange-500 leading-none" id="statusMinutes">30 : 00</p>
          <p class="text-[10px] font-bold text-orange-400 tracking-widest mt-1">DAYS&nbsp;&nbsp;&nbsp;HRS</p>
          <p class="hidden text-xs font-semibold mt-1" id="statusCountdown" style="font-family:'DM Mono',monospace"></p>
        </div>
      </div>

      <!-- FORM CARD: REDESIGNED FILLOUT FORM -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-soft overflow-hidden" id="formCard">

        <!-- Form Card Header -->
        <div class="form-card-header px-7 py-6 text-white">
          <h2 class="text-xl sm:text-2xl font-bold tracking-tight">Unit Reservation form</h2>
          <p class="text-slate-300 text-xs sm:text-sm mt-1">Zeppelin Suites — Please fill in all required fields</p>
        </div>

        <!-- Form Body with exact requested layout -->
        <div class="p-6 sm:p-7 space-y-7" id="formBody">
          <form id="reservationForm" action="ActionsGV/submitReservation.php" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event)">
            <input type="hidden" name="reservation_token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="paymentMethodInput" name="payment_method" value="GCash QR">
            <input type="hidden" id="moveOutDate" name="move_out_date">
            <input type="hidden" id="declaredAmountInput" name="declared_amount" value="<?= (float)$price_basis * 0.35 ?>">
            <input type="hidden" name="payment_reference" value="N/A">

            <!-- 1. UNIT DETAILS BOX -->
            <div class="border border-slate-200 rounded-xl p-4 sm:p-5 bg-white mb-6">
              <h3 class="text-sm sm:text-base font-bold text-slate-900 pb-2.5 border-b border-slate-100 mb-4">Unit Details</h3>
              
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6 text-xs sm:text-sm">
                <!-- Col 1 -->
                <div class="space-y-4">
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Unit</p>
                    <p class="font-bold text-slate-900"><?= $unitNum ?> - <?= $unitTypeUpper ?></p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Furnishing</p>
                    <p class="font-bold text-slate-900"><?= $furnishingVal ?></p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Unit owner</p>
                    <p class="font-bold text-slate-900"><?= $ownerName ?></p>
                  </div>
                </div>

                <!-- Col 2 -->
                <div class="space-y-4">
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Floor</p>
                    <p class="font-bold text-slate-900"><?= $floorNum ?></p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5"><?= htmlspecialchars($price_label) ?></p>
                    <p class="font-bold text-slate-900 font-mono">₱<?= number_format($price_basis, 0) ?> php</p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Email</p>
                    <?php if (!empty($ownerEmail)): ?>
                      <a href="mailto:<?= htmlspecialchars($ownerEmail) ?>" class="font-bold text-slate-900 underline hover:text-blue-600 truncate block"><?= htmlspecialchars($ownerEmail) ?></a>
                    <?php else: ?>
                      <p class="font-bold text-slate-900">—</p>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Col 3 -->
                <div class="space-y-4">
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">SQM</p>
                    <p class="font-bold text-slate-900"><?= $sqmVal ?></p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Listing</p>
                    <p class="font-bold text-slate-900"><?= $listingVal ?></p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-xs mb-0.5">Contact</p>
                    <p class="font-bold text-slate-900 font-mono"><?= $ownerContact ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. FILL OUT YOUR INFORMATION (With Person/Applicant Icon) -->
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">FILL OUT YOUR INFORMATION</h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Left: Auto-input Contact Details -->
                <div class="space-y-3.5">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="client_name" value="<?= htmlspecialchars($data['sender_name']) ?>" placeholder="John Doe" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none">
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                    <input type="email" name="client_email" value="<?= htmlspecialchars($data['sender_email']) ?>" placeholder="johndoe@gmail.com" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none">
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Phone Number</label>
                    <input type="tel" name="client_contact" value="<?= htmlspecialchars($data['sender_contact']) ?>" placeholder="1234 123 1234" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 font-mono focus:outline-none">
                  </div>
                </div>

                <!-- Right: Sex, Age, Nationality -->
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
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Age <span class="text-red-500">*</span></label>
                    <input type="number" name="client_age" min="18" max="120" placeholder="Input" required class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-slate-900">
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

            <!-- 3. LEASE TERM DETAILS (With Calendar Icon) -->
            <div class="mb-6">
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
                  <p class="text-[10px] text-slate-400 mt-1">Available up to 30 days from reservation issuance.</p>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-600 mb-1">Move-out Date</label>
                  <input type="text" id="moveOutDateDisplay" name="move_out_date_display" placeholder="auto calculated" readonly class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 font-mono focus:outline-none">
                </div>
              </div>
            </div>

            <!-- 4. PAYMENT SECTION -->
            <div class="mb-6">
              <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide mb-3">PAYMENT</h3>

              <!-- Payment Options Tabs -->
              <div class="flex border border-slate-200 rounded-lg overflow-hidden mb-4 max-w-sm">
                <button type="button" id="tabGcash" onclick="switchPaymentTab('GCash QR')" class="flex-1 py-2 px-3 text-xs font-bold transition-all bg-[#0f172a] text-white">
                  Pay using GCASH QR
                </button>
                <button type="button" id="tabInHouse" onclick="switchPaymentTab('In-House')" class="flex-1 py-2 px-3 text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">
                  Pay In-House
                </button>
              </div>

              <!-- Payment Details: Left Option Content, Right Breakdown Card -->
              <div class="grid grid-cols-1 sm:grid-cols-[1fr_240px] gap-4 items-start">
                
                <!-- Left Column: Option Panels -->
                <div>
                  <!-- Panel 1: GCash QR -->
                  <div id="panelGcash" class="flex flex-col sm:flex-row items-start gap-4">
                    <!-- QR Code Box -->
                    <div class="w-32 h-32 border border-slate-200 rounded-xl p-2 bg-slate-50 flex items-center justify-center shrink-0 cursor-pointer hover:border-slate-400 transition-all text-center group relative overflow-hidden" onclick="openQRModal()">
                      <?php if ($owner_has_qr): ?>
                        <img src="<?= htmlspecialchars($owner_qr_path) ?>" alt="Owner GCash QR" class="w-full h-full object-contain rounded-lg">
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold rounded-lg">
                          Click to Enlarge
                        </div>
                      <?php else: ?>
                        <div class="space-y-1">
                          <svg class="w-6 h-6 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                          <span class="text-[10px] font-bold text-slate-700 tracking-wide block leading-tight">GCASH QR<br>PLACEHOLDER</span>
                        </div>
                      <?php endif; ?>
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
                      <h4 class="text-xs font-bold uppercase">Pay In-House During Lease Signing</h4>
                    </div>
                    <p class="text-xs text-slate-700 leading-relaxed">
                      Please prepare the payment amount (cash or manager's check). Payment will be settled in person during your scheduled lease signing appointment.
                    </p>
                    <p class="text-[11px] text-slate-500 italic">No online proof of payment is required for in-house payment.</p>
                  </div>
                </div>

                <!-- Right Column: PAYMENT BREAKDOWN CARD -->
                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50/70 space-y-2.5">
                  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">PAYMENT BREAKDOWN</p>
                  <div>
                    <p class="text-[10px] text-slate-500 mb-0.5"><?= htmlspecialchars($price_label) ?></p>
                    <p class="text-base font-bold text-slate-900 font-mono">₱<?= number_format($price_basis, 2) ?></p>
                  </div>
                  <div>
                    <p class="text-[10px] font-semibold text-slate-600 mb-1">Down Payment Option</p>
                    <select id="dpOption" name="payment_percentage" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:border-slate-900" onchange="calculateBreakdown()">
                      <option value="0.35" selected>35% Down Payment</option>
                      <option value="0.50">50% Down Payment</option>
                      <option value="0.75">75% Down Payment</option>
                      <option value="1.00">Full Payment (100%)</option>
                    </select>
                  </div>
                  <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-200/80">
                    <span class="text-slate-500 font-medium">Required Amount</span>
                    <span class="font-bold text-slate-900 font-mono" id="dpAmount">₱<?= number_format($price_basis * 0.35, 2) ?></span>
                  </div>
                  <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-200/80">
                    <span class="text-slate-500 font-medium">Payment Status</span>
                    <span class="text-[11px] font-bold text-amber-600">Pending Unit Owner Review</span>
                  </div>
                </div>

              </div>
            </div>

            <!-- 5. LEASE SIGNING DATE -->
            <div class="mb-6">
              <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">LEASE SIGNING DATE</h3>
              <p class="text-xs text-slate-500 mb-2.5">Choose a date when you are available for the lease signing.</p>
              
              <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[180px] max-w-xs">
                  <input type="date" id="leaseSigningDate" name="lease_signing_date" min="<?= date('Y-m-d') ?>" max="<?= $maxSigningDate ?>" class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-slate-900">
                </div>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input type="checkbox" id="imFlexible" name="is_flexible_signing" value="1" class="w-4 h-4 rounded text-slate-900 accent-slate-900" onchange="handleFlexibleSigning(this)">
                  <span class="text-xs font-semibold text-slate-700">Im Flexible</span>
                </label>
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
                <span>I agree to the <a href="#" class="font-semibold text-slate-900 underline">Terms and Conditions</a> and <a href="#" class="font-semibold text-slate-900 underline">Privacy Policy</a> of Zeppelin Suites. I confirm that all information provided is accurate and complete.</span>
              </label>

              <div class="flex items-center justify-between pt-2">
                <p class="text-xs text-slate-400">
                  Inquiry ID:
                  <span class="font-bold text-slate-700 font-mono">
                    #<?php echo htmlspecialchars($data['inq_id']); ?>
                  </span>
                </p>
                <button type="submit" id="btnSubmitReservation" class="btn-press px-6 py-2.5 bg-[#0f172a] hover:bg-[#1e293b] text-white text-xs font-bold uppercase tracking-wider rounded-lg flex items-center gap-2 transition-all shadow-md">
                  <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg>
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
          <a href="../generalViewPages/contact.php" class="btn-press inline-block bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-8 py-3 rounded-xl tracking-wide transition-all">Submit New Inquiry</a>
        </div>

      </div><!-- /form card -->
    </div><!-- /left content -->

    <!-- 2. RIGHT SIDEBAR (KEPT AS REQUESTED) -->
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
          A condominium unit may be reserved for thirty days by presenting a Reservation Fee of
          <span class="font-bold text-blue-700">PHP 100,000</span> per unit, and the following documents:
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
              <p class="text-sm text-slate-600 mt-1">signed by buyer</p>
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
          <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">!</div>
            <p class="text-blue-700 font-bold uppercase tracking-wide">Please Note</p>
          </div>
          <p class="text-sm leading-7 text-slate-800">
            Completing this webform <span class="font-bold">enlists you for a reservation</span> and secures your intent to reserve the unit. You will be notified once the <span class="font-bold">reservation form is ready</span> for signing and notarization, if required. After submission, you must <span class="font-bold">meet with the owner, HOA, or authorized representative</span> to submit the signed Reservation Agreement and required IDs.
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

<!-- 3. FOOTER (KEPT AS REQUESTED) -->
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
      <button type="button" onclick="proceedSubmit()" class="px-5 py-2 bg-[#0f172a] hover:bg-[#1e293b] text-white rounded-lg text-xs font-bold">Yes, Submit</button>
    </div>
  </div>
</div>

<!-- QR Enlarged Lightbox Modal -->
<?php if ($owner_has_qr): ?>
<div id="qrModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4" onclick="closeQRModal()">
  <div class="bg-white rounded-3xl p-5 max-w-sm w-full shadow-2xl border border-slate-100 text-center" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
      <h3 class="text-sm font-bold text-slate-900"><?= $ownerName ?>'s GCash QR</h3>
      <button type="button" onclick="closeQRModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-3 bg-slate-50 rounded-2xl flex items-center justify-center">
      <img src="<?= htmlspecialchars($owner_qr_path) ?>" alt="GCash QR" class="max-h-[60vh] max-w-full object-contain rounded-xl shadow-sm">
    </div>
    <p class="text-xs text-slate-500 mt-3">Scan with GCash or any supported e-wallet</p>
    <div class="pt-3 flex justify-end">
      <button type="button" onclick="closeQRModal()" class="px-4 py-1.5 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">Close</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 5. JAVASCRIPT -->
<script>
// ======= Timer configuration from original form =======
const expirationSeconds = 30 * 24 * 60 * 60; // 30 days
let reservationStatus = 'pending';

<?php
  $expiresAtMs = !empty($data['reservation_token_expires_at'])
      ? strtotime($data['reservation_token_expires_at']) * 1000
      : (time() + 30 * 24 * 60 * 60) * 1000;
?>
const expiresAt = <?php echo (int)$expiresAtMs; ?>;
const circumference = 2 * Math.PI * 17;

function updateStatus() {
  const now = new Date().getTime();
  const banner = document.getElementById('statusBanner');
  const title = document.getElementById('statusTitle');
  const msg = document.getElementById('statusMsg');
  const countdown = document.getElementById('statusCountdown');
  const minutesBox = document.getElementById('statusMinutes');
  const ring = document.getElementById('ringProgress');
  const ringLabel = document.getElementById('ringLabel');
  const formBody = document.getElementById('formBody');
  const expiredOverlay = document.getElementById('expiredOverlay');
  const submitBtn = document.getElementById('btnSubmitReservation');

  if (now > expiresAt) {
    reservationStatus = 'expired';
    banner.className = 'status-banner rounded-xl border px-7 py-5 flex items-center justify-between gap-5 bg-red-50 border-red-200 shadow-sm';
    document.getElementById('countdownRing').innerHTML = '<svg class="w-11 h-11 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    title.textContent = 'Reservation Link Expired';
    title.className = 'font-bold text-red-800';
    msg.textContent = 'This reservation link has expired. Please submit a new inquiry.';
    msg.className = 'text-sm mt-1 text-red-700';
    minutesBox.textContent = 'EXPIRED';
    minutesBox.className = 'font-mono text-sm font-bold text-red-600 leading-none';
    countdown.textContent = '';
    formBody.classList.add('hidden');
    expiredOverlay.classList.remove('hidden');
    if (submitBtn) submitBtn.disabled = true;
    return;
  }

  const remaining = Math.ceil((expiresAt - now) / 1000);
  const days = Math.floor(remaining / 86400);
  const hours = Math.floor((remaining % 86400) / 3600);
  const fraction = remaining / expirationSeconds;
  const offset = circumference * (1 - fraction);
  const isUrgent = remaining <= 24 * 60 * 60; // last day

  title.textContent = 'Reservation Pending';
  title.className = 'font-bold text-amber-800';
  msg.textContent = 'Please complete the form and submit before the reservation link expires.';
  msg.className = 'text-sm text-slate-600 mt-1';

  minutesBox.textContent = `${String(days).padStart(2, '0')} : ${String(hours).padStart(2, '0')}`;
  countdown.textContent = `Time remaining: ${days} day${days !== 1 ? 's' : ''}, ${hours} hour${hours !== 1 ? 's' : ''}`;

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

// ======= Move-out date calculation =======
const priceBasis = <?= (float)$price_basis ?>;
const leaseDurationStr = <?= json_encode($inqLeaseDuration) ?>;

function parseDurationToMonths(str) {
  if (!str) return 12;
  const s = str.toLowerCase();
  if (s.includes('year')) {
    const match = s.match(/(\d+)/);
    return match ? parseInt(match[1], 10) * 12 : 12;
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
  document.getElementById('moveOutDate').value = moveOutVal;
  document.getElementById('moveOutDateDisplay').value = moveOutVal ? formatDisplayDate(moveOutVal) : '';
}

// ======= Flexible signing checkbox handler =======
function handleFlexibleSigning(cb) {
  const signingInput = document.getElementById('leaseSigningDate');
  if (cb.checked) {
    signingInput.value = '';
    signingInput.disabled = true;
    signingInput.classList.add('bg-slate-100', 'cursor-not-allowed', 'text-slate-400');
  } else {
    signingInput.disabled = false;
    signingInput.classList.remove('bg-slate-100', 'cursor-not-allowed', 'text-slate-400');
  }
}

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
  const reqAmount = priceBasis * pct;

  const formatted = '₱' + reqAmount.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  document.getElementById('dpAmount').textContent = formatted;
  document.getElementById('declaredAmountInput').value = reqAmount;
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
  if (!moveIn.value) {
    alert("Please select your Move-in Date.");
    moveIn.focus();
    return false;
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