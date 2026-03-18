<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * User Model
 */
class User extends Model
{
    protected string $table = 'usuarios';

    // ----------------------------------------------------------------
    // Lookups
    // ----------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    public function findByCpf(string $cpf): ?array
    {
        $clean = preg_replace('/\D/', '', $cpf);
        // Accept formatted or raw CPF
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE REPLACE(REPLACE(REPLACE(`cpf`, '.', ''), '-', ''), ' ', '') = ? LIMIT 1",
            [$clean]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `email` = ? LIMIT 1",
            [$email]
        );
    }

    // ----------------------------------------------------------------
    // Auth
    // ----------------------------------------------------------------

    /**
     * Verify CPF + password. Returns user row or null.
     *
     * @return array<string,mixed>|null
     */
    public function authenticate(string $cpf, string $senha): ?array
    {
        $user = $this->findByCpf($cpf);

        if ($user === null || !(bool) $user['ativo']) {
            return null;
        }

        if (!$this->verifyPassword($senha, (string) $user['senha'])) {
            return null;
        }

        return $user;
    }

    public function hashPassword(string $senha): string
    {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verifyPassword(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }

    // ----------------------------------------------------------------
    // Password Reset
    // ----------------------------------------------------------------

    /**
     * Generate a secure reset token for the user identified by CPF.
     * Returns the token string or null if user not found.
     */
    public function createResetToken(string $cpf): ?string
    {
        $user = $this->findByCpf($cpf);
        if ($user === null) {
            return null;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 7200); // 2 hours

        $this->db->execute(
            "UPDATE `{$this->table}` SET `token_reset` = ?, `token_expira` = ? WHERE `id` = ?",
            [$token, $expires, $user['id']]
        );

        return $token;
    }

    /**
     * Validate a reset token. Returns user row or null.
     *
     * @return array<string,mixed>|null
     */
    public function validateResetToken(string $token): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `token_reset` = ? AND `token_expira` > NOW() LIMIT 1",
            [$token]
        );
    }

    /**
     * Reset the password using a valid token.
     */
    public function resetPassword(string $token, string $novaSenha): bool
    {
        $user = $this->validateResetToken($token);
        if ($user === null) {
            return false;
        }

        $hash = $this->hashPassword($novaSenha);

        $this->db->execute(
            "UPDATE `{$this->table}` SET `senha` = ?, `token_reset` = NULL, `token_expira` = NULL WHERE `id` = ?",
            [$hash, $user['id']]
        );

        return true;
    }

    // ----------------------------------------------------------------
    // Profile
    // ----------------------------------------------------------------

    /**
     * Update profile (excludes password).
     *
     * @param array<string,mixed> $data
     */
    public function updateProfile(int $id, array $data): int
    {
        unset($data['senha'], $data['cpf'], $data['id']);
        return $this->update($id, $data);
    }

    public function changePassword(int $id, string $newPassword): void
    {
        $hash = $this->hashPassword($newPassword);
        $this->db->execute(
            "UPDATE `{$this->table}` SET `senha` = ? WHERE `id` = ?",
            [$hash, $id]
        );
    }
}
