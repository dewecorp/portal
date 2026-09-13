<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

ensure_sections_table($conn);

$area = $_GET['area'] ?? 'home';
if (!in_array($area, ['home', 'sidebar', 'footer'], true)) $area = 'home';

$kategoris = [];
$rk = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($rk) while ($r = $rk->fetch_assoc()) $kategoris[] = $r;

$boot = [
    'area' => $area,
    'kategoris' => $kategoris,
    'tipeHome' => section_tipe_home(),
    'tipeSidebar' => section_tipe_sidebar(),
    'tipeFooter' => section_tipe_footer(),
    'orderHome' => section_widget_order('home'),
    'orderSidebar' => section_widget_order('sidebar'),
    'orderFooter' => section_widget_order('footer'),
    'meta' => section_widget_meta(),
    'icons' => array_combine(
        array_keys(section_widget_meta()),
        array_map('section_widget_icon', array_keys(section_widget_meta()))
    ),
    'ui' => [
        'tool' => ui_icon('tool', 'w-5 h-5'),
        'home' => ui_icon('home', 'w-4 h-4'),
        'panel' => ui_icon('panel', 'w-4 h-4'),
        'news' => ui_icon('news', 'w-4 h-4'),
        'eye' => ui_icon('eye', 'w-4 h-4'),
        'eyeOff' => ui_icon('eye-off', 'w-4 h-4'),
        'edit' => ui_icon('edit', 'w-4 h-4'),
        'trash' => ui_icon('trash', 'w-4 h-4'),
        'copy' => ui_icon('copy', 'w-4 h-4'),
        'grip' => ui_icon('grip', 'w-4 h-4'),
        'chevL' => ui_icon('chev-left', 'w-4 h-4'),
        'chevR' => ui_icon('chev-right', 'w-4 h-4'),
        'chevU' => ui_icon('chev-up', 'w-4 h-4'),
        'chevD' => ui_icon('chev-down', 'w-4 h-4'),
        'image' => ui_icon('image', 'w-4 h-4'),
        'link' => ui_icon('link', 'w-4 h-4'),
        'save' => ui_icon('save', 'w-4 h-4'),
        'layers' => ui_icon('layers', 'w-4 h-4'),
        'sliders' => ui_icon('sliders', 'w-4 h-4'),
        'x' => ui_icon('x', 'w-4 h-4'),
        'plus' => ui_icon('plus', 'w-4 h-4'),
    ],
    'animasi' => section_animasi_list(),
    'gaya' => section_gaya_gambar_list(),
    'styleGrid' => section_grid_style_list(),
    'previewUrl' => '../index',
];

include __DIR__ . '/header.php';
?>
<style>
.sb-wrap { width: 100%; margin: 0; }
.sb-topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
.sb-brand { display:flex; align-items:center; gap:10px; }
.sb-logo { width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#10b981,#0d9488); color:#fff; display:grid; place-items:center; }
.sb-logo svg { width:19px; height:19px; }
.sb-tab { display:inline-flex; align-items:center; gap:7px; border:0; background:transparent; padding:8px 16px; border-radius:9px; font-weight:800; font-size:13px; color:#64748b; cursor:pointer; }
.sb-tab svg { width:16px; height:16px; }
.sb-tab.active { background:#0f766e; color:#fff; box-shadow:0 6px 16px rgba(15,118,110,.3); }
.sb-live { display:inline-flex; align-items:center; gap:8px; border:1px solid var(--admin-line); background:#fff; border-radius:12px; padding:9px 16px; font-weight:800; font-size:13px; color:#0f172a; cursor:pointer; }
.sb-live svg { width:16px; height:16px; }
.sb-live:hover { border-color:#10b981; color:#047857; }
.sb-hint { font-size:12.5px; color:#64748b; margin:2px 0 14px; }
.sb-panel { background:#fff; border:1px solid #e6f0ee; border-radius:18px; box-shadow:0 18px 45px rgba(15,23,42,.06); padding:18px; margin-bottom:16px; }
.sb-panel-title { font-size:12px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; color:#0f766e; margin-bottom:14px; }
.sb-widgets { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
@media(min-width:640px){ .sb-widgets{ grid-template-columns:repeat(3,1fr);} }
@media(min-width:1024px){ .sb-widgets{ grid-template-columns:repeat(6,1fr);} }
.sb-widget { border:1.5px solid #e2e8f0; border-radius:14px; background:#fff; padding:16px 8px 12px; text-align:center; cursor:grab; transition:.25s ease; }
.sb-widget:hover { border-color:#10b981; box-shadow:0 10px 24px rgba(16,185,129,.25); transform:translateY(-2px); }
.sb-widget:active { cursor:grabbing; }
.sb-widget .ic { display:grid; place-items:center; width:46px; height:46px; margin:0 auto 8px; border-radius:14px; background:linear-gradient(135deg,#ecfdf5,#e0f2fe); color:#0f766e; }
.sb-widget .ic svg { width:25px; height:25px; }
.sb-ic-fallback { display:block; width:25px; height:25px; border-radius:8px; background:#cbd5e1; }
.sb-widget[data-cat="berita"] .ic { background:linear-gradient(135deg,#ede9fe,#dbeafe); color:#6d28d9; }
.sb-widget[data-cat="interaksi"] .ic { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309; }
.sb-widget[data-cat="media"] .ic { background:linear-gradient(135deg,#e0f2fe,#bae6fd); color:#0369a1; }
.sb-widget[data-cat="info"] .ic { background:linear-gradient(135deg,#f1f5f9,#e2e8f0); color:#475569; }
.sb-widget .lb { font-size:12px; font-weight:800; color:#0f766e; }
.sb-main { display:grid; gap:16px; }
@media(min-width:1024px){ .sb-main{ grid-template-columns:minmax(0,1fr) 380px; align-items:start;} }
.sb-canvas-title { font-size:12px; font-weight:900; letter-spacing:.1em; color:#64748b; text-transform:uppercase; margin-bottom:12px; }
.sb-card { display:flex; gap:12px; align-items:flex-start; border:1.5px solid #e2e8f0; border-radius:16px; background:#fbfefd; padding:14px; margin-bottom:10px; cursor:pointer; transition:.25s ease; }
.sb-card:hover { border-color:#6ee7b7; transform:translateY(-2px); box-shadow:0 8px 20px rgba(16,185,129,.15); }
.sb-card.selected { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.15); background:#fff; }
.sb-card.draft-off { opacity:.55; }
.sb-card.dragging { opacity:.4; }
.sb-grip { color:#cbd5e1; display:grid; place-items:center; cursor:grab; padding-top:10px; }
.sb-grip svg { width:15px; height:15px; }
.sb-thumb { width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#ecfdf5,#e0f2fe); color:#0f766e; display:grid; place-items:center; flex:0 0 auto; }
.sb-thumb svg { width:24px; height:24px; }
.sb-info { flex:1 1 auto; min-width:0; }
.sb-name { font-weight:900; color:#0f172a; font-size:14.5px; }
.sb-sub { font-size:11.5px; color:#64748b; font-family:ui-monospace,monospace; margin-top:2px; }
.sb-actions { display:flex; gap:6px; flex:0 0 auto; align-items:center; }
.sb-actions .sb-nav { display:flex; gap:4px; }
.sb-actions .sb-nav .sb-iconbtn { width:30px; height:34px; }
.sb-actions .sb-nav .sb-iconbtn svg { width:14px; height:14px; }
.sb-iconbtn:disabled { opacity:.35; cursor:not-allowed; }
.sb-iconbtn { width:34px; height:34px; border-radius:10px; border:1.5px solid #e2e8f0; background:#fff; cursor:pointer; display:grid; place-items:center; transition:.15s; color:#475569; }
.sb-iconbtn svg { width:16px; height:16px; }
.sb-iconbtn:hover { border-color:#10b981; }
.sb-iconbtn.danger { color:#dc2626; }
.sb-iconbtn.danger:hover { border-color:#fca5a5; background:#fef2f2; }
.sb-drop-hint { border:2px dashed #cbd5e1; border-radius:14px; padding:26px; text-align:center; color:#94a3b8; font-weight:700; font-size:13px; }
.sb-placeholder { height:64px; border:2px dashed #10b981; border-radius:14px; background:#ecfdf5; margin-bottom:10px; }
.sb-drop-end { margin-top:4px; border:2px dashed #cbd5e1; border-radius:14px; padding:18px; text-align:center; color:#94a3b8; font-weight:700; font-size:13px; background:#f8fafc; transition:.15s; }
.sb-drop-end:hover { border-color:#10b981; color:#047857; background:#ecfdf5; }
.sb-empty { text-align:center; color:#94a3b8; padding:24px 10px; font-size:13px; font-weight:600; }
.sb-insp-head { font-size:15px; font-weight:900; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.sb-insp-tabs { display:flex; background:#eef4f3; border-radius:12px; padding:4px; gap:4px; margin-bottom:16px; }
.sb-insp-tab { flex:1; border:0; background:transparent; border-radius:9px; padding:9px 4px; font-weight:800; font-size:13px; color:#64748b; cursor:pointer; }
.sb-insp-tab.active { background:#fff; color:#0f172a; box-shadow:0 2px 8px rgba(15,23,42,.12); }
.sb-field { margin-bottom:14px; }
.sb-field label { display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px; }
.sb-field input[type=text], .sb-field input[type=number], .sb-field input[type=url], .sb-field input[type=datetime-local], .sb-field select, .sb-field textarea { width:100%; border:1.5px solid #e2e8f0; border-radius:12px; padding:10px 14px; font-size:13.5px; font-family:inherit; color:#0f172a; background:#fff; outline:none; }
.sb-field input:focus, .sb-field select:focus, .sb-field textarea:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.15); }
.sb-field textarea { min-height:90px; resize:vertical; }
.sb-opt-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.sb-opt { border:2px solid #e2e8f0; border-radius:12px; background:#fff; padding:10px 4px; text-align:center; cursor:pointer; transition:.15s; font-size:11.5px; font-weight:800; color:#334155; }
.sb-opt:hover { border-color:#6ee7b7; }
.sb-opt.sel { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.2); color:#047857; }
.sb-opt .oi { display:grid; place-items:center; margin-bottom:6px; color:#64748b; }
.sb-opt .oi svg { width:21px; height:21px; }
.sb-opt.sel .oi { color:#047857; }
.sb-opt-sec { font-size:11px; font-weight:900; letter-spacing:.08em; color:#94a3b8; text-transform:uppercase; margin:16px 0 8px; }
.sb-imgprev { width:100%; border-radius:12px; object-fit:cover; max-height:220px; margin-top:8px; }
.sb-note { font-size:11.5px; color:#94a3b8; margin-top:6px; }
.sb-gal { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:8px; }
.sb-gal .g { position:relative; border-radius:10px; overflow:hidden; }
.sb-gal img { width:100%; height:72px; object-fit:cover; display:block; }
.sb-gal button { position:absolute; top:4px; right:4px; width:22px; height:22px; border-radius:7px; border:0; background:rgba(0,0,0,.6); color:#fff; cursor:pointer; font-size:12px; }
.sb-savebar { display:flex; gap:10px; margin-top:18px; }
.sb-savebar-float { position:sticky; bottom:12px; z-index:20; background:rgba(255,255,255,.92); backdrop-filter:blur(10px); border:1.5px solid #a7f3d0; border-radius:14px; padding:10px; box-shadow:0 14px 32px rgba(16,185,129,.22); }
.sb-links-list { display:flex; flex-direction:column; gap:8px; margin-bottom:8px; }
.sb-link-row { display:grid; grid-template-columns:1fr 1fr 30px; gap:6px; align-items:center; }
.sb-link-row input { border:1.5px solid #e2e8f0; border-radius:10px; padding:8px 10px; font-size:12.5px; outline:none; width:100%; }
.sb-link-row input:focus { border-color:#10b981; }
.sb-link-del { width:30px; height:34px; border-radius:9px; border:1.5px solid #fecaca; background:#fff; color:#dc2626; cursor:pointer; font-size:14px; }
.sb-link-add { width:100%; border:1.5px dashed #6ee7b7; border-radius:10px; background:#ecfdf5; color:#047857; font-weight:800; font-size:12.5px; padding:9px; cursor:pointer; }
.sb-btn-save { flex:1; border:0; border-radius:12px; background:linear-gradient(135deg,#10b981,#0d9488); color:#fff; font-weight:900; font-size:15px; padding:13px; cursor:pointer; box-shadow:0 10px 24px rgba(16,185,129,.35); }
.sb-btn-save:hover { filter:brightness(1.05); }
.sb-btn-save:disabled { opacity:.6; cursor:wait; }
.sb-btn-cancel { border:1.5px solid #e2e8f0; border-radius:12px; background:#fff; font-weight:800; font-size:14px; padding:13px 22px; cursor:pointer; color:#475569; }
.sb-switch { display:flex; align-items:center; justify-content:space-between; border:1.5px solid #e2e8f0; border-radius:12px; padding:10px 14px; font-size:13.5px; font-weight:700; color:#334155; margin-bottom:10px; }
.sb-segwrap { display:flex; gap:6px; }
.sb-seg { flex:1; border:1.5px solid #e2e8f0; background:#f8fafc; border-radius:10px; padding:9px 4px; font-size:12.5px; font-weight:800; color:#64748b; cursor:pointer; transition:.15s; }
.sb-seg:hover { border-color:#6ee7b7; }
.sb-seg.sel { background:#0f766e; border-color:#0f766e; color:#fff; box-shadow:0 6px 14px rgba(15,118,110,.3); }
.sb-pills { display:flex; flex-wrap:wrap; gap:6px; }
.sb-pill { border:1.5px solid #e2e8f0; background:#f8fafc; border-radius:999px; padding:7px 14px; font-size:12px; font-weight:800; color:#475569; cursor:pointer; transition:.15s; }
.sb-pill:hover { border-color:#6ee7b7; }
.sb-pill.sel { background:#0f766e; border-color:#0f766e; color:#fff; }
.sb-slider { display:flex; gap:10px; align-items:center; }
.sb-slider input[type=range] { flex:1; accent-color:#10b981; height:28px; }
.sb-gridpicker { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
.sb-gridpicker .sb-grid-opt { border:2px solid #e2e8f0; border-radius:12px; background:#fff; padding:8px; cursor:pointer; transition:.15s; text-align:center; }
.sb-gridpicker .sb-grid-opt:hover { border-color:#6ee7b7; transform:translateY(-1px); box-shadow:0 8px 18px rgba(16,185,129,.15); }
.sb-gridpicker .sb-grid-opt.sel { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.25); }
.sb-gridpicker .pv { height:46px; border-radius:8px; background:#f1f5f9; margin-bottom:6px; padding:6px; display:flex; gap:3px; overflow:hidden; }
.sb-gridpicker .pv i { display:block; border-radius:4px; background:#cbd5e1; flex:1; min-width:0; }
.sb-gridpicker .pv i.big { flex:1.5; background:#34d399; }
.sb-gridpicker .pv.timeline { align-items:stretch; }
.sb-gridpicker .pv.timeline::before { content:''; width:4px; border-radius:99px; background:#34d399; flex:0 0 auto; }
.sb-gridpicker .pv.minimal { flex-direction:column; gap:4px; }
.sb-gridpicker .pv.minimal i { flex:1; }
.sb-gridpicker .pv.overlay i { background:linear-gradient(135deg,#6ee7b7,#34d399); }
.sb-gridpicker .lb { font-size:11px; font-weight:800; color:#334155; display:block; }
.sb-gridpicker .sb-grid-opt.sel .lb { color:#047857; }
.sb-toast { position:fixed; bottom:24px; right:24px; z-index:200; background:#0f172a; color:#fff; border-radius:12px; padding:12px 18px; font-size:13.5px; font-weight:700; box-shadow:0 18px 40px rgba(15,23,42,.35); display:none; }
.sb-sideprev { border:1.5px dashed #a78bfa; border-radius:14px; background:linear-gradient(135deg,#f5f3ff,#eef2ff); padding:12px 14px; margin-bottom:14px; display:flex; flex-direction:column; gap:2px; }
.sb-sideprev-tag { font-size:10.5px; font-weight:900; letter-spacing:.1em; text-transform:uppercase; color:#7c3aed; }
.sb-sideprev-title { font-size:15px; font-weight:900; color:#0f172a; }
.sb-sideprev-sub { font-size:11.5px; color:#64748b; font-weight:600; }
.sb-widget[data-cat] { position:relative; }
.sb-widget .cat { position:absolute; top:6px; right:6px; font-size:9px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; border-radius:999px; padding:2px 7px; background:#f1f5f9; color:#64748b; }
.sb-widget[data-cat="sidebar"] .cat { background:#ede9fe; color:#6d28d9; }
.sb-widget[data-cat="home"] .cat { background:#dcfce7; color:#047857; }
.sb-widget[data-cat="footer"] .cat { background:#e0f2fe; color:#0369a1; }
.sb-widget[data-cat="semua"] .cat { background:#f1f5f9; color:#64748b; }
.sb-catbar { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
@media(max-width:1023px){ .sb-insp { position:sticky; bottom:0; } }
</style>

<div class="sb-wrap">
    <div class="sb-topbar">
        <div class="sb-brand">
            <span class="sb-logo"><?php echo ui_icon('tool', 'w-5 h-5'); ?></span>
            <span class="sb-title">Section Builder</span>
            <div class="sb-tabs" id="sbAreaTabs">
                <button type="button" class="sb-tab" data-area="home"><?php echo ui_icon('home', 'w-4 h-4'); ?> Landing</button>
                <button type="button" class="sb-tab" data-area="sidebar"><?php echo ui_icon('panel', 'w-4 h-4'); ?> Sidebar</button>
                <button type="button" class="sb-tab" data-area="footer"><?php echo ui_icon('news', 'w-4 h-4'); ?> Footer</button>
            </div>
        </div>
        <button type="button" class="sb-live" id="sbLive"><?php echo ui_icon('eye', 'w-4 h-4'); ?> Live Preview</button>
    </div>
    <p class="sb-hint">Drag widget ke kanvas (atau klik) • drag kartu untuk urutkan • klik kartu untuk edit • tab Sidebar untuk widget kolom kanan home.</p>

    <div class="sb-panel">
        <div class="sb-panel-title"><span style="display:inline-flex;vertical-align:-3px;margin-right:6px;"><?php echo ui_icon('plus', 'w-4 h-4'); ?></span>Widget — drag ke kanvas / klik</div>
        <input type="text" id="sbWidgetSearch" placeholder="Cari widget..." style="width:100%;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px;font-size:13px;margin-bottom:12px;outline:none;" onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
        <div class="sb-catbar" id="sbCatBar"></div>
        <div class="sb-widgets" id="sbWidgets"></div>
    </div>

    <div class="sb-main">
        <div class="sb-panel">
            <div class="sb-canvas-title" id="sbCanvasTitle">Kanvas — drop di sini (0)</div>
            <div id="sbCanvas"></div>
        </div>
        <div class="sb-panel sb-insp">
            <div class="sb-insp-head" id="sbInspHead">Inspector</div>
            <div class="sb-insp-tabs">
                <button type="button" class="sb-insp-tab active" data-tab="konten">Konten</button>
                <button type="button" class="sb-insp-tab" data-tab="gaya">Gaya</button>
                <button type="button" class="sb-insp-tab" data-tab="lanjut">Lanjut</button>
            </div>
            <div id="sbInspBody"><p class="sb-empty">Pilih kartu di kanvas untuk mengedit.</p></div>
        </div>
    </div>
</div>
<div class="sb-toast" id="sbToast"></div>

<script>
(function() {
    var BOOT = <?php echo json_encode($boot, JSON_UNESCAPED_UNICODE); ?>;
    var META = BOOT.meta || {};
    var ICONS = BOOT.icons || {};
    var UI = BOOT.ui || {};
    var ANIM = BOOT.animasi || {};
    var GAYA = BOOT.gaya || {};
    var STYLES = BOOT.styleGrid || {};
    var GRID_TIPES = ['latest', 'kategori', 'kategori_berita', 'populer'];
    var GRID_ORDER = ['kartu-2', 'kartu-3', 'kartu-4', 'sorotan-list', 'magazine', 'masonry', 'kartu-horizontal', 'timeline', 'list', 'overlay', 'minimal'];
    var KATS = BOOT.kategoris || [];
    var GAYA_TIPES = ['hero', 'carousel', 'image'];
    var ANIM_ICON = { 'tanpa': UI.x, 'fade-up': UI.chevU, 'fade-down': UI.chevD, 'fade-left': UI.chevL, 'fade-right': UI.chevR, 'fade': UI.eye, 'zoom-in': UI.sliders, 'zoom-out': UI.sliders, 'flip': UI.layers, 'bounce': UI.news, 'slide': UI.chevR };
    var GAYA_ICON = { 'statis': UI.x, 'kenburns': UI.image, 'kenburns-balik': UI.image, 'zoom-lambat': UI.sliders, 'geser-kiri': UI.chevL, 'geser-kanan': UI.chevR, 'fade-zoom': UI.eye, 'melayang': UI.layers };
    var SIDE_ICON = UI.panel;
    function sidePrevHtml(s) {
        if (state.area !== 'sidebar' || !SIDE_ICON) return '';
        return '<div class="sb-sideprev"><span class="sb-sideprev-tag"><span style="display:inline-flex;vertical-align:-2px;margin-right:4px;">' + SIDE_ICON + '</span>Pratinjau Sidebar</span><span class="sb-sideprev-title">' + esc(s.judul || labelTipe(s.tipe)) + '</span><span class="sb-sideprev-sub">' + esc(labelTipe(s.tipe)) + ' • tampil di kolom kanan home</span></div>';
    }

    var state = { area: BOOT.area || 'home', items: [], sel: 0, tab: 'konten', dirty: false };

    function $(id) { return document.getElementById(id); }
    function toast(msg, ok) {
        var t = $('sbToast');
        t.textContent = msg;
        t.style.display = 'block';
        t.style.background = ok === false ? '#b91c1c' : '#0f172a';
        clearTimeout(t._tm);
        t._tm = setTimeout(function() { t.style.display = 'none'; }, 2600);
    }
    function api(aksi, data, files) {
        var url = 'sections_api.php?aksi=' + encodeURIComponent(aksi);
        if (files) {
            var fd = new FormData();
            Object.keys(data || {}).forEach(function(k) { fd.append(k, data[k]); });
            fd.append('file', files);
            return fetch(url, { method: 'POST', body: fd }).then(function(r) { return r.json(); });
        }
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data || {}) }).then(function(r) { return r.json(); });
    }
    function dropTipe(e) {
        var t = '';
        if (window._sbDragId) return '';
        try { t = e.dataTransfer.getData('text/sb-tipe'); } catch (x) {}
        if (!t) { try { t = e.dataTransfer.getData('text/plain'); } catch (x) {} }
        if (t && t.indexOf('sb-tipe:') === 0) t = t.slice(8);
        if (!t || !tipeList()[t]) return '';
        return t;
    }
    function dropMoveId(e) {
        var m = 0;
        try { m = parseInt(e.dataTransfer.getData('text/sb-move') || '0', 10); } catch (x) {}
        if (!m) {
            try {
                var p = e.dataTransfer.getData('text/plain') || '';
                if (p.indexOf('sb-move:') === 0) m = parseInt(p.slice(8), 10) || 0;
            } catch (x) {}
        }
        if (!m && window._sbDragId) m = parseInt(window._sbDragId, 10) || 0;
        return m || 0;
    }
    function tipeList() {
        if (state.area === 'footer') return BOOT.tipeFooter;
        if (state.area === 'sidebar') return BOOT.tipeSidebar;
        return BOOT.tipeHome;
    }
    function labelTipe(t) { var m = META[t]; if (m) return m.label; var l = tipeList(); return l[t] || t; }
    function iconTipe(t) { return ICONS[t] || '<span class="sb-ic-fallback"></span>'; }
    function cur() { return state.items.find(function(x) { return String(x.id) === String(state.sel); }); }
    function nid(x) { return parseInt(x, 10) || 0; }

    var _loading = false;
    function load() {
        if (_loading) return;
        _loading = true;
        $('sbCanvas').innerHTML = '<div class="sb-empty">Memuat...</div>';
        api('list', { area: state.area }).then(function(d) {
            if (!d || !d.success) { toast((d && d.error) || 'Gagal memuat sections.', false); return; }
            state.items = d.data || [];
            if (!state.items.some(function(x) { return nid(x.id) === nid(state.sel); })) {
                state.sel = state.items.length ? state.items[0].id : 0;
            }
            renderAll();
        }).catch(function() { toast('Server error.', false); })
        .finally(function() { _loading = false; });
    }

    function renderAll() { renderTabs(); renderWidgets(); renderCanvas(); renderInspector(); }

    function renderTabs() {
        document.querySelectorAll('#sbAreaTabs .sb-tab').forEach(function(b) {
            b.classList.toggle('active', b.dataset.area === state.area);
        });
    }

    function widgetOrder() {
        var k = state.area === 'footer' ? 'orderFooter' : (state.area === 'sidebar' ? 'orderSidebar' : 'orderHome');
        var ord = BOOT[k] || [];
        var all = Object.keys(tipeList());
        var out = ord.filter(function(t) { return all.indexOf(t) !== -1; });
        all.forEach(function(t) { if (out.indexOf(t) === -1) out.push(t); });
        return out;
    }
    var _widgetFilter = '';
    var _widgetCat = 'semua';
    var WIDGET_CATS = { 'semua': 'Semua', 'berita': 'Berita', 'interaksi': 'Interaksi', 'media': 'Media', 'info': 'Info', 'lain': 'Lain' };
    function widgetCat(t) {
        if (['berita_terbaru', 'populer'].indexOf(t) !== -1) return 'berita';
        if (['kategori_berita', 'kategori', 'kategori_list', 'latest'].indexOf(t) !== -1) return 'berita';
        if (['search', 'newsletter', 'cta', 'sosmed', 'countdown', 'iklan'].indexOf(t) !== -1) return 'interaksi';
        if (['hero', 'carousel', 'carousel_berita', 'image', 'video', 'audio'].indexOf(t) !== -1) return 'media';
        if (['brand', 'links', 'contact', 'teks', 'html', 'bottom'].indexOf(t) !== -1) return 'info';
        return 'lain';
    }
    function renderCatBar() {
        var bar = $('sbCatBar');
        if (!bar) return;
        bar.innerHTML = '';
        Object.keys(WIDGET_CATS).forEach(function(c) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'sb-pill' + (_widgetCat === c ? ' sel' : '');
            b.textContent = WIDGET_CATS[c];
            b.addEventListener('click', function() { _widgetCat = c; renderCatBar(); renderWidgets(); });
            bar.appendChild(b);
        });
    }
    function widgetAreaTag(t) {
        var tags = [];
        if (BOOT.tipeHome && BOOT.tipeHome[t]) tags.push('home');
        if (BOOT.tipeSidebar && BOOT.tipeSidebar[t]) tags.push('sidebar');
        if (BOOT.tipeFooter && BOOT.tipeFooter[t]) tags.push('footer');
        if (tags.length >= 3) return 'semua';
        return tags.join('+') || 'semua';
    }
    function renderWidgets() {
        renderCatBar();
        var box = $('sbWidgets');
        box.innerHTML = '';
        widgetOrder().forEach(function(t) {
            if (_widgetFilter && labelTipe(t).toLowerCase().indexOf(_widgetFilter) === -1 && t.toLowerCase().indexOf(_widgetFilter) === -1) return;
            if (_widgetCat !== 'semua' && widgetCat(t) !== _widgetCat) return;
            var d = document.createElement('div');
            d.className = 'sb-widget';
            d.draggable = true;
            d.dataset.tipe = t;
            d.dataset.cat = widgetAreaTag(t);
            d.innerHTML = '<span class="cat">' + widgetAreaTag(t) + '</span><span class="ic">' + iconTipe(t) + '</span><span class="lb">' + labelTipe(t) + '</span>';
            d.addEventListener('dragstart', function(e) {
                e.dataTransfer.effectAllowed = 'copy';
                try { e.dataTransfer.setData('text/sb-tipe', t); } catch (x) {}
                try { e.dataTransfer.setData('text/plain', 'sb-tipe:' + t); } catch (x) {}
            });
            d.addEventListener('click', function() { addWidget(t); });
            box.appendChild(d);
        });
    }

    function cardSub(s) {
        var gaya = (s.cfg && s.cfg.gaya_gambar) ? s.cfg.gaya_gambar : '-';
        return s.tipe + ' • ' + gaya + '/' + (s.animasi || '-') + ' • #' + s.id;
    }

    function renderCanvas() {
        var box = $('sbCanvas');
        $('sbCanvasTitle').textContent = 'Kanvas — drop di sini (' + state.items.length + ')';
        box.innerHTML = '';
        // Handler drop dipasang SELALU (termasuk kanvas kosong) agar drag pertama bisa masuk.
        // Bebas pindah: terima copy (widget baru) maupun move (geser kartu) di semua zona.
        box.ondragover = function(e) {
            e.preventDefault();
            try { e.dataTransfer.dropEffect = window._sbDragId ? 'move' : 'copy'; } catch (x) {}
            if (e.target === box && !$('sbPh')) box.appendChild(phEl());
        };
        box.ondragleave = function(e) {
            if (e.target === box && !box.contains(e.relatedTarget)) clearPh();
        };
        box.ondrop = function(e) {
            e.preventDefault();
            var t = dropTipe(e);
            var mid = dropMoveId(e);
            var beforeId = 0;
            var ph = $('sbPh');
            if (ph && ph.nextSibling && ph.nextSibling.dataset && ph.nextSibling.dataset.id) {
                beforeId = parseInt(ph.nextSibling.dataset.id, 10) || 0;
            }
            clearPh();
            if (t) { addWidget(t, beforeId); return; }
            if (mid) {
                if (beforeId) moveCard(mid, beforeId);
                else {
                    var ids = state.items.map(function(x) { return x.id; });
                    var from = ids.indexOf(mid);
                    if (from !== -1) {
                        var mv = ids.splice(from, 1)[0];
                        ids.push(mv);
                        saveOrder(ids);
                    }
                }
            }
        };
        if (!state.items.length) {
            box.innerHTML = '<div class="sb-empty">Kanvas kosong — drag widget ke sini atau klik widget.</div>';
            return;
        }
        state.items.forEach(function(s) {
            var card = document.createElement('div');
            card.className = 'sb-card' + (nid(s.id) === nid(state.sel) ? ' selected' : '') + (parseInt(s.is_active, 10) ? '' : ' draft-off');
            card.draggable = true;
            card.dataset.id = s.id;
            var title = (s.judul && s.judul.trim() !== '') ? s.judul : labelTipe(s.tipe);
            var isFirst = state.items.length && nid(state.items[0].id) === nid(s.id);
            var isLast = state.items.length && nid(state.items[state.items.length - 1].id) === nid(s.id);
            card.innerHTML = '<span class="sb-grip" title="Geser untuk pindah">' + (UI.grip || '') + '</span>'
                + '<span class="sb-thumb">' + iconTipe(s.tipe) + '</span>'
                + '<span class="sb-info"><span class="sb-name"></span><br><span class="sb-sub"></span></span>'
                + '<span class="sb-actions">'
                + '<span class="sb-nav">'
                + '<button type="button" class="sb-iconbtn" data-act="up" title="Naik"' + (isFirst ? ' disabled' : '') + '>' + (UI.chevL || '') + '</button>'
                + '<button type="button" class="sb-iconbtn" data-act="down" title="Turun"' + (isLast ? ' disabled' : '') + '>' + (UI.chevR || '') + '</button>'
                + '</span>'
                + '<button type="button" class="sb-iconbtn" data-act="dup" title="Duplikat">' + (UI.copy || '') + '</button>'
                + '<button type="button" class="sb-iconbtn" data-act="eye" title="Aktif/Nonaktif">' + (parseInt(s.is_active, 10) ? (UI.eye || '') : (UI.eyeOff || '')) + '</button>'
                + '<button type="button" class="sb-iconbtn danger" data-act="del" title="Hapus">' + (UI.trash || '') + '</button>'
                + '</span>';
            card.querySelector('.sb-name').textContent = title;
            card.querySelector('.sb-sub').textContent = cardSub(s);
            card.addEventListener('click', function(e) {
                var act = e.target.closest('[data-act]');
                if (act) {
                    e.stopPropagation();
                    cardAction(s.id, act.dataset.act);
                    return;
                }
                state.sel = s.id;
                state.dirty = false;
                renderCanvas();
                renderInspector();
            });
            card.addEventListener('dragstart', function(e) {
                if (e.target.closest && e.target.closest('[data-act]')) { e.preventDefault(); return; }
                try { e.dataTransfer.effectAllowed = 'copyMove'; } catch (x) {}
                try { e.dataTransfer.setData('text/sb-move', String(s.id)); } catch (x) {}
                try { e.dataTransfer.setData('text/plain', 'sb-move:' + s.id); } catch (x) {}
                window._sbDragId = nid(s.id);
                card.classList.add('dragging');
            });
            card.addEventListener('dragend', function() { card.classList.remove('dragging'); clearPh(); window._sbDragId = 0; });
            card.addEventListener('dragenter', function(e) {
                e.preventDefault();
                showPh(box, card);
            });
            card.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                try { e.dataTransfer.dropEffect = window._sbDragId ? 'move' : 'copy'; } catch (x) {}
                showPh(box, card);
            });
            card.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var t = dropTipe(e);
                if (t) { clearPh(); addWidget(t, nid(s.id)); return; }
                var mid = dropMoveId(e);
                clearPh();
                if (mid && mid !== nid(s.id)) moveCard(mid, s.id);
            });
            box.appendChild(card);
        });
        var end = document.createElement('div');
        end.className = 'sb-drop-end';
        end.id = 'sbDropEnd';
        end.textContent = 'Taruh di sini untuk tambah di bawah';
        end.addEventListener('dragover', function(e) {
            e.preventDefault();
            try { e.dataTransfer.dropEffect = 'copy'; } catch (x) {}
            showPh(box, end);
        });
        end.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var t = dropTipe(e);
            var mid = dropMoveId(e);
            clearPh();
            if (t) { addWidget(t, 0); return; }
            if (mid) {
                var ids = state.items.map(function(x) { return nid(x.id); });
                var from = ids.indexOf(nid(mid));
                if (from !== -1) {
                    var mv = ids.splice(from, 1)[0];
                    ids.push(mv);
                    saveOrder(ids);
                }
            }
        });
        box.appendChild(end);
    }

    function phEl() {
        var p = document.createElement('div');
        p.className = 'sb-placeholder';
        p.id = 'sbPh';
        return p;
    }
    function clearPh() { var p = $('sbPh'); if (p) p.remove(); }
    function showPh(box, beforeCard) {
        clearPh();
        box.insertBefore(phEl(), beforeCard);
    }

    function moveCard(moveId, beforeId) {
        var ids = state.items.map(function(x) { return nid(x.id); });
        moveId = nid(moveId); beforeId = nid(beforeId);
        var from = ids.indexOf(moveId);
        if (from === -1) return;
        ids.splice(from, 1);
        var to = ids.indexOf(beforeId);
        if (to === -1) ids.push(moveId);
        else ids.splice(to, 0, moveId);
        state.sel = moveId;
        saveOrder(ids);
    }

    function moveStep(id, dir) {
        var ids = state.items.map(function(x) { return nid(x.id); });
        id = nid(id);
        var i = ids.indexOf(id);
        var j = i + dir;
        if (i === -1 || j < 0 || j >= ids.length) return;
        var t = ids[i]; ids[i] = ids[j]; ids[j] = t;
        state.sel = id;
        toast(dir < 0 ? 'Naik...' : 'Turun...');
        saveOrder(ids);
    }

    function saveOrder(ids, silent) {
        ids = (ids || []).map(nid);
        api('reorder', { area: state.area, ids: ids }).then(function(d) {
            if (!d || !d.success) { toast((d && d.error) || 'Gagal menyimpan urutan.', false); load(); return; }
            var map = {};
            state.items.forEach(function(x) { map[String(nid(x.id))] = x; });
            state.items = ids.map(function(id) { return map[String(id)]; }).filter(Boolean);
            renderCanvas();
            if (!silent) toast('Urutan tersimpan.');
        }).catch(function() { toast('Server error — urutan tidak tersimpan.', false); load(); });
    }

    function addWidget(tipe, beforeId) {
        if (!tipeList()[tipe]) { toast('Tipe widget tidak valid.', false); return; }
        toast('Menambahkan ' + labelTipe(tipe) + '...');
        api('create', { area: state.area, tipe: tipe }).then(function(d) {
            if (!d || !d.success) { toast((d && d.error) || 'Gagal menambah widget.', false); load(); return; }
            var newId = nid(d.id);
            state.sel = newId;
            state.tab = 'konten';
            beforeId = nid(beforeId);
            if (beforeId) {
                var ids = state.items.map(function(x) { return nid(x.id); });
                ids.push(newId);
                var from = ids.indexOf(newId);
                var mv = ids.splice(from, 1)[0];
                var to = ids.indexOf(beforeId);
                if (to === -1) ids.push(mv);
                else ids.splice(to, 0, mv);
                saveOrder(ids, true);
            }
            load();
            toast('Widget ditambahkan — silakan edit di Inspector.');
        }).catch(function() { toast('Server error.', false); });
    }

    function cardAction(id, act) {
        if (act === 'up') { moveStep(id, -1); return; }
        if (act === 'down') { moveStep(id, 1); return; }
        if (act === 'del') {
            Swal.fire({ title: 'Hapus section?', text: 'Section hilang dari home/footer.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then(function(r) {
                if (!r.isConfirmed) return;
                api('delete', { id: id }).then(function(d) {
                    if (!d.success) { toast('Gagal menghapus.', false); return; }
                    if (state.sel === id) state.sel = 0;
                    load();
                });
            });
        } else if (act === 'eye') {
            api('toggle', { id: id }).then(function(d) { if (d.success) load(); });
        } else if (act === 'dup') {
            api('duplicate', { id: id }).then(function(d) {
                if (!d.success) { toast('Gagal menduplikat.', false); return; }
                state.sel = d.id || 0;
                load();
            });
        }
    }

    // ---------- Inspector ----------
    var FIELD_DEFS = {
        hero: [['subjudul', 'text', 'Subjudul'], ['isi', 'textarea', 'Konten / HTML'], ['jumlah', 'slider', 'Jumlah berita (mode berita)', 1, 10], ['gambar', 'image', 'Gambar'], ['tombol_teks', 'text', 'Teks tombol'], ['tombol_link', 'text', 'Link tombol']],
        carousel: [['galeri', 'gallery', 'Gambar'], ['autoplay', 'slider', 'Auto-slide (detik, 0 = mati)', 0, 30]],
        carousel_berita: [['jumlah', 'slider', 'Jumlah berita', 1, 12], ['kategori_id', 'kategori_pill', 'Kategori'], ['autoplay', 'slider', 'Auto-slide (detik, 0 = mati)', 0, 30]],
        kategori_berita: [['kategori_id', 'kategori_pill', 'Kategori'], ['jumlah', 'slider', 'Jumlah berita', 1, 12], ['kolom', 'segment_kolom', 'Kolom']],
        kategori: [['kategori_id', 'kategori_pill', 'Kategori'], ['jumlah', 'slider', 'Jumlah berita', 1, 12], ['kolom', 'segment_kolom', 'Kolom']],
        latest: [['jumlah', 'slider', 'Jumlah berita', 1, 20], ['kolom', 'segment_kolom', 'Kolom'], ['tampil_tombol', 'switch', 'Tombol Lihat Semua']],
        populer: [['jumlah', 'slider', 'Jumlah berita', 1, 10], ['kolom', 'segment_kolom', 'Kolom']],
        countdown: [['target_tanggal', 'datetime', 'Target (tanggal & jam)'], ['isi', 'textarea', 'Deskripsi']],
        image: [['gambar', 'image', 'Gambar'], ['alt', 'text', 'Alt text'], ['link', 'text', 'Link (opsional)']],
        video: [['video_url', 'text', 'URL Video / YouTube'], ['poster', 'image', 'Poster (opsional)']],
        audio: [['audio_url', 'text', 'URL Audio (mp3)']],
        html: [['html', 'code', 'Kode HTML']],
        cta: [['deskripsi', 'textarea', 'Deskripsi'], ['cta_teks', 'text', 'Teks tombol'], ['cta_link', 'text', 'Link tombol']],
        teks: [['isi', 'textarea', 'Isi teks'], ['rata', 'segment_rata', 'Perataan']],
        berita_terbaru: [['jumlah', 'slider', 'Jumlah berita', 1, 10], ['kategori_id', 'kategori_pill', 'Kategori'], ['tampil_gambar', 'switch', 'Tampilkan gambar']],
        search: [['placeholder', 'text', 'Teks placeholder']],
        kategori_list: [['jumlah', 'slider', 'Maksimal kategori', 1, 30], ['tampil_jumlah', 'switch', 'Tampilkan jumlah berita']],
        iklan: [['gambar', 'image', 'Gambar banner'], ['link', 'text', 'Link tujuan (opsional)'], ['alt', 'text', 'Alt text'], ['kode', 'code', 'Kode iklan (opsional, mis. AdSense)']],
        sosmed: [['facebook', 'text', 'Link Facebook'], ['twitter', 'text', 'Link X / Twitter'], ['instagram', 'text', 'Link Instagram'], ['youtube', 'text', 'Link YouTube']],
        brand: [['logo', 'image', 'Logo brand'], ['deskripsi', 'textarea', 'Deskripsi (kosong = tagline situs)']],
        links: [['tautan', 'links', 'Daftar tautan']],
        contact: [],
        newsletter: [['judul', 'text', 'Judul (cfg)'], ['deskripsi', 'textarea', 'Deskripsi']],
        bottom: [['isi', 'textarea', 'Catatan tambahan']]
    };

    function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

    function gridPreview(gk) {
        var inner = '';
        if (gk === 'kartu-2') inner = '<div class="pv"><i></i><i></i></div>';
        else if (gk === 'kartu-3') inner = '<div class="pv"><i></i><i></i><i></i></div>';
        else if (gk === 'kartu-4') inner = '<div class="pv"><i></i><i></i><i></i><i></i></div>';
        else if (gk === 'sorotan-list') inner = '<div class="pv"><i class="big"></i><i></i></div>';
        else if (gk === 'magazine') inner = '<div class="pv"><i class="big"></i><i></i></div>';
        else if (gk === 'masonry') inner = '<div class="pv"><i style="height:70%"></i><i style="height:100%"></i><i style="height:55%"></i></div>';
        else if (gk === 'kartu-horizontal') inner = '<div class="pv"><i></i><i></i></div>';
        else if (gk === 'timeline') inner = '<div class="pv timeline"><div style="flex:1;display:flex;flex-direction:column;gap:3px;"><i></i><i></i></div></div>';
        else if (gk === 'list') inner = '<div class="pv minimal"><i></i><i></i><i></i></div>';
        else if (gk === 'overlay') inner = '<div class="pv overlay"><i></i><i></i><i></i></div>';
        else if (gk === 'minimal') inner = '<div class="pv minimal"><i></i><i></i></div>';
        else inner = '<div class="pv"><i></i><i></i></div>';
        return inner;
    }

    function segBtn(val, opt, key) {
        return '<button type="button" class="sb-seg' + (String(val) === String(opt[0]) ? ' sel' : '') + '" data-seg="' + esc(opt[0]) + '" data-key="' + key + '">' + esc(opt[1]) + '</button>';
    }
    function fieldHtml(s, f) {
        var key = f[0], type = f[1], label = f[2];
        var val = (s.cfg && s.cfg[key] != null) ? s.cfg[key] : (f[3] != null ? '' : '');
        var h = '<div class="sb-field"><label>' + esc(label) + '</label>';
        if (type === 'text') h += '<input type="text" data-k="' + key + '" value="' + esc(val) + '" placeholder="Ketik di sini...">';
        else if (type === 'number') h += '<input type="number" data-k="' + key + '" min="0" max="60" value="' + esc(val) + '">';
        else if (type === 'slider') {
            var mn = f[3] != null ? f[3] : 0, mx = f[4] != null ? f[4] : 20;
            var vv = val === '' ? mn : parseInt(val, 10);
            h += '<div class="sb-slider"><input type="range" min="' + mn + '" max="' + mx + '" value="' + vv + '" data-k="' + key + '" data-slider><input type="number" min="' + mn + '" max="' + mx + '" value="' + vv + '" data-k="' + key + '" data-num style="width:64px;"></div>';
        }
        else if (type === 'textarea') h += '<textarea data-k="' + key + '" placeholder="Tulis di sini...">' + esc(val) + '</textarea>';
        else if (type === 'code') h += '<textarea data-k="' + key + '" spellcheck="false" style="font-family:ui-monospace,monospace;font-size:12px;" placeholder="<div>...</div>">' + esc(val) + '</textarea>';
        else if (type === 'datetime') h += '<input type="datetime-local" data-k="' + key + '" value="' + esc(String(val).replace(' ', 'T').slice(0, 16)) + '">';
        else if (type === 'kolom' || type === 'segment_kolom') {
            h += '<div class="sb-segwrap" data-segwrap="' + key + '">';
            [2, 3, 4].forEach(function(k) { h += segBtn(val === '' ? 4 : val, [k, k], key); });
            h += '</div><input type="hidden" data-k="' + key + '" value="' + esc(val === '' ? 4 : val) + '">';
        }
        else if (type === 'kolom6') {
            h += '<div class="sb-segwrap" data-segwrap="' + key + '">';
            [2, 3, 4, 6].forEach(function(k) { h += segBtn(val === '' ? 4 : val, [k, k], key); });
            h += '</div><input type="hidden" data-k="' + key + '" value="' + esc(val === '' ? 4 : val) + '">';
        }
        else if (type === 'rata' || type === 'segment_rata') {
            h += '<div class="sb-segwrap" data-segwrap="' + key + '">';
            [['kiri', 'Kiri'], ['tengah', 'Tengah'], ['kanan', 'Kanan']].forEach(function(o) { h += segBtn(val || 'kiri', o, key); });
            h += '</div><input type="hidden" data-k="' + key + '" value="' + esc(val || 'kiri') + '">';
        }
        else if (type === 'kategori') {
            h += '<select data-k="' + key + '"><option value="0">— Semua / Pilih —</option>';
            KATS.forEach(function(k) { h += '<option value="' + k.id + '"' + (parseInt(val, 10) === parseInt(k.id, 10) ? ' selected' : '') + '>' + esc(k.nama) + '</option>'; });
            h += '</select>';
        }
        else if (type === 'kategori_pill') {
            h += '<div class="sb-pills" data-pillwrap="' + key + '">';
            h += '<button type="button" class="sb-pill' + (parseInt(val, 10) === 0 ? ' sel' : '') + '" data-pill="0" data-key="' + key + '">Semua</button>';
            KATS.forEach(function(k) { h += '<button type="button" class="sb-pill' + (parseInt(val, 10) === parseInt(k.id, 10) ? ' sel' : '') + '" data-pill="' + k.id + '" data-key="' + key + '">' + esc(k.nama) + '</button>'; });
            h += '</div><input type="hidden" data-k="' + key + '" value="' + esc(val === '' ? 0 : val) + '">';
        }
        else if (type === 'switch') {
            h += '<div class="sb-switch"><span>Aktif</span><input type="checkbox" data-k="' + key + '"' + (val ? ' checked' : '') + ' style="width:20px;height:20px;accent-color:#10b981;"></div>';
        }
        else if (type === 'grid_picker') {
            var curG = val || 'kartu-4';
            h += '<div class="sb-gridpicker" data-gridcontent="' + key + '">';
            GRID_ORDER.forEach(function(gk) {
                h += '<div class="sb-grid-opt' + (curG === gk ? ' sel' : '') + '" data-cgrid="' + esc(gk) + '">' + gridPreview(gk) + '<span class="lb">' + esc(STYLES[gk] || gk) + '</span></div>';
            });
            h += '</div><input type="hidden" data-k="' + key + '" value="' + esc(curG) + '">';
        }
        else if (type === 'image') {
            h += '<div class="sb-field" style="margin:0;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px;"><span class="sb-note"><span style="display:inline-flex;vertical-align:-3px;margin-right:4px;">' + (UI.image || '') + '</span>Gambar' + (val ? ' (1 gambar)' : '') + '</span>';
            if (val) h += '<img class="sb-imgprev" src="../' + esc(val) + '">';
            h += '<input type="file" accept="image/*" data-upload="' + key + '" style="margin-top:8px;font-size:12px;">';
            h += '<input type="text" data-k="' + key + '" value="' + esc(val) + '" placeholder="uploads/..." style="margin-top:6px;">';
            h += '<div class="sb-note">Gambar bawaan otomatis — upload untuk ganti.</div></div>';
            return h;
        }
        else if (type === 'gallery') {
            var arr = Array.isArray(val) ? val : [];
            h += '<div class="sb-field" style="margin:0;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px;"><span class="sb-note"><span style="display:inline-flex;vertical-align:-3px;margin-right:4px;">' + (UI.image || '') + '</span>Gambar (' + arr.length + ' gambar)</span>';
            h += '<div class="sb-gal" data-gal="' + key + '">';
            arr.forEach(function(p, i) { h += '<span class="g"><img src="../' + esc(p) + '"><button type="button" data-rm="' + i + '" data-gk="' + key + '">×</button></span>'; });
            h += '</div><input type="file" accept="image/*" multiple data-upload-gal="' + key + '" style="margin-top:8px;font-size:12px;"></div>';
            return h;
        }
        else if (type === 'links') {
            var list = [];
            try {
                if (Array.isArray(val)) list = val.slice();
                else if (typeof val === 'string' && val.trim() !== '') {
                    var parsed = JSON.parse(val);
                    if (Array.isArray(parsed)) list = parsed;
                }
            } catch (e) {}
            if (!list.length && typeof val === 'string' && val.trim() !== '' && val.trim().charAt(0) !== '[') {
                // migrasi format lama "Label | URL" per baris
                String(val).split(/\r?\n/).forEach(function(line) {
                    line = String(line || '').trim();
                    if (!line) return;
                    var parts = line.split('|');
                    var lb = (parts[0] || '').trim(), ur = (parts[1] || '').trim();
                    if (lb && ur) list.push({ label: lb, url: ur });
                });
            }
            h += '<div class="sb-field" style="margin:0;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px;"><span class="sb-note"><span style="display:inline-flex;vertical-align:-3px;margin-right:4px;">' + (UI.link || '') + '</span>1 baris = 1 tautan (label + URL)</span>';
            h += '<div class="sb-links-list" data-links="' + key + '">';
            if (!list.length) list = [{ label: '', url: '' }];
            list.forEach(function(it, i) {
                h += '<div class="sb-link-row"><input type="text" data-link-label="' + i + '" value="' + esc(it.label || '') + '" placeholder="Label: Beranda"><input type="text" data-link-url="' + i + '" value="' + esc(it.url || '') + '" placeholder="URL: index"><button type="button" class="sb-link-del" data-link-del="' + i + '" title="Hapus">×</button></div>';
            });
            h += '</div><button type="button" class="sb-link-add" data-link-add="1"><span style="display:inline-flex;vertical-align:-2px;margin-right:4px;">' + (UI.plus || '+') + '</span>Tambah tautan</button>';
            h += '<input type="hidden" data-k="' + key + '" value=\'' + esc(JSON.stringify(list)) + '\'>';
            h += '</div>';
            return h;
        }
        h += '</div>';
        return h;
    }

    function renderInspector() {
        document.querySelectorAll('.sb-insp-tab').forEach(function(b) {
            b.classList.toggle('active', b.dataset.tab === state.tab);
        });
        var body = $('sbInspBody');
        var s = cur();
        if (!s) {
            $('sbCanvasTitle').textContent = 'Kanvas — drop di sini (0)';
            $('sbInspHead').textContent = 'Inspector';
            body.innerHTML = '<p class="sb-empty">Pilih kartu di kanvas untuk mengedit.</p>';
            return;
        }
        var sidebarHint = sidePrevHtml(s);
        var canvasCount = state.items.length;
        if (canvasCount) {
            $('sbCanvasTitle').textContent = 'Kanvas — drop di sini (' + canvasCount + ')';
        }
        $('sbInspHead').textContent = 'Inspector: ' + labelTipe(s.tipe);
        var h = sidebarHint;
        if (state.tab === 'konten') {
            h += '<div class="sb-field"><label>Judul</label><input type="text" id="sbJudul" value="' + esc(s.judul || '') + '"></div>';
            var defs = FIELD_DEFS[s.tipe] || [];
            var styleNote = '';
            if (GRID_TIPES.indexOf(s.tipe) !== -1) {
                var curPrev = (s.cfg && s.cfg.style_grid) || 'kartu-4';
                styleNote = '<div class="sb-note" style="margin-bottom:10px;display:flex;align-items:center;gap:6px;"><span style="display:inline-flex;">' + (UI.layers || '') + '</span><span>Gaya aktif: <b>' + esc(STYLES[curPrev] || curPrev) + '</b> — ubah via tab Gaya.</span></div>';
                h += styleNote;
            }
            if (s.tipe === 'newsletter') {
                h += '<div class="sb-note" style="margin-bottom:10px;">Judul tampil memakai kolom Judul di atas bila diisi.</div>';
            }
            if (state.area === 'sidebar' && (s.tipe === 'iklan' || s.tipe === 'sosmed')) {
                h += '<div class="sb-note" style="margin-bottom:10px;">Widget sidebar tampil di kolom kanan home (lebar ± 1/3).</div>';
            }
            defs.forEach(function(f) {
                if (f[0] === 'judul' && s.tipe === 'newsletter') return;
                h += fieldHtml(s, f);
            });
            if (!defs.length) h += '<p class="sb-note">Tipe ini memakai data situs otomatis.</p>';
        } else if (state.tab === 'gaya') {
            if (GRID_TIPES.indexOf(s.tipe) !== -1) {
                var curStyle = (s.cfg && s.cfg.style_grid) || 'kartu-4';
                h += '<div class="sb-opt-sec">Gaya Grid (Berita & Kategori Berita)</div><div class="sb-gridpicker" id="sbGridPicker">';
                GRID_ORDER.forEach(function(gk) {
                    h += '<div class="sb-grid-opt' + (curStyle === gk ? ' sel' : '') + '" data-grid="' + esc(gk) + '">' + gridPreview(gk) + '<span class="lb">' + esc(STYLES[gk] || gk) + '</span></div>';
                });
                h += '</div><input type="hidden" id="sbStyleGrid" value="' + esc(curStyle) + '">';
            }
            if (GAYA_TIPES.indexOf(s.tipe) !== -1) {
                h += '<div class="sb-opt-sec">Style Gambar</div><div class="sb-opt-grid" id="sbGayaGrid">';
                Object.keys(GAYA).forEach(function(g) {
                    var curG = (s.cfg && s.cfg.gaya_gambar) || 'statis';
                    h += '<div class="sb-opt' + (curG === g ? ' sel' : '') + '" data-gaya="' + esc(g) + '"><span class="oi">' + (GAYA_ICON[g] || (UI.image || '')) + '</span>' + esc(GAYA[g]) + '</div>';
                });
                h += '</div>';
            }
            h += '<div class="sb-opt-sec">Animasi Masuk (Saat Scroll)</div><div class="sb-opt-grid" id="sbAnimGrid">';
            Object.keys(ANIM).forEach(function(a) {
                h += '<div class="sb-opt' + ((s.animasi || 'fade-up') === a ? ' sel' : '') + '" data-anim="' + esc(a) + '"><span class="oi">' + (ANIM_ICON[a] || (UI.eye || '')) + '</span>' + esc(ANIM[a]) + '</div>';
            });
            h += '</div>';
        } else {
            h += '<div class="sb-field"><label>Urutan</label><input type="number" id="sbUrutan" min="1" max="999" value="' + esc(s.urutan) + '"></div>';
            var areaLabel = state.area === 'footer' ? 'footer' : (state.area === 'sidebar' ? 'sidebar (kolom kanan home)' : 'home');
            h += '<div class="sb-switch"><span>Tampilkan di ' + areaLabel + '</span><input type="checkbox" id="sbAktif"' + (parseInt(s.is_active, 10) ? ' checked' : '') + ' style="width:20px;height:20px;accent-color:#10b981;"></div>';
            h += '<div class="sb-field"><label>ID</label><input type="text" value="#' + s.id + ' • ' + esc(s.tipe) + '" disabled></div>';
            h += '<button type="button" class="sb-iconbtn danger" id="sbDelFull" style="width:100%;height:auto;padding:10px;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;gap:8px;">' + (UI.trash || '') + '<span>Hapus section ini</span></button>';
        }
        h += '<div class="sb-savebar sb-savebar-float"><button type="button" class="sb-btn-save" id="sbSave"><span style="display:inline-flex;vertical-align:-3px;margin-right:6px;">' + (UI.save || '') + '</span><span>Simpan Perubahan</span></button><button type="button" class="sb-btn-cancel" id="sbCancel">Batal</button></div>';
        body.innerHTML = h;
        bindInspector(s);
    }

    function pendingState(s) {
        if (!s._pending) s._pending = { judul: null, cfg: {}, animasi: null, gaya: null, grid: null, aktif: null, urutan: null };
        return s._pending;
    }
    function collectCfgLive(s) {
        // Kumpulkan SEMUA tab sekaligus: konten + gaya + lanjut, walau tab tidak sedang dibuka.
        var cfg = Object.assign({}, s.cfg || {});
        var p = s._pending || {};
        Object.keys(p.cfg || {}).forEach(function(k) { cfg[k] = p.cfg[k]; });
        document.querySelectorAll('#sbInspBody [data-k]').forEach(function(el) {
            var k = el.dataset.k;
            if (el.hasAttribute('data-slider')) return;
            if (el.type === 'checkbox') cfg[k] = el.checked ? 1 : 0;
            else if (el.type === 'number' || el.hasAttribute('data-num')) cfg[k] = el.value === '' ? 0 : parseInt(el.value, 10);
            else cfg[k] = el.value;
        });
        return cfg;
    }
    function snapshotInspector(s) {
        // Simpan perubahan tab aktif ke pending sebelum pindah tab / simpan.
        var p = pendingState(s);
        var j = $('sbJudul');
        if (j) p.judul = j.value;
        document.querySelectorAll('#sbInspBody [data-k]').forEach(function(el) {
            var k = el.dataset.k;
            if (el.hasAttribute('data-slider')) return;
            if (el.type === 'checkbox') p.cfg[k] = el.checked ? 1 : 0;
            else if (el.type === 'number' || el.hasAttribute('data-num')) p.cfg[k] = el.value === '' ? 0 : parseInt(el.value, 10);
            else p.cfg[k] = el.value;
        });
        var g = document.querySelector('#sbGayaGrid .sb-opt.sel');
        if (g) p.gaya = g.dataset.gaya;
        var gp = document.querySelector('#sbGridPicker .sb-grid-opt.sel');
        if (gp) p.grid = gp.dataset.grid;
        else { var hs = $('sbStyleGrid'); if (hs) p.grid = hs.value; }
        var a = document.querySelector('#sbAnimGrid .sb-opt.sel');
        if (a) p.animasi = a.dataset.anim;
        var u = $('sbUrutan');
        if (u) p.urutan = u.value;
        var ak = $('sbAktif');
        if (ak) p.aktif = ak.checked ? 1 : 0;
    }

    function collectCfg(s) {
        var cfg = Object.assign({}, s.cfg || {});
        document.querySelectorAll('#sbInspBody [data-k]').forEach(function(el) {
            var k = el.dataset.k;
            if (el.hasAttribute('data-slider')) return; // range diduplikasi number: ambil number saja
            if (el.type === 'checkbox') cfg[k] = el.checked ? 1 : 0;
            else if (el.type === 'number' || el.hasAttribute('data-num')) cfg[k] = el.value === '' ? 0 : parseInt(el.value, 10);
            else cfg[k] = el.value;
        });
        return cfg;
    }

    function bindInspector(s) {
        var save = $('sbSave');
        var cancel = $('sbCancel');
        if (save) save.addEventListener('click', function() {
            snapshotInspector(s);
            var p = pendingState(s);
            var payload = { id: s.id, area: state.area };
            if (p.judul !== null && p.judul !== undefined) payload.judul = p.judul;
            payload.cfg = collectCfgLive(s);
            if (p.gaya) payload.gaya_gambar = p.gaya;
            if (p.grid) payload.cfg.style_grid = p.grid;
            if (p.animasi) payload.animasi = p.animasi;
            if (p.aktif !== null && p.aktif !== undefined) payload.is_active = p.aktif;
            payload.urutan_keep = true;
            save.disabled = true;
            save.textContent = 'Menyimpan...';
            // urutan diubah via input? simpan manual lewat reorder agar konsisten
            api('update', payload).then(function(d) {
                if (!d || !d.success) { toast((d && d.error) || 'Gagal menyimpan.', false); return; }
                var nu = p.urutan !== null && p.urutan !== undefined ? parseInt(p.urutan, 10) : NaN;
                if (nu && nu !== parseInt(s.urutan, 10)) {
                    var ids = state.items.map(function(x) { return x.id; });
                    ids = ids.filter(function(x) { return x !== s.id; });
                    ids.splice(Math.max(0, Math.min(ids.length, nu - 1)), 0, s.id);
                    saveOrder(ids);
                }
                s._pending = null;
                state.dirty = false;
                load();
                toast('Tersimpan.');
            }).catch(function() { toast('Server error.', false); })
            .finally(function() { save.disabled = false; save.innerHTML = (UI.save || '') + '<span>Simpan Perubahan</span>'; });
        });
        if (cancel) cancel.addEventListener('click', function() { s._pending = null; state.dirty = false; renderInspector(); });
        document.querySelectorAll('#sbGayaGrid .sb-opt').forEach(function(o) {
            o.addEventListener('click', function() {
                document.querySelectorAll('#sbGayaGrid .sb-opt').forEach(function(x) { x.classList.remove('sel'); });
                o.classList.add('sel');
            });
        });
        document.querySelectorAll('#sbAnimGrid .sb-opt').forEach(function(o) {
            o.addEventListener('click', function() {
                document.querySelectorAll('#sbAnimGrid .sb-opt').forEach(function(x) { x.classList.remove('sel'); });
                o.classList.add('sel');
            });
        });
        document.querySelectorAll('#sbGridPicker .sb-grid-opt').forEach(function(o) {
            o.addEventListener('click', function() {
                document.querySelectorAll('#sbGridPicker .sb-grid-opt').forEach(function(x) { x.classList.remove('sel'); });
                o.classList.add('sel');
                var hs = $('sbStyleGrid');
                if (hs) hs.value = o.dataset.grid;
            });
        });
        document.querySelectorAll('#sbInspBody [data-seg]').forEach(function(b) {
            b.addEventListener('click', function() {
                var wrap = b.closest('[data-segwrap]');
                if (wrap) wrap.querySelectorAll('[data-seg]').forEach(function(x) { x.classList.remove('sel'); });
                b.classList.add('sel');
                var hid = wrap ? wrap.parentNode.querySelector('input[data-k="' + b.dataset.key + '"]') : null;
                if (!hid) hid = document.querySelector('#sbInspBody input[data-k="' + b.dataset.key + '"]');
                if (hid) hid.value = b.dataset.seg;
            });
        });
        document.querySelectorAll('#sbInspBody [data-pill]').forEach(function(b) {
            b.addEventListener('click', function() {
                var wrap = b.closest('[data-pillwrap]');
                if (wrap) wrap.querySelectorAll('[data-pill]').forEach(function(x) { x.classList.remove('sel'); });
                b.classList.add('sel');
                var hid = document.querySelector('#sbInspBody input[data-k="' + b.dataset.key + '"]');
                if (hid) hid.value = b.dataset.pill;
            });
        });
        document.querySelectorAll('#sbInspBody [data-gridcontent] [data-cgrid]').forEach(function(o) {
            o.addEventListener('click', function() {
                var wrap = o.closest('[data-gridcontent]');
                if (wrap) wrap.querySelectorAll('[data-cgrid]').forEach(function(x) { x.classList.remove('sel'); });
                o.classList.add('sel');
                var hid = wrap ? wrap.parentNode.querySelector('input[data-k="style_grid"]') : null;
                if (hid) hid.value = o.dataset.cgrid;
            });
        });
        document.querySelectorAll('#sbInspBody [data-slider]').forEach(function(rg) {
            rg.addEventListener('input', function() {
                var num = rg.parentNode.querySelector('[data-num]');
                if (num) num.value = rg.value;
            });
        });
        document.querySelectorAll('#sbInspBody [data-num]').forEach(function(num) {
            num.addEventListener('input', function() {
                var rg = num.parentNode.querySelector('[data-slider]');
                if (rg) rg.value = num.value;
            });
        });
        var del = $('sbDelFull');
        if (del) del.addEventListener('click', function() { cardAction(s.id, 'del'); });
        document.querySelectorAll('#sbInspBody [data-upload]').forEach(function(inp) {
            inp.addEventListener('change', function() {
                var key = inp.dataset.upload;
                var f = inp.files && inp.files[0];
                if (!f) return;
                toast('Mengunggah...');
                api('upload', {}, f).then(function(d) {
                    if (!d.success) { toast(d.error || 'Upload gagal.', false); return; }
                    var hid = document.querySelector('#sbInspBody [data-k="' + key + '"]');
                    if (hid) hid.value = d.path;
                    var box = inp.closest('.sb-field');
                    var old = box ? box.querySelector('.sb-imgprev') : null;
                    if (old) old.src = d.url;
                    else if (box) {
                        var im = document.createElement('img');
                        im.className = 'sb-imgprev';
                        im.src = d.url;
                        inp.parentNode.insertBefore(im, inp);
                    }
                    toast('Gambar terunggah — klik Simpan.');
                });
            });
        });
        document.querySelectorAll('#sbInspBody [data-upload-gal]').forEach(function(inp) {
            inp.addEventListener('change', function() {
                var files = inp.files;
                if (!files || !files.length) return;
                toast('Mengunggah ' + files.length + ' gambar...');
                var done = 0, added = [];
                Array.prototype.forEach.call(files, function(f) {
                    api('upload', {}, f).then(function(d) {
                        if (d.success) added.push(d.path);
                    }).finally(function() {
                        done++;
                        if (done === files.length) {
                            var gal = inp.closest('.sb-field').querySelector('[data-gal]');
                            var curArr = (cur() && cur().cfg && Array.isArray(cur().cfg[inp.dataset.uploadGal])) ? cur().cfg[inp.dataset.uploadGal].slice() : [];
                            added.forEach(function(p) { curArr.push(p); });
                            var c = cur();
                            if (c) { c.cfg[inp.dataset.uploadGal] = curArr; }
                            renderInspector();
                            toast(added.length + ' gambar ditambahkan — klik Simpan.');
                        }
                    });
                });
            });
        });
        document.querySelectorAll('#sbInspBody [data-rm]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var c = cur();
                if (!c) return;
                var gk = btn.dataset.gk;
                var arr = (c.cfg && Array.isArray(c.cfg[gk])) ? c.cfg[gk].slice() : [];
                arr.splice(parseInt(btn.dataset.rm, 10), 1);
                c.cfg[gk] = arr;
                renderInspector();
            });
        });
        // Editor tautan: tambah/hapus baris, sinkron ke hidden JSON.
        document.querySelectorAll('#sbInspBody [data-links]').forEach(function(box) {
            var key = box.dataset.links;
            var hid = box.parentNode.querySelector('input[data-k="' + key + '"]') || document.querySelector('#sbInspBody input[data-k="' + key + '"]');
            function sync() {
                var arr = [];
                box.querySelectorAll('.sb-link-row').forEach(function(row) {
                    var lb = row.querySelector('[data-link-label]');
                    var ur = row.querySelector('[data-link-url]');
                    var l = lb ? lb.value.trim() : '', u = ur ? ur.value.trim() : '';
                    if (l !== '' || u !== '') arr.push({ label: l, url: u });
                });
                if (hid) hid.value = JSON.stringify(arr);
                var c = cur();
                if (c) { c.cfg = c.cfg || {}; c.cfg[key] = arr; pendingState(c).cfg[key] = arr; }
            }
            box.querySelectorAll('[data-link-label],[data-link-url]').forEach(function(inp) {
                inp.addEventListener('input', sync);
            });
            var add = box.parentNode.querySelector('[data-link-add]') || document.querySelector('#sbInspBody [data-link-add]');
            if (add) add.addEventListener('click', function() {
                sync();
                var c = cur();
                var arr = (c && c.cfg && Array.isArray(c.cfg[key])) ? c.cfg[key].slice() : [];
                arr.push({ label: '', url: '' });
                if (c) { c.cfg[key] = arr; pendingState(c).cfg[key] = arr; }
                renderInspector();
            });
            box.querySelectorAll('[data-link-del]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var i = parseInt(btn.dataset.linkDel, 10);
                    var c = cur();
                    var arr = (c && c.cfg && Array.isArray(c.cfg[key])) ? c.cfg[key].slice() : [];
                    if (!arr.length) {
                        sync();
                        arr = (c && c.cfg && Array.isArray(c.cfg[key])) ? c.cfg[key].slice() : [];
                    }
                    arr.splice(i, 1);
                    if (c) { c.cfg[key] = arr; pendingState(c).cfg[key] = arr; }
                    renderInspector();
                });
            });
        });
    }

    // ---------- events ----------
    document.querySelectorAll('#sbAreaTabs .sb-tab').forEach(function(b) {
        b.addEventListener('click', function() {
            var s = cur();
            if (s) snapshotInspector(s);
            if (state.area === b.dataset.area) return;
            state.area = b.dataset.area;
            state.sel = 0;
            state.tab = 'konten';
            try { window.history.replaceState(null, '', 'sections?area=' + state.area); } catch (e) {}
            load();
        });
    });
    document.querySelectorAll('.sb-insp-tab').forEach(function(b) {
        b.addEventListener('click', function() {
            var s = cur();
            if (s) snapshotInspector(s);
            state.tab = b.dataset.tab;
            renderInspector();
        });
    });
    $('sbLive').addEventListener('click', function() {
        window.open(BOOT.previewUrl || '../index', '_blank');
    });
    var _searchBox = $('sbWidgetSearch');
    if (_searchBox) {
        _searchBox.addEventListener('input', function() {
            _widgetFilter = _searchBox.value.trim().toLowerCase();
            renderWidgets();
        });
    }

    load();
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
