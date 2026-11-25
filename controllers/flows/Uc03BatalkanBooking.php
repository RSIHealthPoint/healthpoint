<?php
/**
 * File: Flows/Uc03BatalkanBooking.php
 * UC-03 Membatalkan booking | ACT-03 (di header SEQ-03 tertulis ACT-05) | SEQ-03 | Aktor: Pasien
 *
 * Main Flow 1-6        => SEQ pesan 1,2,3,4,5,6,7,8,9,10
 * Alternative Flow-1   => 5a: melewati batas waktu pembatalan -> pesan 1-6, 8a, 9a ("Booking tidak dapat dibatalkan"),
 *                          tampilan kembali ke daftar booking tanpa perubahan.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\HalamanBookingSaya;
use HealthPoint\Controllers\BookingController;
use HealthPoint\Core\Request;

final class Uc03BatalkanBooking extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-03', 'act' => 'ACT-03', 'actDiHeaderSeq' => 'ACT-05', 'seq' => 'SEQ-03', 'judul' => 'Membatalkan booking', 'aktor' => 'Pasien'];
    }

    public function jalankan(int $idBooking): FlowResult
    {
        $this->mulai();
        $halaman = new HalamanBookingSaya();
        new BookingController($halaman, $this->db, $this->idPasienLogin());
        $halaman->klikBatalkan($idBooking);                 // 1-2 (ACT-03 A,B,C)
        $notif = $halaman->konfirmasiPembatalan();          // 3-10 (ACT-03 D,E,F) atau 3-6,8a,9a
        $ok = $halaman->terakhir('pesan') !== null && $halaman->terakhir('pesan')['tipe'] === 'sukses';
        return $this->selesai($ok ? 'main' : 'alt-1', $ok, $halaman, ['notifikasi' => $notif]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $halaman = new HalamanBookingSaya();
        new BookingController($halaman, $this->db, $this->idPasienLogin());
        if ($r->adaPost() && $r->ambil('konfirmasi') === '1') {
            $halaman->klikBatalkan((int) $r->ambil('idBooking'));
            $halaman->konfirmasiPembatalan();
        }
        $halaman->bukaMenuBookingSaya();
        return $halaman->renderHalaman($this->petaLayanan());
    }
}
