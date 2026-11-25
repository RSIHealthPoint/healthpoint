<?php
/**
 * File: Flows/Uc01DaftarLayanan.php
 * UC-01 Mendaftar ke layanan yang dibutuhkan | ACT-01 | SEQ-01 | Aktor: Pasien
 *
 * Main Flow 1-7        => SEQ pesan 1,2,3,4,5,6,7
 * Alternative Flow-1   => layanan tidak tersedia: pesan 1,2,3,4,6 lalu pasien memilih layanan lain (ulang dari pesan 3)
 * Alternative Flow-2   => data tidak lengkap: pesan 1,2,3,4,6 lalu pasien melengkapi data (ulang dari pesan 3)
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormBookingLayanan;
use HealthPoint\Controllers\PendaftaranController;
use HealthPoint\Core\Request;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\Layanan;

final class Uc01DaftarLayanan extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-01', 'act' => 'ACT-01', 'actDiHeaderSeq' => 'UC-01', 'seq' => 'SEQ-01', 'judul' => 'Mendaftar ke layanan', 'aktor' => 'Pasien'];
    }

    private function susun(): FormBookingLayanan
    {
        $form = new FormBookingLayanan();
        new PendaftaranController($form, $this->db);
        return $form;
    }

    public function mainFlow(int $idLayanan, string $tanggal, string $keluhan = ''): FlowResult
    {
        $this->mulai();
        $form = $this->susun();
        $form->pilihMenuPendaftaran();                                  // 1-2 (ACT-01 A,B)
        $form->masukkanDataPasien($this->idPasienLogin(), $tanggal, $keluhan);
        $form->pilihLayanan($idLayanan);                                // 3  (ACT-01 C)
        $status = $form->konfirmasiPendaftaran();                       // 4-7 (ACT-01 D)
        return $this->selesai('main', $status === 'berhasil', $form, ['status' => $status]);
    }

    /** Alternative Flow-1 lalu pasien memilih layanan lain. */
    public function altLayananTidakTersedia(int $idLayananTakTersedia, int $idLayananLain, string $tanggal): FlowResult
    {
        $this->mulai();
        $form = $this->susun();
        $form->pilihMenuPendaftaran();
        $form->masukkanDataPasien($this->idPasienLogin(), $tanggal);
        $form->pilihLayanan($idLayananTakTersedia);
        $s1 = $form->konfirmasiPendaftaran();                           // 6: pesan "layanan tidak tersedia"
        $form->pilihLayanan($idLayananLain);                            // kembali ke pesan 3
        $s2 = $form->konfirmasiPendaftaran();
        return $this->selesai('alt-1', $s1 === 'layanan_tidak_tersedia' && $s2 === 'berhasil', $form, ['status1' => $s1, 'status2' => $s2]);
    }

    /** Alternative Flow-2: tanggal kosong -> pesan kesalahan -> pasien melengkapi data. */
    public function altDataTidakLengkap(int $idLayanan, string $tanggalValid): FlowResult
    {
        $this->mulai();
        $form = $this->susun();
        $form->pilihMenuPendaftaran();
        $form->masukkanDataPasien($this->idPasienLogin(), '');
        $form->pilihLayanan($idLayanan);
        $s1 = $form->konfirmasiPendaftaran();
        $form->masukkanDataPasien($this->idPasienLogin(), $tanggalValid);
        $form->pilihLayanan($idLayanan);
        $s2 = $form->konfirmasiPendaftaran();
        return $this->selesai('alt-2', $s1 === 'data_tidak_lengkap' && $s2 === 'berhasil', $form, ['status1' => $s1, 'status2' => $s2]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $form = $this->susun();
        $form->pilihMenuPendaftaran();

        $idLayanan = (int) $r->ambil('idLayanan');
        if ($idLayanan === 0) {
            $l = new Layanan($this->db);
            $idLayanan = $l->getIdByNama('Poli Umum') ?? $l->getIdPertama() ?? 0;
        }
        $tanggal = $r->ambil('tanggal');
        if ($r->adaPost()) {
            $form->masukkanDataPasien($this->idPasienLogin(), $tanggal, $r->ambil('keluhan'));
            $form->pilihLayanan($idLayanan);
            $form->konfirmasiPendaftaran();
        }
        $tanggal = $tanggal !== '' ? $tanggal : Waktu::hariIni();
        return $form->renderHalaman($idLayanan, $tanggal, $this->jadwalBooking($idLayanan, $tanggal));
    }
}
