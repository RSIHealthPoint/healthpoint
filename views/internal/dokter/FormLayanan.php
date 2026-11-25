<?php
/**
 * File: views/internal/dokter/FormLayanan.php
 * Boundary UC-11 / ACT-11 (header SEQ: ACT-13) / SEQ-11. Aktor: Petugas (pesan 1 menulis Dokter).
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\LayananController;
use HealthPoint\Core\Jejak;

final class FormLayanan extends Boundary
{
    private ?LayananController $kontrol = null;
    private int $idPasien = 0;
    private string $diagnosis = '';
    private string $tindakan = '';
    private bool $perluKontrol = false;
    private string $tanggal = '';
    private int $idLayanan = 0;
    private string $jam = '';

    public function hubungkan(LayananController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Simpan Data Layanan'; }

    public function isiData(int $idPasien, string $diagnosis, string $tindakan, bool $perluKontrol = false, string $tanggal = '', int $idLayanan = 0, string $jam = ''): void
    {
        [$this->idPasien, $this->diagnosis, $this->tindakan, $this->perluKontrol, $this->tanggal, $this->idLayanan, $this->jam]
            = [$idPasien, $diagnosis, $tindakan, $perluKontrol, $tanggal, $idLayanan, $jam];
    }

    /** SEQ-11 pesan 1-2 */
    public function klikSimpanDataLayanan(): string
    {
        Jejak::catat('SEQ-11', '1', 'Petugas', 'FormLayanan', 'klikSimpanDataLayanan()');
        Jejak::catat('SEQ-11', '2', 'FormLayanan', 'LayananController', 'simpanDataLayanan(idPasien, diagnosis, tindakan, perluKontrol, tanggal, idLayanan, jam)');
        return $this->kontrol->simpanDataLayanan($this->idPasien, $this->diagnosis, $this->tindakan, $this->perluKontrol, $this->tanggal, $this->idLayanan, $this->jam);
    }

    /** SEQ-11 pesan 6 dan 10 */
    public function tampilkanPesanKesalahan(string $pesan, string $no): void
    {
        Jejak::catat('SEQ-11', $no, 'FormLayanan', 'Petugas', 'tampilkanPesanKesalahan(pesan)');
        $this->tampilPesan('galat', $pesan);
    }

    /** SEQ-11 pesan 16 */
    public function tampilkanNotifikasiBerhasil(string $pesan, string $status): void
    {
        Jejak::catat('SEQ-11', '16', 'FormLayanan', 'Petugas', 'tampilkanNotifikasiBerhasil(pesan, status)');
        $this->tampilPesan('sukses', $pesan);
        $this->tampil('status', $status);
    }

    /** SEQ-11 pesan 17 (opt) */
    public function tutupNotifikasi(): void
    {
        Jejak::catat('SEQ-11', '17', 'Petugas', 'FormLayanan', 'tutupNotifikasi()');
        $this->layar = array_values(array_filter($this->layar, static fn (array $l): bool => $l['jenis'] !== 'pesan'));
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/11') . '<label>ID pasien <input type="number" name="idPasien" value="' . $this->idPasien . '"></label>'
            . '<label>Diagnosis <input name="diagnosis" value="' . self::h($this->diagnosis) . '"></label>'
            . '<label>Tindakan <input name="tindakan" value="' . self::h($this->tindakan) . '"></label>'
            . '<label><input type="checkbox" name="perluKontrol" value="1"' . ($this->perluKontrol ? ' checked' : '') . '> Perlu kontrol ulang</label>'
            . '<label>Tanggal kontrol <input type="date" name="tanggal" value="' . self::h($this->tanggal) . '"></label>'
            . '<label>Poli (ID) <input type="number" name="idLayanan" value="' . $this->idLayanan . '"></label>'
            . '<label>Jam <input name="jam" placeholder="09:00" value="' . self::h($this->jam) . '"></label><button>Simpan</button></form>';
    }
}
