<?php
/**
 * Master Data Tahun Ajaran — Daftar, Set Aktif, Paginasi, dan Hapus
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';

require_role(['admin']);

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id_ta'] ?? 0);

    try {
        if ($action === 'set_aktif') {
            TahunAjaranModel::setAktif($id);
            flash('success', 'Tahun ajaran aktif berhasil diperbarui.');
        } elseif ($action === 'delete') {
            TahunAjaranModel::delete($id);
            flash('success', 'Data tahun ajaran berhasil dihapus.');
        }
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/admin/tahun_ajaran/index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$q    = trim($_GET['q'] ?? '');
$res  = TahunAjaranModel::paginate($page, $q);

$pageTitle = 'Data Tahun Ajaran';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Daftar Tahun Ajaran</h1>
        <p class="text-xs text-slate-500">Hanya satu tahun ajaran yang dapat berstatus aktif pada satu waktu.</p>
    </div>
    <a href="<?= e(url('modules/admin/tahun_ajaran/form.php')) ?>" class="btn btn-sm h-9 px-3.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('plus', 'w-4 h-4') ?>
        <span>Tambah Tahun Ajaran</span>
    </a>
</div>

<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 flex flex-col sm:flex-row gap-2">
    <div class="relative flex-1">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari tahun (mis. 2026/2027) atau semester..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-800 hover:bg-slate-900 text-white border-none">Cari</button>
        <?php if ($q !== ''): ?>
            <a href="<?= e(url('modules/admin/tahun_ajaran/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php
$rows = [];
foreach ($res['data'] as $row) {
    $isAktif = (int)$row['aktif'] === 1;
    $btnAktif = '';
    if (!$isAktif) {
        $btnAktif = '<form method="POST" action="" class="inline">'
            . csrf_field()
            . '<input type="hidden" name="action" value="set_aktif">'
            . '<input type="hidden" name="id_ta" value="' . (int)$row['id_ta'] . '">'
            . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-none">'
            . ui_icon('check', 'w-3.5 h-3.5') . ' <span>Aktifkan</span></button></form>';
    }

    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . $btnAktif
        . '<a href="' . e(url('modules/admin/tahun_ajaran/form.php?id=' . (int)$row['id_ta'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">'
        . ui_icon('edit', 'w-3.5 h-3.5') . ' <span>Ubah</span></a>'
        . '<form method="POST" action="" data-confirm="Hapus tahun ajaran ' . e($row['tahun'] . ' ' . $row['semester']) . '?" class="inline">'
        . csrf_field()
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id_ta" value="' . (int)$row['id_ta'] . '">'
        . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
        . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Hapus</span></button>'
        . '</form></div>';

    $rows[] = [
        '<strong class="text-slate-800">' . e($row['tahun']) . '</strong>',
        ui_badge(ucfirst($row['semester'])),
        $isAktif ? ui_badge('Aktif', 'success') : ui_badge('Tidak Aktif', 'neutral'),
        (int)($row['jumlah_kelas'] ?? 0) . ' Kelas',
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Tahun Akademik', 'Semester', 'Status', 'Jumlah Kelas', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada data tahun ajaran.'
);

echo ui_pagination($res['page'], $res['pages'], $res['total'], ['q' => $q]);
?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
