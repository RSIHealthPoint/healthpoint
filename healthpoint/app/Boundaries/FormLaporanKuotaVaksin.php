<?php
/**
 * File: app/Boundaries/FormLaporanKuotaVaksin.php
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
}
