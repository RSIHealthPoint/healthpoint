<?php
/**
 * File: Core/Jejak.php
 * Perekam jejak pesan (Tabel Analisis Messages). Setiap Boundary/Controller mencatat pesan bernomor sesuai
 * Sequence Diagram sehingga urutan eksekusi nyata dapat dibandingkan dengan PDF (lihat tests/run_tests.php).
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Jejak
{
    /** @var list<array{seq:string,no:string,dari:string,ke:string,pesan:string}> */
    private static array $baris = [];

    public static function reset(): void
    {
        self::$baris = [];
    }

    public static function catat(string $seq, string $no, string $dari, string $ke, string $pesan): void
    {
        self::$baris[] = ['seq' => $seq, 'no' => $no, 'dari' => $dari, 'ke' => $ke, 'pesan' => $pesan];
    }

    /** @return list<array{seq:string,no:string,dari:string,ke:string,pesan:string}> */
    public static function ambil(): array
    {
        return self::$baris;
    }

    /** @return list<string> nomor pesan berurutan, mis. ['1','2','3','4','4.1',...] */
    public static function nomor(): array
    {
        return array_map(static fn (array $b): string => $b['no'], self::$baris);
    }

    public static function teks(): string
    {
        return implode("\n", array_map(static fn (array $b): string => sprintf('%s #%s  %s -> %s : %s', $b['seq'], $b['no'], $b['dari'], $b['ke'], $b['pesan']), self::$baris));
    }
}
