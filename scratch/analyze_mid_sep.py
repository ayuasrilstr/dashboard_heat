import zipfile
import xml.etree.ElementTree as ET
import re
import datetime
from pathlib import Path

def read_xlsx(path):
    with zipfile.ZipFile(path, 'r') as z:
        shared_strings = []
        if 'xl/sharedStrings.xml' in z.namelist():
            ss_tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
            for si in ss_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
                t = si.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
                if t is not None:
                    shared_strings.append(t.text or '')
                else:
                    parts = [r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text or '' for r in si.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}r') if r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') is not None]
                    shared_strings.append(''.join(parts))

        sheet_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
        sheet_data = sheet_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData')
        rows = []
        for r in sheet_data.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
            cells = {}
            for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
                ref = c.get('r')
                col_letters = re.match(r'^[A-Z]+', ref).group(0)
                idx = 0
                for ch in col_letters:
                    idx = idx * 26 + (ord(ch) - ord('A') + 1)
                idx -= 1
                t = c.get('t')
                v = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
                val = v.text if v is not None else ''
                if t == 's' and val.isdigit():
                    val = shared_strings[int(val)]
                cells[idx] = val
            if cells:
                max_idx = max(cells.keys())
                row_list = [cells.get(i, '') for i in range(max_idx + 1)]
                rows.append(row_list)
        return rows

rows = read_xlsx('rpa/aps-rpa/downloads/JO.xlsx')

def parse_date(val):
    val = str(val).strip()
    if not val: return None
    if re.match(r'^\d+(\.\d+)?$', val):
        num = float(val)
        if 20000 < num < 90000:
            return (datetime.datetime(1899, 12, 30) + datetime.timedelta(days=int(num))).date()
    m = re.match(r'^(\d{1,2})[/\.-](\d{1,2})[/\.-](\d{2,4})', val)
    if m:
        d, mon, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        if y < 100: y += 2000
        return datetime.date(y, mon, d)
    return None

def is_heat_route(route):
    r = str(route).upper()
    return 'HT' in r or 'HEAT' in r

mid_sep_rows = []
for idx, r in enumerate(rows[5:], start=6):
    if not r: continue
    dt = parse_date(r[6] if len(r) > 6 else '')
    if dt and dt.year == 2026 and dt.month == 9 and dt.day <= 15:
        route = r[16] if len(r) > 16 else ''
        heat_plan = float(r[49]) if len(r) > 49 and r[49] else 0
        heat_qty = float(r[50]) if len(r) > 50 and r[50] else 0
        heat_bal = float(r[51]) if len(r) > 51 and r[51] else 0
        
        is_ht = is_heat_route(route) or heat_plan > 0 or heat_qty > 0 or heat_bal > 0
        
        jo = r[1] if len(r) > 1 else ''
        order_qty = float(r[5]) if len(r) > 5 and r[5] else 0
        plan_qty = float(r[7]) if len(r) > 7 and r[7] else 0
        
        mid_sep_rows.append({
            'row': idx,
            'jo': jo,
            'delivery': r[6],
            'date': str(dt),
            'route': route,
            'is_ht': is_ht,
            'order_qty': order_qty,
            'plan_qty': plan_qty,
            'heat_plan': heat_plan,
        })

print(f'Total rows in MID SEP (1-15 Sep 2026): {len(mid_sep_rows)}')

ht_rows = [x for x in mid_sep_rows if x['is_ht']]
non_ht_rows = [x for x in mid_sep_rows if not x['is_ht']]

print('\nHT Rows in MID SEP (Heat Transfer - Terhitung di Dashboard):')
ht_by_date = {}
for x in ht_rows:
    qty = x['heat_plan'] if x['heat_plan'] > 0 else (x['plan_qty'] if x['plan_qty'] > 0 else x['order_qty'])
    ht_by_date[x['date']] = ht_by_date.get(x['date'], 0) + qty
    print(f"Row {x['row']}: Date={x['date']} ({x['delivery']}), JO={x['jo']}, Route={x['route']}, Qty={qty:,.0f}")

print('\nHT Summary by Date:')
for d, q in sorted(ht_by_date.items()):
    print(f'{d}: {q:,.0f}')
print(f'Total HT MID SEP (Dashboard): {sum(ht_by_date.values()):,.0f}')

print('\nNon-HT summary by date in MID SEP (Routes non-HT):')
non_ht_by_date = {}
for x in non_ht_rows:
    qty = x['plan_qty'] if x['plan_qty'] > 0 else x['order_qty']
    non_ht_by_date[x['date']] = non_ht_by_date.get(x['date'], 0) + qty

for d, q in sorted(non_ht_by_date.items()):
    print(f'{d}: {q:,.0f}')
print(f'Total Non-HT MID SEP: {sum(non_ht_by_date.values()):,.0f}')
print(f'Total Seluruh Order (HT + Non-HT) di MID SEP: {sum(ht_by_date.values()) + sum(non_ht_by_date.values()):,.0f}')

# Let's search where 36,835 might come from:
print('\nSearching for subsets that sum to 36,835:')
# check combinations of dates or styles or lines
date_all = {}
for x in mid_sep_rows:
    qty = x['plan_qty'] if x['plan_qty'] > 0 else x['order_qty']
    date_all[x['date']] = date_all.get(x['date'], 0) + qty

for d, q in sorted(date_all.items()):
    print(f'All routes on {d}: {q:,.0f}')
