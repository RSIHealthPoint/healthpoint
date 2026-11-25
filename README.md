# HealthPoint / SiKlinik — PHP 8 native OOP

Implementasi UC-01..UC-17, ACT-01..ACT-19 dan SEQ-01..SEQ-17 dari `PHP.pdf`.

## Menjalankan (XAMPP / MySQL)
1. Start **MySQL** di XAMPP Control Panel (Apache tidak perlu).
2. Impor database (phpMyAdmin tab *Import*, DBeaver *Run script*, atau CLI), **berurutan**:

       mysql -u root < database/schema.sql
       mysql -u root < database/seed.sql

   `schema.sql` membuat database `healthpoint` + 14 tabel; `seed.sql` mengisi data demo (jalankan sekali; untuk mengulang,
   `DROP DATABASE healthpoint;` lalu impor lagi keduanya).
3. Dari folder proyek, jalankan (pakai PHP milik XAMPP bila `php` belum ada di PATH, mis. `C:\\xampp\\php\\php.exe`):

       php -S localhost:8000 index.php
4. Buka http://localhost:8000 lalu login dengan akun demo.

Bawaan koneksi: `127.0.0.1:3306`, user `root`, password kosong (default XAMPP). Ubah di `config/config.php` atau lewat
variabel lingkungan `HP_DB_HOST`, `HP_DB_PORT`, `HP_DB_NAME`, `HP_DB_USER`, `HP_DB_PASS`. Aplikasi hanya mendukung MySQL/MariaDB.
Butuh PHP >= 8.1 dengan `pdo_mysql` (aktif di XAMPP).
Akun demo: `budi/pasien123`, `siti/pasien123`, `petugas/petugas123`, `dokter/dokter123`, `pengelola/pengelola123`.

Pengujian: `php tests/run_tests.php` (database terpisah `healthpoint_test` dibuat otomatis dan diisi dari `schema.sql` + `seed.sql`;
memeriksa juga kesesuaian skema dengan DDL.pdf). Tanpa MySQL: `HP_DB_DRIVER=sqlite php tests/run_tests.php` (SQLite di memori; dua tes
khusus MySQL dilewati).

## Skema database (DDL.pdf 2.5)
`database/schema.sql` mengikuti `DDL.pdf` bagian 2.5: 13 tabel entitas (`DataPasien`, `Layanan`, `Dokter`, `JadwalDokter`, `Booking`, `Antrean`,
`WaktuPelayanan`, `Pendaftaran`, `PendaftaranVaksinasi`, `StokVaksin`, `PemakaianVaksin`, `RiwayatKunjungan`, `RekamMedis`) dengan nama tabel/kolom persis
seperti DDL. Satu tabel di luar DDL: `Pengguna` (login web). Di Linux nama tabel MySQL case-sensitive; gunakan penulisan seperti di atas.
Keputusan untuk hal yang tidak dijelaskan/bertentangan di DDL: lihat bagian "Asumsi / penyimpangan" di bawah.

## Struktur folder
    index.php             front controller (satu-satunya pintu masuk)
    config/               config.php (koneksi), Database.php (PDO), bootstrap.php (autoloader)
    controllers/          kelas kontrol (nama Kontrol* / *Controller mengikuti Class Diagram; jangan diganti agar jejak pesan cocok dengan PDF),
                          Router, Auth, Sesi, Request, Jejak
      flows/              satu kelas per use case (UC-01..UC-17: Main/Alternative Flow + halaman web)
    models/               Entity + akses data (+ Waktu.php, utilitas tanggal)
    views/
      layout/             Layout.php (header, navigasi, login, beranda), Boundary.php (kelas dasar)
      pasien/             boundary UC-01, 02, 03, 05, 06, 07, 13
      internal/petugas/   boundary UC-04, 08, 14, 15
      internal/dokter/    boundary UC-09, 10, 11
      internal/pengelola/ boundary UC-12, 16, 17
    database/             schema.sql (struktur tabel), seed.sql (data demo)
    public/               css/ (style.css = desain UI, app.css = komponen generik), js/, images/, mockups/ (prototipe UI statis HTML)
    tests/                run_tests.php, pemuat.php (memuat schema.sql + seed.sql untuk tes)

## Arsitektur
`views/` (boundary: form/halaman, method sesuai Class Diagram) → `controllers/` (validasi, alt/opt) → `models/` (Entity + akses data).
`controllers/flows/UcNN*.php` menyatukan Use Case Spec + Activity + Tabel Analisis Messages: tiap Flow punya `mainFlow()`/`alt*()` yang
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
10. Login/CSRF/peran (`Core/Auth`, `Router`) ditambahkan; tidak ada pada diagram.
