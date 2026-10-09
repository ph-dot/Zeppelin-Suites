<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Database Backup & Restore Model
 * Pure MVC database interaction model for creating SQL dumps and restoring schemas.
 */
class Backup extends Model {

    /**
     * Get summary statistics of the current database.
     */
    public function getDatabaseStats(): array {
        $dbName = (string)env('DB_NAME', 'zepellin_test');

        $tablesStmt = $this->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);
        $tableNames = array_map(fn($row) => $row[0], $tables);

        $totalRows = 0;
        foreach ($tableNames as $tbl) {
            $countStmt = $this->query("SELECT COUNT(*) AS total FROM `{$tbl}`");
            $countRow = $countStmt->fetch();
            $totalRows += (int)($countRow['total'] ?? 0);
        }

        $sizeSql = "SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                    FROM information_schema.TABLES 
                    WHERE table_schema = :db_name";
        $sizeRow = $this->fetchOne($sizeSql, ['db_name' => $dbName]);
        $sizeMb = (float)($sizeRow['size_mb'] ?? 0.0);

        return [
            'database_name' => $dbName,
            'table_count'   => count($tableNames),
            'total_rows'    => $totalRows,
            'size_mb'       => $sizeMb,
            'tables'        => $tableNames,
        ];
    }

    /**
     * Generate a complete, self-contained SQL dump string.
     */
    public function generateBackupSql(): string {
        $dbName = (string)env('DB_NAME', 'zepellin_test');
        $dateStr = date('Y-m-d H:i:s');

        $out = [];
        $out[] = "-- =========================================================";
        $out[] = "-- Zeppelin Suites Database Backup";
        $out[] = "-- Generated on: {$dateStr}";
        $out[] = "-- Database: `{$dbName}`";
        $out[] = "-- =========================================================";
        $out[] = "";
        $out[] = "SET FOREIGN_KEY_CHECKS=0;";
        $out[] = "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";";
        $out[] = "SET time_zone = \"+00:00\";";
        $out[] = "SET NAMES utf8mb4;";
        $out[] = "";

        $tablesStmt = $this->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

        foreach ($tables as $tRow) {
            $tableName = $tRow[0];

            $out[] = "-- ---------------------------------------------------------";
            $out[] = "-- Table structure for `{$tableName}`";
            $out[] = "-- ---------------------------------------------------------";
            $out[] = "DROP TABLE IF EXISTS `{$tableName}`;";

            $createStmt = $this->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            if (!empty($createRow[1])) {
                $out[] = $createRow[1] . ";";
            }
            $out[] = "";

            // Table Data
            $rowsStmt = $this->query("SELECT * FROM `{$tableName}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $out[] = "-- Dumping data for table `{$tableName}`";

                $columns = array_keys($rows[0]);
                $colNamesEscaped = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

                $chunks = array_chunk($rows, 100);
                foreach ($chunks as $chunk) {
                    $valueLines = [];
                    foreach ($chunk as $row) {
                        $escapedValues = [];
                        foreach ($columns as $col) {
                            $val = $row[$col];
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $escapedValues[] = (string)$val;
                            } else {
                                $escapedValues[] = $this->db->quote((string)$val);
                            }
                        }
                        $valueLines[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    $out[] = "INSERT INTO `{$tableName}` ({$colNamesEscaped}) VALUES\n" . implode(",\n", $valueLines) . ";";
                }
                $out[] = "";
            }
        }

        $out[] = "SET FOREIGN_KEY_CHECKS=1;";
        $out[] = "-- Backup complete.";

        return implode("\n", $out);
    }

    /**
     * Restore database from a raw SQL backup string.
     */
    public function restoreFromSql(string $sqlContent): array {
        $trimmed = trim($sqlContent);
        if ($trimmed === '') {
            return [
                'success' => false,
                'message' => 'Uploaded SQL file is empty.',
            ];
        }

        try {
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // Execute the imported SQL script
            $this->db->exec($trimmed);
            
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $stats = $this->getDatabaseStats();

            return [
                'success'        => true,
                'message'        => 'Database successfully restored! Total tables: ' . $stats['table_count'] . ', Total rows: ' . $stats['total_rows'],
                'database_stats' => $stats,
            ];
        } catch (PDOException $e) {
            try {
                $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (Exception $ignored) {}

            return [
                'success' => false,
                'message' => 'Database restore failed: ' . $e->getMessage(),
            ];
        }
    }
}
