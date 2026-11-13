<?php
/**
 * Master Data Kelas & Jadwal — Daftar, Filter Tahun Ajaran/Prodi, Pencarian, Paginasi, dan Hapus
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/KelasModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id_kelas'] ?? 0);
    try {
        KelasModel::delete($id);
        flash('success', 'Data kelas perkuliahan berhasil dihapus.');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/admin/kelas/index.php');
}

$taAktif   = TahunAjaranModel::aktif();
$page      = max(1, (int)($_GET['page'] ?? 1));
$q         = trim($_GET['q'] ?? '');
$idTa      = isset($_GET['id_ta']) ? ($_GET['id_ta'] !== '' ? (int)$_GET['id_ta'] : null) : ($taAktif ? (int)$taAktif['id_ta'] : null);
$idProdi   = isset($_GET['id_prodi']) && $_GET['id_prodi'] !== '' ? (int)$_GET['id_prodi'] : null;

$res       = KelasModel::paginate($page, $q, $idTa, $idProdi);
$taList    = TahunAjaranModel::all();
$prodiList = ProdiModel::all();

$pageTitle = 'Kelas & Jadwal';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Daftar Kelas &amp; Jadwal Perkuliahan</h1>
        <p class="text-xs text-slate-500">Kelola penawaran mata kuliah, dosen pengampu, ruang, jadwal, dan kuota peserta.</p>
    </div>
    <a href="<?= e(url('modules/admin/kelas/form.php')) ?>" class="btn btn-sm h-9 px-3.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('plus', 'w-4 h-4') ?>
        <span>Tambah Kelas</span>
    </a>
</div>

<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2">
    <div class="sm:col-span-4">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari mata kuliah, dosen, hari, atau ruang..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="sm:col-span-3">
        <select name="id_ta" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($taList as $ta): ?>
                <option value="<?= (int)$ta['id_ta'] ?>" <?= ($idTa === (int)$ta['id_ta']) ? 'selected' : '' ?>>
                    <?= e($ta['tahun'] . ' - ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-3">
        <select name="id_prodi" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Program Studi</option>
            <?php foreach ($prodiList as $p): ?>
                <option value="<?= (int)$p['id_prodi'] ?>" <?= ($idProdi === (int)$p['id_prodi']) ? 'selected' : '' ?>>
                    <?= e($p['kode_prodi'] . ' — ' . $p['nama_prodi']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-2 flex gap-1.5">
        <button type="submit" class="btn btn-sm h-9 flex-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white border-none">Filter</button>
        <a href="<?= e(url('modules/admin/kelas/index.php?id_ta=')) ?>" class="btn btn-sm h-9 px-2.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
    </div>
</form>

<?php
$rows = [];
foreach ($res['data'] as $row) {
    $jam = substr($row['jam_mulai'], 0, 5) . ' – ' . substr($row['jam_selesai'], 0, 5);
    $terisi = (int)$row['terisi'];
    $kuota  = (int)$row['kuota'];

    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/admin/kelas/form.php?id=' . (int)$row['id_kelas'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">'
        . ui_icon('edit', 'w-3.5 h-3.5') . ' <span>Ubah</span></a>'
        . '<form method="POST" action="" data-confirm="Hapus kelas ' . e($row['kode_mk'] . ' (' . $row['nama_kelas'] . ')') . '?" class="inline">'
        . csrf_field()
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id_kelas" value="' . (int)$row['id_kelas'] . '">'
        . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
        . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Hapus</span></button>'
        . '</form></div>';

    $rows[] = [
        '<div class="font-semibold text-slate-800">' . e($row['kode_mk']) . ' &mdash; ' . e($row['nama_mk']) . '</div>'
            . '<div class="text-xs text-slate-500">' . e($row['nama_prodi']) . ' &middot; ' . (int)$row['sks'] . ' SKS &middot; TA ' . e($row['tahun']) . ' (' . e(ucfirst($row['semester'])) . ')</div>',
        ui_badge('Kelas ' . $row['nama_kelas'], 'info'),
        e($row['nama_dosen']),
        '<div class="font-medium text-slate-800">' . e($row['hari']) . ', ' . e($jam) . '</div><div class="text-xs text-slate-500">Ruang: ' . e($row['ruang']) . '</div>',
        '<span class="font-medium">' . $terisi . ' / ' . $kuota . '</span>',
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Mata Kuliah', 'Kelas', 'Dosen Pengampu', 'Jadwal & Ruang', 'Terisi/Kuota', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada kelas perkuliahan pada filter yang dipilih.'
);

echo ui_pagination($res['page'], $res['pages'], $res['total'], ['q' => $q, 'id_ta' => $idTa, 'id_prodi' => $idProdi]);
?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
