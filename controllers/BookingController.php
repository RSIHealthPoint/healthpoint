<?php
/**
 * File: Controllers/BookingController.php
 * Control UC-02/SEQ-02 (getDaftarBooking) dan UC-03/SEQ-03 (batalkanBooking).
 * Class Diagram: atribut private idPasien : int.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\HalamanBookingSaya;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Booking;
use HealthPoint\Models\Model;
use HealthPoint\Models\Antrean;

final class BookingController extends Controller
{
    private int $idPasien;

    public function __construct(private HalamanBookingSaya $halaman, Database $db, int $idPasien)
    {
        parent::__construct($db);
        $this->idPasien = $idPasien;
        $halaman->hubungkan($this);
        $halaman->setIdPasien($idPasien);
    }

    /** @return list<array<string,mixed>> SEQ-02 pesan 2-5 */
    public function getDaftarBooking(int $idPasien): array
    {
        $this->idPasien = $idPasien;
        Jejak::catat('SEQ-02', '3', 'BookingController', 'Booking', 'cariBookingPasien(idPasien)');
        $daftar = (new Booking($this->db))->cariBookingPasien($idPasien);
        Jejak::catat('SEQ-02', '4', 'Booking', 'BookingController', 'return dataBookingPasien');
        Jejak::catat('SEQ-02', '5', 'BookingController', 'HalamanBookingSaya', 'return bookingPasien');
        return Model::daftarKeArray($daftar);
    }

    /** SEQ-03 pesan 4-10 / 8a-9a. @return string notifikasi */
    public function batalkanBooking(int $idBooking): string
    {
        $booking = new Booking($this->db);
        $milik = $booking->getById($idBooking);
        if ($milik === null || $milik->getIdPasien() !== $this->idPasien) {
            return $this->gagal('Booking tidak ditemukan');
        }
        Jejak::catat('SEQ-03', '5', 'BookingController', 'Booking', 'cekBatasWaktuPembatalan(idBooking)');
        $valid = $booking->cekWaktuPembatalan($idBooking);
        Jejak::catat('SEQ-03', '6', 'Booking', 'BookingController', 'return isValid');
        if (!$valid) {
            return $this->gagal('Booking tidak dapat dibatalkan');               // ACT-03 alt: melewati batas waktu
        }
        Jejak::catat('SEQ-03', '7', 'BookingController', 'Booking', 'updateStatusDibatalkan(idBooking)');
        $tersimpan = $booking->updateStatusDibatalkan($idBooking);
        Jejak::catat('SEQ-03', '8', 'Booking', 'BookingController', 'return statusTersimpan');
        if (!$tersimpan) {
            return $this->gagal('Booking tidak dapat dibatalkan');
        }
        (new Antrean($this->db))->hapusByBooking($idBooking);   // nomor antrean booking yang dibatalkan dilepas
        Jejak::catat('SEQ-03', '9', 'BookingController', 'HalamanBookingSaya', 'return notifikasiBerhasil');
        $n = 'Booking berhasil dibatalkan';
        $this->halaman->tampilkanNotifikasi($n, true);
        return $n;
    }

    private function gagal(string $n): string
    {
        Jejak::catat('SEQ-03', '8a', 'BookingController', 'HalamanBookingSaya', 'return notifikasiGagal');
        $this->halaman->tampilkanNotifikasi($n, false);
        return $n;
    }
}
