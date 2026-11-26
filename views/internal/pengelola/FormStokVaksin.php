<?php
/**
 * File: views/internal/pengelola/FormStokVaksin.php
 * Boundary UC-12 / ACT-12 (header SEQ: ACT-14) / SEQ-12. Aktor: Pengelola Klinik.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\StokVaksinController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Sesi;

final class FormStokVaksin extends Boundary
{
    private ?StokVaksinController $kontrol = null;
    private int $idVaksin = 0;
    private string $jumlahTambah = '';
    /** Stok di bawah angka ini ditandai "Menipis". */
    public const AMBANG_MENIPIS = 10;
    private bool $berhasil = false;

    public function hubungkan(StokVaksinController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Manajemen Stok Vaksin'; }

    /** SEQ-12 pesan 1-6 */
    public function bukaHalamanStokVaksin(): void
    {
        Jejak::catat('SEQ-12', '1', 'Pengelola Klinik', 'FormStokVaksin', 'bukaHalamanStokVaksin()');
        Jejak::catat('SEQ-12', '2', 'FormStokVaksin', 'StokVaksinController', 'getDaftarVaksin()');
        $this->tampilkanDaftarStokVaksin($this->kontrol->getDaftarVaksin());
    }

    /** @param list<array<string,mixed>> $daftarVaksin */
    public function tampilkanDaftarStokVaksin(array $daftarVaksin, string $no = '6'): void
    {
        if ($no === '23') { $this->berhasil = true; }
        Jejak::catat('SEQ-12', $no, 'FormStokVaksin', 'Pengelola Klinik', $no === '6' ? 'tampilkanDaftarStokVaksin(daftarVaksin)' : 'tampilkanDaftarStokTerbaru(stokTerbaru)');
        $this->layar = array_values(array_filter($this->layar, static fn (array $l): bool => $l['jenis'] !== 'tabel'));
        $this->tampil('tabel', ['judul' => 'Stok vaksin', 'baris' => array_map(static fn (array $v): array => [
            'id' => $v['idVaksin'], 'nama' => $v['jenisVaksin'], 'tipe' => $v['tipeVaksin'], 'stok' => $v['sisaKuota'], 'sisaKuota' => $v['sisaKuota'], 'status' => $v['status']], $daftarVaksin)]);
    }

    public function pilihVaksin(int $idVaksin): void
    {
        Jejak::catat('SEQ-12', '7', 'Pengelola Klinik', 'FormStokVaksin', 'pilihVaksin(idVaksin)');
        $this->idVaksin = $idVaksin;
    }

    /** SEQ-12 pesan 8-13; proyeksi hanya dihitung bila jumlah berupa bilangan bulat. */
    public function isiTambahStok(string $jumlah): void
    {
        Jejak::catat('SEQ-12', '8', 'Pengelola Klinik', 'FormStokVaksin', 'isiTambahStok(jumlah)');
        $this->jumlahTambah = $jumlah;
        if (preg_match('/^\d+$/', trim($jumlah)) === 1) {
            Jejak::catat('SEQ-12', '9', 'FormStokVaksin', 'StokVaksinController', 'hitungProyeksiKuota(idVaksin, jumlah)');
            $this->tampilkanProyeksiKuota($this->kontrol->hitungProyeksiKuota($this->idVaksin, (int) $jumlah));
        }
    }

    public function tampilkanProyeksiKuota(int $proyeksiKuota): void
    {
        Jejak::catat('SEQ-12', '13', 'FormStokVaksin', 'Pengelola Klinik', 'tampilkanProyeksiKuota(proyeksiKuota)');
        $this->tampil('proyeksi', $proyeksiKuota);
    }

    /** SEQ-12 pesan 14-15 */
    public function klikSimpanStokVaksin(): int|string
    {
        Jejak::catat('SEQ-12', '14', 'Pengelola Klinik', 'FormStokVaksin', 'klikSimpanStokVaksin()');
        Jejak::catat('SEQ-12', '15', 'FormStokVaksin', 'StokVaksinController', 'perbaruiStok(idVaksin, jumlah)');
        return $this->kontrol->perbaruiStok($this->idVaksin, $this->jumlahTambah);
    }

    /** SEQ-12 pesan 19 */
    public function tampilkanPesanKesalahan(string $pesan): void
    {
        Jejak::catat('SEQ-12', '19', 'FormStokVaksin', 'Pengelola Klinik', 'tampilkanPesanKesalahan(pesan)');
        $this->tampilPesan('galat', $pesan);
    }

    public function formHtml(): string
    {
        return self::formTag('/uc/12') . '<label>ID vaksin <input type="number" name="idVaksin" value="' . $this->idVaksin . '"></label>'
            . '<label>Jumlah stok masuk <input name="jumlah" value="' . self::h($this->jumlahTambah) . '"></label><button>Simpan stok</button></form>';
    }

    /** Halaman UI: banner sukses, Daftar Stok Vaksin, form Perbarui Stok Vaksin. */
    public function renderHalaman(): string
    {
        $t = $this->terakhir('tabel');
        $baris = is_array($t) ? (array) ($t['baris'] ?? []) : [];

        $o = '';
        if ($this->berhasil) {
            $o .= '<div class="success-banner" role="status"><span class="success-banner__icon">' . self::ikon('check') . '</span>'
                . '<h1 class="success-banner__title">Pembaruan Stok Berhasil</h1></div>';
        }
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= '<div class="notice notice--' . self::h((string) $l['data']['tipe']) . '" role="alert">' . self::h((string) $l['data']['teks']) . '</div>';
            }
        }

        $idPilih = $this->idVaksin;
        if ($idPilih === 0 && $baris !== []) { $idPilih = (int) $baris[0]['id']; }

        $o .= '<section class="inventory-card" aria-label="Daftar stok vaksin"><div class="card-head">'
            . '<span class="card-head__icon card-head__icon--violet">' . self::ikon('archive') . '</span><h2 class="card-head__title">Daftar Stok Vaksin</h2></div>'
            . '<div class="inv-table"><div class="inv-table__head"><span>Nama Vaksin</span><span>Tipe</span><span>Stok</span><span>Status</span></div>';
        if ($baris === []) { $o .= '<p class="data-empty">Belum ada data vaksin.</p>'; }

        $opsi = '';
        $stokPilih = 0;
        foreach ($baris as $b) {
            $stok = (int) $b['stok'];
            [$status, $rendah] = $stok <= 0 ? ['Habis', true] : ($stok < self::AMBANG_MENIPIS ? ['Menipis', true] : ['Tersedia', false]);
            $o .= '<div class="inv-row' . ($rendah ? ' inv-row--low' : '') . '">'
                . '<span class="inv-row__name">' . self::ikon('syringe') . self::h($b['nama']) . '</span>'
                . '<span class="inv-row__stock">' . self::h($b['tipe']) . '</span>'
                . '<span class="inv-row__min">' . $stok . '</span>'
                . '<span class="inv-row__status">' . $status . '</span></div>';
            $dipilih = (int) $b['id'] === $idPilih;
            if ($dipilih) { $stokPilih = $stok; }
            $opsi .= '<option value="' . (int) $b['id'] . '" data-stok="' . $stok . '"' . ($dipilih ? ' selected' : '') . '>Vaksin ' . self::h($b['nama']) . ' (Stok: ' . $stok . ')</option>';
        }
        $o .= '</div></section>';

        $jumlah = $this->berhasil ? '' : $this->jumlahTambah;
        $proyeksi = $stokPilih + (ctype_digit(trim($jumlah)) && trim($jumlah) !== '' ? (int) trim($jumlah) : 0);

        $o .= '<form class="stock-form" method="post" action="/uc/12">'
            . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
            . '<div class="card-head"><span class="card-head__icon">' . self::ikon('list-edit') . '</span><h2 class="card-head__title">Perbarui Stok Vaksin</h2></div>'
            . '<div class="stock-form__field"><label class="stock-form__label" for="pilih-vaksin">Pilih Vaksin <span class="req">*</span></label>'
            . '<div class="tint-select"><select class="tint-select__control" id="pilih-vaksin" name="idVaksin">' . $opsi . '</select>'
            . '<span class="tint-select__icon icon icon--chevron-down" aria-hidden="true"></span></div></div>'
            . '<div class="stock-form__grid">'
            . '<div><label class="stock-form__label" for="tambah-stok">Tambah Stok <span class="req">*</span></label>'
            . '<div class="number-field"><input class="number-field__input" id="tambah-stok" name="jumlah" type="number" inputmode="numeric" value="' . self::h($jumlah) . '">'
            . '<span class="number-field__unit">Dosis</span></div></div>'
            . '<div><span class="stock-form__label">Proyeksi Kuota</span><div class="projection"><span class="projection__label">Setelah Update:</span>'
            . '<span class="projection__value" id="proyeksi-nilai">' . $proyeksi . ' Dosis</span></div></div></div>'
            . '<button class="btn btn--teal" type="submit">' . self::ikon('check-circle') . 'Simpan Stok Vaksin</button></form>'
            // Proyeksi kuota langsung berubah saat vaksin/jumlah diganti (server tetap menghitung ulang saat simpan).
            . '<script>(function(){var s=document.getElementById("pilih-vaksin"),n=document.getElementById("tambah-stok"),o=document.getElementById("proyeksi-nilai");'
            . 'if(!s||!n||!o){return;}function h(){var p=s.options[s.selectedIndex];var st=p?parseInt(p.getAttribute("data-stok"),10)||0:0;'
            . 'var j=parseInt(n.value,10);o.textContent=(st+(isNaN(j)?0:j))+" Dosis";}s.addEventListener("change",h);n.addEventListener("input",h);h();})();</script>';
        return $o;
}
}
