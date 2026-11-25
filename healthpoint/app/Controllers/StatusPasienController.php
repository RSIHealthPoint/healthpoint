<?php
/**
 * File: app/Controllers/StatusPasienController.php
 * Control UC-04 / SEQ-04. Atribut private idPasien:int, statusBaru:string (Class Diagram).
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\HalamanStatusPasien;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Pasien;

final class StatusPasienController extends Controller
{
    private int $idPasien = 0;
    private string $statusBaru = '';

    public function __construct(private HalamanStatusPasien $halaman, Database $db)
    {
        parent::__construct($db);
        $halaman->hubungkan($this);
    }

    /** SEQ-04 pesan 2-5 */
    public function ambilStatusPasien(int $idPasien): void
    {
        $this->idPasien = $idPasien;
        Jejak::catat('SEQ-04', '3', 'StatusPasienController', 'Pasien', 'ambilStatusKedatangan(idPasien)');
        $status = (new Pasien($this->db))->getStatusKedatangan($idPasien);
        if ($status === null) {
            $this->halaman->tampilkanGalat('Pasien tidak ditemukan');
            return;
        }
        Jejak::catat('SEQ-04', '4', 'StatusPasienController', 'HalamanStatusPasien', 'kirimStatusSaatIni()');
        $this->halaman->tampilkanStatusSaatIni($status);
    }

    /** SEQ-04 pesan 7-10 */
    public function ubahStatus(int $idPasien, string $statusBaru): void
    {
        $this->idPasien = $idPasien;
        $this->statusBaru = $statusBaru;
        Jejak::catat('SEQ-04', '8', 'StatusPasienController', 'Pasien', 'simpanStatusKedatangan(idPasien, statusBaru)');
        if (!(new Pasien($this->db))->simpanStatusKedatangan($idPasien, $statusBaru)) {
            $this->halaman->tampilkanGalat('Status tidak valid atau pasien tidak ditemukan');
            return;
        }
        Jejak::catat('SEQ-04', '9', 'StatusPasienController', 'HalamanStatusPasien', 'kirimStatusTerbaru()');
        $this->halaman->tampilkanStatusTerbaru($statusBaru);
    }
}
