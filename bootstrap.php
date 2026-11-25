<?php
/**
 * File: bootstrap.php
 * Autoloader PSR-4 sederhana (HealthPoint\ => app/) dan konfigurasi dasar.
 */
declare(strict_types=1);

spl_autoload_register(static function (string $kelas): void {
    $prefix = 'HealthPoint\\';
    if (strncmp($kelas, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relatif = str_replace('\\', '/', substr($kelas, strlen($prefix)));
    $berkas  = __DIR__ . '/app/' . $relatif . '.php';
    if (is_file($berkas)) {
        require $berkas;
    }
});

date_default_timezone_set('Asia/Jakarta');
