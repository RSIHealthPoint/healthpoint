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
        if ($pengguna !== null) {
            $aksi = '<div class="app-header__user"><span>' . Boundary::h($pengguna['username']) . ' <small>' . Boundary::h($pengguna['role']) . '</small></span>'
                . '<span class="avatar-chip" role="img" aria-label="Profil"><span class="icon icon--user" aria-hidden="true"></span></span></div>';
            $nav = '<nav class="bottom-nav" aria-label="Navigasi utama"><div class="bottom-nav__inner">'
                . '<a class="bottom-nav__link" href="/"><span class="icon icon--dashboard" aria-hidden="true"></span><span class="bottom-nav__label">Beranda</span></a>'
                . '<a class="bottom-nav__link bottom-nav__link--compact" href="/logout"><span class="icon icon--lock" aria-hidden="true"></span><span class="bottom-nav__label">Keluar</span></a>'
                . '</div></nav>';
        }
        return self::kerangka($judul, $aksi, '<main class="page ' . Boundary::h($kelasHalaman) . '">' . $isi . '</main>' . $nav);
    }

    /** Beranda: sapaan + daftar use case sesuai peran. @param array<string,mixed> $pengguna */
    public static function beranda(array $pengguna): string
    {
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
    public static function login(string $token, string $galat = ''): string
    {
        $pesan = $galat === '' ? '' : '<div class="notice notice--galat login__error" role="alert">' . Boundary::h($galat) . '</div>';
        $isi = '<main class="page page--login"><div class="login"><div class="login__aura" aria-hidden="true"></div><h1 class="login__title">Login Akun</h1>'
            . '<form class="login__form" action="/login" method="post"><input type="hidden" name="_token" value="' . Boundary::h($token) . '">'
            . '<div class="login-card"><div class="form-stack">'
            . '<div class="field"><div class="field__row"><label class="field__label" for="username">Username</label></div><div class="input-wrap">'
            . '<input class="input" id="username" name="username" type="text" autocomplete="username" autofocus required><span class="input-wrap__icon icon icon--user" aria-hidden="true"></span></div></div>'
            . '<div class="field"><div class="field__row"><label class="field__label" for="password">Kata sandi</label></div><div class="input-wrap">'
            . '<input class="input input--password" id="password" name="password" type="password" autocomplete="current-password" required><span class="input-wrap__icon icon icon--lock" aria-hidden="true"></span></div></div>'
            . '<div class="form-actions"><button class="btn btn--login" type="submit">Masuk ke Akun <span class="icon icon--arrow-right" aria-hidden="true"></span></button></div>'
            . '</div></div></form>' . $pesan
            . '<p class="login__hint">Akun demo: budi / pasien123 &middot; petugas / petugas123 &middot; dokter / dokter123 &middot; pengelola / pengelola123</p>'
            . '</div></main>';
        return self::kerangka('Login', '', $isi);
    }

    private static function kerangka(string $judul, string $aksiHeader, string $badan): string
    {
        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . Boundary::h($judul) . ' – HealthPoint</title>'
            . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link href="' . self::FONT . '" rel="stylesheet">'
            . '<link rel="stylesheet" href="/public/css/style.css"><link rel="stylesheet" href="/public/css/app.css"></head><body><div class="app">'
            . '<header class="app-header"><a class="app-header__brand" href="/" aria-label="HealthPoint"><span class="app-header__logo" role="img" aria-label="Logo HealthPoint"></span>'
            . '<span class="app-header__text"><span class="app-header__title">HealthPoint</span></span></a>' . $aksiHeader . '</header>'
            . $badan . '</div></body></html>';
    }
}
