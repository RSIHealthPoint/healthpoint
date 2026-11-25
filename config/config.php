<?php
/**
 * File: config/config.php
 * Konfigurasi database MySQL/MariaDB. Bawaan = XAMPP (user root tanpa password).
 * Dapat ditimpa lewat variabel lingkungan HP_DB_* tanpa mengubah file ini.
 */
declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host'   => getenv('HP_DB_HOST') ?: '127.0.0.1',
    'port'   => getenv('HP_DB_PORT') ?: '3306',
    'nama'   => getenv('HP_DB_NAME') ?: 'healthpoint',        // dibuat otomatis bila belum ada
    'user'   => getenv('HP_DB_USER') ?: 'root',
    'pass'   => getenv('HP_DB_PASS') !== false ? (string) getenv('HP_DB_PASS') : '',
];
