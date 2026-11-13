<?php
/**
 * Halaman Profil Mahasiswa & Pembaruan Kontak / Password
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/MahasiswaModel.php';

$user   = require_role(['mahasiswa']);
$idMhs  = (int)($user['id_mahasiswa'] ?? 0);
$profil = MahasiswaModel::find($idMhs);

if (!$profil) {
    flash('error', 'Profil mahasiswa tidak ditemukan.');
    redirect('modules/mahasiswa/dashboard.php');
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'id_prodi' => (int)$profil['id_prodi'],
        'nim'      => $profil['nim'],
        'nama'     => $profil['nama'],
        'angkatan' => (int)$profil['angkatan'],
        'status'   => $profil['status'],
        'email'    => $_POST['email'] ?? '',
        'no_hp'    => $_POST['no_hp'] ?? '',
        'alamat'   => $_POST['alamat'] ?? '',
        'password' => $_POST['password'] ?? '',
    ];

    try {
        MahasiswaModel::update($idMhs, $payload);
        flash('success', 'Data kontak dan profil Anda berhasil diperbarui.');
        redirect('modules/mahasiswa/profil/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $profil = array_merge($profil, $payload);
    }
}

$pageTitle = 'Profil Mahasiswa';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-5">
        <h1 class="text-lg font-bold text-slate-800">Profil Akademik Mahasiswa</h1>
        <p class="text-xs text-slate-500">Data identitas akademik dan formulir pembaruan kontak mahasiswa.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Kartu Identitas Tetap -->
        <div class="md:col-span-5 bg-white border border-slate-200 rounded-lg p-5 space-y-3 h-fit">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-11 h-11 rounded-lg bg-teal-800 text-white flex items-center justify-center font-bold text-base">
                    <?= e(strtoupper(substr($profil['nama'], 0, 2))) ?>
                </div>
                <div>
                    <div class="font-bold text-slate-800 text-sm"><?= e($profil['nama']) ?></div>
                    <div class="text-xs text-slate-500">NIM: <code><?= e($profil['nim']) ?></code></div>
                </div>
            </div>

            <div class="text-xs space-y-2.5 pt-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Program Studi</span>
                    <span class="font-semibold text-slate-800"><?= e($profil['nama_prodi']) ?> (<?= e($profil['jenjang']) ?>)</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tahun Angkatan</span>
                    <span class="font-semibold text-slate-800"><?= e($profil['angkatan']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Status Akademik</span>
                    <?= ui_badge(ucfirst($profil['status'])) ?>
                </div>
            </div>
        </div>

        <!-- Form Ubah Kontak & Password -->
        <div class="md:col-span-7">
            <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
                <?= csrf_field() ?>
                <h2 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100">Perbarui Kontak &amp; Keamanan Akun</h2>

                <?= ui_field([
                    'name'        => 'email',
                    'label'       => 'Alamat Email',
                    'type'        => 'email',
                    'value'       => $profil['email'] ?? '',
                    'placeholder' => 'nama@mhs.kampus.ac.id',
                ]) ?>

                <?= ui_field([
                    'name'        => 'no_hp',
                    'label'       => 'Nomor HP / WhatsApp',
                    'value'       => $profil['no_hp'] ?? '',
                    'placeholder' => '0813xxxxxxxx',
                ]) ?>

                <?= ui_field([
                    'name'        => 'alamat',
                    'label'       => 'Alamat Tempat Tinggal',
                    'type'        => 'textarea',
                    'value'       => $profil['alamat'] ?? '',
                    'placeholder' => 'Alamat lengkap saat ini',
                ]) ?>

                <?= ui_field([
                    'name'        => 'password',
                    'label'       => 'Password Baru (Opsional)',
                    'type'        => 'password',
                    'placeholder' => 'Kosongkan jika tidak ingin mengubah password',
                    'help'        => 'Minimal 6 karakter apabila diisi.',
                ]) ?>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
