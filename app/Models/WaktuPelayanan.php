<?php
/**
 * File: app/Models/WaktuPelayanan.php
 * Entity WaktuPelayanan. SEQ-06 pesan 2.2 getRataRataWaktuPelayanan().
 * Atribut sesuai DDL.pdf: idWaktuPelayanan, idLayanan, rataRataWaktu (DECIMAL(5,2), menit), tanggal.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

final class WaktuPelayanan extends Model
{
    private int $idWaktuPelayanan = 0;
    private int $idLayanan = 0;
    private float $rataRataWaktu = 0.0;
    private string $tanggal = '';

    public function getIdWaktuPelayanan(): int { return $this->idWaktuPelayanan; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getRataRataWaktu(): float { return $this->rataRataWaktu; }
    public function getTanggal(): string { return $this->tanggal; }

    /** Rata-rata menit seluruh layanan (dibulatkan); null bila belum ada data (UC-06 Alt-1). */
    public function getRataRataWaktuPelayanan(): ?int
    {
        $v = $this->db->ambilNilai('SELECT AVG(rataRataWaktu) FROM WaktuPelayanan');
        return $v === null ? null : (int) round((float) $v);
    }

    public function toArray(): array
    {
        return ['idWaktuPelayanan' => $this->idWaktuPelayanan, 'idLayanan' => $this->idLayanan, 'rataRataWaktu' => $this->rataRataWaktu, 'tanggal' => $this->tanggal];
    }
}
