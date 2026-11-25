<?php
/**
 * File: views/pasien/HalamanBookingSaya.php
 * Boundary UC-02 / ACT-02 / SEQ-02 (daftar booking) dan UC-03 / ACT-03 / SEQ-03 (batalkan booking).
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\BookingController;
use HealthPoint\Core\Jejak;

final class HalamanBookingSaya extends Boundary
{
    private ?BookingController $kontrol = null;
    private int $idPasien = 0;
    private int $idBookingDipilih = 0;

    public function hubungkan(BookingController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Booking Saya'; }

    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-02 pesan 1-2 */
    public function bukaMenuBookingSaya(): void
    {
        Jejak::catat('SEQ-02', '1', 'Pasien', 'HalamanBookingSaya', 'bukaMenuBookingSaya()');
        Jejak::catat('SEQ-02', '2', 'HalamanBookingSaya', 'BookingController', 'getDaftarBooking(idPasien)');
        $this->tampilkanBookingPasien($this->kontrol->getDaftarBooking($this->idPasien));
    }

    /**
     * SEQ-02 pesan 6. Alternative Flow-1 (ACT-02): daftar kosong -> "Belum ada booking aktif".
     * @param list<array<string,mixed>> $bookingPasien
     */
    public function tampilkanBookingPasien(array $bookingPasien): void
    {
        Jejak::catat('SEQ-02', '6', 'HalamanBookingSaya', 'Pasien', 'tampilkanBookingPasien(bookingPasien)');
        if ($bookingPasien === []) {
            $this->tampilPesan('info', 'Belum ada booking aktif');
            return;
        }
        $this->tampil('tabel', ['judul' => 'Booking aktif', 'baris' => $bookingPasien]);
    }

    /** SEQ-03 pesan 1 */
    public function klikBatalkan(int $idBooking): void
    {
        Jejak::catat('SEQ-03', '1', 'Pasien', 'HalamanBookingSaya', 'klikBatalkan()');
        $this->idBookingDipilih = $idBooking;
        $this->tampilkanKonfirmasi();
    }

    /** SEQ-03 pesan 2 */
    public function tampilkanKonfirmasi(): void
    {
        Jejak::catat('SEQ-03', '2', 'HalamanBookingSaya', 'Pasien', 'tampilkanKonfirmasi()');
        $this->tampilPesan('info', 'Yakin membatalkan booking #' . $this->idBookingDipilih . '?');
    }

    /** SEQ-03 pesan 3-4 */
    public function konfirmasiPembatalan(): string
    {
        Jejak::catat('SEQ-03', '3', 'Pasien', 'HalamanBookingSaya', 'konfirmasiPembatalan()');
        Jejak::catat('SEQ-03', '4', 'HalamanBookingSaya', 'BookingController', 'batalkanBooking(idBooking)');
        return $this->kontrol->batalkanBooking($this->idBookingDipilih);
    }

    /** SEQ-03 pesan 10 (berhasil) / 9a (gagal) */
    public function tampilkanNotifikasi(string $notifikasi, bool $berhasil): void
    {
        Jejak::catat('SEQ-03', $berhasil ? '10' : '9a', 'HalamanBookingSaya', 'Pasien', 'tampilkanNotifikasi()');
        $this->tampilPesan($berhasil ? 'sukses' : 'galat', $notifikasi);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/03') . '<label>ID booking yang dibatalkan <input name="idBooking" type="number" min="1"></label>'
            . '<label><input type="checkbox" name="konfirmasi" value="1"> Ya, batalkan</label><button>Batalkan</button></form>';
    }
}
