<?php
/**
 * File: config/Database.php
 * Pembungkus PDO untuk MySQL/MariaDB (XAMPP). Dipakai oleh seluruh Model.
 * Skema & data demo diimpor manual dari database/schema.sql lalu database/seed.sql (lihat README).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use PDO;

final class Database
{
    private PDO $pdo;
    private string $driver;

    /**
     * @param array<string,mixed> $konfigurasi dari config/config.php (driver, host, port, nama, user, pass); 'buat' => true membuat database bila belum ada (dipakai pengujian)
     */
    public function __construct(array $konfigurasi)
    {
        $this->driver = (string) ($konfigurasi['driver'] ?? 'mysql');
        if ($this->driver === 'sqlite') {
            $this->sambungSqlite((string) $konfigurasi['nama']);
            return;
        }
        $nama = (string) $konfigurasi['nama'];
        if (preg_match('/^[A-Za-z0-9_]+$/', $nama) !== 1) {
            throw new \InvalidArgumentException('Nama database hanya boleh huruf, angka, dan underscore.');
        }
        $opsi = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,   // rowCount() = baris yang cocok, bukan hanya yang berubah
            // Mode ketat: nilai melebihi panjang VARCHAR (mis. VARCHAR(255)) ditolak, bukan dipotong diam-diam.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'",
        ];
        $dsnServer = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $konfigurasi['host'], $konfigurasi['port']);
        if (!empty($konfigurasi['buat'])) {
            $server = new PDO($dsnServer, (string) $konfigurasi['user'], (string) $konfigurasi['pass'], $opsi);
            $server->exec("CREATE DATABASE IF NOT EXISTS `$nama` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        $this->pdo = new PDO($dsnServer . ';dbname=' . $nama, (string) $konfigurasi['user'], (string) $konfigurasi['pass'], $opsi);
    }

    /**
     * Mode SQLite khusus pengujian (tanpa server MySQL): 'nama' = path berkas atau ':memory:'.
     * Skema/seed dimuat oleh tests/pemuat.php (menerjemahkan schema.sql dan seed.sql).
     */
    private function sambungSqlite(string $berkas): void
    {
        $this->pdo = new PDO('sqlite:' . $berkas, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /** Menjalankan satu perintah SQL mentah (tanpa parameter), mis. DDL dari berkas skema. */
    public function eksekusi(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    /** Menghapus seluruh tabel (untuk setup ulang data demo). */
    public function hapusSemuaTabel(): void
    {
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = OFF');
            foreach ($this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN) as $t) {
                $this->pdo->exec('DROP TABLE IF EXISTS "' . $t . '"');
            }
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            return;
        }
        $tabel = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tabel as $t) { $this->pdo->exec('DROP TABLE IF EXISTS `' . $t . '`'); }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
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
