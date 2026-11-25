<?php
/**
 * File: app/Models/Pendaftaran.php
 * Entity Pendaftaran (layanan). SEQ-01 pesan 5; SEQ-16 pesan 4.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class Pendaftaran extends Model
{
    private int $idPendaftaran = 0;
    private int $idPasien = 0;
    private int $idLayanan = 0;
    private ?int $idVaksin = null;
    private string $tanggalDaftar = '';
    private string $statusKedatangan = 'Belum Hadir';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idPendaftaran = (int) $r['id_pendaftaran'];
        $m->idPasien = (int) $r['id_pasien'];
        $m->idLayanan = (int) $r['id_layanan'];
        $m->idVaksin = $r['id_vaksin'] === null ? null : (int) $r['id_vaksin'];
        $m->tanggalDaftar = (string) $r['tanggal_daftar'];
        $m->statusKedatangan = (string) $r['status_kedatangan'];
        return $m;
    }

    public function getIdPendaftaran(): int { return $this->idPendaftaran; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getIdVaksin(): ?int { return $this->idVaksin; }
    public function getTanggalDaftar(): string { return $this->tanggalDaftar; }
    public function getStatusKedatangan(): string { return $this->statusKedatangan; }

    /** SEQ-01 pesan 5: simpan(dataPendaftaran) => statusTersimpan. (Tanda tangan PDF + idLayanan.) */
    public function simpanPendaftaran(int $idPasien, string $tanggal, int $idLayanan, ?int $idVaksin = null): bool
    {
        return $this->db->jalankan(
            'INSERT INTO pendaftaran (id_pasien,id_layanan,id_vaksin,tanggal_daftar) VALUES (?,?,?,?)',
            [$idPasien, $idLayanan, $idVaksin, $tanggal]
        ) === 1;
    }

    /** @return list<self> SEQ-16 pesan 4 */
    public function getPendaftaranByTanggal(string $tanggal): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r),
            $this->db->ambilSemua('SELECT * FROM pendaftaran WHERE tanggal_daftar = ? ORDER BY id_pendaftaran', [$tanggal]));
    }

    public function toArray(): array
    {
        return ['idPendaftaran' => $this->idPendaftaran, 'idPasien' => $this->idPasien, 'idLayanan' => $this->idLayanan,
                'idVaksin' => $this->idVaksin, 'tanggalDaftar' => $this->tanggalDaftar, 'statusKedatangan' => $this->statusKedatangan];
    }
}
