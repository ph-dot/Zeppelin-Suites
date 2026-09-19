/**
 * Zeppelin Suites - Reusable Premium Footer Component
 * Dynamically renders the standardized premium footer across generalViewPages.
 */
(function () {
  function renderFooter() {
    const target = document.getElementById('footer');
    if (!target) return;

    const footerHtml = `
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
            <a href="../generalViewPages/login.php" class="hover:text-white transition-colors">Login</a>
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
            <a href="https://www.facebook.com/zeppilinsuites2015" target="_blank"
              class="text-zinc-400 hover:text-white transition-all hover:scale-110">
              Facebook
            </a>
            <a href="https://www.instagram.com/zeppelinsuites" target="_blank"
              class="text-zinc-400 hover:text-white transition-all hover:scale-110">
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
      <div
        class="border-t border-zinc-800 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-zinc-500">
        <p class="text-center md:text-left">© 2026 Zeppelin Suites. All rights reserved.</p>

        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center md:justify-start">
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
