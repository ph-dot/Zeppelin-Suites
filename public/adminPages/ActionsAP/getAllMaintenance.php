<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

$sql = "
    SELECT 
        m.maintenance_id,
        m.unit_id,
        m.subject,
        m.category,
        m.description,
        m.priority,
        m.photo_paths,
        m.status,
        m.admin_remarks,
        m.submitted_at,
        m.updated_at,
        m.resolved_at,
        u.unit_number,
        u.unit_type,
        owner.full_name AS owner_name,
        owner.email AS owner_email
    FROM maintenance_requests m
    LEFT JOIN units_table u ON m.unit_id = u.unit_id
    LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
    ORDER BY m.submitted_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo '
    <tr>
        <td colspan="8" class="px-5 py-6 text-center text-red-500">
            Error loading maintenance requests.
        </td>
    </tr>';
    return;
}

if ($result->num_rows === 0) {
    echo '
    <tr>
        <td colspan="8" class="px-5 py-6 text-center text-slate-400">
            No maintenance requests found.
        </td>
    </tr>';
    return;
}

while ($row = $result->fetch_assoc()) {

    $maintenanceId = (int) $row['maintenance_id'];
    $mrNumber = 'MR-' . str_pad($maintenanceId, 4, '0', STR_PAD_LEFT);

    $unitNumber = $row['unit_number'] ?? '-';
    $unitType = $row['unit_type'] ?? '';
    $unitDisplay = trim($unitNumber . ' ' . $unitType);

    $ownerName = $row['owner_name'] ?? '-';
    $ownerEmail = $row['owner_email'] ?? '-';

    $subject = $row['subject'] ?? '-';
    $category = $row['category'] ?? '-';
    $description = $row['description'] ?? '-';
    $priority = $row['priority'] ?? 'normal';
    $status = $row['status'] ?? 'pending';
    $remarks = $row['admin_remarks'] ?? '';
    $submittedAt = $row['submitted_at'] ?? '-';
    $resolvedAt = !empty($row['resolved_at']) ? $row['resolved_at'] : 'Not yet resolved';

    $photoPaths = [];

    if (!empty($row['photo_paths'])) {
        $savedPhotos = explode(',', $row['photo_paths']);

        foreach ($savedPhotos as $photo) {
            $photo = str_replace('\\/', '/', $photo);
            $photo = trim($photo, " \t\n\r\0\x0B[]\"'");

            if (strpos($photo, 'uploads/maintenance/') === 0) {
                $photoPaths[] = '../' . ltrim($photo, '/');
            }
        }
    }

    $photoData = htmlspecialchars(implode('|', $photoPaths), ENT_QUOTES, 'UTF-8');

    $statusClass = 'bg-slate-100 text-slate-600';

    if ($status === 'pending') {
        $statusClass = 'bg-yellow-100 text-yellow-700';
    } elseif ($status === 'in progress') {
        $statusClass = 'bg-blue-100 text-blue-700';
    } elseif ($status === 'resolved') {
        $statusClass = 'bg-green-100 text-green-700';
    } elseif ($status === 'cancelled') {
        $statusClass = 'bg-red-100 text-red-700';
    }
    ?>

    <tr 
        class="room-row cursor-pointer"
        onclick="openMaintenanceModal(this)"
        data-maintenance-id="<?= $maintenanceId ?>"
        data-mr="<?= htmlspecialchars($mrNumber, ENT_QUOTES, 'UTF-8') ?>"
        data-unit="<?= htmlspecialchars($unitDisplay, ENT_QUOTES, 'UTF-8') ?>"
        data-owner-name="<?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?>"
        data-owner-email="<?= htmlspecialchars($ownerEmail, ENT_QUOTES, 'UTF-8') ?>"
        data-subject="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>"
        data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
        data-priority="<?= htmlspecialchars($priority, ENT_QUOTES, 'UTF-8') ?>"
        data-description="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>"
        data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
        data-admin-remarks="<?= htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8') ?>"
        data-submitted-at="<?= htmlspecialchars($submittedAt, ENT_QUOTES, 'UTF-8') ?>"
        data-resolved-at="<?= htmlspecialchars($resolvedAt, ENT_QUOTES, 'UTF-8') ?>"
        data-photos="<?= $photoData ?>"
    >
        <td class="px-5 py-4 font-semibold text-slate-800 whitespace-nowrap">
            <?= htmlspecialchars($mrNumber) ?>
        </td>

        <td class="px-4 py-4 text-slate-600 whitespace-nowrap">
            <?= htmlspecialchars($unitNumber) ?>
        </td>

        <td class="px-4 py-4 text-slate-600 whitespace-nowrap">
            <?= htmlspecialchars($ownerName) ?>
        </td>

        <td class="px-4 py-4">
            <div class="font-semibold text-slate-800">
                <?= htmlspecialchars($subject) ?>
            </div>
            <div class="text-xs text-slate-400">
                <?= htmlspecialchars($category) ?> / <?= htmlspecialchars(ucfirst($priority)) ?>
            </div>
        </td>

        <td class="px-4 py-4 text-slate-500 whitespace-nowrap">
            <?= htmlspecialchars($submittedAt) ?>
        </td>

        <td class="px-4 py-4 whitespace-nowrap">
            <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                <?= htmlspecialchars(ucfirst($status)) ?>
            </span>
        </td>

        <td class="px-4 py-4 whitespace-nowrap">
            <?php if (count($photoPaths) > 0): ?>
                <span class="text-xs font-semibold text-blue-600">
                    <?= count($photoPaths) ?> photo<?= count($photoPaths) > 1 ? 's' : '' ?>
                </span>
            <?php else: ?>
                <span class="text-xs text-slate-400">No photo</span>
            <?php endif; ?>
        </td>

        <td class="px-4 py-4 text-right">
            <button 
                type="button"
                onclick="event.stopPropagation(); openMaintenanceModal(this.closest('tr'))"
                class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-700"
            >
                View
            </button>
        </td>
    </tr>

    <?php
}
?>
