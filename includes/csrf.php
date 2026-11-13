<?php
/**
 * Proteksi Cross-Site Request Forgery (CSRF)
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Menghasilkan atau mengambil token CSRF aktif di session.
 *
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Mengembalikan elemen HTML input hidden berisi token CSRF untuk disisipkan di dalam <form>.
 *
 * @return string
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Memverifikasi token CSRF pada request POST.
 * Menghentikan eksekusi dengan kode 403 apabila token tidak cocok atau kosong.
 *
 * @return void
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['_csrf_token'] ?? '';

    if (empty($submitted) || empty($sessionToken) || !hash_equals($sessionToken, (string)$submitted)) {
        http_response_code(403);
        die('Permintaan ditolak: Token keamanan (CSRF) tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.');
    }
}
