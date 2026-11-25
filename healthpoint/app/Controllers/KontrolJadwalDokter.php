<?php
/**
 * File: app/Controllers/KontrolJadwalDokter.php
 * Control UC-07 / SEQ-07. Atribut: hari:string, daftarJadwal:list.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormJadwalDokter;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Dokter;
use HealthPoint\Models\JadwalDokter;

final class KontrolJadwalDokter extends Controller
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private string $hari = '';
    /** @var list<JadwalDokter> */
    private array $daftarJadwal = [];

    public function __construct(private FormJadwalDokter $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** SEQ-07 pesan 4.1-4.4. @return list<array<string,string>>|string daftar jadwal atau pesan */
    public function lihatJadwal(string $hari): array|string
    {
        $this->hari = $hari;
        Jejak::catat('SEQ-07', '4.1', 'KontrolJadwalDokter', 'JadwalDokter', 'getJadwalByHari(hari)');
        $this->daftarJadwal = in_array($hari, self::HARI, true) ? (new JadwalDokter($this->db))->getJadwalByHari($hari) : [];
        if ($this->daftarJadwal === []) {
            Jejak::catat('SEQ-07', '4.2', 'KontrolJadwalDokter', 'FormJadwalDokter', 'kirimPesan(pesan)');
            $this->form->tampilkanPesan('Jadwal tidak tersedia');
            return 'Jadwal tidak tersedia';
        }
        $hasil = [];
        $dokter = new Dokter($this->db);
        foreach ($this->daftarJadwal as $j) {
            Jejak::catat('SEQ-07', '4.3', 'KontrolJadwalDokter', 'Dokter', 'getNamaDokter(idDokter)');
            $nama = $dokter->getNamaDokter($j->getIdDokter()) ?? '(dokter tidak diketahui)';
            Jejak::catat('SEQ-07', '4.4', 'KontrolJadwalDokter', 'FormJadwalDokter', 'kirimJadwal(namaDokter, jamPraktik, statusKetersediaan)');
            $this->form->tampilkanJadwal($nama, $j->getJamPraktik(), $j->getStatusKetersediaan());
            $hasil[] = ['namaDokter' => $nama, 'jamPraktik' => $j->getJamPraktik(), 'statusKetersediaan' => $j->getStatusKetersediaan()];
        }
        return $hasil;
    }
}
