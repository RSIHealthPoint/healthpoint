<?php
/**
 * File: Flows/Uc05NomorPosisiAntrean.php
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
use HealthPoint\Boundaries\FormEstimasiWaktuTunggu;
use HealthPoint\Controllers\KontrolEstimasi;
use HealthPoint\Core\Waktu;

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
        // "A-018" dan "A018" dianggap sama
        $nomor = (string) preg_replace('/[\s-]+/', '', $r->ambil('nomor'));
        $detail = [];
        $estimasi = null;
        if ($nomor !== '') {
            $f->inputNomorAntrean($nomor);
            if ($f->getInformasi() !== null) {
                $detail = $this->detailAntrean($nomor);
                $estimasi = $this->estimasiMenit($nomor);
            }
        }
        return $f->renderHalaman($detail, $estimasi);
    }

    /** UC-06: estimasi waktu tunggu (menit) untuk nomor yang sama. */
    private function estimasiMenit(string $nomor): ?int
    {
        $e = new FormEstimasiWaktuTunggu();
        new KontrolEstimasi($e, $this->db);
        $e->setNomorAntrean($nomor);
        $e->bukaMenuEstimasi();
        return $e->getEstimasiMenit();
    }

    /** Layanan, status antrean, dan dokter jaga (dokter pertama berstatus Tersedia pada hari ini). @return array{namaLayanan:string,statusAntrean:string,dokter:string} */
    private function detailAntrean(string $nomor): array
    {
        $a = $this->db->ambilSatu(
            'SELECT l.namaLayanan, a.statusAntrean FROM Antrean a
            JOIN Booking b ON b.idBooking = a.idBooking JOIN Layanan l ON l.idLayanan = b.idLayanan
            WHERE a.nomorAntrean = ? AND DATE(a.waktuDaftar) = ? LIMIT 1',
            [$nomor, Waktu::hariIni()]
        ) ?? [];
        $dokter = $this->db->ambilNilai(
            "SELECT d.namaDokter FROM JadwalDokter j JOIN Dokter d ON d.idDokter = j.idDokter
            WHERE j.hari = ? AND j.statusKetersediaan = 'Tersedia' ORDER BY j.jamPraktik, d.idDokter LIMIT 1",
            [Waktu::namaHari(Waktu::hariIni())]
        );
        return [
            'namaLayanan' => (string) ($a['namaLayanan'] ?? 'Layanan'),
            'statusAntrean' => (string) ($a['statusAntrean'] ?? ''),
            'dokter' => $dokter === null ? '' : (string) $dokter,
        ];
    }
}
