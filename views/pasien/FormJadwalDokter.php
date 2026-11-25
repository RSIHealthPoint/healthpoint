<?php
/**
 * File: views/pasien/FormJadwalDokter.php
 * Boundary UC-07 / ACT-07 (header SEQ: ACT-09) / SEQ-07. Aktor: Pasien.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolJadwalDokter;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Waktu;

final class FormJadwalDokter extends Boundary
{
    private ?KontrolJadwalDokter $kontrol = null;
    private string $hari = '';
    private string $pesan = '';
    /** @var list<array<string,string>> */
    private array $baris = [];

    public function hubungkan(KontrolJadwalDokter $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Jadwal dan Ketersediaan Dokter'; }

    /** SEQ-07 pesan 1-2 */
    public function bukaMenuJadwalDokter(): void
    {
        Jejak::catat('SEQ-07', '1', 'Pasien', 'FormJadwalDokter', 'bukaMenuJadwalDokter()');
        $this->tampilkanPilihanHari();
    }

    public function tampilkanPilihanHari(): void
    {
        Jejak::catat('SEQ-07', '2', 'FormJadwalDokter', 'Pasien', 'tampilkanPilihanHari()');
        $this->tampil('pilihanHari', KontrolJadwalDokter::HARI);
    }

    /** SEQ-07 pesan 3-4 */
    public function pilihHari(string $hari): void
    {
        Jejak::catat('SEQ-07', '3', 'Pasien', 'FormJadwalDokter', 'pilihHari(hari)');
        $this->hari = $hari;
        Jejak::catat('SEQ-07', '4', 'FormJadwalDokter', 'KontrolJadwalDokter', 'lihatJadwal(hari)');
        $this->kontrol->lihatJadwal($hari);
    }

    /** SEQ-07 pesan 4.2.1 (ACT-07 alt: tidak ada dokter praktik) */
    public function tampilkanPesan(string $pesan): void
    {
        Jejak::catat('SEQ-07', '4.2.1', 'FormJadwalDokter', 'Pasien', 'tampilkanPesan(pesan)');
        $this->pesan = $pesan;
        $this->tampilPesan('info', $pesan);
    }

    /** SEQ-07 pesan 4.4.1; status "Tidak Tersedia" ditampilkan apa adanya (UC-07 Alt-2). */
    public function tampilkanJadwal(string $namaDokter, string $jamPraktik, string $statusKetersediaan): void
    {
        Jejak::catat('SEQ-07', '4.4.1', 'FormJadwalDokter', 'Pasien', 'tampilkanJadwal(namaDokter, jamPraktik, statusKetersediaan)');
        $this->baris[] = ['dokter' => $namaDokter, 'jamPraktik' => $jamPraktik, 'status' => $statusKetersediaan];
        $this->layar = array_values(array_filter($this->layar, static fn (array $l): bool => $l['jenis'] !== 'tabel'));
        $this->tampil('tabel', ['judul' => 'Jadwal hari ' . $this->hari, 'baris' => $this->baris]);
    }

    public function formHtml(): string
    {
        $o = '';
        foreach (KontrolJadwalDokter::HARI as $h) { $o .= '<option' . ($h === $this->hari ? ' selected' : '') . '>' . $h . '</option>'; }
        return self::formTag('/uc/07', 'get') . '<label>Hari <select name="hari">' . $o . '</select></label><button>Lihat jadwal</button></form>';
    }

    /** Halaman UI-05: "Ketersediaan Dokter" untuk hari terpilih (bawaan: hari ini). */
    public function renderHalaman(): string
    {
        $siaga = 0;
        foreach ($this->baris as $b) {
            if ($b['status'] === 'Tersedia') { $siaga++; }
        }
        $hariIni = $this->hari === Waktu::namaHari(Waktu::hariIni());

        $opsi = '';
        foreach (KontrolJadwalDokter::HARI as $h) {
            $opsi .= '<option value="' . $h . '"' . ($h === $this->hari ? ' selected' : '') . '>' . $h . '</option>';
        }

        $o = '<header class="availability-head"><h1 class="availability-head__title">Ketersediaan Dokter</h1>'
            . '<p class="availability-head__subtitle">Praktek aktif ' . ($hariIni ? 'hari ini' : 'hari ' . self::h($this->hari)) . ' di Klinik Pratama</p></header>'
            . '<section class="availability-card" aria-label="Daftar dokter">'
            . '<div class="legend"><span class="legend__item"><span class="legend__dot" aria-hidden="true"></span>' . $siaga . ' Dokter Siaga</span>'
            . '<select class="day-select" aria-label="Pilih hari" onchange="window.location.search=\'hari=\'+this.value">' . $opsi . '</select></div>';

        if ($this->baris === []) {
            return $o . '<p class="data-empty">Tidak ada dokter praktik pada hari ini.</p></section>';
        }
        $o .= '<ul class="doctor-list">';
        foreach ($this->baris as $i => $b) {
            $aktif = $b['status'] === 'Tersedia';
            [$mulai, $selesai] = array_pad(explode('-', (string) $b['jamPraktik'], 2), 2, '');
            $o .= '<li class="doctor-card' . ($i === 0 ? ' doctor-card--first' : '') . ($aktif ? '' : ' doctor-card--off') . '">'
                . '<span class="doctor-card__avatar">' . self::ikon('user') . '</span>'
                . '<div class="doctor-card__body"><h2 class="doctor-card__name">' . self::h($b['dokter']) . '</h2>'
                . '<p class="doctor-card__spec">Dokter • Klinik Pratama</p>'
                . '<p class="doctor-card__time">' . ($aktif ? 'Praktek: ' . self::h($mulai) . ' - ' . self::h($selesai) : 'Mulai: ' . self::h($mulai) . ' WIB') . '</p></div>'
                . '<div class="doctor-card__aside"><span class="status-chip ' . ($aktif ? 'status-chip--on' : 'status-chip--off') . '">' . self::h($b['status']) . '</span>'
                . ($aktif ? '<a class="mini-button" href="/uc/01">Antre</a>' : '<span class="mini-button mini-button--off">Nanti</span>') . '</div></li>';
        }
        return $o . '</ul></section>';
    }
}
