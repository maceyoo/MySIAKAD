<?php
/**
 * Model Program Studi (prodi)
 */

require_once __DIR__ . '/../config/database.php';

class ProdiModel
{
    /**
     * Mengambil semua data program studi.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.id_prodi = p.id_prodi) AS jumlah_mahasiswa,
                   (SELECT COUNT(*) FROM dosen d WHERE d.id_prodi = p.id_prodi) AS jumlah_dosen,
                   (SELECT COUNT(*) FROM mata_kuliah mk WHERE mk.id_prodi = p.id_prodi) AS jumlah_mk
            FROM prodi p
            ORDER BY p.kode_prodi ASC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Paginasi dan pencarian program studi.
     *
     * @param int $page
     * @param string $q
     * @param int $perPage
     * @return array
     */
    public static function paginate(int $page = 1, string $q = '', int $perPage = PER_PAGE): array
    {
        $pdo = db();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $q = trim($q);

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE p.kode_prodi LIKE :q1 OR p.nama_prodi LIKE :q2 OR p.jenjang LIKE :q3";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
        }

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM prodi p {$where}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.id_prodi = p.id_prodi) AS jumlah_mahasiswa,
                   (SELECT COUNT(*) FROM dosen d WHERE d.id_prodi = p.id_prodi) AS jumlah_dosen,
                   (SELECT COUNT(*) FROM mata_kuliah mk WHERE mk.id_prodi = p.id_prodi) AS jumlah_mk
            FROM prodi p
            {$where}
            ORDER BY p.kode_prodi ASC
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
     * Mengambil satu baris prodi berdasarkan ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM prodi WHERE id_prodi = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Menambahkan program studi baru.
     *
     * @param array $d ['kode_prodi', 'nama_prodi', 'jenjang']
     * @return int ID prodi baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $kode    = strtoupper(trim($d['kode_prodi'] ?? ''));
        $nama    = trim($d['nama_prodi'] ?? '');
        $jenjang = strtoupper(trim($d['jenjang'] ?? 'S1'));

        if ($kode === '' || $nama === '') {
            throw new Exception('Kode prodi dan nama program studi wajib diisi.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM prodi WHERE kode_prodi = :kode LIMIT 1");
        $cek->execute(['kode' => $kode]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kode program studi '{$kode}' sudah terdaftar.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO prodi (kode_prodi, nama_prodi, jenjang)
            VALUES (:kode, :nama, :jenjang)
        ");
        $stmt->execute([
            'kode'    => $kode,
            'nama'    => $nama,
            'jenjang' => $jenjang !== '' ? $jenjang : 'S1',
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Memperbarui data program studi.
     *
     * @param int $id
     * @param array $d
     * @return bool
     */
    public static function update(int $id, array $d): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data program studi tidak ditemukan.');
        }

        $kode    = strtoupper(trim($d['kode_prodi'] ?? ''));
        $nama    = trim($d['nama_prodi'] ?? '');
        $jenjang = strtoupper(trim($d['jenjang'] ?? 'S1'));

        if ($kode === '' || $nama === '') {
            throw new Exception('Kode prodi dan nama program studi wajib diisi.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM prodi WHERE kode_prodi = :kode AND id_prodi <> :id LIMIT 1");
        $cek->execute(['kode' => $kode, 'id' => $id]);
        if ($cek->fetchColumn()) {
            throw new Exception("Kode program studi '{$kode}' sudah digunakan oleh prodi lain.");
        }

        $stmt = $pdo->prepare("
            UPDATE prodi
            SET kode_prodi = :kode, nama_prodi = :nama, jenjang = :jenjang
            WHERE id_prodi = :id
        ");
        return $stmt->execute([
            'kode'    => $kode,
            'nama'    => $nama,
            'jenjang' => $jenjang !== '' ? $jenjang : 'S1',
            'id'      => $id,
        ]);
    }

    /**
     * Menghapus program studi jika belum direferensikan oleh mahasiswa, dosen, atau mata kuliah.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data program studi tidak ditemukan.');
        }

        $cekMhs = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa WHERE id_prodi = :id");
        $cekMhs->execute(['id' => $id]);
        if ((int)$cekMhs->fetchColumn() > 0) {
            throw new Exception('Program studi tidak dapat dihapus karena masih memiliki data mahasiswa.');
        }

        $cekDosen = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE id_prodi = :id");
        $cekDosen->execute(['id' => $id]);
        if ((int)$cekDosen->fetchColumn() > 0) {
            throw new Exception('Program studi tidak dapat dihapus karena masih memiliki data dosen.');
        }

        $cekMk = $pdo->prepare("SELECT COUNT(*) FROM mata_kuliah WHERE id_prodi = :id");
        $cekMk->execute(['id' => $id]);
        if ((int)$cekMk->fetchColumn() > 0) {
            throw new Exception('Program studi tidak dapat dihapus karena masih memiliki data mata kuliah.');
        }

        $stmt = $pdo->prepare("DELETE FROM prodi WHERE id_prodi = :id");
        return $stmt->execute(['id' => $id]);
    }
}
