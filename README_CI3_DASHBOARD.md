# Dashboard GM

Dashboard GM adalah aplikasi dashboard internal berbasis web untuk memantau data produksi, terutama modul **Heat Transfer**. Web ini membaca hasil download RPA dari APS, Engage, dan Accessories, lalu menggabungkannya menjadi indikator dashboard seperti output, balance, ready to load, demand harian, tracking jam-jamanan (hourly), dan prioritas order.

## Dibuat menggunakan

- **PHP CodeIgniter 3** untuk backend MVC.
- **HTML, CSS, dan JavaScript (Vanilla JS & Chart.js)** untuk tampilan dashboard interaktif.
- **MySQL (`db_dashboardgm`)** untuk penyimpanan data transaksi Engage, riwayat kapasitas/demand, akun pengguna, dan konfigurasi SMV.
- **Python** untuk RPA downloader dan master scheduler.
- **Playwright Python** untuk RPA berbasis browser seperti Engage dan Accessories.
- **Windows GUI Automation** untuk RPA APS melalui aplikasi IOS-APS di UNC share server.
- **XAMPP/Apache** sebagai web server lokal.
- **Excel `.xlsx`** sebagai media pertukaran data dari RPA ke dashboard.

## Cara Pasang dan Akses URL

1. Pastikan server Apache & MySQL telah aktif di XAMPP.
2. Web CodeIgniter berada di `web/application` dan `web/system`.
3. Database utama dashboard berada di MySQL `db_dashboardgm`.
4. Akses URL:
   - **Portal GM**: `http://localhost/dashboard_gm/index.php/dashboard` (atau root `http://localhost/dashboard_gm/`)
   - **Dashboard Heat Transfer**: `http://localhost/dashboard_gm/index.php/dashboard_heat`
   - **Admin Panel GM**: `http://localhost/dashboard_gm/index.php/dashboard_heat/admin`

## File Utama

- `web/application/controllers/Dashboard.php`: Landing page portal GM dan perutean akses Dashboard / Admin.
- `web/application/controllers/Dashboard_base.php`: Base controller dashboard (helper URL, response JSON API).
- `web/application/controllers/Dashboard_heat.php`: Controller utama modul Heat Transfer, admin panel, filter rentang tanggal, manajemen user, dan endpoint API (status, SMV, analytics settings, workdays, users, download).
- `web/application/models/Dashboard_model.php`: Core data logic, parser APS & Accessories, koneksi MySQL Engage/History, kalkulasi hourly tracking, jam kerja dinamis, buffer export bertingkat, SMV, kalender, dan analytics.
- `web/application/views/dashboard_portal.php`: View landing portal GM dan form login admin modal.
- `web/application/views/dashboard_admin.php`: View panel admin terpadu (Overview, Summary, SMV & Direct Aktual, Kalender, Analytics, Data Sources, Notes, Kelola Akun).
- `web/application/views/dashboards/heat/index.php`: View utama Dashboard Heat Transfer dengan instant server-side pre-hydration, filter tanggal dinamis, dan chart hourly/daily.
- `web/application/config/dashboard.php`: Konfigurasi direktori root RPA (`dashboard_heat_rpa_dir`), tabel user portal (`tbl_login`), dan kredensial kalender.
- `web/application/config/database.php`: Konfigurasi koneksi database MySQL (`dashboard_heat_history` pada `db_dashboardgm`).
- `web/application/cache/dashboard_heat_style_smv.json`: Cache konfigurasi nilai SMV per style.
- `web/application/cache/dashboard_heat_analytics_settings.json`: Cache konfigurasi kartu analytics, bahasa, dan direct actual.
- `web/application/cache/dashboard_heat_holidays.json`: Cache konfigurasi hari libur dan kalender kerja.
- `dist/RPA_Master.exe`: Launcher standalone pipeline RPA tanpa terminal.

Mapping detail fitur per file dan fungsi terdokumentasi lengkap di [CODE_FEATURE_MAP.md](CODE_FEATURE_MAP.md).

## Pola Modul Dashboard

Untuk menambahkan modul baru di kemudian hari, ikuti pola pembuatan controller, model, dan view terpisah:

```text
web/application/controllers/Dashboard_nama_bagian.php
web/application/models/Dashboard_nama_bagian_model.php
web/application/views/dashboards/nama_bagian/index.php
web/application/views/analytics/nama_bagian/index.php
```

Contoh URL rencana ekspansi:
```text
/dashboard_heat
/dashboard_cutting
/dashboard_sewing
```

Tombol `Run Download` pada dashboard memicu master scheduler:
```text
dist/RPA_Master.exe --once
```

Scheduler menjalankan RPA secara berurutan:
1. Accessories RPA
2. Engage RPA
3. APS RPA

## Sumber Data Heat

```text
rpa/engage-rpa        -> DATABASE / output aktual (MySQL tb_engage_transactions, tb_engage_archieve, engage_daily_history)
rpa/aps-rpa           -> DELIVERY / data JO APS (JO.xlsx)
rpa/accessories-rpa   -> CONTROLIST / data Accessories (CONTROLIST.xlsx)
```

## Cara Pengambilan Data

### Accessories

Accessories RPA login ke CIUROX, membuka halaman Accessories GM, mengisi filter tanggal dan status `COMPLETED`, lalu export Controlist.

Output file:
```text
rpa/accessories-rpa/downloads/CONTROLIST.xlsx
```
File ini dibuat tetap (overwrite) agar download berikutnya menimpa data lama tanpa memenuhi kapasitas penyimpanan.

### Engage & Database Sinkronisasi

Engage RPA login ke portal warehouse Engage via Playwright browser, membuka menu report warehouse, lalu mengambil data transaksi untuk storage `32` dan `32a` dengan `direction = 0` (arah gabungan inflow & outflow). Pada eksekusi otomatis scheduler, data ditarik berdasarkan tanggal acuan (`reference_date`, yaitu hari berjalan H atau H-1 untuk run penutup tengah malam).

Setelah report ditarik, Engage RPA menyimpan file cadangan Excel (`32_engage.xlsx` dan `32a_engage.xlsx`) di folder `rpa/engage-rpa/downloads/`, lalu otomatis memicu helper script PHP untuk sinkronisasi database MySQL:
- `sync_engage_transactions.php`: Melakukan upsert data transaksi mentah ke tabel `tb_engage_transactions` (untuk hari berjalan dan mendatang) dan `tb_engage_archieve` (arsip lampau dengan retensi 90 hari).
- `sync_engage_daily_history.php`: Menghitung dan menyimpan ringkasan harian input, output, dan ready quantity ke tabel `engage_daily_history`.
- Script cadangan/pemulihan:
  - `sync_excel_engage.py`: Skrip utilitas Python untuk sinkronisasi cadangan file Excel Engage ke MySQL secara manual.
  - `backfill_past_7_days.py`: Skrip utilitas Python untuk mengisi ulang data transaksi Engage 7 hari ke belakang ke dalam database.

### APS

APS RPA membuka aplikasi IOS-APS dari network share server `\\172.23.1.10\ios-aps\IOS-APS.exe` (atau shortcut lokal), menangani dialog peringatan keamanan Windows, melakukan login otomatis (username, password, dan klik tombol Login), membuka menu JO Tracking Report, mengisi filter rentang Delivery Date dari awal bulan lalu sampai akhir 2 bulan ke depan, merefresh data dengan deteksi stabilitas layar serta penanganan popup otomatis, lalu mengekspor hasil ke Excel.

File disimpan ke:
```text
rpa/aps-rpa/downloads/JO.xlsx
```

RPA mampu menangani penyimpanan langsung dari dialog Save As APS maupun Save As (F12) melalui jendela Microsoft Excel 2010, lalu menutup spreadsheet dan aplikasi APS secara bersih.

---

## Fitur Aplikasi

### 1. Portal GM (`/dashboard`)

Halaman gerbang utama (landing portal) yang bersih dan responsif:
- **Akses Cepat Dashboard Publik**: Langsung membuka Dashboard Heat Transfer tanpa login.
- **Login Admin Modal**: Akses terautentikasi ke Panel Admin GM dengan memvalidasi username dan password ke tabel database `tbl_login`.
- **Session Protected**: Pengguna admin yang telah login diarahkan ke `/dashboard_heat/admin`.

---

### 2. Dashboard Heat Transfer (`/dashboard_heat`)

Halaman monitoring produksi utama yang didesain interaktif dan modern:

- **Instant Pre-Hydration (Fast Load)**: Data awal disuntikkan langsung oleh controller via server-side JSON payload (`initial_dashboard_payload`). Halaman langsung terisi data seketika saat dibuka tanpa delay blank page.
- **Filter Rentang Tanggal Dinamis (`From` - `To`)**:
  - Input kalender interaktif pada top bar header (`#dashboardDateFrom` dan `#dashboardDateTo`) beserta tombol `#btnDateRefresh`.
  - Default otomatis cerdas:
    - Jika tanggal hari ini <= 15: Otomatis memilih tanggal 01 s.d. 15 bulan berjalan (**MID**).
    - Jika tanggal hari ini > 15: Otomatis memilih tanggal 16 s.d. akhir bulan berjalan (**END**).
  - Pilihan rentang tanggal disimpan di browser via cookie `heatDateFrom` dan `heatDateTo`.
- **Selector Periode Delivery (1, 2, 4, 6 Delivery)**: Tombol alternatif untuk memilih cakupan monitoring periode dengan cookie `heatDeliveryCount`.
- **Period Pill**: Badge visual yang menandai periode aktif (`MID` atau `END`).
- **Kartu Ringkasan KPI**:
  - `Total Output`: Akumulasi output aktual dari database Engage.
  - `Balance Qty`: Sisa kuantitas yang belum terpenuhi pada periode aktif.
  - `Balance Breakdown`: Distribusi balance per periode delivery.
- **Visualisasi Grafik Interaktif**:
  - `Target vs Aktual`: Perbandingan kuantitas rencana (PDK) dari APS terhadap realisasi output Engage.
  - `Ready TO Production`: Grafik batang kelompok yang memisahkan material siap produksi berdasarkan kesiapan aksesoris (**Completed** dan **Uncompleted**) per periode delivery.
  - `KAPASITAS vs OUT vs IN`:
    - **Mode Jam-jamanan (Hourly)**: Jika data transaksi hari ini memiliki timestamp jam, grafik menyajikan breakdown jam kerja hari ini (07:00 s.d. 16:00+) terhadap garis target kapasitas per jam.
    - **Mode Harian**: Grafik harian perbandingan Output, Input, dan Demand/Kapasitas jika data jam-jamanan belum tersedia.
- **Tabel Material To Load & Export Excel**:
  - Menampilkan daftar order aktif untuk periode yang dipilih, lengkap dengan kolom **Route / Process** (Heat Transfer, Sublim, Direct Print, dll), Style, Delivery Date, Periode, PDK, Output, Balance, dan Status Ready.
  - Tombol **Export Excel** yang mengunduh data dalam format Excel tabel dengan header rentang tanggal aktif.
- **Top Priority Orders**: Daftar order mendesak yang diprioritaskan berdasarkan tanggal delivery terdekat.
- **Monitoring Analytics & Action Plan (CAP)**:
  - Kartu indikator performa: `Production Status`, `Output Achievement`, `Data Accuracy`, `Ready Coverage`, dll.
  - Insight manajemen otomatis dan Action Plan rekomendasi penanganan kendala produksi (CAP).
- **Auto-Refresh Sinkron Jam Dinding (Setiap 30 Menit)**: Pembaruan data otomatis setiap **30 menit sekali** tepat pada menit `:00` dan `:30` setiap jam (sinkron jam dinding) melalui `scheduleNextAlignedRefresh()`.
- **Status Sinkronisasi & Run Download**: Menampilkan status file data dan tombol pemicu eksekusi scheduler RPA secara on-demand.

---

### 3. Panel Admin Terpadu GM (`/dashboard_heat/admin`)

Halaman kontrol terpusat bagi supervisor dan manajemen untuk mengatur parameter operasional dan konfigurasi sistem. Memerlukan login portal.

Navigasi Sidebar Admin Panel:
1. **Overview**: Ringkasan performa real-time, status data source, dan shortcut aksi penting.
2. **Summary**: Analisis metrik output, balance, demand harian, dan pencapaian target produksi.
3. **SMV & Direct (Style SMV Catalog)**:
   - **Style SMV**: Mengatur nilai Standard Minute Value (SMV) per nomor style produk.
   - **Multi-Proses SMV**: Pengaturan jumlah tahapan proses kerja dan rincian nilai SMV pada setiap proses.
   - **Show in Dashboard Toggle**: Menentukan style mana saja yang akan ditampilkan sebagai running styles di dashboard.
   - **Bulk Actions**: Tombol aktifkan atau nonaktifkan semua style sekaligus.
   - **Direct Aktual Mode**: Toggle untuk menggunakan nilai input manual Direct Aktual atau rumus kalkulasi sistem.
4. **Kalender Kerja**:
   - Pengaturan hari kerja dan hari libur secara visual.
   - Mendukung tipe: **Work Day** (1), **Holiday** (0), **Half Day** (0.5), **Quarter Day** (0.25), dan **Minggu Kerja**.
5. **Analytics Settings**:
   - Pemilihan kartu analytics yang aktif ditampilkan di dashboard publik.
   - Pengaturan bahasa tampilan: **Bahasa Indonesia (`id`)** atau **English (`en`)**.
6. **Data Sources**:
   - Monitoring ketersediaan, ukuran, dan timestamp file `JO.xlsx`, `CONTROLIST.xlsx`, serta tabel MySQL Engage.
   - Tombol unduh langsung untuk file Excel sumber.
7. **Catatan Operasional**: Dokumentasi teknis internal mengenai rumus dan ambang batas metrik operasional.
8. **Kelola Akun Pengguna (`usersSection`)**:
   - Antarmuka manajemen pengguna (User CRUD) terhubung ke database `tbl_login`.
   - Menampilkan tabel akun: No, Username, Nama Lengkap, Role, Status, Terakhir Login, dan Aksi.
   - Modal form **Tambah Akun Baru** dan **Edit Akun**.
   - Pilihan 4 tingkatan hak akses / role:
     - `Admin`: Akses penuh ke seluruh fitur dan konfigurasi sistem.
     - `Planning`: Akses pengelolaan SMV, kalender kerja, dan analisis perencanaan.
     - `Production`: Akses pemantauan produksi dan status operasional.
     - `Viewer`: Akses melihat data tanpa izin modifikasi konfigurasi.
   - Search bar real-time untuk memfilter akun berdasarkan username atau nama.
   - Toggle switch untuk mengaktifkan / menonaktifkan status akun.
   - Proteksi keamanan: Pengguna tidak dapat menghapus atau menonaktifkan akun miliknya sendiri yang sedang aktif login.

---

### 4. Master RPA Scheduler & Standalone Launcher (`dist/RPA_Master.exe`)

Untuk memastikan kestabilan dan kemudahan operasional di server Windows:
- **Executable Mandiri**: Dikompilasi ke `dist/RPA_Master.exe`. Cukup dijalankan dengan klik ganda tanpa terminal.
- **Penjadwalan Otomatis per 1,5 Jam (90 Menit)**:
  - Downloader RPA (Accessories, Engage, dan APS) dijalankan berurutan setiap **1,5 jam sekali (90 menit)** mulai pukul **07:00 hingga 22:00** dan ditutup pada pukul **00:00** (tengah malam).
  - Slot waktu harian: `07:00`, `08:30`, `10:00`, `11:30`, `13:00`, `14:30`, `16:00`, `17:30`, `19:00`, `20:30`, `22:00`, dan `00:00`.
- **Eksekusi Sekali (On-Demand)**:
  ```text
  dist/RPA_Master.exe --once
  ```
  Opsi ini juga dipanggil secara otomatis oleh tombol `Run Download` pada dashboard web.
- **Process Lock**: Dilengkapi file lock `rpa/logs/scheduler.lock` agar proses scheduler tidak berjalan tumpang tindih jika eksekusi sebelumnya belum selesai.

---

## Struktur Data Dashboard

Model `Dashboard_model.php` menyatukan data menjadi struktur utama berikut:

| Field JSON | Isi | Asal Utama |
| --- | --- | --- |
| `kpis.total_output` | Total output produksi | Engage Outflow (Database MySQL) |
| `kpis.balance_qty` | Total balance dari periode dipilih | APS JO dan output Engage |
| `qty_pdk_vs_output` | PDK vs output per periode | APS JO + Engage Outflow |
| `ready_to_load` | Qty ready per periode | APS JO + Engage/Accessories |
| `output_vs_capacity` | Output, input, capacity harian/hourly | Engage Outflow + Inflow + kalkulasi capacity |
| `list_orders` | Daftar order Material To Load lengkap dengan rute proses | APS JO + Engage |
| `top_priority_orders` | Order prioritas delivery terdekat | APS JO + ready/output |
| `sources` | Status file & database sumber data | Folder downloads & MySQL |
| `delivery_workdays` | Kalender kerja tiap periode | Kalender dashboard |
| `management_analytics` | Metrics, status, insight, detail, CAP | Hasil kalkulasi dashboard |

---

## Rumus dan Perhitungan Utama

### 1. Target vs Aktual (QTY PDK vs Output)

Periode delivery dibentuk dari delivery date APS:
- Tanggal 1 s.d. 15 -> `MID <Bulan>`
- Tanggal 16 s.d. akhir bulan -> `END <Bulan>`

Rumus per periode:
```text
QTY PDK = total kuantitas rencana (PDK) dari APS pada periode tersebut
QTY Output = total output Engage yang cocok dengan order/periode tersebut
Balance = max(0, QTY PDK - QTY Output)
```

### 2. Jam Kerja Operasional Dinamis

Sistem secara adaptif memeriksa apakah hari Sabtu pada minggu/periode tersebut merupakan hari kerja aktif atau hari libur (`is_saturday_workday`):
- **Jika Sabtu adalah Hari Kerja**:
  - Hari Biasa (Senin - Jumat): **7 jam kerja/hari**
  - Hari Sabtu: **5 jam kerja/hari**
- **Jika Sabtu adalah Hari Libur**:
  - Hari Biasa (Senin - Jumat): **8 jam kerja/hari**
  - Hari Sabtu: **0 jam kerja/hari**

### 3. Tracking Jam-jamanan (Hourly Capacity & Output)

Pada hari berjalan, target kapasitas dihitung per jam kerja:
```text
Target Kapasitas Per Jam = round(Daily Capacity / Jam Kerja Hari Ini)
```
Grafik *KAPASITAS vs OUT vs IN* menampilkan batang output aktual per jam (pukul 07:00, 08:00, ..., 16:00) terhadap garis target kapasitas per jam. Jika data transaksi hari ini belum memiliki rincian jam, grafik otomatis beralih ke tampilan harian.

### 4. Buffer Persiapan Export Bertingkat

Sebelum pesanan dikirim (export), dialokasikan buffer hari kerja persiapan secara bertingkat:
- **1 Delivery**: `4 hari kerja`
- **2 Delivery**: `8 hari kerja`
- **4+ Delivery**: `14 hari kerja`

Rumus sisa hari kerja produksi efektif:
```text
Sisa Hari Kerja = max(0, Total Hari Kerja Sisa - Buffer Hari Export)
```

### 5. Total Output

```text
Total Output = total output dari data Engage 32a Outflow
```
Nilai ini ditampilkan di kartu KPI utama dan dipakai untuk evaluasi Output Achievement.

### 6. Balance Qty

```text
Balance Qty = total QTY PDK - total QTY Output
```

### 7. Ready To Load

```text
Ready To Load = qty order/periode yang sudah ready (inflow - outflow) berdasarkan data Engage dan status Accessories (Completed vs Uncompleted)
```

### 8. Demand Harian

```text
Daily Demand = Balance Qty / Sisa Hari Kerja Produksi Efektif
```

### 9. Avg Daily Output

```text
Avg Daily Output = total output harian / jumlah hari yang memiliki transaksi output
```

### 10. Avg Daily Demand

```text
Avg Daily Demand = total demand harian / jumlah hari yang memiliki demand
```

### 11. Demand Gap / Surplus

```text
Demand Gap = total demand - total daily output
```
- Jika positif: masih ada kekurangan target (gap).
- Jika negatif: realisasi melebihi demand (surplus).

### 12. Ready Coverage

```text
Ready Coverage Days = Total Ready Load / Avg Daily Demand
```

Ambang batas status:
| Nilai | Status |
| --- | --- |
| `>= 10 hari` | `good` |
| `>= 5` dan `< 10 hari` | `watch` |
| `< 5 hari` | `risk` |

### 13. Required Daily Output

```text
Required Daily Output = balance periode berjalan / sisa hari kerja export
```

Ambang batas status:
| Kondisi | Status |
| --- | --- |
| `Avg Daily Output >= Required Daily Output` | `good` |
| `Avg Daily Output >= Required Daily Output * 0.9` | `watch` |
| Di bawah itu | `risk` |

### 14. Output Achievement

```text
Output Achievement = (Total Output Aktual / Total Target PDK) * 100%
```

Ambang batas status:
| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 15. Data Accuracy

Memeriksa apakah 2 periode delivery lampau sudah diselesaikan sebelum periode berjalan diproses. Jika masih ada sisa balance atau ready di periode lampau, dihitung sebagai anomali urutan (*sequence issue*).

```text
Data Accuracy Score = max(0, 100 - (Jumlah Periode Bermasalah * 20))
```

Ambang batas status:
| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 16. Critical Orders

Dihitung dari order prioritas yang sisa hari kerjanya `<= 5 hari kerja`.
- Tidak ada critical order -> `good`
- Terdapat critical order -> `risk`

### 17. Source Sync

```text
Source Sync = jumlah source file/db yang tersedia / total source yang dimonitor
```

### 18. Monitoring Coverage

Menampilkan lingkup modul operasional yang dipantau (APS JO Tracking, Engage 32a Inflow/Outflow, Accessories Controlist, Dashboard Analytics).

### 19. Data Update

Menampilkan waktu pembaruan terakhir dari seluruh sumber data.

---

## Status Keseluruhan Produksi

Backend mengevaluasi kondisi operasional melalui `Dashboard_model::build_overall_condition()` dengan memperhitungkan risk points dan watch points:

| Kondisi Evaluasi | Dampak Poin |
| --- | --- |
| `achievement_rate < 75%` | +1 Risk |
| `achievement_rate >= 75%` dan `< 90%` | +1 Watch |
| `ready_coverage_days < 5` | +1 Risk |
| `ready_coverage_days >= 5` dan `< 10` | +1 Watch |
| `sisa hari kerja export > 11` | +3 Risk |
| `sisa hari kerja export >= 5` dan `<= 11` | +2 Watch |
| `sisa hari kerja <= 0` dan balance masih ada | +1 Risk |
| `avg_daily_capacity < required_daily_output` | +1 Risk |
| `avg_daily_output < required_daily_output` | +1 Watch |
| `critical_orders > 0` | +1 Risk |
| `data_accuracy.score < 75` | +1 Risk |
| `data_accuracy.score >= 75` dan `< 90` | +1 Watch |

Keputusan status keseluruhan:
- `risk` (**High Risk / At Risk**): Jika risk points >= 3 atau sisa hari kerja export > 11.
- `watch` (**Medium Risk / Need Attention**): Jika terdapat risk point atau watch points >= 1.
- `good` (**Low Risk / On Track**): Jika kondisi terkendali dan tidak ada anomali signifikan.

---

## CAP / Corrective Action Plan

Corrective Action Plan (CAP) dibentuk oleh `Dashboard_model::build_management_action_plan()` yang memberikan rekomendasi tindakan konkret:

| Kategori CAP | Pemicu Muncul | Status | Rekomendasi Tindakan |
| --- | --- | --- | --- |
| **Data Accuracy** | Score `< 90%` | Mengikuti status accuracy | Verifikasi order periode terdahulu di Engage dan pastikan transaksi output terinput rapi |
| **Output Achievement** | Achievement `< 90%` | `watch` / `risk` | Tingkatkan utilisasi mesin heat transfer dan optimalkan alokasi operator |
| **Coverage Ready Load** | Ready coverage `< 10 hari` | `watch` / `risk` | Koordinasikan pasokan material printing/heat dan aksesoris agar buffer kerja aman |
| **Kebutuhan Output Harian** | Avg Output `<` Req. Output | `watch` / `risk` | Lakukan penambahan jam kerja / lembur terarah untuk mengejar gap harian |
| **Order Delivery Kritis** | Critical Orders `> 0` | `risk` | Prioritaskan proses heat transfer untuk nomor order dengan tanggal delivery terdekat |
| **Kondisi Terkendali** | Semua indikator aman | `good` | Pertahankan ritme kerja dan pantau fluktuasi input material harian |

---

## Batasan dan Catatan Operasional

- **Ketergantungan Data RPA**: Angka dashboard bergantung pada data terbaru (`JO.xlsx`, `CONTROLIST.xlsx`, dan database MySQL Engage).
- **Format Header Excel**: Header file Excel APS dan Accessories harus tetap konsisten dengan format kolom yang dibaca oleh `Dashboard_model.php`.
- **Manajemen Akun Pengguna**: Pengelolaan user dilakukan di Panel Admin GM (`/dashboard_heat/admin` -> menu Kelola Akun) yang tersimpan di tabel `tbl_login`.
- **Sinkronisasi Database**: Jika angka dashboard terlihat tidak bergerak, periksa koneksi MySQL `db_dashboardgm`, waktu modifikasi file cadangan di folder downloads, serta catatan eksekusi pada `rpa/logs/scheduler.log`.
