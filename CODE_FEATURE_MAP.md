# Code Feature Map

Dokumen ini adalah tanda/mapping fitur untuk kode custom project Dashboard GM. File bawaan framework CodeIgniter di `web/system` tidak dipetakan satu per satu karena fungsinya adalah core/vendor framework.

## Ringkasan Fitur

| Fitur | File utama | Output/hasil |
| --- | --- | --- |
| Portal GM (Landing & Login) | `web/application/controllers/Dashboard.php`, `web/application/views/dashboard_portal.php` | Halaman portal `/dashboard` dengan akses cepat ke Dashboard Publik dan modal login Admin |
| Dashboard Heat Transfer Publik | `web/application/controllers/Dashboard_heat.php`, `web/application/models/Dashboard_model.php`, `web/application/views/dashboards/heat/index.php` | Halaman monitoring `/dashboard_heat` dengan instant pre-hydration, filter tanggal dinamis (`From`-`To`), toggle 1/2/4/6 delivery, chart hourly/daily, tabel, dan analytics |
| Panel Admin Terpadu GM | `web/application/controllers/Dashboard_heat.php`, `web/application/views/dashboard_admin.php` | Halaman admin `/dashboard_heat/admin` dengan sidebar navigasi (Overview, Summary, SMV & Direct Aktual, Kalender, Analytics, Data Prioritas, Notes, Kelola Akun) |
| Manajemen Akun Pengguna (User CRUD) | `Dashboard_heat.php` (`api_users`, `api_save_user`, `api_delete_user`, `api_toggle_user_status`), `dashboard_admin.php`, database MySQL `tbl_login` | Kelola akun pengguna admin portal (tambah, edit, hapus, toggle aktif/nonaktif) dengan 4 role (`admin`, `planning`, `production`, `viewer`) dan proteksi keamanan akun |
| Filter Rentang Tanggal Dinamis | `Dashboard_heat.php` (`resolve_date_range`), `Dashboard_model.php`, `views/dashboards/heat/index.php`, `views/dashboard_admin.php` | Kontrol kalender tanggal (`From` & `To`) dengan default otomatis MID (01-15) atau END (16-akhir bulan) serta persistensi cookie |
| Tracking Output & Kapasitas Jam-jamanan | `Dashboard_model.php` (`build_today_hourly_output_vs_capacity`), `dashboards/heat/index.php` | Visualisasi grafik *KAPASITAS vs OUT vs IN* berbasis jam kerja hari ini (07:00 s.d. 16:00+) terhadap kapasitas per jam, dengan auto-fallback ke grafik harian |
| Perhitungan Jam Kerja Dinamis | `Dashboard_model.php` (`is_saturday_workday`, `working_hours_weekday`, `working_hours_saturday`) | Penyesuaian jam kerja: Sabtu Kerja = Weekday 7 jam & Sabtu 5 jam; Sabtu Libur = Weekday 8 jam & Sabtu 0 jam |
| Buffer Hari Persiapan Export Bertingkat | `Dashboard_model.php` (`export_prep_workdays`) | Pengurangan buffer hari kerja sebelum delivery export: 1 delivery = 4 hari, 2 delivery = 8 hari, 4+ delivery = 14 hari |
| Manajemen Style SMV & Multi-Proses | `Dashboard_heat.php`, `Dashboard_model.php`, database MySQL `dashboard_heat_style_smv` (sync: `dashboard_heat_style_smv.json`) | Konfigurasi Standard Minute Value (SMV), pemetaan route/proses (Heat Transfer, Sublim, Direct Print, dll), jumlah proses, rincian SMV tiap proses, dan Direct Aktual |
| Kustomisasi Analytics & Bahasa | `Dashboard_heat.php`, `Dashboard_model.php`, `web/application/cache/dashboard_heat_analytics_settings.json` | Pemilihan kartu analytics (visible cards), pilihan bahasa (ID/EN), dan opsi Direct Actual |
| Data APS / JO Tracking | `rpa/aps-rpa/main.py`, `rpa/aps-rpa/config.json` | Unduh data JO otomatis dari server UNC `\\172.23.1.10\ios-aps\IOS-APS.exe` ke `rpa/aps-rpa/downloads/JO.xlsx` |
| Data Engage / Warehouse & Sinkronisasi DB | `rpa/engage-rpa/main.py`, `sync_engage_transactions.php`, `sync_engage_daily_history.php`, `sync_excel_engage.py`, `backfill_past_7_days.py` | Sinkronisasi ke MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`), pencadangan file Excel, dan script utilitas perbaikan/backfill data |
| Data Accessories / Controlist | `rpa/accessories-rpa/main.py`, `rpa/accessories-rpa/config.json` | Unduh data pesanan aksesoris berstatus completed ke `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` |
| Material To Load & Ekspor Excel | `Dashboard_model.php`, `Dashboard_heat.php`, `dashboards/heat/index.php` | Tabel order aktif periode berjalan dengan kolom route/proses dan ekspor Excel dinamis dengan label rentang periode aktif |
| Master Scheduler & Standalone Launcher | `dist/RPA_Master.exe`, `rpa/scheduler.py` | Launcher tunggal tanpa terminal dengan penjadwalan per 1,5 jam (90 menit) dari 07:00 s.d. 00:00 dan process lock |
| Kalender Kerja / Libur Heat | `Dashboard_heat.php`, `Dashboard_model.php`, `web/application/cache/dashboard_heat_holidays.json` | Simpan hari libur (0), setengah hari (0.5), seperempat hari (0.25), dan Minggu kerja (1) |
| Histori Demand & Qty Heat | `Dashboard_model.php`, database MySQL tabel `dashboard_heat_history` | Menyimpan riwayat demand dan kuantitas harian di database `db_dashboardgm` |
| Download File Sumber Langsung | `Dashboard_heat.php`, `Dashboard_model.php` | Endpoint `dashboard_heat/download?file=...` untuk verifikasi data sumber mentah |

## Alur Data Utama

1. **Pengambilan Data Otomatis**:
   - `dist/RPA_Master.exe` (atau `rpa/scheduler.py`) menjalankan downloader secara berkala setiap **1,5 jam (90 menit)** mulai pukul 07:00 s.d. 22:00 dan run penutup 00:00.
   - `rpa/accessories-rpa/main.py` mengunduh `CONTROLIST.xlsx` dari CIUROX.
   - `rpa/engage-rpa/main.py` mengunduh report warehouse Engage lalu memicu helper PHP (`sync_engage_transactions.php` & `sync_engage_daily_history.php`) untuk melakukan upsert data ke database MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`). Jika dibutuhkan rekonsiliasi cadangan Excel, tersedia `sync_excel_engage.py` dan `backfill_past_7_days.py`.
   - `rpa/aps-rpa/main.py` membuka aplikasi IOS-APS di UNC path server `\\172.23.1.10\ios-aps\IOS-APS.exe`, menangani popup security, memfilter periode delivery, dan mengekspor `rpa/aps-rpa/downloads/JO.xlsx`.
2. **Kalkulasi Model**:
   - `web/application/models/Dashboard_model.php` membaca data Excel APS & Accessories serta tabel database Engage dan riwayat `dashboard_heat_history`.
   - Mengkombinasikan data sesuai filter rentang tanggal aktif (`date_from` s.d. `date_to`), menghitung QTY PDK vs Output, Ready to Load, Demand harian/jam-jamanan, Style SMV catalog, dan Management Analytics.
3. **Penyajian Controller & Pre-Hydration**:
   - `web/application/controllers/Dashboard.php`: Menyajikan landing portal atau redirect.
   - `web/application/controllers/Dashboard_heat.php`: Menyelesaikan rentang tanggal aktif (`resolve_date_range()`), menyajikan view publik dengan `initial_dashboard_payload` terintegrasi (menghilangkan delay rendering pada browser), atau view panel admin yang terautentikasi.
4. **Tampilan Web**:
   - `web/application/views/dashboards/heat/index.php`: Menampilkan filter rentang tanggal, visualisasi chart harian/hourly, tabel Material To Load dengan rute proses, kartu analytics, dan modal interaktif.
   - `web/application/views/dashboard_admin.php`: Menyediakan manajemen Akun Pengguna, Style SMV & Multi-Proses, kalender kerja, pengaturan kartu analytics, dan diagnostik data.

## Asal Data Per Komponen Dashboard Heat

### Target vs Aktual (PDK vs Output)

- Sumber: `JO.xlsx` dari APS + data output aktual dari database Engage (`tb_engage_transactions` / `tb_engage_archieve`).
- Dibentuk dari data periode aktif yang dihitung pada `selected_qty_pdk_vs_output`.
- Referensi fungsi: `build_heat_data_from_rpa_sources()`, `build_heat_data_from_source()`.

### Ready TO Production

- Sumber: kombinasi APS, Engage, dan CIUROX Accessories (`CONTROLIST.xlsx`).
- Menampilkan grafik batang kelompok (grouped bar) per periode delivery:
  - **Completed** (bar biru): Kuantitas order ready (32A in - out) yang status pesanan aksesorisnya sudah `COMPLETED` di `CONTROLIST.xlsx`.
  - **Uncompleted** (bar hijau): Kuantitas order ready yang status pesanan aksesorisnya belum `COMPLETED` (misal masih `RECEIVED` atau belum tercatat).
- Nilai total `ready` tetap disimpan untuk kalkulasi cakupan hari (`coverage_days`), validasi akurasi data, dan analitik manajemen.
- Referensi fungsi: `build_heat_data_from_rpa_sources()`, `build_heat_data_from_source()`.

### KAPASITAS vs OUT vs IN (Daily & Hourly)

- **Mode Harian**: Menampilkan perbandingan target kapasitas harian (Kapasitas = Balance Qty / Sisa Hari Kerja) dalam bentuk garis (*line*) merah terhadap realisasi batang (*bar*) Output harian dan Input Engage harian.
- **Mode Jam-jamanan (Hourly)**: Jika data transaksi hari ini memiliki timestamp jam, grafik otomatis menampilkan rincian per jam kerja (07:00, 08:00, ..., 16:00) terhadap target kapasitas per jam (`hourly_capacity = daily_capacity / working_hours`).
- Sumber utama: data transaksi Engage dan tabel database histori `dashboard_heat_history`.
- Referensi fungsi: `build_today_hourly_output_vs_capacity()`, `build_output_vs_capacity_from_engage_daily()`, `build_output_vs_capacity_from_rpa()`.

### Material To Load / Ready To Load

- Sumber awal: daftar order dari APS atau source delivery, lalu dipadankan dengan data ready dan output Engage.
- Memetakan rute / proses spesifik (`process` / `route`) dari APS (misal Heat Transfer, Sublim, Direct Print).
- Alur:
  - `build_list_orders_from_rpa()` atau `build_list_orders_from_source()` membentuk list order awal.
  - `filter_orders_for_active_period()` menyaring hanya periode yang sedang aktif sesuai filter tanggal / delivery.
  - Hasil akhirnya disimpan di `material_to_load`.
- Referensi fungsi: `build_list_orders_from_rpa()`, `build_list_orders_from_source()`, `filter_orders_for_active_period()`.

### Management Analytics & Action Plan

- Sumber: gabungan `qty_pdk_vs_output`, `ready_to_load`, `output_vs_capacity`, dan `top_priority_orders`.
- Hasilnya dipakai untuk kartu status, insight, dan action plan (CAP).
- Referensi fungsi: `build_management_analytics()`, `build_overall_condition()`, `build_data_accuracy()`, `build_management_action_plan()`.

## Web CodeIgniter

### `index.php`

Bootstrap CodeIgniter. Entry point aplikasi web lewat Apache/XAMPP.

### `web/application/config/routes.php`

Fitur routing:
- `default_controller = dashboard`: URL root diarahkan ke controller `Dashboard`.
- `translate_uri_dashes = FALSE`: nama route tidak otomatis mengubah dash menjadi underscore.

### `web/application/config/dashboard.php`

Fitur konfigurasi dashboard:
- `dashboard_heat_rpa_dir`: Direktori root sumber data Excel RPA (`E:\xampp\htdocs\dashboard_gm\rpa`).
- `dashboard_heat_excel_file`, `dashboard_heat_unc_excel_file`: Path alternatif file Excel dashboard Heat.
- `dashboard_heat_data_dir`, `dashboard_heat_unc_dir`: Path direktori data lokal dan UNC.
- `dashboard_heat_holidays`: Default tanggal libur bawaan.
- `dashboard_heat_calendar_user`, `dashboard_heat_calendar_password`: Kredensial untuk edit Kalender Libur legacy.
- `dashboard_portal_user_table`: Nama tabel autentikasi pengguna portal di database (`tbl_login`).

### `web/application/config/database.php`

Fitur konfigurasi koneksi database:
- Grup database `default`: koneksi standar CodeIgniter.
- Grup database `dashboard_heat_history`: koneksi ke database `db_dashboardgm` di `localhost` (digunakan untuk tabel `dashboard_heat_history`, `tbl_login`, `tb_engage_transactions`, `tb_engage_archieve`, dan `engage_daily_history`).

### `web/application/controllers/Dashboard.php`

Fitur hub portal dashboard:
- `index()`: Memuat view `dashboard_portal` dengan navigasi ke Dashboard Publik (`dashboard_heat`), Admin Panel (`dashboard_heat/admin`), serta form login portal.

### `web/application/controllers/Dashboard_base.php`

Fitur base controller:
- `__construct()`: load helper URL untuk controller dashboard.
- `json($payload, $status)`: helper response JSON standar untuk API.

### `web/application/controllers/Dashboard_heat.php`

Fitur controller Heat Transfer dan Admin Panel:
- `__construct()`: mulai session dan load `Dashboard_model`.
- `index()`: render halaman dashboard publik dengan filter tanggal dinamis, instant pre-hydration (`initial_dashboard_payload`), katalog Style SMV, dan pengaturan analytics.
- `admin()`: render halaman Admin Panel GM (`dashboard_admin`) terlindungi session login portal.
- `resolve_date_range()`: fungsi helper untuk menentukan rentang tanggal (`from` dan `to`) dari parameter query URL, cookie (`heatDateFrom`, `heatDateTo`, `heatSelectedDate`), atau default otomatis MID (01-15) / END (16-akhir bulan).
- `api($action)`: router internal untuk API:
  - `status`: status data dashboard, server time, session auth, dan payload kalkulasi terkini.
  - `qty-history`: riwayat demand dan kuantitas harian dari database.
  - `run-download`: trigger eksekusi `dist/RPA_Master.exe --once`.
  - `save-workdays`: simpan konfigurasi hari libur dan kalender kerja.
  - `save-style-smv`: simpan nilai SMV per style, multi-proses SMV, dan toggle Direct Actual.
  - `save-analytics-settings`: simpan kartu analytics yang tampil, bahasa (ID/EN), dan Direct Actual.
  - `calendar-login` / `calendar-logout`: login/logout untuk modal kalender kerja.
  - `portal-login` / `portal-logout`: login/logout berbasis database untuk Admin Panel GM.
  - `users`: ambil daftar akun pengguna untuk panel kelola akun (`api_users`).
  - `save-user`: buat akun baru atau perbarui akun pengguna (`api_save_user`).
  - `delete-user`: hapus akun pengguna dengan proteksi anti-hapus akun sendiri (`api_delete_user`).
  - `toggle-user-status`: aktifkan/nonaktifkan akun pengguna dengan proteksi akun sendiri (`api_toggle_user_status`).
- `download_material_to_load()`: ekspor data tabel Material To Load ke format Excel (.xls HTML table) dengan menyertakan rentang tanggal aktif dan rincian proses.
- `download()`: unduh file Excel sumber langsung (`JO.xlsx`, `CONTROLIST.xlsx`, dll).

## Model Dashboard

### `web/application/models/Dashboard_model.php`

Pusat data dan kalkulasi utama Dashboard Heat. Mapping fungsi berdasarkan modul:

#### Path, Config, dan Cache
- `root_path()`, `rpa_root_path()`, `rpa_root_candidates()`, `data_dir()`, `data_file()`: menentukan lokasi root aplikasi dan root direktori sumber data Excel RPA (`E:\xampp\htdocs\dashboard_gm\rpa` atau via config `dashboard_heat_rpa_dir`).
- `data_file_diagnostics()`, `data_file_candidates()`, `data_dir_candidates()`, `rpa_data_dirs()`: diagnosa kandidat path data dan folder downloads/archive RPA.
- `dashboard_config()`, `log_path()`: baca konfigurasi dan path log `scheduler.log`.
- `heat_history_connection()`, `ensure_heat_history_table()`, `heat_history_table()`: koneksi database dan skema tabel `dashboard_heat_history`.
- `read_heat_capacity_history()`, `write_heat_capacity_history()`: baca dan simpan riwayat demand ke MySQL.
- `read_heat_qty_history()`, `write_heat_qty_history()`: baca dan simpan riwayat kuantitas ke MySQL.
- `heat_style_smv_path()`, `heat_style_smv_table()`, `ensure_heat_style_smv_table()`: cache JSON dan skema tabel database `dashboard_heat_style_smv`.
- `heat_analytics_settings_path()`: lokasi file cache JSON pengaturan analytics.

#### Manajemen Style SMV & Multi-Proses
- `get_heat_saved_style_smv_settings()`: membaca data SMV dan proses dari MySQL `dashboard_heat_style_smv` dengan auto-fallback ke cache JSON.
- `get_heat_style_smv_catalog($selected_date_from, $list_orders, $top_priority_orders)`: membentuk katalog style gabungan dari order aktif dan database SMV tersimpan, mencakup SMV total, jumlah proses, rincian SMV per proses, status `show_in_dashboard`, dan daftar `running_styles`.
- `save_heat_style_smv_settings($items)`: simpan batch nilai SMV serta rincian proses per style ke database MySQL dan sinkronisasi ke file cache JSON.

#### Manajemen Pengaturan Analytics
- `get_heat_analytics_settings()`: membaca preferensi kartu analytics aktif (`visible_cards`), bahasa tampilan (`id` atau `en`), dan mode Direct Actual.
- `save_heat_analytics_settings($settings)`: menyimpan konfigurasi analytics ke file cache JSON.

#### Kalender Kerja & Jam Kerja Dinamis
- `heat_holidays_path()`: lokasi file kalender (`dashboard_heat_holidays.json`).
- `get_heat_holiday_settings()`, `save_heat_holiday_settings()`: baca dan simpan pengaturan kalender kerja.
- `normalize_calendar_dates()`: standardisasi daftar tanggal kalender.
- `dashboard_holidays()`, `dashboard_calendar_days()`: baca hari libur, setengah hari, seperempat hari, dan hari kerja Minggu.
- `is_saturday_workday($calendar_days, $date)`: memeriksa apakah hari Sabtu di minggu tersebut adalah hari kerja aktif atau libur.
- Logika jam kerja operasional:
  - Jika Sabtu adalah hari kerja: `working_hours_weekday = 7` jam, `working_hours_saturday = 5` jam.
  - Jika Sabtu adalah hari libur: `working_hours_weekday = 8` jam, `working_hours_saturday = 0` jam.
- `count_workdays()`, `calendar_workday_value()`, `date_range()`: utilitas hitung jumlah hari kerja dalam rentang tanggal.

#### Buffer Persiapan Export & Sisa Hari Kerja
- `export_prep_workdays($delivery_count)`: menghitung buffer hari kerja persiapan export secara berjenjang:
  - 1 Delivery: **4 hari kerja**
  - 2 Delivery: **8 hari kerja**
  - 4+ Delivery: **14 hari kerja**
- `remaining_delivery_workdays($qty_rows, $as_of_date)`: menghitung total sisa hari kalender (`remaining_days`), buffer export (`export_prep_days`), dan sisa hari kerja produksi efektif (`sisa_hari_kerja = max(0, remaining_days - buffer_export)`).
- `source_prod_days_left()`: pembulatan sisa hari kerja produksi.

#### Pembacaan Data RPA & Parsing File
- `heat_rpa_sources()`: daftar sumber data utama (APS `JO.xlsx`, Accessories `CONTROLIST.xlsx`, Engage database/file).
- `has_engage_db_data()`: memeriksa ketersediaan data transaksi di database MySQL Engage.
- `read_html_report()`, `parse_table_rows()`: parser report format HTML dari APS dan Accessories.
- `is_xlsx_zip()`, `read_xlsx_report()`, `read_xlsx_sheet_grid()`: parser file `.xlsx` native unzip tanpa library eksternal yang lambat.
- `format_excel_date()`, `format_excel_datetime()`: konversi dan pemformatan tanggal serial Excel ke format teks standar (`dd MMM yyyy`).

#### Kalkulasi Dashboard Heat & Mode Hourly
- `get_heat_dashboard_data($date_from, $date_to)`: titik masuk utama pembentukan payload data Dashboard Heat berdasarkan filter rentang tanggal.
- `get_heat_dashboard_data_from_rpa($date_from, $date_to)`: agregasi data dari APS, Engage, dan Accessories.
- `extract_qty_pdk_vs_output()`: kalkulasi perbandingan kuantitas PDK vs Output per periode delivery.
- `extract_ready_to_load()`: kalkulasi jumlah ready to load (completed vs uncompleted accessories).
- `build_today_hourly_output_vs_capacity($day, $in_hourly, $out_hourly, $daily_output_vs_capacity)`: menyusun data grafik jam-jamanan hari ini (07:00, 08:00, ..., 16:00) terhadap kapasitas per jam (`hourly_capacity = round(daily_capacity / day_working_hours)`).
- `build_output_vs_capacity_from_engage_daily()`: agregasi grafik harian dari tabel transaksi Engage jika data jam-jamanan belum tersedia.
- `summarize_engage_rows_by_order()`: meringkas transaksi Engage per order, per tanggal, dan per jam (`hourly_materials`).
- `build_list_orders_from_rpa()`, `build_list_orders_from_source()`: menyusun daftar order Material To Load lengkap dengan kolom `process` / `route` dan status ready.
- `filter_orders_for_active_period()`: menyaring daftar order sesuai periode tanggal aktif.

#### Analytics Management & Action Plan (CAP)
- `build_management_analytics()`: menghasilkan kumpulan metrik analytics (achievement, status, coverage, gap/surplus, issue sequence).
- `build_overall_condition()`: menentukan kondisi operasional (`good`, `watch`, `risk`) berdasarkan akumulasi risk points dan sisa hari export.
- `build_management_action_plan()`: menyusun rekomendasi Corrective Action Plan (CAP) lengkap dengan masalah, akar penyebab, pencegahan (*prevention*), dan penanganan (*handling*).
- `build_data_accuracy()`: evaluasi sequence penyelesaian delivery periode terdahulu.

## Views Aplikasi Web

### 1. Portal GM (`web/application/views/dashboard_portal.php`)

Landing page gerbang utama GM yang ringan dan responsif:
- Navigasi cepat: Tombol **Buka Dashboard Publik** langsung ke `/dashboard_heat`.
- Modal Login Admin: Form username & password dengan pengiriman request AJAX POST ke `dashboard_heat/api/portal-login`.
- Penanganan error login dan auto-redirect ke Panel Admin tanpa reload halaman penuh.

### 2. Dashboard Publik Heat Transfer (`web/application/views/dashboards/heat/index.php`)

Halaman utama monitoring Heat Transfer:
- **Instant Server-Side Pre-Hydration**: Membaca variabel `window.INITIAL_DASHBOARD_PAYLOAD` yang diinjeksi server pada tag script, merender data langsung tanpa menunggu AJAX awal.
- **Filter Rentang Tanggal Dinamis**: Kontrol tanggal interaktif (`#dashboardDateFrom` & `#dashboardDateTo`) dan tombol `#btnDateRefresh` pada top bar header. Default tanggal otomatis menyesuaikan paruh bulan (MID/END).
- **Selector Periode Delivery (1, 2, 4, 6 Delivery)**: Tombol toggle alternatif untuk memilih jumlah periode delivery dengan persistensi cookie `heatDeliveryCount`.
- **Period Pill & Header Info**: Badge penanda periode aktif berjalan (`MID` atau `END`).
- **Visualisasi Chart Interaktif**:
  - `Target vs Aktual`: Grafik batang ganda QTY PDK vs Output per periode.
  - `Ready TO Production`: Grafik batang kelompok Completed vs Uncompleted Accessories.
  - `KAPASITAS vs OUT vs IN`: Grafik perbandingan kapasitas terhadap realisasi Output dan Input, mendukung tampilan **Hourly Tracking** (per jam kerja) dan mode harian.
- **Tabel Material To Load**:
  - Menampilkan daftar order aktif lengkap dengan kolom **Route / Process**, Style, Delivery Date, Periode, PDK, Output, Balance, dan Status Ready.
  - Tombol **Download Excel**: Mengunduh tabel dalam format Excel dengan menyertakan rentang tanggal aktif.
- **Tabel Top Priority Orders**: Menampilkan order mendesak dengan delivery date terdekat.
- **Kartu Analytics & Action Plan (CAP)**: Kartu metrik performa dinamis, dukungan dwibahasa (ID/EN), dan modal rekomendasi tindakan perbaikan.
- **Modal Kalender Kerja**: Antarmuka visual untuk mengatur hari libur, setengah hari, seperempat hari, dan Minggu kerja.
- **Auto-Refresh Sinkron Jam Dinding**: Menjadwalkan reload data otomatis setiap **30 menit sekali** (tepat di menit `:00` dan `:30` setiap jam).

### 3. Panel Admin Terpadu GM (`web/application/views/dashboard_admin.php`)

Halaman kontrol terpusat bagi supervisor dan administrator sistem:
- **Sidebar Navigasi**: Menu navigasi cepat dengan indikator profil akun yang sedang login.
- **Section 1 - Overview (`overviewSection`)**:
  - Status koneksi data sumber (`JO.xlsx`, `CONTROLIST.xlsx`, Database Engage).
  - Kartu KPI ringkas (Total Output, Balance, Demand Harian, Active Orders).
  - Shortcut aksi: Run RPA Download, Buka Kalender, Buka Pengaturan Analytics.
- **Section 2 - Summary (`summarySection`)**:
  - Analisis mendalam performa per periode delivery dan tabel pencapaian PDK vs Output.
- **Section 3 - SMV & Direct (`style-smv`)**:
  - Tabel Style SMV Catalog dengan pagination, search bar filter, dan bulk actions.
  - Input Standard Minute Value (SMV) per style dan konfigurasi multi-proses (rincian SMV tiap tahap).
  - Checkbox toggle *Show in Dashboard* dan toggle mode *Direct Aktual*.
- **Section 4 - Kalender Kerja (`workdays`)**:
  - Antarmuka kalender bulanan interaktif (Kerja = 1, Libur = 0, Setengah Hari = 0.5, Seperempat Hari = 0.25).
  - Ringkasan total hari kerja per periode delivery.
- **Section 5 - Analytics Settings (`analytics`)**:
  - Pemilihan kartu analytics yang aktif (visible cards) dan pemilihan bahasa tampilan (ID/EN).
- **Section 6 - Data Sources (`data`)**:
  - Monitoring status file data sumber (lokasi, keberadaan, ukuran, waktu modifikasi) dan tombol download langsung.
- **Section 7 - Catatan Operasional (`notes`)**:
  - Dokumentasi acuan definisi teknis dan rumus operasional dashboard.
- **Section 8 - Kelola Akun Pengguna (`usersSection`)**:
  - Tabel daftar semua akun pengguna di database `tbl_login`.
  - Search bar filter username dan nama lengkap.
  - Modal form Tambah Akun dan Edit Akun (`userModal`).
  - Pemilihan role pengguna: `admin`, `planning`, `production`, `viewer`.
  - Toggle switch status aktif/nonaktif akun (dengan proteksi anti-nonaktifkan akun sendiri).
  - Tombol aksi Edit dan Hapus akun (dengan proteksi anti-hapus akun sendiri).

## RPA Master Scheduler & Launcher

### `dist/RPA_Master.exe`

Executable standalone yang dikompilasi dari Python master scheduler. Digunakan sebagai launcher tunggal di server Windows tanpa dependensi command line terminal:
- Klik ganda untuk menjalankan continuous loop scheduler.
- Argumen `--once` untuk menjalankan satu putaran pipeline lalu keluar:
  ```text
  dist/RPA_Master.exe --once
  ```

### `rpa/scheduler.py`

Skrip Python master scheduler:
- `log()`: penulisan log terpusat ke `rpa/logs/scheduler.log`.
- `acquire_process_lock()`, `release_process_lock()`: mekanisme locking via `rpa/logs/scheduler.lock` untuk mencegah eksekusi ganda.
- `run_rpa_script()`: menjalankan sub-skrip RPA dan meneruskan output stdout/stderr ke file log.
- `SCHEDULE_INTERVAL_MINUTES = 90`: interval eksekusi otomatis setiap 1,5 jam.
- `SCHEDULE_DAILY_TIMES`: jadwal tetap harian (`07:00`, `08:30`, `10:00`, `11:30`, `13:00`, `14:30`, `16:00`, `17:30`, `19:00`, `20:30`, `22:00`, dan `00:00`).
- `get_engage_reference_date()`: penentuan tanggal acuan Engage (hari berjalan H, atau H-1 untuk run penutup pukul 00:00).
- `run_all_rpa_once()`: menjalankan Accessories, Engage, lalu APS secara berurutan.
- `get_next_run_time()`: menghitung jadwal eksekusi berikutnya berdasarkan slot tetap per 1,5 jam.

## Komponen RPA Downloader & Helper

### APS RPA

- `rpa/aps-rpa/config.json`: Konfigurasi otomasi GUI IOS-APS, mencakup path executable di UNC share (`\\\\172.23.1.10\\ios-aps\\IOS-APS.exe`), filter JO, langkah navigasi keyboard/mouse (klik username [795, 368], klik password [795, 402], klik Login [773, 497], klik export Excel [1129, 678]), deteksi Save As langsung atau jendela Microsoft Excel 2010 F12, penanganan popup keamanan/warning, dan konfirmasi popup keluar APS.
- `rpa/aps-rpa/main.py`: Runner otomasi Windows GUI menggunakan Win32 API, keyboard/mouse emulation, deteksi window dinamis, recovery dialog warning, dan ekspor `rpa/aps-rpa/downloads/JO.xlsx`.
- `rpa/aps-rpa/mouse_position.py`: Tool pembantu pengembang untuk memetakan koordinat layar.

### Engage RPA & Helper Database MySQL

- `rpa/engage-rpa/main.py`: Skrip Playwright untuk login ke sistem warehouse Engage, mengunduh report transaksi gabungan storage 32 & 32a, menyimpan cadangan Excel, dan memicu sinkronisasi MySQL.
- `rpa/engage-rpa/sync_engage_transactions.php`: Helper PHP yang melakukan upsert transaksi mentah ke tabel MySQL `tb_engage_transactions` (data berjalan) dan `tb_engage_archieve` (data arsip lama).
- `rpa/engage-rpa/sync_engage_daily_history.php`: Helper PHP untuk menghitung akumulasi harian input, output, dan ready qty ke tabel MySQL `engage_daily_history`.
- `rpa/engage-rpa/sync_excel_engage.py`: Skrip utilitas Python untuk membaca cadangan file Excel Engage dan menyinkronkan kembali transaksi ke MySQL secara manual.
- `rpa/engage-rpa/backfill_past_7_days.py`: Skrip utilitas Python untuk mengisi ulang data transaksi Engage 7 hari ke belakang ke dalam database.

### Accessories RPA

- `rpa/accessories-rpa/config.json`: Konfigurasi Playwright untuk CIUROX Accessories (URL login, form filter tanggal bulan berjalan, status `COMPLETED`).
- `rpa/accessories-rpa/main.py`: Runner Playwright otomatis untuk mengunduh `rpa/accessories-rpa/downloads/CONTROLIST.xlsx`.

## Ringkasan File Konfigurasi, Cache, Database, dan Log

| Path / Identifier | Tipe | Deskripsi & Fungsi |
| --- | --- | --- |
| `dist/RPA_Master.exe` | Executable | Launcher tunggal master scheduler RPA tanpa terminal |
| `rpa/logs/scheduler.log` | Log File | Catatan eksekusi pipeline scheduler dan RPA |
| `rpa/logs/scheduler.lock` | Lock File | Mencegah konflik eksekusi proses simultan |
| `rpa/aps-rpa/downloads/JO.xlsx` | Data Excel | Data order, style, PDK, rute proses, dan delivery date dari APS |
| `rpa/engage-rpa/downloads/` | Folder Download | File cadangan Excel Engage (`32_engage.xlsx`, `32a_engage.xlsx`) |
| `tb_engage_transactions` | MySQL Table | Tabel transaksi berjalan Engage di database `db_dashboardgm` |
| `tb_engage_archieve` | MySQL Table | Tabel arsip transaksi lampau Engage |
| `engage_daily_history` | MySQL Table | Tabel ringkasan harian input, output, ready Engage |
| `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` | Data Excel | Data status order Accessories yang telah `COMPLETED` |
| `dashboard_heat_history` | MySQL Table | Tabel riwayat demand harian dan kuantitas di database `db_dashboardgm` |
| `tbl_login` | MySQL Table | Tabel akun pengguna terautentikasi untuk akses Admin Panel GM |
| `dashboard_heat_style_smv` | MySQL Table | Tabel nilai Standard Minute Value (SMV) dan rincian proses per style |
| `web/application/cache/dashboard_heat_style_smv.json` | JSON Cache | Cache lokal konfigurasi nilai SMV per style |
| `web/application/cache/dashboard_heat_analytics_settings.json` | JSON Cache | Pengaturan kartu analytics, preferensi bahasa, dan direct actual |
| `web/application/cache/dashboard_heat_holidays.json` | JSON Cache | Pengaturan kalender kerja, hari libur, dan hari kerja khusus |

## Panduan Modifikasi & Troubleshooting

- **Mengubah Tampilan Dashboard Publik**: Buka `web/application/views/dashboards/heat/index.php`.
- **Mengubah Fitur & Panel Admin**: Buka `web/application/views/dashboard_admin.php`.
- **Mengubah Tampilan Portal Landing**: Buka `web/application/views/dashboard_portal.php`.
- **Mengubah Rumus, Metrik, Jam Kerja, atau Sumber Data**: Buka `web/application/models/Dashboard_model.php`.
- **Menambah / Mengubah Endpoint API atau Kelola User**: Buka `web/application/controllers/Dashboard_heat.php`.
- **Mengatur Kredensial atau Tabel User Portal**: Buka `web/application/config/dashboard.php`.
- **Menyesuaikan Langkah Otomasi APS**: Buka `rpa/aps-rpa/config.json` dan `rpa/aps-rpa/main.py`.
- **Menyesuaikan Jadwal Downloader**: Buka `rpa/scheduler.py`.
- **Jika Angka Dashboard Terlihat Tidak Berubah**: Periksa status database MySQL, waktu pembaruan file di folder downloads, serta log pada `rpa/logs/scheduler.log`.
