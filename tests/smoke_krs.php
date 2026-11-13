<?php
/**
 * Smoke Test Tahap A3: KelasModel & KrsModel
 * Menguji skenario: normal, duplikat, kuota penuh, jadwal bentrok, lewat batas SKS (24 SKS), dan pembatalan KRS.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TahunAjaranModel.php';
require_once __DIR__ . '/../models/MataKuliahModel.php';
require_once __DIR__ . '/../models/KelasModel.php';
require_once __DIR__ . '/../models/KrsModel.php';
require_once __DIR__ . '/../models/MahasiswaModel.php';

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

echo "=== SMOKE TEST KRS & JADWAL (TAHAP A3) ===\n";

$pdo = db();
$taAktif = TahunAjaranModel::aktif();
$idTa = (int)$taAktif['id_ta'];

// Buat mahasiswa khusus uji KRS
$idMhsUji = MahasiswaModel::create([
    'id_prodi' => 1,
    'nim'      => '9902001',
    'nama'     => 'Mahasiswa Uji KRS',
    'angkatan' => 2026,
]);
$idMhsUji2 = MahasiswaModel::create([
    'id_prodi' => 1,
    'nim'      => '9902002',
    'nama'     => 'Mahasiswa Uji Kuota',
    'angkatan' => 2026,
]);

// Buat beberapa mata kuliah & kelas uji pada TA aktif
$idMk1 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI01', 'nama_mk' => 'MK Uji Normal',  'sks' => 6, 'semester_paket' => 1]);
$idMk2 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI02', 'nama_mk' => 'MK Uji Bentrok', 'sks' => 4, 'semester_paket' => 1]);
$idMk3 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI03', 'nama_mk' => 'MK Uji Kuota',   'sks' => 3, 'semester_paket' => 1]);
$idMk4 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI04', 'nama_mk' => 'MK Uji SKS 1',   'sks' => 6, 'semester_paket' => 1]);
$idMk5 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI05', 'nama_mk' => 'MK Uji SKS 2',   'sks' => 6, 'semester_paket' => 1]);
$idMk6 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI06', 'nama_mk' => 'MK Uji SKS 3',   'sks' => 6, 'semester_paket' => 1]);
$idMk7 = MataKuliahModel::create(['id_prodi' => 1, 'kode_mk' => 'UJI07', 'nama_mk' => 'MK Uji SKS Lewat','sks' => 3, 'semester_paket' => 1]);

// Kelas 1: Senin 08:00-10:00 (6 SKS)
$idKelasNormal = KelasModel::create([
    'id_mk' => $idMk1, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U1', 'kuota' => 10,
]);

// Kelas 1B (Mata kuliah sama dengan Kelas 1, jadwal beda): Selasa 08:00-10:00
$idKelasMkSama = KelasModel::create([
    'id_mk' => $idMk1, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'B',
    'hari' => 'Selasa', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U2', 'kuota' => 10,
]);

// Kelas Bentrok dengan Kelas 1: Senin 09:00-11:00
$idKelasBentrok = KelasModel::create([
    'id_mk' => $idMk2, 'id_dosen' => 2, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Senin', 'jam_mulai' => '09:00:00', 'jam_selesai' => '11:00:00', 'ruang' => 'R.U3', 'kuota' => 10,
]);

// Kelas Kuota 1: Rabu 08:00-10:00, kuota = 1
$idKelasKuota1 = KelasModel::create([
    'id_mk' => $idMk3, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Rabu', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U4', 'kuota' => 1,
]);

// Kelas tambahan untuk uji batas 24 SKS
$idKelasSks1 = KelasModel::create([
    'id_mk' => $idMk4, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Kamis', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U5', 'kuota' => 10,
]);
$idKelasSks2 = KelasModel::create([
    'id_mk' => $idMk5, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Jumat', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U6', 'kuota' => 10,
]);
$idKelasSks3 = KelasModel::create([
    'id_mk' => $idMk6, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Sabtu', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'ruang' => 'R.U7', 'kuota' => 10,
]);
$idKelasLewatSks = KelasModel::create([
    'id_mk' => $idMk7, 'id_dosen' => 1, 'id_ta' => $idTa, 'nama_kelas' => 'A',
    'hari' => 'Sabtu', 'jam_mulai' => '13:00:00', 'jam_selesai' => '15:00:00', 'ruang' => 'R.U8', 'kuota' => 10,
]);

// Skenario 1: Normal ambil KRS
$idKrs1 = KrsModel::ambil($idMhsUji, $idKelasNormal);
check('Skenario 1: Ambil KRS normal berhasil', $idKrs1 > 0 && KrsModel::totalSks($idMhsUji, $idTa) === 6);

// Skenario 2a: Duplikat kelas yang sama
$errDupKelas = false;
try {
    KrsModel::ambil($idMhsUji, $idKelasNormal);
} catch (Exception $e) {
    $errDupKelas = true;
}
check('Skenario 2a: Tolak ambil kelas yang sama dua kali', $errDupKelas);

// Skenario 2b: Duplikat mata kuliah yang sama di kelas berbeda
$errDupMk = false;
try {
    KrsModel::ambil($idMhsUji, $idKelasMkSama);
} catch (Exception $e) {
    $errDupMk = true;
}
check('Skenario 2b: Tolak ambil mata kuliah yang sama di kelas berbeda', $errDupMk);

// Skenario 3: Jadwal bentrok (Senin 08:00-10:00 vs Senin 09:00-11:00)
$errBentrok = false;
try {
    KrsModel::ambil($idMhsUji, $idKelasBentrok);
} catch (Exception $e) {
    $errBentrok = true;
}
check('Skenario 3: Tolak kelas dengan jadwal bentrok', $errBentrok);

// Skenario 4: Kuota penuh (Kuota = 1, diisi mhs 2 dulu, lalu mhs 1 mencoba ambil)
KrsModel::ambil($idMhsUji2, $idKelasKuota1);
$errKuota = false;
try {
    KrsModel::ambil($idMhsUji, $idKelasKuota1);
} catch (Exception $e) {
    $errKuota = true;
}
check('Skenario 4: Tolak kelas yang kuotanya sudah penuh', $errKuota);

// Skenario 5: Lewat batas MAX_SKS (24 SKS)
// Saat ini mhs 1 punya 6 SKS. Tambah 3 kelas @ 6 SKS = 24 SKS (harus lolos)
KrsModel::ambil($idMhsUji, $idKelasSks1);
KrsModel::ambil($idMhsUji, $idKelasSks2);
KrsModel::ambil($idMhsUji, $idKelasSks3);
check('Skenario 5a: Ambil tepat 24 SKS diizinkan', KrsModel::totalSks($idMhsUji, $idTa) === 24);

$errLewatSks = false;
try {
    KrsModel::ambil($idMhsUji, $idKelasLewatSks); // 24 + 3 = 27 SKS (> 24)
} catch (Exception $e) {
    $errLewatSks = true;
}
check('Skenario 5b: Tolak pengambilan kelas yang menyebabkan SKS > 24', $errLewatSks);

// Skenario 6: Batal KRS saat status 'diajukan' vs 'disetujui'
KrsModel::setStatus($idKrs1, 'disetujui');
$errBatalDisetujui = false;
try {
    KrsModel::batal($idKrs1, $idMhsUji);
} catch (Exception $e) {
    $errBatalDisetujui = true;
}
check('Skenario 6a: Tolak batal KRS yang sudah disetujui', $errBatalDisetujui);

KrsModel::setStatus($idKrs1, 'diajukan');
$batalOk = KrsModel::batal($idKrs1, $idMhsUji);
check('Skenario 6b: Batal KRS berstatus diajukan berhasil', $batalOk === true);

// Bersihkan seluruh data uji KRS
$pdo->prepare("DELETE FROM krs WHERE id_mahasiswa IN (?, ?)")->execute([$idMhsUji, $idMhsUji2]);
foreach ([$idKelasNormal, $idKelasMkSama, $idKelasBentrok, $idKelasKuota1, $idKelasSks1, $idKelasSks2, $idKelasSks3, $idKelasLewatSks] as $idk) {
    KelasModel::delete($idk);
}
foreach ([$idMk1, $idMk2, $idMk3, $idMk4, $idMk5, $idMk6, $idMk7] as $idmk) {
    MataKuliahModel::delete($idmk);
}
MahasiswaModel::delete($idMhsUji);
MahasiswaModel::delete($idMhsUji2);

echo "-----------------------------------------\n";
echo "Ringkasan: PASS = {$passCount}, FAIL = {$failCount}\n";
exit($failCount > 0 ? 1 : 0);
