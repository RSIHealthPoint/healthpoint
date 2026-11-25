<?php
/**
 * File: Models/PendaftaranVaksinasi.php
 * Entity PendaftaranVaksinasi (subclass dari Pendaftaran). UC-13 / ACT-13 / SEQ-13 pesan 15-16.
 *
 * Sesuai DDL.pdf: tabel PendaftaranVaksinasi hanya memuat idPendaftaran (PK sekaligus FK ke Pendaftaran) dan idVaksin (FK ke StokVaksin).
 * idPasien, idLayanan, tanggalDaftar, statusKedatangan disimpan pada baris Pendaftaran induknya (idLayanan = layanan 'Vaksinasi').
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use RuntimeException;

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

    /** Class Diagram p.118: createPendaftaran (ditambah param tanggal agar sesuai SEQ-13 pesan 15). Membuat baris Pendaftaran + PendaftaranVaksinasi. */
    public function createPendaftaran(int $idPasien, string $tanggal, int $idVaksin): self
    {
        $idLayanan = (new Layanan($this->db))->getIdVaksinasi();
        if ($idLayanan === null) { throw new RuntimeException("Layanan 'Vaksinasi' belum ada pada tabel Layanan."); }
        $idPendaftaran = (new Pendaftaran($this->db))->buatPendaftaran($idPasien, $tanggal, $idLayanan);
        $this->db->jalankan('INSERT INTO PendaftaranVaksinasi (idPendaftaran,idVaksin) VALUES (?,?)', [$idPendaftaran, $idVaksin]);
        $m = new self($this->db);
        $m->idPendaftaran = $idPendaftaran;
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
