<?php
/**
 * File: Models/Model.php
 * Kelas dasar Entity. Setiap Entity memegang koneksi Database dan dapat diubah ke array untuk Boundary.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

abstract class Model
{
    public function __construct(protected Database $db)
    {
    }

    /** @return array<string,mixed> */
    abstract public function toArray(): array;

    /**
     * @param list<static> $daftar
     * @return list<array<string,mixed>>
     */
    public static function daftarKeArray(array $daftar): array
    {
        return array_map(static fn (Model $m): array => $m->toArray(), $daftar);
    }
}
