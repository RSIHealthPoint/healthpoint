<?php
/**
 * File: app/Controllers/PendaftaranVaksinController.php
 * Control UC-13 / SEQ-13. Alt (3a): kuota habis -> pendaftaran ditolak + pesan kuota habis.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormPendaftaranVaksin;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\Layanan;
use HealthPoint\Models\Model;
use HealthPoint\Models\PemakaianVaksin;
use HealthPoint\Models\PendaftaranVaksinasi;
use HealthPoint\Models\StokVaksin;

final class PendaftaranVaksinController extends Controller
{
    public function __construct(private FormPendaftaranVaksin $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-13 pesan 3-6 */
    public function getVaksinTersedia(string $tanggal): array
    {
        Jejak::catat('SEQ-13', '4', 'PendaftaranVaksinController', 'StokVaksin', 'getVaksinDanKuota(tanggal)');
        $d = Model::daftarKeArray((new StokVaksin($this->db))->getVaksinDanKuota($tanggal));
        Jejak::catat('SEQ-13', '5', 'StokVaksin', 'PendaftaranVaksinController', 'return daftarVaksin');
        Jejak::catat('SEQ-13', '6', 'PendaftaranVaksinController', 'FormPendaftaranVaksin', 'return daftarVaksin');
        return $d;
    }

    /** SEQ-13 pesan 11-20. @return int sisaKuota | string pesanKuotaHabis */
    public function simpanPendaftaran(int $idPasien, string $tanggal, int $idVaksin): int|string
    {
        $stok = new StokVaksin($this->db);
        if (!Waktu::tanggalValid($tanggal) || $tanggal < Waktu::hariIni() || !$stok->ada($idVaksin) || $idPasien <= 0) {
            return $this->habis('Data pendaftaran tidak valid.');
        }
        if ((new Layanan($this->db))->getIdVaksinasi() === null) {
            return $this->habis('Layanan vaksinasi belum tersedia.');   // PendaftaranVaksinasi (subclass) butuh Pendaftaran berlayanan Vaksinasi
        }
        Jejak::catat('SEQ-13', '11', 'PendaftaranVaksinController', 'StokVaksin', 'cekKuota(idVaksin)');
        $tersedia = $stok->cekKuota($idVaksin);
        Jejak::catat('SEQ-13', '12', 'StokVaksin', 'PendaftaranVaksinController', 'return kuotaTersedia');
        if (!$tersedia) { return $this->habis('Kuota vaksin habis.'); }

        $this->db->mulaiTransaksi();
        try {
            Jejak::catat('SEQ-13', '15', 'PendaftaranVaksinController', 'PendaftaranVaksinasi', 'simpanPendaftaran(idPasien, tanggal, idVaksin)');
            $tersimpan = (new PendaftaranVaksinasi($this->db))->simpanPendaftaran($idPasien, $tanggal, $idVaksin);
            Jejak::catat('SEQ-13', '16', 'PendaftaranVaksinasi', 'PendaftaranVaksinController', 'return statusTersimpan');
            Jejak::catat('SEQ-13', '17', 'PendaftaranVaksinController', 'StokVaksin', 'kurangiKuota(idVaksin)');
            $sisa = $tersimpan ? $stok->kurangiKuota($idVaksin) : -1;
            Jejak::catat('SEQ-13', '18', 'StokVaksin', 'PendaftaranVaksinController', 'return sisaKuota');
            if ($sisa < 0 || !(new PemakaianVaksin($this->db))->catatPemakaian($idVaksin, $tanggal, 1)) {
                $this->db->rollback();                    // kuota diambil pendaftar lain di saat bersamaan
                return $this->habis('Kuota vaksin habis.');
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        Jejak::catat('SEQ-13', '19', 'PendaftaranVaksinController', 'FormPendaftaranVaksin', 'return sisaKuota');
        $this->form->tampilkanSisaKuotaTerbaru($sisa);
        return $sisa;
    }

    private function habis(string $pesan): string
    {
        Jejak::catat('SEQ-13', '13', 'PendaftaranVaksinController', 'FormPendaftaranVaksin', 'pesanKuotaHabis(alt: false)');
        $this->form->tampilkanPesanKuotaHabis($pesan);
        return $pesan;
    }
}
