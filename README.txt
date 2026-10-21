WEBSITE POMPARAN MANIK RAJA - VERSI AWAL

CARA INSTALASI DI XAMPP (Windows)
1. Pastikan XAMPP terpasang.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Ekstrak folder manik-raja-website ke C:\xampp\htdocs\
   Hasilnya: C:\xampp\htdocs\manik-raja-website\index.php
4. Buka http://localhost/phpmyadmin
5. Pilih menu Import, pilih file database.sql dari folder website, lalu klik Import/Go.
   File ini menggunakan database marga_manik sesuai config.php dan menambah kolom
   yang dibutuhkan tanpa menghapus data lama. Jika instalasi memakai nama database
   lain, sesuaikan config.php dan baris USE di database.sql.
6. Periksa config.php. Default XAMPP biasanya user root dan password kosong.
7. Buka http://localhost/manik-raja-website/setup_admin.php
8. Buat username dan password admin. Gunakan password unik minimal 12 karakter.
9. Setelah admin berhasil dibuat, HAPUS file setup_admin.php dari folder website.
10. Login melalui http://localhost/manik-raja-website/login.php
11. Website publik: http://localhost/manik-raja-website/

FITUR DALAM VERSI AWAL
- Beranda, Sejarah, Pengurus, Berita, Galeri, Pendaftaran, Kontak
- Formulir pendaftaran masuk ke database dan menunggu pemeriksaan admin
- Admin dapat mengubah status pendaftaran, mengelola anggota, menambah/mengedit/menghapus
  berita/sejarah dan pengurus, serta mengelola foto dan video galeri.

CATATAN PENTING
- Ini adalah starter lokal, belum siap langsung dipasang di internet tanpa hardening tambahan.
- Unggahan gambar dashboard dibatasi maksimal 5 MB dan diverifikasi berdasarkan tipe file.
- Link video yang diterima berasal dari YouTube dan harus memakai HTTPS.
- Untuk online: wajib HTTPS, password database kuat, backup rutin, validasi keamanan tambahan, dan pengaturan server yang aman.
- Jangan publikasikan data pendaftaran anggota.
- Untuk mengisi galeri secara manual, simpan gambar di folder images (buat folder itu), lalu masukkan record ke tabel gallery dengan image_path misalnya images/foto-kegiatan.jpg.

PENGELOLAAN KELOMPOK PENGURUS (DATABASE LAMA)
Untuk database lama, buka phpMyAdmin, pilih database marga_manik, lalu impor file
migrasi-pengurus-regional.sql satu kali. Migrasi ini menyiapkan kelompok pengurus,
slot jabatan, dan menautkan data pengurus lama. Instalasi baru dengan database.sql
terbaru tidak perlu menjalankan migrasi terpisah. Setiap kelompok mendapat tiga slot
Penasehat serta slot Ketua, Wakil Ketua, Sekretaris, Wakil Sekretaris, Bendahara,
dan Wakil Bendahara. Jabatan yang belum memiliki nama disembunyikan dari laman publik.
