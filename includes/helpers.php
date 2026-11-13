<?php
/**
 * Fungsi Helper Umum (Escape HTML, Redirect, Flash Message, Input Old, Format Tanggal)
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escape string untuk mencegah XSS saat ditampilkan di HTML.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Membentuk URL lengkap berdasarkan BASE_URL.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    return rtrim(BASE_URL, '/') . ($path !== '' ? '/' . $path : '');
}

/**
 * Mengalihkan (redirect) pengguna ke URL tujuan lalu menghentikan eksekusi skrip.
 *
 * @param string $path Path relatif terhadap BASE_URL atau URL penuh
 * @return never
 */
function redirect(string $path): void
{
    $target = url($path);
    if (!headers_sent()) {
        header('Location: ' . $target);
    } else {
        echo '<script>window.location.href=' . json_encode($target) . ';</script>';
    }
    exit;
}

/**
 * Menyimpan pesan kilat (flash message) ke dalam session.
 *
 * @param string $type 'success' | 'error' | 'warning' | 'info'
 * @param string $message Pesan yang akan ditampilkan
 * @return void
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Mengambil dan menghapus pesan kilat (flash message) dari session.
 *
 * @return array|null ['type' => string, 'message' => string] atau null
 */
function get_flash(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

/**
 * Memeriksa apakah request saat ini menggunakan metode HTTP POST.
 *
 * @return bool
 */
function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Mengambil nilai input sebelumnya (dari $_POST atau fallback default) untuk form sticky.
 *
 * @param string $key Nama field
 * @param mixed $default Nilai bawaan jika belum ada POST
 * @return string
 */
function old(string $key, $default = ''): string
{
    if (isset($_POST[$key]) && !is_array($_POST[$key])) {
        return e($_POST[$key]);
    }
    return e($default);
}

/**
 * Memformat tanggal/waktu SQL menjadi format Bahasa Indonesia (contoh: 13 November 2026).
 *
 * @param string|null $dateStr
 * @param bool $withTime Sertakan jam:menit
 * @return string
 */
function format_tanggal(?string $dateStr, bool $withTime = false): string
{
    if (empty($dateStr)) {
        return '-';
    }

    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return e($dateStr);
    }

    $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $tgl = (int)date('j', $timestamp);
    $bln = $bulan[(int)date('n', $timestamp)] ?? '';
    $thn = date('Y', $timestamp);

    $hasil = "{$tgl} {$bln} {$thn}";
    if ($withTime) {
        $hasil .= ' ' . date('H:i', $timestamp);
    }

    return $hasil;
}
