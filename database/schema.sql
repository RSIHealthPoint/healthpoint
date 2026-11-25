-- File: database/schema.sql  (MySQL 5.7+/8.x dan MariaDB 10.x XAMPP, InnoDB, utf8mb4)
-- Sumber: DDL.pdf bagian 2.5 Pemodelan Data (tabel entitas + tabel Hubungan Antar Kelas).
-- Nama tabel = nama kelas entitas, nama kolom = nama atribut (camelCase) persis seperti pada DDL.pdf.
-- Catatan: pada server MySQL berbasis Linux nama tabel bersifat case-sensitive. Pakai penulisan persis di bawah ini.
-- Tabel Pengguna (paling bawah) BUKAN entitas DDL.pdf: hanya tabel pendukung login web.

CREATE DATABASE IF NOT EXISTS healthpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthpoint;

CREATE TABLE IF NOT EXISTS DataPasien (
  idPasien      INT          NOT NULL AUTO_INCREMENT,
  noRM          VARCHAR(15)  NOT NULL,
  nama          VARCHAR(100) NOT NULL,
  tanggalLahir  DATE         NOT NULL,
  jenisKelamin  VARCHAR(10)  NOT NULL,
  alamat        VARCHAR(255) NOT NULL,
  noTelepon     VARCHAR(15)  NOT NULL,
  PRIMARY KEY (idPasien),
  UNIQUE KEY uq_datapasien_norm (noRM)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Layanan (
  idLayanan     INT          NOT NULL AUTO_INCREMENT,
  namaLayanan   VARCHAR(100) NOT NULL,
  deskripsi     VARCHAR(255) NULL,
  PRIMARY KEY (idLayanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Dokter (
  idDokter      INT          NOT NULL AUTO_INCREMENT,
  namaDokter    VARCHAR(100) NOT NULL,
  PRIMARY KEY (idDokter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS JadwalDokter (
  idJadwal            INT         NOT NULL AUTO_INCREMENT,
  idDokter            INT         NOT NULL,
  hari                VARCHAR(10) NOT NULL,
  jamPraktik          VARCHAR(15) NOT NULL,
  statusKetersediaan  VARCHAR(20) NOT NULL,
  PRIMARY KEY (idJadwal),
  CONSTRAINT fk_jadwaldokter_dokter FOREIGN KEY (idDokter) REFERENCES Dokter (idDokter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Booking (
  idBooking         INT         NOT NULL AUTO_INCREMENT,
  idPasien          INT         NOT NULL,
  idLayanan         INT         NOT NULL,
  tanggalKunjungan  DATE        NOT NULL,
  status            VARCHAR(20) NOT NULL,
  jamKontrol        VARCHAR(5)  NULL,
  jenisBooking      VARCHAR(20) NOT NULL,
  PRIMARY KEY (idBooking),
  CONSTRAINT fk_booking_datapasien FOREIGN KEY (idPasien)  REFERENCES DataPasien (idPasien),
  CONSTRAINT fk_booking_layanan    FOREIGN KEY (idLayanan) REFERENCES Layanan (idLayanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Booking 1 : 0..1 Antrean ("memperoleh") => idBooking NOT NULL + UNIQUE.
CREATE TABLE IF NOT EXISTS Antrean (
  idAntrean     INT         NOT NULL AUTO_INCREMENT,
  idBooking     INT         NOT NULL,
  nomorAntrean  VARCHAR(10) NOT NULL,
  statusAntrean VARCHAR(20) NOT NULL,
  waktuDaftar   DATETIME    NOT NULL,
  PRIMARY KEY (idAntrean),
  UNIQUE KEY uq_antrean_booking (idBooking),
  CONSTRAINT fk_antrean_booking FOREIGN KEY (idBooking) REFERENCES Booking (idBooking)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS WaktuPelayanan (
  idWaktuPelayanan INT          NOT NULL AUTO_INCREMENT,
  idLayanan        INT          NOT NULL,
  rataRataWaktu    DECIMAL(5,2) NOT NULL,
  tanggal          DATE         NOT NULL,
  PRIMARY KEY (idWaktuPelayanan),
  CONSTRAINT fk_waktupelayanan_layanan FOREIGN KEY (idLayanan) REFERENCES Layanan (idLayanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Superclass dari PendaftaranVaksinasi (generalisasi / "spesialisasi").
CREATE TABLE IF NOT EXISTS Pendaftaran (
  idPendaftaran    INT         NOT NULL AUTO_INCREMENT,
  idPasien         INT         NOT NULL,
  idLayanan        INT         NOT NULL,
  tanggalDaftar    DATE        NOT NULL,
  statusKedatangan VARCHAR(20) NOT NULL,
  PRIMARY KEY (idPendaftaran),
  CONSTRAINT fk_pendaftaran_datapasien FOREIGN KEY (idPasien)  REFERENCES DataPasien (idPasien),
  CONSTRAINT fk_pendaftaran_layanan    FOREIGN KEY (idLayanan) REFERENCES Layanan (idLayanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS StokVaksin (
  idVaksin     INT          NOT NULL AUTO_INCREMENT,
  jenisVaksin  VARCHAR(100) NOT NULL,
  tipeVaksin   VARCHAR(50)  NOT NULL,
  jumlahStok   INT          NOT NULL,
  sisaKuota    INT          NOT NULL,
  PRIMARY KEY (idVaksin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subclass: PK sekaligus FK ke Pendaftaran.
CREATE TABLE IF NOT EXISTS PendaftaranVaksinasi (
  idPendaftaran INT NOT NULL,
  idVaksin      INT NOT NULL,
  PRIMARY KEY (idPendaftaran),
  CONSTRAINT fk_pendaftaranvaksinasi_pendaftaran FOREIGN KEY (idPendaftaran) REFERENCES Pendaftaran (idPendaftaran),
  CONSTRAINT fk_pendaftaranvaksinasi_stokvaksin  FOREIGN KEY (idVaksin)      REFERENCES StokVaksin (idVaksin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS PemakaianVaksin (
  idPemakaian       INT  NOT NULL AUTO_INCREMENT,
  idVaksin          INT  NOT NULL,
  tanggalPemakaian  DATE NOT NULL,
  jumlahDosis       INT  NOT NULL,
  PRIMARY KEY (idPemakaian),
  CONSTRAINT fk_pemakaianvaksin_stokvaksin FOREIGN KEY (idVaksin) REFERENCES StokVaksin (idVaksin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS RiwayatKunjungan (
  idKunjungan       INT           NOT NULL AUTO_INCREMENT,
  idPasien          INT           NOT NULL,
  idLayanan         INT           NOT NULL,
  tanggalKunjungan  DATE          NOT NULL,
  keluhanUtama      VARCHAR(255)  NOT NULL,
  tekananDarah      VARCHAR(10)   NULL,
  nadi              INT           NULL,
  suhu              DECIMAL(4,1)  NULL,
  spo2              INT           NULL,
  diagnosis         VARCHAR(255)  NOT NULL,
  tindakan          VARCHAR(255)  NOT NULL,
  PRIMARY KEY (idKunjungan),
  CONSTRAINT fk_riwayatkunjungan_datapasien FOREIGN KEY (idPasien)  REFERENCES DataPasien (idPasien),
  CONSTRAINT fk_riwayatkunjungan_layanan    FOREIGN KEY (idLayanan) REFERENCES Layanan (idLayanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RekamMedis tidak punya tabel atribut pada DDL.pdf (hanya di diagram dan tabel hubungan: DataPasien 1 : 0..* RekamMedis).
-- Atribut diambil dari diagram DDL (idRekamMedis, idPasien, tanggalLayanan) + Class Diagram 2.4 (diagnosis, tindakan).
CREATE TABLE IF NOT EXISTS RekamMedis (
  idRekamMedis    INT          NOT NULL AUTO_INCREMENT,
  idPasien        INT          NOT NULL,
  diagnosis       VARCHAR(255) NOT NULL,
  tindakan        VARCHAR(255) NOT NULL,
  tanggalLayanan  DATE         NOT NULL,
  PRIMARY KEY (idRekamMedis),
  CONSTRAINT fk_rekammedis_datapasien FOREIGN KEY (idPasien) REFERENCES DataPasien (idPasien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Di luar DDL.pdf: akun login aplikasi web (Core/Auth). Tidak berelasi dengan entitas lain kecuali idPasien (akun pasien).
CREATE TABLE IF NOT EXISTS Pengguna (
  idPengguna    INT          NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  passwordHash  VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL,
  idPasien      INT          NULL,
  PRIMARY KEY (idPengguna),
  UNIQUE KEY uq_pengguna_username (username),
  CONSTRAINT fk_pengguna_datapasien FOREIGN KEY (idPasien) REFERENCES DataPasien (idPasien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
