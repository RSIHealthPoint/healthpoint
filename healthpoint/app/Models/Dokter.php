<?php
/**
 * File: app/Models/Dokter.php
 * Entity Dokter. SEQ-07 pesan 4.3 getNamaDokter(); SEQ-08 pesan 2.1 getDaftarDokter().
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class Dokter extends Model
{
    private int $idDokter = 0;
    private string $namaDokter = '';

    public function getIdDokter(): int { return $this->idDokter; }
    public function getNamaDokterAttr(): string { return $this->namaDokter; }

    /** @return list<self> */
    public function getDaftarDokter(): array
    {
        return array_map(function (array $r): self {
            $m = new self($this->db);
            $m->idDokter = (int) $r['id_dokter'];
            $m->namaDokter = (string) $r['nama_dokter'];
            return $m;
        }, $this->db->ambilSemua('SELECT * FROM dokter ORDER BY id_dokter'));
    }

    public function getNamaDokter(int $idDokter): ?string
    {
        $n = $this->db->ambilNilai('SELECT nama_dokter FROM dokter WHERE id_dokter = ?', [$idDokter]);
        return $n === null ? null : (string) $n;
    }

    public function ada(int $idDokter): bool
    {
        return $this->getNamaDokter($idDokter) !== null;
    }

    public function toArray(): array
    {
        return ['idDokter' => $this->idDokter, 'namaDokter' => $this->namaDokter];
    }
}
