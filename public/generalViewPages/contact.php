<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zeppelin Suites — Contact</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="navbar.js" defer></script>
  <script src="footer.js" defer></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .footer-bg { background-color: #c4c4c4; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
    /* Input focus — matching login.html */
    .zep-input:focus { outline: none; border-color: #18181b; }
    .zep-select:focus { outline: none; border-color: #18181b; }
  </style>
</head>
<body class="bg-white text-zinc-900">

<!-- ── NAV ──────────────────────────────────────────────── -->
<div id="navbar"></div>

<!-- ── PAGE HEADER ───────────────────────────────────────── -->
<section class="px-6 md:px-16 lg:px-24 xl:px-32 pt-16 pb-10">
  <p class="text-xs tracking-widest uppercase text-zinc-400 mb-2">Get in Touch</p>
  <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 mb-3">Submit an Inquiry</h1>
  <p class="text-zinc-500 max-w-lg">Send us an inquiry and we'll respond back as soon as possible. Our team typically replies within 1–2 business days.</p>
</section>

<!-- ── MAIN CONTENT ──────────────────────────────────────── -->
<section class="px-6 md:px-16 lg:px-24 xl:px-32 pb-24">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 max-w-6xl mx-auto">

    <!-- ── LEFT: CONTACT FORM ────────────────────────────── -->
   <div class="lg:col-span-2 border border-zinc-200 rounded-2xl p-8 md:p-10">
    
       <form id="contactForm" action="ActionsGV/inquiryInput.php" method="POST" class="space-y-6" novalidate>
        <?php if ($errorMessage): ?>
          <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?= htmlspecialchars($errorMessage) ?></span>
          </div>
        <?php endif; ?>

        <!-- Name -->
        <div>
          <label for="sender_name" class="block text-sm font-semibold text-zinc-800 mb-2">Name:</label>
          <input type="text" name="sender_name" id="sender_name" placeholder="Your full name" required
            class="zep-input w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
        </div>

        <!-- Email + Phone -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="sender_email" class="block text-sm font-semibold text-zinc-800 mb-2">Email:</label>
            <input type="email" name="sender_email" id="sender_email" placeholder="your@email.com" required
              class="zep-input w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
            <p id="emailError" class="hidden text-xs text-rose-600 mt-1.5 flex items-center gap-1.5 font-medium transition-all">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span id="emailErrorText">Please enter a valid email address (e.g. name@domain.com)</span>
            </p>
          </div>
          <div>
            <label for="sender_contact" class="block text-sm font-semibold text-zinc-800 mb-2">Phone:</label>
            <input type="tel" name="sender_contact" id="sender_contact" placeholder="09XX-XXX-XXXX or +63 9XX..." required
              class="zep-input w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
            <p id="phoneError" class="hidden text-xs text-rose-600 mt-1.5 flex items-center gap-1.5 font-medium transition-all">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span id="phoneErrorText">Please enter a valid phone number (e.g. 0917-123-4567 or +63 917 123 4567)</span>
            </p>
          </div>
        </div>

<!-- Inquiry Type -->
  <div>
    <label class="block text-sm font-semibold text-zinc-800 mb-2">Inquiry Type:</label>
    <div class="relative">
      <select name="inquiry_type" id="inquiry_type" class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors" required>
        <option value="" disabled selected>Choose option</option>
        <option value="Unit Lease / Rental Reservation">Unit Lease / Rental Reservation</option>
        <option value="Buy / Purchase a Unit (Resale)">Buy / Purchase a Unit (Resale)</option>
        <option value="General Inquiry & Amenities">General Inquiry & Amenities</option>
        <option value="Other Concerns">Other Concerns</option>
      </select>
      <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center">
        <svg width="12" height="8" viewBox="0 0 10 6" fill="none"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
    </div>
  </div>

  <!-- Unit Preference (Existing) -->
  <div id="unit-preference" style="display: none;">
    <label class="block text-sm font-semibold text-zinc-800 mb-2">Unit Preference:</label>
    <div class="relative">
      <select
        name="Preferred_unit_id"
        id="preferred_unit_id"
        disabled
        class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors"> <option value="" disabled selected>Choose option</option>
        <option>Studio Type A</option>
        <option>Studio Type B</option>
        <option>One Bedroom</option>
        <option>Two Bedroom</option>
      </select>
      <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center">
        <svg width="12" height="8" viewBox="0 0 10 6" fill="none"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
    </div>
  </div>

  <!-- LEASE DURATION: WRAPPED IN A DIV WITH ID -->
  <div id="preferred-move-in-container" style="display: none;">
    <label
      for="preferred_move_in_time"
      class="block text-sm font-semibold text-zinc-800 mb-2">
      When do you plan to move in?
    </label>

    <div class="relative">
      <select
        name="preferred_move_in_time"
        id="preferred_move_in_time"
        disabled
        class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors">

        <option value="" disabled selected>Choose option</option>
        <option value="Immediately (Within 30 days)">Immediately (Within 30 days)</option>
        <option value="Next Month (1-2 months)">Next Month (1-2 months)</option>
        <option value="In 2-3 Months">In 2-3 Months</option>
        <option value="In 3-6 Months">In 3-6 Months</option>
        <option value="Flexible / Not sure yet">Flexible / Not sure yet</option>
      </select>

      <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center">
        <svg width="12" height="8" viewBox="0 0 10 6" fill="none">
          <path
            d="m1 1 4 4 4-4"
            stroke="#71717b"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"/>
        </svg>
      </div>
    </div>
  </div>


  <!-- Preferred Lease Duration -->
  <div id="lease-duration-container" style="display: none;">
    <label
      for="lease_duration"
      class="block text-sm font-semibold text-zinc-800 mb-2">
      Preferred Lease Duration:
    </label>

    <div class="relative">
      <select
        name="lease_duration"
        id="lease_duration"
        disabled
        class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors">

        <option value="" disabled selected>Choose option</option>
        <option value="3 months">3 months</option>
        <option value="6 months">6 months</option>
        <option value="1 year">1 year</option>
        <option value="2 years">2 years</option>
        <option value="Longer than 2 years">Longer than 2 years</option>
        <option value="Not sure yet">Not sure yet</option>
      </select>

      <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center">
        <svg width="12" height="8" viewBox="0 0 10 6" fill="none">
          <path
            d="m1 1 4 4 4-4"
            stroke="#71717b"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"/>
        </svg>
      </div>
    </div>
  </div>


        <!-- Message -->
        <div>
          <label class="block text-sm font-semibold text-zinc-800 mb-2">Message:</label>
          <textarea name="Message" rows="5" placeholder="Write your message here..." required
            class="zep-input w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none resize-none"></textarea>
        </div>

        <!-- Submit -->
        <div class="flex justify-end pt-2">
          <button type="button" onclick="openInquiryModal()" class="btn-primary bg-zinc-900 text-white px-10 py-3.5 font-bold text-sm tracking-widest uppercase hover:bg-zinc-700 active:scale-95 transition-all rounded-full">
            Submit
          </button>
        </div>
      </form>
    </div>

    <!-- ── RIGHT: CONTACT DETAILS ─────────────────────────── -->
    <div class="bg-zinc-900 rounded-2xl p-8 text-white flex flex-col gap-8">
      <div>
        <h2 class="text-xl font-bold mb-6">Contact:</h2>

        <!-- Address -->
        <div class="flex items-start gap-3 mb-5">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <p class="font-semibold text-sm text-white/90 mb-1">Address</p>
            <p class="text-sm text-white/60 leading-relaxed">Zeppelin Street, Hensonville, Angeles City, Pampanga, Philippines 2009</p>
          </div>
        </div>

        <!-- Phone -->
        <div class="flex items-start gap-3 mb-5">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          </div>
          <div>
            <p class="font-semibold text-sm text-white/90 mb-1">Phone  & Mobile Numbers</p>
            <p class="text-sm text-white/60">+645 304 3016</p>
            <p class="text-sm text-white/60">+63998 224 3692</p>
            <p class="text-sm text-white/60">+63916 449 1253</p>
          </div>
        </div>

        <!-- Email -->
        <div class="flex items-start gap-3 mb-5">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <div>
            <p class="font-semibold text-sm text-white/90 mb-1">Email</p>
            <a class="text-sm text-amber-600 hover:text-amber-500" href="mailto:sales@zeppelinsuites.com">sales@zeppelinsuites.com</a>
          </div>
        </div>

        <!-- Hours -->
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <p class="font-semibold text-sm text-white/90 mb-1">Office Hours</p>
            <p class="text-sm text-white/60">Open Daily From 8am to 5pm</p>
          </div>
        </div>
      </div>
     
      <!-- Map link -->
      <a 
        href="https://www.google.com/maps/search/?api=1&query=Zeppelin+Suites+Fields+Avenue+Angeles+City" 
        target="_blank" 
        rel="noopener noreferrer"
        class="relative block w-full aspect-[4/3] bg-white/10 rounded-xl overflow-hidden cursor-pointer hover:opacity-90 transition-opacity"
      >
        <div class="w-full h-full flex items-center justify-center text-center">
          <img 
            src="../images/ZeppelinSuitesMap.png" 
            alt="Map showing Zeppelin Suites location in Angeles City"
            class="w-full h-full object-cover"
            onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-zinc-200 text-zinc-400 font-bold text-xs text-center px-6\'>MAP IMAGE NOT FOUND<br>(add zeppelin-location-map.png to /images)</div>'"
          >
        </div>
      </a>

      <!-- Social links -->
      <div>
        <p class="text-xs text-white/50 mb-3 uppercase tracking-widest">Follow Us</p>
        <div class="flex gap-3">
          <a href="https://www.facebook.com/zeppilinsuites2015" class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-lg flex items-center justify-center transition-colors">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <a href="https://www.instagram.com/zeppelinsuites" class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-lg flex items-center justify-center transition-colors">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── PREMIUM FOOTER ────────────────────────────────────────────── -->
<div id="footer"></div>

<div id="inquiryModal" 
class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">

    <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-xl">

        <h2 class="text-xl font-bold text-zinc-900 mb-3">
            Confirm Submission
        </h2>

        <p class="text-sm text-zinc-600 mb-6">
            Please confirm that all provided information is correct before submitting your inquiry.
        </p>

        <div class="flex justify-end gap-3">

            <button onclick="closeInquiryModal()"
            class="px-5 py-2 rounded-full border border-zinc-300">
                Cancel
            </button>

            <button onclick="submitInquiry()"
            class="px-5 py-2 rounded-full bg-zinc-900 text-white">
                Confirm
            </button>

        </div>

    </div>

</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
  const inquiryTypeSelect = document.getElementById("inquiry_type");

  const unitPreferenceContainer =
    document.getElementById("unit-preference");

  const unitPreferenceSelect =
    document.getElementById("preferred_unit_id");

  const preferredMoveInContainer =
    document.getElementById("preferred-move-in-container");

  const preferredMoveInSelect =
    document.getElementById("preferred_move_in_time");

  const leaseDurationContainer =
    document.getElementById("lease-duration-container");

  const leaseDurationSelect =
    document.getElementById("lease_duration");

  function toggleFields() {
    const inquiryType = (inquiryTypeSelect.value || '').toLowerCase();

    const isLeaseFlow =
      inquiryType.includes('lease') ||
      inquiryType.includes('rental') ||
      inquiryType.includes('unit reservation');

    const isResaleFlow =
      inquiryType.includes('resale') ||
      inquiryType.includes('buy') ||
      inquiryType.includes('purchase');

    const needsUnitPreference = isLeaseFlow || isResaleFlow;
    const needsLeaseDetails = isLeaseFlow;

    // Unit preference
    unitPreferenceContainer.style.display =
      needsUnitPreference ? "block" : "none";

    unitPreferenceSelect.disabled = !needsUnitPreference;
    unitPreferenceSelect.required = needsUnitPreference;

    // Move-in time
    preferredMoveInContainer.style.display =
      needsLeaseDetails ? "block" : "none";

    preferredMoveInSelect.disabled = !needsLeaseDetails;
    preferredMoveInSelect.required = needsLeaseDetails;

    // Lease duration
    leaseDurationContainer.style.display =
      needsLeaseDetails ? "block" : "none";

    leaseDurationSelect.disabled = !needsLeaseDetails;
    leaseDurationSelect.required = needsLeaseDetails;

    if (!needsUnitPreference) {
      unitPreferenceSelect.value = "";
    }

    if (!needsLeaseDetails) {
      preferredMoveInSelect.value = "";
      leaseDurationSelect.value = "";
    }
  }

  toggleFields();

  inquiryTypeSelect.addEventListener("change", toggleFields);

  // ── Email & Phone Security Validation ─────────────────────
  const emailInput = document.getElementById("sender_email");
  const emailError = document.getElementById("emailError");
  const emailErrorText = document.getElementById("emailErrorText");

  const phoneInput = document.getElementById("sender_contact");
  const phoneError = document.getElementById("phoneError");
  const phoneErrorText = document.getElementById("phoneErrorText");

  let emailTouched = false;
  let phoneTouched = false;

  // Known typos for major providers
  const typoDomains = {
    "gmaidla.com": "gmail.com", "gmaild.com": "gmail.com", "gamil.com": "gmail.com",
    "gmial.com": "gmail.com", "gmaill.com": "gmail.com", "gmai.com": "gmail.com",
    "gmal.com": "gmail.com", "gmeil.com": "gmail.com", "gmaio.com": "gmail.com",
    "gmail.co": "gmail.com", "gmaill.co": "gmail.com", "yaho.com": "yahoo.com",
    "yahooo.com": "yahoo.com", "yaho.co": "yahoo.com", "ymail.co": "yahoo.com",
    "outlok.com": "outlook.com", "outloo.com": "outlook.com", "hotmial.com": "hotmail.com",
    "hotmai.com": "hotmail.com", "iclou.com": "icloud.com", "icld.com": "icloud.com"
  };

  const disposableDomains = [
    "tempmail.com", "10minutemail.com", "mailinator.com",
    "guerrillamail.com", "throwawaymail.com", "yopmail.com"
  ];

  function validateEmail(val) {
    const trimmed = (val || "").trim();
    if (!trimmed) {
      return { valid: false, message: "Email address is required." };
    }
    if (/\s/.test(trimmed)) {
      return { valid: false, message: "Email address cannot contain spaces." };
    }
    if (!trimmed.includes("@")) {
      return { valid: false, message: "Email must include '@' (e.g. name@domain.com)." };
    }
    const parts = trimmed.split("@");
    if (parts.length !== 2 || !parts[0] || !parts[1]) {
      return { valid: false, message: "Please provide a valid username and domain." };
    }
    const user = parts[0].toLowerCase();
    const domain = parts[1].toLowerCase();

    if (!domain.includes(".")) {
      return { valid: false, message: "Email domain must include an extension (e.g. .com, .ph)." };
    }
    const domainParts = domain.split(".");
    const tld = domainParts[domainParts.length - 1];
    if (tld.length < 2) {
      return { valid: false, message: "Domain extension must be at least 2 letters (e.g. .com)." };
    }

    // Check known typo domains
    if (typoDomains[domain]) {
      return { valid: false, message: "Please enter a valid email domain provider (e.g. name@gmail.com)." };
    }

    // Check disposable domains
    if (disposableDomains.includes(domain)) {
      return { valid: false, message: "Please enter a valid personal or business email domain." };
    }

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(trimmed)) {
      return { valid: false, message: "Please enter a valid email format (e.g. name@domain.com)." };
    }
    return { valid: true };
  }

  function validatePhone(val) {
    const trimmed = (val || "").trim();
    if (!trimmed) {
      return { valid: false, message: "Phone number is required." };
    }

    if (/[^0-9+\s\-()]/.test(trimmed)) {
      return { valid: false, message: "Phone number can only contain digits, '+', '-', and spaces." };
    }

    const hasPlus = trimmed.startsWith("+");
    const digitsOnly = trimmed.replace(/\D/g, "");

    if (digitsOnly.length < 7) {
      return { valid: false, message: "Phone number is too short (minimum 7 digits)." };
    }
    if (digitsOnly.length > 15) {
      return { valid: false, message: "Phone number is too long (maximum 15 digits)." };
    }

    // Check dummy repeating digits (e.g. 09111111111, 09000000000)
    if (/(.)\1{5,}/.test(digitsOnly)) {
      return { valid: false, message: "Please enter a valid, active phone number (repeated digits detected)." };
    }

    // Check dummy sequential numbers (e.g. 12345678, 87654321)
    if (digitsOnly.includes("12345678") || digitsOnly.includes("87654321") || digitsOnly.includes("01234567")) {
      return { valid: false, message: "Please enter a valid, active phone number (sequential pattern detected)." };
    }

    if (hasPlus) {
      if (digitsOnly.startsWith("63")) {
        if (digitsOnly.length !== 12 || !digitsOnly.startsWith("639")) {
          return { valid: false, message: "PH mobile with +63 must have 12 digits (e.g. +63 917 123 4567)." };
        }
      } else {
        if (digitsOnly.length < 8 || digitsOnly.length > 15) {
          return { valid: false, message: "Please enter a valid international number with country code (e.g. +1 555 123 4567)." };
        }
      }
      return { valid: true };
    }

    if (digitsOnly.startsWith("09")) {
      if (digitsOnly.length !== 11) {
        return { valid: false, message: "Philippine mobile number must be 11 digits (e.g. 0917-123-4567)." };
      }
      const prefix4 = digitsOnly.substring(0, 4);
      if (['0900', '0901', '0902', '0903', '0904'].includes(prefix4)) {
        return { valid: false, message: `Prefix '${prefix4}' is not a valid Philippine mobile network prefix.` };
      }
      return { valid: true };
    }

    if (digitsOnly.startsWith("639")) {
      if (digitsOnly.length !== 12) {
        return { valid: false, message: "Philippine mobile number must be 12 digits (e.g. 639171234567)." };
      }
      return { valid: true };
    }

    if (digitsOnly.startsWith("0")) {
      if (digitsOnly.length < 9 || digitsOnly.length > 11) {
        return { valid: false, message: "Landline number must be 9–11 digits including area code." };
      }
      return { valid: true };
    }

    return { valid: false, message: "Please enter a valid phone number (e.g. 0917-123-4567 or +63 917 123 4567)." };
  }

  function applyValidationState(input, errorContainer, errorTextElem, result, showUI) {
    if (!result.valid) {
      if (showUI) {
        errorTextElem.textContent = result.message;
        errorContainer.classList.remove("hidden");
        input.classList.add("border-rose-500", "focus:border-rose-500", "bg-rose-50/20");
        input.classList.remove("border-zinc-300", "focus:border-zinc-900");
      }
      return false;
    } else {
      errorContainer.classList.add("hidden");
      input.classList.remove("border-rose-500", "focus:border-rose-500", "bg-rose-50/20");
      input.classList.add("border-zinc-300", "focus:border-zinc-900");
      return true;
    }
  }

  let emailDomainCheckTimer = null;
  let lastCheckedEmail = "";
  let isEmailDomainValid = true;

  function verifyEmailDomainAsync(email) {
    if (!email || !email.includes("@")) return;
    const parts = email.split("@");
    if (parts.length !== 2 || !parts[1].includes(".")) return;

    if (email === lastCheckedEmail) return;

    clearTimeout(emailDomainCheckTimer);
    emailDomainCheckTimer = setTimeout(() => {
      fetch('ActionsGV/checkEmailDomain.php?email=' + encodeURIComponent(email))
        .then(res => res.json())
        .then(data => {
          lastCheckedEmail = email;
          if (!data.valid) {
            isEmailDomainValid = false;
            emailErrorText.textContent = data.message;
            emailError.classList.remove("hidden");
            emailInput.classList.add("border-rose-500", "focus:border-rose-500", "bg-rose-50/20");
            emailInput.classList.remove("border-zinc-300", "focus:border-zinc-900");
          } else {
            isEmailDomainValid = true;
            const localRes = validateEmail(email);
            if (localRes.valid) {
              emailError.classList.add("hidden");
              emailInput.classList.remove("border-rose-500", "focus:border-rose-500", "bg-rose-50/20");
              emailInput.classList.add("border-zinc-300", "focus:border-zinc-900");
            }
          }
        })
        .catch(() => {
          isEmailDomainValid = true;
        });
    }, 400);
  }

  function checkEmail(forceShow = false) {
    if (!emailInput) return true;
    const val = emailInput.value.trim();
    const res = validateEmail(val);
    const localOk = applyValidationState(emailInput, emailError, emailErrorText, res, forceShow || emailTouched);
    if (localOk && val) {
      verifyEmailDomainAsync(val);
    }
    return localOk && isEmailDomainValid;
  }

  function checkPhone(forceShow = false) {
    if (!phoneInput) return true;
    const res = validatePhone(phoneInput.value);
    return applyValidationState(phoneInput, phoneError, phoneErrorText, res, forceShow || phoneTouched);
  }

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      if (/\s/.test(emailInput.value)) {
        emailTouched = true;
      }
      if (emailTouched) {
        checkEmail(false);
      }
    });

    emailInput.addEventListener("blur", function () {
      if (emailInput.value.trim() !== "") {
        emailTouched = true;
        checkEmail(true);
      }
    });
  }

  if (phoneInput) {
    phoneInput.addEventListener("input", function () {
      if (/[^0-9+\s\-()]/.test(phoneInput.value)) {
        phoneTouched = true;
        checkPhone(true);
        return;
      }
      if (phoneTouched) {
        checkPhone(false);
      }
    });

    phoneInput.addEventListener("blur", function () {
      if (phoneInput.value.trim() !== "") {
        phoneTouched = true;
        checkPhone(true);
      }
    });
  }

  // Clear red borders on other inputs upon interaction
  ['sender_name', 'inquiry_type', 'preferred_unit_id', 'preferred_move_in_time', 'lease_duration'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', () => {
        el.classList.remove('border-rose-500');
      });
    }
  });

  const msgTextarea = document.querySelector('textarea[name="Message"]');
  if (msgTextarea) {
    msgTextarea.addEventListener('input', () => {
      msgTextarea.classList.remove('border-rose-500');
    });
  }

  // Expose check functions for openInquiryModal
  window.checkEmailField = checkEmail;
  window.checkPhoneField = checkPhone;
});

async function openInquiryModal() {
  const form = document.getElementById("contactForm");
  if (!form) return;

  const emailInput = document.getElementById("sender_email");
  const phoneInput = document.getElementById("sender_contact");
  const nameInput = document.getElementById("sender_name");
  const inquirySelect = document.getElementById("inquiry_type");
  const messageInput = form.querySelector('textarea[name="Message"]');

  const isEmailOk = window.checkEmailField ? window.checkEmailField(true) : true;
  const isPhoneOk = window.checkPhoneField ? window.checkPhoneField(true) : true;

  let firstInvalid = null;

  if (nameInput && !nameInput.value.trim()) {
    firstInvalid = firstInvalid || nameInput;
    nameInput.classList.add("border-rose-500");
  } else if (nameInput) {
    nameInput.classList.remove("border-rose-500");
  }

  if (!isEmailOk) {
    firstInvalid = firstInvalid || emailInput;
  }

  if (!isPhoneOk) {
    firstInvalid = firstInvalid || phoneInput;
  }

  if (inquirySelect && !inquirySelect.value) {
    firstInvalid = firstInvalid || inquirySelect;
    inquirySelect.classList.add("border-rose-500");
  } else if (inquirySelect) {
    inquirySelect.classList.remove("border-rose-500");
  }

  const unitContainer = document.getElementById("unit-preference");
  const unitSelect = document.getElementById("preferred_unit_id");
  if (unitContainer && unitContainer.style.display !== "none" && (!unitSelect || !unitSelect.value)) {
    firstInvalid = firstInvalid || unitSelect;
    if (unitSelect) unitSelect.classList.add("border-rose-500");
  } else if (unitSelect) {
    unitSelect.classList.remove("border-rose-500");
  }

  const moveInContainer = document.getElementById("preferred-move-in-container");
  const moveInSelect = document.getElementById("preferred_move_in_time");
  if (moveInContainer && moveInContainer.style.display !== "none" && (!moveInSelect || !moveInSelect.value)) {
    firstInvalid = firstInvalid || moveInSelect;
    if (moveInSelect) moveInSelect.classList.add("border-rose-500");
  } else if (moveInSelect) {
    moveInSelect.classList.remove("border-rose-500");
  }

  const leaseContainer = document.getElementById("lease-duration-container");
  const leaseSelect = document.getElementById("lease_duration");
  if (leaseContainer && leaseContainer.style.display !== "none" && (!leaseSelect || !leaseSelect.value)) {
    firstInvalid = firstInvalid || leaseSelect;
    if (leaseSelect) leaseSelect.classList.add("border-rose-500");
  } else if (leaseSelect) {
    leaseSelect.classList.remove("border-rose-500");
  }

  if (messageInput && !messageInput.value.trim()) {
    firstInvalid = firstInvalid || messageInput;
    messageInput.classList.add("border-rose-500");
  } else if (messageInput) {
    messageInput.classList.remove("border-rose-500");
  }

  if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
    firstInvalid.focus();
    return;
  }

  // Pre-submit online domain verification
  if (emailInput && emailInput.value.trim()) {
    try {
      const emailVal = emailInput.value.trim();
      const res = await fetch('ActionsGV/checkEmailDomain.php?email=' + encodeURIComponent(emailVal));
      const data = await res.json();
      if (!data.valid) {
        const emailError = document.getElementById("emailError");
        const emailErrorText = document.getElementById("emailErrorText");
        emailErrorText.textContent = data.message;
        emailError.classList.remove("hidden");
        emailInput.classList.add("border-rose-500", "focus:border-rose-500", "bg-rose-50/20");
        emailInput.classList.remove("border-zinc-300", "focus:border-zinc-900");
        emailInput.scrollIntoView({ behavior: "smooth", block: "center" });
        emailInput.focus();
        return;
      }
    } catch (err) {
      console.warn("Domain check skipped:", err);
    }
  }

  document.getElementById("inquiryModal")
    .classList.remove("hidden");

  document.getElementById("inquiryModal")
    .classList.add("flex");
}

function closeInquiryModal(){
    document.getElementById("inquiryModal")
    .classList.add("hidden");

    document.getElementById("inquiryModal")
    .classList.remove("flex");
}

function submitInquiry() {
  document.getElementById("contactForm").submit();
}
</script>

</body>
</html>