<?php
/**
 * File: app/Models/Layanan.php
 * Entity Layanan. Class Diagram: FT (SEQ-01), AN (SEQ-10), AZ (SEQ-16).
 * Dipakai oleh: UC-01/ACT-01/SEQ-01, UC-10/ACT-10/SEQ-10, UC-16/ACT-16/SEQ-16.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class Layanan extends Model
{
    private int $idLayanan = 0;
    private string $namaLayanan = '';
    private string $deskripsi = '';
    private bool $tersedia = true; // tambahan: dasar Alternative Flow-1 UC-01

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idLayanan = (int) $r['id_layanan'];
        $m->namaLayanan = (string) $r['nama_layanan'];
        $m->deskripsi = (string) $r['deskripsi'];
        $m->tersedia = (bool) $r['tersedia'];
        return $m;
    }

    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getNamaLayananAttr(): string { return $this->namaLayanan; }
    public function getDeskripsi(): string { return $this->deskripsi; }
    public function isTersedia(): bool { return $this->tersedia; }

    /** @return list<self> SEQ-10 pesan 3 / SEQ-01 pesan 2 */
    public function getDaftarLayanan(): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua('SELECT * FROM layanan ORDER BY id_layanan'));
    }

    public function getById(int $idLayanan): ?self
    {
        $r = $this->db->ambilSatu('SELECT * FROM layanan WHERE id_layanan = ?', [$idLayanan]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /** SEQ-16 pesan 5 */
    public function getNamaLayanan(int $idLayanan): string
    {
        return (string) ($this->db->ambilNilai('SELECT nama_layanan FROM layanan WHERE id_layanan = ?', [$idLayanan]) ?? '(tidak diketahui)');
    }

    public function toArray(): array
    {
        return ['idLayanan' => $this->idLayanan, 'namaLayanan' => $this->namaLayanan, 'deskripsi' => $this->deskripsi, 'tersedia' => $this->tersedia];
    }
}
