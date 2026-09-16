# Testing Checklist - Sparepart Barcode Manager
Gunakan dokumen ini untuk memastikan semua fitur berjalan dengan baik setelah deployment ke cPanel.

## Pre-Deployment (Local Testing)
- [x] Syntax check semua PHP files — **PASS**
- [x] CODE128 barcode encoding — **PASS**
- [x] Generate 20 label identik — **PASS**
- [x] XLSX tulis & baca — **PASS**
- [x] Validasi kode custom — **PASS**
- [x] CSRF token generation — **PASS**
- [x] URL routing & helpers — **PASS**
- [x] Format currency — **PASS**

---

## Post-Deployment Testing (Di cPanel)

### TEST 1: Database Connection & Login
**Prosedur:**
1. Akses `https://sparepart.tokosayaraya.com/`
2. Harus redirect ke login page
3. Masukkan username: `admin`, password: `admin123`
4. Klik Login

**Expected Result:**
- ✓ Halaman login muncul
- ✓ Tidak ada error 500/database connection error
- ✓ Login berhasil, redirect ke halaman Katalog
- ✓ Navbar terlihat dengan nama user "Administrator"

**Checkpoint:**
```
[ ] Login berhasil tanpa error
[ ] Navbar dan menu tampil
```

---

### TEST 2: Tambah Sparepart (1 item, tanpa foto)
**Prosedur:**
1. Klik menu "Tambah Sparepart"
2. Isi form:
   - Kode Custom: `JL-BEAT-KR-001`
   - Nama: `Kampas Rem Depan Beat`
   - Merk: `JASINDO`
   - Jenis Motor: `Honda Beat`
   - Kategori: `Kampas Rem`
   - Harga Beli: `25000`
   - Harga Jual: `35000`
   - Stok: `20`
   - Lokasi Rak: `A-01`
3. Klik Simpan

**Expected Result:**
- ✓ Flash message "berhasil ditambahkan"
- ✓ Redirect ke halaman detail sparepart
- ✓ Data yang diisi tampil kembali dengan foto placeholder
- ✓ Record dibuat 1 baris saja (bukan 20 baris)

**Checkpoint:**
```
[ ] Sparepart berhasil disimpan
[ ] Detail halaman tampil correct
[ ] Stok = 20 (bukan 20 record terpisah)
```

---

### TEST 3: Kode Custom Duplikat Ditolak
**Prosedur:**
1. Klik "Tambah Sparepart" lagi
2. Isi dengan kode yang sama: `JL-BEAT-KR-001`
3. Klik Simpan

**Expected Result:**
- ✓ Error message: "Kode Custom "JL-BEAT-KR-001" sudah digunakan..."
- ✓ Halaman tidak refresh, form tetap ada
- ✓ Data tidak disimpan (tidak ada record duplikat)

**Checkpoint:**
```
[ ] Duplikat ditolak dengan pesan jelas
[ ] Stok tetap 20 (tidak bertambah)
```

---

### TEST 4: Generate Barcode 1 Label
**Prosedur:**
1. Dari Katalog, cari "Kampas Rem Beat"
2. Klik kartu → ke halaman detail
3. Klik tombol "Cetak 1"

**Expected Result:**
- ✓ Redirect ke halaman preview barcode
- ✓ 1 label dengan barcode CODE128 terlihat
- ✓ Kode `JL-BEAT-KR-001` terbaca di bawah barcode
- ✓ Tombol "PRINT BARCODE" tersedia

**Checkpoint:**
```
[ ] Barcode preview muncul
[ ] Kode tercetak di bawah barcode
[ ] Print button ready
```

---

### TEST 5: Generate & Print 20 Label Identik
**Prosedur:**
1. Dari halaman detail sparepart, klik "Cetak 20"
2. Preview: harus ada 20 label dengan barcode yang SAMA
3. Klik "PRINT BARCODE"
4. Print dialog muncul → print ke printer Anda (atau print ke PDF)

**Expected Result:**
- ✓ 20 label muncul di preview (bukan 20 halaman terpisah — layout optimal per A4)
- ✓ Semua barcode identik (kode `JL-BEAT-KR-001` sama di semua label)
- ✓ Print dialog PDF menunjukkan N halaman sesuai ukuran label
- ✓ Riwayat cetak bertambah 1 entry (jumlah=20, bukan 20 entry)

**Checkpoint:**
```
[ ] 20 label preview tampil
[ ] Semua barcode identik
[ ] Print dialog muncul
[ ] Riwayat = 1 entry, jumlah=20
```

---

### TEST 6: Scan Barcode via Scanner USB
**Prosedur (butuh scanner + label cetak):**
1. Print label dari test 5 (minimal 1 label)
2. Tempel label ke barang (atau kertas untuk testing)
3. Buka halaman Scan: `https://sparepart.../modules/scan/scan.php`
4. Arahkan scanner ke label → barcode otomatis terbaca (atau ketik manual `JL-BEAT-KR-001` lalu Enter)

**Expected Result:**
- ✓ Kode terbaca di input field
- ✓ Sistem mencari di database
- ✓ Kartu sparepart muncul dengan:
  - Foto (placeholder atau foto upload)
  - Kode: JL-BEAT-KR-001
  - Nama: Kampas Rem Depan Beat
  - Harga: Rp35.000
  - Stok: 20
  - Lokasi: A-01
- ✓ Tidak ada delay > 2 detik (response cepat)

**Checkpoint:**
```
[ ] Scan berhasil terbaca
[ ] Data sparepart muncul correct
[ ] Response time < 2 detik
```

---

### TEST 7: Kode Tidak Ketemu → Tawarkan Tambah
**Prosedur:**
1. Di halaman Scan, ketik kode yang tidak ada: `TEST-NOTFOUND-999`
2. Press Enter

**Expected Result:**
- ✓ Warning message: "Barcode belum terdaftar: TEST-NOTFOUND-999"
- ✓ Link "Tambahkan sebagai Sparepart Baru" muncul (jika user adalah admin/staff)
- ✓ Klik link → form sudah pre-filled dengan kode `TEST-NOTFOUND-999`

**Checkpoint:**
```
[ ] Not found message clear
[ ] Prefill kode di form tambah
```

---

### TEST 8: Import Excel (Valid + Duplikat)
**Prosedur:**
1. Download template Excel dari menu Import/Export
2. Isi data di Excel:
   ```
   Kode Custom       | Nama Sparepart      | Harga Beli | Harga Jual | Stok
   JL-BEAT-KR-001    | Kampas Rem Beat     | 25000      | 35000      | 20      (DUPLIKAT - sudah ada)
   JL-VARIO-KR-002   | Kampas Rem Vario    | 27000      | 38000      | 15      (BARU)
   JL-SCOOPY-OLI-003 | Oli Scoopy 0.8L     | 30000      | 42000      | 5       (BARU)
   ```
3. Save as .xlsx
4. Upload ke halaman Import
5. Pilih strategi: **"Lewati duplikat"**
6. Klik Import

**Expected Result:**
- ✓ Import berhasil: 2 baru, 1 duplikat lewati
- ✓ Ringkasan: "2 berhasil, 1 gagal"
- ✓ Tabel error menunjukkan baris mana yang duplikat
- ✓ Katalog sekarang punya 3 sparepart total:
  - JL-BEAT-KR-001 (stok tetap 20, bukan bertambah)
  - JL-VARIO-KR-002 (baru, stok 15)
  - JL-SCOOPY-OLI-003 (baru, stok 5)

**Checkpoint:**
```
[ ] Duplikat handling correct ("Lewati")
[ ] 2 baru berhasil diimport
[ ] Stok sparepart lama tidak berubah
[ ] Baris error ditampilkan
```

---

### TEST 9: Import Excel (Update Strategy)
**Prosedur (lanjutan dari TEST 8):**
1. Edit file Excel dari TEST 8:
   - Ubah `JL-BEAT-KR-001` stok dari 20 → 50
   - Harga jual: 35000 → 40000
2. Upload ulang
3. Pilih strategi: **"Update data"**
4. Klik Import

**Expected Result:**
- ✓ Import: 2 baru, 1 update (tidak ada gagal)
- ✓ JL-BEAT-KR-001 sekarang punya stok=50, harga_jual=40000
- ✓ Catalog menampilkan data yang sudah di-update

**Checkpoint:**
```
[ ] Update strategy work
[ ] Data lama overwrite dengan data baru
[ ] Stok berubah dari 20 ke 50
```

---

### TEST 10: Import Excel (Cancel Strategy)
**Prosedur (lanjutan):**
1. Edit file Excel lagi:
   - Ubah `JL-BEAT-KR-001` stok ke 99
   - Tambah error: salah satu baris "Stok" diisi "ABC" (bukan angka)
2. Upload
3. Pilih strategi: **"Batalkan jika duplikat"**
4. Klik Import

**Expected Result:**
- ✓ Sistem deteksi duplikat + error di baris ke-N
- ✓ Flash message: "Import dibatalkan: Kode ... sudah ada / Stok bukan angka"
- ✓ TIDAK ada data yang disimpan (atomic transaction)
- ✓ Stok JL-BEAT-KR-001 tetap 50 (dari test sebelumnya)

**Checkpoint:**
```
[ ] Validation error detected
[ ] Import aborted, no data saved
[ ] Previous data unchanged
```

---

### TEST 11: Export Excel
**Prosedur:**
1. Dari menu Import/Export, klik "EXPORT EXCEL"
2. File `export_sparepart_YYYY-MM-DD_HHMMSS.xlsx` terunduh

**Expected Result:**
- ✓ File .xlsx valid (bisa dibuka di Excel / LibreOffice)
- ✓ Berisi 4 baris: 1 header + 3 data sparepart
- ✓ Data sesuai dengan apa yang ada di katalog
- ✓ Kolom: Kode Custom, Nama, Merk, Jenis Motor, Kategori, Satuan, Harga Beli, Harga Jual, Stok, Lokasi Rak, Keterangan

**Checkpoint:**
```
[ ] File export berhasil terunduh
[ ] File valid di Excel/LibreOffice
[ ] Data akurat sesuai katalog
```

---

### TEST 12: Performa dengan Data Banyak
**Prosedur:**
1. Buat file Excel dengan 500 baris sparepart (bisa duplikasi dengan kode beda)
2. Import dengan strategi "Insert" (buat data baru)
3. Tunggu proses selesai (catat waktu)
4. Buka Katalog, cek pagination & search

**Expected Result:**
- ✓ Import 500 data selesai dalam < 10 detik
- ✓ Katalog masih responsif (page load < 2 detik)
- ✓ Search dengan keyword muncul hasil dalam < 1 detik
- ✓ Filter (merk, kategori, stok) bekerja normal
- ✓ Pagination menampilkan 24 kartu per halaman
- ✓ Detail sparepart load cepat

**Checkpoint:**
```
[ ] Import 500 data selesai < 10 detik
[ ] Katalog load < 2 detik (24 kartu)
[ ] Search responsif < 1 detik
[ ] Filter work normal
```

---

## Post-Testing Sign-Off

| Test | Status | Tester | Tanggal | Catatan |
|------|--------|--------|---------|---------|
| TEST 1: Login | [ ] PASS | | | |
| TEST 2: Tambah 1 Sparepart | [ ] PASS | | | |
| TEST 3: Duplikat Ditolak | [ ] PASS | | | |
| TEST 4: Cetak 1 Barcode | [ ] PASS | | | |
| TEST 5: Cetak 20 Label | [ ] PASS | | | |
| TEST 6: Scan Barcode | [ ] PASS | | | |
| TEST 7: Scan Not Found | [ ] PASS | | | |
| TEST 8: Import (Skip) | [ ] PASS | | | |
| TEST 9: Import (Update) | [ ] PASS | | | |
| TEST 10: Import (Cancel) | [ ] PASS | | | |
| TEST 11: Export Excel | [ ] PASS | | | |
| TEST 12: Performa 500 Data | [ ] PASS | | | |

**Overall Status:** [ ] READY FOR PRODUCTION

**Signed by:**
- Tester: __________________ Date: __________
- Approver: ________________ Date: __________

---

## Known Limitations & Future Improvements

### Current Limitations:
- Hanya 1 admin default (password hardcoded di database.sql)
- Tidak ada UI untuk change password atau manage user
- Scanner USB hanya support HID keyboard mode (tidak ada driver setup)
- Print label hanya CSS @media print (tidak ada backend PDF generation)
- Import Excel hanya support .xlsx & .csv (tidak support .xls lama/biner)

### Planned for v1.1:
- Change password UI
- Multi-user management & permission granular
- Print to PDF backend (via mPDF atau wkhtmltopdf)
- Barcode types: EAN-13, QR Code, Code39
- Dashboard analytics (top spareparts, trend)
- Integrasi SIMBENG (kasir/inventory/transaksi)

---

Dokumen terakhir diupdate: **September 2026**
