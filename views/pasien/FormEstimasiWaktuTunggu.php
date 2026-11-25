<?php
/**
 * File: views/pasien/FormEstimasiWaktuTunggu.php
 * Boundary UC-06 / ACT-06 (header SEQ: ACT-08) / SEQ-06. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolEstimasi;
use HealthPoint\Core\Jejak;

final class FormEstimasiWaktuTunggu extends Boundary
{
    private ?KontrolEstimasi $kontrol = null;
    private string $nomorAntrean = '';
    private int $estimasiMenit = 0;
    private string $pesan = '';

    public function hubungkan(KontrolEstimasi $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Estimasi Waktu Tunggu'; }
    public function setNomorAntrean(string $nomor): void { $this->nomorAntrean = $nomor; }

    /** SEQ-06 pesan 1-2 */
    public function bukaMenuEstimasi(): void
    {
        Jejak::catat('SEQ-06', '1', 'Pasien', 'FormEstimasiWaktuTunggu', 'bukaMenuEstimasi()');
        Jejak::catat('SEQ-06', '2', 'FormEstimasiWaktuTunggu', 'KontrolEstimasi', 'lihatEstimasi(nomorAntrean)');
        $this->kontrol->lihatEstimasi($this->nomorAntrean);
    }

    /** SEQ-06 pesan 2.3.1 */
    public function tampilkanPesan(string $pesan): void
    {
        Jejak::catat('SEQ-06', '2.3.1', 'FormEstimasiWaktuTunggu', 'Pasien', 'tampilkanPesan(pesan)');
        $this->pesan = $pesan;
        $this->tampilPesan('info', $pesan);
    }

    /** SEQ-06 pesan 2.4.1 (posisi pertama) atau 2.6.1 */
    public function tampilkanEstimasi(int $estimasiMenit, string $no): void
    {
        Jejak::catat('SEQ-06', $no, 'FormEstimasiWaktuTunggu', 'Pasien', 'tampilkanEstimasi(estimasiMenit)');
        $this->estimasiMenit = $estimasiMenit;
        $this->tampil('rincian', ['judul' => 'Estimasi', 'data' => ['nomorAntrean' => $this->nomorAntrean, 'estimasi' => $estimasiMenit . ' menit']]);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/06') . '<label>Nomor antrean <input name="nomor" value="' . self::h($this->nomorAntrean) . '"></label><button>Hitung estimasi</button></form>';
    }

    /** Estimasi (menit) atau null bila belum dapat dihitung. */
    public function getEstimasiMenit(): ?int
    {
        return $this->terakhir('rincian') !== null ? $this->estimasiMenit : null;
    }
}
