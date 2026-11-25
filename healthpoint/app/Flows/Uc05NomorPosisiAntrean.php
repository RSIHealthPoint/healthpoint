<?php
/**
 * File: app/Flows/Uc05NomorPosisiAntrean.php
 * UC-05 Melihat nomor dan posisi antrean | ACT-05 (header SEQ: ACT-07) | SEQ-05 | Aktor: Pasien
 *
 * Main Flow 1-5      => pesan 1,2,3,4,4.1,4.2,4.3,4.4,4.5,4.5.1
 * Alternative Flow-1 => 3a-3c: nomor tidak ditemukan -> 4.6,4.6.1; pasien memperbaiki (5) -> kembali ke pesan 4
 * Alternative Flow-2 => 4a-4b: antrean belum dimulai (pesan info), use case selesai
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormInformasiAntrean;
use HealthPoint\Controllers\KontrolAntrean;
use HealthPoint\Core\Request;

final class Uc05NomorPosisiAntrean extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-05', 'act' => 'ACT-05', 'actDiHeaderSeq' => 'ACT-07', 'seq' => 'SEQ-05', 'judul' => 'Nomor dan posisi antrean', 'aktor' => 'Pasien'];
    }

    private function susun(): FormInformasiAntrean
    {
        $f = new FormInformasiAntrean();
        new KontrolAntrean($f, $this->db);
        return $f;
    }

    public function mainFlow(string $nomor): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuInformasiAntrean();
        $f->inputNomorAntrean($nomor);
        $ok = $f->terakhir('rincian') !== null;
        return $this->selesai($ok ? 'main' : 'alt', $ok, $f);
    }

    /** Alternative Flow-1: nomor salah lalu diperbaiki. */
    public function altNomorTidakDitemukan(string $nomorSalah, string $nomorBenar): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuInformasiAntrean();
        $f->inputNomorAntrean($nomorSalah);
        $f->perbaikiNomorAntrean($nomorBenar);
        return $this->selesai('alt-1', $f->terakhir('rincian') !== null, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->bukaMenuInformasiAntrean();
        if ($r->ambil('nomor') !== '') { $f->inputNomorAntrean($r->ambil('nomor')); }
        return $f->render() . $this->tautan('/uc/06', 'Lihat estimasi waktu tunggu');
    }
}
