<?php
/**
 * File: Controllers/KontrolController.php
 * Control UC-10 / SEQ-10. Entitas: Layanan, JadwalDokter, Booking.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormKontrol;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\Booking;
use HealthPoint\Models\JadwalDokter;
use HealthPoint\Models\Layanan;
use HealthPoint\Models\Model;

final class KontrolController extends Controller
{
    public function __construct(private FormKontrol $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-10 pesan 3-5 (hanya poli yang tersedia) */
    public function getDaftarPoli(): array
    {
        Jejak::catat('SEQ-10', '3', 'KontrolController', 'Layanan', 'getDaftarLayanan()');
        $daftar = array_values(array_filter((new Layanan($this->db))->getDaftarLayanan(), static fn (Layanan $l): bool => $l->isTersedia()));
        Jejak::catat('SEQ-10', '4', 'Layanan', 'KontrolController', 'return daftarPoli');
        Jejak::catat('SEQ-10', '5', 'KontrolController', 'FormKontrol', 'return daftarPoli');
        return Model::daftarKeArray($daftar);
    }

    /** @return list<array<string,mixed>> SEQ-10 pesan 9-11: hari diturunkan dari tanggal; hanya dokter berstatus Tersedia */
    public function getDaftarJam(string $tanggal): array
    {
        if (!Waktu::tanggalValid($tanggal)) { return []; }
        Jejak::catat('SEQ-10', '9', 'KontrolController', 'JadwalDokter', 'getJadwalByHari(hari)');
        $daftar = array_values(array_filter((new JadwalDokter($this->db))->getJadwalByHari(Waktu::namaHari($tanggal)),
            static fn (JadwalDokter $j): bool => $j->getStatusKetersediaan() === JadwalDokter::STATUS_TERSEDIA));
        Jejak::catat('SEQ-10', '10', 'JadwalDokter', 'KontrolController', 'return daftarJam');
        Jejak::catat('SEQ-10', '11', 'KontrolController', 'FormKontrol', 'return daftarJam');
        return Model::daftarKeArray($daftar);
    }

    /** SEQ-10 pesan 15-21. @return string 'jadwalDiterima' atau pesan jadwal tidak valid */
    public function validasiJadwalKontrol(string $tanggal, int $idLayanan, string $jam): string
    {
        Jejak::catat('SEQ-10', '16', 'KontrolController', 'Booking', 'cekKetersediaanJadwal(tanggal, idLayanan, jam)');
        $valid = (new Booking($this->db))->cekKetersediaanJadwal($tanggal, $idLayanan, $jam);
        Jejak::catat('SEQ-10', '17', 'Booking', 'KontrolController', 'return isValid');
        if (!$valid) {
            $p = 'Jadwal kontrol tidak valid.';
            Jejak::catat('SEQ-10', '18', 'KontrolController', 'FormKontrol', 'pesanJadwalTidakValid(alt: false)');
            $this->form->tampilkanPesanDanMintaIsiUlang($p);
            return $p;
        }
        Jejak::catat('SEQ-10', '20', 'KontrolController', 'FormKontrol', 'return jadwalDiterima');
        $this->form->tampilkanJadwalKontrol($tanggal, $idLayanan, $jam);
        return 'jadwalDiterima';
    }
}
