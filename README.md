# Dashboard GM

Sistem Dashboard Produksi Internal Berbasis Web (PHP CodeIgniter 3) yang terintegrasi dengan Pipeline Otomasi RPA (Python, Playwright, Windows GUI Automation) dan Database MySQL.

---

## Navigasi Dokumentasi Lengkap

1. **[README_CI3_DASHBOARD.md](README_CI3_DASHBOARD.md)**:
   - Panduan arsitektur sistem, instalasi, dan struktur file aplikasi.
   - Dokumentasi lengkap modul Dashboard Heat Transfer, Portal GM, dan Panel Admin Terpadu.
   - Manajemen Akun Pengguna (Kelola Akun, Role, Hak Akses, dan Proteksi Akun).
   - Fitur Filter Rentang Tanggal Dinamis (From-To Date Picker) dan Toggle Delivery Count.
   - Tracking Kapasitas & Output Jam-jamanan (Hourly Tracking) dan Jam Kerja Dinamis (Sabtu Kerja vs Libur).
   - Skema Buffer Hari Kerja Persiapan Export (4, 8, dan 14 hari kerja).
   - Panduan integrasi data RPA (APS, Engage, Accessories) dan database MySQL.
   - Penjelasan formula metrik: PDK vs Output, Ready to Load, Demand vs Output, Style SMV, Kalender Kerja, dan Management Analytics.
   - Panduan penggunaan Master Launcher `dist/RPA_Master.exe`.

2. **[CODE_FEATURE_MAP.md](CODE_FEATURE_MAP.md)**:
   - Pemetaan rinci hubungan kode sumber (Controllers, Models, Views, Scripts, Database, Config, dan Cache JSON) terhadap fitur fungsional.
   - Rujukan fungsi backend dan frontend untuk kebutuhan modifikasi atau maintenance.

---

## Ringkasan Akses & URL Aplikasi

Setelah Apache dan MySQL aktif pada XAMPP:

| Modul | URL Akses | Keterangan |
| --- | --- | --- |
| **Portal GM** | `http://localhost/dashboard_gm/` atau `/index.php/dashboard` | Gerbang landing page utama & modal login admin |
| **Dashboard Heat Transfer** | `http://localhost/dashboard_gm/index.php/dashboard_heat` | Dashboard monitoring publik dengan filter tanggal dan delivery |
| **Admin Panel GM** | `http://localhost/dashboard_gm/index.php/dashboard_heat/admin` | Panel manajemen terpadu (Akun, SMV, Kalender, Analytics, Data) |

---

## Ringkasan Fitur Utama

- **Instant Pre-Hydration**: Dashboard publik memuat data seketika tanpa delay blank page menggunakan payload server-side (`initial_dashboard_payload`).
- **Filter Rentang Tanggal Dinamis**: Kontrol tanggal interaktif (`From` & `To`) pada header dashboard dengan default cerdas (MID: 01-15, END: 16-akhir bulan) serta opsi toggle delivery count (1, 2, 4, 6 delivery) dengan persistensi cookie.
- **Tracking Output & Kapasitas Jam-jamanan (Hourly Tracking)**: Grafik *KAPASITAS vs OUT vs IN* mendukung visualisasi pencapaian jam kerja hari ini (07:00 s.d. 16:00+) dibandingkan target kapasitas per jam, dengan auto-fallback ke grafik harian jika data belum memiliki timestamp jam.
- **Perhitungan Jam Kerja Dinamis**: Logika adaptif terhadap hari kerja Sabtu:
  - *Jika Sabtu Kerja*: Weekday 7 jam kerja, Sabtu 5 jam kerja.
  - *Jika Sabtu Libur*: Weekday 8 jam kerja, Sabtu 0 jam kerja.
- **Buffer Persiapan Export Bertingkat**: Pengurangan buffer hari kerja sebelum batas waktu export secara berjenjang:
  - 1 Delivery: **4 hari kerja**
  - 2 Delivery: **8 hari kerja**
  - 4+ Delivery: **14 hari kerja**
- **Manajemen Akun Pengguna (Kelola Akun)**: Fitur CRUD pengguna di Panel Admin (`tbl_login`) dengan 4 tingkatan role (`Admin`, `Planning`, `Production`, `Viewer`), pencarian akun, toggle aktif/nonaktif, dan proteksi anti-hapus/nonaktif akun sendiri.
- **Style SMV Catalog & Multi-Process Route**: Konfigurasi Standard Minute Value (SMV) per style baju, pemetaan route proses kerja (Heat Transfer, Sublim, Direct Print, dll), toggle *Show in Dashboard*, dan opsi input manual *Direct Aktual*.
- **Kalender Kerja Visual**: Pengaturan hari kerja, libur, setengah hari (0.5), dan seperempat hari (0.25) yang berdampak langsung pada perhitungan kapasitas dan demand harian.
- **Analytics & Action Plan (CAP)**: Deteksi otomatis status risiko produksi, akurasi urutan data (data accuracy), dan rekomendasi Corrective Action Plan.
- **Auto-Refresh Sinkron Jam Dinding**: Pembaruan tampilan otomatis setiap **30 menit sekali** (tepat di menit `:00` dan `:30` setiap jam).
- **Pipeline RPA Terpadu**:
  - `dist/RPA_Master.exe`: Launcher standalone tanpa terminal.
  - Penjadwalan download semua RPA otomatis setiap **1,5 jam (90 menit)** dari pukul 07:00 s.d. 22:00 dan penutup 00:00.
  - Sinkronisasi transaksi Engage ke database MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`).
  - Otomasi Windows GUI IOS-APS via UNC network path server.
