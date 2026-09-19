<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetMonthlyGoal - meta mensal de lucro do módulo de Apostas.
 *
 * Um valor por usuário/mês/ano, editável pelo usuário no dashboard.
 */
class BetMonthlyGoal extends Model
{
    protected string $table = 'apostas_meta_mensal';

    public function getForUserMonth(int $userId, int $mes, int $ano): ?float
    {
        $row = $this->db->fetch(
            "SELECT `valor` FROM `{$this->table}` WHERE `usuario_id` = ? AND `mes` = ? AND `ano` = ?",
            [$userId, $mes, $ano]
        );

        return $row !== null ? (float) $row['valor'] : null;
    }

    public function setForUserMonth(int $userId, int $mes, int $ano, float $valor): void
    {
        $this->db->execute(
            "INSERT INTO `{$this->table}` (`usuario_id`, `mes`, `ano`, `valor`)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)",
            [$userId, $mes, $ano, $valor]
        );
    }

    public function deleteForUserMonth(int $userId, int $mes, int $ano): void
    {
        $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `usuario_id` = ? AND `mes` = ? AND `ano` = ?",
            [$userId, $mes, $ano]
        );
    }
}