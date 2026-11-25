<?php
/**
 * File: tests/run_tests.php
 * Skrip pengujian alur utama (Main Flow) dan alur alternatif (Alternative Flow) UC-01 s.d. UC-17.
 * Setiap skenario membandingkan nomor pesan hasil eksekusi nyata dengan Tabel Analisis Messages pada PHP.pdf.
 * Jalankan: php tests/run_tests.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use HealthPoint\Core\{Auth, Database, Jejak, Request, Router, Seeder, Sesi, Waktu};
use HealthPoint\Flows\{FlowResult, Uc01DaftarLayanan, Uc02DaftarBookingAktif, Uc03BatalkanBooking, Uc04UbahStatusPasien, Uc05NomorPosisiAntrean,
    Uc06EstimasiWaktuTunggu, Uc07JadwalDokter, Uc08KelolaJadwalDokter, Uc09IsiHasilPemeriksaan, Uc10TentukanTanggalKontrol, Uc11SimpanDataLayanan,
    Uc12KelolaStokVaksin, Uc13DaftarVaksinasi, Uc14CariRiwayatPasien, Uc15PilihKandidat, Uc16LaporanHarian, Uc17LaporanKuotaVaksin};

const HARI_INI = '2026-11-23';        // Senin
$gagal = 0; $lulus = 0;

function baru(): Database
{
    Waktu::setSekarang(HARI_INI . ' 09:00:00');
    $db = new Database(':memory:');
    $db->migrasi();
    Seeder::isi($db);
    return $db;
}

function tegas(bool $kondisi, string $pesan): void
{
    global $gagal, $lulus;
    if ($kondisi) { $lulus++; return; }
    $gagal++;
    echo "    GAGAL: $pesan\n";
}

/** @param list<string> $harapan */
function urutan(FlowResult $r, array $harapan, string $label): void
{
    $nyata = $r->nomorPesan();
    tegas($nyata === $harapan, "$label — urutan pesan\n      harapan: " . implode(',', $harapan) . "\n      nyata  : " . implode(',', $nyata));
}

function uji(string $nama, callable $fn): void
{
    global $gagal;
    $sebelum = $gagal;
    try { $fn(); } catch (Throwable $e) { $gagal++; echo "    EXCEPTION: " . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine() . "\n"; }
    echo ($gagal === $sebelum ? '[OK]   ' : '[GAGAL]') . " $nama\n";
}

$pasien1 = ['role' => 'pasien', 'idPasien' => 1];
$pasien2 = ['role' => 'pasien', 'idPasien' => 2];
$petugas = ['role' => 'petugas'];
$dokter = ['role' => 'dokter'];
$pengelola = ['role' => 'pengelola'];

// ---------------------------------------------------------------- UC-01
uji('UC-01 Main Flow (SEQ-01: 1-7)', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, '2026-11-25', 'Demam');
    tegas($r->berhasil, 'pendaftaran berhasil');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7'], 'UC-01 main');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM pendaftaran') === 1, 'baris pendaftaran tersimpan');
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM booking WHERE status='Aktif'") === 1, 'booking aktif dibuat (integrasi UC-02)');
});
uji('UC-01 Alt-1 layanan tidak tersedia', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc01DaftarLayanan($db, $pasien1))->altLayananTidakTersedia(4, 1, '2026-11-25');
    tegas($r->berhasil, 'berhasil setelah memilih layanan lain');
    urutan($r, ['1', '2', '3', '4', '6', '3', '4', '5', '6', '7'], 'UC-01 alt-1');
});
uji('UC-01 Alt-2 data tidak lengkap (tanggal kosong & identitas pasien kosong)', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc01DaftarLayanan($db, $pasien1))->altDataTidakLengkap(1, '2026-11-25');
    tegas($r->berhasil, 'berhasil setelah melengkapi');
    urutan($r, ['1', '2', '3', '4', '6', '3', '4', '5', '6', '7'], 'UC-01 alt-2');
    $db->jalankan("INSERT INTO pasien (no_rm,nama,tanggal_lahir,jenis_kelamin,alamat,no_telepon) VALUES ('RM-9','Tanpa Telepon','1999-01-01','Laki-laki','Jl. X','')");
    $r2 = (new Uc01DaftarLayanan($db, ['role' => 'pasien', 'idPasien' => 5]))->mainFlow(1, '2026-11-25');
    tegas(!$r2->berhasil && $r2->data['status'] === 'data_tidak_lengkap', 'identitas tidak lengkap ditolak');
});

// ---------------------------------------------------------------- UC-02 & UC-03
uji('UC-02 Main Flow (SEQ-02: 1-6) dan Alt-1 (belum ada booking)', function () use ($pasien1, $pasien2) {
    $db = baru();
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, '2026-11-25');
    $r = (new Uc02DaftarBookingAktif($db, $pasien1))->jalankan();
    urutan($r, ['1', '2', '3', '4', '5', '6'], 'UC-02 main');
    tegas($r->jalur === 'main', 'jalur main');
    $a = (new Uc02DaftarBookingAktif($db, $pasien2))->jalankan();
    tegas($a->jalur === 'alt-1' && $a->pesanTerakhir() === 'Belum ada booking aktif', 'pesan "Belum ada booking aktif"');
});
uji('UC-03 Main Flow (SEQ-03: 1-10) dan Alt-1 (lewat batas waktu)', function () use ($pasien1, $pasien2) {
    $db = baru();
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, '2026-11-25');   // booking #1 (jauh)
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, HARI_INI);       // booking #2 (hari ini 08:00, sudah lewat 09:00)
    $r = (new Uc03BatalkanBooking($db, $pasien1))->jalankan(1);
    tegas($r->berhasil, 'booking #1 dibatalkan');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], 'UC-03 main');
    tegas($db->ambilNilai('SELECT status FROM booking WHERE id_booking=1') === 'Dibatalkan', 'status Dibatalkan');
    $a = (new Uc03BatalkanBooking($db, $pasien1))->jalankan(2);
    tegas(!$a->berhasil && $a->pesanTerakhir() === 'Booking tidak dapat dibatalkan', 'pesan "Booking tidak dapat dibatalkan"');
    urutan($a, ['1', '2', '3', '4', '5', '6', '8a', '9a'], 'UC-03 alt-1');
    tegas($db->ambilNilai('SELECT status FROM booking WHERE id_booking=2') === 'Aktif', 'booking tidak berubah');
    $x = (new Uc03BatalkanBooking($db, $pasien2))->jalankan(2);       // keamanan: bukan milik pasien lain
    tegas(!$x->berhasil && $db->ambilNilai('SELECT status FROM booking WHERE id_booking=2') === 'Aktif', 'pasien lain tidak bisa membatalkan booking orang lain');
});

// ---------------------------------------------------------------- UC-04
uji('UC-04 Main Flow (SEQ-04: 1-10)', function () use ($petugas) {
    $db = baru();
    $r = (new Uc04UbahStatusPasien($db, $petugas))->mainFlow(1, 'Dilewati');
    tegas($r->berhasil, 'status berubah');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], 'UC-04');
    tegas($db->ambilNilai('SELECT status_kedatangan FROM pasien WHERE id_pasien=1') === 'Dilewati', 'status tersimpan');
    $x = (new Uc04UbahStatusPasien($db, $petugas))->mainFlow(1, 'Sembarang');
    tegas(!$x->berhasil, 'status tidak valid ditolak');
});

// ---------------------------------------------------------------- UC-05
uji('UC-05 Main Flow, Alt-1 (tidak ditemukan), Alt-2 (belum dimulai)', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc05NomorPosisiAntrean($db, $pasien1))->mainFlow('A004');
    tegas($r->berhasil, 'posisi tampil');
    urutan($r, ['1', '2', '3', '4', '4.1', '4.2', '4.3', '4.4', '4.5', '4.5.1'], 'UC-05 main');
    $info = $r->layar[count($r->layar) - 1]['data']['data'];
    tegas($info['nomorBerjalan'] === 'A002' && $info['posisi'] === 3, 'A004: berjalan A002, posisi 3 (di depan: A002, A003)');
    $a = (new Uc05NomorPosisiAntrean($db, $pasien1))->altNomorTidakDitemukan('Z999', 'A003');
    tegas($a->berhasil, 'berhasil setelah diperbaiki');
    urutan($a, ['1', '2', '3', '4', '4.1', '4.2', '4.3', '4.6', '4.6.1', '5', '4', '4.1', '4.2', '4.3', '4.4', '4.5', '4.5.1'], 'UC-05 alt-1');
    $db->jalankan("UPDATE antrean SET status_antrean='Menunggu'");
    $b = (new Uc05NomorPosisiAntrean($db, $pasien1))->mainFlow('A003');
    tegas($b->pesanTerakhir() === 'Antrean belum dimulai', 'Alt-2: antrean belum dimulai');
});

// ---------------------------------------------------------------- UC-06
uji('UC-06 Main, Alt-1 (rata-rata kosong), Alt-2 (urutan pertama)', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc06EstimasiWaktuTunggu($db, $pasien1))->jalankan('A005');
    urutan($r, ['1', '2', '2.1', '2.1.1', '2.2', '2.5', '2.6', '2.6.1'], 'UC-06 main');
    $e = $r->layar[count($r->layar) - 1]['data']['data']['estimasi'];
    tegas($e === '30 menit', "A005: 3 di depan x rata-rata 10 = 30 menit (nyata: $e)");
    $p = (new Uc06EstimasiWaktuTunggu($db, $pasien1))->jalankan('A002');
    tegas($p->jalur === 'alt-2', 'jalur alt-2');
    urutan($p, ['1', '2', '2.1', '2.1.1', '2.2', '2.4', '2.4.1'], 'UC-06 alt-2');
    $db->jalankan('DELETE FROM waktu_pelayanan');
    $k = (new Uc06EstimasiWaktuTunggu($db, $pasien1))->jalankan('A005');
    urutan($k, ['1', '2', '2.1', '2.1.1', '2.2', '2.3', '2.3.1'], 'UC-06 alt-1');
    tegas($k->pesanTerakhir() === 'Estimasi belum dapat dihitung', 'pesan estimasi belum dapat dihitung');
});

// ---------------------------------------------------------------- UC-07
uji('UC-07 Main, Alt-1 (tidak ada dokter), Alt-2 (Tidak Tersedia)', function () use ($pasien1) {
    $db = baru();
    $r = (new Uc07JadwalDokter($db, $pasien1))->jalankan('Senin');
    urutan($r, ['1', '2', '3', '4', '4.1', '4.3', '4.4', '4.4.1', '4.3', '4.4', '4.4.1'], 'UC-07 main');
    $a = (new Uc07JadwalDokter($db, $pasien1))->jalankan('Sabtu');
    urutan($a, ['1', '2', '3', '4', '4.1', '4.2', '4.2.1'], 'UC-07 alt-1');
    tegas($a->pesanTerakhir() === 'Jadwal tidak tersedia', 'pesan jadwal tidak tersedia');
    $b = (new Uc07JadwalDokter($db, $pasien1))->jalankan('Jumat');
    tegas($b->jalur === 'alt-2', 'Jumat memuat status Tidak Tersedia');
});

// ---------------------------------------------------------------- UC-08
uji('UC-08 Main, Alt-1 (data tidak valid), Alt-2 (gagal simpan)', function () use ($petugas) {
    $db = baru();
    $r = (new Uc08KelolaJadwalDokter($db, $petugas))->mainFlow(1, 'Sabtu', '08:00-11:00', 'Tersedia');
    tegas($r->berhasil, 'tersimpan');
    urutan($r, ['1', '2', '2.1', '3', '4', '5', '6', '7', '7.1', '7.3', '7.5', '7.5.1'], 'UC-08 main');
    $a = (new Uc08KelolaJadwalDokter($db, $petugas))->altDataTidakValid(1, 'Minggu', '10:00-08:00', '08:00-10:00', 'Tersedia');
    tegas($a->berhasil, 'berhasil setelah diperbaiki');
    urutan($a, ['1', '2', '2.1', '3', '4', '5', '6', '7', '7.1', '7.2', '7.2.1', '7.2.2', '7', '7.1', '7.3', '7.5', '7.5.1'], 'UC-08 alt-1');
    $g = (new Uc08KelolaJadwalDokter($db, $petugas))->altPenyimpananGagal(1, 'Senin', '08:00-12:00', 'Tersedia');
    urutan($g, ['1', '2', '2.1', '3', '4', '5', '6', '7', '7.1', '7.3', '7.4', '7.4.1'], 'UC-08 alt-2');
    tegas(!$g->berhasil && $g->pesanTerakhir() === 'Gagal menyimpan jadwal, silakan ulangi', 'pesan gagal');
});

// ---------------------------------------------------------------- UC-09
uji('UC-09 Main Flow (SEQ-09) + opsi hapus pencarian', function () use ($dokter) {
    $db = baru();
    $r = (new Uc09IsiHasilPemeriksaan($db, $dokter))->mainFlow('RM-0001', 1, 'Influenza', 'Istirahat');
    tegas($r->berhasil && $r->data['diagnosis'] === 'Influenza', 'form terisi');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '11', '12', '13', '14', '15', '16', '17'], 'UC-09 main');
    $o = (new Uc09IsiHasilPemeriksaan($db, $dokter))->mainFlow('Budi', 1, 'x', 'y', true);
    tegas(in_array('9', $o->nomorPesan(), true) && in_array('10', $o->nomorPesan(), true), 'pesan opt 9,10 muncul');
    $n = (new Uc09IsiHasilPemeriksaan($db, $dokter))->mainFlow('tidak ada', 1, 'x', 'y');
    tegas(!$n->berhasil, 'pencarian kosong tidak menampilkan form');
});

// ---------------------------------------------------------------- UC-10
uji('UC-10 Main Flow (SEQ-10: 1-21) dan Alt (jadwal tidak valid)', function () use ($dokter) {
    $db = baru();
    $r = (new Uc10TentukanTanggalKontrol($db, $dokter))->mainFlow('2026-11-24', 1, '09:00');
    tegas($r->berhasil, 'jadwal diterima');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '20', '21'], 'UC-10 main');
    $a = (new Uc10TentukanTanggalKontrol($db, $dokter))->altJadwalTidakValid('2026-11-28', '2026-11-24', 1, '09:00');
    tegas($a->berhasil, 'valid setelah isi ulang');
    tegas(in_array('18', $a->nomorPesan(), true) && in_array('19', $a->nomorPesan(), true), 'pesan 18,19 muncul pada jadwal salah');
    foreach (['2026-11-20' => 'tanggal lampau', '2026-11-24' => 'jam di luar praktik'] as $tgl => $ket) {
        $x = (new Uc10TentukanTanggalKontrol($db, $dokter))->mainFlow($tgl, 1, $ket === 'jam di luar praktik' ? '15:00' : '09:00');
        tegas(!$x->berhasil, "ditolak: $ket");
    }
});

// ---------------------------------------------------------------- UC-11
uji('UC-11 Main (tanpa & dengan kontrol), Alt-1, jadwal kontrol tidak valid', function () use ($petugas) {
    $db = baru();
    $r = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(1, 'Influenza', 'Istirahat', false, '', 0, '', true);
    urutan($r, ['1', '2', '3', '4', '11', '12', '15', '16', '17'], 'UC-11 main tanpa kontrol');
    tegas($db->ambilNilai('SELECT diagnosis FROM riwayat_kunjungan WHERE id_pasien=1 AND tanggal_kunjungan=?', [HARI_INI]) === 'Influenza', 'kunjungan aktif terisi');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM rekam_medis') === 1, 'rekam medis tersimpan');
    $k = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(2, 'Karies', 'Tambal', true, '2026-11-24', 2, '10:00');
    urutan($k, ['1', '2', '3', '4', '7', '8', '11', '12', '13', '14', '15', '16'], 'UC-11 main dengan kontrol');
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM booking WHERE jenis_booking='kontrol'") === 1, 'booking kontrol masuk daftar');
    $a = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(3, '', 'Tambal');
    urutan($a, ['1', '2', '3', '4', '5', '6'], 'UC-11 alt-1');
    tegas($a->jalur === 'alt-1' && (int) $db->ambilNilai('SELECT COUNT(*) FROM rekam_medis') === 2, 'tidak ada data baru tersimpan');
    $j = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(3, 'Karies', 'Tambal', true, '2026-11-28', 2, '10:00');
    urutan($j, ['1', '2', '3', '4', '7', '8', '9', '10'], 'UC-11 jadwal tidak valid');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM rekam_medis') === 2, 'tidak disimpan saat jadwal tidak valid');
});

// ---------------------------------------------------------------- UC-12
uji('UC-12 Main Flow dan Alt-1', function () use ($pengelola) {
    $db = baru();
    $r = (new Uc12KelolaStokVaksin($db, $pengelola))->jalankan(2, '10');
    tegas($r->berhasil && $r->data['hasil'] === 12, 'stok Hepatitis B 2 + 10 = 12');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '20', '21', '22', '23'], 'UC-12 main');
    tegas((int) $db->ambilNilai('SELECT sisa_kuota FROM stok_vaksin WHERE id_vaksin=2') === 12, 'sisa kuota ikut bertambah');
    foreach (['' => 'kosong', 'abc' => 'bukan angka', '-5' => 'negatif', '0' => 'nol'] as $in => $ket) {
        $a = (new Uc12KelolaStokVaksin($db, $pengelola))->jalankan(2, (string) $in);
        $n = $a->nomorPesan();
        tegas(!$a->berhasil && array_slice($n, -2) === ['18', '19'], "alt-1 ($ket): pesan 18,19");
        tegas((int) $db->ambilNilai('SELECT jumlah_stok FROM stok_vaksin WHERE id_vaksin=2') === 12, "alt-1 ($ket): stok tidak berubah");
    }
});

// ---------------------------------------------------------------- UC-13
uji('UC-13 Main Flow, Alt-1 (kuota habis), kuota atomik', function () use ($pasien1, $pasien2) {
    $db = baru();
    $r = (new Uc13DaftarVaksinasi($db, $pasien1))->jalankan(HARI_INI, 2);
    tegas($r->berhasil && $r->data['hasil'] === 1, 'sisa kuota Hepatitis B menjadi 1');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '15', '16', '17', '18', '19', '20'], 'UC-13 main');
    (new Uc13DaftarVaksinasi($db, $pasien2))->jalankan(HARI_INI, 2);   // kuota 0
    $a = (new Uc13DaftarVaksinasi($db, $pasien1))->jalankan(HARI_INI, 2);
    tegas(!$a->berhasil && $a->pesanTerakhir() === 'Kuota vaksin habis.', 'kuota habis ditolak');
    urutan($a, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'], 'UC-13 alt-1');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM pendaftaran_vaksinasi') === 2 && (int) $db->ambilNilai('SELECT sisa_kuota FROM stok_vaksin WHERE id_vaksin=2') === 0, 'tidak ada pendaftaran/kuota negatif');
    $h = (new Uc13DaftarVaksinasi($db, $pasien1))->jalankan(HARI_INI, 3);   // stok 0 sejak awal
    tegas(!$h->berhasil, 'vaksin stok 0 ditolak');
});

// ---------------------------------------------------------------- UC-14 & UC-15
uji('UC-14 Main dan Alt-1', function () use ($petugas) {
    $db = baru();
    $r = (new Uc14CariRiwayatPasien($db, $petugas))->mainFlow('Siti');
    tegas($r->berhasil, 'ditemukan');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8'], 'UC-14 main');
    $a = (new Uc14CariRiwayatPasien($db, $petugas))->altTidakDitemukan('Tidak Ada', 'Siti', '1985-11-02');
    tegas($a->berhasil, 'berhasil setelah diperbaiki');
    urutan($a, ['1', '2', '3', '4', '5', '5a', '5b', '5c', '4', '5', '6', '7', '8'], 'UC-14 alt-1');
    $m = (new Uc14CariRiwayatPasien($db, $petugas))->mainFlow('Budi', '', 'RM-0003');
    tegas($m->berhasil, 'No RM menyaring menjadi satu hasil');
    $ringkasan = json_encode($m->layar);
    tegas(!str_contains((string) $ringkasan, 'Karies'), 'ringkasan tidak memuat diagnosis (tanpa detail medis)');
});
uji('UC-15 Main Flow (hasil ganda -> pilih kandidat)', function () use ($petugas) {
    $db = baru();
    $r = (new Uc15PilihKandidat($db, $petugas))->mainFlow('Budi Santoso', 3);
    tegas($r->berhasil, 'detail tampil');
    urutan($r, ['1', '2', '3', '4', '5', '1', '2', '3', '4', '5', '6', '7', '8'], 'UC-15 (SEQ-14 1-5 lalu SEQ-15 1-8)');
    $x = (new Uc15PilihKandidat($db, $petugas))->mainFlow('Budi Santoso', 2);   // Siti bukan kandidat
    tegas(!$x->berhasil, 'pasien di luar kandidat ditolak');
});

// ---------------------------------------------------------------- UC-16 & UC-17
uji('UC-16 Main dan Alt-1', function () use ($pasien1, $pasien2, $pengelola) {
    $db = baru();
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, HARI_INI);
    (new Uc01DaftarLayanan($db, $pasien2))->mainFlow(1, HARI_INI);
    (new Uc01DaftarLayanan($db, $pasien2))->mainFlow(2, HARI_INI);
    $r = (new Uc16LaporanHarian($db, $pengelola))->mainFlow(HARI_INI);
    urutan($r, ['1', '2', '3', '4', '5', '5', '6', '7', '8'], 'UC-16 main');
    $baris = $r->layar[count($r->layar) - 1]['data']['baris'];
    tegas($baris === [['layanan' => 'Poli Umum', 'jumlahPasien' => 2], ['layanan' => 'Poli Gigi', 'jumlahPasien' => 1]], 'rekap per layanan benar');
    $a = (new Uc16LaporanHarian($db, $pengelola))->altDataKosong('2026-01-01', HARI_INI);
    urutan($a, ['1', '2', '3', '4', '4a', '4b', '4c', '3', '4', '5', '5', '6', '7', '8'], 'UC-16 alt-1');
});
uji('UC-17 Main dan Alt-1', function () use ($pasien1, $pengelola) {
    $db = baru();
    (new Uc13DaftarVaksinasi($db, $pasien1))->jalankan(HARI_INI, 1);
    $r = (new Uc17LaporanKuotaVaksin($db, $pengelola))->mainFlow(HARI_INI);
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8'], 'UC-17 main');
    $d = $r->layar[count($r->layar) - 1]['data']['data'];
    tegas($d['jumlahDosisTerpakai'] === 1 && $d['sisaKuota'] === 51, 'dosis terpakai 1, sisa kuota 51 (stok 52 - 1)');
    $a = (new Uc17LaporanKuotaVaksin($db, $pengelola))->altDataKosong('2026-01-01', HARI_INI);
    urutan($a, ['1', '2', '3', '4', '4a', '4b', '4c', '3', '4', '5', '6', '7', '8'], 'UC-17 alt-1');
});

// ---------------------------------------------------------------- Web layer
uji('Web: login, pembatasan peran, CSRF, escaping HTML', function () {
    $db = baru();
    Sesi::hapus('pengguna');
    [$s] = Router::tangani(new Request('GET', '/uc/01'), $db);
    tegas($s === 302, 'tanpa login dialihkan');
    tegas(Auth::masuk($db, 'budi', 'salah') === null, 'password salah ditolak');
    tegas(Auth::masuk($db, 'budi', 'pasien123') !== null, 'login pasien');
    [$s] = Router::tangani(new Request('GET', '/uc/12'), $db);
    tegas($s === 403, 'pasien tidak boleh membuka UC-12');
    [$s, $h] = Router::tangani(new Request('GET', '/uc/01'), $db);
    tegas($s === 200 && str_contains($h, 'Poli Umum'), 'UC-01 tampil untuk pasien');
    [$s] = Router::tangani(new Request('POST', '/uc/01', [], ['idLayanan' => '1', 'tanggal' => '2026-11-25']), $db);
    tegas($s === 400, 'POST tanpa token CSRF ditolak');
    [$s, $h] = Router::tangani(new Request('POST', '/uc/01', [], ['_token' => Sesi::token(), 'idLayanan' => '1', 'tanggal' => '2026-11-25', 'keluhan' => '<script>x</script>']), $db);
    tegas($s === 200 && str_contains($h, 'Pendaftaran berhasil') && !str_contains($h, '<script>x'), 'POST valid berhasil & output di-escape');
    Auth::keluar();
});

echo "\nTotal pemeriksaan: " . ($lulus + $gagal) . ", lulus: $lulus, gagal: $gagal\n";
exit($gagal === 0 ? 0 : 1);
