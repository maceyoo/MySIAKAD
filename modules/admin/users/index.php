<?php
/**
 * Manajemen Akun Pengguna (users) — Daftar, Filter Role, Pencarian, Paginasi, dan Hapus
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/UserModel.php';

require_role(['admin']);

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id_user'] ?? 0);
    try {
        UserModel::delete($id);
        flash('success', 'Akun pengguna berhasil dihapus.');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/admin/users/index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$q    = trim($_GET['q'] ?? '');
$role = trim($_GET['role'] ?? '');
$res  = UserModel::paginate($page, $q, $role);

$pageTitle = 'Manajemen User';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Manajemen Akun Pengguna</h1>
        <p class="text-xs text-slate-500">Kelola status aktif akun dan reset password untuk Admin, Dosen, dan Mahasiswa.</p>
    </div>
    <a href="<?= e(url('modules/admin/users/form.php')) ?>" class="btn btn-sm h-9 px-3.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('plus', 'w-4 h-4') ?>
        <span>Tambah User Admin</span>
    </a>
</div>

<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2">
    <div class="sm:col-span-6">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari username atau nama lengkap..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="sm:col-span-4">
        <select name="role" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Role</option>
            <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="dosen" <?= ($role === 'dosen') ? 'selected' : '' ?>>Dosen</option>
            <option value="mahasiswa" <?= ($role === 'mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
        </select>
    </div>
    <div class="sm:col-span-2 flex gap-1.5">
        <button type="submit" class="btn btn-sm h-9 flex-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white border-none">Filter</button>
        <?php if ($q !== '' || $role !== ''): ?>
            <a href="<?= e(url('modules/admin/users/index.php')) ?>" class="btn btn-sm h-9 px-2.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php
$rows = [];
foreach ($res['data'] as $row) {
    $isMainAdmin = ($row['username'] === 'admin');
    $btnHapus = '';
    if (!$isMainAdmin) {
        $btnHapus = '<form method="POST" action="" data-confirm="Hapus akun pengguna ' . e($row['username']) . '?" class="inline">'
            . csrf_field()
            . '<input type="hidden" name="action" value="delete">'
            . '<input type="hidden" name="id_user" value="' . (int)$row['id_user'] . '">'
            . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
            . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Hapus</span></button></form>';
    }

    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/admin/users/form.php?id=' . (int)$row['id_user'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">'
        . ui_icon('edit', 'w-3.5 h-3.5') . ' <span>Ubah / Reset Sandi</span></a>'
        . $btnHapus
        . '</div>';

    $rows[] = [
        '<code class="font-semibold text-slate-800">' . e($row['username']) . '</code>',
        '<div class="font-medium text-slate-800">' . e($row['nama_lengkap']) . '</div><div class="text-xs text-slate-500">' . e($row['nama_prodi']) . '</div>',
        ui_badge(ucfirst($row['role'])),
        (int)$row['aktif'] === 1 ? ui_badge('Aktif', 'success') : ui_badge('Nonaktif', 'error'),
        format_tanggal($row['created_at']),
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Username', 'Nama Pengguna & Prodi', 'Role', 'Status Akun', 'Terdaftar', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Belum ada data pengguna.'
);

echo ui_pagination($res['page'], $res['pages'], $res['total'], ['q' => $q, 'role' => $role]);
?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
