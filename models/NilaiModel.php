<?php
/**
 * Model Nilai, KHS (IPS), dan Transkrip (IPK)
 */

require_once __DIR__ . '/../config/database.php';

class NilaiModel
{
    /**
     * Menghitung nilai akhir berdasarkan bobot konstanta (Tugas 30% + UTS 30% + UAS 40%)
     * dengan pembulatan 2 angka desimal.
     *
     * @param float $tugas
     * @param float $uts
     * @param float $uas
     * @return float
     */
    public static function hitung(float $tugas, float $uts, float $uas): float
    {
        $na = ($tugas * BOBOT_TUGAS) + ($uts * BOBOT_UTS) + ($uas * BOBOT_UAS);
        return round($na, 2);
    }

    /**
     * Mengonversi nilai akhir angka (0-100) menjadi pasangan [huruf, bobot] sesuai aturan bisnis di docs/01.
     * Mengembalikan array yang mendukung destructuring [$huruf, $bobot] maupun akses key ['huruf', 'bobot'].
     *
     * @param float $nilaiAkhir
     * @return array
     */
    public static function konversi(float $nilaiAkhir): array
    {
        if ($nilaiAkhir >= 80) {
            $huruf = 'A';
            $bobot = 4.00;
        } elseif ($nilaiAkhir >= 75) {
            $huruf = 'B+';
            $bobot = 3.50;
        } elseif ($nilaiAkhir >= 70) {
            $huruf = 'B';
            $bobot = 3.00;
        } elseif ($nilaiAkhir >= 65) {
            $huruf = 'C+';
            $bobot = 2.50;
        } elseif ($nilaiAkhir >= 60) {
            $huruf = 'C';
            $bobot = 2.00;
        } elseif ($nilaiAkhir >= 50) {
            $huruf = 'D';
            $bobot = 1.00;
        } else {
            $huruf = 'E';
            $bobot = 0.00;
        }

        return [
            0       => $huruf,
            1       => $bobot,
            'huruf' => $huruf,
            'bobot' => $bobot,
        ];
    }

    /**
     * Mengambil daftar peserta kelas yang KRS-nya berstatus 'disetujui' beserta nilai mereka.
     *
     * @param int $idKelas
     * @return array
     */
    public static function pesertaKelas(int $idKelas): array
    {
        $pdo = db();
        $sql = "
            SELECT k.id_krs, k.id_mahasiswa, k.id_kelas, k.status AS status_krs,
                   m.nim, m.nama AS nama_mahasiswa, m.angkatan,
                   p.kode_prodi, p.nama_prodi,
                   n.id_nilai, n.tugas, n.uts, n.uas, n.nilai_akhir, n.huruf, n.bobot, n.updated_at
            FROM krs k
            JOIN mahasiswa m ON m.id_mahasiswa = k.id_mahasiswa
            JOIN prodi p ON p.id_prodi = m.id_prodi
            LEFT JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_kelas = :id_kelas
              AND k.status = 'disetujui'
            ORDER BY m.nim ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_kelas' => $idKelas]);
        return $stmt->fetchAll();
    }

    /**
     * Menyimpan atau memperbarui nilai mahasiswa untuk satu KRS.
     * Memvalidasi rentang 0-100, status KRS harus 'disetujui', dan kepemilikan kelas oleh dosen pengampu.
     *
     * @param int $idKrs
     * @param float $tugas
     * @param float $uts
     * @param float $uas
     * @param int|null $idDosen Jika diisi, wajib cocok dengan id_dosen pengampu kelas
     * @return bool
     */
    public static function simpan(int $idKrs, float $tugas, float $uts, float $uas, ?int $idDosen = null): bool
    {
        if ($tugas < 0 || $tugas > 100 || $uts < 0 || $uts > 100 || $uas < 0 || $uas > 100) {
            throw new Exception('Nilai Tugas, UTS, dan UAS harus berada dalam rentang 0 sampai 100.');
        }

        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT k.id_krs, k.status, kl.id_dosen
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            WHERE k.id_krs = :id_krs
            LIMIT 1
        ");
        $stmt->execute(['id_krs' => $idKrs]);
        $krs = $stmt->fetch();

        if (!$krs) {
            throw new Exception('Data KRS tidak ditemukan.');
        }

        if ($krs['status'] !== 'disetujui') {
            throw new Exception('Nilai hanya dapat diinput untuk KRS yang sudah berstatus disetujui.');
        }

        if ($idDosen !== null && (int)$krs['id_dosen'] !== $idDosen) {
            throw new Exception('Akses ditolak: Anda bukan dosen pengampu untuk kelas ini.');
        }

        $nilaiAkhir = self::hitung($tugas, $uts, $uas);
        [$huruf, $bobot] = self::konversi($nilaiAkhir);

        $sql = "
            INSERT INTO nilai (id_krs, tugas, uts, uas, nilai_akhir, huruf, bobot)
            VALUES (:id_krs, :tugas, :uts, :uas, :nilai_akhir, :huruf, :bobot)
            ON DUPLICATE KEY UPDATE
                tugas       = VALUES(tugas),
                uts         = VALUES(uts),
                uas         = VALUES(uas),
                nilai_akhir = VALUES(nilai_akhir),
                huruf       = VALUES(huruf),
                bobot       = VALUES(bobot),
                updated_at  = CURRENT_TIMESTAMP
        ";
        $stmtSave = $pdo->prepare($sql);
        return $stmtSave->execute([
            'id_krs'      => $idKrs,
            'tugas'       => round($tugas, 2),
            'uts'         => round($uts, 2),
            'uas'         => round($uas, 2),
            'nilai_akhir' => $nilaiAkhir,
            'huruf'       => $huruf,
            'bobot'       => $bobot,
        ]);
    }

    /**
     * Mengambil data Kartu Hasil Studi (KHS) mahasiswa pada satu tahun ajaran.
     *
     * @param int $idMhs
     * @param int $idTa
     * @return array
     */
    public static function khs(int $idMhs, int $idTa): array
    {
        $pdo = db();
        $sql = "
            SELECT k.id_krs,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket,
                   kl.nama_kelas,
                   d.nama AS nama_dosen,
                   n.tugas, n.uts, n.uas, n.nilai_akhir, n.huruf, n.bobot,
                   ROUND(COALESCE(n.bobot, 0) * mk.sks, 2) AS mutu
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            LEFT JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_mahasiswa = :mhs
              AND kl.id_ta = :ta
              AND k.status = 'disetujui'
            ORDER BY mk.kode_mk ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mhs' => $idMhs,
            'ta'  => $idTa,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil seluruh riwayat akademik yang telah dinilai untuk Transkrip Nilai mahasiswa.
     *
     * @param int $idMhs
     * @return array
     */
    public static function transkrip(int $idMhs): array
    {
        $pdo = db();
        $sql = "
            SELECT k.id_krs,
                   ta.id_ta, ta.tahun, ta.semester,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket,
                   kl.nama_kelas,
                   n.tugas, n.uts, n.uas, n.nilai_akhir, n.huruf, n.bobot,
                   ROUND(n.bobot * mk.sks, 2) AS mutu
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_mahasiswa = :mhs
              AND k.status = 'disetujui'
            ORDER BY ta.tahun ASC, FIELD(ta.semester, 'ganjil', 'genap'), mk.semester_paket ASC, mk.kode_mk ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mhs' => $idMhs]);
        return $stmt->fetchAll();
    }

    /**
     * Menghitung Indeks Prestasi Semester (IPS) sesuai kueri pada docs/03.
     *
     * @param int $idMhs
     * @param int $idTa
     * @return float
     */
    public static function ips(int $idMhs, int $idTa): float
    {
        $pdo = db();
        $sql = "
            SELECT ROUND(SUM(n.bobot * mk.sks) / SUM(mk.sks), 2) AS ips
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_mahasiswa = :mhs
              AND kl.id_ta = :ta
              AND k.status = 'disetujui'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mhs' => $idMhs,
            'ta'  => $idTa,
        ]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? (float)$val : 0.00;
    }

    /**
     * Menghitung Indeks Prestasi Kumulatif (IPK) seluruh semester sesuai kueri pada docs/03.
     *
     * @param int $idMhs
     * @return float
     */
    public static function ipk(int $idMhs): float
    {
        $pdo = db();
        $sql = "
            SELECT ROUND(SUM(n.bobot * mk.sks) / SUM(mk.sks), 2) AS ipk
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_mahasiswa = :mhs
              AND k.status = 'disetujui'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mhs' => $idMhs]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? (float)$val : 0.00;
    }
}
