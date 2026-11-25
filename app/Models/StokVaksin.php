<?php
/**
 * File: app/Models/StokVaksin.php
 * Entity StokVaksin. UC-12/SEQ-12, UC-13/SEQ-13, UC-17/SEQ-17.
 * jenisVaksin juga dirujuk sebagai "namaVaksin" pada Class Diagram AZ (UC-17).
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class StokVaksin extends Model
{
    private int $idVaksin = 0;
    private string $jenisVaksin = '';
    private string $tipeVaksin = '';
    private int $jumlahStok = 0;
    private int $sisaKuota = 0;

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idVaksin = (int) $r['idVaksin'];
        $m->jenisVaksin = (string) $r['jenisVaksin'];
        $m->tipeVaksin = (string) $r['tipeVaksin'];
        $m->jumlahStok = (int) $r['jumlahStok'];
        $m->sisaKuota = (int) $r['sisaKuota'];
        return $m;
    }

    public function getIdVaksin(): int { return $this->idVaksin; }
    public function getJenisVaksin(): string { return $this->jenisVaksin; }
    public function getTipeVaksin(): string { return $this->tipeVaksin; }
    public function getJumlahStokAttr(): int { return $this->jumlahStok; }
    public function getSisaKuotaAttr(): int { return $this->sisaKuota; }

    /** @return list<self> SEQ-12 pesan 3 */
    public function getDaftarStok(): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua('SELECT * FROM StokVaksin ORDER BY idVaksin'));
    }

    /** SEQ-12 pesan 10 */
    public function getJumlahStok(int $idVaksin): int
    {
        return (int) ($this->db->ambilNilai('SELECT jumlahStok FROM StokVaksin WHERE idVaksin = ?', [$idVaksin]) ?? 0);
    }

    public function ada(int $idVaksin): bool
    {
        return $this->db->ambilNilai('SELECT 1 FROM StokVaksin WHERE idVaksin = ?', [$idVaksin]) !== null;
    }

    /** SEQ-12 pesan 16: jumlah stok masuk harus bilangan bulat positif. */
    public function cekValiditasStok(int $jumlah): bool
    {
        return $jumlah > 0;
    }

    /** SEQ-12 pesan 20: menambah stok dan kuota; mengembalikan stok terbaru. */
    public function updateStok(int $idVaksin, int $jumlah): int
    {
        $this->db->jalankan('UPDATE StokVaksin SET jumlahStok = jumlahStok + ?, sisaKuota = sisaKuota + ? WHERE idVaksin = ?', [$jumlah, $jumlah, $idVaksin]);
        return $this->getJumlahStok($idVaksin);
    }

    /** SEQ-13 pesan 4. $tanggal dipertahankan untuk kesesuaian tanda tangan; kuota berlaku global. @return list<self> */
    public function getVaksinDanKuota(string $tanggal): array
    {
        return $this->getDaftarStok();
    }

    /** SEQ-13 pesan 11 */
    public function cekKuota(int $idVaksin): bool
    {
        return (int) ($this->db->ambilNilai('SELECT sisaKuota FROM StokVaksin WHERE idVaksin = ?', [$idVaksin]) ?? 0) > 0;
    }

    /** SEQ-13 pesan 17: mengurangi 1 kuota secara atomik; mengembalikan sisa kuota terbaru (-1 bila gagal). */
    public function kurangiKuota(int $idVaksin): int
    {
        $n = $this->db->jalankan('UPDATE StokVaksin SET sisaKuota = sisaKuota - 1 WHERE idVaksin = ? AND sisaKuota > 0', [$idVaksin]);
        if ($n !== 1) { return -1; }
        return (int) $this->db->ambilNilai('SELECT sisaKuota FROM StokVaksin WHERE idVaksin = ?', [$idVaksin]);
    }

    /** SEQ-17 pesan 5: total stok masuk dikurangi dosis terpakai s.d. tanggal. */
    public function getSisaKuota(string $tanggal): int
    {
        $stok = (int) $this->db->ambilNilai('SELECT COALESCE(SUM(jumlahStok),0) FROM StokVaksin');
        $pakai = (int) $this->db->ambilNilai('SELECT COALESCE(SUM(jumlahDosis),0) FROM PemakaianVaksin WHERE tanggalPemakaian <= ?', [$tanggal]);
        return max(0, $stok - $pakai);
    }

    public function toArray(): array
    {
        return ['idVaksin' => $this->idVaksin, 'jenisVaksin' => $this->jenisVaksin, 'tipeVaksin' => $this->tipeVaksin,
                'jumlahStok' => $this->jumlahStok, 'sisaKuota' => $this->sisaKuota, 'status' => $this->sisaKuota > 0 ? 'Tersedia' : 'Habis'];
    }
}
