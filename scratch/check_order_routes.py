import openpyxl

wb = openpyxl.load_workbook('rpa/aps-rpa/downloads/JO.xlsx', data_only=True)
sheet = wb.active

orders = ['0903095197-1', '0903093893-1', '0903095189-1']
for r in sheet.iter_rows(values_only=True, min_row=4):
    jo = str(r[1]).strip() if r[1] is not None else ''
    for o in orders:
        if o in jo:
            route = r[16]
            print(f"Order {o}: route={route}, style={r[4]}")

