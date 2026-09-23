import openpyxl

wb = openpyxl.load_workbook('rpa/aps-rpa/downloads/JO.xlsx', data_only=True)
sheet = wb.active
for r_idx in range(6, 9):
    r = list(sheet.iter_rows(values_only=True, min_row=r_idx, max_row=r_idx))[0]
    items = [(i, str(c).strip()) for i, c in enumerate(r) if c is not None and str(c).strip() != '']
    print(f"Row {r_idx}:", items[:10])

