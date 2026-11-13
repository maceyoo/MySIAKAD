<?php
/**
 * Model Mahasiswa (mahasiswa + users)
 */

require_once __DIR__ . '/../config/database.php';

class MahasiswaModel
{
    /**
     * Mengambil semua data mahasiswa.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT m.*, p.kode_prodi, p.nama_prodi, p.jenjang, u.username, u.aktif
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            JOIN users u ON u.id_user = m.id_user
            ORDER BY m.nim ASC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Paginasi dan pencarian daftar mahasiswa.
     *
     * @param int $page
     * @param string $q
     * @param int|null $idProdi
     * @param string $status
     * @param int $perPage
     * @return array
     */
    public static function paginate(
        int $page = 1,
        string $q = '',
        ?int $idProdi = null,
        string $status = '',
        int $perPage = PER_PAGE
    ): array {
        $pdo = db();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $q = trim($q);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(m.nim LIKE :q1 OR m.nama LIKE :q2 OR m.email LIKE :q3 OR p.nama_prodi LIKE :q4)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
            $params['q4'] = "%{$q}%";
        }

        if ($idProdi !== null && $idProdi > 0) {
            $where[] = "m.id_prodi = :id_prodi";
            $params['id_prodi'] = $idProdi;
        }

        if ($status !== '' && in_array($status, ['aktif', 'cuti', 'lulus', 'keluar'], true)) {
            $where[] = "m.status = :status";
            $params['status'] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            {$whereSql}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT m.*, p.kode_prodi, p.nama_prodi, p.jenjang, u.username, u.aktif
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            JOIN users u ON u.id_user = m.id_user
            {$whereSql}
            ORDER BY m.nim ASC
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
     * Mengambil satu mahasiswa berdasarkan id_mahasiswa.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT m.*, p.kode_prodi, p.nama_prodi, p.jenjang, u.username, u.aktif
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            JOIN users u ON u.id_user = m.id_user
            WHERE m.id_mahasiswa = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil satu mahasiswa berdasarkan id_user.
     *
     * @param int $idUser
     * @return array|false
     */
    public static function findByUser(int $idUser)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT m.*, p.kode_prodi, p.nama_prodi, p.jenjang, u.username, u.aktif
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            JOIN users u ON u.id_user = m.id_user
            WHERE m.id_user = :id_user
            LIMIT 1
        ");
        $stmt->execute(['id_user' => $idUser]);
        return $stmt->fetch();
    }

    /**
     * Membuat akun users dan profil mahasiswa dalam SATU transaksi.
     *
     * @param array $d ['id_prodi', 'nim', 'nama', 'email', 'no_hp', 'alamat', 'angkatan', 'status', 'password']
     * @return int ID mahasiswa baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $nim      = trim($d['nim'] ?? '');
        $nama     = trim($d['nama'] ?? '');
        $idProdi  = (int)($d['id_prodi'] ?? 0);
        $email    = trim($d['email'] ?? '');
        $noHp     = trim($d['no_hp'] ?? '');
        $alamat   = trim($d['alamat'] ?? '');
        $angkatan = (int)($d['angkatan'] ?? date('Y'));
        $status   = $d['status'] ?? 'aktif';
        $rawPass  = !empty($d['password']) ? (string)$d['password'] : $nim;

        if ($nim === '' || $nama === '' || $idProdi <= 0 || $angkatan < 2000 || $angkatan > 2100) {
            throw new Exception('NIM, Nama Mahasiswa, Program Studi, dan Tahun Angkatan wajib diisi dengan benar.');
        }

        if (!in_array($status, ['aktif', 'cuti', 'lulus', 'keluar'], true)) {
            throw new Exception('Status akademik mahasiswa tidak valid.');
        }

        // Cek duplikat NIM & username
        $cekNim = $pdo->prepare("SELECT 1 FROM mahasiswa WHERE nim = :nim LIMIT 1");
        $cekNim->execute(['nim' => $nim]);
        if ($cekNim->fetchColumn()) {
            throw new Exception("NIM '{$nim}' sudah terdaftar di sistem.");
        }

        $cekUser = $pdo->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
        $cekUser->execute(['u' => $nim]);
        if ($cekUser->fetchColumn()) {
            throw new Exception("Username '{$nim}' sudah digunakan oleh akun lain.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $stmtUser = $pdo->prepare("
                INSERT INTO users (username, password, role, aktif)
                VALUES (:username, :password, 'mahasiswa', 1)
            ");
            $stmtUser->execute([
                'username' => $nim,
                'password' => password_hash($rawPass, PASSWORD_DEFAULT),
            ]);
            $idUser = (int)$pdo->lastInsertId();

            $stmtMhs = $pdo->prepare("
                INSERT INTO mahasiswa (id_user, id_prodi, nim, nama, email, no_hp, alamat, angkatan, status)
                VALUES (:id_user, :id_prodi, :nim, :nama, :email, :no_hp, :alamat, :angkatan, :status)
            ");
            $stmtMhs->execute([
                'id_user'  => $idUser,
                'id_prodi' => $idProdi,
                'nim'      => $nim,
                'nama'     => $nama,
                'email'    => $email !== '' ? $email : null,
                'no_hp'    => $noHp !== '' ? $noHp : null,
                'alamat'   => $alamat !== '' ? $alamat : null,
                'angkatan' => $angkatan,
                'status'   => $status,
            ]);
            $idMahasiswa = (int)$pdo->lastInsertId();

            if ($ownTx) {
                $pdo->commit();
            }
            return $idMahasiswa;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal menyimpan data mahasiswa: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui data mahasiswa dan akun user terkait dalam satu transaksi.
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
            throw new Exception('Data mahasiswa tidak ditemukan.');
        }

        $nim      = trim($d['nim'] ?? $existing['nim']);
        $nama     = trim($d['nama'] ?? $existing['nama']);
        $idProdi  = (int)($d['id_prodi'] ?? $existing['id_prodi']);
        $email    = trim($d['email'] ?? '');
        $noHp     = trim($d['no_hp'] ?? '');
        $alamat   = trim($d['alamat'] ?? '');
        $angkatan = (int)($d['angkatan'] ?? $existing['angkatan']);
        $status   = $d['status'] ?? $existing['status'];
        $rawPass  = trim((string)($d['password'] ?? ''));

        if ($nim === '' || $nama === '' || $idProdi <= 0 || $angkatan < 2000 || $angkatan > 2100) {
            throw new Exception('NIM, Nama Mahasiswa, Program Studi, dan Tahun Angkatan wajib diisi dengan benar.');
        }

        if (!in_array($status, ['aktif', 'cuti', 'lulus', 'keluar'], true)) {
            throw new Exception('Status akademik mahasiswa tidak valid.');
        }

        $cekNim = $pdo->prepare("SELECT 1 FROM mahasiswa WHERE nim = :nim AND id_mahasiswa <> :id LIMIT 1");
        $cekNim->execute(['nim' => $nim, 'id' => $id]);
        if ($cekNim->fetchColumn()) {
            throw new Exception("NIM '{$nim}' sudah digunakan oleh mahasiswa lain.");
        }

        $cekUser = $pdo->prepare("SELECT 1 FROM users WHERE username = :u AND id_user <> :id_user LIMIT 1");
        $cekUser->execute(['u' => $nim, 'id_user' => $existing['id_user']]);
        if ($cekUser->fetchColumn()) {
            throw new Exception("Username '{$nim}' sudah digunakan oleh akun lain.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $stmtMhs = $pdo->prepare("
                UPDATE mahasiswa
                SET id_prodi = :id_prodi, nim = :nim, nama = :nama, email = :email,
                    no_hp = :no_hp, alamat = :alamat, angkatan = :angkatan, status = :status
                WHERE id_mahasiswa = :id
            ");
            $stmtMhs->execute([
                'id_prodi' => $idProdi,
                'nim'      => $nim,
                'nama'     => $nama,
                'email'    => $email !== '' ? $email : null,
                'no_hp'    => $noHp !== '' ? $noHp : null,
                'alamat'   => $alamat !== '' ? $alamat : null,
                'angkatan' => $angkatan,
                'status'   => $status,
                'id'       => $id,
            ]);

            if ($rawPass !== '') {
                $stmtUser = $pdo->prepare("UPDATE users SET username = :u, password = :p WHERE id_user = :id_user");
                $stmtUser->execute([
                    'u'       => $nim,
                    'p'       => password_hash($rawPass, PASSWORD_DEFAULT),
                    'id_user' => $existing['id_user'],
                ]);
            } else {
                $stmtUser = $pdo->prepare("UPDATE users SET username = :u WHERE id_user = :id_user");
                $stmtUser->execute([
                    'u'       => $nim,
                    'id_user' => $existing['id_user'],
                ]);
            }

            if ($ownTx) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal memperbarui data mahasiswa: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data mahasiswa (dan akun users terkait).
     * Menolak penghapusan jika mahasiswa masih memiliki riwayat KRS/nilai.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception('Data mahasiswa tidak ditemukan.');
        }

        $cekKrs = $pdo->prepare("SELECT COUNT(*) FROM krs WHERE id_mahasiswa = :id");
        $cekKrs->execute(['id' => $id]);
        if ((int)$cekKrs->fetchColumn() > 0) {
            throw new Exception('Mahasiswa tidak dapat dihapus karena masih memiliki data KRS/Nilai. Hapus KRS terkait terlebih dahulu atau ubah status mahasiswa.');
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id_user");
        return $stmt->execute(['id_user' => $existing['id_user']]);
    }
}
