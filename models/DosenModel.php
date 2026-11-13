<?php
/**
 * Model Dosen (dosen + users)
 */

require_once __DIR__ . '/../config/database.php';

class DosenModel
{
    /**
     * Mengambil semua data dosen beserta prodi.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = "
            SELECT d.*, p.kode_prodi, p.nama_prodi, u.username, u.aktif
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            JOIN users u ON u.id_user = d.id_user
            ORDER BY d.nama ASC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Paginasi dan pencarian data dosen.
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
            $where[] = "(d.nidn LIKE :q1 OR d.nama LIKE :q2 OR d.email LIKE :q3 OR p.nama_prodi LIKE :q4)";
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
            $params['q4'] = "%{$q}%";
        }

        if ($idProdi !== null && $idProdi > 0) {
            $where[] = "d.id_prodi = :id_prodi";
            $params['id_prodi'] = $idProdi;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            {$whereSql}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT d.*, p.kode_prodi, p.nama_prodi, u.username, u.aktif,
                   (SELECT COUNT(*) FROM kelas kl WHERE kl.id_dosen = d.id_dosen) AS jumlah_kelas
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            JOIN users u ON u.id_user = d.id_user
            {$whereSql}
            ORDER BY d.nama ASC
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
     * Mengambil data dosen berdasarkan id_dosen.
     *
     * @param int $id
     * @return array|false
     */
    public static function find(int $id)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT d.*, p.kode_prodi, p.nama_prodi, u.username, u.aktif
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            JOIN users u ON u.id_user = d.id_user
            WHERE d.id_dosen = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil data dosen berdasarkan id_user.
     *
     * @param int $idUser
     * @return array|false
     */
    public static function findByUser(int $idUser)
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT d.*, p.kode_prodi, p.nama_prodi, u.username, u.aktif
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            JOIN users u ON u.id_user = d.id_user
            WHERE d.id_user = :id_user
            LIMIT 1
        ");
        $stmt->execute(['id_user' => $idUser]);
        return $stmt->fetch();
    }

    /**
     * Membuat akun users dan profil dosen dalam SATU transaksi.
     *
     * @param array $d ['id_prodi', 'nidn', 'nama', 'email', 'no_hp', 'password'(opsional)]
     * @return int ID dosen baru
     */
    public static function create(array $d): int
    {
        $pdo = db();
        $nidn    = trim($d['nidn'] ?? '');
        $nama    = trim($d['nama'] ?? '');
        $idProdi = (int)($d['id_prodi'] ?? 0);
        $email   = trim($d['email'] ?? '');
        $noHp    = trim($d['no_hp'] ?? '');
        $rawPass = !empty($d['password']) ? (string)$d['password'] : $nidn;

        if ($nidn === '' || $nama === '' || $idProdi <= 0) {
            throw new Exception('NIDN, Nama Dosen, dan Program Studi wajib diisi.');
        }

        // Cek duplikat NIDN atau username
        $cekNidn = $pdo->prepare("SELECT 1 FROM dosen WHERE nidn = :nidn LIMIT 1");
        $cekNidn->execute(['nidn' => $nidn]);
        if ($cekNidn->fetchColumn()) {
            throw new Exception("NIDN '{$nidn}' sudah terdaftar di sistem.");
        }

        $cekUser = $pdo->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
        $cekUser->execute(['u' => $nidn]);
        if ($cekUser->fetchColumn()) {
            throw new Exception("Username '{$nidn}' sudah digunakan oleh akun lain.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $stmtUser = $pdo->prepare("
                INSERT INTO users (username, password, role, aktif)
                VALUES (:username, :password, 'dosen', 1)
            ");
            $stmtUser->execute([
                'username' => $nidn,
                'password' => password_hash($rawPass, PASSWORD_DEFAULT),
            ]);
            $idUser = (int)$pdo->lastInsertId();

            $stmtDosen = $pdo->prepare("
                INSERT INTO dosen (id_user, id_prodi, nidn, nama, email, no_hp)
                VALUES (:id_user, :id_prodi, :nidn, :nama, :email, :no_hp)
            ");
            $stmtDosen->execute([
                'id_user'  => $idUser,
                'id_prodi' => $idProdi,
                'nidn'     => $nidn,
                'nama'     => $nama,
                'email'    => $email !== '' ? $email : null,
                'no_hp'    => $noHp !== '' ? $noHp : null,
            ]);
            $idDosen = (int)$pdo->lastInsertId();

            if ($ownTx) {
                $pdo->commit();
            }
            return $idDosen;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Gagal menyimpan data dosen: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui data dosen dan akun user terkait dalam satu transaksi.
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
            throw new Exception('Data dosen tidak ditemukan.');
        }

        $nidn    = trim($d['nidn'] ?? '');
        $nama    = trim($d['nama'] ?? '');
        $idProdi = (int)($d['id_prodi'] ?? 0);
        $email   = trim($d['email'] ?? '');
        $noHp    = trim($d['no_hp'] ?? '');
        $rawPass = trim((string)($d['password'] ?? ''));

        if ($nidn === '' || $nama === '' || $idProdi <= 0) {
            throw new Exception('NIDN, Nama Dosen, dan Program Studi wajib diisi.');
        }

        $cekNidn = $pdo->prepare("SELECT 1 FROM dosen WHERE nidn = :nidn AND id_dosen <> :id LIMIT 1");
        $cekNidn->execute(['nidn' => $nidn, 'id' => $id]);
        if ($cekNidn->fetchColumn()) {
            throw new Exception("NIDN '{$nidn}' sudah digunakan oleh dosen lain.");
        }

        $cekUser = $pdo->prepare("SELECT 1 FROM users WHERE username = :u AND id_user <> :id_user LIMIT 1");
        $cekUser->execute(['u' => $nidn, 'id_user' => $existing['id_user']]);
        if ($cekUser->fetchColumn()) {
            throw new Exception("Username '{$nidn}' sudah digunakan oleh akun lain.");
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $stmtDosen = $pdo->prepare("
                UPDATE dosen
                SET id_prodi = :id_prodi, nidn = :nidn, nama = :nama, email = :email, no_hp = :no_hp
                WHERE id_dosen = :id
            ");
            $stmtDosen->execute([
                'id_prodi' => $idProdi,
                'nidn'     => $nidn,
                'nama'     => $nama,
                'email'    => $email !== '' ? $email : null,
                'no_hp'    => $noHp !== '' ? $noHp : null,
                'id'       => $id,
            ]);

            if ($rawPass !== '') {
                $stmtUser = $pdo->prepare("UPDATE users SET username = :u, password = :p WHERE id_user = :id_user");
                $stmtUser->execute([
                    'u'       => $nidn,
                    'p'       => password_hash($rawPass, PASSWORD_DEFAULT),
                    'id_user' => $existing['id_user'],
                ]);
            } else {
                $stmtUser = $pdo->prepare("UPDATE users SET username = :u WHERE id_user = :id_user");
                $stmtUser->execute([
                    'u'       => $nidn,
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
            throw new Exception('Gagal memperbarui data dosen: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data dosen (dan akun users terkait) jika belum mengampu kelas.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception('Data dosen tidak ditemukan.');
        }

        $cekKelas = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_dosen = :id");
        $cekKelas->execute(['id' => $id]);
        if ((int)$cekKelas->fetchColumn() > 0) {
            throw new Exception('Dosen tidak dapat dihapus karena masih mengampu kelas perkuliahan.');
        }

        // Hapus dari users (ON DELETE CASCADE akan menghapus baris di dosen)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id_user");
        return $stmt->execute(['id_user' => $existing['id_user']]);
    }

    /**
     * Mengambil daftar kelas yang diampu oleh seorang dosen pada tahun ajaran tertentu (atau semua TA jika null).
     *
     * @param int $idDosen
     * @param int|null $idTa
     * @return array
     */
    public static function kelasDiampu(int $idDosen, ?int $idTa = null): array
    {
        $pdo = db();
        $where = "WHERE kl.id_dosen = :id_dosen";
        $params = ['id_dosen' => $idDosen];

        if ($idTa !== null && $idTa > 0) {
            $where .= " AND kl.id_ta = :id_ta";
            $params['id_ta'] = $idTa;
        }

        $sql = "
            SELECT kl.*,
                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester_paket,
                   p.nama_prodi, p.kode_prodi,
                   ta.tahun, ta.semester, ta.aktif AS ta_aktif,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status = 'disetujui') AS jumlah_disetujui,
                   (SELECT COUNT(*) FROM krs k WHERE k.id_kelas = kl.id_kelas AND k.status <> 'ditolak') AS jumlah_peserta,
                   (SELECT COUNT(*) FROM krs k JOIN nilai n ON n.id_krs = k.id_krs WHERE k.id_kelas = kl.id_kelas AND k.status = 'disetujui') AS jumlah_dinilai
            FROM kelas kl
            JOIN mata_kuliah mk ON mk.id_mk = kl.id_mk
            JOIN prodi p ON p.id_prodi = mk.id_prodi
            JOIN tahun_ajaran ta ON ta.id_ta = kl.id_ta
            {$where}
            ORDER BY ta.aktif DESC, FIELD(kl.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), kl.jam_mulai ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
