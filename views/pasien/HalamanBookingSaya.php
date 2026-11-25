<?php
/**
 * File: views/pasien/HalamanBookingSaya.php
 * Boundary UC-02 / ACT-02 / SEQ-02 (daftar booking) dan UC-03 / ACT-03 / SEQ-03 (batalkan booking).
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\BookingController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Sesi;
use HealthPoint\Core\TampilanPetugas;
use HealthPoint\Core\Waktu;

final class HalamanBookingSaya extends Boundary
{
    private ?BookingController $kontrol = null;
    private int $idPasien = 0;
    private int $idBookingDipilih = 0;

    public function hubungkan(BookingController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Booking Saya'; }

    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-02 pesan 1-2 */
    public function bukaMenuBookingSaya(): void
    {
        Jejak::catat('SEQ-02', '1', 'Pasien', 'HalamanBookingSaya', 'bukaMenuBookingSaya()');
        Jejak::catat('SEQ-02', '2', 'HalamanBookingSaya', 'BookingController', 'getDaftarBooking(idPasien)');
        $this->tampilkanBookingPasien($this->kontrol->getDaftarBooking($this->idPasien));
    }

    /**
     * SEQ-02 pesan 6. Alternative Flow-1 (ACT-02): daftar kosong -> "Belum ada booking aktif".
     * @param list<array<string,mixed>> $bookingPasien
     */
    public function tampilkanBookingPasien(array $bookingPasien): void
    {
        Jejak::catat('SEQ-02', '6', 'HalamanBookingSaya', 'Pasien', 'tampilkanBookingPasien(bookingPasien)');
        if ($bookingPasien === []) {
            $this->tampilPesan('info', 'Belum ada booking aktif');
            return;
        }
        $this->tampil('tabel', ['judul' => 'Booking aktif', 'baris' => $bookingPasien]);
    }

    /** SEQ-03 pesan 1 */
    public function klikBatalkan(int $idBooking): void
    {
        Jejak::catat('SEQ-03', '1', 'Pasien', 'HalamanBookingSaya', 'klikBatalkan()');
        $this->idBookingDipilih = $idBooking;
        $this->tampilkanKonfirmasi();
    }

    /** SEQ-03 pesan 2 */
    public function tampilkanKonfirmasi(): void
    {
        Jejak::catat('SEQ-03', '2', 'HalamanBookingSaya', 'Pasien', 'tampilkanKonfirmasi()');
        $this->tampilPesan('info', 'Yakin membatalkan booking #' . $this->idBookingDipilih . '?');
    }

    /** SEQ-03 pesan 3-4 */
    public function konfirmasiPembatalan(): string
    {
        Jejak::catat('SEQ-03', '3', 'Pasien', 'HalamanBookingSaya', 'konfirmasiPembatalan()');
        Jejak::catat('SEQ-03', '4', 'HalamanBookingSaya', 'BookingController', 'batalkanBooking(idBooking)');
        return $this->kontrol->batalkanBooking($this->idBookingDipilih);
    }

    /** SEQ-03 pesan 10 (berhasil) / 9a (gagal) */
    public function tampilkanNotifikasi(string $notifikasi, bool $berhasil): void
    {
        Jejak::catat('SEQ-03', $berhasil ? '10' : '9a', 'HalamanBookingSaya', 'Pasien', 'tampilkanNotifikasi()');
        $this->tampilPesan($berhasil ? 'sukses' : 'galat', $notifikasi);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/03') . '<label>ID booking yang dibatalkan <input name="idBooking" type="number" min="1"></label>'
            . '<label><input type="checkbox" name="konfirmasi" value="1"> Ya, batalkan</label><button>Batalkan</button></form>';
    }

    /** "Besok, 02 Okt 2026" / "Hari ini, ..." / "Kamis, ...". */
    private static function labelTanggal(string $ymd): string
    {
        $hariIni = Waktu::hariIni();
        $besok = (new \DateTimeImmutable($hariIni))->modify('+1 day')->format('Y-m-d');
        $awal = $ymd === $hariIni ? 'Hari ini' : ($ymd === $besok ? 'Besok' : Waktu::namaHari($ymd));
        return $awal . ', ' . TampilanPetugas::tanggal($ymd);
    }

    /** Halaman UI-02: "Booking Saya" + kartu booking aktif + tombol batalkan (UC-03). @param array<int,string> $namaLayanan */
    public function renderHalaman(array $namaLayanan): string
    {
        $t = $this->terakhir('tabel');
        $baris = is_array($t) ? (array) ($t['baris'] ?? []) : [];
        $jumlah = count($baris);

        $o = '<div class="section-head"><h1 class="section-head__title">Booking Saya <span class="count-badge" aria-label="' . $jumlah . ' booking">' . $jumlah . '</span></h1>'
            . '<a class="section-head__link" href="#">Riwayat</a></div>';

        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan' && in_array($l['data']['tipe'], ['sukses', 'galat'], true)) {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }
        if ($baris === []) {
            return $o . '<p class="data-empty">Belum ada booking aktif.</p><a class="app-link" href="/uc/01">Daftar layanan baru</a>';
        }

        foreach ($baris as $b) {
            $id = (int) $b['idBooking'];
            $layanan = $namaLayanan[(int) $b['idLayanan']] ?? 'Layanan';
            $vaksin = str_contains(strtolower($layanan), 'vaksin');
            $caption = $vaksin ? 'Booking Vaksinasi' : ($b['jenisBooking'] === 'Kontrol' ? 'Booking Kontrol' : 'Booking ' . $layanan);
            $jam = (string) $b['jamKontrol'] !== '' ? $b['jamKontrol'] . ' WIB' : 'Sesuai nomor antrean';

            $o .= '<article class="booking-card" id="booking-' . $id . '"><div class="booking-card__top">'
                . '<span class="booking-card__icon">' . self::ikon($vaksin ? 'syringe' : 'stethoscope') . '</span>'
                . '<div class="booking-card__body"><p class="booking-card__caption">' . self::h($caption) . '</p><h2 class="booking-card__title">' . self::h($layanan) . '</h2></div>'
                . '<span class="booking-card__status">Terkonfirmasi</span></div>'
                . '<ul class="booking-meta">'
                . '<li class="booking-meta__item booking-meta__item--strong">' . self::ikon('calendar') . self::h(self::labelTanggal((string) $b['tanggalKunjungan'])) . '</li>'
                . '<li class="booking-meta__item booking-meta__item--strong">' . self::ikon('clock') . self::h($jam) . '</li>'
                . '<li class="booking-meta__item">' . self::ikon('building') . 'Klinik Pratama</li>'
                . '<li class="booking-meta__item">' . self::ikon('square-plus') . self::h($layanan) . '</li></ul>'
                . '<div class="booking-ticket"><div class="ticket-row"><div class="ticket-code"><span class="qr-thumb">' . self::ikon('qr') . '</span>'
                . '<div><p class="ticket-code__label">Kode Booking</p><p class="ticket-code__value">' . $id . '</p></div></div>'
                . '<a class="pill-button" href="#booking-' . $id . '">Detail Booking</a></div>'
                . '<form class="cancel-note" method="post" action="/uc/03" onsubmit="return confirm(\'Batalkan booking ini?\')">'
                . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
                . '<input type="hidden" name="idBooking" value="' . $id . '"><input type="hidden" name="konfirmasi" value="1">'
                . '<p class="cancel-note__text">' . self::ikon('alert') . 'Pembatalan maks. 2 jam sebelum kunjungan</p>'
                . '<button class="cancel-note__button" type="submit">Batalkan Booking</button></form></div></article>';
        }
        return $o;
    }
}
