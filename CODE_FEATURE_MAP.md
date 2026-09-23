# Code Feature Map

Dokumen ini adalah tanda/mapping fitur untuk kode custom project Dashboard GM. File bawaan framework CodeIgniter di `web/system` tidak dipetakan satu per satu karena fungsinya adalah core/vendor framework.

## Ringkasan Fitur

| Fitur | File utama | Output/hasil |
| --- | --- | --- |
| Portal GM (Landing & Login) | `web/application/controllers/Dashboard.php`, `web/application/views/dashboard_portal.php` | Halaman portal `/dashboard` dengan akses cepat ke Dashboard Publik dan modal login Admin |
| Dashboard Heat Transfer Publik | `web/application/controllers/Dashboard_heat.php`, `web/application/models/Dashboard_model.php`, `web/application/views/dashboards/heat/index.php` | Halaman monitoring `/dashboard_heat` dengan instant pre-hydration, toggle 1/2/4/6 delivery, chart, tabel, dan analytics |
| Panel Admin Terpadu GM | `web/application/controllers/Dashboard_heat.php`, `web/application/views/dashboard_admin.php` | Halaman admin `/dashboard_heat/admin` dengan sidebar navigasi (Overview, Summary, SMV & Direct Aktual, Kalender, Analytics, Data, Notes) |
| Manajemen Style SMV & Proses | `Dashboard_heat.php`, `Dashboard_model.php`, database MySQL tabel `dashboard_heat_style_smv` (sync: `dashboard_heat_style_smv.json`) | Konfigurasi Standard Minute Value (SMV), jumlah proses, dan nilai SMV tiap proses per style yang tersimpan di database |
| Kustomisasi Analytics & Bahasa | `Dashboard_heat.php`, `Dashboard_model.php`, `web/application/cache/dashboard_heat_analytics_settings.json` | Pemilihan kartu analytics (visible cards), pilihan bahasa (ID/EN), dan opsi Direct Actual |
| Data APS / JO Tracking | `rpa/aps-rpa/main.py`, `rpa/aps-rpa/config.json` | Unduh data JO otomatis dari server UNC `\\172.23.1.10\ios-aps\IOS-APS.exe` ke `rpa/aps-rpa/downloads/JO.xlsx` |
| Data Engage / Warehouse | `rpa/engage-rpa/main.py`, `rpa/engage-rpa/sync_engage_transactions.php`, `rpa/engage-rpa/sync_engage_daily_history.php` | Sinkronisasi ke MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`) dan backup file Excel |
| Data Accessories / Controlist | `rpa/accessories-rpa/main.py`, `rpa/accessories-rpa/config.json` | `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` |
| Material To Load & Ekspor Excel | `Dashboard_model.php`, `Dashboard_heat.php`, `dashboards/heat/index.php` | Tabel order aktif periode berjalan dan ekspor Excel dinamis dengan label periode aktif |
| Master Scheduler & Launcher | `dist/RPA_Master.exe`, `rpa/scheduler.py` | Launcher tunggal tanpa terminal dengan penjadwalan per 1,5 jam (90 menit) dari 07:00 s.d. 00:00 dan process lock |
| Kalender Kerja / Libur Heat | `Dashboard_heat.php`, `Dashboard_model.php`, `web/application/cache/dashboard_heat_holidays.json` | Simpan hari libur (0), setengah hari (0.5), seperempat hari (0.25), dan Minggu kerja (1) |
| Histori Demand & Qty Heat | `Dashboard_model.php`, database MySQL tabel `dashboard_heat_history` | Menyimpan riwayat demand dan kuantitas harian di database `db_dashboardgm` |
| Download File Sumber | `Dashboard_heat.php`, `Dashboard_model.php` | Endpoint `dashboard_heat/download?file=...` |

## Alur Data Utama

1. **Pengambilan Data Otomatis**:
   - `dist/RPA_Master.exe` (atau `rpa/scheduler.py`) menjalankan downloader secara berkala setiap **1,5 jam (90 menit)** mulai pukul 07:00 s.d. 22:00 dan run penutup 00:00.
   - `rpa/accessories-rpa/main.py` mengunduh `CONTROLIST.xlsx` dari CIUROX.
   - `rpa/engage-rpa/main.py` mengunduh report warehouse Engage lalu memicu helper PHP (`sync_engage_transactions.php` & `sync_engage_daily_history.php`) untuk melakukan upsert data ke database MySQL (`tb_engage_transactions`, `tb_engage_archieve`, `engage_daily_history`).
   - `rpa/aps-rpa/main.py` membuka aplikasi IOS-APS di UNC path server `\\172.23.1.10\ios-aps\IOS-APS.exe`, menangani popup security, memfilter periode delivery, dan mengekspor `rpa/aps-rpa/downloads/JO.xlsx`.
2. **Kalkulasi Model**:
   - `web/application/models/Dashboard_model.php` membaca data Excel APS & Accessories serta tabel database Engage dan riwayat `dashboard_heat_history`.
   - Mengkombinasikan data ke periode delivery aktif (1, 2, 4, atau 6 delivery), menghitung QTY PDK vs Output, Ready to Load, Demand harian, Style SMV catalog, dan Management Analytics.
3. **Penyajian Controller & Pre-Hydration**:
   - `web/application/controllers/Dashboard.php`: Menyajikan landing portal atau redirect.
   - `web/application/controllers/Dashboard_heat.php`: Menyajikan view publik dengan `initial_dashboard_payload` terintegrasi (menghilangkan delay rendering pada browser) atau view panel admin yang terautentikasi.
4. **Tampilan Web**:
   - `web/application/views/dashboards/heat/index.php`: Menampilkan visualisasi chart, tabel Material To Load, kartu analytics, dan modal interaktif.
   - `web/application/views/dashboard_admin.php`: Menyediakan manajemen Style SMV, kalender kerja, pengaturan kartu analytics, dan eksekusi download.

## Asal Data Per Komponen Dashboard Heat

### Target vs Aktual (sebelumnya QTY PDK vs QTY OUTPUT)

- Sumber: `JO.xlsx` dari APS + data output aktual dari database Engage.
- Dibentuk dari data periode aktif yang dihitung pada `selected_qty_pdk_vs_output`.
- Referensi fungsi: `build_heat_data_from_rpa_sources()`, `build_heat_data_from_source()`.

### Ready TO Production (sebelumnya READY TO LOAD PRODUCTION)

- Sumber: kombinasi APS, Engage, dan CIUROX Accessories (`CONTROLIST.xlsx`).
- Menampilkan grafik batang kelompok (grouped bar) per periode delivery:
  - **Completed** (bar biru): Kuantitas order ready (32A in - out) yang status pesanan aksesorisnya sudah `COMPLETED` di `CONTROLIST.xlsx`.
  - **Uncompleted** (bar hijau): Kuantitas order ready yang status pesanan aksesorisnya belum `COMPLETED` (misal masih `RECEIVED` atau belum tercatat).
- Nilai total `ready` tetap disimpan untuk kalkulasi cakupan hari (`coverage_days`), validasi akurasi data, dan analitik manajemen.
- Referensi fungsi: `build_heat_data_from_rpa_sources()`, `build_heat_data_from_source()`.

### KAPASITAS vs OUT vs IN

- Menampilkan perbandingan target kapasitas harian (Kapasitas = Balance Qty / Sisa Hari Kerja) dalam bentuk garis (*line*) merah terhadap realisasi batang (*bar*) Output harian dan Input Engage harian.
- Sumber utama: data harian Engage dan tabel database histori `dashboard_heat_history`.
- Referensi fungsi: `build_output_vs_capacity_from_engage_daily()`, `build_output_vs_capacity_from_rpa()`, `build_management_analytics()`.

### Material To Load / Ready To Load

- Sumber awal: daftar order dari APS atau source delivery, lalu dipadankan dengan data ready dan output Engage.
- Alur:
  - `build_list_orders_from_rpa()` atau `build_list_orders_from_source()` membentuk list order awal.
  - `filter_orders_for_active_period()` menyaring hanya periode yang sedang aktif (misal 1, 2, 4, atau 6 delivery).
  - Hasil akhirnya disimpan di `material_to_load`.
- Untuk dashboard saat ini, `top_priority_orders` diarahkan ke data aktif yang sama dengan `material_to_load`.
- Referensi fungsi: `build_list_orders_from_rpa()`, `build_list_orders_from_source()`, `filter_orders_for_active_period()`.

### Management Analytics & Action Plan

- Sumber: gabungan `qty_pdk_vs_output`, `ready_to_load`, `output_vs_capacity`, dan `top_priority_orders`.
- Hasilnya dipakai untuk kartu status, insight, dan action plan (CAP).
- Referensi fungsi: `build_management_analytics()`, `build_overall_condition()`, `build_data_accuracy()`, `build_management_action_plan()`.

## Web CodeIgniter

### `index.php`

Bootstrap CodeIgniter. Fitur: entry point aplikasi web lewat XAMPP/Apache.

### `web/application/config/routes.php`

Fitur routing:

- `default_controller = dashboard`: URL root diarahkan ke controller `Dashboard`.
- `translate_uri_dashes = FALSE`: nama route tidak otomatis mengubah dash menjadi underscore.

### `web/application/config/dashboard.php`

Fitur konfigurasi dashboard:

- Path alternatif file Excel dashboard Heat.
- Path UNC/share untuk data dashboard.
- Default kalender libur Heat.
- Username/password untuk edit Kalender Libur legacy (`admin`/`admin`).
- Tabel autentikasi portal user (`dashboard_portal_user_table = 'tbl_login'`).

### `web/application/config/database.php`

Fitur konfigurasi koneksi database:

- Grup database `default`: koneksi standar CodeIgniter.
- Grup database `dashboard_heat_history`: koneksi ke database `db_dashboardgm` di `localhost` (digunakan untuk tabel `dashboard_heat_history` dan autentikasi user portal).

### `web/application/controllers/Dashboard.php`

Fitur hub portal dashboard:

- `index()`: Memuat view `dashboard_portal` dengan URL ke Dashboard Publik (`dashboard_heat`), Admin (`dashboard_heat/admin`), serta endpoint login/logout portal.

### `web/application/controllers/Dashboard_base.php`

Fitur base controller:

- `__construct()`: load helper URL untuk controller dashboard.
- `json($payload, $status)`: helper response JSON standar untuk API.

### `web/application/controllers/Dashboard_heat.php`

Fitur controller Heat Transfer dan Admin Panel:

- `__construct()`: mulai session dan load `Dashboard_model`.
- `index()`: render halaman dashboard Heat dengan instant pre-hydration (`initial_dashboard_payload`), katalog Style SMV, dan pengaturan analytics.
- `admin()`: render halaman Admin Panel GM (`dashboard_admin`) terlindungi session login portal.
- `api($action)`: router internal untuk API:
  - `status`: status data dashboard, server time, dan session auth.
  - `run-download`: trigger eksekusi `dist/RPA_Master.exe --once`.
  - `save-workdays`: simpan konfigurasi hari libur dan kalender kerja.
  - `save-style-smv`: simpan nilai SMV per style dan toggle Direct Actual.
  - `save-analytics-settings`: simpan kartu analytics yang tampil, bahasa (ID/EN), dan Direct Actual.
  - `calendar-login` / `calendar-logout`: login/logout untuk modal kalender kerja.
  - `portal-login` / `portal-logout`: login/logout berbasis database untuk Admin Panel.
- `download_material_to_load()`: ekspor data tabel Material To Load ke format Excel (.xls HTML table) dengan menyertakan jumlah delivery (1, 2, 4, 6) dan label periode aktif.
- `download()`: unduh file Excel sumber langsung (`JO.xlsx`, `CONTROLIST.xlsx`, dll).

## Model Dashboard

### `web/application/models/Dashboard_model.php`

File ini adalah pusat data dan kalkulasi Dashboard Heat. Mapping fungsi berdasarkan fitur:

#### Path, Config, dan Cache

- `root_path()`, `rpa_root_path()`, `rpa_root_candidates()`, `data_dir()`, `data_file()`: menentukan lokasi root aplikasi dan root direktori sumber data Excel RPA (prioritas utama: `E:\xampp\htdocs\dashboard_gm\rpa` atau via config `dashboard_heat_rpa_dir`).
- `data_file_diagnostics()`, `data_file_candidates()`, `data_dir_candidates()`, `rpa_data_dirs()`: diagnosa kandidat path data dan folder downloads/archive RPA.
- `dashboard_config()`, `log_path()`: baca config dan path log `scheduler.log`.
- `heat_history_connection()`, `ensure_heat_history_table()`, `heat_history_table()`: koneksi database dan skema tabel `dashboard_heat_history` untuk histori demand/qty.
- `read_heat_capacity_history()`, `write_heat_capacity_history()`: membaca dan menyimpan riwayat demand ke database MySQL.
- `read_heat_qty_history()`, `write_heat_qty_history()`: membaca dan menyimpan riwayat qty ke database MySQL.
- `heat_style_smv_path()`: lokasi file cache JSON konfigurasi SMV (`web/application/cache/dashboard_heat_style_smv.json`).
- `heat_style_smv_table()`, `ensure_heat_style_smv_table()`: koneksi database dan skema tabel `dashboard_heat_style_smv` (`style`, `smv`, `process_count`, `process_smvs`, `show_in_dashboard`, `updated_at`).
- `heat_analytics_settings_path()`: lokasi file cache JSON pengaturan analytics (`web/application/cache/dashboard_heat_analytics_settings.json`).

#### Manajemen Style SMV (Standard Minute Value) & Proses

- `get_heat_saved_style_smv_settings()`: membaca data SMV dan proses dari database MySQL `dashboard_heat_style_smv`, dengan auto-fallback dan auto-migrasi dari cache JSON jika tabel kosong.
- `get_heat_style_smv_catalog($delivery_count, $list_orders, $top_priority_orders)`: membentuk katalog style gabungan dari order aktif dan data database/cache tersimpan, mencakup nilai SMV, jumlah proses, rincian SMV per proses, status `show_in_dashboard`, dan daftar `running_styles`.
- `save_heat_style_smv_settings($items)`: validasi dan penyimpanan batch nilai SMV serta proses per style ke database MySQL `dashboard_heat_style_smv` sekaligus sinkronisasi ke file cache JSON.

#### Manajemen Pengaturan Analytics

- `get_heat_analytics_settings()`: membaca preferensi kartu analytics yang ditampilkan (`visible_cards`), bahasa tampilan (`id` atau `en`), dan mode Direct Actual.
- `save_heat_analytics_settings($settings)`: menyimpan konfigurasi analytics, bahasa, dan direct actual ke file cache JSON.

#### Kalender Heat

- `heat_holidays_path()`: lokasi file kalender (`dashboard_heat_holidays.json`).
- `get_heat_holiday_settings()`: baca setting kalender libur/kerja.
- `save_heat_holiday_settings()`: simpan setting kalender libur/kerja.
- `normalize_calendar_dates()`: normalisasi daftar tanggal kalender.
- `dashboard_calendar_signature()`: signature cache berdasarkan kalender.
- `dashboard_holidays()`, `dashboard_calendar_days()`: baca hari libur dan hari kerja khusus.
- `build_delivery_workdays()`, `build_current_period_calendar()`, `build_period_calendar()`: hitung kalender per periode.
- `count_workdays()`, `calendar_workday_value()`, `date_range()`: utilitas hitung hari kerja.

#### Status, Logs, dan Download

- `get_report_status()`: status ketersediaan file/report.
- `read_recent_logs()`: baca baris log scheduler terbaru.
- `run_download_once()`: jalankan `dist/RPA_Master.exe --once` (atau fallback Python scheduler).
- `get_download_path()`: validasi path file yang boleh diunduh pengguna.

#### Pembacaan Report Excel/HTML

- `latest_report_path()`, `report_filename_candidates()`: cari file report terbaru.
- `read_html_report()`, `parse_table_rows()`: baca report format HTML.
- `is_xlsx_zip()`, `read_xlsx_report()`, `read_xlsx_sheet_grid()`: parser file `.xlsx` berbasis XML unzip native (tanpa library berat eksternal).
- `xlsx_sheet_path_by_name()`, `xlsx_sheet_name_for_hint()`, `xlsx_sheet_map()`, `first_xlsx_sheet_name()`: pemetaan sheet Excel.
- `read_xlsx_shared_strings()`, `xlsx_cell_value()`, `xlsx_column_index()`: parsing sel data string & angka Excel.
- `normalize_xlsx_report_rows()`, `xlsx_header_score()`, `combine_xlsx_group_headers()`: perapian header baris ganda Excel.

#### Sumber Data RPA

- `heat_rpa_sources()`: daftar sumber data utama (APS `JO.xlsx`, Accessories `CONTROLIST.xlsx`, Engage database/file).
- `latest_matching_file()`, `latest_existing_file()`, `latest_mtime_iso()`: deteksi waktu file sumber terbaru.
- `source_status_rows()`: ringkasan status kelengkapan data sumber.
- `read_combined_engage_outflow_report()`: pembacaan report outflow Engage.
- `engage_rpa_history_roots()`: lokasi arsip data histori Engage.
- `build_heat_data_from_rpa_sources()`: pipeline penggabungan APS, Engage, dan Accessories.
- `build_list_orders_from_rpa()`: pembuatan daftar order aktif.
- `filter_orders_for_active_period()`: penyaringan order sesuai periode delivery yang dipilih.

#### Dashboard Heat Data

- `get_dashboard_sheet()`: pembacaan sheet dashboard legacy.
- `get_heat_dashboard_data($delivery_count)`: titik masuk utama kalkulasi data Dashboard Heat.
- `get_heat_dashboard_data_from_rpa($delivery_count)`: agregasi data dari sumber RPA.
- `build_heat_data_from_database_delivery()`: pembentukan data dari delivery source database.
- `normalize_delivery_count($delivery_count)`: validasi batas periode delivery (mendukung nilai 1, 2, 4, dan 6).

#### Perhitungan Output, Balance, Ready, dan Demand

- `extract_qty_pdk_vs_output()`: kalkulasi perbandingan kuantitas PDK vs Output per periode.
- `extract_ready_to_load()`: kalkulasi jumlah ready to load per periode.
- `extract_output_vs_capacity()`: kalkulasi grafik harian output vs input vs demand.
- `build_output_vs_capacity_from_engage_daily()`: agregasi output harian dari tabel Engage.
- `build_output_vs_capacity_from_rpa()`: chart output/demand dari data RPA.
- `capacity_for_output_day()`: demand per tanggal output.
- `source_prod_days_left()`, `source_daily_capacity()`, `source_daily_capacity_detail()`: hitung sisa hari produksi dan demand harian.
- `selected_delivery_order_whitelist()`: daftar order yang diizinkan untuk periode aktif.
- `current_delivery_index()`: penentuan index periode aktif berdasarkan tanggal sistem.

#### Order, Period, dan Prioritas

- `normalize_order_number()`: standardisasi format nomor order / JO.
- `period_label_from_date_value()`, `period_label_from_excel_date()`, `format_period_label()`: pembuatan label periode (`MID <Bulan>`, `END <Bulan>`).
- `parse_date_timestamp()`, `excel_date_serial()`, `excel_serial_to_date()`, `excel_serial_to_timestamp()`: konversi tanggal dan serial Excel.
- `summarize_engage_rows_by_order()`: agregasi transaksi Engage per nomor order.
- `summarize_accessories_completed_orders()`: daftar order Accessories yang berstatus `COMPLETED`.
- `build_priority_orders_from_rpa()`, `build_priority_orders_from_source()`: pembentukan daftar order prioritas.
- `extract_top_priority_orders()`: seleksi urutan prioritas teratas.
- `is_heat_transfer_delivery()`, `is_database_out()`: filter validasi tipe Heat Transfer.
- `build_list_orders_from_rpa()`, `build_list_orders_from_source()`: pembentukan daftar order untuk tabel `Material To Load`.

#### Analytics Management & Action Plan

- `build_management_analytics()`: pembuatan seluruh metrik ringkasan analytics.
- `build_overall_condition()`: penentuan status kondisi operasional (`good`, `watch`, `risk`).
- `build_management_action_plan()`: pembentukan rekomendasi tindakan CAP (Corrective Action Plan).
- `build_data_accuracy()`: evaluasi kepatuhan urutan penyelesaian delivery periode lampau.
- `current_running_period()`, `previous_period_labels()`, `find_period_by_label()`: pelacakan periode aktif dan pembanding.
- `analytics_insight()`: generator teks insight otomatis untuk manajemen.

#### Helper Format dan Normalisasi

- `format_display_date()`, `format_output_day_label()`, `format_output_day_label_from_serial()`: format visual tanggal.
- `format_percent()`, `floor_decimal()`, `format_compact_number()`, `format_bytes()`: format angka, persentase, dan ukuran memori.
- `grid_header_column()`, `grid_value()`, `grid_cell()`, `cell()`, `cell_any()`: utilitas pembacaan matriks baris/kolom.
- `dedupe_grid_rows()`, `row_has_value()`, `trim_empty_grid()`: pembersihan data duplikat/kosong.
- `parse_number()`, `normalize()`, `sum_qty()`: normalisasi string ke angka numerik.
- `header_index()`, `column_letters_to_index()`: konversi huruf kolom Excel ke indeks array.

## Views Aplikasi Web

### 1. Portal GM (`web/application/views/dashboard_portal.php`)

Halaman portal landing GM yang ringan, elegan, dan mobile-friendly.

Fitur & Struktur:
- Kartu pengenal brand Dashboard GM.
- Tombol **Buka Dashboard Publik**: Navigasi langsung ke `/dashboard_heat` tanpa login.
- Tombol **Login Admin GM**: Membuka panel login AJAX.
- Form Login: Input username & password yang mengirim request POST ke endpoint `dashboard_heat/api/portal-login`.
- Penanganan status error atau sukses login secara visual tanpa reload halaman.

### 2. Dashboard Publik Heat Transfer (`web/application/views/dashboards/heat/index.php`)

Halaman utama monitoring Heat Transfer untuk operator, supervisor, dan manajemen.

Fitur Tampilan:
- **Instant Server-Side Pre-Hydration**: Membaca variabel `window.INITIAL_DASHBOARD_PAYLOAD` yang diinjeksi server pada tag script, merender data langsung tanpa menunggu AJAX awal.
- **Selector Periode Delivery (1, 2, 4, 6 Delivery)**: Tombol toggle periode delivery dengan penyimpanan pilihan di cookie `heatDeliveryCount`.
- **Top Bar Header**: Menampilkan ringkasan status operasional dan tombol pemicu `Run Download`.
- **Visualisasi Chart**:
  - `renderGroupedChart()`: Grafik batang ganda QTY PDK vs Output per periode.
  - `renderReadyChart()`: Grafik Ready To Load produksi.
  - `renderCapacityChart()`: Grafik harian perbandingan Output, Input, Demand, dan Gap/Surplus.
- **Tabel Material To Load**:
  - Menampilkan order aktif periode delivery berjalan.
  - Tombol **Download Excel**: Mengunduh data tabel dengan format tabel Excel yang memuat periode aktif.
- **Tabel Top Priority Orders**: Menampilkan order mendesak dengan delivery terdekat.
- **Kartu Analytics & Management Insight**:
  - Menampilkan kartu indikator performa yang dipilih secara dinamis.
  - Dukungan multi-bahasa (Bahasa Indonesia dan English).
  - Modal detail metrik dan rekomendasi Action Plan (CAP).
- **Modal Kalender Kerja**: Modal visual untuk mengatur hari libur, setengah hari, seperempat hari, dan hari kerja khusus (dengan autentikasi).
- **Auto-Refresh Sinkron Jam Dinding (Setiap 30 Menit)**: Fungsi `scheduleNextAlignedRefresh()` menjalankan fetch update data otomatis tepat di menit `:00` dan `:30` setiap jam secara sinkron dengan jam dinding.

### 3. Panel Admin Terpadu GM (`web/application/views/dashboard_admin.php`)

Halaman manajemen komprehensif bagi admin untuk mengendalikan konfigurasi sistem dan parameter produksi.

Fitur & Struktur:
- **Sidebar Navigasi Tetap**:
  - Menampilkan profil pengguna yang sedang login (`user.username`, `user.full_name`, `user.role`).
  - Tombol navigasi menu tab tanpa reload halaman.
  - Tombol **Buka Dashboard** dan **Logout**.
- **Section 1 - Overview (`overviewSection`)**:
  - Status koneksi data sumber (`JO.xlsx`, `CONTROLIST.xlsx`, Database Engage).
  - Kartu KPI ringkas (Total Output, Balance, Demand Harian, Active Orders).
  - Tombol eksekusi cepat: Run RPA Download, Buka Kalender, Buka Pengaturan Analytics.
- **Section 2 - Summary (`summarySection`)**:
  - Analisis mendalam performa produksi per periode delivery.
  - Tabel ringkasan pencapaian PDK vs Realisasi Output.
- **Section 3 - SMV & Direct Aktual (`style-smv`)**:
  - Tabel data Style SMV Catalog dengan pagination, search bar filter style, dan info counter.
  - Input angka Standard Minute Value (SMV) per style.
  - Checkbox toggle `Show in Dashboard` per style.
  - **Bulk Actions**: Tombol aktifkan semua / nonaktifkan semua style.
  - **Direct Aktual Mode Toggle**: Beralih antara input manual Direct Aktual atau rumus kalkulasi sistem.
  - Tombol **Simpan SMV**: Mengirimkan perubahan via AJAX ke `dashboard_heat/api/save-style-smv`.
- **Section 4 - Kalender Kerja (`workdays`)**:
  - Antarmuka visual kalender bulanan.
  - Klik tanggal untuk mengubah tipe hari: Kerja (1) -> Libur (0) -> Setengah Hari (0.5) -> Seperempat Hari (0.25).
  - Ringkasan otomatis total hari kerja per periode delivery.
  - Tombol **Simpan Kalender**: Mengirim data via AJAX ke `dashboard_heat/api/save-workdays`.
- **Section 5 - Analytics Settings (`analytics`)**:
  - Pemilihan kartu analytics aktif menggunakan grid kartu checkbox.
  - Pilihan bahasa tampilan: Bahasa Indonesia (`id`) atau English (`en`).
  - Tombol **Simpan Analytics Settings**: Mengirimkan preferensi ke `dashboard_heat/api/save-analytics-settings`.
- **Section 6 - Data Sources (`data`)**:
  - Tabel status rinci setiap sumber data (lokasi file/tabel, status ada/tidak, ukuran, waktu modifikasi).
  - Tombol download langsung file sumber untuk verifikasi data.
- **Section 7 - Catatan Operasional (`notes`)**:
  - Dokumentasi referensi definisi teknis, rumus perhitungan, dan acuan status risiko untuk tim operasional.

## RPA Master Scheduler & Launcher

### `dist/RPA_Master.exe`

Executable standalone yang dikompilasi dari Python master scheduler. Digunakan sebagai launcher tunggal di server Windows tanpa perlu dependensi command line terminal:
- Dijalankan langsung dengan klik ganda untuk mode continuous scheduler.
- Menerima argumen `--once` untuk menjalankan pipeline satu putaran lalu keluar:
  ```text
  dist/RPA_Master.exe --once
  ```

### `rpa/scheduler.py`

Skrip Python master scheduler:
- `log()`: penulisan log terpusat ke `rpa/logs/scheduler.log`.
- `acquire_process_lock()`, `release_process_lock()`: mekanisme locking via `rpa/logs/scheduler.lock` untuk mencegah eksekusi ganda.
- `run_rpa_script()`: menjalankan sub-skrip RPA dan meneruskan output stdout/stderr ke file log.
- `SCHEDULE_INTERVAL_MINUTES = 90`: interval eksekusi setiap 1,5 jam.
- `SCHEDULE_DAILY_TIMES`: jadwal tetap harian (07:00, 08:30, 10:00, 11:30, 13:00, 14:30, 16:00, 17:30, 19:00, 20:30, 22:00, dan 00:00).
- `get_engage_reference_date()`: penentuan tanggal acuan Engage (hari berjalan H, atau H-1 untuk run penutup pukul 00:00).
- `run_all_rpa_once()`: menjalankan Accessories, Engage, lalu APS secara berurutan.
- `get_next_run_time()`: menghitung jadwal eksekusi berikutnya berdasarkan slot tetap per 1,5 jam.
- `run_scheduler()`: loop utama scheduler dengan penanganan error dan recovery otomatis.
- `main()`: parser CLI `--once` atau continuous loop.

## Komponen RPA Downloader & Helper

### APS RPA

- `rpa/aps-rpa/config.json`: Konfigurasi otomasi GUI IOS-APS, mencakup path executable di UNC share (`\\\\172.23.1.10\\ios-aps\\IOS-APS.exe`), filter JO, langkah navigasi keyboard/mouse (klik username [795, 368], klik password [795, 402], klik Login [773, 497], klik export Excel [1129, 678]), deteksi Save As langsung atau jendela Microsoft Excel 2010 F12, penanganan popup keamanan/warning, dan konfirmasi popup keluar APS.
- `rpa/aps-rpa/main.py`: Runner otomasi Windows GUI menggunakan Win32 API, keyboard/mouse emulation, deteksi window dinamis, recovery dialog warning, dan ekspor `rpa/aps-rpa/downloads/JO.xlsx`.
- `rpa/aps-rpa/mouse_position.py`: Tool pembantu pengembang untuk memetakan koordinat layar.

### Engage RPA & Helper Database MySQL

- `rpa/engage-rpa/main.py`: Skrip Playwright untuk login ke sistem warehouse Engage, mengunduh report transaksi gabungan storage 32 & 32a, menyimpan cadangan Excel, dan memicu sinkronisasi MySQL.
- `rpa/engage-rpa/sync_engage_transactions.php`: Helper PHP yang melakukan upsert transaksi mentah ke tabel MySQL `tb_engage_transactions` (data berjalan) dan `tb_engage_archieve` (data arsip lama).
- `rpa/engage-rpa/sync_engage_daily_history.php`: Helper PHP untuk menghitung akumulasi harian input, output, dan ready qty ke tabel MySQL `engage_daily_history`.
- `rpa/engage-rpa/sync_excel_engage.py`: Skrip sinkronisasi cadangan file Excel Engage ke MySQL jika diperlukan sinkronisasi ulang manual.
- `rpa/engage-rpa/backfill_past_7_days.py`: Skrip pengisian ulang data historis 7 hari ke belakang ke dalam database.

### Accessories RPA

- `rpa/accessories-rpa/config.json`: Konfigurasi Playwright untuk CIUROX Accessories (URL login, form filter tanggal bulan berjalan, status `COMPLETED`).
- `rpa/accessories-rpa/main.py`: Runner Playwright otomatis untuk mengunduh `rpa/accessories-rpa/downloads/CONTROLIST.xlsx`.

## Ringkasan File Konfigurasi, Cache, Database, dan Log

| Path / Identifier | Tipe | Deskripsi & Fungsi |
| --- | --- | --- |
| `dist/RPA_Master.exe` | Executable | Launcher tunggal master scheduler RPA |
| `rpa/logs/scheduler.log` | Log File | Catatan eksekusi pipeline scheduler dan RPA |
| `rpa/logs/scheduler.lock` | Lock File | Mencegah konflik eksekusi proses simultan |
| `rpa/aps-rpa/logs/` | Folder Log | Catatan rinci proses otomasi APS GUI |
| `rpa/aps-rpa/screenshots/` | Folder Screenshot | Gambar tangkapan layar saat debug atau error APS |
| `rpa/aps-rpa/downloads/JO.xlsx` | Data Excel | Data order, style, PDK, dan delivery date dari APS |
| `rpa/engage-rpa/downloads/` | Folder Download | File cadangan Excel Engage (`32_engage.xlsx`, `32a_engage.xlsx`) |
| `tb_engage_transactions` | MySQL Table | Tabel data transaksi berjalan Engage di database `db_dashboardgm` |
| `tb_engage_archieve` | MySQL Table | Tabel arsip transaksi lampau Engage |
| `engage_daily_history` | MySQL Table | Tabel ringkasan harian input, output, ready Engage |
| `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` | Data Excel | Data status order Accessories yang telah `COMPLETED` |
| `dashboard_heat_history` | MySQL Table | Tabel riwayat demand harian dan kuantitas di database `db_dashboardgm` |
| `tbl_login` (atau `dashboard_portal_users`) | MySQL Table | Tabel user terautentikasi untuk akses Admin Panel GM |
| `web/application/cache/dashboard_heat_style_smv.json` | JSON Cache | Konfigurasi nilai Standard Minute Value (SMV) per style |
| `web/application/cache/dashboard_heat_analytics_settings.json` | JSON Cache | Pengaturan kartu analytics, preferensi bahasa, dan direct actual |
| `web/application/cache/dashboard_heat_holidays.json` | JSON Cache | Konfigurasi kalender kerja, hari libur, dan hari kerja khusus |

## Panduan Modifikasi & Troubleshooting

- **Mengubah Tampilan Dashboard Publik**: Buka `web/application/views/dashboards/heat/index.php`.
- **Mengubah Fitur & Panel Admin**: Buka `web/application/views/dashboard_admin.php`.
- **Mengubah Tampilan Portal Landing**: Buka `web/application/views/dashboard_portal.php`.
- **Mengubah Rumus, Metrik, atau Sumber Data**: Buka `web/application/models/Dashboard_model.php`.
- **Menambah / Mengubah Endpoint API**: Buka `web/application/controllers/Dashboard_heat.php`.
- **Mengatur Kredensial atau Tabel User**: Buka `web/application/config/dashboard.php`.
- **Menyesuaikan Langkah Otomasi APS**: Buka `rpa/aps-rpa/config.json` dan `rpa/aps-rpa/main.py`.
- **Menyesuaikan Jadwal Downloader**: Buka `rpa/scheduler.py`.
- **Jika Angka Dashboard Terlihat Tidak Berubah**: Periksa status database MySQL, waktu pembaruan file di folder downloads, serta log pada `rpa/logs/scheduler.log`.
