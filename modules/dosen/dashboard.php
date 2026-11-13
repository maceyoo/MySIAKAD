<?php
/**
 * Dashboard Dosen Pengampu
 * Menampilkan ringkasan kelas yang diampu pada semester aktif dan progres pengisian nilai.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../models/DosenModel.php';
require_once __DIR__ . '/../../models/TahunAjaranModel.php';

$user    = require_role(['dosen']);
$idDosen = (int)($user['id_dosen'] ?? 0);
$dosen   = DosenModel::find($idDosen);
$taAktif = TahunAjaranModel::aktif();
$idTa    = $taAktif ? (int)$taAktif['id_ta'] : null;

$kelasList = $idDosen > 0 ? DosenModel::kelasDiampu($idDosen, $idTa) : [];

$totalSks       = 0;
$totalMahasiswa = 0;
$totalDinilai   = 0;
foreach ($kelasList as $kl) {
    $totalSks       += (int)$kl['sks'];
    $totalMahasiswa += (int)$kl['jumlah_disetujui'];
    $totalDinilai   += (int)$kl['jumlah_dinilai'];
}

$pageTitle = 'Dashboard Dosen';
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dashboard Dosen Pengampu</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            <?= e($dosen['nama'] ?? $user['nama']) ?> &middot; NIDN: <code><?= e($dosen['nidn'] ?? '-') ?></code> &middot; Prodi <?= e($dosen['nama_prodi'] ?? '-') ?>
        </p>
    </div>
    <?php if ($taAktif): ?>
        <div class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 flex items-center gap-2">
            <span class="text-slate-500">Semester Aktif:</span>
            <strong><?= e($taAktif['tahun']) ?> &middot; <?= e(ucfirst($taAktif['semester'])) ?></strong>
            <?= ui_badge('Aktif', 'success') ?>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">Kelas Diampu Semester Ini</div>
        <div class="text-2xl font-bold text-slate-800"><?= count($kelasList) ?> <span class="text-sm font-normal text-slate-500">Kelas (<?= $totalSks ?> SKS)</span></div>
    </div>
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">Mahasiswa Peserta (KRS Disetujui)</div>
        <div class="text-2xl font-bold text-slate-800"><?= $totalMahasiswa ?> <span class="text-sm font-normal text-slate-500">Mahasiswa</span></div>
    </div>
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">Progres Input Nilai</div>
        <div class="text-2xl font-bold text-slate-800"><?= $totalDinilai ?> / <?= $totalMahasiswa ?> <span class="text-sm font-normal text-slate-500">Dinilai</span></div>
    </div>
</div>

<div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-bold text-slate-800">Jadwal Mengajar Semester Aktif</h2>
    <a href="<?= e(url('modules/dosen/kelas/index.php')) ?>" class="text-xs font-medium text-teal-700 hover:underline">Lihat Semua Kelas &rarr;</a>
</div>

<?php
$rows = [];
foreach ($kelasList as $kl) {
    $jam = substr($kl['jam_mulai'], 0, 5) . ' – ' . substr($kl['jam_selesai'], 0, 5);
    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/dosen/kelas/index.php?id_kelas=' . (int)$kl['id_kelas'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">Peserta</a>'
        . '<a href="' . e(url('modules/dosen/nilai/index.php?id_kelas=' . (int)$kl['id_kelas'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-teal-800 hover:bg-teal-900 text-white border-none">Input Nilai</a>'
        . '</div>';

    $rows[] = [
        '<div class="font-semibold text-slate-800">' . e($kl['kode_mk']) . ' &mdash; ' . e($kl['nama_mk']) . '</div>'
            . '<div class="text-xs text-slate-500">' . e($kl['nama_prodi']) . ' &middot; ' . (int)$kl['sks'] . ' SKS</div>',
        ui_badge('Kelas ' . $kl['nama_kelas'], 'info'),
        '<div class="font-medium text-slate-800">' . e($kl['hari']) . ', ' . e($jam) . '</div><div class="text-xs text-slate-500">Ruang ' . e($kl['ruang']) . '</div>',
        (int)$kl['jumlah_disetujui'] . ' Mhs',
        (int)$kl['jumlah_dinilai'] . ' / ' . (int)$kl['jumlah_disetujui'],
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Mata Kuliah', 'Kelas', 'Jadwal & Ruang', 'Peserta', 'Dinilai', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada kelas yang ditugaskan kepada Anda pada semester aktif ini.'
);
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
