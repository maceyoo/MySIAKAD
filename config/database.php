<?php
/**
 * Koneksi Database PDO (Singleton)
 * Menggunakan utf8mb4, ERRMODE_EXCEPTION, FETCH_ASSOC, dan emulate prepares off.
 */

require_once __DIR__ . '/config.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'siakad_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance tunggal koneksi PDO ke database siakad_db.
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Pastikan layanan MySQL/MariaDB di XAMPP sedang berjalan dan database siakad_db sudah dibuat.');
        }
    }

    return $pdo;
}
