<?php
/**
 * File: config/config.php
 * Konfigurasi database MySQL/MariaDB. Bawaan = XAMPP (user root tanpa password).
 * Dapat ditimpa lewat variabel lingkungan HP_DB_* tanpa mengubah file ini.
 */
declare(strict_types=1);

$driver = getenv('HP_DB_DRIVER') ?: 'mysql';          // 'mysql' (bawaan); 'sqlite' hanya untuk pengujian (tests/)

return [
    'driver' => $driver,
    'host'   => getenv('HP_DB_HOST') ?: '127.0.0.1',
    'port'   => getenv('HP_DB_PORT') ?: '3306',
    // MySQL: nama database (dibuat oleh database/schema.sql). SQLite: path berkas.
    'nama'   => getenv('HP_DB_NAME') ?: ($driver === 'sqlite' ? sys_get_temp_dir() . '/healthpoint.sqlite' : 'healthpoint'),
    'user'   => getenv('HP_DB_USER') ?: 'root',
    'pass'   => getenv('HP_DB_PASS') !== false ? (string) getenv('HP_DB_PASS') : '',
];
