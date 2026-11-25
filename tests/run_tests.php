<?php
/**
 * File: tests/run_tests.php
 * Skrip pengujian alur utama (Main Flow) dan alur alternatif (Alternative Flow) UC-01 s.d. UC-17.
 * Setiap skenario membandingkan nomor pesan hasil eksekusi nyata dengan Tabel Analisis Messages pada PHP.pdf.
 * Jalankan: php tests/run_tests.php   (MySQL/MariaDB; memakai database terpisah `healthpoint_test`, dibuat otomatis,
 *           koneksi dari config/config.php atau variabel lingkungan HP_DB_*)
 */
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';
require __DIR__ . '/pemuat.php';

use HealthPoint\Core\{Auth, Database, Jejak, Request, Router, Sesi, Waktu};
use HealthPoint\Flows\{FlowResult, Uc01DaftarLayanan, Uc02DaftarBookingAktif, Uc03BatalkanBooking, Uc04UbahStatusPasien, Uc05NomorPosisiAntrean,
    Uc06EstimasiWaktuTunggu, Uc07JadwalDokter, Uc08KelolaJadwalDokter, Uc09IsiHasilPemeriksaan, Uc10TentukanTanggalKontrol, Uc11SimpanDataLayanan,
    Uc12KelolaStokVaksin, Uc13DaftarVaksinasi, Uc14CariRiwayatPasien, Uc15PilihKandidat, Uc16LaporanHarian, Uc17LaporanKuotaVaksin};

const HARI_INI = '2026-11-23';        // Senin
$gagal = 0; $lulus = 0;

function baru(): Database
{
    Waktu::setSekarang(HARI_INI . ' 09:00:00');
    $cfg = require dirname(__DIR__) . '/config/config.php';
    // MySQL: database terpisah `healthpoint_test`; SQLite: basis data di memori (tidak menyentuh berkas aplikasi).
    $db = new Database(['nama' => $cfg['driver'] === 'sqlite' ? ':memory:' : 'healthpoint_test', 'buat' => true] + $cfg);
    muatDatabase($db);
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

/** Pengujian khusus MySQL/MariaDB (information_schema, mode ketat); dilewati bila HP_DB_DRIVER=sqlite. */
function ujiMysql(string $nama, callable $fn): void
{
    $cfg = require dirname(__DIR__) . '/config/config.php';
    if ($cfg['driver'] !== 'mysql') { echo "[LEWAT] $nama (khusus MySQL)\n"; return; }
    uji($nama, $fn);
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
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM Pendaftaran WHERE tanggalDaftar='2026-11-25' AND statusKedatangan='Belum Hadir'") === 1, 'baris pendaftaran tersimpan');
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM Booking WHERE status='Aktif' AND tanggalKunjungan='2026-11-25' AND jamKontrol IS NULL AND jenisBooking='Layanan'") === 1, 'booking aktif dibuat (integrasi UC-02), jamKontrol NULL pada booking reguler');
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
    $db->jalankan("INSERT INTO DataPasien (noRM,nama,tanggalLahir,jenisKelamin,alamat,noTelepon) VALUES ('RM-9','Tanpa Telepon','1999-01-01','Laki-laki','Jl. X','')");
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
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, '2026-11-25');   // booking pertama (jauh)
    $b1 = (int) $db->ambilNilai('SELECT MAX(idBooking) FROM Booking');
    (new Uc01DaftarLayanan($db, $pasien1))->mainFlow(1, HARI_INI);       // booking kedua (hari ini 08:00, sudah lewat 09:00)
    $b2 = (int) $db->ambilNilai('SELECT MAX(idBooking) FROM Booking');
    $r = (new Uc03BatalkanBooking($db, $pasien1))->jalankan($b1);
    tegas($r->berhasil, 'booking #1 dibatalkan');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], 'UC-03 main');
    tegas($db->ambilNilai('SELECT status FROM Booking WHERE idBooking=?', [$b1]) === 'Dibatalkan', 'status Dibatalkan');
    $a = (new Uc03BatalkanBooking($db, $pasien1))->jalankan($b2);
    tegas(!$a->berhasil && $a->pesanTerakhir() === 'Booking tidak dapat dibatalkan', 'pesan "Booking tidak dapat dibatalkan"');
    urutan($a, ['1', '2', '3', '4', '5', '6', '8a', '9a'], 'UC-03 alt-1');
    tegas($db->ambilNilai('SELECT status FROM Booking WHERE idBooking=?', [$b2]) === 'Aktif', 'booking tidak berubah');
    $x = (new Uc03BatalkanBooking($db, $pasien2))->jalankan($b2);       // keamanan: bukan milik pasien lain
    tegas(!$x->berhasil && $db->ambilNilai('SELECT status FROM Booking WHERE idBooking=?', [$b2]) === 'Aktif', 'pasien lain tidak bisa membatalkan booking orang lain');
});

// ---------------------------------------------------------------- UC-04
uji('UC-04 Main Flow (SEQ-04: 1-10)', function () use ($petugas) {
    $db = baru();
    $r = (new Uc04UbahStatusPasien($db, $petugas))->mainFlow(1, 'Dilewati');
    tegas($r->berhasil, 'status berubah');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], 'UC-04');
    tegas($db->ambilNilai('SELECT statusKedatangan FROM Pendaftaran WHERE idPasien=1 ORDER BY tanggalDaftar DESC, idPendaftaran DESC LIMIT 1') === 'Dilewati', 'status tersimpan di Pendaftaran terbaru');
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
    $db->jalankan("UPDATE Antrean SET statusAntrean='Menunggu'");
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
    $db->jalankan('DELETE FROM WaktuPelayanan');
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
    tegas($db->ambilNilai('SELECT diagnosis FROM RiwayatKunjungan WHERE idPasien=1 AND tanggalKunjungan=?', [HARI_INI]) === 'Influenza', 'kunjungan aktif terisi');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM RekamMedis') === 1, 'rekam medis tersimpan');
    $k = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(2, 'Karies', 'Tambal', true, '2026-11-24', 2, '10:00');
    urutan($k, ['1', '2', '3', '4', '7', '8', '11', '12', '13', '14', '15', '16'], 'UC-11 main dengan kontrol');
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM Booking WHERE jenisBooking='Kontrol'") === 1, 'booking kontrol masuk daftar');
    $a = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(3, '', 'Tambal');
    urutan($a, ['1', '2', '3', '4', '5', '6'], 'UC-11 alt-1');
    tegas($a->jalur === 'alt-1' && (int) $db->ambilNilai('SELECT COUNT(*) FROM RekamMedis') === 2, 'tidak ada data baru tersimpan');
    $j = (new Uc11SimpanDataLayanan($db, $petugas))->jalankan(3, 'Karies', 'Tambal', true, '2026-11-28', 2, '10:00');
    urutan($j, ['1', '2', '3', '4', '7', '8', '9', '10'], 'UC-11 jadwal tidak valid');
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM RekamMedis') === 2, 'tidak disimpan saat jadwal tidak valid');
});

// ---------------------------------------------------------------- UC-12
uji('UC-12 Main Flow dan Alt-1', function () use ($pengelola) {
    $db = baru();
    $r = (new Uc12KelolaStokVaksin($db, $pengelola))->jalankan(2, '10');
    tegas($r->berhasil && $r->data['hasil'] === 12, 'stok Hepatitis B 2 + 10 = 12');
    urutan($r, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '20', '21', '22', '23'], 'UC-12 main');
    tegas((int) $db->ambilNilai('SELECT sisaKuota FROM StokVaksin WHERE idVaksin=2') === 12, 'sisa kuota ikut bertambah');
    foreach (['' => 'kosong', 'abc' => 'bukan angka', '-5' => 'negatif', '0' => 'nol'] as $in => $ket) {
        $a = (new Uc12KelolaStokVaksin($db, $pengelola))->jalankan(2, (string) $in);
        $n = $a->nomorPesan();
        tegas(!$a->berhasil && array_slice($n, -2) === ['18', '19'], "alt-1 ($ket): pesan 18,19");
        tegas((int) $db->ambilNilai('SELECT jumlahStok FROM StokVaksin WHERE idVaksin=2') === 12, "alt-1 ($ket): stok tidak berubah");
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
    tegas((int) $db->ambilNilai('SELECT COUNT(*) FROM PendaftaranVaksinasi') === 2 && (int) $db->ambilNilai('SELECT sisaKuota FROM StokVaksin WHERE idVaksin=2') === 0, 'tidak ada pendaftaran/kuota negatif');
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

// ---------------------------------------------------------------- Kesesuaian skema dengan DDL.pdf 2.5
ujiMysql('Skema MySQL sesuai DDL.pdf: tabel, tipe/panjang, NULL, PK, UQ, FK, kardinalitas', function () {
    $db = baru();
    // [kolom => [COLUMN_TYPE, nullable, extra]] persis seperti tabel entitas DDL.pdf (Constraint "-" = boleh NULL).
    $spec = [
        'DataPasien' => ['idPasien' => ['int', 0, 'auto_increment'], 'noRM' => ['varchar(15)', 0], 'nama' => ['varchar(100)', 0], 'tanggalLahir' => ['date', 0],
            'jenisKelamin' => ['varchar(10)', 0], 'alamat' => ['varchar(255)', 0], 'noTelepon' => ['varchar(15)', 0]],
        'Layanan' => ['idLayanan' => ['int', 0, 'auto_increment'], 'namaLayanan' => ['varchar(100)', 0], 'deskripsi' => ['varchar(255)', 1]],
        'Dokter' => ['idDokter' => ['int', 0, 'auto_increment'], 'namaDokter' => ['varchar(100)', 0]],
        'JadwalDokter' => ['idJadwal' => ['int', 0, 'auto_increment'], 'idDokter' => ['int', 0], 'hari' => ['varchar(10)', 0], 'jamPraktik' => ['varchar(15)', 0],
            'statusKetersediaan' => ['varchar(20)', 0]],
        'Booking' => ['idBooking' => ['int', 0, 'auto_increment'], 'idPasien' => ['int', 0], 'idLayanan' => ['int', 0], 'tanggalKunjungan' => ['date', 0],
            'status' => ['varchar(20)', 0], 'jamKontrol' => ['varchar(5)', 1], 'jenisBooking' => ['varchar(20)', 0]],
        'Antrean' => ['idAntrean' => ['int', 0, 'auto_increment'], 'idBooking' => ['int', 0], 'nomorAntrean' => ['varchar(10)', 0], 'statusAntrean' => ['varchar(20)', 0],
            'waktuDaftar' => ['datetime', 0]],
        'WaktuPelayanan' => ['idWaktuPelayanan' => ['int', 0, 'auto_increment'], 'idLayanan' => ['int', 0], 'rataRataWaktu' => ['decimal(5,2)', 0], 'tanggal' => ['date', 0]],
        'Pendaftaran' => ['idPendaftaran' => ['int', 0, 'auto_increment'], 'idPasien' => ['int', 0], 'idLayanan' => ['int', 0], 'tanggalDaftar' => ['date', 0],
            'statusKedatangan' => ['varchar(20)', 0]],
        'PendaftaranVaksinasi' => ['idPendaftaran' => ['int', 0], 'idVaksin' => ['int', 0]],
        'StokVaksin' => ['idVaksin' => ['int', 0, 'auto_increment'], 'jenisVaksin' => ['varchar(100)', 0], 'tipeVaksin' => ['varchar(50)', 0], 'jumlahStok' => ['int', 0],
            'sisaKuota' => ['int', 0]],
        'PemakaianVaksin' => ['idPemakaian' => ['int', 0, 'auto_increment'], 'idVaksin' => ['int', 0], 'tanggalPemakaian' => ['date', 0], 'jumlahDosis' => ['int', 0]],
        'RiwayatKunjungan' => ['idKunjungan' => ['int', 0, 'auto_increment'], 'idPasien' => ['int', 0], 'idLayanan' => ['int', 0], 'tanggalKunjungan' => ['date', 0],
            'keluhanUtama' => ['varchar(255)', 0], 'tekananDarah' => ['varchar(10)', 1], 'nadi' => ['int', 1], 'suhu' => ['decimal(4,1)', 1], 'spo2' => ['int', 1],
            'diagnosis' => ['varchar(255)', 0], 'tindakan' => ['varchar(255)', 0]],
        'RekamMedis' => ['idRekamMedis' => ['int', 0, 'auto_increment'], 'idPasien' => ['int', 0], 'diagnosis' => ['varchar(255)', 0], 'tindakan' => ['varchar(255)', 0],
            'tanggalLayanan' => ['date', 0]],
    ];
    $pk = ['DataPasien' => 'idPasien', 'Layanan' => 'idLayanan', 'Dokter' => 'idDokter', 'JadwalDokter' => 'idJadwal', 'Booking' => 'idBooking', 'Antrean' => 'idAntrean',
        'WaktuPelayanan' => 'idWaktuPelayanan', 'Pendaftaran' => 'idPendaftaran', 'PendaftaranVaksinasi' => 'idPendaftaran', 'StokVaksin' => 'idVaksin',
        'PemakaianVaksin' => 'idPemakaian', 'RiwayatKunjungan' => 'idKunjungan', 'RekamMedis' => 'idRekamMedis'];
    // [tabel.kolom => tabel.kolom induk] — sisi anak NOT NULL (modalitas 1 – 0)
    $fk = ['JadwalDokter.idDokter' => 'Dokter.idDokter', 'Booking.idPasien' => 'DataPasien.idPasien', 'Booking.idLayanan' => 'Layanan.idLayanan', 'Antrean.idBooking' => 'Booking.idBooking',
        'WaktuPelayanan.idLayanan' => 'Layanan.idLayanan', 'Pendaftaran.idPasien' => 'DataPasien.idPasien', 'Pendaftaran.idLayanan' => 'Layanan.idLayanan',
        'PendaftaranVaksinasi.idPendaftaran' => 'Pendaftaran.idPendaftaran', 'PendaftaranVaksinasi.idVaksin' => 'StokVaksin.idVaksin', 'PemakaianVaksin.idVaksin' => 'StokVaksin.idVaksin',
        'RiwayatKunjungan.idPasien' => 'DataPasien.idPasien', 'RiwayatKunjungan.idLayanan' => 'Layanan.idLayanan', 'RekamMedis.idPasien' => 'DataPasien.idPasien'];
    $nama = (string) $db->ambilNilai('SELECT DATABASE()');

    foreach ($spec as $tabel => $kolom) {
        $nyata = [];
        foreach ($db->ambilSemua('SELECT COLUMN_NAME c, COLUMN_TYPE t, IS_NULLABLE n, EXTRA e, COLUMN_KEY k FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$nama, $tabel]) as $r) {
            $nyata[$r['c']] = $r;
        }
        tegas(array_keys($nyata) === array_keys($kolom), "$tabel: daftar & urutan kolom sama dengan DDL (nyata: " . implode(',', array_keys($nyata)) . ')');
        foreach ($kolom as $k => $d) {
            $r = $nyata[$k] ?? null;
            if ($r === null) { continue; }
            // MariaDB/MySQL 8 menulis int / int(11) berbeda; samakan sebelum membandingkan.
            $tipe = preg_replace('/^(int)\(\d+\)$/', '$1', strtolower((string) $r['t']));
            tegas($tipe === $d[0], "$tabel.$k tipe {$d[0]} (nyata: {$r['t']})");
            tegas(($r['n'] === 'YES') === (bool) $d[1], "$tabel.$k " . ($d[1] ? 'boleh NULL' : 'NOT NULL'));
            tegas(str_contains((string) $r['e'], 'auto_increment') === (($d[2] ?? '') === 'auto_increment'), "$tabel.$k AUTO_INCREMENT");
            tegas(($r['k'] === 'PRI') === ($pk[$tabel] === $k), "$tabel.$k status primary key");
        }
    }
    $uq = $db->ambilSemua("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND NON_UNIQUE=0 AND INDEX_NAME<>'PRIMARY' AND TABLE_NAME<>'Pengguna'", [$nama]);
    $uqNyata = array_map(static fn (array $r): string => $r['t'] . '.' . $r['c'], $uq);
    sort($uqNyata);
    tegas($uqNyata === ['Antrean.idBooking', 'DataPasien.noRM'], 'UNIQUE hanya DataPasien.noRM (DDL) dan Antrean.idBooking (kardinalitas 1 : 0..1); nyata: ' . implode(',', $uqNyata));
    $fkNyata = [];
    foreach ($db->ambilSemua('SELECT TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME<>\'Pengguna\'', [$nama]) as $r) {
        $fkNyata[$r['t'] . '.' . $r['c']] = $r['rt'] . '.' . $r['rc'];
    }
    ksort($fkNyata); ksort($fk);
    tegas($fkNyata === $fk, 'seluruh FOREIGN KEY sesuai tabel Hubungan Antar Kelas; nyata: ' . json_encode($fkNyata));
    foreach ($db->ambilSemua('SELECT TABLE_NAME t, ENGINE e, TABLE_COLLATION c FROM information_schema.TABLES WHERE TABLE_SCHEMA=?', [$nama]) as $r) {
        tegas($r['e'] === 'InnoDB' && str_starts_with((string) $r['c'], 'utf8mb4'), "{$r['t']}: InnoDB + utf8mb4");
    }
    tegas(count($spec) === 13, '13 entitas DDL tercakup (PendaftaranVaksinasi, RekamMedis, dst.)');
});
ujiMysql('Contoh data DDL.pdf dapat disimpan (panjang/tipe) & batas panjang ditolak', function () {
    $db = baru();
    $db->jalankan("INSERT INTO DataPasien (noRM,nama,tanggalLahir,jenisKelamin,alamat,noTelepon) VALUES ('RM-000123','Karina','1995-03-09','Perempuan','Jl. Jaya No. 5, Malang','081222889990')");
    $db->jalankan("INSERT INTO Booking (idPasien,idLayanan,tanggalKunjungan,status,jamKontrol,jenisBooking) VALUES (?,1,'2026-11-06','Aktif','09.00','Kontrol')", [$db->idTerakhir()]);
    $db->jalankan("INSERT INTO JadwalDokter (idDokter,hari,jamPraktik,statusKetersediaan) VALUES (1,'Selasa','08.00-12.00','Tersedia')");
    $db->jalankan("INSERT INTO WaktuPelayanan (idLayanan,rataRataWaktu,tanggal) VALUES (1,15.50,'2026-10-24')");
    tegas((string) $db->ambilNilai('SELECT rataRataWaktu FROM WaktuPelayanan ORDER BY idWaktuPelayanan DESC LIMIT 1') === '15.50', 'DECIMAL(5,2) menyimpan 15.50');
    $ditolak = static function (callable $f): bool { try { $f(); return false; } catch (PDOException) { return true; } };
    tegas($ditolak(fn () => $db->jalankan("INSERT INTO DataPasien (noRM,nama,tanggalLahir,jenisKelamin,alamat,noTelepon) VALUES ('RM-0001','Dobel','1990-01-01','Laki-laki','x','1')")), 'noRM duplikat ditolak (UNIQUE)');
    tegas($ditolak(fn () => $db->jalankan("INSERT INTO DataPasien (noRM,nama,tanggalLahir,jenisKelamin,alamat,noTelepon) VALUES ('RM-1234567890123456','x','1990-01-01','Laki-laki','x','1')")), 'noRM > 15 karakter ditolak (mode ketat)');
    tegas($ditolak(fn () => $db->jalankan("INSERT INTO Pendaftaran (idPasien,idLayanan,tanggalDaftar,statusKedatangan) VALUES (999,1,'2026-11-01','Hadir')")), 'FK idPasien tidak ada ditolak');
    tegas($ditolak(fn () => $db->jalankan("INSERT INTO Antrean (idBooking,nomorAntrean,statusAntrean,waktuDaftar) VALUES (1,'X-1','Menunggu','2026-11-23 08:00:00')")), 'booking kedua kali pada Antrean ditolak (1 : 0..1)');
    tegas($ditolak(fn () => $db->jalankan("INSERT INTO Dokter (namaDokter) VALUES (NULL)")), 'NOT NULL Dokter.namaDokter');
    tegas($db->ambilNilai('SELECT jamKontrol FROM Booking WHERE jenisBooking=\'Kontrol\' ORDER BY idBooking DESC LIMIT 1') === '09.00', 'jamKontrol format DDL tersimpan');
});
uji('Generalisasi Pendaftaran -> PendaftaranVaksinasi & perilaku turunan skema', function () use ($pasien1, $pasien2, $pengelola) {
    $db = baru();
    $r = (new Uc13DaftarVaksinasi($db, $pasien1))->jalankan(HARI_INI, 1);
    tegas($r->berhasil, 'UC-13 berhasil');
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM Pendaftaran p JOIN PendaftaranVaksinasi pv ON pv.idPendaftaran = p.idPendaftaran JOIN Layanan l ON l.idLayanan = p.idLayanan
        WHERE p.idPasien = 1 AND p.tanggalDaftar = ? AND l.namaLayanan = 'Vaksinasi' AND pv.idVaksin = 1 AND p.statusKedatangan = 'Belum Hadir'", [HARI_INI]) === 1,
        'satu baris Pendaftaran (layanan Vaksinasi) + satu baris PendaftaranVaksinasi');
    $l = (new Uc16LaporanHarian($db, $pengelola))->mainFlow(HARI_INI);
    tegas($l->berhasil && ($l->layar[count($l->layar) - 1]['data']['baris'] ?? []) === [['layanan' => 'Vaksinasi', 'jumlahPasien' => 1]], 'UC-16 ikut menghitung pendaftaran vaksinasi per layanan');
    // jam disimpan format DDL, tetapi aplikasi tetap bekerja dengan HH:MM
    tegas($db->ambilNilai("SELECT jamPraktik FROM JadwalDokter WHERE idDokter=1 AND hari='Senin'") === '08.00-12.00', 'seed jamPraktik berformat DDL');
    (new Uc08KelolaJadwalDokter($db, ['role' => 'petugas']))->mainFlow(1, 'Sabtu', '08:00-11:00', 'Tersedia');
    tegas($db->ambilNilai("SELECT jamPraktik FROM JadwalDokter WHERE idDokter=1 AND hari='Sabtu'") === '08.00-11.00', 'simpan jadwal -> 08.00-11.00 di DB');
    $j = (new Uc07JadwalDokter($db, $pasien1))->jalankan('Sabtu');
    tegas(str_contains(json_encode($j->layar), '08:00-11:00'), 'baca jadwal -> 08:00-11:00 di aplikasi');
    // UC-04 pada pasien tanpa pendaftaran: ditolak dengan pesan, tidak melempar exception
    $x = (new Uc04UbahStatusPasien($db, ['role' => 'petugas']))->mainFlow(4, 'Hadir');
    tegas(!$x->berhasil, 'UC-04: pasien tanpa Pendaftaran tidak dapat diubah statusnya');
    // diagnosis > 255 karakter -> alur "data belum lengkap", tidak ada exception SQL
    $p = (new Uc11SimpanDataLayanan($db, ['role' => 'petugas']))->jalankan(1, str_repeat('x', 256), 'Istirahat');
    tegas(!$p->berhasil && $p->jalur === 'alt-1', 'diagnosis 256 karakter ditolak sebelum menyentuh DB');
    // kunjungan aktif memakai diagnosis '' (NOT NULL pada DDL)
    tegas((int) $db->ambilNilai("SELECT COUNT(*) FROM RiwayatKunjungan WHERE idPasien=1 AND diagnosis=''") === 1, 'kunjungan aktif pasien 1 (diagnosis kosong)');
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
