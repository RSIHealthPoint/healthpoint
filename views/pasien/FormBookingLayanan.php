<?php
/**
 * File: views/pasien/FormBookingLayanan.php
 * Boundary "Form Booking Layanan" (pada pesan tertulis FormPendaftaran). UC-01 / ACT-01 / SEQ-01.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PendaftaranController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;

final class FormBookingLayanan extends Boundary
{
    private ?PendaftaranController $kontrol = null;
    /** @var list<array<string,mixed>> */
    private array $daftarLayanan = [];
    private int $idPasien = 0;
    private int $idLayanan = 0;
    private string $tanggal = '';
    private string $keluhan = '';

    public function hubungkan(PendaftaranController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Pendaftaran Layanan'; }

    /** SEQ-01 pesan 1 -> 2 (ACT-01 langkah A-B). */
    public function pilihMenuPendaftaran(): void
    {
        Jejak::catat('SEQ-01', '1', 'Pasien', 'FormBookingLayanan', 'pilihMenuPendaftaran()');
        Jejak::catat('SEQ-01', '2', 'FormBookingLayanan', 'PendaftaranController', 'tampilkanLayanan()');
        $this->tampilkanLayanan($this->kontrol->tampilkanLayanan());
    }

    /** @param list<array<string,mixed>> $daftar */
    public function tampilkanLayanan(array $daftar): void
    {
        $this->daftarLayanan = $daftar;
        $this->tampil('tabel', ['judul' => 'Layanan yang tersedia', 'baris' => array_map(
            static fn (array $l): array => ['id' => $l['idLayanan'], 'layanan' => $l['namaLayanan'], 'keterangan' => $l['deskripsi'], 'status' => $l['tersedia'] ? 'Tersedia' : 'Tidak tersedia'], $daftar)]);
    }

    /** Input identitas pasien (login) dan data pendukung booking. */
    public function masukkanDataPasien(int $idPasien, string $tanggal, string $keluhan = ''): void
    {
        $this->idPasien = $idPasien;
        $this->tanggal = $tanggal;
        $this->keluhan = $keluhan;
    }

    /** SEQ-01 pesan 3 */
    public function pilihLayanan(int $idLayanan): void
    {
        Jejak::catat('SEQ-01', '3', 'Pasien', 'FormBookingLayanan', 'pilihLayanan(idLayanan)');
        $this->idLayanan = $idLayanan;
    }

    /** SEQ-01 pesan 4: Pasien mengonfirmasi pendaftaran. */
    public function konfirmasiPendaftaran(): string
    {
        Jejak::catat('SEQ-01', '4', 'FormBookingLayanan', 'PendaftaranController', 'daftar(DataPasien, idLayanan)');
        return $this->kontrol->daftar($this->idPasien, $this->idLayanan, $this->tanggal, $this->keluhan);
    }

    /** SEQ-01 pesan 6 (dari controller). */
    public function tampilkanInformasi(string $status): void
    {
        $this->tampil('status', $status);
        match ($status) {
            'berhasil' => $this->informasiBerhasil(),
            'layanan_tidak_tersedia' => $this->tampilPesan('galat', 'Layanan yang dipilih tidak tersedia. Silakan pilih layanan lain.'),
            default => $this->tampilPesan('galat', 'Data pendaftaran tidak lengkap. Lengkapi data yang diperlukan.'),
        };
    }

    /** SEQ-01 pesan 7 */
    public function informasiBerhasil(): void
    {
        Jejak::catat('SEQ-01', '7', 'FormBookingLayanan', 'Pasien', 'informasiBerhasil()');
        $this->tampilPesan('sukses', 'Pendaftaran berhasil disimpan.');
    }

    public function formHtml(): string
    {
        $opsi = '';
        foreach ($this->daftarLayanan as $l) {
            if ($l['tersedia']) { $opsi .= '<option value="' . (int) $l['idLayanan'] . '">' . self::h($l['namaLayanan']) . '</option>'; }
        }
        return self::formTag('/uc/01') . '<label>Tanggal booking <input type="date" name="tanggal" min="' . self::h(Waktu::hariIni()) . '" value="' . self::h($this->tanggal) . '"></label>'
            . '<label>Layanan <select name="idLayanan">' . $opsi . '</select></label>'
            . '<label>Keluhan <textarea name="keluhan">' . self::h($this->keluhan) . '</textarea></label><button>Simpan &amp; Konfirmasi Booking</button></form>';
    }
}
