<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Resident Table Row Partial
 * Rendered during both initial page loads and AJAX live filter requests.
 */
$baseUrl = $baseUrl ?? rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
$userId = (int)($resident['user_id'] ?? 0);
$fullName = htmlspecialchars((string)($resident['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars((string)($resident['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$contact = htmlspecialchars((string)(!empty($resident['contact']) ? $resident['contact'] : '—'), ENT_QUOTES, 'UTF-8');
$rawRole = (string)($resident['user_role'] ?? 'tenant');
$role = htmlspecialchars($rawRole === 'unit owner' ? 'Unit Owner' : ucfirst($rawRole), ENT_QUOTES, 'UTF-8');
$createdAt = !empty($resident['created_at']) ? date('M d, Y', strtotime((string)$resident['created_at'])) : '—';
$status = trim((string)($resident['resident_status'] ?? 'Active'));
$viewResidentUrl = $baseUrl . '/admin/residents/view?id=' . $userId;
?>
<tr class="emp-row cursor-pointer hover:bg-slate-50/80 transition-colors" data-status="<?= htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8') ?>" onclick="window.location.href='<?= htmlspecialchars($viewResidentUrl) ?>'">
    <td class="px-5 py-3.5 font-semibold emp-name text-slate-800 whitespace-nowrap align-middle"><?= $fullName ?></td>
    <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap align-middle"><?= $email ?></td>
    <td class="px-4 py-3.5 text-center text-slate-600 text-xs whitespace-nowrap align-middle" style="font-family:'DM Mono',monospace"><?= $contact ?></td>
    <td class="px-4 py-3.5 text-center text-slate-600 text-xs font-medium whitespace-nowrap align-middle"><?= $role ?></td>
    <td class="px-4 py-3.5 text-center text-slate-500 text-xs whitespace-nowrap align-middle" style="font-family:'DM Mono',monospace"><?= $createdAt ?></td>
    <td class="px-4 py-3.5 text-center align-middle whitespace-nowrap">
        <?php if ($status === 'Active'): ?>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full inline-flex items-center bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
        <?php else: ?>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full inline-flex items-center bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3.5 text-center align-middle whitespace-nowrap">
        <a href="<?= htmlspecialchars($viewResidentUrl) ?>"
           class="btn-press text-xs font-semibold text-slate-500 border border-slate-200 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-full active:scale-95 transition-all inline-block"
           onclick="event.stopPropagation()">View</a>
    </td>
</tr>
