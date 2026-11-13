<?php
/**
 * Sidebar Navigasi Role Admin
 */

$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$menuGroups = [
    'Utama' => [
        ['label' => 'Dashboard', 'url' => 'modules/admin/dashboard.php', 'match' => '/modules/admin/dashboard', 'icon' => 'dashboard'],
    ],
    'Master Data' => [
        ['label' => 'Program Studi', 'url' => 'modules/admin/prodi/index.php',        'match' => '/modules/admin/prodi/',        'icon' => 'building'],
        ['label' => 'Dosen',         'url' => 'modules/admin/dosen/index.php',        'match' => '/modules/admin/dosen/',        'icon' => 'user'],
        ['label' => 'Mahasiswa',     'url' => 'modules/admin/mahasiswa/index.php',    'match' => '/modules/admin/mahasiswa/',    'icon' => 'academic'],
        ['label' => 'Mata Kuliah',   'url' => 'modules/admin/matkul/index.php',       'match' => '/modules/admin/matkul/',       'icon' => 'book'],
        ['label' => 'Tahun Ajaran',  'url' => 'modules/admin/tahun_ajaran/index.php', 'match' => '/modules/admin/tahun_ajaran/', 'icon' => 'calendar'],
    ],
    'Perkuliahan' => [
        ['label' => 'Kelas & Jadwal', 'url' => 'modules/admin/kelas/index.php', 'match' => '/modules/admin/kelas/', 'icon' => 'clock'],
        ['label' => 'Verifikasi KRS', 'url' => 'modules/admin/krs/index.php',   'match' => '/modules/admin/krs/',   'icon' => 'clipboard'],
    ],
    'Sistem' => [
        ['label' => 'Manajemen User', 'url' => 'modules/admin/users/index.php', 'match' => '/modules/admin/users/', 'icon' => 'users'],
    ],
];
?>
<aside id="app-sidebar" class="app-sidebar no-print fixed lg:static inset-y-0 left-0 z-40 w-60 bg-white border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col shrink-0">
    <div class="p-4 flex-1 overflow-y-auto space-y-5">
        <?php foreach ($menuGroups as $groupTitle => $items): ?>
            <div>
                <div class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <?= e($groupTitle) ?>
                </div>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $isActive = strpos($currentScript, $item['match']) !== false;
                        $cls = $isActive
                            ? 'bg-teal-800 text-white font-medium'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
                        ?>
                        <li>
                            <a href="<?= e(url($item['url'])) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors <?= $cls ?>">
                                <?= ui_icon($item['icon'], 'w-4 h-4 shrink-0') ?>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="p-4 border-t border-slate-200 text-xs text-slate-400">
        Panel Administrasi Akademik
    </div>
</aside>
