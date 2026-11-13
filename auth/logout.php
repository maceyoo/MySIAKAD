<?php
/**
 * Halaman Logout SIAKAD
 */

require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
flash('info', 'Anda telah keluar dari sistem.');
redirect('auth/login.php');
