<?php
/**
 * File: app/Models/Pasien.php
 * Entity Pasien (status kedatangan). UC-04 / ACT-04 / SEQ-04 pesan 3 & 8.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

final class Pasien extends Model
{
    public const STATUS_VALID = ['Hadir', 'Dilewati'];

    private int $idPasien = 0;
    private string $namaPasien = '';
    private string $statusKedatangan = '';

    public function getIdPasien(): int { return $this->idPasien; }
    public function getNamaPasien(): string { return $this->namaPasien; }

    /** SEQ-04 pesan 3: ambilStatusKedatangan -> null bila pasien tidak ada. */
    public function getStatusKedatangan(int $idPasien): ?string
    {
        $r = $this->db->ambilSatu('SELECT id_pasien, nama, status_kedatangan FROM pasien WHERE id_pasien = ?', [$idPasien]);
        if ($r === null) { return null; }
        $this->idPasien = (int) $r['id_pasien'];
        $this->namaPasien = (string) $r['nama'];
        $this->statusKedatangan = (string) $r['status_kedatangan'];
        return $this->statusKedatangan;
    }

    /** SEQ-04 pesan 8 */
    public function simpanStatusKedatangan(int $idPasien, string $statusBaru): bool
    {
        if (!in_array($statusBaru, self::STATUS_VALID, true)) { return false; }
        return $this->db->jalankan('UPDATE pasien SET status_kedatangan = ? WHERE id_pasien = ?', [$statusBaru, $idPasien]) === 1;
    }

    public function toArray(): array
    {
        return ['idPasien' => $this->idPasien, 'namaPasien' => $this->namaPasien, 'statusKedatangan' => $this->statusKedatangan];
    }
}
