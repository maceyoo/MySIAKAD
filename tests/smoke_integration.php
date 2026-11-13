<?php
/**
 * Smoke Test Tahap D (Integrasi Penuh):
 * Menguji alur lengkap Admin -> Mahasiswa (KRS) -> Admin (Setujui KRS) -> Dosen (Input Nilai) -> Mahasiswa (KHS & Transkrip)
 * serta memverifikasi bahwa halaman HTTP di Apache localhost merespons 200 OK tanpa error/warning PHP.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProdiModel.php';
require_once __DIR__ . '/../models/DosenModel.php';
require_once __DIR__ . '/../models/MahasiswaModel.php';
require_once __DIR__ . '/../models/MataKuliahModel.php';
require_once __DIR__ . '/../models/TahunAjaranModel.php';
require_once __DIR__ . '/../models/KelasModel.php';
require_once __DIR__ . '/../models/KrsModel.php';
require_once __DIR__ . '/../models/NilaiModel.php';

$passCount = 0;
$failCount = 0;

function check(string $name, bool $cond, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($cond) {
        $passCount++;
        echo "[PASS] {$name}\n";
    } else {
        $failCount++;
        echo "[FAIL] {$name}" . ($detail !== '' ? " -> {$detail}" : "") . "\n";
    }
}

echo "=== SMOKE TEST INTEGRASI TIM (TAHAP D) ===\n";

// 1. Cek halaman login via HTTP Apache
$loginHtml = @file_get_contents('http://localhost/MySIAKAD/auth/login.php');
check(
    'HTTP GET http://localhost/MySIAKAD/auth/login.php (200 OK & bebas PHP error)',
    $loginHtml !== false
        && strpos($loginHtml, 'Masuk ke Akun Anda') !== false
        && stripos($loginHtml, 'Fatal error') === false
        && stripos($loginHtml, 'Warning:') === false
);

// 2. Simulasi alur penuh end-to-end:
// Mahasiswa Rizky Pratama (2401003, id_mahasiswa=3) mengambil kelas IF101 (id_kelas=1, 4 SKS, Dosen 1) & IF102 (id_kelas=2, 3 SKS, Dosen 2)
$mhsRizky = MahasiswaModel:: paginate(1, '2401003')['data'][0] ?? null;
check('Data seed Mahasiswa 2401003 (Rizky Pratama) tersedia', is_array($mhsRizky));

$idMhs = (int)$mhsRizky['id_mahasiswa'];
$taAktif = TahunAjaranModel::aktif();
$idTa = (int)$taAktif['id_ta'];

$idKrs1 = KrsModel::ambil($idMhs, 1); // IF101 (4 SKS, Dosen 1)
$idKrs2 = KrsModel::ambil($idMhs, 2); // IF102 (3 SKS, Dosen 2)
check('Alur 1: Mahasiswa 2401003 mengambil 2 kelas (total 7 SKS)', KrsModel::totalSks($idMhs, $idTa) === 7);

// Admin menyetujui kedua KRS secara massal
$updated = KrsModel::setStatusMassal([$idKrs1, $idKrs2], 'disetujui');
check('Alur 2: Admin menyetujui KRS secara massal', $updated === 2);

// Dosen 1 menginput nilai IF101 (85, 85, 85 -> NA 85.00 -> A, 4.00)
// Dosen 2 menginput nilai IF102 (75, 75, 75 -> NA 75.00 -> B+, 3.50)
NilaiModel::simpan($idKrs1, 85, 85, 85, 1);
NilaiModel::simpan($idKrs2, 75, 75, 75, 2);

// Mahasiswa melihat KHS & Transkrip:
// Total SKS = 4 + 3 = 7 SKS
// Total Mutu = (4 * 4.00) + (3 * 3.50) = 16.00 + 10.50 = 26.50
// IPS = ROUND(26.50 / 7, 2) = 3.79
$khs = NilaiModel::khs($idMhs, $idTa);
$transkrip = NilaiModel::transkrip($idMhs);
$ips = NilaiModel::ips($idMhs, $idTa);
$ipk = NilaiModel::ipk($idMhs);

check('Alur 3: KHS & Transkrip menampilkan 2 mata kuliah yang telah dinilai', count($khs) === 2 && count($transkrip) === 2);
check('Alur 4: Perhitungan IPS & IPK akurat (26.50 / 7 = 3.79)', abs($ips - 3.79) < 0.001 && abs($ipk - 3.79) < 0.001, "IPS={$ips}, IPK={$ipk}");

// Bersihkan KRS uji pada 2401003 agar kembali ke kondisi seed awal
db()->prepare("DELETE FROM krs WHERE id_mahasiswa = ?")->execute([$idMhs]);

echo "-----------------------------------------\n";
echo "Ringkasan Integrasi: PASS = {$passCount}, FAIL = {$failCount}\n";
exit($failCount > 0 ? 1 : 0);
