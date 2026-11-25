<?php
/**
 * File: tests/pemuat.php
 * Memuat database/schema.sql dan database/seed.sql ke database pengujian.
 *  - CREATE DATABASE / USE dilewati (database pengujian ditentukan oleh pemanggil).
 *  - CURDATE() diganti Waktu::hariIni() agar jam yang dikunci pengujian berlaku.
 *  - SQLite (khusus pengujian tanpa MySQL): DDL & CONCAT diterjemahkan.
 */
declare(strict_types=1);

use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

function bacaPerintahSql(string $berkas): array
{
    $sql = (string) file_get_contents(dirname(__DIR__) . '/database/' . $berkas);
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = str_replace('CURDATE()', "'" . Waktu::hariIni() . "'", $sql);
    $hasil = [];
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) ?: [] as $perintah) {
        $perintah = trim($perintah);
        if ($perintah === '' || preg_match('/^(CREATE DATABASE|USE)\b/i', $perintah) === 1) { continue; }
        $hasil[] = $perintah;
    }
    return $hasil;
}

function keSqlite(string $sql): string
{
    $sql = (string) preg_replace('/(\w+)\s+INT\s+NOT NULL AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $sql, -1, $jumlah);
    if ($jumlah > 0) { $sql = (string) preg_replace('/,\s*PRIMARY KEY \(\w+\)/i', '', $sql); }
    $sql = (string) preg_replace('/UNIQUE KEY \w+ \(/i', 'UNIQUE (', $sql);
    $sql = (string) preg_replace('/\)\s*ENGINE=.*$/is', ')', $sql);
    return (string) preg_replace("/CONCAT\\(('[^']*'),\\s*('[^']*')\\)/", '($1 || $2)', $sql);
}

/** Mengosongkan database lalu memuat schema.sql + seed.sql. */
function muatDatabase(Database $db): void
{
    $db->hapusSemuaTabel();
    foreach (['schema.sql', 'seed.sql'] as $berkas) {
        foreach (bacaPerintahSql($berkas) as $perintah) {
            $db->eksekusi($db->driver() === 'sqlite' ? keSqlite($perintah) : $perintah);
        }
    }
}
