<?php
/**
 * File: app/Core/Seeder.php
 * Data awal untuk demo & pengujian. Tanggal relatif terhadap Waktu::hariIni().
 */
declare(strict_types=1);

namespace HealthPoint\Core;

final class Seeder
{
    public static function isi(Database $db): void
    {
        $h = Waktu::hariIni();
        $db->jalankan("INSERT INTO layanan (nama_layanan, deskripsi, tersedia) VALUES
            ('Poli Umum','Pemeriksaan kesehatan umum',1),
            ('Poli Gigi','Pemeriksaan dan perawatan gigi',1),
            ('Vaksinasi','Layanan imunisasi',1),
            ('Poli Mata','Sedang tidak melayani',0)");
        $db->jalankan("INSERT INTO dokter (nama_dokter) VALUES ('dr. Nabila Maulidya'),('dr. Sunaryo Wijaya')");
        foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $hari) {
            $db->jalankan('INSERT INTO jadwal_dokter (id_dokter,hari,jam_praktik,status_ketersediaan) VALUES (1,?,?,?)', [$hari, '08:00-12:00', 'Tersedia']);
            $db->jalankan('INSERT INTO jadwal_dokter (id_dokter,hari,jam_praktik,status_ketersediaan) VALUES (2,?,?,?)', [$hari, '09:00-13:00', $hari === 'Jumat' ? 'Tidak Tersedia' : 'Tersedia']);
        }
        $db->jalankan("INSERT INTO pasien (no_rm,nama,tanggal_lahir,jenis_kelamin,alamat,no_telepon) VALUES
            ('RM-0001','Budi Santoso','1990-05-14','Laki-laki','Jl. Veteran 1, Malang','081200000001'),
            ('RM-0002','Siti Aminah','1985-11-02','Perempuan','Jl. Soekarno Hatta 9, Malang','081200000002'),
            ('RM-0003','Budi Santoso','1996-01-20','Laki-laki','Jl. Kawi 5, Malang','081200000003'),
            ('RM-0004','Dewi Lestari','2000-07-07','Perempuan','Jl. Ijen 3, Malang','081200000004')");
        $db->jalankan("INSERT INTO pengguna (username,password_hash,role,id_pasien) VALUES (?,?,?,?),(?,?,?,?),(?,?,?,?),(?,?,?,?),(?,?,?,?)", [
            'budi', password_hash('pasien123', PASSWORD_DEFAULT), 'pasien', 1,
            'siti', password_hash('pasien123', PASSWORD_DEFAULT), 'pasien', 2,
            'petugas', password_hash('petugas123', PASSWORD_DEFAULT), 'petugas', null,
            'dokter', password_hash('dokter123', PASSWORD_DEFAULT), 'dokter', null,
            'pengelola', password_hash('pengelola123', PASSWORD_DEFAULT), 'pengelola', null,
        ]);
        // Antrean hari ini: A001 selesai, A002 sedang dilayani, A003..A005 menunggu.
        foreach ([['A001', 'Selesai'], ['A002', 'Dilayani'], ['A003', 'Menunggu'], ['A004', 'Menunggu'], ['A005', 'Menunggu']] as $i => [$no, $st]) {
            $db->jalankan('INSERT INTO antrean (nomor_antrean,status_antrean,waktu_daftar) VALUES (?,?,?)', [$no, $st, "$h 07:0$i:00"]);
        }
        foreach ([10, 12, 8] as $durasi) {
            $db->jalankan('INSERT INTO waktu_pelayanan (id_layanan,durasi_menit,tanggal) VALUES (1,?,?)', [$durasi, $h]);
        }
        // Riwayat: pasien 1 punya 1 kunjungan selesai + 1 kunjungan aktif (belum diagnosis).
        $db->jalankan("INSERT INTO riwayat_kunjungan (id_pasien,tanggal_kunjungan,layanan,ringkasan,keluhan_utama,tekanan_darah,nadi,suhu,spo2,diagnosis,tindakan)
            VALUES (1,'2026-08-10','Poli Umum','Kontrol tekanan darah','Pusing','130/85',80,36.5,98,'Hipertensi ringan','Edukasi')");
        $db->jalankan("INSERT INTO riwayat_kunjungan (id_pasien,tanggal_kunjungan,layanan,ringkasan,keluhan_utama,tekanan_darah,nadi,suhu,spo2)
            VALUES (1,?,'Poli Umum','Pemeriksaan umum','Demam 2 hari','120/80',88,38.1,97)", [$h]);
        $db->jalankan("INSERT INTO riwayat_kunjungan (id_pasien,tanggal_kunjungan,layanan,ringkasan,keluhan_utama,diagnosis,tindakan)
            VALUES (3,'2026-07-01','Poli Gigi','Tambal gigi','Gigi berlubang','Karies','Penambalan')");
        $db->jalankan("INSERT INTO stok_vaksin (jenis_vaksin,tipe_vaksin,jumlah_stok,sisa_kuota) VALUES
            ('Influenza','Inaktif',50,50),('Hepatitis B','Rekombinan',2,2),('Tetanus','Toksoid',0,0)");
    }
}
