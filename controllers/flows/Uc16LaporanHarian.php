<?php
/**
 * File: Flows/Uc16LaporanHarian.php
 * UC-16 Laporan harian jumlah pasien per layanan | ACT-16 (header SEQ: ACT-18) | SEQ-16 | Aktor: Pengelola Klinik
 *
 * Main Flow 1-4      => pesan 1,2,3,4,5,6,7,8
 * Alternative Flow-1 => 3a: data kosong -> 4a,4b ("Belum ada data pada tanggal ini"); pilih tanggal lain (4c) -> pesan 3
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormLaporanHarian;
use HealthPoint\Controllers\LaporanHarianController;
use HealthPoint\Core\Request;

final class Uc16LaporanHarian extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-16', 'act' => 'ACT-16', 'actDiHeaderSeq' => 'ACT-18', 'seq' => 'SEQ-16', 'judul' => 'Laporan harian pasien per layanan', 'aktor' => 'Pengelola Klinik'];
    }

    private function susun(): FormLaporanHarian
    {
        $f = new FormLaporanHarian();
        new LaporanHarianController($f, $this->db);
        return $f;
    }

    public function mainFlow(string $tanggal): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanHarian();
        $f->pilihTanggal($tanggal);
        return $this->selesai('main', $f->terakhir('tabel') !== null, $f);
    }

    /** Alternative Flow-1: tanggal kosong lalu memilih tanggal lain. */
    public function altDataKosong(string $tanggalKosong, string $tanggalLain): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanHarian();
        $f->pilihTanggal($tanggalKosong);
        $f->pilihTanggal($tanggalLain, true);
        return $this->selesai('alt-1', $f->terakhir('tabel') !== null, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanHarian();
        if ($r->ambil('tanggal') !== '') { $f->pilihTanggal($r->ambil('tanggal')); }
        return $f->render();
    }
}
