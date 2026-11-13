<?php
/**
 * Master Data Mata Kuliah — Form Tambah & Ubah
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/MataKuliahModel.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$data   = [
    'id_prodi'       => '',
    'kode_mk'        => '',
    'nama_mk'        => '',
    'sks'            => 3,
    'semester_paket' => 1,
];

if ($isEdit) {
    $found = MataKuliahModel::find($id);
    if (!$found) {
        flash('error', 'Data mata kuliah tidak ditemukan.');
        redirect('modules/admin/matkul/index.php');
    }
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'id_prodi'       => (int)($_POST['id_prodi'] ?? 0),
        'kode_mk'        => $_POST['kode_mk'] ?? '',
        'nama_mk'        => $_POST['nama_mk'] ?? '',
        'sks'            => (int)($_POST['sks'] ?? 3),
        'semester_paket' => (int)($_POST['semester_paket'] ?? 1),
    ];

    try {
        if ($isEdit) {
            MataKuliahModel::update($id, $payload);
            flash('success', 'Data mata kuliah berhasil diperbarui.');
        } else {
            MataKuliahModel::create($payload);
            flash('success', 'Mata kuliah baru berhasil ditambahkan.');
        }
        redirect('modules/admin/matkul/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$prodiOptions = [];
foreach (ProdiModel::all() as $p) {
    $prodiOptions[$p['id_prodi']] = $p['kode_prodi'] . ' — ' . $p['nama_prodi'];
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Mata Kuliah';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Pastikan kode mata kuliah unik dan jumlah SKS antara 1–6.</p>
        </div>
        <a href="<?= e(url('modules/admin/matkul/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <?= ui_field([
            'name'        => 'id_prodi',
            'label'       => 'Program Studi',
            'type'        => 'select',
            'value'       => $data['id_prodi'],
            'placeholder' => '-- Pilih Program Studi --',
            'required'    => true,
            'options'     => $prodiOptions,
        ]) ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= ui_field([
                'name'        => 'kode_mk',
                'label'       => 'Kode Mata Kuliah',
                'value'       => $data['kode_mk'],
                'placeholder' => 'Contoh: IF101',
                'required'    => true,
            ]) ?>

            <?= ui_field([
                'name'     => 'sks',
                'label'    => 'Bobot SKS (1–6)',
                'type'     => 'number',
                'value'    => $data['sks'],
                'required' => true,
                'attrs'    => 'min="1" max="6"',
            ]) ?>
        </div>

        <?= ui_field([
            'name'        => 'nama_mk',
            'label'       => 'Nama Mata Kuliah',
            'value'       => $data['nama_mk'],
            'placeholder' => 'Contoh: Algoritma dan Pemrograman',
            'required'    => true,
        ]) ?>

        <?= ui_field([
            'name'     => 'semester_paket',
            'label'    => 'Semester Paket',
            'type'     => 'select',
            'value'    => $data['semester_paket'],
            'required' => true,
            'options'  => [
                1 => 'Semester 1',
                2 => 'Semester 2',
                3 => 'Semester 3',
                4 => 'Semester 4',
                5 => 'Semester 5',
                6 => 'Semester 6',
                7 => 'Semester 7',
                8 => 'Semester 8',
            ],
        ]) ?>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/matkul/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
