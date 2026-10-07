// Dynamic Navbar Component
(function() {
  function renderNavbar() {
    const target = document.getElementById('navbar');
    if (!target) return;

    // Detect active page from clean path or current script
    const path = window.location.pathname.toLowerCase();

    const isHome = path.endsWith('/public/') || path.endsWith('/public') || path.endsWith('/index.html') || path.endsWith('/home') || path === '/';
    const isTour = path.includes('tour');
    const isStudioA = path.includes('studio-type-a') || path.includes('studiotypea');
    const isStudioB = path.includes('studio-type-b') || path.includes('studiotypeb');
    const isOneBed = path.includes('one-bedroom') || path.includes('onebedroom');
    const isTwoBed = path.includes('two-bedroom') || path.includes('twobedroom');
    const isUnit = isStudioA || isStudioB || isOneBed || isTwoBed;
    const isFaq = path.includes('faq');
    const isAbout = path.includes('about');
    const isContact = path.includes('contact');

    function getLinkClass(active) {
      return active
        ? 'text-sm font-medium text-zinc-900 border-b-2 border-zinc-900 pb-1'
        : 'text-sm text-zinc-500 hover:text-zinc-800 transition-colors';
    }

    function getDropdownItemClass(active) {
      return active
        ? 'block px-4 py-2 text-sm text-zinc-900 bg-zinc-50 font-medium'
        : 'block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50 transition-colors';
    }

    function getMobileLinkClass(active) {
      return active
        ? 'px-4 py-2.5 rounded-lg text-sm font-semibold text-zinc-900 bg-zinc-100'
        : 'px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50';
    }

    function getMobileSubItemClass(active) {
      return active
        ? 'px-4 py-2 rounded-lg text-sm font-semibold text-zinc-900 bg-zinc-100'
        : 'px-4 py-2 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50';
    }

    const navHtml = `
  <nav class="sticky top-0 w-full bg-white/80 backdrop-blur-md px-6 md:px-16 lg:px-24 xl:px-32 py-4 flex items-center justify-between z-50 border-b border-zinc-200/50">
    <a href="../">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" style="height:60px;"
        onerror="this.outerHTML='<span class=\\'font-bold text-xl tracking-tight text-zinc-900\\'>ZEPPELIN<br><span class=\\'text-xs font-normal tracking-widest\\'>SUITES</span></span>'">
    </a>
    <div class="hidden min-[851px]:flex items-center gap-8">
      <a href="../" class="${getLinkClass(isHome)}">Home</a>
      <a href="../tour" class="${getLinkClass(isTour)}">Take a Tour</a>
      <div class="relative group">
        <button
          class="flex items-center gap-1.5 text-sm cursor-pointer bg-transparent border-0 py-2 transition-colors ${
            isUnit ? 'text-zinc-900 font-medium' : 'text-zinc-500 hover:text-zinc-800'
          }">
          Browse Units
          <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
            <path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <div
          class="absolute top-full left-0 mt-1 w-44 bg-white border border-zinc-200 rounded-xl shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
          <a href="../units/studio-type-a" class="${getDropdownItemClass(isStudioA)}">Studio Type A</a>
          <a href="../units/studio-type-b" class="${getDropdownItemClass(isStudioB)}">Studio Type B</a>
          <a href="../units/one-bedroom" class="${getDropdownItemClass(isOneBed)}">One Bedroom</a>
          <a href="../units/two-bedroom" class="${getDropdownItemClass(isTwoBed)}">Two Bedroom</a>
        </div>
      </div>
      <a href="../faq" class="${getLinkClass(isFaq)}">FAQ</a>
      <a href="../about" class="${getLinkClass(isAbout)}">About Us</a>
      <a href="../contact" class="${getLinkClass(isContact)}">Contact</a>
    </div>
    <button onclick="toggleMenu()"
      class="min-[851px]:hidden flex flex-col gap-1.5 cursor-pointer bg-transparent border-0 p-1"
      aria-label="Toggle menu">
      <span id="bar1" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
      <span id="bar2" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
      <span id="bar3" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
    </button>
    <div id="mobileMenu"
      class="absolute top-full left-0 w-full bg-white border-t border-zinc-200 flex-col p-5 gap-1 min-[851px]:hidden z-50 hidden">
      <a href="../" class="${getMobileLinkClass(isHome)}">Home</a>
      <a href="../tour" class="${getMobileLinkClass(isTour)}">Take a Tour</a>
      <button onclick="toggleDropdown('mobileDropdown','mobileChevron')"
        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-zinc-50 bg-transparent border-0 cursor-pointer ${
          isUnit ? 'text-zinc-900 font-semibold' : 'text-zinc-500'
        }">
        Browse Units <svg id="mobileChevron" class="transition-transform ${isUnit ? 'rotate-180' : ''}" width="10" height="6" viewBox="0 0 10 6" fill="none">
          <path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>
      <div id="mobileDropdown" class="${isUnit ? 'flex' : 'hidden'} flex-col pl-4">
        <a href="../units/studio-type-a" class="${getMobileSubItemClass(isStudioA)}">Studio Type A</a>
        <a href="../units/studio-type-b" class="${getMobileSubItemClass(isStudioB)}">Studio Type B</a>
        <a href="../units/one-bedroom" class="${getMobileSubItemClass(isOneBed)}">One Bedroom</a>
        <a href="../units/two-bedroom" class="${getMobileSubItemClass(isTwoBed)}">Two Bedroom</a>
      </div>
      <a href="../faq" class="${getMobileLinkClass(isFaq)}">FAQ</a>
      <a href="../about" class="${getMobileLinkClass(isAbout)}">About Us</a>
      <a href="../contact" class="${getMobileLinkClass(isContact)}">Contact</a>
    </div>
  </nav>`;

    target.outerHTML = navHtml;
  }

  window.toggleMenu = function() {
    const menu = document.getElementById('mobileMenu');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');
    if (!menu) return;
    const isHidden = menu.classList.contains('hidden');
    if (isHidden) {
      menu.classList.remove('hidden');
      menu.classList.add('flex');
      if (bar1) bar1.style.transform = 'translateY(8px) rotate(45deg)';
      if (bar2) bar2.style.opacity = '0';
      if (bar3) bar3.style.transform = 'translateY(-8px) rotate(-45deg)';
    } else {
      menu.classList.add('hidden');
      menu.classList.remove('flex');
      if (bar1) bar1.style.transform = 'none';
      if (bar2) bar2.style.opacity = '1';
      if (bar3) bar3.style.transform = 'none';
    }
  };

  window.toggleDropdown = function(dropdownId, chevronId) {
    const dropdown = document.getElementById(dropdownId);
    const chevron = document.getElementById(chevronId);
    if (!dropdown) return;
    const isHidden = dropdown.classList.contains('hidden');
    if (isHidden) {
      dropdown.classList.remove('hidden');
      dropdown.classList.add('flex');
      if (chevron) chevron.classList.add('rotate-180');
    } else {
      dropdown.classList.add('hidden');
      dropdown.classList.remove('flex');
      if (chevron) chevron.classList.remove('rotate-180');
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderNavbar);
  } else {
    renderNavbar();
  }
})();
