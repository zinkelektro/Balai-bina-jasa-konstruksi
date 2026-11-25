# Update Panel Admin / Editor — Terintegrasi

Paket ini sudah menyatukan pengelolaan konten ke panel `admin.html`.

## Yang diperbaiki

- Admin dan Editor login melalui **satu sistem akun** di `api.php`.
- Menu baru **Konten Website** tersedia di `admin.html`.
- Admin dan Editor dapat mengubah:
  - Parameter Pengujian
  - Kegiatan
  - Peralatan
  - Laboratorium
  - Pelayanan
  - Tentang UPTD
- Tidak ada login/password kedua di `admin-konten.html`.
- `konten-api.php` memakai session yang sama dengan `api.php`.
- Setiap penyimpanan konten membuat backup `*.backup.json`.
- Penyimpanan JSON dibuat lebih aman menggunakan file sementara lalu rename.
- Batas upload backend diseragamkan menjadi **8 MB**.
- File lama dan data upload tetap dipertahankan.

## Upload

Upload/overwrite seluruh isi folder paket ke root website lama.

**Penting:** jangan menghapus folder `storage/uploads/` karena berisi gambar berita dan dokumen yang sudah ada.

Setelah upload:

1. Pastikan `storage/` dan `konten-data/` dapat ditulis oleh PHP (`www-data` pada instalasi Nginx/PHP-FPM umumnya).
2. Login di `admin.html`.
3. Buat/kelola akun Editor melalui **Kelola Akun**.
4. Login sebagai Editor dan buka **Konten Website** untuk menguji semua enam halaman.
5. Untuk pengaturan permohonan, statistik, WhatsApp, dan akun pengguna, gunakan akun Admin.

## Catatan Nginx

Tetap blokir akses langsung ke `storage/` dan `konten-data/`. Data harus diakses melalui API.

Contoh lokasi Nginx:

```nginx
location ^~ /storage/ { deny all; }
location ^~ /konten-data/ { deny all; }
```

Sesuaikan jika struktur server Anda memakai lokasi yang berbeda.
