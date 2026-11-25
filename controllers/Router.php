<?php
/**
 * File: Core/Router.php
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

    /** Ikon (nama kelas `icon--*` di style.css) untuk kartu menu tiap use case. */
    private const IKON = [
        '01' => 'file-text', '02' => 'ticket', '03' => 'x', '04' => 'user-check', '05' => 'ticket', '06' => 'hourglass',
        '07' => 'stethoscope', '08' => 'calendar-check', '09' => 'clipboard', '10' => 'calendar', '11' => 'save',
        '12' => 'package', '13' => 'syringe', '14' => 'search', '15' => 'users', '16' => 'bar-chart', '17' => 'bar-chart',
    ];

    /** @return array<string,array{uc:string,judul:string,ikon:string}> menu per peran, kunci = path */
    public static function menuUntuk(string $role): array
    {
        $m = [];
        foreach (self::RUTE as $no => [$kelas, $peran]) {
            if (in_array($role, $peran, true)) {
                $i = $kelas::info();
                $m['/uc/' . $no] = ['uc' => $i['uc'], 'judul' => $i['judul'], 'ikon' => self::IKON[$no] ?? 'file-text'];
            }
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
        if ($r->path === '/') { return [200, Layout::beranda($pengguna), null]; }
        if (preg_match('#^/uc/(\d{2})$#', $r->path, $m) !== 1 || !isset(self::RUTE[$m[1]])) {
            return [404, self::pesanHalaman('Halaman tidak ditemukan', 'Alamat yang Anda buka tidak ada.', $pengguna), null];
        }
        [$kelas, $peran] = self::RUTE[$m[1]];
        // Peran harus cocok; use case khusus pasien juga butuh akun yang tertaut ke DataPasien.
        $khususPasien = $peran === ['pasien'];
        if (!in_array($pengguna['role'], $peran, true) || ($khususPasien && empty($pengguna['idPasien']))) {
            return [403, self::pesanHalaman('Akses ditolak', 'Anda tidak berhak mengakses use case ini.', $pengguna), null];
        }
        if ($r->adaPost() && !Sesi::tokenValid($r->ambil('_token'))) {
            return [400, self::pesanHalaman('Token tidak valid', 'Token formulir tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.', $pengguna), null];
        }
        try {
            $flow = new $kelas($db, $pengguna);
            $judul = $kelas::info()['uc'];
            $isi = '<span class="page-code">' . Boundary::h($judul) . '</span>' . $flow->web($r);
            return [200, Layout::halaman($isi, $pengguna, $judul), null];
        } catch (\Throwable $e) {
            error_log('[HealthPoint] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return [500, self::pesanHalaman('Terjadi kesalahan', 'Permintaan tidak dapat diproses. Silakan coba lagi; jika berulang, hubungi pengelola sistem.', $pengguna), null];
        }
    }

    /** @param array<string,mixed> $pengguna */
    private static function pesanHalaman(string $judul, string $teks, array $pengguna): string
    {
        $isi = '<h1 class="app-title">' . Boundary::h($judul) . '</h1><div class="notice notice--galat">' . Boundary::h($teks) . '</div>'
            . '<a class="app-link" href="/">Kembali ke beranda</a>';
        return Layout::halaman($isi, $pengguna, $judul);
    }

    /** @return array{0:int,1:string,2:?string} */
    private static function login(Request $r, Database $db): array
    {
        $galat = '';
        if ($r->adaPost()) {
            if (!Sesi::tokenValid($r->ambil('_token'))) { return [400, Layout::login(Sesi::token(), 'Token tidak valid. Muat ulang halaman lalu coba lagi.'), null]; }
            if (Auth::masuk($db, $r->ambil('username'), $r->ambil('password')) !== null) { return [302, '', '/']; }
            $galat = 'Username atau password salah.';
        }
        return [200, Layout::login(Sesi::token(), $galat), null];
    }
}
