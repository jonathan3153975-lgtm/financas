<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetShareLink - links públicos de compartilhamento do dashboard de apostas
 */
class BetShareLink extends Model
{
    protected string $table = 'apostas_links_publicos';

    /**
     * @return array<string,mixed>|null
     */
    public function findActiveForUser(int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `usuario_id` = ? AND `ativo` = 1 ORDER BY `id` DESC LIMIT 1",
            [$userId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `token` = ? AND `ativo` = 1 LIMIT 1",
            [$token]
        );
    }

    public function generateForUser(int $userId): string
    {
        // Revoke previous active links before creating a new one
        $this->db->execute(
            "UPDATE `{$this->table}` SET `ativo` = 0 WHERE `usuario_id` = ?",
            [$userId]
        );

        $token = bin2hex(random_bytes(24));

        $this->create([
            'usuario_id' => $userId,
            'token'      => $token,
            'ativo'      => 1,
        ]);

        return $token;
    }

    public function revokeForUser(int $userId): int
    {
        return $this->db->execute(
            "UPDATE `{$this->table}` SET `ativo` = 0 WHERE `usuario_id` = ?",
            [$userId]
        );
    }
}
