<?php
/**
 * Master Data Program Studi — Form Tambah & Ubah
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$data   = [
    'kode_prodi' => '',
    'nama_prodi' => '',
    'jenjang'    => 'S1',
];

if ($isEdit) {
    $found = ProdiModel::find($id);
    if (!$found) {
        flash('error', 'Data program studi tidak ditemukan.');
        redirect('modules/admin/prodi/index.php');
    }
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'kode_prodi' => $_POST['kode_prodi'] ?? '',
        'nama_prodi' => $_POST['nama_prodi'] ?? '',
        'jenjang'    => $_POST['jenjang'] ?? 'S1',
    ];

    try {
        if ($isEdit) {
            ProdiModel::update($id, $payload);
            flash('success', 'Data program studi berhasil diperbarui.');
        } else {
            ProdiModel::create($payload);
            flash('success', 'Program studi baru berhasil ditambahkan.');
        }
        redirect('modules/admin/prodi/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Program Studi';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Isi informasi program studi secara lengkap.</p>
        </div>
        <a href="<?= e(url('modules/admin/prodi/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <?= ui_field([
            'name'        => 'kode_prodi',
            'label'       => 'Kode Program Studi',
            'value'       => $data['kode_prodi'],
            'placeholder' => 'Contoh: IF atau SI',
            'required'    => true,
            'help'        => 'Maksimal 10 karakter, unik untuk setiap program studi.',
        ]) ?>

        <?= ui_field([
            'name'        => 'nama_prodi',
            'label'       => 'Nama Program Studi',
            'value'       => $data['nama_prodi'],
            'placeholder' => 'Contoh: Teknik Informatika',
            'required'    => true,
        ]) ?>

        <?= ui_field([
            'name'     => 'jenjang',
            'label'    => 'Jenjang Pendidikan',
            'type'     => 'select',
            'value'    => $data['jenjang'],
            'required' => true,
            'options'  => [
                'D3' => 'D3 (Diploma Tiga)',
                'D4' => 'D4 (Sarjana Terapan)',
                'S1' => 'S1 (Sarjana)',
                'S2' => 'S2 (Magister)',
            ],
        ]) ?>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/prodi/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
