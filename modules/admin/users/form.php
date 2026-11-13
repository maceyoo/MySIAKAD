<?php
/**
 * Manajemen Akun Pengguna (users) — Form Tambah & Ubah / Reset Password
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/UserModel.php';

require_role(['admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$data   = [
    'username' => '',
    'role'     => 'admin',
    'aktif'    => 1,
];

if ($isEdit) {
    $found = UserModel::find($id);
    if (!$found) {
        flash('error', 'Data pengguna tidak ditemukan.');
        redirect('modules/admin/users/index.php');
    }
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'username' => $_POST['username'] ?? '',
        'role'     => $_POST['role'] ?? $data['role'],
        'aktif'    => isset($_POST['aktif']) ? 1 : 0,
        'password' => $_POST['password'] ?? '',
    ];

    try {
        if ($isEdit) {
            UserModel::update($id, $payload);
            flash('success', 'Data akun pengguna berhasil diperbarui.');
        } else {
            UserModel::create($payload);
            flash('success', 'Akun pengguna baru berhasil dibuat.');
        }
        redirect('modules/admin/users/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Akun Pengguna';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Untuk membuat akun Dosen atau Mahasiswa lengkap dengan profilnya, gunakan menu Dosen / Mahasiswa.</p>
        </div>
        <a href="<?= e(url('modules/admin/users/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <?= ui_field([
            'name'        => 'username',
            'label'       => 'Username',
            'value'       => $data['username'],
            'placeholder' => 'Masukkan username',
            'required'    => true,
        ]) ?>

        <?= ui_field([
            'name'     => 'role',
            'label'    => 'Role Akses',
            'type'     => 'select',
            'value'    => $data['role'],
            'required' => true,
            'options'  => [
                'admin'     => 'Admin',
                'dosen'     => 'Dosen',
                'mahasiswa' => 'Mahasiswa',
            ],
        ]) ?>

        <?= ui_field([
            'name'        => 'password',
            'label'       => $isEdit ? 'Password Baru (Kosongkan bila tidak diubah)' : 'Password',
            'type'        => 'password',
            'required'    => !$isEdit,
            'placeholder' => 'Minimal 6 karakter',
            'help'        => 'Password akan di-hash secara aman menggunakan algoritma bcrypt (PASSWORD_DEFAULT).',
        ]) ?>

        <div class="form-control pt-1">
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" name="aktif" value="1" class="checkbox checkbox-sm rounded border-slate-300" <?= !empty($data['aktif']) ? 'checked' : '' ?>>
                <span class="label-text text-sm font-medium text-slate-700">Akun Aktif (dapat login ke sistem)</span>
            </label>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/users/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
