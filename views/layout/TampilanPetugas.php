<?php
/**
 * File: views/layout/TampilanPetugas.php
 * Pembangun HTML halaman Petugas (UC-04/08/14/15) mengikuti desain UI. Hanya menyusun string HTML.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use HealthPoint\Boundaries\Boundary;

final class TampilanPetugas
{
    private const BULAN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    private const BULAN_PENUH = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public static function ikon(string $nama): string
    {
        return '<span class="icon icon--' . $nama . '" aria-hidden="true"></span>';
    }

    public static function notice(string $tipe, string $teks): string
    {
        return '<div class="notice notice--' . Boundary::h($tipe) . '" role="alert">' . Boundary::h($teks) . '</div>';
    }

    public static function token(): string
    {
        return '<input type="hidden" name="_token" value="' . Boundary::h(Sesi::token()) . '">';
    }

    /** 2026-08-12 -> "12 Ags 2026" (atau "12 Agustus 2026" bila $penuh). */
    public static function tanggal(string $ymd, bool $penuh = false): string
    {
        try {
            $d = new \DateTimeImmutable($ymd);
        } catch (\Throwable) {
            return $ymd;
        }
        $bulan = $penuh ? self::BULAN_PENUH : self::BULAN;
        return $d->format('d') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    public static function usia(string $tanggalLahir): int
    {
        try {
            return (new \DateTimeImmutable($tanggalLahir))->diff(Waktu::sekarang())->y;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** "Siti Rahmawati" -> "SR". */
    public static function inisial(string $nama): string
    {
        $o = '';
        foreach (array_slice(preg_split('/\s+/', trim($nama)) ?: [], 0, 2) as $kata) {
            if (preg_match('/./su', $kata, $m) === 1) { $o .= $m[0]; }
        }
        return strtoupper($o);
    }

    /** "A002" -> "A-002". */
    public static function nomorAntrean(string $nomor): string
    {
        return (string) preg_replace('/^([A-Za-z]+)(\d+)$/', '$1-$2', $nomor);
    }

    /** Layar "Cari Pasien" (UI-12). */
    public static function formPencarian(string $nama, string $tanggalLahir, string $noRM): string
    {
        return '<form class="search-card" action="/uc/14" method="get">'
            . '<label class="search-card__label" for="nama-pasien">Nama Pasien</label>'
            . '<div class="lookup__field"><input class="lookup__input" id="nama-pasien" name="nama" type="text" value="' . Boundary::h($nama) . '" autofocus>'
            . '<span class="lookup__icon icon icon--user" aria-hidden="true"></span></div>'
            . '<div class="search-card__row">'
            . '<div class="search-card__col"><label class="search-card__label" for="tanggal-lahir">Tanggal Lahir:</label>'
            . '<div class="static-field">' . self::ikon('calendar') . '<input class="static-field__input" id="tanggal-lahir" name="tanggalLahir" type="date" value="' . Boundary::h($tanggalLahir) . '"></div></div>'
            . '<div class="search-card__col"><label class="search-card__label" for="no-rm">No. Rekam Medis (RM):</label>'
            . '<div class="static-field">' . self::ikon('id-card') . '<input class="static-field__input" id="no-rm" name="noRM" type="text" value="' . Boundary::h($noRM) . '"></div></div>'
            . '</div><button class="btn btn--form" type="submit">Cari Pasien</button></form>';
    }

    /** Daftar kandidat bila nama pasien kembar (UC-15). @param list<array<string,mixed>> $daftar */
    public static function kandidat(array $daftar, string $nama): string
    {
        $o = '<p class="candidate-hint">Ditemukan ' . count($daftar) . ' pasien dengan nama serupa. Pilih salah satu:</p><div class="candidate-list">';
        foreach ($daftar as $p) {
            $url = '/uc/15?nama=' . rawurlencode($nama) . '&idPasien=' . (int) $p['idPasien'];
            $o .= '<a class="candidate" href="' . Boundary::h($url) . '"><span class="candidate__name">' . Boundary::h($p['nama']) . '</span>'
                . '<span class="candidate__meta">' . Boundary::h($p['noRM']) . ' · ' . self::tanggal((string) $p['tanggalLahir']) . ' (' . self::usia((string) $p['tanggalLahir']) . ' th) · ' . Boundary::h($p['alamat']) . '</span></a>';
        }
        return $o . '</div>';
    }

    public static function tautanKembali(): string
    {
        return '<a class="app-link app-link--back back-link" href="/uc/14">Cari pasien lain</a>';
    }

    private static function tile(string $label, string $nilai, bool $lebar = false, bool $alamat = false): string
    {
        return '<div class="data-tile' . ($lebar ? ' data-tile--wide' : '') . '"><span class="data-tile__label">' . $label . '</span>'
            . '<span class="data-tile__value' . ($alamat ? ' data-tile__value--address' : '') . '">' . Boundary::h($nilai) . '</span></div>';
    }

    /**
     * Kartu detail pasien + ringkasan riwayat (UI-13).
     * @param array<string,mixed> $p DataPasien::toArray()
     * @param list<array{tanggal:string,layanan:string,keluhan:string}> $riwayat kunjungan selesai, terbaru dulu
     */
    public static function detailPasien(array $p, array $riwayat): string
    {
        $nama = (string) $p['nama'];
        $o = '<article class="candidate-card" aria-label="Data pasien ' . Boundary::h($nama) . '">'
            . '<header class="candidate-head"><span class="candidate-head__avatar" aria-hidden="true">' . Boundary::h(self::inisial($nama)) . '</span>'
            . '<div><h1 class="candidate-head__name">' . Boundary::h($nama) . '</h1><p class="candidate-head__sub">No. RM: ' . Boundary::h($p['noRM']) . '</p></div></header>'
            . '<section class="data-block" aria-label="Data identitas dasar pasien"><h2 class="eyebrow">A. Data Identitas Dasar Pasien</h2><div class="data-grid">'
            . self::tile('Tanggal Lahir / Usia', self::tanggal((string) $p['tanggalLahir'], true) . ' (' . self::usia((string) $p['tanggalLahir']) . ' th)')
            . self::tile('Jenis Kelamin', (string) $p['jenisKelamin'])
            . self::tile('No. Kontak (HP)', (string) $p['noTelepon'], true)
            . self::tile('Alamat Domisili', (string) $p['alamat'], true, true)
            . '</div></section>';

        $total = count($riwayat);
        $o .= '<section class="candidate-history" aria-label="Ringkasan riwayat kunjungan pasien"><div class="candidate-history__head">'
            . '<h2 class="eyebrow">B. Ringkasan Riwayat Kunjungan Pasien</h2><span class="total-pill">Total Kunjungan: ' . $total . '</span></div>';
        if ($total === 0) {
            $o .= '<p class="data-empty">Belum ada riwayat kunjungan.</p>';
        } else {
            $o .= '<div class="visit-list">';
            foreach (array_slice($riwayat, 0, 5) as $i => $v) {
                $judul = ($i === 0 ? 'Kunjungan Terakhir' : 'Riwayat Kunjungan') . ' (' . self::tanggal($v['tanggal']) . ')';
                $keluhan = $v['keluhan'] !== '' ? $v['keluhan'] : 'tanpa keluhan tercatat';
                $o .= '<div class="visit' . ($i === 0 ? ' visit--latest' : '') . '"><div class="visit__row">'
                    . '<span class="visit__date">' . self::ikon('history') . Boundary::h($judul) . '</span><span class="visit__status">Selesai</span></div>'
                    . '<p class="visit__detail">' . Boundary::h($v['layanan']) . ' • ' . Boundary::h($keluhan) . '</p></div>';
            }
            $o .= '</div>';
        }
        return $o . '</section><button class="btn btn--print" type="button" onclick="window.print()">' . self::ikon('printer') . 'Cetak Ringkasan</button></article>';
    }
}