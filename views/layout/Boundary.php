<?php
/**
 * File: views/layout/Boundary.php
 * Kelas dasar Boundary. Setiap method tampil*() tidak melakukan echo; hasilnya ditumpuk di "layar"
 * sehingga dapat diperiksa pengujian dan dirender ke HTML oleh render().
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Core\Sesi;

abstract class Boundary
{
    /** @var list<array{jenis:string,data:mixed}> */
    protected array $layar = [];

    abstract public function judul(): string;

    /** Bagian formulir input (HTML). Default kosong. */
    public function formHtml(): string
    {
        return '';
    }

    protected function tampil(string $jenis, mixed $data): void
    {
        $this->layar[] = ['jenis' => $jenis, 'data' => $data];
    }

    protected function tampilPesan(string $tipe, string $teks): void
    {
        $this->tampil('pesan', ['tipe' => $tipe, 'teks' => $teks]);
    }

    /** @return list<array{jenis:string,data:mixed}> */
    public function getLayar(): array
    {
        return $this->layar;
    }

    public function bersihkanLayar(): void
    {
        $this->layar = [];
    }

    /** Data layar terakhir bertipe $jenis (null bila belum ada). */
    public function terakhir(string $jenis): mixed
    {
        for ($i = count($this->layar) - 1; $i >= 0; $i--) {
            if ($this->layar[$i]['jenis'] === $jenis) {
                return $this->layar[$i]['data'];
            }
        }
        return null;
    }

    /** Teks pesan terakhir (apa pun tipenya). */
    public function pesanTerakhir(): ?string
    {
        $p = $this->terakhir('pesan');
        return is_array($p) ? (string) $p['teks'] : null;
    }

    public function render(): string
    {
        $o = '<h1 class="app-title">' . self::h($this->judul()) . '</h1>';
        foreach ($this->layar as $l) {
            $o .= match ($l['jenis']) {
                'pesan' => '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>',
                'tabel' => self::tabelHtml((array) $l['data']),
                'rincian' => self::rincianHtml((array) $l['data']),
                default => '<p>' . self::h(is_scalar($l['data']) ? (string) $l['data'] : (string) json_encode($l['data'])) . '</p>',
            };
        }
        return $o . $this->formHtml();
    }

    public static function h(mixed $v): string
    {
        return htmlspecialchars(is_scalar($v) || $v === null ? (string) $v : (string) json_encode($v), ENT_QUOTES, 'UTF-8');
    }

    protected static function formTag(string $aksi, string $metode = 'post'): string
    {
        $t = strtolower($metode) === 'post' ? '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">' : '';
        return '<form class="app-form" method="' . $metode . '" action="' . self::h($aksi) . '">' . $t;
    }

    /** Baris tabel ditampilkan sebagai kartu (layar sempit). @param array<string,mixed> $d */
    private static function tabelHtml(array $d): string
    {
        $baris = (array) ($d['baris'] ?? []);
        $o = '<h2 class="section-title">' . self::h($d['judul'] ?? '') . '</h2>';
        if ($baris === []) {
            return $o . '<p class="data-empty">Tidak ada data.</p>';
        }
        $o .= '<div class="data-list">';
        foreach ($baris as $b) {
            $o .= '<dl class="data-item">';
            foreach ((array) $b as $k => $v) { $o .= '<dt>' . self::h($k) . '</dt><dd>' . self::h($v) . '</dd>'; }
            $o .= '</dl>';
        }
        return $o . '</div>';
    }

    /** @param array<string,mixed> $d */
    private static function rincianHtml(array $d): string
    {
        $o = '<h2 class="section-title">' . self::h($d['judul'] ?? '') . '</h2><div class="data-grid data-grid--app">';
        foreach ((array) ($d['data'] ?? []) as $k => $v) {
            $o .= '<div class="data-tile"><span class="data-tile__label">' . self::h(strtoupper((string) $k)) . '</span><span class="data-tile__value">' . self::h($v) . '</span></div>';
        }
        return $o . '</div>';
    }
}
