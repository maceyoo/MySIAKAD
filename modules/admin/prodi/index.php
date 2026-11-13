<?php
/**
 * Master Data Program Studi — Daftar, Pencarian, Paginasi, dan Hapus
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id_prodi'] ?? 0);
    try {
        ProdiModel::delete($id);
        flash('success', 'Data program studi berhasil dihapus.');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/admin/prodi/index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$q    = trim($_GET['q'] ?? '');
$res  = ProdiModel::paginate($page, $q);

$pageTitle = 'Data Program Studi';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Daftar Program Studi</h1>
        <p class="text-xs text-slate-500">Kelola master data program studi dan jenjang pendidikan.</p>
    </div>
    <a href="<?= e(url('modules/admin/prodi/form.php')) ?>" class="btn btn-sm h-9 px-3.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('plus', 'w-4 h-4') ?>
        <span>Tambah Program Studi</span>
    </a>
</div>

<!-- Form Pencarian -->
<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 flex flex-col sm:flex-row gap-2">
    <div class="relative flex-1">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari kode atau nama program studi..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-800 hover:bg-slate-900 text-white border-none">Cari</button>
        <?php if ($q !== ''): ?>
            <a href="<?= e(url('modules/admin/prodi/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php
$rows = [];
foreach ($res['data'] as $row) {
    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/admin/prodi/form.php?id=' . (int)$row['id_prodi'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">'
        . ui_icon('edit', 'w-3.5 h-3.5') . ' <span>Ubah</span></a>'
        . '<form method="POST" action="" data-confirm="Hapus program studi ' . e($row['nama_prodi']) . '?" class="inline">'
        . csrf_field()
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id_prodi" value="' . (int)$row['id_prodi'] . '">'
        . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
        . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Hapus</span></button>'
        . '</form></div>';

    $rows[] = [
        '<code class="font-semibold text-slate-800">' . e($row['kode_prodi']) . '</code>',
        '<span class="font-medium text-slate-800">' . e($row['nama_prodi']) . '</span>',
        ui_badge($row['jenjang']),
        (int)$row['jumlah_mahasiswa'] . ' Mhs &middot; ' . (int)$row['jumlah_dosen'] . ' Dosen &middot; ' . (int)$row['jumlah_mk'] . ' MK',
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Kode', 'Nama Program Studi', 'Jenjang', 'Statistik', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada data program studi yang ditemukan.'
);

echo ui_pagination($res['page'], $res['pages'], $res['total'], ['q' => $q]);
?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
