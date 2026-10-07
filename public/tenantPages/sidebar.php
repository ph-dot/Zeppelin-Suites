<?php
/**
 * Zeppelin Suites - Tenant Sidebar Component
 * Reusable DRY sidebar for all tenant pages.
 */
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$sidebarActiveTab = $sidebarActiveTab ?? $navActiveTab ?? '';

if (empty($sidebarActiveTab)) {
    switch ($currentScript) {
        case 'homeTenant.php':
            $sidebarActiveTab = 'home';
            break;
        case 'maintenanceTenant.php':
            $sidebarActiveTab = 'maintenance';
            break;
        case 'account.php':
            $sidebarActiveTab = 'account';
            break;
        default:
            $sidebarActiveTab = $activeTab ?? '';
            break;
    }
}

$navItems = [
    [
        'id' => 'home',
        'label' => 'Home',
        'url' => 'homeTenant.php',
        'svg' => '<svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'
    ],
    [
        'id' => 'maintenance',
        'label' => 'Maintenance',
        'url' => 'maintenanceTenant.php',
        'svg' => '<svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
    ],
    [
        'id' => 'account',
        'label' => 'Account',
        'url' => 'account.php',
        'svg' => '<svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
    ]
];
?>

<style>
/* ── Reusable Tenant Sidebar Styles ─────────────────── */
.sidebar {
  width: 256px;
  transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
}
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) {
  .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; }
  .sidebar.open { transform: translateX(0); }
}
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label, .sidebar.collapsed .logo-text { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after {
  content: attr(data-tooltip);
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
  background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px;
  border-radius: 8px; white-space: nowrap; z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none;
}
.nav-label, .logo-text { transition: opacity 0.2s ease; }
.collapse-icon { transition: transform 0.3s ease; }
</style>

<!-- Overlay -->
<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-[73px]">
    <a href="homeTenant.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain" onerror="this.outerHTML='<span class=\'font-bold text-slate-900 text-sm tracking-tight\'>ZEPPELIN SUITES</span>'">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1" title="Toggle Sidebar">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = ($sidebarActiveTab === $item['id']); ?>
      <a href="<?= htmlspecialchars($item['url']) ?>"
         data-tooltip="<?= htmlspecialchars($item['label']) ?>"
         class="sidebar-link <?= $isActive ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= $isActive ? 'text-white' : 'text-slate-500' ?>">
        <?= $item['svg'] ?>
        <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>

<script>
window.sidebarCollapsed = window.sidebarCollapsed || false;
function toggleCollapse() {
  window.sidebarCollapsed = !window.sidebarCollapsed;
  var sb = document.getElementById('sidebar');
  var mw = document.getElementById('mainWrapper');
  if (sb) sb.classList.toggle('collapsed', window.sidebarCollapsed);
  if (mw) mw.classList.toggle('sidebar-collapsed', window.sidebarCollapsed);
}
function openMobileSidebar() {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('overlay');
  if (sb) sb.classList.add('open');
  if (ov) ov.classList.add('show');
}
function closeMobileSidebar() {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('overlay');
  if (sb) sb.classList.remove('open');
  if (ov) ov.classList.remove('show');
}
</script>
