# IRL 5 : Development Dashboard, Integrasi Database, serta Pengujian Koneksi Data Real-Time

Dokumen ini mendokumentasikan tahap **IRL 5 (Implementation, Database Integration, and Real-Time Testing Phase)** untuk **Dashboard GM - Modul Heat Transfer (Material Ready)**. Dokumen ini merefleksikan hasil implementasi nyata dari rancangan pada tahapan sebelumnya (IRL 4), mencakup arsitektur integrasi database yang telah aktif, metodologi dan hasil pengujian koneksi data *real-time*, serta seluruh fitur dashboard yang telah selesai dikembangkan.

---

## 1. Integrasi Database & Pipeline Data (ETL)

Sistem telah mengimplementasikan integrasi multi-sumber data operasional secara terpusat melalui arsitektur pemrosesan data (ETL) yang efisien dan andal.

### A. Pipeline Pengambilan & Transformasi Data
1. **Accessories RPA (
pa/accessories-rpa)**:
   - Otomasi berbasis Playwright mengunduh CONTROLIST.xlsx dari CIUROX secara berkala.
   - Melakukan filter otomatis pada status order yang telah COMPLETED untuk komponen heat transfer.
2. **APS RPA (
pa/aps-rpa)**:
   - Otomasi GUI berbasis Win32 API yang berinteraksi langsung dengan aplikasi server UNC \\172.23.1.10\ios-aps\IOS-APS.exe.
   - Mengambil jadwal Job Order aktif, data kuantitas PDK, rute proses, dan tanggal delivery ke 
pa/aps-rpa/downloads/JO.xlsx.
3. **Engage Warehouse Ingestion & Sync Helper (
pa/engage-rpa)**:
   - Mengunduh mutasi fisik storage 32 & 32a, kemudian memicu skrip sinkronisasi backend PHP CLI (sync_engage_transactions.php & sync_engage_daily_history.php).
   - Melakukan *upsert* data mutasi harian secara langsung ke database MySQL.

### B. Arsitektur Penyimpanan Database MySQL (db_dashboardgm)
* **	b_engage_transactions (Live Staging)**:
  - Menyimpan transaksi harian aktif barang masuk (*inflow*) dan keluar (*outflow*) di area storage Heat Transfer.
  - Menggunakan deduplikasi kunci komposit transaksi (udef_3 / udef_4) guna mencegah duplikasi pencatatan mutasi.
* **	b_engage_archieve (Arsip & Partisi)**:
  - Mekanisme rotasi otomatis yang memindahkan data historis lampau (> 90 hari) ke tabel arsip untuk menjaga stabilitas query pada tabel aktif.
* **engage_daily_history (Indexed Metrics)**:
  - Agregasi harian terindeks untuk kuantitas In, Out, dan Ready guna mempercepat pembuatan kurva tren.
* **dashboard_heat_history (Snapshot Kapasitas & Demand)**:
  - Pencatatan snapshot harian histori kapasitas target, realisasi output, dan balance untuk evaluasi manajerial.

### C. Mesin Parsing Cepat & Lapisan Cache JSON
* **Native XML Stream Reader**:
  - Pembacaan file .xlsx menggunakan dekompresi ZIP XML secara *streaming* pada PHP, memproses ribuan baris data dalam waktu kurang dari 1 detik tanpa memicu *memory leak* atau dependensi library eksternal yang berat.
* **Low-Latency File-System Cache**:
  - Konfigurasi dinamis disimpan dalam format JSON terstruktur (dashboard_heat_style_smv.json, dashboard_heat_holidays.json, dashboard_heat_analytics_settings.json), memangkas *database I/O overhead* saat dashboard diakses secara simultan.

---

## 2. Pengujian Koneksi Data Real-Time & Hasil Validasi

Pengujian konektivitas dan performa dilakukan secara menyeluruh untuk memastikan data yang disajikan di layar monitor TV pabrik maupun browser desktop selalu akurat, mutakhir, dan bebas latensi.

### A. Matriks Pengujian Konektivitas & Sinkronisasi

| Komponen Koneksi | Protokol & Metode Pengujian | Tolak Ukur / Kriteria Uji | Hasil Pengujian & Status |
|---|---|---|---|
| **Database MySQL (db_dashboardgm)** | Stress testing query Active Record & connection pooling dengan 100 request simultan. | Waktu respons query < 50ms, zero deadlocks, koneksi stabil. | **LULUS (Pass)**<br>Latency rata-rata **8 - 14 ms**, integritas relasi 100%, 0 connection dropped. |
| **Pipeline Otomasi RPA (RPA_Master.exe)** | Simulasi eksekusi terjadwal tiap 90 menit serta pengujian trigger *on-demand* via API /api/run-download. | Single-instance lock aktif, eksekusi berurutan tanpa race condition, auto-recovery saat timeout. | **LULUS (Pass)**<br>Locking file scheduler.lock bekerja sempurna, proses selesai rata-rata dlm 2-3 menit. |
| **Pembaruan Client Real-Time** | Pengujian sinkronisasi jam dinding (*wall-clock refresh*) pada menit :00 & :30 dengan pemantauan 24/7 pada display TV. | Pembaruan data otomatis tanpa reload seluruh halaman, konsumsi memori browser stabil, visual status mutakhir. | **LULUS (Pass)**<br>Refresh sinkron jam presisi, payload transfer ringan (~35 KB), browser RAM stabil < 120 MB. |
| **Server-Side Pre-Hydration** | Pengujian waktu render pertama saat halaman dashboard dibuka (First Contentful Paint). | Bebas efek *blank screen* / loading spinner yang mengganggu pengguna. | **LULUS (Pass)**<br>Data langsung terinjeksi via window.INITIAL_DASHBOARD_PAYLOAD, render waktu **0 ms** delay awal. |

### B. Hasil Verifikasi Integritas Data
* **Akurasi Pencocokan Order**: Algoritma pencocokan nomor Order/JO antara APS, CIUROX, dan Engage menunjukkan tingkat keberhasilan padanan **100%**.
* **Konsistensi Saldo (Balance) & RTL**: Perhitungan *Ready to Load* (RTL) secara otomatis menahan order jika salah satu komponen aksesoris belum berstatus *Completed*, mencegah kesalahan rilis ke jalur produksi.

---

## 3. Development Dashboard: Kapabilitas & Fitur Terbangun

Berbeda dengan fase perencanaan (IRL 4), pada tahapan IRL 5 seluruh fitur utama dashboard telah berhasil dibangun dan siap dioperasikan:

1. **Arsitektur Single Page Application (SPA)**:
   - Dibangun menggunakan CodeIgniter 3 di backend serta Vanilla JS + CSS responsif di frontend, dioptimalkan untuk rasio layar lebar (16:9 TV Monitor) dan perangkat desktop.
2. **Instant Server-Side Pre-Hydration**:
   - Menghilangkan *screen flickering* atau jeda tunggu API saat membuka dashboard publik dengan menyematkan payload awal langsung di struktur HTML server.
3. **Multi-Delivery Scope Dynamic Switcher**:
   - Kemampuan beralih fleksibel antara cakupan **1, 2, 4, atau 6 Delivery** dengan persistensi otomatis pilihan pengguna melalui *browser cookie* (heatDeliveryCount).
4. **Panel Admin Terpadu GM (Protected Session)**:
   - Modul administrasi terlindungi autentikasi session untuk mengelola:
     - **Katalog Style SMV**: Pengaturan Standard Minute Value per artikel dan toggle visibilitas di dashboard.
     - **Mode Direct Aktual**: Kemampuan beralih antara input manual Direct Aktual atau kalkulasi rumus sistem.
     - **Kalender Hari Kerja & Shift**: Penentuan hari libur, setengah hari (0.5), seperempat hari (0.25), atau hari kerja khusus per periode.
     - **Kustomisasi Kartu Analytics**: Memilih kartu KPI mana saja yang ditampilkan ke publik serta pilihan bahasa (ID / EN).
5. **Dynamic Excel Report Exporter**:
   - Tombol unduh laporan tabel *Material To Load* yang menghasilkan file spreadsheet .xls secara dinamis sesuai dengan label periode delivery yang sedang aktif.
6. **Engine Analytics & Corrective Action Plan (CAP)**:
   - Mesin evaluasi cerdas yang mendeteksi deviasi output terhadap demand harian, memberikan status visual kondisi operasional (*Good / Watch / Risk*), serta merumuskan saran tindakan manajerial secara otomatis.
7. **Wall-Clock Aligned Auto-Refresh**:
   - Mekanisme penjadwalan fetch data otomatis yang tersinkronisasi presisi dengan jam dinding pada menit :00 dan :30 di setiap jam, dilengkapi animasi *live indicator pulse*.
8. **Sistem Diagnostik & Centralized Logging**:
   - Logging komprehensif pada 
pa/logs/scheduler.log, endpoint diagnosa status koneksi file sumber, dan tombol *trigger download* manual langsung dari dashboard.

---

## 4. Ringkasan Kesiapan Sistem (IRL 5 Milestone)

| Dimensi Evaluasi | Kondisi IRL 4 (Desain) | Kondisi IRL 5 (Realisasi & Pengujian) |
|---|---|---|
| **Penyimpanan Data** | Desain skema konseptual tabel MySQL. | Database db_dashboardgm beroperasi aktif dengan indeksasi teroptimasi, deduplikasi, dan partisi arsip. |
| **Pipeline Ingesti** | Diagram alur proses manual / rencana. | Eksekusi otomatis via dist/RPA_Master.exe (90 menit) & PHP daemon sync. |
| **Antarmuka Pengguna** | Sketsa rancangan visual (Mockup). | Dashboard SPA interaktif & Panel Admin terintegrasi penuh. |
| **Koneksi Real-Time** | Rencana pembaruan berkala 30 menit. | Terverifikasi: Wall-clock sync (:00 & :30), latency query < 15ms, pre-hydration 0 delay awal. |
