<?php
/**
 * File: Controllers/StokVaksinController.php
 * Control UC-12 / SEQ-12. Alt (4a): data kosong / negatif / bukan angka -> pesan kesalahan, data tidak disimpan.
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Boundaries\FormStokVaksin;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Models\Model;
use HealthPoint\Models\StokVaksin;

final class StokVaksinController extends Controller
{
    public function __construct(private FormStokVaksin $form, Database $db)
    {
        parent::__construct($db);
        $form->hubungkan($this);
    }

    /** @return list<array<string,mixed>> SEQ-12 pesan 3-5 */
    public function getDaftarVaksin(): array
    {
        Jejak::catat('SEQ-12', '3', 'StokVaksinController', 'StokVaksin', 'getDaftarStok()');
        $d = Model::daftarKeArray((new StokVaksin($this->db))->getDaftarStok());
        Jejak::catat('SEQ-12', '4', 'StokVaksin', 'StokVaksinController', 'return daftarVaksin');
        Jejak::catat('SEQ-12', '5', 'StokVaksinController', 'FormStokVaksin', 'return daftarVaksin');
        return $d;
    }

    /** SEQ-12 pesan 10-12: proyeksi = stok saat ini + jumlah masuk. */
    public function hitungProyeksiKuota(int $idVaksin, int $jumlah): int
    {
        Jejak::catat('SEQ-12', '10', 'StokVaksinController', 'StokVaksin', 'getJumlahStok(idVaksin)');
        $stokSaatIni = (new StokVaksin($this->db))->getJumlahStok($idVaksin);
        Jejak::catat('SEQ-12', '11', 'StokVaksin', 'StokVaksinController', 'return stokSaatIni');
        Jejak::catat('SEQ-12', '12', 'StokVaksinController', 'FormStokVaksin', 'return proyeksiKuota');
        return $stokSaatIni + $jumlah;
    }

    /** @return int|string bilangan bulat positif, atau pesan kesalahan */
    public function validasiJumlahStok(string $jumlahStok): int|string
    {
        $j = trim($jumlahStok);
        if ($j === '') { return 'Jumlah stok wajib diisi.'; }
        if (preg_match('/^-?\d+$/', $j) !== 1) { return 'Jumlah stok harus berupa angka.'; }
        return (int) $j;
    }

    /** SEQ-12 pesan 15-23. @return int stokTerbaru | string pesanKesalahan */
    public function perbaruiStok(int $idVaksin, string $jumlah): int|string
    {
        $stok = new StokVaksin($this->db);
        if (!$stok->ada($idVaksin)) { return $this->galat('Vaksin tidak ditemukan.'); }
        $n = $this->validasiJumlahStok($jumlah);
        if (is_string($n)) { return $this->galat($n); }                              // ACT-12 alt: kosong / bukan angka
        Jejak::catat('SEQ-12', '16', 'StokVaksinController', 'StokVaksin', 'cekValiditasStok(jumlah)');
        $valid = $stok->cekValiditasStok($n);
        Jejak::catat('SEQ-12', '17', 'StokVaksin', 'StokVaksinController', 'return isValid');
        if (!$valid) { return $this->galat('Jumlah stok tidak valid (harus lebih dari 0).'); }
        Jejak::catat('SEQ-12', '20', 'StokVaksinController', 'StokVaksin', 'updateStok(idVaksin, jumlah)');
        $terbaru = $stok->updateStok($idVaksin, $n);
        Jejak::catat('SEQ-12', '21', 'StokVaksin', 'StokVaksinController', 'return stokTerbaru');
        Jejak::catat('SEQ-12', '22', 'StokVaksinController', 'FormStokVaksin', 'return stokTerbaru');
        $this->form->tampilkanDaftarStokVaksin(Model::daftarKeArray($stok->getDaftarStok()), '23');
        return $terbaru;
    }

    private function galat(string $pesan): string
    {
        Jejak::catat('SEQ-12', '18', 'StokVaksinController', 'FormStokVaksin', 'return pesanKesalahan(alt: false)');
        $this->form->tampilkanPesanKesalahan($pesan);
        return $pesan;
    }
}
