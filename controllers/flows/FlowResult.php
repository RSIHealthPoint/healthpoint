<?php
/**
 * File: Flows/FlowResult.php
 * Hasil eksekusi satu jalur skenario (Main Flow / Alternative Flow).
 */
declare(strict_types=1);

namespace HealthPoint\Flows;

final class FlowResult
{
    /**
     * @param list<array{seq:string,no:string,dari:string,ke:string,pesan:string}> $jejak
     * @param list<array{jenis:string,data:mixed}> $layar
     * @param array<string,mixed> $data
     */
    public function __construct(
        public readonly string $uc,
        public readonly string $jalur,
        public readonly bool $berhasil,
        public readonly array $jejak,
        public readonly array $layar,
        public readonly array $data = [],
    ) {
    }

    /** @return list<string> */
    public function nomorPesan(): array
    {
        return array_map(static fn (array $b): string => $b['no'], $this->jejak);
    }

    public function pesanTerakhir(): ?string
    {
        for ($i = count($this->layar) - 1; $i >= 0; $i--) {
            if ($this->layar[$i]['jenis'] === 'pesan') {
                return (string) $this->layar[$i]['data']['teks'];
            }
        }
        return null;
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
}
