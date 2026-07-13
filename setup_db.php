<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'portal_berita';

$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die('Gagal konek ke MySQL: ' . $conn->connect_error . PHP_EOL);
}

$sqlDb = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sqlDb)) {
    die('Gagal membuat database: ' . $conn->error . PHP_EOL);
}

if (!$conn->select_db($db_name)) {
    die('Gagal memilih database: ' . $conn->error . PHP_EOL);
}

$sqlAdmin = "CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlAdmin)) {
    die('Gagal membuat tabel admin_users: ' . $conn->error . PHP_EOL);
}

$sqlMenus = "CREATE TABLE IF NOT EXISTS menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  urutan INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  menu_type ENUM('link','dropdown','mega') NOT NULL DEFAULT 'link',
  kategori_id INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlMenus)) {
    die('Gagal membuat tabel menus: ' . $conn->error . PHP_EOL);
}

$columns = [];
$resultDesc = $conn->query("DESCRIBE menus");
if ($resultDesc) {
    while ($row = $resultDesc->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

if (!in_array('parent_id', $columns, true)) {
    $alter = "ALTER TABLE menus ADD COLUMN parent_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active";
    if (!$conn->query($alter)) {
        die('Gagal menambah kolom parent_id: ' . $conn->error . PHP_EOL);
    }
}

if (!in_array('menu_type', $columns, true)) {
    $alter = "ALTER TABLE menus ADD COLUMN menu_type ENUM('link','dropdown','mega') NOT NULL DEFAULT 'link' AFTER parent_id";
    if (!$conn->query($alter)) {
        die('Gagal menambah kolom menu_type: ' . $conn->error . PHP_EOL);
    }
}

$sqlKategori = "CREATE TABLE IF NOT EXISTS kategori (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  grid_count INT NOT NULL DEFAULT 12,
  grid_style ENUM('grid','list','masonry') NOT NULL DEFAULT 'grid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlKategori)) {
    die('Gagal membuat tabel kategori: ' . $conn->error . PHP_EOL);
}

$sqlBerita = "CREATE TABLE IF NOT EXISTS berita (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT UNSIGNED NOT NULL,
  judul VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  ringkasan TEXT NULL,
  isi LONGTEXT NOT NULL,
  penulis VARCHAR(100) NULL,
  status ENUM('draft','publish') NOT NULL DEFAULT 'draft',
  tanggal_publikasi DATETIME NULL,
  gambar VARCHAR(255) NULL,
  show_meta TINYINT(1) NOT NULL DEFAULT 1,
  show_ringkasan TINYINT(1) NOT NULL DEFAULT 1,
  show_penulis TINYINT(1) NOT NULL DEFAULT 1,
  show_tanggal TINYINT(1) NOT NULL DEFAULT 1,
  show_kategori TINYINT(1) NOT NULL DEFAULT 1,
  show_gambar_detail TINYINT(1) NOT NULL DEFAULT 1,
  layout_style ENUM('default','wide','boxed') NOT NULL DEFAULT 'default',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_berita_kategori FOREIGN KEY (kategori_id)
    REFERENCES kategori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlBerita)) {
    die('Gagal membuat tabel berita: ' . $conn->error . PHP_EOL);
}

$sqlSettings = "CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_name VARCHAR(150) NOT NULL,
  site_tagline VARCHAR(255) NULL,
  logo_path VARCHAR(255) NULL,
  favicon_path VARCHAR(255) NULL,
  latest_news_count INT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlSettings)) {
    die('Gagal membuat tabel settings: ' . $conn->error . PHP_EOL);
}

$sqlVisitorLogs = "CREATE TABLE IF NOT EXISTS visitor_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash CHAR(64) NOT NULL,
  user_agent TEXT NULL,
  os VARCHAR(80) NOT NULL DEFAULT 'Tidak diketahui',
  country VARCHAR(100) NOT NULL DEFAULT 'Tidak diketahui',
  page_type VARCHAR(40) NOT NULL DEFAULT 'page',
  page_url VARCHAR(500) NULL,
  kategori_id INT UNSIGNED NULL,
  berita_id INT UNSIGNED NULL,
  referer VARCHAR(500) NULL,
  visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visitor_logs_visited_at (visited_at),
  INDEX idx_visitor_logs_kategori (kategori_id),
  INDEX idx_visitor_logs_berita (berita_id),
  INDEX idx_visitor_logs_os (os),
  INDEX idx_visitor_logs_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlVisitorLogs)) {
    die('Gagal membuat tabel visitor_logs: ' . $conn->error . PHP_EOL);
}

$check = $conn->query("SELECT COUNT(*) AS jml FROM admin_users WHERE username = 'admin'");
$needInsert = true;

if ($check) {
    $row = $check->fetch_assoc();
    if ((int)$row['jml'] > 0) {
        $needInsert = false;
    }
}

if ($needInsert) {
    $nama = 'Administrator';
    $username = 'admin';
    $plainPassword = bin2hex(random_bytes(6));
    $password = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin_users (nama, username, password) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $nama, $username, $password);
    if (!$stmt->execute()) {
        die('Gagal membuat akun admin: ' . $stmt->error . PHP_EOL);
    }
    $stmt->close();
    echo '==================================================' . PHP_EOL;
    echo 'Akun admin dibuat.' . PHP_EOL;
    echo 'Username: ' . $username . PHP_EOL;
    echo 'Password: ' . $plainPassword . PHP_EOL;
    echo 'SIMPAN password ini sekarang. Tidak akan ditampilkan lagi.' . PHP_EOL;
    echo '==================================================' . PHP_EOL;
}

$checkMenus = $conn->query("SELECT COUNT(*) AS jml FROM menus");
$needMenus = true;

if ($checkMenus) {
    $row = $checkMenus->fetch_assoc();
    if ((int)$row['jml'] > 0) {
        $needMenus = false;
    }
}

// Migration: add favicon_path column
$checkFavicon = $conn->query("SHOW COLUMNS FROM settings LIKE 'favicon_path'");
if ($checkFavicon && $checkFavicon->num_rows === 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN favicon_path VARCHAR(255) NULL AFTER logo_path");
}

// Migration: add latest news count setting
$checkLatestNewsCount = $conn->query("SHOW COLUMNS FROM settings LIKE 'latest_news_count'");
if ($checkLatestNewsCount && $checkLatestNewsCount->num_rows === 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN latest_news_count INT NOT NULL DEFAULT 5 AFTER favicon_path");
}

// Migration: move berita from menu_id to kategori_id
$checkKategoriCol = $conn->query("SHOW COLUMNS FROM berita LIKE 'kategori_id'");
if ($checkKategoriCol && $checkKategoriCol->num_rows === 0) {
    // Step 1: copy existing menu data into kategori
    $conn->query("INSERT INTO kategori (nama, slug) SELECT DISTINCT nama, slug FROM menus ON DUPLICATE KEY UPDATE nama = VALUES(nama)");

    // Step 2: add kategori_id column
    $conn->query("ALTER TABLE berita ADD COLUMN kategori_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id");

    // Step 3: populate kategori_id from menu slug mapping
    $mapResult = $conn->query("SELECT b.id AS berita_id, k.id AS kategori_id
                               FROM berita b
                               INNER JOIN menus m ON m.id = b.menu_id
                               INNER JOIN kategori k ON k.slug = m.slug");
    if ($mapResult) {
        while ($mapRow = $mapResult->fetch_assoc()) {
            $conn->query("UPDATE berita SET kategori_id = " . (int)$mapRow['kategori_id'] . " WHERE id = " . (int)$mapRow['berita_id']);
        }
    }

    // Step 4: drop old foreign key and menu_id column
    $conn->query("ALTER TABLE berita DROP FOREIGN KEY fk_berita_menu");
    $conn->query("ALTER TABLE berita DROP COLUMN menu_id");
    $conn->query("ALTER TABLE berita ADD CONSTRAINT fk_berita_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE");
}

$checkKategori = $conn->query("SELECT COUNT(*) AS jml FROM kategori");
$needKategori = true;

// Migration: add display settings columns to berita table
$displayColumns = [
    'show_meta' => 'ALTER TABLE berita ADD COLUMN show_meta TINYINT(1) NOT NULL DEFAULT 1 AFTER gambar',
    'show_ringkasan' => 'ALTER TABLE berita ADD COLUMN show_ringkasan TINYINT(1) NOT NULL DEFAULT 1 AFTER show_meta',
    'show_penulis' => 'ALTER TABLE berita ADD COLUMN show_penulis TINYINT(1) NOT NULL DEFAULT 1 AFTER show_ringkasan',
    'show_tanggal' => 'ALTER TABLE berita ADD COLUMN show_tanggal TINYINT(1) NOT NULL DEFAULT 1 AFTER show_penulis',
    'show_kategori' => 'ALTER TABLE berita ADD COLUMN show_kategori TINYINT(1) NOT NULL DEFAULT 1 AFTER show_tanggal',
    'show_gambar_detail' => 'ALTER TABLE berita ADD COLUMN show_gambar_detail TINYINT(1) NOT NULL DEFAULT 1 AFTER show_kategori',
    'layout_style' => "ALTER TABLE berita ADD COLUMN layout_style ENUM('default','wide','boxed') NOT NULL DEFAULT 'default' AFTER show_gambar_detail"
];

foreach ($displayColumns as $colName => $alterSql) {
    $checkCol = $conn->query("SHOW COLUMNS FROM berita LIKE '$colName'");
    if ($checkCol && $checkCol->num_rows === 0) {
        $conn->query($alterSql);
    }
}

// Migration: add display settings columns to kategori table
$kategoriColumns = [
    'grid_count' => 'ALTER TABLE kategori ADD COLUMN grid_count INT NOT NULL DEFAULT 12 AFTER slug',
    'grid_style' => "ALTER TABLE kategori ADD COLUMN grid_style ENUM('grid','list','masonry') NOT NULL DEFAULT 'grid' AFTER grid_count"
];

foreach ($kategoriColumns as $colName => $alterSql) {
    $checkCol = $conn->query("SHOW COLUMNS FROM kategori LIKE '$colName'");
    if ($checkCol && $checkCol->num_rows === 0) {
        $conn->query($alterSql);
    }
}

if ($checkKategori) {
    $row = $checkKategori->fetch_assoc();
    if ((int)$row['jml'] > 0) {
        $needKategori = false;
    }
}

if ($needKategori) {
    $kategoriData = [
        ['Berita Utama', 'berita-utama'],
        ['Teknologi', 'teknologi'],
        ['Olahraga', 'olahraga']
    ];

    $stmt = $conn->prepare("INSERT INTO kategori (nama, slug) VALUES (?, ?)");

    foreach ($kategoriData as $k) {
        $stmt->bind_param('ss', $k[0], $k[1]);
        $stmt->execute();
    }

    $stmt->close();
}

if ($needMenus) {
    $menus = [
        ['Berita Utama', 'berita-utama', 1, 1, 0, 'dropdown'],
        ['Teknologi', 'teknologi', 2, 1, 0, 'mega'],
        ['Olahraga', 'olahraga', 3, 1, 0, 'link']
    ];

    $stmt = $conn->prepare("INSERT INTO menus (nama, slug, urutan, is_active, parent_id, menu_type) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($menus as $m) {
        $stmt->bind_param('ssiiss', $m[0], $m[1], $m[2], $m[3], $m[4], $m[5]);
        $stmt->execute();
    }

    $stmt->close();
}

$checkBerita = $conn->query("SELECT COUNT(*) AS jml FROM berita");
$needBerita = true;

if ($checkBerita) {
    $row = $checkBerita->fetch_assoc();
    if ((int)$row['jml'] > 0) {
        $needBerita = false;
    }
}

if ($needBerita) {
    $kategoriMap = [];
    $resultKategori = $conn->query("SELECT id, slug FROM kategori");
    if ($resultKategori) {
        while ($row = $resultKategori->fetch_assoc()) {
            $kategoriMap[$row['slug']] = (int)$row['id'];
        }
    }

    $now = date('Y-m-d H:i:s');

    $dummy = [
        [
            'slug' => 'berita-utama',
            'judul' => 'Transformasi Digital Membentuk Wajah Baru Media Online',
            'ringkasan' => 'Portal berita modern hadir dengan tampilan responsif dan informasi aktual untuk pembaca.',
            'isi' => "Portal Berita adalah platform informasi yang dirancang untuk menyajikan berita terkini secara cepat dan akurat.\n\nDengan tampilan modern dan fokus pada pengalaman pengguna, portal ini memudahkan pembaca menemukan berita sesuai minat, mulai dari berita utama, teknologi, hingga olahraga.\n\nDashboard admin mempermudah redaksi dalam mengelola kategori dan konten berita sehari-hari.",
            'penulis' => 'Redaksi Portal'
        ],
        [
            'slug' => 'teknologi',
            'judul' => 'Inovasi Teknologi Mempercepat Akses Informasi Masyarakat',
            'ringkasan' => 'Perkembangan teknologi mempercepat distribusi berita melalui berbagai kanal digital.',
            'isi' => "Teknologi telah mengubah cara masyarakat mengonsumsi informasi.\nPortal berita kini dapat diakses dari berbagai perangkat, kapan saja dan di mana saja.\n\nRedaksi dapat memperbarui konten secara real-time melalui dashboard yang terintegrasi.",
            'penulis' => 'Tim Teknologi'
        ],
        [
            'slug' => 'olahraga',
            'judul' => 'Olahraga dan Gaya Hidup Sehat Jadi Tren Masyarakat Urban',
            'ringkasan' => 'Berita olahraga kini tidak hanya soal skor, tetapi juga gaya hidup sehat.',
            'isi' => "Konten olahraga di portal berita dikemas lebih segar dengan sudut pandang gaya hidup.\n\nMulai dari liputan pertandingan, profil atlet, hingga tips latihan harian, semua dirangkum untuk menginspirasi pembaca.",
            'penulis' => 'Redaksi Olahraga'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO berita (kategori_id, judul, slug, ringkasan, isi, penulis, status, tanggal_publikasi) VALUES (?, ?, ?, ?, ?, ?, 'publish', ?)");

    foreach ($dummy as $item) {
        $slug = $item['slug'];
        if (!isset($kategoriMap[$slug])) {
            continue;
        }
        $kategoriId = $kategoriMap[$slug];
        $judul = $item['judul'];
        $slugBerita = strtolower(trim(preg_replace('~[^\pL\d]+~u', '-', $judul), '-'));
        $ringkasan = $item['ringkasan'];
        $isi = $item['isi'];
        $penulis = $item['penulis'];
        $tanggal = $now;

        $stmt->bind_param('issssss', $kategoriId, $judul, $slugBerita, $ringkasan, $isi, $penulis, $tanggal);
        $stmt->execute();
    }

    $stmt->close();
}

$checkSettings = $conn->query("SELECT COUNT(*) AS jml FROM settings");
$needSettings = true;

if ($checkSettings) {
    $row = $checkSettings->fetch_assoc();
    if ((int)$row['jml'] > 0) {
        $needSettings = false;
    }
}

if ($needSettings) {
    $stmt = $conn->prepare("INSERT INTO settings (site_name, site_tagline, logo_path) VALUES (?, ?, ?)");
    $name = 'Portal Berita';
    $tagline = 'Portal berita modern dan informatif';
    $logo = null;
    $stmt->bind_param('sss', $name, $tagline, $logo);
    if (!$stmt->execute()) {
        die('Gagal membuat pengaturan awal: ' . $stmt->error . PHP_EOL);
    }
    $stmt->close();
}

echo 'Database, tabel, dan data contoh portal_berita sudah siap.' . PHP_EOL;
echo PHP_EOL;
echo 'PERINGATAN KEAMANAN: Hapus file setup_db.php setelah selesai!' . PHP_EOL;
echo 'Jangan biarkan file ini di server production.' . PHP_EOL;
