<?php
/**
 * File: Models/Pasien.php
 * Model UC-04 / ACT-04 / SEQ-04 pesan 3 & 8 (Class Diagram: Pasien{idPasien, namaPasien, statusKedatangan}).
 *
 * DDL.pdf tidak memiliki entitas/tabel "Pasien". Pemetaan ke skema DDL:
 *   idPasien, namaPasien -> DataPasien.idPasien, DataPasien.nama
 *   statusKedatangan     -> Pendaftaran.statusKedatangan pada pendaftaran TERBARU pasien (tanggalDaftar, lalu idPendaftaran terbesar)
 * Pasien yang belum pernah mendaftar dianggap 'Belum Hadir' dan statusnya belum dapat diubah (tidak ada baris Pendaftaran).
 */
declare(strict_types=1);

namespace HealthPoint\Models;

final class Pasien extends Model
{
    public const STATUS_VALID = ['Hadir', 'Dilewati'];
    public const STATUS_AWAL = 'Belum Hadir';

    private int $idPasien = 0;
    private string $namaPasien = '';
    private string $statusKedatangan = '';

    public function getIdPasien(): int { return $this->idPasien; }
    public function getNamaPasien(): string { return $this->namaPasien; }

    /** Id pendaftaran terbaru milik pasien; null bila belum pernah mendaftar. */
    private function idPendaftaranTerbaru(int $idPasien): ?int
    {
        $v = $this->db->ambilNilai('SELECT idPendaftaran FROM Pendaftaran WHERE idPasien = ? ORDER BY tanggalDaftar DESC, idPendaftaran DESC LIMIT 1', [$idPasien]);
        return $v === null ? null : (int) $v;
    }

    /** SEQ-04 pesan 3: ambilStatusKedatangan -> null bila pasien tidak ada. */
    public function getStatusKedatangan(int $idPasien): ?string
    {
        $r = $this->db->ambilSatu('SELECT idPasien, nama FROM DataPasien WHERE idPasien = ?', [$idPasien]);
        if ($r === null) { return null; }
        $this->idPasien = (int) $r['idPasien'];
        $this->namaPasien = (string) $r['nama'];
        $status = $this->db->ambilNilai('SELECT statusKedatangan FROM Pendaftaran WHERE idPasien = ? ORDER BY tanggalDaftar DESC, idPendaftaran DESC LIMIT 1', [$idPasien]);
        $this->statusKedatangan = $status === null ? self::STATUS_AWAL : (string) $status;
        return $this->statusKedatangan;
    }

    /** SEQ-04 pesan 8. False bila status tidak valid atau pasien belum punya pendaftaran. */
    public function simpanStatusKedatangan(int $idPasien, string $statusBaru): bool
    {
        if (!in_array($statusBaru, self::STATUS_VALID, true)) { return false; }
        $id = $this->idPendaftaranTerbaru($idPasien);
        if ($id === null) { return false; }
        return $this->db->jalankan('UPDATE Pendaftaran SET statusKedatangan = ? WHERE idPendaftaran = ?', [$statusBaru, $id]) === 1;
    }

    public function toArray(): array
    {
        return ['idPasien' => $this->idPasien, 'namaPasien' => $this->namaPasien, 'statusKedatangan' => $this->statusKedatangan];
    }
}
