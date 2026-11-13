<?php
/**
 * Halaman Jadwal Perkuliahan Mahasiswa
 * Menampilkan jadwal mingguan dalam bentuk Grid Hari (Senin–Sabtu) pada layar desktop
 * serta tampilan daftar berurutan untuk perangkat mobile.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../../models/KrsModel.php';
require_once __DIR__ . '/../../../models/KelasModel.php';

$user    = require_role(['mahasiswa']);
$idMhs   = (int)($user['id_mahasiswa'] ?? 0);
$taAktif = TahunAjaranModel::aktif();
$taList  = TahunAjaranModel::all();

$idTa = isset($_GET['id_ta']) && $_GET['id_ta'] !== ''
    ? (int)$_GET['id_ta']
    : ($taAktif ? (int)$taAktif['id_ta'] : (!empty($taList) ? (int)$taList[0]['id_ta'] : 0));

$krsList = $idTa > 0 ? KrsModel::daftar($idMhs, $idTa) : [];

// Kelompokkan jadwal berdasarkan hari (hanya kelas yang tidak ditolak)
$jadwalPerHari = [];
foreach (KelasModel::HARI_VALID as $h) {
    $jadwalPerHari[$h] = [];
}

$jadwalAktif = [];
foreach ($krsList as $item) {
    if ($item['status'] !== 'ditolak') {
        $jadwalPerHari[$item['hari']][] = $item;
        $jadwalAktif[] = $item;
    }
}

$pageTitle = 'Jadwal Kuliah';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Jadwal Perkuliahan Mingguan</h1>
        <p class="text-xs text-slate-500">Daftar waktu dan ruang kuliah berdasarkan KRS yang telah Anda ambil.</p>
    </div>

    <form method="GET" action="" class="flex items-center gap-2">
        <label for="id_ta" class="text-xs font-medium text-slate-600">Semester:</label>
        <select id="id_ta" name="id_ta" onchange="this.form.submit()" class="select select-bordered select-sm h-9 rounded-lg bg-white border-slate-300 text-sm">
            <?php foreach ($taList as $ta): ?>
                <option value="<?= (int)$ta['id_ta'] ?>" <?= ($idTa === (int)$ta['id_ta']) ? 'selected' : '' ?>>
                    <?= e($ta['tahun'] . ' — ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Tampilan Grid Mingguan (Desktop & Tablet) -->
<div class="hidden md:grid md:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
    <?php foreach (KelasModel::HARI_VALID as $hari): ?>
        <div class="bg-white border border-slate-200 rounded-lg flex flex-col">
            <div class="px-3.5 py-2.5 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex items-center justify-between">
                <span><?= e($hari) ?></span>
                <span class="text-[11px] font-normal text-slate-400"><?= count($jadwalPerHari[$hari]) ?> Kelas</span>
            </div>
            <div class="p-3 space-y-2.5 flex-1">
                <?php if (empty($jadwalPerHari[$hari])): ?>
                    <div class="py-6 text-center text-xs text-slate-400">Tidak ada jadwal</div>
                <?php else: ?>
                    <?php foreach ($jadwalPerHari[$hari] as $j): ?>
                        <?php $jam = substr($j['jam_mulai'], 0, 5) . ' – ' . substr($j['jam_selesai'], 0, 5); ?>
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[11px] font-bold text-teal-800"><?= e($jam) ?></span>
                                <?= ui_badge(ucfirst($j['status'])) ?>
                            </div>
                            <div class="font-semibold text-xs text-slate-800 leading-snug">
                                <?= e($j['kode_mk']) ?> &mdash; <?= e($j['nama_mk']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Kelas <?= e($j['nama_kelas']) ?> &middot; Ruang <?= e($j['ruang']) ?> &middot; <?= (int)$j['sks'] ?> SKS
                            </div>
                            <div class="text-[11px] text-slate-500 truncate" title="<?= e($j['nama_dosen']) ?>">
                                <?= e($j['nama_dosen']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tampilan Tabel / List (Mobile & Ringkasan Lengkap) -->
<div>
    <h2 class="text-sm font-bold text-slate-800 mb-3">Daftar Urutan Jadwal Kuliah</h2>
    <?php
    $rows = [];
    foreach ($jadwalAktif as $j) {
        $jam = substr($j['jam_mulai'], 0, 5) . ' – ' . substr($j['jam_selesai'], 0, 5);
        $rows[] = [
            '<strong>' . e($j['hari']) . '</strong><div class="text-xs text-slate-500">' . e($jam) . '</div>',
            '<span class="font-medium text-slate-800">' . e($j['ruang']) . '</span>',
            '<code class="font-semibold text-slate-800">' . e($j['kode_mk']) . '</code> &mdash; <span class="font-medium text-slate-800">' . e($j['nama_mk']) . '</span>',
            ui_badge('Kelas ' . $j['nama_kelas'], 'info'),
            (int)$j['sks'] . ' SKS',
            e($j['nama_dosen']),
            ui_badge(ucfirst($j['status'])),
        ];
    }

    echo ui_table(
        ['Hari & Jam', 'Ruang', 'Mata Kuliah', 'Kelas', 'SKS', 'Dosen Pengampu', 'Status KRS'],
        $rows,
        'Belum ada jadwal perkuliahan pada semester yang dipilih.'
    );
    ?>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
