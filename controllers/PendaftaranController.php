<?php
/**
 * File: Controllers/PendaftaranController.php
 * Control UC-01 / ACT-01 / SEQ-01. Alternative Flow-1: layanan tidak tersedia; Alternative Flow-2: data tidak lengkap.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormBookingLayanan;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\Booking;
use HealthPoint\Models\DataPasien;
use HealthPoint\Models\Layanan;
use HealthPoint\Models\Model;
use HealthPoint\Models\Pendaftaran;
use HealthPoint\Models\Antrean;

final class PendaftaranController extends Controller
{
    public function __construct(private FormBookingLayanan $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-01 pesan 2 */
    public function tampilkanLayanan(): array
    {
        return Model::daftarKeArray((new Layanan($this->db))->getDaftarLayanan());
    }

    /**
     * SEQ-01 pesan 4-6. @return string 'berhasil' | 'layanan_tidak_tersedia' | 'data_tidak_lengkap'
     */
    public function daftar(int $idPasien, int $idLayanan, string $tanggal, string $keluhan = ''): string
    {
        $pasien = (new DataPasien($this->db))->getById($idPasien);
        if ($pasien === null || !$pasien->lengkap() || !Waktu::tanggalValid($tanggal) || $tanggal < Waktu::hariIni()) {
            return $this->kirimStatus('data_tidak_lengkap');                       // ACT-01 alt: data tidak lengkap
        }
        if ((new Booking($this->db))->validasiTanggalBooking($tanggal) !== null) {
            return $this->kirimStatus('tanggal_tidak_tersedia');   // bukan H-1 / tidak ada dokter tersedia
        }
        $layanan = (new Layanan($this->db))->getById($idLayanan);
        if ($layanan === null || !$layanan->isTersedia()) {
            return $this->kirimStatus('layanan_tidak_tersedia');                   // ACT-01 alt: layanan tidak tersedia
        }
        Jejak::catat('SEQ-01', '5', 'PendaftaranController', 'Pendaftaran', 'simpan(dataPendaftaran)');
        $this->db->mulaiTransaksi();
        try {
            $ok = (new Pendaftaran($this->db))->simpanPendaftaran($idPasien, $tanggal, $idLayanan)
                && (new Booking($this->db))->simpanBookingLayanan($idPasien, $tanggal, $idLayanan);
            if ($ok) {
                // booking baru langsung mendapat nomor antrean (urut per hari kunjungan)
                $ok = (new Antrean($this->db))->buatUntukBooking($this->db->idTerakhir(), $tanggal) !== null;
            }
            $ok ? $this->db->commit() : $this->db->rollback();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        return $this->kirimStatus($ok ? 'berhasil' : 'data_tidak_lengkap');
    }

    private function kirimStatus(string $status): string
    {
        Jejak::catat('SEQ-01', '6', 'PendaftaranController', 'FormBookingLayanan', 'tampilkanInformasi(status)');
        $this->form->tampilkanInformasi($status);
        return $status;
    }
}
