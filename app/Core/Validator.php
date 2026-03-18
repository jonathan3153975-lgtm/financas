<?php declare(strict_types=1);

namespace App\Core;

/**
 * Validator - Input validation
 */
class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data  Usually $_POST
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ----------------------------------------------------------------
    // Rules
    // ----------------------------------------------------------------

    public function required(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field] = "{$label} é obrigatório.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} deve ser um e-mail válido.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = "{$label} deve ter pelo menos {$min} caracteres.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "{$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    public function date(string $field, string $format = 'Y-m-d', string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '') {
            $d = \DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = "{$label} deve ser uma data válida.";
            }
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "{$label} deve ser um número.";
        }
        return $this;
    }

    public function regex(string $field, string $pattern, string $message): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !preg_match($pattern, $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Validate a Brazilian CPF using the two-digit verification algorithm.
     */
    public function cpf(string $field, string $label = 'CPF'): static
    {
        $raw = preg_replace('/\D/', '', (string) ($this->data[$field] ?? ''));

        if (strlen($raw) !== 11) {
            $this->errors[$field] = "{$label} inválido.";
            return $this;
        }

        // Reject all-same-digit CPFs
        if (preg_match('/^(\d)\1{10}$/', $raw)) {
            $this->errors[$field] = "{$label} inválido.";
            return $this;
        }

        // First digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $raw[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $raw[9] !== $digit1) {
            $this->errors[$field] = "{$label} inválido.";
            return $this;
        }

        // Second digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $raw[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $raw[10] !== $digit2) {
            $this->errors[$field] = "{$label} inválido.";
        }

        return $this;
    }

    public function confirmed(string $field, string $confirmField, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $a = (string) ($this->data[$field]        ?? '');
        $b = (string) ($this->data[$confirmField] ?? '');
        if ($a !== $b) {
            $this->errors[$confirmField] = "A confirmação de {$label} não confere.";
        }
        return $this;
    }

    // ----------------------------------------------------------------
    // Results
    // ----------------------------------------------------------------

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }
}
