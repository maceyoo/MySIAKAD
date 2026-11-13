<?php
/**
 * Master Data Dosen — Daftar, Pencarian, Filter Prodi, Paginasi, dan Hapus
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/DosenModel.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id_dosen'] ?? 0);
    try {
        DosenModel::delete($id);
        flash('success', 'Data dosen beserta akun pengguna berhasil dihapus.');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/admin/dosen/index.php');
}

$page      = max(1, (int)($_GET['page'] ?? 1));
$q         = trim($_GET['q'] ?? '');
$idProdi   = isset($_GET['id_prodi']) && $_GET['id_prodi'] !== '' ? (int)$_GET['id_prodi'] : null;
$res       = DosenModel::paginate($page, $q, $idProdi);
$prodiList = ProdiModel::all();

$pageTitle = 'Data Dosen';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Daftar Dosen</h1>
        <p class="text-xs text-slate-500">Kelola data dosen pengampu dan akun akses portal dosen.</p>
    </div>
    <a href="<?= e(url('modules/admin/dosen/form.php')) ?>" class="btn btn-sm h-9 px-3.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('plus', 'w-4 h-4') ?>
        <span>Tambah Dosen</span>
    </a>
</div>

<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2">
    <div class="sm:col-span-6">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari NIDN, nama dosen, atau email..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="sm:col-span-4">
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
        <?php if ($q !== '' || $idProdi !== null): ?>
            <a href="<?= e(url('modules/admin/dosen/index.php')) ?>" class="btn btn-sm h-9 px-2.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php
$rows = [];
foreach ($res['data'] as $row) {
    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/admin/dosen/form.php?id=' . (int)$row['id_dosen'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">'
        . ui_icon('edit', 'w-3.5 h-3.5') . ' <span>Ubah</span></a>'
        . '<form method="POST" action="" data-confirm="Hapus dosen ' . e($row['nama']) . ' beserta akun loginnya?" class="inline">'
        . csrf_field()
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id_dosen" value="' . (int)$row['id_dosen'] . '">'
        . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
        . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Hapus</span></button>'
        . '</form></div>';

    $rows[] = [
        '<code class="font-semibold text-slate-800">' . e($row['nidn']) . '</code>',
        '<div class="font-medium text-slate-800">' . e($row['nama']) . '</div><div class="text-xs text-slate-500">' . e($row['email'] ?: '-') . '</div>',
        e($row['nama_prodi']),
        e($row['no_hp'] ?: '-'),
        (int)($row['jumlah_kelas'] ?? 0) . ' Kelas',
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['NIDN', 'Nama & Email', 'Program Studi', 'No. HP', 'Beban Kelas', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada data dosen yang sesuai dengan kriteria pencarian.'
);

echo ui_pagination($res['page'], $res['pages'], $res['total'], ['q' => $q, 'id_prodi' => $idProdi]);
?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
