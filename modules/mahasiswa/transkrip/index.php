<?php
/**
 * Halaman Transkrip Akademik Sementara Mahasiswa + Fitur Cetak A4 Resmi
 * Menampilkan seluruh mata kuliah yang telah dinilai lintas semester beserta perhitungan IPK.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/MahasiswaModel.php';
require_once __DIR__ . '/../../../models/NilaiModel.php';

$user   = require_role(['mahasiswa']);
$idMhs  = (int)($user['id_mahasiswa'] ?? 0);
$profil = MahasiswaModel::find($idMhs);

$transkripRows = NilaiModel::transkrip($idMhs);
$ipk           = NilaiModel::ipk($idMhs);

$totalSksKumulatif  = 0;
$totalMutuKumulatif = 0.0;
foreach ($transkripRows as $r) {
    $totalSksKumulatif  += (int)$r['sks'];
    $totalMutuKumulatif += (float)$r['mutu'];
}

if ($ipk >= 3.51) {
    $predikat = 'Dengan Pujian (Cum Laude)';
} elseif ($ipk >= 3.01) {
    $predikat = 'Sangat Memuaskan';
} elseif ($ipk >= 2.76) {
    $predikat = 'Memuaskan';
} elseif ($ipk >= 2.00) {
    $predikat = 'Cukup';
} else {
    $predikat = '-';
}

$pageTitle = 'Transkrip Akademik';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<!-- Toolbar Layar -->
<div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Transkrip Nilai Akademik</h1>
        <p class="text-xs text-slate-500">Rekapitulasi nilai seluruh semester dan Indeks Prestasi Kumulatif (IPK).</p>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-sm h-9 px-4 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
        <?= ui_icon('printer', 'w-4 h-4') ?>
        <span>Cetak Transkrip</span>
    </button>
</div>

<!-- Kontainer Dokumen Transkrip -->
<div class="print-container bg-white border border-slate-200 rounded-lg p-6 lg:p-8">
    <!-- Kop Resmi Kampus -->
    <div class="border-b-2 border-slate-800 pb-4 mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900 uppercase tracking-wide"><?= e(NAMA_KAMPUS) ?></h2>
            <p class="text-xs text-slate-600"><?= e(ALAMAT_KAMPUS) ?></p>
        </div>
        <div class="text-right">
            <div class="text-sm font-bold text-slate-800 uppercase">Transkrip Nilai Akademik</div>
            <div class="text-xs text-slate-500">Dokumen Rekapitulasi Studi</div>
        </div>
    </div>

    <!-- Identitas Mahasiswa -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-1.5 gap-x-8 text-xs mb-6">
        <div class="flex justify-between sm:justify-start sm:gap-3">
            <span class="text-slate-500 w-32 shrink-0">Nama Mahasiswa</span>
            <span class="font-semibold text-slate-800">: <?= e($profil['nama'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between sm:justify-start sm:gap-3">
            <span class="text-slate-500 w-32 shrink-0">Program Studi</span>
            <span class="font-semibold text-slate-800">: <?= e(($profil['nama_prodi'] ?? '-') . ' (' . ($profil['jenjang'] ?? 'S1') . ')') ?></span>
        </div>
        <div class="flex justify-between sm:justify-start sm:gap-3">
            <span class="text-slate-500 w-32 shrink-0">NIM</span>
            <span class="font-semibold text-slate-800">: <?= e($profil['nim'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between sm:justify-start sm:gap-3">
            <span class="text-slate-500 w-32 shrink-0">Tahun Angkatan</span>
            <span class="font-semibold text-slate-800">: <?= e($profil['angkatan'] ?? '-') ?></span>
        </div>
    </div>

    <!-- Tabel Transkrip -->
    <div class="overflow-x-auto">
        <table class="table print-table w-full text-sm border border-slate-200">
            <thead class="bg-slate-50 text-slate-700 border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-3 text-center w-10">No</th>
                    <th class="py-2.5 px-3">Kode MK</th>
                    <th class="py-2.5 px-3">Nama Mata Kuliah</th>
                    <th class="py-2.5 px-3">Tahun Ajaran</th>
                    <th class="py-2.5 px-3 text-center">SKS (K)</th>
                    <th class="py-2.5 px-3 text-center">Nilai Akhir</th>
                    <th class="py-2.5 px-3 text-center">Huruf</th>
                    <th class="py-2.5 px-3 text-center">Bobot (B)</th>
                    <th class="py-2.5 px-3 text-center">Mutu (K&times;B)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transkripRows)): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-500">
                            Belum ada mata kuliah yang telah dinilai pada riwayat akademik Anda.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transkripRows as $idx => $r): ?>
                        <tr class="border-b border-slate-200">
                            <td class="py-2.5 px-3 text-center"><?= $idx + 1 ?></td>
                            <td class="py-2.5 px-3 font-mono font-semibold"><?= e($r['kode_mk']) ?></td>
                            <td class="py-2.5 px-3 font-medium"><?= e($r['nama_mk']) ?></td>
                            <td class="py-2.5 px-3 text-xs text-slate-600"><?= e($r['tahun'] . ' (' . ucfirst($r['semester']) . ')') ?></td>
                            <td class="py-2.5 px-3 text-center font-semibold"><?= (int)$r['sks'] ?></td>
                            <td class="py-2.5 px-3 text-center"><?= e(number_format((float)$r['nilai_akhir'], 2)) ?></td>
                            <td class="py-2.5 px-3 text-center font-bold"><?= e($r['huruf']) ?></td>
                            <td class="py-2.5 px-3 text-center"><?= e(number_format((float)$r['bobot'], 2)) ?></td>
                            <td class="py-2.5 px-3 text-center font-semibold"><?= e(number_format((float)$r['mutu'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-300">
                    <td colspan="4" class="py-2.5 px-3 text-right">Total Kumulatif</td>
                    <td class="py-2.5 px-3 text-center"><?= $totalSksKumulatif ?></td>
                    <td colspan="3" class="py-2.5 px-3 text-right">Total Angka Mutu (&Sigma; K&times;B)</td>
                    <td class="py-2.5 px-3 text-center"><?= number_format($totalMutuKumulatif, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Ringkasan IPK & Pengesahan -->
    <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6">
        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-xs space-y-1">
            <div>Total SKS Lulus/Diambil: <strong><?= $totalSksKumulatif ?> SKS</strong></div>
            <div>Total Angka Mutu Kumulatif: <strong><?= number_format($totalMutuKumulatif, 2) ?></strong></div>
            <div>Predikat Sementara: <strong><?= e($predikat) ?></strong></div>
            <div class="text-sm pt-1">
                Indeks Prestasi Kumulatif (IPK): <strong class="text-teal-800 text-base"><?= number_format($ipk, 2) ?></strong>
            </div>
        </div>

        <div class="text-xs text-slate-700 text-right sm:min-w-[220px]">
            <div>Dicetak pada: <?= e(format_tanggal(date('Y-m-d'))) ?></div>
            <div class="mt-1 font-medium">Wakil Dekan Bidang Akademik</div>
            <div class="h-14"></div>
            <div class="font-bold underline">( ________________________ )</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
