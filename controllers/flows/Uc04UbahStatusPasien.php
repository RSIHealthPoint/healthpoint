<?php
/**
 * File: Flows/Uc04UbahStatusPasien.php
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
use HealthPoint\Core\Waktu;

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
        $idLayanan = (int) $r->ambil('idLayanan', '0');
        $antrean = $this->antreanSaatIni($idLayanan);
        $idPasien = (int) $r->ambil('idPasien', '0');
        if ($idPasien === 0 && $antrean !== null) { $idPasien = (int) $antrean['idPasien']; }
        $h = $this->susun($idPasien);
        if ($r->adaPost()) {
            $h->pilihMenuStatusPasien();
            $h->pilihStatusKedatangan($r->ambil('status'));
        }
        return $h->renderHalaman($antrean, $idLayanan);
    }

    /** Antrean yang sedang dilayani (atau berikutnya yang menunggu) hari ini pada layanan terpilih; layanan 0 = layanan pertama. @return array<string,mixed>|null */
    private function antreanSaatIni(int $idLayanan): ?array
    {
        return $this->db->ambilSatu(
            "SELECT a.nomorAntrean, a.statusAntrean, b.idPasien, b.idLayanan, l.namaLayanan, d.nama, d.noRM
            FROM Antrean a
            JOIN Booking b ON b.idBooking = a.idBooking
            JOIN Layanan l ON l.idLayanan = b.idLayanan
            JOIN DataPasien d ON d.idPasien = b.idPasien
            WHERE DATE(a.waktuDaftar) = ?
                AND b.idLayanan = COALESCE(NULLIF(?, 0), (SELECT MIN(idLayanan) FROM Layanan))
                AND a.statusAntrean IN ('Dilayani', 'Menunggu')
            ORDER BY (a.statusAntrean = 'Dilayani') DESC, a.waktuDaftar, a.idAntrean LIMIT 1",
            [Waktu::hariIni(), $idLayanan]
        );
    }
}
