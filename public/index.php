<?php
/**
 * File: public/index.php  — front controller.
 * Jalankan: php -S localhost:8000 -t public public/index.php
 */
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $berkas = __DIR__ . (parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if (is_file($berkas) && !str_ends_with($berkas, '.php')) { return false; }
}

require dirname(__DIR__) . '/bootstrap.php';

use HealthPoint\Core\Database;
use HealthPoint\Core\Request;
use HealthPoint\Core\Router;
use HealthPoint\Core\Seeder;
use HealthPoint\Core\Sesi;

$db = new Database(require dirname(__DIR__) . '/config/config.php');
if (!$db->sudahMigrasi()) { $db->migrasi(); Seeder::isi($db); }   // pertama kali: buat tabel + data demo

Sesi::mulai();
[$status, $html, $alihkan] = Router::tangani(Request::dariGlobal(), $db);
if ($alihkan !== null) { header('Location: ' . $alihkan, true, $status); exit; }
http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
echo $html;
