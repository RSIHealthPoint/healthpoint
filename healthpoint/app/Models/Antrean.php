<?php
/**
 * File: app/Models/Antrean.php
 * Entity Antrean (hari ini). UC-05/ACT-05/SEQ-05 pesan 4.1-4.2; UC-06/ACT-06/SEQ-06 pesan 2.1.1.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

final class Antrean extends Model
{
    public const MENUNGGU = 'Menunggu';
    public const DILAYANI = 'Dilayani';
    public const SELESAI = 'Selesai';

    private int $idAntrean = 0;
    private string $nomorAntrean = '';
    private string $statusAntrean = self::MENUNGGU;
    private string $waktuDaftar = '';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idAntrean = (int) $r['id_antrean'];
        $m->nomorAntrean = (string) $r['nomor_antrean'];
        $m->statusAntrean = (string) $r['status_antrean'];
        $m->waktuDaftar = (string) $r['waktu_daftar'];
        return $m;
    }

    public function getIdAntrean(): int { return $this->idAntrean; }
    public function getNomorAntrean(): string { return $this->nomorAntrean; }
    public function getStatusAntrean(): string { return $this->statusAntrean; }
    public function getWaktuDaftar(): string { return $this->waktuDaftar; }

    /** @return list<self> urut berdasarkan waktu daftar */
    public function getDaftarAntrean(): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua(
            'SELECT * FROM antrean WHERE substr(waktu_daftar,1,10) = ? ORDER BY waktu_daftar, id_antrean', [Waktu::hariIni()]));
    }

    /** Nomor yang sedang dilayani; null bila antrean belum dimulai. */
    public function getNomorBerjalan(): ?string
    {
        $n = $this->db->ambilNilai('SELECT nomor_antrean FROM antrean WHERE status_antrean = ? AND substr(waktu_daftar,1,10) = ? ORDER BY waktu_daftar LIMIT 1',
            [self::DILAYANI, Waktu::hariIni()]);
        return $n === null ? null : (string) $n;
    }

    public function toArray(): array
    {
        return ['idAntrean' => $this->idAntrean, 'nomorAntrean' => $this->nomorAntrean, 'statusAntrean' => $this->statusAntrean, 'waktuDaftar' => $this->waktuDaftar];
    }
}
