<?php
/**
 * File: Models/JadwalDokter.php
 * Entity JadwalDokter. UC-07/SEQ-07 pesan 4.1; UC-08/SEQ-08 pesan 7.3; UC-10/SEQ-10 pesan 9.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

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
            $m->idJadwal = (int) $r['idJadwal'];
            $m->idDokter = (int) $r['idDokter'];
            $m->hari = (string) $r['hari'];
            $m->jamPraktik = Waktu::jamDariDb((string) $r['jamPraktik']);   // DB memakai format DDL '08.00-12.00'
            $m->statusKetersediaan = (string) $r['statusKetersediaan'];
            return $m;
        }, $this->db->ambilSemua('SELECT * FROM JadwalDokter WHERE hari = ? ORDER BY jamPraktik, idDokter', [$hari]));
    }

    /** SEQ-08 pesan 7.3: ubah jadwal dokter pada hari tersebut, atau tambah bila belum ada (DDL.pdf tidak mengenal UNIQUE(idDokter,hari), jadi upsert dilakukan di sini). */
    public function simpanJadwal(int $idDokter, string $hari, string $jamPraktik, string $statusKetersediaan): bool
    {
        $ada = $this->db->ambilNilai('SELECT idJadwal FROM JadwalDokter WHERE idDokter = ? AND hari = ?', [$idDokter, $hari]);
        if ($ada !== null) {
            $this->db->jalankan('UPDATE JadwalDokter SET jamPraktik = ?, statusKetersediaan = ? WHERE idJadwal = ?', [Waktu::jamKeDb($jamPraktik), $statusKetersediaan, (int) $ada]);
            return true;
        }
        return $this->db->jalankan('INSERT INTO JadwalDokter (idDokter,hari,jamPraktik,statusKetersediaan) VALUES (?,?,?,?)',
            [$idDokter, $hari, Waktu::jamKeDb($jamPraktik), $statusKetersediaan]) === 1;
    }

    public function toArray(): array
    {
        return ['idJadwal' => $this->idJadwal, 'idDokter' => $this->idDokter, 'hari' => $this->hari,
                'jamPraktik' => $this->jamPraktik, 'statusKetersediaan' => $this->statusKetersediaan];
    }
}
