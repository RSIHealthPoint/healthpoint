<?php
/**
 * File: Models/Pendaftaran.php
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
    private string $tanggalDaftar = '';
    private string $statusKedatangan = 'Belum Hadir';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idPendaftaran = (int) $r['idPendaftaran'];
        $m->idPasien = (int) $r['idPasien'];
        $m->idLayanan = (int) $r['idLayanan'];
        $m->tanggalDaftar = (string) $r['tanggalDaftar'];
        $m->statusKedatangan = (string) $r['statusKedatangan'];
        return $m;
    }

    public function getIdPendaftaran(): int { return $this->idPendaftaran; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getTanggalDaftar(): string { return $this->tanggalDaftar; }
    public function getStatusKedatangan(): string { return $this->statusKedatangan; }

    /** Menyimpan pendaftaran baru (statusKedatangan awal 'Belum Hadir'); mengembalikan idPendaftaran baru. */
    public function buatPendaftaran(int $idPasien, string $tanggal, int $idLayanan): int
    {
        $this->db->jalankan(
            'INSERT INTO Pendaftaran (idPasien,idLayanan,tanggalDaftar,statusKedatangan) VALUES (?,?,?,?)',
            [$idPasien, $idLayanan, $tanggal, 'Belum Hadir']
        );
        return $this->db->idTerakhir();
    }

    /** SEQ-01 pesan 5: simpan(dataPendaftaran) => statusTersimpan. (Tanda tangan PDF + idLayanan.) */
    public function simpanPendaftaran(int $idPasien, string $tanggal, int $idLayanan): bool
    {
        return $this->buatPendaftaran($idPasien, $tanggal, $idLayanan) > 0;
    }

    /** @return list<self> SEQ-16 pesan 4 */
    public function getPendaftaranByTanggal(string $tanggal): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r),
            $this->db->ambilSemua('SELECT * FROM Pendaftaran WHERE tanggalDaftar = ? ORDER BY idPendaftaran', [$tanggal]));
    }

    public function toArray(): array
    {
        return ['idPendaftaran' => $this->idPendaftaran, 'idPasien' => $this->idPasien, 'idLayanan' => $this->idLayanan,
                'tanggalDaftar' => $this->tanggalDaftar, 'statusKedatangan' => $this->statusKedatangan];
    }
}
