# IRL 4: Desain Database, Struktur Dashboard, Mockup Tampilan, dan Mapping Data

Dokumen ini merinci desain teknis untuk **Dashboard GM - Modul Heat Transfer (Material Ready)**, mencakup rancangan database, struktur sistem, mockup antarmuka, serta pemetaan aliran data dari RPA hingga ke tampilan dashboard.

---

## 1. Manajemen & Penyimpanan Data

Sistem merancang penyimpanan data secara terstruktur untuk mengelola data operasional aktual serta memelihara data riwayat (historis) tanpa membebani kinerja dashboard.

### A. Penyimpanan Data Aktual (Engage)
Menyimpan seluruh catatan transaksi masuk (Inflow) dan keluar (Outflow) barang dari sistem gudang Engage secara berkala.
* **Data Transaksi Berjalan**: Menyimpan transaksi harian aktif untuk modul Heat Transfer.
* **Pengarsipan Otomatis**: Transaksi lama diarsipkan secara otomatis untuk memastikan database utama tetap ringan dan cepat diakses.
* **Informasi yang Disimpan**: Tanggal transaksi, nomor Order/JO, jumlah barang, kode material, informasi style, serta catatan transaksi (status/tipe gerakan barang).

### B. Rekapitulasi Harian & Tren Historis
* **Rekapitulasi Harian**: Mengonsolidasi data transaksi harian (total barang masuk, keluar, dan siap kirim) untuk mempercepat proses loading grafik performa dashboard.
* **Tren Historis**: Menyimpan catatan historis harian dari kapasitas produksi, output aktual, dan status backlog (balance) guna memfasilitasi analisis tren oleh manajemen.

---

## 2. Arsitektur Aliran Data (Bagaimana Dashboard Bekerja)

Dashboard dirancang dengan arsitektur modular yang memisahkan antara pengambilan data, pengolahan logika bisnis, dan penyajian tampilan visual untuk pengguna.

### A. Pengambilan Data & Integrasi (Input)
* Menghubungkan berbagai sumber data eksternal: data jadwal produksi (APS), status kelengkapan aksesoris (CIUROX), dan riwayat logistik (Engage).
* RPA bertindak sebagai penarik data berkala secara otomatis agar data selalu terbarui tanpa input manual.

### B. Logika Bisnis & Perhitungan (Process)
* Memproses data mentah yang telah ditarik dan menggabungkannya berdasarkan kecocokan Nomor Order/JO.
* Melakukan kalkulasi otomatis untuk metrik-metrik operasional: sisa target (Balance), kesiapan material (RTL), beban kerja harian (Demand Harian), dan sisa hari kerja operasional.

### C. Visualisasi & Tampilan Pengguna (Output)
* Menyajikan visualisasi interaktif berupa grafik kapasitas vs output harian, tabel prioritas order, dan kartu indikator utama (KPI).
* Halaman diperbarui secara berkala dan otomatis (Real-Time) saat mendeteksi adanya pembaruan data backend.

---

## 3. Mockup Tampilan Dashboard (UI/UX)

Tampilan didesain dalam satu layar (One-Page Dashboard) untuk kemudahan pemantauan *real-time*.

```text
====================================================================================
|  [LOGO] Dashboard GM - Material Ready Production              🕒 Update: 08:15 WIB |
====================================================================================
|                                                                                  |
|  [ TOTAL OUTPUT ]      [ SISA (BALANCE) ]     [ ORDER READY ]    [ KAPASITAS ]   |
|      2.840 Pcs             460 Pcs                12 JO            285 / Hari    |
|                                                                                  |
------------------------------------------------------------------------------------
|                                      |                                           |
|       GRAFIK KAPASITAS VS OUTPUT     |       MATERIAL READY (TOP PRIORITY)       |
|                                      |                                           |
|  350 |       [ ]             [ ]     |  Order       | Tgl Kirim | Status         |
|  285 |--------|---TARGET------|----  |  1234567-1   |  END AUG  | [ READY ]      |
|  200 |  [ ]   |       [ ]     |      |  1234568-2   |  END AUG  | [ READY ]      |
|      |___|____|________|______|____  |  1234569-1   |  MID SEP  | [ PROSES]      |
|         Sen  Sel      Rab    Kam     |  1234570-3   |  MID SEP  | [PENDING]      |
|                                      |                                           |
|                                      |  [ Download Export Excel ]                |
------------------------------------------------------------------------------------
|                                                                                  |
|   🔔 MANAGEMENT ANALYTICS (ACTION PLAN):                                        |
|   - Status: NEED ATTENTION (Output Harian < Demand yang Dibutuhkan)               |
|   - Kebutuhan: Tingkatkan output menjadi 285 pcs/hari untuk mencapai target.     |
|                                                                                  |
====================================================================================
```

---

## 4. Kamus Metrik & Aliran Data

Bagian ini menjelaskan bagaimana indikator kinerja (metrik) pada dashboard dihitung dari berbagai sumber data operasional.

### A. Tabel Pemetaan Metrik
| Metrik Utama UI | Sumber Data | Logika Bisnis & Perhitungan |
|---|---|---|
| **Daftar Order** | Rencana Produksi (APS) | Daftar pesanan aktif yang memiliki proses Heat Transfer (HT) pada jadwal pengiriman terdekat. |
| **Total Output** | Gudang Aktual (Engage) | Akumulasi barang keluar (Outflow) untuk pesanan terkait dari pencatatan logistik. |
| **Sisa Target (Balance)**| APS & Engage | Target kuantitas rencana (APS) dikurangi dengan jumlah output yang telah selesai (Engage). |
| **Kesiapan Order (RTL)**| APS, Engage & CIUROX | Dinyatakan *Ready* jika material masuk telah tersedia di area HT **dan** seluruh aksesoris pendukung berstatus *Completed*. |
| **Hari Kerja Tersisa**| Kalender Operasional | Jumlah hari kerja aktif yang tersisa dari hari ini sampai batas tanggal pengiriman order. |
| **Beban Harian (Demand)**| Kalkulasi Sistem | Sisa Target (Balance) dibagi dengan Sisa Hari Kerja, memberikan panduan minimal output harian. |
| **Urutan Prioritas** | Logika Pengurutan | Order dengan tanggal pengiriman terdekat diletakkan paling atas, diprioritaskan bagi order yang berstatus *Ready*. |

---

### B. Alur Sinkronisasi Data (End-to-End)

1. **Pengumpulan Data Otomatis (RPA)**:
   * Setiap jam, robot RPA secara otomatis mengunduh laporan status aksesoris dari CIUROX, menarik log transaksi terbaru dari Engage, serta mengambil jadwal produksi (JO) dari APS.
2. **Konsolidasi & Pencocokan**:
   * Sistem secara otomatis memadankan nomor Order/JO dari ketiga sumber data tersebut di server.
   * Melakukan perhitungan rumus-rumus bisnis di atas untuk menghasilkan status performa terbaru.
3. **Penyajian Informasi**:
   * Hasil perhitungan langsung dikirimkan ke halaman dashboard web.
   * Layar dashboard diperbarui secara dinamis sehingga manajemen dan tim produksi melihat angka riil yang sama secara real-time.
