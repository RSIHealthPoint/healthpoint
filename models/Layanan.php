<?php
/**
 * File: Models/Layanan.php
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
    /** DDL.pdf tidak punya atribut 'tersedia': layanan dianggap tersedia selama barisnya ada di tabel Layanan. */

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idLayanan = (int) $r['idLayanan'];
        $m->namaLayanan = (string) $r['namaLayanan'];
        $m->deskripsi = (string) ($r['deskripsi'] ?? '');   // deskripsi nullable pada DDL
        return $m;
    }

    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getNamaLayananAttr(): string { return $this->namaLayanan; }
    public function getDeskripsi(): string { return $this->deskripsi; }
    public function isTersedia(): bool { return true; }   // objek hanya ada bila barisnya ada (lihat catatan di atas)

    /** @return list<self> SEQ-10 pesan 3 / SEQ-01 pesan 2 */
    public function getDaftarLayanan(): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua('SELECT * FROM Layanan ORDER BY idLayanan'));
    }

    public function getById(int $idLayanan): ?self
    {
        $r = $this->db->ambilSatu('SELECT * FROM Layanan WHERE idLayanan = ?', [$idLayanan]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /** SEQ-16 pesan 5 */
    public function getNamaLayanan(int $idLayanan): string
    {
        return (string) ($this->db->ambilNilai('SELECT namaLayanan FROM Layanan WHERE idLayanan = ?', [$idLayanan]) ?? '(tidak diketahui)');
    }

    /** Id layanan menurut nama (mis. 'Vaksinasi', 'Poli Umum'); null bila tidak ada. */
    public function getIdByNama(string $namaLayanan): ?int
    {
        $v = $this->db->ambilNilai('SELECT idLayanan FROM Layanan WHERE namaLayanan = ? ORDER BY idLayanan LIMIT 1', [$namaLayanan]);
        return $v === null ? null : (int) $v;
    }

    /** Id layanan pertama (cadangan bila 'Poli Umum' tidak ada); null bila tabel kosong. */
    public function getIdPertama(): ?int
    {
        $v = $this->db->ambilNilai('SELECT idLayanan FROM Layanan ORDER BY idLayanan LIMIT 1');
        return $v === null ? null : (int) $v;
    }

    /** Layanan untuk pendaftaran vaksinasi (PendaftaranVaksinasi adalah subclass Pendaftaran yang wajib punya idLayanan). */
    public function getIdVaksinasi(): ?int
    {
        return $this->getIdByNama('Vaksinasi');
    }

    public function toArray(): array
    {
        return ['idLayanan' => $this->idLayanan, 'namaLayanan' => $this->namaLayanan, 'deskripsi' => $this->deskripsi, 'tersedia' => true];
    }
}
