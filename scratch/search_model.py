with open('web/application/models/Dashboard_model.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

print(f"Total lines in Dashboard_model.php: {len(lines)}")
for i, line in enumerate(lines):
    l = line.lower()
    if any(k in l for k in ['process', 'proses', 'route', 'pcs', 'unit']):
        print(f"{i+1}: {line.strip()[:120]}")

