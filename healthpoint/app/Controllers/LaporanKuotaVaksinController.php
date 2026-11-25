<?php
/**
 * File: app/Controllers/LaporanKuotaVaksinController.php
 * Control UC-17 / SEQ-17. Atribut private: tanggal, jumlahDosisTerpakai, sisaKuota.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormLaporanKuotaVaksin;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\PemakaianVaksin;
use HealthPoint\Models\StokVaksin;

final class LaporanKuotaVaksinController extends Controller
{
    private string $tanggal = '';
    private int $jumlahDosisTerpakai = 0;
    private int $sisaKuota = 0;

    public function __construct(private FormLaporanKuotaVaksin $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** SEQ-17 pesan 4-8 / 4a. @return array{jumlahDosisTerpakai:int,sisaKuota:int}|string */
    public function lihatLaporan(string $tanggal): array|string
    {
        $this->tanggal = $tanggal;
        if (!Waktu::tanggalValid($tanggal)) { return $this->kosong('Tanggal tidak valid'); }
        Jejak::catat('SEQ-17', '4', 'LaporanKuotaVaksinController', 'PemakaianVaksin', 'getPemakaianByTanggal(tanggal)');
        $daftar = (new PemakaianVaksin($this->db))->getPemakaianByTanggal($tanggal);
        if ($daftar === []) { return $this->kosong('Belum ada pemakaian vaksin pada tanggal ini'); }      // UC-17 Alt-1
        Jejak::catat('SEQ-17', '5', 'LaporanKuotaVaksinController', 'StokVaksin', 'getSisaKuota(tanggal)');
        $this->sisaKuota = (new StokVaksin($this->db))->getSisaKuota($tanggal);
        Jejak::catat('SEQ-17', '6', 'LaporanKuotaVaksinController', 'LaporanKuotaVaksinController', 'hitungDosisTerpakai(daftarPemakaian)');
        $this->jumlahDosisTerpakai = $this->hitungDosisTerpakai($daftar);
        Jejak::catat('SEQ-17', '7', 'LaporanKuotaVaksinController', 'FormLaporanKuotaVaksin', 'kirimRekap(jumlahDosisTerpakai, sisaKuota)');
        $this->form->tampilkanRekap($this->jumlahDosisTerpakai, $this->sisaKuota);
        return ['jumlahDosisTerpakai' => $this->jumlahDosisTerpakai, 'sisaKuota' => $this->sisaKuota];
    }

    /** @param list<PemakaianVaksin> $daftarPemakaian */
    public function hitungDosisTerpakai(array $daftarPemakaian): int
    {
        return array_sum(array_map(static fn (PemakaianVaksin $p): int => $p->getJumlahDosis(), $daftarPemakaian));
    }

    private function kosong(string $pesan): string
    {
        Jejak::catat('SEQ-17', '4a', 'LaporanKuotaVaksinController', 'FormLaporanKuotaVaksin', 'kirimPesan(pesan)');
        $this->form->tampilkanPesan($pesan);
        return $pesan;
    }
}
