<?php
/**
 * File: config/bootstrap.php
 * Autoloader sederhana (namespace HealthPoint\* -> folder proyek) dan zona waktu.
 *
 *   HealthPoint\Controllers => controllers/            HealthPoint\Flows  => controllers/flows/
 *   HealthPoint\Models      => models/                 HealthPoint\Boundaries => views/pasien/, views/internal/{petugas,dokter,pengelola}/, views/layout/
 *   HealthPoint\Core        => config/, controllers/, models/, views/layout/ (dicari berurutan)
 */
declare(strict_types=1);

spl_autoload_register(static function (string $kelas): void {
    $prefix = 'HealthPoint\\';
    if (strncmp($kelas, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $akar = dirname(__DIR__);
    $peta = [
        'Controllers' => ['controllers'],
        'Flows'       => ['controllers/flows'],
        'Models'      => ['models'],
        'Boundaries'  => ['views/pasien', 'views/internal/petugas', 'views/internal/dokter', 'views/internal/pengelola', 'views/layout'],
        'Core'        => ['config', 'controllers', 'models', 'views/layout'],
    ];
    $bagian = explode('\\', substr($kelas, strlen($prefix)));
    $nama = array_pop($bagian);
    foreach ($peta[$bagian[0] ?? ''] ?? [] as $folder) {
        $berkas = $akar . '/' . $folder . '/' . $nama . '.php';
        if (is_file($berkas)) {
            require $berkas;
            return;
        }
    }
});

date_default_timezone_set('Asia/Jakarta');
