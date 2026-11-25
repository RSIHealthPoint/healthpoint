<?php
/**
 * File: Flows/Uc15PilihKandidat.php
 * UC-15 Memilih pasien dari daftar kandidat | ACT-15 (header SEQ: ACT-17) | SEQ-15 | Aktor: Petugas
 * Precondition: UC-14 menghasilkan >1 pasien (Extend UC-14).
 *
 * Main Flow 1-3 => pesan 1,2,3,4,5,6,7,8
 * Tidak ada Alternative Flow; pemilihan di luar daftar kandidat ditolak sebagai pengamanan.
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Core\Jejak;
use HealthPoint\Core\Request;

final class Uc15PilihKandidat extends Flow
{
    public static function info(): array
    {
        return ['uc' => 'UC-15', 'act' => 'ACT-15', 'actDiHeaderSeq' => 'ACT-17', 'seq' => 'SEQ-15', 'judul' => 'Memilih pasien dari kandidat', 'aktor' => 'Petugas'];
    }

    /** Menjalankan UC-14 (hasil ganda) lalu UC-15; jejak berisi SEQ-14 pesan 1-5 dan SEQ-15 pesan 1-8. */
    public function mainFlow(string $nama, int $idPasienDipilih): FlowResult
    {
        $this->mulai();
        [$f, $k] = (new Uc14CariRiwayatPasien($this->db, $this->pengguna))->susun();
        $f->bukaMenuPencarian();
        $f->inputPencarian($nama);
        $k->pilihPasien($idPasienDipilih);
        return $this->selesai('main', $k->terakhir('rincian') !== null, $k, ['seq15' => array_values(array_filter(Jejak::ambil(), static fn (array $b): bool => $b['seq'] === 'SEQ-15'))]);
    }

    public function web(Request $r): string
    {
        $this->mulai();
        [$f, $k] = (new Uc14CariRiwayatPasien($this->db, $this->pengguna))->susun();
        $f->bukaMenuPencarian();
        if ($r->ambil('nama') !== '') { $f->inputPencarian($r->ambil('nama')); }
        if ($r->ambil('idPasien') !== '') { $k->pilihPasien((int) $r->ambil('idPasien')); }

        $detail = $k->getDetail();
        $data = $detail ?? $f->getDataPasien();
        if ($data === null) {
            return $f->renderHalaman($k, []);
        }
        $riwayat = $this->riwayatPasien((int) $data['idPasien']);
        return $detail !== null ? $k->renderHalaman($riwayat) : $f->renderHalaman($k, $riwayat);
    }
}
