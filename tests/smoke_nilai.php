<?php
/**
 * Smoke Test Tahap A4: NilaiModel
 * Menguji minimal 6 kasus batas konversi nilai (49.99, 50, 59.99, 60, 79.99, 80)
 * serta perhitungan manual IPS & IPK dan otorisasi dosen pengampu.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TahunAjaranModel.php';
require_once __DIR__ . '/../models/MataKuliahModel.php';
require_once __DIR__ . '/../models/KelasModel.php';
require_once __DIR__ . '/../models/KrsModel.php';
require_once __DIR__ . '/../models/MahasiswaModel.php';
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

echo "=== SMOKE TEST NILAI, IPS, & IPK (TAHAP A4) ===\n";

// 1. Uji Kasus Batas Konversi Nilai
$boundaryCases = [
    ['na' => 49.99, 'huruf' => 'E',  'bobot' => 0.00],
    ['na' => 50.00, 'huruf' => 'D',  'bobot' => 1.00],
    ['na' => 59.99, 'huruf' => 'D',  'bobot' => 1.00],
    ['na' => 60.00, 'huruf' => 'C',  'bobot' => 2.00],
    ['na' => 64.99, 'huruf' => 'C',  'bobot' => 2.00],
    ['na' => 65.00, 'huruf' => 'C+', 'bobot' => 2.50],
    ['na' => 69.99, 'huruf' => 'C+', 'bobot' => 2.50],
    ['na' => 70.00, 'huruf' => 'B',  'bobot' => 3.00],
    ['na' => 74.99, 'huruf' => 'B',  'bobot' => 3.00],
    ['na' => 75.00, 'huruf' => 'B+', 'bobot' => 3.50],
    ['na' => 79.99, 'huruf' => 'B+', 'bobot' => 3.50],
    ['na' => 80.00, 'huruf' => 'A',  'bobot' => 4.00],
];

foreach ($boundaryCases as $c) {
    [$h, $b] = NilaiModel::konversi($c['na']);
    check(
        "Konversi batas nilai {$c['na']} -> {$c['huruf']} ({$c['bobot']})",
        $h === $c['huruf'] && abs($b - $c['bobot']) < 0.001,
        "Aktual: {$h} ({$b})"
    );
}

// 2. Uji rumus hitung(tugas, uts, uas) -> 30%*80 + 30%*70 + 40%*90 = 24 + 21 + 36 = 81.00
$naHitung = NilaiModel::hitung(80, 70, 90);
check('NilaiModel::hitung(80, 70, 90) == 81.00', abs($naHitung - 81.00) < 0.001, "Aktual: {$naHitung}");

// 3. Uji penyimpanan nilai, validasi dosen pengampu, dan perhitungan IPS/IPK manual
$pdo = db();
$taAktif = TahunAjaranModel::aktif();
$idTa = (int)$taAktif['id_ta'];

$idMhs = MahasiswaModel::create([
    'id_prodi' => 1,
    'nim'      => '9903001',
    'nama'     => 'Mahasiswa Uji Nilai',
    'angkatan' => 2026,
]);

$idMkA = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'NLA01', 'nama_mk' => 'MK Nilai A (4 SKS)', 'sks' => 4, 'semester_paket' => 1]);
$idMkB = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'NLB01', 'nama_mk' => 'MK Nilai B (2 SKS)', 'sks' => 2, 'semester_paket' => 1]);

$idKelasA = KelasModel::create([
    'id_mk' => $idMkA, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Senin', 'jam_mulai' => '14:00:00', 'jam_selesai' => '16:00:00', 'ruang' => 'R.N1', 'kuota' => 10,
]);
$idKelasB = KelasModel::create([
    'id_mk' => $idMkB, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Selasa', 'jam_mulai' => '14:00:00', 'jam_selesai' => '16:00:00', 'ruang' => 'R.N2', 'kuota' => 10,
]);

$idKrsA = KrsModel::ambil($idMhs, $idKelasA);
$idKrsB = KrsModel::ambil($idMhs, $idKelasB);

// Coba input nilai sebelum KRS disetujui -> harus ditolak
$errBelumDisetujui = false;
try {
    NilaiModel::simpan($idKrsA, 85, 85, 85, 1);
} catch (Exception $e) {
    $errBelumDisetujui = true;
}
check('NilaiModel::simpan menolak KRS yang belum disetujui', $errBelumDisetujui);

// Setujui kedua KRS
KrsModel::setStatus($idKrsA, 'disetujui');
KrsModel::setStatus($idKrsB, 'disetujui');

// Coba input nilai oleh dosen lain (id_dosen = 2 padahal kelas milik id_dosen = 1) -> harus ditolak
$errDosenLain = false;
try {
    NilaiModel::simpan($idKrsA, 85, 85, 85, 2);
} catch (Exception $e) {
    $errDosenLain = true;
}
check('NilaiModel::simpan menolak dosen yang bukan pengampu kelas', $errDosenLain);

// Input nilai valid oleh dosen 1:
// MK A (4 SKS): 80, 80, 80 -> NA = 80.00 -> Huruf A, Bobot 4.00 -> Mutu = 16.00
// MK B (2 SKS): 70, 70, 70 -> NA = 70.00 -> Huruf B, Bobot 3.00 -> Mutu = 6.00
// Total SKS = 6, Total Mutu = 22.00 -> IPS/IPK = ROUND(22 / 6, 2) = 3.67
NilaiModel::simpan($idKrsA, 80, 80, 80, 1);
NilaiModel::simpan($idKrsB, 70, 70, 70, 1);

$ips = NilaiModel::ips($idMhs, $idTa);
$ipk = NilaiModel::ipk($idMhs);
check('Perhitungan IPS manual (4 SKS * 4.00 + 2 SKS * 3.00) / 6 = 3.67', abs($ips - 3.67) < 0.001, "Aktual: {$ips}");
check('Perhitungan IPK manual = 3.67', abs($ipk - 3.67) < 0.001, "Aktual: {$ipk}");

// Bersihkan data uji
$pdo->prepare("DELETE FROM krs WHERE id_mahasiswa = ?")->execute([$idMhs]);
KelasModel::delete($idKelasA);
KelasModel::delete($idKelasB);
MataKuliahModel::delete($idMkA);
MataKuliahModel::delete($idMkB);
MahasiswaModel::delete($idMhs);

echo "-----------------------------------------\n";
echo "Ringkasan: PASS = {$passCount}, FAIL = {$failCount}\n";
exit($failCount > 0 ? 1 : 0);
