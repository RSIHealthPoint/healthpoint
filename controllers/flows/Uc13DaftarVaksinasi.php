<?php
/**
 * File: Flows/Uc13DaftarVaksinasi.php
 * UC-13 Mendaftar layanan vaksinasi | ACT-13 (header SEQ: ACT-15) | SEQ-13 | Aktor: Pasien
 *
 * Main Flow 1-7      => pesan 1-12, 15-20 (kuota berkurang otomatis, sisa kuota diperbarui)
 * Alternative Flow-1 => 3a: kuota habis -> 13,14 ("Kuota vaksin habis."); pendaftaran tidak tersimpan
 * Catatan: UC menyebut stok berkurang setelah vaksinasi selesai, SEQ-13 mengurangi saat pendaftaran; kode mengikuti SEQ-13.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormPendaftaranVaksin;
use HealthPoint\Controllers\PendaftaranVaksinController;
use HealthPoint\Core\Request;
use HealthPoint\Core\Waktu;

final class Uc13DaftarVaksinasi extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-13', 'act' => 'ACT-13', 'actDiHeaderSeq' => 'ACT-15', 'seq' => 'SEQ-13', 'judul' => 'Mendaftar layanan vaksinasi', 'aktor' => 'Pasien'];
    }

    private function susun(): FormPendaftaranVaksin
    {
        $f = new FormPendaftaranVaksin();
        new PendaftaranVaksinController($f, $this->db);
        $f->setIdPasien($this->idPasienLogin());
        return $f;
    }

    public function jalankan(string $tanggal, int $idVaksin): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaFormPendaftaran();
        $f->pilihTanggalBooking($tanggal);
        $f->pilihJenisVaksin($idVaksin);
        $hasil = $f->klikSimpanPendaftaran();
        return $this->selesai(is_int($hasil) ? 'main' : 'alt-1', is_int($hasil), $f, ['hasil' => $hasil]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaFormPendaftaran();
        $tanggal = $r->ambil('tanggal') !== '' ? $r->ambil('tanggal') : Waktu::hariIni();
        $f->pilihTanggalBooking($tanggal);
        if ($r->adaPost()) {
            $f->pilihJenisVaksin((int) $r->ambil('idVaksin'));
            $f->klikSimpanPendaftaran();
            $f->pilihTanggalBooking($tanggal);   // muat ulang daftar agar sisa kuota terbaru tampil
        }
        return $f->renderHalaman($tanggal);
    }
}
