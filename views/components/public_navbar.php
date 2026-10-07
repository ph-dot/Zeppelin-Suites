<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Public Navigation Bar Component
 * Reusable DRY navigation component for all public/general pages.
 *
 * @var string $baseUrl Base application URL
 * @var string $activePage Current active page identifier (home, tour, studio_a, studio_b, one_bed, two_bed, faq, about, contact)
 */
$activePage = $activePage ?? '';
$baseUrl = rtrim((string)($baseUrl ?? env('APP_URL', '/Zeppelin-Suites/public')), '/');

$isUnit = in_array($activePage, ['studio_a', 'studio_b', 'one_bed', 'two_bed'], true);

$linkClass = function (bool $isActive): string {
    return $isActive
        ? 'text-sm text-zinc-900 font-semibold transition-colors'
        : 'text-sm text-zinc-600 hover:text-zinc-900 transition-colors';
};

$dropdownClass = function (bool $isActive): string {
    return $isActive
        ? 'block px-4 py-2 text-sm text-zinc-900 font-semibold bg-zinc-50'
        : 'block px-4 py-2 text-sm text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 transition-colors';
};
?>
<nav class="sticky top-0 w-full bg-white/90 backdrop-blur-md px-6 md:px-16 lg:px-24 xl:px-32 py-4 flex items-center justify-between z-50 border-b border-zinc-200/70 transition-all">
  <a href="<?= htmlspecialchars($baseUrl) ?>/" class="flex items-center gap-2 group">
    <img src="<?= htmlspecialchars($baseUrl) ?>/images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-12 w-auto object-contain transition-transform group-hover:scale-105"
      onerror="this.outerHTML='<span class=\'font-bold text-xl tracking-tight text-zinc-900\'>ZEPPELIN<br><span class=\'text-xs font-normal tracking-widest text-amber-600\'>SUITES</span></span>'">
  </a>

  <!-- Desktop Navigation -->
  <div class="hidden min-[900px]:flex items-center gap-7">
    <a href="<?= htmlspecialchars($baseUrl) ?>/" class="<?= $linkClass($activePage === 'home') ?>">Home</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/tour" class="<?= $linkClass($activePage === 'tour') ?>">Take a Tour</a>

    <!-- Units Dropdown -->
    <div class="relative group" id="desktopUnitsDropdown">
      <button type="button" aria-haspopup="true" aria-expanded="false"
        class="flex items-center gap-1.5 py-1 text-sm <?= $isUnit ? 'text-zinc-900 font-semibold' : 'text-zinc-600 hover:text-zinc-900' ?> transition-colors">
        <span>Units</span>
        <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-200 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <div class="absolute left-0 top-full pt-2 w-52 hidden group-hover:block transition-all">
        <div class="bg-white rounded-xl shadow-xl border border-zinc-200/80 py-1.5 overflow-hidden">
          <a href="<?= htmlspecialchars($baseUrl) ?>/units/studio-type-a" class="<?= $dropdownClass($activePage === 'studio_a') ?>">Studio Type A</a>
          <a href="<?= htmlspecialchars($baseUrl) ?>/units/studio-type-b" class="<?= $dropdownClass($activePage === 'studio_b') ?>">Studio Type B</a>
          <a href="<?= htmlspecialchars($baseUrl) ?>/units/one-bedroom" class="<?= $dropdownClass($activePage === 'one_bed') ?>">One Bedroom</a>
          <a href="<?= htmlspecialchars($baseUrl) ?>/units/two-bedroom" class="<?= $dropdownClass($activePage === 'two_bed') ?>">Two Bedroom</a>
        </div>
      </div>
    </div>

    <a href="<?= htmlspecialchars($baseUrl) ?>/faq" class="<?= $linkClass($activePage === 'faq') ?>">FAQ</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/about" class="<?= $linkClass($activePage === 'about') ?>">About Us</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="<?= $linkClass($activePage === 'contact') ?>">Contact</a>
  </div>

  <!-- Right Actions: Portal Login -->
  <div class="hidden min-[900px]:flex items-center gap-4">
    <a href="<?= htmlspecialchars($baseUrl) ?>/login"
      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-zinc-950 text-white hover:bg-zinc-800 text-xs font-semibold tracking-wider uppercase shadow-sm transition-all hover:shadow hover:-translate-y-0.5 active:translate-y-0 active:scale-95">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
      </svg>
      <span>Portal Login</span>
    </a>
  </div>

  <!-- Mobile Menu Button -->
  <button id="mobileMenuBtn" type="button" aria-label="Toggle Navigation Menu"
    class="min-[900px]:hidden p-2 rounded-xl text-zinc-700 hover:text-zinc-950 hover:bg-zinc-100 transition-colors">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>
</nav>

<!-- Mobile Navigation Drawer -->
<div id="mobileMenuDrawer" class="hidden min-[900px]:hidden border-b border-zinc-200 bg-white/95 backdrop-blur-md px-6 py-5 shadow-lg">
  <div class="flex flex-col gap-2">
    <a href="<?= htmlspecialchars($baseUrl) ?>/" class="px-4 py-2 rounded-lg text-sm <?= $activePage === 'home' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-zinc-50' ?>">Home</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/tour" class="px-4 py-2 rounded-lg text-sm <?= $activePage === 'tour' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-zinc-50' ?>">Take a Tour</a>

    <!-- Mobile Units Collapsible -->
    <div>
      <button id="mobileUnitsToggle" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded-lg text-sm text-zinc-700 hover:bg-zinc-50">
        <span class="<?= $isUnit ? 'font-bold text-zinc-900' : '' ?>">Units</span>
        <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div id="mobileUnitsSubmenu" class="hidden pl-6 pr-2 py-1 flex flex-col gap-1 border-l-2 border-zinc-100 ml-4 mt-1">
        <a href="<?= htmlspecialchars($baseUrl) ?>/units/studio-type-a" class="px-3 py-1.5 rounded text-xs <?= $activePage === 'studio_a' ? 'font-bold text-zinc-900 bg-zinc-100' : 'text-zinc-600' ?>">Studio Type A</a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/units/studio-type-b" class="px-3 py-1.5 rounded text-xs <?= $activePage === 'studio_b' ? 'font-bold text-zinc-900 bg-zinc-100' : 'text-zinc-600' ?>">Studio Type B</a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/units/one-bedroom" class="px-3 py-1.5 rounded text-xs <?= $activePage === 'one_bed' ? 'font-bold text-zinc-900 bg-zinc-100' : 'text-zinc-600' ?>">One Bedroom</a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/units/two-bedroom" class="px-3 py-1.5 rounded text-xs <?= $activePage === 'two_bed' ? 'font-bold text-zinc-900 bg-zinc-100' : 'text-zinc-600' ?>">Two Bedroom</a>
      </div>
    </div>

    <a href="<?= htmlspecialchars($baseUrl) ?>/faq" class="px-4 py-2 rounded-lg text-sm <?= $activePage === 'faq' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-zinc-50' ?>">FAQ</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/about" class="px-4 py-2 rounded-lg text-sm <?= $activePage === 'about' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-zinc-50' ?>">About Us</a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="px-4 py-2 rounded-lg text-sm <?= $activePage === 'contact' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-zinc-50' ?>">Contact</a>

    <div class="pt-3 border-t border-zinc-100 mt-2">
      <a href="<?= htmlspecialchars($baseUrl) ?>/login"
        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-zinc-950 text-white text-xs font-semibold tracking-wider uppercase">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        <span>Portal Login</span>
      </a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const menuBtn = document.getElementById('mobileMenuBtn');
  const drawer = document.getElementById('mobileMenuDrawer');
  const unitsToggle = document.getElementById('mobileUnitsToggle');
  const unitsSub = document.getElementById('mobileUnitsSubmenu');

  if (menuBtn && drawer) {
    menuBtn.addEventListener('click', function() {
      drawer.classList.toggle('hidden');
    });
  }

  if (unitsToggle && unitsSub) {
    unitsToggle.addEventListener('click', function() {
      unitsSub.classList.toggle('hidden');
    });
  }
});
</script>
