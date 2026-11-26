<?php
/**
 * File: views/layout/Layout.php
 * Kerangka halaman HTML mengikuti desain UI (public/css/style.css): header, isi halaman, navigasi bawah.
 * Komponen generik tambahan ada di public/css/app.css.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use HealthPoint\Boundaries\Boundary;

final class Layout
{
    private const FONT = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap';

    /**
     * Halaman biasa (setelah login).
     * @param array<string,mixed>|null $pengguna
     */
    public static function halaman(string $isi, ?array $pengguna, string $judul = 'HealthPoint', string $kelasHalaman = 'page--app'): string
    {
        $aksi = '';
        $nav = '';
        $subjudul = '';
        $role = (string) ($pengguna['role'] ?? '');
        if ($pengguna !== null && in_array($role, ['dokter', 'pengelola', 'petugas', 'pasien'], true)) {
            $subjudul = ($role === 'dokter' || $kelasHalaman === 'page--schedule') ? 'Layanan / Home' : '';
            $aksi = '<div class="app-header__actions"><button class="icon-button" type="button" aria-label="Notifikasi"><span class="icon icon--bell" aria-hidden="true"></span><span class="icon-button__dot" aria-hidden="true"></span></button>'
                . '<span class="avatar-chip" role="img" aria-label="Profil"><span class="icon icon--user" aria-hidden="true"></span></span></div>';
            $nav = '<nav class="bottom-nav" aria-label="Navigasi utama"><div class="bottom-nav__inner">'
                . '<a class="bottom-nav__link" href="/"><span class="icon icon--dashboard" aria-hidden="true"></span><span class="bottom-nav__label">Dashboard</span></a>'
                . '<a class="bottom-nav__link bottom-nav__link--compact" href="/logout"><span class="icon icon--logout" aria-hidden="true"></span><span class="bottom-nav__label">Keluar</span></a>'
                . '</div></nav>';
        } elseif ($pengguna !== null) {
            $aksi = '<div class="app-header__user"><span>' . Boundary::h($pengguna['username']) . ' <small>' . Boundary::h($pengguna['role']) . '</small></span>'
                . '<span class="avatar-chip" role="img" aria-label="Profil"><span class="icon icon--user" aria-hidden="true"></span></span></div>';
            $nav = '<nav class="bottom-nav" aria-label="Navigasi utama"><div class="bottom-nav__inner">'
                . '<a class="bottom-nav__link" href="/"><span class="icon icon--dashboard" aria-hidden="true"></span><span class="bottom-nav__label">Beranda</span></a>'
                . '<a class="bottom-nav__link bottom-nav__link--compact" href="/logout"><span class="icon icon--lock" aria-hidden="true"></span><span class="bottom-nav__label">Keluar</span></a>'
                . '</div></nav>';
        }
        return self::kerangka($judul, $aksi, '<main class="page ' . Boundary::h($kelasHalaman) . '">' . $isi . '</main>' . $nav, $subjudul);
    }

    /** Beranda: sapaan + daftar use case sesuai peran. @param array<string,mixed> $pengguna */
    public static function beranda(array $pengguna, array $ringkasan = []): string
    {
        if (($pengguna['role'] ?? '') === 'dokter') {
            return self::berandaDokter($pengguna);
        }
        if (($pengguna['role'] ?? '') === 'pengelola') {
            return self::berandaPengelola($pengguna);
        }
        if (($pengguna['role'] ?? '') === 'petugas') {
            return self::berandaPetugas($pengguna, $ringkasan);
        }
        if (($pengguna['role'] ?? '') === 'pasien') {
            return self::berandaPasien($pengguna, $ringkasan);
        }
    // ... sisa isi method (pasien) tetap seperti semula, mulai dari "$kartu = '';"
    // ... sisa isi method (pasien) tetap seperti semula, mulai dari "$kartu = '';"
        $kartu = '';
        foreach (Router::menuUntuk((string) $pengguna['role']) as $path => $m) {
            $kartu .= '<a class="service-card" href="' . Boundary::h($path) . '">'
                . '<span class="service-card__icon"><span class="icon icon--' . Boundary::h($m['ikon']) . '" aria-hidden="true"></span></span>'
                . '<span class="service-card__body"><span class="service-card__title">' . Boundary::h($m['judul']) . '</span>'
                . '<span class="service-card__meta">' . Boundary::h($m['uc']) . '</span></span>'
                . '<span class="service-card__aside"><span class="round-action"><span class="icon icon--arrow-right" aria-hidden="true"></span></span></span></a>';
        }
        $isi = '<section class="greeting" aria-label="Sapaan"><span class="greeting__avatar"><span class="icon icon--user" aria-hidden="true"></span></span>'
            . '<div class="greeting__body"><h1 class="greeting__name">Halo, ' . Boundary::h($pengguna['username']) . '</h1>'
            . '<span class="greeting__role">' . Boundary::h(ucfirst((string) $pengguna['role'])) . '</span></div></section>'
            . '<section class="panel" aria-label="Menu"><div class="panel__head"><h2 class="panel__title">Menu</h2></div>'
            . '<p class="panel__subtitle">Pilih layanan yang ingin dibuka</p><div class="panel__list">' . $kartu . '</div></section>';
        return self::halaman($isi, $pengguna, 'Beranda', 'page--dashboard');
    }

    /** Halaman login bergaya UI (tanpa navigasi). */
    /** Halaman login bergaya UI (header dengan notifikasi + profil, tanpa navigasi bawah). */
    public static function login(string $token, string $galat = ''): string
    {
        $pesan = $galat === '' ? '' : '<div class="notice notice--galat login__error" role="alert">' . Boundary::h($galat) . '</div>';
        $aksi = '<div class="app-header__actions"><button class="icon-button" type="button" aria-label="Notifikasi"><span class="icon icon--bell" aria-hidden="true"></span><span class="icon-button__dot" aria-hidden="true"></span></button>'
            . '<span class="avatar-chip" role="img" aria-label="Profil"><span class="icon icon--user" aria-hidden="true"></span></span></div>';

        $isi = '<main class="page page--login"><div class="login"><div class="login__aura" aria-hidden="true"></div><h1 class="login__title">Login Akun</h1>'
            . '<form class="login__form" action="/login" method="post"><input type="hidden" name="_token" value="' . Boundary::h($token) . '">'
            . '<div class="login-card"><div class="form-stack">'
            // Email / No. HP (dipakai sebagai username)
            . '<div class="field"><div class="field__row"><label class="field__label" for="username">Email/Username</label></div><div class="input-wrap">'
            . '<input class="input" id="username" name="username" type="text" autocomplete="username" autofocus required>'
            . '<span class="input-wrap__icon icon icon--id-card" aria-hidden="true"></span></div></div>'
            // Kata sandi + lupa sandi + lihat sandi
            . '<div class="field"><div class="field__row"><label class="field__label" for="password">Kata Sandi</label>'
            . '<a class="field__link" href="#">Lupa Kata Sandi?</a></div><div class="input-wrap">'
            . '<input class="input input--password" id="password" name="password" type="password" autocomplete="current-password" required>'
            . '<span class="input-wrap__icon icon icon--lock" aria-hidden="true"></span>'
            . '<button class="input-wrap__toggle" id="toggle-password" type="button" aria-label="Lihat kata sandi"><span class="icon icon--eye" aria-hidden="true"></span></button></div></div>'
            . '<label class="check"><input class="check__box" type="checkbox" name="remember" value="1"><span>Ingat saya di perangkat ini</span></label>'
            . '<div class="form-actions"><button class="btn btn--login" type="submit">Masuk ke Akun <span class="icon icon--arrow-right" aria-hidden="true"></span></button></div>'
            . '</div></div></form>' . $pesan
            // Kartu pendaftaran akun
            . '<section class="register-card" aria-label="Pendaftaran akun"><span class="register-card__icon"><span class="icon icon--user-check" aria-hidden="true"></span></span>'
            . '<div class="register-card__body"><h2 class="register-card__title">Belum memiliki akun?</h2>'
            . '<a class="register-card__link" href="#">Daftar Akun Baru di sini <span class="icon icon--external-link" aria-hidden="true"></span></a></div></section>'
            . '</div></main>'
                    . '<script>(function(){var b=document.getElementById("toggle-password"),i=document.getElementById("password");'
        . 'if(!b||!i){return;}'
        . 'function sembunyi(){if(i.type!=="password"){i.type="password";}b.setAttribute("aria-pressed","false");b.setAttribute("aria-label","Lihat kata sandi");}'
        . 'sembunyi();'
        // klik ikon mata: tampilkan / sembunyikan sementara
        . 'b.addEventListener("click",function(){var tampak=i.type==="password";i.type=tampak?"text":"password";'
        . 'b.setAttribute("aria-pressed",tampak?"true":"false");b.setAttribute("aria-label",tampak?"Sembunyikan kata sandi":"Lihat kata sandi");});'
        // mengetik lagi / kembali ke halaman: langsung tersembunyi
        . 'i.addEventListener("input",sembunyi);window.addEventListener("pageshow",sembunyi);})();</script>';
        return self::kerangka('Login', $aksi, $isi);
    }

    private static function kerangka(string $judul, string $aksiHeader, string $badan, string $subjudul = ''): string
    {
        $sub = $subjudul === '' ? '' : '<span class="app-header__subtitle">' . Boundary::h($subjudul) . '</span>';
        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . Boundary::h($judul) . ' – HealthPoint</title>'
            . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link href="' . self::FONT . '" rel="stylesheet">'
            . '<link rel="stylesheet" href="/public/css/style.css"><link rel="stylesheet" href="/public/css/app.css"></head><body><div class="app">'
            . '<header class="app-header"><a class="app-header__brand" href="/" aria-label="HealthPoint"><span class="app-header__logo" role="img" aria-label="Logo HealthPoint"></span>'
            . '<span class="app-header__text"><span class="app-header__title">HealthPoint</span>' . $sub . '</span></a>' . $aksiHeader . '</header>'
            . $badan . '</div></body></html>';
    }

    /** Dashboard Dokter: sapaan + panel "Data Layanan". @param array<string,mixed> $pengguna */
    private static function berandaDokter(array $pengguna): string
    {
        $nama = 'dr. ' . ucfirst((string) $pengguna['username']);
        $isi = '<section class="greeting" aria-label="Sapaan"><span class="greeting__avatar"><span class="icon icon--user" aria-hidden="true"></span><span class="greeting__status" aria-hidden="true"></span></span>'
            . '<div class="greeting__body"><h1 class="greeting__name">Halo, ' . Boundary::h($nama) . ' <span class="icon icon--badge-check greeting__verified" aria-hidden="true"></span></h1>'
            . '<span class="greeting__role">Dokter</span></div></section>'
            . '<section class="panel panel--doctor u-mt-26" aria-label="Data Layanan"><div class="panel__head"><h2 class="panel__title">Data Layanan</h2></div>'
            . '<div class="panel__list"><a class="finding-card" href="/uc/09"><span class="finding-card__icon"><span class="icon icon--list-edit" aria-hidden="true"></span></span>'
            . '<span class="finding-card__title">Hasil Pemeriksaan &amp; Tindakan</span></a></div></section>';
        return self::halaman($isi, $pengguna, 'Beranda', 'page--dashboard');
    }

    /** Dashboard Pengelola: sapaan + kartu Laporan Harian + panel Vaksinasi. @param array<string,mixed> $pengguna */
    private static function berandaPengelola(array $pengguna): string
    {
        $nama = ucfirst((string) $pengguna['username']);
        $kartu = static fn (string $href, string $ikon, string $judul): string =>
            '<a class="service-card" href="' . $href . '"><span class="service-card__icon"><span class="icon icon--' . $ikon . '" aria-hidden="true"></span></span>'
            . '<span class="service-card__body service-card__body--center"><span class="service-card__title">' . $judul . '</span></span>'
            . '<span class="service-card__aside service-card__aside--center"><span class="round-action"><span class="icon icon--arrow-right" aria-hidden="true"></span></span></span></a>';

        $isi = '<section class="greeting" aria-label="Sapaan"><span class="greeting__avatar"><span class="icon icon--user" aria-hidden="true"></span><span class="greeting__status" aria-hidden="true"></span></span>'
            . '<div class="greeting__body"><h1 class="greeting__name">Halo, ' . Boundary::h($nama) . ' <span class="icon icon--badge-check greeting__verified" aria-hidden="true"></span></h1>'
            . '<span class="greeting__role">Pengelola Klinik</span></div></section>'
            . '<a class="nav-card" href="/uc/16"><div class="nav-card__row"><span class="nav-card__icon"><span class="icon icon--search-check" aria-hidden="true"></span></span>'
            . '<h2 class="nav-card__title">Laporan Harian</h2></div></a>'
            . '<section class="panel panel--manage" aria-label="Vaksinasi"><div class="panel__head"><h2 class="panel__title">Vaksinasi</h2></div><div class="panel__list">'
            . $kartu('/uc/12', 'archive', 'Kelola Stok Vaksin')
            . $kartu('/uc/17', 'syringe', 'Laporan Pemakaian')
            . '</div></section>';
        return self::halaman($isi, $pengguna, 'Beranda', 'page--dashboard');
    }

    /** Dashboard Petugas. @param array<string,mixed> $pengguna @param array<string,mixed> $ringkasan ['layanan' => list<{idLayanan,namaLayanan,menunggu}>] */
    private static function berandaPetugas(array $pengguna, array $ringkasan): string
    {
        $nama = ucfirst((string) $pengguna['username']);
        $daftar = (array) ($ringkasan['layanan'] ?? []);
        if ($daftar === []) {
            $daftar = [['idLayanan' => 0, 'namaLayanan' => 'Status Pasien', 'menunggu' => 0]];
        }
        $kartu = '';
        foreach ($daftar as $l) {
            $n = strtolower((string) $l['namaLayanan']);
            $ikon = str_contains($n, 'vaksin') ? 'syringe' : (str_contains($n, 'gigi') ? 'tooth' : 'stethoscope');
            $kartu .= '<a class="service-card" href="/uc/04?idLayanan=' . (int) $l['idLayanan'] . '">'
                . '<span class="service-card__icon"><span class="icon icon--' . $ikon . '" aria-hidden="true"></span></span>'
                . '<span class="service-card__body"><span class="service-card__title">' . Boundary::h($l['namaLayanan']) . '</span>'
                . '<span class="service-card__meta">Antrean: ' . (int) $l['menunggu'] . '</span></span>'
                . '<span class="service-card__aside"><span class="status-pill">Buka</span><span class="round-action"><span class="icon icon--arrow-right" aria-hidden="true"></span></span></span></a>';
        }
        $navCard = static fn (string $href, string $judul): string =>
            '<a class="nav-card" href="' . $href . '"><div class="nav-card__row"><span class="nav-card__icon"><span class="icon icon--search-check" aria-hidden="true"></span></span>'
            . '<h2 class="nav-card__title">' . $judul . '</h2></div></a>';

        $isi = '<section class="greeting" aria-label="Sapaan"><span class="greeting__avatar"><span class="icon icon--user" aria-hidden="true"></span><span class="greeting__status" aria-hidden="true"></span></span>'
            . '<div class="greeting__body"><h1 class="greeting__name">Halo, ' . Boundary::h($nama) . ' <span class="icon icon--badge-check greeting__verified" aria-hidden="true"></span></h1>'
            . '<span class="greeting__role">Petugas</span></div></section>'
            . $navCard('/uc/14', 'Cari Data Pasien')
            . '<section class="panel" aria-label="Status Pasien"><div class="panel__head"><h2 class="panel__title">Status Pasien</h2><a class="panel__link" href="/uc/04">Semua</a></div>'
            . '<p class="panel__subtitle">Pilih poli klinik atau jadwal vaksinasi berkala</p><div class="panel__list">' . $kartu . '</div></section>'
            . $navCard('/uc/08', 'Jadwal Dokter');
        return self::halaman($isi, $pengguna, 'Beranda', 'page--dashboard');
    }

    /** Dashboard Pasien. @param array<string,mixed> $pengguna @param array<string,mixed> $ringkasan ['nama' => string, 'layanan' => list<{idLayanan,namaLayanan,menunggu}>] */
    private static function berandaPasien(array $pengguna, array $ringkasan): string
    {
        $nama = (string) ($ringkasan['nama'] ?? '') !== '' ? (string) $ringkasan['nama'] : ucfirst((string) $pengguna['username']);
        $navCard = static fn (string $href, string $judul): string =>
            '<a class="nav-card" href="' . $href . '"><div class="nav-card__row"><span class="nav-card__icon"><span class="icon icon--search-check" aria-hidden="true"></span></span>'
            . '<h2 class="nav-card__title">' . $judul . '</h2></div></a>';

        $kartu = '';
        foreach ((array) ($ringkasan['layanan'] ?? []) as $l) {
            $n = strtolower((string) $l['namaLayanan']);
            $vaksin = str_contains($n, 'vaksin');
            $ikon = $vaksin ? 'syringe' : (str_contains($n, 'gigi') ? 'tooth' : 'stethoscope');
            $href = $vaksin ? '/uc/13' : '/uc/01?idLayanan=' . (int) $l['idLayanan'];
            $kartu .= '<a class="service-card" href="' . $href . '"><span class="service-card__icon"><span class="icon icon--' . $ikon . '" aria-hidden="true"></span></span>'
                . '<span class="service-card__body service-card__body--center"><span class="service-card__title">' . Boundary::h($l['namaLayanan']) . '</span></span>'
                . '<span class="service-card__aside"><span class="status-pill">Buka</span><span class="round-action"><span class="icon icon--arrow-right" aria-hidden="true"></span></span></span></a>';
        }

        $isi = '<section class="greeting" aria-label="Sapaan"><span class="greeting__avatar"><span class="icon icon--user" aria-hidden="true"></span><span class="greeting__status" aria-hidden="true"></span></span>'
            . '<div class="greeting__body"><h1 class="greeting__name">Halo, ' . Boundary::h($nama) . ' <span class="icon icon--badge-check greeting__verified" aria-hidden="true"></span></h1>'
            . '<span class="greeting__role">Pasien</span></div></section>'
            . $navCard('/uc/05', 'Cek Status Antrean')
            . '<section class="panel" aria-label="Pendaftaran Layanan"><div class="panel__head"><h2 class="panel__title">Pendaftaran Layanan</h2><a class="panel__link" href="/uc/01">Semua</a></div>'
            . '<p class="panel__subtitle">Pilih poli klinik atau jadwal vaksinasi berkala</p><div class="panel__list">' . $kartu . '</div></section>'
            . $navCard('/uc/02', 'Booking Saya')
            . $navCard('/uc/07', 'Jadwal Dokter');
        return self::halaman($isi, $pengguna, 'Beranda', 'page--dashboard');
    }
}
