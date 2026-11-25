<?php
/**
 * File: app/Flows/Flow.php
 * Kelas dasar Flow: menyatukan Use Case Specification (Main/Alternative Flow), Activity Diagram, dan
 * Tabel Analisis Messages Sequence Diagram dalam satu kelas yang dapat dijalankan (web maupun skrip uji).
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\Boundary;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Request;

abstract class Flow
{
    /** @param array<string,mixed> $pengguna hasil Auth::pengguna() */
    public function __construct(protected Database $db, protected array $pengguna = [])
    {
    }

    /** @return array{uc:string,act:string,actDiHeaderSeq:string,seq:string,judul:string,aktor:string} */
    abstract public static function info(): array;

    /** Menangani permintaan HTTP untuk use case ini dan mengembalikan HTML isi halaman. */
    abstract public function web(Request $r): string;

    protected function idPasienLogin(): int
    {
        return (int) ($this->pengguna['idPasien'] ?? 0);
    }

    protected function mulai(): void
    {
        Jejak::reset();
    }

    /** @param array<string,mixed> $data */
    protected function selesai(string $jalur, bool $berhasil, Boundary $b, array $data = []): FlowResult
    {
        return new FlowResult(static::info()['uc'], $jalur, $berhasil, Jejak::ambil(), $b->getLayar(), $data);
    }

    /** Tautan antar-use-case untuk navigasi. */
    protected function tautan(string $path, string $teks): string
    {
        return '<p><a href="' . Boundary::h($path) . '">' . Boundary::h($teks) . '</a></p>';
    }
}
