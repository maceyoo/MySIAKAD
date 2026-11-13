<?php
/**
 * Model User (Autentikasi & Manajemen Akun)
 */

require_once __DIR__ . '/../config/database.php';

class UserModel
{
    /**
     * Memverifikasi kredensial login pengguna.
     *
     * @param string $username
     * @param string $password
     * @return array|false Data user jika valid dan aktif, false jika gagal
     */
    public static function login(string $username, string $password)
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if ((int)$user['aktif'] !== 1) {
            throw new Exception('Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator Akademik.');
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        return $user;
    }

    /**
     * Mengambil seluruh daftar user.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT u.*,
                   COALESCE(m.nama, d.nama, 'Administrator') AS nama_lengkap,
                   COALESCE(p_m.nama_prodi, p_d.nama_prodi, '-') AS nama_prodi
            FROM users u
            LEFT JOIN mahasiswa m ON m.id_user = u.id_user
            LEFT JOIN prodi p_m ON p_m.id_prodi = m.id_prodi
            LEFT JOIN dosen d ON d.id_user = u.id_user
            LEFT JOIN prodi p_d ON p_d.id_prodi = d.id_prodi
            ORDER BY u.id_user DESC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Paginasi dan pencarian daftar user.
     *
     * @param int $page
     * @param string $q
     * @param string $role
     * @param int $perPage
     * @return array
     */
    public static function paginate(int $page = 1, string $q = '', string $role = '', int $perPage = PER_PAGE): array
    {
        $pdo = db();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $q = trim($q);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(u.username LIKE :q1 OR m.nama LIKE :q2 OR d.nama LIKE :q3)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
        }

        if ($role !== '' && in_array($role, ['admin', 'dosen', 'mahasiswa'], true)) {
            $where[] = "u.role = :role";
            $params['role'] = $role;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "
            SELECT COUNT(*)
            FROM users u
            LEFT JOIN mahasiswa m ON m.id_user = u.id_user
            LEFT JOIN dosen d ON d.id_user = u.id_user
            {$whereSql}
        ";
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $dataSql = "
            SELECT u.*,
                   COALESCE(m.nama, d.nama, 'Administrator') AS nama_lengkap,
                   COALESCE(p_m.nama_prodi, p_d.nama_prodi, '-') AS nama_prodi
            FROM users u
            LEFT JOIN mahasiswa m ON m.id_user = u.id_user
            LEFT JOIN prodi p_m ON p_m.id_prodi = m.id_prodi
            LEFT JOIN dosen d ON d.id_user = u.id_user
            LEFT JOIN prodi p_d ON p_d.id_prodi = d.id_prodi
            {$whereSql}
            ORDER BY u.id_user DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($dataSql);
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
     * Mencari user berdasarkan ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Membuat akun user baru (khususnya admin atau akun mandiri).
     *
     * @param array $d
     * @return int ID user baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $username = trim($d['username'] ?? '');
        $password = (string)($d['password'] ?? '');
        $role     = $d['role'] ?? 'admin';
        $aktif    = isset($d['aktif']) ? (int)$d['aktif'] : 1;

        if ($username === '' || $password === '') {
            throw new Exception('Username dan password wajib diisi.');
        }
        if (strlen($password) < 6) {
            throw new Exception('Password minimal harus terdiri dari 6 karakter.');
        }
        if (!in_array($role, ['admin', 'dosen', 'mahasiswa'], true)) {
            throw new Exception('Role pengguna tidak valid.');
        }

        // Cek duplikat username
        $cek = $pdo->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
        $cek->execute(['u' => $username]);
        if ($cek->fetchColumn()) {
            throw new Exception("Username '{$username}' sudah terdaftar. Gunakan username lain.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, aktif)
            VALUES (:username, :password, :role, :aktif)
        ");
        $stmt->execute([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => $role,
            'aktif'    => $aktif ? 1 : 0,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Memperbarui data akun user.
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
            throw new Exception('Data pengguna tidak ditemukan.');
        }

        $username = trim($d['username'] ?? $existing['username']);
        $role     = $d['role'] ?? $existing['role'];
        $aktif    = isset($d['aktif']) ? (int)$d['aktif'] : (int)$existing['aktif'];
        $password = (string)($d['password'] ?? '');

        if ($username === '') {
            throw new Exception('Username tidak boleh kosong.');
        }

        $cek = $pdo->prepare("SELECT 1 FROM users WHERE username = :u AND id_user <> :id LIMIT 1");
        $cek->execute(['u' => $username, 'id' => $id]);
        if ($cek->fetchColumn()) {
            throw new Exception("Username '{$username}' sudah digunakan oleh pengguna lain.");
        }

        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new Exception('Password baru minimal harus terdiri dari 6 karakter.');
            }
            $stmt = $pdo->prepare("
                UPDATE users
                SET username = :username, password = :password, role = :role, aktif = :aktif
                WHERE id_user = :id
            ");
            return $stmt->execute([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role'     => $role,
                'aktif'    => $aktif ? 1 : 0,
                'id'       => $id,
            ]);
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET username = :username, role = :role, aktif = :aktif
            WHERE id_user = :id
        ");
        return $stmt->execute([
            'username' => $username,
            'role'     => $role,
            'aktif'    => $aktif ? 1 : 0,
            'id'       => $id,
        ]);
    }

    /**
     * Menghapus akun user jika tidak lagi memiliki ketergantungan data akademik.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $user = self::find($id);
        if (!$user) {
            throw new Exception('Data pengguna tidak ditemukan.');
        }

        if ($user['username'] === 'admin') {
            throw new Exception('Akun administrator utama tidak dapat dihapus.');
        }

        // Cek apakah terhubung ke dosen yang mengampu kelas
        $cekDosen = $pdo->prepare("
            SELECT COUNT(*) FROM kelas kl
            JOIN dosen d ON d.id_dosen = kl.id_dosen
            WHERE d.id_user = :id
        ");
        $cekDosen->execute(['id' => $id]);
        if ((int)$cekDosen->fetchColumn() > 0) {
            throw new Exception('Akun dosen tidak dapat dihapus karena masih mengampu kelas perkuliahan.');
        }

        // Cek apakah terhubung ke mahasiswa yang punya KRS
        $cekMhs = $pdo->prepare("
            SELECT COUNT(*) FROM krs k
            JOIN mahasiswa m ON m.id_mahasiswa = k.id_mahasiswa
            WHERE m.id_user = :id
        ");
        $cekMhs->execute(['id' => $id]);
        if ((int)$cekMhs->fetchColumn() > 0) {
            throw new Exception('Akun mahasiswa tidak dapat dihapus karena sudah memiliki riwayat KRS.');
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
        return $stmt->execute(['id' => $id]);
    }
}
