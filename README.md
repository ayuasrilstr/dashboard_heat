# Dashboard GM

Sistem Dashboard Produksi Internal Berbasis Web (PHP CodeIgniter 3) yang terintegrasi dengan Pipeline Otomasi RPA (Python, Playwright, Windows GUI Automation) dan Database MySQL.

---

## Navigasi Dokumentasi Lengkap

1. **[README_CI3_DASHBOARD.md](README_CI3_DASHBOARD.md)**:
   - Panduan instalasi dan arsitektur sistem.
   - Dokumentasi lengkap modul Dashboard Heat Transfer, Portal GM, dan Panel Admin.
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
| **Portal GM** | `http://localhost/dashboard_gm/` atau `/index.php/dashboard` | Gerbang landing page utama & login modal admin |
| **Dashboard Heat Transfer** | `http://localhost/dashboard_gm/index.php/dashboard_heat` | Dashboard monitoring publik tanpa login |
| **Admin Panel GM** | `http://localhost/dashboard_gm/index.php/dashboard_heat/admin` | Panel manajemen terpadu (SMV, Kalender, Analytics, Data) |

---

## Ringkasan Fitur Utama

- **Instant Pre-Hydration**: Dashboard publik memuat data seketika tanpa delay blank page menggunakan payload terinjeksi server.
- **Pilihan Periode Delivery (1, 2, 4, 6 Delivery)**: Fleksibilitas memilih cakupan monitoring periode dengan persistensi cookie.
- **Style SMV Catalog & Direct Aktual**: Pengaturan Standard Minute Value (SMV) per style dan toggle Direct Aktual pada Panel Admin.
- **Kalender Kerja Visual**: Pengaturan hari kerja, libur, setengah hari, dan seperempat hari yang langsung berdampak pada perhitungan demand harian.
- **Analytics & Action Plan (CAP)**: Deteksi otomatis status risiko produksi, akurasi sequence data, dan rekomendasi penanganan masalah.
- **Auto-Refresh Sinkron Jam Dinding**: Pembaruan tampilan dashboard otomatis setiap **30 menit sekali** (pada menit `:00` dan `:30` setiap jam).
- **Pipeline RPA Terpadu**:
  - `dist/RPA_Master.exe`: Launcher standalone tanpa terminal.
  - Penjadwalan download semua RPA otomatis setiap **1,5 jam (90 menit)** dari pukul 07:00 s.d. 22:00 dan penutup 00:00.
  - Sinkronisasi otomatis transaksi Engage ke database MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`).
  - Otomasi Windows GUI IOS-APS via UNC network path.
