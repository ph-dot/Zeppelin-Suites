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
          <span style="height:26px"></span>
          <span style="height:40px"></span>
          <span style="height:52px"></span>
          <span style="height:64px"></span>
          <span style="height:48px"></span>
          <span style="height:34px"></span>
          <span style="height:22px"></span>
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
          <p class="font-mono text-xl font-bold text-orange-500 leading-none" id="statusMinutes">29 : 52</p>
          <p class="text-[10px] font-bold text-orange-400 tracking-widest mt-1">MIN&nbsp;&nbsp;&nbsp;SEC</p>
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
                 <?php if ($is_lease): ?>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5 space-y-5">
                    Lease Duration <span class="text-red-400">*</span>
                  </label>

                  <input 
                    type="text"
                    id="resLeaseDuration"
                    name="lease_duration"
                    value="<?php echo htmlspecialchars($data['lease_duration'] ?? 'Not specified'); ?>"
                    readonly
                    class="zep-input w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-700">
                <?php endif; ?>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <?php echo $is_lease ? 'Move-in Date' : 'Preferred Appointment / Turnover Date'; ?>
                    <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="date" 
                    id="resMoveIn"
                    name="move_in_date"
                    required
                    class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
                </div>

                <?php if ($is_lease): ?>
                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                      Move-out Date <span class="text-red-500">*</span>
                    </label>
                    <input 
                      type="date" 
                      id="resLease"
                      name="move_out_date"
                      required
                      class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
                  </div>
                <?php endif; ?>
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
                type="submit"
                id="submitBtn" 
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

        <!-- Confirmed overlay -->
        <div id="confirmedOverlay" class="hidden p-10 text-center">
          <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p class="text-xl font-bold text-slate-900 mb-2">Reservation Confirmed!</p>
          <p class="text-sm text-slate-500 mb-2">Reservation <span class="font-semibold text-slate-700" style="font-family:'DM Mono',monospace">#101</span> has been successfully submitted.</p>
          <p class="text-xs text-slate-400">Our team will contact you within 24 hours to finalize your move-in details.</p>
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

<?php if (isset($_GET['submitted']) && $_GET['submitted'] == '1'): ?>
  <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-7 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
        <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <h2 class="text-2xl font-bold text-slate-900 mb-2">Submit Complete!</h2>

      <p class="text-slate-600 mb-6">
        Your reservation form has been submitted successfully. Your selected unit is now on hold while the admin reviews your payment proof and details.
      </p>

      <button 
        type="button"
        onclick="closeModal('successModal')"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white font-bold py-3 rounded-xl transition-all">
        Okay
      </button>
    </div>
  </div>
<?php endif; ?>


<?php if (isset($_GET['already_submitted']) && $_GET['already_submitted'] == '1'): ?>
  <div id="alreadySubmittedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-7 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
        <svg class="w-9 h-9 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
      </div>

      <h2 class="text-2xl font-bold text-slate-900 mb-2">Already Submitted</h2>

      <p class="text-slate-600 mb-6">
        A reservation form has already been submitted for this approved unit. Please wait for admin review.
      </p>

      <button 
        type="button"
        onclick="closeModal('alreadySubmittedModal')"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white font-bold py-3 rounded-xl transition-all">
        Okay
      </button>
    </div>
  </div>
<?php endif; ?>

<script>
// ======= Reservation timer configuration =======
const expirationSeconds = 30 * 60; // 30 minutes for demo. Change to 30 * 24 * 60 * 60 for 30 days.
let reservationStatus = 'pending';
const reservationId = 101;

const createdAt = new Date().getTime();
const expiresAt = createdAt + expirationSeconds * 1000;
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
  const mins = Math.floor(remaining / 60);
  const secs = remaining % 60;
  const fraction = remaining / expirationSeconds;
  const offset = circumference * (1 - fraction);

  title.textContent = 'Reservation Pending';
  title.className = 'font-bold text-amber-800';
  msg.textContent = 'Please complete the form and submit before the timer expires.';
  msg.className = 'text-sm text-slate-600 mt-1';

  minutesBox.textContent = `${String(mins).padStart(2, '0')} : ${String(secs).padStart(2, '0')}`;
  countdown.textContent = `Time remaining: ${remaining} second${remaining !== 1 ? 's' : ''}`;

  if (ring) {
    ring.style.strokeDasharray = circumference;
    ring.style.strokeDashoffset = offset;
    ring.setAttribute('stroke', remaining <= 60 ? '#ef4444' : '#b7791f');
  }

  if (ringLabel) {
    ringLabel.textContent = mins > 0 ? mins : secs;
    ringLabel.className = `absolute inset-0 flex items-center justify-center text-[10px] font-bold ${remaining <= 60 ? 'text-red-500' : 'text-amber-700'}`;
  }
}

function submitForm() {
  const now = new Date().getTime();

  if (now > expiresAt || reservationStatus === 'expired') {
    reservationStatus = 'expired';
    updateStatus();
    return;
  }

  const name = document.getElementById('resName').value.trim();
  const email = document.getElementById('resEmail').value.trim();
  const agree = document.getElementById('agreeCheck').checked;

  if (!name || !email) {
    if (!name) document.getElementById('resName').style.borderColor = '#ef4444';
    if (!email) document.getElementById('resEmail').style.borderColor = '#ef4444';
    return;
  }

  if (!agree) {
    document.getElementById('agreeCheck').style.outline = '2px solid #ef4444';
    return;
  }

  reservationStatus = 'confirmed';
  updateStatus();
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
</script>

</body>
</html>
