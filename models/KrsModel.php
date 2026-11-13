<?php
/**
 * Model Kartu Rencana Studi (KRS)
 * Menangani pengambilan kelas oleh mahasiswa dengan 5 validasi aturan bisnis dalam transaksi.
 */

require_once __DIR__ . '/../config/database.php';

class KrsModel
{
    /**
     * Mengambil kelas ke dalam KRS mahasiswa dengan validasi:
     * (a) Kelas milik tahun ajaran aktif
     * (b) Kuota kelas belum penuh
     * (c) Mahasiswa belum mengambil kelas yang sama atau mata kuliah yang sama di semester tersebut
     * (d) Total SKS saat ini + SKS kelas baru <= MAX_SKS (24)
     * (e) Tidak bentrok jadwal dengan kelas lain di semester yang sama
     *
     * @param int $idMhs
     * @param int $idKelas
     * @return int ID KRS baru
     */
    public static function ambil(int $idMhs, int $idKelas): int
    {
        $pdo = db();
        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            // Kunci baris kelas (FOR UPDATE) agar perhitungan kuota akurat
            $stmtKelas = $pdo->prepare("
                SELECT kl.*, mk.kode_mk, mk.nama_mk, mk.sks, ta.aktif AS ta_aktif, ta.tahun, ta.semester
                FROM kelas kl
                JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
                JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
                WHERE kl.id_kelas = :id_kelas
                FOR UPDATE
            ");
            $stmtKelas->execute(['id_kelas' => $idKelas]);
            $kelas = $stmtKelas->fetch();

            if (!$kelas) {
                throw new Exception('Kelas yang dipilih tidak ditemukan.');
            }

            // (a) Validasi kelas milik tahun ajaran aktif
            if ((int)$kelas['ta_aktif'] !== 1) {
                throw new Exception('Pengisian KRS gagal: Kelas ini tidak berada pada tahun ajaran yang sedang aktif.');
            }

            $idTa = (int)$kelas['id_ta'];
            $idMk = (int)$kelas['id_mk'];
            $sksBaru = (int)$kelas['sks'];

            // (c.1) Cek apakah mahasiswa sudah mengambil kelas yang sama (status <> 'ditolak')
            $stmtCekKelas = $pdo->prepare("
                SELECT status FROM krs
                WHERE id_mahasiswa = :mhs AND id_kelas = :kelas
                LIMIT 1
            ");
            $stmtCekKelas->execute(['mhs' => $idMhs, 'kelas' => $idKelas]);
            $existingRow = $stmtCekKelas->fetch();
            if ($existingRow && $existingRow['status'] !== 'ditolak') {
                throw new Exception('Anda sudah mengambil kelas ini pada KRS semester aktif.');
            }

            // (c.2) Cek apakah mahasiswa sudah mengambil mata kuliah yang sama (di kelas berbeda) pada semester ini
            $stmtCekMk = $pdo->prepare("
                SELECT kl.nama_kelas
                FROM krs k
                JOIN kelas kl ON kl.id_kelas = k.id_kelas
                WHERE k.id_mahasiswa = :mhs
                  AND kl.id_ta = :id_ta
                  AND kl.id_mk = :id_mk
                  AND k.status <> 'ditolak'
                LIMIT 1
            ");
            $stmtCekMk->execute([
                'mhs'   => $idMhs,
                'id_ta' => $idTa,
                'id_mk' => $idMk,
            ]);
            if ($stmtCekMk->fetchColumn()) {
                throw new Exception("Mata kuliah '{$kelas['nama_mk']}' ({$kelas['kode_mk']}) sudah diambil di kelas lain pada semester ini.");
            }

            // (b) Validasi kuota belum penuh
            $stmtKuota = $pdo->prepare("
                SELECT COUNT(*) FROM krs
                WHERE id_kelas = :id_kelas AND status <> 'ditolak'
            ");
            $stmtKuota->execute(['id_kelas' => $idKelas]);
            $terisi = (int)$stmtKuota->fetchColumn();

            if ($terisi >= (int)$kelas['kuota']) {
                throw new Exception("Kuota kelas {$kelas['kode_mk']} ({$kelas['nama_kelas']}) sudah penuh ({$terisi}/{$kelas['kuota']}).");
            }

            // (d) Validasi total SKS + SKS baru <= MAX_SKS
            $totalSksSaatIni = self::totalSks($idMhs, $idTa);
            if (($totalSksSaatIni + $sksBaru) > MAX_SKS) {
                throw new Exception(sprintf(
                    'Melebihi batas maksimum SKS: Total SKS Anda saat ini %d SKS, ditambah %d SKS menjadi %d SKS (maksimal %d SKS).',
                    $totalSksSaatIni,
                    $sksBaru,
                    $totalSksSaatIni + $sksBaru,
                    MAX_SKS
                ));
            }

            // (e) Validasi tidak bentrok jadwal (menggunakan kueri sesuai docs/03)
            $stmtBentrok = $pdo->prepare("
                SELECT mk.kode_mk, mk.nama_mk, a.nama_kelas, a.hari, a.jam_mulai, a.jam_selesai
                FROM krs k
                JOIN kelas a ON a.id_kelas = k.id_kelas
                JOIN mata_kuliah mk ON mk.id_mk = a.id_mk
                JOIN kelas b ON b.id_kelas = :kelas
                WHERE k.id_mahasiswa = :mhs AND k.status <> 'ditolak'
                  AND a.id_ta = b.id_ta AND a.hari = b.hari
                  AND a.jam_mulai < b.jam_selesai AND b.jam_mulai < a.jam_selesai
                LIMIT 1
            ");
            $stmtBentrok->execute([
                'mhs'   => $idMhs,
                'kelas' => $idKelas,
            ]);
            $bentrok = $stmtBentrok->fetch();
            if ($bentrok) {
                $jamA = substr($bentrok['jam_mulai'], 0, 5) . '-' . substr($bentrok['jam_selesai'], 0, 5);
                throw new Exception(
                    "Jadwal bentrok dengan mata kuliah {$bentrok['nama_mk']} (Kelas {$bentrok['nama_kelas']}) pada hari {$bentrok['hari']} pukul {$jamA}."
                );
            }

            // Simpan atau perbarui jika sebelumnya berstatus ditolak
            if ($existingRow && $existingRow['status'] === 'ditolak') {
                $stmtUpd = $pdo->prepare("
                    UPDATE krs SET status = 'diajukan', created_at = CURRENT_TIMESTAMP
                    WHERE id_mahasiswa = :mhs AND id_kelas = :kelas
                ");
                $stmtUpd->execute(['mhs' => $idMhs, 'kelas' => $idKelas]);
                $stmtId = $pdo->prepare("SELECT id_krs FROM krs WHERE id_mahasiswa = :mhs AND id_kelas = :kelas LIMIT 1");
                $stmtId->execute(['mhs' => $idMhs, 'kelas' => $idKelas]);
                $idKrs = (int)$stmtId->fetchColumn();
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO krs (id_mahasiswa, id_kelas, status)
                    VALUES (:mhs, :kelas, 'diajukan')
                ");
                $stmtIns->execute(['mhs' => $idMhs, 'kelas' => $idKelas]);
                $idKrs = (int)$pdo->lastInsertId();
            }

            if ($ownTx) {
                $pdo->commit();
            }
            return $idKrs;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Membatalkan pengambilan kelas pada KRS (hanya diizinkan jika status masih 'diajukan' dan tahun ajaran aktif).
     *
     * @param int $idKrs
     * @param int|null $idMhs Opsional untuk memverifikasi kepemilikan KRS oleh mahasiswa
     * @return bool
     */
    public static function batal(int $idKrs, ?int $idMhs = null): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT k.*, ta.aktif AS ta_aktif
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            WHERE k.id_krs = :id_krs
            LIMIT 1
        ");
        $stmt->execute(['id_krs' => $idKrs]);
        $krs = $stmt->fetch();

        if (!$krs) {
            throw new Exception('Data KRS tidak ditemukan.');
        }

        if ($idMhs !== null && (int)$krs['id_mahasiswa'] !== $idMhs) {
            throw new Exception('Akses ditolak: Anda tidak berhak membatalkan KRS mahasiswa lain.');
        }

        if ($krs['status'] !== 'diajukan') {
            throw new Exception("KRS tidak dapat dibatalkan karena statusnya sudah '{$krs['status']}'.");
        }

        if ((int)$krs['ta_aktif'] !== 1) {
            throw new Exception('KRS tidak dapat dibatalkan karena tahun ajaran sudah tidak aktif.');
        }

        $stmtDel = $pdo->prepare("DELETE FROM krs WHERE id_krs = :id_krs");
        return $stmtDel->execute(['id_krs' => $idKrs]);
    }

    /**
     * Mengambil daftar KRS milik seorang mahasiswa pada tahun ajaran tertentu.
     *
     * @param int $idMhs
     * @param int $idTa
     * @return array
     */
    public static function daftar(int $idMhs, int $idTa): array
    {
        $pdo = db();
        $sql = "
            SELECT k.*,
                   kl.nama_kelas, kl.hari, kl.jam_mulai, kl.jam_selesai, kl.ruang, kl.kuota, kl.id_ta,
                   mk.id_mk, mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket,
                   d.nidn, d.nama AS nama_dosen,
                   n.tugas, n.uts, n.uas, n.nilai_akhir, n.huruf, n.bobot
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            LEFT JOIN nilai n ON n.id_krs = k.id_krs
            WHERE k.id_mahasiswa = :mhs AND kl.id_ta = :ta
            ORDER BY FIELD(kl.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), kl.jam_mulai ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mhs' => $idMhs,
            'ta'  => $idTa,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Menghitung total SKS aktif (status <> 'ditolak') mahasiswa pada satu tahun ajaran.
     *
     * @param int $idMhs
     * @param int $idTa
     * @return int
     */
    public static function totalSks(int $idMhs, int $idTa): int
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(mk.sks), 0)
            FROM krs k
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            WHERE k.id_mahasiswa = :mhs
              AND kl.id_ta = :ta
              AND k.status <> 'ditolak'
        ");
        $stmt->execute([
            'mhs' => $idMhs,
            'ta'  => $idTa,
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengubah status KRS oleh Admin ('diajukan', 'disetujui', 'ditolak').
     *
     * @param int $id
     * @param string $st
     * @return bool
     */
    public static function setStatus(int $id, string $st): bool
    {
        $pdo = db();
        if (!in_array($st, ['diajukan', 'disetujui', 'ditolak'], true)) {
            throw new Exception('Status KRS tidak valid.');
        }

        $cek = $pdo->prepare("SELECT id_krs FROM krs WHERE id_krs = :id LIMIT 1");
        $cek->execute(['id' => $id]);
        if (!$cek->fetchColumn()) {
            throw new Exception('Data KRS tidak ditemukan.');
        }

        $stmt = $pdo->prepare("UPDATE krs SET status = :st WHERE id_krs = :id");
        return $stmt->execute([
            'st' => $st,
            'id' => $id,
        ]);
    }

    /**
     * Mengubah status banyak KRS sekaligus (persetujuan/penolakan massal oleh Admin).
     *
     * @param array $ids
     * @param string $st
     * @return int Jumlah baris yang diperbarui
     */
    public static function setStatusMassal(array $ids, string $st): int
    {
        if (empty($ids)) {
            return 0;
        }
        if (!in_array($st, ['diajukan', 'disetujui', 'ditolak'], true)) {
            throw new Exception('Status KRS tidak valid.');
        }

        $pdo = db();
        $cleanIds = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "UPDATE krs SET status = ? WHERE id_krs IN ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$st], $cleanIds));
        return $stmt->rowCount();
    }

    /**
     * Mengambil daftar seluruh pengajuan KRS untuk halaman verifikasi Admin.
     *
     * @param int|null $idTa
     * @param int|null $idProdi
     * @param string $status
     * @param string $q
     * @return array
     */
    public static function daftarAdmin(?int $idTa = null, ?int $idProdi = null, string $status = '', string $q = ''): array
    {
        $pdo = db();
        $where = [];
        $params = [];

        if ($idTa !== null && $idTa > 0) {
            $where[] = "kl.id_ta = :id_ta";
            $params['id_ta'] = $idTa;
        }

        if ($idProdi !== null && $idProdi > 0) {
            $where[] = "m.id_prodi = :id_prodi";
            $params['id_prodi'] = $idProdi;
        }

        if ($status !== '' && in_array($status, ['diajukan', 'disetujui', 'ditolak'], true)) {
            $where[] = "k.status = :status";
            $params['status'] = $status;
        }

        $q = trim($q);
        if ($q !== '') {
            $where[] = "(m.nim LIKE :q1 OR m.nama LIKE :q2 OR mk.kode_mk LIKE :q3 OR mk.nama_mk LIKE :q4)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
            $params['q4'] = "%{$q}%";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT k.*,
                   m.nim, m.nama AS nama_mahasiswa, m.angkatan,
                   p.kode_prodi, p.nama_prodi,
                   kl.nama_kelas, kl.hari, kl.jam_mulai, kl.jam_selesai, kl.ruang,
                   mk.kode_mk, mk.nama_mk, mk.sks,
                   d.nama AS nama_dosen,
                   ta.tahun, ta.semester
            FROM krs k
            JOIN mahasiswa m ON m.id_mahasiswa = k.id_mahasiswa
            JOIN prodi p ON p.id_prodi = m.id_prodi
            JOIN kelas kl ON kl.id_kelas = k.id_kelas
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            {$whereSql}
            ORDER BY FIELD(k.status, 'diajukan', 'disetujui', 'ditolak'), k.created_at DESC, m.nim ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
