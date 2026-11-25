<?php
/**
 * File: Flows/Uc02DaftarBookingAktif.php
 * UC-02 Melihat daftar booking aktif | ACT-02 (di header SEQ-02 tertulis ACT-04) | SEQ-02 | Aktor: Pasien
 *
 * Main Flow 1-3        => SEQ pesan 1,2,3,4,5,6
 * Alternative Flow-1   => 2a/ACT-02 alt: tidak ada booking aktif -> pesan "Belum ada booking aktif" (pesan 6)
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\HalamanBookingSaya;
use HealthPoint\Controllers\BookingController;
use HealthPoint\Core\Request;

final class Uc02DaftarBookingAktif extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-02', 'act' => 'ACT-02', 'actDiHeaderSeq' => 'ACT-04', 'seq' => 'SEQ-02', 'judul' => 'Melihat daftar booking aktif', 'aktor' => 'Pasien'];
    }

    /** Main Flow; jalur otomatis menjadi 'alt-1' bila pasien belum punya booking aktif. */
    public function jalankan(): FlowResult
    {
        $this->mulai();
        $halaman = new HalamanBookingSaya();
        new BookingController($halaman, $this->db, $this->idPasienLogin());
        $halaman->bukaMenuBookingSaya();
        $kosong = $halaman->terakhir('tabel') === null;
        return $this->selesai($kosong ? 'alt-1' : 'main', true, $halaman, ['kosong' => $kosong]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $halaman = new HalamanBookingSaya();
        new BookingController($halaman, $this->db, $this->idPasienLogin());
        $halaman->bukaMenuBookingSaya();
        return $halaman->renderHalaman($this->petaLayanan());
    }
}
