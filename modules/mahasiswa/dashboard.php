<?php
/**
 * Dashboard Akademik Mahasiswa
 * Menampilkan ringkasan SKS diambil, IPS semester berjalan, IPK kumulatif, dan daftar KRS aktif.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../models/MahasiswaModel.php';
require_once __DIR__ . '/../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../models/KrsModel.php';
require_once __DIR__ . '/../../models/NilaiModel.php';

$user   = require_role(['mahasiswa']);
$idMhs  = (int)($user['id_mahasiswa'] ?? 0);
$profil = MahasiswaModel::find($idMhs);

$taAktif   = TahunAjaranModel::aktif();
$idTaAktif = $taAktif ? (int)$taAktif['id_ta'] : 0;

$krsAktif = $idTaAktif > 0 ? KrsModel::daftar($idMhs, $idTaAktif) : [];
$totalSks = $idTaAktif > 0 ? KrsModel::totalSks($idMhs, $idTaAktif) : 0;
$ips      = $idTaAktif > 0 ? NilaiModel::ips($idMhs, $idTaAktif) : 0.00;
$ipk      = NilaiModel::ipk($idMhs);

$pageTitle = 'Dashboard Mahasiswa';
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dashboard Akademik Mahasiswa</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            <?= e($profil['nama'] ?? $user['nama']) ?> &middot; NIM: <code><?= e($profil['nim'] ?? '-') ?></code> &middot; <?= e($profil['nama_prodi'] ?? '-') ?> (Angkatan <?= e($profil['angkatan'] ?? '-') ?>)
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

<!-- Kartu Ringkasan Akademik -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">Total SKS Semester Ini</div>
        <div class="text-2xl font-bold text-slate-800"><?= (int)$totalSks ?> <span class="text-sm font-normal text-slate-500">/ <?= (int)MAX_SKS ?> SKS</span></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/mahasiswa/krs/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Kelola KRS &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">IPS Semester Berjalan</div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($ips, 2) ?></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/mahasiswa/khs/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Lihat KHS &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">IPK Kumulatif</div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($ipk, 2) ?></div>
        <div class="mt-2 text-xs">
            <a href="<?= e(url('modules/mahasiswa/transkrip/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Lihat Transkrip &rarr;</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <div class="text-xs text-slate-500 font-medium mb-1">Status Mahasiswa</div>
        <div class="mt-1"><?= ui_badge(ucfirst($profil['status'] ?? 'aktif')) ?></div>
        <div class="mt-3 text-xs">
            <a href="<?= e(url('modules/mahasiswa/profil/index.php')) ?>" class="text-teal-700 hover:underline font-medium">Profil Saya &rarr;</a>
        </div>
    </div>
</div>

<div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-bold text-slate-800">Mata Kuliah yang Diambil Semester Ini</h2>
    <a href="<?= e(url('modules/mahasiswa/jadwal/index.php')) ?>" class="text-xs font-medium text-teal-700 hover:underline">Lihat Jadwal Mingguan &rarr;</a>
</div>

<?php
$rows = [];
foreach ($krsAktif as $k) {
    $jam = substr($k['jam_mulai'], 0, 5) . ' – ' . substr($k['jam_selesai'], 0, 5);
    $rows[] = [
        '<code class="font-semibold text-slate-800">' . e($k['kode_mk']) . '</code>',
        '<div class="font-medium text-slate-800">' . e($k['nama_mk']) . '</div><div class="text-xs text-slate-500">' . e($k['nama_dosen']) . '</div>',
        ui_badge('Kelas ' . $k['nama_kelas'], 'info'),
        (int)$k['sks'] . ' SKS',
        e($k['hari']) . ', ' . e($jam) . ' (' . e($k['ruang']) . ')',
        ui_badge(ucfirst($k['status'])),
        $k['huruf'] !== null ? ui_badge($k['huruf']) : '<span class="text-xs text-slate-400">Belum dinilai</span>',
    ];
}

echo ui_table(
    ['Kode MK', 'Mata Kuliah & Dosen', 'Kelas', 'SKS', 'Jadwal & Ruang', 'Status KRS', 'Nilai'],
    $rows,
    'Anda belum mengambil mata kuliah pada semester aktif ini. Silakan buka menu Pengisian KRS.'
);
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
