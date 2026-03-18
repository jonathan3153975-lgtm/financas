<?php declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database - Singleton PDO wrapper
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host    = $_ENV['DB_HOST']     ?? 'localhost';
        $port    = $_ENV['DB_PORT']     ?? '3306';
        $dbname  = $_ENV['DB_DATABASE'] ?? 'financas';
        $user    = $_ENV['DB_USERNAME'] ?? 'root';
        $pass    = $_ENV['DB_PASSWORD'] ?? '';
        $charset = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $debug = $_ENV['APP_DEBUG'] ?? 'false';
            if ($debug === 'true') {
                throw $e;
            }
            throw new \RuntimeException('Falha na conexão com o banco de dados.');
        }
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /** Prevent cloning */
    private function __clone() {}

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prepare and execute a statement, returning the PDOStatement.
     *
     * @param string $sql
     * @param array<mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return array<string,mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows.
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute a statement (INSERT / UPDATE / DELETE).
     *
     * @param string $sql
     * @param array<mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
