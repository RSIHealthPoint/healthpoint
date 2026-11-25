<?php
/**
 * File: app/Controllers/LayananController.php
 * Control UC-11 / SEQ-11. Alt: data tidak lengkap (2a), jadwal kontrol tidak valid.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormLayanan;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Booking;
use HealthPoint\Models\RekamMedis;
use HealthPoint\Models\RiwayatKunjungan;

final class LayananController extends Controller
{
    public function __construct(private FormLayanan $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /**
     * SEQ-11 pesan 3-16. @return string pesanDataTidakLengkap | pesanJadwalTidakValid | statusBerhasil
     */
    public function simpanDataLayanan(int $idPasien, string $diagnosis, string $tindakan, bool $perluKontrol, string $tanggal, int $idLayanan, string $jam): string
    {
        $riwayat = new RiwayatKunjungan($this->db);
        $booking = new Booking($this->db);

        Jejak::catat('SEQ-11', '3', 'LayananController', 'RiwayatKunjungan', 'cekKelengkapan(diagnosis, tindakan)');
        $lengkap = $riwayat->cekKelengkapan($diagnosis, $tindakan) && $idPasien > 0;
        Jejak::catat('SEQ-11', '4', 'RiwayatKunjungan', 'LayananController', 'return isLengkap');
        if (!$lengkap) {
            $p = 'Data layanan belum lengkap. Data tidak disimpan.';
            Jejak::catat('SEQ-11', '5', 'LayananController', 'FormLayanan', 'pesanDataTidakLengkap(alt)');
            $this->form->tampilkanPesanKesalahan($p, '6');
            return $p;
        }
        if ($perluKontrol) {
            Jejak::catat('SEQ-11', '7', 'LayananController', 'Booking', 'cekKetersediaanJadwal(tanggal, idLayanan, jam)');
            $valid = $booking->cekKetersediaanJadwal($tanggal, $idLayanan, $jam);
            Jejak::catat('SEQ-11', '8', 'Booking', 'LayananController', 'return isValid');
            if (!$valid) {
                $p = 'Jadwal kontrol tidak valid. Data tidak disimpan.';
                Jejak::catat('SEQ-11', '9', 'LayananController', 'FormLayanan', 'pesanJadwalTidakValid(alt: tidak valid)');
                $this->form->tampilkanPesanKesalahan($p, '10');
                return $p;
            }
        }
        $this->db->mulaiTransaksi();
        try {
            Jejak::catat('SEQ-11', '11', 'LayananController', 'RiwayatKunjungan', 'simpanHasil(idPasien, diagnosis, tindakan)');
            $ok = $riwayat->simpanHasil($idPasien, $diagnosis, $tindakan);
            Jejak::catat('SEQ-11', '12', 'RiwayatKunjungan', 'LayananController', 'return statusTersimpan');
            // UC-11 langkah 3 / Class Diagram: riwayat pelayanan juga dicatat ke RekamMedis.
            $ok = $ok && (new RekamMedis($this->db))->simpanRiwayatLayanan($idPasien, (string) json_encode(['diagnosis' => trim($diagnosis), 'tindakan' => trim($tindakan)]));
            if ($ok && $perluKontrol) {
                Jejak::catat('SEQ-11', '13', 'LayananController', 'Booking', 'simpanBookingKontrol(idPasien, tanggal, idLayanan, jam)');
                $ok = $booking->simpanBookingKontrol($idPasien, $tanggal, $idLayanan, $jam);
                Jejak::catat('SEQ-11', '14', 'Booking', 'LayananController', 'return statusTersimpan');
            }
            $ok ? $this->db->commit() : $this->db->rollback();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        if (!$ok) {
            $p = 'Data layanan gagal disimpan.';
            $this->form->tampilkanPesanKesalahan($p, '10');
            return $p;
        }
        Jejak::catat('SEQ-11', '15', 'LayananController', 'FormLayanan', 'return statusBerhasil');
        $this->form->tampilkanNotifikasiBerhasil('Data layanan berhasil disimpan', 'berhasil');
        return 'berhasil';
    }
}
