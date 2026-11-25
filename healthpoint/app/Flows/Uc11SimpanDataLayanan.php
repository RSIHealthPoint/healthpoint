<?php
/**
 * File: app/Flows/Uc11SimpanDataLayanan.php
 * UC-11 Menyimpan data layanan | ACT-11 (header SEQ: ACT-13) | SEQ-11 | Aktor: Petugas
 *
 * Main Flow 1-5      => pesan 1,2,3,4,[7,8 bila perlu kontrol],11,12,[13,14],15,16,(17)
 * Alternative Flow-1 => 2a: validasi gagal -> 5,6; data tidak disimpan
 * Tambahan SEQ-11    => jadwal kontrol tidak valid -> 9,10; data tidak disimpan
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormLayanan;
use HealthPoint\Controllers\LayananController;
use HealthPoint\Core\Request;

final class Uc11SimpanDataLayanan extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-11', 'act' => 'ACT-11', 'actDiHeaderSeq' => 'ACT-13', 'seq' => 'SEQ-11', 'judul' => 'Menyimpan data layanan', 'aktor' => 'Petugas'];
    }

    private function susun(): FormLayanan
    {
        $f = new FormLayanan();
        new LayananController($f, $this->db);
        return $f;
    }

    public function jalankan(int $idPasien, string $diagnosis, string $tindakan, bool $perluKontrol = false, string $tanggal = '', int $idLayanan = 0, string $jam = '', bool $tutup = false): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->isiData($idPasien, $diagnosis, $tindakan, $perluKontrol, $tanggal, $idLayanan, $jam);
        $status = $f->klikSimpanDataLayanan();
        if ($status === 'berhasil' && $tutup) { $f->tutupNotifikasi(); }
        $nomor = \HealthPoint\Core\Jejak::nomor();
        $jalur = $status === 'berhasil' ? 'main' : (in_array('5', $nomor, true) ? 'alt-1' : 'alt-jadwal');
        return $this->selesai($jalur, $status === 'berhasil', $f, ['status' => $status]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        if ($r->adaPost()) {
            $f->isiData((int) $r->ambil('idPasien'), $r->ambil('diagnosis'), $r->ambil('tindakan'), $r->ambil('perluKontrol') === '1',
                $r->ambil('tanggal'), (int) $r->ambil('idLayanan'), $r->ambil('jam'));
            $f->klikSimpanDataLayanan();
        }
        return $f->render();
    }
}
