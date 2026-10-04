/**
 * Zeppelin Suites - Reusable Premium Footer Component
 * Dynamically renders the standardized premium footer across generalViewPages.
 */
(function () {
  function renderFooter() {
    const target = document.getElementById('footer');
    if (!target) return;

    const footerHtml = `
  <footer class="mt-16 md:mt-24 bg-zinc-950 text-zinc-300 py-16 md:py-20 px-6 md:px-8 lg:px-12 xl:px-20 border-t border-zinc-900">
    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-10 md:gap-8 lg:gap-10 xl:gap-12">

        <!-- Brand -->
        <div class="col-span-1 sm:col-span-2 md:col-span-4 flex flex-col items-center sm:items-start text-center sm:text-left">
          <a href="../generalViewPages/index.html" class="inline-block mb-6 group">
            <div class="font-bold tracking-tighter text-white leading-none">
              <span class="text-4xl md:text-5xl font-black tracking-tight">ZEPPELIN</span><br>
              <span class="text-lg md:text-xl tracking-[0.28em] font-light text-amber-500/90 group-hover:text-amber-400 transition-colors">SUITES</span>
            </div>
          </a>
          <p class="text-zinc-400 max-w-sm text-sm leading-relaxed mb-6">
            Experience unparalleled luxury in the heart of Angeles City. Zeppelin Suites redefines modern living with sophisticated design, executive comfort, and exceptional amenities.
          </p>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-900 border border-zinc-800 text-[11px] font-medium text-zinc-400">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            Premier Residences · Angeles City
          </div>
        </div>

        <!-- Explore -->
        <div class="col-span-1 md:col-span-3 lg:col-span-2 flex flex-col items-center sm:items-start text-center sm:text-left">
          <h3 class="font-semibold text-white mb-5 tracking-wider text-xs uppercase text-zinc-200">EXPLORE</h3>
          <div class="flex flex-col gap-3 text-sm">
            <a href="../generalViewPages/index.html" class="hover:text-amber-400 transition-colors">Home</a>
            <a href="../generalViewPages/tour.html" class="hover:text-amber-400 transition-colors">Virtual Tour</a>
            <a href="../generalViewPages/aboutUs.html" class="hover:text-amber-400 transition-colors">About Us</a>
            <a href="../generalViewPages/faq.html" class="hover:text-amber-400 transition-colors">FAQ</a>
            <a href="../generalViewPages/contact.php" class="hover:text-amber-400 transition-colors">Contact</a>
            <div class="pt-3">
              <a href="../generalViewPages/login.php"
                class="group inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-gradient-to-r from-amber-500 via-yellow-300 to-amber-500 hover:from-amber-400 hover:via-yellow-200 hover:to-amber-400 text-zinc-950 font-bold text-xs tracking-wider uppercase whitespace-nowrap shadow-lg shadow-amber-500/20 hover:shadow-amber-500/35 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-200">
                <svg class="w-3.5 h-3.5 text-zinc-950 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <span>Portal Login</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Units -->
        <div class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col items-center sm:items-start text-center sm:text-left">
          <h3 class="font-semibold text-white mb-5 tracking-wider text-xs uppercase text-zinc-200">UNITS</h3>
          <div class="flex flex-col gap-3 text-sm">
            <a href="../generalViewPages/studioTypeA.html" class="hover:text-amber-400 transition-colors">Studio Type A</a>
            <a href="../generalViewPages/studioTypeB.html" class="hover:text-amber-400 transition-colors">Studio Type B</a>
            <a href="../generalViewPages/oneBedroom.html" class="hover:text-amber-400 transition-colors">One Bedroom</a>
            <a href="../generalViewPages/twoBedroom.html" class="hover:text-amber-400 transition-colors">Two Bedroom</a>
          </div>
        </div>

        <!-- Connect -->
        <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4 flex flex-col items-center sm:items-start text-center sm:text-left">
          <h3 class="font-semibold text-white mb-5 tracking-wider text-xs uppercase text-zinc-200">CONNECT WITH US</h3>

          <!-- Social Media SVG Linkers -->
          <div class="flex items-center gap-3 mb-6 justify-center sm:justify-start">
            <a href="https://www.facebook.com/zeppilinsuites2015" target="_blank" rel="noopener noreferrer"
              aria-label="Zeppelin Suites on Facebook"
              class="w-9 h-9 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-amber-500/50 hover:bg-zinc-800 transition-all duration-200 hover:-translate-y-0.5 shadow-sm group">
              <svg class="w-4 h-4 fill-current group-hover:text-amber-400 transition-colors" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
              </svg>
            </a>
            <a href="https://www.instagram.com/zeppelinsuites" target="_blank" rel="noopener noreferrer"
              aria-label="Zeppelin Suites on Instagram"
              class="w-9 h-9 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-amber-500/50 hover:bg-zinc-800 transition-all duration-200 hover:-translate-y-0.5 shadow-sm group">
              <svg class="w-4 h-4 fill-current group-hover:text-amber-400 transition-colors" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/>
              </svg>
            </a>
          </div>

          <!-- Contact Info -->
          <div class="space-y-4 text-sm w-full">
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500 mb-1">Address</p>
              <p class="text-zinc-300 text-xs sm:text-sm">Fields Avenue, Angeles City, Pampanga, Philippines</p>
            </div>

            <div>
              <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500 mb-1">Phone</p>
              <div class="space-y-0.5">
                <a href="tel:+6453043016" class="hover:text-amber-400 transition-colors block text-xs sm:text-sm font-mono text-zinc-300">+645 304 3016</a>
                <a href="tel:+639982243692" class="hover:text-amber-400 transition-colors block text-xs sm:text-sm font-mono text-zinc-300">+63 998 224 3692</a>
                <a href="tel:+639164491253" class="hover:text-amber-400 transition-colors block text-xs sm:text-sm font-mono text-zinc-300">+63 916 449 1253</a>
              </div>
            </div>

            <div>
              <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500 mb-1">Email</p>
              <a href="mailto:info@zeppelinsuites.com" class="text-amber-500 hover:text-amber-400 transition-colors text-xs sm:text-sm font-medium">
                info@zeppelinsuites.com
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div
        class="border-t border-zinc-900 mt-16 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-zinc-500">
        <p class="text-center sm:text-left">© 2026 Zeppelin Suites. All rights reserved.</p>

        <div class="flex gap-6 justify-center sm:justify-start">
          <a href="../generalViewPages/privacy-policy.html" class="hover:text-zinc-300 transition-colors">Privacy Policy</a>
          <a href="../generalViewPages/terms-of-service.htm" class="hover:text-zinc-300 transition-colors">Terms of Service</a>
        </div>
      </div>
    </div>
  </footer>`;

    target.outerHTML = footerHtml;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderFooter);
  } else {
    renderFooter();
  }
})();
