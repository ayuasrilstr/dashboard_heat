import re

def scan_file(filepath):
    print("=== Scanning", filepath, "===")
    with open(filepath, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f):
            l = line.strip()
            if any(k in l.lower() for k in ['id="list', 'id="order', 'listorder', 'orderlist', 'table', 'filter', 'proses', 'process', 'data-view']):
                if len(l) < 140:
                    print(f"{i+1}: {l}")

scan_file('web/application/views/dashboards/heat/index.php')
scan_file('web/application/views/dashboard_admin.php')

