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
use HealthPoint\Core\Sesi;
use HealthPoint\Models\Booking;

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
            'tanggal_tidak_tersedia' => $this->tampilPesan('galat', 'Tanggal booking tidak tersedia. Booking dilakukan paling lambat H-1 pada hari yang memiliki jadwal dokter tersedia.'),
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

    /**
     * Halaman UI-01: "Pendaftaran <Layanan>" + tanggal + kartu jadwal + keluhan.
     * @param list<array{dokter:string,jam:string,diambil:int,sisa:int}> $jadwal
     */
    public function renderHalaman(int $idLayanan, string $tanggal, array $jadwal, ?string $nomorBaru = null): string
    {
        $nama = 'Layanan';
        foreach ($this->daftarLayanan as $l) {
            if ((int) $l['idLayanan'] === $idLayanan) { $nama = (string) $l['namaLayanan']; }
        }
        $o = '<header class="page-heading"><span class="page-heading__icon">' . self::ikon('file-text') . '</span><div>'
            . '<h1 class="page-heading__title">Pendaftaran ' . self::h($nama) . '</h1><p class="page-heading__subtitle">Formulir Booking Antrean</p></div></header>';

        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }
        if ($this->terakhir('status') === 'berhasil') {
            $o .= '<a class="app-link" href="/uc/02">Lihat booking saya</a>';
        }
        if ($nomorBaru !== null) {
            $o .= '<div class="queue-banner" role="status"><span class="queue-banner__label">Nomor antrean Anda</span>'
                . '<span class="queue-banner__number">' . self::h($nomorBaru) . '</span>'
                . ($tanggal === Waktu::hariIni() ? '<a class="app-link" href="/uc/05?nomor=' . rawurlencode($nomorBaru) . '">Cek status antrean</a>' : '<span class="queue-banner__hint">Status antrean dapat dicek pada hari kunjungan.</span>')
                . '</div>';
        }
        $o .= '<form class="booking-form" method="post" action="/uc/01">'
            . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
            . '<input type="hidden" name="idLayanan" value="' . $idLayanan . '">'
            . '<div class="form-group"><label class="form-label" for="tanggal-booking">Pilih Tanggal Booking</label><div class="date-field">'
            . '<input class="date-field__input" id="tanggal-booking" name="tanggal" type="date" min="' . self::h(Booking::tanggalMinimal()) . '"'
            . (Booking::tanggalMaksimal() !== null ? ' max="' . self::h((string) Booking::tanggalMaksimal()) . '"' : '')
            . ' value="' . self::h($tanggal) . '"'
            . ' onchange="window.location.search=\'idLayanan=' . $idLayanan . '&amp;tanggal=\'+this.value">'
            . '<span class="date-field__icon icon icon--calendar" aria-hidden="true"></span></div></div>'
            . '<fieldset class="choice-group"><legend class="form-label">Pilih Jadwal</legend><div class="choice-list">';

        if ($jadwal === []) {
            $o .= '<p class="data-empty">Tidak ada dokter praktik pada tanggal ini.</p>';
        }
        foreach ($jadwal as $i => $j) {
            $o .= '<label class="choice"><input class="choice__input" type="radio" name="jadwal" value="' . $i . '"' . ($i === 0 ? ' checked' : '') . '>'
                . '<span class="choice__body"><span class="choice__header"><span class="choice__title"><span class="choice__radio" aria-hidden="true"></span>'
                . '<span class="choice__name">' . self::h($j['dokter']) . '</span></span><span class="choice__pill">' . self::h($j['jam']) . '</span></span>'
                . '<span class="choice__metrics">'
                . '<span class="choice__metric"><span class="choice__metric-label">Antrean diambil:</span><span class="choice__metric-value">' . (int) $j['diambil'] . '</span></span>'
                . '<span class="choice__metric"><span class="choice__metric-label">Sisa antrean:</span><span class="choice__metric-value choice__metric-value--accent">' . (int) $j['sisa'] . '</span></span>'
                . '</span></span></label>';
        }

        return $o . '</div></fieldset>'
            . '<div class="form-group form-group--keluhan"><textarea class="textarea" id="keluhan" name="keluhan" aria-label="Keluhan" placeholder="Tuliskan keluhan atau gejala yang dirasakan...">' . self::h($this->keluhan) . '</textarea></div>'
            . '<button class="btn btn--form" type="submit"' . ($jadwal === [] ? ' disabled' : '') . '>Simpan &amp; Konfirmasi Booking Layanan</button></form>'; 
    }
}
