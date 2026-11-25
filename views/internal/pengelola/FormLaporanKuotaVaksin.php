<?php
/**
 * File: views/internal/pengelola/FormLaporanKuotaVaksin.php
 * Boundary UC-17 / ACT-17 (header SEQ: ACT-19) / SEQ-17. Aktor: Pengelola Klinik.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\LaporanKuotaVaksinController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;

final class FormLaporanKuotaVaksin extends Boundary
{
    private ?LaporanKuotaVaksinController $kontrol = null;
    private string $tanggal = '';
    private int $jumlahDosisTerpakai = 0;
    private int $sisaKuota = 0;
    private string $pesan = '';

    public function hubungkan(LaporanKuotaVaksinController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Laporan Pemakaian Kuota Vaksin'; }

    public function bukaMenuLaporanVaksin(): void
    {
        Jejak::catat('SEQ-17', '1', 'Pengelola Klinik', 'FormLaporanKuotaVaksin', 'bukaMenuLaporanVaksin()');
    }

    /** SEQ-17 pesan 2-3 (4c: memilih tanggal lain, kembali ke pesan 3) */
    public function pilihTanggal(string $tanggal, bool $ulang = false): void
    {
        Jejak::catat('SEQ-17', $ulang ? '4c' : '2', 'Pengelola Klinik', 'FormLaporanKuotaVaksin', 'pilihTanggal(tanggal)');
        $this->tanggal = $tanggal;
        Jejak::catat('SEQ-17', '3', 'FormLaporanKuotaVaksin', 'LaporanKuotaVaksinController', 'lihatLaporan(tanggal)');
        $this->kontrol->lihatLaporan($tanggal);
    }

    /** SEQ-17 pesan 8 */
    public function tampilkanRekap(int $jumlahDosisTerpakai, int $sisaKuota): void
    {
        Jejak::catat('SEQ-17', '8', 'FormLaporanKuotaVaksin', 'Pengelola Klinik', 'tampilkanRekap(jumlahDosisTerpakai, sisaKuota)');
        [$this->jumlahDosisTerpakai, $this->sisaKuota] = [$jumlahDosisTerpakai, $sisaKuota];
        $this->tampil('rincian', ['judul' => 'Rekap pemakaian kuota ' . $this->tanggal, 'data' => ['jumlahDosisTerpakai' => $jumlahDosisTerpakai, 'sisaKuota' => $sisaKuota]]);
    }

    /** SEQ-17 pesan 4b */
    public function tampilkanPesan(string $pesan): void
    {
        Jejak::catat('SEQ-17', '4b', 'FormLaporanKuotaVaksin', 'Pengelola Klinik', 'tampilkanPesan(pesan)');
        $this->pesan = $pesan;
        $this->tampilPesan('info', $pesan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/17', 'get') . '<label>Tanggal <input type="date" name="tanggal" value="' . self::h($this->tanggal ?: Waktu::hariIni()) . '"></label><button>Lihat laporan</button></form>';
    }

    /**
     * Halaman UI-15: ringkasan total terpakai + rincian per vaksin.
     * @param list<array{nama:string,tipe:string,terpakai:int,sisa:int}> $rincian
     */
    public function renderHalaman(array $rincian): string
    {
        $tanggal = $this->tanggal !== '' ? $this->tanggal : Waktu::hariIni();
        $o = '<section class="quota-card" aria-label="Laporan pemakaian kuota vaksin"><div>'
            . '<h1 class="quota-card__title">' . self::ikon('archive') . 'Laporan Pemakaian Kuota Vaksin</h1>'
            . '<form class="date-picker" method="get" action="/uc/17">'
            . '<label class="date-picker__label" for="tanggal-kuota">Pilih Tanggal :</label>'
            . '<label class="date-chip date-chip--light">' . self::ikon('calendar')
            . '<input class="date-chip__input" type="date" id="tanggal-kuota" name="tanggal" value="' . self::h($tanggal) . '" onchange="this.form.submit()"></label>'
            . '</form></div>';

        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }

        if ($this->terakhir('rincian') !== null) {
            $o .= '<div class="summary-tile"><p class="summary-tile__label">' . self::ikon('syringe') . 'Total Terpakai</p>'
                . '<p class="summary-tile__value">' . $this->jumlahDosisTerpakai . ' <span class="summary-tile__unit">Dosis</span></p></div>';

            $o .= '<div class="quota-list">';
            foreach ($rincian as $r) {
                $terpakai = (int) $r['terpakai'];
                $sisa = (int) $r['sisa'];
                $kuota = $terpakai + $sisa;
                $habis = $sisa <= 0;
                $tipis = $habis || ($kuota > 0 && $sisa * 100 <= $kuota * 40);
                $status = $habis ? 'Habis' : ($tipis ? 'Menipis' : 'Aman');
                $o .= '<article class="quota-item"><div class="quota-item__top"><div>'
                    . '<h2 class="quota-item__name">Vaksin ' . self::h($r['nama']) . '</h2>'
                    . '<p class="quota-item__sub">Tipe: ' . self::h($r['tipe']) . '</p>'
                    . '<span class="quota-item__flag' . ($tipis ? ' quota-item__flag--warn' : '') . '">' . $status . '</span></div>'
                    . '<span class="quota-item__quota">Kuota: ' . $kuota . '</span></div>'
                    . '<div class="quota-item__tiles">'
                    . '<div class="quota-item__tile"><p class="quota-item__tile-label">Terpakai:</p><p class="quota-item__tile-value">' . $terpakai . ' dosis</p></div>'
                    . '<div class="quota-item__tile"><p class="quota-item__tile-label">Sisa Kuota:</p>'
                    . '<p class="quota-item__tile-value ' . ($tipis ? 'quota-item__tile-value--low' : 'quota-item__tile-value--ok') . '">' . $sisa . ' dosis</p></div>'
                    . '</div></article>';
            }
            $o .= '</div><button class="btn btn--export-strong" type="button" onclick="window.print()">'
                . self::ikon('download') . 'Ekspor Rekap Pemakaian Kuota (PDF/Excel)</button>';
        }
        return $o . '</section>';
    }
}
