<?php
/**
 * File: views/layout/TampilanDokter.php
 * Pembangun HTML halaman Dokter (UC-09/10/11) mengikuti desain UI. Hanya menyusun string HTML.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use HealthPoint\Boundaries\Boundary;

final class TampilanDokter
{
    private static function ikon(string $nama): string
    {
        return '<span class="icon icon--' . $nama . '" aria-hidden="true"></span>';
    }

    private static function usia(string $tanggalLahir): int
    {
        try {
            return (new \DateTimeImmutable($tanggalLahir))->diff(Waktu::sekarang())->y;
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function kodeJk(string $jk): string
    {
        return str_starts_with(strtolower($jk), 'p') ? 'P' : 'L';
    }

    public static function token(): string
    {
        return '<input type="hidden" name="_token" value="' . Boundary::h(Sesi::token()) . '">';
    }

    public static function notice(string $tipe, string $teks): string
    {
        return '<div class="notice notice--' . Boundary::h($tipe) . '" role="alert">' . Boundary::h($teks) . '</div>';
    }

    public static function toast(string $judul, string $chip): string
    {
        return '<div class="toast" role="status"><div class="toast__main">'
            . '<span class="toast__icon">' . self::ikon('check-circle') . '</span>'
            . '<div><h1 class="toast__title">' . Boundary::h($judul) . '</h1>'
            . '<p class="toast__chip">' . self::ikon('badge-check') . Boundary::h($chip) . '</p></div></div>'
            . '<button class="toast__close" type="button" aria-label="Tutup notifikasi" onclick="this.closest(\'.toast\').remove()">'
            . self::ikon('x') . '</button></div>';
    }

    /** Bagian "Cari Data Pasien" + tombol "Pilih Pasien Ini". */
    public static function pencarian(string $keyword): string
    {
        $hapus = $keyword === ''
            ? ''
            : '<a class="lookup__clear" href="/uc/09" aria-label="Bersihkan pencarian">' . self::ikon('x-circle') . '</a>';
        return '<section aria-label="Pencarian pasien">'
            . '<h1 class="lookup__title">' . self::ikon('list-search') . 'Cari Data Pasien</h1>'
            . '<form action="/uc/09" method="get">'
            . '<div class="lookup__field">'
            . '<input class="lookup__input" type="text" name="keyword" aria-label="Nama atau No. RM pasien" placeholder="Nama atau No. RM" value="' . Boundary::h($keyword) . '">'
            . '<span class="lookup__icon icon icon--search" aria-hidden="true"></span>' . $hapus . '</div>'
            . '<button class="btn btn--teal btn--sm lookup__submit" type="submit">' . self::ikon('clipboard') . 'Pilih Pasien Ini</button>'
            . '</form></section>';
    }

    /** Dipakai bila hasil pencarian > 1 pasien. @param list<array<string,mixed>> $hasil */
    public static function kandidat(array $hasil, string $keyword): string
    {
        $o = '<p class="candidate-hint">Ditemukan ' . count($hasil) . ' pasien, pilih salah satu:</p><div class="candidate-list">';
        foreach ($hasil as $p) {
            $url = '/uc/09?keyword=' . rawurlencode($keyword) . '&idPasien=' . (int) $p['idPasien'];
            $o .= '<a class="candidate" href="' . Boundary::h($url) . '">'
                . '<span class="candidate__name">' . Boundary::h($p['nama']) . '</span>'
                . '<span class="candidate__meta">' . Boundary::h($p['noRM']) . ' · ' . self::usia((string) $p['tanggalLahir']) . ' thn · ' . self::kodeJk((string) $p['jenisKelamin']) . '</span></a>';
        }
        return $o . '</div>';
    }

    private static function vital(string $label, string $nilai, string $unit, bool $alert = false): string
    {
        return '<div class="vital' . ($alert ? ' vital--alert' : '') . '"><span class="vital__label">' . $label . '</span>'
            . '<span class="vital__value">' . Boundary::h($nilai) . '</span><span class="vital__unit">' . Boundary::h($unit) . '</span></div>';
    }

    /**
     * Kartu identitas + keluhan + tanda vital.
     * @param array<string,mixed> $p DataPasien::toArray()   @param array<string,mixed> $k kunjungan (boleh kosong)
     */
    public static function kartuPasien(array $p, array $k): string
    {
        $suhu = $k['suhu'] ?? null;
        $spo2 = $k['spo2'] ?? null;
        $nadi = $k['nadi'] ?? null;
        $td = ($k['tekananDarah'] ?? null) ?: '-';
        $keluhan = ($k['keluhanUtama'] ?? '') !== '' ? (string) $k['keluhanUtama'] : '-';

        return '<section class="patient-card" aria-label="Identitas dan triase pasien">'
            . '<div class="patient-card__head"><span class="patient-card__avatar">' . self::ikon('user') . '</span><div>'
            . '<div class="patient-card__name-row"><h2 class="patient-card__name">' . Boundary::h($p['nama']) . '</h2>'
            . '<span class="tag">' . self::usia((string) $p['tanggalLahir']) . ' thn • ' . self::kodeJk((string) $p['jenisKelamin']) . '</span></div>'
            . '<p class="patient-card__meta">No. RM:<strong>' . Boundary::h($p['noRM']) . '</strong></p></div></div>'
            . '<div class="info-box"><p class="info-box__title">' . self::ikon('clipboard') . 'Keluhan Utama</p>'
            . '<p class="info-box__text">' . Boundary::h($keluhan) . '</p></div>'
            . '<div class="vitals">'
            . self::vital('TD', (string) $td, 'mmHg')
            . self::vital('Nadi', $nadi === null ? '-' : (string) $nadi, 'x/m')
            . self::vital('Suhu', $suhu === null ? '-' : number_format((float) $suhu, 1, '.', ''), '°C', $suhu !== null && (float) $suhu >= 37.5)
            . self::vital('SpO2', $spo2 === null ? '-' : $spo2 . '%', $spo2 === null ? '-' : ((int) $spo2 >= 95 ? 'Normal' : 'Rendah'))
            . '</div></section>';
    }

    private static function tanggalDefault(): string
    {
        $t = Waktu::sekarang()->modify('+1 day');
        while ((int) $t->format('N') >= 6) {
            $t = $t->modify('+1 day');
        }
        return $t->format('Y-m-d');
    }

    /** Kartu "Jadwal Kontrol Lanjutan". @param list<array{id:int,poli:string}> $poli */
    public static function kartuKontrol(array $poli, string $tanggal, int $idLayanan, string $jam): string
    {
        $tanggal = $tanggal === '' ? self::tanggalDefault() : $tanggal;
        $jam = $jam === '' ? '09:00' : $jam;

        $optPoli = '';
        foreach ($poli as $p) {
            $id = (int) $p['id'];
            $optPoli .= '<option value="' . $id . '"' . ($id === $idLayanan ? ' selected' : '') . '>' . Boundary::h($p['poli']) . '</option>';
        }
        $optJam = '';
        for ($m = 8 * 60; $m <= 15 * 60 + 30; $m += 30) {
            $mulai = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $akhir = sprintf('%02d:%02d', intdiv($m + 30, 60), ($m + 30) % 60);
            $optJam .= '<option value="' . $mulai . '"' . ($mulai === $jam ? ' selected' : '') . '>' . $mulai . ' - ' . $akhir . '</option>';
        }

        return '<section class="followup-card" aria-label="Jadwal kontrol lanjutan">'
            . '<div class="followup-card__head"><span class="followup-card__icon">' . self::ikon('calendar-plus') . '</span>'
            . '<h2 class="followup-card__title">Jadwal Kontrol Lanjutan</h2></div>'
            . '<div class="followup-panel">'
            . '<div class="followup-panel__group"><label class="followup-panel__label" for="tanggal-kontrol">Pilih Tanggal:</label>'
            . '<input class="select-plain" type="date" id="tanggal-kontrol" name="tanggal" value="' . Boundary::h($tanggal) . '"></div>'
            . '<div class="followup-panel__group"><label class="followup-panel__label" for="poli-kontrol">Poli:</label>'
            . '<select class="select-plain" id="poli-kontrol" name="idLayanan">' . $optPoli . '</select></div>'
            . '<div class="followup-panel__group"><label class="followup-panel__label" for="jam-kontrol">Jam:</label>'
            . '<select class="select-plain" id="jam-kontrol" name="jam">' . $optJam . '</select></div>'
            . '</div>'
            . '<div class="note-box">' . '<span class="note-box__icon icon icon--info" aria-hidden="true"></span>'
            . '<p class="note-box__text">Tanggal kontrol yang ditetapkan akan otomatis tersinkronisasi ke daftar booking &amp; kontrol pasien saat data disimpan. Pasien akan menerima notifikasi pengingat via aplikasi HealthPoint.</p></div>'
            . '</section>';
    }

    /** Form Hasil Pemeriksaan & Tindakan + toggle kontrol ulang + tombol simpan (post ke UC-11). @param list<array{id:int,poli:string}> $poli */
    public static function formLayanan(int $idPasien, string $diagnosis, string $tindakan, bool $kontrol, string $tanggal, int $idLayanan, string $jam, array $poli): string
    {
        return '<form class="dokter-form" method="post" action="/uc/11">' . self::token()
            . '<input type="hidden" name="idPasien" value="' . $idPasien . '">'
            . '<section class="findings" aria-label="Hasil pemeriksaan dan tindakan">'
            . '<div class="findings__head"><span class="findings__icon">' . self::ikon('list-edit') . '</span><h2 class="findings__title">Hasil Pemeriksaan &amp; Tindakan</h2></div>'
            . '<div class="findings__group"><div class="findings__label-row"><label class="findings__label" for="diagnosis">Diagnosis Utama <span class="req">*</span></label></div>'
            . '<textarea class="findings__field findings__field--single" id="diagnosis" name="diagnosis" rows="1" required>' . Boundary::h($diagnosis) . '</textarea></div>'
            . '<div class="findings__group"><div class="findings__label-row"><label class="findings__label" for="tindakan">Tindakan Medis &amp; Terapi <span class="req">*</span></label>'
            . '<span class="findings__hint">Resep &amp; Prosedur</span></div>'
            . '<textarea class="findings__field findings__field--double" id="tindakan" name="tindakan" rows="2" required>' . Boundary::h($tindakan) . '</textarea></div>'
            . '</section>'
            . '<div class="toggle-row"><span class="toggle-row__label">' . self::ikon('history') . 'Perlu Jadwal Kontrol Ulang?</span>'
            . '<label class="switch"><input class="switch__input" type="checkbox" name="perluKontrol" value="1" role="switch" aria-label="Perlu Jadwal Kontrol Ulang?"' . ($kontrol ? ' checked' : '') . '>'
            . '<span class="switch__track" aria-hidden="true"></span></label></div>'
            . '<div class="followup-slot">' . self::kartuKontrol($poli, $tanggal, $idLayanan, $jam) . '</div>'
            . '<button class="btn btn--primary btn--save" type="submit">' . self::ikon('save') . 'Simpan Data Layanan</button>'
            . '</form>';
    }

    /** Halaman UC-10 berdiri sendiri (tampilan toggle aktif + kartu kontrol). @param list<array{id:int,poli:string}> $poli */
    public static function formKontrolMandiri(array $poli, string $tanggal, int $idLayanan, string $jam): string
    {
        return '<form class="dokter-form" method="post" action="/uc/10">' . self::token()
            . '<div class="toggle-row"><span class="toggle-row__label">' . self::ikon('history') . 'Perlu Jadwal Kontrol Ulang?</span>'
            . '<label class="switch"><input class="switch__input" type="checkbox" role="switch" checked disabled aria-label="Perlu Jadwal Kontrol Ulang?">'
            . '<span class="switch__track" aria-hidden="true"></span></label></div>'
            . self::kartuKontrol($poli, $tanggal, $idLayanan, $jam)
            . '<button class="btn btn--primary btn--save" type="submit">' . self::ikon('save') . 'Validasi Jadwal Kontrol</button></form>';
    }
}