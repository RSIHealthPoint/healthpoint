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
}
