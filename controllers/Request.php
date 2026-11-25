<?php
/**
 * File: Core/Request.php
 * Representasi permintaan HTTP sederhana (agar Flow dapat diuji tanpa server web).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Request
{
    /** @param array<string,mixed> $query @param array<string,mixed> $body */
    public function __construct(
        public readonly string $metode = 'GET',
        public readonly string $path = '/',
        public readonly array $query = [],
        public readonly array $body = [],
    ) {
    }

    public static function dariGlobal(): self
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), $path, $_GET, $_POST);
    }

    public function ambil(string $kunci, string $bawaan = ''): string
    {
        $v = $this->body[$kunci] ?? $this->query[$kunci] ?? $bawaan;
        return is_scalar($v) ? trim((string) $v) : $bawaan;
    }

    public function adaPost(): bool
    {
        return $this->metode === 'POST';
    }
}
