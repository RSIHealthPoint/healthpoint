# HealthPoint / SiKlinik — PHP 8 native OOP

Implementasi UC-01..UC-17, ACT-01..ACT-19 dan SEQ-01..SEQ-17 dari `PHP.pdf`.

## Menjalankan
    php tests/run_tests.php                               # 103 pemeriksaan Main/Alternative Flow
    php -S localhost:8000 -t public public/index.php      # web (DB SQLite dibuat otomatis + data demo)
Butuh PHP >= 8.1 dengan `pdo_sqlite`. Akun demo: `budi/pasien123`, `siti/pasien123`, `petugas/petugas123`, `dokter/dokter123`, `pengelola/pengelola123`.

## Arsitektur
`Boundaries/` (form/halaman, method sesuai Class Diagram) → `Controllers/` (validasi, alt/opt) → `Models/` (Entity + akses data).
`Flows/UcNN*.php` menyatukan Use Case Spec + Activity + Tabel Analisis Messages: tiap Flow punya `mainFlow()`/`alt*()` yang
mengembalikan `FlowResult` berisi **jejak pesan** (`Core/Jejak`) berformat nomor pesan PDF (`4.1`, `5a`, ...). `tests/run_tests.php`
membandingkan jejak nyata dengan Tabel Analisis Messages. Alur web (`web()`) memakai kelas yang sama.

## Penomoran ACT
Bagian Activity Diagram PDF memakai ACT-01..ACT-17 (sama dengan nomor UC), sedangkan header SEQ-02..SEQ-17 menulis ACT-04..ACT-19
(UC + 2). Keduanya dicantumkan di komentar tiap Flow (`ACT-02 (header SEQ: ACT-04)`) dan di `Flow::info()`.

## Asumsi / penyimpangan (karena PDF tidak merinci atau saling berbeda)
1. Visibility: atribut Entity `private`, method `public` (tabel PDF). Booking diambil versi semua-private (p.95/113). Method
   `FormKelolaJadwalDokter` ditandai Private di PDF; dibuat `public` agar dapat dipanggil.
2. UC-01 membuat `Pendaftaran` **dan** `Booking` (jenis `layanan`) agar UC-02/UC-03 punya data (precondition UC-03).
3. UC-04: pesan 9-10 (`kirimStatusTerbaru`/`tampilkanStatusTerbaru`) diturunkan dari Class Diagram; tabel hanya sampai pesan 8.
4. UC-05 Alt-2 ("antrean belum dimulai") tidak ada di SEQ-05; diberi nomor `4a`.
5. Pembatalan booking: maksimal 2 jam sebelum jadwal (`Booking::BATAS_JAM_PEMBATALAN`). Kapasitas kontrol 4 booking/jam.
6. UC-11 juga menulis `RekamMedis` (Class Diagram + langkah 3 UC) selain `RiwayatKunjungan::simpanHasil` (SEQ-11).
7. UC-13: kuota dikurangi saat pendaftaran (SEQ-13 pesan 17), bukan setelah vaksinasi selesai (teks UC); dosis dicatat ke
   `PemakaianVaksin` agar UC-17 punya sumber data (PDF tidak menyebut jalur tulisnya).
8. `StokVaksin.jenisVaksin` = `namaVaksin` (Class Diagram AZ). `WaktuPelayanan` tidak punya tabel kelas; atribut diturunkan.
9. Aktor SEQ-09 tertulis "Petugas" di header tetapi "Dokter" di pesan/UC; dipakai Dokter. UC-11: Petugas atau Dokter.
10. Login/CSRF/peran (`Core/Auth`, `Router`) ditambahkan; tidak ada pada diagram. `styles.css` pada `healthpoint-ui.zip` belum dipasang.
