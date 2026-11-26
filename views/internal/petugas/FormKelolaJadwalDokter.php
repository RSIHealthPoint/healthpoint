<?php
/**
 * File: views/internal/petugas/FormKelolaJadwalDokter.php
 * Boundary UC-08 / ACT-08 (header SEQ: ACT-10) / SEQ-08. Aktor: Petugas.
 * Catatan: pada Class Diagram seluruh method ditandai Private; di sini dibuat public karena dipanggil Flow/Controller.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\KontrolKelolaJadwal;
use HealthPoint\Core\Jejak;
use HealthPoint\Controllers\KontrolJadwalDokter;
use HealthPoint\Core\Sesi;
use HealthPoint\Core\TampilanPetugas;
use HealthPoint\Core\Waktu;

final class FormKelolaJadwalDokter extends Boundary
{
    private ?KontrolKelolaJadwal $kontrol = null;
    private int $idDokter = 0;
    private string $hari = '';
    private string $jamPraktik = '';
    private string $statusKetersediaan = '';
    private string $pesanHasil = '';
    /** @var list<array<string,mixed>> */
    private array $daftarDokter = [];

    public function hubungkan(KontrolKelolaJadwal $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Kelola Jadwal Dokter'; }

    /** SEQ-08 pesan 1-3 */
    public function bukaMenuKelolaJadwal(): void
    {
        Jejak::catat('SEQ-08', '1', 'Petugas', 'FormKelolaJadwalDokter', 'bukaMenuKelolaJadwal()');
        Jejak::catat('SEQ-08', '2', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'getDaftarDokter()');
        $this->tampilkanFormJadwal($this->kontrol->getDaftarDokter());
    }

    /** @param list<array<string,mixed>> $daftarDokter */
    public function tampilkanFormJadwal(array $daftarDokter): void
    {
        Jejak::catat('SEQ-08', '3', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanFormJadwal(daftarDokter)');
        $this->daftarDokter = $daftarDokter;
    }

    public function pilihDokterDanHari(int $idDokter, string $hari): void
    {
        Jejak::catat('SEQ-08', '4', 'Petugas', 'FormKelolaJadwalDokter', 'pilihDokterDanHari(idDokter, hari)');
        $this->idDokter = $idDokter;
        $this->hari = $hari;
    }

    public function isiJadwal(string $jamPraktik, string $statusKetersediaan): void
    {
        Jejak::catat('SEQ-08', '5', 'Petugas', 'FormKelolaJadwalDokter', 'isiJadwal(jamPraktik, statusKetersediaan)');
        $this->jamPraktik = $jamPraktik;
        $this->statusKetersediaan = $statusKetersediaan;
    }

    /** SEQ-08 pesan 6-7 */
    public function klikSimpan(): string
    {
        Jejak::catat('SEQ-08', '6', 'Petugas', 'FormKelolaJadwalDokter', 'klikSimpan()');
        Jejak::catat('SEQ-08', '7', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'simpanJadwal(idDokter, hari, jamPraktik, statusKetersediaan)');
        return $this->kontrol->simpanJadwal($this->idDokter, $this->hari, $this->jamPraktik, $this->statusKetersediaan);
    }

    /** SEQ-08 pesan 7.2.2 (ACT-08 alt D): petugas memperbaiki data lalu mengulang pesan 7. */
    public function perbaikiData(string $jamPraktik, string $statusKetersediaan): string
    {
        Jejak::catat('SEQ-08', '7.2.2', 'Petugas', 'FormKelolaJadwalDokter', 'perbaikiData(jamPraktik, statusKetersediaan)');
        $this->jamPraktik = $jamPraktik;
        $this->statusKetersediaan = $statusKetersediaan;
        Jejak::catat('SEQ-08', '7', 'FormKelolaJadwalDokter', 'KontrolKelolaJadwal', 'simpanJadwal(idDokter, hari, jamPraktik, statusKetersediaan)');
        return $this->kontrol->simpanJadwal($this->idDokter, $this->hari, $this->jamPraktik, $this->statusKetersediaan);
    }

    public function tampilkanPesanKesalahan(string $p): void { Jejak::catat('SEQ-08', '7.2.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanKesalahan(pesanKesalahan)'); $this->pesanHasil = $p; $this->tampilPesan('galat', $p); }
    public function tampilkanPesanGagal(string $p): void { Jejak::catat('SEQ-08', '7.4.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanGagal(pesanGagal)'); $this->pesanHasil = $p; $this->tampilPesan('galat', $p); }
    public function tampilkanPesanBerhasil(string $p): void { Jejak::catat('SEQ-08', '7.5.1', 'FormKelolaJadwalDokter', 'Petugas', 'tampilkanPesanBerhasil(pesanBerhasil)'); $this->pesanHasil = $p; $this->tampilPesan('sukses', $p); }

    public function formHtml(): string
    {
        $o = '';
        foreach ($this->daftarDokter as $d) { $o .= '<option value="' . (int) $d['idDokter'] . '">' . self::h($d['namaDokter']) . '</option>'; }
        return self::formTag('/uc/08') . '<label>Dokter <select name="idDokter">' . $o . '</select></label>'
            . '<label>Hari <input name="hari" placeholder="Senin" value="' . self::h($this->hari) . '"></label>'
            . '<label>Jam praktik <input name="jamPraktik" placeholder="08:00-12:00" value="' . self::h($this->jamPraktik) . '"></label>'
            . '<label>Status <select name="status"><option>Tersedia</option><option>Tidak Tersedia</option></select></label><button>Simpan</button></form>';
    }

    /** Halaman UI-06: "Formulir Kelola Jadwal & Ketersediaan". */
    public function renderHalaman(string $tanggal = ''): string
    {
        $o = '<h1 class="page-title">Formulir Kelola Jadwal &amp; Ketersediaan</h1>';
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= TampilanPetugas::notice((string) $l['data']['tipe'], (string) $l['data']['teks']);
            }
        }

        $opsiDokter = '';
        foreach ($this->daftarDokter as $d) {
            $opsiDokter .= '<option value="' . (int) $d['idDokter'] . '"' . ((int) $d['idDokter'] === $this->idDokter ? ' selected' : '') . '>' . self::h($d['namaDokter']) . '</option>';
        }
        $tanggal = Waktu::tanggalValid($tanggal) ? $tanggal : Waktu::hariIni();

        [$mulai, $selesai] = array_pad(explode('-', $this->jamPraktik, 2), 2, '');
        $mulai = preg_match('/^\d{2}:\d{2}$/', $mulai) === 1 ? $mulai : '08:00';
        $selesai = preg_match('/^\d{2}:\d{2}$/', $selesai) === 1 ? $selesai : '14:00';
        $status = $this->statusKetersediaan !== '' ? $this->statusKetersediaan : 'Tersedia';

        $chip = '';
        foreach ([['Tersedia', 'check-circle'], ['Istirahat', 'coffee'], ['Cuti', 'x']] as [$nilai, $ikon]) {
            $chip .= '<label class="toggle-chip"><input class="toggle-chip__input" type="radio" name="status" value="' . $nilai . '"' . ($status === $nilai ? ' checked' : '') . '>'
                . '<span class="toggle-chip__body">' . self::ikon($ikon) . $nilai . '</span></label>';
        }

        return $o . '<form class="schedule-card" method="post" action="/uc/08">'
            . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
            . '<div class="field-group"><div class="field-group__head"><label class="mini-label" for="dokter-poli">Pilih Dokter &amp; Spesialisasi:</label>'
            . '<span class="mini-label mini-label--muted">Shift Aktif</span></div>'
            . '<div class="select-field"><select class="select-field__control" id="dokter-poli" name="idDokter">' . $opsiDokter . '</select>'
            . '<span class="select-field__icon icon icon--chevron-down" aria-hidden="true"></span></div></div>'
            . '<div class="field-group field-group--wide"><label class="mini-label" for="tanggal-praktik">Tanggal Praktik:</label>'
            . '<div class="chip-row"><div class="select-chip">' . self::ikon('calendar')
            . '<input class="select-chip__control" id="tanggal-praktik" name="tanggal" type="date" min="' . self::h(Waktu::hariIni()) . '" value="' . self::h($tanggal) . '" required></div></div></div>'
            . '<div class="field-group"><span class="mini-label">Jam Praktik &amp; Kuota:</span><div class="time-row">'
            . '<div class="time-field"><label class="mini-label mini-label--muted" for="jam-mulai">Jam Mulai:</label><input class="time-field__input" id="jam-mulai" name="jamMulai" type="time" value="' . self::h($mulai) . '"></div>'
            . '<div class="time-field"><label class="mini-label mini-label--muted" for="jam-selesai">Jam Selesai:</label><input class="time-field__input" id="jam-selesai" name="jamSelesai" type="time" value="' . self::h($selesai) . '"></div>'
            . '</div></div>'
            . '<fieldset class="field-group"><legend class="mini-label">Status Ketersediaan Praktik:</legend><div class="chip-row">' . $chip . '</div></fieldset>'
            . '<button class="btn btn--tiny" type="submit">' . self::ikon('save') . 'Simpan Jadwal Dokter</button></form>';
    }
}
