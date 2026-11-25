<?php
/**
 * File: Controllers/KontrolEstimasi.php
 * Control UC-06 / SEQ-06. Memakai KontrolAntrean (dipakai ulang) dan entity WaktuPelayanan.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormEstimasiWaktuTunggu;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\WaktuPelayanan;

final class KontrolEstimasi extends Controller
{
    private KontrolAntrean $kontrolAntrean;

    public function __construct(private FormEstimasiWaktuTunggu $form, Database $db)
    {
        parent::__construct($db);
        $this->kontrolAntrean = new KontrolAntrean(null, $db);
        $form->hubungkan($this);
    }

    /** SEQ-06 pesan 2-2.6.1. @return int|string estimasi menit atau pesan */
    public function lihatEstimasi(string $nomorAntrean): int|string
    {
        Jejak::catat('SEQ-06', '2.1', 'KontrolEstimasi', 'KontrolAntrean', 'getJumlahDiDepan(nomorAntrean)');
        $jumlahDiDepan = $this->kontrolAntrean->getJumlahDiDepan($nomorAntrean);
        if ($jumlahDiDepan === null) {
            return $this->pesan('Nomor antrean tidak ditemukan atau sudah selesai');
        }
        Jejak::catat('SEQ-06', '2.2', 'KontrolEstimasi', 'WaktuPelayanan', 'getRataRataWaktuPelayanan()');
        $rata = (new WaktuPelayanan($this->db))->getRataRataWaktuPelayanan();
        if ($rata === null) {
            return $this->pesan('Estimasi belum dapat dihitung');                  // UC-06 Alt-1
        }
        if ($jumlahDiDepan === 0) {                                                 // UC-06 Alt-2: urutan pertama
            Jejak::catat('SEQ-06', '2.4', 'KontrolEstimasi', 'FormEstimasiWaktuTunggu', 'kirimEstimasi(estimasiMenit = 0)');
            $this->form->tampilkanEstimasi(0, '2.4.1');
            return 0;
        }
        Jejak::catat('SEQ-06', '2.5', 'KontrolEstimasi', 'KontrolEstimasi', 'hitungEstimasi(jumlahDiDepan, rataRataWaktu)');
        $menit = $this->hitungEstimasi($jumlahDiDepan, $rata);
        Jejak::catat('SEQ-06', '2.6', 'KontrolEstimasi', 'FormEstimasiWaktuTunggu', 'kirimEstimasi(estimasiMenit)');
        $this->form->tampilkanEstimasi($menit, '2.6.1');
        return $menit;
    }

    public function hitungEstimasi(int $jumlahDiDepan, int $rataRataWaktu): int
    {
        return $jumlahDiDepan * $rataRataWaktu;
    }

    private function pesan(string $teks): string
    {
        Jejak::catat('SEQ-06', '2.3', 'KontrolEstimasi', 'FormEstimasiWaktuTunggu', 'kirimPesan(pesan)');
        $this->form->tampilkanPesan($teks);
        return $teks;
    }
}
