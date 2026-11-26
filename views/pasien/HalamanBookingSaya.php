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
    public function renderHalaman(array $namaLayanan, array $nomorAntrean = []): string
    {
        $t = $this->terakhir('tabel');
        $baris = is_array($t) ? (array) ($t['baris'] ?? []) : [];
        $jumlah = count($baris);

        $o = '<div class="section-head"><h1 class="section-head__title">Booking Saya <span class="count-badge" aria-label="' . $jumlah . ' booking">' . $jumlah . '</span></h1></div>';

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
            $nomor = $nomorAntrean[$id] ?? null;
            if ($nomor === null) {
                $tombolCek = '';
            } elseif ((string) $b['tanggalKunjungan'] === Waktu::hariIni()) {
                $tombolCek = '<a class="pill-button" href="/uc/05?nomor=' . rawurlencode($nomor) . '">Cek Antrean</a>';
            } else {
                $tombolCek = '<span class="pill-button pill-button--off">Cek di hari kunjungan</span>';
            }
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
                . '<div><p class="ticket-code__label">Nomor Antrean</p><p class="ticket-code__value">' . self::h($nomor ?? '-') . '</p></div></div>'
                . $tombolCek . '</div>'
                . '<form class="cancel-note" method="post" action="/uc/03" data-layanan="' . self::h($layanan) . '" data-tanggal="' . self::h(self::labelTanggal((string) $b['tanggalKunjungan'])) . '" data-jam="' . self::h($jam) . '" data-nomor="' . self::h($nomor ?? '-') . '">'
                . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
                . '<input type="hidden" name="idBooking" value="' . $id . '"><input type="hidden" name="konfirmasi" value="1">'
                . '<p class="cancel-note__text">' . self::ikon('alert') . 'Pembatalan maks. 2 jam sebelum kunjungan</p>'
                . '<button class="cancel-note__button" type="submit">Batalkan Booking</button></form></div></article>';
        }
                return $o . self::modalKonfirmasiBatal();
        }

        /** Pop up konfirmasi pembatalan (SEQ-03 pesan 2-3). Satu modal dipakai bersama oleh semua kartu booking. */
        private static function modalKonfirmasiBatal(): string
        {
            return '<div class="modal" id="modal-batal" role="alertdialog" aria-modal="true" aria-labelledby="modal-batal-judul" aria-describedby="modal-batal-isi" hidden>'
                . '<div class="modal__backdrop" data-tutup></div>'
                . '<div class="modal__dialog">'
                . '<span class="modal__icon">' . self::ikon('alert') . '</span>'
                . '<h2 class="modal__title" id="modal-batal-judul">Batalkan booking?</h2>'
                . '<p class="modal__text" id="modal-batal-isi">Booking yang dibatalkan tidak dapat dikembalikan dan nomor antrean Anda akan dilepas.</p>'
                . '<ul class="modal__detail">'
                . '<li class="modal__detail-item modal__detail-item--strong"><span data-isi="layanan"></span></li>'
                . '<li class="modal__detail-item">' . self::ikon('calendar') . '<span data-isi="tanggal"></span></li>'
                . '<li class="modal__detail-item">' . self::ikon('clock') . '<span data-isi="jam"></span></li>'
                . '<li class="modal__detail-item">' . self::ikon('qr') . '<span data-isi="nomor"></span></li>'
                . '</ul>'
                . '<div class="modal__actions">'
                . '<button class="btn btn--sm modal__btn modal__btn--back" type="button" id="modal-batal-kembali" data-tutup>Tidak, Kembali</button>'
                . '<button class="btn btn--sm modal__btn modal__btn--danger" type="button" id="modal-batal-ya">Ya, Batalkan</button>'
                . '</div></div></div>'
                . '<script>(function(){var m=document.getElementById("modal-batal");if(!m){return;}'
                . 'var ya=document.getElementById("modal-batal-ya"),kembali=document.getElementById("modal-batal-kembali"),form=null,pemicu=null;'
                . 'function isi(k,v){var e=m.querySelector("[data-isi=\\""+k+"\\"]");if(e){e.textContent=v;}}'
                . 'function reset(){m.hidden=true;document.body.classList.remove("modal-open");ya.disabled=false;ya.textContent="Ya, Batalkan";}'
                . 'function buka(f,p){form=f;pemicu=p;isi("layanan",f.dataset.layanan);isi("tanggal",f.dataset.tanggal);isi("jam",f.dataset.jam);'
                . 'isi("nomor","Antrean "+f.dataset.nomor);reset();m.hidden=false;document.body.classList.add("modal-open");kembali.focus();}'
                . 'function tutup(){reset();if(pemicu){pemicu.focus();}}'
                . 'document.querySelectorAll("form.cancel-note").forEach(function(f){f.addEventListener("submit",function(e){e.preventDefault();buka(f,f.querySelector("button"));});});'
                . 'm.querySelectorAll("[data-tutup]").forEach(function(el){el.addEventListener("click",tutup);});'
                . 'ya.addEventListener("click",function(){if(!form){return;}ya.disabled=true;ya.textContent="Memproses\\u2026";form.submit();});'
                . 'document.addEventListener("keydown",function(e){if(m.hidden){return;}'
                . 'if(e.key==="Escape"){tutup();return;}'
                . 'if(e.key==="Tab"){var a=document.activeElement;if(e.shiftKey&&a===kembali){e.preventDefault();ya.focus();}else if(!e.shiftKey&&a===ya){e.preventDefault();kembali.focus();}}});'
                . 'window.addEventListener("pageshow",reset);})();</script>';
        }
    
}
