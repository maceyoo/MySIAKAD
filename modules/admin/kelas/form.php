<?php
/**
 * Master Data Kelas & Jadwal — Form Tambah & Ubah
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/KelasModel.php';
require_once __DIR__ . '/../../../models/MataKuliahModel.php';
require_once __DIR__ . '/../../../models/DosenModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';

require_role(['admin']);

$taAktif = TahunAjaranModel::aktif();
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $id > 0;
$data    = [
    'id_mk'       => '',
    'id_dosen'    => '',
    'id_ta'       => $taAktif ? (int)$taAktif['id_ta'] : '',
    'nama_kelas'  => 'A',
    'hari'        => 'Senin',
    'jam_mulai'   => '08:00',
    'jam_selesai' => '10:30',
    'ruang'       => 'R.201',
    'kuota'       => 35,
];

if ($isEdit) {
    $found = KelasModel::find($id);
    if (!$found) {
        flash('error', 'Data kelas tidak ditemukan.');
        redirect('modules/admin/kelas/index.php');
    }
    $found['jam_mulai']   = substr($found['jam_mulai'], 0, 5);
    $found['jam_selesai'] = substr($found['jam_selesai'], 0, 5);
    $data = $found;
}

if (is_post()) {
    csrf_verify();
    $payload = [
        'id_mk'       => (int)($_POST['id_mk'] ?? 0),
        'id_dosen'    => (int)($_POST['id_dosen'] ?? 0),
        'id_ta'       => (int)($_POST['id_ta'] ?? 0),
        'nama_kelas'  => $_POST['nama_kelas'] ?? 'A',
        'hari'        => $_POST['hari'] ?? 'Senin',
        'jam_mulai'   => $_POST['jam_mulai'] ?? '',
        'jam_selesai' => $_POST['jam_selesai'] ?? '',
        'ruang'       => $_POST['ruang'] ?? '',
        'kuota'       => (int)($_POST['kuota'] ?? 35),
    ];

    try {
        if ($isEdit) {
            KelasModel::update($id, $payload);
            flash('success', 'Data kelas perkuliahan berhasil diperbarui.');
        } else {
            KelasModel::create($payload);
            flash('success', 'Kelas perkuliahan baru berhasil dibuka.');
        }
        redirect('modules/admin/kelas/index.php');
    } catch (Exception $e) {
        flash('error', $e->getMessage());
        $data = array_merge($data, $payload);
    }
}

$mkOptions = [];
foreach (MataKuliahModel::all() as $mk) {
    $mkOptions[$mk['id_mk']] = $mk['kode_mk'] . ' — ' . $mk['nama_mk'] . ' (' . $mk['sks'] . ' SKS · ' . $mk['kode_prodi'] . ')';
}

$dosenOptions = [];
foreach (DosenModel::all() as $d) {
    $dosenOptions[$d['id_dosen']] = $d['nama'] . ' (' . $d['nidn'] . ' · ' . $d['kode_prodi'] . ')';
}

$taOptions = [];
foreach (TahunAjaranModel::all() as $ta) {
    $taOptions[$ta['id_ta']] = $ta['tahun'] . ' — Semester ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '');
}

$hariOptions = [];
foreach (KelasModel::HARI_VALID as $h) {
    $hariOptions[$h] = $h;
}

$pageTitle = ($isEdit ? 'Ubah' : 'Tambah') . ' Kelas Perkuliahan';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
            <p class="text-xs text-slate-500">Atur mata kuliah, dosen pengampu, waktu, ruang, dan kapasitas kelas.</p>
        </div>
        <a href="<?= e(url('modules/admin/kelas/index.php')) ?>" class="btn btn-sm h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none flex items-center gap-1.5">
            <?= ui_icon('arrow-left', 'w-4 h-4') ?>
            <span>Kembali</span>
        </a>
    </div>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
        <?= csrf_field() ?>

        <?= ui_field([
            'name'        => 'id_ta',
            'label'       => 'Tahun Ajaran',
            'type'        => 'select',
            'value'       => $data['id_ta'],
            'placeholder' => '-- Pilih Tahun Ajaran --',
            'required'    => true,
            'options'     => $taOptions,
        ]) ?>

        <?= ui_field([
            'name'        => 'id_mk',
            'label'       => 'Mata Kuliah',
            'type'        => 'select',
            'value'       => $data['id_mk'],
            'placeholder' => '-- Pilih Mata Kuliah --',
            'required'    => true,
            'options'     => $mkOptions,
        ]) ?>

        <?= ui_field([
            'name'        => 'id_dosen',
            'label'       => 'Dosen Pengampu',
            'type'        => 'select',
            'value'       => $data['id_dosen'],
            'placeholder' => '-- Pilih Dosen Pengampu --',
            'required'    => true,
            'options'     => $dosenOptions,
        ]) ?>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= ui_field([
                'name'        => 'nama_kelas',
                'label'       => 'Nama Kelas',
                'value'       => $data['nama_kelas'],
                'placeholder' => 'Contoh: A, B, atau C',
                'required'    => true,
            ]) ?>

            <?= ui_field([
                'name'        => 'ruang',
                'label'       => 'Ruang Kuliah',
                'value'       => $data['ruang'],
                'placeholder' => 'Contoh: R.201 atau Lab-DB',
                'required'    => true,
            ]) ?>

            <?= ui_field([
                'name'     => 'kuota',
                'label'    => 'Kuota Peserta',
                'type'     => 'number',
                'value'    => $data['kuota'],
                'required' => true,
                'attrs'    => 'min="1" max="500"',
            ]) ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= ui_field([
                'name'     => 'hari',
                'label'    => 'Hari Perkuliahan',
                'type'     => 'select',
                'value'    => $data['hari'],
                'required' => true,
                'options'  => $hariOptions,
            ]) ?>

            <?= ui_field([
                'name'     => 'jam_mulai',
                'label'    => 'Jam Mulai',
                'type'     => 'time',
                'value'    => $data['jam_mulai'],
                'required' => true,
            ]) ?>

            <?= ui_field([
                'name'     => 'jam_selesai',
                'label'    => 'Jam Selesai',
                'type'     => 'time',
                'value'    => $data['jam_selesai'],
                'required' => true,
            ]) ?>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="<?= e(url('modules/admin/kelas/index.php')) ?>" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</a>
            <button type="submit" class="btn btn-sm h-9 px-5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
