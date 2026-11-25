<?php
/**
 * File: Controllers/PemeriksaanController.php
 * Control UC-09 / SEQ-09.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormPemeriksaan;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\DataPasien;
use HealthPoint\Models\Model;
use HealthPoint\Models\RiwayatKunjungan;

final class PemeriksaanController extends Controller
{
    public function __construct(private FormPemeriksaan $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-09 pesan 4-7 */
    public function cariPasien(string $keyword): array
    {
        Jejak::catat('SEQ-09', '5', 'PemeriksaanController', 'DataPasien', 'getDataPasien(keyword)');
        $hasil = Model::daftarKeArray((new DataPasien($this->db))->getDataPasien($keyword));
        Jejak::catat('SEQ-09', '6', 'DataPasien', 'PemeriksaanController', 'return dataPasien');
        Jejak::catat('SEQ-09', '7', 'PemeriksaanController', 'FormPemeriksaan', 'return dataPasien');
        return $hasil;
    }

    /** @return array<string,mixed>|null SEQ-09 pesan 13-15 */
    public function getKunjunganAktif(int $idPasien): ?array
    {
        Jejak::catat('SEQ-09', '13', 'PemeriksaanController', 'RiwayatKunjungan', 'getKunjunganAktif(idPasien)');
        $k = (new RiwayatKunjungan($this->db))->getKunjunganAktif($idPasien);
        Jejak::catat('SEQ-09', '14', 'RiwayatKunjungan', 'PemeriksaanController', 'return dataKunjungan');
        Jejak::catat('SEQ-09', '15', 'PemeriksaanController', 'FormPemeriksaan', 'return dataKunjungan');
        return $k?->toArray();
    }
}
