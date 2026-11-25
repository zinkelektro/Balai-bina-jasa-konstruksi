====================================================================
UPGRADE: KELOLA KONTEN "QUICK CARD" OLEH ADMIN/EDITOR
UPTD Bina Jasa Konstruksi dan Pengujian Material Konstruksi — NTB
====================================================================

RINGKASAN
---------
Sebelumnya hanya bagian Berita, Pengumuman, Unduhan, Statistik, dan
Kontak di index.html yang bisa diubah oleh admin (lewat api.php yang
sudah ada di server Anda).

Upgrade ini menambahkan kemampuan yang sama untuk 6 halaman lain:
  1. pengujian-material.html  -> kartu "Parameter Pengujian"
  2. kegiatan.html             -> kartu statistik + kronologi kegiatan
  3. peralatan.html            -> tabel peralatan per kategori lab
  4. laboratorium.html         -> statistik + kartu unit laboratorium
  5. pelayanan.html            -> alur layanan, kartu layanan, jam operasional
  6. tentang.html               -> profil, identitas, visi-misi, struktur, dasar hukum

Semua bisa diedit lewat panel baru: admin-konten.html
(tautannya juga sudah ditambahkan di footer setiap halaman, label
"Kelola Konten Kartu").

Sistem ini TIDAK memakai database — data disimpan sebagai file JSON
di folder konten-data/, jadi tidak perlu setting MySQL apa pun. Aman
digabung dengan api.php/admin.html yang sudah ada (tidak akan bentrok
karena nama file dan tabel/data yang dipakai berbeda).


CARA PASANG
-----------
1. Upload SELURUH isi folder ini ke root website Anda (folder yang
   sama tempat index.html berada saat ini), lalu timpa/replace file
   lama dengan yang baru. File/folder yang ditambahkan:
     - konten-api.php
     - admin-konten.html
     - konten-data/               (folder + 6 file .json + .htaccess)
     - js/konten-shared.js
     - js/render-pengujian.js
     - js/render-kegiatan.js
     - js/render-peralatan.js
     - js/render-laboratorium.js
     - js/render-pelayanan.js
     - js/render-tentang.js
   File yang DIPERBARUI (ditimpa):
     - pengujian-material.html, kegiatan.html, peralatan.html,
       laboratorium.html, pelayanan.html, tentang.html, index.html

2. Pastikan folder "konten-data" bisa DITULIS oleh PHP.
   Lewat cPanel File Manager / FTP, set permission folder tsb ke 755
   (atau 775 jika 755 masih gagal saat menyimpan dari admin panel).

3. GANTI PASSWORD DEFAULT. Password bawaan adalah:
       Username: admin
       Password: uptd2026
   Segera ganti dengan cara:
   a. Buka di browser:
        https://domain-anda.com/konten-api.php?action=buat_hash&password=PASSWORD_BARU_ANDA
      Anda akan melihat balasan JSON berisi "hash": "xxxxxxxx...".
   b. Salin nilai hash tsb, lalu edit konten-api.php, cari baris:
        $ADMIN_PASS_HASH = '...';
      dan ganti isinya dengan hash yang baru.
   c. Setelah selesai, HAPUS blok kode berikut dari konten-api.php
      (supaya orang lain tidak bisa membuat hash sembarangan):
        if ($action === 'buat_hash') { ... }
   d. Upload ulang konten-api.php yang sudah diedit.

4. Buka https://domain-anda.com/admin.html dan login.
5. Pilih menu "Konten Website". Admin dan Editor dapat langsung mengedit
   Pengujian, Kegiatan, Peralatan, Laboratorium, Pelayanan, dan Tentang.


CARA PAKAI PANEL ADMIN (admin-konten.html)
-------------------------------------------
- Pilih tab halaman di atas (Parameter Pengujian, Kegiatan, dst).
- Klik judul kartu untuk membuka/menutup form edit kartu tsb.
- Tombol "+ Tambah" untuk menambah kartu/baris baru.
- Tombol panah atas/bawah untuk mengubah urutan tampil.
- Tombol tanda silang (x) untuk menghapus.
- Untuk daftar "satu baris = satu item" (mis. daftar parameter,
  daftar tag), gunakan tombol "+ Tambah Baris".
- Setelah selesai mengedit satu halaman, WAJIB klik tombol
  "Simpan Perubahan" di bagian bawah — perubahan baru tersimpan dan
  tampil di website setelah tombol ini ditekan.
- Perubahan langsung terlihat di halaman publik terkait begitu
  halaman tsb dibuka/di-refresh (tidak perlu proses build apa pun).


CATATAN KEAMANAN & TEKNIS
--------------------------
- Login editor sekarang TERINTEGRASI dengan admin.html. Admin dan Editor
  cukup login sekali di admin.html lalu menu "Konten Website" dapat
  digunakan tanpa login ulang.
- konten-api.php membaca session $_SESSION['user'] yang dibuat api.php.
  Role admin dan editor sama-sama diberi izin mengedit keenam halaman
  konten. Tidak ada lagi password/hash login kedua untuk editor konten.
- Setiap halaman publik (pengujian-material.html dkk) akan mencoba
  mengambil data terbaru dari konten-api.php. Jika karena suatu hal
  file tsb belum ter-upload atau server bermasalah, halaman akan
  tetap menampilkan konten bawaan (fallback) yang sudah ada di HTML,
  jadi situs TIDAK akan tampil kosong/rusak.
- Setiap kali menyimpan, sistem otomatis membuat file cadangan
  (mis. konten-data/kegiatan.backup.json) berisi versi sebelumnya,
  untuk jaga-jaga jika ingin mengembalikan data lama.
- Folder konten-data/ diblokir dari akses langsung browser lewat
  .htaccess (hanya bisa dibaca lewat konten-api.php). Ini berlaku
  jika server memakai Apache; jika server Anda memakai Nginx, minta
  developer/hosting menambahkan blokir serupa untuk folder tsb.


DAFTAR IKON & WARNA TEMA YANG TERSEDIA
----------------------------------------
Tema warna: Navy, Emas, Teal, Merah, Abu-abu
  (disesuaikan otomatis dengan palet warna situs yang sudah ada)
Ikon: Beton, Aspal, Tanah, Baja, Dokumen, Alat, Laboratorium
  (dipilih lewat dropdown di form, tidak perlu tahu kode SVG)

Jika suatu saat perlu menambah pilihan ikon/tema baru, developer bisa
menambahkannya di file js/konten-shared.js.
