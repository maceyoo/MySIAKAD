<?php
/**
 * Master Data Tahun Ajaran — Form Tambah & Ubah
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';

require_role(['admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$data   = [
    'tahun'    => date('Y') . '/' . (date('Y') + 1),
    'semester' => 'ganjil',
    'aktif'    => 0,
];

if ($isEdit) {
    $found = TahunAjaranModel::find($id);
    if (!$found) {
        flash('error', 'Data tahun ajaran tidak ditemukan.');
        redirect('modules/admin/tahun_ajaran/index.php');
    }
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'tahun'    => $_POST['tahun'] ?? '',
        'semester' => $_POST['semester'] ?? 'ganjil',
        'aktif'    => !empty($_POST['aktif']) ? 1 : 0,
    ];

    try {
        if ($isEdit) {
            TahunAjaranModel::update($id, $payload);
            flash('success', 'Data tahun ajaran berhasil diperbarui.');
        } else {
            TahunAjaranModel::create($payload);
            flash('success', 'Tahun ajaran baru berhasil ditambahkan.');
        }
        redirect('modules/admin/tahun_ajaran/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Tahun Ajaran';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Mengaktifkan tahun ajaran ini akan otomatis menonaktifkan tahun ajaran lainnya.</p>
        </div>
        <a href="<?= e(url('modules/admin/tahun_ajaran/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <?= ui_field([
            'name'        => 'tahun',
            'label'       => 'Tahun Akademik',
            'value'       => $data['tahun'],
            'placeholder' => 'Contoh: 2026/2027',
            'required'    => true,
        ]) ?>

        <?= ui_field([
            'name'     => 'semester',
            'label'    => 'Semester',
            'type'     => 'select',
            'value'    => $data['semester'],
            'required' => true,
            'options'  => [
                'ganjil' => 'Ganjil',
                'genap'  => 'Genap',
            ],
        ]) ?>

        <div class="form-control pt-1">
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" name="aktif" value="1" class="checkbox checkbox-sm rounded border-slate-300" <?= !empty($data['aktif']) ? 'checked' : '' ?>>
                <span class="label-text text-sm font-medium text-slate-700">Jadikan sebagai Tahun Ajaran Aktif saat ini</span>
            </label>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/tahun_ajaran/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
