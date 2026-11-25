<?php
/**
 * File: views/pasien/FormInformasiAntrean.php
 * Boundary UC-05 / ACT-05 (header SEQ: ACT-07) / SEQ-05. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolAntrean;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\TampilanPetugas;
use HealthPoint\Core\Waktu;

final class FormInformasiAntrean extends Boundary
{
    private ?KontrolAntrean $kontrol = null;
    private string $nomorAntrean = '';
    private string $nomorBerjalan = '';
    private int $posisi = 0;
    private string $pesanKesalahan = '';

    public function hubungkan(KontrolAntrean $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Informasi Antrean'; }

    /** SEQ-05 pesan 1-2 */
    public function bukaMenuInformasiAntrean(): void
    {
        Jejak::catat('SEQ-05', '1', 'Pasien', 'FormInformasiAntrean', 'bukaMenuInformasiAntrean()');
        $this->tampilkanFormInput();
    }

    public function tampilkanFormInput(): void
    {
        Jejak::catat('SEQ-05', '2', 'FormInformasiAntrean', 'Pasien', 'tampilkanFormInput()');
    }

    /** SEQ-05 pesan 3-4 (5 = perbaikiNomorAntrean memanggil ulang dari pesan 4) */
    public function inputNomorAntrean(string $nomorAntrean): void
    {
        Jejak::catat('SEQ-05', '3', 'Pasien', 'FormInformasiAntrean', 'inputNomorAntrean(nomorAntrean)');
        $this->nomorAntrean = $nomorAntrean;
        Jejak::catat('SEQ-05', '4', 'FormInformasiAntrean', 'KontrolAntrean', 'lihatPosisiAntrean(nomorAntrean)');
        $this->kontrol->lihatPosisiAntrean($nomorAntrean);
    }

    /** SEQ-05 pesan 5 (ACT-05 alt C): pasien memperbaiki input. */
    public function perbaikiNomorAntrean(string $nomorBaru): void
    {
        Jejak::catat('SEQ-05', '5', 'Pasien', 'FormInformasiAntrean', 'perbaikiNomorAntrean(nomorBaru)');
        $this->nomorAntrean = $nomorBaru;
        Jejak::catat('SEQ-05', '4', 'FormInformasiAntrean', 'KontrolAntrean', 'lihatPosisiAntrean(nomorAntrean)');
        $this->kontrol->lihatPosisiAntrean($nomorBaru);
    }

    /** SEQ-05 pesan 4.5.1 */
    public function tampilkanInformasi(string $nomorBerjalan, int $posisi): void
    {
        Jejak::catat('SEQ-05', '4.5.1', 'FormInformasiAntrean', 'Pasien', 'tampilkanInformasi(nomorBerjalan, posisi)');
        $this->nomorBerjalan = $nomorBerjalan;
        $this->posisi = $posisi;
        $this->tampil('rincian', ['judul' => 'Posisi antrean', 'data' => ['nomorAntrean' => $this->nomorAntrean, 'nomorBerjalan' => $nomorBerjalan, 'posisi' => $posisi]]);
    }

    /** SEQ-05 pesan 4.6.1 (dan 4a untuk "antrean belum dimulai") */
    public function tampilkanPesanKesalahan(string $pesanKesalahan, string $no = '4.6.1'): void
    {
        Jejak::catat('SEQ-05', $no, 'FormInformasiAntrean', 'Pasien', 'tampilkanPesanKesalahan(pesanKesalahan)');
        $this->pesanKesalahan = $pesanKesalahan;
        $this->tampilPesan($no === '4a' ? 'info' : 'galat', $pesanKesalahan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/05') . '<label>Nomor antrean <input name="nomor" value="' . self::h($this->nomorAntrean) . '"></label><button>Lihat posisi</button></form>';
    }

    /** @return array<string,mixed>|null data posisi antrean bila nomor ditemukan */
    public function getInformasi(): ?array
    {
        $d = $this->terakhir('rincian');
        return is_array($d) ? (array) ($d['data'] ?? []) : null;
    }

    /**
     * Halaman UI-04: form cek antrean + tiket antrean dengan estimasi tunggu.
     * @param array{namaLayanan?:string,statusAntrean?:string,dokter?:string} $detail
     */
    public function renderHalaman(array $detail, ?int $estimasi): string
    {
        $o = '<header class="status-head"><div class="status-head__title"><span class="status-head__icon">' . self::ikon('search-check') . '</span>'
            . '<h1>Cek Status Antrean</h1></div><span class="live-badge">Live Track</span></header>'
            . '<section class="status-card" aria-label="Hasil pengecekan antrean">'
            . '<form class="status-search" action="/uc/05" method="get"><div class="status-search__row">'
            . '<div class="status-search__field status-search__field--ticket"><label class="status-search__label" for="nomor-antrean">Nomor Antrean</label>'
            . '<div class="status-search__control">' . self::ikon('ticket')
            . '<input class="status-search__input status-search__input--ticket" id="nomor-antrean" name="nomor" type="text" value="' . self::h($this->nomorAntrean === '' ? '' : TampilanPetugas::nomorAntrean($this->nomorAntrean)) . '"></div></div>'
            . '<div class="status-search__field"><label class="status-search__label" for="tanggal-kunjungan">Tanggal Kunjungan</label>'
            . '<div class="status-search__control">' . self::ikon('calendar')
            . '<input class="status-search__input status-search__input--date" id="tanggal-kunjungan" type="text" readonly value="' . self::h(TampilanPetugas::tanggal(Waktu::hariIni())) . '" title="Antrean berlaku untuk hari ini"></div></div>'
            . '</div><button class="btn btn--search" type="submit">' . self::ikon('search') . 'Cari Status Antrean</button></form>';

        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }

        $info = $this->getInformasi();
        if ($info === null) {
            return $o . '</section>';
        }

        $nomor = TampilanPetugas::nomorAntrean((string) $info['nomorAntrean']);
        $berjalan = TampilanPetugas::nomorAntrean((string) $info['nomorBerjalan']);
        $diDepan = max(0, (int) $info['posisi'] - 1);
        $layanan = (string) ($detail['namaLayanan'] ?? 'Layanan');
        $dilayani = ($detail['statusAntrean'] ?? '') === 'Dilayani';
        $teksEstimasi = $estimasi === null ? '-' : ($estimasi === 0 ? 'Segera' : $estimasi . ' Menit');

        $o .= '<p class="result-banner">' . self::ikon('check-circle') . 'Antrean Ditemukan: ' . self::h($layanan) . '</p>'
            . '<article class="queue-ticket" aria-label="Tiket antrean ' . self::h($nomor) . '">'
            . '<div class="queue-ticket__ticker"><span class="live-chip"><span class="live-chip__dot" aria-hidden="true"></span>Dipanggil Saat Ini: ' . self::h($berjalan) . '</span>'
            . '<span class="room-chip">' . self::h($layanan) . '</span></div>'
            . '<p class="queue-ticket__label">Nomor Antrean Anda</p><p class="queue-ticket__number">' . self::h($nomor) . '</p>'
            . '<p class="queue-ticket__ahead">(' . ($diDepan === 0 ? 'Giliran Anda' : $diDepan . ' Pasien di Depan') . ')</p>'
            . '<div class="queue-ticket__wait"><p class="queue-ticket__wait-label">Estimasi Tunggu</p><p class="wait-pill">' . self::ikon('hourglass') . self::h($teksEstimasi) . '</p></div>';
        if (($detail['dokter'] ?? '') !== '') {
            $o .= '<p class="queue-ticket__meta">' . self::ikon('stethoscope') . self::h($detail['dokter']) . '</p>';
        }
        return $o . '<p class="queue-ticket__status"><span class="queue-ticket__status-dot" aria-hidden="true"></span>'
            . ($dilayani ? 'Sedang Dilayani' : 'Menunggu Pemanggilan') . '</p></article></section>';
    }
}
