<?php
/**
 * File: Models/DataPasien.php
 * Entity DataPasien (identitas). SEQ-01 (validasi kelengkapan), SEQ-09 pesan 5, SEQ-14 pesan 5, SEQ-15 pesan 5.
 */
declare(strict_types=1);

namespace HealthPoint\Models;

use HealthPoint\Core\Database;

final class DataPasien extends Model
{
    private int $idPasien = 0;
    private string $noRM = '';
    private string $nama = '';
    private string $tanggalLahir = '';
    private string $jenisKelamin = '';
    private string $alamat = '';
    private string $noTelepon = '';

    /** @param array<string,mixed> $r */
    private static function dari(Database $db, array $r): self
    {
        $m = new self($db);
        $m->idPasien = (int) $r['idPasien'];
        $m->noRM = (string) $r['noRM'];
        $m->nama = (string) $r['nama'];
        $m->tanggalLahir = (string) $r['tanggalLahir'];
        $m->jenisKelamin = (string) $r['jenisKelamin'];
        $m->alamat = (string) $r['alamat'];
        $m->noTelepon = (string) $r['noTelepon'];
        return $m;
    }

    public function getIdPasien(): int { return $this->idPasien; }
    public function getNoRM(): string { return $this->noRM; }
    public function getNama(): string { return $this->nama; }
    public function getTanggalLahir(): string { return $this->tanggalLahir; }
    public function getJenisKelamin(): string { return $this->jenisKelamin; }
    public function getAlamat(): string { return $this->alamat; }
    public function getNoTelepon(): string { return $this->noTelepon; }

    /** Identitas wajib untuk pendaftaran (UC-01 Alternative Flow-2: data tidak lengkap). */
    public function lengkap(): bool
    {
        return $this->nama !== '' && $this->tanggalLahir !== '' && $this->noTelepon !== '' && $this->alamat !== '';
    }

    /** @return list<string> nama kolom yang kosong */
    public function kolomKosong(): array
    {
        $kosong = [];
        foreach (['nama' => $this->nama, 'tanggalLahir' => $this->tanggalLahir, 'alamat' => $this->alamat, 'noTelepon' => $this->noTelepon] as $k => $v) {
            if ($v === '') { $kosong[] = $k; }
        }
        return $kosong;
    }

    public function getById(int $idPasien): ?self
    {
        $r = $this->db->ambilSatu('SELECT * FROM DataPasien WHERE idPasien = ?', [$idPasien]);
        return $r === null ? null : self::dari($this->db, $r);
    }

    /** SEQ-15 pesan 5 */
    public function getDataLengkap(int $idPasien): ?self
    {
        return $this->getById($idPasien);
    }

    /**
     * SEQ-14 pesan 5: nama wajib; tanggal lahir dan/atau No RM menyaring bila diisi.
     * @return list<self>
     */
    public function cariPasien(string $nama, ?string $tanggalLahir = null, ?string $noRM = null): array
    {
        $sql = 'SELECT * FROM DataPasien WHERE LOWER(nama) LIKE ?';
        $par = ['%' . strtolower(trim($nama)) . '%'];
        if ($tanggalLahir !== null && $tanggalLahir !== '') { $sql .= ' AND tanggalLahir = ?'; $par[] = $tanggalLahir; }
        if ($noRM !== null && $noRM !== '') { $sql .= ' AND UPPER(noRM) = ?'; $par[] = strtoupper(trim($noRM)); }
        return array_map(fn (array $r): self => self::dari($this->db, $r), $this->db->ambilSemua($sql . ' ORDER BY nama, idPasien', $par));
    }

    /** SEQ-09 pesan 5: keyword = nama atau No. RM. @return list<self> */
    public function getDataPasien(string $keyword): array
    {
        $k = strtolower(trim($keyword));
        if ($k === '') { return []; }
        return array_map(fn (array $r): self => self::dari($this->db, $r),
            $this->db->ambilSemua('SELECT * FROM DataPasien WHERE LOWER(nama) LIKE ? OR LOWER(noRM) = ? ORDER BY nama, idPasien', ['%' . $k . '%', $k]));
    }

    public function toArray(): array
    {
        return ['idPasien' => $this->idPasien, 'noRM' => $this->noRM, 'nama' => $this->nama, 'tanggalLahir' => $this->tanggalLahir,
                'jenisKelamin' => $this->jenisKelamin, 'alamat' => $this->alamat, 'noTelepon' => $this->noTelepon];
    }
}
