<?php
/**
 * Konfigurasi Utama Aplikasi SIAKAD
 * Berisi konstanta aturan bisnis, URL dasar, dan pengaturan sesi yang aman.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Deteksi otomatis BASE_URL agar fleksibel di localhost/MySIAKAD maupun localhost/siakad
if (!defined('BASE_URL')) {
    $folderName = basename(BASE_PATH);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . '/' . $folderName);
}

// Identitas Institusi
if (!defined('NAMA_KAMPUS')) {
    define('NAMA_KAMPUS', 'Institut Teknologi & Bisnis Nusantara');
}
if (!defined('ALAMAT_KAMPUS')) {
    define('ALAMAT_KAMPUS', 'Jl. Pendidikan Raya No. 45, Gedung Rektorat Lt. 2');
}

// Konstanta Aturan Bisnis Akademik (Sesuai docs/01 & prompt.md)
if (!defined('MAX_SKS')) {
    define('MAX_SKS', 24);
}
if (!defined('BOBOT_TUGAS')) {
    define('BOBOT_TUGAS', 0.30);
}
if (!defined('BOBOT_UTS')) {
    define('BOBOT_UTS', 0.30);
}
if (!defined('BOBOT_UAS')) {
    define('BOBOT_UAS', 0.40);
}
if (!defined('PER_PAGE')) {
    define('PER_PAGE', 10);
}

// Pengaturan Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi Sesi Aman (HttpOnly, SameSite=Lax, Strict Mode)
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    } else {
        @session_start();
    }
}
