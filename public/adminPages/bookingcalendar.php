<?php
require_once '../php_files/admin_auth.php';
require_once '../php_files/db.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites — Booking Calendar</title>
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
.profile-dropdown.open { opacity:1; visibility:visible; transform:translateY(0); }
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
  max-height: calc(100vh - 260px);
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
.bar-maintenance { background: #dc2626; }  /* red    */

/* Legend dot colours */
.dot-occupied    { background: #16a34a; }
.dot-reserved    { background: #ca8a04; }
.dot-maintenance { background: #dc2626; }

/* ─────────────────────────────────────────────
   QUICK-VIEW POPOVER (hover tooltip)
───────────────────────────────────────────── */
#quickView {
  position: fixed;
  z-index: 9000;
  width: 300px;
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
.qv-footer { padding: 10px 16px 14px; border-top: 1px solid #f1f5f9; display: flex; gap: 8px; }
.qv-btn    { flex: 1; padding: 7px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
.qv-btn-edit   { background: #eff6ff; color: #1d4ed8; }
.qv-btn-edit:hover   { background: #dbeafe; }
.qv-btn-delete { background: #fef2f2; color: #dc2626; }
.qv-btn-delete:hover { background: #fee2e2; }
.qv-btn-done   { background: #0f172a; color: #fff; }
.qv-btn-done:hover   { background: #1e293b; }

/* Status badge in quick view */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
}
.badge-occupied    { background: #dcfce7; color: #16a34a; }
.badge-reserved    { background: #fef9c3; color: #ca8a04; }
.badge-maintenance { background: #fee2e2; color: #dc2626; }

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
      <span class="nav-badge ml-auto bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center shrink-0" style="font-size:10px;font-family:'DM Mono',monospace;">3</span>
    </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
      <span class="nav-badge ml-auto bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center shrink-0" style="font-size:10px;font-family:'DM Mono',monospace;">3</span>
    </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/roomsAdmin.php" data-tooltip="Rooms" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M7 6h10a2 2 0 012 2v8a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
      <span class="nav-label">Rooms</span>
    </a>
    <a href="../adminPages/bookingcalendar.php" data-tooltip="Booking Calendar" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Booking Calendar</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/employees.php" data-tooltip="Employees" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Employees</span>
    </a>
    <a href="../adminPages/analytics.html" data-tooltip="Analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
    <a href="../adminPages/settingsAdmin.php" data-tooltip="Settings" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Settings</span>
    </a>
  </nav>
  <div class="notice-section px-2 py-4 border-t border-slate-100 shrink-0">
    <button onclick="toggleNotice()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-50 transition-all btn-press active:scale-95">
      <span class="nav-label">Notice</span>
      <svg class="notice-chevron w-3.5 h-3.5 text-slate-400 shrink-0" id="noticeChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="notice-panel open px-2 pt-1 space-y-0.5" id="noticePanel">
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Summer Vacation</span></a>
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Employment Notice</span></a>
    </div>
  </div>
</aside>

<!-- ═══════════════════════ MAIN ═══════════════════════ -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- Top Header -->
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" placeholder="Search bookings..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <button class="relative p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">A</div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none">Admin Name</p>
            <p class="text-xs text-slate-400 mt-0.5">Admin</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-2xl py-2 z-50" id="profileDropdown">
          <a href="../adminPages/myProfileAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>My Profile</a>
          <a href="../adminPages/settingsAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Settings</a>
          <div class="border-t border-slate-100 my-1 mx-3"></div>
          <a href="public/generalViewPages/login.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Sign out</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Page Content -->
  <div class="main-scroll p-4 md:p-6">
    <!-- Page Title Bar -->
    <div class="glass-header border border-slate-100/80 px-5 py-4 mb-5 rounded-2xl flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 mb-0.5">Booking Calendar</h1>
        <p class="text-slate-500 text-xs">Click any date cell to start a new booking. Hover a bar for quick actions.</p>
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
        <button class="btn-press px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium transition-all active:scale-95" onclick="openBookingModalManual()">+ Add Booking</button>
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

         <div id="unitTypeFilters" class="space-y-2 p-4">
          <?php foreach($unitsByType as $type => $units): ?>
              <div class="unit-type-block border-b pb-2">
                      <!-- Unit type header + master checkbox -->
                      <div class="unit-type-header flex items-center justify-between">
                          <span class="font-semibold"><?= htmlspecialchars($type) ?></span>
                          <input type="checkbox" class="unit-type-master" data-unit="<?= htmlspecialchars($type) ?>">
                      </div>

                      <!-- Individual room checkboxes -->
                      <div class="room-list pl-4 mt-1 space-y-1">
                          <?php foreach($units as $unit): ?>
                              <label class="flex items-center gap-2 text-sm">
                                  <input type="checkbox"
                                        class="room-toggle"
                                        data-unit="<?= htmlspecialchars($type) ?>"
                                        data-room="<?= htmlspecialchars($unit['id']) ?>"
                                        checked>
                                  <?= htmlspecialchars($unit['number']) ?>
                                  <?php if (!$unit['open']): ?>
                                      <span class="text-red-500 text-xs">(Closed)</span>
                                  <?php endif; ?>
                              </label>
                          <?php endforeach; ?>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
        </div>

        <!-- Legend -->
        <div class="mb-5">
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
          </div>
        </div>

        <!-- Clear -->
        <button class="w-full btn-press px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-semibold transition-all active:scale-95" onclick="clearBookings()">
          Clear All Bookings
        </button>
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

<!-- ═══════════ BOOKING MODAL (Two-Step) ═══════════ -->
<div class="modal-backdrop" id="bookingModal">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="px-7 pt-6 pb-4 border-b border-slate-100 flex items-start justify-between">
      <div>
        <div class="modal-step-pill mb-2">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Step 2 — Confirm Booking
        </div>
        <h2 class="text-xl font-bold text-slate-900">New Booking</h2>
        <p class="text-slate-400 text-xs mt-0.5">Fill in guest details and end date.</p>
      </div>
      <button onclick="closeBookingModal()" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors mt-1">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="bookingForm" class="px-7 py-5 space-y-4">
      <!-- Pre-filled: Room context (read-only display) -->
      <div class="flex gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex-1">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Unit Type</p>
          <p class="text-sm font-bold text-slate-800" id="modal_unitTypeDisplay">—</p>
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Room</p>
          <p class="text-sm font-bold text-slate-800" id="modal_roomDisplay">—</p>
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Check-in</p>
          <p class="text-sm font-bold text-slate-800" id="modal_startDisplay">—</p>
        </div>
      </div>

      <input type="hidden" id="modal_unitType">
      <input type="hidden" id="modal_roomNumber">
      <input type="hidden" id="modal_startDate">

      <!-- Manual mode fields (shown when opened via "+ Add Booking") -->
      <div id="manualFields" class="hidden space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Unit Type</label>
          <select id="manual_unitType" class="zep-select w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
            <option value="">Select unit type…</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Room Number</label>
          <select id="manual_roomNumber" class="zep-select w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
            <option value="">Select room…</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Check-in Date</label>
          <input id="manual_startDate" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
      </div>

      <!-- Guest details -->
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Guest / Resident Name <span class="text-red-400">*</span></label>
        <input id="guestName" type="text" placeholder="e.g. Juan Dela Cruz" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Email</label>
          <input id="guestEmail" type="email" placeholder="guest@email.com" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Phone</label>
          <input id="guestPhone" type="tel" placeholder="+63 9xx xxx xxxx" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
      </div>

      <!-- End date + Status -->
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">End / Check-out Date <span class="text-red-400">*</span></label>
          <input id="endDate" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50" required>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Status <span class="text-red-400">*</span></label>
          <select id="status" class="zep-select w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
            <option value="Occupied">Occupied</option>
            <option value="Reserved">Reserved</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
      </div>
    </form>

    <div class="px-7 pb-6 flex items-center gap-3 justify-end border-t border-slate-100 pt-4">
      <button class="btn-press px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium transition-all active:scale-95" onclick="closeBookingModal()">Cancel</button>
      <button class="btn-press px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow transition-all active:scale-95" onclick="saveBooking()">Save Booking</button>
    </div>
  </div>
</div>

<!-- ═══════════ EDIT MODAL ═══════════ -->
<div class="modal-backdrop" id="editModal">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="px-7 pt-6 pb-4 border-b border-slate-100 flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Edit Booking</h2>
        <p class="text-slate-400 text-xs mt-0.5">Update the booking details below.</p>
      </div>
      <button onclick="closeEditModal()" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors mt-1">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="editForm" class="px-7 py-5 space-y-4">
      <input type="hidden" id="edit_bookingId">
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Guest Name <span class="text-red-400">*</span></label>
        <input id="edit_guestName" type="text" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Email</label>
          <input id="edit_email" type="email" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Phone</label>
          <input id="edit_phone" type="tel" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">Check-in</label>
          <input id="edit_startDate" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-700 block">End Date</label>
          <input id="edit_endDate" type="date" class="zep-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-700 block">Status</label>
        <select id="edit_status" class="zep-select w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50">
          <option value="Occupied">Occupied</option>
          <option value="Reserved">Reserved</option>
          <option value="Maintenance">Maintenance</option>
        </select>
      </div>
    </form>
    <div class="px-7 pb-6 flex items-center gap-3 justify-end border-t border-slate-100 pt-4">
      <button class="btn-press px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium transition-all active:scale-95" onclick="closeEditModal()">Cancel</button>
      <button class="btn-press px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow transition-all active:scale-95" onclick="saveEdit()">Save Changes</button>
    </div>
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
  <div class="qv-body">
    <div class="qv-row"><span class="qv-label">Email</span><span class="qv-value" id="qv_email">—</span></div>
    <div class="qv-row"><span class="qv-label">Phone</span><span class="qv-value" id="qv_phone">—</span></div>
    <div class="qv-row"><span class="qv-label">Check-in</span><span class="qv-value" id="qv_checkin">—</span></div>
    <div class="qv-row"><span class="qv-label">Check-out</span><span class="qv-value" id="qv_checkout">—</span></div>
  </div>
  <div class="qv-footer">
    <button class="qv-btn qv-btn-edit" onclick="editFromQuickView()">Edit</button>
    <button class="qv-btn qv-btn-delete" onclick="deleteFromQuickView()">Delete</button>
    <button class="qv-btn qv-btn-done" onclick="hideQuickView()">Done</button>
  </div>
</div>

<!-- Toast -->
<div id="rangeToast"></div>

<!-- ═══════════════════════ JAVASCRIPT ═══════════════════════ -->
<script>
  let bookings = <?php echo json_encode($bookings); ?>;
// ════════════════════════════════════════════
// CONFIGURATION — Unit Types & Rooms
// ════════════════════════════════════════════

// Status → bar CSS class
const STATUS_BAR = {
  "Occupied":    "bar-occupied",
  "Reserved":    "bar-reserved",
  "Maintenance": "bar-maintenance"
};

// Status → badge CSS class
const STATUS_BADGE = {
  "Occupied":    "badge-occupied",
  "Reserved":    "badge-reserved",
  "Maintenance": "badge-maintenance"
};

// ════════════════════════════════════════════
// DATA
// ════════════════════════════════════════════
function getDateOffset(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().split("T")[0];
}

let bookings = JSON.parse(localStorage.getItem("zepBookings") || "null") || [
  { id:1, guestName:"Maria Santos",  email:"maria@example.com",  phone:"09171234567", unitType:"Studio A",    roomNumber:"101", startDate:getDateOffset(0),  endDate:getDateOffset(4),  status:"Occupied"    },
  { id:2, guestName:"John Reyes",    email:"john@example.com",   phone:"09221234567", unitType:"One Bedroom", roomNumber:"301", startDate:getDateOffset(2),  endDate:getDateOffset(6),  status:"Reserved"    },
  { id:3, guestName:"Ana Cruz",      email:"ana@example.com",    phone:"09331234567", unitType:"Studio B",    roomNumber:"202", startDate:getDateOffset(-2), endDate:getDateOffset(3),  status:"Occupied"    },
  { id:4, guestName:"—",             email:"",                   phone:"",            unitType:"Two Bedroom", roomNumber:"401", startDate:getDateOffset(5),  endDate:getDateOffset(8),  status:"Maintenance" }
];

function saveToStorage() {
  localStorage.setItem("zepBookings", JSON.stringify(bookings));
}


// ════════════════════════════════════════════
// FILTER STATE — which rooms are visible
// ════════════════════════════════════════════
// visibleRooms: Set of "UnitType::Room" keys
let visibleRooms = new Set();



// Initialize the sidebar checkboxes (PHP already generated the HTML)
function buildFilterSidebar() {
    const container = document.getElementById("unitTypeFilters");
    
    // Individual room checkbox toggle (use event delegation)
    container.addEventListener("change", e => {
        if (!e.target.classList.contains("room-toggle")) return;
        
        const k = e.target.dataset.unit + "::" + e.target.dataset.room;
        e.target.checked ? visibleRooms.add(k) : visibleRooms.delete(k);

        // ✅ FIXED: Proper master checkbox sync
        const unitKey = e.target.dataset.unit;
        const masterCb = container.querySelector(`.unit-type-master[data-unit="${unitKey}"]`);
        if (masterCb) {
            const block = masterCb.closest(".unit-type-block");
            const allRoomCbs = block.querySelectorAll(".room-toggle");
            masterCb.checked = Array.from(allRoomCbs).every(cb => cb.checked);
        }

        updateSelectAll();
        renderTimeline();
    });

    // Unit type blocks setup
    container.querySelectorAll(".unit-type-block").forEach(block => {
        const header = block.querySelector(".unit-type-header");
        const roomList = block.querySelector(".room-list");
        const masterCb = block.querySelector(".unit-type-master");

        if (!roomList || !masterCb) return;

        // Initialize visibleRooms from checked state
        roomList.querySelectorAll(".room-toggle:checked").forEach(cb => {
            visibleRooms.add(cb.dataset.unit + "::" + cb.dataset.room);
        });

        // Master checkbox toggle
        masterCb.addEventListener("change", () => {
            roomList.querySelectorAll(".room-toggle").forEach(cb => {
                cb.checked = masterCb.checked;
                const k = cb.dataset.unit + "::" + cb.dataset.room;
                masterCb.checked ? visibleRooms.add(k) : visibleRooms.delete(k);
            });
            updateSelectAll();
            renderTimeline();
        });

        // Collapse toggle (excluding checkbox clicks)
        header.addEventListener("click", e => {
            if (e.target.type === "checkbox") return;
            const chevron = header.querySelector(".unit-type-chevron");
            const isOpen = roomList.classList.toggle("hidden"); // or "open"
            chevron.textContent = isOpen ? "▲" : "▼";
        });
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    buildFilterSidebar();
    populateManualSelects();
    // Initial render
    renderTimeline();
});
// ════════════════════════════════════════════
// TIMELINE RENDER
// ════════════════════════════════════════════
let currentDate = new Date();

function renderTimeline() {
  const year  = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const today = new Date().toISOString().split("T")[0];

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
    const visRooms = ut.rooms.filter(r => visibleRooms.has(ut.key + "::" + r));
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
    visRooms.forEach(room => {
      const tr = document.createElement("tr");
      tr.className = "room-row";

      // Room label cell
      const labelTd = document.createElement("td");
      labelTd.className = "room-label px-3 py-0 text-xs font-semibold text-slate-600";
      labelTd.style.height = "40px";
      labelTd.innerHTML = `<span class="block truncate">Room ${room}</span>`;
      tr.appendChild(labelTd);

      // Get bookings for this room
      const roomBookings = bookings.filter(b =>
        b.unitType === ut.key && b.roomNumber === room && b.status !== "Cancelled"
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

        // Click handler: Step 1 — set start date → open modal
        td.addEventListener("click", () => handleCellClick(ut.key, room, dateStr));

        // Booking bar?
        const booking = roomBookings.find(b => dateStr >= b.startDate && dateStr <= b.endDate);
        if (booking) {
          const isStart  = dateStr === booking.startDate;
          const isEnd    = dateStr === booking.endDate;
          const isSingle = isStart && isEnd;

          let posClass = isSingle ? "bar-single" : isStart ? "bar-start" : isEnd ? "bar-end" : "bar-middle";

          const bar = document.createElement("div");
          bar.className = `cell-bar ${posClass} ${STATUS_BAR[booking.status] || "bar-reserved"}`;
          bar.dataset.bookingId = booking.id;

          // Show label only on start/single
          if (isStart || isSingle) {
            bar.textContent = booking.guestName || "—";
          }

          // Hover → Quick View
          bar.addEventListener("mouseenter", e => showQuickView(booking, e));
          bar.addEventListener("mouseleave", startHideTimer);
          bar.addEventListener("click", e => {
            e.stopPropagation();
            showQuickView(booking, e, true);
          });

          td.appendChild(bar);
        }

        tr.appendChild(td);
      });

      tbody.appendChild(tr);
    });
  });
}

function formatDate(year, month, day) {
  const d = new Date(year, month, day);
  return d.toISOString().split("T")[0];
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
// TWO-STEP BOOKING FLOW
// ════════════════════════════════════════════
function handleCellClick(unitType, room, dateStr) {
  // Step 1: clicked a cell → pre-fill and open modal
  openBookingModal(unitType, room, dateStr);
}

function openBookingModal(unitType, room, startDate) {
  const modal = document.getElementById("bookingModal");
  const isManual = !unitType;

  document.getElementById("manualFields").classList.toggle("hidden", !isManual);
  document.querySelector("#bookingModal .modal-step-pill").style.display = isManual ? "none" : "";

  document.getElementById("modal_unitType").value  = unitType || "";
  document.getElementById("modal_roomNumber").value = room || "";
  document.getElementById("modal_startDate").value  = startDate || "";

  document.getElementById("modal_unitTypeDisplay").textContent = unitType || "—";
  document.getElementById("modal_roomDisplay").textContent = room ? "Room " + room : "—";
  document.getElementById("modal_startDisplay").textContent = startDate ? formatDisplayDate(startDate) : "—";

  if (startDate) {
    const next = new Date(startDate);
    next.setDate(next.getDate() + 1);
    document.getElementById("endDate").value = next.toISOString().split("T")[0];
  } else {
    document.getElementById("endDate").value = "";
  }

  document.getElementById("bookingForm").reset();
  // Re-apply hidden fields (reset clears them)
  document.getElementById("modal_unitType").value   = unitType || "";
  document.getElementById("modal_roomNumber").value = room || "";
  document.getElementById("modal_startDate").value  = startDate || "";
  document.getElementById("modal_unitTypeDisplay").textContent = unitType || "—";
  document.getElementById("modal_roomDisplay").textContent = room ? "Room " + room : "—";
  document.getElementById("modal_startDisplay").textContent = startDate ? formatDisplayDate(startDate) : "—";
  if (startDate) {
    const next = new Date(startDate);
    next.setDate(next.getDate() + 1);
    document.getElementById("endDate").value = next.toISOString().split("T")[0];
  }

  modal.classList.add("open");
}

function openBookingModalManual() {
  openBookingModal(null, null, null);
}

function closeBookingModal() {
  document.getElementById("bookingModal").classList.remove("open");
}
document.getElementById("bookingModal").addEventListener("click", e => {
  if (e.target === e.currentTarget) closeBookingModal();
});

function saveBooking() {
  const isManual = !document.getElementById("manualFields").classList.contains("hidden");

  const unitType  = isManual ? document.getElementById("manual_unitType").value  : document.getElementById("modal_unitType").value;
  const room      = isManual ? document.getElementById("manual_roomNumber").value : document.getElementById("modal_roomNumber").value;
  const startDate = isManual ? document.getElementById("manual_startDate").value  : document.getElementById("modal_startDate").value;
  const endDate   = document.getElementById("endDate").value;
  const guestName = document.getElementById("guestName").value.trim();
  const email     = document.getElementById("guestEmail").value.trim();
  const phone     = document.getElementById("guestPhone").value.trim();
  const status    = document.getElementById("status").value;

  if (!guestName || !startDate || !endDate || !unitType || !room) {
    alert("Please fill in all required fields.");
    return;
  }
  if (endDate < startDate) {
    alert("End date must be on or after the check-in date.");
    return;
  }

  const conflict = bookings.some(b =>
    b.unitType === unitType &&
    b.roomNumber === room &&
    b.status !== "Cancelled" &&
    startDate <= b.endDate &&
    endDate >= b.startDate
  );
  if (conflict) {
    alert("This room already has a booking for those dates. Please choose different dates.");
    return;
  }

  bookings.push({ id: Date.now(), guestName, email, phone, unitType, roomNumber: room, startDate, endDate, status });
  saveToStorage();
  closeBookingModal();
  renderTimeline();
  showToast("✅ Booking saved for Room " + room + "!");
}

// ════════════════════════════════════════════
// QUICK VIEW POPOVER
// ════════════════════════════════════════════
let qvBookingId  = null;
let _hideTimer   = null;

function showQuickView(booking, e, pin = false) {
  clearTimeout(_hideTimer);
  qvBookingId = booking.id;

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

  // Position: near cursor but keep in viewport
  const x = Math.min(e.clientX + 14, window.innerWidth  - 320);
  const y = Math.min(e.clientY + 10, window.innerHeight - 240);
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
  qvBookingId = null;
}

document.getElementById("quickView").addEventListener("mouseenter", () => clearTimeout(_hideTimer));
document.getElementById("quickView").addEventListener("mouseleave", startHideTimer);

function editFromQuickView() {
  if (!qvBookingId) return;
  const b = bookings.find(bk => bk.id === qvBookingId);
  if (!b) return;
  hideQuickView();
  openEditModal(b);
}

function deleteFromQuickView() {
  if (!qvBookingId) return;
  if (!confirm("Delete this booking? This cannot be undone.")) return;
  bookings = bookings.filter(b => b.id !== qvBookingId);
  saveToStorage();
  hideQuickView();
  renderTimeline();
  showToast("🗑 Booking deleted.");
}

// ════════════════════════════════════════════
// EDIT MODAL
// ════════════════════════════════════════════
function openEditModal(booking) {
  document.getElementById("edit_bookingId").value  = booking.id;
  document.getElementById("edit_guestName").value  = booking.guestName;
  document.getElementById("edit_email").value      = booking.email || "";
  document.getElementById("edit_phone").value      = booking.phone || "";
  document.getElementById("edit_startDate").value  = booking.startDate;
  document.getElementById("edit_endDate").value    = booking.endDate;
  document.getElementById("edit_status").value     = booking.status;
  document.getElementById("editModal").classList.add("open");
}

function closeEditModal() {
  document.getElementById("editModal").classList.remove("open");
}
document.getElementById("editModal").addEventListener("click", e => {
  if (e.target === e.currentTarget) closeEditModal();
});

function saveEdit() {
  const id        = parseInt(document.getElementById("edit_bookingId").value);
  const guestName = document.getElementById("edit_guestName").value.trim();
  const email     = document.getElementById("edit_email").value.trim();
  const phone     = document.getElementById("edit_phone").value.trim();
  const startDate = document.getElementById("edit_startDate").value;
  const endDate   = document.getElementById("edit_endDate").value;
  const status    = document.getElementById("edit_status").value;

  if (!guestName || !startDate || !endDate) {
    alert("Please fill in all required fields.");
    return;
  }
  if (endDate < startDate) {
    alert("End date must be on or after check-in.");
    return;
  }

  const idx = bookings.findIndex(b => b.id === id);
  if (idx === -1) return;
  bookings[idx] = { ...bookings[idx], guestName, email, phone, startDate, endDate, status };
  saveToStorage();
  closeEditModal();
  renderTimeline();
  showToast("✏️ Booking updated.");
}

// ════════════════════════════════════════════
// CLEAR ALL
// ════════════════════════════════════════════
function clearBookings() {
  if (!confirm("Clear ALL bookings? This cannot be undone.")) return;
  bookings = [];
  saveToStorage();
  renderTimeline();
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
function toggleNotice() {
  document.getElementById("noticePanel").classList.toggle("open");
  document.getElementById("noticeChevron").classList.toggle("rotated");
}
function toggleProfile() {
  document.getElementById("profileDropdown").classList.toggle("open");
  document.getElementById("profileChevron").style.transform =
    document.getElementById("profileDropdown").classList.contains("open") ? "rotate(180deg)" : "";
}
document.addEventListener("click", e => {
  if (!document.getElementById("profileWrapper").contains(e.target)) {
    document.getElementById("profileDropdown").classList.remove("open");
    document.getElementById("profileChevron").style.transform = "";
  }
});

// ════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════
buildFilterSidebar();
populateManualSelects();
renderTimeline();

// Scroll to today column on load
setTimeout(() => {
  const today = new Date().toISOString().split("T")[0];
  const todayTh = document.querySelector(`th[data-date="${today}"]`);
  if (todayTh) todayTh.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
}, 200);
</script>

</body>
</html>
<?php
/*
╔══════════════════════════════════════════════════════════════════════════════╗
║  SUGGESTED DATABASE SCHEMA CHANGES                                           ║
║  Run the following SQL to align your bookings table with this calendar.      ║
╚══════════════════════════════════════════════════════════════════════════════╝

-- Add new status ENUM to support the three calendar states:
ALTER TABLE bookings
  MODIFY COLUMN status ENUM('Occupied','Reserved','Maintenance','Cancelled')
    NOT NULL DEFAULT 'Reserved';

-- Add guest contact fields if not already present:
ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS email   VARCHAR(255) NULL AFTER guest_name,
  ADD COLUMN IF NOT EXISTS phone   VARCHAR(30)  NULL AFTER email;

-- Recommended index for fast timeline queries (filter by room + date range):
CREATE INDEX IF NOT EXISTS idx_bookings_room_dates
  ON bookings (unit_type, room_number, start_date, end_date);

-- Full suggested table structure:
CREATE TABLE IF NOT EXISTS bookings (
  id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  guest_name   VARCHAR(255)      NOT NULL,
  email        VARCHAR(255)      NULL,
  phone        VARCHAR(30)       NULL,
  unit_type    VARCHAR(100)      NOT NULL,   -- e.g. 'Studio A', 'One Bedroom'
  room_number  VARCHAR(20)       NOT NULL,   -- e.g. '101', '301'
  start_date   DATE              NOT NULL,
  end_date     DATE              NOT NULL,
  status       ENUM('Occupied','Reserved','Maintenance','Cancelled')
                                 NOT NULL DEFAULT 'Reserved',
  created_at   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_room_dates (unit_type, room_number, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PHP fetch snippet (replace localStorage with DB data like so):
-- <?php
--   $pdo = new PDO("mysql:host=localhost;dbname=zeppelin", $user, $pass);
--   $stmt = $pdo->query("SELECT * FROM bookings WHERE status != 'Cancelled'");
--   $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
--   echo "<script>let bookings = " . json_encode($bookings) . ";</script>";
-- ?>
*/
?>
