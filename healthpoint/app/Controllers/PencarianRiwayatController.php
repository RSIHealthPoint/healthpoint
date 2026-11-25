<?php
/**
 * File: app/Controllers/PencarianRiwayatController.php
 * Control UC-14 (SEQ-14) dan UC-15 (SEQ-15). Atribut private: daftarPasien:List, ringkasanRiwayat:String.
 * Hasil tunggal -> ditampilkan langsung (UC-14); hasil >1 -> diteruskan ke FormDaftarKandidat (UC-15).
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormDaftarKandidat;
use HealthPoint\Boundaries\FormPencarianRiwayat;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\DataPasien;
use HealthPoint\Models\Model;
use HealthPoint\Models\RiwayatKunjungan;

final class PencarianRiwayatController extends Controller
{
    /** @var list<array<string,mixed>> */
    private array $daftarPasien = [];
    private string $ringkasanRiwayat = '';

    public function __construct(private FormPencarianRiwayat $form, private FormDaftarKandidat $kandidat, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
        $kandidat->hubungkan($this);
    }

    /**
     * SEQ-14 pesan 5-8 / 5a-5b; hasil >1 => SEQ-15 pesan 1.
     * @return list<array<string,mixed>>|string daftar pasien atau pesan
     */
    public function cariPasien(string $nama, string $tanggalLahir = '', string $noRM = ''): array|string
    {
        if (trim($nama) === '') { return $this->galat('Data pasien tidak ditemukan'); }
        Jejak::catat('SEQ-14', '5', 'PencarianRiwayatController', 'DataPasien', 'cariPasien(nama, tanggalLahir, noRM)');
        $this->daftarPasien = Model::daftarKeArray((new DataPasien($this->db))->cariPasien($nama, $tanggalLahir, $noRM));
        if ($this->daftarPasien === []) { return $this->galat('Data pasien tidak ditemukan'); }       // UC-14 Alt-1
        if (count($this->daftarPasien) > 1) {
            $this->form->tampilkanInfoKandidat(count($this->daftarPasien));
            Jejak::catat('SEQ-15', '1', 'PencarianRiwayatController', 'FormDaftarKandidat', 'kirimDaftarKandidat(daftarPasien)');
            $this->kandidat->tampilkanDaftarKandidat($this->daftarPasien);
            return $this->daftarPasien;
        }
        $p = $this->daftarPasien[0];
        Jejak::catat('SEQ-14', '6', 'PencarianRiwayatController', 'RiwayatKunjungan', 'getRingkasanRiwayat(idPasien)');
        $this->ringkasanRiwayat = (new RiwayatKunjungan($this->db))->getRingkasanRiwayat((int) $p['idPasien']);
        Jejak::catat('SEQ-14', '7', 'PencarianRiwayatController', 'FormPencarianRiwayat', 'kirimHasil(dataPasien, ringkasanRiwayat)');
        $this->form->tampilkanDataPasien($p, $this->ringkasanRiwayat);
        return $this->daftarPasien;
    }

    /**
     * SEQ-15 pesan 5-8. Hanya pasien yang ada pada daftar kandidat yang dapat dipilih.
     * @return array{dataLengkap:array<string,mixed>,ringkasanRiwayat:string}|null
     */
    public function getDetailPasien(int $idPasien): ?array
    {
        $ada = array_filter($this->daftarPasien, static fn (array $p): bool => (int) $p['idPasien'] === $idPasien);
        if ($ada === []) { $this->kandidat->tampilkanGalat('Pasien tidak ada pada daftar kandidat'); return null; }
        Jejak::catat('SEQ-15', '5', 'PencarianRiwayatController', 'DataPasien', 'getDataLengkap(idPasien)');
        $data = (new DataPasien($this->db))->getDataLengkap($idPasien);
        Jejak::catat('SEQ-15', '6', 'PencarianRiwayatController', 'RiwayatKunjungan', 'getRingkasanRiwayat(idPasien)');
        $this->ringkasanRiwayat = (new RiwayatKunjungan($this->db))->getRingkasanRiwayat($idPasien);
        Jejak::catat('SEQ-15', '7', 'PencarianRiwayatController', 'FormDaftarKandidat', 'kirimDetailPasien(dataLengkap, ringkasanRiwayat)');
        $this->kandidat->tampilkanDetailPasien($data->toArray(), $this->ringkasanRiwayat);
        return ['dataLengkap' => $data->toArray(), 'ringkasanRiwayat' => $this->ringkasanRiwayat];
    }

    private function galat(string $pesan): string
    {
        Jejak::catat('SEQ-14', '5a', 'PencarianRiwayatController', 'FormPencarianRiwayat', 'kirimPesanKesalahan(pesan)');
        $this->form->tampilkanPesanKesalahan($pesan);
        return $pesan;
    }
}
