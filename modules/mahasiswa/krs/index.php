<?php
/**
 * Halaman Pengisian Kartu Rencana Studi (KRS) Mahasiswa
 * Menampilkan progres total SKS terhadap MAX_SKS (24), daftar kelas yang telah diambil (beserta tombol Batal),
 * dan daftar kelas yang tersedia pada semester aktif (beserta tombol Ambil).
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/ui.php';
require_once __DIR__ . '/../../../models/TahunAjaranModel.php';
require_once __DIR__ . '/../../../models/KelasModel.php';
require_once __DIR__ . '/../../../models/KrsModel.php';

$user    = require_role(['mahasiswa']);
$idMhs   = (int)($user['id_mahasiswa'] ?? 0);
$taAktif = TahunAjaranModel::aktif();

if (!$taAktif) {
    $pageTitle = 'Pengisian KRS';
    require_once __DIR__ . '/../../../layouts/header.php';
    echo '<div class="bg-white border border-slate-200 rounded-lg p-8 text-center text-slate-600">Saat ini tidak ada tahun ajaran yang aktif. Pengisian KRS ditutup.</div>';
    require_once __DIR__ . '/../../../layouts/footer.php';
    exit;
}

$idTaAktif = (int)$taAktif['id_ta'];

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'ambil') {
            $idKelas = (int)($_POST['id_kelas'] ?? 0);
            KrsModel::ambil($idMhs, $idKelas);
            flash('success', 'Kelas berhasil ditambahkan ke KRS Anda dengan status diajukan.');
        } elseif ($action === 'batal') {
            $idKrs = (int)($_POST['id_krs'] ?? 0);
            KrsModel::batal($idKrs, $idMhs);
            flash('info', 'Pengambilan kelas pada KRS berhasil dibatalkan.');
        }
    } catch (Exception $e) {
        flash('error', $e->getMessage());
    }
    redirect('modules/mahasiswa/krs/index.php');
}

$krsDiambil    = KrsModel::daftar($idMhs, $idTaAktif);
$totalSks      = KrsModel::totalSks($idMhs, $idTaAktif);
$kelasTersedia = KelasModel::tersedia($idMhs, $idTaAktif);
$persenSks     = min(100, (int)round(($totalSks / MAX_SKS) * 100));

$pageTitle = 'Pengisian KRS';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Pengisian Kartu Rencana Studi (KRS)</h1>
        <p class="text-xs text-slate-500">
            Tahun Ajaran Aktif: <strong><?= e($taAktif['tahun']) ?> &mdash; Semester <?= e(ucfirst($taAktif['semester'])) ?></strong>
        </p>
    </div>
</div>

<!-- Ringkasan Progres SKS -->
<div class="bg-white border border-slate-200 rounded-lg p-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
        <div class="text-sm font-semibold text-slate-800">
            Total Beban Studi Diambil: <span class="text-teal-800"><?= (int)$totalSks ?> SKS</span> dari batas maksimum <?= (int)MAX_SKS ?> SKS
        </div>
        <div class="text-xs text-slate-500">
            Sisa kuota SKS Anda: <strong><?= max(0, MAX_SKS - $totalSks) ?> SKS</strong>
        </div>
    </div>
    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
        <div class="bg-teal-700 h-2.5 rounded-full transition-all" style="width: <?= $persenSks ?>%"></div>
    </div>
</div>

<!-- Bagian 1: KRS Mahasiswa Saat Ini -->
<div class="mb-8">
    <h2 class="text-sm font-bold text-slate-800 mb-3">1. Daftar Kelas pada KRS Anda Semester Ini</h2>
    <?php
    $diambilRows = [];
    foreach ($krsDiambil as $k) {
        $jam = substr($k['jam_mulai'], 0, 5) . ' – ' . substr($k['jam_selesai'], 0, 5);
        $aksi = '<span class="text-xs text-slate-400">Terkunci</span>';
        if ($k['status'] === 'diajukan') {
            $aksi = '<form method="POST" action="" data-confirm="Batalkan pengambilan mata kuliah ' . e($k['nama_mk']) . ' dari KRS Anda?" class="inline">'
                . csrf_field()
                . '<input type="hidden" name="action" value="batal">'
                . '<input type="hidden" name="id_krs" value="' . (int)$k['id_krs'] . '">'
                . '<button type="submit" class="btn btn-xs h-7 px-2.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border-none">'
                . ui_icon('trash', 'w-3.5 h-3.5') . ' <span>Batal</span></button></form>';
        }

        $diambilRows[] = [
            '<code class="font-semibold text-slate-800">' . e($k['kode_mk']) . '</code>',
            '<div class="font-medium text-slate-800">' . e($k['nama_mk']) . '</div><div class="text-xs text-slate-500">Semester Paket ' . (int)$k['semester_paket'] . '</div>',
            ui_badge('Kelas ' . $k['nama_kelas'], 'info'),
            '<strong>' . (int)$k['sks'] . '</strong> SKS',
            e($k['nama_dosen']),
            e($k['hari']) . ', ' . e($jam) . ' (' . e($k['ruang']) . ')',
            ui_badge(ucfirst($k['status'])),
            ['html' => $aksi, 'class' => 'text-right'],
        ];
    }

    echo ui_table(
        ['Kode', 'Mata Kuliah', 'Kelas', 'SKS', 'Dosen Pengampu', 'Jadwal & Ruang', 'Status KRS', ['label' => 'Aksi', 'class' => 'text-right']],
        $diambilRows,
        'Anda belum memilih kelas apa pun untuk semester ini. Silakan pilih dari tabel kelas tersedia di bawah.'
    );
    ?>
</div>

<!-- Bagian 2: Daftar Kelas Tersedia -->
<div>
    <h2 class="text-sm font-bold text-slate-800 mb-3">2. Daftar Kelas Tersedia pada Semester Aktif</h2>
    <?php
    $tersediaRows = [];
    foreach ($kelasTersedia as $kl) {
        $jam       = substr($kl['jam_mulai'], 0, 5) . ' – ' . substr($kl['jam_selesai'], 0, 5);
        $sisaKuota = (int)$kl['sisa_kuota'];
        $sudahDiambilKelasIni = !empty($kl['id_krs_diambil']);
        $sudahDiambilMkIni    = (int)$kl['mk_sudah_diambil'] > 0;

        if ($sudahDiambilKelasIni) {
            $tombol = ui_badge('Sudah Diambil (' . ucfirst($kl['status_krs']) . ')', 'success');
        } elseif ($sudahDiambilMkIni) {
            $tombol = ui_badge('MK Sudah Diambil', 'neutral');
        } elseif ($sisaKuota <= 0) {
            $tombol = ui_badge('Kuota Penuh', 'error');
        } elseif (($totalSks + (int)$kl['sks']) > MAX_SKS) {
            $tombol = ui_badge('Melebihi 24 SKS', 'warning');
        } else {
            $tombol = '<form method="POST" action="" class="inline">'
                . csrf_field()
                . '<input type="hidden" name="action" value="ambil">'
                . '<input type="hidden" name="id_kelas" value="' . (int)$kl['id_kelas'] . '">'
                . '<button type="submit" class="btn btn-xs h-7 px-3 rounded bg-teal-800 hover:bg-teal-900 text-white border-none font-medium">'
                . ui_icon('plus', 'w-3.5 h-3.5') . ' <span>Ambil</span></button></form>';
        }

        $kuotaBadge = $sisaKuota > 0
            ? '<span class="text-slate-700 font-medium">' . $sisaKuota . '</span> <span class="text-xs text-slate-400">/ ' . (int)$kl['kuota'] . '</span>'
            : '<span class="text-rose-600 font-semibold">0 / ' . (int)$kl['kuota'] . '</span>';

        $tersediaRows[] = [
            '<code class="font-semibold text-slate-800">' . e($kl['kode_mk']) . '</code>',
            '<div class="font-medium text-slate-800">' . e($kl['nama_mk']) . '</div><div class="text-xs text-slate-500">' . e($kl['nama_prodi']) . ' &middot; Sem ' . (int)$kl['semester_paket'] . '</div>',
            ui_badge('Kelas ' . $kl['nama_kelas'], 'info'),
            '<strong>' . (int)$kl['sks'] . '</strong> SKS',
            e($kl['nama_dosen']),
            '<div class="text-slate-800">' . e($kl['hari']) . ', ' . e($jam) . '</div><div class="text-xs text-slate-500">Ruang ' . e($kl['ruang']) . '</div>',
            $kuotaBadge,
            ['html' => $tombol, 'class' => 'text-right'],
        ];
    }

    echo ui_table(
        ['Kode', 'Mata Kuliah', 'Kelas', 'SKS', 'Dosen Pengampu', 'Jadwal & Ruang', 'Sisa Kuota', ['label' => 'Aksi', 'class' => 'text-right']],
        $tersediaRows,
        'Belum ada kelas yang dibuka untuk semester aktif ini.'
    );
    ?>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
