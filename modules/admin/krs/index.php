<?php
/**
 * Verifikasi KRS oleh Admin
 * Daftar KRS diajukan/disetujui/ditolak, filter per tahun ajaran, prodi, mahasiswa,
 * serta tombol Setujui/Tolak per baris maupun massal.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/KrsModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../../models/ProdiModel.php';

require_role(['admin']);

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'single_status') {
            $idKrs  = (int)($_POST['id_krs'] ?? 0);
            $status = $_POST['status'] ?? '';
            KrsModel::setStatus($idKrs, $status);
            flash('success', "Status KRS berhasil diubah menjadi '{$status}'.");
        } elseif ($action === 'bulk_status') {
            $ids    = $_POST['krs_ids'] ?? [];
            $status = $_POST['bulk_status_val'] ?? '';
            if (empty($ids) || !is_array($ids)) {
                throw new Exception('Pilih minimal satu baris KRS terlebih dahulu.');
            }
            $count = KrsModel::setStatusMassal($ids, $status);
            flash('success', "{$count} pengajuan KRS berhasil diubah menjadi '{$status}'.");
        }
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }

    $qs = http_build_query([
        'id_ta'    => $_GET['id_ta'] ?? '',
        'id_prodi' => $_GET['id_prodi'] ?? '',
        'status'   => $_GET['status'] ?? '',
        'q'        => $_GET['q'] ?? '',
    ]);
    redirect('modules/admin/krs/index.php' . ($qs !== '' ? '?' . $qs : ''));
}

$taAktif   = TahunAjaranModel::aktif();
$idTa      = isset($_GET['id_ta']) ? ($_GET['id_ta'] !== '' ? (int)$_GET['id_ta'] : null) : ($taAktif ? (int)$taAktif['id_ta'] : null);
$idProdi   = isset($_GET['id_prodi']) && $_GET['id_prodi'] !== '' ? (int)$_GET['id_prodi'] : null;
$status    = trim($_GET['status'] ?? '');
$q         = trim($_GET['q'] ?? '');

$daftarKrs = KrsModel::daftarAdmin($idTa, $idProdi, $status, $q);
$taList    = TahunAjaranModel::all();
$prodiList = ProdiModel::all();

$pageTitle = 'Verifikasi KRS Mahasiswa';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Verifikasi KRS Mahasiswa</h1>
        <p class="text-xs text-slate-500">Periksa dan setujui rencana studi mahasiswa agar mahasiswa terdaftar resmi di kelas dosen.</p>
    </div>
</div>

<!-- Filter Pencarian -->
<form method="GET" action="" class="bg-white border border-slate-200 rounded-lg p-3.5 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2">
    <div class="sm:col-span-3">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari NIM, nama, atau mata kuliah..." class="input input-bordered input-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
    </div>
    <div class="sm:col-span-3">
        <select name="id_ta" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($taList as $ta): ?>
                <option value="<?= (int)$ta['id_ta'] ?>" <?= ($idTa === (int)$ta['id_ta']) ? 'selected' : '' ?>>
                    <?= e($ta['tahun'] . ' - ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-2">
        <select name="id_prodi" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Prodi</option>
            <?php foreach ($prodiList as $p): ?>
                <option value="<?= (int)$p['id_prodi'] ?>" <?= ($idProdi === (int)$p['id_prodi']) ? 'selected' : '' ?>>
                    <?= e($p['kode_prodi']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-2">
        <select name="status" class="select select-bordered select-sm h-9 w-full rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Status</option>
            <option value="diajukan" <?= ($status === 'diajukan') ? 'selected' : '' ?>>Diajukan</option>
            <option value="disetujui" <?= ($status === 'disetujui') ? 'selected' : '' ?>>Disetujui</option>
            <option value="ditolak" <?= ($status === 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
        </select>
    </div>
    <div class="sm:col-span-2 flex gap-1.5">
        <button type="submit" class="btn btn-sm h-9 flex-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white border-none">Filter</button>
        <a href="<?= e(url('modules/admin/krs/index.php?id_ta=')) ?>" class="btn btn-sm h-9 px-2.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Reset</a>
    </div>
</form>

<!-- Form Aksi Massal + Tabel KRS -->
<form method="POST" action="" id="form-bulk-krs">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bulk_status">
    <input type="hidden" name="bulk_status_val" id="bulk-status-val" value="disetujui">

    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div class="text-xs text-slate-600">
            Menampilkan <strong><?= count($daftarKrs) ?></strong> baris pengajuan KRS.
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" onclick="document.getElementById('bulk-status-val').value='disetujui'" class="btn btn-xs h-8 px-3 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white border-none flex items-center gap-1">
                <?= ui_icon('check', 'w-3.5 h-3.5') ?>
                <span>Setujui Terpilih</span>
            </button>
            <button type="submit" onclick="document.getElementById('bulk-status-val').value='ditolak'" class="btn btn-xs h-8 px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white border-none flex items-center gap-1">
                <?= ui_icon('x', 'w-3.5 h-3.5') ?>
                <span>Tolak Terpilih</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto bg-white border border-slate-200 rounded-lg">
        <table class="table table-zebra w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                <tr>
                    <th class="w-10 py-3 px-4">
                        <input type="checkbox" id="check-all-krs" class="checkbox checkbox-xs rounded border-slate-300" aria-label="Pilih Semua">
                    </th>
                    <th class="font-semibold py-3 px-4">Mahasiswa</th>
                    <th class="font-semibold py-3 px-4">Mata Kuliah &amp; Kelas</th>
                    <th class="font-semibold py-3 px-4">Jadwal &amp; Dosen</th>
                    <th class="font-semibold py-3 px-4">Status</th>
                    <th class="font-semibold py-3 px-4 text-right">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarKrs)): ?>
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">
                            Belum ada data pengajuan KRS yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarKrs as $row): ?>
                        <?php $jam = substr($row['jam_mulai'], 0, 5) . '–' . substr($row['jam_selesai'], 0, 5); ?>
                        <tr class="hover:bg-slate-50/80 border-b border-slate-100 last:border-none">
                            <td class="py-3 px-4">
                                <input type="checkbox" name="krs_ids[]" value="<?= (int)$row['id_krs'] ?>" class="check-item-krs checkbox checkbox-xs rounded border-slate-300">
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800"><?= e($row['nama_mahasiswa']) ?></div>
                                <div class="text-xs text-slate-500"><code><?= e($row['nim']) ?></code> &middot; <?= e($row['kode_prodi']) ?></div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800"><?= e($row['kode_mk']) ?> &mdash; <?= e($row['nama_mk']) ?></div>
                                <div class="text-xs text-slate-500">Kelas <?= e($row['nama_kelas']) ?> &middot; <?= (int)$row['sks'] ?> SKS &middot; TA <?= e($row['tahun']) ?> (<?= e(ucfirst($row['semester'])) ?>)</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-slate-800"><?= e($row['hari']) ?>, <?= e($jam) ?> (<?= e($row['ruang']) ?>)</div>
                                <div class="text-xs text-slate-500"><?= e($row['nama_dosen']) ?></div>
                            </td>
                            <td class="py-3 px-4">
                                <?= ui_badge(ucfirst($row['status'])) ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <?php if ($row['status'] !== 'disetujui'): ?>
                                        <button type="button" onclick="submitSingleKrs(<?= (int)$row['id_krs'] ?>, 'disetujui')" class="btn btn-xs h-7 px-2.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-none">
                                            Setujui
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($row['status'] !== 'ditolak'): ?>
                                        <button type="button" onclick="submitSingleKrs(<?= (int)$row['id_krs'] ?>, 'ditolak')" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">
                                            Tolak
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Form Tersembunyi untuk Aksi Per Baris -->
<form method="POST" action="" id="form-single-krs" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="single_status">
    <input type="hidden" name="id_krs" id="single-id-krs" value="">
    <input type="hidden" name="status" id="single-status-krs" value="">
</form>

<script>
function submitSingleKrs(idKrs, status) {
    document.getElementById('single-id-krs').value = idKrs;
    document.getElementById('single-status-krs').value = status;
    document.getElementById('form-single-krs').submit();
}
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
