<?php
/**
 * File: Flows/Uc12KelolaStokVaksin.php
 * UC-12 Mengelola stok vaksin | ACT-12 (header SEQ: ACT-14) | SEQ-12 | Aktor: Pengelola Klinik
 *
 * Main Flow 1-7      => pesan 1-17, 20-23 (tampil stok terbaru / sisa kuota)
 * Alternative Flow-1 => 4a: kosong / negatif / bukan angka -> 18,19; data tidak disimpan
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormStokVaksin;
use HealthPoint\Controllers\StokVaksinController;
use HealthPoint\Core\Request;

final class Uc12KelolaStokVaksin extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-12', 'act' => 'ACT-12', 'actDiHeaderSeq' => 'ACT-14', 'seq' => 'SEQ-12', 'judul' => 'Mengelola stok vaksin', 'aktor' => 'Pengelola Klinik'];
    }

    private function susun(): FormStokVaksin
    {
        $f = new FormStokVaksin();
        new StokVaksinController($f, $this->db);
        return $f;
    }

    public function jalankan(int $idVaksin, string $jumlah): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaHalamanStokVaksin();
        $f->pilihVaksin($idVaksin);
        $f->isiTambahStok($jumlah);
        $hasil = $f->klikSimpanStokVaksin();
        return $this->selesai(is_int($hasil) ? 'main' : 'alt-1', is_int($hasil), $f, ['hasil' => $hasil]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaHalamanStokVaksin();
        if ($r->adaPost()) {
            $f->pilihVaksin((int) $r->ambil('idVaksin'));
            $f->isiTambahStok($r->ambil('jumlah'));
            $f->klikSimpanStokVaksin();
        }
        return $f->renderHalaman();
    }
}
