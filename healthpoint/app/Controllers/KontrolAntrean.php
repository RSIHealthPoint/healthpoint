<?php
/**
 * File: app/Controllers/KontrolAntrean.php
 * Control UC-05 / SEQ-05; dipakai ulang oleh UC-06 (KontrolEstimasi::getJumlahDiDepan).
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormInformasiAntrean;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Antrean;

final class KontrolAntrean extends Controller
{
    private string $nomorAntrean = '';
    private int $jumlahDiDepan = 0;
    private int $posisi = 0;

    public function __construct(private ?FormInformasiAntrean $form, Database $db)
    {
        parent::__construct($db);
        $form?->hubungkan($this);
    }

    /**
     * SEQ-05 pesan 4.1-4.6.
     * @return array{nomorBerjalan:string,posisi:int}|string array bila sukses, string pesan bila gagal
     */
    public function lihatPosisiAntrean(string $nomorAntrean): array|string
    {
        $this->nomorAntrean = trim($nomorAntrean);
        $antrean = new Antrean($this->db);
        Jejak::catat('SEQ-05', '4.1', 'KontrolAntrean', 'Antrean', 'getDaftarAntrean()');
        $daftar = $antrean->getDaftarAntrean();
        Jejak::catat('SEQ-05', '4.2', 'KontrolAntrean', 'Antrean', 'getNomorBerjalan()');
        $berjalan = $antrean->getNomorBerjalan();
        Jejak::catat('SEQ-05', '4.3', 'KontrolAntrean', 'KontrolAntrean', 'cekNomorAntrean(nomorAntrean, daftarAntrean)');
        if (!$this->cekNomorAntrean($this->nomorAntrean, $daftar)) {
            return $this->galat('Nomor antrean tidak ditemukan');                   // ACT-05 alt: tidak ditemukan
        }
        Jejak::catat('SEQ-05', '4.4', 'KontrolAntrean', 'KontrolAntrean', 'hitungJumlahDiDepan(nomorAntrean, daftarAntrean)');
        $hasil = $this->hitungJumlahDiDepan($this->nomorAntrean, $daftar);
        if ($hasil === null) {
            return $this->galat('Nomor antrean sudah selesai dilayani');
        }
        if ($berjalan === null) {
            Jejak::catat('SEQ-05', '4a', 'KontrolAntrean', 'FormInformasiAntrean', 'kirimPesanKesalahan(pesanKesalahan)');
            $this->form?->tampilkanPesanKesalahan('Antrean belum dimulai', '4a');   // UC-05 Alt-2
            return 'Antrean belum dimulai';
        }
        [$this->jumlahDiDepan, $this->posisi] = $hasil;
        Jejak::catat('SEQ-05', '4.5', 'KontrolAntrean', 'FormInformasiAntrean', 'kirimHasilPosisi(nomorBerjalan, jumlahDiDepan, posisi)');
        $this->form?->tampilkanInformasi($berjalan, $this->posisi);
        return ['nomorBerjalan' => $berjalan, 'posisi' => $this->posisi];
    }

    /** @param list<Antrean> $daftarAntrean */
    public function cekNomorAntrean(string $nomorAntrean, array $daftarAntrean): bool
    {
        foreach ($daftarAntrean as $a) {
            if (strcasecmp($a->getNomorAntrean(), $nomorAntrean) === 0) { return true; }
        }
        return false;
    }

    /**
     * Antrean di depan = entri lebih awal berstatus Menunggu/Dilayani. null bila nomor sudah selesai/dilewati.
     * @param list<Antrean> $daftarAntrean @return array{0:int,1:int}|null [jumlahDiDepan, posisi]
     */
    public function hitungJumlahDiDepan(string $nomorAntrean, array $daftarAntrean): ?array
    {
        $di = 0;
        foreach ($daftarAntrean as $a) {
            if (strcasecmp($a->getNomorAntrean(), $nomorAntrean) === 0) {
                return in_array($a->getStatusAntrean(), [Antrean::MENUNGGU, Antrean::DILAYANI], true) ? [$di, $di + 1] : null;
            }
            if (in_array($a->getStatusAntrean(), [Antrean::MENUNGGU, Antrean::DILAYANI], true)) { $di++; }
        }
        return null;
    }

    /** SEQ-06 pesan 2.1: jumlah antrean di depan, null bila nomor tidak valid/selesai. */
    public function getJumlahDiDepan(string $nomorAntrean): ?int
    {
        Jejak::catat('SEQ-06', '2.1.1', 'KontrolAntrean', 'Antrean', 'getDaftarAntrean()');
        $daftar = (new Antrean($this->db))->getDaftarAntrean();
        if (!$this->cekNomorAntrean($nomorAntrean, $daftar)) { return null; }
        $h = $this->hitungJumlahDiDepan($nomorAntrean, $daftar);
        return $h === null ? null : $h[0];
    }

    private function galat(string $pesan): string
    {
        Jejak::catat('SEQ-05', '4.6', 'KontrolAntrean', 'FormInformasiAntrean', 'kirimPesanKesalahan(pesanKesalahan)');
        $this->form?->tampilkanPesanKesalahan($pesan);
        return $pesan;
    }
}
