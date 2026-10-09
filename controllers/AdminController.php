<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/Backup.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Zeppelin Suites - Admin Controller
 * Handles administrative overview, property analytics, and dashboard actions.
 * Enforces admin authorization. No raw SQL or presentation markup.
 */
class AdminController extends Controller {
    private Analytics $analyticsModel;
    private Reservation $reservationModel;

    public function __construct() {
        $this->analyticsModel = new Analytics();
        $this->reservationModel = new Reservation();
    }

    /**
     * Property Performance & Portfolio Analytics Dashboard.
     */
    public function analytics(): void {
        $userSession = Middleware::requireRole(['admin']);

        // Keep unit statuses synchronized with lease expirations
        $this->reservationModel->syncExpiredUnitStatuses();

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $unitTypeOptions = [
            'all'     => 'Entire Portfolio',
            'studioA' => 'Studio Type A',
            'studioB' => 'Studio Type B',
            'one'     => 'One Bedroom',
            'two'     => 'Two Bedroom',
        ];

        // Parse and validate query filters
        $monthInput = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
        $selectedMonthIndex = ($monthInput !== null && $monthInput !== false && $monthInput >= 0 && $monthInput <= 11)
            ? $monthInput
            : ((int)date('n') - 1);

        $yearInput = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        $selectedYear = ($yearInput !== null && $yearInput !== false && $yearInput >= 2020 && $yearInput <= 2100)
            ? $yearInput
            : (int)date('Y');

        $selectedUnitKey = (string)$this->getQuery('unit', 'all');
        if (!array_key_exists($selectedUnitKey, $unitTypeOptions)) {
            $selectedUnitKey = 'all';
        }

        $selectedUnitType = $selectedUnitKey === 'all' ? null : $unitTypeOptions[$selectedUnitKey];
        $selectedMonthName = $months[$selectedMonthIndex];
        $selectedUnitLabel = $unitTypeOptions[$selectedUnitKey];

        // Date calculation for current and preceding period
        $startObj = new DateTime(sprintf('%04d-%02d-01 00:00:00', $selectedYear, $selectedMonthIndex + 1));
        $endObj = (clone $startObj)->modify('+1 month');
        $prevStartObj = (clone $startObj)->modify('-1 month');
        $prevEndObj = clone $startObj;

        $start = $startObj->format('Y-m-d H:i:s');
        $end = $endObj->format('Y-m-d H:i:s');
        $prevStart = $prevStartObj->format('Y-m-d H:i:s');
        $prevEnd = $prevEndObj->format('Y-m-d H:i:s');
        $daysInMonth = (int)$startObj->format('t');
        $dayLabels = range(1, $daysInMonth);
        $yearOptions = range(max(2023, $selectedYear - 2), max((int)date('Y') + 1, $selectedYear + 1));

        // Pending counts for sidebar badges
        $pendingCounts = $this->analyticsModel->getPendingCounts();

        // Occupancy KPI
        $occStats = $this->analyticsModel->getOccupancyStats($selectedUnitType);
        $totalUnits = $occStats['total_units'];
        $activeUnits = $occStats['active_units'];
        $occupancyRate = $occStats['occupancy_rate'];

        // Funnel & Conversion
        $totalInquiries = $this->analyticsModel->countInquiries($start, $end, $selectedUnitType);
        $hoaChecked = $this->analyticsModel->countHoaChecked($start, $end, $selectedUnitType);
        $ownerApproved = $this->analyticsModel->countOwnerApproved($start, $end, $selectedUnitType);
        $webformSubmitted = $this->reservationModel->countReservations($start, $end, $selectedUnitType);
        $officiallyBooked = $this->reservationModel->countReservations($start, $end, $selectedUnitType, "AND r.reservation_status = 'reserved'");

        $conversionRate = $totalInquiries > 0 ? round(($officiallyBooked / $totalInquiries) * 100, 1) : 0.0;
        $prevTotalInquiries = $this->analyticsModel->countInquiries($prevStart, $prevEnd, $selectedUnitType);
        $prevOfficiallyBooked = $this->reservationModel->countReservations($prevStart, $prevEnd, $selectedUnitType, "AND r.reservation_status = 'reserved'");
        $prevConversionRate = $prevTotalInquiries > 0 ? round(($prevOfficiallyBooked / $prevTotalInquiries) * 100, 1) : null;

        if ($prevConversionRate === null) {
            $conversionTrendText = 'No previous data';
            $conversionTrendClass = 'trend-neu';
        } else {
            $diff = round($conversionRate - $prevConversionRate, 1);
            if ($diff > 0) {
                $conversionTrendText = '↑ ' . number_format(abs($diff), 1) . '%';
                $conversionTrendClass = 'trend-up';
            } elseif ($diff < 0) {
                $conversionTrendText = '↓ ' . number_format(abs($diff), 1) . '%';
                $conversionTrendClass = 'trend-down';
            } else {
                $conversionTrendText = '— 0.0%';
                $conversionTrendClass = 'trend-neu';
            }
        }

        // Donut: active vs cancelled
        $cancelledReservations = $this->reservationModel->countReservations($start, $end, $selectedUnitType, "AND r.reservation_status = 'cancelled'");
        $activeReservations = $this->reservationModel->countReservations($start, $end, $selectedUnitType, "AND r.reservation_status NOT IN ('cancelled', 'rejected')");

        // Bar: demand by category
        $categoryLabels = ['Studio Type A', 'Studio Type B', 'One Bedroom', 'Two Bedroom'];
        $barInquiries = [];
        $barConfirmed = [];
        foreach ($categoryLabels as $label) {
            if ($selectedUnitType !== null && $selectedUnitType !== $label) {
                $barInquiries[] = 0;
                $barConfirmed[] = 0;
                continue;
            }
            $barInquiries[] = $this->analyticsModel->countInquiries($start, $end, $label);
            $barConfirmed[] = $this->reservationModel->countReservations($start, $end, $label, "AND r.reservation_status NOT IN ('cancelled', 'rejected')");
        }
        $barInquiries[] = $totalInquiries;
        $barConfirmed[] = $activeReservations;

        // Line: Daily maintenance
        $maintenanceRequests = $this->analyticsModel->getDailyMaintenance($start, $end, $daysInMonth, $selectedUnitType, false);
        $maintenanceCompleted = $this->analyticsModel->getDailyMaintenance($start, $end, $daysInMonth, $selectedUnitType, true);

        // Room maintenance trends
        $roomMaintenanceRows = $this->analyticsModel->getRoomMaintenanceTrends($start, $end, $selectedUnitType);
        $roomsWithMaintenance = count($roomMaintenanceRows);
        $openRoomMaintenance = array_sum(array_column($roomMaintenanceRows, 'open'));
        $urgentRoomMaintenance = array_sum(array_column($roomMaintenanceRows, 'urgent'));
        $roomMaintenanceLabels = array_column($roomMaintenanceRows, 'unit');
        $roomMaintenanceTotals = array_column($roomMaintenanceRows, 'total');
        $roomMaintenanceOpen = array_column($roomMaintenanceRows, 'open');

        // Sales Analytics
        $salesSummary = $this->reservationModel->getSalesSummary($start, $end, $selectedUnitType);
        $prevSalesSummary = $this->reservationModel->getSalesSummary($prevStart, $prevEnd, $selectedUnitType);
        $totalSales = (float)($salesSummary['collected_sales'] ?? 0);
        $prevTotalSales = (float)($prevSalesSummary['collected_sales'] ?? 0);
        $totalContractValue = (float)($salesSummary['contract_value'] ?? 0);
        $paidReservationCount = (int)($salesSummary['paid_reservations'] ?? 0);
        $salesCollectionRate = $totalContractValue > 0 ? round(($totalSales / $totalContractValue) * 100, 1) : 0.0;
        $salesDaily = $this->reservationModel->getDailySales($start, $end, $daysInMonth, $selectedUnitType);

        // Sales trend calculation
        if ($prevTotalSales <= 0) {
            $salesTrend = $totalSales > 0
                ? ['text' => 'New this month', 'class' => 'trend-up']
                : ['text' => 'No previous data', 'class' => 'trend-neu'];
        } else {
            $diff = round((($totalSales - $prevTotalSales) / $prevTotalSales) * 100, 1);
            if ($diff > 0) {
                $salesTrend = ['text' => '↑ ' . number_format(abs($diff), 1) . '%', 'class' => 'trend-up'];
            } elseif ($diff < 0) {
                $salesTrend = ['text' => '↓ ' . number_format(abs($diff), 1) . '%', 'class' => 'trend-down'];
            } else {
                $salesTrend = ['text' => '— 0.0%', 'class' => 'trend-neu'];
            }
        }

        // Top units
        $topUnitsRaw = $this->reservationModel->getTopRevenueUnits($start, $end, $selectedUnitType, 8);
        $topUnits = [];
        $rank = 1;
        foreach ($topUnitsRaw as $row) {
            $status = (string)($row['unit_current_status'] ?? '');
            $statusLower = strtolower($status);
            $statusClass = 'bg-slate-50 text-slate-500 border-slate-100';
            if (in_array($statusLower, ['occupied', 'reserved'], true)) {
                $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
            } elseif (in_array($statusLower, ['on hold', 'under maintenance'], true)) {
                $statusClass = 'bg-amber-50 text-amber-700 border-amber-100';
            } elseif ($statusLower === 'resale') {
                $statusClass = 'bg-blue-50 text-blue-700 border-blue-100';
            }

            $revenue = (float)($row['revenue'] ?? 0);
            $revenueFormatted = $revenue >= 1000000
                ? '₱' . number_format($revenue / 1000000, 1) . 'M'
                : ($revenue >= 1000 ? '₱' . number_format($revenue / 1000, 0) . 'k' : '₱' . number_format($revenue, 0));

            $topUnits[] = [
                'rank'        => $rank++,
                'unit'        => $row['unit_number'] ?? '',
                'type'        => $row['unit_type'] ?? '',
                'owner'       => $row['owner_name'] ?? 'No owner assigned',
                'revenue'     => $revenueFormatted,
                'occ'         => in_array($status, ['Occupied', 'Reserved'], true) ? '100%' : '0%',
                'status'      => $status,
                'statusClass' => $statusClass,
            ];
        }

        $analyticsData = [
            'kpi' => [
                'occupancy'               => number_format($occupancyRate, 1) . '%',
                'occBar'                  => $occupancyRate,
                'occActive'               => $activeUnits,
                'occTotal'                => $totalUnits,
                'occText'                 => $activeUnits . ' / ' . $totalUnits . ' units occupied/reserved',
                'conversion'              => number_format($conversionRate, 1) . '%',
                'convBar'                 => $conversionRate,
                'convTotal'               => $totalInquiries,
                'convConfirmed'           => $officiallyBooked,
                'convText'                => $totalInquiries . ' inquiries → ' . $officiallyBooked . ' officially booked',
                'conversionTrendText'     => $conversionTrendText,
                'conversionTrendClass'    => $conversionTrendClass,
                'sales'                   => $totalSales >= 1000000 ? '₱' . number_format($totalSales / 1000000, 1) . 'M' : ($totalSales >= 1000 ? '₱' . number_format($totalSales / 1000, 0) . 'k' : '₱' . number_format($totalSales, 0)),
                'salesFull'               => '₱' . number_format($totalSales, 2),
                'salesTrendText'          => $salesTrend['text'],
                'salesTrendClass'         => $salesTrend['class'],
                'salesBar'                => min(100, $salesCollectionRate),
                'salesText'               => $paidReservationCount . ' verified payment' . ($paidReservationCount === 1 ? '' : 's') . ' · ' . ($totalContractValue >= 1000000 ? '₱' . number_format($totalContractValue / 1000000, 1) . 'M' : '₱' . number_format($totalContractValue, 0)) . ' contract value',
                'roomsMaintenance'        => (string)$roomsWithMaintenance,
                'roomsMaintenanceBar'     => min(100, $totalUnits > 0 ? round(($roomsWithMaintenance / $totalUnits) * 100, 1) : 0),
                'roomsMaintenanceText'    => $openRoomMaintenance . ' open request' . ($openRoomMaintenance === 1 ? '' : 's') . ' · ' . $urgentRoomMaintenance . ' urgent',
            ],
            'funnel'          => [$totalInquiries, $hoaChecked, $ownerApproved, $webformSubmitted, $officiallyBooked],
            'donut'           => [$activeReservations, $cancelledReservations],
            'bar'             => [
                'labels'    => ['Studio Type A', 'Studio Type B', 'One Bedroom', 'Two Bedroom', 'Entire Portfolio'],
                'inquiries' => $barInquiries,
                'confirmed' => $barConfirmed,
            ],
            'line'            => [
                'labels'    => $dayLabels,
                'requests'  => $maintenanceRequests,
                'completed' => $maintenanceCompleted,
            ],
            'sales'           => [
                'labels'    => $dayLabels,
                'collected' => $salesDaily,
                'leasing'   => (float)($salesSummary['leasing_sales'] ?? 0),
                'resale'    => (float)($salesSummary['resale_sales'] ?? 0),
            ],
            'roomMaintenance' => [
                'labels' => $roomMaintenanceLabels,
                'total'  => $roomMaintenanceTotals,
                'open'   => $roomMaintenanceOpen,
                'rows'   => $roomMaintenanceRows,
            ],
        ];

        $selectedMeta = [
            'monthIndex'  => $selectedMonthIndex,
            'monthName'   => $selectedMonthName,
            'year'        => $selectedYear,
            'unitKey'     => $selectedUnitKey,
            'unitLabel'   => $selectedUnitLabel,
            'generatedAt' => date('M j, Y g:i A'),
        ];

        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('admin/analytics', [
            'pageTitle'           => 'Zeppelin Suites - Analytics',
            'activeTab'           => 'analytics',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'months'              => $months,
            'yearOptions'         => $yearOptions,
            'unitTypeOptions'     => $unitTypeOptions,
            'selectedMonthIndex'  => $selectedMonthIndex,
            'selectedYear'        => $selectedYear,
            'selectedUnitKey'     => $selectedUnitKey,
            'selectedMonthName'   => $selectedMonthName,
            'selectedUnitLabel'   => $selectedUnitLabel,
            'selectedMeta'        => $selectedMeta,
            'analyticsData'       => $analyticsData,
            'topUnits'            => $topUnits,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * Admin home overview screen.
     */
     public function home(): void {
        $userSession = Middleware::requireRole(['admin']);
        $homeStats = $this->analyticsModel->getHomeStats();
        $pendingCounts = $this->analyticsModel->getPendingCounts();
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('admin/home', [
            'pageTitle'           => 'Zeppelin Suites - Admin Home',
            'activeTab'           => 'home',
            'adminName'           => $userSession['full_name'],
            'adminInitial'        => $userSession['initial'],
            'baseUrl'             => $baseUrl,
            'homeStats'           => $homeStats,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
        ]);
    }

    /**
     * Partial HTML endpoint for 20s polling on admin home overview.
     */
    public function pendingActions(): void {
        Middleware::requireRole(['admin']);
        $homeStats = $this->analyticsModel->getHomeStats();
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('admin/pending_actions', [
            'homeStats' => $homeStats,
            'baseUrl'   => $baseUrl,
        ]);
    }

    /**
     * Administrator profile and credentials view.
     */
    public function account(): void {
        $userSession = Middleware::requireRole(['admin']);
        $userModel = new User();
        $admin = $userModel->findById((int)$userSession['user_id']);

        if (!$admin) {
            $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');
            $this->redirect("{$baseUrl}/admin/home");
            return;
        }

        $dobFormatted = '—';
        if (!empty($admin['date_of_birth'])) {
            try {
                $dobDate = new DateTime($admin['date_of_birth']);
                $today = new DateTime();
                $age = $today->diff($dobDate)->y;
                $dobFormatted = $dobDate->format('M d, Y') . " ($age yrs old)";
            } catch (Exception $ex) {
                $dobFormatted = (string)$admin['date_of_birth'];
            }
        }

        $additionalPhone = !empty($admin['additional_contact']) ? $admin['additional_contact'] : '—';
        $additionalEmail = !empty($admin['additional_email']) ? $admin['additional_email'] : '—';
        $pendingCounts = $this->analyticsModel->getPendingCounts();
        $backupModel = new Backup();
        $dbStats = $backupModel->getDatabaseStats();
        $baseUrl = rtrim((string)env('APP_URL', '/Zeppelin-Suites'), '/');

        $this->render('admin/account', [
            'pageTitle'           => 'Zeppelin Suites - Admin Account',
            'activeTab'           => 'account',
            'admin'               => $admin,
            'adminName'           => $admin['full_name'] ?? $userSession['full_name'],
            'adminInitial'        => strtoupper(substr((string)($admin['full_name'] ?? 'A'), 0, 1)),
            'baseUrl'             => $baseUrl,
            'dobFormatted'        => $dobFormatted,
            'additionalPhone'     => $additionalPhone,
            'additionalEmail'     => $additionalEmail,
            'pendingInquiries'    => $pendingCounts['pending_inquiries'],
            'pendingReservations' => $pendingCounts['pending_reservations'],
            'dbStats'             => $dbStats,
        ]);
    }

    /**
     * Generate and download a full SQL database backup.
     */
    public function backupDownload(): void {
        Middleware::requireRole(['admin']);
        $backupModel = new Backup();
        $sql = $backupModel->generateBackupSql();

        $filename = 'zeppelin_suites_backup_' . date('Y-m-d_His') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $sql;
        exit;
    }

    /**
     * Restore database from an uploaded SQL backup file.
     */
    public function restoreDatabase(): void {
        Middleware::requireRole(['admin']);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        if (empty($_FILES['backup_file']['tmp_name']) || !is_uploaded_file($_FILES['backup_file']['tmp_name'])) {
            $this->json(['success' => false, 'message' => 'Please select a valid .sql backup file to upload.'], 400);
            return;
        }

        $file = $_FILES['backup_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $this->json(['success' => false, 'message' => 'Only .sql backup files are allowed.'], 400);
            return;
        }

        $sqlContent = file_get_contents($file['tmp_name']);
        if ($sqlContent === false || trim($sqlContent) === '') {
            $this->json(['success' => false, 'message' => 'Unable to read the uploaded backup file or the file is empty.'], 400);
            return;
        }

        $backupModel = new Backup();
        $result = $backupModel->restoreFromSql($sqlContent);

        $this->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Admin dashboard entrypoint.
     */
    public function dashboard(): void {
        $this->home();
    }
}
