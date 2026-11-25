<?php
/**
 * File: app/Models/RiwayatKunjungan.php
 * Entity RiwayatKunjungan. UC-09/SEQ-09 pesan 13; UC-11/SEQ-11 pesan 3 & 11; UC-14/SEQ-14 pesan 6; UC-15/SEQ-15 pesan 6.
 *
 * Sesuai DDL.pdf kolom tabel: idKunjungan, idPasien, idLayanan, tanggalKunjungan, keluhanUtama, tekananDarah, nadi, suhu, spo2, diagnosis, tindakan.
 * Atribut Class Diagram yang TIDAK ada di DDL diturunkan, bukan disimpan:
 *   layanan   = Layanan.namaLayanan (JOIN lewat idLayanan)
 *   ringkasan = keluhanUtama (bukan diagnosis, agar ringkasan tetap tanpa detail medis)
 * diagnosis & tindakan NOT NULL pada DDL: kunjungan yang belum diperiksa disimpan dengan diagnosis '' (string kosong).
 * Itu penanda "kunjungan aktif"; pada objek/array nilainya muncul sebagai null seperti sebelumnya.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

final class RiwayatKunjungan extends Model
{
    /** Batas VARCHAR(255) pada DDL untuk diagnosis dan tindakan. */
    public const PANJANG_MAKS = 255;

    private int $idKunjungan = 0;
    private int $idPasien = 0;
    private int $idLayanan = 0;
    private string $tanggalKunjungan = '';
    private string $layanan = '';
    private string $keluhanUtama = '';
    private ?string $tekananDarah = null;
    private ?int $nadi = null;
    private ?float $suhu = null;
    private ?int $spo2 = null;
    private ?string $diagnosis = null;
    private ?string $tindakan = null;

    private const SELECT = 'SELECT rk.*, l.namaLayanan AS namaLayanan FROM RiwayatKunjungan rk JOIN Layanan l ON l.idLayanan = rk.idLayanan';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idKunjungan = (int) $r['idKunjungan'];
        $m->idPasien = (int) $r['idPasien'];
        $m->idLayanan = (int) $r['idLayanan'];
        $m->tanggalKunjungan = (string) $r['tanggalKunjungan'];
        $m->layanan = (string) $r['namaLayanan'];
        $m->keluhanUtama = (string) $r['keluhanUtama'];
        $m->tekananDarah = $r['tekananDarah'] === null ? null : (string) $r['tekananDarah'];
        $m->nadi = $r['nadi'] === null ? null : (int) $r['nadi'];
        $m->suhu = $r['suhu'] === null ? null : (float) $r['suhu'];
        $m->spo2 = $r['spo2'] === null ? null : (int) $r['spo2'];
        $m->diagnosis = (string) $r['diagnosis'] === '' ? null : (string) $r['diagnosis'];
        $m->tindakan = (string) $r['tindakan'] === '' ? null : (string) $r['tindakan'];
        return $m;
    }

    public function getIdKunjungan(): int { return $this->idKunjungan; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getTanggalKunjungan(): string { return $this->tanggalKunjungan; }
    public function getLayanan(): string { return $this->layanan; }
    public function getRingkasan(): string { return $this->keluhanUtama; }
    public function getKeluhanUtama(): string { return $this->keluhanUtama; }
    public function getDiagnosis(): ?string { return $this->diagnosis; }
    public function getTindakan(): ?string { return $this->tindakan; }

    /** Kunjungan aktif = kunjungan terbaru pasien yang diagnosisnya belum diisi (diagnosis = ''). */
    public function getKunjunganAktif(int $idPasien): ?self
    {
        $r = $this->db->ambilSatu(self::SELECT . " WHERE rk.idPasien = ? AND rk.diagnosis = '' ORDER BY rk.tanggalKunjungan DESC, rk.idKunjungan DESC LIMIT 1", [$idPasien]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /** SEQ-11 pesan 3: diagnosis dan tindakan wajib terisi dan muat di VARCHAR(255). */
    public function cekKelengkapan(string $diagnosis, string $tindakan): bool
    {
        $d = trim($diagnosis);
        $t = trim($tindakan);
        return $d !== '' && $t !== '' && self::panjang($d) <= self::PANJANG_MAKS && self::panjang($t) <= self::PANJANG_MAKS;
    }

    /** Jumlah karakter UTF-8 (VARCHAR menghitung karakter, bukan byte); tanpa ketergantungan ekstensi mbstring. */
    private static function panjang(string $s): int
    {
        $n = preg_match_all('/./su', $s);
        return $n === false ? PHP_INT_MAX : $n;    // bukan UTF-8 valid => dianggap tidak muat
    }

    /** SEQ-11 pesan 11: mengisi kunjungan aktif; jika belum ada, membuat baris kunjungan baru hari ini (layanan 'Poli Umum'). */
    public function simpanHasil(int $idPasien, string $diagnosis, string $tindakan): bool
    {
        $aktif = $this->getKunjunganAktif($idPasien);
        if ($aktif !== null) {
            return $this->db->jalankan('UPDATE RiwayatKunjungan SET diagnosis = ?, tindakan = ? WHERE idKunjungan = ?',
                [trim($diagnosis), trim($tindakan), $aktif->idKunjungan]) === 1;
        }
        $idLayanan = (new Layanan($this->db))->getIdByNama('Poli Umum')
            ?? ($this->db->ambilNilai('SELECT idLayanan FROM Layanan ORDER BY idLayanan LIMIT 1') === null ? null : (int) $this->db->ambilNilai('SELECT idLayanan FROM Layanan ORDER BY idLayanan LIMIT 1'));
        if ($idLayanan === null) { return false; }
        return $this->db->jalankan(
            "INSERT INTO RiwayatKunjungan (idPasien,idLayanan,tanggalKunjungan,keluhanUtama,diagnosis,tindakan) VALUES (?,?,?,'',?,?)",
            [$idPasien, $idLayanan, Waktu::hariIni(), trim($diagnosis), trim($tindakan)]
        ) === 1;
    }

    /** SEQ-14 pesan 6 / SEQ-15 pesan 6: ringkasan tanpa detail medis (hanya tanggal, layanan, keluhan utama). */
    public function getRingkasanRiwayat(int $idPasien): string
    {
        $baris = $this->db->ambilSemua(self::SELECT . " WHERE rk.idPasien = ? AND rk.diagnosis <> '' ORDER BY rk.tanggalKunjungan DESC, rk.idKunjungan DESC LIMIT 5", [$idPasien]);
        if ($baris === []) { return 'Belum ada riwayat kunjungan.'; }
        return implode('; ', array_map(static fn (array $r): string => $r['tanggalKunjungan'] . ' - ' . $r['namaLayanan'] . ' ('
            . ((string) $r['keluhanUtama'] !== '' ? $r['keluhanUtama'] : 'tanpa keluhan tercatat') . ')', $baris));
    }

    public function toArray(): array
    {
        return ['idKunjungan' => $this->idKunjungan, 'idPasien' => $this->idPasien, 'idLayanan' => $this->idLayanan,
                'tanggalKunjungan' => $this->tanggalKunjungan, 'layanan' => $this->layanan, 'ringkasan' => $this->keluhanUtama,
                'keluhanUtama' => $this->keluhanUtama, 'tekananDarah' => $this->tekananDarah, 'nadi' => $this->nadi,
                'suhu' => $this->suhu, 'spo2' => $this->spo2, 'diagnosis' => $this->diagnosis, 'tindakan' => $this->tindakan];
    }
}
