<?php
/**
 * File: views/pasien/FormPendaftaranVaksin.php
 * Boundary UC-13 / ACT-13 (header SEQ: ACT-15) / SEQ-13. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\PendaftaranVaksinController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;
use HealthPoint\Core\Sesi;
use HealthPoint\Models\Booking;

final class FormPendaftaranVaksin extends Boundary
{
    private ?PendaftaranVaksinController $kontrol = null;
    private int $idPasien = 0;
    private string $tanggalBooking = '';
    private int $idVaksin = 0;

    public function hubungkan(PendaftaranVaksinController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Pendaftaran Layanan Vaksinasi'; }
    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-13 pesan 1 */
    public function bukaFormPendaftaran(): void
    {
        Jejak::catat('SEQ-13', '1', 'Pasien', 'FormPendaftaranVaksin', 'bukaFormPendaftaran()');
    }

    /** SEQ-13 pesan 2-7 */
    public function pilihTanggalBooking(string $tanggal): void
    {
        Jejak::catat('SEQ-13', '2', 'Pasien', 'FormPendaftaranVaksin', 'pilihTanggalBooking(tanggal)');
        $this->tanggalBooking = $tanggal;
        Jejak::catat('SEQ-13', '3', 'FormPendaftaranVaksin', 'PendaftaranVaksinController', 'getVaksinTersedia(tanggal)');
        $this->tampilkanVaksinDanSisaKuota($this->kontrol->getVaksinTersedia($tanggal));
    }

    /** @param list<array<string,mixed>> $daftarVaksin ; vaksin berkuota 0 ditandai "Habis" (nonaktif) */
    public function tampilkanVaksinDanSisaKuota(array $daftarVaksin): void
    {
        Jejak::catat('SEQ-13', '7', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanVaksinDanSisaKuota(daftarVaksin)');
        $this->tampil('tabel', ['judul' => 'Vaksin dan sisa kuota', 'baris' => array_map(static fn (array $v): array => [
            'id' => $v['idVaksin'], 'nama' => $v['jenisVaksin'], 'status' => $v['status'], 'sisaKuota' => $v['sisaKuota']], $daftarVaksin)]);
    }

    public function pilihJenisVaksin(int $idVaksin): void
    {
        Jejak::catat('SEQ-13', '8', 'Pasien', 'FormPendaftaranVaksin', 'pilihJenisVaksin(idVaksin)');
        $this->idVaksin = $idVaksin;
    }

    /** SEQ-13 pesan 9-10 */
    public function klikSimpanPendaftaran(): int|string
    {
        Jejak::catat('SEQ-13', '9', 'Pasien', 'FormPendaftaranVaksin', 'klikSimpanPendaftaran()');
        Jejak::catat('SEQ-13', '10', 'FormPendaftaranVaksin', 'PendaftaranVaksinController', 'simpanPendaftaran(idPasien, tanggal, idVaksin)');
        return $this->kontrol->simpanPendaftaran($this->idPasien, $this->tanggalBooking, $this->idVaksin);
    }

    /** SEQ-13 pesan 14 (alt) */
    public function tampilkanPesanKuotaHabis(string $pesan): void
    {
        Jejak::catat('SEQ-13', '14', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanPesanKuotaHabis()');
        $this->tampilPesan('galat', $pesan);
    }

    /** SEQ-13 pesan 20 */
    public function tampilkanSisaKuotaTerbaru(int $sisaKuota): void
    {
        Jejak::catat('SEQ-13', '20', 'FormPendaftaranVaksin', 'Pasien', 'tampilkanSisaKuotaTerbaru(sisaKuota)');
        $this->tampilPesan('sukses', 'Pendaftaran vaksinasi tersimpan. Sisa kuota vaksin: ' . $sisaKuota);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/13') . '<label>Tanggal booking <input type="date" name="tanggal" min="' . self::h(Waktu::hariIni()) . '" value="' . self::h($this->tanggalBooking) . '"></label>'
            . '<label>ID vaksin <input type="number" name="idVaksin" value="' . $this->idVaksin . '"></label><button>Simpan pendaftaran</button></form>';
    }

    /** Halaman UI-11: "Pendaftaran Vaksinasi" + tanggal + kartu vaksin + tombol simpan. */
    public function renderHalaman(string $tanggal): string
    {
        $t = $this->terakhir('tabel');
        $baris = is_array($t) ? (array) ($t['baris'] ?? []) : [];

        $o = '<header class="page-heading"><span class="page-heading__icon">' . self::ikon('file-text') . '</span><div>'
            . '<h1 class="page-heading__title">Pendaftaran Vaksinasi</h1><p class="page-heading__subtitle">Formulir Booking Antrean</p></div></header>';
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }

        $dipilih = $this->idVaksin;
        if ($dipilih === 0) {
            foreach ($baris as $b) {
                if ($b['status'] === 'Tersedia') { $dipilih = (int) $b['id']; break; }
            }
        }

        $o .= '<form class="booking-form" method="post" action="/uc/13">'
            . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
            . '<div class="form-group"><label class="form-label" for="tanggal-daftar">Pilih Tanggal Booking</label><div class="date-field">'
            . '<input class="date-field__input" id="tanggal-daftar" name="tanggal" type="date" min="' . self::h(Booking::tanggalMinimal()) . '"'
            . (Booking::tanggalMaksimal() !== null ? ' max="' . self::h((string) Booking::tanggalMaksimal()) . '"' : '')
            . ' value="' . self::h($tanggal) . '"'
            . ' onchange="window.location.search=\'tanggal=\'+this.value">'
            . '<span class="date-field__icon icon icon--calendar" aria-hidden="true"></span></div></div>'
            . '<fieldset class="choice-group"><legend class="form-label">Pilih Jadwal</legend><div class="choice-list">';

        if ($baris === []) {
            $o .= '<p class="data-empty">Belum ada vaksin yang tersedia.</p>';
        }
        foreach ($baris as $b) {
            $habis = $b['status'] !== 'Tersedia';
            $o .= '<label class="choice' . ($habis ? ' choice--disabled' : '') . '"><input class="choice__input" type="radio" name="idVaksin" value="' . (int) $b['id'] . '"'
                . ((int) $b['id'] === $dipilih ? ' checked' : '') . ($habis ? ' disabled' : '') . '>'
                . '<span class="choice__body"><span class="choice__header"><span class="choice__title"><span class="choice__radio" aria-hidden="true"></span>'
                . '<span class="choice__name">Vaksin ' . self::h($b['nama']) . '</span></span><span class="choice__pill">' . self::h($b['status']) . '</span></span>'
                . '<span class="choice__metrics"><span class="choice__metric choice__metric--inline"><span class="choice__metric-label">Sisa Kuota :</span>'
                . '<span class="choice__metric-value">' . (int) $b['sisaKuota'] . '</span></span></span></span></label>';
        }
        return $o . '</div></fieldset><button class="btn btn--form" type="submit">Simpan Pendaftaran</button></form>';
    }
}
