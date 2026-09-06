<?php
require_once '../php_files/auth.php';
require_once '../php_files/db.php';

$userData = requireRole($conn, ['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Booking Calendar</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
/* ─────────────────────────────────────────────
   BASE / GLOBAL
───────────────────────────────────────────── */
* { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }

::-webkit-scrollbar { width: 4px; height: 6px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* ─────────────────────────────────────────────
   SIDEBAR
───────────────────────────────────────────── */
.sidebar { width:256px; transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
.sidebar.collapsed { width:68px; }
@media (max-width:767px) { .sidebar { transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:256px !important; } .sidebar.open { transform:translateX(0); } }
.main-wrapper { margin-left:256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left:68px; }
@media (max-width:767px) { .main-wrapper { margin-left:0 !important; } }
.overlay { display:none; pointer-events:none; }
.overlay.show { display:block; pointer-events:auto; }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity:0; width:0; overflow:hidden; pointer-events:none; }
.sidebar-link { position:relative; transition:all 0.18s ease; white-space:nowrap; overflow:hidden; }
.sidebar-link.active { background:#0f172a; color:#fff; }
.sidebar-link.active .nav-icon { color:#60a5fa; }
.sidebar-link:not(.active):hover { background:#eff6ff; color:#1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color:#3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,.sidebar.collapsed .notice-section { display:none; }
.sidebar.collapsed .sidebar-link { justify-content:center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform:rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after { content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#0f172a; color:#fff; font-size:12px; padding:5px 10px; border-radius:8px; white-space:nowrap; z-index:999; box-shadow:0 4px 16px rgba(0,0,0,0.18); pointer-events:none; }
.collapse-icon { transition:transform 0.3s ease; }
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition:max-height 0.3s ease,opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition:transform 0.3s ease; }
.notice-chevron.rotated { transform:rotate(180deg); }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
.main-scroll { height:calc(100vh - 65px); overflow-y:auto; }

/* ─────────────────────────────────────────────
   MISC UI
───────────────────────────────────────────── */
.btn-press { transition:all 0.15s ease; }
.btn-press:active { transform:scale(0.95); }
.zep-input:focus  { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }

/* ─────────────────────────────────────────────
   FILTER SIDEBAR — NESTED CHECKBOXES
───────────────────────────────────────────── */
.unit-type-block { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
.unit-type-header { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f8fafc; cursor: pointer; user-select: none; transition: background 0.15s; }
.unit-type-header:hover { background: #f1f5f9; }
.unit-type-chevron { margin-left: auto; transition: transform 0.25s ease; color: #94a3b8; }
.unit-type-chevron.open { transform: rotate(180deg); }
.room-list { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, opacity 0.3s ease; opacity: 0; }
.room-list.open { max-height: 400px; opacity: 1; }
.room-item { display: flex; align-items: center; gap: 10px; padding: 7px 14px 7px 28px; border-top: 1px solid #f1f5f9; transition: background 0.12s; cursor: pointer; }
.room-item:hover { background: #f8fafc; }

/* ─────────────────────────────────────────────
   HORIZONTAL TIMELINE TABLE
───────────────────────────────────────────── */
.timeline-wrapper {
  overflow-x: auto;
  overflow-y: auto;
  max-height: calc(100vh - 180px);
  position: relative;
}

.timeline-table {
  border-collapse: separate;
  border-spacing: 0;
  min-width: max-content;
  width: 100%;
}

/* Sticky header row */
.timeline-table thead tr th {
  position: sticky;
  top: 0;
  z-index: 20;
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}

/* Sticky first column (room labels) */
.timeline-table td.room-label,
.timeline-table th.label-col {
  position: sticky;
  left: 0;
  z-index: 15;
  background: #fff;
  min-width: 160px;
  max-width: 160px;
  border-right: 2px solid #e2e8f0;
}
.timeline-table thead th.label-col {
  z-index: 25;
  background: #f8fafc;
}

/* Day header cells */
.day-header {
  min-width: 52px;
  width: 52px;
  padding: 8px 4px;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  border-right: 1px solid #e2e8f0;
  white-space: nowrap;
}
.day-header.is-today {
  background: #0f172a;
  color: #fff;
  border-radius: 0;
}
.day-header.is-weekend {
  background: #f1f5f9;
  color: #94a3b8;
}

/* Unit type group header rows */
.unit-group-row td {
  background: #0f172a;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 7px 14px;
  border-right: 1px solid #1e293b;
}

/* Room data rows */
.room-row td {
  border-right: 1px solid #e2e8f0;
  border-bottom: 1px solid #f1f5f9;
}
.room-row:hover td { background: #fafbfc; }

/* Day cells */
.day-cell {
  min-width: 52px;
  width: 52px;
  height: 40px;
  position: relative;
  cursor: pointer;
  vertical-align: middle;
  padding: 0;
  transition: background 0.1s;
}
.day-cell:hover { background: #eff6ff !important; }
.day-cell.is-today-col { background: rgba(15,23,42,0.03); }
.day-cell.is-weekend-col { background: #fafafa; }

/* Booking bar inside a cell */
.cell-bar {
  position: absolute;
  top: 7px;
  bottom: 7px;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  overflow: hidden;
  white-space: nowrap;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  cursor: pointer;
  z-index: 5;
  transition: filter 0.15s, transform 0.1s;
}
.cell-bar:hover { filter: brightness(1.12); transform: scaleY(1.15); z-index: 6; }

/* Bar position rounding */
.bar-start  { margin-left: 4px; border-radius: 9999px 0 0 9999px; padding-left: 8px; }
.bar-middle { margin-left: 0;   border-radius: 0; }
.bar-end    { margin-right: 4px; border-radius: 0 9999px 9999px 0; }
.bar-single { margin-left: 4px; margin-right: 4px; border-radius: 9999px; padding-left: 8px; }

/* Status colours */
.bar-occupied    { background: #16a34a; }  /* green  */
.bar-reserved    { background: #ca8a04; }  /* yellow */
.bar-maintenance { background: #f59e0b; }  /* amber  */
.bar-unavailable { background: #64748b; }  /* grey   */

/* Legend dot colours */
.dot-occupied    { background: #16a34a; }
.dot-reserved    { background: #ca8a04; }
.dot-maintenance { background: #f59e0b; }
.dot-unavailable { background: #64748b; }

/* ─────────────────────────────────────────────
   QUICK-VIEW POPOVER (hover tooltip)
───────────────────────────────────────────── */
#quickView {
  position: fixed;
  z-index: 9000;
  width: 310px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.08);
  padding: 0;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
  transform: translateY(6px) scale(0.97);
  transition: opacity 0.18s ease, transform 0.18s ease;
}
#quickView.visible {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0) scale(1);
}
.qv-header { padding: 14px 16px 10px; border-bottom: 1px solid #f1f5f9; }
.qv-body   { padding: 12px 16px; font-size: 12px; color: #475569; line-height: 1.7; }
.qv-row    { display: flex; justify-content: space-between; }
.qv-label  { color: #94a3b8; font-weight: 500; }
.qv-value  { font-weight: 600; color: #1e293b; text-align: right; }
.qv-footer { padding: 10px 16px 14px; border-top: 1px solid #f1f5f9; }
.qv-btn-lease {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  background: #0f172a;
  color: #fff;
  transition: all 0.15s;
}
.qv-btn-lease:hover { background: #1e293b; color: #fff; }
.qv-btn-unblock {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  background: #fee2e2;
  color: #dc2626;
  transition: all 0.15s;
}
.qv-btn-unblock:hover { background: #fecaca; }

/* Status badge in quick view */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
}
.badge-occupied    { background: #dcfce7; color: #16a34a; }
.badge-reserved    { background: #fef9c3; color: #ca8a04; }
.badge-maintenance { background: #fef3c7; color: #d97706; }
.badge-unavailable { background: #f1f5f9; color: #475569; }

/* ─────────────────────────────────────────────
   BOOKING MODAL
───────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45);
  backdrop-filter: blur(4px);
  z-index: 8000;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
  opacity: 0; pointer-events: none;
  transition: opacity 0.2s ease;
}
.modal-backdrop.open { opacity: 1; pointer-events: auto; }
.modal-box {
  background: #fff;
  border-radius: 24px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 32px 80px rgba(0,0,0,0.18);
  width: 100%;
  max-width: 460px;
  max-height: 92vh;
  overflow-y: auto;
  transform: translateY(16px) scale(0.97);
  transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1);
}
.modal-backdrop.open .modal-box { transform: translateY(0) scale(1); }

/* Step indicator */
.modal-step-pill {
  display: inline-flex; align-items: center; gap: 6px;
  background: #f1f5f9; border-radius: 999px;
  padding: 4px 12px; font-size: 11px; font-weight: 600; color: #64748b;
}

/* ─────────────────────────────────────────────
   TOAST
───────────────────────────────────────────── */
#rangeToast {
  position: fixed; bottom: 24px; left: 50%;
  transform: translateX(-50%) translateY(12px);
  background: #0f172a; color: #fff;
  font-size: 13px; font-weight: 500;
  padding: 10px 20px; border-radius: 999px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);
  opacity: 0; pointer-events: none;
  transition: opacity 0.25s ease, transform 0.25s ease;
  z-index: 9999; white-space: nowrap;
}
#rangeToast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ─────────────────────────────────────────────
   EDIT MODAL (reuse modal styles, smaller)
───────────────────────────────────────────── */
#editModal .modal-box { max-width: 420px; }
</style>
</head>

<body class="bg-slate-50 text-slate-800 overflow-hidden">
<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
  <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 min-h-18.25">
    <a href="../adminPages/homeAdmin.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
    <a href="../adminPages/homeAdmin.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="../adminPages/inquiry.php" data-tooltip="Inquiry" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
   </a>
    <a href="../adminPages/reservation.php" data-tooltip="Lease Management" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Lease Management</span>
    </a>
   <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg  class="nav-icon w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-range-icon lucide-calendar-range"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/><path d="M7 14h.01"/><path d="M17 18h.01"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/residents.php" data-tooltip="residents" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <spans class="nav-label">Residents</span>
    </a>
    <a href="../adminPages/analytics.php" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
    <a href="../adminPages/account.php" data-tooltip="Account" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="nav-label">Account</span>
    </a>
  </nav>
</aside>

<!-- ═══════════════════════ MAIN ═══════════════════════ -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

<!-- TOP BAR — sticky -->
<header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
  <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  
  <div class="relative flex-1 max-w-sm">
    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" placeholder="Search..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
  </div>
  
    <div class="flex items-center gap-2 ml-auto">
        <!-- Profile -->
        <div class="relative">
          <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press">
            <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0" id="userInitials">A</div>
            <div class="hidden sm:block text-left">
              <p class="text-sm font-semibold text-slate-800 truncate" id="userName">Admin</p>
            </div>
            <svg class="w-3.5 h-3.5 text-slate-400" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          
          <!-- Simple Dropdown -->
          <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 hidden" id="profileDropdown">
            <a href="account.php" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg mx-1">Account Settings</a>
            <div class="border-t border-slate-100 my-1"></div>
            <button onclick="confirmLogout()" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-1">Sign out</button>
          </div>
        </div>
    </div>
  </header>

  <!-- Simple Modal -->
  <div id="logoutModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm border shadow-xl">
      <h3 class="text-lg font-bold text-slate-900 mb-2">Sign out?</h3>
      <p class="text-sm text-slate-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex gap-3 justify-end">
        <button onclick="hideModal()" class="px-4 py-2 text-sm hover:bg-slate-50 rounded-lg">Cancel</button>
        <button onclick="doLogout()" class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg">Logout</button>
      </div>
    </div>
  </div>

  <!-- Page Content -->
  <div class="main-scroll p-4 md:p-6">
    <!-- Page Title Bar -->
    <div class="glass-header border border-slate-100/80 px-5 py-4 mb-5 rounded-2xl flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 mb-0.5">Booking Calendar</h1>
        <p class="text-slate-500 text-xs">Click any empty date cell to block dates for maintenance or unavailable. Hover a bar for details.</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn-press px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-all active:scale-95" onclick="changeMonth(-1)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <span id="monthTitle" class="text-sm font-bold text-slate-900 min-w-[120px] text-center"></span>
        <button class="btn-press px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-all active:scale-95" onclick="changeMonth(1)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button class="btn-press px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium transition-all active:scale-95" onclick="goToday()">Today</button>
        <button class="btn-press px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs sm:text-sm font-medium transition-all active:scale-95 flex items-center gap-1.5" onclick="openBlockDatesModalManual()">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          <span>+ Block Dates</span>
        </button>
      </div>
    </div>

    <!-- Layout: Filters + Timeline -->
    <div class="flex gap-4 items-start">

      <!-- ── FILTERS SIDEBAR ── -->
      <aside class="shrink-0 w-64 bg-white border border-slate-100/80 rounded-2xl p-4 shadow-lg sticky top-0">

        <!-- Unit Types + Rooms (nested) -->
        <div class="mb-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Show Rooms</h2>
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input type="checkbox" id="selectAllTypes" class="w-3.5 h-3.5 rounded text-slate-900" checked onchange="toggleSelectAll(this)">
              <span class="text-xs font-medium text-slate-500">All</span>
            </label>
          </div>

          <div class="space-y-2" id="unitTypeFilters">
            <!-- Dynamically injected by JS from UNIT_TYPES config -->
          </div>
        </div>

        <!-- Legend -->
        <div class="mb-2">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Legend</h2>
          <div class="space-y-1.5">
            <div class="flex items-center gap-2.5 py-1.5 px-2.5 bg-slate-50 rounded-xl">
              <span class="w-3 h-3 rounded-full dot-occupied shrink-0"></span>
              <span class="text-xs font-semibold text-slate-700">Occupied</span>
            </div>
            <div class="flex items-center gap-2.5 py-1.5 px-2.5 bg-slate-50 rounded-xl">
              <span class="w-3 h-3 rounded-full dot-reserved shrink-0"></span>
              <span class="text-xs font-semibold text-slate-700">Reserved</span>
            </div>
            <div class="flex items-center gap-2.5 py-1.5 px-2.5 bg-slate-50 rounded-xl">
              <span class="w-3 h-3 rounded-full dot-maintenance shrink-0"></span>
              <span class="text-xs font-semibold text-slate-700">Maintenance</span>
            </div>
            <div class="flex items-center gap-2.5 py-1.5 px-2.5 bg-slate-50 rounded-xl">
              <span class="w-3 h-3 rounded-full dot-unavailable shrink-0"></span>
              <span class="text-xs font-semibold text-slate-700">Not Available</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- ── TIMELINE CALENDAR ── -->
      <div class="flex-1 bg-white border border-slate-100/80 rounded-2xl shadow-lg overflow-hidden">
        <div class="timeline-wrapper" id="timelineWrapper">
          <table class="timeline-table" id="timelineTable">
            <thead id="timelineHead"></thead>
            <tbody id="timelineBody"></tbody>
          </table>
        </div>
      </div>

    </div><!-- /layout -->
  </div><!-- /main-scroll -->
</div><!-- /main-wrapper -->

<!-- ═══════════ BLOCK DATES MODAL ═══════════ -->
<div class="modal-backdrop" id="blockDatesModal">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="px-7 pt-6 pb-4 border-b border-slate-100 flex items-start justify-between">
      <div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold mb-2">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
          Date Availability Control
        </div>
        <h2 class="text-xl font-bold text-slate-900">Block Unit Dates</h2>
        <p class="text-slate-400 text-xs mt-0.5">Block dates for maintenance or unavailability (does not create a reservation).</p>
      </div>
      <button onclick="closeBlockDatesModal()" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors mt-1">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="blockDatesForm" onsubmit="handleSaveBlockDates(event)" class="px-7 py-5 space-y-4">
      <input type="hidden" id="block_unitId" name="unit_id">

      <!-- Room context display -->
      <div class="flex gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex-1">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Unit Type</p>
          <p class="text-sm font-bold text-slate-800" id="block_unitTypeDisplay">—</p>
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Room</p>
          <p class="text-sm font-bold text-slate-800" id="block_roomDisplay">—</p>
        </div>
      </div>

      <!-- Manual Unit Selector (shown only if opened manually) -->
      <div id="block_manualUnitWrap" class="hidden space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Select Unit / Room <span class="text-red-400">*</span></label>
        <select id="block_manualUnitSelect" class="zep-select w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
          <option value="">Choose a room…</option>
        </select>
      </div>

      <!-- Date range -->
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Start Date <span class="text-red-400">*</span></label>
          <input id="block_startDate" name="start_date" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50" required>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">End Date <span class="text-red-400">*</span></label>
          <input id="block_endDate" name="end_date" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50" required>
        </div>
      </div>

      <!-- Block Type / Reason -->
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Block Reason / Category <span class="text-red-400">*</span></label>
        <div class="grid grid-cols-2 gap-3">
          <label class="cursor-pointer border border-slate-200 rounded-xl p-3 flex items-center gap-2.5 hover:border-slate-900 transition-all has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
            <input type="radio" name="block_type" value="Not Available" checked class="w-4 h-4 text-slate-900">
            <div>
              <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                Not Available
              </p>
              <p class="text-[11px] text-slate-500">Temporarily unlisted</p>
            </div>
          </label>
          <label class="cursor-pointer border border-slate-200 rounded-xl p-3 flex items-center gap-2.5 hover:border-slate-900 transition-all has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
            <input type="radio" name="block_type" value="Maintenance" class="w-4 h-4 text-slate-900">
            <div>
              <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                Maintenance
              </p>
              <p class="text-[11px] text-slate-500">Repairs / servicing</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Remarks -->
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Remarks / Notes</label>
        <textarea id="block_remarks" name="remarks" rows="3" placeholder="Add specific notes (e.g., Scheduled air conditioning servicing, owner personal stay, painting)..." class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 resize-none"></textarea>
      </div>

      <div id="blockErrorNotice" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium"></div>

      <div class="pb-2 flex items-center gap-3 justify-end border-t border-slate-100 pt-4">
        <button type="button" class="btn-press px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium transition-all active:scale-95" onclick="closeBlockDatesModal()">Cancel</button>
        <button type="submit" id="btnSaveBlock" class="btn-press px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow transition-all active:scale-95">Block Dates</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════ QUICK-VIEW POPOVER ═══════════ -->
<div id="quickView">
  <div class="qv-header">
    <div class="flex items-center justify-between">
      <p class="text-sm font-bold text-slate-900" id="qv_guestName">Guest Name</p>
      <span class="status-badge" id="qv_statusBadge">Status</span>
    </div>
    <p class="text-xs text-slate-400 mt-0.5" id="qv_roomInfo">Unit · Room</p>
  </div>
  <div class="qv-body" id="qv_bookingBody">
    <div class="qv-row"><span class="qv-label">Email</span><span class="qv-value" id="qv_email">—</span></div>
    <div class="qv-row"><span class="qv-label">Phone</span><span class="qv-value" id="qv_phone">—</span></div>
    <div class="qv-row"><span class="qv-label">Check-in</span><span class="qv-value" id="qv_checkin">—</span></div>
    <div class="qv-row"><span class="qv-label">Check-out</span><span class="qv-value" id="qv_checkout">—</span></div>
  </div>
  <div class="qv-body hidden" id="qv_blockedBody">
    <div class="qv-row"><span class="qv-label">Start Date</span><span class="qv-value" id="qv_blockStart">—</span></div>
    <div class="qv-row"><span class="qv-label">End Date</span><span class="qv-value" id="qv_blockEnd">—</span></div>
    <div class="qv-row"><span class="qv-label">Blocked By</span><span class="qv-value" id="qv_blockBy">—</span></div>
    <div class="mt-2 pt-2 border-t border-slate-100">
      <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Remarks</p>
      <p class="text-xs text-slate-700 italic bg-slate-50 p-2 rounded-lg" id="qv_blockRemarks">—</p>
    </div>
  </div>
  <div class="qv-footer">
    <a href="#" id="qv_viewLeaseBtn" class="qv-btn-lease">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      View Lease Details
    </a>
    <button type="button" id="qv_unblockBtn" class="qv-btn-unblock hidden" onclick="handleUnblockClick()">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      Unblock Dates
    </button>
  </div>
</div>

<!-- Toast -->
<div id="rangeToast"></div>

<!-- ═══════════════════════ JAVASCRIPT ═══════════════════════ -->
<script>
// ════════════════════════════════════════════
// CONFIGURATION — Unit Types & Rooms are now loaded from the
// database (see ActionsAP/getBookingCalendarData.php). These start
// empty and get populated by loadCalendarData() on page load.
// ════════════════════════════════════════════
let UNIT_TYPES   = [];   // [{ key: "Studio Type A", rooms: [{room,unitId,maintenance}, ...] }, ...]
let bookings     = [];   // [{ id, guestName, email, phone, unitType, roomNumber, unitId, startDate, endDate, status }, ...]
let blockedDates = [];   // [{ blockId, unitId, unitType, roomNumber, startDate, endDate, blockType, remarks, createdByRole }, ...]

// Status → bar CSS class
const STATUS_BAR = {
  "Occupied":      "bar-occupied",
  "Reserved":      "bar-reserved",
  "Maintenance":   "bar-maintenance",
  "Not Available": "bar-unavailable"
};

// Status → badge CSS class
const STATUS_BADGE = {
  "Occupied":      "badge-occupied",
  "Reserved":      "badge-reserved",
  "Maintenance":   "badge-maintenance",
  "Not Available": "badge-unavailable"
};

// ════════════════════════════════════════════
// DATA — fetched from the database
// ════════════════════════════════════════════
async function loadCalendarData() {
  try {
    const res  = await fetch("ActionsAP/getBookingCalendarData.php");
    const data = await res.json();
    if (!data.success) {
      showToast("⚠️ Could not load calendar data.");
      return;
    }
    UNIT_TYPES   = data.unitTypes || [];
    bookings     = data.bookings || [];
    blockedDates = data.blockedDates || [];
  } catch (err) {
    console.error(err);
    showToast("⚠️ Could not reach the server.");
  }
}

// Look up a room's unit_id from the loaded UNIT_TYPES config
function findUnitId(unitType, room) {
  const ut = UNIT_TYPES.find(u => u.key === unitType);
  if (!ut) return null;
  const r = ut.rooms.find(r => r.room === room);
  return r ? r.unitId : null;
}

function findRoomMeta(unitType, room) {
  const ut = UNIT_TYPES.find(u => u.key === unitType);
  if (!ut) return null;
  return ut.rooms.find(r => r.room === room) || null;
}

// ════════════════════════════════════════════
// FILTER STATE — which rooms are visible
// ════════════════════════════════════════════
// visibleRooms: Set of "UnitType::Room" keys
let visibleRooms = new Set();

function buildFilterSidebar() {
  const container = document.getElementById("unitTypeFilters");
  container.innerHTML = "";

  UNIT_TYPES.forEach(ut => {
    const block = document.createElement("div");
    block.className = "unit-type-block";

    // Header row
    const header = document.createElement("div");
    header.className = "unit-type-header";
    header.innerHTML = `
      <input type="checkbox" class="unit-type-master w-3.5 h-3.5 rounded" data-unit="${ut.key}" checked>
      <span class="text-xs font-bold text-slate-700">${ut.key}</span>
      <svg class="unit-type-chevron open w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    `;
    block.appendChild(header);

    // Room list
    const roomList = document.createElement("div");
    roomList.className = "room-list open";
    ut.rooms.forEach(room => {
      const key = ut.key + "::" + room.room;
      visibleRooms.add(key);

      const item = document.createElement("label");
      item.className = "room-item";
      item.innerHTML = `
        <input type="checkbox" class="room-toggle w-3.5 h-3.5 rounded" data-unit="${ut.key}" data-room="${room.room}" checked>
        <span class="text-xs font-medium text-slate-600">Room ${room.room}</span>
        ${room.maintenance ? '<span class="w-1.5 h-1.5 rounded-full dot-maintenance shrink-0 ml-auto" title="Under maintenance"></span>' : ''}
      `;
      roomList.appendChild(item);
    });
    block.appendChild(roomList);

    container.appendChild(block);

    // Toggle collapse on header click (but not on checkbox click)
    header.addEventListener("click", e => {
      if (e.target.type === "checkbox") return;
      const chevron = header.querySelector(".unit-type-chevron");
      const isOpen = roomList.classList.toggle("open");
      chevron.classList.toggle("open", isOpen);
    });

    // Master checkbox: check/uncheck all rooms in this type
    const masterCb = header.querySelector(".unit-type-master");
    masterCb.addEventListener("change", () => {
      roomList.querySelectorAll(".room-toggle").forEach(cb => {
        cb.checked = masterCb.checked;
        const k = cb.dataset.unit + "::" + cb.dataset.room;
        masterCb.checked ? visibleRooms.add(k) : visibleRooms.delete(k);
      });
      updateSelectAll();
      renderTimeline();
    });
  });

  // Individual room checkboxes
  container.addEventListener("change", e => {
    if (!e.target.classList.contains("room-toggle")) return;
    const k = e.target.dataset.unit + "::" + e.target.dataset.room;
    e.target.checked ? visibleRooms.add(k) : visibleRooms.delete(k);

    // Sync master checkbox for this unit type
    const unitKey = e.target.dataset.unit;
    const allRoomCbs = container.querySelectorAll(`.room-toggle[data-unit="${unitKey}"]`);
    const allChecked = Array.from(allRoomCbs).every(cb => cb.checked);
    const masterCb = container.querySelector(`.unit-type-master[data-unit="${unitKey}"]`);
    if (masterCb) masterCb.checked = allChecked;

    updateSelectAll();
    renderTimeline();
  });
}

function toggleSelectAll(cb) {
  const allRoomCbs = document.querySelectorAll(".room-toggle");
  const allMasterCbs = document.querySelectorAll(".unit-type-master");
  allRoomCbs.forEach(r => {
    r.checked = cb.checked;
    const k = r.dataset.unit + "::" + r.dataset.room;
    cb.checked ? visibleRooms.add(k) : visibleRooms.delete(k);
  });
  allMasterCbs.forEach(m => m.checked = cb.checked);
  renderTimeline();
}

function updateSelectAll() {
  const allRoomCbs = document.querySelectorAll(".room-toggle");
  const allChecked = Array.from(allRoomCbs).every(cb => cb.checked);
  document.getElementById("selectAllTypes").checked = allChecked;
}

// Populate manual unit select in blockDatesModal
function populateBlockManualUnits() {
  const sel = document.getElementById("block_manualUnitSelect");
  if (!sel) return;
  sel.innerHTML = '<option value="">Choose a room…</option>';
  UNIT_TYPES.forEach(ut => {
    ut.rooms.forEach(r => {
      const opt = document.createElement("option");
      opt.value = r.unitId;
      opt.textContent = `${ut.key} — Room ${r.room}` + (r.maintenance ? " (Under maintenance)" : "");
      opt.dataset.unitType = ut.key;
      opt.dataset.room = r.room;
      sel.appendChild(opt);
    });
  });
  sel.addEventListener("change", () => {
    const opt = sel.selectedOptions[0];
    if (opt && opt.value) {
      document.getElementById("block_unitId").value = opt.value;
      document.getElementById("block_unitTypeDisplay").textContent = opt.dataset.unitType || "—";
      document.getElementById("block_roomDisplay").textContent = "Room " + opt.dataset.room;
    }
  });
}

// ════════════════════════════════════════════
// TIMELINE RENDER
// ════════════════════════════════════════════
let currentDate = new Date();

function renderTimeline() {
  const year  = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const today = toLocalISODate(new Date());

  const lastDay   = new Date(year, month + 1, 0);
  const totalDays = lastDay.getDate();
  const days      = Array.from({ length: totalDays }, (_, i) => i + 1);

  document.getElementById("monthTitle").textContent =
    currentDate.toLocaleString("default", { month: "long", year: "numeric" });

  // Build day labels
  const dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];

  // ── HEAD ──────────────────────────────────
  const thead = document.getElementById("timelineHead");
  thead.innerHTML = "";
  const headRow = document.createElement("tr");

  const labelTh = document.createElement("th");
  labelTh.className = "label-col text-left px-3 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b-2 border-slate-200";
  labelTh.textContent = "Room";
  headRow.appendChild(labelTh);

  days.forEach(d => {
    const dateStr = formatDate(year, month, d);
    const dow     = new Date(year, month, d).getDay();
    const isWknd  = dow === 0 || dow === 6;
    const isTdy   = dateStr === today;
    const th      = document.createElement("th");
    th.className  = "day-header" + (isTdy ? " is-today" : isWknd ? " is-weekend" : "");
    th.innerHTML  = `<div style="font-size:10px;line-height:1.1">${dayNames[dow]}</div><div style="font-size:13px;font-weight:700">${d}</div>`;
    headRow.appendChild(th);
  });
  thead.appendChild(headRow);

  // ── BODY ──────────────────────────────────
  const tbody = document.getElementById("timelineBody");
  tbody.innerHTML = "";

  UNIT_TYPES.forEach(ut => {
    // Filter rooms that are visible
    const visRooms = ut.rooms.filter(r => visibleRooms.has(ut.key + "::" + r.room));
    if (visRooms.length === 0) return;

    // Group header row
    const groupRow = document.createElement("tr");
    groupRow.className = "unit-group-row";
    const groupTd = document.createElement("td");
    groupTd.colSpan = totalDays + 1;
    groupTd.textContent = ut.key;
    groupRow.appendChild(groupTd);
    tbody.appendChild(groupRow);

    // Each room row
    visRooms.forEach(roomMeta => {
      const room = roomMeta.room;
      const tr = document.createElement("tr");
      tr.className = "room-row";

      // Room label cell
      const labelTd = document.createElement("td");
      labelTd.className = "room-label px-3 py-0 text-xs font-semibold text-slate-600";
      labelTd.style.height = "40px";
      labelTd.innerHTML = `<span class="block truncate">Room ${room}</span>`;
      tr.appendChild(labelTd);

      // A room under maintenance is a unit-level state, not a dated
      // booking — render the whole row as blocked and skip bookings.
      if (roomMeta.maintenance) {
        const td = document.createElement("td");
        td.colSpan = totalDays;
        td.className = "day-cell";
        td.style.cursor = "not-allowed";
        td.innerHTML = `<div class="cell-bar bar-single bar-maintenance" style="position:static;width:calc(100% - 8px);justify-content:center;">Under Maintenance</div>`;
        td.addEventListener("click", () => showToast("🛠 This unit is under maintenance. Change its status on the Units page to book it."));
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
      }

      // Get bookings for this room
      const roomBookings = bookings.filter(b =>
        b.unitType === ut.key && b.roomNumber === room
      );

      // Get blocked dates for this room
      const roomBlocked = blockedDates.filter(b =>
        (b.unitId && roomMeta.unitId && b.unitId === roomMeta.unitId) ||
        (b.unitType === ut.key && b.roomNumber === room)
      );

      // Day cells
      days.forEach(d => {
        const dateStr = formatDate(year, month, d);
        const dow     = new Date(year, month, d).getDay();
        const isWknd  = dow === 0 || dow === 6;
        const isTdy   = dateStr === today;

        const td = document.createElement("td");
        td.className = "day-cell" + (isTdy ? " is-today-col" : isWknd ? " is-weekend-col" : "");
        td.dataset.date = dateStr;
        td.dataset.unit = ut.key;
        td.dataset.room = room;

        const booking = roomBookings.find(b => dateStr >= b.startDate && dateStr <= b.endDate);
        const block   = roomBlocked.find(b => dateStr >= b.startDate && dateStr <= b.endDate);

        if (booking) {
          const isStart  = dateStr === booking.startDate;
          const isEnd    = dateStr === booking.endDate;
          const isSingle = isStart && isEnd;

          let posClass = isSingle ? "bar-single" : isStart ? "bar-start" : isEnd ? "bar-end" : "bar-middle";

          const bar = document.createElement("div");
          bar.className = `cell-bar ${posClass} ${STATUS_BAR[booking.status] || "bar-reserved"}`;
          bar.dataset.bookingId = booking.id;

          if (isStart || isSingle) {
            bar.textContent = booking.guestName || "—";
          }

          bar.addEventListener("mouseenter", e => showQuickView(booking, e));
          bar.addEventListener("mouseleave", startHideTimer);
          bar.addEventListener("click", e => {
            e.stopPropagation();
            showQuickView(booking, e, true);
          });

          td.appendChild(bar);
        } else if (block) {
          const isStart  = dateStr === block.startDate;
          const isEnd    = dateStr === block.endDate;
          const isSingle = isStart && isEnd;

          let posClass = isSingle ? "bar-single" : isStart ? "bar-start" : isEnd ? "bar-end" : "bar-middle";

          const bar = document.createElement("div");
          const barClass = block.blockType === "Maintenance" ? "bar-maintenance" : "bar-unavailable";
          bar.className = `cell-bar ${posClass} ${barClass}`;
          bar.dataset.blockId = block.blockId;

          if (isStart || isSingle) {
            bar.textContent = block.remarks ? (block.blockType + " — " + block.remarks) : block.blockType;
          }

          bar.addEventListener("mouseenter", e => showBlockedQuickView(block, e));
          bar.addEventListener("mouseleave", startHideTimer);
          bar.addEventListener("click", e => {
            e.stopPropagation();
            showBlockedQuickView(block, e, true);
          });

          td.appendChild(bar);
        } else {
          // Empty cell: click to block dates
          td.addEventListener("click", () => handleCellClick(ut.key, room, roomMeta.unitId, dateStr));
        }

        tr.appendChild(td);
      });

      tbody.appendChild(tr);
    });
  });
}

function formatDate(year, month, day) {
  const d = new Date(year, month, day);
  return toLocalISODate(d);
}

// Timezone-safe date helpers.
function toLocalISODate(d) {
  return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function parseLocalDate(dateStr) {
  const [y, m, d] = dateStr.split("-").map(Number);
  return new Date(y, m - 1, d);
}

function changeMonth(dir) {
  currentDate.setMonth(currentDate.getMonth() + dir);
  renderTimeline();
}

function goToday() {
  currentDate = new Date();
  renderTimeline();
}

// ════════════════════════════════════════════
// BLOCK DATES FLOW
// ════════════════════════════════════════════
function handleCellClick(unitType, room, unitId, dateStr) {
  openBlockDatesModal(unitType, room, unitId, dateStr);
}

function openBlockDatesModal(unitType, room, unitId, startDate) {
  const modal = document.getElementById("blockDatesModal");
  const errNotice = document.getElementById("blockErrorNotice");
  if (errNotice) errNotice.classList.add("hidden");

  document.getElementById("block_unitId").value = unitId || "";
  document.getElementById("block_unitTypeDisplay").textContent = unitType || "—";
  document.getElementById("block_roomDisplay").textContent = room ? "Room " + room : "—";
  document.getElementById("block_startDate").value = startDate || "";
  document.getElementById("block_endDate").value   = startDate || "";
  document.getElementById("block_remarks").value   = "";

  const defaultRadio = document.querySelector('input[name="block_type"][value="Not Available"]');
  if (defaultRadio) defaultRadio.checked = true;

  const manualWrap = document.getElementById("block_manualUnitWrap");
  if (!unitId) {
    manualWrap?.classList.remove("hidden");
    const manualSelect = document.getElementById("block_manualUnitSelect");
    if (manualSelect) manualSelect.value = "";
  } else {
    manualWrap?.classList.add("hidden");
  }

  modal.classList.add("open");
}

function openBlockDatesModalManual() {
  const today = toLocalISODate(new Date());
  openBlockDatesModal(null, null, null, today);
}

function closeBlockDatesModal() {
  document.getElementById("blockDatesModal").classList.remove("open");
}
document.getElementById("blockDatesModal")?.addEventListener("click", e => {
  if (e.target === e.currentTarget) closeBlockDatesModal();
});

async function handleSaveBlockDates(e) {
  e.preventDefault();
  const form = document.getElementById("blockDatesForm");
  const btn = document.getElementById("btnSaveBlock");
  const errNotice = document.getElementById("blockErrorNotice");
  errNotice.classList.add("hidden");

  let unitId = document.getElementById("block_unitId").value;
  if (!unitId) {
    unitId = document.getElementById("block_manualUnitSelect").value;
    document.getElementById("block_unitId").value = unitId;
  }

  if (!unitId) {
    errNotice.textContent = "Please select a room to block.";
    errNotice.classList.remove("hidden");
    return;
  }

  btn.disabled = true;
  btn.textContent = "Blocking...";

  try {
    const fd = new FormData(form);
    const res = await fetch("ActionsAP/saveBlockedDate.php", { method: "POST", body: fd });
    const data = await res.json();

    if (data.success) {
      closeBlockDatesModal();
      showToast(data.message || "Dates blocked successfully.");
      await loadCalendarData();
      renderTimeline();
    } else {
      errNotice.textContent = data.message || "Failed to block dates.";
      errNotice.classList.remove("hidden");
    }
  } catch (err) {
    console.error(err);
    errNotice.textContent = "A network error occurred. Please try again.";
    errNotice.classList.remove("hidden");
  } finally {
    btn.disabled = false;
    btn.textContent = "Block Dates";
  }
}

// ════════════════════════════════════════════
// QUICK VIEW POPOVER (Hover / Click)
// ════════════════════════════════════════════
let currentQuickView = null; // { type: 'booking' | 'blocked', data: ... }
let _hideTimer       = null;

function showQuickView(booking, e, pin = false) {
  clearTimeout(_hideTimer);
  currentQuickView = { type: 'booking', data: booking };

  const qv = document.getElementById("quickView");
  document.getElementById("qv_guestName").textContent = booking.guestName || "—";
  document.getElementById("qv_email").textContent     = booking.email  || "—";
  document.getElementById("qv_phone").textContent     = booking.phone  || "—";
  document.getElementById("qv_checkin").textContent   = formatDisplayDate(booking.startDate);
  document.getElementById("qv_checkout").textContent  = formatDisplayDate(booking.endDate);
  document.getElementById("qv_roomInfo").textContent  = booking.unitType + " · Room " + booking.roomNumber;

  const badge = document.getElementById("qv_statusBadge");
  badge.textContent  = booking.status;
  badge.className    = "status-badge " + (STATUS_BADGE[booking.status] || "badge-reserved");

  document.getElementById("qv_bookingBody").classList.remove("hidden");
  document.getElementById("qv_blockedBody").classList.add("hidden");

  // View Lease Details button points to viewReservation.php
  const leaseBtn = document.getElementById("qv_viewLeaseBtn");
  leaseBtn.href = `viewReservation.php?reservation_id=${booking.id}`;
  leaseBtn.classList.remove("hidden");
  document.getElementById("qv_unblockBtn").classList.add("hidden");

  // Position
  const x = Math.min(e.clientX + 14, window.innerWidth  - 330);
  const y = Math.min(e.clientY + 10, window.innerHeight - 260);
  qv.style.left = x + "px";
  qv.style.top  = y + "px";
  qv.classList.add("visible");

  if (pin) {
    qv.addEventListener("mouseleave", startHideTimer, { once: true });
  }
}

function showBlockedQuickView(block, e, pin = false) {
  clearTimeout(_hideTimer);
  currentQuickView = { type: 'blocked', data: block };

  const qv = document.getElementById("quickView");
  document.getElementById("qv_guestName").textContent = block.blockType;
  document.getElementById("qv_roomInfo").textContent  = block.unitType + " · Room " + block.roomNumber;

  const badge = document.getElementById("qv_statusBadge");
  badge.textContent  = block.blockType;
  badge.className    = "status-badge " + (block.blockType === "Maintenance" ? "badge-maintenance" : "badge-unavailable");

  document.getElementById("qv_blockStart").textContent   = formatDisplayDate(block.startDate);
  document.getElementById("qv_blockEnd").textContent     = formatDisplayDate(block.endDate);
  document.getElementById("qv_blockBy").textContent      = block.createdByRole === "unit owner" ? "Unit Owner" : "Admin";
  document.getElementById("qv_blockRemarks").textContent = block.remarks || "No remarks provided.";

  document.getElementById("qv_bookingBody").classList.add("hidden");
  document.getElementById("qv_blockedBody").classList.remove("hidden");

  document.getElementById("qv_viewLeaseBtn").classList.add("hidden");
  document.getElementById("qv_unblockBtn").classList.remove("hidden");

  // Position
  const x = Math.min(e.clientX + 14, window.innerWidth  - 330);
  const y = Math.min(e.clientY + 10, window.innerHeight - 280);
  qv.style.left = x + "px";
  qv.style.top  = y + "px";
  qv.classList.add("visible");

  if (pin) {
    qv.addEventListener("mouseleave", startHideTimer, { once: true });
  }
}

function startHideTimer() {
  _hideTimer = setTimeout(hideQuickView, 300);
}

function hideQuickView() {
  document.getElementById("quickView").classList.remove("visible");
  currentQuickView = null;
}

document.getElementById("quickView").addEventListener("mouseenter", () => clearTimeout(_hideTimer));
document.getElementById("quickView").addEventListener("mouseleave", startHideTimer);

async function handleUnblockClick() {
  if (!currentQuickView || currentQuickView.type !== 'blocked' || !currentQuickView.data) return;
  const block = currentQuickView.data;
  if (!confirm(`Unblock Room ${block.roomNumber} (${formatDisplayDate(block.startDate)} to ${formatDisplayDate(block.endDate)})?`)) return;

  const fd = new FormData();
  fd.append("block_id", block.blockId);

  try {
    const res = await fetch("ActionsAP/deleteBlockedDate.php", { method: "POST", body: fd });
    const data = await res.json();
    if (!data.success) {
      alert(data.message || "Could not unblock dates.");
      return;
    }
    hideQuickView();
    showToast("Dates successfully unblocked.");
    await loadCalendarData();
    renderTimeline();
  } catch (err) {
    console.error(err);
    alert("Could not reach the server.");
  }
}

// ════════════════════════════════════════════
// TOAST
// ════════════════════════════════════════════
let _toastTimer = null;
function showToast(msg) {
  const el = document.getElementById("rangeToast");
  el.innerHTML = msg;
  el.classList.add("show");
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(() => el.classList.remove("show"), 3000);
}

// ════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════
function formatDisplayDate(str) {
  if (!str) return "—";
  const d = new Date(str + "T00:00:00");
  return d.toLocaleDateString("en-PH", { month: "short", day: "numeric", year: "numeric" });
}

// ════════════════════════════════════════════
// SIDEBAR UTILS (preserved from original)
// ════════════════════════════════════════════
function toggleCollapse() {
  document.getElementById("sidebar").classList.toggle("collapsed");
  document.getElementById("mainWrapper").classList.toggle("sidebar-collapsed");
}
function openMobileSidebar() {
  document.getElementById("sidebar").classList.add("open");
  document.getElementById("overlay").classList.add("show");
}
function closeMobileSidebar() {
  document.getElementById("sidebar").classList.remove("open");
  document.getElementById("overlay").classList.remove("show");
}

  // Logout modal functions
document.addEventListener('DOMContentLoaded', function() {
  const userName = '<?php echo htmlspecialchars($_SESSION["full_name"] ?? "Admin"); ?>';
  const initials = '<?php echo $_SESSION["initial"] ?? "A"; ?>';
  
  document.getElementById('userName').textContent = userName;
  document.getElementById('userInitials').textContent = initials;
});

let profileOpen = false;

function toggleProfile() {
  const dd = document.getElementById('profileDropdown');
  const ch = document.getElementById('profileChevron');
  const open = dd.classList.toggle('open');
  ch.style.transform = open ? 'rotate(180deg)' : '';
}
function toggleProfile() {
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  
  dropdown.classList.toggle('hidden');  // Toggle hidden class
  chevron.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
  const profileBtn = e.target.closest('button[onclick="toggleProfile()"]');
  const profileWrapper = document.querySelector('.relative'); // Your profile container
  
  if (!profileWrapper.contains(e.target) && !profileBtn) {
    document.getElementById('profileDropdown').classList.add('hidden');
    document.getElementById('profileChevron').style.transform = 'rotate(0deg)';
  }
});
document.addEventListener("click", e => {
  if (!document.getElementById("profileWrapper").contains(e.target)) {
    document.getElementById("profileDropdown").classList.remove("open");
    document.getElementById("profileChevron").style.transform = "";
  }
});

function confirmLogout() {
  document.getElementById('logoutModal').classList.remove('hidden');
}

function hideModal() {
  document.getElementById('logoutModal').classList.add('hidden');
}

function doLogout() {
  window.location.href = '/Zeppelin-Suites/public/php_files/logout_session.php';  // Your logout file
}

// ════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════
(async function init() {
  await loadCalendarData();
  buildFilterSidebar();
  populateBlockManualUnits();
  renderTimeline();

  // Scroll to today column on load
  setTimeout(() => {
    const today = toLocalISODate(new Date());
    const todayTh = document.querySelector(`th[data-date="${today}"]`);
    if (todayTh) todayTh.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
  }, 200);
})();
</script>

</body>
</html>