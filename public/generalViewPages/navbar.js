/**
 * Zeppelin Suites - Reusable Navigation Bar Component
 * Dynamically renders the navigation bar with active link highlighting and mobile menu toggles.
 */
(function () {
  function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.split('/').pop().toLowerCase();
    if (!page || page === '' || page === 'index.html' || page === 'index.php') {
      return 'index.html';
    }
    return page;
  }

  function renderNavbar() {
    const target = document.getElementById('navbar');
    if (!target) return;

    const page = getCurrentPage();

    const isHome = page === 'index.html';
    const isTour = page === 'tour.html';
    const isStudioA = page === 'studiotypea.html';
    const isStudioB = page === 'studiotypeb.html';
    const isOneBed = page === 'onebedroom.html';
    const isTwoBed = page === 'twobedroom.html';
    const isUnit = isStudioA || isStudioB || isOneBed || isTwoBed;
    const isFaq = page === 'faq.html';
    const isAbout = page === 'aboutus.html';
    const isContact = page === 'contact.php';
    const isLogin = page === 'login.php';

    const getLinkClass = (active) =>
      active
        ? 'text-sm text-zinc-900 font-medium hover:text-zinc-600 transition-colors'
        : 'text-sm text-zinc-500 hover:text-zinc-800 transition-colors';

    const getDropdownItemClass = (active) =>
      active
        ? 'block px-4 py-2 text-sm text-zinc-900 font-medium bg-zinc-50'
        : 'block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50';

    const getMobileLinkClass = (active) =>
      active
        ? 'px-4 py-2.5 rounded-lg text-sm text-zinc-900 font-semibold bg-zinc-100/70'
        : 'px-4 py-2.5 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50';

    const getMobileSubItemClass = (active) =>
      active
        ? 'px-4 py-2 rounded-lg text-sm text-zinc-900 font-semibold bg-zinc-100/50'
        : 'px-4 py-2 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50';

    const navHtml = `
  <nav class="sticky top-0 w-full bg-white/80 backdrop-blur-md px-6 md:px-16 lg:px-24 xl:px-32 py-4 flex items-center justify-between z-50 border-b border-zinc-200/50">
    <a href="../generalViewPages/index.html">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" style="height:60px;"
        onerror="this.outerHTML='<span class=\\'font-bold text-xl tracking-tight text-zinc-900\\'>ZEPPELIN<br><span class=\\'text-xs font-normal tracking-widest\\'>SUITES</span></span>'">
    </a>
    <div class="hidden min-[851px]:flex items-center gap-8">
      <a href="../generalViewPages/index.html" class="${getLinkClass(isHome)}">Home</a>
      <a href="../generalViewPages/tour.html" class="${getLinkClass(isTour)}">Take a Tour</a>
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
          <a href="../generalViewPages/studioTypeA.html" class="${getDropdownItemClass(isStudioA)}">Studio Type A</a>
          <a href="../generalViewPages/studioTypeB.html" class="${getDropdownItemClass(isStudioB)}">Studio Type B</a>
          <a href="../generalViewPages/oneBedroom.html" class="${getDropdownItemClass(isOneBed)}">One Bedroom</a>
          <a href="../generalViewPages/twoBedroom.html" class="${getDropdownItemClass(isTwoBed)}">Two Bedroom</a>
        </div>
      </div>
      <a href="../generalViewPages/faq.html" class="${getLinkClass(isFaq)}">FAQ</a>
      <a href="../generalViewPages/aboutUs.html" class="${getLinkClass(isAbout)}">About Us</a>
      <a href="../generalViewPages/contact.php" class="${getLinkClass(isContact)}">Contact</a>
      <a href="../generalViewPages/login.php" class="${getLinkClass(isLogin)}">Portal</a>
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
      <a href="../generalViewPages/index.html" class="${getMobileLinkClass(isHome)}">Home</a>
      <a href="../generalViewPages/tour.html" class="${getMobileLinkClass(isTour)}">Take a Tour</a>
      <button onclick="toggleDropdown('mobileDropdown','mobileChevron')"
        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-zinc-50 bg-transparent border-0 cursor-pointer ${
          isUnit ? 'text-zinc-900 font-semibold' : 'text-zinc-500'
        }">
        Browse Units <svg id="mobileChevron" class="transition-transform ${isUnit ? 'rotate-180' : ''}" width="10" height="6" viewBox="0 0 10 6" fill="none">
          <path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>
      <div id="mobileDropdown" class="${isUnit ? 'flex' : 'hidden'} flex-col pl-4">
        <a href="../generalViewPages/studioTypeA.html" class="${getMobileSubItemClass(isStudioA)}">Studio Type A</a>
        <a href="../generalViewPages/studioTypeB.html" class="${getMobileSubItemClass(isStudioB)}">Studio Type B</a>
        <a href="../generalViewPages/oneBedroom.html" class="${getMobileSubItemClass(isOneBed)}">One Bedroom</a>
        <a href="../generalViewPages/twoBedroom.html" class="${getMobileSubItemClass(isTwoBed)}">Two Bedroom</a>
      </div>
      <a href="../generalViewPages/faq.html" class="${getMobileLinkClass(isFaq)}">FAQ</a>
      <a href="../generalViewPages/aboutUs.html" class="${getMobileLinkClass(isAbout)}">About Us</a>
      <a href="../generalViewPages/contact.php" class="${getMobileLinkClass(isContact)}">Contact</a>
      <a href="../generalViewPages/login.php" class="${getMobileLinkClass(isLogin)}">Portal</a>
    </div>
  </nav>`;

    target.outerHTML = navHtml;
  }

  // Global mobile navigation toggle handlers
  window.toggleMenu = function () {
    const menu = document.getElementById('mobileMenu');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');
    if (!menu) return;

    const isClosed = menu.classList.contains('hidden');
    menu.classList.toggle('hidden', !isClosed);
    menu.classList.toggle('flex', isClosed);

    if (bar1 && bar2 && bar3) {
      bar1.style.transform = isClosed ? 'translateY(8px) rotate(45deg)' : '';
      bar2.style.opacity = isClosed ? '0' : '1';
      bar3.style.transform = isClosed ? 'translateY(-8px) rotate(-45deg)' : '';
    }
  };

  window.toggleDropdown = function (id, chevronId) {
    const el = document.getElementById(id);
    const ch = document.getElementById(chevronId);
    if (!el) return;

    const hidden = el.classList.contains('hidden');
    el.classList.toggle('hidden', !hidden);
    el.classList.toggle('flex', hidden);
    if (ch) {
      ch.style.transform = hidden ? 'rotate(180deg)' : '';
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderNavbar);
  } else {
    renderNavbar();
  }
})();
