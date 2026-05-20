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
    <div class="space-y-6">

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
                <input type="text" id="resName" placeholder="e.g. Juan Dela Cruz" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Resident Type <span class="text-red-500">*</span></label>
                <select id="resType" class="zep-select w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base text-slate-500 cursor-pointer">
                  <option value="" disabled selected>Select type</option>
                  <option>New Tenant</option>
                  <option>Existing Tenant</option>
                  <option>Owner</option>
                  <option>Buyer</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                <input type="tel" id="resContact" placeholder="+63 900 000 0000" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="resEmail" placeholder="you@example.com" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Unit Type &amp; No. (Preference) <span class="text-red-500">*</span></label>
                <input type="text" id="resUnit" placeholder="e.g. Studio A — 302" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
              </div>
            </div>
          </section>

          <div class="h-px bg-slate-200"></div>

          <!-- LEASE TERM DETAILS -->
          <section>
            <div class="flex items-center gap-3 mb-5">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">2. Lease Term Details</h3>
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Reservation Type <span class="text-red-500">*</span></label>
                <select id="resReservationType" class="zep-select w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base text-slate-500 cursor-pointer">
                  <option value="" disabled selected>Select reservation type</option>
                  <option value="new-lease">New Lease</option>
                  <option value="lease-renewal">Lease Renewal</option>
                  <option value="unit-transfer">Unit Transfer</option>
                  <option value="short-term">Short-Term Stay</option>
                </select>
                 <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5 space-y-5">Lease Duration <span class="text-red-400">*</span></label>
                    <select id="resLeaseDuration" class="zep-select w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 cursor-pointer transition-all appearance-none" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394a3b8\' stroke-width=\'2\'%3E%3Cpath d=\'m6 9 6 6 6-6\'/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 14px center;">
                    <option value="" disabled selected>Select lease duration</option>
                    <option value="lease immediately">Lease Immediately</option>
                    <option value="for the next 3 months">For the next 3 months</option>
                    <option value="for the next 6 months">For the next 6 months</option>
                    <option value="1 year">1 year</option>
                    <option value="2 years">2 years</option>
                    <option value="longer contract">Longer contract</option>
                    <option value="still deciding">Still deciding</option>
                    </select>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">Move-in Date <span class="text-red-500">*</span></label>
                  <input type="date" id="resMoveIn" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
                </div>

                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">Move-out Date <span class="text-red-500">*</span></label>
                  <input type="date" id="resLease" class="zep-input w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base">
                </div>
              </div>
            </div>
          </section>

          <div class="h-px bg-slate-200"></div>

          <!-- 4. TIN and ID Upload Section -->
        <div class="space-y-5 p-4 border rounded-xl">
            <h2 class="text-lg font-semibold text-slate-900">4. Identification Documents</h2>
            
            <!-- TIN Input -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tax Identification Number (TIN) <span class="text-red-500">*</span></label>
                <input type="text" name="tin" placeholder="Enter your TIN" class="zep-input w-full p-3 border rounded-xl text-base" required>
            </div>

            <!-- First ID -->
            <div class="space-y-2 mt-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">First Government ID <span class="text-red-500">*</span></label>
                <select name="id_type_1" class="zep-select w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base text-slate-500 cursor-pointer" required>
                <option value="" disabled selected>Select ID type</option>
                <option value="passport">Passport</option>
                <option value="driver_license">Driver's License</option>
                <option value="gsis_id">GSIS ID</option>
                <option value="sss_id">SSS ID</option>
                <option value="philhealth_id">PhilHealth ID</option>
                <option value="tin_id">TIN ID</option>
                <option value="postal_id">Postal ID</option>
                <option value="voter_id">Voter’s ID</option>
                <option value="other">Other</option>
                </select>
               <label class="upload-zone w-full p-3 rounded-xl border-dashed border-2 border-slate-300 flex flex-col items-center justify-center cursor-pointer text-slate-500 hover:border-blue-600 hover:bg-slate-50 transition">
                    Click or drag file here
                    <input type="file" name="id_file_1" accept=".jpg,.jpeg,.png,.pdf" class="opacity-0 absolute inset-0 cursor-pointer" required>
                </label>          
            </div>

            <!-- Second ID -->
            <div class="space-y-2 mt-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Second Government ID <span class="text-red-500">*</span></label>
                <select name="id_type_2" class="zep-select w-full h-12 px-5 bg-white border border-slate-300 rounded-xl text-base text-slate-500 cursor-pointer" required>
                <option value="" disabled selected>Select ID type</option>
                <option value="passport">Passport</option>
                <option value="driver_license">Driver's License</option>
                <option value="gsis_id">GSIS ID</option>
                <option value="sss_id">SSS ID</option>
                <option value="philhealth_id">PhilHealth ID</option>
                <option value="tin_id">TIN ID</option>
                <option value="postal_id">Postal ID</option>
                <option value="voter_id">Voter’s ID</option>
                <option value="other">Other</option>
                </select>
               <label class="upload-zone w-full p-3 rounded-xl border-dashed border-2 border-slate-300 flex flex-col items-center justify-center cursor-pointer text-slate-500 hover:border-blue-600 hover:bg-slate-50 transition">
                    Click or drag file here
                    <input type="file" name="id_file_2" accept=".jpg,.jpeg,.png,.pdf" class="opacity-0 absolute inset-0 cursor-pointer" required>
                    </label>          
            </div>
            </div>

            
          <!-- PAYMENT DETAILS -->
          <section>
            <div class="flex items-center gap-3 mb-5">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h.01M11 15h2M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">3. Payment Details</h3>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
              <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_150px] gap-5 items-center">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">Reservation Fee (PHP) <span class="text-red-500">*</span></label>
                  <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base font-bold text-slate-500">₱</span>
                    <input type="text" id="resAmount" value="100,000.00" readonly class="zep-input w-full h-12 pl-9 pr-5 bg-white border border-slate-200 rounded-xl text-base text-slate-500">
                  </div>
                </div>

                <div class="text-sm text-slate-600 leading-relaxed">
                  <p class="font-bold text-slate-900 mb-1">Scan the QR code to pay</p>
                  <p>Use your preferred e-wallet or banking app to complete your payment.</p>
                </div>

                <div class="flex flex-col items-center">
                  <p class="text-xs font-bold text-slate-800 tracking-wide mb-2">SCAN TO PAY</p>
                  <div class="qr-grid">
                    <div class="qr-grid w-48 h-48 flex items-center justify-center">
                    <img src="path/to/your-qr-code.png" alt="Reservation QR Code" class="w-full h-full object-contain rounded-xl shadow-sm">
                    </div>
                  </div>
                  <p class="text-xs text-slate-500 mt-2">Reference ID: <span class="font-bold">#101</span></p>
                </div>
              </div>
            </div>
          </section>

          <!-- UPLOAD PROOF -->
          <section>
            <div class="flex items-center gap-3 mb-4">
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0l-4 4m4-4l4 4"/>
              </svg>
              <h3 class="font-bold text-slate-900 text-base uppercase tracking-wide">4. Upload Proof of Payment <span class="text-red-500">*</span></h3>
            </div>

            <label for="proofUpload" class="upload-zone w-full min-h-[120px] rounded-xl flex flex-col items-center justify-center gap-2 cursor-pointer">
              <svg class="w-9 h-9 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <div class="text-center">
                <p class="text-base font-bold text-slate-800">Click to upload or drag &amp; drop</p>
                <p class="text-sm text-slate-500 mt-1">PNG, JPG, PDF up to 10MB</p>
              </div>
              <span id="uploadFileName" class="text-sm text-emerald-600 font-semibold hidden"></span>
              <input type="file" id="proofUpload" accept=".png,.jpg,.jpeg,.pdf" class="hidden" onchange="handleUpload(this)">
            </label>
            <p class="text-xs text-slate-400 text-center mt-3">Scan the QR code using your preferred e-wallet or banking app to complete your payment.</p>
          </section>

          <div class="h-px bg-slate-200"></div>

          <!-- AGREEMENT + SUBMIT -->
          <section>
            <div class="flex items-start gap-3 mb-7">
              <input type="checkbox" id="agreeCheck" class="mt-1 w-4 h-4 rounded border-slate-300 accent-slate-900 cursor-pointer">
              <label for="agreeCheck" class="text-sm text-slate-700 leading-relaxed cursor-pointer">
                I agree to the <a href="#" class="text-slate-950 font-bold underline hover:no-underline">Terms and Conditions</a> and <a href="#" class="text-slate-950 font-bold underline hover:no-underline">Privacy Policy</a> of Zeppelin Suites.
                I confirm that all information provided is accurate and complete.
              </label>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <p class="text-sm text-slate-400">Reservation ID: <span class="font-bold text-slate-700" style="font-family:'DM Mono',monospace">#101</span></p>
              <button onclick="submitForm()" id="submitBtn" class="btn-press w-full sm:w-auto bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-10 py-3.5 rounded-xl tracking-widest transition-all">
                <span class="inline-flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                  </svg>
                  SUBMIT RESERVATION
                </span>
              </button>
            </div>
          </section>

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

const idTypeSelect = document.getElementById('idTypeSelect');
  const otherIdDiv = document.getElementById('otherIdDiv');
  
  idTypeSelect.addEventListener('change', function() {
    if(this.value === 'other'){
      otherIdDiv.classList.remove('hidden');
    } else {
      otherIdDiv.classList.add('hidden');
    }
  });
</script>

</body>
</html>
