# Deploy ke shared hosting

Panduan ini untuk memasang Gurupai lewat `git clone`. Hosting perlu PHP 8.2, MySQL, dan Git (SSH atau menu Git di cPanel).

Arahkan domain ke folder hasil clone (biasanya `public_html`).

## 1. Clone

Lewat SSH:

```bash
cd public_html
git clone https://github.com/kangjhooe/semampai.git .
```

Titik di akhir artinya isi repo langsung masuk ke folder itu, tanpa subfolder baru.

Kalau repo bersifat privat, masukkan dulu username dan token GitHub saat diminta password.

## 2. Isi pengaturan

Salin `.env.example` menjadi `.env`, lalu sesuaikan:

```
APP_NAME=Gurupai
APP_URL=https://domain-anda.com
APP_DEBUG=false
APP_KEY=isi-acak-panjang

DB_HOST=localhost
DB_PORT=3306
DB_NAME=nama_database
DB_USER=user_database
DB_PASS=password_database
```

`APP_URL` harus sama dengan alamat yang dibuka di browser. Kalau aplikasi ada di subfolder, sertakan subfoldernya, misalnya `https://domain-anda.com/gurupai`.

`APP_KEY` cukup deretan acak, misalnya hasil dari:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Data database diambil dari panel hosting (cPanel → MySQL Databases). Nama database dan user biasanya ada awalannya, misalnya `user_gurupai`.

## 3. Pasang library

Folder `vendor` tidak ikut di Git. Lewat SSH, di folder aplikasi:

```bash
composer install --no-dev
```

Kalau hosting tidak punya Composer, jalankan perintah yang sama di komputer, lalu unggah folder `vendor` ke server.

## 4. Siapkan database

1. Di panel hosting, buat database kosong beserta usernya. Isi nama, user, dan password itu di `.env`.
2. Buka sekali di browser: `https://domain-anda.com/database/install`

Kalau berhasil, halaman itu menampilkan `OK`. Semua tabel langsung dibuat dari skema terbaru. File `migrate_*.php` tidak perlu dijalankan; file itu hanya untuk database lama yang sudah terpasang sebelumnya.

## 5. Izinkan unggahan

Pastikan folder ini bisa ditulis oleh aplikasi:

- `uploads/bahan`
- `uploads/kuis`

Di File Manager, izin `755` biasanya cukup. Kalau unggah file gagal, naikkan ke `775`.

## 6. Cek

Buka alamat situs. Seharusnya muncul halaman masuk. Daftar akun pertama dari situ.

Setelah berhasil, hapus file `database/install.php` supaya tidak bisa dijalankan orang lain.

## Update berikutnya

```bash
git pull
composer install --no-dev
```

File `.env` tidak ikut tertimpa karena tidak ada di Git.
