"""
Generate PPT IRL 2 - Dashboard GM (Modul Heat Transfer)
"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.util import Inches, Pt
from pptx.enum.dml import MSO_THEME_COLOR
import copy

# ── Warna Tema ──────────────────────────────────────────────
NAVY       = RGBColor(0x1E, 0x3A, 0x5F)   # header utama
NAVY_LIGHT = RGBColor(0x2E, 0x55, 0x8A)   # aksen biru muda
TEAL       = RGBColor(0x00, 0x7A, 0x87)   # aksen teal
GREEN_HUM  = RGBColor(0xC8, 0xE6, 0xC9)   # Human Process
BLUE_SYS   = RGBColor(0xBB, 0xDE, 0xFB)   # System Process
GRAY_ROW   = RGBColor(0xF5, 0xF7, 0xFA)   # baris tabel alternating
WHITE      = RGBColor(0xFF, 0xFF, 0xFF)
DARK_TEXT  = RGBColor(0x1A, 0x1A, 0x2E)
RISK_RED   = RGBColor(0xD3, 0x2F, 0x2F)
WATCH_ORG  = RGBColor(0xF5, 0x7C, 0x00)
GOOD_GRN   = RGBColor(0x2E, 0x7D, 0x32)
ACCENT     = RGBColor(0x00, 0xBF, 0xD8)   # cyan accent

prs = Presentation()
prs.slide_width  = Inches(13.33)
prs.slide_height = Inches(7.5)

blank_layout = prs.slide_layouts[6]  # completely blank


# ════════════════════════════════════════════════════════════
# HELPERS
# ════════════════════════════════════════════════════════════

def add_rect(slide, l, t, w, h, fill=None, line_color=None, line_w=None):
    shape = slide.shapes.add_shape(1, Inches(l), Inches(t), Inches(w), Inches(h))
    shape.line.fill.background()
    if fill:
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill
    else:
        shape.fill.background()
    if line_color:
        shape.line.color.rgb = line_color
        shape.line.width = Pt(line_w or 1)
    else:
        shape.line.fill.background()
    return shape


def add_text(slide, text, l, t, w, h,
             size=11, bold=False, color=DARK_TEXT,
             align=PP_ALIGN.LEFT, wrap=True, italic=False):
    txBox = slide.shapes.add_textbox(Inches(l), Inches(t), Inches(w), Inches(h))
    tf = txBox.text_frame
    tf.word_wrap = wrap
    p = tf.paragraphs[0]
    p.alignment = align
    run = p.add_run()
    run.text = text
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = color
    return txBox


def section_header(slide, title, l, t, w, h=0.38):
    add_rect(slide, l, t, w, h, fill=NAVY)
    add_text(slide, title, l+0.08, t+0.03, w-0.1, h-0.06,
             size=11, bold=True, color=WHITE, align=PP_ALIGN.LEFT)


def table_header_row(slide, cols, col_widths, l, t, row_h=0.35):
    x = l
    for i, col in enumerate(cols):
        add_rect(slide, x, t, col_widths[i], row_h, fill=NAVY_LIGHT)
        add_text(slide, col, x+0.06, t+0.04, col_widths[i]-0.1, row_h-0.08,
                 size=9.5, bold=True, color=WHITE, align=PP_ALIGN.LEFT)
        x += col_widths[i]


def table_data_rows(slide, rows, col_widths, l, t, row_h=0.30, start_offset=0):
    y = t
    for ri, row in enumerate(rows):
        x = l
        fill = GRAY_ROW if (ri + start_offset) % 2 == 0 else WHITE
        for ci, cell in enumerate(row):
            add_rect(slide, x, y, col_widths[ci], row_h, fill=fill,
                     line_color=RGBColor(0xDD,0xDD,0xDD), line_w=0.5)
            # bold first col
            bold = (ci == 0)
            add_text(slide, str(cell), x+0.06, y+0.03, col_widths[ci]-0.1, row_h-0.06,
                     size=9, bold=bold, color=DARK_TEXT)
            x += col_widths[ci]
        y += row_h
    return y   # return next y


def slide_title_bar(slide, title, subtitle=None):
    add_rect(slide, 0, 0, 13.33, 1.0, fill=NAVY)
    add_text(slide, title, 0.3, 0.1, 12.5, 0.5,
             size=20, bold=True, color=WHITE, align=PP_ALIGN.LEFT)
    if subtitle:
        add_text(slide, subtitle, 0.3, 0.58, 12.5, 0.35,
                 size=11, bold=False, color=ACCENT, align=PP_ALIGN.LEFT)


def add_bullet(slide, items, l, t, w, h, size=10, title=None, title_size=10.5):
    if title:
        add_text(slide, title, l, t, w, 0.3, size=title_size, bold=True, color=NAVY)
        t += 0.28
    txBox = slide.shapes.add_textbox(Inches(l), Inches(t), Inches(w), Inches(h))
    tf = txBox.text_frame
    tf.word_wrap = True
    first = True
    for item in items:
        if first:
            p = tf.paragraphs[0]
            first = False
        else:
            p = tf.add_paragraph()
        p.space_before = Pt(3)
        run = p.add_run()
        run.text = item
        run.font.size = Pt(size)
        run.font.color.rgb = DARK_TEXT


def flow_box(slide, text, l, t, w=2.1, h=0.42, is_system=False, is_oval=False):
    fill = BLUE_SYS if is_system else GREEN_HUM
    if is_oval:
        shape = slide.shapes.add_shape(9, Inches(l), Inches(t), Inches(w), Inches(h))  # oval=9
        shape.fill.solid()
        shape.fill.fore_color.rgb = NAVY
        shape.line.color.rgb = NAVY
        tf = shape.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.alignment = PP_ALIGN.CENTER
        run = p.add_run()
        run.text = text
        run.font.size = Pt(9)
        run.font.bold = True
        run.font.color.rgb = WHITE
    else:
        shape = slide.shapes.add_shape(1, Inches(l), Inches(t), Inches(w), Inches(h))
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill
        shape.line.color.rgb = NAVY_LIGHT
        shape.line.width = Pt(0.75)
        tf = shape.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.alignment = PP_ALIGN.CENTER
        run = p.add_run()
        run.text = text
        run.font.size = Pt(8.5)
        run.font.bold = False
        run.font.color.rgb = DARK_TEXT
    return shape


def arrow_down(slide, l, t, h=0.22):
    """Vertical arrow pointing down."""
    line = slide.shapes.add_connector(1, Inches(l), Inches(t), Inches(l), Inches(t + h))
    line.line.color.rgb = NAVY
    line.line.width = Pt(1.5)


def page_number(slide, num, total):
    add_text(slide, f"{num} / {total}", 12.5, 7.2, 0.7, 0.25,
             size=8, color=RGBColor(0xAA,0xAA,0xAA), align=PP_ALIGN.RIGHT)


def footer_bar(slide):
    add_rect(slide, 0, 7.25, 13.33, 0.25, fill=NAVY)
    add_text(slide, "Dashboard GM — Modul Heat Transfer  |  IRL 2 Document",
             0.2, 7.26, 10, 0.22, size=7.5, color=RGBColor(0xBB,0xCC,0xDD))


TOTAL_SLIDES = 10


# ════════════════════════════════════════════════════════════
# SLIDE 1 — COVER
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)

add_rect(sl, 0, 0, 13.33, 7.5, fill=NAVY)
add_rect(sl, 0, 2.8, 13.33, 2.2, fill=NAVY_LIGHT)

# Accent line
add_rect(sl, 0.5, 2.75, 0.08, 2.3, fill=ACCENT)

add_text(sl, "IRL 2", 0.8, 2.85, 12, 0.6, size=14, bold=True, color=ACCENT)
add_text(sl,
         "Analisis Proses Capture Data Existing, Sumber Data,\nField yang Dibutuhkan, serta Penyusunan Project Scope & Process Flow",
         0.8, 3.35, 11.5, 1.1, size=22, bold=True, color=WHITE)
add_text(sl, "Dashboard GM — Modul Heat Transfer", 0.8, 4.42, 11, 0.4,
         size=13, color=ACCENT)

add_text(sl, "Dibuat: Agustus 2026  |  Versi: 1.0",
         0.8, 6.9, 6, 0.3, size=9, color=RGBColor(0x88,0x99,0xAA))

# Decorative dots
for i in range(8):
    x = 10.5 + (i % 4) * 0.5
    y = 1.0 + (i // 4) * 0.5
    add_rect(sl, x, y, 0.18, 0.18, fill=ACCENT)


# ════════════════════════════════════════════════════════════
# SLIDE 2 — RINGKASAN EKSEKUTIF
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Ringkasan Eksekutif", "Latar Belakang & Tujuan Proyek")

add_text(sl, "Dashboard GM", 0.4, 1.15, 5.8, 0.35, size=13, bold=True, color=NAVY)
add_text(sl,
         "Platform pemantauan produksi internal berbasis web yang memetakan, menganalisis, "
         "dan memvisualisasikan data operasional secara real-time.",
         0.4, 1.48, 5.8, 0.6, size=10, color=DARK_TEXT)

# 3 source cards
cards = [
    ("🏭  APS", "Rencana & Pengiriman\n(Delivery / PDK)\nSistem IOS-APS"),
    ("📦  Engage", "Aktual Produksi\n(Inflow / Outflow)\nWarehouse Engage"),
    ("🪡  CIUROX", "Kesiapan Aksesoris\n(Accessories)\nSistem CIUROX"),
]
card_colors = [NAVY_LIGHT, TEAL, RGBColor(0x1B, 0x5E, 0x20)]
for i, (title, desc) in enumerate(cards):
    cx = 0.4 + i * 2.05
    add_rect(sl, cx, 2.2, 1.9, 1.5, fill=card_colors[i])
    add_text(sl, title, cx+0.1, 2.28, 1.7, 0.38, size=10.5, bold=True, color=WHITE)
    add_text(sl, desc, cx+0.1, 2.62, 1.7, 0.98, size=9, color=WHITE)

# RPA highlight box
add_rect(sl, 0.4, 3.88, 5.8, 0.7, fill=RGBColor(0xE3,0xF2,0xFD),
         line_color=NAVY_LIGHT, line_w=1)
add_text(sl, "⚙️  Teknologi Otomasi: Robotic Process Automation (RPA)",
         0.55, 3.92, 5.5, 0.28, size=10, bold=True, color=NAVY)
add_text(sl, "Playwright Python (Engage & Accessories)  +  Windows GUI Automation (APS)",
         0.55, 4.18, 5.5, 0.28, size=9, color=DARK_TEXT)

# Right: Key Metrics
add_rect(sl, 6.6, 1.1, 6.4, 5.8, fill=GRAY_ROW, line_color=RGBColor(0xDD,0xDD,0xDD), line_w=0.5)
add_text(sl, "Metrik Utama Dashboard", 6.75, 1.18, 6, 0.32, size=11, bold=True, color=NAVY)

metrics = [
    ("📊 Total Output",         "Akumulasi output aktual dari Engage Outflow"),
    ("⚖️ Balance Qty",           "Sisa qty: PDK APS − Output Aktual"),
    ("✅ Ready to Load (RTL)",  "Gabungan APS + Engage + CIUROX"),
    ("📅 Demand Harian",        "Balance ÷ Remaining Workdays"),
    ("🔺 Prioritas Order",       "Berdasarkan delivery date terdekat"),
    ("📈 Management Analytics", "Insight, status, CAP, action plan"),
]
for i, (m, d) in enumerate(metrics):
    y = 1.55 + i * 0.75
    add_rect(sl, 6.7, y, 6.1, 0.65, fill=WHITE,
             line_color=RGBColor(0xCC,0xCC,0xCC), line_w=0.5)
    add_text(sl, m, 6.85, y+0.04, 5.8, 0.28, size=10, bold=True, color=NAVY)
    add_text(sl, d, 6.85, y+0.3, 5.8, 0.28, size=9, color=DARK_TEXT)

footer_bar(sl)
page_number(sl, 2, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 3 — EXISTING DATA SOURCE & USER REQUIREMENT
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Existing Data Source & User Requirement", "Sumber Data & Kebutuhan Pengguna")

# --- LEFT: Existing Data Source ---
section_header(sl, "1. Existing Data Source", 0.35, 1.1, 6.0)
table_header_row(sl, ["Source", "Fungsi"], [1.3, 4.7], 0.35, 1.48)
rows_ds = [
    ("APS",    "Master Data Order — JO Tracking (JO.xlsx)"),
    ("Engage", "Master Data IN & OUT Transaksi — Database MySQL"),
    ("CIUROX", "Ready Accessories — CONTROLIST.xlsx"),
]
table_data_rows(sl, rows_ds, [1.3, 4.7], 0.35, 1.83, row_h=0.38)

# --- LEFT: Scope ---
section_header(sl, "4. Scope", 0.35, 3.15, 6.0)
table_header_row(sl, ["Lingkup Fitur"], [6.0], 0.35, 3.53)
scope_items = [
    ("Material Ready Dashboard",),
    ("Heat Transfer Dashboard",),
    ("Integrasi APS (JO Tracking)",),
    ("Integrasi Engage (IN/OUT Transaksi)",),
    ("Integrasi CIUROX (Accessories Controlist)",),
    ("Capture Data Otomatis via RPA",),
    ("Monitoring Material Ready",),
]
table_data_rows(sl, scope_items, [6.0], 0.35, 3.91, row_h=0.32)

# --- RIGHT: User Requirement ---
section_header(sl, "3. User Requirement", 6.65, 1.1, 6.35)
table_header_row(sl, ["User", "Requirement"], [1.8, 4.55], 6.65, 1.48)
rows_ur = [
    ("Operator",    "Mengetahui hasil scan IN & OUT material"),
    ("Line Leader", "Monitoring Output produksi"),
    ("Supervisor",  "Monitoring Ready Material"),
    ("Planner",     "Planning Loading berdasarkan data ready"),
    ("Management",  "Summary Dashboard — ringkasan status produksi"),
]
table_data_rows(sl, rows_ur, [1.8, 4.55], 6.65, 1.83, row_h=0.38)

# Catatan box
add_rect(sl, 6.65, 3.75, 6.35, 2.8, fill=RGBColor(0xFF, 0xF8, 0xE1),
         line_color=RGBColor(0xF9, 0xA8, 0x25), line_w=1)
add_text(sl, "📌 Catatan Akses per Role", 6.8, 3.82, 6.0, 0.3,
         size=10, bold=True, color=RGBColor(0xE6, 0x5C, 0x00))
notes = [
    "• Operator  → Input scan & lihat status IN/OUT",
    "• Line Leader → Monitor progress output harian",
    "• Supervisor → Monitor ready material & approve",
    "• Planner   → Planning loading ke produksi",
    "• Management → Dashboard ringkasan eksekutif",
]
add_bullet(sl, notes, 6.8, 4.15, 6.0, 2.2, size=9.5)

footer_bar(sl)
page_number(sl, 3, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 4 — DATA REQUIREMENT (FIELD DASHBOARD)
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Data Requirement — Field Dashboard", "Mapping field tampilan dashboard dengan sumber datanya")

section_header(sl, "2. Data Requirement — Field Dashboard & Sumber Data", 0.35, 1.1, 12.65)
table_header_row(sl, ["Field Dashboard", "Sumber", "Keterangan / Formula"],
                 [2.2, 2.6, 7.85], 0.35, 1.48)
rows_dr = [
    ("Order",              "APS",                       "Nomor Job Order dari JO.xlsx"),
    ("Style",              "APS",                       "Kode / nama desain dari kolom Factory Style / Cust. Style"),
    ("Tgl. Delivery",      "APS",                       "Tanggal target pengiriman dari kolom Delivery Date"),
    ("Qty PDK",            "APS",                       "Target rencana produksi dari kolom HEAT TRANSFER Plan Qty"),
    ("Qty IN & OUT Panel", "Engage (MySQL)",             "Transaksi inflow (+) dan outflow (−) dari tb_engage_transactions & tb_engage_archieve"),
    ("Qty Material Ready", "APS + Engage + CIUROX ✅",  "Gabungan: order dari APS ↔ stok Engage (inflow−outflow) ↔ COMPLETED di CIUROX"),
    ("Balance",            "Calculation",               "Qty PDK − Qty Output"),
    ("Working Days",       "Configured by SPV",         "Kalender kerja dikonfigurasi admin / SPV via dashboard"),
    ("Remaining Workdays", "Calculation",               "Jumlah hari kerja tersisa dari hari ini hingga akhir periode delivery aktif"),
    ("Demand",             "Calculation",               "Balance ÷ Remaining Workdays → beban harian minimum agar target tercapai"),
]
ny = table_data_rows(sl, rows_dr, [2.2, 2.6, 7.85], 0.35, 1.83, row_h=0.38)

# Formula box
add_rect(sl, 0.35, ny+0.1, 12.65, 1.15, fill=RGBColor(0xE8,0xF5,0xE9),
         line_color=RGBColor(0x2E,0x7D,0x32), line_w=1)
add_text(sl, "📐 Formula Utama", 0.55, ny+0.16, 12.0, 0.28, size=10, bold=True, color=GOOD_GRN)
formulas = (
    "  Balance = Qty PDK − Qty Output        |        "
    "Remaining Workdays = Σ NilaiHari(t) dari hari ini s.d akhir periode        |        "
    "Demand = Balance ÷ Remaining Workdays"
)
add_text(sl, formulas, 0.55, ny+0.42, 12.3, 0.35, size=9.5, color=DARK_TEXT)
add_text(sl,
         "NilaiHari:  Hari kerja biasa / Lembur Minggu = 1.0  |  Setengah hari = 0.5  |  "
         "Seperempat hari = 0.25  |  Libur / Minggu biasa = 0.0",
         0.55, ny+0.75, 12.3, 0.35, size=9, italic=True, color=RGBColor(0x33,0x69,0x1E))

footer_bar(sl)
page_number(sl, 4, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 5 — ANALISIS CAPTURE DATA (MEKANISME RPA)
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Analisis Proses Capture Data", "Mekanisme otomasi pengumpulan data dari sistem legacy via RPA")

# Flow horizontal: Scheduler → 3 RPA → Output → DB → Dashboard
boxes = [
    ("Master\nScheduler\nrpa/scheduler.py", NAVY),
    ("Accessories RPA\nPlaywright Python\n→ CONTROLIST.xlsx", RGBColor(0x1B,0x5E,0x20)),
    ("Engage RPA\nPlaywright Python\n→ MySQL Sync", TEAL),
    ("APS RPA\nWindows GUI\n→ JO.xlsx", NAVY_LIGHT),
    ("Clean Data\n& Kalkulasi\nDashboard_model.php", RGBColor(0x4A,0x14,0x8C)),
    ("Web Dashboard\n& Analytics\nFrontend CI3", RGBColor(0x01,0x57,0x9B)),
]

bw, bh = 1.85, 1.15
start_x = 0.35
y_main = 1.2

for i, (label, color) in enumerate(boxes):
    bx = start_x + i * 2.18
    shape = sl.shapes.add_shape(1, Inches(bx), Inches(y_main), Inches(bw), Inches(bh))
    shape.fill.solid()
    shape.fill.fore_color.rgb = color
    shape.line.color.rgb = WHITE
    shape.line.width = Pt(1)
    tf = shape.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = label
    run.font.size = Pt(8.5)
    run.font.bold = True
    run.font.color.rgb = WHITE

    if i < len(boxes)-1:
        ax = bx + bw + 0.03
        add_text(sl, "→", ax, y_main+0.35, 0.3, 0.45, size=16, bold=True,
                 color=NAVY, align=PP_ALIGN.CENTER)

# Detail cards below each RPA
details = [
    None,
    "• Login CIUROX\n• Filter: awal bulan s.d hari ini\n• Status: COMPLETED\n• Download CONTROLIST.xlsx",
    "• Login Engage web\n• Storage 32 & 32a, dir=0\n• 10 hari terakhir\n• Sync → MySQL via PHP helper",
    "• Buka IOS-APS\n• Login & filter Route HT\n• Export JO.xlsx\n• Periode: bulan lalu s.d +2 bln",
    "• Baca JO.xlsx & CONTROLIST\n• Query MySQL Engage\n• Hitung Balance, RTL, Demand\n• Simpan ke dashboard_heat_history",
    "• Render Chart & Tabel\n• API JSON endpoint\n• Kalender kerja\n• Analytics & CAP",
]

for i, detail in enumerate(details):
    if detail:
        bx = start_x + i * 2.18
        add_rect(sl, bx, 2.5, bw, 2.55, fill=GRAY_ROW,
                 line_color=RGBColor(0xCC,0xCC,0xCC), line_w=0.5)
        add_text(sl, detail, bx+0.08, 2.55, bw-0.15, 2.4, size=8.5, color=DARK_TEXT)

# Timing info
add_rect(sl, 0.35, 5.25, 12.65, 0.7, fill=RGBColor(0xE3,0xF2,0xFD),
         line_color=NAVY_LIGHT, line_w=1)
add_text(sl, "⏱  Jadwal Eksekusi Otomatis", 0.55, 5.3, 5, 0.28, size=10, bold=True, color=NAVY)
add_text(sl,
         "Accessories & Engage: setiap 1 jam  |  APS: setiap 2 jam  |  "
         "Aktif: 07:00 – 23:00 (Windows Server Admin)  |  File lock: scheduler.lock",
         0.55, 5.56, 12.3, 0.3, size=9, color=DARK_TEXT)

footer_bar(sl)
page_number(sl, 5, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 6 — SUMBER DATA & FIELD DETAIL
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Sumber Data & Field yang Dibutuhkan", "Data dictionary per sumber RPA")

# ---- APS ----
section_header(sl, "A. APS — JO.xlsx  (Filter: Route mengandung 'HT')", 0.35, 1.1, 12.65)
table_header_row(sl, ["Field (Excel Header)", "Tipe", "Kegunaan"],
                 [3.5, 1.2, 7.95], 0.35, 1.48)
aps_rows = [
    ("JO / Order No.",                   "String", "Kunci relasi utama (Order ID)"),
    ("Process Route / Route Name",        "String", "Validasi rute produksi — wajib mengandung sub-string 'HT'"),
    ("HEAT TRANSFER Plan Qty",            "Int",    "Target rencana produksi modul Heat Transfer"),
    ("HEAT TRANSFER Qty.",               "Int",    "Qty sudah selesai menurut catatan APS"),
    ("HEAT TRANSFER Non-finished Qty",   "Int",    "Sisa (balance) rencana yang belum selesai"),
    ("Delivery Date",                     "Date",   "Penentu periode target pengiriman (MID / END bulan)"),
    ("Factory Style / Cust. Style",       "String", "Kode / nama desain pakaian untuk visualisasi UI"),
]
ny = table_data_rows(sl, aps_rows, [3.5, 1.2, 7.95], 0.35, 1.83, row_h=0.32)

# ---- CIUROX ----
section_header(sl, "C. CIUROX — CONTROLIST.xlsx  (Filter: Status = 'COMPLETED')", 0.35, ny+0.1, 12.65)
table_header_row(sl, ["Field (Excel Header)", "Tipe", "Kegunaan"],
                 [3.5, 1.2, 7.95], 0.35, ny+0.48)
acc_rows = [
    ("Order",          "String", "Nomor Order untuk dicocokkan dengan order aktif di APS"),
    ("Status Pesanan", "String", "Penanda kesiapan. Jika COMPLETED → aksesoris siap mendukung produksi"),
]
ny2 = table_data_rows(sl, acc_rows, [3.5, 1.2, 7.95], 0.35, ny+0.83, row_h=0.32)

# Note box Engage
add_rect(sl, 0.35, ny2+0.12, 12.65, 1.1, fill=RGBColor(0xE0,0xF7,0xFA),
         line_color=TEAL, line_w=1)
add_text(sl, "B. Engage — Database MySQL (tb_engage_transactions & tb_engage_archieve)",
         0.55, ny2+0.17, 12.0, 0.3, size=10, bold=True, color=TEAL)
add_text(sl,
         "Kolom utama: date (tanggal transaksi)  |  cost_center (nomor order)  |  qty (positif=Input, negatif=Output)  |  "
         "udef_1 (Style)  |  text (catatan filter)\n"
         "Filter Storage 32: Udef5/4='rpl' OR Udef6='sk' OR text ∈ {rpl, ts, return, koreksi, sk}  |  "
         "Filter Storage 32a: Udef5/4='rpl' OR Udef6='sk' OR text ∈ {csdb, csbd, ts}",
         0.55, ny2+0.47, 12.3, 0.65, size=8.8, color=DARK_TEXT)

footer_bar(sl)
page_number(sl, 6, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 7 — PROCESS FLOW (EXISTING vs PROPOSED)
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Process Flow", "Existing Flow (Manual) vs Proposed Flow (Otomatis)")

# ---- EXISTING FLOW (left) ----
add_rect(sl, 0.35, 1.08, 5.9, 0.32, fill=NAVY_LIGHT)
add_text(sl, "EXISTING FLOW  —  Proses Manual", 0.45, 1.1, 5.7, 0.28,
         size=10, bold=True, color=WHITE)

ex_steps = [
    ("Start",                                  True,  True),
    ("Operator scan Material",                 False, False),
    ("Operator login APS & download JO.xlsx",  False, False),
    ("Operator login Engage & download data",  False, False),
    ("Tunggu Email Controlist dari Accessories", False, False),
    ("Pengolahan & Rekap Data di Excel Manual", False, False),
    ("Kirim Laporan Manual ke Supervisor",     False, False),
    ("Verifikasi data?",                       False, False),   # diamond — fake as box
    ("Operator Menyampaikan Laporan",          False, False),
    ("Supervisor & Planning Review",           False, False),
    ("Finish",                                 True,  True),
]

cx = 0.35 + 5.9/2 - 1.05
y = 1.45
for label, is_oval, is_sys in ex_steps:
    flow_box(sl, label, cx, y, w=2.1, h=0.38, is_system=is_sys, is_oval=is_oval)
    y += 0.38
    if label != "Finish":
        arrow_down(sl, cx + 1.05, y, 0.12)
        y += 0.12

# ---- PROPOSED FLOW (right) ----
add_rect(sl, 6.75, 1.08, 6.2, 0.32, fill=TEAL)
add_text(sl, "PROPOSED FLOW  —  Otomasi Sistem", 6.85, 1.1, 6.0, 0.28,
         size=10, bold=True, color=WHITE)

prop_steps = [
    ("Start",                                           True,  False),
    ("Operator scan Material",                          False, False),  # still human
    ("RPA Automated Data Capture\n(APS + Engage + CIUROX)", False, True),
    ("Database & Data Cleansing\n(MySQL)", False, True),
    ("Web Dashboard & Analytics",          False, True),
    ("Supervisor & Planning Review",       False, False),
    ("Finish",                             True,  False),
]

cx2 = 6.75 + 6.2/2 - 1.05
y2 = 1.45
for label, is_oval, is_sys in prop_steps:
    flow_box(sl, label, cx2, y2, w=2.1, h=0.48, is_system=is_sys, is_oval=is_oval)
    y2 += 0.48
    if label != "Finish":
        arrow_down(sl, cx2 + 1.05, y2, 0.14)
        y2 += 0.14

# LEGEND
add_rect(sl, 6.75, 6.55, 6.2, 0.65, fill=GRAY_ROW,
         line_color=RGBColor(0xCC,0xCC,0xCC), line_w=0.5)
add_text(sl, "Keterangan:", 6.9, 6.58, 2, 0.25, size=9, bold=True, color=NAVY)
add_rect(sl, 7.6, 6.65, 0.28, 0.18, fill=GREEN_HUM,
         line_color=NAVY_LIGHT, line_w=0.5)
add_text(sl, "Human Process", 7.95, 6.63, 2, 0.24, size=9, color=DARK_TEXT)
add_rect(sl, 10.0, 6.65, 0.28, 0.18, fill=BLUE_SYS,
         line_color=NAVY_LIGHT, line_w=0.5)
add_text(sl, "System Process", 10.35, 6.63, 2, 0.24, size=9, color=DARK_TEXT)

footer_bar(sl)
page_number(sl, 7, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 8 — PROJECT SCOPE (IN-SCOPE & OUT-OF-SCOPE)
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Project Scope", "Batasan lingkup pengembangan Dashboard GM — Modul Heat Transfer")

# IN-SCOPE
add_rect(sl, 0.35, 1.1, 6.1, 0.38, fill=RGBColor(0x2E,0x7D,0x32))
add_text(sl, "✅  Fitur Masuk Lingkup (In-Scope)", 0.5, 1.14, 5.8, 0.3,
         size=11, bold=True, color=WHITE)

in_scope = [
    ("Master Scheduler Core",
     "Otomasi berjalan di background Windows Server. Tidak perlu trigger manual dari user."),
    ("Excel Data Parsing Engine",
     "Baca .xlsx hemat RAM langsung dari zip/xml. Fuzzy header matching untuk antisipasi perubahan kolom."),
    ("Logika Bisnis Dashboard",
     "Total Output, Balance Qty, Ready to Load (APS+Engage+CIUROX), Demand Harian."),
    ("Kalender Kerja & Hari Libur",
     "Manajemen kalender Heat (libur, setengah hari, lembur). Auth session PHP."),
    ("Database Sinkronisasi & Caching",
     "Sync transaksi ke tb_engage_transactions, arsip ke tb_engage_archieve, harian ke engage_daily_history."),
    ("Material Ready Dashboard",
     "Modul baru monitoring material ready lintas role: Operator, Line Leader, Supervisor, Planner, Management."),
]
y = 1.55
for title, desc in in_scope:
    add_rect(sl, 0.35, y, 6.1, 0.72, fill=RGBColor(0xF1,0xF8,0xE9),
             line_color=RGBColor(0x81,0xC7,0x84), line_w=0.5)
    add_text(sl, title, 0.5, y+0.04, 5.8, 0.26, size=9.5, bold=True, color=GOOD_GRN)
    add_text(sl, desc, 0.5, y+0.28, 5.8, 0.36, size=8.8, color=DARK_TEXT)
    y += 0.76

# OUT-OF-SCOPE
add_rect(sl, 6.75, 1.1, 6.23, 0.38, fill=RISK_RED)
add_text(sl, "❌  Fitur di Luar Lingkup (Out-of-Scope)", 6.9, 1.14, 6.0, 0.3,
         size=11, bold=True, color=WHITE)

out_scope = [
    ("Modifikasi Aplikasi Legacy",
     "Tidak ada perubahan fitur pada IOS-APS, Engage, atau CIUROX. Jika legacy down, RPA gagal."),
    ("Koneksi Database Langsung ke ERP",
     "Data diambil via RPA & scraping Excel/web — bukan koneksi DB langsung (kendala akses & keamanan)."),
    ("Modul Dashboard Selain Heat Transfer",
     "Dokumen ini fokus pada Heat Transfer. Modul cutting, sewing, dll. didefinisikan di scope terpisah."),
]
y2 = 1.55
for title, desc in out_scope:
    add_rect(sl, 6.75, y2, 6.23, 0.9, fill=RGBColor(0xFF,0xEB,0xEE),
             line_color=RGBColor(0xEF,0x9A,0x9A), line_w=0.5)
    add_text(sl, title, 6.9, y2+0.05, 5.9, 0.28, size=9.5, bold=True, color=RISK_RED)
    add_text(sl, desc, 6.9, y2+0.34, 5.9, 0.44, size=8.8, color=DARK_TEXT)
    y2 += 0.96

footer_bar(sl)
page_number(sl, 8, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 9 — LOGIKA PERHITUNGAN
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Logika Bisnis & Rumus Perhitungan", "Formula utama yang digunakan backend Dashboard_model.php")

calcs = [
    ("📊  QTY PDK vs Output",
     "Periode delivery dibentuk dari Delivery Date APS:\n"
     "  • Tanggal 1–15      →  MID <bulan>\n"
     "  • Tanggal 16–akhir  →  END <bulan>\n\n"
     "QTY PDK = total Qty Plan APS pada periode tsb.\n"
     "QTY Output = total output Engage yang cocok order/periode.\n"
     "Balance = max(0, QTY PDK − QTY Output)",
     NAVY),
    ("✅  Ready to Load (RTL)",
     "Sumber gabungan tiga sistem:\n"
     "  1. APS → daftar order aktif\n"
     "  2. Engage → stok inflow − outflow per order\n"
     "  3. CIUROX → konfirmasi status COMPLETED\n\n"
     "Order dianggap ready jika: ada di APS + stok Engage tersedia + aksesoris COMPLETED CIUROX.",
     TEAL),
    ("📅  Demand Harian",
     "Remaining Workdays = Σ NilaiHari(t)  dari hari ini s.d akhir periode\n\n"
     "  Hari kerja / lembur Minggu  = 1.0\n"
     "  Setengah hari               = 0.5\n"
     "  Seperempat hari             = 0.25\n"
     "  Libur / Minggu biasa        = 0.0\n\n"
     "Demand Harian = Balance ÷ Remaining Workdays",
     NAVY_LIGHT),
    ("⚠️  Required Daily Output",
     "Export Remaining Workdays = Remaining Workdays − 4 hari buffer export\n\n"
     "Required Daily Output = Balance periode berjalan ÷ Export Remaining Workdays\n\n"
     "Status:\n"
     "  • Avg Output ≥ Required       → good (On Track)\n"
     "  • Avg Output ≥ Required × 90% → watch (Need Attention)\n"
     "  • Di bawah itu                → risk (At Risk)",
     RGBColor(0xE6,0x5C,0x00)),
]

positions = [(0.35, 1.1, 6.1), (6.65, 1.1, 6.25), (0.35, 4.3, 6.1), (6.65, 4.3, 6.25)]
for i, ((title, body, color), (lx, ly, lw)) in enumerate(zip(calcs, positions)):
    add_rect(sl, lx, ly, lw, 0.35, fill=color)
    add_text(sl, title, lx+0.1, ly+0.04, lw-0.15, 0.28,
             size=10.5, bold=True, color=WHITE)
    add_rect(sl, lx, ly+0.35, lw, 2.8, fill=GRAY_ROW,
             line_color=RGBColor(0xCC,0xCC,0xCC), line_w=0.5)
    add_text(sl, body, lx+0.12, ly+0.42, lw-0.2, 2.65, size=9, color=DARK_TEXT)

footer_bar(sl)
page_number(sl, 9, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SLIDE 10 — KESIMPULAN & REKOMENDASI
# ════════════════════════════════════════════════════════════
sl = prs.slides.add_slide(blank_layout)
slide_title_bar(sl, "Kesimpulan & Rekomendasi Teknis", "Temuan analisis IRL 2 dan langkah teknis selanjutnya")

# Left: Kesimpulan
add_rect(sl, 0.35, 1.1, 5.9, 0.35, fill=NAVY)
add_text(sl, "Kesimpulan Analisis", 0.5, 1.14, 5.6, 0.27, size=11, bold=True, color=WHITE)

conclusions = [
    ("Stabilitas Data", "Data transaksi Engage diproses langsung dari MySQL — lebih stabil vs parsing Excel besar."),
    ("Otomasi Penuh",   "Capture data sepenuhnya otomatis via RPA scheduler (07:00–23:00). Tidak ada intervensi manual."),
    ("3 Sumber Terintegrasi", "APS + Engage + CIUROX terintegrasi menghasilkan metrik Material Ready yang akurat."),
    ("Kalkulasi Real-time", "Balance, Demand, RTL dihitung ulang setiap kali data terbaru tersedia dari RPA."),
]
y = 1.5
for title, desc in conclusions:
    add_rect(sl, 0.35, y, 5.9, 0.75, fill=WHITE,
             line_color=RGBColor(0xCC,0xCC,0xCC), line_w=0.5)
    add_rect(sl, 0.35, y, 0.06, 0.75, fill=ACCENT)
    add_text(sl, title, 0.55, y+0.06, 5.5, 0.26, size=9.5, bold=True, color=NAVY)
    add_text(sl, desc,  0.55, y+0.34, 5.5, 0.34, size=9, color=DARK_TEXT)
    y += 0.8

# Right: Rekomendasi
add_rect(sl, 6.55, 1.1, 6.45, 0.35, fill=TEAL)
add_text(sl, "Rekomendasi Teknis", 6.7, 1.14, 6.2, 0.27, size=11, bold=True, color=WHITE)

recs = [
    ("🔍  Monitor Log Sinkronisasi",
     "Pantau rpa/logs/scheduler.log secara rutin untuk deteksi duplikat data atau deadlock saat sync Engage."),
    ("🔒  Hindari File Lock",
     "Jangan buka JO.xlsx atau CONTROLIST.xlsx secara manual saat RPA berjalan — dapat menyebabkan Permission Error."),
    ("📈  Optimasi Query Database",
     "Tambah indeks pada kolom date dan cost_center di tb_engage_archieve agar query bulanan tetap cepat seiring data menumpuk."),
    ("🆕  Pengembangan Modul Berikutnya",
     "Material Ready Dashboard, monitoring multi-role, dan dashboard cutting/sewing didefinisikan di scope terpisah."),
]
y2 = 1.5
for title, desc in recs:
    add_rect(sl, 6.55, y2, 6.45, 0.9, fill=RGBColor(0xE0,0xF7,0xFA),
             line_color=TEAL, line_w=0.5)
    add_text(sl, title, 6.7, y2+0.06, 6.1, 0.28, size=9.5, bold=True, color=TEAL)
    add_text(sl, desc,  6.7, y2+0.36, 6.1, 0.46, size=9, color=DARK_TEXT)
    y2 += 0.96

footer_bar(sl)
page_number(sl, 10, TOTAL_SLIDES)


# ════════════════════════════════════════════════════════════
# SAVE
# ════════════════════════════════════════════════════════════
out_path = r"c:\xampp\htdocs\dashboard_gm\IRL2_Dashboard_GM.pptx"
prs.save(out_path)
print(f"[OK] PPT berhasil dibuat: {out_path}")
print(f"     Total slides: {len(prs.slides)}")
