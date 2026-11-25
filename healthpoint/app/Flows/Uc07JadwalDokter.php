<?php
/**
 * File: app/Flows/Uc07JadwalDokter.php
 * UC-07 Melihat jadwal dan ketersediaan dokter | ACT-07 (header SEQ: ACT-09) | SEQ-07 | Aktor: Pasien
 *
 * Main Flow 1-4      => pesan 1,2,3,4,4.1,4.3,4.4,4.4.1 (diulang per dokter)
 * Alternative Flow-1 => tidak ada dokter praktik: 4.2,4.2.1 ("Jadwal tidak tersedia")
 * Alternative Flow-2 => dokter berstatus "Tidak Tersedia": tampil pada 4.4.1
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\FormJadwalDokter;
use HealthPoint\Controllers\KontrolJadwalDokter;
use HealthPoint\Core\Request;

final class Uc07JadwalDokter extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-07', 'act' => 'ACT-07', 'actDiHeaderSeq' => 'ACT-09', 'seq' => 'SEQ-07', 'judul' => 'Jadwal dan ketersediaan dokter', 'aktor' => 'Pasien'];
    }

    public function jalankan(string $hari): FlowResult
    {
        $this->mulai();
        $f = new FormJadwalDokter();
        new KontrolJadwalDokter($f, $this->db);
        $f->bukaMenuJadwalDokter();
        $f->pilihHari($hari);
        $adaJadwal = $f->terakhir('tabel') !== null;
        $tidakTersedia = false;
        foreach ((array) ($f->terakhir('tabel')['baris'] ?? []) as $b) { $tidakTersedia = $tidakTersedia || $b['status'] === 'Tidak Tersedia'; }
        return $this->selesai(!$adaJadwal ? 'alt-1' : ($tidakTersedia ? 'alt-2' : 'main'), true, $f);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        $f = new FormJadwalDokter();
        new KontrolJadwalDokter($f, $this->db);
        $f->bukaMenuJadwalDokter();
        if ($r->ambil('hari') !== '') { $f->pilihHari($r->ambil('hari')); }
        return $f->render();
    }
}
