<?php
/**
 * File: app/Boundaries/FormPendaftaranVaksin.php
 * Boundary UC-13 / ACT-13 (header SEQ: ACT-15) / SEQ-13. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PendaftaranVaksinController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;

final class FormPendaftaranVaksin extends Boundary
{
    private ?PendaftaranVaksinController $kontrol = null;
    private int $idPasien = 0;
    private string $tanggalBooking = '';
    private int $idVaksin = 0;

    public function hubungkan(PendaftaranVaksinController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Pendaftaran Layanan Vaksinasi'; }
    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-13 pesan 1 */
    public function bukaFormPendaftaran(): void
    {
        Jejak::catat('SEQ-13', '1', 'Pasien', 'FormPendaftaranVaksin', 'bukaFormPendaftaran()');
    }

    /** SEQ-13 pesan 2-7 */
    public function pilihTanggalBooking(string $tanggal): void
    {
        Jejak::catat('SEQ-13', '2', 'Pasien', 'FormPendaftaranVaksin', 'pilihTanggalBooking(tanggal)');
        $this->tanggalBooking = $tanggal;
        Jejak::catat('SEQ-13', '3', 'FormPendaftaranVaksin', 'PendaftaranVaksinController', 'getVaksinTersedia(tanggal)');
        $this->tampilkanVaksinDanSisaKuota($this->kontrol->getVaksinTersedia($tanggal));
    }

    /** @param list<array<string,mixed>> $daftarVaksin ; vaksin berkuota 0 ditandai "Habis" (nonaktif) */
    public function tampilkanVaksinDanSisaKuota(array $daftarVaksin): void
    {
        Jejak::catat('SEQ-13', '7', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanVaksinDanSisaKuota(daftarVaksin)');
        $this->tampil('tabel', ['judul' => 'Vaksin dan sisa kuota', 'baris' => array_map(static fn (array $v): array => [
            'id' => $v['idVaksin'], 'nama' => $v['jenisVaksin'], 'status' => $v['status'], 'sisaKuota' => $v['sisaKuota']], $daftarVaksin)]);
    }

    public function pilihJenisVaksin(int $idVaksin): void
    {
        Jejak::catat('SEQ-13', '8', 'Pasien', 'FormPendaftaranVaksin', 'pilihJenisVaksin(idVaksin)');
        $this->idVaksin = $idVaksin;
    }

    /** SEQ-13 pesan 9-10 */
    public function klikSimpanPendaftaran(): int|string
    {
        Jejak::catat('SEQ-13', '9', 'Pasien', 'FormPendaftaranVaksin', 'klikSimpanPendaftaran()');
        Jejak::catat('SEQ-13', '10', 'FormPendaftaranVaksin', 'PendaftaranVaksinController', 'simpanPendaftaran(idPasien, tanggal, idVaksin)');
        return $this->kontrol->simpanPendaftaran($this->idPasien, $this->tanggalBooking, $this->idVaksin);
    }

    /** SEQ-13 pesan 14 (alt) */
    public function tampilkanPesanKuotaHabis(string $pesan): void
    {
        Jejak::catat('SEQ-13', '14', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanPesanKuotaHabis()');
        $this->tampilPesan('galat', $pesan);
    }

    /** SEQ-13 pesan 20 */
    public function tampilkanSisaKuotaTerbaru(int $sisaKuota): void
    {
        Jejak::catat('SEQ-13', '20', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanSisaKuotaTerbaru(sisaKuota)');
        $this->tampilPesan('sukses', 'Pendaftaran vaksinasi tersimpan. Sisa kuota vaksin: ' . $sisaKuota);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/13') . '<label>Tanggal booking <input type="date" name="tanggal" min="' . self::h(Waktu::hariIni()) . '" value="' . self::h($this->tanggalBooking) . '"></label>'
            . '<label>ID vaksin <input type="number" name="idVaksin" value="' . $this->idVaksin . '"></label><button>Simpan pendaftaran</button></form>';
    }
}
