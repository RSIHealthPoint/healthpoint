<?php
/**
 * File: app/Boundaries/FormStokVaksin.php
 * Boundary UC-12 / ACT-12 (header SEQ: ACT-14) / SEQ-12. Aktor: Pengelola Klinik.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\StokVaksinController;
use HealthPoint\Core\Jejak;

final class FormStokVaksin extends Boundary
{
    private ?StokVaksinController $kontrol = null;
    private int $idVaksin = 0;
    private string $jumlahTambah = '';

    public function hubungkan(StokVaksinController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Manajemen Stok Vaksin'; }

    /** SEQ-12 pesan 1-6 */
    public function bukaHalamanStokVaksin(): void
    {
        Jejak::catat('SEQ-12', '1', 'Pengelola Klinik', 'FormStokVaksin', 'bukaHalamanStokVaksin()');
        Jejak::catat('SEQ-12', '2', 'FormStokVaksin', 'StokVaksinController', 'getDaftarVaksin()');
        $this->tampilkanDaftarStokVaksin($this->kontrol->getDaftarVaksin());
    }

    /** @param list<array<string,mixed>> $daftarVaksin */
    public function tampilkanDaftarStokVaksin(array $daftarVaksin, string $no = '6'): void
    {
        Jejak::catat('SEQ-12', $no, 'FormStokVaksin', 'Pengelola Klinik', $no === '6' ? 'tampilkanDaftarStokVaksin(daftarVaksin)' : 'tampilkanDaftarStokTerbaru(stokTerbaru)');
        $this->layar = array_values(array_filter($this->layar, static fn (array $l): bool => $l['jenis'] !== 'tabel'));
        $this->tampil('tabel', ['judul' => 'Stok vaksin', 'baris' => array_map(static fn (array $v): array => [
            'id' => $v['idVaksin'], 'nama' => $v['jenisVaksin'], 'tipe' => $v['tipeVaksin'], 'stok' => $v['jumlahStok'], 'sisaKuota' => $v['sisaKuota'], 'status' => $v['status']], $daftarVaksin)]);
    }

    public function pilihVaksin(int $idVaksin): void
    {
        Jejak::catat('SEQ-12', '7', 'Pengelola Klinik', 'FormStokVaksin', 'pilihVaksin(idVaksin)');
        $this->idVaksin = $idVaksin;
    }

    /** SEQ-12 pesan 8-13; proyeksi hanya dihitung bila jumlah berupa bilangan bulat. */
    public function isiTambahStok(string $jumlah): void
    {
        Jejak::catat('SEQ-12', '8', 'Pengelola Klinik', 'FormStokVaksin', 'isiTambahStok(jumlah)');
        $this->jumlahTambah = $jumlah;
        if (preg_match('/^\d+$/', trim($jumlah)) === 1) {
            Jejak::catat('SEQ-12', '9', 'FormStokVaksin', 'StokVaksinController', 'hitungProyeksiKuota(idVaksin, jumlah)');
            $this->tampilkanProyeksiKuota($this->kontrol->hitungProyeksiKuota($this->idVaksin, (int) $jumlah));
        }
    }

    public function tampilkanProyeksiKuota(int $proyeksiKuota): void
    {
        Jejak::catat('SEQ-12', '13', 'FormStokVaksin', 'Pengelola Klinik', 'tampilkanProyeksiKuota(proyeksiKuota)');
        $this->tampil('proyeksi', $proyeksiKuota);
    }

    /** SEQ-12 pesan 14-15 */
    public function klikSimpanStokVaksin(): int|string
    {
        Jejak::catat('SEQ-12', '14', 'Pengelola Klinik', 'FormStokVaksin', 'klikSimpanStokVaksin()');
        Jejak::catat('SEQ-12', '15', 'FormStokVaksin', 'StokVaksinController', 'perbaruiStok(idVaksin, jumlah)');
        return $this->kontrol->perbaruiStok($this->idVaksin, $this->jumlahTambah);
    }

    /** SEQ-12 pesan 19 */
    public function tampilkanPesanKesalahan(string $pesan): void
    {
        Jejak::catat('SEQ-12', '19', 'FormStokVaksin', 'Pengelola Klinik', 'tampilkanPesanKesalahan(pesan)');
        $this->tampilPesan('galat', $pesan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/12') . '<label>ID vaksin <input type="number" name="idVaksin" value="' . $this->idVaksin . '"></label>'
            . '<label>Jumlah stok masuk <input name="jumlah" value="' . self::h($this->jumlahTambah) . '"></label><button>Simpan stok</button></form>';
    }
}
