<?php
/**
 * File: views/internal/petugas/FormKelolaJadwalDokter.php
 * Boundary UC-08 / ACT-08 (header SEQ: ACT-10) / SEQ-08. Aktor: Petugas.
 * Catatan: pada Class Diagram seluruh method ditandai Private; di sini dibuat public karena dipanggil Flow/Controller.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolKelolaJadwal;
use HealthPoint\Core\Jejak;

final class FormKelolaJadwalDokter extends Boundary
{
    private ?KontrolKelolaJadwal $kontrol = null;
    private int $idDokter = 0;
    private string $hari = '';
    private string $jamPraktik = '';
    private string $statusKetersediaan = '';
    private string $pesanHasil = '';
    /** @var list<array<string,mixed>> */
    private array $daftarDokter = [];

    public function hubungkan(KontrolKelolaJadwal $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Kelola Jadwal Dokter'; }

    /** SEQ-08 pesan 1-3 */
    public function bukaMenuKelolaJadwal(): void
    {
        Jejak::catat('SEQ-08', '1', 'Petugas', 'FormKelolaJadwalDokter', 'bukaMenuKelolaJadwal()');
        Jejak::catat('SEQ-08', '2', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'getDaftarDokter()');
        $this->tampilkanFormJadwal($this->kontrol->getDaftarDokter());
    }

    /** @param list<array<string,mixed>> $daftarDokter */
    public function tampilkanFormJadwal(array $daftarDokter): void
    {
        Jejak::catat('SEQ-08', '3', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanFormJadwal(daftarDokter)');
        $this->daftarDokter = $daftarDokter;
    }

    public function pilihDokterDanHari(int $idDokter, string $hari): void
    {
        Jejak::catat('SEQ-08', '4', 'Petugas', 'FormKelolaJadwalDokter', 'pilihDokterDanHari(idDokter, hari)');
        $this->idDokter = $idDokter;
        $this->hari = $hari;
    }

    public function isiJadwal(string $jamPraktik, string $statusKetersediaan): void
    {
        Jejak::catat('SEQ-08', '5', 'Petugas', 'FormKelolaJadwalDokter', 'isiJadwal(jamPraktik, statusKetersediaan)');
        $this->jamPraktik = $jamPraktik;
        $this->statusKetersediaan = $statusKetersediaan;
    }

    /** SEQ-08 pesan 6-7 */
    public function klikSimpan(): string
    {
        Jejak::catat('SEQ-08', '6', 'Petugas', 'FormKelolaJadwalDokter', 'klikSimpan()');
        Jejak::catat('SEQ-08', '7', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'simpanJadwal(idDokter, hari, jamPraktik, statusKetersediaan)');
        return $this->kontrol->simpanJadwal($this->idDokter, $this->hari, $this->jamPraktik, $this->statusKetersediaan);
    }

    /** SEQ-08 pesan 7.2.2 (ACT-08 alt D): petugas memperbaiki data lalu mengulang pesan 7. */
    public function perbaikiData(string $jamPraktik, string $statusKetersediaan): string
    {
        Jejak::catat('SEQ-08', '7.2.2', 'Petugas', 'FormKelolaJadwalDokter', 'perbaikiData(jamPraktik, statusKetersediaan)');
        $this->jamPraktik = $jamPraktik;
        $this->statusKetersediaan = $statusKetersediaan;
        Jejak::catat('SEQ-08', '7', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'simpanJadwal(idDokter, hari, jamPraktik, statusKetersediaan)');
        return $this->kontrol->simpanJadwal($this->idDokter, $this->hari, $this->jamPraktik, $this->statusKetersediaan);
    }

    public function tampilkanPesanKesalahan(string $p): void { Jejak::catat('SEQ-08', '7.2.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanKesalahan(pesanKesalahan)'); $this->pesanHasil = $p; $this->tampilPesan('galat', $p); }
    public function tampilkanPesanGagal(string $p): void { Jejak::catat('SEQ-08', '7.4.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanGagal(pesanGagal)'); $this->pesanHasil = $p; $this->tampilPesan('galat', $p); }
    public function tampilkanPesanBerhasil(string $p): void { Jejak::catat('SEQ-08', '7.5.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanBerhasil(pesanBerhasil)'); $this->pesanHasil = $p; $this->tampilPesan('sukses', $p); }

    public function formHtml(): string
    {
        $o = '';
        foreach ($this->daftarDokter as $d) { $o .= '<option value="' . (int) $d['idDokter'] . '">' . self::h($d['namaDokter']) . '</option>'; }
        return self::formTag('/uc/08') . '<label>Dokter <select name="idDokter">' . $o . '</select></label>'
            . '<label>Hari <input name="hari" placeholder="Senin" value="' . self::h($this->hari) . '"></label>'
            . '<label>Jam praktik <input name="jamPraktik" placeholder="08:00-12:00" value="' . self::h($this->jamPraktik) . '"></label>'
            . '<label>Status <select name="status"><option>Tersedia</option><option>Tidak Tersedia</option></select></label><button>Simpan</button></form>';
    }
}
