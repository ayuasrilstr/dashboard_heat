import openpyxl

wb = openpyxl.load_workbook('rpa/aps-rpa/downloads/JO.xlsx', data_only=True)
sheet = wb.active
routes = {}
for r in sheet.iter_rows(values_only=True, min_row=6):
    route = str(r[16]).strip() if r[16] is not None else ''
    routes[route] = routes.get(route, 0) + 1

for route, count in sorted(routes.items(), key=lambda x: -x[1])[:20]:
    print(f"'{route}': {count}")

