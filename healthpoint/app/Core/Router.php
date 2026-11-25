<?php
/**
 * File: app/Core/Router.php
 * Pemetaan URL /uc/NN -> Flow, pembatasan peran, login/logout, dan verifikasi CSRF.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use HealthPoint\Boundaries\Boundary;
use HealthPoint\Flows;

final class Router
{
    /** @var array<string,array{0:class-string<Flows\Flow>,1:list<string>}> */
    private const RUTE = [
        '01' => [Flows\Uc01DaftarLayanan::class, ['pasien']],
        '02' => [Flows\Uc02DaftarBookingAktif::class, ['pasien']],
        '03' => [Flows\Uc03BatalkanBooking::class, ['pasien']],
        '04' => [Flows\Uc04UbahStatusPasien::class, ['petugas']],
        '05' => [Flows\Uc05NomorPosisiAntrean::class, ['pasien']],
        '06' => [Flows\Uc06EstimasiWaktuTunggu::class, ['pasien']],
        '07' => [Flows\Uc07JadwalDokter::class, ['pasien']],
        '08' => [Flows\Uc08KelolaJadwalDokter::class, ['petugas']],
        '09' => [Flows\Uc09IsiHasilPemeriksaan::class, ['dokter']],
        '10' => [Flows\Uc10TentukanTanggalKontrol::class, ['dokter']],
        '11' => [Flows\Uc11SimpanDataLayanan::class, ['petugas', 'dokter']],
        '12' => [Flows\Uc12KelolaStokVaksin::class, ['pengelola']],
        '13' => [Flows\Uc13DaftarVaksinasi::class, ['pasien']],
        '14' => [Flows\Uc14CariRiwayatPasien::class, ['petugas']],
        '15' => [Flows\Uc15PilihKandidat::class, ['petugas']],
        '16' => [Flows\Uc16LaporanHarian::class, ['pengelola']],
        '17' => [Flows\Uc17LaporanKuotaVaksin::class, ['pengelola']],
    ];

    /** @return array<string,string> */
    public static function menuUntuk(string $role): array
    {
        $m = [];
        foreach (self::RUTE as $no => [$kelas, $peran]) {
            if (in_array($role, $peran, true)) { $i = $kelas::info(); $m['/uc/' . $no] = $i['uc'] . ' ' . $i['judul']; }
        }
        return $m;
    }

    /** @return array{0:int,1:string,2:?string} [status, html, redirect] */
    public static function tangani(Request $r, Database $db): array
    {
        $pengguna = Auth::pengguna();
        if ($r->path === '/login') { return self::login($r, $db); }
        if ($r->path === '/logout') { Auth::keluar(); return [302, '', '/login']; }
        if ($pengguna === null) { return [302, '', '/login']; }
        if ($r->path === '/') {
            $isi = '<h1>Beranda</h1><p>Peran: <strong>' . Boundary::h($pengguna['role']) . '</strong>. Pilih use case pada menu di atas.</p>';
            return [200, Layout::halaman($isi, $pengguna), null];
        }
        if (preg_match('#^/uc/(\d{2})$#', $r->path, $m) !== 1 || !isset(self::RUTE[$m[1]])) {
            return [404, Layout::halaman('<h1>404</h1><p>Halaman tidak ditemukan.</p>', $pengguna), null];
        }
        [$kelas, $peran] = self::RUTE[$m[1]];
        if (!in_array($pengguna['role'], $peran, true) || (in_array('pasien', $peran, true) && count($peran) === 1 && empty($pengguna['idPasien']))) {
            return [403, Layout::halaman('<h1>403</h1><p>Anda tidak berhak mengakses use case ini.</p>', $pengguna), null];
        }
        if ($r->adaPost() && !Sesi::tokenValid($r->ambil('_token'))) {
            return [400, Layout::halaman('<h1>400</h1><p>Token formulir tidak valid.</p>', $pengguna), null];
        }
        $flow = new $kelas($db, $pengguna);
        return [200, Layout::halaman($flow->web($r), $pengguna, $kelas::info()['uc']), null];
    }

    /** @return array{0:int,1:string,2:?string} */
    private static function login(Request $r, Database $db): array
    {
        $galat = '';
        if ($r->adaPost()) {
            if (!Sesi::tokenValid($r->ambil('_token'))) { return [400, Layout::halaman('<p>Token tidak valid.</p>', null), null]; }
            if (Auth::masuk($db, $r->ambil('username'), $r->ambil('password')) !== null) { return [302, '', '/']; }
            $galat = '<div class="alert alert-galat">Username atau password salah.</div>';
        }
        $isi = '<h1>Login Akun</h1>' . $galat . '<form method="post" action="/login"><input type="hidden" name="_token" value="' . Boundary::h(Sesi::token()) . '">'
            . '<label>Username <input name="username" autofocus></label><label>Password <input type="password" name="password"></label><button>Masuk</button></form>'
            . '<p><small>Akun demo: budi/pasien123, petugas/petugas123, dokter/dokter123, pengelola/pengelola123</small></p>';
        return [200, Layout::halaman($isi, null, 'Login'), null];
    }
}
