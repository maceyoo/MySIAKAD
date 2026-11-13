<?php
/**
 * Model Tahun Ajaran (tahun_ajaran)
 */

require_once __DIR__ . '/../config/database.php';

class TahunAjaranModel
{
    /**
     * Mengambil semua tahun ajaran diurutkan dari yang aktif dan terbaru.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT ta.*,
                   (SELECT COUNT(*) FROM kelas kl WHERE kl.id_ta = ta.id_ta) AS jumlah_kelas
            FROM tahun_ajaran ta
            ORDER BY ta.aktif DESC, ta.tahun DESC, FIELD(ta.semester, 'genap', 'ganjil')
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Paginasi dan pencarian tahun ajaran.
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
            $where = "WHERE ta.tahun LIKE :q1 OR ta.semester LIKE :q2";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
        }

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tahun_ajaran ta {$where}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT ta.*,
                   (SELECT COUNT(*) FROM kelas kl WHERE kl.id_ta = ta.id_ta) AS jumlah_kelas
            FROM tahun_ajaran ta
            {$where}
            ORDER BY ta.aktif DESC, ta.tahun DESC, FIELD(ta.semester, 'genap', 'ganjil')
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
     * Mengambil tahun ajaran berdasarkan ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM tahun_ajaran WHERE id_ta = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil satu tahun ajaran yang sedang berstatus aktif (aktif = 1).
     *
     * @return array|false
     */
    public static function aktif()
    {
        $pdo = db();
        $stmt = $pdo->query("SELECT * FROM tahun_ajaran WHERE aktif = 1 ORDER BY id_ta DESC LIMIT 1");
        return $stmt->fetch();
    }

    /**
     * Mengaktifkan satu tahun ajaran dan menonaktifkan seluruh tahun ajaran lainnya dalam satu transaksi.
     *
     * @param int $id
     * @return bool
     */
    public static function setAktif(int $id): bool
    {
        $pdo = db();
        if (!self::find($id)) {
            throw new Exception('Data tahun ajaran tidak ditemukan.');
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $pdo->exec("UPDATE tahun_ajaran SET aktif = 0");
            $stmt = $pdo->prepare("UPDATE tahun_ajaran SET aktif = 1 WHERE id_ta = :id");
            $stmt->execute(['id' => $id]);

            if ($ownTx) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal mengubah status tahun ajaran aktif: ' . $e->getMessage());
        }
    }

    /**
     * Menambahkan tahun ajaran baru.
     *
     * @param array $d ['tahun', 'semester', 'aktif'(opsional)]
     * @return int
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $tahun    = trim($d['tahun'] ?? '');
        $semester = strtolower(trim($d['semester'] ?? ''));
        $aktif    = !empty($d['aktif']) ? 1 : 0;

        if ($tahun === '' || !in_array($semester, ['ganjil', 'genap'], true)) {
            throw new Exception('Tahun ajaran (contoh: 2026/2027) dan semester (ganjil/genap) wajib diisi dengan benar.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM tahun_ajaran WHERE tahun = :t AND semester = :s LIMIT 1");
        $cek->execute(['t' => $tahun, 's' => $semester]);
        if ($cek->fetchColumn()) {
            throw new Exception("Tahun ajaran {$tahun} semester {$semester} sudah terdaftar.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            if ($aktif === 1) {
                $pdo->exec("UPDATE tahun_ajaran SET aktif = 0");
            }

            $stmt = $pdo->prepare("
                INSERT INTO tahun_ajaran (tahun, semester, aktif)
                VALUES (:tahun, :semester, :aktif)
            ");
            $stmt->execute([
                'tahun'    => $tahun,
                'semester' => $semester,
                'aktif'    => $aktif,
            ]);
            $id = (int)$pdo->lastInsertId();

            if ($ownTx) {
                $pdo->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal menambahkan tahun ajaran: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui tahun ajaran.
     *
     * @param int $id
     * @param array $d
     * @return bool
     */
    public static function update(int $id, array $d): bool
    {
        $pdo = db();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception('Data tahun ajaran tidak ditemukan.');
        }

        $tahun    = trim($d['tahun'] ?? '');
        $semester = strtolower(trim($d['semester'] ?? ''));
        $aktif    = isset($d['aktif']) ? ((int)$d['aktif'] ? 1 : 0) : (int)$existing['aktif'];

        if ($tahun === '' || !in_array($semester, ['ganjil', 'genap'], true)) {
            throw new Exception('Tahun ajaran dan semester wajib diisi dengan benar.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM tahun_ajaran WHERE tahun = :t AND semester = :s AND id_ta <> :id LIMIT 1");
        $cek->execute(['t' => $tahun, 's' => $semester, 'id' => $id]);
        if ($cek->fetchColumn()) {
            throw new Exception("Tahun ajaran {$tahun} semester {$semester} sudah digunakan.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            if ($aktif === 1) {
                $pdo->exec("UPDATE tahun_ajaran SET aktif = 0");
            }

            $stmt = $pdo->prepare("
                UPDATE tahun_ajaran
                SET tahun = :tahun, semester = :semester, aktif = :aktif
                WHERE id_ta = :id
            ");
            $stmt->execute([
                'tahun'    => $tahun,
                'semester' => $semester,
                'aktif'    => $aktif,
                'id'       => $id,
            ]);

            if ($ownTx) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal memperbarui tahun ajaran: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus tahun ajaran jika tidak sedang aktif dan belum memiliki kelas.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception('Data tahun ajaran tidak ditemukan.');
        }

        if ((int)$existing['aktif'] === 1) {
            throw new Exception('Tahun ajaran yang sedang aktif tidak dapat dihapus. Aktifkan tahun ajaran lain terlebih dahulu.');
        }

        $cekKelas = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_ta = :id");
        $cekKelas->execute(['id' => $id]);
        if ((int)$cekKelas->fetchColumn() > 0) {
            throw new Exception('Tahun ajaran tidak dapat dihapus karena masih memiliki data kelas perkuliahan.');
        }

        $stmt = $pdo->prepare("DELETE FROM tahun_ajaran WHERE id_ta = :id");
        return $stmt->execute(['id' => $id]);
    }
}
