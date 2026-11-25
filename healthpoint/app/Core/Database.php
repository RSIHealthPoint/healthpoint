<?php
/**
 * File: app/Core/Database.php
 * Pembungkus PDO SQLite. Dipakai oleh seluruh Model (Entity).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(string $berkas = ':memory:')
    {
        $this->pdo = new PDO('sqlite:' . $berkas);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function migrasi(): void
    {
        $this->pdo->exec((string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql'));
    }

    /** @param array<string|int,mixed> $param */
    public function jalankan(string $sql, array $param = []): int
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($param);
        return $st->rowCount();
    }

    /** @param array<string|int,mixed> $param @return list<array<string,mixed>> */
    public function ambilSemua(string $sql, array $param = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($param);
        return $st->fetchAll();
    }

    /** @param array<string|int,mixed> $param @return array<string,mixed>|null */
    public function ambilSatu(string $sql, array $param = []): ?array
    {
        $baris = $this->ambilSemua($sql, $param);
        return $baris[0] ?? null;
    }

    /** @param array<string|int,mixed> $param */
    public function ambilNilai(string $sql, array $param = []): mixed
    {
        $baris = $this->ambilSatu($sql, $param);
        return $baris === null ? null : array_values($baris)[0];
    }

    public function idTerakhir(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function mulaiTransaksi(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollback(): void { if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); } }
}
