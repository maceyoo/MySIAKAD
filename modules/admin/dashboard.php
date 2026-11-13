<?php
/**
 * Dashboard Administrator Akademik
 * Menampilkan ringkasan master data, tahun ajaran aktif, dan pengajuan KRS terbaru.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../models/ProdiModel.php';
require_once __DIR__ . '/../../models/DosenModel.php';
require_once __DIR__ . '/../../models/MahasiswaModel.php';
require_once __DIR__ . '/../../models/MataKuliahModel.php';
require_once __DIR__ . '/../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../models/KelasModel.php';
require_once __DIR__ . '/../../models/KrsModel.php';

require_role(['admin']);

$prodiList      = ProdiModel::all();
$totalProdi     = count($prodiList);
$totalDosen     = DosenModel::paginate(1, '')['total'];
$totalMahasiswa = MahasiswaModel::paginate(1, '')['total'];
$totalMatkul    = MataKuliahModel::paginate(1, '')['total'];
$taAktif        = TahunAjaranModel::aktif();
$idTaAktif      = $taAktif ? (int)$taAktif['id_ta'] : null;
$kelasAktif     = $idTaAktif ? KelasModel::all($idTaAktif) : [];
$krsDiajukan    = $idTaAktif ? KrsModel::daftarAdmin($idTaAktif, null, 'diajukan') : [];

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dashboard Administrasi Akademik</h1>
        <p class="text-xs text-slate-500 mt-0.5">Ringkasan data akademik dan aktivitas perkuliahan berjalan.</p>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($taAktif): ?>
            <div class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 flex items-center gap-2">
                <span class="text-slate-500">Semester Aktif:</span>
                <strong><?= e($taAktif['tahun']) ?> &middot; <?= e(ucfirst($taAktif['semester'])) ?></strong>
                <?= ui_badge('Aktif', 'success') ?>
            </div>
        <?php else: ?>
            <?= ui_badge('Belum Ada Tahun Ajaran Aktif', 'warning') ?>
        <?php endif; ?>
    </div>
</div>

<!-- Kartu Ringkasan Statistik -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="flex items-center justify-between text-slate-500 text-xs font-medium mb-1">
            <span>Total Mahasiswa</span>
            <?= ui_icon('academic', 'w-4 h-4 text-teal-700') ?>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= (int)$totalMahasiswa ?></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/admin/mahasiswa/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Kelola Mahasiswa &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="flex items-center justify-between text-slate-500 text-xs font-medium mb-1">
            <span>Total Dosen</span>
            <?= ui_icon('user', 'w-4 h-4 text-teal-700') ?>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= (int)$totalDosen ?></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/admin/dosen/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Kelola Dosen &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="flex items-center justify-between text-slate-500 text-xs font-medium mb-1">
            <span>Mata Kuliah &amp; Kelas Aktif</span>
            <?= ui_icon('book', 'w-4 h-4 text-teal-700') ?>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= (int)$totalMatkul ?> <span class="text-sm font-normal text-slate-500">MK (<?= count($kelasAktif) ?> kelas)</span></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/admin/kelas/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Kelola Kelas &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="flex items-center justify-between text-slate-500 text-xs font-medium mb-1">
            <span>KRS Menunggu Verifikasi</span>
            <?= ui_icon('clipboard', 'w-4 h-4 text-amber-600') ?>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= count($krsDiajukan) ?></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/admin/krs/index.php?status=diajukan')) ?>" class="text-amber-700 hover:underline font-medium">Verifikasi Sekarang &rarr;</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Tabel Pengajuan KRS Terbaru -->
    <div class="lg:col-span-7">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-800">Pengajuan KRS Menunggu Persetujuan</h2>
            <a href="<?= e(url('modules/admin/krs/index.php')) ?>" class="text-xs font-medium text-teal-700 hover:underline">Lihat Semua</a>
        </div>
        <?php
        $krsRows = [];
        foreach (array_slice($krsDiajukan, 0, 5) as $k) {
            $krsRows[] = [
                '<strong>' . e($k['nim']) . '</strong><div class="text-xs text-slate-500">' . e($k['nama_mahasiswa']) . '</div>',
                '<strong>' . e($k['kode_mk']) . '</strong> - ' . e($k['nama_mk']) . ' (' . e($k['nama_kelas']) . ')<div class="text-xs text-slate-500">' . (int)$k['sks'] . ' SKS &middot; ' . e($k['hari']) . '</div>',
                ui_badge($k['status']),
            ];
        }
        echo ui_table(['Mahasiswa', 'Mata Kuliah & Kelas', 'Status'], $krsRows, 'Tidak ada pengajuan KRS yang menunggu verifikasi.');
        ?>
    </div>

    <!-- Ringkasan Program Studi -->
    <div class="lg:col-span-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-800">Rekapitulasi Program Studi (<?= (int)$totalProdi ?>)</h2>
            <a href="<?= e(url('modules/admin/prodi/index.php')) ?>" class="text-xs font-medium text-teal-700 hover:underline">Kelola Prodi</a>
        </div>
        <?php
        $prodiRows = [];
        foreach ($prodiList as $p) {
            $prodiRows[] = [
                '<strong>' . e($p['kode_prodi']) . '</strong> &mdash; ' . e($p['nama_prodi']) . ' ' . ui_badge($p['jenjang']),
                (int)$p['jumlah_mahasiswa'] . ' Mhs',
                (int)$p['jumlah_dosen'] . ' Dosen',
            ];
        }
        echo ui_table(['Program Studi', 'Mahasiswa', 'Dosen'], $prodiRows, 'Belum ada data program studi.');
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
