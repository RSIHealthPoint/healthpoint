<?php
/**
 * File: Controllers/KontrolKelolaJadwal.php
 * Control UC-08 / SEQ-08. Atribut private: valid:boolean, statusSimpan:string.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormKelolaJadwalDokter;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Dokter;
use HealthPoint\Models\JadwalDokter;
use HealthPoint\Models\Model;

final class KontrolKelolaJadwal extends Controller
{
    /** UC-08 Alt-2: penyimpanan > 3 detik dianggap gagal. */
    public const BATAS_DETIK = 3.0;

    private bool $valid = false;
    private string $statusSimpan = '';

    public function __construct(private FormKelolaJadwalDokter $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-08 pesan 2.1 */
    public function getDaftarDokter(): array
    {
        Jejak::catat('SEQ-08', '2.1', 'KontrolKelolaJadwal', 'Dokter', 'getDaftarDokter()');
        return Model::daftarKeArray((new Dokter($this->db))->getDaftarDokter());
    }

    /** SEQ-08 pesan 7.1-7.5. @return string pesan hasil */
    public function simpanJadwal(int $idDokter, string $hari, string $jamPraktik, string $statusKetersediaan): string
    {
        Jejak::catat('SEQ-08', '7.1', 'KontrolKelolaJadwal', 'KontrolKelolaJadwal', 'validasiData(idDokter, hari, jamPraktik, statusKetersediaan)');
        $this->valid = $this->validasiData($idDokter, $hari, $jamPraktik, $statusKetersediaan);
        if (!$this->valid) {
            $p = 'Data jadwal tidak lengkap atau tidak valid';
            Jejak::catat('SEQ-08', '7.2', 'KontrolKelolaJadwal', 'FormKelolaJadwalDokter', 'kirimPesanKesalahan()');
            $this->form->tampilkanPesanKesalahan($p);
            return $p;
        }
        Jejak::catat('SEQ-08', '7.3', 'KontrolKelolaJadwal', 'JadwalDokter', 'simpanJadwal(idDokter, hari, jamPraktik, statusKetersediaan)');
        $t0 = microtime(true);
        try {
            $ok = (new JadwalDokter($this->db))->simpanJadwal($idDokter, $hari, $jamPraktik, $statusKetersediaan);
        } catch (\Throwable) {
            $ok = false;
        }
        $this->statusSimpan = ($ok && (microtime(true) - $t0) <= self::BATAS_DETIK) ? 'berhasil' : 'gagal';
        if ($this->statusSimpan === 'gagal') {
            $p = 'Gagal menyimpan jadwal, silakan ulangi';
            Jejak::catat('SEQ-08', '7.4', 'KontrolKelolaJadwal', 'FormKelolaJadwalDokter', 'kirimPesanGagal()');
            $this->form->tampilkanPesanGagal($p);
            return $p;
        }
        $p = 'Jadwal berhasil disimpan';
        Jejak::catat('SEQ-08', '7.5', 'KontrolKelolaJadwal', 'FormKelolaJadwalDokter', 'kirimPesanBerhasil()');
        $this->form->tampilkanPesanBerhasil($p);
        return $p;
    }

    public function validasiData(int $idDokter, string $hari, string $jamPraktik, string $statusKetersediaan): bool
    {
        if (!(new Dokter($this->db))->ada($idDokter) || !in_array($hari, KontrolJadwalDokter::HARI, true)) { return false; }
        if (!in_array($statusKetersediaan, [JadwalDokter::STATUS_TERSEDIA, JadwalDokter::STATUS_TIDAK_TERSEDIA, JadwalDokter::STATUS_ISTIRAHAT, JadwalDokter::STATUS_CUTI], true)) { return false; }
        if (!preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $jamPraktik, $m)) { return false; }
        return \HealthPoint\Core\Waktu::jamValid($m[1]) && \HealthPoint\Core\Waktu::jamValid($m[2]) && $m[1] < $m[2];
    }
}
