<?php
/**
 * File: Flows/Flow.php
 * Kelas dasar Flow: menyatukan Use Case Specification (Main/Alternative Flow), Activity Diagram, dan
 * Tabel Analisis Messages Sequence Diagram dalam satu kelas yang dapat dijalankan (web maupun skrip uji).
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

use HealthPoint\Boundaries\Boundary;
use HealthPoint\Core\Database;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Request;
use HealthPoint\Core\Waktu;

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
        return '<a class="app-link" href="' . Boundary::h($path) . '">' . Boundary::h($teks) . '</a>';
    }

    /** @return list<array{id:int,poli:string}> poli tersedia untuk pilihan jadwal kontrol */
    protected function daftarPoliTersedia(): array
    {
        $poli = [];
        foreach ((new \HealthPoint\Models\Layanan($this->db))->getDaftarLayanan() as $l) {
            if ($l->isTersedia()) {
                $a = $l->toArray();
                $poli[] = ['id' => (int) $a['idLayanan'], 'poli' => (string) $a['namaLayanan']];
            }
        }
        return $poli;
    }

    /** Kunjungan selesai pasien (terbaru dulu), untuk ringkasan riwayat. @return list<array{tanggal:string,layanan:string,keluhan:string}> */
    protected function riwayatPasien(int $idPasien): array
    {
        $baris = $this->db->ambilSemua(
            "SELECT rk.tanggalKunjungan, l.namaLayanan, rk.keluhanUtama FROM RiwayatKunjungan rk
            JOIN Layanan l ON l.idLayanan = rk.idLayanan
            WHERE rk.idPasien = ? AND rk.diagnosis <> '' ORDER BY rk.tanggalKunjungan DESC, rk.idKunjungan DESC",
            [$idPasien]
        );
        return array_map(static fn (array $r): array => [
            'tanggal' => (string) $r['tanggalKunjungan'], 'layanan' => (string) $r['namaLayanan'], 'keluhan' => (string) $r['keluhanUtama'],
        ], $baris);
    }

    /** @return array<int,string> idLayanan => namaLayanan */
    protected function petaLayanan(): array
    {
        $peta = [];
        foreach ($this->db->ambilSemua('SELECT idLayanan, namaLayanan FROM Layanan') as $r) {
            $peta[(int) $r['idLayanan']] = (string) $r['namaLayanan'];
        }
        return $peta;
    }

    /** Nomor antrean terbaru milik pasien pada tanggal kunjungan tertentu (null bila belum ada). */
    protected function nomorAntreanTerbaru(int $idPasien, string $tanggal): ?string
    {
        $n = $this->db->ambilNilai(
            'SELECT a.nomorAntrean FROM Antrean a JOIN Booking b ON b.idBooking = a.idBooking
            WHERE b.idPasien = ? AND b.tanggalKunjungan = ? ORDER BY a.idAntrean DESC LIMIT 1',
            [$idPasien, $tanggal]
        );
        return $n === null ? null : (string) $n;
    }

    /** @return array<int,string> idBooking => nomorAntrean milik pasien */
    protected function petaNomorAntrean(int $idPasien): array
    {
        $peta = [];
        foreach ($this->db->ambilSemua(
            'SELECT a.idBooking, a.nomorAntrean FROM Antrean a JOIN Booking b ON b.idBooking = a.idBooking WHERE b.idPasien = ?',
            [$idPasien]
        ) as $r) {
            $peta[(int) $r['idBooking']] = (string) $r['nomorAntrean'];
        }
        return $peta;
    }

    /**
     * Kartu "Pilih Jadwal" untuk booking: dokter yang praktik pada hari tanggal terpilih.
     * Booking tidak menyimpan dokter, jadi "antrean diambil" dibagi proporsional menurut kapasitas jam praktik tiap dokter.
     * @return list<array{dokter:string,jam:string,diambil:int,sisa:int}>
     */
    protected function jadwalBooking(int $idLayanan, string $tanggal): array
    {
        if (!Waktu::tanggalValid($tanggal)) { return []; }
        $baris = $this->db->ambilSemua(
            "SELECT d.namaDokter, j.jamPraktik FROM JadwalDokter j JOIN Dokter d ON d.idDokter = j.idDokter
            WHERE j.hari = ? AND j.statusKetersediaan = 'Tersedia' ORDER BY j.jamPraktik, d.idDokter",
            [Waktu::namaHari($tanggal)]
        );
        $diambil = (int) $this->db->ambilNilai(
            "SELECT COUNT(*) FROM Booking WHERE idLayanan = ? AND tanggalKunjungan = ? AND status = 'Aktif'",
            [$idLayanan, $tanggal]
        );
        $kuota = [];
        foreach ($baris as $b) {
            [$mulai, $selesai] = array_pad(explode('-', Waktu::jamDariDb((string) $b['jamPraktik']), 2), 2, '');
            $jam = max(1, (int) round((strtotime($selesai) - strtotime($mulai)) / 3600));
            $kuota[] = $jam * \HealthPoint\Models\Booking::KAPASITAS_PER_JAM;
        }
        $total = array_sum($kuota);
        $jadwal = [];
        foreach ($baris as $i => $b) {
            $ambil = $total > 0 ? (int) round($diambil * $kuota[$i] / $total) : 0;
            $jadwal[] = [
                'dokter' => (string) $b['namaDokter'],
                'jam' => str_replace('-', ' - ', Waktu::jamDariDb((string) $b['jamPraktik'])),
                'diambil' => $ambil,
                'sisa' => max(0, $kuota[$i] - $ambil),
            ];
        }
        return $jadwal;
    }
}
