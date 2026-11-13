<?php
/**
 * Halaman Input & Pembaruan Nilai oleh Dosen Pengampu
 * Menghitung Nilai Akhir (Tugas 30% + UTS 30% + UAS 40%), Huruf, dan Bobot secara otomatis
 * via live preview JS di klien serta divalidasi ulang di server lewat NilaiModel::simpan().
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/DosenModel.php';
require_once __DIR__ . '/../../../models/KelasModel.php';
require_once __DIR__ . '/../../../models/NilaiModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';

$user    = require_role(['dosen']);
$idDosen = (int)($user['id_dosen'] ?? 0);

$kelasList = DosenModel::kelasDiampu($idDosen);
$idKelas   = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : (!empty($kelasList) ? (int)$kelasList[0]['id_kelas'] : 0);

$detailKelas = null;
$pesertaList = [];

if ($idKelas > 0) {
    $k = KelasModel::find($idKelas);
    if (!$k || (int)$k['id_dosen'] !== $idDosen) {
        flash('error', 'Akses ditolak: Anda hanya dapat mengubah nilai pada kelas yang Anda ampu.');
        redirect('modules/dosen/nilai/index.php');
    }
    $detailKelas = $k;
}

if (is_post() && $detailKelas) {
    csrf_verify();
    $nilaiInput = $_POST['nilai'] ?? [];

    if (!is_array($nilaiInput) || empty($nilaiInput)) {
        flash('warning', 'Tidak ada data nilai yang dikirimkan.');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $savedCount = 0;
            foreach ($nilaiInput as $idKrs => $komponen) {
                $idKrs = (int)$idKrs;
                $tugas = isset($komponen['tugas']) && $komponen['tugas'] !== '' ? (float)$komponen['tugas'] : 0.0;
                $uts   = isset($komponen['uts'])   && $komponen['uts'] !== ''   ? (float)$komponen['uts']   : 0.0;
                $uas   = isset($komponen['uas'])   && $komponen['uas'] !== ''   ? (float)$komponen['uas']   : 0.0;

                NilaiModel::simpan($idKrs, $tugas, $uts, $uas, $idDosen);
                $savedCount++;
            }
            $pdo->commit();
            flash('success', "Nilai untuk {$savedCount} mahasiswa berhasil disimpan dan dikonversi.");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', $e->getMessage());
        }
    }
    redirect('modules/dosen/nilai/index.php?id_kelas=' . (int)$detailKelas['id_kelas']);
}

if ($detailKelas) {
    $pesertaList = NilaiModel::pesertaKelas((int)$detailKelas['id_kelas']);
}

$pageTitle = 'Input Nilai Mahasiswa';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Input Nilai Perkuliahan</h1>
        <p class="text-xs text-slate-500">Bobot perhitungan otomatis: Tugas 30% &middot; UTS 30% &middot; UAS 40% (Rentang nilai 0–100).</p>
    </div>

    <!-- Pemilih Kelas -->
    <form method="GET" action="" class="flex items-center gap-2">
        <label for="select_id_kelas" class="text-xs font-medium text-slate-600">Pilih Kelas:</label>
        <select id="select_id_kelas" name="id_kelas" onchange="this.form.submit()" class="select select-bordered select-sm h-9 rounded-lg bg-white border-slate-300 text-sm">
            <?php if (empty($kelasList)): ?>
                <option value="">-- Belum Ada Kelas --</option>
            <?php else: ?>
                <?php foreach ($kelasList as $kl): ?>
                    <option value="<?= (int)$kl['id_kelas'] ?>" <?= ($idKelas === (int)$kl['id_kelas']) ? 'selected' : '' ?>>
                        <?= e($kl['kode_mk'] . ' — ' . $kl['nama_mk'] . ' (Kelas ' . $kl['nama_kelas'] . ' · ' . $kl['tahun'] . ' ' . ucfirst($kl['semester']) . ')') ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </form>
</div>

<?php if (!$detailKelas): ?>
    <div class="bg-white border border-slate-200 rounded-lg p-10 text-center text-slate-500 text-sm">
        Silakan pilih kelas yang Anda ampu terlebih dahulu.
    </div>
<?php else: ?>
    <!-- Info Kelas -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <span class="text-slate-400 block">Mata Kuliah</span>
            <strong class="text-slate-800 text-sm"><?= e($detailKelas['kode_mk'] . ' — ' . $detailKelas['nama_mk']) ?></strong>
        </div>
        <div>
            <span class="text-slate-400 block">Kelas &amp; Bobot</span>
            <strong class="text-slate-800 text-sm">Kelas <?= e($detailKelas['nama_kelas']) ?> &middot; <?= (int)$detailKelas['sks'] ?> SKS</strong>
        </div>
        <div>
            <span class="text-slate-400 block">Jadwal &amp; Ruang</span>
            <strong class="text-slate-800 text-sm"><?= e($detailKelas['hari']) ?>, <?= e(substr($detailKelas['jam_mulai'], 0, 5) . '–' . substr($detailKelas['jam_selesai'], 0, 5)) ?> (<?= e($detailKelas['ruang']) ?>)</strong>
        </div>
        <div>
            <span class="text-slate-400 block">Tahun Ajaran</span>
            <strong class="text-slate-800 text-sm"><?= e($detailKelas['tahun'] . ' ' . ucfirst($detailKelas['semester'])) ?></strong>
        </div>
    </div>

    <?php if (empty($pesertaList)): ?>
        <div class="bg-white border border-slate-200 rounded-lg p-10 text-center text-slate-500 text-sm">
            Belum ada mahasiswa dengan KRS berstatus <strong>disetujui</strong> pada kelas ini.
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="overflow-x-auto bg-white border border-slate-200 rounded-lg">
                <table class="table table-zebra w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="font-semibold py-3 px-4 w-12">No</th>
                            <th class="font-semibold py-3 px-4">NIM &amp; Nama Mahasiswa</th>
                            <th class="font-semibold py-3 px-4 w-32 text-center">Tugas (30%)</th>
                            <th class="font-semibold py-3 px-4 w-32 text-center">UTS (30%)</th>
                            <th class="font-semibold py-3 px-4 w-32 text-center">UAS (40%)</th>
                            <th class="font-semibold py-3 px-4 text-center">Nilai Akhir</th>
                            <th class="font-semibold py-3 px-4 text-center">Huruf</th>
                            <th class="font-semibold py-3 px-4 text-center">Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesertaList as $idx => $m): ?>
                            <?php
                            $idKrs = (int)$m['id_krs'];
                            $tugas = $m['tugas'] !== null ? (float)$m['tugas'] : 0;
                            $uts   = $m['uts']   !== null ? (float)$m['uts']   : 0;
                            $uas   = $m['uas']   !== null ? (float)$m['uas']   : 0;
                            $na    = $m['nilai_akhir'] !== null ? number_format((float)$m['nilai_akhir'], 2) : '0.00';
                            $huruf = $m['huruf'] ?? 'E';
                            $bobot = $m['bobot'] !== null ? number_format((float)$m['bobot'], 2) : '0.00';
                            ?>
                            <tr data-nilai-row class="border-b border-slate-100 last:border-none">
                                <td class="py-3 px-4 text-slate-500"><?= $idx + 1 ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800"><?= e($m['nama_mahasiswa']) ?></div>
                                    <div class="text-xs text-slate-500"><code><?= e($m['nim']) ?></code> &middot; <?= e($m['kode_prodi']) ?></div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="nilai[<?= $idKrs ?>][tugas]"
                                        value="<?= e($tugas) ?>"
                                        required
                                        class="inp-tugas input input-bordered input-xs h-8 w-full text-center rounded bg-white border-slate-300 text-sm"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="nilai[<?= $idKrs ?>][uts]"
                                        value="<?= e($uts) ?>"
                                        required
                                        class="inp-uts input input-bordered input-xs h-8 w-full text-center rounded bg-white border-slate-300 text-sm"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="nilai[<?= $idKrs ?>][uas]"
                                        value="<?= e($uas) ?>"
                                        required
                                        class="inp-uas input input-bordered input-xs h-8 w-full text-center rounded bg-white border-slate-300 text-sm"
                                    >
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-800">
                                    <span class="out-nilai-akhir"><?= e($na) ?></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="out-huruf font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-800"><?= e($huruf) ?></span>
                                </td>
                                <td class="py-3 px-4 text-center text-slate-600">
                                    <span class="out-bobot"><?= e($bobot) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500">
                    Skala Konversi: &ge;80 A (4.00) &middot; &ge;75 B+ (3.50) &middot; &ge;70 B (3.00) &middot; &ge;65 C+ (2.50) &middot; &ge;60 C (2.00) &middot; &ge;50 D (1.00) &middot; &lt;50 E (0.00)
                </div>
                <button type="submit" class="btn btn-sm h-10 px-6 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-semibold">
                    Simpan Seluruh Nilai Kelas Ini
                </button>
            </div>
        </form>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
