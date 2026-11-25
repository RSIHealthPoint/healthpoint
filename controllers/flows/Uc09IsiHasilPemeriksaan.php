<?php
/**
 * File: Flows/Uc09IsiHasilPemeriksaan.php
 * UC-09 Mengisi hasil pemeriksaan dan tindakan | ACT-09 (header SEQ: ACT-11) | SEQ-09 | Aktor: Dokter
 *
 * Main Flow 1-4 => pesan 1,2,3,4,5,6,7,8,11,12,13,14,15,16,17
 * Opsional      => 9,10 (hapus pencarian lalu form pencarian ditampilkan lagi) sebelum pesan 3 diulang.
 * UC tidak memiliki Alternative Flow; pencarian kosong menampilkan "Data pasien tidak ditemukan" pada pesan 8.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormPemeriksaan;
use HealthPoint\Controllers\PemeriksaanController;
use HealthPoint\Core\Request;

final class Uc09IsiHasilPemeriksaan extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-09', 'act' => 'ACT-09', 'actDiHeaderSeq' => 'ACT-11', 'seq' => 'SEQ-09', 'judul' => 'Mengisi hasil pemeriksaan', 'aktor' => 'Dokter'];
    }

    private function susun(): FormPemeriksaan
    {
        $f = new FormPemeriksaan();
        new PemeriksaanController($f, $this->db);
        return $f;
    }

    public function mainFlow(string $keyword, int $idPasien, string $diagnosis, string $tindakan, bool $hapusPencarianDulu = false): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaFormPemeriksaan();
        if ($hapusPencarianDulu) {
            $f->isiKeywordPasien('salah ketik');
            $f->hapusPencarian();
        }
        $f->isiKeywordPasien($keyword);
        $f->pilihPasien($idPasien);
        $f->isiDiagnosisDanTindakan($diagnosis, $tindakan);
        return $this->selesai('main', $f->terakhir('rincian') !== null, $f, ['idPasien' => $f->getIdPasien(), 'diagnosis' => $f->getDiagnosis(), 'tindakan' => $f->getTindakan()]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaFormPemeriksaan();
        $keyword = $r->ambil('keyword');
        if ($keyword !== '') {
            $f->isiKeywordPasien($keyword);
            $id = (int) $r->ambil('idPasien');
            $hasil = $f->getHasilPencarian();
            if ($id === 0 && count($hasil) === 1) { $id = (int) $hasil[0]['idPasien']; }   // hanya 1 hasil -> langsung dipilih
            if ($id > 0) { $f->pilihPasien($id); }
        }
        return $f->renderHalaman($this->daftarPoliTersedia());
    }
}
