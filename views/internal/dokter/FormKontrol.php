<?php
/**
 * File: views/internal/dokter/FormKontrol.php
 * Boundary UC-10 / ACT-10 (header SEQ: ACT-12) / SEQ-10. Aktor: Dokter. (Pada PDF tertulis "FormKotrol".)
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\TampilanDokter;

final class FormKontrol extends Boundary
{
    private ?KontrolController $kontrol = null;
    private string $tanggalKontrol = '';
    private int $idLayanan = 0;
    private string $jamKontrol = '';

    public function hubungkan(KontrolController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Tanggal Kontrol Ulang'; }
    public function getTanggalKontrol(): string { return $this->tanggalKontrol; }
    public function getIdLayanan(): int { return $this->idLayanan; }
    public function getJamKontrol(): string { return $this->jamKontrol; }

    /** SEQ-10 pesan 1-6 */
    public function aktifkanKontrolUlang(): void
    {
        Jejak::catat('SEQ-10', '1', 'Dokter', 'FormKontrol', 'aktifkanKontrolUlang()');
        Jejak::catat('SEQ-10', '2', 'FormKontrol', 'KontrolController', 'getDaftarPoli()');
        $this->tampilkanFormJadwalKontrol($this->kontrol->getDaftarPoli());
    }

    /** @param list<array<string,mixed>> $daftarPoli */
    public function tampilkanFormJadwalKontrol(array $daftarPoli): void
    {
        Jejak::catat('SEQ-10', '6', 'FormKontrol', 'Dokter', 'tampilkanFormJadwalKontrol(daftarPoli)');
        $this->tampil('tabel', ['judul' => 'Poli tersedia', 'baris' => array_map(static fn (array $p): array => ['id' => $p['idLayanan'], 'poli' => $p['namaLayanan']], $daftarPoli)]);
    }

    /** SEQ-10 pesan 7-12 */
    public function pilihTanggalKontrol(string $tanggal): void
    {
        Jejak::catat('SEQ-10', '7', 'Dokter', 'FormKontrol', 'pilihTanggalKontrol(tanggal)');
        $this->tanggalKontrol = $tanggal;
        Jejak::catat('SEQ-10', '8', 'FormKontrol', 'KontrolController', 'getDaftarJam(tanggal)');
        $this->tampilkanPilihanJam($this->kontrol->getDaftarJam($tanggal));
    }

    /** @param list<array<string,mixed>> $daftarJam SEQ-10 pesan 12 */
    public function tampilkanPilihanJam(array $daftarJam): void
    {
        Jejak::catat('SEQ-10', '12', 'FormKontrol', 'Dokter', 'tampilkanPilihanJam(daftarJam)');
        $this->tampil('tabel', ['judul' => 'Jam praktik tanggal ' . $this->tanggalKontrol, 'baris' => array_map(
            static fn (array $j): array => ['idDokter' => $j['idDokter'], 'jamPraktik' => $j['jamPraktik']], $daftarJam)]);
    }

    public function pilihPoli(int $idLayanan): void
    {
        Jejak::catat('SEQ-10', '13', 'Dokter', 'FormKontrol', 'pilihPoli(idLayanan)');
        $this->idLayanan = $idLayanan;
    }

    /** SEQ-10 pesan 14-15 */
    public function pilihJam(string $jam): string
    {
        Jejak::catat('SEQ-10', '14', 'Dokter', 'FormKontrol', 'pilihJam(jam)');
        $this->jamKontrol = $jam;
        Jejak::catat('SEQ-10', '15', 'FormKontrol', 'KontrolController', 'validasiJadwalKontrol(tanggal, idLayanan, jam)');
        return $this->kontrol->validasiJadwalKontrol($this->tanggalKontrol, $this->idLayanan, $jam);
    }

    /** SEQ-10 pesan 19 (alt: false) */
    public function tampilkanPesanDanMintaIsiUlang(string $pesan): void
    {
        Jejak::catat('SEQ-10', '19', 'FormKontrol', 'Dokter', 'tampilkanPesanDanMintaIsiUlang(pesan)');
        $this->tampilPesan('galat', $pesan . ' Silakan isi ulang.');
    }

    /** SEQ-10 pesan 21 (else: true) */
    public function tampilkanJadwalKontrol(string $tanggal, int $idLayanan, string $jam): void
    {
        Jejak::catat('SEQ-10', '21', 'FormKontrol', 'Dokter', 'tampilkanJadwalKontrol(tanggal, idLayanan, jam)');
        $this->tampilPesan('sukses', "Jadwal kontrol diterima: $tanggal pukul $jam (layanan #$idLayanan).");
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/10') . '<label>Tanggal <input type="date" name="tanggal" value="' . self::h($this->tanggalKontrol) . '"></label>'
            . '<label>Poli (ID) <input type="number" name="idLayanan" value="' . $this->idLayanan . '"></label>'
            . '<label>Jam <input name="jam" placeholder="09:00" value="' . self::h($this->jamKontrol) . '"></label><button>Validasi jadwal</button></form>';
    }

    /** Halaman UC-10 sesuai UI-08. @param list<array{id:int,poli:string}> $poli */
    public function renderHalaman(array $poli): string
    {
        $o = '';
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= TampilanDokter::notice((string) $l['data']['tipe'], (string) $l['data']['teks']);
            }
        }
        return $o . TampilanDokter::formKontrolMandiri($poli, $this->tanggalKontrol, $this->idLayanan, $this->jamKontrol);
    }
}
