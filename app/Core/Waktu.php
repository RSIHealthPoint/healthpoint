<?php
/**
 * File: app/Core/Waktu.php
 * Utilitas tanggal. Jam sistem dapat dikunci (setSekarang) agar skenario pengujian deterministik.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

use DateTimeImmutable;

final class Waktu
{
    private static ?DateTimeImmutable $dikunci = null;

    private const NAMA_HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public static function setSekarang(?string $waktu): void
    {
        self::$dikunci = $waktu === null ? null : new DateTimeImmutable($waktu);
    }

    public static function sekarang(): DateTimeImmutable
    {
        return self::$dikunci ?? new DateTimeImmutable('now');
    }

    public static function hariIni(): string
    {
        return self::sekarang()->format('Y-m-d');
    }

    /** Menurunkan nama hari (Senin..Minggu) dari tanggal Y-m-d (SEQ-10 pesan 9). */
    public static function namaHari(string $tanggal): string
    {
        return self::NAMA_HARI[(int) (new DateTimeImmutable($tanggal))->format('N')];
    }

    public static function tanggalValid(string $tanggal): bool
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        return $d !== false && $d->format('Y-m-d') === $tanggal;
    }

    /**
     * DDL.pdf memberi contoh jam berformat titik ('08.00-12.00', '09.00'); aplikasi/HTML memakai titik dua ('08:00').
     * Penyimpanan ke DB memakai format DDL, sisi aplikasi tetap 'HH:MM'. Berlaku juga untuk rentang 'HH:MM-HH:MM'.
     */
    public static function jamKeDb(string $jam): string
    {
        return str_replace(':', '.', $jam);
    }

    public static function jamDariDb(string $jam): string
    {
        return str_replace('.', ':', $jam);
    }

    public static function jamValid(string $jam): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam);
    }
}
