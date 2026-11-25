<?php
/**
 * File: Flows/Uc10TentukanTanggalKontrol.php
 * UC-10 Menentukan tanggal kontrol | ACT-10 (header SEQ: ACT-12) | SEQ-10 | Aktor: Dokter
 *
 * Main Flow          => pesan 1..17, 20, 21 (UC-09 langkah 1-3 sudah dijalankan; Include/Extend pada UC-09)
 * Alternative Flow-1 => 3a: tanggal/jadwal tidak valid -> 18,19; dokter mengisi ulang (kembali ke pesan 7)
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormKontrol;
use HealthPoint\Controllers\KontrolController;
use HealthPoint\Core\Request;

final class Uc10TentukanTanggalKontrol extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-10', 'act' => 'ACT-10', 'actDiHeaderSeq' => 'ACT-12', 'seq' => 'SEQ-10', 'judul' => 'Menentukan tanggal kontrol', 'aktor' => 'Dokter'];
    }

    private function susun(): FormKontrol
    {
        $f = new FormKontrol();
        new KontrolController($f, $this->db);
        return $f;
    }

    public function mainFlow(string $tanggal, int $idLayanan, string $jam): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->aktifkanKontrolUlang();
        $f->pilihTanggalKontrol($tanggal);
        $f->pilihPoli($idLayanan);
        $hasil = $f->pilihJam($jam);
        return $this->selesai($hasil === 'jadwalDiterima' ? 'main' : 'alt-1', $hasil === 'jadwalDiterima', $f, ['hasil' => $hasil]);
    }

    /** Alternative Flow-1: jadwal salah, dokter mengisi ulang dengan jadwal benar. */
    public function altJadwalTidakValid(string $tanggalSalah, string $tanggalBenar, int $idLayanan, string $jam): FlowResult
    {
        $this->mulai();
        $f = $this->susun();
        $f->aktifkanKontrolUlang();
        $f->pilihTanggalKontrol($tanggalSalah);
        $f->pilihPoli($idLayanan);
        $h1 = $f->pilihJam($jam);
        $f->pilihTanggalKontrol($tanggalBenar);
        $h2 = $f->pilihJam($jam);
        return $this->selesai('alt-1', $h1 !== 'jadwalDiterima' && $h2 === 'jadwalDiterima', $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = $this->susun();
        $f->aktifkanKontrolUlang();
        if ($r->adaPost()) {
            $f->pilihTanggalKontrol($r->ambil('tanggal'));
            $f->pilihPoli((int) $r->ambil('idLayanan'));
            $f->pilihJam($r->ambil('jam'));
        }
        return $f->render();
    }
}
