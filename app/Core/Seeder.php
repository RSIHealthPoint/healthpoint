<?php
/**
 * File: app/Core/Seeder.php
 * Data awal untuk demo & pengujian (MySQL). Format data mengikuti kolom Contoh Data pada DDL.pdf 2.5.
 * Tanggal relatif terhadap Waktu::hariIni(). Seluruh nilai muat dalam tipe/panjang kolom DDL.
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Seeder
{
    public static function isi(Database $db): void
    {
        $h = Waktu::hariIni();

        // Layanan (deskripsi nullable). Layanan 'Vaksinasi' wajib ada: dipakai Pendaftaran milik PendaftaranVaksinasi.
        $db->jalankan("INSERT INTO Layanan (namaLayanan, deskripsi) VALUES
            ('Poli Umum','Pemeriksaan dan konsultasi kesehatan umum'),
            ('Poli Gigi','Pemeriksaan dan perawatan gigi'),
            ('Vaksinasi','Layanan imunisasi')");

        // Dokter + JadwalDokter (jamPraktik disimpan berformat DDL: '08.00-12.00').
        $db->jalankan("INSERT INTO Dokter (namaDokter) VALUES ('dr. Nabila Maulidya'),('dr. Sunaryo Wijaya')");
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
            $db->jalankan('INSERT INTO JadwalDokter (idDokter,hari,jamPraktik,statusKetersediaan) VALUES (1,?,?,?)', [$hari, Waktu::jamKeDb('08:00-12:00'), 'Tersedia']);
            $db->jalankan('INSERT INTO JadwalDokter (idDokter,hari,jamPraktik,statusKetersediaan) VALUES (2,?,?,?)', [$hari, Waktu::jamKeDb('09:00-13:00'), $hari === 'Jumat' ? 'Tidak Tersedia' : 'Tersedia']);
        }

        $db->jalankan("INSERT INTO DataPasien (noRM,nama,tanggalLahir,jenisKelamin,alamat,noTelepon) VALUES
            ('RM-0001','Budi Santoso','1990-05-14','Laki-laki','Jl. Veteran 1, Malang','081200000001'),
            ('RM-0002','Siti Aminah','1985-11-02','Perempuan','Jl. Soekarno Hatta 9, Malang','081200000002'),
            ('RM-0003','Budi Santoso','1996-01-20','Laki-laki','Jl. Kawi 5, Malang','081200000003'),
            ('RM-0004','Dewi Lestari','2000-07-07','Perempuan','Jl. Ijen 3, Malang','081200000004')");
        $db->jalankan('INSERT INTO Pengguna (username,passwordHash,role,idPasien) VALUES (?,?,?,?),(?,?,?,?),(?,?,?,?),(?,?,?,?),(?,?,?,?)', [
            'budi', password_hash('pasien123', PASSWORD_DEFAULT), 'pasien', 1,
            'siti', password_hash('pasien123', PASSWORD_DEFAULT), 'pasien', 2,
            'petugas', password_hash('petugas123', PASSWORD_DEFAULT), 'petugas', null,
            'dokter', password_hash('dokter123', PASSWORD_DEFAULT), 'dokter', null,
            'pengelola', password_hash('pengelola123', PASSWORD_DEFAULT), 'pengelola', null,
        ]);

        // Pendaftaran lama pasien 1 & 2 (sumber statusKedatangan untuk UC-04; tanggal lampau agar tidak masuk laporan hari ini).
        $db->jalankan("INSERT INTO Pendaftaran (idPasien,idLayanan,tanggalDaftar,statusKedatangan) VALUES (1,1,'2026-08-10','Hadir'),(2,1,'2026-08-12','Hadir')");

        // Antrean hari ini: Antrean wajib berasal dari Booking (idBooking NOT NULL, 1 : 0..1). Booking demo atas nama pasien 3 & 4.
        // A001 selesai, A002 sedang dilayani, A003..A005 menunggu.
        foreach ([['A001', 'Selesai', 3], ['A002', 'Dilayani', 4], ['A003', 'Menunggu', 3], ['A004', 'Menunggu', 4], ['A005', 'Menunggu', 3]] as $i => [$no, $st, $idPasien]) {
            $db->jalankan('INSERT INTO Booking (idPasien,idLayanan,tanggalKunjungan,status,jamKontrol,jenisBooking) VALUES (?,?,?,?,NULL,?)', [$idPasien, 1, $h, 'Aktif', 'Layanan']);
            $db->jalankan('INSERT INTO Antrean (idBooking,nomorAntrean,statusAntrean,waktuDaftar) VALUES (?,?,?,?)', [$db->idTerakhir(), $no, $st, "$h 07:0$i:00"]);
        }

        foreach ([10, 12, 8] as $rata) {
            $db->jalankan('INSERT INTO WaktuPelayanan (idLayanan,rataRataWaktu,tanggal) VALUES (1,?,?)', [number_format($rata, 2, '.', ''), $h]);
        }

        // Riwayat: pasien 1 punya 1 kunjungan selesai + 1 kunjungan aktif (belum diagnosis: diagnosis & tindakan '' karena NOT NULL).
        $db->jalankan("INSERT INTO RiwayatKunjungan (idPasien,idLayanan,tanggalKunjungan,keluhanUtama,tekananDarah,nadi,suhu,spo2,diagnosis,tindakan)
            VALUES (1,1,'2026-08-10','Pusing','130/85',80,36.5,98,'Hipertensi ringan','Edukasi')");
        $db->jalankan("INSERT INTO RiwayatKunjungan (idPasien,idLayanan,tanggalKunjungan,keluhanUtama,tekananDarah,nadi,suhu,spo2,diagnosis,tindakan)
            VALUES (1,1,?,'Demam 2 hari','120/80',88,38.1,97,'','')", [$h]);
        $db->jalankan("INSERT INTO RiwayatKunjungan (idPasien,idLayanan,tanggalKunjungan,keluhanUtama,tekananDarah,nadi,suhu,spo2,diagnosis,tindakan)
            VALUES (3,2,'2026-07-01','Gigi berlubang',NULL,NULL,NULL,NULL,'Karies','Penambalan')");

        $db->jalankan("INSERT INTO StokVaksin (jenisVaksin,tipeVaksin,jumlahStok,sisaKuota) VALUES
            ('Influenza','Inaktif',50,50),('Hepatitis B','Rekombinan',2,2),('Tetanus','Toksoid',0,0)");
    }
}
