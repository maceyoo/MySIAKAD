<?php
/**
 * Daftar Kelas Diampu & Peserta Kelas (Role Dosen) + Fitur Ekspor CSV Rekap Nilai
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/DosenModel.php';
require_once __DIR__ . '/../../../models/KelasModel.php';
require_once __DIR__ . '/../../../models/NilaiModel.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';

$user    = require_role(['dosen']);
$idDosen = (int)($user['id_dosen'] ?? 0);

$taAktif = TahunAjaranModel::aktif();
$idTa    = isset($_GET['id_ta']) ? ($_GET['id_ta'] !== '' ? (int)$_GET['id_ta'] : null) : ($taAktif ? (int)$taAktif['id_ta'] : null);
$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

$kelasList = DosenModel::kelasDiampu($idDosen, $idTa);
$taList    = TahunAjaranModel::all();

// Jika id_kelas dipilih, pastikan kelas tersebut milik dosen yang sedang login
$detailKelas = null;
$pesertaList = [];
if ($idKelas > 0) {
    $k = KelasModel::find($idKelas);
    if (!$k || (int)$k['id_dosen'] !== $idDosen) {
        flash('error', 'Akses ditolak: Anda bukan dosen pengampu untuk kelas tersebut.');
        redirect('modules/dosen/kelas/index.php');
    }
    $detailKelas = $k;
    $pesertaList = NilaiModel::pesertaKelas($idKelas);

    // Ekspor CSV Rekap Nilai jika diminta
    if (($_GET['export'] ?? '') === 'csv') {
        $filename = 'rekap_nilai_' . strtolower($detailKelas['kode_mk']) . '_kelas_' . strtolower($detailKelas['nama_kelas']) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['No', 'NIM', 'Nama Mahasiswa', 'Prodi', 'Tugas (30%)', 'UTS (30%)', 'UAS (40%)', 'Nilai Akhir', 'Huruf', 'Bobot']);
        foreach ($pesertaList as $idx => $m) {
            fputcsv($out, [
                $idx + 1,
                $m['nim'],
                $m['nama_mahasiswa'],
                $m['nama_prodi'],
                $m['tugas'] ?? '-',
                $m['uts'] ?? '-',
                $m['uas'] ?? '-',
                $m['nilai_akhir'] ?? '-',
                $m['huruf'] ?? '-',
                $m['bobot'] ?? '-',
            ]);
        }
        fclose($out);
        exit;
    }
}

$pageTitle = 'Kelas Diampu';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Kelas yang Diampu</h1>
        <p class="text-xs text-slate-500">Daftar jadwal mengajar dan peserta mahasiswa yang KRS-nya telah disetujui.</p>
    </div>
    <form method="GET" action="" class="flex items-center gap-2">
        <select name="id_ta" onchange="this.form.submit()" class="select select-bordered select-sm h-9 rounded-lg bg-white border-slate-300 text-sm">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($taList as $ta): ?>
                <option value="<?= (int)$ta['id_ta'] ?>" <?= ($idTa === (int)$ta['id_ta']) ? 'selected' : '' ?>>
                    <?= e($ta['tahun'] . ' - ' . ucfirst($ta['semester']) . ((int)$ta['aktif'] === 1 ? ' (Aktif)' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php
$rows = [];
foreach ($kelasList as $kl) {
    $jam = substr($kl['jam_mulai'], 0, 5) . ' – ' . substr($kl['jam_selesai'], 0, 5);
    $aksi = '<div class="flex items-center justify-end gap-1.5">'
        . '<a href="' . e(url('modules/dosen/kelas/index.php?id_ta=' . (int)$kl['id_ta'] . '&id_kelas=' . (int)$kl['id_kelas'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">Lihat Peserta</a>'
        . '<a href="' . e(url('modules/dosen/nilai/index.php?id_kelas=' . (int)$kl['id_kelas'])) . '" class="btn btn-xs h-7 px-2.5 rounded bg-teal-800 hover:bg-teal-900 text-white border-none">Input Nilai</a>'
        . '</div>';

    $rows[] = [
        '<div class="font-semibold text-slate-800">' . e($kl['kode_mk']) . ' &mdash; ' . e($kl['nama_mk']) . '</div>'
            . '<div class="text-xs text-slate-500">TA ' . e($kl['tahun']) . ' (' . e(ucfirst($kl['semester'])) . ') &middot; ' . (int)$kl['sks'] . ' SKS</div>',
        ui_badge('Kelas ' . $kl['nama_kelas'], 'info'),
        e($kl['hari']) . ', ' . e($jam) . ' (' . e($kl['ruang']) . ')',
        (int)$kl['jumlah_disetujui'] . ' / ' . (int)$kl['kuota'] . ' Mhs',
        (int)$kl['jumlah_dinilai'] . ' Dinilai',
        ['html' => $aksi, 'class' => 'text-right'],
    ];
}

echo ui_table(
    ['Mata Kuliah & Semester', 'Kelas', 'Jadwal & Ruang', 'Peserta Disetujui', 'Status Nilai', ['label' => 'Aksi', 'class' => 'text-right']],
    $rows,
    'Tidak ada kelas yang diampu pada tahun ajaran ini.'
);
?>

<?php if ($detailKelas): ?>
    <div class="mt-8 bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-200">
            <div>
                <h2 class="text-base font-bold text-slate-800">
                    Daftar Peserta: <?= e($detailKelas['kode_mk']) ?> — <?= e($detailKelas['nama_mk']) ?> (Kelas <?= e($detailKelas['nama_kelas']) ?>)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Jadwal: <?= e($detailKelas['hari']) ?>, <?= e(substr($detailKelas['jam_mulai'], 0, 5) . '–' . substr($detailKelas['jam_selesai'], 0, 5)) ?> &middot; Ruang <?= e($detailKelas['ruang']) ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= e(url('modules/dosen/kelas/index.php?id_kelas=' . (int)$detailKelas['id_kelas'] . '&export=csv')) ?>" class="btn btn-xs h-8 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border-none">
                    Ekspor CSV
                </a>
                <a href="<?= e(url('modules/dosen/nilai/index.php?id_kelas=' . (int)$detailKelas['id_kelas'])) ?>" class="btn btn-xs h-8 px-3 rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none">
                    Kelola Nilai Kelas Ini
                </a>
            </div>
        </div>

        <?php
        $pesertaRows = [];
        foreach ($pesertaList as $idx => $m) {
            $sudahDinilai = ($m['id_nilai'] !== null);
            $pesertaRows[] = [
                $idx + 1,
                '<code class="font-semibold text-slate-800">' . e($m['nim']) . '</code>',
                '<span class="font-medium text-slate-800">' . e($m['nama_mahasiswa']) . '</span>',
                e($m['nama_prodi']),
                $sudahDinilai ? e(number_format((float)$m['nilai_akhir'], 2)) : '<span class="text-xs text-slate-400">Belum diisi</span>',
                $sudahDinilai ? ui_badge($m['huruf']) : '-',
            ];
        }
        echo ui_table(
            ['No', 'NIM', 'Nama Mahasiswa', 'Program Studi', 'Nilai Akhir', 'Huruf'],
            $pesertaRows,
            'Belum ada mahasiswa dengan KRS berstatus disetujui di kelas ini.'
        );
        ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
