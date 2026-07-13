# Panduan Membuat Menu Dropdown yang Benar

## Struktur Hierarki Menu

### Aturan Utama:
1. **Menu Induk (Parent)** - Harus `parent_id = 0` (top-level)
2. **Menu Anak (Child)** - Harus `parent_id = ID dari menu induk`

---

## Contoh: Membuat Dropdown "Sosial" dengan Anak "Politik"

### ❌ SALAH (Yang Terjadi Sebelumnya):
```
Sosial:
  - menu_type: link
  - parent_id: 4 (salah! menunjuk ke diri sendiri)

Politik:
  - menu_type: dropdown  
  - parent_id: 4 (benar, tapi induknya salah)
```
**Hasil**: Sosial tidak muncul di public!

---

### ✅ BENAR:
```
Sosial (INDUK):
  - id: 4
  - menu_type: dropdown  ← Tipe dropdown untuk menampilkan submenu
  - parent_id: 0         ← Harus 0 (top-level menu)
  - kategori_id: 8       ← Link ke kategori (opsional)

Politik (ANAK):
  - id: 8
  - menu_type: link      ← Anak menu biasanya tipe link
  - parent_id: 4         ← Harus ID dari Sosial (4)
  - kategori_id: 8       ← Link ke kategori Politik
```

**Hasil**: 
- "Sosial" muncul di navbar
- Saat hover, muncul dropdown dengan "Politik" di dalamnya

---

## Cara Membuat di Admin Panel

### Langkah 1: Buat Menu Induk (Sosial)
1. Klik "Tambah Menu"
2. Nama: **Sosial**
3. Tipe Menu: **Dropdown** ← PENTING!
4. Menu Induk: **-- Tidak Ada (Menu Utama) --** ← HARUS INI!
5. Kategori: Pilih kategori Sosial
6. Simpan

### Langkah 2: Buat Menu Anak (Politik)
1. Klik "Tambah Menu"
2. Nama: **Politik**
3. Tipe Menu: **Link Biasa**
4. Menu Induk: **Sosial** ← Pilih dari dropdown!
5. Kategori: Pilih kategori Politik
6. Simpan

---

## Struktur Database yang Benar

| id | nama    | menu_type | parent_id | kategori_id | Keterangan          |
|----|---------|-----------|-----------|-------------|---------------------|
| 4  | Sosial  | dropdown  | 0         | 8           | ✓ Top-level menu    |
| 8  | Politik | link      | 4         | 8           | ✓ Child of Sosial   |

---

## Jenis-Jenis Menu

### 1. Link Biasa
- Langsung membuka halaman kategori
- Contoh: Budaya, Kesehatan, Wisata

```
[Link] Budaya → klik → buka kategori.php?kategori=9
```

### 2. Dropdown
- Menampilkan submenu saat hover
- Harus punya anak menu (parent_id != 0)
- Contoh: Sosial → Politik

```
[Dropdown] Sosial
  └─ Politik (muncul saat hover)
```

### 3. Mega Menu
- Panel lebar dengan berita + thumbnail
- Bisa dengan atau tanpa submenu
- Contoh: Teknologi, Olahraga

```
[Mega] Teknologi
  ┌─────────────────────────────────┐
  │ [Gambar] Berita Teknologi 1     │
  │ [Gambar] Berita Teknologi 2     │
  │ [Gambar] Berita Teknologi 3     │
  └─────────────────────────────────┘
```

---

## Troubleshooting

### ❌ Masalah: Menu tidak muncul di public
**Penyebab**: 
- `parent_id` tidak 0 untuk menu top-level
- Menu menunjuk ke diri sendiri (circular reference)

**Solusi**:
```sql
UPDATE menus SET parent_id = 0 WHERE nama = 'NamaMenu';
```

---

### ❌ Masalah: Dropdown kosong/tidak ada submenu
**Penyebab**:
- Tidak ada menu anak dengan `parent_id` = ID menu induk

**Solusi**:
1. Buat menu baru
2. Pilih menu induk yang benar
3. Atau update menu existing:
```sql
UPDATE menus SET parent_id = 4 WHERE nama = 'Politik';
```

---

### ❌ Masalah: Dropdown muncul tapi tidak bisa di-hover
**Penyebab**:
- Ada gap antara menu dan dropdown panel
- Cursor kehilangan hover state

**Solusi**: Sudah diperbaiki di code - margin-top: 0px

---

## Checklist Membuat Dropdown

- [ ] Menu induk: `menu_type = dropdown`
- [ ] Menu induk: `parent_id = 0` (top-level)
- [ ] Menu anak: `parent_id = ID_menu_induk`
- [ ] Menu anak: `menu_type = link` (biasanya)
- [ ] Semua menu: `is_active = 1`
- [ ] Semua menu: punya `kategori_id` yang valid

---

## Contoh Lengkap: Dropdown dengan Multiple Children

```
Olahraga (dropdown, parent_id: 0)
  ├─ Sepakbola (link, parent_id: 3)
  ├─ Basket (link, parent_id: 3)
  └─ Tenis (link, parent_id: 3)
```

**Database**:
| id | nama      | menu_type | parent_id |
|----|-----------|-----------|-----------|
| 3  | Olahraga  | dropdown  | 0         |
| 9  | Sepakbola | link      | 3         |
| 10 | Basket    | link      | 3         |
| 11 | Tenis     | link      | 3         |

---

## Kesimpulan

**Rumus Dropdown yang Benar**:
```
INDUK:  menu_type=dropdown + parent_id=0
ANAK:   menu_type=link + parent_id=ID_INDUK
```

Selalu pastikan:
1. Induk menu adalah TOP-LEVEL (parent_id = 0)
2. Anak menu menunjuk ke INDUK (parent_id = ID induk)
3. Tidak ada circular reference (menu menunjuk ke diri sendiri)
