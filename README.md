# Food Tracker 
**Food_Tracker** adalah aplikasi pelacak konsumsi makanan berbasis web yang membantu pengguna menghitung kalori, protein, dan karbohidrat dari makanan yang dikonsumsi. Pengguna cukup memasukkan data diri dan tujuan kesehatan seperti diet, menaikkan, atau menjaga berat badan sebagai syarat registrasi dan login pengguna. Setelah berhasil, pengguna dapat melakukan perhitungan makanan yang diinginkan. Aplikasi ini menampilkan perhitungan dari asupan makanan pengguna, informasi BMI (Body Mass Index), BMR (Basal Metabolic Rate), dan TDEE (Total Daily Energy Expenditure). Aplikasi ini juga dilengkapi fitur riwayat perhitungan pengguna, dashboard pengguna, dan dashboard admin untuk mengelola data makanan dan pengguna. Website ini dirancang untuk membantu pengguna mencapai tujuan kesehatan yang diinginkan.

---

## 🍽️ Fitur Utama

- **Login dan Register**  
  pengguna dapat memasukan informasi pribadi yang dapat digunakan sebagai acuan perhitungan BMI (Body Mass Index) , BMR (Basal Metabolic Rate), dan TDEE (Total Daily Energy Expenditure) pengguna
- **Perhitungan Kalori, Protein, dan Karbohidrat**  
  Berdasarkan input makanan dari pengguna.
- **Laporan Nutrisi**  
  Menampilkan ringkasan asupan nutrisi harian, BMR (Basal Metabolic Rate), serta kebutuhan kalori harian tubuh.  
  Terdapat tombol **"Simpan Laporan Hari Ini"** agar laporan nutrisi pengguna tersimpan di riwayat laporan pengguna.
- **Riwayat Laporan**  
  Menyimpan dan menampilkan catatan nutrisi dari hari-hari sebelumnya.
- **Dashboard Pengguna**  
  berisi data diri pengguna yang dapat di edit sesuai kebutuhan, serta informasi BMI (Body Mass Index) pengguna
- **Dashboard Admin**  
  Untuk pengelolaan data makanan dan pengguna yang dapat di lakukan admin
  
---

## 🛠️ Teknologi Yang Digunakan

- **HTML**: Struktur isi halaman website  
- **CSS**: Tampilan antarmuka untuk memperindah tampilan website
- **PHP**: Menangani pengolahan data disisi backend, seperti  perhitungan nutrisi, BMR, TDEE, atau untuk menautkan database dengan perhitungan. 
- **JavaScript**:  JavaScript hanya digunakan untuk membuat tampilan menjadi lebih interaktif
- **MySQL**: Database untuk menyimpan data makanan dan user
  
---

## 🧾 Persyaratan Sistem

- **XAMPP** versi terbaru
- **VSCode** versi terbaru
- **Web browser** modern

---

## 🗂️ Struktur File
```
food_tracker/
├── assets/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
├── config/
│   ├── database.php
│   └── session.php
├── admins1.php
├── config.php
├── dash.css
├── dash.php
├── dashboard1.php
├── food_tracker.css
├── food_tracker.php
├── food-logs1.php
├── foods1.php
├── index.php
├── login.php
├── login_backend.php
├── login1.php
├── logout.php
├── logout1.php
├── package.json
├── regist.php
├── report_history.php
├── reports1.php
├── user-detail1.php
└── users1.php
```

---

## 🚀 Langkah-langkah Instalasi Project

### 1. Clone Repository

```
git clone https://github.com/WidyaRatna/food_tracker.git
cd food_tracker
```
### 2. Pindahkan ke Direktori Web Server
Jika menggunakan XAMPP, salin seluruh folder proyek ke direktori berikut:
```
C:\xampp\htdocs\food_tracker
```
### 3. Buka Proyek di VSCode
- ‎Install VSCode jika belum punya
- ‎Buka folder tersebut di VSCode
### 4. Jalankan XAMPP
- Pastikan anda sudah menginstall XAMPP
- ‎Buka **XAMPP Control Panel** dan nyalakan server Apache & MySql
### 5. Siapkan Database
- Buka browser dan akses: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
- Buat database baru dengan nama: `web_login`
- Import file `web_login.sql` yang terdapat dalam folder proyek ke dalam database `web_login`.
### 6. Jalankan Website
- Setelah database disiapkan dan XAMPP berjalan, akses website melalui browser:
  [http://localhost/food_tracker/](http://localhost/food_tracker/)

---

## 📖 Panduan Penggunaan

‎### 1. Registrasi / Login
- Masuk ke menu **Register** dan isi data pribadi Anda.
- Jika sudah memiliki akun, langsung **Login**.
### 2. Input Makanan
- Masukkan makanan dan beratnya di menu **Input Makanan** dan klik **+ Tambah Makanan** untuk memulai perhitungan.
### 3. Lihat Laporan Nutrisi
- Buka bagian bawah menu **Food Tracker** untuk melihat laporan nutrisi yang berisi total asupan hari itu, sistem akan menampilkan data membandingkan data tersebut dengan kebutuhan kalori harian (TDEE & BMR).
### 4. Simpan Laporan
- Klik **Simpan Laporan Hari Ini** agar data masuk ke riwayat laporan.
### 5. Lihat Riwayat Laporan
- Buka menu **Riwayat Laporan** untuk melihat data laporan nutrisi dari hari-hari sebelumnya.
### 6. Dashboard 
- Kunjungi **Dashboard** untuk melihat dan mengedit profil serta melihat informasi BMI pengguna.
### 7. Logout
- Klik **Logout** untuk keluar dari sistem.

---

## 🎥 Cek Demo Website Kita Pada:
👉 [https://wpwbmi.infinityfreeapp.com/]( https://wpwbmi.infinityfreeapp.com/)



