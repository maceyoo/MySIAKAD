<?php
/**
 * Manajemen Autentikasi & Otorisasi Berbasis Role (RBAC)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

/**
 * Menyimpan informasi pengguna yang berhasil login ke dalam session
 * dan melakukan regenerasi ID session untuk mencegah Session Fixation.
 *
 * @param array $user Baris data dari tabel users (dan profil terkait bila ada)
 * @return void
 */
function login_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }

    // Ambil informasi tambahan profil jika dosen atau mahasiswa
    $pdo = db();
    $idMahasiswa = null;
    $idDosen = null;
    $namaLengkap = $user['username'];
    $nomorInduk = $user['username'];
    $namaProdi = null;
    $idProdi = null;

    if ($user['role'] === 'mahasiswa') {
        $stmt = $pdo->prepare("
            SELECT m.*, p.nama_prodi, p.kode_prodi, p.jenjang
            FROM mahasiswa m
            JOIN prodi p ON p.id_prodi = m.id_prodi
            WHERE m.id_user = :id_user
            LIMIT 1
        ");
        $stmt->execute(['id_user' => $user['id_user']]);
        $profil = $stmt->fetch();
        if ($profil) {
            $idMahasiswa = (int)$profil['id_mahasiswa'];
            $namaLengkap = $profil['nama'];
            $nomorInduk  = $profil['nim'];
            $namaProdi   = $profil['nama_prodi'];
            $idProdi     = (int)$profil['id_prodi'];
        }
    } elseif ($user['role'] === 'dosen') {
        $stmt = $pdo->prepare("
            SELECT d.*, p.nama_prodi, p.kode_prodi
            FROM dosen d
            JOIN prodi p ON p.id_prodi = d.id_prodi
            WHERE d.id_user = :id_user
            LIMIT 1
        ");
        $stmt->execute(['id_user' => $user['id_user']]);
        $profil = $stmt->fetch();
        if ($profil) {
            $idDosen     = (int)$profil['id_dosen'];
            $namaLengkap = $profil['nama'];
            $nomorInduk  = $profil['nidn'];
            $namaProdi   = $profil['nama_prodi'];
            $idProdi     = (int)$profil['id_prodi'];
        }
    } else {
        $namaLengkap = 'Administrator Akademik';
    }

    $_SESSION['user'] = [
        'id_user'      => (int)$user['id_user'],
        'username'     => $user['username'],
        'role'         => $user['role'],
        'nama'         => $namaLengkap,
        'nomor_induk'  => $nomorInduk,
        'id_mahasiswa' => $idMahasiswa,
        'id_dosen'     => $idDosen,
        'id_prodi'     => $idProdi,
        'nama_prodi'   => $namaProdi,
        'login_at'     => date('Y-m-d H:i:s'),
    ];

    // Buat ulang token CSRF baru setelah login
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Menghapus sesi pengguna dan menghancurkan cookie sesi.
 *
 * @return void
 */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        if (!headers_sent()) {
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Mengembalikan data pengguna yang sedang login, atau null jika belum login.
 *
 * @return array|null
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Memastikan pengguna sudah login. Jika belum, alihkan ke halaman login.
 *
 * @return array Data user aktif
 */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.');
        redirect('auth/login.php');
    }
    return $user;
}

/**
 * Memastikan pengguna yang login memiliki salah satu dari role yang diizinkan.
 * Jika tidak sesuai, kembalikan status 403 atau arahkan ke dashboard masing-masing.
 *
 * @param array $roles Contoh: ['admin'] atau ['dosen', 'admin']
 * @return array Data user aktif
 */
function require_role(array $roles): array
{
    $user = require_login();

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        flash('error', 'Akses ditolak: Anda tidak memiliki hak akses ke halaman tersebut.');
        $role = $user['role'];
        if ($role === 'admin') {
            redirect('modules/admin/dashboard.php');
        } elseif ($role === 'dosen') {
            redirect('modules/dosen/dashboard.php');
        } else {
            redirect('modules/mahasiswa/dashboard.php');
        }
    }

    return $user;
}
