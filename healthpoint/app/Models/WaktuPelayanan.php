<?php
/**
 * File: app/Models/WaktuPelayanan.php
 * Entity WaktuPelayanan. SEQ-06 pesan 2.2 getRataRataWaktuPelayanan(). (Tidak punya tabel kelas di PDF; atribut diturunkan.)
 */
declare(strict_types=1);

namespace HealthPoint\Models;

final class WaktuPelayanan extends Model
{
    private int $idWaktu = 0;
    private int $idLayanan = 0;
    private int $durasiMenit = 0;
    private string $tanggal = '';

    public function getIdWaktu(): int { return $this->idWaktu; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getDurasiMenit(): int { return $this->durasiMenit; }
    public function getTanggal(): string { return $this->tanggal; }

    /** Rata-rata menit (dibulatkan); null bila belum ada data (UC-06 Alt-1). */
    public function getRataRataWaktuPelayanan(): ?int
    {
        $v = $this->db->ambilNilai('SELECT AVG(durasi_menit) FROM waktu_pelayanan');
        return $v === null ? null : (int) round((float) $v);
    }

    public function toArray(): array
    {
        return ['idWaktu' => $this->idWaktu, 'idLayanan' => $this->idLayanan, 'durasiMenit' => $this->durasiMenit, 'tanggal' => $this->tanggal];
    }
}
