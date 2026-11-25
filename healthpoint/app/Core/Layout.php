<?php
/**
 * File: app/Core/Layout.php
 * Kerangka halaman HTML + menu per peran.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use HealthPoint\Boundaries\Boundary;

final class Layout
{
    /** @param array<string,mixed>|null $pengguna */
    public static function halaman(string $isi, ?array $pengguna, string $judul = 'HealthPoint'): string
    {
        $menu = '';
        if ($pengguna !== null) {
            foreach (Router::menuUntuk((string) $pengguna['role']) as $path => $teks) {
                $menu .= '<a href="' . Boundary::h($path) . '">' . Boundary::h($teks) . '</a>';
            }
            $menu .= '<a href="/logout">Keluar (' . Boundary::h($pengguna['username']) . ')</a>';
        }
        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . Boundary::h($judul) . '</title><link rel="stylesheet" href="/assets/style.css"></head><body>'
            . '<header><strong>HealthPoint / SiKlinik</strong><nav>' . $menu . '</nav></header><main>' . $isi . '</main></body></html>';
    }
}
