<?php
/**
 * File: Flows/Uc17LaporanKuotaVaksin.php
 * UC-17 Laporan pemakaian kuota vaksin | ACT-17 (header SEQ: ACT-19) | SEQ-17 | Aktor: Pengelola Klinik
 *
 * Main Flow 1-4      => pesan 1,2,3,4,5,6,7,8
 * Alternative Flow-1 => 3a: pemakaian kosong -> 4a,4b; pilih tanggal lain (4c) -> pesan 3
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormLaporanKuotaVaksin;
use HealthPoint\Controllers\LaporanKuotaVaksinController;
use HealthPoint\Core\Request;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\PemakaianVaksin;
use HealthPoint\Models\StokVaksin;

final class Uc17LaporanKuotaVaksin extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-17', 'act' => 'ACT-17', 'actDiHeaderSeq' => 'ACT-19', 'seq' => 'SEQ-17', 'judul' => 'Laporan pemakaian kuota vaksin', 'aktor' => 'Pengelola Klinik'];
    }

    private function susun(): FormLaporanKuotaVaksin
    {
        $f = new FormLaporanKuotaVaksin();
        new LaporanKuotaVaksinController($f, $this->db);
        return $f;
    }

    public function mainFlow(string $tanggal): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanVaksin();
        $f->pilihTanggal($tanggal);
        return $this->selesai('main', $f->terakhir('rincian') !== null, $f);
    }

    public function altDataKosong(string $tanggalKosong, string $tanggalLain): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanVaksin();
        $f->pilihTanggal($tanggalKosong);
        $f->pilihTanggal($tanggalLain, true);
        return $this->selesai('alt-1', $f->terakhir('rincian') !== null, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuLaporanVaksin();
        $tanggal = $r->ambil('tanggal') !== '' ? $r->ambil('tanggal') : Waktu::hariIni();
        $f->pilihTanggal($tanggal);
        return $f->renderHalaman($f->terakhir('rincian') !== null ? $this->rincianPerVaksin($tanggal) : []);
    }

    /** Rincian per vaksin pada tanggal laporan. @return list<array{nama:string,tipe:string,terpakai:int,sisa:int}> */
    private function rincianPerVaksin(string $tanggal): array
    {
        $pakai = [];
        foreach ((new PemakaianVaksin($this->db))->getPemakaianByTanggal($tanggal) as $p) {
            $pakai[$p->getIdVaksin()] = ($pakai[$p->getIdVaksin()] ?? 0) + $p->getJumlahDosis();
        }
        $rincian = [];
        foreach ((new StokVaksin($this->db))->getDaftarStok() as $v) {
            if (isset($pakai[$v->getIdVaksin()])) {
                $rincian[] = [
                    'nama' => $v->getJenisVaksin(), 'tipe' => $v->getTipeVaksin(),
                    'terpakai' => $pakai[$v->getIdVaksin()], 'sisa' => $v->getSisaKuotaAttr(),
                ];
            }
        }
        return $rincian;
    }
}
