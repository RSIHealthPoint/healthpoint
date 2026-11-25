<?php
/**
 * File: app/Boundaries/FormJadwalDokter.php
 * Boundary UC-07 / ACT-07 (header SEQ: ACT-09) / SEQ-07. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolJadwalDokter;
use HealthPoint\Core\Jejak;

final class FormJadwalDokter extends Boundary
{
    private ?KontrolJadwalDokter $kontrol = null;
    private string $hari = '';
    private string $pesan = '';
    /** @var list<array<string,string>> */
    private array $baris = [];

    public function hubungkan(KontrolJadwalDokter $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Jadwal dan Ketersediaan Dokter'; }

    /** SEQ-07 pesan 1-2 */
    public function bukaMenuJadwalDokter(): void
    {
        Jejak::catat('SEQ-07', '1', 'Pasien', 'FormJadwalDokter', 'bukaMenuJadwalDokter()');
        $this->tampilkanPilihanHari();
    }

    public function tampilkanPilihanHari(): void
    {
        Jejak::catat('SEQ-07', '2', 'FormJadwalDokter', 'Pasien', 'tampilkanPilihanHari()');
        $this->tampil('pilihanHari', KontrolJadwalDokter::HARI);
    }

    /** SEQ-07 pesan 3-4 */
    public function pilihHari(string $hari): void
    {
        Jejak::catat('SEQ-07', '3', 'Pasien', 'FormJadwalDokter', 'pilihHari(hari)');
        $this->hari = $hari;
        Jejak::catat('SEQ-07', '4', 'FormJadwalDokter', 'KontrolJadwalDokter', 'lihatJadwal(hari)');
        $this->kontrol->lihatJadwal($hari);
    }

    /** SEQ-07 pesan 4.2.1 (ACT-07 alt: tidak ada dokter praktik) */
    public function tampilkanPesan(string $pesan): void
    {
        Jejak::catat('SEQ-07', '4.2.1', 'FormJadwalDokter', 'Pasien', 'tampilkanPesan(pesan)');
        $this->pesan = $pesan;
        $this->tampilPesan('info', $pesan);
    }

    /** SEQ-07 pesan 4.4.1; status "Tidak Tersedia" ditampilkan apa adanya (UC-07 Alt-2). */
    public function tampilkanJadwal(string $namaDokter, string $jamPraktik, string $statusKetersediaan): void
    {
        Jejak::catat('SEQ-07', '4.4.1', 'FormJadwalDokter', 'Pasien', 'tampilkanJadwal(namaDokter, jamPraktik, statusKetersediaan)');
        $this->baris[] = ['dokter' => $namaDokter, 'jamPraktik' => $jamPraktik, 'status' => $statusKetersediaan];
        $this->layar = array_values(array_filter($this->layar, static fn (array $l): bool => $l['jenis'] !== 'tabel'));
        $this->tampil('tabel', ['judul' => 'Jadwal hari ' . $this->hari, 'baris' => $this->baris]);
    }

    public function formHtml(): string
    {
        $o = '';
        foreach (KontrolJadwalDokter::HARI as $h) { $o .= '<option' . ($h === $this->hari ? ' selected' : '') . '>' . $h . '</option>'; }
        return self::formTag('/uc/07', 'get') . '<label>Hari <select name="hari">' . $o . '</select></label><button>Lihat jadwal</button></form>';
    }
}
