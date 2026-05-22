# Hisabara

Sistem Informasi Akuntansi Masjid berbasis **entitas nonlaba**. Dibangun dengan PHP Native, MySQL, Bootstrap 5, dan Chart.js.

Hisabara dirancang untuk membantu pengurus masjid melakukan pencatatan, pelaporan, pengawasan, dan pengelolaan keuangan secara lebih transparan, terstruktur, efisien, dan sesuai standar akuntansi entitas nonlaba.

---

## Keunggulan

| Aspek | Deskripsi |
|-------|-----------|
| **Standar Akuntansi** | Mengacu entitas nonlaba |
| **Workflow Approval** | Alur persetujuan transaksi (bendahara → admin) |
| **Dashboard Interaktif** | Grafik pemasukan/pengeluaran, Skor Kesehatan Keuangan, komposisi beban |
| **HISA AI** | Satu halaman terpadu dengan AI Financial Monitor + 2 tab: Finance, Analytics |
| **Import Data** | Import transaksi & inventaris massal via CSV atau Excel (.xlsx/.xls) |
| **Audit Log** | Catatan aktivitas pengguna dengan IP address dan detail perubahan |
| **Anggaran** | Budget bulanan/tahunan dengan progress bar dan peringatan over budget |
| **Penyusutan Aset** | Manajemen aset tetap, umur ekonomis, nilai residu, jurnal otomatis |
| **REST API** | Endpoint terproteksi untuk integrasi sistem eksternal |
| **Responsive Design** | Tampilan optimal di desktop, tablet, dan perangkat mobile |
| **Arsitektur Modular** | Struktur kode terpisah per modul, mudah dikembangkan dan dipelihara |
| **Transparansi** | Seluruh transaksi tercatat, teraudit, dan dapat dilaporkan ke publik |

---

## Fitur

| Modul | Deskripsi |
|-------|-----------|
| **Dashboard** | KPI cards, Skor Kesehatan Keuangan, grafik pemasukan/pengeluaran 12 bulan, komposisi beban, peringatan anggaran, transaksi terbaru |
| **Input Transaksi dengan Bantuan** | Deskripsikan transaksi dalam teks, sistem membantu parsing dan pengisian form secara otomatis |
| **Analisis & Perbaikan Transaksi** | Deteksi otomatis indikasi jurnal tidak balance, duplikasi, anomali jumlah, transaksi tanpa jurnal, dan pending lama — dengan opsi perbaikan massal |
| **Chart of Accounts** | COA 6 kategori dengan klasifikasi dana terikat/tidak terikat |
| **Transaksi + Approval** | Bendahara input, admin approve/reject, status pending/approved/rejected, upload bukti, lock tutup buku |
| **Buku Besar** | Mutasi per akun, saldo berjalan, filter periode |
| **Laporan Keuangan** | Neraca Saldo, Laporan Posisi Keuangan, Laporan Aktivitas, Laporan Arus Kas, Laporan Dana Khusus — dilengkapi audit dan narasi otomatis |
| **CALK** | Catatan Atas Laporan Keuangan — buat otomatis atau kelola manual |
| **Anggaran** | Budget bulanan/tahunan, realisasi dengan progress bar, peringatan melebihi anggaran |
| **AI Perbaikan Anggaran** | Deteksi over budget, anggaran terlalu besar, akun tanpa anggaran — saran nominal disesuaikan saldo kas, eksekusi massal |
| **AI Pembuat Anggaran** | Prompt instruksi → AI hasilkan 2-3 opsi anggaran lengkap dengan rincian, alasan, proyeksi, dan rekomendasi — pilih dan terapkan |
| **Periode Akuntansi** | Kelola periode, tutup/buka buku, lock transaksi |
| **Inventaris + Penyusutan** | Aset tetap, umur ekonomis, nilai residu, nilai buku, jurnal penyusutan otomatis |
| **HISA AI** | Satu halaman terpadu dengan 2 tab: Finance, Analytics |
| **Audit Log** | Catatan aktivitas dengan IP address, user agent, before/after value |
| **Profil & Pengguna** | Edit profil masjid, manajemen user (admin/bendahara) |
| **Import Data** | Import transaksi & inventaris massal dari file CSV atau Excel (.xlsx/.xls) dengan preview & mapping kolom otomatis |
| **Halaman Publik** | Informasi keuangan ringkas tanpa login — saldo, grafik, ringkasan |
| **REST API** | Endpoint dashboard, transaksi, laporan, neraca, grafik — dilindungi token |
| **Export** | PDF (DomPDF), Excel (PhpSpreadsheet) |
| **Dark Mode** | Toggle tema terang/gelap |

### Kepatuhan entitas nonlaba

- Klasifikasi Aset Neto dalam Laporan Posisi Keuangan
- Dana terikat dan tidak terikat pada akun
- Laporan Aktivitas (Pendapatan - Beban = Surplus/Defisit)
- Catatan Atas Laporan Keuangan (CALK)
- Nomor transaksi otomatis untuk setiap entry
- Validasi keseimbangan debit dan kredit

---

## Arsitektur Sistem

```
User (Browser)
     │
     ▼
PHP Native Application (index.php → modules/*.php)
     │
     ├── MySQL Database (keuangan_masjid)
     │
     └── Smart Service (OpenRouter API)
             │
             └── deepseek/deepseek-chat (cepat & hemat)
```

### Komponen Arsitektur

| Lapisan | Teknologi | Keterangan |
|---------|-----------|------------|
| **Presentation** | Bootstrap 5.3, Chart.js 4 | UI responsif, grafik interaktif, dark mode |
| **Application** | PHP 8+ (Native) | Router tunggal (`index.php`), modul terpisah per halaman |
| **Data** | MySQL / MariaDB | PDO, prepared statements, migrasi otomatis |
| **Smart Service** | OpenRouter API | Request ke deepseek/deepseek-chat, cache file-based |
| **Security** | CSRF, Rate Limiting, CSP, Session Mgmt | Pertahanan berlapis |

Aplikasi menggunakan arsitektur modular: setiap modul berada di file terpisah dalam direktori `modules/`, konfigurasi di `config/`, dan aset statis di `assets/`. Router `index.php` menangani layout, sidebar, navbar, dan memuat modul sesuai parameter `page`.

---

## Persyaratan Sistem

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10+
- Composer (untuk export PDF & Excel)
- Ekstensi PHP: `pdo_mysql`, `curl`, `mbstring`, `gd`, `fileinfo`
- Server: XAMPP, WAMP, Laragon, atau LEMP

---

## Instalasi

### 1. Clone atau Salin Project

Letakkan folder `bis` di direktori `htdocs` server web Anda.

### 2. Konfigurasi Database

Salin file `.env.example` menjadi `.env` dan sesuaikan:

```bash
cp .env.example .env
```

Edit `.env`:

```
DB_HOST=localhost
DB_NAME=keuangan_masjid
DB_USER=root
DB_PASS=
```

Atau edit langsung `config/database.php` jika tidak menggunakan `.env`.

### 3. Import Database

**Opsi A — via phpMyAdmin:**
1. Buat database `keuangan_masjid`
2. Import `sql/database.sql`
3. Import `sql/migration.sql`
4. (Opsional) Import `sql/seed_data.sql` untuk data dummy

**Opsi B — via terminal:**

```bash
mysql -u root -p < sql/database.sql
mysql -u root -p < sql/migration.sql
mysql -u root -p < sql/seed_data.sql
```

Database akan termigrasi otomatis saat pertama kali aplikasi dijalankan.

### 4. Konfigurasi Layanan AI

Layanan AI menggunakan API OpenRouter. Konfigurasi di `.env`:

```
AI_API_KEY=sk-or-v1-...
AI_MODEL=deepseek/deepseek-chat
```

Pastikan ekstensi PHP `curl` aktif.

### 5. Composer (Opsional)

Untuk export PDF dan Excel:

```bash
cd /path/to/bis
composer install
```

Aplikasi tetap berjalan tanpa Composer.

### 6. Akses Aplikasi

Buka `http://localhost/bis` di browser.

**Akun default:**

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Administrator |
| `bendahara` | `admin123` | Bendahara |

### 7. API

Token API tergenerate otomatis saat migrasi. Cek di tabel `settings` key `api_token`.

Gunakan via header:
```
X-API-Token: token_anda
```
Atau query parameter:
```
?token=token_anda
```

**Endpoint:**

| Endpoint | Deskripsi |
|----------|-----------|
| `?endpoint=dashboard` | Ringkasan saldo, pemasukan, pengeluaran, health score |
| `?endpoint=transaksi` | Daftar transaksi |
| `?endpoint=laporan` | Laporan posisi keuangan |
| `?endpoint=neraca` | Neraca saldo |
| `?endpoint=grafik` | Grafik pemasukan/pengeluaran per bulan |
| `?endpoint=beban` | Breakdown beban per akun |
| `?endpoint=dana_khusus` | Saldo dan transaksi dana khusus |

---

## HISA AI — Hisabara Intelligence

Dua modul kecerdasan untuk pengelolaan keuangan masjid dalam satu halaman terpadu dengan tab navigasi, dilengkapi AI Financial Monitor:

### Halaman Terpadu HISA AI
- **AI Financial Monitor** — Peringatan real-time: saldo kritis, pengeluaran naik tajam, pemasukan menurun, jurnal tidak balance, transaksi besar, duplikasi, dan transaksi waktu tidak biasa
- **Tab Finance** — Parse transaksi, tanya data keuangan, ringkasan & narasi, rekomendasi efisiensi + AI Financial Monitor
- **Tab Analytics** — 4 fitur analisis: Prediktif (prediksi saldo + ketahanan), Pola & Anomali (pola transaksi + anomali + tren), Audit & Risiko (audit + transparansi + kepatuhan + risiko kas), Laporan & Sosial (dampak sosial + narasi dana)

### Detail Fitur per Tab

#### Finance Tab
- **AI Financial Monitor** — Peringatan keuangan real-time: saldo kritis, pengeluaran naik tajam, pemasukan menurun, jurnal tidak balance, transaksi besar, duplikasi, transaksi mencurigakan
- **Tanya & Catat Keuangan** — Chat AI: tanya kondisi keuangan, catat transaksi baru, minta ringkasan/narasi/rekomendasi
- **Parse Transaksi** — deskripsi teks → AI parsing jenis, akun, nominal → preview konfirmasi → simpan
- **Quick Actions** — Ringkasan, Narasi, Efisiensi, Analisis (satu klik)

#### Analytics Tab
- **Analisis Prediktif** — prediksi saldo 3 bulan + skor ketahanan finansial A-E
- **Pola & Anomali** — pola transaksi, deteksi anomali, tren keuangan 12 bulan
- **Audit & Risiko** — audit neraca saldo, skor transparansi, evaluasi kepatuhan, risiko kas
- **Laporan & Sosial** — analisis dampak sosial, narasi perjalanan dana

### CALK Auto-Generate

Catatan Atas Laporan Keuangan (CALK) dapat dibuat otomatis dengan sekali klik:
- Klik **Buat Otomatis** di halaman CALK
- Sistem mengumpulkan data keuangan (neraca, aktivitas, arus kas, aset, dana khusus)
- Menghasilkan 5-8 bagian CALK
- Pratinjau hasil sebelum disimpan ke database

### Performa

- Model: `deepseek/deepseek-chat` via OpenRouter (cepat & hemat)
- Cache file-based (2 jam)
- Markdown rendering + JSON extraction otomatis
- Dark mode support penuh

### Disclaimer

> HISA AI berfungsi sebagai alat bantu analisis dan memberikan rekomendasi berdasarkan data yang tersedia. Seluruh keputusan, pemeriksaan, dan validasi akhir tetap berada pada pengurus atau administrator sistem.

---

## Fitur Dokumentasi Sistem

| Fitur | Keterangan |
|-------|------------|
| **Perbandingan Laporan Antar Periode** | Filter laporan berdasarkan rentang tanggal untuk membandingkan kinerja keuangan |
| **Analisis Tren Pemasukan & Pengeluaran** | Grafik 12 bulan untuk melihat pola musiman dan tren keuangan |
| **Analisis Surplus/Defisit** | Perhitungan selisih pendapatan dan beban setiap periode |
| **Arsip Dokumen Transaksi** | Upload dan penyimpanan bukti transaksi (JPG, PNG, PDF) |
| **Nomor Transaksi Otomatis** | Setiap transaksi mendapat nomor urut sistem |
| **Dashboard Aktivitas Pengguna** | Log aktivitas dengan filter aksi, tabel, IP address |
| **Validasi Keseimbangan Debit & Kredit** | Setiap jurnal dipastikan balance sebelum disimpan |

---

## Struktur Direktori

```
bis/
├── index.php              # Router utama + layout
├── login.php              # Halaman login
├── logout.php             # Logout
├── ajax_ai.php            # Endpoint HISA AI
├── .env.example           # Contoh konfigurasi lingkungan
├── composer.json
├── cache/                 # Cache HISA AI
├── api/
│   └── index.php          # REST API
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── js/chart.js
├── config/
│   ├── database.php       # Koneksi database + migrasi
│   ├── functions.php      # Helper functions
│   ├── security.php       # CSRF, rate limiting, upload, CSP
│   └── ai.php             # Smart service integration
├── modules/
│   ├── dashboard.php
│   ├── akun.php
│   ├── transaksi.php
│   ├── laporan.php
│   ├── buku_besar.php
│   ├── inventaris.php
│   ├── import.php          # Import data CSV/Excel
│   ├── anggaran.php
│   ├── periode.php
│   ├── profil.php
│   ├── backup.php
│   ├── ai_hisa.php        # HISA AI terpadu (2 tab + monitor)
│   ├── users.php
│   ├── audit_log.php
│   └── calk.php
├── public/
│   └── index.php          # Halaman publik (tanpa login)
├── exports/
├── uploads/
├── vendor/
└── sql/
    ├── database.sql
    ├── migration.sql
    └── seed_data.sql
```

---

## Alur Sistem

### Input Transaksi
```
Deskripsi teks → Diproses → Preview konfirmasi → Upload bukti → Simpan
```

### Analisis & Perbaikan Transaksi
```
Klik "Analisis & Perbaikan" → Sistem menganalisis transaksi → Daftar indikasi masalah + saran → "Setuju Semua" → Perbaikan diproses
```

### HISA Budget
```
Instruksi teks → Sistem hasilkan 2-3 opsi (rincian + alasan + proyeksi) → Pilih opsi → Terapkan
```

### Analisis & Perbaikan Anggaran
```
Klik "Perbaiki" → Sistem analisis anggaran: over budget, kurang realisasi, tanpa anggaran → Disesuaikan saldo → Setuju Semua → Update
```

### Approval Transaksi
```
Bendahara input → Pending → Admin review → Approve/Reject → Jurnal/Batal
```

### Siklus Akuntansi
```
Transaksi (approved) → Jurnal Detail → Buku Besar → Neraca Saldo → Laporan Keuangan → CALK
```

### Penyusutan Aset
```
Input aset (harga, umur, residu) → Hitung penyusutan → Generate jurnal → Update nilai buku
```

---

## Keamanan

| Lapisan | Implementasi |
|---------|-------------|
| **CSRF** | Token di setiap form, auto-regenerate jika mismatch |
| **Rate Limiting** | Login dibatasi (default 5 percobaan, lockout 15 menit) |
| **Session** | HTTP-only, SameSite=Lax, regenerasi periodik, timeout 1 jam |
| **Password** | `password_hash()` + `password_verify()` |
| **SQL Injection** | PDO prepared statements |
| **XSS** | `htmlspecialchars()` pada output |
| **Upload** | Validasi ekstensi, MIME type, hash file (SHA-256), ukuran maksimal |
| **Logging** | Login attempt logging, aktivitas pengguna, IP, user agent |
| **Headers** | CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy |
| **Environment** | Konfigurasi via `.env` file |

---

## Data Dummy

File `sql/seed_data.sql` menyediakan data contoh untuk pengujian:

| Data | Jumlah |
|------|--------|
| Akun (COA) | 29 |
| Users | 2 |
| Transaksi | 77 (balance) |
| Jurnal Detail | 154 |
| Dana Khusus | 3 |
| Inventaris | 6 |
| Anggaran | 11 |
| Periode | 5 |
| CALK | 8 |

Saldo awal kas: Rp 75.000.000. Seluruh transaksi menggunakan double-entry.

---

## Batasan Sistem

- **HISA AI** berfungsi sebagai alat bantu analisis, bukan pengganti keputusan manusia
- **Multi-cabang** belum didukung — saat ini dirancang untuk satu entitas masjid
- **Aplikasi mobile native** belum tersedia — akses melalui browser mobile

---

## Pengembangan Selanjutnya

- Integrasi QRIS untuk donasi dan penerimaan dana
- Payment gateway untuk transaksi online
- OCR (Optical Character Recognition) untuk scan struk dan dokumen
- Notifikasi WhatsApp untuk reminder dan laporan berkala
- Dukungan multi-cabang masjid dalam satu instalasi
- Aplikasi mobile (Android / iOS)
- AI prediksi anggaran berdasarkan tren historis multi-tahun
- Rekonsiliasi bank otomatis berbasis AI

---

## Teknologi

- **Backend:** PHP 8+ (Native, no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Chart.js 4
- **Layanan AI:** OpenRouter API (deepseek/deepseek-chat)
- **Export:** DomPDF, PhpSpreadsheet
- **Security:** CSRF, Rate Limiting, CSP, Session Management
- **Accounting:** Non-Profit Entities

