<?php
/**
 * File: app/Controllers/LaporanHarianController.php
 * Control UC-16 / SEQ-16. Atribut private: tanggal, daftarPendaftaran, rekapPerLayanan.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormLaporanHarian;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Models\Layanan;
use HealthPoint\Models\Pendaftaran;

final class LaporanHarianController extends Controller
{
    private string $tanggal = '';
    /** @var list<Pendaftaran> */
    private array $daftarPendaftaran = [];
    /** @var list<array<string,mixed>> */
    private array $rekapPerLayanan = [];
    /** @var array<int,string> */
    private array $namaLayanan = [];

    public function __construct(private FormLaporanHarian $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** SEQ-16 pesan 4-8 / 4a. @return list<array<string,mixed>>|string rekap atau pesan */
    public function lihatLaporan(string $tanggal): array|string
    {
        $this->tanggal = $tanggal;
        if (!Waktu::tanggalValid($tanggal)) { return $this->kosong('Tanggal tidak valid'); }
        Jejak::catat('SEQ-16', '4', 'LaporanHarianController', 'Pendaftaran', 'getPendaftaranByTanggal(tanggal)');
        $this->daftarPendaftaran = (new Pendaftaran($this->db))->getPendaftaranByTanggal($tanggal);
        if ($this->daftarPendaftaran === []) { return $this->kosong('Belum ada data pada tanggal ini'); }   // UC-16 Alt-1
        $layanan = new Layanan($this->db);
        $this->namaLayanan = [];
        foreach ($this->daftarPendaftaran as $p) {
            if (!isset($this->namaLayanan[$p->getIdLayanan()])) {
                Jejak::catat('SEQ-16', '5', 'LaporanHarianController', 'Layanan', 'getNamaLayanan(idLayanan)');
                $this->namaLayanan[$p->getIdLayanan()] = $layanan->getNamaLayanan($p->getIdLayanan());
            }
        }
        Jejak::catat('SEQ-16', '6', 'LaporanHarianController', 'LaporanHarianController', 'hitungJumlahPerLayanan(daftarPendaftaran)');
        $this->rekapPerLayanan = $this->hitungJumlahPerLayanan($this->daftarPendaftaran);
        Jejak::catat('SEQ-16', '7', 'LaporanHarianController', 'FormLaporanHarian', 'kirimRekap(rekapPerLayanan)');
        $this->form->tampilkanRekap($this->rekapPerLayanan);
        return $this->rekapPerLayanan;
    }

    /** @param list<Pendaftaran> $daftarPendaftaran @return list<array<string,mixed>> */
    public function hitungJumlahPerLayanan(array $daftarPendaftaran): array
    {
        $jumlah = [];
        foreach ($daftarPendaftaran as $p) { $jumlah[$p->getIdLayanan()] = ($jumlah[$p->getIdLayanan()] ?? 0) + 1; }
        ksort($jumlah);
        $rekap = [];
        foreach ($jumlah as $id => $n) { $rekap[] = ['layanan' => $this->namaLayanan[$id] ?? (string) $id, 'jumlahPasien' => $n]; }
        return $rekap;
    }

    private function kosong(string $pesan): string
    {
        Jejak::catat('SEQ-16', '4a', 'LaporanHarianController', 'FormLaporanHarian', 'kirimPesan(pesan)');
        $this->form->tampilkanPesan($pesan);
        return $pesan;
    }
}
