<?php
/**
 * File: Flows/Uc14CariRiwayatPasien.php
 * UC-14 Mencari data & riwayat kunjungan pasien lama | ACT-14 (header SEQ: ACT-16) | SEQ-14 | Aktor: Petugas
 *
 * Main Flow 1-5      => pesan 1,2,3,4,5,6,7,8
 * Alternative Flow-1 => 4a: tidak ditemukan -> 5a,5b; petugas memperbaiki input (5c) -> kembali ke pesan 4
 * Hasil >1 nama      => lanjut ke UC-15 (Uc15PilihKandidat) yang memakai controller yang sama.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormDaftarKandidat;
use HealthPoint\Boundaries\FormPencarianRiwayat;
use HealthPoint\Controllers\PencarianRiwayatController;
use HealthPoint\Core\Request;

final class Uc14CariRiwayatPasien extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-14', 'act' => 'ACT-14', 'actDiHeaderSeq' => 'ACT-16', 'seq' => 'SEQ-14', 'judul' => 'Mencari data & riwayat pasien lama', 'aktor' => 'Petugas'];
    }

    /** @return array{0:FormPencarianRiwayat,1:FormDaftarKandidat} */
    public function susun(): array
    {
        $f = new FormPencarianRiwayat();
        $k = new FormDaftarKandidat();
        new PencarianRiwayatController($f, $k, $this->db);
        return [$f, $k];
    }

    public function mainFlow(string $nama, string $tanggalLahir = '', string $noRM = ''): FlowResult
    {
        $this->mulai();
        [$f] = $this->susun();
        $f->bukaMenuPencarian();
        $f->inputPencarian($nama, $tanggalLahir, $noRM);
        return $this->selesai('main', $f->terakhir('rincian') !== null, $f);
    }

    /** Alternative Flow-1: input salah lalu diperbaiki. */
    public function altTidakDitemukan(string $namaSalah, string $namaBenar, string $tanggalLahir = '', string $noRM = ''): FlowResult
    {
        $this->mulai();
        [$f] = $this->susun();
        $f->bukaMenuPencarian();
        $f->inputPencarian($namaSalah);
        $f->perbaikiInput($namaBenar, $tanggalLahir, $noRM);
        return $this->selesai('alt-1', $f->terakhir('rincian') !== null, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        [$f, $k] = $this->susun();
        $f->bukaMenuPencarian();
        if ($r->adaPost() || array_key_exists('nama', $r->query)) {
            $f->inputPencarian($r->ambil('nama'), $r->ambil('tanggalLahir'), $r->ambil('noRM'));
        }
        $data = $f->getDataPasien();
        return $f->renderHalaman($k, $data !== null ? $this->riwayatPasien((int) $data['idPasien']) : []);
    }
}
