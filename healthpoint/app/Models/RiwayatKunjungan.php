<?php
/**
 * File: app/Models/RiwayatKunjungan.php
 * Entity RiwayatKunjungan. UC-09/SEQ-09 pesan 13; UC-11/SEQ-11 pesan 3 & 11; UC-14/SEQ-14 pesan 6; UC-15/SEQ-15 pesan 6.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

final class RiwayatKunjungan extends Model
{
    private int $idKunjungan = 0;
    private int $idPasien = 0;
    private string $tanggalKunjungan = '';
    private string $layanan = '';
    private string $ringkasan = '';
    private string $keluhanUtama = '';
    private string $tekananDarah = '';
    private int $nadi = 0;
    private float $suhu = 0.0;
    private int $spo2 = 0;
    private ?string $diagnosis = null;
    private ?string $tindakan = null;

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idKunjungan = (int) $r['id_kunjungan'];
        $m->idPasien = (int) $r['id_pasien'];
        $m->tanggalKunjungan = (string) $r['tanggal_kunjungan'];
        $m->layanan = (string) $r['layanan'];
        $m->ringkasan = (string) $r['ringkasan'];
        $m->keluhanUtama = (string) $r['keluhan_utama'];
        $m->tekananDarah = (string) $r['tekanan_darah'];
        $m->nadi = (int) $r['nadi'];
        $m->suhu = (float) $r['suhu'];
        $m->spo2 = (int) $r['spo2'];
        $m->diagnosis = $r['diagnosis'] === null ? null : (string) $r['diagnosis'];
        $m->tindakan = $r['tindakan'] === null ? null : (string) $r['tindakan'];
        return $m;
    }

    public function getIdKunjungan(): int { return $this->idKunjungan; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getTanggalKunjungan(): string { return $this->tanggalKunjungan; }
    public function getLayanan(): string { return $this->layanan; }
    public function getRingkasan(): string { return $this->ringkasan; }
    public function getKeluhanUtama(): string { return $this->keluhanUtama; }
    public function getDiagnosis(): ?string { return $this->diagnosis; }
    public function getTindakan(): ?string { return $this->tindakan; }

    /** Kunjungan aktif = kunjungan terbaru pasien yang diagnosisnya belum diisi. */
    public function getKunjunganAktif(int $idPasien): ?self
    {
        $r = $this->db->ambilSatu('SELECT * FROM riwayat_kunjungan WHERE id_pasien = ? AND diagnosis IS NULL ORDER BY tanggal_kunjungan DESC, id_kunjungan DESC LIMIT 1', [$idPasien]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /** SEQ-11 pesan 3: diagnosis dan tindakan wajib terisi. */
    public function cekKelengkapan(string $diagnosis, string $tindakan): bool
    {
        return trim($diagnosis) !== '' && trim($tindakan) !== '';
    }

    /** SEQ-11 pesan 11: mengisi kunjungan aktif; jika belum ada, membuat baris kunjungan baru hari ini. */
    public function simpanHasil(int $idPasien, string $diagnosis, string $tindakan): bool
    {
        $aktif = $this->getKunjunganAktif($idPasien);
        if ($aktif !== null) {
            return $this->db->jalankan('UPDATE riwayat_kunjungan SET diagnosis = ?, tindakan = ? WHERE id_kunjungan = ?',
                [trim($diagnosis), trim($tindakan), $aktif->idKunjungan]) === 1;
        }
        return $this->db->jalankan(
            "INSERT INTO riwayat_kunjungan (id_pasien,tanggal_kunjungan,layanan,ringkasan,diagnosis,tindakan) VALUES (?,?,'Poli Umum',?,?,?)",
            [$idPasien, Waktu::hariIni(), trim($diagnosis), trim($diagnosis), trim($tindakan)]
        ) === 1;
    }

    /** SEQ-14 pesan 6 / SEQ-15 pesan 6: ringkasan tanpa detail medis (hanya tanggal, layanan, ringkasan). */
    public function getRingkasanRiwayat(int $idPasien): string
    {
        $baris = $this->db->ambilSemua('SELECT tanggal_kunjungan, layanan, ringkasan FROM riwayat_kunjungan WHERE id_pasien = ? AND diagnosis IS NOT NULL ORDER BY tanggal_kunjungan DESC, id_kunjungan DESC LIMIT 5', [$idPasien]);
        if ($baris === []) { return 'Belum ada riwayat kunjungan.'; }
        return implode('; ', array_map(static fn (array $r): string => $r['tanggal_kunjungan'] . ' - ' . $r['layanan'] . ' (' . $r['ringkasan'] . ')', $baris));
    }

    public function toArray(): array
    {
        return ['idKunjungan' => $this->idKunjungan, 'idPasien' => $this->idPasien, 'tanggalKunjungan' => $this->tanggalKunjungan,
                'layanan' => $this->layanan, 'ringkasan' => $this->ringkasan, 'keluhanUtama' => $this->keluhanUtama,
                'tekananDarah' => $this->tekananDarah, 'nadi' => $this->nadi, 'suhu' => $this->suhu, 'spo2' => $this->spo2,
                'diagnosis' => $this->diagnosis, 'tindakan' => $this->tindakan];
    }
}
