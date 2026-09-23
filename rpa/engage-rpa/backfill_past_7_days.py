import asyncio
import os
import sys
from datetime import datetime, timedelta
from pathlib import Path

os.environ["RPA_BYPASS_CHILD_LOCK"] = "1"

import main
from playwright.async_api import async_playwright

ROOT_DIR = Path(__file__).resolve().parent
DOWNLOAD_DIR = ROOT_DIR / "downloads"
ARCHIVE_DIR = ROOT_DIR / "archive"

DOWNLOAD_DIR.mkdir(parents=True, exist_ok=True)
ARCHIVE_DIR.mkdir(parents=True, exist_ok=True)

TARGET_DATES = [
    "2026-08-28",
    "2026-08-29",
    "2026-08-30",
    "2026-08-31",
    "2026-09-01",
    "2026-09-02",
    "2026-09-03",
    "2026-09-04",
]

async def run_backfill():
    print(f"=== Memulai Backfill Data Engage Storage 32a untuk {len(TARGET_DATES)} Hari ===")
    
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(accept_downloads=True)
        page = await context.new_page()
        page.set_default_timeout(600000)
        page.set_default_navigation_timeout(600000)

        # Login
        print(f"Membuka halaman login: {main.LOGIN_URL}")
        await page.goto(main.LOGIN_URL)
        await page.fill("#email", main.USERNAME)
        await page.fill("#password", main.PASSWORD)
        await page.click("button[type='submit']")
        await page.wait_for_load_state("networkidle")
        await page.wait_for_timeout(3000)
        print("Login Engage berhasil.")

        total_synced_dates = 0
        total_rows_all = 0

        for dt_str in TARGET_DATES:
            print(f"\n--- [Tanggal {dt_str}] Memproses filter storage 32a, direction 0 ---")
            try:
                row_count = await main.prepare_report_context(page, storage="32a", date_from=dt_str, date_to=dt_str, direction="0")
            except Exception as e:
                print(f"Error saat prepare report untuk tanggal {dt_str}: {e}")
                continue

            if row_count == 0:
                print(f"Tanggal {dt_str}: Tidak ada data di Engage (0 row).")
                continue

            print(f"Tanggal {dt_str}: Ditemukan transaksi, mengambil {row_count} baris...")
            try:
                rows = await main.fetch_report_rows(page, storage="32a", date_from=dt_str, date_to=dt_str, direction="0")
            except Exception as e:
                print(f"Error saat fetch rows untuk tanggal {dt_str}: {e}")
                continue

            row_len = len(rows)
            total_rows_all += row_len
            total_synced_dates += 1
            print(f"Tanggal {dt_str}: Berhasil fetch {row_len} baris data.")

            # Simpan file excel arsip
            month_folder = ARCHIVE_DIR / dt_str[:7]
            month_folder.mkdir(parents=True, exist_ok=True)
            archive_file = month_folder / f"{dt_str}_32a_engage.xlsx"
            main.save_report_rows(rows, archive_file)

            # Jika hari ini (2026-09-04), update juga downloads/32a_engage.xlsx
            if dt_str == "2026-09-04":
                main.save_report_rows(rows, DOWNLOAD_DIR / "32a_engage.xlsx")

            # Sinkronisasi ke MySQL (tb_engage_transactions & tb_engage_archieve)
            meta = {
                "storage": "32a",
                "direction": "0",
                "source_report": "32a_engage",
                "source_file": "32a_engage.xlsx",
                "period_key": dt_str,
                "period_label": dt_str,
                "date_from": dt_str,
                "date_to": dt_str,
            }
            main.sync_engage_transactions_to_mysql(rows, meta)

            # Sinkronisasi ke engage_daily_history
            daily_history = main.build_engage_daily_history(rows)
            main.sync_engage_daily_history_to_mysql(daily_history)

            await page.wait_for_timeout(2000)

        await browser.close()
        print(f"\n=== BACKFILL SELESAI: {total_synced_dates} hari berhasil disinkron, total {total_rows_all} baris ===")

if __name__ == "__main__":
    asyncio.run(run_backfill())
