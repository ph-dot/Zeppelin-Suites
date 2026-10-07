// Dynamic Footer Component
(function() {
  function renderFooter() {
    const target = document.getElementById('footer');
    if (!target) return;

    const footerHtml = `
  <footer class="mt-16 md:mt-24 bg-zinc-950 text-zinc-300 py-16 md:py-20 px-6 md:px-8 lg:px-12 xl:px-20 border-t border-zinc-900">
    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-10 md:gap-8 lg:gap-10 xl:gap-12">

        <!-- Brand -->
        <div class="col-span-1 sm:col-span-2 md:col-span-4 flex flex-col items-center sm:items-start text-center sm:text-left">
          <a href="../" class="inline-block mb-6 group">
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
            <a href="../" class="hover:text-amber-400 transition-colors">Home</a>
            <a href="../tour" class="hover:text-amber-400 transition-colors">Virtual Tour</a>
            <a href="../about" class="hover:text-amber-400 transition-colors">About Us</a>
            <a href="../faq" class="hover:text-amber-400 transition-colors">FAQ</a>
            <a href="../contact" class="hover:text-amber-400 transition-colors">Contact</a>
            <div class="pt-3">
              <a href="../login"
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
            <a href="../units/studio-type-a" class="hover:text-amber-400 transition-colors">Studio Type A</a>
            <a href="../units/studio-type-b" class="hover:text-amber-400 transition-colors">Studio Type B</a>
            <a href="../units/one-bedroom" class="hover:text-amber-400 transition-colors">One Bedroom</a>
            <a href="../units/two-bedroom" class="hover:text-amber-400 transition-colors">Two Bedroom</a>
          </div>
        </div>

        <!-- Connect -->
        <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4 flex flex-col items-center sm:items-start text-center sm:text-left">
          <h3 class="font-semibold text-white mb-5 tracking-wider text-xs uppercase text-zinc-200">CONNECT WITH US</h3>

          <!-- Social Media SVG Linkers -->
          <div class="flex items-center gap-3 mb-6 justify-center sm:justify-start">
            <a href="https://www.facebook.com/zeppilinsuites2015" target="_blank" rel="noopener noreferrer"
              class="w-9 h-9 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 hover:border-zinc-700 transition-all"
              aria-label="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
              </svg>
            </a>
            <a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer"
              class="w-9 h-9 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 hover:border-zinc-700 transition-all"
              aria-label="Instagram">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>
          </div>

          <!-- Contact Details -->
          <div class="space-y-3 text-sm text-zinc-400">
            <div class="flex items-start gap-3 justify-center sm:justify-start">
              <svg class="w-4 h-4 text-amber-500/80 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span>18-19 Zeppelin St, Hensonville, Malabanias, Angeles City, Pampanga</span>
            </div>
            <div class="flex items-center gap-3 justify-center sm:justify-start">
              <svg class="w-4 h-4 text-amber-500/80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              <span>+63 917 123 4567</span>
            </div>
            <div class="flex items-center gap-3 justify-center sm:justify-start">
              <svg class="w-4 h-4 text-amber-500/80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <span>contact@zeppelinsuites.com</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="mt-12 md:mt-16 pt-8 border-t border-zinc-900/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-500 text-center sm:text-left">
        <p>&copy; ${new Date().getFullYear()} Zeppelin Suites. All rights reserved.</p>
        <div class="flex items-center gap-6">
          <a href="../privacy-policy" class="hover:text-zinc-300 transition-colors">Privacy Policy</a>
          <a href="../terms-of-service" class="hover:text-zinc-300 transition-colors">Terms of Service</a>
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
