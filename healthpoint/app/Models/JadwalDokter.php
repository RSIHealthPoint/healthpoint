<?php
/**
 * File: app/Models/JadwalDokter.php
 * Entity JadwalDokter. UC-07/SEQ-07 pesan 4.1; UC-08/SEQ-08 pesan 7.3; UC-10/SEQ-10 pesan 9.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class JadwalDokter extends Model
{
    public const STATUS_TERSEDIA = 'Tersedia';
    public const STATUS_TIDAK_TERSEDIA = 'Tidak Tersedia';

    private int $idJadwal = 0;
    private int $idDokter = 0;
    private string $hari = '';
    private string $jamPraktik = '';
    private string $statusKetersediaan = '';

    public function getIdJadwal(): int { return $this->idJadwal; }
    public function getIdDokter(): int { return $this->idDokter; }
    public function getHari(): string { return $this->hari; }
    public function getJamPraktik(): string { return $this->jamPraktik; }
    public function getStatusKetersediaan(): string { return $this->statusKetersediaan; }

    /** @return list<self> */
    public function getJadwalByHari(string $hari): array
    {
        return array_map(function (array $r): self {
            $m = new self($this->db);
            $m->idJadwal = (int) $r['id_jadwal'];
            $m->idDokter = (int) $r['id_dokter'];
            $m->hari = (string) $r['hari'];
            $m->jamPraktik = (string) $r['jam_praktik'];
            $m->statusKetersediaan = (string) $r['status_ketersediaan'];
            return $m;
        }, $this->db->ambilSemua('SELECT * FROM jadwal_dokter WHERE hari = ? ORDER BY jam_praktik, id_dokter', [$hari]));
    }

    /** SEQ-08 pesan 7.3: insert atau ubah jadwal dokter pada hari tersebut. */
    public function simpanJadwal(int $idDokter, string $hari, string $jamPraktik, string $statusKetersediaan): bool
    {
        return $this->db->jalankan(
            'INSERT INTO jadwal_dokter (id_dokter,hari,jam_praktik,status_ketersediaan) VALUES (?,?,?,?)
             ON CONFLICT(id_dokter,hari) DO UPDATE SET jam_praktik = excluded.jam_praktik, status_ketersediaan = excluded.status_ketersediaan',
            [$idDokter, $hari, $jamPraktik, $statusKetersediaan]
        ) >= 1;
    }

    public function toArray(): array
    {
        return ['idJadwal' => $this->idJadwal, 'idDokter' => $this->idDokter, 'hari' => $this->hari,
                'jamPraktik' => $this->jamPraktik, 'statusKetersediaan' => $this->statusKetersediaan];
    }
}
