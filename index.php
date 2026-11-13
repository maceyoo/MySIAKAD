<?php
/**
 * Entry Point Utama Aplikasi SIAKAD
 * Mengalihkan pengguna ke halaman login atau ke dashboard sesuai role masing-masing.
 */

require_once __DIR__ . '/includes/auth.php';

$user = current_user();

if (!$user) {
    redirect('auth/login.php');
}

if ($user['role'] === 'admin') {
    redirect('modules/admin/dashboard.php');
} elseif ($user['role'] === 'dosen') {
    redirect('modules/dosen/dashboard.php');
} else {
    redirect('modules/mahasiswa/dashboard.php');
}
