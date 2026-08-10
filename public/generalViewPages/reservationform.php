<?php require_once __DIR__ . '/ActionsGV/loadReservationForm.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Condominium Reservation</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
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
      }
    }
  }
}
</script>
<style>
* { font-family: 'DM Sans', sans-serif; }
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.96); }

.zep-input,
.zep-select {
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.zep-input:focus,
.zep-select:focus {
  outline: none;
  border-color: #0f172a;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.07);
}

.upload-zone {
  border: 2px dashed #bfd0e6;
  transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
}
.upload-zone:hover {
  border-color: #0f172a;
  background: #f8fafc;
  transform: translateY(-1px);
}

.status-banner { transition: all 0.3s ease; }
.countdown-ring { transition: stroke-dashoffset 1s linear; }

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

.logo-bars span {
  display: block;
  width: 4px;
  border-radius: 999px 999px 2px 2px;
  background: linear-gradient(180deg, #d6a246, #f7d27a);
}

.form-card-header {
  background:
    radial-gradient(circle at 100% 0%, rgba(29,78,216,.22), transparent 28%),
    linear-gradient(135deg, #091329 0%, #0f172a 100%);
}

.qr-grid {
  width: 132px;
  height: 132px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 10px;
  box-shadow: 0 12px 25px rgba(15, 23, 42, 0.10);
}

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
</style>
</head>

<body class="bg-slate-50 text-slate-900 min-h-screen">

<!-- HERO HEADER -->
<header class="zep-hero relative overflow-hidden border-b border-slate-200">
  <div class="building-mark hidden md:block"></div>

  <div class="max-w-[1180px] mx-auto px-5 py-8 md:py-9 flex items-center justify-between gap-6 relative">
    <div class="flex items-center gap-7">
      <div class="w-[112px] flex flex-col items-center justify-center">
        <div class="logo-bars flex items-end justify-center gap-1.5 h-14 mb-2">
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

<main class="max-w-[1180px] mx-auto px-5 py-8 md:py-10">

  <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,720px)_360px] gap-9 items-start">

    <!-- LEFT CONTENT -->
    <div class="space-y-8">

      <!-- STATUS BANNER -->
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

      <!-- FORM CARD -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-soft overflow-hidden" id="formCard">

        <div class="form-card-header px-7 py-6">
          <h2 class="text-2xl font-bold text-white tracking-tight">Condominium Reservation Form</h2>
          <p class="text-slate-300 text-sm mt-1">Zeppelin Suites — Please fill in all required fields</p>
        </div>

        <div class="p-7 md:p-8 space-y-8" id="formBody">
          <form action="ActionsGV/submitReservation.php" method="POST" enctype="multipart/form-data" class="space-y-8">
           <input type="hidden" name="reservation_token" value="<?php echo htmlspecialchars($token); ?>">

          <!-- RESIDENT INFO -->
          <section>
            <div class="flex items-center gap-3 mb-5">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.015-8 4.5V20h16v-1.5c0-2.485-3.582-4.5-8-4.5z"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">1. Resident Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name <span class="text-red-500">*</span></label>
               <input 
                  type="text" 
                  id="resName"
                  name="client_name"
                  value="<?php echo htmlspecialchars($data['sender_name']); ?>"
                  readonly
                  class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base"> 
                </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Resident Type <span class="text-red-500">*</span></label>
                <input 
                  type="text"
                  id="resType"
                  name="resident_type"
                  value="<?php echo htmlspecialchars($resident_type); ?>"
                  readonly
                  class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                <input 
                type="tel" 
                id="resContact"
                name="client_contact"
                value="<?php echo htmlspecialchars($data['sender_contact']); ?>"
                readonly
                class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input 
                type="email" 
                id="resEmail"
                name="client_email"
                value="<?php echo htmlspecialchars($data['sender_email']); ?>"
                readonly
                class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
              </div>

              <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                Unit Type &amp; No. <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                id="resUnit"
                value="<?php echo htmlspecialchars($data['unit_type'] . ' — Unit ' . $data['unit_number']); ?>"
                readonly
                class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                Unit Owner
              </label>
              <input 
                type="text"
                id="unitOwnerName"
                value="<?php echo htmlspecialchars($data['owner_name'] ?? 'No owner assigned'); ?>"
                readonly
                class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                Owner Email / Contact
              </label>
              <input 
                type="email"
                id="unitOwnerEmail"
                value="<?php echo htmlspecialchars($data['owner_email'] ?? 'No email available'); ?>"
                readonly
                class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
            </div>
            </div>
          </section>

          <div class="h-px bg-slate-200 my-8"></div>

          <!-- LEASE TERM DETAILS -->
          <section>
            <div class="flex items-center gap-3 mb-5">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">2. Lease Term Details</h3>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                Transaction Type <span class="text-red-500">*</span>
              </label>
             <input 
              type="text"
              id="resTransactionType"
              value="<?php echo htmlspecialchars($transaction_type); ?>"
              readonly
              class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Reservation Type <span class="text-red-500">*</span></label>
                <input 
                  type="text"
                  id="resReservationType"
                  name="reservation_type"
                  value="<?php echo htmlspecialchars($reservation_type); ?>"
                  readonly
                  class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
              </div>

              <?php if ($is_lease): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                      Chosen Move-in Time <span class="text-red-500">*</span>
                    </label>
                    <input 
                      type="text"
                      id="resMoveInTime"
                      name="preferred_move_in_time_display"
                      value="<?php echo htmlspecialchars($data['preferred_move_in_time'] ?? 'Not specified'); ?>"
                      readonly
                      class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                      Lease Duration <span class="text-red-500">*</span>
                    </label>
                    <input 
                      type="text"
                      id="resLeaseDuration"
                      name="lease_duration"
                      value="<?php echo htmlspecialchars($data['lease_duration'] ?? 'Not specified'); ?>"
                      readonly
                      class="zep-input w-full h-12 px-5 bg-slate-100 border border-slate-300 rounded-xl text-base">
                  </div>
                </div>
              <?php endif; ?>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative">
                  <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <?php echo $is_lease ? 'Move-in Date' : 'Preferred Appointment / Turnover Date'; ?>
                    <span class="text-red-500">*</span>
                  </label>
                  <button
                    type="button"
                    id="resMoveInTrigger"
                    class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base flex items-center justify-between text-left">
                    <span id="resMoveInDisplay" class="text-slate-400">Select a date</span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </button>
                  <input type="hidden" id="resMoveIn" name="move_in_date">
                  <div id="resMoveInCalendar" class="zep-calendar-panel hidden absolute left-0 z-30 mt-2 w-72 max-w-[90vw] bg-white border border-slate-200 rounded-xl shadow-xl p-4"></div>
                  <p class="text-xs text-slate-400 mt-1.5" id="resMoveInHint">Dates already reserved for this unit are shown in red and can't be selected.</p>
                </div>

                <?php if ($is_lease): ?>
                  <div class="relative">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                      Move-out Date <span class="text-red-500">*</span>
                    </label>
                    <button
                      type="button"
                      id="resLeaseTrigger"
                      class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base flex items-center justify-between text-left">
                      <span id="resLeaseDisplay" class="text-slate-400">Select a date</span>
                      <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                    </button>
                    <input type="hidden" id="resLease" name="move_out_date">
                    <div id="resLeaseCalendar" class="zep-calendar-panel hidden absolute left-0 z-30 mt-2 w-72 max-w-[90vw] bg-white border border-slate-200 rounded-xl shadow-xl p-4"></div>
                    <p class="text-xs text-slate-400 mt-1.5" id="resLeaseHint">Pick your Move-in Date first — dates that would overlap another tenant's stay are blocked.</p>
                  </div>
                <?php endif; ?>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                  Remarks / Special Requests <span class="text-slate-400 font-normal normal-case">(optional)</span>
                </label>
                <textarea 
                  id="resRemarks"
                  name="remarks"
                  rows="3"
                  maxlength="500"
                  placeholder="Anything else you'd like us to know about your reservation..."
                  class="zep-input w-full px-5 py-3 bg-white border border-slate-300 rounded-xl text-base resize-none"></textarea>
              </div>
            </div>
          </section>

          <div class="h-px bg-slate-200 my-8"></div>
            
          <!-- PAYMENT DETAILS -->
          <section class="pt-2">
            <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-8 items-start">

            <!-- LEFT: YOUR ORIGINAL STEPS -->
            <div class="space-y-6">

              <!-- STEP 1 -->
              <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                  Step 1
                </p>
                <p class="font-bold text-slate-900">Reservation Fee</p>

                <div class="mt-3 relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base font-bold text-slate-500">₱</span>
                 <input 
                  type="text" 
                  id="requiredAmountInput"
                  readonly
                  class="w-full h-12 pl-9 pr-5 bg-white border border-slate-200 rounded-xl text-base text-slate-500">
                </div>

                <p class="text-xs text-slate-500 mt-2">
                 This is the required amount based on your selected payment option.
                </p>
              </div>


              <!-- STEP 2 -->
              <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                  Step 2
                </p>
                <p class="font-bold text-slate-900 mb-1">Scan to Pay</p>
                <p class="text-sm text-slate-600 mb-4">
                  Use GCash or any QR-supported app to complete your reservation.
                </p>

              <div class="flex justify-center my-5">                  <div onclick="openQRModal()"
                    class="w-40 h-40 cursor-pointer hover:scale-105 transition-all duration-200">
                    <img src="../images/QR.jpg" class="w-full h-full object-contain rounded-xl shadow-sm">
                  </div>
                </div>
              </div>


              <!-- STEP 3 -->
              <div class="border-t pt-4">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                Step 3
              </p>

              <label class="block text-sm font-semibold text-slate-700 mb-2">
                GCash Reference Number <span class="text-red-500">*</span>
              </label>

              <input 
                type="text"
                name="payment_reference"
                id="paymentReference"
                placeholder="Enter your GCash reference number"
                required
                class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">

              <label class="block text-sm font-semibold text-slate-700 mb-2 mt-4">
                Amount You Sent <span class="text-red-500">*</span>
              </label>

              <div class="relative">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-base font-bold text-slate-500">₱</span>
                <input 
                  type="number"
                  name="declared_amount"
                  id="declaredAmount"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  required
                  class="zep-input w-full h-12 pl-9 pr-5 bg-white border border-slate-300 rounded-xl text-base">
              </div>
              <p class="text-xs text-slate-500 mt-2">
                Enter the exact amount shown in your GCash payment confirmation. This is compared against your required amount during admin review.
              </p>
            </div>

            </div>


            <!-- RIGHT: BREAKDOWN PANEL -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 h-fit">

              <p class="text-xs font-bold text-slate-500 uppercase mb-3">
                Payment Breakdown
              </p>

              <!-- TOTAL -->
              <div class="mb-4">
                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($price_label); ?></p>
                <p class="text-lg font-bold text-slate-900">
                  ₱<?php echo number_format($price_basis, 2); ?>
                </p>
              </div>

              <!-- DP SELECTOR -->
              <div class="mb-4">
                <p class="text-xs font-bold text-slate-600 mb-2">Down Payment Option</p>

                <select 
                  id="dpOption"
                  name="payment_percentage"
                  required
                  class="w-full h-10 border border-slate-200 rounded-lg text-sm px-2">
                  <option value="0.35">35% Down Payment</option>
                  <option value="0.50">50% Down Payment</option>
                  <option value="0.75">75% Down Payment</option>
                  <option value="1.00">Full Payment</option>
                </select>
              </div>

              <!-- BREAKDOWN -->
              <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-600">Required Amount</span>
                <span class="font-semibold" id="dpAmount">₱0.00</span>
              </div>

              <div class="flex justify-between border-t pt-2">
                <span class="text-slate-800 font-semibold">Payment Status</span>
                <span class="font-bold text-amber-600">Pending Admin Review</span>
              </div>
            </div>

            </div>
          </div>
          </section>

          <!-- UPLOAD PROOF -->
          <section class="pt-2">
            <div class="flex items-center gap-3 mb-5">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0l-4 4m4-4l4 4"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">4. Upload Proof of Payment <span class="text-red-500">*</span></h3>
            </div>

            <label for="proofUpload" class="upload-zone w-full min-h-[140px] rounded-xl flex flex-col items-center justify-center gap-3 cursor-pointer">
              <svg class="w-9 h-9 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <div class="text-center">
                <p class="text-base font-bold text-slate-800">Click to upload or drag &amp; drop</p>
                <p class="text-sm text-slate-500 mt-1">PNG, JPG, PDF up to 10MB</p>
              </div>
              <span id="uploadFileName" class="text-sm text-emerald-600 font-semibold hidden"></span>
              <input 
                type="file" 
                name="payment_proof"
                id="proofUpload" 
                accept=".png,.jpg,.jpeg,.pdf" 
                class="hidden" 
                onchange="handleUpload(this)"
                required>
            </label>
            <p class="text-xs text-slate-400 text-center mt-3">Scan the QR code using your preferred e-wallet or banking app to complete your payment.</p>
          </section>

          <div class="h-px bg-slate-200 my-8"></div>

          <!-- AGREEMENT + SUBMIT -->
          <section class="pt-2">
            <div class="flex items-start gap-3 mb-8">
              <input 
              type="checkbox" 
              id="agreeCheck"
              name="agreement"
              required
              class="mt-1 w-4 h-4 rounded border-slate-300 accent-slate-900 cursor-pointer">
              <label for="agreeCheck" class="text-sm text-slate-700 leading-relaxed cursor-pointer">
                I agree to the <a href="#" class="text-slate-950 font-bold underline hover:no-underline">Terms and Conditions</a> and <a href="#" class="text-slate-950 font-bold underline hover:no-underline">Privacy Policy</a> of Zeppelin Suites.
                I confirm that all information provided is accurate and complete.
              </label>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pt-4">
             <p class="text-sm text-slate-400">
                Inquiry ID:
                <span class="font-bold text-slate-700" style="font-family:'DM Mono',monospace">
                  #<?php echo htmlspecialchars($data['inq_id']); ?>
                </span>
              </p>
                <button 
                type="button"
                onclick="openReservationModal()"
                class="btn-press w-full sm:w-auto bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-10 py-3.5 rounded-xl tracking-widest transition-all">
                <span class="inline-flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                  </svg>
                  SUBMIT RESERVATION
                </span>
              </button>
            </div>
          </section>
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
          <a href="../generalViewPages/contact.html" class="btn-press inline-block bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-8 py-3 rounded-xl tracking-wide transition-all">Submit New Inquiry</a>
        </div>

      </div><!-- /form card -->
    </div><!-- /left content -->

    <!-- RIGHT SIDEBAR -->
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
               Completing this webform <span class="font-bold">enlists you for a reservation</span> and secures your intent to reserve the unit. You will be notified once the <span class="font-bold">reservation form is ready</span> for signing and notarization, if required. After submission, you must <span class="font-bold">meet with the owner, HOA, or authorized representative</span> to submit the signed Reservation Agreement and required IDs. </p>
        </div>
      </div>

      

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

<footer class="bg-slate-950 text-slate-300">
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
    <p class="text-slate-500">© 2024 Zeppelin Suites. All rights reserved.</p>
  </div>
</footer>

<div id="reservationModal" 
  class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">

    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-7">

      <h2 class="text-xl font-bold text-slate-900 mb-3">
        Confirm Reservation Submission
      </h2>

      <p class="text-sm text-slate-600 leading-relaxed mb-6">
        Please review your information and payment details before submitting your reservation.
        Are you sure you want to continue?
      </p>

      <div class="flex justify-end gap-3">

        <button 
        type="button"
        onclick="closeReservationModal()"
        class="px-5 py-2 border rounded-xl text-sm font-semibold">
          Go Back
        </button>

        <button
        type="button"
        onclick="submitReservationForm()"
        class="px-5 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold">
          Yes, Submit
        </button>

      </div>

    </div>

  </div>

<script>
// ======= Reservation timer configuration =======
// The link is valid for 30 days from the moment it was generated (server-side),
// NOT from the moment this page happens to be opened — so it keeps counting
// down even if the recipient never opens it right away.
const expirationSeconds = 30 * 24 * 60 * 60; // 30 days
let reservationStatus = 'pending';
const reservationId = 101;

<?php
  // Real expiry timestamp set when the reservation token was created (see
  // respondApprovalRequest.php). Falls back to "now + 30 days" only if for
  // some reason it isn't set, so the timer never silently breaks.
  $expiresAtMs = !empty($data['reservation_token_expires_at'])
      ? strtotime($data['reservation_token_expires_at']) * 1000
      : (time() + 30 * 24 * 60 * 60) * 1000;
?>
const expiresAt = <?php echo (int)$expiresAtMs; ?>;
const createdAt = expiresAt - expirationSeconds * 1000;
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
  const confirmedOverlay = document.getElementById('confirmedOverlay');
  const submitBtn = document.getElementById('submitBtn');

  if (reservationStatus === 'confirmed') {
    banner.className = 'status-banner rounded-xl border px-7 py-5 flex items-center justify-between gap-5 bg-emerald-50 border-emerald-200 shadow-sm';
    document.getElementById('countdownRing').innerHTML = '<svg class="w-11 h-11 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    title.textContent = 'Reservation #' + reservationId + ' Confirmed!';
    title.className = 'font-bold text-emerald-800';
    msg.textContent = 'Your reservation has been successfully submitted.';
    msg.className = 'text-sm mt-1 text-emerald-700';
    minutesBox.textContent = 'DONE';
    minutesBox.className = 'font-mono text-xl font-bold text-emerald-600 leading-none';
    countdown.textContent = '';
    formBody.classList.add('hidden');
    expiredOverlay.classList.add('hidden');
    confirmedOverlay.classList.remove('hidden');
    return;
  }

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
    confirmedOverlay.classList.add('hidden');
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


function handleUpload(input) {
  const label = document.getElementById('uploadFileName');
  if (input.files && input.files[0]) {
    label.textContent = '✓ ' + input.files[0].name;
    label.classList.remove('hidden');
  }
}

document.querySelectorAll('.zep-input').forEach(el => {
  el.addEventListener('input', () => { el.style.borderColor = ''; });
});

setInterval(updateStatus, 1000);
updateStatus();

// ======= Unit availability (blocked dates) for the calendar picker =======
// Existing reservations on this unit (not cancelled/rejected) — rendered as
// red, unselectable dates so two tenants/buyers can't be booked over each other.
const blockedRanges = <?php echo json_encode($blocked_ranges); ?>;
console.log('Blocked ranges for unit #<?php echo (int)$data['unit_id']; ?>:', blockedRanges);

function expandRangeToDates(start, end) {
  const dates = [];
  let d = new Date(start + 'T00:00:00');
  const endD = new Date(end + 'T00:00:00');
  while (d <= endD) {
    dates.push(toISODate(d));
    d.setDate(d.getDate() + 1);
  }
  return dates;
}

function toISODate(d) {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function formatDisplayDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

const blockedDateSet = new Set();
blockedRanges.forEach(r => expandRangeToDates(r.start, r.end).forEach(d => blockedDateSet.add(d)));
const sortedBlockedDates = Array.from(blockedDateSet).sort();

function firstBlockedOnOrAfter(dateStr) {
  for (const b of sortedBlockedDates) {
    if (b >= dateStr) return b;
  }
  return null;
}

const todayISO = toISODate(new Date());

// ======= Generic calendar picker =======
function initDatePicker({ triggerId, panelId, hiddenId, displayId, hintId, minDateFn, isDisabledFn, guardFn, onSelect }) {
  const trigger = document.getElementById(triggerId);
  const panel = document.getElementById(panelId);
  const hidden = document.getElementById(hiddenId);
  const display = document.getElementById(displayId);
  const hint = hintId ? document.getElementById(hintId) : null;
  if (!trigger || !panel || !hidden || !display) return null;

  let viewDate = new Date();
  viewDate.setDate(1);

  function isDisabled(dateStr) {
    const min = minDateFn ? minDateFn() : null;
    if (min && dateStr < min) return true;
    if (blockedDateSet.has(dateStr)) return true;
    if (isDisabledFn && isDisabledFn(dateStr)) return true;
    return false;
  }

  function render() {
    const year = viewDate.getFullYear();
    const month = viewDate.getMonth();
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const startWeekday = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const selected = hidden.value;

    let html = `
      <div class="flex items-center justify-between mb-3">
        <button type="button" data-nav="prev" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-600 font-bold">&#8249;</button>
        <p class="text-sm font-bold text-slate-800">${monthNames[month]} ${year}</p>
        <button type="button" data-nav="next" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-600 font-bold">&#8250;</button>
      </div>
      <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-slate-400 mb-1">
        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
      </div>
      <div class="grid grid-cols-7 gap-1 text-sm">`;

    for (let i = 0; i < startWeekday; i++) html += `<span></span>`;

    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const blocked = blockedDateSet.has(dateStr);
      const disabled = isDisabled(dateStr);
      const isSelected = dateStr === selected;

      let cls = 'w-9 h-9 flex items-center justify-center rounded-lg text-xs font-semibold ';
      if (isSelected) {
        cls += 'bg-slate-900 text-white';
      } else if (blocked) {
        cls += 'bg-red-100 text-red-500 cursor-not-allowed';
      } else if (disabled) {
        cls += 'text-slate-300 cursor-not-allowed';
      } else {
        cls += 'text-slate-700 hover:bg-slate-100 cursor-pointer';
      }

      html += `<button type="button" data-date="${dateStr}" title="${blocked ? 'Already reserved' : ''}" ${disabled ? 'disabled' : ''} class="${cls}">${day}</button>`;
    }

    html += `</div>
      <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100 text-[10px] font-semibold">
        <span class="flex items-center gap-1 text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-red-200 inline-block"></span> Reserved</span>
        <span class="flex items-center gap-1 text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-slate-900 inline-block"></span> Selected</span>
      </div>`;

    panel.innerHTML = html;

    panel.querySelectorAll('[data-nav]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        viewDate.setMonth(viewDate.getMonth() + (btn.dataset.nav === 'next' ? 1 : -1));
        render();
      });
    });

    panel.querySelectorAll('[data-date]:not([disabled])').forEach(btn => {
      btn.addEventListener('click', () => {
        hidden.value = btn.dataset.date;
        display.textContent = formatDisplayDate(btn.dataset.date);
        display.classList.remove('text-slate-400');
        display.classList.add('text-slate-900');
        panel.classList.add('hidden');
        if (onSelect) onSelect(btn.dataset.date);
        render();
      });
    });
  }

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    if (guardFn && !guardFn()) return;
    document.querySelectorAll('.zep-calendar-panel').forEach(p => { if (p !== panel) p.classList.add('hidden'); });
    const willOpen = panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    if (willOpen) {
      if (hidden.value) viewDate = new Date(hidden.value + 'T00:00:00');
      render();
    }
  });

  document.addEventListener('click', (e) => {
    if (panel.classList.contains('hidden')) return;
    if (!panel.contains(e.target) && e.target !== trigger && !trigger.contains(e.target)) {
      panel.classList.add('hidden');
    }
  });

  return { render, refresh: render, flashHint: () => {
    if (!hint) return;
    const original = hint.textContent;
    const originalClass = hint.className;
    hint.textContent = 'Please select a Move-in Date first.';
    hint.className = 'text-xs text-red-500 font-semibold mt-1.5';
    setTimeout(() => { hint.textContent = original; hint.className = originalClass; }, 2500);
  }};
}

const moveInPicker = initDatePicker({
  triggerId: 'resMoveInTrigger',
  panelId: 'resMoveInCalendar',
  hiddenId: 'resMoveIn',
  displayId: 'resMoveInDisplay',
  hintId: 'resMoveInHint',
  minDateFn: () => todayISO,
  onSelect: (dateStr) => {
    const moveOutHidden = document.getElementById('resLease');
    if (moveOutHidden && moveOutHidden.value && moveOutHidden.value < dateStr) {
      moveOutHidden.value = '';
      const moveOutDisplay = document.getElementById('resLeaseDisplay');
      if (moveOutDisplay) {
        moveOutDisplay.textContent = 'Select a date';
        moveOutDisplay.classList.add('text-slate-400');
        moveOutDisplay.classList.remove('text-slate-900');
      }
    }
    if (moveOutPicker) moveOutPicker.refresh();
  }
});

let moveOutPicker = null;
if (document.getElementById('resLeaseTrigger')) {
  moveOutPicker = initDatePicker({
    triggerId: 'resLeaseTrigger',
    panelId: 'resLeaseCalendar',
    hiddenId: 'resLease',
    displayId: 'resLeaseDisplay',
    hintId: 'resLeaseHint',
    minDateFn: () => document.getElementById('resMoveIn').value || todayISO,
    isDisabledFn: (dateStr) => {
      const moveIn = document.getElementById('resMoveIn').value;
      if (!moveIn) return true;
      const limit = firstBlockedOnOrAfter(moveIn);
      return limit ? dateStr >= limit : false;
    },
    guardFn: () => {
      const moveIn = document.getElementById('resMoveIn').value;
      if (!moveIn) {
        if (moveOutPicker) moveOutPicker.flashHint();
        return false;
      }
      return true;
    }
  });
}

const priceBasis = <?php echo json_encode((float)$price_basis); ?>;
const dpOption = document.getElementById("dpOption");
const dpAmount = document.getElementById("dpAmount");
const requiredAmountInput = document.getElementById("requiredAmountInput");

function updatePaymentAmount() {
  const percentage = parseFloat(dpOption.value);
  const requiredAmount = priceBasis * percentage;

  const formattedAmount = "₱" + requiredAmount.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  dpAmount.textContent = formattedAmount;
  requiredAmountInput.value = requiredAmount.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

dpOption.addEventListener("change", updatePaymentAmount);
updatePaymentAmount();

function closeModal(modalId) {
  const modal = document.getElementById(modalId);

  if (modal) {
    modal.remove();
  }

  const url = new URL(window.location.href);
  url.searchParams.delete('submitted');
  url.searchParams.delete('already_submitted');
  window.history.replaceState({}, document.title, url.toString());
}

function openReservationModal(){
    const moveIn = document.getElementById('resMoveIn');
    const moveOut = document.getElementById('resLease');

    if (moveIn && !moveIn.value) {
      document.getElementById('resMoveInHint').textContent = 'Please select a date before submitting.';
      document.getElementById('resMoveInHint').className = 'text-xs text-red-500 font-semibold mt-1.5';
      document.getElementById('resMoveInTrigger').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    if (moveOut && !moveOut.value) {
      document.getElementById('resLeaseHint').textContent = 'Please select a date before submitting.';
      document.getElementById('resLeaseHint').className = 'text-xs text-red-500 font-semibold mt-1.5';
      document.getElementById('resLeaseTrigger').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    document.getElementById("reservationModal")
    .classList.remove("hidden");

    document.getElementById("reservationModal")
    .classList.add("flex");

}
function closeReservationModal(){
    document.getElementById("reservationModal")
    .classList.add("hidden");

    document.getElementById("reservationModal")
    .classList.remove("flex");

}
function submitReservationForm(){
    document.querySelector("form[action='ActionsGV/submitReservation.php']")
    .submit();
}
</script>

</body>
</html>