<?php
/**
 * Zeppelin Suites - Admin Navbar Component
 * Reusable DRY top navigation bar and logout modal for all admin pages.
 */
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');

$adminName = $_SESSION['full_name'] ?? $GLOBALS['full_name'] ?? 'Admin';
$adminInitial = $_SESSION['initial'] ?? $GLOBALS['initial'] ?? strtoupper(substr($adminName, 0, 1));

// Determine search configuration if not explicitly overridden
$searchId = $navSearchId ?? null;
$searchPlaceholder = $navSearchPlaceholder ?? null;
$searchAttr = $navSearchHandler ?? null;

if ($searchId === null || $searchPlaceholder === null) {
    switch ($currentScript) {
        case 'inquiry.php':
            $searchId = $searchId ?? 'inquirySearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search inquiries...';
            $searchAttr = $searchAttr ?? 'oninput="if(typeof handleInquirySearch===\'function\') handleInquirySearch(this.value)"';
            break;
        case 'reservation.php':
            $searchId = $searchId ?? 'searchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search reservations...';
            $searchAttr = $searchAttr ?? 'onkeyup="if(typeof filterSearch===\'function\') filterSearch()"';
            break;
        case 'units.php':
            $searchId = $searchId ?? 'topSearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search units, floor, owner...';
            $searchAttr = $searchAttr ?? 'onkeyup="if(typeof syncSearch===\'function\') syncSearch(this.value)"';
            break;
        case 'maintenance.php':
            $searchId = $searchId ?? 'topSearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search tickets, units, issues...';
            $searchAttr = $searchAttr ?? 'onkeyup="if(typeof syncSearch===\'function\') syncSearch(this.value)"';
            break;
        case 'residents.php':
            $searchId = $searchId ?? 'headerSearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search residents...';
            $searchAttr = $searchAttr ?? '';
            break;
        case 'analytics.php':
            $searchId = $searchId ?? 'topSearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search analytics...';
            $searchAttr = $searchAttr ?? '';
            break;
        default:
            $searchId = $searchId ?? 'topSearchInput';
            $searchPlaceholder = $searchPlaceholder ?? 'Search...';
            $searchAttr = $searchAttr ?? '';
            break;
    }
}
?>

<style>
/* ── Reusable Navbar & Modal Styles ─────────────────── */
.glass-header { 
  background: rgba(255, 255, 255, 0.85); 
  backdrop-filter: blur(16px); 
  -webkit-backdrop-filter: blur(16px); 
}
.profile-dropdown { 
  opacity: 0; 
  visibility: hidden; 
  transform: translateY(-6px); 
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
}
.profile-dropdown:not(.hidden),
.profile-dropdown.open { 
  opacity: 1; 
  visibility: visible; 
  transform: translateY(0); 
}
</style>

<!-- TOP BAR / NAVBAR -->
<header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
  <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()" title="Open Navigation Menu">
    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  
  <?php if (!empty($navBreadcrumb)): ?>
    <!-- Page Breadcrumb -->
    <?= $navBreadcrumb ?>
  <?php elseif (!empty($navCenterContent)): ?>
    <!-- Custom Center Content -->
    <?= $navCenterContent ?>
  <?php elseif (empty($navHideSearch) && $currentScript !== 'replyform.php'): ?>
    <!-- Search Input -->
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" 
             id="<?= htmlspecialchars($searchId) ?>" 
             placeholder="<?= htmlspecialchars($searchPlaceholder) ?>" 
             <?= $searchAttr ?> 
             class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
  <?php endif; ?>
  
  <div class="flex items-center gap-2 ml-auto">
    <?php if (!empty($navExtraRight)): ?>
      <?= $navExtraRight ?>
    <?php elseif ($currentScript === 'analytics.php'): ?>
      <button class="btn-press hidden sm:flex items-center gap-2 text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 rounded-full transition-all active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export
      </button>
    <?php endif; ?>

    <!-- User Profile Dropdown Menu -->
    <div class="relative" id="profileWrapper">
      <button onclick="toggleProfile(event)" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95" id="profileBtn">
        <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials"><?= htmlspecialchars($adminInitial) ?></div>
        <div class="hidden sm:block text-left">
          <p class="text-sm font-semibold text-slate-800 truncate" id="userName"><?= htmlspecialchars($adminName) ?></p>
        </div>
        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      
      <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
        <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          Account Settings
        </a>
        <div class="border-t border-slate-100 my-1"></div>
        <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">
          <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Sign out
        </button>
      </div>
    </div>
  </div>
</header>

<!-- Universal Logout Confirmation Modal -->
<div id="logoutModal" onclick="if(event.target===this) hideModal()" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
    <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
    <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
    <div class="flex gap-3 justify-end">
      <button onclick="hideModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
      <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">Logout</button>
    </div>
  </div>
</div>

<script>
function toggleProfile(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  var dd = document.getElementById('profileDropdown');
  var ch = document.getElementById('profileChevron');
  if (!dd) return;
  var isHidden = dd.classList.toggle('hidden');
  dd.classList.toggle('open', !isHidden);
  if (ch) {
    ch.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
  }
}

function toggleProfileDropdown(e) {
  toggleProfile(e);
}

document.addEventListener('click', function(e) {
  var profileBtn = document.getElementById('profileBtn') || (e.target && e.target.closest ? e.target.closest('button[onclick*="toggleProfile"]') : null);
  var profileWrapper = document.getElementById('profileWrapper') || (profileBtn ? profileBtn.closest('.relative') : null);
  var dd = document.getElementById('profileDropdown');
  var ch = document.getElementById('profileChevron');
  if (dd && !dd.classList.contains('hidden')) {
    if (!profileWrapper || !profileWrapper.contains(e.target)) {
      dd.classList.add('hidden');
      dd.classList.remove('open');
      if (ch) ch.style.transform = 'rotate(0deg)';
    }
  }
});

function confirmLogout() {
  var m = document.getElementById('logoutModal');
  if (m) m.classList.remove('hidden');
}

function hideModal() {
  var m = document.getElementById('logoutModal');
  if (m) m.classList.add('hidden');
}

function doLogout() {
  window.location.href = '../php_files/logout_session.php';
}
</script>
