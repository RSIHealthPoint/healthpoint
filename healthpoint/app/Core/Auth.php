<?php
/**
 * File: app/Core/Auth.php
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
        $u = $db->ambilSatu('SELECT * FROM pengguna WHERE username = ?', [$username]);
        if ($u === null || !password_verify($password, (string) $u['password_hash'])) {
            return null;
        }
        $p = ['username' => (string) $u['username'], 'role' => (string) $u['role'], 'idPasien' => $u['id_pasien'] === null ? null : (int) $u['id_pasien']];
        Sesi::set('pengguna', $p);
        return $p;
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
    }
}
