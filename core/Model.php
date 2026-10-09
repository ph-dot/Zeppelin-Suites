<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Zeppelin Suites - Base Model
 * All models inherit database access and prepared statement helpers from this class.
 * NO HTML or presentation output is permitted in models.
 */
abstract class Model {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Prepare and execute a parameterized SQL query.
     *
     * @param string $sql Parameterized SQL string with ? or :named placeholders
     * @param array $params Array of values to bind
     * @return PDOStatement
     */
    protected function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row as an associative array.
     */
    protected function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all matching rows as an array of associative arrays.
     */
    protected function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query.
     */
    protected function execute(string $sql, array $params = []): bool {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get the last inserted ID.
     */
    protected function lastInsertId(): string {
        return $this->db->lastInsertId();
    }

    /**
     * Transaction management helpers.
     */
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollBack(): bool {
        return $this->db->rollBack();
    }
}
