-- File: database/schema.sql  (SQLite)
-- Tabel mengikuti atribut Entity pada Class Diagram (PHP.pdf, bagian 2.4).
CREATE TABLE IF NOT EXISTS pengguna (
  id_pengguna INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK (role IN ('pasien','petugas','dokter','pengelola')),
  id_pasien INTEGER NULL
);
CREATE TABLE IF NOT EXISTS pasien (
  id_pasien INTEGER PRIMARY KEY AUTOINCREMENT,
  no_rm TEXT NOT NULL UNIQUE,
  nama TEXT NOT NULL,
  tanggal_lahir TEXT NOT NULL,
  jenis_kelamin TEXT NOT NULL,
  alamat TEXT NOT NULL DEFAULT '',
  no_telepon TEXT NOT NULL DEFAULT '',
  status_kedatangan TEXT NOT NULL DEFAULT 'Belum Hadir'
);
CREATE TABLE IF NOT EXISTS layanan (
  id_layanan INTEGER PRIMARY KEY AUTOINCREMENT,
  nama_layanan TEXT NOT NULL,
  deskripsi TEXT NOT NULL DEFAULT '',
  tersedia INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS dokter (
  id_dokter INTEGER PRIMARY KEY AUTOINCREMENT,
  nama_dokter TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS jadwal_dokter (
  id_jadwal INTEGER PRIMARY KEY AUTOINCREMENT,
  id_dokter INTEGER NOT NULL REFERENCES dokter(id_dokter),
  hari TEXT NOT NULL,
  jam_praktik TEXT NOT NULL,
  status_ketersediaan TEXT NOT NULL,
  UNIQUE (id_dokter, hari)
);
CREATE TABLE IF NOT EXISTS booking (
  id_booking INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pasien INTEGER NOT NULL REFERENCES pasien(id_pasien),
  tanggal_kunjungan TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Aktif',
  id_layanan INTEGER NOT NULL REFERENCES layanan(id_layanan),
  jam_kontrol TEXT NOT NULL DEFAULT '',
  jenis_booking TEXT NOT NULL DEFAULT 'layanan'
);
CREATE TABLE IF NOT EXISTS pendaftaran (
  id_pendaftaran INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pasien INTEGER NOT NULL REFERENCES pasien(id_pasien),
  id_layanan INTEGER NOT NULL REFERENCES layanan(id_layanan),
  id_vaksin INTEGER NULL,
  tanggal_daftar TEXT NOT NULL,
  status_kedatangan TEXT NOT NULL DEFAULT 'Belum Hadir'
);
CREATE TABLE IF NOT EXISTS antrean (
  id_antrean INTEGER PRIMARY KEY AUTOINCREMENT,
  nomor_antrean TEXT NOT NULL UNIQUE,
  status_antrean TEXT NOT NULL DEFAULT 'Menunggu',
  waktu_daftar TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS waktu_pelayanan (
  id_waktu INTEGER PRIMARY KEY AUTOINCREMENT,
  id_layanan INTEGER NOT NULL,
  durasi_menit INTEGER NOT NULL,
  tanggal TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS riwayat_kunjungan (
  id_kunjungan INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pasien INTEGER NOT NULL REFERENCES pasien(id_pasien),
  tanggal_kunjungan TEXT NOT NULL,
  layanan TEXT NOT NULL,
  ringkasan TEXT NOT NULL DEFAULT '',
  keluhan_utama TEXT NOT NULL DEFAULT '',
  tekanan_darah TEXT NOT NULL DEFAULT '',
  nadi INTEGER NOT NULL DEFAULT 0,
  suhu REAL NOT NULL DEFAULT 0,
  spo2 INTEGER NOT NULL DEFAULT 0,
  diagnosis TEXT NULL,
  tindakan TEXT NULL
);
CREATE TABLE IF NOT EXISTS rekam_medis (
  id_rekam_medis INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pasien INTEGER NOT NULL REFERENCES pasien(id_pasien),
  diagnosis TEXT NOT NULL,
  tindakan TEXT NOT NULL,
  tanggal_layanan TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS stok_vaksin (
  id_vaksin INTEGER PRIMARY KEY AUTOINCREMENT,
  jenis_vaksin TEXT NOT NULL,
  tipe_vaksin TEXT NOT NULL,
  jumlah_stok INTEGER NOT NULL DEFAULT 0,
  sisa_kuota INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS pendaftaran_vaksinasi (
  id_pendaftaran INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pasien INTEGER NOT NULL REFERENCES pasien(id_pasien),
  id_vaksin INTEGER NOT NULL REFERENCES stok_vaksin(id_vaksin),
  tanggal_daftar TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS pemakaian_vaksin (
  id_pemakaian INTEGER PRIMARY KEY AUTOINCREMENT,
  id_vaksin INTEGER NOT NULL REFERENCES stok_vaksin(id_vaksin),
  tanggal_pemakaian TEXT NOT NULL,
  jumlah_dosis INTEGER NOT NULL
);
