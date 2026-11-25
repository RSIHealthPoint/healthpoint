<?php
/**
 * File: views/internal/petugas/HalamanStatusPasien.php
 * Boundary UC-04 / ACT-04 (header SEQ: ACT-06) / SEQ-04. Aktor: Petugas.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\StatusPasienController;
use HealthPoint\Core\Jejak;

final class HalamanStatusPasien extends Boundary
{
    private ?StatusPasienController $kontrol = null;
    private int $idPasien = 0;
    private string $statusKedatangan = '';

    public function hubungkan(StatusPasienController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Status Kedatangan Pasien'; }
    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-04 pesan 1-2 */
    public function pilihMenuStatusPasien(): void
    {
        Jejak::catat('SEQ-04', '1', 'Petugas', 'HalamanStatusPasien', 'pilihMenuStatusPasien()');
        Jejak::catat('SEQ-04', '2', 'HalamanStatusPasien', 'StatusPasienController', 'ambilStatusPasien(idPasien)');
        $this->kontrol->ambilStatusPasien($this->idPasien);
    }

    /** SEQ-04 pesan 4 (kirim) -> 5 (tampil) */
    public function tampilkanStatusSaatIni(string $statusKedatangan): void
    {
        Jejak::catat('SEQ-04', '5', 'HalamanStatusPasien', 'Petugas', 'tampilkanStatusSaatIni(statusKedatangan)');
        $this->statusKedatangan = $statusKedatangan;
        $this->tampil('rincian', ['judul' => 'Status saat ini', 'data' => ['idPasien' => $this->idPasien, 'status' => $statusKedatangan]]);
    }

    /** SEQ-04 pesan 6-7 */
    public function pilihStatusKedatangan(string $statusBaru): void
    {
        Jejak::catat('SEQ-04', '6', 'Petugas', 'HalamanStatusPasien', 'pilihStatusKedatangan(statusBaru)');
        Jejak::catat('SEQ-04', '7', 'HalamanStatusPasien', 'StatusPasienController', 'ubahStatus(idPasien, statusBaru)');
        $this->kontrol->ubahStatus($this->idPasien, $statusBaru);
    }

    /** SEQ-04 pesan 10 */
    public function tampilkanStatusTerbaru(string $statusTerbaru): void
    {
        Jejak::catat('SEQ-04', '10', 'HalamanStatusPasien', 'Petugas', 'tampilkanStatusTerbaru(statusTerbaru)');
        $this->statusKedatangan = $statusTerbaru;
        $this->tampilPesan('sukses', 'Status terbaru pasien #' . $this->idPasien . ': ' . $statusTerbaru);
    }

    public function tampilkanGalat(string $pesan): void { $this->tampilPesan('galat', $pesan); }

    public function formHtml(): string
    {
        return self::formTag('/uc/04') . '<label>ID pasien <input type="number" name="idPasien" value="' . (int) $this->idPasien . '"></label>'
            . '<select name="status"><option>Hadir</option><option>Dilewati</option></select><button>Ubah status</button></form>';
    }
}
