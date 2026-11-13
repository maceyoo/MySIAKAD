<?php
/**
 * Halaman Login Multi-Role SIAKAD
 * Dua kolom di desktop (identitas kampus di kiri, form masuk di kanan), satu kolom di mobile.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../models/UserModel.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';

if (is_post()) {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $user = UserModel::login($username, $password);
            if ($user) {
                login_user($user);
                flash('success', 'Selamat datang kembali, ' . ($_SESSION['user']['nama'] ?? $user['username']) . '.');
                redirect('index.php');
            } else {
                $error = 'Username atau password yang Anda masukkan salah.';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$flashMsg = get_flash();
?>
<!DOCTYPE html>
<html lang="id" data-theme="siakad">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIAKAD <?= e(NAMA_KAMPUS) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-4xl bg-white border border-slate-200 rounded-lg overflow-hidden grid grid-cols-1 lg:grid-cols-12 shadow-sm">
    <!-- Kolom Kiri: Identitas Kampus -->
    <div class="lg:col-span-6 bg-teal-900 text-white p-8 lg:p-10 flex flex-col justify-between">
        <div>
            <div class="inline-flex items-center gap-2.5 mb-6">
                <div class="w-9 h-9 rounded-lg bg-amber-600 text-white flex items-center justify-center font-bold text-base">
                    SI
                </div>
                <span class="font-bold text-lg tracking-tight">SIAKAD</span>
            </div>
            <h1 class="text-xl lg:text-2xl font-bold leading-snug text-white mb-3">
                Sistem Informasi Manajemen Akademik &amp; Nilai Mahasiswa
            </h1>
            <p class="text-sm text-teal-100/90 leading-relaxed">
                <?= e(NAMA_KAMPUS) ?>. Portal terpadu untuk pengelolaan rencana studi (KRS), jadwal perkuliahan, penilaian dosen, kartu hasil studi (KHS), dan transkrip akademik.
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-teal-800 text-xs text-teal-200 space-y-2">
            <div class="font-semibold text-white">Panduan Akses Cepat (Akun Demo):</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                <div class="bg-teal-950/60 p-2.5 rounded-lg border border-teal-800">
                    <div class="font-semibold text-amber-400">Admin</div>
                    <div>User: <code>admin</code></div>
                    <div>Pass: <code>admin123</code></div>
                </div>
                <div class="bg-teal-950/60 p-2.5 rounded-lg border border-teal-800">
                    <div class="font-semibold text-amber-400">Dosen</div>
                    <div>User: <code>0112048801</code></div>
                    <div>Pass: <code>dosen123</code></div>
                </div>
                <div class="bg-teal-950/60 p-2.5 rounded-lg border border-teal-800">
                    <div class="font-semibold text-amber-400">Mahasiswa</div>
                    <div>User: <code>2401001</code></div>
                    <div>Pass: <code>mhs123</code></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Form Login -->
    <div class="lg:col-span-6 p-8 lg:p-10 flex flex-col justify-center">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-slate-800">Masuk ke Akun Anda</h2>
            <p class="text-xs text-slate-500 mt-1">Gunakan NIM, NIDN, atau username administrator.</p>
        </div>

        <?php if ($flashMsg): ?>
            <div class="mb-4 px-3.5 py-2.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-xs font-medium">
                <?= e($flashMsg['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-4 px-3.5 py-2.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <?= csrf_field() ?>

            <div class="form-control">
                <label class="label pb-1" for="username">
                    <span class="label-text text-sm font-medium text-slate-700">Username / NIM / NIDN</span>
                </label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= old('username') ?>"
                    placeholder="Contoh: 2401001 atau admin"
                    required
                    autofocus
                    class="input input-bordered input-sm h-10 w-full rounded-lg bg-white border-slate-300 text-slate-800 text-sm"
                >
            </div>

            <div class="form-control">
                <label class="label pb-1" for="password">
                    <span class="label-text text-sm font-medium text-slate-700">Password</span>
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                    class="input input-bordered input-sm h-10 w-full rounded-lg bg-white border-slate-300 text-slate-800 text-sm"
                >
            </div>

            <div class="pt-2">
                <button type="submit" class="btn btn-sm h-10 w-full rounded-lg bg-teal-800 hover:bg-teal-900 text-white border-none font-semibold text-sm">
                    Masuk Sistem
                </button>
            </div>
        </form>

        <div class="mt-8 pt-4 border-t border-slate-100 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> <?= e(NAMA_KAMPUS) ?>
        </div>
    </div>
</div>

</body>
</html>
