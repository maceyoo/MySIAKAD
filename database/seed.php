<?php
/**
 * Skrip Seed Database SIAKAD (Dapat dijalankan via CLI maupun Browser)
 * Mengisi data awal:
 * - 1 Admin
 * - 2 Program Studi
 * - 4 Dosen
 * - 10 Mahasiswa
 * - 8 Mata Kuliah
 * - Tahun Ajaran (1 histori non-aktif + 1 tahun ajaran aktif)
 * - 6 Kelas di Tahun Ajaran Aktif tanpa jadwal bentrok
 * - Data sampel KRS & Nilai agar KHS/Transkrip/Verifikasi langsung siap diuji
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$isCli = (php_sapi_name() === 'cli');

try {
    // Kosongkan tabel dengan urutan aman
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $tables = ['nilai', 'krs', 'kelas', 'tahun_ajaran', 'mata_kuliah', 'dosen', 'mahasiswa', 'prodi', 'users'];
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE {$t}");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $pdo->beginTransaction();

    // 1. Buat Akun Admin
    $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, aktif) VALUES (:u, :p, :r, 1)");
    $stmtUser->execute([
        'u' => 'admin',
        'p' => $passAdmin,
        'r' => 'admin',
    ]);

    // 2. Buat 2 Program Studi
    $prodiData = [
        ['kode_prodi' => 'IF', 'nama_prodi' => 'Teknik Informatika', 'jenjang' => 'S1'],
        ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi',   'jenjang' => 'S1'],
    ];
    $stmtProdi = $pdo->prepare("INSERT INTO prodi (kode_prodi, nama_prodi, jenjang) VALUES (:kode_prodi, :nama_prodi, :jenjang)");
    $prodiIds = [];
    foreach ($prodiData as $p) {
        $stmtProdi->execute($p);
        $prodiIds[$p['kode_prodi']] = (int)$pdo->lastInsertId();
    }

    // 3. Buat 4 Dosen (beserta akun users)
    $passDosen = password_hash('dosen123', PASSWORD_DEFAULT);
    $dosenData = [
        ['nidn' => '0112048801', 'nama' => 'Dr. Budi Santoso, M.Kom.',      'email' => 'budi.santoso@kampus.ac.id',  'no_hp' => '081210001001', 'prodi' => 'IF'],
        ['nidn' => '0115079002', 'nama' => 'Rina Wijaya, S.Kom., M.T.',     'email' => 'rina.wijaya@kampus.ac.id',   'no_hp' => '081210001002', 'prodi' => 'IF'],
        ['nidn' => '0120098503', 'nama' => 'Hendra Kusuma, S.T., M.Cs.',    'email' => 'hendra.kusuma@kampus.ac.id', 'no_hp' => '081210001003', 'prodi' => 'SI'],
        ['nidn' => '0108119204', 'nama' => 'Siti Aminah, S.Si., M.Kom.',    'email' => 'siti.aminah@kampus.ac.id',   'no_hp' => '081210001004', 'prodi' => 'SI'],
    ];
    $stmtDosen = $pdo->prepare("
        INSERT INTO dosen (id_user, id_prodi, nidn, nama, email, no_hp)
        VALUES (:id_user, :id_prodi, :nidn, :nama, :email, :no_hp)
    ");
    $dosenIds = [];
    foreach ($dosenData as $d) {
        $stmtUser->execute([
            'u' => $d['nidn'],
            'p' => $passDosen,
            'r' => 'dosen',
        ]);
        $idUser = (int)$pdo->lastInsertId();

        $stmtDosen->execute([
            'id_user'  => $idUser,
            'id_prodi' => $prodiIds[$d['prodi']],
            'nidn'     => $d['nidn'],
            'nama'     => $d['nama'],
            'email'    => $d['email'],
            'no_hp'    => $d['no_hp'],
        ]);
        $dosenIds[] = (int)$pdo->lastInsertId();
    }

    // 4. Buat 10 Mahasiswa (beserta akun users)
    $passMhs = password_hash('mhs123', PASSWORD_DEFAULT);
    $mahasiswaData = [
        ['nim' => '2401001', 'nama' => 'Ahmad Fauzi',          'email' => 'ahmad.fauzi@mhs.kampus.ac.id',    'no_hp' => '081320002001', 'alamat' => 'Jl. Merdeka No. 10, Medan',       'angkatan' => 2024, 'prodi' => 'IF'],
        ['nim' => '2401002', 'nama' => 'Nadya Putri Lestari',  'email' => 'nadya.putri@mhs.kampus.ac.id',    'no_hp' => '081320002002', 'alamat' => 'Jl. Sudirman No. 22, Medan',      'angkatan' => 2024, 'prodi' => 'IF'],
        ['nim' => '2401003', 'nama' => 'Rizky Pratama',        'email' => 'rizky.pratama@mhs.kampus.ac.id',  'no_hp' => '081320002003', 'alamat' => 'Jl. Diponegoro No. 5, Medan',     'angkatan' => 2024, 'prodi' => 'IF'],
        ['nim' => '2401004', 'nama' => 'Dewi Kartika Sari',    'email' => 'dewi.kartika@mhs.kampus.ac.id',   'no_hp' => '081320002004', 'alamat' => 'Jl. Gatot Subroto No. 88, Medan', 'angkatan' => 2024, 'prodi' => 'IF'],
        ['nim' => '2401005', 'nama' => 'Dimas Aditya',         'email' => 'dimas.aditya@mhs.kampus.ac.id',   'no_hp' => '081320002005', 'alamat' => 'Jl. Setiabudi No. 14, Medan',     'angkatan' => 2025, 'prodi' => 'IF'],
        ['nim' => '2402001', 'nama' => 'Clara Nathania',       'email' => 'clara.nathania@mhs.kampus.ac.id', 'no_hp' => '081320002006', 'alamat' => 'Jl. Imam Bonjol No. 31, Medan',   'angkatan' => 2024, 'prodi' => 'SI'],
        ['nim' => '2402002', 'nama' => 'Fajar Ramadhan',       'email' => 'fajar.ramadhan@mhs.kampus.ac.id', 'no_hp' => '081320002007', 'alamat' => 'Jl. Sisingamangaraja No. 19',     'angkatan' => 2024, 'prodi' => 'SI'],
        ['nim' => '2402003', 'nama' => 'Melati Puspita',       'email' => 'melati.puspita@mhs.kampus.ac.id', 'no_hp' => '081320002008', 'alamat' => 'Jl. Brigjen Katamso No. 40',      'angkatan' => 2024, 'prodi' => 'SI'],
        ['nim' => '2402004', 'nama' => 'Bagas Setiawan',       'email' => 'bagas.setiawan@mhs.kampus.ac.id', 'no_hp' => '081320002009', 'alamat' => 'Jl. Pemuda No. 12, Medan',        'angkatan' => 2025, 'prodi' => 'SI'],
        ['nim' => '2402005', 'nama' => 'Intan Permata',        'email' => 'intan.permata@mhs.kampus.ac.id',  'no_hp' => '081320002010', 'alamat' => 'Jl. Asia No. 77, Medan',          'angkatan' => 2025, 'prodi' => 'SI'],
    ];
    $stmtMhs = $pdo->prepare("
        INSERT INTO mahasiswa (id_user, id_prodi, nim, nama, email, no_hp, alamat, angkatan, status)
        VALUES (:id_user, :id_prodi, :nim, :nama, :email, :no_hp, :alamat, :angkatan, 'aktif')
    ");
    $mhsIds = [];
    foreach ($mahasiswaData as $m) {
        $stmtUser->execute([
            'u' => $m['nim'],
            'p' => $passMhs,
            'r' => 'mahasiswa',
        ]);
        $idUser = (int)$pdo->lastInsertId();

        $stmtMhs->execute([
            'id_user'  => $idUser,
            'id_prodi' => $prodiIds[$m['prodi']],
            'nim'      => $m['nim'],
            'nama'     => $m['nama'],
            'email'    => $m['email'],
            'no_hp'    => $m['no_hp'],
            'alamat'   => $m['alamat'],
            'angkatan' => $m['angkatan'],
        ]);
        $mhsIds[$m['nim']] = (int)$pdo->lastInsertId();
    }

    // 5. Buat 8 Mata Kuliah
    $mkData = [
        ['kode_mk' => 'IF101', 'nama_mk' => 'Algoritma dan Pemrograman',     'sks' => 4, 'semester_paket' => 1, 'prodi' => 'IF'],
        ['kode_mk' => 'IF102', 'nama_mk' => 'Struktur Data',                 'sks' => 3, 'semester_paket' => 2, 'prodi' => 'IF'],
        ['kode_mk' => 'IF201', 'nama_mk' => 'Sistem Basis Data',             'sks' => 4, 'semester_paket' => 3, 'prodi' => 'IF'],
        ['kode_mk' => 'IF202', 'nama_mk' => 'Pemrograman Web',               'sks' => 3, 'semester_paket' => 3, 'prodi' => 'IF'],
        ['kode_mk' => 'IF301', 'nama_mk' => 'Rekayasa Perangkat Lunak',      'sks' => 3, 'semester_paket' => 5, 'prodi' => 'IF'],
        ['kode_mk' => 'SI101', 'nama_mk' => 'Pengantar Sistem Informasi',    'sks' => 3, 'semester_paket' => 1, 'prodi' => 'SI'],
        ['kode_mk' => 'SI201', 'nama_mk' => 'Analisis dan Desain Sistem',    'sks' => 4, 'semester_paket' => 3, 'prodi' => 'SI'],
        ['kode_mk' => 'SI202', 'nama_mk' => 'Manajemen Proyek TI',           'sks' => 3, 'semester_paket' => 4, 'prodi' => 'SI'],
    ];
    $stmtMk = $pdo->prepare("
        INSERT INTO mata_kuliah (id_prodi, kode_mk, nama_mk, sks, semester_paket)
        VALUES (:id_prodi, :kode_mk, :nama_mk, :sks, :semester_paket)
    ");
    $mkIds = [];
    foreach ($mkData as $mk) {
        $stmtMk->execute([
            'id_prodi'       => $prodiIds[$mk['prodi']],
            'kode_mk'        => $mk['kode_mk'],
            'nama_mk'        => $mk['nama_mk'],
            'sks'            => $mk['sks'],
            'semester_paket' => $mk['semester_paket'],
        ]);
        $mkIds[$mk['kode_mk']] = (int)$pdo->lastInsertId();
    }

    // 6. Buat Tahun Ajaran (1 aktif: 2026/2027 Ganjil, dan 1 non-aktif: 2025/2026 Genap)
    $stmtTa = $pdo->prepare("INSERT INTO tahun_ajaran (tahun, semester, aktif) VALUES (:tahun, :semester, :aktif)");
    $stmtTa->execute(['tahun' => '2025/2026', 'semester' => 'genap',  'aktif' => 0]);
    $idTaLalu = (int)$pdo->lastInsertId();

    $stmtTa->execute(['tahun' => '2026/2027', 'semester' => 'ganjil', 'aktif' => 1]);
    $idTaAktif = (int)$pdo->lastInsertId();

    // 7. Buat 6 Kelas di Tahun Ajaran Aktif tanpa jadwal bentrok
    $kelasData = [
        ['mk' => 'IF101', 'dosen' => $dosenIds[0], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Senin',  'jam_mulai' => '08:00:00', 'jam_selesai' => '10:30:00', 'ruang' => 'R.201',   'kuota' => 35],
        ['mk' => 'IF102', 'dosen' => $dosenIds[1], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Selasa', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:30:00', 'ruang' => 'R.202',   'kuota' => 35],
        ['mk' => 'IF201', 'dosen' => $dosenIds[0], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Rabu',   'jam_mulai' => '09:00:00', 'jam_selesai' => '11:30:00', 'ruang' => 'Lab-DB',  'kuota' => 30],
        ['mk' => 'IF202', 'dosen' => $dosenIds[1], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Kamis',  'jam_mulai' => '13:00:00', 'jam_selesai' => '15:30:00', 'ruang' => 'Lab-Web', 'kuota' => 30],
        ['mk' => 'SI101', 'dosen' => $dosenIds[2], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Jumat',  'jam_mulai' => '08:30:00', 'jam_selesai' => '11:00:00', 'ruang' => 'R.301',   'kuota' => 40],
        ['mk' => 'SI201', 'dosen' => $dosenIds[3], 'ta' => $idTaAktif, 'nama_kelas' => 'A', 'hari' => 'Sabtu',  'jam_mulai' => '09:00:00', 'jam_selesai' => '11:30:00', 'ruang' => 'R.302',   'kuota' => 35],
    ];
    $stmtKelas = $pdo->prepare("
        INSERT INTO kelas (id_mk, id_dosen, id_ta, nama_kelas, hari, jam_mulai, jam_selesai, ruang, kuota)
        VALUES (:id_mk, :id_dosen, :id_ta, :nama_kelas, :hari, :jam_mulai, :jam_selesai, :ruang, :kuota)
    ");
    $kelasIds = [];
    foreach ($kelasData as $k) {
        $stmtKelas->execute([
            'id_mk'       => $mkIds[$k['mk']],
            'id_dosen'    => $k['dosen'],
            'id_ta'       => $k['ta'],
            'nama_kelas'  => $k['nama_kelas'],
            'hari'        => $k['hari'],
            'jam_mulai'   => $k['jam_mulai'],
            'jam_selesai' => $k['jam_selesai'],
            'ruang'       => $k['ruang'],
            'kuota'       => $k['kuota'],
        ]);
        $kelasIds[$k['mk']] = (int)$pdo->lastInsertId();
    }

    // 8. Tambahkan contoh KRS & Nilai awal untuk demo langsung (Mahasiswa 2401001 & 2401002)
    $stmtKrs = $pdo->prepare("INSERT INTO krs (id_mahasiswa, id_kelas, status) VALUES (:mhs, :kelas, :status)");
    $stmtNilai = $pdo->prepare("
        INSERT INTO nilai (id_krs, tugas, uts, uas, nilai_akhir, huruf, bobot)
        VALUES (:id_krs, :tugas, :uts, :uas, :na, :huruf, :bobot)
    ");

    // Ahmad Fauzi (2401001): 2 kelas disetujui & sudah dinilai, 1 kelas diajukan (menunggu persetujuan admin)
    $stmtKrs->execute(['mhs' => $mhsIds['2401001'], 'kelas' => $kelasIds['IF101'], 'status' => 'disetujui']);
    $krs1 = (int)$pdo->lastInsertId();
    $stmtNilai->execute(['id_krs' => $krs1, 'tugas' => 85, 'uts' => 80, 'uas' => 88, 'na' => 84.70, 'huruf' => 'A', 'bobot' => 4.00]);

    $stmtKrs->execute(['mhs' => $mhsIds['2401001'], 'kelas' => $kelasIds['IF102'], 'status' => 'disetujui']);
    $krs2 = (int)$pdo->lastInsertId();
    $stmtNilai->execute(['id_krs' => $krs2, 'tugas' => 78, 'uts' => 75, 'uas' => 76, 'na' => 76.30, 'huruf' => 'B+', 'bobot' => 3.50]);

    $stmtKrs->execute(['mhs' => $mhsIds['2401001'], 'kelas' => $kelasIds['IF201'], 'status' => 'diajukan']);

    // Nadya Putri (2401002): 1 kelas disetujui (belum dinilai dosen), 1 kelas diajukan
    $stmtKrs->execute(['mhs' => $mhsIds['2401002'], 'kelas' => $kelasIds['IF101'], 'status' => 'disetujui']);
    $stmtKrs->execute(['mhs' => $mhsIds['2401002'], 'kelas' => $kelasIds['IF202'], 'status' => 'diajukan']);

    $pdo->commit();

    if ($isCli) {
        echo "============================================================\n";
        echo " SEED DATABASE SIAKAD BERHASIL DIJALANKAN!\n";
        echo "============================================================\n";
        echo " Kredensial Akun Demo:\n";
        echo " - Admin     : username = admin       | password = admin123\n";
        echo " - Dosen 1   : username = 0112048801  | password = dosen123 (Dr. Budi Santoso)\n";
        echo " - Dosen 2   : username = 0115079002  | password = dosen123 (Rina Wijaya)\n";
        echo " - Dosen 3   : username = 0120098503  | password = dosen123 (Hendra Kusuma)\n";
        echo " - Dosen 4   : username = 0108119204  | password = dosen123 (Siti Aminah)\n";
        echo " - Mahasiswa : username = 2401001 s/d 2401005, 2402001 s/d 2402005 | password = mhs123\n";
        echo "============================================================\n";
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="id" data-theme="light">
        <head>
            <meta charset="UTF-8">
            <title>Seed Database SIAKAD</title>
            <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 min-h-screen p-8 font-sans">
            <div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-lg p-6 shadow-sm">
                <div class="alert alert-success mb-6">
                    <span>Seed database <strong>siakad_db</strong> berhasil dijalankan!</span>
                </div>
                <h1 class="text-xl font-bold text-slate-800 mb-4">Kredensial Akun Demo</h1>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full text-sm">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-primary">Admin</span></td>
                                <td><code>admin</code></td>
                                <td><code>admin123</code></td>
                                <td>Administrator Akademik</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">Dosen</span></td>
                                <td><code>0112048801</code></td>
                                <td><code>dosen123</code></td>
                                <td>Dr. Budi Santoso, M.Kom. (Teknik Informatika)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">Dosen</span></td>
                                <td><code>0115079002</code></td>
                                <td><code>dosen123</code></td>
                                <td>Rina Wijaya, S.Kom., M.T. (Teknik Informatika)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">Dosen</span></td>
                                <td><code>0120098503</code></td>
                                <td><code>dosen123</code></td>
                                <td>Hendra Kusuma, S.T., M.Cs. (Sistem Informasi)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">Dosen</span></td>
                                <td><code>0108119204</code></td>
                                <td><code>dosen123</code></td>
                                <td>Siti Aminah, S.Si., M.Kom. (Sistem Informasi)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-accent">Mahasiswa</span></td>
                                <td><code>2401001</code> s/d <code>2401005</code></td>
                                <td><code>mhs123</code></td>
                                <td>Mahasiswa Teknik Informatika (Contoh: Ahmad Fauzi - 2401001)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-accent">Mahasiswa</span></td>
                                <td><code>2402001</code> s/d <code>2402005</code></td>
                                <td><code>mhs123</code></td>
                                <td>Mahasiswa Sistem Informasi (Contoh: Clara Nathania - 2402001)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-between items-center">
                    <p class="text-xs text-slate-500">Demi keamanan, hapus atau ganti nama file <code>database/seed.php</code> di lingkungan produksi.</p>
                    <a href="<?= htmlspecialchars(BASE_URL . '/auth/login.php') ?>" class="btn btn-primary btn-sm">Ke Halaman Login</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Gagal menjalankan seed: " . $e->getMessage() . "\n";
    exit(1);
}
