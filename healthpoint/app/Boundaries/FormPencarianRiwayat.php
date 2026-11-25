<?php
/**
 * File: app/Boundaries/FormPencarianRiwayat.php
 * Boundary UC-14 / ACT-14 (header SEQ: ACT-16) / SEQ-14. Aktor: Petugas.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PencarianRiwayatController;
use HealthPoint\Core\Jejak;

final class FormPencarianRiwayat extends Boundary
{
    private ?PencarianRiwayatController $kontrol = null;
    private string $nama = '';
    private string $tanggalLahir = '';
    private string $noRM = '';
    private string $pesanKesalahan = '';

    public function hubungkan(PencarianRiwayatController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Pencarian Data & Riwayat Pasien'; }

    /** SEQ-14 pesan 1-2 */
    public function bukaMenuPencarian(): void
    {
        Jejak::catat('SEQ-14', '1', 'Petugas', 'FormPencarianRiwayat', 'bukaMenuPencarian()');
        $this->tampilkanKolomPencarian();
    }

    public function tampilkanKolomPencarian(): void
    {
        Jejak::catat('SEQ-14', '2', 'FormPencarianRiwayat', 'Petugas', 'tampilkanKolomPencarian()');
    }

    /** SEQ-14 pesan 3-4 */
    public function inputPencarian(string $nama, string $tanggalLahir = '', string $noRM = ''): void
    {
        Jejak::catat('SEQ-14', '3', 'Petugas', 'FormPencarianRiwayat', 'inputPencarian(nama, tanggalLahir/noRM)');
        [$this->nama, $this->tanggalLahir, $this->noRM] = [$nama, $tanggalLahir, $noRM];
        $this->kirimPencarian();
    }

    /** SEQ-14 pesan 5c (ACT-14 alt 2): memperbaiki input lalu kembali ke pesan 4. */
    public function perbaikiInput(string $nama, string $tanggalLahir = '', string $noRM = ''): void
    {
        Jejak::catat('SEQ-14', '5c', 'Petugas', 'FormPencarianRiwayat', 'perbaikiInput(nama, tanggalLahir/noRM)');
        [$this->nama, $this->tanggalLahir, $this->noRM] = [$nama, $tanggalLahir, $noRM];
        $this->kirimPencarian();
    }

    private function kirimPencarian(): void
    {
        Jejak::catat('SEQ-14', '4', 'FormPencarianRiwayat', 'PencarianRiwayatController', 'cariPasien(nama, tanggalLahir, noRM)');
        $this->kontrol->cariPasien($this->nama, $this->tanggalLahir, $this->noRM);
    }

    /** SEQ-14 pesan 8 @param array<string,mixed> $dataPasien */
    public function tampilkanDataPasien(array $dataPasien, string $ringkasanRiwayat): void
    {
        Jejak::catat('SEQ-14', '8', 'FormPencarianRiwayat', 'Petugas', 'tampilkanDataPasien(dataPasien, ringkasanRiwayat)');
        $this->tampil('rincian', ['judul' => 'Identitas dasar', 'data' => $dataPasien]);
        $this->tampil('rincian', ['judul' => 'Ringkasan riwayat kunjungan', 'data' => ['ringkasan' => $ringkasanRiwayat]]);
    }

    /** SEQ-14 pesan 5b */
    public function tampilkanPesanKesalahan(string $pesan): void
    {
        Jejak::catat('SEQ-14', '5b', 'FormPencarianRiwayat', 'Petugas', 'tampilkanPesanKesalahan(pesan)');
        $this->pesanKesalahan = $pesan;
        $this->tampilPesan('galat', $pesan);
    }

    /** Informasi bahwa hasil >1 sehingga alur dilanjutkan ke UC-15. */
    public function tampilkanInfoKandidat(int $jumlah): void
    {
        $this->tampilPesan('info', "Ditemukan $jumlah pasien dengan nama serupa. Pilih salah satu dari daftar kandidat.");
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/14') . '<label>Nama <input name="nama" value="' . self::h($this->nama) . '"></label>'
            . '<label>Tanggal lahir <input type="date" name="tanggalLahir" value="' . self::h($this->tanggalLahir) . '"></label>'
            . '<label>No. RM <input name="noRM" value="' . self::h($this->noRM) . '"></label><button>Cari</button></form>';
    }
}
