# Aplikasi Sparepart Barcode Manager
Sistem manajemen sparepart lokal/aftermarket dengan generator barcode CODE128, import/export Excel, dan scan barcode via scanner USB.

## Prasyarat
- **cPanel** dengan akses FTP/SSH
- **PHP 8.0 - 8.2** (sudah standard di hosting cPanel modern)
- **MySQL/MariaDB 5.7+** (sudah ada di cPanel)
- **Ekstensi PHP**: `pdo_mysql`, `gd`, `zip`, `xml` (semua sudah aktif default di cPanel)
- **Scanner USB** model HID keyboard (optional, untuk fitur scan barcode)

## Instalasi Cepat (10 langkah)

### 1. Buat Subdomain di cPanel
- Buka **cPanel → Addon Domains** (atau **Subdomains**)
- Buat subdomain baru, misal: `sparepart.tokosayaraya.com`
- Document Root biasanya: `/home/cpaneluser/public_html/sparepart/`

### 2. Buat Database MySQL
- Buka **cPanel → MySQL Databases**
- Nama database: `cpaneluser_sparepart_barcode` (sesuai naming convention hosting Anda)
- Username baru: `cpaneluser_sp_user`
- Password: buat password kuat, catat
- Berikan ALL PRIVILEGES ke user tersebut

### 3. Upload Source Code
- Download project ini (atau clone dari Git)
- Via FTP, upload seluruh isi folder ke document root subdomain (`/home/cpaneluser/public_html/sparepart/`)
- Struktur folder lokal di server harus:
  ```
  /home/cpaneluser/public_html/sparepart/
  ├── index.php
  ├── database.sql
  ├── config/
  ├── includes/
  ├── modules/
  ├── views/
  ├── assets/
  ├── uploads/
  └── vendor/ (jika ada)
  ```

### 4. Import Database
- Di **cPanel → MySQL Databases** → **phpMyAdmin**
- Pilih database `cpaneluser_sparepart_barcode`
- Tab **Import**, upload file `database.sql` dari project
- Klik Import → tunggu selesai

### 5. Konfigurasi Database
- Edit file `config/database.php`
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'cpaneluser_sparepart_barcode');
  define('DB_USER', 'cpaneluser_sp_user');
  define('DB_PASS', 'password_yang_Anda_buat_di_step_2');
  ```
- Simpan file

### 6. Atur Permission Folder
- Via SSH/Terminal di cPanel:
  ```bash
  cd /home/cpaneluser/public_html/sparepart/
  chmod 755 .
  chmod 755 config/
  chmod 755 includes/
  chmod 755 modules/
  chmod 755 assets/
  chmod 777 uploads/
  chmod -R 777 uploads/*
  ```
- Atau via FTP: set `uploads/` ke mode 777 (read/write/execute)

### 7. Verifikasi PHP Version
- Di **cPanel → Select PHP Version**
- Pastikan PHP 8.0 atau lebih tinggi, dan aktifkan ekstensi:
  - ✅ pdo_mysql
  - ✅ gd
  - ✅ zip
  - ✅ xml
  - (biasanya sudah aktif otomatis)

### 8. Test Login
- Akses di browser: `https://sparepart.tokosayaraya.com/`
- Akan redirect ke halaman login
- **Username**: `admin`
- **Password**: `admin123`
- **WAJIB UBAH PASSWORD ADMIN SETELAH LOGIN PERTAMA** (belum ada fitur change password di v1, edit langsung di phpMyAdmin atau hubungi developer)

### 9. Test Input Sparepart
- Login dengan admin
- Klik **"Tambah Sparepart"**
- Isi form:
  - Kode Custom: `TEST-001` (unik)
  - Nama Sparepart: `Test Sparepart`
  - Harga Beli: `10000`
  - Harga Jual: `15000`
  - Stok: `5`
  - Klik **Simpan**

### 10. Test Generate Barcode & Scanner
- Klik sparepart yang baru dibuat
- Klik **"Cetak 1"** → preview halaman dengan barcode
- Klik **"PRINT BARCODE"** → print ke printer lokal Anda
- Tunggu label keluar, tempel di sparepart
- Di halaman **"Scan Barcode"**, arahkan scanner USB ke label
- Kode `TEST-001` akan terbaca dan data sparepart muncul di layar

---

## Penggunaan

### Halaman Utama: Katalog Marketplace
- **URL**: `https://sparepart.tokosayaraya.com/modules/sparepart/list.php`
- Grid kartu 4-6 kartu per baris (responsive)
- Search: kode / nama sparepart
- Filter: Merk, Jenis Motor, Kategori, Status Stok (Tersedia/Menipis/Habis)
- Pagination server-side → aman untuk 20.000+ data

### Tambah/Edit Sparepart
- Klik tombol **"Tambah Sparepart"** atau ikon **Edit** di detail sparepart
- Form validation:
  - Kode Custom: huruf/angka/underscore/dash, 2-50 karakter, WAJIB UNIK
  - Jika kode sudah ada → error, tidak bisa simpan
  - Upload foto (JPG/PNG/WEBP, max 3MB)

### Generator Barcode
- Pilih sparepart di katalog → klik **"Cetak Barcode"**
- Atau langsung dari detail sparepart, pilih jumlah (1/5/10/20/50/custom)
- Ukuran label: 30x20mm / 40x25mm / 50x30mm
- Preview: lihat layout label sebelum print
- **"PRINT BARCODE"**: buka dialog printer, print ke printer label Anda
- Riwayat cetak otomatis tercatat (tidak menambah record sparepart, hanya 1 baris inventory tetap sama stoknya)

### Cetak Massal (Bulk Print)
- **URL**: `https://sparepart.tokosayaraya.com/modules/barcode/bulk_print.php`
- Centang beberapa sparepart
- Tentukan jumlah cetak masing-masing
- Klik **"CETAK SEMUA BARCODE"** → print dalam sekali proses

### Scan Barcode
- **URL**: `https://sparepart.tokosayaraya.com/modules/scan/scan.php`
- Input field auto-focus, siap terima scanner USB
- Scanner membaca barcode → kode otomatis dikirim form
- Sistem mencari kode di database
- Jika ketemu: tampilkan kartu sparepart (foto, harga, stok, lokasi rak)
- Jika tidak ketemu: tawarkan link "Tambah Sparepart Baru"

### Import Excel
- **URL**: `https://sparepart.tokosayaraya.com/modules/excel/import.php`
- Download template Excel
- Isi data sesuai kolom
- Upload file (.xlsx atau .csv)
- Pilih strategi duplikat:
  - **Lewati**: baris dengan kode yang sudah ada → dilewati (data lama tetap)
  - **Update**: baris dengan kode yang ada → update data (timpa)
  - **Batalkan**: jika ada 1 duplikat, batalkan seluruh import (aman untuk bulk pertama kali)
- Sistem validasi per baris:
  - Kode wajib unik & format valid
  - Harga/stok harus numerik
  - Jika error → tampilkan daftar baris mana yang error + alasannya
  - Hanya baris valid yang di-commit (atomic transaction)

### Export Excel
- **URL**: `https://sparepart.tokosayaraya.com/modules/excel/export.php`
- Download Excel berisi semua sparepart aktif
- Format: Kode Custom | Nama | Merk | Jenis Motor | Kategori | Satuan | Harga Beli | Harga Jual | Stok | Lokasi Rak | Keterangan
- Bisa juga di-export sesuai filter pencarian terakhir (jika masih ada parameter di URL)

### Riwayat Cetak
- **URL**: `https://sparepart.tokosayaraya.com/modules/barcode/history.php`
- Tabel riwayat cetak barcode (tanggal, kode, nama, jumlah, user)
- Tombol "Cetak Ulang" → langsung print lagi dengan jumlah yang sama

---

## Performa & Keamanan

### Database Optimization
- Index pada `kode_custom` (UNIQUE) → O(1) lookup saat scan
- Index pada `nama_sparepart` → FULLTEXT search cepat
- Prepared statements di semua query → proteksi SQL injection
- Pagination server-side → hanya load N baris per halaman (default 24)

### Keamanan Input
- CSRF protection: setiap form wajib ada hidden `csrf_token`
- Output escaping: `e()` helper mencegah XSS
- Upload file:
  - Cek MIME type (JPG/PNG/WEBP)
  - Rename file random (tidak pakai nama asli user)
  - Simpan di folder terisolasi `uploads/`
- Password: bcrypt hashing (PHP `password_hash()`)

### Kompatibilitas cPanel
- **Tanpa NodeJS/PM2/Docker** → murni PHP + MySQL
- **Tanpa Composer di server** → semua library bundled atau native PHP
- **No symlink** → semua file standard copying
- **Session berbasis file** → PHP bawaan cukup (tidak perlu Redis)

---

## Troubleshooting

### Halaman blank / Error 500
- Cek `config/database.php` — DB_NAME, DB_USER, DB_PASS sudah benar?
- Cek file `error_log` di document root / SSH: `tail -f error_log`
- Cek PHP version: `php -v` di SSH

### Login gagal
- Pastikan database sudah di-import (jalankan `database.sql`)
- Default user: `admin` / `admin123`
- Di phpMyAdmin, cek tabel `users`, pastikan ada 1 row dengan username `admin`

### Scanner USB tidak terbaca
- Pastikan scanner dalam mode "HID Keyboard" (bukan mode custom/serial)
- Scanner perlu emulate Enter key setelah membaca barcode
- Di halaman Scan, klik input field untuk auto-focus, barnya siap terima

### Upload foto gagal
- Cek folder `uploads/` permission: harus 777 (read/write/execute)
- Cek file size: max 3MB
- Cek format: harus JPG/PNG/WEBP (ekstensi .gif tidak diterima)

### Cetak barcode tidak jelas
- Pastikan printer support printing tingkat detail 300 DPI ke atas
- Label size terlalu kecil? gunakan 50x30mm, bukan 30x20mm
- Browser zoom level: coba set ke 100% sebelum print

---

## Fitur di Versi Ini (v1.0)

✅ CRUD sparepart (katalog marketplace grid)
✅ Generator barcode CODE128 (SVG vektor, print-ready)
✅ Cetak massal barcode (bulk print)
✅ Scan barcode via USB scanner
✅ Import/Export Excel (.xlsx, .csv)
✅ Search & filter server-side (aman untuk 20rb+ data)
✅ Riwayat cetak barcode
✅ Simple authentication (admin/staff roles)
✅ Responsive design (mobile/tablet/desktop)

Fitur yang bisa ditambah di versi mendatang:
- Integrasi dengan modul SIMBENG (kasir/POS/inventory)
- Perubahan harga historis (track harga beli/jual per tanggal)
- Multi-user permission granular
- Dashboard analytics (trend penjualan, sparepart terpopuler)
- Barcode tipe lain (QR code, EAN-13, Code39)
- Foto zoom modal / gallery
- Print receipt after scan

---

## FAQ

**Q: Berapa banyak sparepart yang bisa dikelola?**
A: Database bisa menampung puluhan ribu record. Sistem sudah optimized dengan pagination & server-side search. Jangan khawatir dengan 20.000 sparepart.

**Q: Stok fisik di gudang ada 20, apakah buat 20 record di database?**
A: TIDAK. Cukup 1 record sparepart dengan stok=20. Cetak barcode yang sama 20 kali label → tempel ke 20 unit fisik. 1 barcode = 1 identitas sparepart.

**Q: Kode custom bisa diubah setelah disimpan?**
A: Ya, bisa diedit di halaman form. Tapi sistem akan cross-check apakah kode baru sudah ada (duplikat). Perubahan kode akan update riwayat cetak secara otomatis.

**Q: Format barcode apa saja yang didukung?**
A: Versi ini hanya CODE128 Set B. CODE128 adalah standar industri, kompatibel dengan semua scanner USB standar. QR code bisa ditambahkan di versi mendatang.

**Q: Apakah bisa terintegrasi dengan SIMBENG?**
A: Iya. Modul ini didesain standalone terlebih dahulu. Untuk integrasi SIMBENG:
  1. Analisa struktur tabel sparepart SIMBENG
  2. Reuse tabel existing, jangan buat duplikat
  3. Tambah kolom `kode_custom` ke tabel sparepart SIMBENG
  4. Adjust relasi foreign key jika perlu
  5. Modifikasi `includes/sparepart_repo.php` sesuai struktur SIMBENG
  Hubungi developer untuk bantuan integrasi lebih detail.

**Q: Lisensi software apa?**
A: Proprietary (khusus untuk toko Anda). Tidak untuk dijual ulang atau disebar.

---

## Support
Jika ada error atau pertanyaan, cek:
1. `error_log` di document root (via FTP / SSH)
2. phpMyAdmin → pastikan database & user sudah benar
3. File `config/database.php` → sesuaikan dengan data hosting Anda
4. Baca section "Troubleshooting" di atas

Untuk update atau custom request, hubungi developer yang membuat aplikasi ini.

---

**Terakhir diperbarui**: September 2026
**Versi**: 1.0
**Status**: Production Ready (siap deployment)
