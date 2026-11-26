<?php
/**
 * File: Core/Auth.php
 * Autentikasi sederhana (precondition "sudah login" pada UC-02, UC-03, UC-08, UC-09, UC-12, UC-13, UC-14, UC-16, UC-17).
 * Tidak ada pada diagram PDF; ditambahkan agar web dapat dijalankan.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Auth
{
    /** @return array{username:string,role:string,idPasien:?int}|null */
    public static function masuk(Database $db, string $username, string $password): ?array
    {
        $u = $db->ambilSatu('SELECT * FROM Pengguna WHERE username = ?', [$username]);
        if ($u === null || !password_verify($password, (string) $u['passwordHash'])) {
            return null;
        }
        $p = ['username' => (string) $u['username'], 'role' => (string) $u['role'], 'idPasien' => $u['idPasien'] === null ? null : (int) $u['idPasien']];
        Sesi::ganti();                                   // cegah session fixation
        Sesi::set('pengguna', $p);
        return $p;
    }

        /**
     * Pendaftaran akun pasien baru: membuat baris DataPasien + Pengguna (role 'pasien') dalam satu transaksi.
     * noRM dibuat otomatis (RM-0001, RM-0002, ...).
     * @param array<string,string> $in nama, tanggalLahir, jenisKelamin, noTelepon, alamat, username, password, konfirmasi
     * @return array<string,string> galat per kolom (kunci 'umum' = galat non-kolom); array kosong = berhasil
     */
    public static function daftarPasien(Database $db, array $in): array
    {
        $g = [];
        $nama = (string) ($in['nama'] ?? '');
        if ($nama === '') { $g['nama'] = 'Nama lengkap wajib diisi'; }
        elseif (mb_strlen($nama) > 100) { $g['nama'] = 'Nama maksimal 100 karakter'; }

        $tgl = (string) ($in['tanggalLahir'] ?? '');
        if (!Waktu::tanggalValid($tgl)) { $g['tanggalLahir'] = 'Tanggal lahir wajib diisi dengan benar'; }
        elseif ($tgl > Waktu::hariIni() || $tgl < '1900-01-01') { $g['tanggalLahir'] = 'Tanggal lahir tidak valid'; }

        $jk = (string) ($in['jenisKelamin'] ?? '');
        if (!in_array($jk, ['Laki-laki', 'Perempuan'], true)) { $g['jenisKelamin'] = 'Pilih jenis kelamin'; }

        $telp = (string) preg_replace('/^(\+62|62)/', '0', (string) preg_replace('/[\s\-]/', '', (string) ($in['noTelepon'] ?? '')));
        if (preg_match('/^0\d{9,13}$/', $telp) !== 1) { $g['noTelepon'] = 'Nomor telepon tidak valid (contoh: 081234567890)'; }

        $alamat = (string) ($in['alamat'] ?? '');
        if ($alamat === '') { $g['alamat'] = 'Alamat wajib diisi'; }
        elseif (mb_strlen($alamat) > 255) { $g['alamat'] = 'Alamat maksimal 255 karakter'; }

        $username = (string) ($in['username'] ?? '');
        if (preg_match('/^[A-Za-z0-9._@-]{4,50}$/', $username) !== 1) {
            $g['username'] = 'Username 4-50 karakter (huruf, angka, titik, garis bawah, @, atau strip)';
        } elseif ($db->ambilSatu('SELECT idPengguna FROM Pengguna WHERE username = ?', [$username]) !== null) {
            $g['username'] = 'Username sudah dipakai, pilih yang lain';
        }

        $sandi = (string) ($in['password'] ?? '');
        if (strlen($sandi) < 8) { $g['password'] = 'Kata sandi minimal 8 karakter'; }
        elseif (strlen($sandi) > 72) { $g['password'] = 'Kata sandi maksimal 72 karakter'; }
        if (!isset($g['password']) && $sandi !== (string) ($in['konfirmasi'] ?? '')) { $g['konfirmasi'] = 'Konfirmasi kata sandi tidak sama'; }

        if ($g !== []) { return $g; }

        for ($coba = 1; $coba <= 3; $coba++) {
            try {
                $db->mulaiTransaksi();
                $maks = (int) ($db->ambilNilai("SELECT MAX(CAST(SUBSTR(noRM, 4) AS UNSIGNED)) FROM DataPasien WHERE noRM LIKE 'RM-%'") ?? 0);
                $db->jalankan('INSERT INTO DataPasien (noRM, nama, tanggalLahir, jenisKelamin, alamat, noTelepon) VALUES (?,?,?,?,?,?)',
                    ['RM-' . str_pad((string) ($maks + 1), 4, '0', STR_PAD_LEFT), $nama, $tgl, $jk, $alamat, $telp]);
                $idPasien = $db->idTerakhir();
                $db->jalankan('INSERT INTO Pengguna (username, passwordHash, role, idPasien) VALUES (?,?,?,?)',
                    [$username, password_hash($sandi, PASSWORD_DEFAULT), 'pasien', $idPasien]);
                $db->commit();
                return [];
            } catch (\Throwable $e) {
                $db->rollback();
                error_log('[HealthPoint] daftarPasien: ' . $e->getMessage());
            }
        }
        return ['umum' => 'Pendaftaran gagal diproses, silakan coba lagi'];
    }

    /** @return array{username:string,role:string,idPasien:?int}|null */
    public static function pengguna(): ?array
    {
        $p = Sesi::get('pengguna');
        return is_array($p) ? $p : null;
    }

    public static function keluar(): void
    {
        Sesi::hapus('pengguna');
        Sesi::ganti();
    }
}
