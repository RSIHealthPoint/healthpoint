<?php
/**
 * File: app/Models/Booking.php
 * Entity Booking. UC-02/ACT-02/SEQ-02, UC-03/ACT-03/SEQ-03, UC-10/ACT-10/SEQ-10, UC-11/ACT-11/SEQ-11.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use DateTimeImmutable;
use HealthPoint\Core\Database;
use HealthPoint\Core\Waktu;

final class Booking extends Model
{
    public const STATUS_AKTIF = 'Aktif';
    public const STATUS_DIBATALKAN = 'Dibatalkan';
    /** Pembatalan hanya boleh sampai N jam sebelum jadwal kedatangan (UC-03 Alt-1). */
    public const BATAS_JAM_PEMBATALAN = 2;
    /** Kapasitas booking per (tanggal, layanan, jam) untuk cekKetersediaanJadwal. */
    public const KAPASITAS_PER_JAM = 4;

    private int $idBooking = 0;
    private int $idPasien = 0;
    private string $tanggalKunjungan = '';
    private string $status = self::STATUS_AKTIF;
    private int $idLayanan = 0;
    private string $jamKontrol = '';
    private string $jenisBooking = 'layanan';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idBooking = (int) $r['id_booking'];
        $m->idPasien = (int) $r['id_pasien'];
        $m->tanggalKunjungan = (string) $r['tanggal_kunjungan'];
        $m->status = (string) $r['status'];
        $m->idLayanan = (int) $r['id_layanan'];
        $m->jamKontrol = (string) $r['jam_kontrol'];
        $m->jenisBooking = (string) $r['jenis_booking'];
        return $m;
    }

    public function getIdBooking(): int { return $this->idBooking; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getTanggalKunjungan(): string { return $this->tanggalKunjungan; }
    public function getStatus(): string { return $this->status; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getJamKontrol(): string { return $this->jamKontrol; }
    public function getJenisBooking(): string { return $this->jenisBooking; }

    public function getById(int $idBooking): ?self
    {
        $r = $this->db->ambilSatu('SELECT * FROM booking WHERE id_booking = ?', [$idBooking]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /**
     * SEQ-02 pesan 3: booking aktif milik pasien yang belum lewat jadwal kedatangan.
     * @return list<self>
     */
    public function cariBookingPasien(int $idPasien): array
    {
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua(
            'SELECT * FROM booking WHERE id_pasien = ? AND status = ? AND tanggal_kunjungan >= ? ORDER BY tanggal_kunjungan, jam_kontrol',
            [$idPasien, self::STATUS_AKTIF, Waktu::hariIni()]
        ));
    }

    /** SEQ-03 pesan 5 (cekBatasWaktuPembatalan): true bila masih di dalam batas pembatalan. */
    public function cekWaktuPembatalan(int $idBooking): bool
    {
        $b = $this->getById($idBooking);
        if ($b === null || $b->status !== self::STATUS_AKTIF) { return false; }
        $jam = $b->jamKontrol !== '' ? $b->jamKontrol : '08:00';
        $jadwal = new DateTimeImmutable($b->tanggalKunjungan . ' ' . $jam);
        $batas = $jadwal->modify('-' . self::BATAS_JAM_PEMBATALAN . ' hours');
        return Waktu::sekarang() <= $batas;
    }

    public function cekBatasWaktuPembatalan(int $idBooking): bool
    {
        return $this->cekWaktuPembatalan($idBooking);
    }

    /** SEQ-03 pesan 7 */
    public function updateStatusDibatalkan(int $idBooking): bool
    {
        return $this->db->jalankan('UPDATE booking SET status = ? WHERE id_booking = ? AND status = ?',
            [self::STATUS_DIBATALKAN, $idBooking, self::STATUS_AKTIF]) === 1;
    }

    /**
     * SEQ-10 pesan 16 / SEQ-11 pesan 7: tanggal harus setelah hari ini, ada dokter berstatus Tersedia
     * pada hari tersebut yang jam praktiknya memuat jam yang dipilih, layanan tersedia, dan kapasitas belum penuh.
     */
    public function cekKetersediaanJadwal(string $tanggal, int $idLayanan, string $jam): bool
    {
        if (!Waktu::tanggalValid($tanggal) || !Waktu::jamValid($jam) || $tanggal <= Waktu::hariIni()) { return false; }
        if ((int) $this->db->ambilNilai('SELECT COUNT(*) FROM layanan WHERE id_layanan = ? AND tersedia = 1', [$idLayanan]) === 0) { return false; }
        $sesuai = false;
        foreach ($this->db->ambilSemua('SELECT jam_praktik FROM jadwal_dokter WHERE hari = ? AND status_ketersediaan = ?', [Waktu::namaHari($tanggal), 'Tersedia']) as $j) {
            [$mulai, $selesai] = explode('-', (string) $j['jam_praktik']) + [1 => ''];
            if ($jam >= $mulai && $jam < $selesai) { $sesuai = true; break; }
        }
        if (!$sesuai) { return false; }
        $terisi = (int) $this->db->ambilNilai(
            'SELECT COUNT(*) FROM booking WHERE tanggal_kunjungan = ? AND id_layanan = ? AND jam_kontrol = ? AND status = ?',
            [$tanggal, $idLayanan, $jam, self::STATUS_AKTIF]
        );
        return $terisi < self::KAPASITAS_PER_JAM;
    }

    /** SEQ-11 pesan 13 */
    public function simpanBookingKontrol(int $idPasien, string $tanggal, int $idLayanan, string $jam): bool
    {
        return $this->simpanBooking($idPasien, $tanggal, $idLayanan, $jam, 'kontrol');
    }

    /** Tambahan integrasi UC-01 -> UC-02/03 (PDF tidak merinci pembuatan Booking dari pendaftaran). */
    public function simpanBookingLayanan(int $idPasien, string $tanggal, int $idLayanan): bool
    {
        return $this->simpanBooking($idPasien, $tanggal, $idLayanan, '', 'layanan');
    }

    private function simpanBooking(int $idPasien, string $tanggal, int $idLayanan, string $jam, string $jenis): bool
    {
        return $this->db->jalankan(
            'INSERT INTO booking (id_pasien,tanggal_kunjungan,status,id_layanan,jam_kontrol,jenis_booking) VALUES (?,?,?,?,?,?)',
            [$idPasien, $tanggal, self::STATUS_AKTIF, $idLayanan, $jam, $jenis]
        ) === 1;
    }

    public function toArray(): array
    {
        return ['idBooking' => $this->idBooking, 'idPasien' => $this->idPasien, 'tanggalKunjungan' => $this->tanggalKunjungan,
                'status' => $this->status, 'idLayanan' => $this->idLayanan, 'jamKontrol' => $this->jamKontrol, 'jenisBooking' => $this->jenisBooking];
    }
}
