<?php
/**
 * File: views/internal/pengelola/FormLaporanHarian.php
 * Boundary UC-16 / ACT-16 (header SEQ: ACT-18) / SEQ-16. Aktor: Pengelola Klinik.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\LaporanHarianController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;

final class FormLaporanHarian extends Boundary
{
    private ?LaporanHarianController $kontrol = null;
    private string $tanggal = '';
    /** @var list<array<string,mixed>> */
    private array $rekapPerLayanan = [];
    private string $pesan = '';

    public function hubungkan(LaporanHarianController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Laporan Harian Jumlah Pasien per Layanan'; }

    public function bukaMenuLaporanHarian(): void
    {
        Jejak::catat('SEQ-16', '1', 'Pengelola Klinik', 'FormLaporanHarian', 'bukaMenuLaporanHarian()');
    }

    /** SEQ-16 pesan 2-3 (4c = memilih tanggal lain, kembali ke pesan 3) */
    public function pilihTanggal(string $tanggal, bool $ulang = false): void
    {
        Jejak::catat('SEQ-16', $ulang ? '4c' : '2', 'Pengelola Klinik', 'FormLaporanHarian', 'pilihTanggal(tanggal)');
        $this->tanggal = $tanggal;
        Jejak::catat('SEQ-16', '3', 'FormLaporanHarian', 'LaporanHarianController', 'lihatLaporan(tanggal)');
        $this->kontrol->lihatLaporan($tanggal);
    }

    /** @param list<array<string,mixed>> $rekapPerLayanan SEQ-16 pesan 8 */
    public function tampilkanRekap(array $rekapPerLayanan): void
    {
        Jejak::catat('SEQ-16', '8', 'FormLaporanHarian', 'Pengelola Klinik', 'tampilkanRekap(rekapPerLayanan)');
        $this->rekapPerLayanan = $rekapPerLayanan;
        $this->tampil('tabel', ['judul' => 'Rekap pasien tanggal ' . $this->tanggal, 'baris' => $rekapPerLayanan]);
    }

    /** SEQ-16 pesan 4b */
    public function tampilkanPesan(string $pesan): void
    {
        Jejak::catat('SEQ-16', '4b', 'FormLaporanHarian', 'Pengelola Klinik', 'tampilkanPesan(pesan)');
        $this->pesan = $pesan;
        $this->tampilPesan('info', $pesan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/16', 'get') . '<label>Tanggal <input type="date" name="tanggal" value="' . self::h($this->tanggal ?: Waktu::hariIni()) . '"></label><button>Lihat laporan</button></form>';
    }

    /** Kuota pasien harian per layanan (DB belum punya kolom kuota; ubah angka di sini bila perlu). */
    private const KUOTA_HARIAN = ['Poli Umum' => 60, 'Poli Gigi' => 25, 'Vaksinasi' => 20];
    private const KUOTA_BAWAAN = 30;

    private static function ikonLayanan(string $nama): string
    {
        $n = strtolower($nama);
        return match (true) {
            str_contains($n, 'gigi')   => 'tooth',
            str_contains($n, 'vaksin') => 'syringe',
            default                    => 'stethoscope',
        };
    }

    /** Halaman UI-14: kartu laporan + pemilih tanggal + progres kuota per layanan. */
    public function renderHalaman(): string
    {
        $tanggal = $this->tanggal !== '' ? $this->tanggal : Waktu::hariIni();
        $o = '<section class="report-card" aria-label="Laporan harian jumlah pasien"><div class="report-head">'
            . '<h1 class="report-card__title">' . self::ikon('bar-chart') . 'Laporan Harian Jumlah Pasien</h1>'
            . '<form class="date-picker" method="get" action="/uc/16">'
            . '<label class="date-picker__label" for="tanggal-laporan">Pilih Tanggal Laporan:</label>'
            . '<label class="date-chip">' . self::ikon('calendar')
            . '<input class="date-chip__input" type="date" id="tanggal-laporan" name="tanggal" value="' . self::h($tanggal) . '" onchange="this.form.submit()"></label>'
            . '</form></div>';

        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }

        if ($this->rekapPerLayanan !== []) {
            $varian = ['', ' progress-row--teal', ' progress-row--violet'];
            $o .= '<div class="progress-list">';
            foreach ($this->rekapPerLayanan as $i => $r) {
                $nama = (string) $r['layanan'];
                $n = (int) $r['jumlahPasien'];
                $kuota = self::KUOTA_HARIAN[$nama] ?? self::KUOTA_BAWAAN;
                $persen = min(100, (int) round($n / $kuota * 100));
                $sisa = max(0, $kuota - $n);
                $o .= '<div class="progress-row' . $varian[$i % 3] . '"><div class="progress-row__top">'
                    . '<span class="progress-row__name">' . self::ikon(self::ikonLayanan($nama)) . self::h($nama) . '</span>'
                    . '<span class="progress-row__stat">' . $n . ' <span class="progress-row__unit">/ ' . $kuota . ' kuota</span><span class="progress-row__percent">' . $persen . '%</span></span></div>'
                    . '<div class="progress" role="progressbar" aria-label="' . self::h($nama) . '" aria-valuenow="' . $persen . '" aria-valuemin="0" aria-valuemax="100">'
                    . '<span class="progress__bar" style="width:' . $persen . '%"></span></div>'
                    . '<span class="progress-row__target">Sisa kuota: ' . $sisa . ' pasien</span></div>';
            }
            $o .= '</div><div class="export-actions"><button class="btn btn--export" type="button" onclick="window.print()">'
                . self::ikon('download') . 'Unduh Laporan Harian (PDF/Excel)</button></div>';
        }
        return $o . '</section>';
    }
}
