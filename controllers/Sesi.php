<?php
/**
 * File: Core/Sesi.php
 * Sesi login dan token CSRF. Menyimpan ke $_SESSION bila sesi aktif, atau array internal (CLI/pengujian).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Sesi
{
    /** @var array<string,mixed> */
    private static array $lokal = [];

    public static function mulai(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
            session_start();
        }
    }

    /** Membuat ID sesi baru (dipanggil saat login/logout). Tidak berpengaruh pada mode CLI/pengujian. */
    public static function ganti(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** @return array<string,mixed> */
    private static function &data(): array
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            return $_SESSION;
        }
        return self::$lokal;
    }

    public static function set(string $k, mixed $v): void { $d = &self::data(); $d[$k] = $v; }
    public static function get(string $k, mixed $bawaan = null): mixed { $d = &self::data(); return $d[$k] ?? $bawaan; }
    public static function hapus(string $k): void { $d = &self::data(); unset($d[$k]); }

    public static function token(): string
    {
        $t = self::get('csrf');
        if (!is_string($t)) {
            $t = bin2hex(random_bytes(16));
            self::set('csrf', $t);
        }
        return $t;
    }

    public static function tokenValid(string $t): bool
    {
        return hash_equals(self::token(), $t);
    }
}
