# MySIAKAD — Sistem Informasi Manajemen Akademik dan Nilai Mahasiswa

Aplikasi web terpusat untuk mengelola siklus akademik perguruan tinggi mulai dari pengelolaan master data, pengisian dan verifikasi Kartu Rencana Studi (KRS), jadwal perkuliahan, penilaian dosen, hingga pencetakan Kartu Hasil Studi (KHS / IPS) dan Transkrip Akademik (IPK).

Dibangun menggunakan **PHP 8 Native (PDO)**, **MySQL / MariaDB**, serta **Tailwind CSS + daisyUI v4**, dirancang untuk berjalan langsung pada lingkungan **XAMPP** tanpa ketergantungan framework eksternal.

---

## Tim Pengembang (Kelompok TRPL III)

| Nama Anggota | Peran Utama | Fokus Tanggung Jawab |
| :--- | :--- | :--- |
| **Amanda Angelica Simanjuntak** | Database & Backend Logic | Skema ERD 9 tabel, koneksi PDO, 9 class Model, autentikasi & keamanan (CSRF/RBAC) |
| **Hutagaol Yohana Abelia** | Front End (UI/UX) | Layout responsif daisyUI v4, komponen tabel/form/modal, modul 3 role & cetak A4 |
| **Celine Ernesta Marcella Br. Barus** | Dokumentasi & SDLC | Metodologi Waterfall Iteratif, spesifikasi SRS, 42 skenario pengujian (UAT) & manual |

---

## Fitur Utama Sistem

### 1. Autentikasi Multi-Role & Keamanan (`auth/`, `includes/`)
- Login terpusat untuk 3 role pengguna (**Admin**, **Dosen**, **Mahasiswa**) dengan sesi aman (`session_regenerate_id`).
- Hash password menggunakan algoritma `bcrypt` (`password_hash` & `password_verify`).
- Proteksi akses halaman lintas role (`require_role()`), sanitasi output anti-XSS (`e()`), token anti-CSRF (`csrf_field()` & `csrf_verify()`), serta `100%` *PDO Prepared Statements*.

### 2. Modul CRUD Master Data (Role: Admin)
- **Program Studi:** Kelola kode prodi unik, nama program studi, dan jenjang (`D3`, `D4`, `S1`).
- **Dosen:** Pembuatan akun login (`users`) dan profil dosen (`dosen`) secara atomik dalam satu transaksi database.
- **Mahasiswa:** Pembuatan akun login (`users`) dan profil mahasiswa (`mahasiswa`) dalam satu transaksi database beserta filter prodi, angkatan, dan status.
- **Mata Kuliah:** Kelola mata kuliah per prodi dengan validasi bobot `1–6 SKS` dan paket semester `1–8`.
- **Tahun Ajaran:** Pengaturan periode akademik dengan jaminan tepat satu semester berstatus **Aktif**.
- **Kelas & Jadwal:** Pembukaan kelas kuliah dengan penetapan dosen pengampu, hari, jam mulai/selesai, ruangan, dan kuota peserta.
- **Manajemen User:** Kelola status aktif/nonaktif akun dan reset password pengguna.

### 3. Tiga Fitur Transaksional Utama
1. **Pengisian & Verifikasi KRS:**
   - Mahasiswa memilih kelas pada semester aktif dengan **5 validasi otomatis** di dalam transaksi database:
     1. Tahun ajaran berstatus aktif.
     2. Kuota kelas belum penuh.
     3. Tidak mengambil mata kuliah yang sama dua kali pada semester yang sama.
     4. Total SKS tidak melebihi batas maksimum **24 SKS** (`MAX_SKS`).
     5. Tidak ada jadwal kuliah yang beririsan/bentrok pada hari dan jam yang sama.
   - Admin memverifikasi pengajuan KRS mahasiswa (**Setujui** / **Tolak**) baik per mata kuliah maupun secara massal.
2. **Input & Konversi Nilai Dosen:**
   - Dosen menginput komponen nilai **Tugas (30%)**, **UTS (30%)**, dan **UAS (40%)** untuk mahasiswa yang KRS-nya berstatus *Disetujui*.
   - Kalkulasi otomatis **Nilai Akhir**, **Huruf Mutu (`A`, `B+`, `B`, `C+`, `C`, `D`, `E`)**, dan **Bobot (`4.00` – `0.00`)** baik secara *live preview* di browser maupun validasi ulang di server, dilengkapi fitur **Ekspor CSV**.
3. **Kartu Hasil Studi (KHS), Transkrip Nilai (IPK) & Cetak A4:**
   - Perhitungan otomatis **Indeks Prestasi Semester (IPS)** pada KHS dan **Indeks Prestasi Kumulatif (IPK)** lintas semester pada Transkrip:
     $$\text{IPS / IPK} = \frac{\sum (\text{Bobot} \times \text{SKS})}{\sum \text{SKS}}$$
   - Tata letak cetak khusus kertas **A4 (`@media print`)** lengkap dengan kop resmi perguruan tinggi dan ruang tanda tangan tanpa menampilkan navigasi web.

---

## Struktur Direktori

```text
MySIAKAD/
├── index.php                  # Pintu masuk utama (redirect sesuai sesi role)
├── config/
│   ├── config.php             # Konstanta aplikasi, nama kampus, MAX_SKS, BASE_URL
│   └── database.php           # Koneksi singleton PDO MySQL/MariaDB (utf8mb4)
├── database/
│   ├── schema.sql             # DDL 9 tabel InnoDB (users, prodi, dosen, mahasiswa, dll.)
│   └── seed.php               # Skrip pengisi data awal dengan password_hash() dinamis
├── includes/
│   ├── auth.php               # Manajemen sesi login & guard otorisasi role
│   ├── csrf.php               # Generator & validator token CSRF
│   ├── helpers.php            # Fungsi utilitas (escape HTML, flash message, format tanggal)
│   └── ui.php                 # Komponen UI bersama (badge, alert, paginasi, modal hapus)
├── models/                    # 9 Class Model PDO (seluruh query SQL & aturan bisnis)
│   ├── UserModel.php
│   ├── ProdiModel.php
│   ├── DosenModel.php
│   ├── MahasiswaModel.php
│   ├── MataKuliahModel.php
│   ├── TahunAjaranModel.php
│   ├── KelasModel.php
│   ├── KrsModel.php
│   └── NilaiModel.php
├── layouts/                   # Kerangka tampilan (header, footer, sidebar per role)
├── auth/                      # Halaman login.php & logout.php
├── modules/
│   ├── admin/                 # Halaman dashboard, CRUD 7 master data, & verifikasi KRS
│   ├── dosen/                 # Halaman dashboard, kelas diampu, & input nilai (+ CSV)
│   └── mahasiswa/             # Halaman dashboard, KRS, jadwal, KHS, transkrip, & profil
├── assets/
│   ├── css/app.css            # Kustomisasi tema Deep Teal & aturan cetak @media print A4
│   └── js/app.js              # Skrip kalkulasi nilai live preview & interaksi UI
└── tests/                     # Skrip pengujian otomatis CLI (smoke_master, krs, nilai)
```

---

## Cara Instalasi & Deployment di XAMPP

1. **Salin Folder Proyek:**
   Tempatkan proyek di dalam folder `htdocs` XAMPP, misalnya:
   `C:\xampp\htdocs\MySIAKAD`
2. **Aktifkan Layanan XAMPP:**
   Buka **XAMPP Control Panel**, lalu jalankan modul **Apache** dan **MySQL** hingga berstatus **Running**.
3. **Impor Skema Database (`siakad_db`):**
   - Buka `http://localhost/phpmyadmin`
   - Klik tab **Import**, pilih berkas `database/schema.sql`, lalu klik **Go** (skrip otomatis membuat database `siakad_db` beserta 9 tabel berelasi).
4. **Jalankan Seed Data Awal:**
   - Buka `http://localhost/MySIAKAD/database/seed.php` satu kali di browser (atau jalankan `C:\xampp\php\php.exe database/seed.php` melalui terminal).
5. **Akses Aplikasi:**
   - Buka `http://localhost/MySIAKAD` di browser.

---

## Akun Login Demo (Auth Credentials)

Setelah menjalankan `database/seed.php`, gunakan akun berikut pada halaman login (`http://localhost/MySIAKAD/auth/login.php`):

| Role Pengguna | Username | Password | Keterangan Akun |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Administrator Akademik |
| **Dosen** | `0112048801` | `dosen123` | Dr. Budi Santoso, M.Kom. (Teknik Informatika) |
| **Dosen** | `0115079002` | `dosen123` | Rina Wijaya, S.Kom., M.T. (Teknik Informatika) |
| **Dosen** | `0120098503` | `dosen123` | Hendra Kusuma, S.T., M.Cs. (Sistem Informasi) |
| **Dosen** | `0108119204` | `dosen123` | Siti Aminah, S.Si., M.Kom. (Sistem Informasi) |
| **Mahasiswa** | `2401001` s/d `2401005` | `mhs123` | Mahasiswa Teknik Informatika (Contoh: Ahmad Fauzi — `2401001`) |
| **Mahasiswa** | `2402001` s/d `2402005` | `mhs123` | Mahasiswa Sistem Informasi (Contoh: Clara Nathania — `2402001`) |

---

## Pengujian Otomatis (CLI Smoke Tests)

Untuk memverifikasi seluruh fungsi model, aturan bisnis KRS, dan konversi nilai melalui terminal:

```powershell
C:\xampp\php\php.exe tests/smoke_master.php
C:\xampp\php\php.exe tests/smoke_krs.php
C:\xampp\php\php.exe tests/smoke_nilai.php
C:\xampp\php\php.exe tests/smoke_integration.php
```
