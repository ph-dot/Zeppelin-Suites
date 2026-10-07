<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Pending Actions Partial
 * Reused for initial home overview rendering and live 20s auto-refresh polling.
 */
$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$totalPendingActions = (int)($homeStats['total_pending'] ?? 0);
$pendingInquiryCount = (int)($homeStats['pending_inquiries'] ?? 0);
$pendingReservationCount = (int)($homeStats['pending_reservations'] ?? 0);
$pendingMaintenanceCount = (int)($homeStats['pending_maintenance'] ?? 0);
?>
<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 border-b border-slate-100">
    <div>
      <h2 class="font-bold text-slate-900">Pending Admin Actions</h2>
      <p class="text-xs text-slate-400 mt-0.5">Items that still need admin review or follow-up.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="bg-slate-900 text-white text-xs font-semibold px-4 py-2 rounded-full">
        <?= htmlspecialchars((string)$totalPendingActions, ENT_QUOTES, 'UTF-8') ?> total
      </span>
      <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries" class="btn-press bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-xs font-semibold px-4 py-2 rounded-full transition-all">
        View Inquiries
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/40">
    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/inquiries" class="bg-white border border-slate-100 rounded-xl px-4 py-3 hover:border-slate-300 transition-colors block">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Pending Inquiries</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?= htmlspecialchars((string)$pendingInquiryCount, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/reservations" class="bg-white border border-slate-100 rounded-xl px-4 py-3 hover:border-slate-300 transition-colors block">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Reservations</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?= htmlspecialchars((string)$pendingReservationCount, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/maintenance" class="bg-white border border-slate-100 rounded-xl px-4 py-3 hover:border-slate-300 transition-colors block">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Maintenance</p>
      <p class="text-xl font-bold text-slate-900 mt-1" style="font-family:'DM Mono',monospace;"><?= htmlspecialchars((string)$pendingMaintenanceCount, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
  </div>
</div>
