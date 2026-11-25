<?php
/**
 * File: app/Models/PendaftaranVaksinasi.php
 * Entity PendaftaranVaksinasi. UC-13 / ACT-13 / SEQ-13 pesan 15-16.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

final class PendaftaranVaksinasi extends Model
{
    private int $idPendaftaran = 0;
    private int $idPasien = 0;
    private int $idVaksin = 0;
    private string $tanggalDaftar = '';

    public function getIdPendaftaran(): int { return $this->idPendaftaran; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getIdVaksin(): int { return $this->idVaksin; }
    public function getTanggalDaftar(): string { return $this->tanggalDaftar; }

    /** Class Diagram p.118: createPendaftaran (ditambah param tanggal agar sesuai SEQ-13 pesan 15). */
    public function createPendaftaran(int $idPasien, string $tanggal, int $idVaksin): self
    {
        $this->db->jalankan('INSERT INTO pendaftaran_vaksinasi (id_pasien,id_vaksin,tanggal_daftar) VALUES (?,?,?)', [$idPasien, $idVaksin, $tanggal]);
        $m = new self($this->db);
        $m->idPendaftaran = $this->db->idTerakhir();
        $m->idPasien = $idPasien;
        $m->idVaksin = $idVaksin;
        $m->tanggalDaftar = $tanggal;
        return $m;
    }

    /** SEQ-13 pesan 15: statusTersimpan. */
    public function simpanPendaftaran(int $idPasien, string $tanggal, int $idVaksin): bool
    {
        return $this->createPendaftaran($idPasien, $tanggal, $idVaksin)->idPendaftaran > 0;
    }

    public function toArray(): array
    {
        return ['idPendaftaran' => $this->idPendaftaran, 'idPasien' => $this->idPasien, 'idVaksin' => $this->idVaksin, 'tanggalDaftar' => $this->tanggalDaftar];
    }
}
