<?php
/**
 * File: views/internal/petugas/HalamanStatusPasien.php
 * Boundary UC-04 / ACT-04 (header SEQ: ACT-06) / SEQ-04. Aktor: Petugas.
 */
declare(strict_types=1);

namespace HealthPoint\Boundaries;

use HealthPoint\Controllers\StatusPasienController;
use HealthPoint\Core\Jejak;
use HealthPoint\Core\Sesi;
use HealthPoint\Core\TampilanPetugas;

final class HalamanStatusPasien extends Boundary
{
    private ?StatusPasienController $kontrol = null;
    private int $idPasien = 0;
    private string $statusKedatangan = '';

    public function hubungkan(StatusPasienController $k): void { $this->kontrol = $k; }
    public function judul(): string { return 'Status Kedatangan Pasien'; }
    public function setIdPasien(int $idPasien): void { $this->idPasien = $idPasien; }

    /** SEQ-04 pesan 1-2 */
    public function pilihMenuStatusPasien(): void
    {
        Jejak::catat('SEQ-04', '1', 'Petugas', 'HalamanStatusPasien', 'pilihMenuStatusPasien()');
        Jejak::catat('SEQ-04', '2', 'HalamanStatusPasien', 'StatusPasienController', 'ambilStatusPasien(idPasien)');
        $this->kontrol->ambilStatusPasien($this->idPasien);
    }

    /** SEQ-04 pesan 4 (kirim) -> 5 (tampil) */
    public function tampilkanStatusSaatIni(string $statusKedatangan): void
    {
        Jejak::catat('SEQ-04', '5', 'HalamanStatusPasien', 'Petugas', 'tampilkanStatusSaatIni(statusKedatangan)');
        $this->statusKedatangan = $statusKedatangan;
        $this->tampil('rincian', ['judul' => 'Status saat ini', 'data' => ['idPasien' => $this->idPasien, 'status' => $statusKedatangan]]);
    }

    /** SEQ-04 pesan 6-7 */
    public function pilihStatusKedatangan(string $statusBaru): void
    {
        Jejak::catat('SEQ-04', '6', 'Petugas', 'HalamanStatusPasien', 'pilihStatusKedatangan(statusBaru)');
        Jejak::catat('SEQ-04', '7', 'HalamanStatusPasien', 'StatusPasienController', 'ubahStatus(idPasien, statusBaru)');
        $this->kontrol->ubahStatus($this->idPasien, $statusBaru);
    }

    /** SEQ-04 pesan 10 */
    public function tampilkanStatusTerbaru(string $statusTerbaru): void
    {
        Jejak::catat('SEQ-04', '10', 'HalamanStatusPasien', 'Petugas', 'tampilkanStatusTerbaru(statusTerbaru)');
        $this->statusKedatangan = $statusTerbaru;
        $this->tampilPesan('sukses', 'Status terbaru pasien #' . $this->idPasien . ': ' . $statusTerbaru);
    }

    public function tampilkanGalat(string $pesan): void { $this->tampilPesan('galat', $pesan); }

    public function formHtml(): string
    {
        return self::formTag('/uc/04') . '<label>ID pasien <input type="number" name="idPasien" value="' . (int) $this->idPasien . '"></label>'
            . '<select name="status"><option>Hadir</option><option>Dilewati</option></select><button>Ubah status</button></form>';
    }

    /** Halaman UI-03: nomor antrean utama + aksi panggil / hadir / lewati. @param array<string,mixed>|null $antrean */
    public function renderHalaman(?array $antrean, int $idLayanan): string
    {
        $o = '<div class="queue-head">' . self::ikon('user-voice') . '<h1 class="queue-head__title">Status Pasien Saat Ini</h1></div>';
        foreach ($this->layar as $l) {
            if ($l['jenis'] === 'pesan') {
                $o .= TampilanPetugas::notice((string) $l['data']['tipe'], (string) $l['data']['teks']);
            }
        }
        if ($antrean === null) {
            return $o . '<p class="data-empty u-mt-9">Tidak ada antrean aktif pada layanan ini.</p>';
        }

        $nomor = TampilanPetugas::nomorAntrean((string) $antrean['nomorAntrean']);
        $nama = (string) $antrean['nama'];
        $suara = 'Nomor antrean ' . $nomor . ', atas nama ' . $nama . ', silakan menuju ' . $antrean['namaLayanan'];

        return $o . '<section class="hero-card" aria-label="Pasien yang sedang dipanggil">'
            . '<div class="call-status"><div><p class="call-status__label">Nomor Antrean Utama</p>'
            . '<p class="call-status__number"><span class="call-status__code">' . self::h($nomor) . '</span><span class="call-status__poli">' . self::h($antrean['namaLayanan']) . '</span></p></div>'
            . '<span class="call-status__icon">' . self::ikon('megaphone') . '</span></div>'
            . '<div class="patient-identity"><span class="patient-identity__avatar" aria-hidden="true">' . self::h(TampilanPetugas::inisial($nama)) . '</span>'
            . '<div><p class="patient-identity__name">' . self::h($nama) . '</p><p class="patient-identity__meta">' . self::h($antrean['noRM']) . ' • ' . self::h($antrean['statusAntrean']) . '</p></div></div>'
            . '<form class="action-stack" method="post" action="/uc/04">'
            . '<input type="hidden" name="_token" value="' . self::h(Sesi::token()) . '">'
            . '<input type="hidden" name="idPasien" value="' . (int) $antrean['idPasien'] . '">'
            . '<input type="hidden" name="idLayanan" value="' . $idLayanan . '">'
            . '<button class="btn btn--primary" type="button" id="btn-panggil" data-suara="' . self::h($suara) . '">' . self::ikon('volume') . 'Panggil Ulang</button>'
            . '<button class="btn btn--teal" type="submit" name="status" value="Hadir">' . self::ikon('user-check') . 'Tandai Hadir / Masuk Poli</button>'
            . '<div class="action-stack__skip"><button class="btn btn--soft-danger" type="submit" name="status" value="Dilewati">' . self::ikon('skip') . 'Lewati Pasien</button></div>'
            . '</form></section>'
            // Panggil Ulang: pengumuman suara lewat browser (tanpa backend)
            . '<script>(function(){var b=document.getElementById("btn-panggil");if(!b){return;}b.addEventListener("click",function(){'
            . 'if(!("speechSynthesis" in window)){return;}var u=new SpeechSynthesisUtterance(b.getAttribute("data-suara"));u.lang="id-ID";'
            . 'window.speechSynthesis.cancel();window.speechSynthesis.speak(u);});})();</script>';
    }
}
