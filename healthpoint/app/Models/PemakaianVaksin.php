<?php
/**
 * File: app/Models/PemakaianVaksin.php
 * Entity PemakaianVaksin. UC-17 / ACT-17 / SEQ-17 pesan 4.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class PemakaianVaksin extends Model
{
    private int $idPemakaian = 0;
    private int $idVaksin = 0;
    private string $tanggalPemakaian = '';
    private int $jumlahDosis = 0;

    public function getIdPemakaian(): int { return $this->idPemakaian; }
    public function getIdVaksin(): int { return $this->idVaksin; }
    public function getTanggalPemakaian(): string { return $this->tanggalPemakaian; }
    public function getJumlahDosis(): int { return $this->jumlahDosis; }

    /** @return list<self> */
    public function getPemakaianByTanggal(string $tanggal): array
    {
        return array_map(function (array $r): self {
            $m = new self($this->db);
            $m->idPemakaian = (int) $r['id_pemakaian'];
            $m->idVaksin = (int) $r['id_vaksin'];
            $m->tanggalPemakaian = (string) $r['tanggal_pemakaian'];
            $m->jumlahDosis = (int) $r['jumlah_dosis'];
            return $m;
        }, $this->db->ambilSemua('SELECT * FROM pemakaian_vaksin WHERE tanggal_pemakaian = ? ORDER BY id_pemakaian', [$tanggal]));
    }

    /** Tambahan: pencatatan dosis terpakai dipicu SEQ-13 (PDF tidak menyebut jalur tulis PemakaianVaksin). */
    public function catatPemakaian(int $idVaksin, string $tanggal, int $jumlahDosis = 1): bool
    {
        return $this->db->jalankan('INSERT INTO pemakaian_vaksin (id_vaksin,tanggal_pemakaian,jumlah_dosis) VALUES (?,?,?)', [$idVaksin, $tanggal, $jumlahDosis]) === 1;
    }

    public function toArray(): array
    {
        return ['idPemakaian' => $this->idPemakaian, 'idVaksin' => $this->idVaksin, 'tanggalPemakaian' => $this->tanggalPemakaian, 'jumlahDosis' => $this->jumlahDosis];
    }
}
