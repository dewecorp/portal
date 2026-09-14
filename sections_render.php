<?php
// Renderer section home + footer ala Elementor.
// Dipakai index.php (home) dan footer.php (footer).

function section_opt(array $pengaturan, string $key, $default = null)
{
    return $pengaturan[$key] ?? $default;
}

function section_youtube_id(string $url): string
{
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) return $m[1];
    return '';
}

function section_galeri_list(array $cfg): array
{
    $g = $cfg['galeri'] ?? [];
    if (is_string($g)) {
        $d = json_decode($g, true);
        $g = is_array($d) ? $d : preg_split('/[\r\n,]+/', $g);
    }
    if (!is_array($g)) return [];
    $out = [];
    foreach ($g as $p) {
        $p = trim((string)$p);
        if ($p !== '') $out[] = $p;
    }
    return array_values(array_unique($out));
}

function berita_card_html(array $item, bool $showRingkasan = true): string
{
    $url = htmlspecialchars(berita_url($item));
    $judul = htmlspecialchars($item['judul'] ?? '');
    $kat = htmlspecialchars($item['kategori_nama'] ?? 'Berita');
    $ringkasan = $showRingkasan && !empty($item['ringkasan'])
        ? '<p class="text-sm text-slate-600 line-clamp-2 mb-3">' . htmlspecialchars($item['ringkasan']) . '</p>' : '';
    $penulis = !empty($item['penulis'])
        ? '<span class="text-slate-300">•</span><span>' . htmlspecialchars($item['penulis']) . '</span>' : '';
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    if (!empty($item['gambar'])) {
        $img = htmlspecialchars(berita_image_url($item['gambar']));
        $thumb = '<a href="' . $url . '" class="block aspect-[16/10] overflow-hidden image-zoom"><img loading="lazy" src="' . $img . '" alt="' . $judul . '" class="h-full w-full object-cover"></a>';
    } else {
        $thumb = '<a href="' . $url . '" class="block aspect-[16/10] bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">'
            . '<svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path></svg></a>';
    }
    return '<article class="group h-full overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">'
        . $thumb
        . '<div class="p-5"><a href="' . $url . '" class="block">'
        . '<span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2">' . $kat . '</span>'
        . '<h3 class="text-base font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2 mb-3">' . $judul . '</h3>'
        . $ringkasan . '</a>'
        . '<div class="flex items-center gap-2 text-xs text-slate-500">'
        . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        . '<span>' . htmlspecialchars($tgl) . '</span>' . $penulis
        . '</div></div></article>';
}

function berita_card_mini_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $judul = htmlspecialchars($item['judul'] ?? '');
    $kat = htmlspecialchars($item['kategori_nama'] ?? 'Berita');
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    if (!empty($item['gambar'])) {
        $img = htmlspecialchars(berita_image_url($item['gambar']));
        $thumb = '<a href="' . $url . '" class="block aspect-[16/9] overflow-hidden image-zoom"><img loading="lazy" src="' . $img . '" alt="' . $judul . '" class="h-full w-full object-cover"></a>';
    } else {
        $thumb = '<a href="' . $url . '" class="block aspect-[16/9] bg-gradient-to-br from-purple-500 to-blue-500"></a>';
    }
    return '<article class="group h-full overflow-hidden rounded-xl bg-white border border-slate-200 card-hover shadow-sm">'
        . $thumb
        . '<div class="p-3"><a href="' . $url . '" class="block">'
        . '<span class="inline-block text-[9px] font-bold uppercase tracking-wider text-purple-600 mb-1">' . $kat . '</span>'
        . '<h3 class="text-xs font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2 mb-1">' . $judul . '</h3></a>'
        . '<div class="text-[11px] text-slate-500">' . htmlspecialchars($tgl) . '</div>'
        . '</div></article>';
}

function section_grid_style_list(): array
{
    return [
        'kartu-2' => 'Kartu 2 Kolom',
        'kartu-3' => 'Kartu 3 Kolom',
        'kartu-4' => 'Kartu 4 Kolom',
        'sorotan-list' => 'Sorotan + List',
        'magazine' => 'Magazine Grid',
        'masonry' => 'Masonry Cards',
        'kartu-horizontal' => 'Kartu Horizontal',
        'timeline' => 'Timeline Berita',
        'list' => 'List Horizontal',
        'overlay' => 'Overlay Gambar',
        'minimal' => 'Minimal Teks',
    ];
}

function section_normalize_style(string $style): string
{
    $style = trim($style);
    $map = [
        'grid' => 'kartu-4',
        'overlay-nomor' => 'overlay',
    ];
    if (isset($map[$style])) $style = $map[$style];
    if (!isset(section_grid_style_list()[$style])) $style = 'kartu-4';
    return $style;
}

function section_grid_class(string $style, int $kolom): string
{
    $style = section_normalize_style($style);
    $kolom = max(2, min(4, $kolom));
    switch ($style) {
        case 'kartu-2': return 'grid gap-6 sm:grid-cols-2';
        case 'kartu-3': return 'grid gap-6 sm:grid-cols-2 xl:grid-cols-3';
        case 'kartu-4': return 'grid gap-6 sm:grid-cols-2 xl:grid-cols-4';
        case 'list':
        case 'minimal':
        case 'timeline': return 'flex flex-col gap-4';
        case 'masonry': return 'columns-1 sm:columns-2 xl:columns-3 gap-6';
        case 'overlay': return 'grid gap-6 sm:grid-cols-2 ' . ($kolom === 2 ? 'xl:grid-cols-2' : ($kolom === 3 ? 'xl:grid-cols-3' : 'xl:grid-cols-4'));
        case 'magazine':
        case 'sorotan-list': return 'grid gap-6 xl:grid-cols-2';
        case 'kartu-horizontal': return 'grid gap-6 xl:grid-cols-2';
        default: return 'grid gap-6 sm:grid-cols-2 xl:grid-cols-4';
    }
}

function berita_sorotan_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $img = berita_image_url($item['gambar'] ?? '');
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    $bg = $img !== ''
        ? '<img loading="lazy" src="' . htmlspecialchars($img) . '" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">'
        : '<div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-600"></div>';
    return '<a href="' . $url . '" class="group relative block w-full flex-1 min-h-[320px] overflow-hidden rounded-2xl shadow-sm card-hover md:min-h-0">'
        . $bg . '<div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>'
        . '<div class="absolute bottom-0 p-4 sm:p-5">'
        . '<span class="mb-2 inline-block rounded-full bg-purple-600 px-3 py-1 text-[11px] font-bold text-white">' . htmlspecialchars($item['kategori_nama'] ?? 'Berita') . '</span>'
        . '<h3 class="text-base sm:text-lg font-black leading-snug text-white line-clamp-3 break-words">' . htmlspecialchars($item['judul']) . '</h3>'
        . '<div class="mt-1 text-xs text-white/80">' . htmlspecialchars($tgl) . '</div>'
        . '</div></a>';
}

function berita_list_kompak_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    $img = berita_image_url($item['gambar'] ?? '');
    $thumb = $img !== ''
        ? '<span class="block h-16 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100"><img loading="lazy" src="' . htmlspecialchars($img) . '" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-110"></span>'
        : '<span class="block h-16 w-20 shrink-0 rounded-xl bg-gradient-to-br from-purple-500 to-blue-500"></span>';
    return '<a href="' . $url . '" class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm card-hover">'
        . $thumb
        . '<span class="min-w-0"><span class="block text-sm font-bold leading-snug text-slate-900 line-clamp-2 group-hover:text-purple-700">' . htmlspecialchars($item['judul']) . '</span>'
        . '<span class="mt-0.5 block truncate text-xs text-slate-500">' . htmlspecialchars($item['kategori_nama'] ?? 'Berita') . ' • ' . htmlspecialchars($tgl) . '</span></span></a>';
}

function berita_list_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $judul = htmlspecialchars($item['judul'] ?? '');
    $kat = htmlspecialchars($item['kategori_nama'] ?? 'Berita');
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    $penulis = !empty($item['penulis']) ? '<span class="text-slate-300">•</span><span>' . htmlspecialchars($item['penulis']) . '</span>' : '';
    $ringkas = !empty($item['ringkasan']) ? '<p class="text-sm text-slate-600 line-clamp-2 mb-2">' . htmlspecialchars($item['ringkasan']) . '</p>' : '';
    $img = berita_image_url($item['gambar'] ?? '');
    $thumb = $img !== ''
        ? '<a href="' . $url . '" class="block w-24 md:w-28 h-20 md:h-16 shrink-0 overflow-hidden rounded-lg image-zoom"><img loading="lazy" src="' . htmlspecialchars($img) . '" alt="' . $judul . '" class="h-full w-full object-cover"></a>'
        : '<div class="w-24 md:w-28 h-20 md:h-16 shrink-0 rounded-lg bg-gradient-to-br from-purple-500 to-blue-500"></div>';
    return '<article class="group flex flex-col md:flex-row gap-2 p-2 rounded-lg bg-white border border-slate-200 card-hover shadow-sm">'
        . $thumb
        . '<div class="min-w-0 flex-1"><span class="inline-block text-[9px] font-bold uppercase tracking-wider text-purple-600 mb-0.5">' . $kat . '</span>'
        . '<a href="' . $url . '"><h3 class="text-xs xl:text-sm font-bold text-slate-900 group-hover:text-purple-700 line-clamp-2 mb-0.5 break-words">' . $judul . '</h3></a>'
        . $ringkas
        . '<div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500"><span class="whitespace-nowrap">' . htmlspecialchars($tgl) . '</span>' . $penulis . '</div>'
        . '</div></article>';
}

function berita_overlay_html(array $item, $nomor = null): string
{
    $url = htmlspecialchars(berita_url($item));
    $img = berita_image_url($item['gambar'] ?? '');
    $bg = $img !== ''
        ? '<img loading="lazy" src="' . htmlspecialchars($img) . '" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">'
        : '<div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-600"></div>';
    $badge = $nomor !== null ? '<span class="absolute top-3 left-3 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/90 text-sm font-black text-purple-700">' . (int)$nomor . '</span>' : '';
    $views = isset($item['views']) ? '<div class="mt-1 text-xs text-white/80">' . number_format((int)$item['views']) . ' dibaca</div>' : '';
    return '<a href="' . $url . '" class="group relative overflow-hidden rounded-2xl shadow-sm card-hover h-64 block">'
        . $bg . '<div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>' . $badge
        . '<div class="absolute bottom-0 p-4"><h3 class="text-sm font-bold text-white line-clamp-2">' . htmlspecialchars($item['judul']) . '</h3>' . $views . '</div></a>';
}

function berita_minimal_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    return '<a href="' . $url . '" class="group block border-b border-slate-100 py-4 hover:pl-2 transition-all">'
        . '<div class="text-[11px] font-bold uppercase tracking-wider text-purple-600 mb-1">' . htmlspecialchars($item['kategori_nama'] ?? 'Berita') . ' • ' . htmlspecialchars($tgl) . '</div>'
        . '<h3 class="text-base font-bold text-slate-900 group-hover:text-purple-700 line-clamp-2">' . htmlspecialchars($item['judul']) . '</h3></a>';
}

function berita_timeline_html(array $item): string
{
    $url = htmlspecialchars(berita_url($item));
    $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
    return '<div class="relative pl-8 pb-6 border-l-2 border-purple-200 last:pb-0">'
        . '<span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 ring-4 ring-purple-100"></span>'
        . '<div class="text-xs font-bold text-purple-600 mb-1">' . htmlspecialchars($tgl) . '</div>'
        . '<a href="' . $url . '"><h3 class="font-bold text-slate-900 hover:text-purple-700 line-clamp-2">' . htmlspecialchars($item['judul']) . '</h3></a>'
        . (!empty($item['ringkasan']) ? '<p class="text-sm text-slate-600 line-clamp-2 mt-1">' . htmlspecialchars($item['ringkasan']) . '</p>' : '')
        . '</div>';
}

function anim_attr_name(string $animAttr): string
{
    if (preg_match('/data-animate="([^"]+)"/', $animAttr, $m)) return $m[1];
    return '';
}

function anim_item_html(string $inner, string $animAttr, int $i, string $cls = ''): string
{
    $anim = anim_attr_name($animAttr);
    if ($anim === '') {
        if ($cls === '') return $inner;
        return '<div class="' . $cls . '">' . $inner . '</div>';
    }
    $delay = $i % 4;
    $attr = ' data-animate="' . htmlspecialchars($anim) . '"';
    if ($delay > 0) $attr .= ' data-animate-delay="' . $delay . '"';
    // Suntik atribut ke tag pertama agar kelas grid (col-span, break-inside) tidak rusak.
    $trim = ltrim($inner);
    if ($cls === '' && preg_match('/^<([a-z0-9]+)((?:\s[^>]*)?)>/i', $trim, $m)) {
        if (strpos($m[0], 'data-animate') !== false) return $inner;
        $newTag = '<' . $m[1] . $m[2] . $attr . '>';
        $pos = strpos($inner, $m[0]);
        if ($pos !== false) return substr($inner, 0, $pos) . $newTag . substr($inner, $pos + strlen($m[0]));
        return $inner;
    }
    if ($cls === '') return '<div' . $attr . '>' . $inner . '</div>';
    return '<div class="' . $cls . '"' . $attr . '>' . $inner . '</div>';
}

function render_berita_grid(array $rows, string $style, int $kolom, string $animAttr, bool $showRingkasan = true, bool $denganNomor = false): void
{
    $style = section_normalize_style($style);
    if ($style === 'magazine') {
        // 1 sorotan besar kiri + 4 kartu mini kanan agar selalu muat.
        $rows = array_slice($rows, 0, 5);
        echo '<div class="grid gap-4 md:grid-cols-5 md:items-stretch">';
        foreach ($rows as $i => $item) {
            if ($i === 0) {
                echo anim_item_html('<div class="flex md:col-span-3">' . berita_sorotan_html($item) . '</div>', $animAttr, $i);
                if (isset($rows[1])) {
                    echo '<div class="grid grid-cols-2 content-between gap-3 md:col-span-2">';
                    foreach (array_slice($rows, 1, 4) as $k => $sub) echo anim_item_html(berita_card_mini_html($sub), $animAttr, $k + 1);
                    echo '</div>';
                }
                break;
            }
        }
        echo '</div>';
        return;
    }
    if ($style === 'sorotan-list') {
        // Sorotan 5 berita: 1 besar setinggi 4 list kompak. Sisa (berita ke-6 dst) tampil sebagai kartu.
        echo '<div class="grid gap-4 md:grid-cols-5 md:items-stretch">';
        foreach ($rows as $i => $item) {
            if ($i === 0) {
                echo anim_item_html('<div class="flex md:col-span-3">' . berita_sorotan_html($item) . '</div>', $animAttr, $i);
                if (isset($rows[1])) {
                    echo '<div class="grid content-between gap-3 md:col-span-2">';
                    foreach (array_slice($rows, 1, 4) as $k => $sub) echo anim_item_html(berita_list_kompak_html($sub), $animAttr, $k + 1);
                    echo '</div>';
                }
                break;
            }
        }
        if (count($rows) > 5) {
            echo '</div><div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3 mt-6">';
            foreach (array_slice($rows, 5) as $k => $item) echo anim_item_html(berita_card_html($item, $showRingkasan), $animAttr, $k);
            echo '</div>';
        } else {
            echo '</div>';
        }
        return;
    }
    if ($style === 'kartu-horizontal') {
        echo '<div class="grid gap-6 xl:grid-cols-2">';
        foreach ($rows as $i => $item) echo anim_item_html(berita_list_html($item), $animAttr, $i);
        echo '</div>';
        return;
    }
    if ($style === 'timeline') {
        echo '<div class="max-w-2xl">';
        foreach ($rows as $i => $item) echo anim_item_html(berita_timeline_html($item), $animAttr, $i);
        echo '</div>';
        return;
    }
    if ($style === 'minimal') {
        echo '<div class="rounded-2xl bg-white border border-slate-200 px-6 py-2 shadow-sm">';
        foreach ($rows as $i => $item) echo anim_item_html(berita_minimal_html($item), $animAttr, $i);
        echo '</div>';
        return;
    }
    if ($style === 'list') {
        echo '<div class="flex flex-col gap-4">';
        foreach ($rows as $i => $item) {
            if ($denganNomor) echo anim_item_html('<div class="flex gap-4 items-start"><span class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 text-white font-black">' . ($i + 1) . '</span><div class="flex-1 min-w-0">' . berita_list_html($item) . '</div></div>', $animAttr, $i);
            else echo anim_item_html(berita_list_html($item), $animAttr, $i);
        }
        echo '</div>';
        return;
    }
    if ($style === 'overlay') {
        echo '<div class="' . section_grid_class($style, $kolom) . '">';
        foreach ($rows as $i => $item) echo anim_item_html(berita_overlay_html($item, $denganNomor ? $i + 1 : null), $animAttr, $i);
        echo '</div>';
        return;
    }
    if ($style === 'masonry') {
        // Rasio bervariasi mengikuti preview admin (70% / 100% / 55%).
        $aspects = ['aspect-[4/3]', 'aspect-square', 'aspect-[4/5]'];
        echo '<div class="columns-1 sm:columns-2 xl:columns-3 gap-6">';
        foreach ($rows as $i => $item) {
            $card = berita_card_html($item, $showRingkasan);
            $card = str_replace('aspect-[16/10]', $aspects[$i % count($aspects)], $card);
            echo anim_item_html('<div class="break-inside-avoid mb-6">' . $card . '</div>', $animAttr, $i);
        }
        echo '</div>';
        return;
    }
    echo '<div class="' . section_grid_class($style, $kolom) . '">';
    foreach ($rows as $i => $item) echo anim_item_html(berita_card_html($item, $showRingkasan), $animAttr, $i);
    echo '</div>';
}

function section_header_html(string $judul, string $linkUrl = '', string $linkText = 'Lihat semua'): string
{
    $h = '<div class="mb-6 flex items-center justify-between gap-4"><div class="flex items-center gap-3">'
        . '<div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>'
        . '<h2 class="text-2xl font-black text-slate-900">' . htmlspecialchars($judul) . '</h2></div>';
    if ($linkUrl !== '') {
        $h .= '<a href="' . htmlspecialchars($linkUrl) . '" class="inline-flex items-center gap-2 text-sm font-bold text-purple-600 transition hover:text-purple-700 group">' . htmlspecialchars($linkText)
            . '<svg class="w-4 h-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></a>';
    }
    return $h . '</div>';
}

function render_home_section(mysqli $conn, array $sec): void
{
    $tipe = (string)($sec['tipe'] ?? 'latest');
    $judul = trim((string)($sec['judul'] ?? ''));
    $animRaw = trim((string)($sec['animasi'] ?? 'fade-up'));
    if ($animRaw === '' || $animRaw === 'tanpa') $animAttr = '';
    else $animAttr = ' data-animate="' . htmlspecialchars(isset(section_animasi_list()[$animRaw]) ? $animRaw : 'fade-up') . '"';
    $cfg = array_merge(section_default_cfg($tipe), section_pengaturan($sec));
    $sid = (int)$sec['id'];

    if ($tipe === 'hero') {
        $subjudul = trim((string)section_opt($cfg, 'subjudul', ''));
        $isiHero = trim((string)section_opt($cfg, 'isi', ''));
        $gambarHero = trim((string)section_opt($cfg, 'gambar', ''));
        $tombolTeks = trim((string)section_opt($cfg, 'tombol_teks', ''));
        $tombolLink = trim((string)section_opt($cfg, 'tombol_link', ''));
        if ($subjudul !== '' || $isiHero !== '' || $gambarHero !== '' || $tombolTeks !== '') {
            $gaya = section_media_gaya_class($cfg);
            echo '<section class="mb-12"' . $animAttr . '><div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-emerald-600 to-teal-700" style="min-height:380px;">';
            if ($gambarHero !== '') echo '<div class="absolute inset-0 overflow-hidden' . $gaya . '"><img src="' . htmlspecialchars(berita_image_url($gambarHero)) . '" alt="' . htmlspecialchars($judul) . '" class="h-full w-full object-cover"></div>';
            echo '<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>';
            echo '<div class="relative p-8 md:p-14 max-w-2xl">';
            if ($judul !== '') echo '<h1 class="text-3xl md:text-5xl font-black leading-tight text-white mb-3">' . htmlspecialchars($judul) . '</h1>';
            if ($subjudul !== '') echo '<p class="text-lg md:text-xl font-semibold text-emerald-200 mb-3">' . htmlspecialchars($subjudul) . '</p>';
            if ($isiHero !== '') echo '<div class="text-white/85 leading-relaxed mb-6">' . nl2br(htmlspecialchars($isiHero)) . '</div>';
            if ($tombolTeks !== '') echo '<a href="' . htmlspecialchars($tombolLink !== '' ? $tombolLink : '#') . '" class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3 font-bold text-emerald-700 shadow-lg hover:scale-105 transition">' . htmlspecialchars($tombolTeks) . '</a>';
            echo '</div></div></section>';
            return;
        }
        $jumlah = max(1, min(10, (int)section_opt($cfg, 'jumlah', 5)));
        $rows = [];
        $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        if (empty($rows)) return;
        $cid = 'heroCarousel' . $sid;
        $gayaHero = section_media_gaya_class($cfg);
        ?>
        <section class="mb-12"<?php echo $animAttr; ?>>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 to-blue-600 shadow-xl" id="<?php echo $cid; ?>">
                <div class="relative" style="min-height: 500px;">
                    <?php foreach ($rows as $index => $news): ?>
                        <div class="carousel-slide absolute inset-0 transition-opacity duration-700 <?php echo $index === 0 ? 'opacity-100 z-10 visible' : 'opacity-0 z-0 invisible pointer-events-none'; ?>" data-slide="<?php echo $index; ?>" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                            <a href="<?php echo htmlspecialchars(berita_url($news)); ?>" class="block h-full">
                                <?php if (!empty($news['gambar'])): ?>
                                    <div class="absolute inset-0 image-zoom overflow-hidden<?php echo $gayaHero; ?>"><img src="<?php echo htmlspecialchars(berita_image_url($news['gambar'])); ?>" alt="<?php echo htmlspecialchars($news['judul']); ?>" class="h-full w-full object-cover"></div>
                                <?php else: ?>
                                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-purple-500 to-blue-500"></div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"></div>
                                <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12">
                                    <span class="inline-block badge-category mb-4"><?php echo htmlspecialchars($news['kategori_nama'] ?? 'Berita Terbaru'); ?></span>
                                    <h1 class="text-3xl md:text-5xl font-black leading-tight text-white mb-4 max-w-4xl"><?php echo htmlspecialchars($news['judul']); ?></h1>
                                    <p class="text-base md:text-lg text-white/90 line-clamp-2 mb-4 max-w-3xl"><?php echo htmlspecialchars($news['ringkasan'] ?? ''); ?></p>
                                    <div class="flex items-center gap-4 text-sm text-white/85">
                                        <span><?php echo formatTanggalIndonesia($news['tanggal_publikasi'], true); ?></span>
                                        <?php if (!empty($news['penulis'])): ?><span class="text-white/40">•</span><span><?php echo htmlspecialchars($news['penulis']); ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/30 transition" data-prev="<?php echo $cid; ?>"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg></button>
                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/30 transition" data-next="<?php echo $cid; ?>"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg></button>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2" data-dots="<?php echo $cid; ?>">
                    <?php foreach ($rows as $index => $n): ?>
                        <button type="button" class="rounded-full transition-all <?php echo $index === 0 ? 'bg-white w-8 h-3' : 'bg-white/50 w-3 h-3'; ?>" data-slide="<?php echo $index; ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <script>
        (function() {
            var cid = <?php echo json_encode($cid); ?>;
            var wrap = document.getElementById(cid);
            if (!wrap || wrap.dataset.init) return;
            wrap.dataset.init = '1';
            var slides = wrap.querySelectorAll('.carousel-slide');
            var dots = document.querySelectorAll('[data-dots="' + cid + '"] button');
            var cur = 0, timer = null;
            function show(i) {
                slides.forEach(function(s, k) {
                    var on = k === i;
                    s.classList.toggle('opacity-100', on);
                    s.classList.toggle('z-10', on);
                    s.classList.toggle('visible', on);
                    s.classList.toggle('opacity-0', !on);
                    s.classList.toggle('z-0', !on);
                    s.classList.toggle('invisible', !on);
                    s.classList.toggle('pointer-events-none', !on);
                    s.setAttribute('aria-hidden', on ? 'false' : 'true');
                });
                dots.forEach(function(d, k) {
                    d.className = 'rounded-full transition-all ' + (k === i ? 'bg-white w-8 h-3' : 'bg-white/50 w-3 h-3');
                });
                cur = i;
            }
            function next() { show((cur + 1) % slides.length); }
            function prev() { show((cur - 1 + slides.length) % slides.length); }
            function start() { timer = setInterval(next, 5000); }
            function stop() { if (timer) clearInterval(timer); }
            var bN = document.querySelector('[data-next="' + cid + '"]');
            var bP = document.querySelector('[data-prev="' + cid + '"]');
            if (bN) bN.addEventListener('click', function() { stop(); next(); start(); });
            if (bP) bP.addEventListener('click', function() { stop(); prev(); start(); });
            dots.forEach(function(d) { d.addEventListener('click', function() { stop(); show(parseInt(this.dataset.slide || '0', 10)); start(); }); });
            wrap.addEventListener('mouseenter', stop);
            wrap.addEventListener('mouseleave', start);
            start();
        })();
        </script>
        <?php
        return;
    }

    if ($tipe === 'latest') {
        $jumlah = max(1, min(20, (int)section_opt($cfg, 'jumlah', 8)));
        $kolom = max(2, min(4, (int)section_opt($cfg, 'kolom', 4)));
        $tombol = (int)section_opt($cfg, 'tampil_tombol', 1) === 1;
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'sorotan-list'));
        $rows = [];
        $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        if (empty($rows)) return;
        echo '<section class="mb-12"' . $animAttr . '>';
        echo section_header_html($judul !== '' ? $judul : 'Berita Terkini');
        render_berita_grid($rows, $style, $kolom, $animAttr, true);
        if ($tombol) {
            echo '<div class="mt-8 text-center"><a href="semua-kategori" class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold shadow-lg hover:shadow-xl hover:scale-105 transition transform">Lihat Semua Berita</a></div>';
        }
        echo '</section>';
        return;
    }

    if ($tipe === 'kategori_berita' || $tipe === 'kategori') {
        $katId = (int)section_opt($cfg, 'kategori_id', 0);
        $jumlah = max(1, min(12, (int)section_opt($cfg, 'jumlah', 4)));
        $kolom = max(2, min(4, (int)section_opt($cfg, 'kolom', 4)));
        $kat = null;
        if ($katId > 0) {
            $st = $conn->prepare("SELECT id, nama FROM kategori WHERE id = ? LIMIT 1");
            $st->bind_param('i', $katId);
            $st->execute();
            $kat = $st->get_result()->fetch_assoc();
            $st->close();
        }
        if (!$kat) {
            $rk = $conn->query("SELECT id, nama FROM kategori ORDER BY id ASC LIMIT 1");
            if ($rk) $kat = $rk->fetch_assoc();
        }
        if (!$kat) return;
        $katId = (int)$kat['id'];
        $rows = [];
        $st = $conn->prepare("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.kategori_id = ? AND b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
        $st->bind_param('i', $katId);
        $st->execute();
        $res = $st->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $st->close();
        if (empty($rows)) return;
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'kartu-4'));
        echo '<section class="mb-12"' . $animAttr . '>';
        echo section_header_html($judul !== '' ? $judul : $kat['nama'], 'kategori?kategori=' . (int)$kat['id']);
        render_berita_grid($rows, $style, $kolom, $animAttr, false);
        echo '</section>';
        return;
    }

    if ($tipe === 'populer') {
        $jumlah = max(1, min(10, (int)section_opt($cfg, 'jumlah', 5)));
        $kolom = max(2, min(4, (int)section_opt($cfg, 'kolom', 4)));
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'overlay'));
        $rows = [];
        $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.views DESC, b.id DESC LIMIT $jumlah");
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        if (empty($rows)) return;
        echo '<section class="mb-12"' . $animAttr . '>';
        echo section_header_html($judul !== '' ? $judul : 'Paling Dibaca');
        render_berita_grid($rows, $style, $kolom, $animAttr, false, true);
        echo '</section>';
        return;
    }

    if ($tipe === 'carousel') {
        $galeri = section_galeri_list($cfg);
        $auto = max(0, min(30, (int)section_opt($cfg, 'autoplay', 5)));
        if (empty($galeri)) return;
        $cid = 'sbCar' . $sid;
        $gaya = section_media_gaya_class($cfg);
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '') echo section_header_html($judul);
        echo '<div class="relative overflow-hidden rounded-2xl shadow-xl bg-slate-900" id="' . $cid . '"><div class="relative" style="min-height:340px;">';
        foreach ($galeri as $i => $g) {
            $src = htmlspecialchars(berita_image_url($g));
            echo '<div class="sb-slide absolute inset-0 transition-opacity duration-700 ' . ($i === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0') . '"><div class="absolute inset-0 overflow-hidden' . $gaya . '"><img src="' . $src . '" alt="" class="h-full w-full object-cover"></div></div>';
        }
        echo '</div>';
        echo '<button type="button" data-prev="' . $cid . '" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30">‹</button>';
        echo '<button type="button" data-next="' . $cid . '" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30">›</button>';
        echo '<div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2" data-dots="' . $cid . '">';
        foreach ($galeri as $i => $g) echo '<button type="button" data-slide="' . $i . '" class="rounded-full ' . ($i === 0 ? 'bg-white w-8 h-3' : 'bg-white/50 w-3 h-3') . '"></button>';
        echo '</div></div></section>';
        echo '<script>(function(){var cid=' . json_encode($cid) . ',w=document.getElementById(cid);if(!w||w.dataset.init)return;w.dataset.init="1";var s=w.querySelectorAll(".sb-slide"),d=document.querySelectorAll(\'[data-dots="\'+cid+\'"] button\'),c=0,t=null;function sh(i){s.forEach(function(x,k){x.classList.toggle("opacity-100",k===i);x.classList.toggle("z-10",k===i);x.classList.toggle("opacity-0",k!==i);x.classList.toggle("z-0",k!==i)});d.forEach(function(x,k){x.className="rounded-full "+(k===i?"bg-white w-8 h-3":"bg-white/50 w-3 h-3")});c=i}function nx(){sh((c+1)%s.length)}function st(){var a=' . (int)$auto . ';if(a>0)t=setInterval(nx,a*1000)}function sp(){if(t)clearInterval(t)}var bN=document.querySelector(\'[data-next="\'+cid+\'"]\'),bP=document.querySelector(\'[data-prev="\'+cid+\'"]\');if(bN)bN.addEventListener("click",function(){sp();nx();st()});if(bP)bP.addEventListener("click",function(){sp();sh((c-1+s.length)%s.length);st()});d.forEach(function(x){x.addEventListener("click",function(){sp();sh(parseInt(this.dataset.slide||"0",10));st()})});w.addEventListener("mouseenter",sp);w.addEventListener("mouseleave",st);st()})();</script>';
        return;
    }

    if ($tipe === 'carousel_berita') {
        $jumlah = max(1, min(12, (int)section_opt($cfg, 'jumlah', 8)));
        $katId = (int)section_opt($cfg, 'kategori_id', 0);
        $auto = max(0, min(30, (int)section_opt($cfg, 'autoplay', 5)));
        $rows = [];
        if ($katId > 0) {
            $st = $conn->prepare("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.kategori_id = ? AND b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
            $st->bind_param('i', $katId);
            $st->execute();
            $res = $st->get_result();
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $st->close();
        } else {
            $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
            if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        if (empty($rows)) return;
        $cid = 'sbCB' . $sid;
        echo '<section class="mb-12"' . $animAttr . '>';
        echo section_header_html($judul !== '' ? $judul : 'Sorotan Berita');
        echo '<div class="relative"><div id="' . $cid . '" class="sb-hscroll flex gap-5 overflow-x-auto pb-2">';
        foreach ($rows as $i => $item) echo anim_item_html(berita_card_html($item, false), $animAttr, $i, 'w-72 shrink-0');
        echo '</div>';
        echo '<button type="button" data-prev="' . $cid . '" class="hidden md:flex absolute -left-5 top-1/3 w-11 h-11 rounded-full bg-white shadow-xl items-center justify-center text-xl font-black text-purple-700">‹</button>';
        echo '<button type="button" data-next="' . $cid . '" class="hidden md:flex absolute -right-5 top-1/3 w-11 h-11 rounded-full bg-white shadow-xl items-center justify-center text-xl font-black text-purple-700">›</button></div></section>';
        echo '<script>(function(){var cid=' . json_encode($cid) . ',w=document.getElementById(cid);if(!w)return;function go(d){w.scrollBy({left:d,behavior:"smooth"})}var bN=document.querySelector(\'[data-next="\'+cid+\'"]\'),bP=document.querySelector(\'[data-prev="\'+cid+\'"]\');if(bN)bN.addEventListener("click",function(){go(320)});if(bP)bP.addEventListener("click",function(){go(-320)});var a=' . (int)$auto . ';if(a>0)setInterval(function(){if(w.scrollLeft+w.clientWidth>=w.scrollWidth-10)w.scrollTo({left:0,behavior:"smooth"});else go(320)},a*1000)})();</script>';
        return;
    }

    if ($tipe === 'teks') {
        $isi = (string)section_opt($cfg, 'isi', '');
        $rata = (string)section_opt($cfg, 'rata', 'kiri');
        $align = $rata === 'tengah' ? 'text-center mx-auto' : ($rata === 'kanan' ? 'text-right ml-auto' : 'text-left');
        echo '<section class="mb-12"' . $animAttr . '><div class="max-w-3xl ' . $align . '">';
        if ($judul !== '') echo '<h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-3">' . htmlspecialchars($judul) . '</h2>';
        if ($isi !== '') echo '<div class="text-slate-600 leading-relaxed">' . nl2br(htmlspecialchars($isi)) . '</div>';
        echo '</div></section>';
        return;
    }

    if ($tipe === 'cta') {
        $desk = (string)section_opt($cfg, 'deskripsi', '');
        $txt = (string)section_opt($cfg, 'cta_teks', 'Lihat Selengkapnya');
        $link = (string)section_opt($cfg, 'cta_link', 'semua-kategori');
        if ($txt === '') $txt = 'Lihat Selengkapnya';
        echo '<section class="mb-12"' . $animAttr . '><div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-12 text-center shadow-xl">';
        echo '<div class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/10"></div><div class="absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-white/10"></div>';
        if ($judul !== '') echo '<h2 class="relative text-2xl md:text-3xl font-black text-white mb-3">' . htmlspecialchars($judul) . '</h2>';
        if ($desk !== '') echo '<p class="relative text-white/85 mb-6 max-w-2xl mx-auto">' . htmlspecialchars($desk) . '</p>';
        echo '<a href="' . htmlspecialchars($link) . '" class="relative inline-flex items-center gap-2 rounded-full bg-white px-8 py-3 font-bold text-purple-700 shadow-lg hover:scale-105 transition">' . htmlspecialchars($txt) . '</a>';
        echo '</div></section>';
        return;
    }

    if ($tipe === 'html') {
        $html = (string)section_opt($cfg, 'html', '');
        if (trim($html) === '') return;
        echo '<section class="mb-12"' . $animAttr . '>' . $html . '</section>';
        return;
    }

    if ($tipe === 'countdown') {
        $target = trim((string)section_opt($cfg, 'target_tanggal', ''));
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        if ($target === '') return;
        $cid = 'sbCD' . $sid;
        echo '<section class="mb-12"' . $animAttr . '><div class="rounded-2xl bg-slate-900 px-8 py-10 text-center shadow-xl">';
        if ($judul !== '') echo '<h2 class="text-2xl font-black text-white mb-2">' . htmlspecialchars($judul) . '</h2>';
        if ($isi !== '') echo '<p class="text-slate-300 mb-6">' . htmlspecialchars($isi) . '</p>';
        echo '<div id="' . $cid . '" class="flex justify-center gap-3 md:gap-5" data-target="' . htmlspecialchars($target) . '">';
        foreach (['Hari' => 'd', 'Jam' => 'h', 'Menit' => 'm', 'Detik' => 's'] as $lbl => $k) {
            echo '<div class="w-20 rounded-2xl bg-white/10 py-4"><div class="text-3xl font-black text-white" data-' . $k . '>00</div><div class="text-xs uppercase tracking-wider text-slate-300">' . $lbl . '</div></div>';
        }
        echo '</div></div></section>';
        echo '<script>(function(){var el=document.getElementById(' . json_encode($cid) . ');if(!el)return;var t=new Date(el.dataset.target).getTime();if(isNaN(t))return;function pad(n){return String(n).padStart(2,"0")}function tick(){var s=Math.max(0,Math.floor((t-Date.now())/1000));el.querySelector("[data-d]").textContent=pad(Math.floor(s/86400));el.querySelector("[data-h]").textContent=pad(Math.floor(s%86400/3600));el.querySelector("[data-m]").textContent=pad(Math.floor(s%3600/60));el.querySelector("[data-s]").textContent=pad(s%60)}tick();setInterval(tick,1000)})();</script>';
        return;
    }

    if ($tipe === 'image') {
        $g = trim((string)section_opt($cfg, 'gambar', ''));
        if ($g === '') return;
        $src = htmlspecialchars(berita_image_url($g));
        $alt = htmlspecialchars((string)section_opt($cfg, 'alt', $judul !== '' ? $judul : 'Gambar'));
        $link = trim((string)section_opt($cfg, 'link', ''));
        $gaya = section_media_gaya_class($cfg);
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '') echo section_header_html($judul);
        $imgTag = '<div class="overflow-hidden rounded-2xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . $src . '" alt="' . $alt . '" class="w-full object-cover"></div>';
        if ($link !== '') echo '<a href="' . htmlspecialchars($link) . '" class="block">' . $imgTag . '</a>';
        else echo $imgTag;
        echo '</section>';
        return;
    }

    if ($tipe === 'video') {
        $url = trim((string)section_opt($cfg, 'video_url', ''));
        if ($url === '') return;
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '') echo section_header_html($judul);
        $yt = section_youtube_id($url);
        echo '<div class="overflow-hidden rounded-2xl shadow-sm bg-black aspect-video">';
        if ($yt !== '') echo '<iframe src="https://www.youtube.com/embed/' . $yt . '" class="w-full h-full" style="min-height:360px;" allowfullscreen loading="lazy" title="Video"></iframe>';
        else echo '<video src="' . htmlspecialchars($url) . '" controls class="w-full h-full"' . (trim((string)section_opt($cfg, 'poster', '')) !== '' ? ' poster="' . htmlspecialchars(berita_image_url((string)section_opt($cfg, 'poster', ''))) . '"' : '') . '></video>';
        echo '</div></section>';
        return;
    }

    if ($tipe === 'audio') {
        $url = trim((string)section_opt($cfg, 'audio_url', ''));
        if ($url === '') return;
        echo '<section class="mb-12"' . $animAttr . '><div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">';
        if ($judul !== '') echo '<h3 class="font-black text-slate-900 mb-3">' . htmlspecialchars($judul) . '</h3>';
        echo '<audio src="' . htmlspecialchars($url) . '" controls class="w-full"></audio></div></section>';
        return;
    }

    if ($tipe === 'sambutan') {
        $nama = trim((string)section_opt($cfg, 'nama', ''));
        $jab = trim((string)section_opt($cfg, 'jabatan', ''));
        $foto = trim((string)section_opt($cfg, 'foto', ''));
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        $gaya = section_media_gaya_class($cfg);
        echo '<section class="mb-12"' . $animAttr . '><div class="grid gap-8 md:grid-cols-3 items-center rounded-2xl bg-white border border-slate-200 p-8 shadow-sm">';
        if ($foto !== '') echo '<div class="overflow-hidden rounded-2xl' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($foto)) . '" alt="' . htmlspecialchars($nama) . '" class="w-full aspect-[3/4] object-cover"></div>';
        echo '<div class="md:col-span-2">';
        if ($judul !== '') echo '<h2 class="text-2xl font-black text-slate-900 mb-2">' . htmlspecialchars($judul) . '</h2>';
        if ($isi !== '') echo '<div class="text-slate-600 leading-relaxed mb-4">' . nl2br(htmlspecialchars($isi)) . '</div>';
        if ($nama !== '') echo '<p class="font-black text-slate-900">' . htmlspecialchars($nama) . '</p>';
        if ($jab !== '') echo '<p class="text-sm text-purple-700 font-semibold">' . htmlspecialchars($jab) . '</p>';
        echo '</div></div></section>';
        return;
    }

    if ($tipe === 'statistik') {
        echo '<section class="mb-12"' . $animAttr . '><div class="rounded-2xl bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-10 shadow-xl">';
        if ($judul !== '') echo '<h2 class="text-2xl font-black text-white text-center mb-8">' . htmlspecialchars($judul) . '</h2>';
        echo '<div class="grid grid-cols-2 lg:grid-cols-4 gap-6">';
        for ($i = 1; $i <= 4; $i++) {
            $a = trim((string)section_opt($cfg, 'stat' . $i . '_angka', ''));
            $l = trim((string)section_opt($cfg, 'stat' . $i . '_label', ''));
            if ($a === '' && $l === '') continue;
            echo '<div class="text-center"><div class="text-3xl md:text-4xl font-black text-white">' . htmlspecialchars($a) . '</div><div class="text-sm text-white/80 mt-1">' . htmlspecialchars($l) . '</div></div>';
        }
        echo '</div></div></section>';
        return;
    }

    if ($tipe === 'agenda') {
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        $jumlah = max(1, min(10, (int)section_opt($cfg, 'jumlah', 5)));
        $items = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $isi))));
        if (empty($items)) {
            $rows = [];
            $res = $conn->query("SELECT judul, tanggal_publikasi FROM berita WHERE status = 'publish' ORDER BY tanggal_publikasi DESC, id DESC LIMIT $jumlah");
            if ($res) while ($r = $res->fetch_assoc()) $items[] = $r['judul'] . ' — ' . formatTanggalIndonesia($r['tanggal_publikasi']);
        }
        if (empty($items)) return;
        $items = array_slice($items, 0, $jumlah);
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'timeline'));
        echo '<section class="mb-12"' . $animAttr . '><div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">';
        echo section_header_html($judul !== '' ? $judul : 'Agenda');
        if ($style === 'timeline') {
            echo '<div class="max-w-2xl">';
            foreach ($items as $ai => $it) echo anim_item_html('<div class="relative pl-8 pb-5 border-l-2 border-purple-200 last:pb-0"><span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 ring-4 ring-purple-100"></span><span class="text-sm text-slate-700">' . htmlspecialchars($it) . '</span></div>', $animAttr, $ai);
            echo '</div>';
        } elseif ($style === 'minimal') {
            echo '<ul class="divide-y divide-slate-100">';
            foreach ($items as $ai => $it) echo anim_item_html('<li class="py-3 text-sm font-semibold text-slate-800">' . htmlspecialchars($it) . '</li>', $animAttr, $ai);
            echo '</ul>';
        } elseif (in_array($style, ['kartu-2', 'kartu-3', 'kartu-4'], true)) {
            $cols = $style === 'kartu-2' ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3';
            echo '<div class="grid gap-4 ' . $cols . '">';
            foreach ($items as $ai => $it) echo anim_item_html('<div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700"><span class="mb-1 inline-block h-2.5 w-2.5 rounded-full bg-purple-600"></span><br>' . htmlspecialchars($it) . '</div>', $animAttr, $ai);
            echo '</div>';
        } elseif ($style === 'overlay') {
            echo '<div class="rounded-2xl bg-gradient-to-r from-purple-600 to-blue-600 p-6 text-white"><ul class="space-y-2">';
            foreach ($items as $ai => $it) echo anim_item_html('<li class="text-sm">• ' . htmlspecialchars($it) . '</li>', $animAttr, $ai);
            echo '</ul></div>';
        } else {
            echo '<ul class="space-y-3">';
            foreach ($items as $ai => $it) echo anim_item_html('<li class="flex gap-3 items-start rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700"><span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-purple-600 shrink-0"></span><span>' . htmlspecialchars($it) . '</span></li>', $animAttr, $ai);
            echo '</ul>';
        }
        echo '</div></section>';
        return;
    }

    if ($tipe === 'pengumuman') {
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        $tgl = trim((string)section_opt($cfg, 'tanggal', ''));
        if ($isi === '' && $judul === '') return;
        echo '<section class="mb-12"' . $animAttr . '><div class="rounded-2xl border-l-8 border-amber-500 bg-amber-50 px-6 py-6 shadow-sm">';
        if ($judul !== '') echo '<h2 class="text-xl font-black text-slate-900 mb-1">📢 ' . htmlspecialchars($judul) . '</h2>';
        if ($tgl !== '') echo '<p class="text-xs font-bold text-amber-700 mb-2">' . htmlspecialchars($tgl) . '</p>';
        if ($isi !== '') echo '<div class="text-sm text-slate-700 leading-relaxed">' . nl2br(htmlspecialchars($isi)) . '</div>';
        echo '</div></section>';
        return;
    }

    if ($tipe === 'galeri') {
        $galeri = section_galeri_list($cfg);
        if (empty($galeri)) return;
        $kolom = max(2, min(6, (int)section_opt($cfg, 'kolom', 4)));
        $gaya = section_media_gaya_class($cfg);
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'kartu-4'));
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '') echo section_header_html($judul);
        if ($style === 'masonry') {
            echo '<div class="columns-2 md:columns-3 gap-4">';
            foreach ($galeri as $gi => $g) echo anim_item_html('<div class="break-inside-avoid mb-4 overflow-hidden rounded-xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full object-cover hover:scale-105 transition duration-500"></div>', $animAttr, $gi);
            echo '</div>';
        } elseif ($style === 'sorotan-list' || $style === 'magazine') {
            $first = $galeri[0];
            $rest = array_slice($galeri, 1, 4);
            echo '<div class="grid gap-4 lg:grid-cols-2">' . anim_item_html('<div class="overflow-hidden rounded-2xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($first)) . '" alt="" class="w-full h-full min-h-[280px] object-cover"></div>', $animAttr, 0);
            echo '<div class="grid grid-cols-2 gap-4">';
            foreach ($rest as $gi => $g) echo anim_item_html('<div class="overflow-hidden rounded-xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full aspect-square object-cover hover:scale-105 transition duration-500"></div>', $animAttr, $gi + 1);
            echo '</div></div>';
        } elseif ($style === 'kartu-horizontal' || $style === 'list') {
            echo '<div class="grid gap-4 md:grid-cols-2">';
            foreach ($galeri as $gi => $g) echo anim_item_html('<div class="flex gap-4 items-center rounded-2xl bg-white border border-slate-200 p-3 shadow-sm"><div class="w-32 h-24 shrink-0 overflow-hidden rounded-xl' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full h-full object-cover"></div><div class="text-sm font-bold text-slate-700">Galeri</div></div>', $animAttr, $gi);
            echo '</div>';
        } elseif ($style === 'timeline') {
            echo '<div class="max-w-2xl">';
            foreach ($galeri as $i => $g) echo anim_item_html('<div class="relative pl-8 pb-6 border-l-2 border-purple-200 last:pb-0"><span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 ring-4 ring-purple-100"></span><div class="overflow-hidden rounded-xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full aspect-video object-cover"></div></div>', $animAttr, $i);
            echo '</div>';
        } elseif ($style === 'minimal') {
            echo '<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">';
            foreach ($galeri as $gi => $g) echo anim_item_html('<div class="overflow-hidden rounded-lg' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full aspect-square object-cover"></div>', $animAttr, $gi);
            echo '</div>';
        } elseif ($style === 'overlay') {
            echo '<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">';
            foreach ($galeri as $i => $g) echo anim_item_html('<div class="group relative overflow-hidden rounded-2xl shadow-sm h-56"><div class="absolute inset-0 overflow-hidden' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110"></div><div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div><span class="absolute bottom-3 left-4 text-sm font-black text-white">Foto ' . ($i + 1) . '</span></div>', $animAttr, $i);
            echo '</div>';
        } else {
            $cols = 'sm:grid-cols-2 xl:grid-cols-4';
            if ($kolom === 2) $cols = 'md:grid-cols-2';
            elseif ($kolom === 3) $cols = 'sm:grid-cols-2 md:grid-cols-3';
            elseif ($kolom >= 5) $cols = 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-' . min(6, $kolom);
            elseif ($style === 'kartu-2') $cols = 'md:grid-cols-2';
            elseif ($style === 'kartu-3') $cols = 'sm:grid-cols-2 md:grid-cols-3';
            echo '<div class="grid gap-4 grid-cols-2 ' . $cols . '">';
            foreach ($galeri as $gi => $g) echo anim_item_html('<div class="overflow-hidden rounded-xl shadow-sm' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="" class="w-full aspect-square object-cover hover:scale-105 transition duration-500"></div>', $animAttr, $gi);
            echo '</div>';
        }
        echo '</section>';
        return;
    }

    if ($tipe === 'guru') {
        $nama = trim((string)section_opt($cfg, 'nama', ''));
        $mapel = trim((string)section_opt($cfg, 'mapel', ''));
        $foto = trim((string)section_opt($cfg, 'foto', ''));
        $quote = trim((string)section_opt($cfg, 'quote', ''));
        if ($nama === '' && $foto === '') return;
        $gaya = section_media_gaya_class($cfg);
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'kartu-4'));
        $kartu = '<div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm text-center h-full">';
        if ($foto !== '') $kartu .= '<div class="w-28 h-28 mx-auto overflow-hidden rounded-full mb-4' . $gaya . '"><img loading="lazy" src="' . htmlspecialchars(berita_image_url($foto)) . '" alt="' . htmlspecialchars($nama) . '" class="w-full h-full object-cover"></div>';
        if ($nama !== '') $kartu .= '<h3 class="font-black text-slate-900">' . htmlspecialchars($nama) . '</h3>';
        if ($mapel !== '') $kartu .= '<p class="text-sm font-bold text-purple-700 mb-2">' . htmlspecialchars($mapel) . '</p>';
        if ($quote !== '') $kartu .= '<p class="text-sm text-slate-600 italic">“' . htmlspecialchars($quote) . '”</p>';
        $kartu .= '</div>';
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '' && $nama !== '') echo section_header_html($judul);
        elseif ($judul !== '' && $nama === '') echo section_header_html($judul);
        if ($style === 'kartu-horizontal' || $style === 'list') {
            echo '<div class="max-w-2xl">' . str_replace('text-center', 'text-left flex gap-5 items-center', $kartu) . '</div>';
        } elseif ($style === 'timeline') {
            echo '<div class="max-w-2xl"><div class="relative pl-8 border-l-2 border-purple-200"><span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 ring-4 ring-purple-100"></span>' . $kartu . '</div></div>';
        } elseif ($style === 'minimal') {
            echo '<div class="max-w-xl rounded-2xl bg-white border border-slate-200 px-6 py-4 shadow-sm"><div class="font-black text-slate-900">' . htmlspecialchars($nama) . '</div><div class="text-sm text-purple-700 font-semibold">' . htmlspecialchars($mapel) . '</div></div>';
        } elseif ($style === 'overlay') {
            echo '<div class="max-w-sm mx-auto relative overflow-hidden rounded-2xl shadow-sm h-80 group">';
            if ($foto !== '') echo '<img loading="lazy" src="' . htmlspecialchars(berita_image_url($foto)) . '" alt="" class="absolute inset-0 h-full w-full object-cover">';
            echo '<div class="absolute inset-0 bg-gradient-to-t from-black/85 to-transparent"></div><div class="absolute bottom-0 p-5"><h3 class="text-lg font-black text-white">' . htmlspecialchars($nama) . '</h3><p class="text-sm text-white/80">' . htmlspecialchars($mapel) . '</p></div></div>';
        } elseif ($style === 'masonry') {
            echo '<div class="columns-1 sm:columns-2 lg:columns-3 gap-6"><div class="break-inside-avoid mb-6">' . $kartu . '</div></div>';
        } elseif ($style === 'sorotan-list' || $style === 'magazine') {
            echo '<div class="grid gap-6 lg:grid-cols-2 items-start"><div>' . $kartu . '</div><div class="rounded-2xl bg-purple-50 border border-purple-100 p-6 text-sm text-slate-600">Tambahkan quote atau profil lain via section kedua untuk melengkapi sorotan.</div></div>';
        } else {
            echo '<div class="max-w-sm mx-auto">' . $kartu . '</div>';
        }
        echo '</section>';
        return;
    }

    if ($tipe === 'prestasi') {
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        $tahun = trim((string)section_opt($cfg, 'tahun', ''));
        if ($isi === '') return;
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'sorotan-list'));
        $kartu = '<div class="rounded-2xl bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200 p-6 shadow-sm h-full">';
        $kartu .= '<div class="flex items-center gap-3 mb-2"><span class="text-3xl">🏆</span><h3 class="text-xl font-black text-slate-900">' . htmlspecialchars($judul !== '' ? $judul : 'Prestasi') . '</h3></div>';
        if ($tahun !== '') $kartu .= '<p class="text-xs font-bold text-amber-700 mb-2">' . htmlspecialchars($tahun) . '</p>';
        $kartu .= '<div class="text-sm text-slate-700 leading-relaxed">' . nl2br(htmlspecialchars($isi)) . '</div></div>';
        echo '<section class="mb-12"' . $animAttr . '>';
        if (in_array($style, ['kartu-2', 'kartu-3', 'kartu-4'], true)) {
            $cols = $style === 'kartu-2' ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3';
            echo '<div class="grid gap-6 ' . $cols . '"><div>' . $kartu . '</div></div>';
        } elseif ($style === 'timeline') {
            echo '<div class="max-w-2xl"><div class="relative pl-8 border-l-2 border-amber-300"><span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-amber-500 ring-4 ring-amber-100"></span>' . $kartu . '</div></div>';
        } elseif ($style === 'minimal') {
            echo '<div class="max-w-2xl rounded-2xl bg-white border border-slate-200 px-6 py-4 shadow-sm"><div class="text-xs font-bold text-amber-700">' . htmlspecialchars($tahun) . '</div><div class="font-bold text-slate-900">' . nl2br(htmlspecialchars($isi)) . '</div></div>';
        } elseif ($style === 'kartu-horizontal' || $style === 'list') {
            echo '<div class="max-w-2xl">' . $kartu . '</div>';
        } elseif ($style === 'overlay') {
            echo '<div class="max-w-2xl relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 p-8 text-white shadow-sm"><h3 class="text-2xl font-black mb-2">' . htmlspecialchars($judul !== '' ? $judul : 'Prestasi') . '</h3><div class="text-white/90 text-sm">' . nl2br(htmlspecialchars($isi)) . '</div></div>';
        } elseif ($style === 'masonry') {
            echo '<div class="columns-1 sm:columns-2 gap-6"><div class="break-inside-avoid mb-6">' . $kartu . '</div></div>';
        } else {
            echo '<div class="grid gap-6 lg:grid-cols-2"><div>' . $kartu . '</div><div class="rounded-2xl bg-white border border-slate-200 p-6 text-sm text-slate-500">Tambahkan prestasi lain sebagai section baru agar tampil berdampingan.</div></div>';
        }
        echo '</section>';
        return;
    }

    if ($tipe === 'ekskul') {
        $nama = trim((string)section_opt($cfg, 'nama', ''));
        $jadwal = trim((string)section_opt($cfg, 'jadwal', ''));
        $desk = trim((string)section_opt($cfg, 'deskripsi', ''));
        if ($nama === '' && $desk === '') return;
        $style = section_normalize_style((string)section_opt($cfg, 'style_grid', 'kartu-4'));
        $kartu = '<div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm h-full">';
        $kartu .= '<div class="flex items-center gap-3 mb-2"><span class="text-3xl">⚽</span><h3 class="text-xl font-black text-slate-900">' . htmlspecialchars($nama !== '' ? $nama : ($judul !== '' ? $judul : 'Ekskul')) . '</h3></div>';
        if ($jadwal !== '') $kartu .= '<p class="text-xs font-bold text-emerald-700 mb-2">🗓️ ' . htmlspecialchars($jadwal) . '</p>';
        if ($desk !== '') $kartu .= '<p class="text-sm text-slate-600">' . nl2br(htmlspecialchars($desk)) . '</p>';
        $kartu .= '</div>';
        echo '<section class="mb-12"' . $animAttr . '>';
        if ($judul !== '' && $nama !== '') echo section_header_html($judul);
        if (in_array($style, ['kartu-2', 'kartu-3', 'kartu-4'], true)) {
            $cols = $style === 'kartu-2' ? 'md:grid-cols-2' : ($style === 'kartu-3' ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-4');
            echo '<div class="grid gap-6 ' . $cols . '"><div>' . $kartu . '</div></div>';
        } elseif ($style === 'timeline') {
            echo '<div class="max-w-2xl"><div class="relative pl-8 border-l-2 border-emerald-200"><span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></span>' . $kartu . '</div></div>';
        } elseif ($style === 'minimal') {
            echo '<div class="max-w-2xl rounded-2xl bg-white border border-slate-200 px-6 py-4 shadow-sm"><div class="font-black text-slate-900">' . htmlspecialchars($nama) . ' <span class="text-xs font-bold text-emerald-700">• ' . htmlspecialchars($jadwal) . '</span></div><div class="text-sm text-slate-600">' . nl2br(htmlspecialchars($desk)) . '</div></div>';
        } elseif ($style === 'kartu-horizontal' || $style === 'list') {
            echo '<div class="max-w-3xl">' . $kartu . '</div>';
        } elseif ($style === 'overlay') {
            echo '<div class="max-w-3xl relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 p-8 text-white shadow-sm"><h3 class="text-2xl font-black mb-1">' . htmlspecialchars($nama) . '</h3><p class="text-sm text-white/80 mb-2">' . htmlspecialchars($jadwal) . '</p><p class="text-white/90 text-sm">' . nl2br(htmlspecialchars($desk)) . '</p></div>';
        } elseif ($style === 'masonry') {
            echo '<div class="columns-1 sm:columns-2 gap-6"><div class="break-inside-avoid mb-6">' . $kartu . '</div></div>';
        } elseif ($style === 'sorotan-list' || $style === 'magazine') {
            echo '<div class="grid gap-6 lg:grid-cols-2"><div>' . $kartu . '</div><div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-6 text-sm text-slate-600">Tambahkan ekskul lain sebagai section baru.</div></div>';
        } else {
            echo '<div class="max-w-2xl">' . $kartu . '</div>';
        }
        echo '</section>';
        return;
    }
}

function render_sidebar_widgets(mysqli $conn, array $settings, array $sections): void
{
    if (empty($sections)) {
        echo '<aside class="sb-empty rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-8 text-center text-sm text-slate-500">Belum ada widget sidebar.<br>Tambahkan via Admin &gt; Sections &gt; Sidebar.</aside>';
        return;
    }
    echo '<aside class="flex flex-col gap-6 lg:sticky lg:top-40">';
    foreach ($sections as $sec) render_sidebar_widget($conn, $settings, $sec);
    echo '</aside>';
}

function sidebar_card_open(array $sec, string $judul): string
{
    $anim = section_anim_attr((string)($sec['animasi'] ?? 'fade-up'));
    $h = '<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"' . $anim . '>';
    $label = $judul !== '' ? $judul : (section_widget_meta()[(string)($sec['tipe'] ?? '')]['label'] ?? 'Widget');
    $h .= '<div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">'
        . '<span class="h-7 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></span>'
        . '<h3 class="text-base font-black text-slate-900">' . htmlspecialchars($label) . '</h3></div>'
        . '<div class="p-5">';
    return $h;
}

function render_sidebar_widget(mysqli $conn, array $settings, array $sec): void
{
    $tipe = (string)($sec['tipe'] ?? 'teks');
    $judul = trim((string)($sec['judul'] ?? ''));
    $cfg = array_merge(section_default_cfg($tipe), section_pengaturan($sec));

    if ($tipe === 'search') {
        $ph = trim((string)section_opt($cfg, 'placeholder', 'Cari berita...'));
        if ($ph === '') $ph = 'Cari berita...';
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Cari Berita');
        echo '<form action="cari" method="get" class="relative">'
            . '<input type="text" name="q" placeholder="' . htmlspecialchars($ph) . '" class="w-full rounded-full border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-purple-400 focus:bg-white focus:ring-2 focus:ring-purple-100">'
            . '<button type="submit" aria-label="Cari" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-purple-600">'
            . '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>'
            . '</button></form>';
        echo '</div></div>';
        return;
    }

    if ($tipe === 'berita_terbaru') {
        $jumlah = max(1, min(10, (int)section_opt($cfg, 'jumlah', 5)));
        $katId = (int)section_opt($cfg, 'kategori_id', 0);
        $withImg = (int)section_opt($cfg, 'tampil_gambar', 1) === 1;
        $rows = [];
        if ($katId > 0) {
            $st = $conn->prepare("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.kategori_id = ? AND b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
            $st->bind_param('i', $katId);
            $st->execute();
            $res = $st->get_result();
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $st->close();
        } else {
            $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT $jumlah");
            if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Berita Terbaru');
        if (empty($rows)) echo '<p class="text-sm text-slate-500">Belum ada berita.</p>';
        else {
            echo '<div class="flex flex-col gap-4">';
            foreach ($rows as $item) {
                $url = htmlspecialchars(berita_url($item));
                $jt = htmlspecialchars($item['judul'] ?? '');
                $tgl = isset($item['tanggal_publikasi']) ? formatTanggalIndonesia($item['tanggal_publikasi']) : '';
                $img = berita_image_url($item['gambar'] ?? '');
                echo '<a href="' . $url . '" class="group flex gap-3">';
                if ($withImg) {
                    if ($img !== '') echo '<span class="block h-16 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100"><img loading="lazy" src="' . htmlspecialchars($img) . '" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-110"></span>';
                    else echo '<span class="block h-16 w-20 shrink-0 rounded-xl bg-gradient-to-br from-purple-500 to-blue-500"></span>';
                }
                echo '<span class="min-w-0"><span class="mb-1 block text-sm font-bold leading-snug text-slate-900 line-clamp-2 group-hover:text-purple-700">' . $jt . '</span>'
                    . '<span class="block text-xs text-slate-500">' . htmlspecialchars($tgl) . '</span></span></a>';
            }
            echo '</div>';
        }
        echo '</div></div>';
        return;
    }

    if ($tipe === 'populer') {
        $jumlah = max(1, min(10, (int)section_opt($cfg, 'jumlah', 5)));
        $rows = [];
        $res = $conn->query("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.status = 'publish' ORDER BY b.views DESC, b.id DESC LIMIT $jumlah");
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Paling Dibaca');
        if (empty($rows)) echo '<p class="text-sm text-slate-500">Belum ada berita.</p>';
        else {
            echo '<ol class="flex flex-col gap-4">';
            foreach ($rows as $i => $item) {
                $url = htmlspecialchars(berita_url($item));
                echo '<li class="flex items-start gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-blue-600 text-sm font-black text-white">' . ($i + 1) . '</span>'
                    . '<span class="min-w-0"><a href="' . $url . '" class="block text-sm font-bold leading-snug text-slate-900 line-clamp-2 hover:text-purple-700">' . htmlspecialchars($item['judul']) . '</a>'
                    . '<span class="mt-0.5 block text-xs text-slate-500">' . number_format((int)($item['views'] ?? 0)) . ' dibaca</span></span></li>';
            }
            echo '</ol>';
        }
        echo '</div></div>';
        return;
    }

    if ($tipe === 'kategori_list') {
        $jumlah = max(1, min(30, (int)section_opt($cfg, 'jumlah', 10)));
        $showCount = (int)section_opt($cfg, 'tampil_jumlah', 1) === 1;
        $kats = [];
        $res = $conn->query("SELECT k.id, k.nama, (SELECT COUNT(*) FROM berita b WHERE b.kategori_id = k.id AND b.status = 'publish') AS jml FROM kategori k ORDER BY k.nama ASC LIMIT $jumlah");
        if ($res) while ($r = $res->fetch_assoc()) $kats[] = $r;
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Kategori');
        if (empty($kats)) echo '<p class="text-sm text-slate-500">Belum ada kategori.</p>';
        else {
            echo '<ul class="flex flex-col gap-1">';
            foreach ($kats as $k) {
                echo '<li><a href="kategori?kategori=' . (int)$k['id'] . '" class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-purple-50 hover:text-purple-700">'
                    . '<span>' . htmlspecialchars($k['nama']) . '</span>'
                    . ($showCount ? '<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-black text-slate-500 group-hover:bg-purple-100 group-hover:text-purple-700">' . (int)$k['jml'] . '</span>' : '')
                    . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</div></div>';
        return;
    }

    if ($tipe === 'iklan') {
        $kode = trim((string)section_opt($cfg, 'kode', ''));
        if ($kode !== '') {
            echo sidebar_card_open($sec, $judul);
            echo '<div class="text-center text-xs text-slate-500">' . $kode . '</div></div></div>';
            return;
        }
        $g = trim((string)section_opt($cfg, 'gambar', ''));
        if ($g === '') return;
        $link = trim((string)section_opt($cfg, 'link', ''));
        $alt = trim((string)section_opt($cfg, 'alt', $judul !== '' ? $judul : 'Iklan'));
        echo sidebar_card_open($sec, $judul);
        $img = '<img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="' . htmlspecialchars($alt) . '" class="w-full rounded-xl border border-slate-200 object-cover">';
        if ($link !== '') echo '<a href="' . htmlspecialchars($link) . '" target="_blank" rel="noopener" class="block">' . $img . '</a>';
        else echo $img;
        echo '</div></div>';
        return;
    }

    if ($tipe === 'newsletter') {
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Newsletter');
        $nd = (string)section_opt($cfg, 'deskripsi', 'Dapatkan info terkini lewat email.');
        $msg = trim((string)($_GET['nl'] ?? ''));
        if ($nd !== '') echo '<p class="mb-3 text-sm text-slate-600">' . htmlspecialchars($nd) . '</p>';
        if ($msg === 'ok') echo '<p class="mb-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700">Berhasil! Email tercatat.</p>';
        elseif ($msg === 'ada') echo '<p class="mb-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-700">Email sudah terdaftar.</p>';
        elseif ($msg === 'err') echo '<p class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Email tidak valid.</p>';
        echo '<form action="newsletter" method="post" class="flex flex-col gap-2"><input type="email" name="email" required placeholder="Email Anda" class="w-full rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-purple-400 focus:bg-white"><button class="rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:scale-[1.02] transition">Langganan</button></form>';
        echo '</div></div>';
        return;
    }

    if ($tipe === 'sosmed') {
        echo sidebar_card_open($sec, $judul !== '' ? $judul : 'Ikuti Kami');
        $links = sosmed_links($cfg, $settings);
        $has = $links['facebook'] !== '' || $links['twitter'] !== '' || $links['instagram'] !== '' || $links['youtube'] !== '';
        if (!$has) echo '<p class="text-sm text-slate-500">Isi link sosmed via Admin &gt; Sections.</p>';
        else {
            echo '<div class="flex gap-2">';
            if ($links['facebook'] !== '') echo '<a href="' . htmlspecialchars($links['facebook']) . '" target="_blank" rel="noopener" aria-label="Facebook" class="flex h-11 w-11 items-center justify-center rounded-full bg-[#1877F2] text-white transition hover:scale-105"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
            if ($links['twitter'] !== '') echo '<a href="' . htmlspecialchars($links['twitter']) . '" target="_blank" rel="noopener" aria-label="X" class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-900 text-white transition hover:scale-105"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>';
            if ($links['instagram'] !== '') echo '<a href="' . htmlspecialchars($links['instagram']) . '" target="_blank" rel="noopener" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-tr from-amber-500 via-pink-600 to-purple-600 text-white transition hover:scale-105"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.31.975.975 1.248 2.242 1.31 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.335 2.633-1.31 3.608-.975.975-2.242 1.248-3.608 1.31-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.335-3.608-1.31-.975-.975-1.248-2.242-1.31-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.335-2.633 1.31-3.608.975-.975 2.242-1.248 3.608-1.31C8.416 2.175 8.796 2.163 12 2.163zM12 0C8.741 0 8.333.014 7.053.072 5.775.131 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.014 8.333 0 8.741 0 12s.014 3.667.072 4.947c.059 1.278.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.986 8.741 24 12 24s3.667-.014 4.947-.072c1.278-.059 2.148-.261 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.058-1.28.072-1.687.072-4.947s-.014-3.667-.072-4.947c-.059-1.278-.261-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.014 15.259 0 12 0z"/></svg></a>';
            if ($links['youtube'] !== '') echo '<a href="' . htmlspecialchars($links['youtube']) . '" target="_blank" rel="noopener" aria-label="YouTube" class="flex h-11 w-11 items-center justify-center rounded-full bg-red-600 text-white transition hover:scale-105"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31 31 0 000 12a31 31 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31 31 0 0024 12a31 31 0 00-.5-5.8zM9.6 15.6V8.4L15.8 12z"/></svg></a>';
            echo '</div>';
        }
        echo '</div></div>';
        return;
    }

    if (in_array($tipe, ['teks', 'html', 'image', 'video'], true)) {
        if ($tipe === 'html') {
            $html = (string)section_opt($cfg, 'html', '');
            if (trim($html) === '') return;
            echo '<div' . section_anim_attr((string)($sec['animasi'] ?? 'fade-up')) . '>' . $html . '</div>';
            return;
        }
        if ($tipe === 'image') {
            $g = trim((string)section_opt($cfg, 'gambar', ''));
            if ($g === '') return;
            $link = trim((string)section_opt($cfg, 'link', ''));
            $img = '<img loading="lazy" src="' . htmlspecialchars(berita_image_url($g)) . '" alt="' . htmlspecialchars((string)section_opt($cfg, 'alt', $judul)) . '" class="w-full rounded-2xl border border-slate-200 object-cover shadow-sm">';
            echo '<div' . section_anim_attr((string)($sec['animasi'] ?? 'fade-up')) . '>';
            if ($link !== '') echo '<a href="' . htmlspecialchars($link) . '" class="block">' . $img . '</a>';
            else echo $img;
            echo '</div>';
            return;
        }
        if ($tipe === 'video') {
            $url = trim((string)section_opt($cfg, 'video_url', ''));
            if ($url === '') return;
            echo sidebar_card_open($sec, $judul);
            $yt = section_youtube_id($url);
            echo '<div class="aspect-video overflow-hidden rounded-xl bg-black">';
            if ($yt !== '') echo '<iframe src="https://www.youtube.com/embed/' . $yt . '" class="h-full w-full" loading="lazy" allowfullscreen title="Video"></iframe>';
            else echo '<video src="' . htmlspecialchars($url) . '" controls class="h-full w-full"></video>';
            echo '</div></div></div>';
            return;
        }
        $isi = trim((string)section_opt($cfg, 'isi', ''));
        if ($isi === '' && $judul === '') return;
        echo sidebar_card_open($sec, $judul);
        if ($isi !== '') echo '<div class="text-sm leading-relaxed text-slate-600">' . nl2br(htmlspecialchars($isi)) . '</div>';
        echo '</div></div>';
        return;
    }

    $isi = trim((string)section_opt($cfg, 'isi', $cfg['deskripsi'] ?? ''));
    if ($isi === '' && $judul === '') return;
    echo sidebar_card_open($sec, $judul);
    if ($isi !== '') echo '<div class="text-sm leading-relaxed text-slate-600">' . nl2br(htmlspecialchars($isi)) . '</div>';
    echo '</div></div>';
}

function section_parse_links($raw): array
{
    $out = [];
    if (is_array($raw)) {
        foreach ($raw as $it) {
            if (is_array($it)) {
                $label = trim((string)($it['label'] ?? ''));
                $url = trim((string)($it['url'] ?? ''));
            } else {
                $label = trim((string)$it);
                $url = $label;
            }
            if ($label === '' || $url === '') continue;
            $out[] = ['label' => $label, 'url' => $url];
        }
        return $out;
    }
    foreach (preg_split('/\r?\n/', (string)$raw) as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        if ($line !== '' && $line[0] === '[') {
            $d = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE) return section_parse_links($d);
        }
        if (strpos($line, '|') !== false) {
            [$label, $url] = array_map('trim', explode('|', $line, 2));
        } else {
            $label = $line;
            $url = $line;
        }
        if ($label === '' || $url === '') continue;
        $out[] = ['label' => $label, 'url' => $url];
    }
    return $out;
}

function sosmed_links(array $cfg, array $settings): array
{
    $get = function ($k, $fallback = null) use ($cfg, $settings) {
        $v = trim((string)($cfg[$k] ?? ''));
        if ($v === '' && $fallback !== null) $v = trim((string)($settings[$fallback] ?? ''));
        return $v;
    };
    return [
        'facebook' => $get('facebook', 'footer_social_facebook'),
        'twitter' => $get('twitter', 'footer_social_twitter'),
        'instagram' => $get('instagram', 'footer_social_instagram'),
        'youtube' => $get('youtube', null),
    ];
}

function sosmed_icons_html(array $links, string $size = 'w-10 h-10'): string
{
    $h = '';
    if ($links['facebook'] !== '') $h .= '<a href="' . htmlspecialchars($links['facebook']) . '" target="_blank" rel="noopener" class="' . $size . ' rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-purple-600 transition" aria-label="Facebook"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
    if ($links['twitter'] !== '') $h .= '<a href="' . htmlspecialchars($links['twitter']) . '" target="_blank" rel="noopener" class="' . $size . ' rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-purple-600 transition" aria-label="X"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>';
    if ($links['instagram'] !== '') $h .= '<a href="' . htmlspecialchars($links['instagram']) . '" target="_blank" rel="noopener" class="' . $size . ' rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-purple-600 transition" aria-label="Instagram"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 1.8A3.7 3.7 0 0 0 3.8 7.5v9a3.7 3.7 0 0 0 3.7 3.7h9a3.7 3.7 0 0 0 3.7-3.7v-9a3.7 3.7 0 0 0-3.7-3.7h-9ZM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 1.8a3.2 3.2 0 1 1 0 6.4 3.2 3.2 0 0 1 0-6.4Zm5.2-3.2a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z"/></svg></a>';
    if ($links['youtube'] !== '') $h .= '<a href="' . htmlspecialchars($links['youtube']) . '" target="_blank" rel="noopener" class="' . $size . ' rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-purple-600 transition" aria-label="YouTube"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31 31 0 000 12a31 31 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31 31 0 0024 12a31 31 0 00-.5-5.8zM9.6 15.6V8.4L15.8 12z"/></svg></a>';
    return $h;
}

function render_footer_sections(mysqli $conn, array $settings, array $navKategoris, array $sections): void
{
    if (empty($sections)) {
        render_footer_legacy($settings, $navKategoris);
        return;
    }
    $top = array_values(array_filter($sections, function ($s) { return ($s['tipe'] ?? '') !== 'bottom'; }));
    $bottoms = array_values(array_filter($sections, function ($s) { return ($s['tipe'] ?? '') === 'bottom'; }));
    $n = count($top);
    $gridCls = $n <= 1 ? 'grid gap-8 md:grid-cols-1 mb-8' : ($n === 2 ? 'grid gap-8 md:grid-cols-2 mb-8' : ($n === 3 ? 'grid gap-8 md:grid-cols-3 mb-8' : 'grid gap-8 md:grid-cols-2 lg:grid-cols-4 mb-8'));
    echo '<div class="' . $gridCls . '">';
    foreach ($top as $sec) render_footer_widget($conn, $settings, $navKategoris, $sec);
    echo '</div>';
    foreach ($bottoms as $sec) {
        $note = trim((string)section_opt(section_pengaturan($sec), 'isi', ''));
        $showLink = (int)($settings['footer_admin_link_show'] ?? 1);
        $linkUrl = htmlspecialchars($settings['footer_admin_link_url'] ?? '/admin/login');
        $linkTitle = htmlspecialchars($settings['footer_admin_link_title'] ?? 'Login Admin');
        echo '<div class="relative flex items-center justify-center pt-8"' . section_anim_attr((string)($sec['animasi'] ?? 'fade')) . '>';
        $desc = $note !== '' ? ' ' . htmlspecialchars($note) : '';
        echo '<p class="text-sm text-slate-400">&copy; ' . date('Y') . ' ' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita') . '.' . $desc . '</p>';
        if ($showLink): ?>
            <a href="<?php echo $linkUrl; ?>" target="_blank" class="absolute right-0 text-xs text-slate-500 hover:text-white transition" title="<?php echo $linkTitle; ?>">
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 10v8h8v-3M10 13a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
                <?php echo $linkTitle; ?>
            </a>
        <?php endif;
        echo '</div>';
    }
}

function render_footer_widget(mysqli $conn, array $settings, array $navKategoris, array $sec): void
{
    $tipe = (string)($sec['tipe'] ?? 'teks');
    $judul = trim((string)($sec['judul'] ?? ''));
    $cfg = array_merge(section_default_cfg($tipe), section_pengaturan($sec));
    echo '<div' . section_anim_attr((string)($sec['animasi'] ?? 'fade-up')) . '>';
    if (in_array($tipe, ['image', 'video', 'audio', 'galeri', 'sambutan', 'statistik', 'pengumuman', 'cta', 'countdown', 'carousel', 'carousel_berita', 'kategori_berita', 'kategori', 'latest', 'populer', 'hero', 'agenda', 'guru', 'prestasi', 'ekskul', 'teks'], true)
        && !in_array($tipe, ['brand', 'links', 'contact', 'newsletter', 'bottom', 'html', 'teks'], true)) {
        if ($judul !== '') echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul) . '</h4>';
        echo '<div class="text-sm text-slate-300">';
        render_footer_mini_widget($conn, $sec, $cfg);
        echo '</div>';
        echo '</div>';
        return;
    }

    if ($tipe === 'brand') {
        $logo = trim((string)section_opt($cfg, 'logo', ''));
        if ($logo === '') $logo = trim((string)($settings['logo_path'] ?? ''));
        $desc = trim((string)section_opt($cfg, 'deskripsi', ''));
        if ($desc === '') $desc = (string)($settings['site_tagline'] ?? 'Berita terkini dan terpercaya');
        if ($logo !== '') echo '<img loading="lazy" src="' . htmlspecialchars(berita_image_url($logo)) . '" alt="' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita') . '" class="mb-3 h-12 w-auto rounded-lg bg-white/95 p-1">';
        echo '<h3 class="text-2xl font-black mb-3 bg-gradient-to-r from-purple-400 to-blue-400 bg-clip-text text-transparent">' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita') . '</h3>';
        if ($desc !== '') echo '<p class="text-sm text-slate-300 leading-relaxed">' . htmlspecialchars($desc) . '</p>';
    } elseif ($tipe === 'links') {
        echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul !== '' ? $judul : 'Tautan Cepat') . '</h4><div class="space-y-2">';
        $manual = section_parse_links((string)section_opt($cfg, 'tautan', ''));
        if (!empty($manual)) {
            foreach ($manual as $t) {
                echo '<a href="' . htmlspecialchars($t['url']) . '" class="block text-sm text-slate-300 hover:text-purple-400 transition">' . htmlspecialchars($t['label']) . '</a>';
            }
        } else {
            echo '<a href="index" class="block text-sm text-slate-300 hover:text-purple-400 transition">Beranda</a>';
            foreach ($navKategoris as $ft) {
                echo '<a href="kategori?kategori=' . (int)$ft['id'] . '" class="block text-sm text-slate-300 hover:text-purple-400 transition">' . htmlspecialchars($ft['nama']) . '</a>';
            }
        }
        echo '</div>';
    } elseif ($tipe === 'sosmed') {
        $links = sosmed_links($cfg, $settings);
        echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul !== '' ? $judul : 'Ikuti Kami') . '</h4>';
        $icons = sosmed_icons_html($links);
        if ($icons !== '') echo '<div class="flex gap-3">' . $icons . '</div>';
        else echo '<p class="text-sm text-slate-400">Isi link sosmed via Admin &gt; Sections &gt; Footer.</p>';
    } elseif ($tipe === 'contact') {
        echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul !== '' ? $judul : 'Informasi') . '</h4><div class="space-y-3 text-sm text-slate-300">';
        if (!empty($settings['footer_address'])) echo '<div class="flex items-start gap-3"><span class="text-purple-400">◉</span><span>' . nl2br(htmlspecialchars($settings['footer_address'])) . '</span></div>';
        if (!empty($settings['footer_email'])) echo '<div class="flex items-start gap-3"><span class="text-purple-400">✉</span><a href="mailto:' . htmlspecialchars($settings['footer_email']) . '" class="hover:text-purple-400 transition">' . htmlspecialchars($settings['footer_email']) . '</a></div>';
        if (!empty($settings['footer_phone'])) echo '<div class="flex items-start gap-3"><span class="text-purple-400">☎</span><a href="tel:' . htmlspecialchars($settings['footer_phone']) . '" class="hover:text-purple-400 transition">' . htmlspecialchars($settings['footer_phone']) . '</a></div>';
        echo '</div>';
    } elseif ($tipe === 'newsletter') {
        $nj = (string)section_opt($cfg, 'judul', 'Kabar Terbaru');
        $nd = (string)section_opt($cfg, 'deskripsi', 'Dapatkan info terkini lewat email.');
        $msg = trim((string)($_GET['nl'] ?? ''));
        echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul !== '' ? $judul : $nj) . '</h4>';
        echo '<p class="text-sm text-slate-300 mb-4">' . htmlspecialchars($nd) . '</p>';
        if ($msg === 'ok') echo '<p class="mb-3 rounded-xl bg-emerald-500/20 border border-emerald-400/40 px-4 py-2 text-sm font-semibold text-emerald-200">Berhasil! Email tercatat.</p>';
        elseif ($msg === 'ada') echo '<p class="mb-3 rounded-xl bg-amber-500/20 border border-amber-400/40 px-4 py-2 text-sm font-semibold text-amber-200">Email sudah terdaftar.</p>';
        elseif ($msg === 'err') echo '<p class="mb-3 rounded-xl bg-red-500/20 border border-red-400/40 px-4 py-2 text-sm font-semibold text-red-200">Email tidak valid.</p>';
        echo '<form action="newsletter" method="post" class="flex gap-2"><input type="email" name="email" required placeholder="Email Anda" class="min-w-0 flex-1 rounded-full border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-slate-400 outline-none focus:border-purple-400"><button class="shrink-0 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:scale-105 transition">Kirim</button></form>';
    } elseif ($tipe === 'html') {
        echo (string)section_opt($cfg, 'html', '');
    } else {
        $isi = (string)section_opt($cfg, 'isi', '');
        if ($judul !== '') echo '<h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">' . htmlspecialchars($judul) . '</h4>';
        if ($isi !== '') echo '<div class="text-sm text-slate-300 leading-relaxed">' . nl2br(htmlspecialchars($isi)) . '</div>';
    }
    echo '</div>';
}

function render_footer_mini_widget(mysqli $conn, array $sec, array $cfg): void
{
    $tipe = (string)($sec['tipe'] ?? '');
    if ($tipe === 'image' && trim((string)($cfg['gambar'] ?? '')) !== '') {
        echo '<img loading="lazy" src="' . htmlspecialchars(berita_image_url((string)$cfg['gambar'])) . '" alt="" class="w-full rounded-xl object-cover">';
    } elseif ($tipe === 'video' && trim((string)($cfg['video_url'] ?? '')) !== '') {
        $yt = section_youtube_id((string)$cfg['video_url']);
        if ($yt !== '') echo '<div class="aspect-video overflow-hidden rounded-xl bg-black"><iframe src="https://www.youtube.com/embed/' . $yt . '" class="w-full h-full" loading="lazy" allowfullscreen></iframe></div>';
        else echo '<audio src="' . htmlspecialchars((string)$cfg['video_url']) . '" controls class="w-full"></audio>';
    } elseif ($tipe === 'audio' && trim((string)($cfg['audio_url'] ?? '')) !== '') {
        echo '<audio src="' . htmlspecialchars((string)$cfg['audio_url']) . '" controls class="w-full"></audio>';
    } elseif ($tipe === 'galeri') {
        $g = section_galeri_list($cfg);
        echo '<div class="grid grid-cols-3 gap-2">';
        foreach (array_slice($g, 0, 6) as $p) echo '<img loading="lazy" src="' . htmlspecialchars(berita_image_url($p)) . '" alt="" class="w-full aspect-square object-cover rounded-lg">';
        echo '</div>';
    } elseif ($tipe === 'statistik') {
        echo '<div class="grid grid-cols-2 gap-3">';
        for ($i = 1; $i <= 4; $i++) {
            $a = trim((string)($cfg['stat' . $i . '_angka'] ?? ''));
            $l = trim((string)($cfg['stat' . $i . '_label'] ?? ''));
            if ($a === '' && $l === '') continue;
            echo '<div><div class="text-xl font-black text-white">' . htmlspecialchars($a) . '</div><div class="text-xs text-slate-400">' . htmlspecialchars($l) . '</div></div>';
        }
        echo '</div>';
    } elseif ($tipe === 'sambutan') {
        if (trim((string)($cfg['foto'] ?? '')) !== '') echo '<img loading="lazy" src="' . htmlspecialchars(berita_image_url((string)$cfg['foto'])) . '" alt="" class="w-20 h-20 rounded-full object-cover mb-2">';
        if (trim((string)($cfg['isi'] ?? '')) !== '') echo '<p class="line-clamp-3">' . nl2br(htmlspecialchars((string)$cfg['isi'])) . '</p>';
        if (trim((string)($cfg['nama'] ?? '')) !== '') echo '<p class="mt-2 font-bold text-white">' . htmlspecialchars((string)$cfg['nama']) . '</p>';
    } elseif ($tipe === 'pengumuman') {
        if (trim((string)($cfg['isi'] ?? '')) !== '') echo '<p>📢 ' . nl2br(htmlspecialchars((string)$cfg['isi'])) . '</p>';
    } elseif ($tipe === 'cta') {
        echo '<a href="' . htmlspecialchars((string)($cfg['cta_link'] ?? '#')) . '" class="inline-flex rounded-full bg-white px-5 py-2 text-sm font-bold text-purple-700">' . htmlspecialchars((string)($cfg['cta_teks'] ?? 'Lihat')) . '</a>';
    } elseif ($tipe === 'countdown') {
        echo '<p class="text-xs">⏳ ' . htmlspecialchars((string)($cfg['target_tanggal'] ?? '')) . '</p>';
    } elseif (in_array($tipe, ['hero', 'carousel', 'carousel_berita', 'kategori_berita', 'kategori', 'latest', 'populer'], true)) {
        $rows = [];
        $res = $conn->query("SELECT judul FROM berita WHERE status = 'publish' ORDER BY tanggal_publikasi DESC, id DESC LIMIT 4");
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo '<ul class="space-y-2">';
        foreach ($rows as $r) echo '<li class="line-clamp-1">• ' . htmlspecialchars($r['judul']) . '</li>';
        echo '</ul>';
    } else {
        $isi = trim((string)($cfg['isi'] ?? $cfg['deskripsi'] ?? ''));
        if ($isi !== '') echo '<p>' . nl2br(htmlspecialchars($isi)) . '</p>';
    }
}

function render_footer_legacy(array $settings, array $navKategoris): void
{
    ?>
    <div class="grid gap-8 md:grid-cols-3 mb-8">
        <div>
            <h3 class="text-2xl font-black mb-3 bg-gradient-to-r from-purple-400 to-blue-400 bg-clip-text text-transparent"><?php echo htmlspecialchars($settings['site_name'] ?? 'Portal Berita'); ?></h3>
            <p class="text-sm text-slate-300 leading-relaxed"><?php echo htmlspecialchars($settings['site_tagline'] ?? 'Berita terkini dan terpercaya'); ?></p>
        </div>
        <div>
            <h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">Tautan Cepat</h4>
            <div class="space-y-2">
                <a href="index" class="block text-sm text-slate-300 hover:text-purple-400 transition">Beranda</a>
                <?php foreach ($navKategoris as $ft): ?>
                    <a href="kategori?kategori=<?php echo (int)$ft['id']; ?>" class="block text-sm text-slate-300 hover:text-purple-400 transition"><?php echo htmlspecialchars($ft['nama']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-black uppercase tracking-wider text-white mb-4">Informasi</h4>
            <div class="space-y-3 text-sm text-slate-300">
                <?php if (!empty($settings['footer_address'])): ?><div><?php echo nl2br(htmlspecialchars($settings['footer_address'])); ?></div><?php endif; ?>
                <?php if (!empty($settings['footer_email'])): ?><div><a href="mailto:<?php echo htmlspecialchars($settings['footer_email']); ?>" class="hover:text-purple-400 transition"><?php echo htmlspecialchars($settings['footer_email']); ?></a></div><?php endif; ?>
                <?php if (!empty($settings['footer_phone'])): ?><div><a href="tel:<?php echo htmlspecialchars($settings['footer_phone']); ?>" class="hover:text-purple-400 transition"><?php echo htmlspecialchars($settings['footer_phone']); ?></a></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="border-t border-slate-700 pt-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-slate-400">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'Portal Berita'); ?>. Semua hak dilindungi.</p>
        </div>
    </div>
    <?php
}
