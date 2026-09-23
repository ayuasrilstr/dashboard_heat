import argparse
import json
import os
import shutil
import subprocess
import sys
import tempfile
from collections import defaultdict
from datetime import datetime, date
from pathlib import Path
from dotenv import load_dotenv

ROOT_DIR = Path(__file__).resolve().parent
load_dotenv(ROOT_DIR / ".env")

REPORT_COLUMNS = [
    ("#", "number"),
    ("Date", "We_datum"),
    ("Storage Nr", "We_lagnr"),
    ("Location Nr", "We_lagfnr"),
    ("Item Nr", "We_artnr"),
    ("Item Name", "Art_name"),
    ("Item Name 2", "Art_name2"),
    ("Serial Nr", "We_sernr"),
    ("Address Nr", "We_adrnr"),
    ("Address Name", "Adr_name"),
    ("Storage 2", "We_lagnr2"),
    ("Location 2", "We_lagfnr2"),
    ("Qty", "We_stck"),
    ("Unit", "Art_me"),
    ("Text", "We_name"),
    ("Cost Center", "We_kstnr"),
    ("Prod. Nr", "We_prdnr"),
    ("Udef 1", "We_flds00"),
    ("Udef 2", "We_flds01"),
    ("Udef 3", "We_flds02"),
    ("Udef 4", "We_flds03"),
    ("Udef 5", "We_flds04"),
    ("Udef 6", "We_flds05"),
    ("Udef 7", "We_flds06"),
    ("Udef 8", "We_flds07"),
    ("Udef 9", "We_flds08"),
    ("Udef 10", "We_flds09"),
    ("User Creator", "We_bennr"),
]

def get_php_executable():
    for candidate in (shutil.which("php"), shutil.which("php.exe")):
        if candidate:
            return candidate

    common_paths = [
        Path(r"E:\xampp\php\php.exe"),
        Path(r"C:\xampp\php\php.exe"),
        Path(r"D:\xampp\php\php.exe"),
    ]
    for candidate in common_paths:
        if candidate.is_file():
            return str(candidate)

    return "php"

def parse_excel_file(file_path):
    import openpyxl

    wb = openpyxl.load_workbook(file_path, data_only=True)
    sheet = wb.active
    raw_rows = list(sheet.iter_rows(values_only=True))

    if not raw_rows:
        return []

    # Cari baris header yang berisi "Date" atau "Storage Nr" atau "Item Nr"
    header_row_idx = None
    for idx, row in enumerate(raw_rows[:10]):
        row_str = [str(c).strip() if c is not None else "" for c in row]
        if "Date" in row_str and ("Item Nr" in row_str or "Storage Nr" in row_str):
            header_row_idx = idx
            break

    if header_row_idx is None:
        raise ValueError(f"Header tidak ditemukan di file {file_path}")

    headers = [str(c).strip() if c is not None else f"col_{i}" for i, c in enumerate(raw_rows[header_row_idx])]
    
    # Header alias mapping
    label_to_key = {label: key for label, key in REPORT_COLUMNS}

    parsed_rows = []
    for row_idx, r in enumerate(raw_rows[header_row_idx + 1:], start=header_row_idx + 2):
        if all(c is None or str(c).strip() == "" for c in r):
            continue

        row_dict = {}
        for col_idx, val in enumerate(r):
            if col_idx < len(headers):
                h_name = headers[col_idx]
                row_dict[h_name] = val
                if h_name in label_to_key:
                    row_dict[label_to_key[h_name]] = val

        # Format Date value
        d_val = row_dict.get("Date") or row_dict.get("We_datum")
        if isinstance(d_val, datetime):
            d_formatted = d_val.strftime("%Y-%m-%d %H:%M:%S")
            row_dict["Date"] = d_formatted
            row_dict["We_datum"] = d_formatted
        elif isinstance(d_val, date):
            d_formatted = d_val.strftime("%Y-%m-%d 00:00:00")
            row_dict["Date"] = d_formatted
            row_dict["We_datum"] = d_formatted

        # Ensure numeric Qty
        q_val = row_dict.get("Qty") or row_dict.get("We_stck")
        try:
            row_dict["Qty"] = float(q_val) if q_val is not None else 0.0
            row_dict["We_stck"] = row_dict["Qty"]
        except (ValueError, TypeError):
            row_dict["Qty"] = 0.0
            row_dict["We_stck"] = 0.0

        parsed_rows.append(row_dict)

    return parsed_rows

def build_engage_daily_history(rows):
    daily = {}
    for row in rows or []:
        date_value = row.get("Date") or row.get("We_datum") or row.get("date")
        if not date_value:
            continue

        if isinstance(date_value, datetime) or isinstance(date_value, date):
            key = date_value.strftime("%Y-%m-%d")
        else:
            key = str(date_value)[:10]

        try:
            raw_qty = float(row.get("Qty") or row.get("We_stck") or row.get("qty") or 0.0)
        except (ValueError, TypeError):
            raw_qty = 0.0

        if raw_qty == 0:
            continue

        bucket = daily.setdefault(
            key,
            {
                "date": key,
                "input_qty": 0,
                "output_qty": 0,
                "ready_qty": 0,
            },
        )
        if raw_qty > 0:
            bucket["input_qty"] += int(round(raw_qty))
        else:
            bucket["output_qty"] += int(round(abs(raw_qty)))

    for bucket in daily.values():
        bucket["ready_qty"] = int(bucket["input_qty"]) - int(bucket["output_qty"])

    return [daily[k] for k in sorted(daily.keys())]

def sync_transactions_to_mysql(rows, meta):
    if not rows:
        print("Tidak ada baris transaksi untuk disinkron.")
        return True

    helper_path = ROOT_DIR / "sync_engage_transactions.php"
    if not helper_path.is_file():
        raise FileNotFoundError(f"Helper MySQL tidak ditemukan: {helper_path}")

    payload = json.dumps({"rows": rows, "meta": meta}, ensure_ascii=False)
    with tempfile.NamedTemporaryFile("w", delete=False, suffix=".json", encoding="utf-8") as temp_file:
        temp_file.write(payload)
        temp_path = temp_file.name

    try:
        php_exe = get_php_executable()
        result = subprocess.run(
            [php_exe, str(helper_path), temp_path],
            cwd=str(ROOT_DIR),
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
        )

        if result.stdout:
            for line in result.stdout.splitlines():
                if line.strip():
                    print(f"[MySQL] {line}")

        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    print(f"[MySQL ERR] {line}")

        if result.returncode != 0:
            print(f"Sinkron transaksi MySQL error dengan exit code {result.returncode}")
            return False

        return True
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            pass

def sync_daily_history_to_mysql(history_rows):
    if not history_rows:
        print("History harian Engage kosong, skip sinkron.")
        return True

    helper_path = ROOT_DIR / "sync_engage_daily_history.php"
    if not helper_path.is_file():
        raise FileNotFoundError(f"Helper MySQL tidak ditemukan: {helper_path}")

    payload = json.dumps(history_rows, ensure_ascii=False)
    with tempfile.NamedTemporaryFile("w", delete=False, suffix=".json", encoding="utf-8") as temp_file:
        temp_file.write(payload)
        temp_path = temp_file.name

    try:
        php_exe = get_php_executable()
        result = subprocess.run(
            [php_exe, str(helper_path), temp_path],
            cwd=str(ROOT_DIR),
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
        )

        if result.stdout:
            for line in result.stdout.splitlines():
                if line.strip():
                    print(f"[MySQL Daily History] {line}")

        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    print(f"[MySQL Daily History ERR] {line}")

        return result.returncode == 0
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            pass

def sync_single_engage_file(excel_path, storage="32", direction="0"):
    excel_path = Path(excel_path)
    if not excel_path.is_file():
        print(f"Error: File Excel tidak ditemukan: {excel_path}")
        return False, []

    print(f"\nMembaca file Excel: {excel_path} (Storage: {storage})...")
    rows = parse_excel_file(excel_path)
    print(f"Berhasil membaca {len(rows)} baris data dari Excel.")

    if not rows:
        print(f"File {excel_path.name} tidak memiliki baris data.")
        return True, []

    dates = []
    for r in rows:
        d = r.get("Date") or r.get("We_datum")
        if d:
            dates.append(str(d)[:10])

    date_min = min(dates) if dates else "unknown"
    date_max = max(dates) if dates else "unknown"
    print(f"Rentang tanggal data ({storage}): {date_min} s/d {date_max}")

    meta = {
        "storage": storage,
        "direction": direction,
        "source_report": excel_path.stem,
        "source_file": excel_path.name,
        "period_key": f"{date_min[:7]}_{date_max[:7]}",
        "period_label": f"{date_min} to {date_max}",
        "date_from": date_min,
        "date_to": date_max,
    }

    print(f"--- Sinkronisasi Data Transaksi Mentah {excel_path.name} ke MySQL ---")
    success = sync_transactions_to_mysql(rows, meta)
    return success, rows


def main():
    parser = argparse.ArgumentParser(description="Sinkron file Excel Engage ke Database MySQL")
    parser.add_argument(
        "--file",
        default=None,
        help="Path ke file Excel Engage (default: scan otomatis downloads/32a_engage.xlsx)",
    )
    parser.add_argument("--storage", default=None, help="Storage code (contoh: 32a)")
    parser.add_argument("--direction", default="0", help="Direction code (default: 0)")
    args = parser.parse_args()

    all_rows = []
    overall_success = True

    if args.file:
        storage = args.storage or ("32a" if "32a" in Path(args.file).name.lower() else "32a")
        success, rows = sync_single_engage_file(args.file, storage=storage, direction=args.direction)
        if not success:
            overall_success = False
        all_rows.extend(rows)
    else:
        # Default: sinkronkan file 32a_engage di folder downloads
        files_to_sync = [
            (ROOT_DIR / "downloads" / "32a_engage.xlsx", "32a"),
        ]

        synced_count = 0
        for fpath, storage in files_to_sync:
            if fpath.is_file():
                success, rows = sync_single_engage_file(fpath, storage=storage, direction=args.direction)
                if not success:
                    overall_success = False
                all_rows.extend(rows)
                synced_count += 1

        if synced_count == 0:
            print("Tidak ada file 32a_engage.xlsx di folder downloads/.")
            sys.exit(0)

    print("\n--- Sinkronisasi Ringkasan Harian (engage_daily_history) ---")
    full_daily_history = build_engage_daily_history(all_rows)
    print(f"Total history harian gabungan: {len(full_daily_history)} hari")
    for d_item in full_daily_history:
        print(f"  {d_item['date']}: Total In={d_item['input_qty']:,}, Total Out={d_item['output_qty']:,}, Ready={d_item['ready_qty']:,}")

    success_daily = sync_daily_history_to_mysql(full_daily_history)
    if not success_daily:
        overall_success = False

    if overall_success:
        print("\nSINKRONISASI EXCEL ENGAGE KE DATABASE BERHASIL SEPENUHNYA.")
    else:
        print("\nSINKRONISASI SELESAI DENGAN BEBERAPA PERINGATAN/CATATAN.")


if __name__ == "__main__":
    main()
