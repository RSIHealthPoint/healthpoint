<?php
/**
 * File: views/pasien/FormInformasiAntrean.php
 * Boundary UC-05 / ACT-05 (header SEQ: ACT-07) / SEQ-05. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolAntrean;
use HealthPoint\Core\Jejak;

final class FormInformasiAntrean extends Boundary
{
    private ?KontrolAntrean $kontrol = null;
    private string $nomorAntrean = '';
    private string $nomorBerjalan = '';
    private int $posisi = 0;
    private string $pesanKesalahan = '';

    public function hubungkan(KontrolAntrean $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Informasi Antrean'; }

    /** SEQ-05 pesan 1-2 */
    public function bukaMenuInformasiAntrean(): void
    {
        Jejak::catat('SEQ-05', '1', 'Pasien', 'FormInformasiAntrean', 'bukaMenuInformasiAntrean()');
        $this->tampilkanFormInput();
    }

    public function tampilkanFormInput(): void
    {
        Jejak::catat('SEQ-05', '2', 'FormInformasiAntrean', 'Pasien', 'tampilkanFormInput()');
    }

    /** SEQ-05 pesan 3-4 (5 = perbaikiNomorAntrean memanggil ulang dari pesan 4) */
    public function inputNomorAntrean(string $nomorAntrean): void
    {
        Jejak::catat('SEQ-05', '3', 'Pasien', 'FormInformasiAntrean', 'inputNomorAntrean(nomorAntrean)');
        $this->nomorAntrean = $nomorAntrean;
        Jejak::catat('SEQ-05', '4', 'FormInformasiAntrean', 'KontrolAntrean', 'lihatPosisiAntrean(nomorAntrean)');
        $this->kontrol->lihatPosisiAntrean($nomorAntrean);
    }

    /** SEQ-05 pesan 5 (ACT-05 alt C): pasien memperbaiki input. */
    public function perbaikiNomorAntrean(string $nomorBaru): void
    {
        Jejak::catat('SEQ-05', '5', 'Pasien', 'FormInformasiAntrean', 'perbaikiNomorAntrean(nomorBaru)');
        $this->nomorAntrean = $nomorBaru;
        Jejak::catat('SEQ-05', '4', 'FormInformasiAntrean', 'KontrolAntrean', 'lihatPosisiAntrean(nomorAntrean)');
        $this->kontrol->lihatPosisiAntrean($nomorBaru);
    }

    /** SEQ-05 pesan 4.5.1 */
    public function tampilkanInformasi(string $nomorBerjalan, int $posisi): void
    {
        Jejak::catat('SEQ-05', '4.5.1', 'FormInformasiAntrean', 'Pasien', 'tampilkanInformasi(nomorBerjalan, posisi)');
        $this->nomorBerjalan = $nomorBerjalan;
        $this->posisi = $posisi;
        $this->tampil('rincian', ['judul' => 'Posisi antrean', 'data' => ['nomorAntrean' => $this->nomorAntrean, 'nomorBerjalan' => $nomorBerjalan, 'posisi' => $posisi]]);
    }

    /** SEQ-05 pesan 4.6.1 (dan 4a untuk "antrean belum dimulai") */
    public function tampilkanPesanKesalahan(string $pesanKesalahan, string $no = '4.6.1'): void
    {
        Jejak::catat('SEQ-05', $no, 'FormInformasiAntrean', 'Pasien', 'tampilkanPesanKesalahan(pesanKesalahan)');
        $this->pesanKesalahan = $pesanKesalahan;
        $this->tampilPesan($no === '4a' ? 'info' : 'galat', $pesanKesalahan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/05') . '<label>Nomor antrean <input name="nomor" value="' . self::h($this->nomorAntrean) . '"></label><button>Lihat posisi</button></form>';
    }
}
