<?php
/**
 * Model Kelas Perkuliahan (kelas)
 */

require_once __DIR__ . '/../config/database.php';

class KelasModel
{
    public const HARI_VALID = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /**
     * Mengambil semua daftar kelas (opsional difilter per tahun ajaran).
     *
     * @param int|null $idTa
     * @return array
     */
    public static function all(?int $idTa = null): array
    {
        $pdo = db();
        $where = '';
        $params = [];
        if ($idTa !== null && $idTa > 0) {
            $where = 'WHERE kl.id_ta = :id_ta';
            $params['id_ta'] = $idTa;
        }

        $sql = "
            SELECT kl.*,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket, mk.id_prodi,
                   p.kode_prodi, p.nama_prodi,
                   d.nidn, d.nama AS nama_dosen,
                   ta.tahun, ta.semester, ta.aktif AS ta_aktif,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status <> 'ditolak') AS terisi
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            {$where}
            ORDER BY ta.aktif DESC, ta.tahun DESC, mk.kode_mk ASC, kl.nama_kelas ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Paginasi dan pencarian kelas.
     *
     * @param int $page
     * @param string $q
     * @param int|null $idTa
     * @param int|null $idProdi
     * @param int $perPage
     * @return array
     */
    public static function paginate(
        int $page = 1,
        string $q = '',
        ?int $idTa = null,
        ?int $idProdi = null,
        int $perPage = PER_PAGE
    ): array {
        $pdo = db();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $q = trim($q);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(mk.kode_mk LIKE :q1 OR mk.nama_mk LIKE :q2 OR d.nama LIKE :q3 OR kl.ruang LIKE :q4 OR kl.hari LIKE :q5)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
            $params['q4'] = "%{$q}%";
            $params['q5'] = "%{$q}%";
        }

        if ($idTa !== null && $idTa > 0) {
            $where[] = "kl.id_ta = :id_ta";
            $params['id_ta'] = $idTa;
        }

        if ($idProdi !== null && $idProdi > 0) {
            $where[] = "mk.id_prodi = :id_prodi";
            $params['id_prodi'] = $idProdi;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            {$whereSql}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT kl.*,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket, mk.id_prodi,
                   p.kode_prodi, p.nama_prodi,
                   d.nidn, d.nama AS nama_dosen,
                   ta.tahun, ta.semester, ta.aktif AS ta_aktif,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status <> 'ditolak') AS terisi
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            {$whereSql}
            ORDER BY ta.aktif DESC, ta.tahun DESC, mk.kode_mk ASC, kl.nama_kelas ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'  => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
        ];
    }

    /**
     * Mengambil satu baris kelas beserta detail lengkapnya.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT kl.*,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket, mk.id_prodi,
                   p.kode_prodi, p.nama_prodi,
                   d.nidn, d.nama AS nama_dosen,
                   ta.tahun, ta.semester, ta.aktif AS ta_aktif,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status <> 'ditolak') AS terisi
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            WHERE kl.id_kelas = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Menghitung jumlah peserta aktif (status <> 'ditolak') pada suatu kelas.
     *
     * @param int $idKelas
     * @return int
     */
    public static function jumlahPeserta(int $idKelas): int
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM krs WHERE id_kelas = :id AND status <> 'ditolak'");
        $stmt->execute(['id' => $idKelas]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil daftar kelas yang tersedia pada tahun ajaran tertentu beserta status keterambilan oleh mahasiswa.
     *
     * @param int $idMhs
     * @param int $idTa
     * @return array
     */
    public static function tersedia(int $idMhs, int $idTa): array
    {
        $pdo = db();
        $sql = "
            SELECT kl.*,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket, mk.id_prodi,
                   p.kode_prodi, p.nama_prodi,
                   d.nidn, d.nama AS nama_dosen,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status <> 'ditolak') AS terisi,
                   (SELECT k2.id_krs FROM krs k2 WHERE k2.id_mahasiswa = :mhs1 AND k2.id_kelas = kl.id_kelas AND k2.status <> 'ditolak' LIMIT 1) AS id_krs_diambil,
                   (SELECT k3.status FROM krs k3 WHERE k3.id_mahasiswa = :mhs2 AND k3.id_kelas = kl.id_kelas AND k3.status <> 'ditolak' LIMIT 1) AS status_krs,
                   (SELECT COUNT(*) FROM krs k4 JOIN kelas kl4 ON kl4.id_kelas = k4.id_kelas
                    WHERE k4.id_mahasiswa = :mhs3 AND kl4.id_ta = kl.id_ta AND kl4.id_mk = kl.id_mk AND k4.status <> 'ditolak') AS mk_sudah_diambil
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            WHERE kl.id_ta = :id_ta
            ORDER BY p.kode_prodi ASC, mk.semester_paket ASC, mk.kode_mk ASC, kl.nama_kelas ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mhs1'  => $idMhs,
            'mhs2'  => $idMhs,
            'mhs3'  => $idMhs,
            'id_ta' => $idTa,
        ]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['terisi']     = (int)$r['terisi'];
            $r['kuota']      = (int)$r['kuota'];
            $r['sisa_kuota'] = max(0, $r['kuota'] - $r['terisi']);
        }
        unset($r);

        return $rows;
    }

    /**
     * Membuat kelas baru.
     *
     * @param array $d
     * @return int ID kelas baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $idMk       = (int)($d['id_mk'] ?? 0);
        $idDosen    = (int)($d['id_dosen'] ?? 0);
        $idTa       = (int)($d['id_ta'] ?? 0);
        $namaKelas  = strtoupper(trim($d['nama_kelas'] ?? ''));
        $hari       = trim($d['hari'] ?? '');
        $jamMulai   = trim($d['jam_mulai'] ?? '');
        $jamSelesai = trim($d['jam_selesai'] ?? '');
        $ruang      = trim($d['ruang'] ?? '');
        $kuota      = (int)($d['kuota'] ?? 40);

        if ($idMk <= 0 || $idDosen <= 0 || $idTa <= 0 || $namaKelas === '' || $ruang === '') {
            throw new Exception('Mata Kuliah, Dosen, Tahun Ajaran, Nama Kelas, dan Ruang wajib diisi.');
        }

        if (!in_array($hari, self::HARI_VALID, true)) {
            throw new Exception('Hari perkuliahan tidak valid.');
        }

        if ($jamMulai === '' || $jamSelesai === '' || strtotime($jamMulai) >= strtotime($jamSelesai)) {
            throw new Exception('Jam mulai harus lebih awal daripada jam selesai.');
        }

        if ($kuota < 1 || $kuota > 500) {
            throw new Exception('Kuota kelas harus bernilai antara 1 sampai 500.');
        }

        // Cek duplikat (id_mk, id_ta, nama_kelas)
        $cek = $pdo->prepare("
            SELECT 1 FROM kelas
            WHERE id_mk = :id_mk AND id_ta = :id_ta AND nama_kelas = :nama_kelas
            LIMIT 1
        ");
        $cek->execute([
            'id_mk'      => $idMk,
            'id_ta'      => $idTa,
            'nama_kelas' => $namaKelas,
        ]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kelas '{$namaKelas}' untuk mata kuliah dan tahun ajaran tersebut sudah terdaftar.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO kelas (id_mk, id_dosen, id_ta, nama_kelas, hari, jam_mulai, jam_selesai, ruang, kuota)
            VALUES (:id_mk, :id_dosen, :id_ta, :nama_kelas, :hari, :jam_mulai, :jam_selesai, :ruang, :kuota)
        ");
        $stmt->execute([
            'id_mk'       => $idMk,
            'id_dosen'    => $idDosen,
            'id_ta'       => $idTa,
            'nama_kelas'  => $namaKelas,
            'hari'        => $hari,
            'jam_mulai'   => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'ruang'       => $ruang,
            'kuota'       => $kuota,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Memperbarui data kelas.
     *
     * @param int $id
     * @param array $d
     * @return bool
     */
    public static function update(int $id, array $d): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data kelas tidak ditemukan.');
        }

        $idMk       = (int)($d['id_mk'] ?? 0);
        $idDosen    = (int)($d['id_dosen'] ?? 0);
        $idTa       = (int)($d['id_ta'] ?? 0);
        $namaKelas  = strtoupper(trim($d['nama_kelas'] ?? ''));
        $hari       = trim($d['hari'] ?? '');
        $jamMulai   = trim($d['jam_mulai'] ?? '');
        $jamSelesai = trim($d['jam_selesai'] ?? '');
        $ruang      = trim($d['ruang'] ?? '');
        $kuota      = (int)($d['kuota'] ?? 40);

        if ($idMk <= 0 || $idDosen <= 0 || $idTa <= 0 || $namaKelas === '' || $ruang === '') {
            throw new Exception('Mata Kuliah, Dosen, Tahun Ajaran, Nama Kelas, dan Ruang wajib diisi.');
        }

        if (!in_array($hari, self::HARI_VALID, true)) {
            throw new Exception('Hari perkuliahan tidak valid.');
        }

        if ($jamMulai === '' || $jamSelesai === '' || strtotime($jamMulai) >= strtotime($jamSelesai)) {
            throw new Exception('Jam mulai harus lebih awal daripada jam selesai.');
        }

        if ($kuota < 1 || $kuota > 500) {
            throw new Exception('Kuota kelas harus bernilai antara 1 sampai 500.');
        }

        $cek = $pdo->prepare("
            SELECT 1 FROM kelas
            WHERE id_mk = :id_mk AND id_ta = :id_ta AND nama_kelas = :nama_kelas AND id_kelas <> :id
            LIMIT 1
        ");
        $cek->execute([
            'id_mk'      => $idMk,
            'id_ta'      => $idTa,
            'nama_kelas' => $namaKelas,
            'id'         => $id,
        ]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kelas '{$namaKelas}' untuk mata kuliah dan tahun ajaran tersebut sudah ada.");
        }

        $stmt = $pdo->prepare("
            UPDATE kelas
            SET id_mk = :id_mk, id_dosen = :id_dosen, id_ta = :id_ta, nama_kelas = :nama_kelas,
                hari = :hari, jam_mulai = :jam_mulai, jam_selesai = :jam_selesai, ruang = :ruang, kuota = :kuota
            WHERE id_kelas = :id
        ");
        return $stmt->execute([
            'id_mk'       => $idMk,
            'id_dosen'    => $idDosen,
            'id_ta'       => $idTa,
            'nama_kelas'  => $namaKelas,
            'hari'        => $hari,
            'jam_mulai'   => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'ruang'       => $ruang,
            'kuota'       => $kuota,
            'id'          => $id,
        ]);
    }

    /**
     * Menghapus kelas jika belum ada mahasiswa yang mengambilnya di KRS.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data kelas tidak ditemukan.');
        }

        $cekKrs = $pdo->prepare("SELECT COUNT(*) FROM krs WHERE id_kelas = :id");
        $cekKrs->execute(['id' => $id]);
        if ((int)$cekKrs->fetchColumn() > 0) {
            throw new Exception('Kelas tidak dapat dihapus karena sudah diambil oleh mahasiswa pada KRS.');
        }

        $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = :id");
        return $stmt->execute(['id' => $id]);
    }
}
