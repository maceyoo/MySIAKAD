<?php
/**
 * Master Data Mahasiswa — Form Tambah & Ubah (membuat/memperbarui users + mahasiswa dalam satu transaksi)
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/MahasiswaModel.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$data   = [
    'id_prodi' => '',
    'nim'      => '',
    'nama'     => '',
    'email'    => '',
    'no_hp'    => '',
    'alamat'   => '',
    'angkatan' => date('Y'),
    'status'   => 'aktif',
];

if ($isEdit) {
    $found = MahasiswaModel::find($id);
    if (!$found) {
        flash('error', 'Data mahasiswa tidak ditemukan.');
        redirect('modules/admin/mahasiswa/index.php');
    }
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'id_prodi' => (int)($_POST['id_prodi'] ?? 0),
        'nim'      => $_POST['nim'] ?? '',
        'nama'     => $_POST['nama'] ?? '',
        'email'    => $_POST['email'] ?? '',
        'no_hp'    => $_POST['no_hp'] ?? '',
        'alamat'   => $_POST['alamat'] ?? '',
        'angkatan' => (int)($_POST['angkatan'] ?? date('Y')),
        'status'   => $_POST['status'] ?? 'aktif',
        'password' => $_POST['password'] ?? '',
    ];

    try {
        if ($isEdit) {
            MahasiswaModel::update($id, $payload);
            flash('success', 'Data mahasiswa berhasil diperbarui.');
        } else {
            MahasiswaModel::create($payload);
            flash('success', 'Mahasiswa baru dan akun login berhasil dibuat.');
        }
        redirect('modules/admin/mahasiswa/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$prodiOptions = [];
foreach (ProdiModel::all() as $p) {
    $prodiOptions[$p['id_prodi']] = $p['kode_prodi'] . ' — ' . $p['nama_prodi'];
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Mahasiswa';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Username akun login mahasiswa otomatis menggunakan NIM.</p>
        </div>
        <a href="<?= e(url('modules/admin/mahasiswa/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= ui_field([
                'name'        => 'nim',
                'label'       => 'NIM (Nomor Induk Mahasiswa)',
                'value'       => $data['nim'],
                'placeholder' => 'Contoh: 2401001',
                'required'    => true,
            ]) ?>

            <?= ui_field([
                'name'        => 'id_prodi',
                'label'       => 'Program Studi',
                'type'        => 'select',
                'value'       => $data['id_prodi'],
                'placeholder' => '-- Pilih Program Studi --',
                'required'    => true,
                'options'     => $prodiOptions,
            ]) ?>
        </div>

        <?= ui_field([
            'name'        => 'nama',
            'label'       => 'Nama Lengkap Mahasiswa',
            'value'       => $data['nama'],
            'placeholder' => 'Contoh: Ahmad Fauzi',
            'required'    => true,
        ]) ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= ui_field([
                'name'     => 'angkatan',
                'label'    => 'Tahun Angkatan',
                'type'     => 'number',
                'value'    => $data['angkatan'],
                'required' => true,
                'attrs'    => 'min="2000" max="2099"',
            ]) ?>

            <?= ui_field([
                'name'     => 'status',
                'label'    => 'Status Akademik',
                'type'     => 'select',
                'value'    => $data['status'],
                'required' => true,
                'options'  => [
                    'aktif'  => 'Aktif',
                    'cuti'   => 'Cuti Akademik',
                    'lulus'  => 'Lulus',
                    'keluar' => 'Keluar / Mengundurkan Diri',
                ],
            ]) ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= ui_field([
                'name'        => 'email',
                'label'       => 'Alamat Email',
                'type'        => 'email',
                'value'       => $data['email'] ?? '',
                'placeholder' => 'nama@mhs.kampus.ac.id',
            ]) ?>

            <?= ui_field([
                'name'        => 'no_hp',
                'label'       => 'Nomor HP / WhatsApp',
                'value'       => $data['no_hp'] ?? '',
                'placeholder' => '0813xxxxxxxx',
            ]) ?>
        </div>

        <?= ui_field([
            'name'        => 'alamat',
            'label'       => 'Alamat Lengkap',
            'type'        => 'textarea',
            'value'       => $data['alamat'] ?? '',
            'placeholder' => 'Alamat tempat tinggal mahasiswa',
        ]) ?>

        <?= ui_field([
            'name'        => 'password',
            'label'       => $isEdit ? 'Password Baru (Kosongkan bila tidak diubah)' : 'Password Awal Akun',
            'type'        => 'password',
            'placeholder' => $isEdit ? 'Biarkan kosong jika tetap menggunakan password lama' : 'Kosongkan untuk menggunakan NIM sebagai password awal',
            'help'        => $isEdit ? 'Isi hanya bila mahasiswa meminta reset password.' : 'Jika dikosongkan, password bawaan adalah sama dengan NIM.',
        ]) ?>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/mahasiswa/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
