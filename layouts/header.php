<?php
/**
 * Layout Header Utama SIAKAD
 * Memuat CDN daisyUI v4 + Tailwind CSS, font Plus Jakarta Sans, navbar tipis, dan sidebar sesuai role.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$currUser  = current_user();
$pageTitle = isset($pageTitle) ? $pageTitle . ' — SIAKAD' : 'SIAKAD — Sistem Informasi Akademik';
$flashMsg  = get_flash();
?>
<!DOCTYPE html>
<html lang="id" data-theme="siakad">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#f0fdfa',
                            100: '#ccfbf1',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<?php if ($currUser): ?>
    <!-- Navbar Tipis Atas -->
    <header class="app-navbar no-print sticky top-0 z-30 h-14 bg-white border-b border-slate-200 px-4 lg:px-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="btn-toggle-sidebar" type="button" class="lg:hidden p-1.5 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Buka Navigasi">
                <?= ui_icon('menu', 'w-5 h-5') ?>
            </button>
            <a href="<?= e(url('index.php')) ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-teal-800 text-white flex items-center justify-center font-bold text-sm tracking-tight">
                    SI
                </div>
                <div>
                    <span class="font-bold text-slate-800 tracking-tight text-sm sm:text-base">SIAKAD</span>
                    <span class="hidden md:inline-block text-xs text-slate-500 ml-1.5 pl-2 border-l border-slate-300"><?= e(NAMA_KAMPUS) ?></span>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-semibold text-slate-800 leading-tight"><?= e($currUser['nama']) ?></div>
                <div class="text-[11px] text-slate-500 capitalize">
                    <?= e($currUser['role']) ?>
                    <?php if (!empty($currUser['nomor_induk']) && $currUser['role'] !== 'admin'): ?>
                        &middot; <?= e($currUser['nomor_induk']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?= ui_badge(ucfirst($currUser['role'])) ?>
            <a href="<?= e(url('auth/logout.php')) ?>" class="btn btn-ghost btn-xs h-8 px-2.5 text-slate-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg flex items-center gap-1.5" title="Keluar">
                <?= ui_icon('logout', 'w-4 h-4') ?>
                <span class="hidden sm:inline text-xs font-medium">Keluar</span>
            </a>
        </div>
    </header>

    <!-- Backdrop Mobile Drawer -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 z-30 hidden lg:hidden no-print"></div>

    <div class="flex-1 flex">
        <?php
        if ($currUser['role'] === 'admin') {
            require_once __DIR__ . '/sidebar_admin.php';
        } elseif ($currUser['role'] === 'dosen') {
            require_once __DIR__ . '/sidebar_dosen.php';
        } else {
            require_once __DIR__ . '/sidebar_mahasiswa.php';
        }
        ?>

        <!-- Konten Utama -->
        <main class="app-main flex-1 min-w-0 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
            <?php if ($flashMsg): ?>
                <?php
                $alertStyles = [
                    'success' => 'bg-emerald-50 border-emerald-300 text-emerald-800',
                    'error'   => 'bg-rose-50 border-rose-300 text-rose-800',
                    'warning' => 'bg-amber-50 border-amber-300 text-amber-800',
                    'info'    => 'bg-teal-50 border-teal-300 text-teal-800',
                ];
                $st = $alertStyles[$flashMsg['type']] ?? $alertStyles['info'];
                ?>
                <div id="flash-alert" role="alert" class="no-print mb-5 px-4 py-3 rounded-lg border text-sm flex items-center justify-between <?= $st ?>">
                    <div class="flex items-center gap-2.5 font-medium">
                        <span><?= e($flashMsg['message']) ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100 font-semibold px-1.5">Tutup</button>
                </div>
            <?php endif; ?>
<?php endif; ?>
