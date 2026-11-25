<?php
/**
 * File: Flows/Uc08KelolaJadwalDokter.php
 * UC-08 Memperbarui jadwal dan ketersediaan dokter | ACT-08 (header SEQ: ACT-10) | SEQ-08 | Aktor: Petugas
 *
 * Main Flow 1-6      => pesan 1,2,2.1,3,4,5,6,7,7.1,7.3,7.5,7.5.1
 * Alternative Flow-1 => 4a-4c: data tidak valid -> 7.2,7.2.1; perbaikan (7.2.2) -> ulang pesan 7
 * Alternative Flow-2 => 5a-5c: gagal simpan/ >3 detik -> 7.4,7.4.1; petugas mengulangi proses
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormKelolaJadwalDokter;
use HealthPoint\Controllers\KontrolKelolaJadwal;
use HealthPoint\Core\Request;

final class Uc08KelolaJadwalDokter extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-08', 'act' => 'ACT-08', 'actDiHeaderSeq' => 'ACT-10', 'seq' => 'SEQ-08', 'judul' => 'Memperbarui jadwal dokter', 'aktor' => 'Petugas'];
    }

    private function susun(): FormKelolaJadwalDokter
    {
        $f = new FormKelolaJadwalDokter();
        new KontrolKelolaJadwal($f, $this->db);
        return $f;
    }

    public function mainFlow(int $idDokter, string $hari, string $jam, string $status): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuKelolaJadwal();
        $f->pilihDokterDanHari($idDokter, $hari);
        $f->isiJadwal($jam, $status);
        $f->klikSimpan();
        return $this->selesai('main', ($f->terakhir('pesan')['tipe'] ?? '') === 'sukses', $f);
    }

    /** Alternative Flow-1: data salah lalu diperbaiki. */
    public function altDataTidakValid(int $idDokter, string $hari, string $jamSalah, string $jamBenar, string $status): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuKelolaJadwal();
        $f->pilihDokterDanHari($idDokter, $hari);
        $f->isiJadwal($jamSalah, $status);
        $f->klikSimpan();
        $f->perbaikiData($jamBenar, $status);
        return $this->selesai('alt-1', ($f->terakhir('pesan')['tipe'] ?? '') === 'sukses', $f);
    }

    /** Alternative Flow-2: penyimpanan gagal (mis. tabel tidak dapat ditulis) -> pesan gagal. */
    public function altPenyimpananGagal(int $idDokter, string $hari, string $jam, string $status): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuKelolaJadwal();
        $f->pilihDokterDanHari($idDokter, $hari);
        $f->isiJadwal($jam, $status);
        $this->db->jalankan('ALTER TABLE JadwalDokter RENAME TO JadwalDokter_x');   // simulasi kegagalan
        try { $f->klikSimpan(); } finally { $this->db->jalankan('ALTER TABLE JadwalDokter_x RENAME TO JadwalDokter'); }
        return $this->selesai('alt-2', false, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuKelolaJadwal();
        if ($r->adaPost()) {
            $f->pilihDokterDanHari((int) $r->ambil('idDokter'), $r->ambil('hari'));
            $f->isiJadwal($r->ambil('jamMulai') . '-' . $r->ambil('jamSelesai'), $r->ambil('status'));
            $f->klikSimpan();
        }
        return $f->renderHalaman();
    }
}
