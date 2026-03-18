<?php declare(strict_types=1);

namespace App\Core;

/**
 * Base Model
 */
abstract class Model
{
    protected Database $db;
    protected string $table  = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAll(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findBy(string $column, mixed $value): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ?",
            [$value]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findOneBy(string $column, mixed $value): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }

    /**
     * Insert a new record and return its ID.
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->db->execute(
            "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update a record by primary key.
     *
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): int
    {
        $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));

        return $this->db->execute(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?",
            [...array_values($data), $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $this->db->fetch($sql, $params);
        return (int) array_values($result ?? [0])[0];
    }

    /**
     * Paginate results.
     *
     * @param int $page  1-based page number
     * @param int $limit Items per page
     * @param string $where Optional WHERE clause (no "WHERE" keyword)
     * @param array<mixed> $params Bound parameters
     * @param string $orderBy ORDER BY clause
     * @return array{data: array<int,array<string,mixed>>, total: int, pages: int, page: int, limit: int}
     */
    public function paginate(
        int $page = 1,
        int $limit = 20,
        string $where = '',
        array $params = [],
        string $orderBy = 'id DESC'
    ): array {
        $total  = $this->count($where, $params);
        $pages  = (int) ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'  => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
            'limit' => $limit,
        ];
    }
}
