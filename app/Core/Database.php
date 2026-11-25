<?php
/**
 * File: app/Core/Database.php
 * Pembungkus PDO untuk MySQL/MariaDB (XAMPP). Dipakai oleh seluruh Model.
 * Skema: database/schema.mysql.sql (mengikuti DDL.pdf bagian 2.5).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use PDO;

final class Database
{
    private PDO $pdo;

    /**
     * @param array<string,mixed> $konfigurasi dari config/config.php (host, port, nama, user, pass)
     */
    public function __construct(array $konfigurasi)
    {
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
        $server = new PDO($dsnServer, (string) $konfigurasi['user'], (string) $konfigurasi['pass'], $opsi);
        $server->exec("CREATE DATABASE IF NOT EXISTS `$nama` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->pdo = new PDO($dsnServer . ';dbname=' . $nama, (string) $konfigurasi['user'], (string) $konfigurasi['pass'], $opsi);
    }

    public function driver(): string
    {
        return 'mysql';
    }

    /** Menjalankan database/schema.mysql.sql (idempotent: CREATE TABLE IF NOT EXISTS). */
    public function migrasi(): void
    {
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.mysql.sql');
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);          // buang baris komentar
        foreach (preg_split('/;\s*(\r?\n|$)/', $sql) ?: [] as $perintah) {
            if (trim($perintah) !== '') { $this->pdo->exec($perintah); }
        }
    }

    /** True bila tabel inti sudah ada (dipakai front controller untuk migrasi pertama kali). */
    public function sudahMigrasi(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM Pengguna LIMIT 1');
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /** Menghapus seluruh tabel (untuk setup ulang data demo). */
    public function hapusSemuaTabel(): void
    {
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
