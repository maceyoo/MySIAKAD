<?php
/**
 * Sidebar Navigasi Role Dosen
 */

$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$items = [
    ['label' => 'Dashboard',       'url' => 'modules/dosen/dashboard.php',   'match' => '/modules/dosen/dashboard', 'icon' => 'dashboard'],
    ['label' => 'Kelas Diampu',    'url' => 'modules/dosen/kelas/index.php', 'match' => '/modules/dosen/kelas',     'icon' => 'clock'],
    ['label' => 'Input Nilai',     'url' => 'modules/dosen/nilai/index.php', 'match' => '/modules/dosen/nilai',     'icon' => 'award'],
];
?>
<aside id="app-sidebar" class="app-sidebar no-print fixed lg:static inset-y-0 left-0 z-40 w-60 bg-white border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col shrink-0">
    <div class="p-4 flex-1 overflow-y-auto">
        <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
            Menu Dosen
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
    <div class="p-4 border-t border-slate-200 text-xs text-slate-400">
        Portal Dosen Pengampu
    </div>
</aside>
