<?php
/**
 * File: Flows/Uc06EstimasiWaktuTunggu.php
 * UC-06 Melihat estimasi waktu tunggu | ACT-06 (header SEQ: ACT-08) | SEQ-06 | Aktor: Pasien
 *
 * Main Flow 1-5      => pesan 1,2,2.1,2.1.1,2.2,2.5,2.6,2.6.1
 * Alternative Flow-1 => rata-rata belum ada: 2.3,2.3.1 ("Estimasi belum dapat dihitung")
 * Alternative Flow-2 => urutan pertama: 2.4,2.4.1 (0 menit)
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormEstimasiWaktuTunggu;
use HealthPoint\Controllers\KontrolEstimasi;
use HealthPoint\Core\Request;

final class Uc06EstimasiWaktuTunggu extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-06', 'act' => 'ACT-06', 'actDiHeaderSeq' => 'ACT-08', 'seq' => 'SEQ-06', 'judul' => 'Estimasi waktu tunggu', 'aktor' => 'Pasien'];
    }

    public function jalankan(string $nomor): FlowResult
    {
        $this->mulai();
        $f = new FormEstimasiWaktuTunggu();
        new KontrolEstimasi($f, $this->db);
        $f->setNomorAntrean($nomor);
        $f->bukaMenuEstimasi();
        $nomorPesan = \HealthPoint\Core\Jejak::nomor();
        $jalur = in_array('2.3', $nomorPesan, true) ? 'alt-1' : (in_array('2.4', $nomorPesan, true) ? 'alt-2' : 'main');
        return $this->selesai($jalur, $f->terakhir('rincian') !== null, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = new FormEstimasiWaktuTunggu();
        new KontrolEstimasi($f, $this->db);
        $f->setNomorAntrean($r->ambil('nomor'));
        if ($r->ambil('nomor') !== '') { $f->bukaMenuEstimasi(); }
        return $f->render();
    }
}
