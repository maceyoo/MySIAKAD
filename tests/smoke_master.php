<?php
/**
 * Smoke Test Tahap A2: Model Master Data
 * Menguji UserModel, ProdiModel, DosenModel, MahasiswaModel, MataKuliahModel, TahunAjaranModel
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProdiModel.php';
require_once __DIR__ . '/../models/DosenModel.php';
require_once __DIR__ . '/../models/MahasiswaModel.php';
require_once __DIR__ . '/../models/MataKuliahModel.php';
require_once __DIR__ . '/../models/TahunAjaranModel.php';

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

echo "=== SMOKE TEST MASTER DATA (TAHAP A2) ===\n";

// 1. UserModel::login
$uAdmin = UserModel::login('admin', 'admin123');
check('UserModel::login kredensial benar', is_array($uAdmin) && $uAdmin['role'] === 'admin');
$uWrong = UserModel::login('admin', 'salahpassword');
check('UserModel::login password salah ditolak', $uWrong === false);

// 2. ProdiModel CRUD + Duplikat + Proteksi Hapus
$idProdiBaru = ProdiModel::create([
    'kode_prodi' => 'TK',
    'nama_prodi' => 'Teknik Komputer',
    'jenjang'    => 'D3',
]);
$prodiRow = ProdiModel::find($idProdiBaru);
check('ProdiModel::create & find', is_array($prodiRow) && $prodiRow['kode_prodi'] === 'TK');

$dupProdi = false;
try {
    ProdiModel::create(['kode_prodi' => 'TK', 'nama_prodi' => 'Duplikat', 'jenjang' => 'S1']);
} catch (Exception $e) {
    $dupProdi = true;
}
check('ProdiModel::create menolak kode_prodi duplikat', $dupProdi);

$refProdi = false;
try {
    // Prodi IF (id=1) masih punya mahasiswa/dosen/matkul
    ProdiModel::delete(1);
} catch (Exception $e) {
    $refProdi = true;
}
check('ProdiModel::delete menolak hapus prodi yang masih direferensikan', $refProdi);

// 3. DosenModel CRUD (Transaksi users + dosen) + Duplikat
$idDosenBaru = DosenModel::create([
    'id_prodi' => $idProdiBaru,
    'nidn'     => '0199999901',
    'nama'     => 'Dosen Uji Smoke, M.Kom.',
    'email'    => 'smoke.dosen@kampus.ac.id',
    'no_hp'    => '0811000099',
]);
$dosenRow = DosenModel::find($idDosenBaru);
check('DosenModel::create membuat users + profil dosen', is_array($dosenRow) && $dosenRow['username'] === '0199999901');

$dupDosen = false;
try {
    DosenModel::create([
        'id_prodi' => $idProdiBaru,
        'nidn'     => '0199999901',
        'nama'     => 'Dosen Duplikat',
    ]);
} catch (Exception $e) {
    $dupDosen = true;
}
check('DosenModel::create menolak NIDN duplikat', $dupDosen);

// 4. MahasiswaModel CRUD (Transaksi users + mahasiswa) + Paginasi + Duplikat
$idMhsBaru = MahasiswaModel::create([
    'id_prodi' => $idProdiBaru,
    'nim'      => '9901001',
    'nama'     => 'Mahasiswa Uji Smoke',
    'email'    => 'smoke.mhs@kampus.ac.id',
    'no_hp'    => '0812000099',
    'alamat'   => 'Jl. Uji Coba No. 1',
    'angkatan' => 2026,
    'status'   => 'aktif',
]);
$mhsRow = MahasiswaModel::find($idMhsBaru);
check('MahasiswaModel::create membuat users + profil mahasiswa', is_array($mhsRow) && $mhsRow['username'] === '9901001');

$pagMhs = MahasiswaModel::paginate(1, 'Mahasiswa Uji Smoke');
check('MahasiswaModel::paginate + pencarian', $pagMhs['total'] === 1 && $pagMhs['data'][0]['nim'] === '9901001');

$dupMhs = false;
try {
    MahasiswaModel::create([
        'id_prodi' => $idProdiBaru,
        'nim'      => '9901001',
        'nama'     => 'Duplikat NIM',
        'angkatan' => 2026,
    ]);
} catch (Exception $e) {
    $dupMhs = true;
}
check('MahasiswaModel::create menolak NIM duplikat', $dupMhs);

// 5. MataKuliahModel CRUD + Duplikat
$idMkBaru = MataKuliahModel::create([
    'id_prodi'       => $idProdiBaru,
    'kode_mk'        => 'TK101',
    'nama_mk'        => 'Arsitektur Komputer',
    'sks'            => 3,
    'semester_paket' => 1,
]);
$mkRow = MataKuliahModel::find($idMkBaru);
check('MataKuliahModel::create & find', is_array($mkRow) && (int)$mkRow['sks'] === 3);

// 6. TahunAjaranModel::setAktif menjamin hanya 1 aktif
$taAktifAwal = TahunAjaranModel::aktif();
$idTaUji = TahunAjaranModel::create([
    'tahun'    => '2029/2030',
    'semester' => 'ganjil',
    'aktif'    => 1,
]);
$jmlAktif = (int)db()->query("SELECT COUNT(*) FROM tahun_ajaran WHERE aktif = 1")->fetchColumn();
check('TahunAjaranModel::create(aktif=1) menjamin hanya satu aktif', $jmlAktif === 1);

TahunAjaranModel::setAktif((int)$taAktifAwal['id_ta']);
$jmlAktifSetelahRestore = (int)db()->query("SELECT COUNT(*) FROM tahun_ajaran WHERE aktif = 1")->fetchColumn();
$taSekarang = TahunAjaranModel::aktif();
check('TahunAjaranModel::setAktif mengembalikan TA aktif semula', $jmlAktifSetelahRestore === 1 && (int)$taSekarang['id_ta'] === (int)$taAktifAwal['id_ta']);

// 7. Bersihkan data uji
TahunAjaranModel::delete($idTaUji);
MataKuliahModel::delete($idMkBaru);
MahasiswaModel::delete($idMhsBaru);
DosenModel::delete($idDosenBaru);
ProdiModel::delete($idProdiBaru);
check('Pembersihan data uji master berhasil', ProdiModel::find($idProdiBaru) === false);

echo "-----------------------------------------\n";
echo "Ringkasan: PASS = {$passCount}, FAIL = {$failCount}\n";
exit($failCount > 0 ? 1 : 0);
