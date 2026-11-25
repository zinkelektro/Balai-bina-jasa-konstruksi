UPDATE HOMEPAGE UPTD - DESAIN MENGIKUTI REFERENSI
=================================================

Isi paket ini adalah website UPTD versi lengkap dengan index.html yang sudah
mengikuti desain pada gambar referensi:
- Header putih dengan logo dan navigasi.
- Hero laboratorium dengan overlay biru.
- Judul UPTD Bina Jasa Konstruksi dan Pengujian Material Provinsi NTB.
- Tombol "Lihat Layanan Kami".
- 6 kartu menu cepat bergambar.
- Responsive untuk desktop/tablet/HP.
- Fungsi API, berita, pengumuman, unduhan, permohonan dan admin lama tetap
dipertahankan.

FILE UTAMA YANG DIUBAH:
- index.html
- css/style.css

ASSET BARU:
- assets/logo-uptd.png
- assets/hero-lab.jpg
- assets/card-1.jpg s/d card-6.jpg

CARA UPLOAD:
1. Backup folder website lama.
2. Extract ZIP ini ke folder website UPTD.
3. Timpa index.html dan css/style.css.
4. Pastikan folder assets ikut tertimpa/ter-upload.
5. Jangan hapus api.php dan folder storage.
6. Reload Nginx bila diperlukan:
   nginx -t && systemctl reload nginx

Paket dibuat untuk stack Nginx + PHP + Cloudflare. Tidak memerlukan database
baru atau perubahan konfigurasi Nginx.
