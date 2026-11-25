<?php
/**
 * File: app/Boundaries/FormDaftarKandidat.php
 * Boundary UC-15 / ACT-15 (header SEQ: ACT-17) / SEQ-15. Aktor: Petugas.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PencarianRiwayatController;
use HealthPoint\Core\Jejak;

final class FormDaftarKandidat extends Boundary
{
    private ?PencarianRiwayatController $kontrol = null;
    /** @var list<array<string,mixed>> */
    private array $daftarPasien = [];
    private int $idPasienTerpilih = 0;

    public function hubungkan(PencarianRiwayatController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Daftar Kandidat Pasien'; }

    /** SEQ-15 pesan 2 (dari kirimDaftarKandidat pesan 1) @param list<array<string,mixed>> $daftarPasien */
    public function tampilkanDaftarKandidat(array $daftarPasien): void
    {
        Jejak::catat('SEQ-15', '2', 'FormDaftarKandidat', 'Petugas', 'tampilkanDaftarKandidat(daftarPasien)');
        $this->daftarPasien = $daftarPasien;
        $this->tampil('tabel', ['judul' => 'Kandidat pasien', 'baris' => array_map(static fn (array $p): array => [
            'id' => $p['idPasien'], 'noRM' => $p['noRM'], 'nama' => $p['nama'], 'tanggalLahir' => $p['tanggalLahir'], 'alamat' => $p['alamat']], $daftarPasien)]);
    }

    /** SEQ-15 pesan 3-4 */
    public function pilihPasien(int $idPasien): void
    {
        Jejak::catat('SEQ-15', '3', 'Petugas', 'FormDaftarKandidat', 'pilihPasien(idPasien)');
        $this->idPasienTerpilih = $idPasien;
        Jejak::catat('SEQ-15', '4', 'FormDaftarKandidat', 'PencarianRiwayatController', 'getDetailPasien(idPasien)');
        $this->kontrol->getDetailPasien($idPasien);
    }

    /** SEQ-15 pesan 8 @param array<string,mixed> $dataLengkap */
    public function tampilkanDetailPasien(array $dataLengkap, string $ringkasanRiwayat): void
    {
        Jejak::catat('SEQ-15', '8', 'FormDaftarKandidat', 'Petugas', 'tampilkanDetailPasien(dataLengkap, ringkasanRiwayat)');
        $this->tampil('rincian', ['judul' => 'Data lengkap pasien terpilih', 'data' => $dataLengkap]);
        $this->tampil('rincian', ['judul' => 'Ringkasan riwayat kunjungan', 'data' => ['ringkasan' => $ringkasanRiwayat]]);
    }

    public function tampilkanGalat(string $pesan): void { $this->tampilPesan('galat', $pesan); }
    public function getIdPasienTerpilih(): int { return $this->idPasienTerpilih; }
}
