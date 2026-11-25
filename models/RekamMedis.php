<?php
/**
 * File: Models/RekamMedis.php
 * Entity RekamMedis. UC-11 langkah 3 ("menyimpan riwayat pelayanan ke rekam medis"); Class Diagram AN halaman LayananController.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Waktu;

final class RekamMedis extends Model
{
    private int $idRekamMedis = 0;
    private int $idPasien = 0;
    private string $diagnosis = '';
    private string $tindakan = '';
    private string $tanggalLayanan = '';

    public function getIdRekamMedis(): int { return $this->idRekamMedis; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getDiagnosis(): string { return $this->diagnosis; }
    public function getTindakan(): string { return $this->tindakan; }
    public function getTanggalLayanan(): string { return $this->tanggalLayanan; }

    /** @param string $dataLayanan JSON {"diagnosis":"...","tindakan":"..."} (tipe String sesuai Class Diagram) */
    public function simpanRiwayatLayanan(int $idPasien, string $dataLayanan): bool
    {
        $d = json_decode($dataLayanan, true);
        if (!is_array($d) || !isset($d['diagnosis'], $d['tindakan'])) { return false; }
        return $this->db->jalankan('INSERT INTO RekamMedis (idPasien,diagnosis,tindakan,tanggalLayanan) VALUES (?,?,?,?)',
            [$idPasien, (string) $d['diagnosis'], (string) $d['tindakan'], Waktu::hariIni()]) === 1;
    }

    public function toArray(): array
    {
        return ['idRekamMedis' => $this->idRekamMedis, 'idPasien' => $this->idPasien, 'diagnosis' => $this->diagnosis,
                'tindakan' => $this->tindakan, 'tanggalLayanan' => $this->tanggalLayanan];
    }
}
