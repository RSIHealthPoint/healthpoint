<?php
/**
 * File: app/Flows/FlowResult.php
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
}
