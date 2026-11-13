<?php
/**
 * Komponen UI Reusable (Tabel, Paginasi, Field Form, Badge Status, Ikon SVG)
 * Memastikan tampilan konsisten di seluruh modul tanpa duplikasi kode HTML.
 */

require_once __DIR__ . '/helpers.php';

/**
 * Merender ikon SVG sederhana bergaya Lucide/Heroicons (tanpa emoji).
 *
 * @param string $name Nama ikon
 * @param string $class Kelas CSS tambahan (default: w-4 h-4)
 * @return string SVG HTML
 */
function ui_icon(string $name, string $class = 'w-4 h-4'): string
{
    $icons = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'building'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'users'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'user'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'academic'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>',
        'book'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        'calendar'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'clock'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'document'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'award'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
        'plus'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
        'edit'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'trash'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'search'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'printer'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>',
        'logout'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
        'check'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
        'x'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
        'arrow-left'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>',
        'menu'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>',
        'key'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>',
    ];

    $path = $icons[$name] ?? $icons['document'];
    return '<svg xmlns="http://www.w3.org/2000/svg" class="' . e($class) . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">' . $path . '</svg>';
}

/**
 * Merender badge status konsisten sesuai nilai domain akademik.
 *
 * @param string $text
 * @param string|null $variant 'success' | 'warning' | 'error' | 'info' | 'neutral' | null (otomatis)
 * @return string HTML badge
 */
function ui_badge(string $text, ?string $variant = null): string
{
    $norm = strtolower(trim($text));
    if ($variant === null) {
        if (in_array($norm, ['aktif', 'disetujui', 'lulus', 'a', 'b+', 'b'], true)) {
            $variant = 'success';
        } elseif (in_array($norm, ['diajukan', 'cuti', 'ganjil', 'c+', 'c'], true)) {
            $variant = 'warning';
        } elseif (in_array($norm, ['ditolak', 'keluar', 'nonaktif', 'tidak aktif', 'd', 'e'], true)) {
            $variant = 'error';
        } elseif (in_array($norm, ['admin', 'dosen', 'mahasiswa', 'genap', 's1', 'd3'], true)) {
            $variant = 'info';
        } else {
            $variant = 'neutral';
        }
    }

    $classes = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'error'   => 'bg-rose-50 text-rose-700 border-rose-200',
        'info'    => 'bg-teal-50 text-teal-800 border-teal-200',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
    ];

    $cls = $classes[$variant] ?? $classes['neutral'];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border ' . $cls . '">' . e($text) . '</span>';
}

/**
 * Merender field form standar dengan label di atas dan pesan petunjuk/error di bawah.
 *
 * @param array $cfg ['name', 'label', 'type', 'value', 'required', 'placeholder', 'options', 'help', 'error', 'attrs']
 * @return string HTML form-control
 */
function ui_field(array $cfg): string
{
    $name        = $cfg['name'] ?? '';
    $label       = $cfg['label'] ?? ucfirst($name);
    $type        = $cfg['type'] ?? 'text';
    $value       = $cfg['value'] ?? '';
    $required    = !empty($cfg['required']);
    $placeholder = $cfg['placeholder'] ?? '';
    $options     = $cfg['options'] ?? [];
    $help        = $cfg['help'] ?? '';
    $error       = $cfg['error'] ?? '';
    $attrs       = $cfg['attrs'] ?? '';

    $reqMark = $required ? ' <span class="text-rose-600">*</span>' : '';
    $reqAttr = $required ? ' required' : '';
    $errCls  = $error !== '' ? ' border-rose-400 focus:border-rose-500' : ' border-slate-300 focus:border-teal-700';

    $html = '<div class="form-control w-full">';
    $html .= '<label class="label pb-1.5" for="field_' . e($name) . '">';
    $html .= '<span class="label-text font-medium text-slate-700 text-sm">' . e($label) . $reqMark . '</span>';
    $html .= '</label>';

    if ($type === 'select') {
        $html .= '<select id="field_' . e($name) . '" name="' . e($name) . '" class="select select-bordered select-sm h-10 w-full rounded-lg bg-white text-slate-800' . $errCls . '"' . $reqAttr . ' ' . $attrs . '>';
        if ($placeholder !== '') {
            $html .= '<option value="">' . e($placeholder) . '</option>';
        }
        foreach ($options as $optVal => $optLabel) {
            $selected = ((string)$value === (string)$optVal) ? ' selected' : '';
            $html .= '<option value="' . e($optVal) . '"' . $selected . '>' . e($optLabel) . '</option>';
        }
        $html .= '</select>';
    } elseif ($type === 'textarea') {
        $html .= '<textarea id="field_' . e($name) . '" name="' . e($name) . '" rows="3" placeholder="' . e($placeholder) . '" class="textarea textarea-bordered w-full rounded-lg bg-white text-slate-800 text-sm' . $errCls . '"' . $reqAttr . ' ' . $attrs . '>' . e($value) . '</textarea>';
    } else {
        $html .= '<input id="field_' . e($name) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" placeholder="' . e($placeholder) . '" class="input input-bordered input-sm h-10 w-full rounded-lg bg-white text-slate-800 text-sm' . $errCls . '"' . $reqAttr . ' ' . $attrs . '>';
    }

    if ($error !== '') {
        $html .= '<p class="mt-1 text-xs text-rose-600">' . e($error) . '</p>';
    } elseif ($help !== '') {
        $html .= '<p class="mt-1 text-xs text-slate-500">' . e($help) . '</p>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Merender tabel data responsif dengan empty state informatif.
 *
 * @param array $headers Daftar judul kolom (string atau ['label' => ..., 'class' => ...])
 * @param array $rows Array berisi array sel HTML per baris
 * @param string $emptyMessage Pesan ketika data kosong
 * @return string HTML tabel
 */
function ui_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data untuk ditampilkan.'): string
{
    $html = '<div class="overflow-x-auto bg-white border border-slate-200 rounded-lg">';
    $html .= '<table class="table table-zebra w-full text-sm">';
    $html .= '<thead class="bg-slate-50 text-slate-600 border-b border-slate-200">';
    $html .= '<tr>';
    foreach ($headers as $h) {
        if (is_array($h)) {
            $cls = $h['class'] ?? '';
            $html .= '<th class="font-semibold py-3 px-4 ' . e($cls) . '">' . e($h['label'] ?? '') . '</th>';
        } else {
            $html .= '<th class="font-semibold py-3 px-4">' . e($h) . '</th>';
        }
    }
    $html .= '</tr></thead><tbody>';

    if (empty($rows)) {
        $colspan = max(1, count($headers));
        $html .= '<tr><td colspan="' . $colspan . '" class="py-10 text-center text-slate-500">';
        $html .= '<div class="flex flex-col items-center justify-center gap-1.5">';
        $html .= ui_icon('document', 'w-7 h-7 text-slate-400');
        $html .= '<p class="text-sm font-medium text-slate-600">' . e($emptyMessage) . '</p>';
        $html .= '</div></td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr class="hover:bg-slate-50/80 border-b border-slate-100 last:border-none">';
            foreach ($row as $cell) {
                if (is_array($cell)) {
                    $cls = $cell['class'] ?? '';
                    $html .= '<td class="py-3 px-4 align-middle ' . e($cls) . '">' . ($cell['html'] ?? e($cell['text'] ?? '')) . '</td>';
                } else {
                    $html .= '<td class="py-3 px-4 align-middle">' . $cell . '</td>';
                }
            }
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table></div>';
    return $html;
}

/**
 * Merender navigasi paginasi.
 *
 * @param int $currentPage Halaman aktif
 * @param int $totalPages Total halaman
 * @param int $totalRows Total seluruh baris
 * @param array $queryParams Parameter GET tambahan yang dipertahankan (mis. ['q' => '...'])
 * @return string HTML paginasi
 */
function ui_pagination(int $currentPage, int $totalPages, int $totalRows = 0, array $queryParams = []): string
{
    if ($totalPages <= 1 && $totalRows === 0) {
        return '';
    }

    $html = '<div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 text-xs text-slate-600 no-print">';
    $html .= '<div>Total <strong>' . (int)$totalRows . '</strong> data &middot; Halaman <strong>' . (int)$currentPage . '</strong> dari <strong>' . (int)$totalPages . '</strong></div>';

    if ($totalPages > 1) {
        $html .= '<div class="join">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $params = array_merge($queryParams, ['page' => $i]);
            $qs = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
            $activeCls = ($i === $currentPage)
                ? 'btn-active bg-teal-800 text-white border-teal-800 hover:bg-teal-900'
                : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100';
            $html .= '<a href="?' . e($qs) . '" class="join-item btn btn-xs h-8 px-3 ' . $activeCls . '">' . $i . '</a>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
