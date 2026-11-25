# Catatan Update Fitur — Website UPTD BJKPMK NTB

**Tanggal update:** 20 September 2026 (revisi: tambah fitur Kontak WhatsApp otomatis)
**Versi sebelumnya:** Halaman statis (`index.html` saja) — semua data (berita, pengumuman, unduhan, tombol "Ajukan Layanan") masih berupa contoh/dummy dan tidak berfungsi (link `#`).

---

## Ringkasan

Website dikembangkan dari **halaman statis (tampilan saja)** menjadi **website dinamis dengan backend PHP + penyimpanan JSON** (tanpa perlu database, cocok untuk server Armbian/HG680P). Sekarang admin dapat mengelola seluruh konten melalui **panel admin**, dan masyarakat dapat **mengajukan layanan serta mengecek status permohonan secara online**.

---

## Fitur Baru

### 1. Panel Admin (`admin.html`)
- Login admin dengan password (default: `admin123` — **wajib diganti** setelah deploy pertama, lewat menu *Ganti Password*).
- Dashboard ringkasan jumlah berita, pengumuman, dokumen, dan permohonan masuk.
- Sepenuhnya berbasis AJAX (tanpa reload halaman), responsif untuk mobile.

### 2. Kelola Berita (dinamis)
- Tambah / edit / hapus berita langsung dari panel admin, termasuk unggah gambar cover.
- Tandai satu berita sebagai "berita utama" (featured) yang tampil besar di beranda.
- Halaman publik (`index.html`) otomatis menampilkan berita terbaru dari server — tidak lagi hardcode di HTML.

### 3. Kelola Pengumuman (dinamis)
- Tambah / edit / hapus pengumuman dengan kategori **Penting / Jadwal / Info**.
- Tampil otomatis di beranda, terurut dari yang terbaru.

### 4. Kelola Dokumen Unduhan (dinamis + fungsional)
- Sebelumnya semua tautan unduhan adalah dummy (`href="#"`) dengan ukuran berkas fiktif.
- Sekarang admin benar-benar **mengunggah berkas asli** (PDF/DOC/DOCX/XLS/XLSX/ZIP, maks 8MB) lewat panel admin.
- Pengunjung mengunduh berkas asli lewat `api.php?action=download`, bukan tautan palsu.
- Bila belum ada dokumen, beranda menampilkan pesan yang jujur ("Belum ada dokumen") — bukan data fiktif.

### 5. Formulir "Ajukan Layanan" (baru, fungsional)
- Sebelumnya tombol "Ajukan Layanan" dan seluruh kartu layanan hanya tautan `#` tanpa aksi.
- Sekarang setiap kartu layanan dan tombol "Ajukan Layanan" membuka **formulir pop-up** (nama, instansi, no. HP, email, jenis layanan otomatis terisi, catatan, lampiran berkas opsional).
- Setelah dikirim, sistem menghasilkan **kode tiket unik** (contoh: `TIK-2609-AB12C`) yang wajib disimpan pemohon.
- Data permohonan tersimpan di server dan dapat dikelola admin di menu **Permohonan Masuk**.

### 6. Cek Status Permohonan (baru, fungsional)
- Tautan "Cek Status Permohonan" di footer sebelumnya tidak berfungsi.
- Sekarang membuka formulir pop-up: masukkan kode tiket + no. HP/email → sistem menampilkan status (**Diajukan / Diproses / Selesai / Ditolak**) beserta catatan dari petugas.

### 7. Kelola Permohonan Masuk (admin)
- Admin melihat seluruh permohonan yang masuk, memfilter berdasarkan status, melihat detail lengkap (termasuk unduh lampiran pemohon), dan mengubah status + menambahkan catatan petugas yang akan terlihat oleh pemohon saat cek status.

### 8. Statistik Beranda Dapat Diedit
- Angka "Tenaga Kerja Tersertifikasi", "Sampel Diuji", "Pelatihan Diselenggarakan", "Kabupaten/Kota Terlayani", "Laboratorium Terakreditasi", dan "SLA Hari Kerja" sebelumnya di-hardcode di HTML.
- Sekarang dapat diubah kapan saja lewat menu **Statistik Beranda** di panel admin, tanpa perlu edit kode.

### 9. Keamanan
- Folder `storage/` (data JSON + berkas upload) diblokir dari akses langsung lewat browser (`deny all` di Nginx) — semua akses berkas harus lewat `api.php` yang tervalidasi.
- Validasi tipe dan ukuran berkas (maks 8MB) pada setiap unggahan.
- Password admin disimpan dalam bentuk hash (`password_hash`), bukan teks biasa.
- Sesi admin menggunakan PHP session standar.

### 10. Ketahanan (Graceful Fallback)
- Jika `api.php` belum aktif/di-deploy (misalnya saat pratinjau file HTML secara lokal), halaman beranda tetap menampilkan **konten contoh** yang sudah ada sebelumnya, sehingga tampilan tidak rusak — begitu backend aktif, konten otomatis berpindah ke data asli dari server.

### 11. Panel Kontak WhatsApp Otomatis (baru)
- Setelah pemohon menekan **Kirim Permohonan** dan permohonan berhasil tersimpan, website otomatis membuka chat **WhatsApp instansi** di tab baru.
- Pesan sudah terisi otomatis: kode tiket, jenis layanan, nama, instansi, no. HP, dan catatan pemohon — pemohon tinggal menekan *Kirim* di WhatsApp.
- Di jendela hasil ditampilkan **Panel Kontak WhatsApp** (nama kontak, nomor, dan tombol *Buka WhatsApp*) sebagai cadangan bila browser memblokir tab baru.
- Nomor WhatsApp, nama kontak, dan status aktif/nonaktif diatur admin di menu **Kontak WhatsApp** (nomor boleh ditulis `0812...`, `+62812...`, atau `62812...`).
- Nomor WhatsApp juga tampil otomatis di bagian *Alamat & Kontak* pada footer beranda.
- Bila fitur nonaktif atau nomor belum diisi, formulir bekerja seperti sebelumnya (hanya menampilkan kode tiket).

---

## Struktur Berkas Baru

```
index.html                  ← halaman publik (kini dinamis, memanggil api.php)
admin.html                  ← panel admin (baru)
api.php                     ← backend/API (baru)
nginx-uptd.conf             ← ditambahkan aturan blokir akses folder storage/
PANDUAN-DEPLOY.md            ← diperbarui: langkah setup PHP, permission storage, login admin
CATATAN-UPDATE-FITUR.md      ← berkas ini
storage/
  config.json               ← dibuat otomatis saat api.php pertama kali dipanggil (hash password)
  stats.json                ← statistik beranda
  berita.json               ← data berita (terisi contoh awal)
  pengumuman.json           ← data pengumuman (terisi contoh awal)
  unduhan.json              ← data dokumen unduhan (kosong — admin perlu unggah dokumen asli)
  permohonan.json           ← data permohonan layanan masuk (kosong)
  kontak.json               ← pengaturan WhatsApp instansi (nomor, nama, aktif/nonaktif)
  uploads/
    berita/                 ← gambar cover berita
    unduhan/                ← berkas dokumen unduhan
    lampiran/                ← lampiran dari formulir permohonan
```

---

## Yang Perlu Dilakukan Admin Setelah Deploy

1. Unggah seluruh isi paket ini ke `/var/www/uptd-bjkpmk/public/` (lihat `PANDUAN-DEPLOY.md`).
2. Pastikan folder `storage/` dan seluruh sub-foldernya dapat ditulis oleh PHP (`chown www-data:www-data`, `chmod 775`).
3. Buka `https://<domain-anda>/admin.html`, login dengan password default **admin123**.
4. **Segera ganti password** di menu *Ganti Password*.
5. Buka menu *Kontak WhatsApp*, isi nomor WhatsApp instansi, pilih status **Aktif**, lalu simpan.
6. Isi statistik beranda sesuai data terkini (menu *Statistik Beranda*).
7. Unggah dokumen/formulir asli di menu *Unduhan* (dokumen contoh sebelumnya adalah data fiktif dan tidak diikutkan).
8. Periksa dan sunting/hapus berita & pengumuman contoh bila tidak diperlukan.

---

## Integrasi Admin & Editor (revisi 25 September 2026)

- Login `admin-konten.html` tidak lagi berdiri sendiri.
- Admin dan Editor login satu kali melalui `admin.html`.
- Menu **Konten Website** sekarang tersedia langsung dari panel admin.
- Admin dan Editor dapat mengedit keenam kelompok konten halaman publik.
- `konten-api.php` memvalidasi session `api.php`, sehingga tidak ada password editor kedua.
- Backup otomatis dibuat sebelum file konten ditimpa.
- Batas upload utama diseragamkan menjadi 8 MB.

## Kemungkinan Pengembangan Selanjutnya (belum diimplementasikan)

- Notifikasi WhatsApp/Email otomatis ke pemohon saat status permohonan berubah.
- Ekspor data permohonan ke Excel/PDF.
- Pengembangan lanjutan role yang lebih granular (saat ini sudah tersedia Admin dan Editor).
- Galeri foto kegiatan.
- Pencarian dan filter pada daftar berita.
