<?php
/**
 * Halaman Kartu Hasil Studi (KHS) Mahasiswa + Fitur Cetak A4 Resmi
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/MahasiswaModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../../models/NilaiModel.php';

$user   = require_role(['mahasiswa']);
$idMhs  = (int)($user['id_mahasiswa'] ?? 0);
$profil = MahasiswaModel::find($idMhs);

$taAktif = TahunAjaranModel::aktif();
$taList  = TahunAjaranModel::all();

$idTa = isset($_GET['id_ta']) && $_GET['id_ta'] !== ''
    ? (int)$_GET['id_ta']
    : ($taAktif ? (int)$taAktif['id_ta'] : (!empty($taList) ? (int)$taList[0]['id_ta'] : 0));

$taTerpilih = $idTa > 0 ? TahunAjaranModel::find($idTa) : null;
$khsRows    = $idTa > 0 ? NilaiModel::khs($idMhs, $idTa) : [];
$ips        = $idTa > 0 ? NilaiModel::ips($idMhs, $idTa) : 0.00;

$totalSks  = 0;
$totalMutu = 0.0;
foreach ($khsRows as $r) {
    $totalSks  += (int)$r['sks'];
    if ($r['bobot'] !== null) {
        $totalMutu += (float)$r['mutu'];
    }
}

$pageTitle = 'Kartu Hasil Studi (KHS)';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<!-- Toolbar Layar (Disembunyikan saat Cetak) -->
<div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Kartu Hasil Studi (KHS)</h1>
        <p class="text-xs text-slate-500">Daftar nilai mata kuliah dan Indeks Prestasi Semester (IPS).</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" action="" class="flex items-center gap-2">
            <select name="id_ta" onchange="this.form.submit()" class="select select-bordered select-sm h-9 rounded-lg bg-white border-slate-300 text-sm">
                <?php foreach ($taList as $ta): ?>
                    <option value="<?= (int)$ta['id_ta'] ?>" <?= ($idTa === (int)$ta['id_ta']) ? 'selected' : '' ?>>
                        <?= e($ta['tahun'] . ' — Semester ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <button type="button" onclick="window.print()" class="btn btn-sm h-9 px-4 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-medium flex items-center gap-1.5">
            <?= ui_icon('printer', 'w-4 h-4') ?>
            <span>Cetak KHS</span>
        </button>
    </div>
</div>

<!-- Kontainer Dokumen KHS (Tampil di Layar & Rapi Satu Halaman A4 saat Cetak) -->
<div class="print-container bg-white border border-slate-200 rounded-lg p-6 lg:p-8">
    <!-- Kop Resmi Kampus -->
    <div class="border-b-2 border-slate-800 pb-4 mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900 uppercase tracking-wide"><?= e(NAMA_KAMPUS) ?></h2>
            <p class="text-xs text-slate-600"><?= e(ALAMAT_KAMPUS) ?></p>
        </div>
        <div class="text-right">
            <div class="text-sm font-bold text-slate-800 uppercase">Kartu Hasil Studi (KHS)</div>
            <div class="text-xs text-slate-500">
                TA <?= e($taTerpilih['tahun'] ?? '-') ?> &middot; Semester <?= e(ucfirst($taTerpilih['semester'] ?? '-')) ?>
            </div>
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
            <span class="text-slate-500 w-32 shrink-0">Tahun Akademik</span>
            <span class="font-semibold text-slate-800">: <?= e(($taTerpilih['tahun'] ?? '-') . ' (' . ucfirst($taTerpilih['semester'] ?? '-') . ')') ?></span>
        </div>
    </div>

    <!-- Tabel Nilai KHS -->
    <div class="overflow-x-auto">
        <table class="table print-table w-full text-sm border border-slate-200">
            <thead class="bg-slate-50 text-slate-700 border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-3 text-center w-10">No</th>
                    <th class="py-2.5 px-3">Kode MK</th>
                    <th class="py-2.5 px-3">Nama Mata Kuliah</th>
                    <th class="py-2.5 px-3 text-center">Kelas</th>
                    <th class="py-2.5 px-3 text-center">SKS (K)</th>
                    <th class="py-2.5 px-3 text-center">Tugas</th>
                    <th class="py-2.5 px-3 text-center">UTS</th>
                    <th class="py-2.5 px-3 text-center">UAS</th>
                    <th class="py-2.5 px-3 text-center">Nilai Akhir</th>
                    <th class="py-2.5 px-3 text-center">Huruf</th>
                    <th class="py-2.5 px-3 text-center">Bobot (B)</th>
                    <th class="py-2.5 px-3 text-center">Mutu (K&times;B)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($khsRows)): ?>
                    <tr>
                        <td colspan="12" class="py-8 text-center text-slate-500">
                            Belum ada mata kuliah berstatus disetujui pada semester ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($khsRows as $idx => $r): ?>
                        <?php $dinilai = ($r['nilai_akhir'] !== null); ?>
                        <tr class="border-b border-slate-200">
                            <td class="py-2.5 px-3 text-center"><?= $idx + 1 ?></td>
                            <td class="py-2.5 px-3 font-mono font-semibold"><?= e($r['kode_mk']) ?></td>
                            <td class="py-2.5 px-3 font-medium"><?= e($r['nama_mk']) ?></td>
                            <td class="py-2.5 px-3 text-center"><?= e($r['nama_kelas']) ?></td>
                            <td class="py-2.5 px-3 text-center font-semibold"><?= (int)$r['sks'] ?></td>
                            <td class="py-2.5 px-3 text-center"><?= $dinilai ? e(number_format((float)$r['tugas'], 2)) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center"><?= $dinilai ? e(number_format((float)$r['uts'], 2)) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center"><?= $dinilai ? e(number_format((float)$r['uas'], 2)) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center font-semibold"><?= $dinilai ? e(number_format((float)$r['nilai_akhir'], 2)) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center font-bold"><?= $dinilai ? e($r['huruf']) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center"><?= $dinilai ? e(number_format((float)$r['bobot'], 2)) : '-' ?></td>
                            <td class="py-2.5 px-3 text-center font-semibold"><?= $dinilai ? e(number_format((float)$r['mutu'], 2)) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-300">
                    <td colspan="4" class="py-2.5 px-3 text-right">Jumlah</td>
                    <td class="py-2.5 px-3 text-center"><?= $totalSks ?></td>
                    <td colspan="6" class="py-2.5 px-3 text-right">Total Angka Mutu (&Sigma; K&times;B)</td>
                    <td class="py-2.5 px-3 text-center"><?= number_format($totalMutu, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Ringkasan IPS & Tanda Tangan -->
    <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6">
        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-xs space-y-1">
            <div>Total SKS Semester Ini: <strong><?= $totalSks ?> SKS</strong></div>
            <div>Total Angka Mutu: <strong><?= number_format($totalMutu, 2) ?></strong></div>
            <div class="text-sm pt-1">
                Indeks Prestasi Semester (IPS): <strong class="text-teal-800 text-base"><?= number_format($ips, 2) ?></strong>
            </div>
        </div>

        <div class="text-xs text-slate-700 text-right sm:min-w-[220px]">
            <div>Dicetak pada: <?= e(format_tanggal(date('Y-m-d'))) ?></div>
            <div class="mt-1 font-medium">Ketua Program Studi <?= e($profil['nama_prodi'] ?? '') ?></div>
            <div class="h-14"></div>
            <div class="font-bold underline">( ________________________ )</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
