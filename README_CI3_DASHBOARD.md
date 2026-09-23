# Dashboard GM

Dashboard GM adalah aplikasi dashboard internal berbasis web untuk memantau data produksi, terutama modul **Heat Transfer**. Web ini membaca hasil download RPA dari APS, Engage, dan Accessories, lalu menggabungkannya menjadi indikator dashboard seperti output, balance, ready to load, demand harian, dan prioritas order.

## Dibuat menggunakan

- **PHP CodeIgniter 3** untuk backend MVC.
- **HTML, CSS, dan JavaScript** untuk tampilan dashboard.
- **Python** untuk RPA downloader.
- **Playwright Python** untuk RPA berbasis browser seperti Engage dan Accessories.
- **Windows GUI automation** untuk RPA APS melalui aplikasi IOS-APS di server share UNC.
- **XAMPP/Apache** sebagai server lokal.
- **Excel `.xlsx`** sebagai media pertukaran data dari RPA ke dashboard.

## Cara pasang dan Akses URL

1. Pastikan server Apache & MySQL aktif di XAMPP.
2. Web CodeIgniter berada di `web/application` dan `web/system`.
3. Database utama dashboard berada di MySQL `db_dashboardgm`.
4. Akses URL:
   - **Portal GM**: `http://localhost/dashboard_gm/index.php/dashboard` (atau root `http://localhost/dashboard_gm/`)
   - **Dashboard Heat Transfer**: `http://localhost/dashboard_gm/index.php/dashboard_heat`
   - **Admin Panel Dashboard**: `http://localhost/dashboard_gm/index.php/dashboard_heat/admin`

## File utama

- `web/application/controllers/Dashboard.php`: Landing page portal GM dan perutean akses Dashboard / Admin.
- `web/application/controllers/Dashboard_base.php`: Base controller dashboard (helper URL, response JSON API).
- `web/application/controllers/Dashboard_heat.php`: Controller utama modul Heat Transfer, admin panel, dan endpoint API (status, SMV, analytics settings, workdays, download).
- `web/application/models/Dashboard_model.php`: Core data logic, parser APS & Accessories, koneksi MySQL Engage/History, kalkulasi SMV, kalender, dan analytics.
- `web/application/views/dashboard_portal.php`: View landing portal GM dan form login admin.
- `web/application/views/dashboard_admin.php`: View panel admin terpadu (Overview, Summary, SMV & Direct Aktual, Kalender, Analytics, Data Sources, Notes).
- `web/application/views/dashboards/heat/index.php`: View utama Dashboard Heat Transfer dengan instant server-side pre-hydration.
- `web/application/config/dashboard.php`: Konfigurasi path data, tabel user portal (`tbl_login`), dan kredensial kalender.
- `web/application/config/database.php`: Konfigurasi koneksi database MySQL (`dashboard_heat_history` pada `db_dashboardgm`).
- `web/application/cache/dashboard_heat_style_smv.json`: Cache konfigurasi nilai SMV per style.
- `web/application/cache/dashboard_heat_analytics_settings.json`: Cache konfigurasi kartu analytics, bahasa, dan direct actual.
- `web/application/cache/dashboard_heat_holidays.json`: Cache konfigurasi hari libur dan kalender kerja.
- `dist/RPA_Master.exe`: Launcher standalone pipeline RPA tanpa terminal.

Mapping detail fitur per file/fungsi terdokumentasi lengkap di `CODE_FEATURE_MAP.md`.

## Pola modul dashboard

Untuk bagian baru, buat controller dan view terpisah:

```text
web/application/controllers/Dashboard_nama_bagian.php
web/application/models/Dashboard_nama_bagian_model.php
web/application/views/dashboards/nama_bagian/index.php
web/application/views/analytics/nama_bagian/index.php
```

Contoh URL:

```text
/dashboard_heat
/dashboard_cutting
/dashboard_sewing
```

Tombol `Run Download` pada dashboard menjalankan master scheduler:

```text
dist/RPA_Master.exe --once
```

Scheduler menjalankan RPA secara berurutan:

1. Accessories RPA
2. Engage RPA
3. APS RPA

## Sumber data Heat

```text
rpa/engage-rpa        -> DATABASE / output aktual
rpa/aps-rpa           -> DELIVERY / data JO APS
rpa/accessories-rpa   -> CONTROLIST / data Accessories
```

## Cara pengambilan data

### Accessories

Accessories RPA login ke CIUROX, membuka halaman Accessories GM, mengisi filter tanggal dan status `COMPLETED`, lalu export Controlist.

Output file:

```text
rpa/accessories-rpa/downloads/CONTROLIST.xlsx
```

File ini dibuat tetap agar download berikutnya menimpa data lama dan folder server tidak dipenuhi file timestamp.

### Engage

Engage RPA login ke portal warehouse Engage via Playwright browser, membuka menu report warehouse, lalu mengambil data transaksi untuk storage `32` dan `32a` dengan `direction = 0` (arah gabungan inflow & outflow). Pada eksekusi otomatis scheduler, data ditarik berdasarkan tanggal acuan (`reference_date`, yaitu hari ini atau H-1 untuk run penutup tengah malam).

Setelah data report ditarik, Engage RPA menyimpan file cadangan Excel (`32_engage.xlsx` dan `32a_engage.xlsx`) di folder `rpa/engage-rpa/downloads/`, lalu otomatis memicu helper PHP untuk sinkronisasi ke database MySQL:
- `sync_engage_transactions.php`: Melakukan upsert data transaksi mentah ke tabel `tb_engage_transactions` (untuk hari berjalan dan mendatang) dan `tb_engage_archieve` (arsip lampau dengan retensi 90 hari).
- `sync_engage_daily_history.php`: Menghitung dan menyimpan ringkasan harian input, output, dan ready quantity ke tabel `engage_daily_history`.
- Script cadangan: `sync_excel_engage.py` (sinkronisasi file Excel Engage ke MySQL) dan `backfill_past_7_days.py` (pengisian data historis 7 hari terakhir).

### APS

APS RPA membuka aplikasi IOS-APS dari network share server `\\172.23.1.10\ios-aps\IOS-APS.exe` (atau shortcut lokal), menangani dialog peringatan keamanan Windows, melakukan login otomatis (username, password, dan klik tombol Login), membuka menu JO Tracking Report, mengisi filter rentang Delivery Date dari **awal bulan lalu** sampai **akhir 2 bulan ke depan**, merefresh data dengan deteksi stabilitas layar serta penanganan popup otomatis, lalu mengekspor hasil ke Excel.

File disimpan ke:

```text
rpa/aps-rpa/downloads/JO.xlsx
```

RPA mampu menangani penyimpanan langsung dari dialog Save As APS maupun Save As (F12) melalui jendela Microsoft Excel 2010, lalu menutup spreadsheet dan aplikasi APS secara bersih. File `JO.xlsx` dibuat tetap (overwrite) supaya setiap download terbaru menggantikan data sebelumnya tanpa memenuhi kapasitas penyimpanan.

## Cara dashboard Heat membaca data

Model utama ada di:

```text
web/application/models/Dashboard_model.php
```

Dashboard Heat membaca data dari sumber berikut:

```text
APS         : File Excel rpa/aps-rpa/downloads/JO.xlsx
Accessories : File Excel rpa/accessories-rpa/downloads/CONTROLIST.xlsx
Engage      : Database MySQL (Tabel tb_engage_transactions & tb_engage_archieve)
```

Data dianggap lengkap jika file APS tersedia dan data Engage di database MySQL dapat dibaca. Di dashboard, `Qty` positif dihitung sebagai input dan `Qty` negatif dihitung sebagai output. Accessories bersifat tambahan untuk menghitung order yang sudah completed.


## Fitur aplikasi

### 1. Portal GM (`/dashboard`)

Halaman gerbang utama (landing portal) yang bersih dan responsif di:

```text
http://localhost/dashboard_gm/index.php/dashboard
```

Fitur pada Portal:
- **Akses Cepat Dashboard Publik**: Langsung membuka Dashboard Heat Transfer tanpa login.
- **Login Admin Modal**: Akses terautentikasi ke Panel Admin GM dengan memvalidasi username dan password ke tabel database (`tbl_login`).
- **Session Protected**: Pengguna admin yang telah login diarahkan ke `/dashboard_heat/admin`.

---

### 2. Dashboard Heat Transfer (`/dashboard_heat`)

Halaman monitoring produksi utama yang didesain interaktif dan modern:

```text
http://localhost/dashboard_gm/index.php/dashboard_heat
```

Fitur Utama:
- **Instant Pre-Hydration (Fast Load)**: Data awal disuntikkan langsung oleh controller via server-side JSON payload (`initial_dashboard_payload`). Halaman langsung terisi data seketika saat dibuka tanpa kedip atau popup loading yang lama.
- **Pilihan Periode Delivery (1, 2, 4, 6 Delivery)**: Pengguna dapat memilih rentang monitoring antara 1, 2, 4, atau 6 periode delivery. Pilihan ini disimpan otomatis di browser via cookie `heatDeliveryCount`.
- **Kartu Ringkasan KPI**:
  - `Total Output`: Akumulasi output aktual dari database Engage.
  - `Balance Qty`: Sisa kuantitas yang belum terpenuhi pada periode delivery aktif.
  - `Balance Breakdown`: Distribusi balance per periode delivery.
- **Visualisasi Grafik Interaktif**:
  - `Target vs Aktual`: Perbandingan kuantitas rencana (PDK) dari APS terhadap realisasi output Engage.
  - `Ready TO Production`: Grafik batang kelompok yang memisahkan material siap produksi berdasarkan kesiapan aksesoris (**Completed** dan **Uncompleted**) per periode delivery.
  - `KAPASITAS vs OUT vs IN`: Grafik harian yang membandingkan target kapasitas harian (**Kapasitas** = *Balance Qty / Sisa Hari Kerja* berupa garis merah) terhadap realisasi batang produksi (**OUT**) dan material masuk (**IN**).
- **Tabel Material To Load & Export Excel**:
  - Menampilkan daftar order aktif untuk periode delivery yang dipilih.
  - Tombol **Export Excel** (`/dashboard_heat/download_material_to_load?delivery_count=...`) yang otomatis menyertakan label periode aktif saat ini.
- **Top Priority Orders**: Daftar order mendesak yang diprioritaskan berdasarkan tanggal delivery terdekat.
- **Monitoring Analytics & Action Plan (CAP)**:
  - Kartu indikator performa: `Production Status`, `Output Achievement`, `Data Accuracy`, `Ready Coverage`, dll.
  - Insight manajemen otomatis dan Action Plan rekomendasi penanganan kendala produksi.
- **Auto-Refresh Sinkron Jam Dinding (Setiap 30 Menit)**: Tampilan dashboard melakukan pembaruan otomatis (auto-refresh) setiap **30 menit sekali** tepat pada menit `:00` dan `:30` setiap jam (sinkron jam dinding) melalui `scheduleNextAlignedRefresh()`, sehingga layar monitor/dashboard display selalu menyajikan data termutakhir tanpa perlu reload manual.
- **Status Sinkronisasi & Run Download**:
  - Menampilkan waktu pembaruan terakhir masing-masing file data.
  - Tombol **Run Download** untuk memicu scheduler RPA secara on-demand.

---

### 3. Panel Admin Terpadu GM (`/dashboard_heat/admin`)

Halaman kontrol sentral khusus supervisor dan manajemen untuk mengatur parameter operasional dan konfigurasi dashboard. Memerlukan autentikasi portal.

Navigasi Sidebar Admin Panel:
1. **Overview**: Ringkasan performa real-time, status data source, dan shortcut aksi penting.
2. **Summary**: Analisis metrik output, balance, demand harian, dan pencapaian target produksi.
3. **SMV & Direct Aktual (Style SMV Catalog)**:
   - **Style SMV**: Mengatur nilai Standard Minute Value (SMV) per nomor style baju/produk. SMV digunakan untuk menghitung kapasitas menit kerja dan beban kerja per style.
   - **Show in Dashboard Toggle**: Menentukan style mana saja yang akan ditampilkan sebagai running styles di dashboard.
   - **Bulk Actions**: Tombol praktis untuk mengaktifkan atau menonaktifkan seluruh style sekaligus.
   - **Direct Aktual Mode**: Toggle untuk menggunakan nilai input Direct Aktual atau kalkulasi formula standar.
   - Data tersimpan otomatis di cache `web/application/cache/dashboard_heat_style_smv.json`.
4. **Kalender Kerja**:
   - Pengaturan hari kerja dan hari libur visual.
   - Mendukung tipe: **Holiday** (0 hari), **Half Day** (0.5 hari), **Quarter Day** (0.25 hari), dan **Work Day** (menjadikan hari Minggu sebagai hari kerja aktif).
   - Data tersimpan di `web/application/cache/dashboard_heat_holidays.json`.
5. **Analytics Settings**:
   - Pemilihan kartu analytics yang ingin diaktifkan di dashboard publik (visible cards).
   - Pengaturan bahasa tampilan: **Bahasa Indonesia (`id`)** atau **English (`en`)**.
   - Data tersimpan di `web/application/cache/dashboard_heat_analytics_settings.json`.
6. **Data Sources**:
   - Monitoring ketersediaan dan timestamp file `JO.xlsx`, `CONTROLIST.xlsx`, serta tabel MySQL Engage.
   - Tombol unduh langsung untuk file Excel sumber.
7. **Catatan Operasional**: Dokumentasi internal mengenai formula dan acuan ambang batas metrik.

---

### 4. Master RPA Scheduler & Standalone Launcher (`dist/RPA_Master.exe`)

Untuk memastikan kestabilan dan kemudahan operasional di server tanpa perlu membuka terminal atau mengelola command prompt:

- **Executable Mandiri**: Dikompilasi ke `dist/RPA_Master.exe`. Cukup dijalankan dengan klik ganda di Windows.
- **Penjadwalan Otomatis per 1,5 Jam (90 Menit)**:
  - Seluruh downloader RPA (Accessories, Engage, dan APS) dijalankan bersama secara berurutan setiap **1,5 jam sekali (90 menit)** mulai pukul **07:00 hingga 22:00** dan ditutup tepat pada pukul **00:00** (tengah malam) untuk merekap data harian.
  - Jadwal tetap harian: `07:00`, `08:30`, `10:00`, `11:30`, `13:00`, `14:30`, `16:00`, `17:30`, `19:00`, `20:30`, `22:00`, dan `00:00`.
- **Eksekusi Sekali (On-Demand)**:
  ```text
  dist/RPA_Master.exe --once
  ```
  Opsi ini juga dipanggil secara otomatis oleh tombol `Run Download` pada dashboard web.
- **Process Lock**: Dilengkapi file lock `rpa/logs/scheduler.lock` agar proses scheduler tidak berjalan ganda jika proses sebelumnya belum selesai.

---

### 5. Integrasi Sinkronisasi Database MySQL Engage

Engage RPA kini terintegrasi langsung dengan database MySQL lokal:
- Mengunduh report warehouse Engage terbaru.
- Menjalankan helper script PHP:
  - `rpa/engage-rpa/sync_engage_transactions.php`: Melakukan upsert data transaksi ke tabel `tb_engage_transactions` (berjalan) dan `tb_engage_archieve` (arsip lama).
  - `rpa/engage-rpa/sync_engage_daily_history.php`: Menghitung dan menyimpan ringkasan harian input, output, dan ready qty ke tabel `engage_daily_history`.
- Skrip pendukung tambahan:
  - `rpa/engage-rpa/sync_excel_engage.py`: Script sinkronisasi file Excel Engage ke MySQL.
  - `rpa/engage-rpa/backfill_past_7_days.py`: Script untuk mengisi ulang data histori 7 hari terakhir.

## Sumber data dan asal angka

### APS / JO Tracking

File:

```text
rpa/aps-rpa/downloads/JO.xlsx
```

Dipakai untuk:

- Data JO/order.
- Style.
- Delivery date.
- QTY PDK/plan.
- Period delivery seperti `MID June` atau `END June`.
- Prioritas order berdasarkan tanggal delivery.

### Engage Database (MySQL)

Tabel:

```text
tb_engage_transactions
tb_engage_archieve
engage_daily_history
```

Dipakai untuk:

- Input dan output aktual Heat Transfer yang diambil secara query langsung dari tabel database MySQL.
- `Qty` positif dihitung sebagai input.
- `Qty` negatif dihitung sebagai output.
- Total output dashboard dan riwayat harian diambil dari tabel database.
- Catatan: File excel `32_engage.xlsx` dan `32a_engage.xlsx` di folder `rpa/engage-rpa/downloads/` tetap diunduh oleh RPA Engage sebagai cadangan dan untuk mendeteksi update timestamp file, namun kalkulasi utama dashboard membaca langsung dari database MySQL.

### Accessories Controlist

File:

```text
rpa/accessories-rpa/downloads/CONTROLIST.xlsx
```

Dipakai untuk:

- Menandai order Accessories yang sudah `COMPLETED`.
- Mendukung perhitungan order ready/completed.

## Struktur data dashboard

Model `Dashboard_model.php` mengubah file RPA menjadi struktur utama berikut:

| Field JSON | Isi | Asal utama |
| --- | --- | --- |
| `kpis.total_output` | Total output produksi | Engage 32a Outflow |
| `kpis.balance_qty` | Total balance dari periode dipilih | APS JO dan output Engage |
| `qty_pdk_vs_output` | PDK vs output per periode | APS JO + Engage Outflow |
| `ready_to_load` | Qty ready per periode | APS JO + Engage/Accessories |
| `output_vs_capacity` | Output, input, capacity harian | Engage Outflow + Engage Inflow + kalkulasi capacity |
| `top_priority_orders` | Order prioritas delivery | APS JO + ready/output |
| `sources` | Status file sumber data | File RPA di folder downloads |
| `delivery_workdays` | Kalender kerja tiap periode | Kalender dashboard |
| `management_analytics` | Metrics, status, insight, detail, CAP | Hasil kalkulasi dashboard |

## Perhitungan utama

### 1. Target vs Aktual (QTY PDK vs Output)

Periode delivery dibentuk dari delivery date APS:

```text
Tanggal 1-15  -> MID <bulan>
Tanggal 16-akhir bulan -> END <bulan>
```

Rumus per periode:

```text
QTY PDK = total qty plan/PDK dari APS pada periode tersebut
QTY Output = total output Engage yang cocok dengan order/periode tersebut
Balance = max(0, QTY PDK - QTY Output)
```

### 2. Total Output

```text
Total Output = total output dari data Engage 32a Outflow
```

Nilai ini ditampilkan di KPI utama dan dipakai untuk insight Output Achievement.

### 3. Balance Qty

```text
Balance Qty = total QTY PDK - total QTY Output
```

Untuk analytics, balance bisa difokuskan ke periode berjalan.

### 4. Ready To Load

```text
Ready To Load = qty order/periode yang sudah tersedia/ready berdasarkan gabungan data APS, Engage, dan Accessories
```

Nilai ini ditampilkan per periode delivery.

### 5. Demand Harian

Demand dihitung dari balance delivery aktif dan sisa hari kerja:

```text
Daily Demand = balance delivery aktif / sisa hari kerja delivery aktif
```

Data demand dipakai untuk grafik `Demand vs Output`.

### 6. Avg Daily Output

```text
Avg Daily Output = total output harian / jumlah hari yang punya output atau capacity
```

Sumber output harian berasal dari Engage 32a Outflow.

### 7. Avg Daily Demand

```text
Avg Daily Demand = total demand harian / jumlah hari yang punya output atau demand
```

### 8. Demand Gap / Surplus

```text
Demand Gap = total demand - total daily output
```

Interpretasi:

- Jika hasil positif, masih ada gap demand tersedia.
- Jika hasil negatif, output lebih besar dari capacity dan ditampilkan sebagai surplus.

### 9. Ready Coverage

```text
Ready Coverage Days = Total Ready Load / Avg Daily Demand
```

Status:

| Nilai | Status |
| --- | --- |
| `>= 10 hari` | `good` |
| `>= 5` dan `< 10 hari` | `watch` |
| `< 5 hari` | `risk` |

### 10. Required Daily Output

Kebutuhan output harian dihitung dari periode berjalan:

```text
Required Daily Output = balance periode berjalan / sisa hari kerja export periode berjalan
```

Sisa hari kerja export memakai buffer export:

```text
Export Remaining Workdays = Remaining Workdays - 4 hari buffer export
```

Nilai buffer export saat ini:

```text
4 hari kerja
```

Status Required Daily Output:

| Kondisi | Status |
| --- | --- |
| `Avg Daily Output >= Required Daily Output` | `good` |
| `Avg Daily Output >= Required Daily Output * 0.9` | `watch` |
| Di bawah itu | `risk` |

### 11. Output Achievement

Rumus:

```text
Output Achievement = output_base / pdk_base * 100
```

`pdk_base` dan `output_base` memakai periode berjalan jika tersedia. Jika tidak tersedia, sistem fallback ke total PDK/output dashboard.

Status:

| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 12. Data Accuracy

Data Accuracy memeriksa apakah periode sebelumnya sudah clear sebelum periode berjalan diproses.

Alur:

1. Cari periode berjalan pertama yang masih punya `balance > 0` atau `ready > 0`.
2. Ambil dua periode sebelum periode berjalan.
3. Jika periode sebelumnya masih punya `balance + ready > 0`, maka dianggap issue.

Rumus score:

```text
Data Accuracy Score = max(0, 100 - (jumlah issue * 20))
```

Status:

| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 13. Critical Orders

Critical Orders dihitung dari order prioritas yang delivery date-nya berada dalam horizon 5 hari kerja.

```text
Critical Order = delivery_workdays_left(order.delivery) <= 5
```

Status:

| Kondisi | Status |
| --- | --- |
| Tidak ada critical order | `good` |
| Ada critical order | `risk` |

### 14. Source Sync

```text
Source Sync = jumlah source file tersedia / total source file yang dimonitor
```

Contoh:

```text
5/5
```

Artinya 5 dari 5 file sumber dashboard tersedia.

### 15. Monitoring Coverage

Monitoring Coverage menunjukkan modul dashboard yang masuk scope monitoring, bukan demand produksi.

Scope saat ini:

- APS JO Tracking
- Engage 32a Inflow
- Engage 32a Outflow
- Accessories Controlist
- Dashboard Analytics

### 16. Data Update

Data Update mengambil timestamp terakhir dari source dashboard:

```text
Data Update = latest updated_at dari daftar sources
```

Detail card menampilkan update terakhir per source.

## Status keseluruhan produksi

Backend menghitung status keseluruhan lewat fungsi:

```text
Dashboard_model::build_overall_condition()
```

Input perhitungan:

- `achievement_rate`
- `ready_coverage_days`
- `required_daily_output`
- `avg_daily_output`
- `avg_daily_capacity`
- `critical_orders`
- `data_accuracy`
- periode berjalan
- kalender kerja periode berjalan

### Risk point dan watch point

Sistem menambahkan risk/watch point dari kondisi berikut:

| Kondisi | Efek |
| --- | --- |
| `achievement_rate < 75%` | tambah risk |
| `achievement_rate >= 75%` dan `< 90%` | tambah watch |
| `ready_coverage_days < 5` | tambah risk |
| `ready_coverage_days >= 5` dan `< 10` | tambah watch |
| `remaining export workdays > 11` | tambah 3 risk |
| `remaining export workdays >= 5` dan `<= 11` | tambah 2 watch |
| `remaining export workdays < 4` | dianggap sisa hari aman |
| `remaining_days <= 0` dan masih ada balance | tambah risk |
| `avg_daily_capacity < required_daily_output` | tambah risk |
| `avg_daily_output < required_daily_output` | tambah watch |
| `avg_daily_output < required_daily_output * 1.1` | tambah watch |
| `critical_orders > 0` | tambah risk |
| `data_accuracy.score < 75` | tambah risk |
| `data_accuracy.score >= 75` dan `< 90` | tambah watch |

### Mapping status keseluruhan

Urutan keputusan status:

| Kondisi | Status | Level |
| --- | --- | --- |
| `remaining export workdays > 11` | `risk` | `High Risk` |
| `remaining export workdays >= 5` | `watch` | `Medium Risk` |
| `remaining export workdays < 4` | `good` | `Low Risk` |
| `risk_points >= 3` | `risk` | `High Risk` |
| `risk_points > 0` atau `watch_points >= 2` | `watch` | `Medium Risk` |
| `watch_points > 0` | `watch` | `Medium Risk` |
| Selain itu | `good` | `Low Risk` |

Catatan penting: pada UI Analytics saat ini, card `Production Status` default ditampilkan sebagai label general `On Track`. Nilai backend yang lebih detail tetap tersedia di `management_analytics.overall_condition` dengan status `good`, `watch`, atau `risk`. Jika ingin status card benar-benar mengikuti kalkulasi backend, mapping yang disarankan:

| Backend | Label tampilan |
| --- | --- |
| `good / Low Risk` | `On Track` |
| `watch / Medium Risk` | `Need Attention` |
| `risk / High Risk` | `At Risk` |

## Management Insight

Management Insight di view dibentuk dari card Analytics yang sedang tampil. Jika card diganti lewat Analytics Display, insight ikut berubah.

Contoh mapping:

| Card tampil | Insight memakai data |
| --- | --- |
| `Production Status` | status card, total output, balance qty |
| `Output Achievement` | achievement dan total output |
| `Data Accuracy` | score akurasi data |
| `Monitoring Coverage` | scope modul dashboard |
| `Source Sync` | jumlah source tersinkron |
| `Data Update` | timestamp update terakhir source |
| `Ready Coverage` | coverage days dan total ready load |
| `Req. Daily Output` | kebutuhan output harian dan balance |
| `Total Ready Load` | total ready dari ready-to-load |
| `Avg Daily Output` | rata-rata output harian |
| `Avg Daily Demand` | rata-rata demand harian |
| `Demand Gap / Surplus` | selisih demand dan output |
| `Sequence Issues` | jumlah issue urutan data |
| `Critical Orders` | jumlah order kritis |

## CAP / Prevention & Handling

CAP dibuat oleh:

```text
Dashboard_model::build_management_action_plan()
```

CAP muncul berdasarkan kondisi:

| CAP | Muncul jika | Status |
| --- | --- | --- |
| `Data Accuracy` | score `< 90%` | mengikuti status Data Accuracy |
| `Output Achievement` | achievement `< 90%` | `watch` jika `>=75%`, `risk` jika `<75%` |
| `Coverage Ready Load` | ready coverage `< 10 hari` | `watch` jika `>=5 hari`, `risk` jika `<5 hari` |
| `Kebutuhan Output Harian` | avg daily output `< required daily output` | `watch` jika masih `>=90%` requirement, selain itu `risk` |
| `Order Delivery Kritis` | critical orders `> 0` | `risk` |
| `Kondisi Terkendali` | tidak ada CAP lain | `good` |

CAP berisi:

- masalah
- penyebab
- prevention
- handling

Di Analytics Display, CAP bisa dipilih manual sebagai card/tindak lanjut.

## Batasan dan catatan operasional

- Dashboard sangat bergantung pada file RPA terbaru. Jika file tidak tersedia, angka bisa kosong atau fallback ke cache.
- APS, Engage, dan Accessories harus memakai format/header Excel yang masih sesuai dengan parser.
- Accessories bersifat tambahan; data utama Heat tetap APS dan Engage.
- Kalender kerja memengaruhi sisa hari kerja, required daily output, capacity, dan status overall.
- Buffer export saat ini adalah `4 hari kerja`.
- Critical order memakai horizon `5 hari kerja`.
- Data Accuracy bukan audit seluruh data, tetapi validasi sequence antar periode delivery.
- Source Sync hanya memeriksa ketersediaan source, bukan menjamin seluruh isi file benar.
- Monitoring Coverage menunjukkan area yang dipantau, bukan achievement produksi.
- Riwayat demand dan qty disimpan di tabel MySQL `dashboard_heat_history` (database `db_dashboardgm`); jika data terlihat tidak berubah, cek status database MySQL, file backup di folder downloads, dan log scheduler.
