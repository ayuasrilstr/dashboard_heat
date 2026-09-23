"""
Generate 1-slide PPT — IRL 2 Dashboard GM
Layout: Title bar | Col1: Data Source + Data Req | Col2: Scope + User Req | Col3+4: Flow charts
"""

from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN

# ── Warna ───────────────────────────────────────────────────
NAVY       = RGBColor(0x1E, 0x3A, 0x5F)
NAVY_LIGHT = RGBColor(0x2E, 0x55, 0x8A)
TEAL       = RGBColor(0x00, 0x7A, 0x87)
GREEN_HUM  = RGBColor(0xC8, 0xE6, 0xC9)
BLUE_SYS   = RGBColor(0xBB, 0xDE, 0xFB)
GRAY_ROW   = RGBColor(0xF4, 0xF6, 0xF9)
WHITE      = RGBColor(0xFF, 0xFF, 0xFF)
DARK_TEXT  = RGBColor(0x1A, 0x1A, 0x2E)
BORDER     = RGBColor(0xCC, 0xCC, 0xCC)

prs = Presentation()
prs.slide_width  = Inches(13.33)
prs.slide_height = Inches(7.5)
sl = prs.slides.add_slide(prs.slide_layouts[6])  # blank


# ════════════════════════════════════════════════════
# HELPER FUNCTIONS
# ════════════════════════════════════════════════════

def rect(l, t, w, h, fill=WHITE, line=None, lw=0.5):
    s = sl.shapes.add_shape(1, Inches(l), Inches(t), Inches(w), Inches(h))
    s.fill.solid(); s.fill.fore_color.rgb = fill
    if line:
        s.line.color.rgb = line; s.line.width = Pt(lw)
    else:
        s.line.fill.background()
    return s


def txt(text, l, t, w, h, size=8, bold=False, color=DARK_TEXT,
        align=PP_ALIGN.LEFT, italic=False):
    tb = sl.shapes.add_textbox(Inches(l), Inches(t), Inches(w), Inches(h))
    tf = tb.text_frame; tf.word_wrap = True
    p  = tf.paragraphs[0]; p.alignment = align
    r  = p.add_run(); r.text = text
    r.font.size = Pt(size); r.font.bold = bold
    r.font.italic = italic; r.font.color.rgb = color
    return tb


def sec_hdr(label, l, t, w, h=0.22):
    rect(l, t, w, h, fill=NAVY)
    txt(label, l+0.05, t+0.02, w-0.08, h-0.04,
        size=7.5, bold=True, color=WHITE)


def tbl_hdr(cols, widths, l, t, h=0.21):
    x = l
    for c, w in zip(cols, widths):
        rect(x, t, w, h, fill=NAVY_LIGHT)
        txt(c, x+0.04, t+0.02, w-0.07, h-0.04, size=7, bold=True, color=WHITE)
        x += w


def tbl_rows(rows, widths, l, t, rh=0.24):
    y = t
    for ri, row in enumerate(rows):
        x = l
        fill = GRAY_ROW if ri % 2 == 0 else WHITE
        for ci, cell in enumerate(row):
            rect(x, y, widths[ci], rh, fill=fill, line=BORDER, lw=0.3)
            txt(str(cell), x+0.04, y+0.02, widths[ci]-0.07, rh-0.04,
                size=7, bold=(ci == 0), color=DARK_TEXT)
            x += widths[ci]
        y += rh
    return y


def oval(label, l, t, w=1.55, h=0.3):
    s = sl.shapes.add_shape(9, Inches(l), Inches(t), Inches(w), Inches(h))
    s.fill.solid(); s.fill.fore_color.rgb = NAVY
    s.line.color.rgb = NAVY; s.line.width = Pt(0.75)
    tf = s.text_frame; tf.word_wrap = True
    p  = tf.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
    r  = p.add_run(); r.text = label
    r.font.size = Pt(7.5); r.font.bold = True; r.font.color.rgb = WHITE


def flow_box(label, l, t, w=1.55, h=0.33, is_sys=False):
    fill = BLUE_SYS if is_sys else GREEN_HUM
    s = sl.shapes.add_shape(1, Inches(l), Inches(t), Inches(w), Inches(h))
    s.fill.solid(); s.fill.fore_color.rgb = fill
    s.line.color.rgb = NAVY_LIGHT; s.line.width = Pt(0.5)
    tf = s.text_frame; tf.word_wrap = True
    p  = tf.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
    r  = p.add_run(); r.text = label
    r.font.size = Pt(7); r.font.color.rgb = DARK_TEXT


def arrow(l, t, h=0.1):
    ln = sl.shapes.add_connector(1, Inches(l), Inches(t),
                                  Inches(l), Inches(t + h))
    ln.line.color.rgb = NAVY; ln.line.width = Pt(1)


# ════════════════════════════════════════════════════
# TITLE BAR
# ════════════════════════════════════════════════════
rect(0, 0, 13.33, 0.36, fill=NAVY)
txt("IRL 2 : Analisis proses capture data existing, sumber data, field yang dibutuhkan, "
    "serta penyusunan project scope dan process flow.",
    0.12, 0.03, 13.1, 0.3, size=9, bold=True, color=WHITE)

TOP = 0.42   # y mulai konten

# ════════════════════════════════════════════════════
# KOLOM 1  —  Existing Data Source + Data Requirement
# (x: 0.05 .. 3.65, width: 3.60)
# ════════════════════════════════════════════════════
C1, W1 = 0.05, 3.60

# 1. Existing Data Source
sec_hdr("1. Existing Data Source", C1, TOP, W1)
tbl_hdr(["Source", "Fungsi"], [0.9, 2.70], C1, TOP+0.22)
y1 = tbl_rows([
    ("APS",    "Master Data Order"),
    ("Engage", "Master Data IN & OUT Panel"),
    ("CIUROX", "Ready Accessories"),
], [0.9, 2.70], C1, TOP+0.43, rh=0.26)

# 2. Data Requirement
y1 += 0.07
sec_hdr("2. Data Requirement", C1, y1, W1)
tbl_hdr(["Field Dashboard", "Source"], [1.65, 1.95], C1, y1+0.22)
tbl_rows([
    ("Order",              "APS"),
    ("Style",              "APS"),
    ("Tgl. Delivery",      "APS"),
    ("Qty PDK",            "APS"),
    ("Qty IN & OUT Panel", "Engage"),
    ("Qty Material Ready", "APS + Engage + CIUROX"),
    ("Balance",            "Calculation"),
    ("Working Days",       "Configured by SPV"),
    ("Remaining Workdays", "Calculation"),
    ("Demand",             "Calculation"),
], [1.65, 1.95], C1, y1+0.43, rh=0.26)


# ════════════════════════════════════════════════════
# KOLOM 2  —  Scope + User Requirement
# (x: 3.72 .. 7.32, width: 3.60)
# ════════════════════════════════════════════════════
C2, W2 = 3.72, 3.60

# 4. Scope
sec_hdr("4. Scope", C2, TOP, W2)
tbl_hdr(["Scope"], [W2], C2, TOP+0.22)
y2 = tbl_rows([
    ("Material Ready Dashboard",),
    ("Heat Transfer",),
    ("Integrasi APS",),
    ("Integrasi Engage",),
    ("Integrasi CIUROX",),
    ("Capture Data (RPA Otomatis)",),
    ("Monitoring Material Ready",),
], [W2], C2, TOP+0.43, rh=0.26)

# 3. User Requirement
y2 += 0.07
sec_hdr("3. User Requirement", C2, y2, W2)
tbl_hdr(["User", "Requirement"], [1.1, 2.50], C2, y2+0.22)
tbl_rows([
    ("Operator",    "Mengetahui hasil scan IN & OUT"),
    ("Line Leader", "Monitoring Output"),
    ("Supervisor",  "Monitoring Ready Material"),
    ("Planner",     "Planning Loading"),
    ("Management",  "Summary Dashboard"),
], [1.1, 2.50], C2, y2+0.43, rh=0.26)


# ════════════════════════════════════════════════════
# KOLOM 3  —  Existing Flow
# (x: 7.42 .. 10.32, width: 2.90)
# ════════════════════════════════════════════════════
C3, W3 = 7.42, 2.90
FW = 1.70   # lebar flow box
FC = C3 + (W3 - FW) / 2  # center x

rect(C3, TOP, W3, 0.25, fill=RGBColor(0xDD,0xDD,0xDD))
txt("Existing Flow", C3+0.05, TOP+0.02, W3-0.08, 0.21,
    size=8, bold=True, color=DARK_TEXT, align=PP_ALIGN.CENTER)

EY = TOP + 0.32   # y start

existing_steps = [
    ("Start",                              "oval"),
    ("Operator scan Material",             "human"),
    ("Operator login APS & download",      "human"),
    ("Operator login Engage & download",   "human"),
    ("Tunggu Email Controlist Accessories","human"),
    ("Pengolahan & Rekap Data di Excel",   "human"),
    ("Kirim Laporan ke Supervisor",        "human"),
    ("verifikasi data?",                   "diamond"),
    ("Operator Laporan ke Bagiannya",      "human"),
    ("Supervisor & Planning Review",       "human"),
    ("Finish",                             "oval"),
]

BH_E = 0.3   # box height existing
ARR_E = 0.09

for i, (label, kind) in enumerate(existing_steps):
    if kind == "oval":
        oval(label, FC, EY, FW, BH_E)
    elif kind == "diamond":
        # fake diamond as rotated rect — just use a narrow box with italic
        s = sl.shapes.add_shape(4, Inches(FC), Inches(EY),
                                 Inches(FW), Inches(BH_E))
        s.fill.solid(); s.fill.fore_color.rgb = GREEN_HUM
        s.line.color.rgb = NAVY_LIGHT; s.line.width = Pt(0.5)
        tf = s.text_frame; tf.word_wrap = True
        p  = tf.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
        r  = p.add_run(); r.text = label
        r.font.size = Pt(7); r.font.italic = True; r.font.color.rgb = DARK_TEXT
    else:
        flow_box(label, FC, EY, FW, BH_E, is_sys=False)

    EY += BH_E
    if i < len(existing_steps) - 1:
        arrow(FC + FW/2, EY, ARR_E)
        EY += ARR_E

# No / Yes label at diamond
# small note
txt("No →  (loop kembali)", C3+0.05, EY - (BH_E+ARR_E)*4 - 0.12, W3-0.1, 0.2,
    size=6, italic=True, color=RGBColor(0x88,0x88,0x88))


# ════════════════════════════════════════════════════
# KOLOM 4  —  Proposed Flow
# (x: 10.42 .. 13.25, width: 2.83)
# ════════════════════════════════════════════════════
C4, W4 = 10.42, 2.83
FW2 = 1.70
FC2 = C4 + (W4 - FW2) / 2

rect(C4, TOP, W4, 0.25, fill=RGBColor(0x1E,0x3A,0x5F))
txt("Proposed Flow", C4+0.05, TOP+0.02, W4-0.08, 0.21,
    size=8, bold=True, color=WHITE, align=PP_ALIGN.CENTER)

PY = TOP + 0.32

proposed_steps = [
    ("Start",                                       "oval",  False),
    ("Operator scan Material",                       "human", False),
    ("Automated Data Capture\n& Integration",        "sys",   True),
    ("Database & Data Cleansing",                    "sys",   True),
    ("Web Dashboard & Analytics",                    "sys",   True),
    ("Supervisor & Planning Review",                 "human", False),
    ("Finish",                                       "oval",  False),
]

BH_P = 0.38
ARR_P = 0.1

for i, (label, kind, is_sys) in enumerate(proposed_steps):
    if kind == "oval":
        oval(label, FC2, PY, FW2, BH_P)
    else:
        flow_box(label, FC2, PY, FW2, BH_P, is_sys=is_sys)
    PY += BH_P
    if i < len(proposed_steps) - 1:
        arrow(FC2 + FW2/2, PY, ARR_P)
        PY += ARR_P

# ════════════════════════════════════════════════════
# LEGEND (bottom right)
# ════════════════════════════════════════════════════
LY = 7.15
rect(10.42, LY, 2.83, 0.28, fill=GRAY_ROW, line=BORDER, lw=0.5)
txt("Notes:", 10.5, LY+0.03, 0.7, 0.22, size=7, bold=True, color=NAVY)
rect(11.25, LY+0.06, 0.18, 0.14, fill=GREEN_HUM, line=NAVY_LIGHT, lw=0.4)
txt("Human Process", 11.47, LY+0.04, 1.2, 0.20, size=7, color=DARK_TEXT)
rect(12.2,  LY+0.06, 0.18, 0.14, fill=BLUE_SYS, line=NAVY_LIGHT, lw=0.4)
txt("System Process", 12.42, LY+0.04, 0.9, 0.20, size=7, color=DARK_TEXT)

# ════════════════════════════════════════════════════
# DIVIDER LINES antara kolom
# ════════════════════════════════════════════════════
for lx in [3.67, 7.37, 10.37]:
    ln = sl.shapes.add_connector(1,
        Inches(lx), Inches(TOP), Inches(lx), Inches(7.45))
    ln.line.color.rgb = BORDER; ln.line.width = Pt(0.5)

# ════════════════════════════════════════════════════
# SAVE
# ════════════════════════════════════════════════════
out_path = r"c:\xampp\htdocs\dashboard_gm\IRL2_Dashboard_GM_1slide.pptx"
prs.save(out_path)
print(f"[OK] Saved: {out_path}")
print(f"     Slides: {len(prs.slides)}")
