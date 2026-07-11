<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zeppelin Suites — Contact</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .footer-bg { background-color: #c4c4c4; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
    /* Input focus — matching login.html */
    .zep-input:focus { outline: none; border-color: #18181b; }
    .zep-select:focus { outline: none; border-color: #18181b; }
    /* Success message */
    .form-success { display: none; }
    .form-success.show { display: flex; }
  </style>
</head>
<body class="bg-white text-zinc-900">

<!-- ── NAV ──────────────────────────────────────────────── -->
<nav class="sticky top-0 w-full bg-white/80 backdrop-blur-md px-6 md:px-16 lg:px-24 xl:px-32 py-4 flex items-center justify-between z-50 border-b border-zinc-200/50">
  <a href="../generalViewPages/index.html">
    <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" style="height:60px;" onerror="this.outerHTML='<span class=\'font-bold text-xl tracking-tight text-zinc-900\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest\'>SUITES</span></span>'">
  </a>
  <div class="hidden min-[851px]:flex items-center gap-8">
    <a href="../generalViewPages/index.html" class="text-sm text-zinc-500 hover:text-zinc-800 transition-colors">Home</a>
    <a href="../generalViewPages/tour.html" class="text-sm text-zinc-500 hover:text-zinc-500 transition-colors">Take a Tour</a>
    <div class="relative group">
      <button class="flex items-center gap-1.5 text-sm text-zinc-500 cursor-pointer bg-transparent border-0 py-2 hover:text-zinc-800">Browse Units <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      <div class="absolute top-full left-0 mt-1 w-44 bg-white border border-zinc-200 rounded-xl shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
        <a href="../generalViewPages/studioTypeA.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Studio Type A</a>
        <a href="../generalViewPages/studioTypeB.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Studio Type B</a>
        <a href="../generalViewPages/oneBedroom.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">One Bedroom</a>
        <a href="../generalViewPages/twoBedroom.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Two Bedroom</a>
      </div>
    </div>
    <a href="../generalViewPages/faq.html" class="text-sm text-zinc-500 hover:text-zinc-800 transition-colors">FAQ</a>
    <a href="../generalViewPages/aboutUs.html" class="text-sm text-zinc-500 hover:text-zinc-800 transition-colors">About Us</a>
    <a href="../generalViewPages/contact.php" class="text-sm text-zinc-800 font-medium hover:text-zinc-500 transition-colors">Contact</a>
    <a href="../generalViewPages/login.php" class="text-sm text-zinc-500 hover:text-zinc-800 transition-colors">Portal</a>
  </div>
  <button onclick="toggleMenu()" class="min-[851px]:hidden flex flex-col gap-1.5 cursor-pointer bg-transparent border-0 p-1">
    <span id="bar1" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
    <span id="bar2" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
    <span id="bar3" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
  </button>
  <div id="mobileMenu" class="absolute top-full left-0 w-full bg-white border-t border-zinc-200 flex-col p-5 gap-1 min-[851px]:hidden z-50 hidden">
    <a href="../generalViewPages/index.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Home</a>
    <a href="../generalViewPages/tour.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">Take a Tour</a>
    <button onclick="toggleDropdown('mobileDropdown','mobileChevron')" class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50 bg-transparent border-0 cursor-pointer">Browse Units <svg id="mobileChevron" class="transition-transform" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    <div id="mobileDropdown" class="hidden flex-col pl-4">
      <a href="../generalViewPages/studioTypeA.html" class="px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Studio Type A</a>
      <a href="../generalViewPages/studioTypeB.html" class="px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Studio Type B</a>
      <a href="../generalViewPages/oneBedroom.html" class="px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">One Bedroom</a>
      <a href="../generalViewPages/twoBedroom.html" class="px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Two Bedroom</a>
    </div>
    <a href="../generalViewPages/faq.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">FAQ</a>
    <a href="../generalViewPages/aboutUs.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">About Us</a>
    <a href="../generalViewPages/contact.php" class="px-4 py-2.5 rounded-lg text-sm text-zinc-900 font-medium hover:bg-zinc-50">Contact</a>
    <a href="../generalViewPages/login.php" class="px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Portal</a>
  </div>
</nav>

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
      <!-- Success message -->
      <div id="formSuccess" class="form-success items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4 mb-6 hidden">
          <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm font-semibold text-emerald-700">Your inquiry has been submitted! We'll be in touch soon.</p>
      </div>


       <form id="contactForm" action="ActionsGV/inquiryInput.php" method="POST" class="space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-semibold text-zinc-800 mb-2">Name:</label>
          <input type="text" name="sender_name" placeholder="Your full name" required
            class="zep-input w-full border-b border-zinc-300 bg-transparent py-2.5 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
        </div>

        <!-- Email + Phone -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-semibold text-zinc-800 mb-2">Email:</label>
            <input type="email" name="sender_email" placeholder="your@email.com" required
              class="zep-input w-full border-b border-zinc-300 bg-transparent py-2.5 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-zinc-800 mb-2">Phone:</label>
            <input type="tel" name="sender_contact" placeholder="09XX-XXX-XXXX"
              class="zep-input w-full border-b border-zinc-300 bg-transparent py-2.5 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none">
          </div>
        </div>

<!-- Inquiry Type -->
  <div>
    <label class="block text-sm font-semibold text-zinc-800 mb-2">Inquiry Type:</label>
    <div class="relative">
      <select name="inquiry_type" id="inquiry_type" class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors" required>
        <option value="" disabled selected>Choose option</option>
        <option value="Unit Reservation">Unit Reservation</option>
        <option value="Resale Inquiry">Resale Inquiry</option>
        <option value="Lease Inquiry">Lease Inquiry</option>
        <option value="General Inquiry">General Inquiry</option>
        <option value="Others">Others</option>
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
      <select name="Preferred_unit_id" class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600 appearance-none cursor-pointer focus:border-zinc-900 outline-none transition-colors">
        <option value="" disabled selected>Choose option</option>
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
  <div id="lease-duration-container" style="display: none;">
    <label for="lease_duration" class="block text-sm font-semibold text-zinc-800 mb-2">Preferred Lease Duration:</label>
    <select name="lease_duration" id="lease_duration" class="zep-select w-full border border-zinc-300 rounded-xl bg-white px-4 py-3 text-sm text-zinc-600">
      <option value="" disabled selected>Choose option</option>
      <option value="lease immediately">Lease Immediately</option>
      <option value="for the next 3 months">For the next 3 months</option>
      <option value="for the next 6 months">For the next 6 months</option>
      <option value="1 year">1 year</option>
      <option value="2 years">2 years</option>
      <option value="longer contract">Longer contract</option>
      <option value="still deciding">Still deciding</option>
    </select>
  </div>


        <!-- Message -->
        <div>
          <label class="block text-sm font-semibold text-zinc-800 mb-2">Message:</label>
          <textarea name="Message" rows="5" placeholder="Write your message here..." required
            class="zep-input w-full border-b border-zinc-300 bg-transparent pt-2 pb-3 text-sm text-zinc-800 placeholder-zinc-400 focus:border-zinc-900 transition-colors outline-none resize-none"></textarea>
        </div>

        <!-- Submit -->
        <div class="flex justify-end pt-2">
          <button type="submit" class="btn-primary bg-zinc-900 text-white px-10 py-3.5 font-bold text-sm tracking-widest uppercase hover:bg-zinc-700 active:scale-95 transition-all rounded-full">
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

      <!-- Map placeholder -->
      <div class="bg-white/10 rounded-xl h-44 flex items-center justify-center">
        <div class="text-center">
          <svg class="w-8 h-8 text-white/40 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
          <p class="text-white/40 text-xs">Map coming soon</p>
        </div>
      </div>

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
<!-- PREMIUM FOOTER -->
<footer class="bg-zinc-950 text-zinc-300 py-16 md:py-20 px-6 md:px-8 lg:px-16 xl:px-24">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-x-12 gap-y-14">

      <!-- Brand -->
      <div class="sm:col-span-5 flex flex-col items-center lg:items-start">
        <a href="../generalViewPages/index.html" class="inline-block mb-8">
          <div class="font-bold tracking-tighter text-white leading-none text-center lg:text-left">
            <span class="text-5xl md:text-6xl">ZEPPELIN</span><br>
            <span class="text-xl md:text-2xl tracking-[0.25em] font-light text-zinc-400">SUITES</span>
          </div>
        </a>
        <p class="text-zinc-400 text-center lg:text-left max-w-sm leading-relaxed text-sm md:text-base px-4 md:px-0">
          Experience unparalleled luxury in the heart of Angeles City.
          Zeppelin Suites redefines modern living with sophisticated design and exceptional amenities.
        </p>
      </div>

      <!-- Explore -->
      <div class="lg:col-span-2">
        <h3 class="font-semibold text-white mb-5 tracking-wider text-sm text-center sm:text-left">EXPLORE</h3>
        <div class="flex flex-col gap-3 text-sm text-center sm:text-left">
          <a href="../generalViewPages/index.html" class="hover:text-white transition-colors">Home</a>
          <a href="../generalViewPages/tour.html" class="hover:text-white transition-colors">Virtual Tour</a>
          <a href="../generalViewPages/aboutUs.html" class="hover:text-white transition-colors">About Us</a>
          <a href="../generalViewPages/faq.html" class="hover:text-white transition-colors">FAQ</a>
          <a href="../generalViewPages/contact.php" class="hover:text-white transition-colors">Contact</a>
        </div>
      </div>

      <!-- Units -->
      <div class="lg:col-span-2">
        <h3 class="font-semibold text-white mb-5 tracking-wider text-sm text-center sm:text-left">UNITS</h3>
        <div class="flex flex-col gap-3 text-sm text-center sm:text-left">
          <a href="../generalViewPages/studioTypeA.html" class="hover:text-white transition-colors">Studio Type A</a>
          <a href="../generalViewPages/studioTypeB.html" class="hover:text-white transition-colors">Studio Type B</a>
          <a href="../generalViewPages/oneBedroom.html" class="hover:text-white transition-colors">One Bedroom</a>
          <a href="../generalViewPages/twoBedroom.html" class="hover:text-white transition-colors">Two Bedroom</a>
        </div>
      </div>

      <!-- Connect -->
      <div class="sm:col-span-2">
        <h3 class="font-semibold text-white mb-5 tracking-wider text-sm text-center sm:text-left">CONNECT WITH US</h3>

        <div class="flex gap-6 mb-8 justify-center sm:justify-start">
          <a href="https://www.facebook.com/zeppilinsuites2015" target="_blank" class="text-zinc-400 hover:text-white transition-all hover:scale-110">
            Facebook
          </a>
          <a href="https://www.instagram.com/zeppelinsuites" target="_blank" class="text-zinc-400 hover:text-white transition-all hover:scale-110">
            Instagram
          </a>
        </div>

        <div class="space-y-5 text-sm text-center sm:text-left">
          <div>
            <p class="text-xs uppercase tracking-widest text-zinc-500 mb-1">Address</p>
            <p class="text-zinc-300">Fields Avenue, Angeles City, Pampanga</p>
          </div>

          <div>
            <p class="text-xs uppercase tracking-widest text-zinc-500 mb-1">Phone</p>
            <a href="tel:+6453043016" class="hover:text-white block">+645 304 3016</a>
            <a href="tel:+639982243692" class="hover:text-white block">+63 998 224 3692</a>
            <a href="tel:+639164491253" class="hover:text-white block">+63 916 449 1253</a>
          </div>

          <div>
            <p class="text-xs uppercase tracking-widest text-zinc-500 mb-1">Email</p>
            <a href="mailto:info@zeppelinsuites.com" class="text-amber-600 hover:text-amber-500">
              info@zeppelinsuites.com
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-zinc-800 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-zinc-500">
      <p class="text-center md:text-left">© 2026 Zeppelin Suites. All rights reserved.</p>

      <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center md:justify-start">
        <a href="../generalViewPages/privacy-policy.htm" class="hover:text-zinc-300 transition-colors">Privacy Policy</a>
        <a href="../generalViewPages/terms-of-service.htm" class="hover:text-zinc-300 transition-colors">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<script>
  let menuOpen = false;

  function toggleMenu() {
    menuOpen = !menuOpen;
    const menu = document.getElementById('mobileMenu');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');

    menu.classList.toggle('hidden', !menuOpen);
    menu.classList.toggle('flex', menuOpen);
    bar1.style.transform = menuOpen ? 'translateY(8px) rotate(45deg)' : '';
    bar2.style.opacity = menuOpen ? '0' : '1';
    bar3.style.transform = menuOpen ? 'translateY(-8px) rotate(-45deg)' : '';
  }

  function toggleDropdown(id, chevronId) {
    const el = document.getElementById(id);
    const ch = document.getElementById(chevronId);
    const hidden = el.classList.contains('hidden');
    el.classList.toggle('hidden', !hidden);
    el.classList.toggle('flex', hidden);
    ch.style.transform = hidden ? 'rotate(180deg)' : '';
  }

  function handleSubmit(e) {
    e.preventDefault();
    const success = document.getElementById('formSuccess');
    const form = document.getElementById('contactForm');
    form.reset();
    success.classList.add('show');
    success.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    setTimeout(() => success.classList.remove('show'), 6000);
  }

  document.addEventListener("DOMContentLoaded", function () {
    const inquiryTypeSelect = document.getElementById('inquiry_type');
    const unitPreferenceDiv = document.getElementById('unit-preference');
    // FIX: Target the new container ID instead of the parent of the select
    const leaseDurationContainer = document.getElementById('lease-duration-container');

    function toggleFields() {
      const value = inquiryTypeSelect.value;

      // Default: hide both
      unitPreferenceDiv.style.display = 'none';
      leaseDurationContainer.style.display = 'none';

      if (value === 'Resale Inquiry') {
        // Resale: Show Unit Preference only
        unitPreferenceDiv.style.display = 'block';
      } 
      else if (value === 'Unit Reservation' || value === 'Lease Inquiry') {
        // Reservation or Lease: Show BOTH
        unitPreferenceDiv.style.display = 'block';
        leaseDurationContainer.style.display = 'block';
      }
      // General Inquiry or Others: Leave both hidden (default)
    }

    // Run once on load to set initial state
    toggleFields();

    // Listen for changes
    inquiryTypeSelect.addEventListener('change', toggleFields);
  });
</script>

</body>
</html>