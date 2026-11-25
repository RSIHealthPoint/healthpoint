<?php
/**
 * File: views/internal/dokter/FormPemeriksaan.php
 * Boundary UC-09 / ACT-09 (header SEQ: ACT-11) / SEQ-09. Aktor: Dokter (header SEQ menulis "Petugas").
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PemeriksaanController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\TampilanDokter;

final class FormPemeriksaan extends Boundary
{
    private ?PemeriksaanController $kontrol = null;
    private int $idPasien = 0;
    private string $diagnosis = '';
    private string $tindakan = '';
    private string $keyword = '';
    /** @var list<array<string,mixed>> */
    private array $hasilPencarian = [];
    /** @var array<string,mixed>|null */
    private ?array $pasienTerpilih = null;
    /** @var array<string,mixed>|null */
    private ?array $kunjunganAktif = null;

    public function hubungkan(PemeriksaanController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Form Hasil Pemeriksaan'; }
    public function getIdPasien(): int { return $this->idPasien; }
    public function getDiagnosis(): string { return $this->diagnosis; }
    public function getTindakan(): string { return $this->tindakan; }

    /** SEQ-09 pesan 1-2 */
    public function bukaFormPemeriksaan(): void
    {
        Jejak::catat('SEQ-09', '1', 'Dokter', 'FormPemeriksaan', 'bukaFormPemeriksaan()');
        $this->tampilkanFormPencarian();
    }

    public function tampilkanFormPencarian(string $no = '2'): void
    {
        Jejak::catat('SEQ-09', $no, 'FormPemeriksaan', 'Dokter', 'tampilkanFormPencarian()');
    }

    /** SEQ-09 pesan 3-8 */
    public function isiKeywordPasien(string $keyword): void
    {
        Jejak::catat('SEQ-09', '3', 'Dokter', 'FormPemeriksaan', 'isiKeywordPasien(keyword)');
        $this->keyword = $keyword;
        Jejak::catat('SEQ-09', '4', 'FormPemeriksaan', 'PemeriksaanController', 'cariPasien(keyword)');
        $this->tampilkanHasilPencarian($this->kontrol->cariPasien($keyword));
    }

    /** @param list<array<string,mixed>> $dataPasien SEQ-09 pesan 8 */
    public function tampilkanHasilPencarian(array $dataPasien): void
    {
        Jejak::catat('SEQ-09', '8', 'FormPemeriksaan', 'Dokter', 'tampilkanHasilPencarian(dataPasien)');
        $this->hasilPencarian = $dataPasien;
        $dataPasien === [] ? $this->tampilPesan('info', 'Data pasien tidak ditemukan') : $this->tampil('tabel', ['judul' => 'Hasil pencarian', 'baris' => $dataPasien]);
    }

    /** SEQ-09 pesan 9-10 (opt) */
    public function hapusPencarian(): void
    {
        Jejak::catat('SEQ-09', '9', 'Dokter', 'FormPemeriksaan', 'hapusPencarian()');
        $this->keyword = '';
        $this->hasilPencarian = [];
        $this->tampilkanFormPencarian('10');
    }

    /** SEQ-09 pesan 11-16 */
    public function pilihPasien(int $idPasien): void
    {
        Jejak::catat('SEQ-09', '11', 'Dokter', 'FormPemeriksaan', 'pilihPasien(idPasien)');
        $this->idPasien = $idPasien;
        Jejak::catat('SEQ-09', '12', 'FormPemeriksaan', 'PemeriksaanController', 'getKunjunganAktif(idPasien)');
        $kunjungan = $this->kontrol->getKunjunganAktif($idPasien);
        $pasien = null;
        foreach ($this->hasilPencarian as $p) { if ((int) $p['idPasien'] === $idPasien) { $pasien = $p; } }
        if ($pasien === null || $kunjungan === null) {
            $this->tampilPesan('galat', 'Pasien tidak ada pada hasil pencarian atau tidak memiliki kunjungan aktif');
            return;
        }
        $this->tampilkanFormBesertaDataPasien($pasien, $kunjungan);
    }

    /** @param array<string,mixed> $dataPasien @param array<string,mixed> $dataKunjungan SEQ-09 pesan 16 */
    public function tampilkanFormBesertaDataPasien(array $dataPasien, array $dataKunjungan): void
    {
        $this->pasienTerpilih = $dataPasien;
        $this->kunjunganAktif = $dataKunjungan;
        Jejak::catat('SEQ-09', '16', 'FormPemeriksaan', 'Dokter', 'tampilkanFormBesertaDataPasien(dataPasien, dataKunjungan)');
        $this->tampil('rincian', ['judul' => 'Identitas pasien', 'data' => $dataPasien]);
        $this->tampil('rincian', ['judul' => 'Kunjungan aktif', 'data' => array_intersect_key($dataKunjungan,
            array_flip(['keluhanUtama', 'tekananDarah', 'nadi', 'suhu', 'spo2']))]);
    }

    /** SEQ-09 pesan 17 */
    public function isiDiagnosisDanTindakan(string $diagnosis, string $tindakan): void
    {
        Jejak::catat('SEQ-09', '17', 'Dokter', 'FormPemeriksaan', 'isiDiagnosisDanTindakan(diagnosis, tindakan)');
        $this->diagnosis = $diagnosis;
        $this->tindakan = $tindakan;
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/09', 'get') . '<label>Cari pasien (nama / No. RM) <input name="keyword" value="' . self::h($this->keyword) . '"></label><button>Cari</button></form>'
            . '<p>Setelah memilih pasien, isi diagnosis dan tindakan lalu simpan lewat <a href="/uc/11">Simpan Data Layanan</a>.</p>';
    }

    /** @return list<array<string,mixed>> */
    public function getHasilPencarian(): array { return $this->hasilPencarian; }

    /** Halaman dokter: pencarian -> kartu pasien -> form hasil (post ke UC-11). @param list<array{id:int,poli:string}> $poli */
    public function renderHalaman(array $poli): string
    {
        $o = TampilanDokter::pencarian($this->keyword);
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= TampilanDokter::notice((string) $l['data']['tipe'], (string) $l['data']['teks']);
            }
        }
        if ($this->pasienTerpilih !== null && $this->kunjunganAktif !== null) {
            $o .= TampilanDokter::kartuPasien($this->pasienTerpilih, $this->kunjunganAktif)
                . TampilanDokter::formLayanan($this->idPasien, '', '', false, '', 0, '', $poli);
        } elseif (count($this->hasilPencarian) > 1) {
            $o .= TampilanDokter::kandidat($this->hasilPencarian, $this->keyword);
        }
        return $o;
    }
}
