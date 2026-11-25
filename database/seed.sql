-- File: database/seed.sql
-- Data demo HealthPoint (jalankan SETELAH schema.sql). Format data mengikuti kolom "Contoh Data" DDL.pdf 2.5.
-- Tanggal hari ini memakai CURDATE(). Jalankan sekali saja; untuk mengulang, impor ulang schema.sql setelah DROP DATABASE healthpoint.
-- Impor manual (DBeaver/phpMyAdmin/CLI): schema.sql lalu seed.sql.
-- Akun demo: budi/pasien123, siti/pasien123, petugas/petugas123, dokter/dokter123, pengelola/pengelola123 (hash bcrypt).

USE healthpoint;

-- Layanan 'Vaksinasi' wajib ada: dipakai Pendaftaran milik PendaftaranVaksinasi.
INSERT INTO Layanan (idLayanan, namaLayanan, deskripsi) VALUES
  (1, 'Poli Umum', 'Pemeriksaan dan konsultasi kesehatan umum'),
  (2, 'Vaksinasi', 'Layanan imunisasi');

INSERT INTO Dokter (idDokter, namaDokter) VALUES
  (1, 'dr. Nabila Maulidya'),
  (2, 'dr. Sunaryo Wijaya');

-- jamPraktik berformat DDL ('08.00-12.00'). Dokter 2 tidak tersedia pada hari Jumat.
INSERT INTO JadwalDokter (idDokter, hari, jamPraktik, statusKetersediaan) VALUES
  (1, 'Senin',  '08.00-12.00', 'Tersedia'),
  (2, 'Senin',  '09.00-13.00', 'Tersedia'),
  (1, 'Selasa', '08.00-12.00', 'Tersedia'),
  (2, 'Selasa', '09.00-13.00', 'Tersedia'),
  (1, 'Rabu',   '08.00-12.00', 'Tersedia'),
  (2, 'Rabu',   '09.00-13.00', 'Tersedia'),
  (1, 'Kamis',  '08.00-12.00', 'Tersedia'),
  (2, 'Kamis',  '09.00-13.00', 'Tersedia'),
  (1, 'Jumat',  '08.00-12.00', 'Tersedia'),
  (2, 'Jumat',  '09.00-13.00', 'Tidak Tersedia');

INSERT INTO DataPasien (idPasien, noRM, nama, tanggalLahir, jenisKelamin, alamat, noTelepon) VALUES
  (1, 'RM-0001', 'Budi Santoso',  '1990-05-14', 'Laki-laki', 'Jl. Veteran 1, Malang',          '081200000001'),
  (2, 'RM-0002', 'Siti Aminah',   '1985-11-02', 'Perempuan', 'Jl. Soekarno Hatta 9, Malang',   '081200000002'),
  (3, 'RM-0003', 'Budi Santoso',  '1996-01-20', 'Laki-laki', 'Jl. Kawi 5, Malang',             '081200000003'),
  (4, 'RM-0004', 'Dewi Lestari',  '2000-07-07', 'Perempuan', 'Jl. Ijen 3, Malang',             '081200000004');

INSERT INTO Pengguna (username, passwordHash, role, idPasien) VALUES
  ('budi',      '$2y$10$kqGbNuZOxy7jxMM0p2clXuLMod8roLYsA5DmR3LBo4Hqj/.63DFQy', 'pasien',    1),
  ('siti',      '$2y$10$kqGbNuZOxy7jxMM0p2clXuLMod8roLYsA5DmR3LBo4Hqj/.63DFQy', 'pasien',    2),
  ('petugas',   '$2y$10$TUqLwV7Y9iswKOzpKGTMG./1lOosAeAuatUCzHd.JbU2ouHJ.eJvW', 'petugas',   NULL),
  ('dokter',    '$2y$10$uMrCfKzQ3q0GLS6puVoidOJOrWmVZVK6eWWGR3tO4js/VlWa0.VkG', 'dokter',    NULL),
  ('pengelola', '$2y$10$G.Co53bjpOy9Q68F/g8Gn.H9MBwuWpY/pEKxNgvUtZcO39nP3P7bO', 'pengelola', NULL);

-- Pendaftaran lama pasien 1 & 2 (sumber statusKedatangan untuk UC-04; tanggal lampau agar tidak masuk laporan hari ini).
INSERT INTO Pendaftaran (idPasien, idLayanan, tanggalDaftar, statusKedatangan) VALUES
  (1, 1, '2026-08-10', 'Hadir'),
  (2, 1, '2026-08-12', 'Hadir');

-- Antrean hari ini (Antrean wajib berasal dari Booking). A001 selesai, A002 dilayani, A003..A005 menunggu.
INSERT INTO Booking (idBooking, idPasien, idLayanan, tanggalKunjungan, status, jamKontrol, jenisBooking) VALUES
  (1, 3, 1, CURDATE(), 'Aktif', NULL, 'Layanan'),
  (2, 4, 1, CURDATE(), 'Aktif', NULL, 'Layanan'),
  (3, 3, 1, CURDATE(), 'Aktif', NULL, 'Layanan'),
  (4, 4, 1, CURDATE(), 'Aktif', NULL, 'Layanan'),
  (5, 3, 1, CURDATE(), 'Aktif', NULL, 'Layanan');

INSERT INTO Antrean (idBooking, nomorAntrean, statusAntrean, waktuDaftar) VALUES
  (1, 'A001', 'Selesai',  CONCAT(CURDATE(), ' 07:00:00')),
  (2, 'A002', 'Dilayani', CONCAT(CURDATE(), ' 07:01:00')),
  (3, 'A003', 'Menunggu', CONCAT(CURDATE(), ' 07:02:00')),
  (4, 'A004', 'Menunggu', CONCAT(CURDATE(), ' 07:03:00')),
  (5, 'A005', 'Menunggu', CONCAT(CURDATE(), ' 07:04:00'));

INSERT INTO WaktuPelayanan (idLayanan, rataRataWaktu, tanggal) VALUES
  (1, 10.00, CURDATE()),
  (1, 12.00, CURDATE()),
  (1,  8.00, CURDATE());

-- Riwayat: pasien 1 punya 1 kunjungan selesai + 1 kunjungan aktif (diagnosis & tindakan '' karena NOT NULL = belum diperiksa).
INSERT INTO RiwayatKunjungan (idPasien, idLayanan, tanggalKunjungan, keluhanUtama, tekananDarah, nadi, suhu, spo2, diagnosis, tindakan) VALUES
  (1, 1, '2026-08-10', 'Pusing',          '130/85', 80,   36.5, 98,   'Hipertensi ringan', 'Edukasi'),
  (1, 1, CURDATE(),    'Demam 2 hari',    '120/80', 88,   38.1, 97,   '', ''),


INSERT INTO StokVaksin (idVaksin, jenisVaksin, tipeVaksin, jumlahStok, sisaKuota) VALUES
  (1, 'Influenza',   'Inaktif',    50, 50),
  (2, 'Hepatitis B', 'Rekombinan',  2,  2),
  (3, 'Tetanus',     'Toksoid',     0,  0);
