<?php
/**
 * Model Mata Kuliah (mata_kuliah)
 */

require_once __DIR__ . '/../config/database.php';

class MataKuliahModel
{
    /**
     * Mengambil semua daftar mata kuliah.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT mk.*, p.kode_prodi, p.nama_prodi
            FROM mata_kuliah mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            ORDER BY p.kode_prodi ASC, mk.semester_paket ASC, mk.kode_mk ASC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Mengambil mata kuliah berdasarkan program studi.
     *
     * @param int $idProdi
     * @return array
     */
    public static function byProdi(int $idProdi): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT mk.*, p.kode_prodi, p.nama_prodi
            FROM mata_kuliah mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            WHERE mk.id_prodi = :id_prodi
            ORDER BY mk.semester_paket ASC, mk.kode_mk ASC
        ");
        $stmt->execute(['id_prodi' => $idProdi]);
        return $stmt->fetchAll();
    }

    /**
     * Paginasi dan pencarian mata kuliah.
     *
     * @param int $page
     * @param string $q
     * @param int|null $idProdi
     * @param int $perPage
     * @return array
     */
    public static function paginate(int $page = 1, string $q = '', ?int $idProdi = null, int $perPage = PER_PAGE): array
    {
        $pdo = db();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $q = trim($q);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(mk.kode_mk LIKE :q1 OR mk.nama_mk LIKE :q2 OR p.nama_prodi LIKE :q3)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
        }

        if ($idProdi !== null && $idProdi > 0) {
            $where[] = "mk.id_prodi = :id_prodi";
            $params['id_prodi'] = $idProdi;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM mata_kuliah mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            {$whereSql}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT mk.*, p.kode_prodi, p.nama_prodi,
                   (SELECT COUNT(*) FROM kelas kl WHERE kl.id_mk = mk.id_mk) AS jumlah_kelas
            FROM mata_kuliah mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            {$whereSql}
            ORDER BY p.kode_prodi ASC, mk.semester_paket ASC, mk.kode_mk ASC
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
     * Mengambil satu mata kuliah berdasarkan ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT mk.*, p.kode_prodi, p.nama_prodi
            FROM mata_kuliah mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            WHERE mk.id_mk = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Menambahkan mata kuliah baru.
     *
     * @param array $d ['id_prodi', 'kode_mk', 'nama_mk', 'sks', 'semester_paket']
     * @return int ID mata kuliah baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $idProdi  = (int)($d['id_prodi'] ?? 0);
        $kodeMk   = strtoupper(trim($d['kode_mk'] ?? ''));
        $namaMk   = trim($d['nama_mk'] ?? '');
        $sks      = (int)($d['sks'] ?? 0);
        $semester = (int)($d['semester_paket'] ?? 1);

        if ($idProdi <= 0 || $kodeMk === '' || $namaMk === '') {
            throw new Exception('Program Studi, Kode Mata Kuliah, dan Nama Mata Kuliah wajib diisi.');
        }

        if ($sks < 1 || $sks > 6) {
            throw new Exception('Jumlah SKS mata kuliah harus bernilai antara 1 sampai 6.');
        }

        if ($semester < 1 || $semester > 8) {
            throw new Exception('Semester paket harus bernilai antara 1 sampai 8.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM mata_kuliah WHERE kode_mk = :kode LIMIT 1");
        $cek->execute(['kode' => $kodeMk]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kode mata kuliah '{$kodeMk}' sudah terdaftar.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO mata_kuliah (id_prodi, kode_mk, nama_mk, sks, semester_paket)
            VALUES (:id_prodi, :kode_mk, :nama_mk, :sks, :semester_paket)
        ");
        $stmt->execute([
            'id_prodi'       => $idProdi,
            'kode_mk'        => $kodeMk,
            'nama_mk'        => $namaMk,
            'sks'            => $sks,
            'semester_paket' => $semester,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Memperbarui data mata kuliah.
     *
     * @param int $id
     * @param array $d
     * @return bool
     */
    public static function update(int $id, array $d): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data mata kuliah tidak ditemukan.');
        }

        $idProdi  = (int)($d['id_prodi'] ?? 0);
        $kodeMk   = strtoupper(trim($d['kode_mk'] ?? ''));
        $namaMk   = trim($d['nama_mk'] ?? '');
        $sks      = (int)($d['sks'] ?? 0);
        $semester = (int)($d['semester_paket'] ?? 1);

        if ($idProdi <= 0 || $kodeMk === '' || $namaMk === '') {
            throw new Exception('Program Studi, Kode Mata Kuliah, dan Nama Mata Kuliah wajib diisi.');
        }

        if ($sks < 1 || $sks > 6) {
            throw new Exception('Jumlah SKS mata kuliah harus bernilai antara 1 sampai 6.');
        }

        if ($semester < 1 || $semester > 8) {
            throw new Exception('Semester paket harus bernilai antara 1 sampai 8.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM mata_kuliah WHERE kode_mk = :kode AND id_mk <> :id LIMIT 1");
        $cek->execute(['kode' => $kodeMk, 'id' => $id]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kode mata kuliah '{$kodeMk}' sudah digunakan oleh mata kuliah lain.");
        }

        $stmt = $pdo->prepare("
            UPDATE mata_kuliah
            SET id_prodi = :id_prodi, kode_mk = :kode_mk, nama_mk = :nama_mk,
                sks = :sks, semester_paket = :semester_paket
            WHERE id_mk = :id
        ");
        return $stmt->execute([
            'id_prodi'       => $idProdi,
            'kode_mk'        => $kodeMk,
            'nama_mk'        => $namaMk,
            'sks'            => $sks,
            'semester_paket' => $semester,
            'id'             => $id,
        ]);
    }

    /**
     * Menghapus mata kuliah jika belum direferensikan oleh kelas.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data mata kuliah tidak ditemukan.');
        }

        $cekKelas = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_mk = :id");
        $cekKelas->execute(['id' => $id]);
        if ((int)$cekKelas->fetchColumn() > 0) {
            throw new Exception('Mata kuliah tidak dapat dihapus karena sudah digunakan pada jadwal kelas perkuliahan.');
        }

        $stmt = $pdo->prepare("DELETE FROM mata_kuliah WHERE id_mk = :id");
        return $stmt->execute(['id' => $id]);
    }
}
