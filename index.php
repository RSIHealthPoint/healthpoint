<?php
/**
 * File: index.php  — front controller.
 * Jalankan (dari folder proyek): php -S localhost:8000 index.php
 */
declare(strict_types=1);

// Server bawaan PHP: hanya berkas statis di folder public/ (css, js, images, mockups) yang disajikan langsung (config/, database/, dst. tidak).
if (PHP_SAPI === 'cli-server') {
    $berkas = realpath(__DIR__ . (parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/'));
    if ($berkas !== false && is_file($berkas) && str_starts_with($berkas, __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR)
        && !str_ends_with($berkas, '.php')) { return false; }
}

require __DIR__ . '/config/bootstrap.php';

use HealthPoint\Core\Database;
use HealthPoint\Core\Request;
use HealthPoint\Core\Router;
use HealthPoint\Core\Sesi;

try {
    $db = new Database(require __DIR__ . '/config/config.php');
} catch (\Throwable $e) {
    error_log('[HealthPoint] ' . $e->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Database belum siap</title>'
        . '<body style="font-family:sans-serif;max-width:560px;margin:48px auto;padding:0 16px">'
        . '<h1>Database belum siap</h1><p>HealthPoint tidak dapat terhubung ke database.</p><ul>'
        . '<li>Pastikan <strong>MySQL</strong> sudah di-<em>Start</em> di XAMPP Control Panel.</li>'
        . '<li>Impor <code>database/schema.sql</code> lalu <code>database/seed.sql</code> (phpMyAdmin / DBeaver / <code>mysql &lt; berkas.sql</code>).</li>'
        . '<li>Periksa host, user, dan password di <code>config/config.php</code> (atau variabel <code>HP_DB_*</code>).</li></ul>'
        . '<p><small>Detail: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></p></body>';
    exit;
}

Sesi::mulai();
[$status, $html, $alihkan] = Router::tangani(Request::dariGlobal(), $db);
if ($alihkan !== null) { header('Location: ' . $alihkan, true, $status); exit; }
http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
echo $html;
