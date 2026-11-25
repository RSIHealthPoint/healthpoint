<?php
/**
 * File: database/setup.php
 * Membuat ulang seluruh tabel MySQL sesuai DDL.pdf + data demo (menghapus data lama!).
 * Jalankan: php database/setup.php
 * Berguna juga bila data antrean demo sudah "basah" (antrean demo memakai tanggal hari seeding).
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use HealthPoint\Core\Database;
use HealthPoint\Core\Seeder;

$db = new Database(require dirname(__DIR__) . '/config/config.php');
$db->hapusSemuaTabel();
$db->migrasi();
Seeder::isi($db);
echo "Database '{$db->driver()}' siap: tabel dibuat dan data demo diisi.\n";
