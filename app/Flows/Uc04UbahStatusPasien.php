<?php
/**
 * File: app/Flows/Uc04UbahStatusPasien.php
 * UC-04 Mengubah status pasien (hadir/dilewati) | ACT-04 (header SEQ: ACT-06) | SEQ-04 | Aktor: Petugas
 *
 * Main Flow 1-6 => SEQ pesan 1..8 (+ 9,10 diturunkan dari Class Diagram: kirimStatusTerbaru/tampilkanStatusTerbaru).
 * Tidak ada Alternative Flow pada UC; validasi status di luar {Hadir, Dilewati} ditolak sebagai pengamanan.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\HalamanStatusPasien;
use HealthPoint\Controllers\StatusPasienController;
use HealthPoint\Core\Request;

final class Uc04UbahStatusPasien extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-04', 'act' => 'ACT-04', 'actDiHeaderSeq' => 'ACT-06', 'seq' => 'SEQ-04', 'judul' => 'Mengubah status pasien', 'aktor' => 'Petugas'];
    }

    private function susun(int $idPasien): HalamanStatusPasien
    {
        $h = new HalamanStatusPasien();
        new StatusPasienController($h, $this->db);
        $h->setIdPasien($idPasien);
        return $h;
    }

    public function mainFlow(int $idPasien, string $statusBaru): FlowResult
    {
        $this->mulai();
        $h = $this->susun($idPasien);
        $h->pilihMenuStatusPasien();
        $h->pilihStatusKedatangan($statusBaru);
        $ok = $h->terakhir('pesan') !== null && $h->terakhir('pesan')['tipe'] === 'sukses';
        return $this->selesai('main', $ok, $h);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $h = $this->susun((int) $r->ambil('idPasien', '0'));
        if ($r->adaPost()) {
            $h->pilihMenuStatusPasien();
            $h->pilihStatusKedatangan($r->ambil('status'));
        }
        return $h->render();
    }
}
