"""
IRL 4 - Presentasi Desain Database, Struktur Dashboard, Mockup Tampilan, dan Mapping Data
"""

from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN

# ── Palette ─────────────────────────────────────────────────
NAVY        = RGBColor(0x1E, 0x3A, 0x5F)
TEAL        = RGBColor(0x00, 0x7A, 0x87)
CYAN        = RGBColor(0x00, 0xBF, 0xD8)
GREEN       = RGBColor(0x2E, 0x7D, 0x32)
AMBER       = RGBColor(0xF5, 0x7C, 0x00)
GRAY        = RGBColor(0xF4, 0xF6, 0xF9)
WHITE       = RGBColor(0xFF, 0xFF, 0xFF)
DARK        = RGBColor(0x1A, 0x1A, 0x2E)

prs = Presentation()
prs.slide_width  = Inches(13.33)
prs.slide_height = Inches(7.5)
BL = prs.slide_layouts[6]
TOTAL = 6

def new_slide(): return prs.slides.add_slide(BL)

def R(sl, l, t, w, h, fill=WHITE, line=None, lw=1, radius=False):
    s = sl.shapes.add_shape(5 if radius else 1, Inches(l), Inches(t), Inches(w), Inches(h))
    s.fill.solid(); s.fill.fore_color.rgb = fill
    if line: s.line.color.rgb = line; s.line.width = Pt(lw)
    else: s.line.fill.background()
    return s

def T(sl, text, l, t, w, h, size=12, bold=False, color=DARK, align=PP_ALIGN.LEFT, italic=False):
    tb = sl.shapes.add_textbox(Inches(l), Inches(t), Inches(w), Inches(h))
    tf = tb.text_frame; tf.word_wrap = True
    p  = tf.paragraphs[0]; p.alignment = align
    r  = p.add_run(); r.text = text
    r.font.size = Pt(size); r.font.bold = bold; r.font.italic = italic; r.font.color.rgb = color
    return tb

def title_bar(sl, title, sub=None):
    R(sl, 0, 0, 13.33, 1.0, fill=NAVY)
    R(sl, 0, 0.9, 13.33, 0.1, fill=CYAN)
    T(sl, title, 0.4, 0.08, 12.5, 0.6, size=24, bold=True, color=WHITE)
    if sub: T(sl, sub, 0.4, 0.65, 12.5, 0.3, size=11, color=CYAN)

def footer(sl, num):
    R(sl, 0, 7.28, 13.33, 0.22, fill=NAVY)
    T(sl, "IRL 4: Desain Database, Struktur Dashboard, Mockup, & Mapping Data", 0.2, 7.29, 11, 0.2, size=7.5, color=RGBColor(0xBB,0xCC,0xDD))
    T(sl, f"{num} / {TOTAL}", 12.5, 7.29, 0.7, 0.2, size=7.5, color=RGBColor(0x88,0x99,0xAA), align=PP_ALIGN.RIGHT)

def bullet_list(sl, items, l, t, w, size=11, gap=0.4, color=DARK, dot_color=CYAN):
    y = t
    for item in items:
        R(sl, l, y+0.09, 0.12, 0.12, fill=dot_color)
        T(sl, item, l+0.22, y, w-0.25, gap, size=size, color=color)
        y += gap
    return y


# ==========================================
# 1. COVER
# ==========================================
sl = new_slide()
R(sl, 0, 0, 13.33, 7.5, fill=NAVY)
T(sl, "IRL 4", 1.0, 2.0, 10, 0.6, size=16, bold=True, color=CYAN)
T(sl, "Desain Aliran Data & Tampilan Dashboard", 1.0, 2.5, 11, 1.3, size=36, bold=True, color=WHITE)
T(sl, "Dashboard GM - Modul Heat Transfer (Material Ready)", 1.0, 3.8, 11, 0.4, size=16, color=WHITE)
T(sl, "Rencana Aliran Sistem, Tampilan Antarmuka, dan Logika Metrik",
  1.0, 4.4, 10, 1.0, size=12, color=CYAN)


# ==========================================
# 2. STRUKTUR DASHBOARD (ALIRAN DATA)
# ==========================================
sl = new_slide()
title_bar(sl, "Arsitektur Aliran Data Dashboard", "Proses penggabungan data dari input hingga visualisasi")

# Input
R(sl, 0.5, 1.5, 3.8, 4.5, fill=GRAY, line=TEAL, lw=2, radius=True)
R(sl, 0.5, 1.5, 3.8, 0.6, fill=TEAL, radius=True)
T(sl, "1. Pengambilan Data (Input)", 0.5, 1.6, 3.8, 0.4, size=14, bold=True, color=WHITE, align=PP_ALIGN.CENTER)
c_items = [
    "Menghubungkan 3 sumber data: Rencana Produksi (APS), Stok Aktual (Engage), Aksesoris (CIUROX).",
    "RPA otomatis mengunduh laporan secara berkala setiap jam.",
    "Mengurangi beban kerja tim untuk input data manual."
]
bullet_list(sl, c_items, 0.7, 2.3, 3.4, size=12, gap=1.2, dot_color=TEAL)

# Process
R(sl, 4.7, 1.5, 3.8, 4.5, fill=GRAY, line=NAVY, lw=2, radius=True)
R(sl, 4.7, 1.5, 3.8, 0.6, fill=NAVY, radius=True)
T(sl, "2. Pengolahan Data (Process)", 4.7, 1.6, 3.8, 0.4, size=14, bold=True, color=WHITE, align=PP_ALIGN.CENTER)
m_items = [
    "Menggabungkan data otomatis berdasarkan nomor Order/JO.",
    "Menganalisis status kesiapan material secara real-time.",
    "Kalkulasi otomatis target harian (Demand) dan sisa hari kerja."
]
bullet_list(sl, m_items, 4.9, 2.3, 3.4, size=12, gap=1.2, dot_color=NAVY)

# Output
R(sl, 8.9, 1.5, 3.8, 4.5, fill=GRAY, line=GREEN, lw=2, radius=True)
R(sl, 8.9, 1.5, 3.8, 0.6, fill=GREEN, radius=True)
T(sl, "3. Visualisasi Pengguna (Output)", 8.9, 1.6, 3.8, 0.4, size=14, bold=True, color=WHITE, align=PP_ALIGN.CENTER)
v_items = [
    "Penyajian informasi dalam satu layar web interaktif (Single Page).",
    "Tabel prioritas berdasarkan tanggal kirim terdekat.",
    "Grafik perbandingan output aktual vs kapasitas target harian."
]
bullet_list(sl, v_items, 9.1, 2.3, 3.4, size=12, gap=1.2, dot_color=GREEN)

footer(sl, 2)


# ==========================================
# ==========================================
# 3. MANAJEMEN & PENYIMPANAN DATA
# ==========================================
sl = new_slide()
title_bar(sl, "Manajemen & Penyimpanan Data", "Rancangan penyimpanan terstruktur untuk performa cepat dan data historis")

# Engage DB
R(sl, 0.5, 1.4, 5.5, 5.0, fill=GRAY, line=NAVY, lw=2, radius=True)
T(sl, "Penyimpanan Data Aktual (Gudang Engage)", 0.7, 1.6, 5.0, 0.4, size=14, bold=True, color=NAVY)
db_items = [
    "Menyimpan log transaksi barang masuk (Inflow) dan keluar (Outflow).",
    "Pemisahan data aktif dengan arsip masa lalu secara otomatis.",
    "Menjaga database utama tetap ringan dan responsif."
]
bullet_list(sl, db_items, 0.7, 2.1, 5.1, size=11, gap=0.7, dot_color=CYAN)
T(sl, "Informasi Utama Yang Disimpan:\n- Tanggal Transaksi\n- Nomor Order / Job Order (JO)\n- Jumlah Aktual Barang (In/Out)\n- Kode Material & Deskripsi Style\n- Catatan Status Gerakan Barang",
  0.7, 4.3, 5.1, 1.9, size=11, color=DARK)

# History DB
R(sl, 6.5, 1.4, 6.3, 2.3, fill=GRAY, line=TEAL, lw=2, radius=True)
T(sl, "Rekapitulasi Harian (Optimasi Performa)", 6.7, 1.6, 5.0, 0.4, size=14, bold=True, color=TEAL)
T(sl, "Fungsi: Mengonsolidasi data transaksi barang harian agar proses pemuatan grafik performa di dashboard menjadi sangat cepat (kurang dari 1 detik).\n\nInformasi: Tanggal, total barang masuk, total barang keluar, total barang siap.",
  6.7, 2.1, 5.9, 1.5, size=11, color=DARK)

R(sl, 6.5, 4.1, 6.3, 2.3, fill=GRAY, line=GREEN, lw=2, radius=True)
T(sl, "Tren Historis & Snapshot Analisis", 6.7, 4.3, 5.0, 0.4, size=14, bold=True, color=GREEN)
T(sl, "Fungsi: Menyimpan catatan harian performa produksi untuk melihat tren historis dari waktu ke waktu.\n\nInformasi: Tanggal snapshot, kapasitas target harian, realisasi output harian, status backlog (balance).",
  6.7, 4.8, 5.9, 1.5, size=11, color=DARK)

footer(sl, 3)


# ==========================================
# 4. MOCKUP TAMPILAN
# ==========================================
sl = new_slide()
title_bar(sl, "Mockup Tampilan (UI/UX)", "One-Page Dashboard untuk Pemantauan Cepat")

R(sl, 1.5, 1.3, 10.33, 5.6, fill=RGBColor(0xEC,0xEF,0xF1), line=NAVY, lw=2)
# Header
R(sl, 1.5, 1.3, 10.33, 0.5, fill=NAVY)
T(sl, "Dashboard GM - Material Ready Production", 1.7, 1.4, 5.0, 0.3, size=12, bold=True, color=WHITE)
T(sl, "Update: 08:15 WIB", 9.5, 1.4, 2.0, 0.3, size=10, color=CYAN, align=PP_ALIGN.RIGHT)

# KPI Row
for i, (lbl, val, c) in enumerate([("Total Output", "2.840", RGBColor(0x2E,0x55,0x8A)), ("Sisa (Balance)", "460", AMBER), ("Order Ready", "12", GREEN), ("Demand / Hari", "285", TEAL)]):
    R(sl, 1.7 + i*2.5, 2.0, 2.3, 1.0, fill=c, radius=True)
    T(sl, val, 1.7 + i*2.5, 2.15, 2.3, 0.4, size=24, bold=True, color=WHITE, align=PP_ALIGN.CENTER)
    T(sl, lbl, 1.7 + i*2.5, 2.65, 2.3, 0.3, size=10, color=WHITE, align=PP_ALIGN.CENTER)

# Content area
R(sl, 1.7, 3.2, 4.9, 2.5, fill=WHITE, line=RGBColor(0xCC,0xCC,0xCC), lw=1)
T(sl, "Grafik Demand vs Output", 1.8, 3.3, 4.0, 0.3, size=11, bold=True, color=NAVY)
R(sl, 2.0, 4.0, 4.3, 1.5, fill=RGBColor(0xE3,0xF2,0xFD)) # fake chart

R(sl, 6.8, 3.2, 4.9, 2.5, fill=WHITE, line=RGBColor(0xCC,0xCC,0xCC), lw=1)
T(sl, "Material Ready (Top Priority)", 6.9, 3.3, 4.0, 0.3, size=11, bold=True, color=NAVY)
T(sl, "Order        | Tgl Kirim | Status\n---------------------------------\n1234567-1  | END AUG   | [READY]\n1234568-2  | END AUG   | [READY]\n1234569-1  | MID SEP   | [PROSES]\n1234570-3  | MID SEP   | [PENDING]", 6.9, 3.8, 4.7, 1.7, size=10)

# Alert
R(sl, 1.7, 5.9, 10.0, 0.8, fill=RGBColor(0xFF,0xEB,0xEE), line=RGBColor(0xC6,0x28,0x28), lw=1, radius=True)
T(sl, "Management Analytics (Action Plan):", 1.8, 6.0, 9.8, 0.3, size=10, bold=True, color=RGBColor(0xC6,0x28,0x28))
T(sl, "- Status: NEED ATTENTION (Output Harian < Demand)\n- Saran: Tingkatkan output menjadi 285 pcs/hari untuk capai target.", 1.8, 6.3, 9.8, 0.4, size=9, color=DARK)

footer(sl, 4)


# ==========================================
# 5. KAMUS METRIK & LOGIKA PERHITUNGAN
# ==========================================
sl = new_slide()
title_bar(sl, "Kamus Metrik & Logika Perhitungan", "Bagaimana indikator kinerja dashboard dihitung secara otomatis")

R(sl, 0.5, 1.2, 12.3, 5.8, fill=WHITE, line=NAVY, lw=2)
# Table Header
R(sl, 0.5, 1.2, 12.3, 0.4, fill=NAVY)
T(sl, "Metrik UI", 0.6, 1.3, 2.0, 0.3, size=11, bold=True, color=WHITE)
T(sl, "Sumber Data", 2.6, 1.3, 2.0, 0.3, size=11, bold=True, color=WHITE)
T(sl, "Logika Bisnis & Metode Perhitungan", 5.6, 1.3, 6.5, 0.3, size=11, bold=True, color=WHITE)

data_mapping = [
    ("Daftar Order", "Rencana Produksi (APS)", "Daftar pesanan aktif yang memiliki proses Heat Transfer (HT) pada jadwal kirim terdekat."),
    ("Total Output", "Aktual Gudang (Engage)", "Akumulasi jumlah barang keluar (Outflow) untuk pesanan tersebut di area HT."),
    ("Sisa Target (Balance)", "APS & Engage", "Target rencana produksi dikurangi dengan total output yang telah diselesaikan."),
    ("Kesiapan Order (RTL)", "APS, Engage & CIUROX", "Berstatus READY jika material masuk sudah tersedia di area HT & aksesoris pendukung lengkap."),
    ("Beban Harian (Demand)", "Kalkulasi Sistem", "Sisa target (Balance) dibagi jumlah sisa hari kerja operasional menuju batas tanggal kirim."),
    ("Prioritas Urutan", "Logika Pengurutan", "Diurutkan otomatis dari tanggal pengiriman terdekat, mendahulukan order berstatus READY.")
]

y = 1.7
for row in data_mapping:
    R(sl, 0.5, y, 12.3, 0.8, fill=GRAY if y > 1.8 and y < 3.0 or y > 3.5 and y < 5.0 else WHITE, line=RGBColor(0xDD,0xDD,0xDD), lw=1)
    T(sl, row[0], 0.6, y+0.1, 1.8, 0.6, size=11, bold=True, color=TEAL)
    T(sl, row[1], 2.6, y+0.1, 2.8, 0.6, size=10, bold=True, color=DARK)
    T(sl, row[2], 5.6, y+0.1, 6.5, 0.6, size=10, color=DARK)
    y += 0.8

footer(sl, 5)


# ==========================================
# 6. PENUTUP
# ==========================================
sl = new_slide()
R(sl, 0, 0, 13.33, 7.5, fill=NAVY)
T(sl, "Penyelarasan Desain IRL 4 Selesai", 1.0, 2.5, 11, 1.0, size=44, bold=True, color=WHITE)
T(sl, "Rencana aliran data, manajemen penyimpanan, dan logika perhitungan metrik siap untuk diintegrasikan ke tahap pengembangan.",
  1.0, 3.7, 11, 1.0, size=16, color=CYAN)
footer(sl, 6)

# SAVE
out_path = r"c:\xampp\htdocs\dashboard_gm\IRL4_Desain_dan_Struktur.pptx"
prs.save(out_path)
print(f"[OK] Saved: {out_path} ({TOTAL} slides)")
