<?php
/**
 * File: Models/Antrean.php
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
        $m->idAntrean = (int) $r['idAntrean'];
        $m->nomorAntrean = (string) $r['nomorAntrean'];
        $m->statusAntrean = (string) $r['statusAntrean'];
        $m->waktuDaftar = (string) $r['waktuDaftar'];
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
            'SELECT * FROM Antrean WHERE DATE(waktuDaftar) = ? ORDER BY waktuDaftar, idAntrean', [Waktu::hariIni()]));
    }

    /** Nomor yang sedang dilayani; null bila antrean belum dimulai. */
    public function getNomorBerjalan(): ?string
    {
        $n = $this->db->ambilNilai('SELECT nomorAntrean FROM Antrean WHERE statusAntrean = ? AND DATE(waktuDaftar) = ? ORDER BY waktuDaftar LIMIT 1',
            [self::DILAYANI, Waktu::hariIni()]);
        return $n === null ? null : (string) $n;
    }

    public function toArray(): array
    {
        return ['idAntrean' => $this->idAntrean, 'nomorAntrean' => $this->nomorAntrean, 'statusAntrean' => $this->statusAntrean, 'waktuDaftar' => $this->waktuDaftar];
    }

    /**
     * Buat antrean untuk booking: nomor urut 1,2,3... per tanggal kunjungan, sehingga otomatis kembali ke 1 setiap hari.
     * @return string|null nomor antrean, null bila gagal
     */
    public function buatUntukBooking(int $idBooking, string $tanggal): ?string
    {
        $nomor = (int) $this->db->ambilNilai(
            'SELECT COALESCE(MAX(CAST(nomorAntrean AS UNSIGNED)), 0) FROM Antrean WHERE DATE(waktuDaftar) = ?',
            [$tanggal]
        ) + 1;
        $waktu = $tanggal . ' ' . Waktu::sekarang()->format('H:i:s');
        $ok = $this->db->jalankan(
            'INSERT INTO Antrean (idBooking, nomorAntrean, statusAntrean, waktuDaftar) VALUES (?,?,?,?)',
            [$idBooking, (string) $nomor, self::MENUNGGU, $waktu]
        ) === 1;
        return $ok ? (string) $nomor : null;
    }

    /** Hapus antrean milik booking (dipakai saat booking dibatalkan). */
    public function hapusByBooking(int $idBooking): void
    {
        $this->db->jalankan('DELETE FROM Antrean WHERE idBooking = ?', [$idBooking]);
    }
}
