from playwright.async_api import TimeoutError as PlaywrightTimeoutError
from playwright.async_api import async_playwright
from dotenv import load_dotenv
import argparse
import asyncio
import json
import msvcrt
import os
import shutil
import subprocess
import threading
import time
import tempfile
from calendar import monthrange
from datetime import datetime, time as dt_time, timedelta
from html import escape
from pathlib import Path

load_dotenv()

ROOT_DIR = Path(__file__).resolve().parent

USERNAME = os.getenv("ENGAGE_USERNAME")
PASSWORD = os.getenv("ENGAGE_PASSWORD")

LOGIN_URL = "http://192.168.8.59:88/csreport1.5/public/login"

REPORT_URL = "http://192.168.8.59:88/csreport1.5/public/wwaw/kundenspez/0000971826"

DOWNLOAD_PATH = ROOT_DIR / "downloads" / "engage_download.xlsx"
DOWNLOAD_DIR = Path(DOWNLOAD_PATH).parent
ARCHIVE_DIR = ROOT_DIR / "archive"
LOG_DIR = ROOT_DIR / "logs"
LOG_PATH = LOG_DIR / "scheduler.log"
LOCK_PATH = LOG_DIR / "scheduler.lock"
SCHEDULE_START_HOUR = 7
SCHEDULE_END_HOUR = 24
SCHEDULE_INTERVAL_MINUTES = 90

# Jadwal tetap per hari: 07:00 hingga 22:00 (per 1,5 jam), ditutup tepat pada pukul 00:00
SCHEDULE_DAILY_TIMES = [
    (7, 0),
    (8, 30),
    (10, 0),
    (11, 30),
    (13, 0),
    (14, 30),
    (16, 0),
    (17, 30),
    (19, 0),
    (20, 30),
    (22, 0),
    (0, 0),  # Download penutup tepat pukul 00:00
]
ARCHIVE_ENABLED = os.getenv("ENGAGE_KEEP_ARCHIVE", "").strip().lower() in {"1", "true", "yes", "on"}
RUN_IN_PROGRESS = False
LOCK_FILE = None

if not USERNAME or not PASSWORD:
    raise RuntimeError("ENGAGE_USERNAME dan ENGAGE_PASSWORD harus diisi di file .env")

DOWNLOAD_DIR.mkdir(parents=True, exist_ok=True)
if ARCHIVE_ENABLED:
    ARCHIVE_DIR.mkdir(parents=True, exist_ok=True)
LOG_DIR.mkdir(parents=True, exist_ok=True)

conditions = [
    {"storage": "32a", "direction": "0", "filename": "32a_engage.xlsx"},
]

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

MONTH_ABBR = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
]

FETCH_PAGE_RETRY_COUNT = 3
FETCH_PAGE_RETRY_DELAY_MS = 3000
REPORT_RECOVERY_RETRY_COUNT = 2
PAGE_FETCH_PAUSE_MS = 750


async def has_csrf_error(page):
    content = await page.content()
    return (
        "Failed to refresh CSRF token" in content
        or "Something wrong in requested link: /refresh-csrf" in content
    )


async def open_report(page, retries=3):
    for attempt in range(1, retries + 1):
        await page.goto(REPORT_URL, wait_until="domcontentloaded")
        await page.wait_for_timeout(2000)

        if await has_csrf_error(page):
            print(f"CSRF token gagal refresh, coba ulang buka report ({attempt}/{retries})")
            await page.reload(wait_until="domcontentloaded")
            await page.wait_for_timeout(3000)
            continue

        try:
            await page.wait_for_selector("#warein_lagnrvon", timeout=30000)
            return
        except PlaywrightTimeoutError:
            if attempt == retries:
                raise
            print(f"Form report belum siap, coba ulang buka report ({attempt}/{retries})")
            await page.wait_for_timeout(3000)

    raise RuntimeError("Gagal membuka halaman report karena CSRF token tidak bisa refresh")


async def close_visible_message_dialog(page):
    dialog = page.locator("#qw_meldung_dialog.modal.show")
    if not await dialog.count():
        return ""

    try:
        if not await dialog.first.is_visible(timeout=1000):
            return ""
    except PlaywrightTimeoutError:
        return ""

    message = normalize_dialog_text(await dialog.first.inner_text())
    log(f"Dialog report muncul: {message}")

    buttons = dialog.locator("button")
    for index in range(await buttons.count()):
        button = buttons.nth(index)
        if await button.is_visible():
            await button.click()
            await page.wait_for_timeout(1000)
            return message

    await page.keyboard.press("Escape")
    await page.wait_for_timeout(1000)
    return message


async def set_input_value(page, selector, value):
    if await page.locator(selector).count() == 0:
        return False

    await page.locator(selector).evaluate(
        """(element, inputValue) => {
            element.value = inputValue;
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
            element.dispatchEvent(new Event('blur', { bubbles: true }));
        }""",
        value,
    )
    return True


async def set_date_filter(page, date_from, date_to):
    from_set = await set_input_value(page, "#warein_datvon", date_from)
    to_set = await set_input_value(page, "#warein_datbis", date_to)

    if not from_set or not to_set:
        raise RuntimeError("Field tanggal warein_datvon/warein_datbis tidak ditemukan di halaman report")


async def wait_for_loading_done(page, timeout=600000):
    try:
        await page.wait_for_selector("#wwloading.modal.show", state="hidden", timeout=timeout)
    except PlaywrightTimeoutError:
        log("Loading report masih muncul terlalu lama, proses dilanjutkan dengan hati-hati")


async def get_displayed_row_count(page):
    return await page.locator("#IDD_LISTE2 tr").count()


async def click_display_and_get_row_count(page):
    async with page.expect_response(
        lambda response: "khtx/warein/fillscreen" in response.url,
        timeout=600000,
    ) as response_info:
        await page.click("#IDD_DISPLAY")

    response = await response_info.value
    body = await response.text()
    row_count = count_response_rows(body)

    await page.wait_for_timeout(1000)
    await wait_for_loading_done(page)

    return row_count


async def prepare_report_context(page, storage, date_from, date_to, direction):
    await open_report(page)

    await page.fill("#warein_lagnrvon", storage)
    await set_date_filter(page, date_from, date_to)
    await page.select_option("#warein_direction", direction)

    row_count = await click_display_and_get_row_count(page)

    log("Display data diklik")

    dialog_message = await close_visible_message_dialog(page)
    if is_date_filter_error(dialog_message):
        raise RuntimeError(f"Filter tanggal gagal: {dialog_message}")

    log(f"Jumlah row response: {row_count}")

    if await has_csrf_error(page):
        log("CSRF error setelah klik Display, ulang proses filter")
        await open_report(page)
        await page.fill("#warein_lagnrvon", storage)
        await set_date_filter(page, date_from, date_to)
        await page.select_option("#warein_direction", direction)
        row_count = await click_display_and_get_row_count(page)

        log("Display data diklik")

        dialog_message = await close_visible_message_dialog(page)
        if is_date_filter_error(dialog_message):
            raise RuntimeError(f"Filter tanggal gagal: {dialog_message}")

        log(f"Jumlah row response setelah retry: {row_count}")

    return row_count


def count_response_rows(body):
    try:
        payload = json.loads(body)
    except json.JSONDecodeError:
        return body.count("<tr")

    data = payload.get("data")
    if isinstance(data, list):
        return len(data)

    if isinstance(data, str):
        return data.count("<tr")

    return 0


def normalize_dialog_text(value):
    return " ".join(value.split())


def is_date_filter_error(message):
    lowered = message.lower()
    return "date from must be filled" in lowered or "date is not in correct format" in lowered


def log(message):
    log_text = f"[{datetime.now():%Y-%m-%d %H:%M:%S}] {message}"
    print(log_text)
    with LOG_PATH.open("a", encoding="utf-8") as log_file:
        log_file.write(log_text + "\n")


def acquire_process_lock():
    global LOCK_FILE

    LOCK_FILE = LOCK_PATH.open("a+")
    try:
        LOCK_FILE.seek(0)
        msvcrt.locking(LOCK_FILE.fileno(), msvcrt.LK_NBLCK, 1)
    except OSError:
        LOCK_FILE.close()
        LOCK_FILE = None
        return False

    return True


def release_process_lock():
    global LOCK_FILE

    if LOCK_FILE is None:
        return

    try:
        LOCK_FILE.seek(0)
        msvcrt.locking(LOCK_FILE.fileno(), msvcrt.LK_UNLCK, 1)
    finally:
        LOCK_FILE.close()
        LOCK_FILE = None


def add_months(value, months):
    month_index = value.month - 1 + months
    year = value.year + month_index // 12
    month = month_index % 12 + 1
    day = min(value.day, monthrange(year, month)[1])
    return value.replace(year=year, month=month, day=day)


def format_report_date(value):
    return f"{value:%Y-%m-%d}"


def get_report_periods(reference_date=None, reference_month=None, date_from=None, date_to=None):
    if date_from is not None and date_to is not None:
        return [
            {
                "key": f"{date_from:%Y-%m-%d}_{date_to:%Y-%m-%d}",
                "label": f"{date_from:%d/%m/%Y} - {date_to:%d/%m/%Y}",
                "date_from": date_from,
                "date_to": date_to,
            }
        ]

    if reference_month is not None:
        ref_first = reference_month.replace(day=1)
        last_day = monthrange(ref_first.year, ref_first.month)[1]
        month_end = ref_first.replace(day=last_day)
        month_label = MONTH_ABBR[ref_first.month - 1]
        return [
            {
                "key": f"{ref_first:%Y-%m}",
                "label": f"{month_label} {ref_first.year}",
                "date_from": ref_first,
                "date_to": month_end,
            }
        ]

    ref = reference_date or datetime.now()
    ref_date = ref.date() if isinstance(ref, datetime) else ref
    month_label = MONTH_ABBR[ref_date.month - 1]
    return [
        {
            "key": f"{ref_date:%Y-%m-%d}",
            "label": f"{ref_date.day} {month_label} {ref_date.year}",
            "date_from": ref_date,
            "date_to": ref_date,
        }
    ]


def archive_stale_download(download_path):
    if not download_path.is_file():
        return False

    file_date = datetime.fromtimestamp(download_path.stat().st_mtime).date()
    today = datetime.now().date()
    if file_date >= today:
        return False

    for attempt in range(1, 6):
        try:
            if ARCHIVE_ENABLED:
                archive_dir = ARCHIVE_DIR / file_date.strftime("%Y-%m")
                archive_dir.mkdir(parents=True, exist_ok=True)
                archive_path = archive_dir / f"{file_date:%Y-%m-%d}_{download_path.name}"
                if archive_path.exists():
                    archive_path.unlink()
                shutil.move(str(download_path), str(archive_path))
                log(f"Arsip dipindah: {archive_path}")
            else:
                download_path.unlink()
                log(f"File lama dihapus: {download_path}")
            return True
        except PermissionError:
            if attempt == 5:
                log(f"Gagal memindah/menghapus file lama karena masih dipakai: {download_path}")
                return False
            time.sleep(2)

    return False


def clean_download_folder():
    if not DOWNLOAD_DIR.exists():
        return

    for path in DOWNLOAD_DIR.iterdir():
        if not path.is_file():
            continue

        name_lower = path.name.lower()
        if name_lower.startswith(".") and ".tmp" in name_lower:
            try:
                path.unlink()
                log(f"Temp file dibuang: {path}")
            except FileNotFoundError:
                pass
            continue

        if path.suffix.lower() in {".xlsx", ".xls", ".html"}:
            # File lama jangan dipindah dulu sebelum ada download baru yang berhasil.
            continue


def archive_download_folder():
    if not DOWNLOAD_DIR.exists():
        return

    for path in DOWNLOAD_DIR.iterdir():
        if not path.is_file():
            continue

        if path.suffix.lower() in {".xlsx", ".xls", ".html"}:
            archive_stale_download(path)


async def save_download(download, download_path):
    temp_path = download_path.with_name(f".{download_path.stem}.tmp{download_path.suffix}")

    download_path.parent.mkdir(parents=True, exist_ok=True)

    if temp_path.exists():
        temp_path.unlink()

    await download.save_as(temp_path)

    for attempt in range(1, 6):
        try:
            os.replace(temp_path, download_path)
            return
        except PermissionError:
            if attempt == 5:
                raise PermissionError(
                    f"Tidak bisa menulis {download_path}. Tutup file Excel tersebut jika sedang dibuka."
                )
            await asyncio.sleep(2)


def build_report_html(rows):
    total_qty = sum(parse_float(row.get("We_stck")) for row in rows)
    colspan = len(REPORT_COLUMNS) - 1
    header_html = "".join(
        f'<th class="text-center bg-dark text-light align-middle">{escape(label)}</th>'
        for label, _ in REPORT_COLUMNS
    )
    body_html = []

    for number, row in enumerate(rows, start=1):
        cells = []
        for _, key in REPORT_COLUMNS:
            value = number if key == "number" else row.get(key, "")
            css_class = ' class="text-right"' if key == "We_stck" else ""
            cells.append(f"<td{css_class}>{escape(str(value if value is not None else ''))}</td>")
        body_html.append("<tr>" + "".join(cells) + "</tr>")

    return f"""<div class="mt-2" id="IDD_CONTENT_CONTAINER_ALLPAGE" style="display: none;">
        <div class="col-12 table-responsive mx-0 px-0" id="IDD_CONTENT_TABLE2"><div class="col-12 table-responsive mx-0 px-0" id="IDD_CONTENT_TABLE">
            <table class="table table-sm shadow-sm table-bordered" id="IDD_CONTENT_TABLE_TABLE">
                <thead>
                    <tr id="IDD_TOTAL2"><td colspan="{colspan}" class="text-right font-weight-bold">Total</td><td class="text-right font-weight-bold">{total_qty:.4f}</td></tr>
                    <tr id="IDD_THEAD">{header_html}</tr>
                </thead><tbody id="IDD_LISTE2">{''.join(body_html)}</tbody></table></div></div>
    </div>"""


def parse_float(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def save_report_rows(rows, download_path):
    temp_path = download_path.with_name(f".{download_path.stem}.tmp{download_path.suffix}")

    download_path.parent.mkdir(parents=True, exist_ok=True)

    if temp_path.exists():
        temp_path.unlink()

    temp_path.write_text(build_report_html(rows), encoding="utf-8")

    for attempt in range(1, 6):
        try:
            os.replace(temp_path, download_path)
            return
        except PermissionError:
            if attempt == 5:
                raise PermissionError(
                    f"Tidak bisa menulis {download_path}. Tutup file Excel tersebut jika sedang dibuka."
                )
            time.sleep(2)


def parse_report_date(value):
    if value is None:
        return None

    if isinstance(value, datetime):
        return value.date()

    text = str(value).strip()
    if not text:
        return None

    try:
        num = float(text)
        if 20000 < num < 90000:
            dt_raw = (datetime(1899, 12, 30) + timedelta(days=int(num))).date()
            if dt_raw.day <= 12:
                try:
                    return datetime(dt_raw.year, dt_raw.day, dt_raw.month).date()
                except ValueError:
                    return dt_raw
            return dt_raw
    except ValueError:
        pass

    clean_text = text.split(".")[0] if "." in text and len(text.split(".")[0]) >= 10 else text

    candidates = (
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%d %H:%M",
        "%Y-%m-%d",
        "%d/%m/%Y %H:%M:%S",
        "%d/%m/%Y %H:%M",
        "%d/%m/%Y",
        "%d/%m/%y %H:%M:%S",
        "%d/%m/%y %H:%M",
        "%d/%m/%y",
        "%d-%m-%Y %H:%M:%S",
        "%d-%m-%Y %H:%M",
        "%d-%m-%Y",
        "%d-%m-%y %H:%M:%S",
        "%d-%m-%y %H:%M",
        "%d-%m-%y",
        "%d.%m.%Y %H:%M:%S",
        "%d.%m.%Y %H:%M",
        "%d.%m.%Y",
        "%d.%m.%y %H:%M:%S",
        "%d.%m.%y %H:%M",
        "%d.%m.%y",
        "%Y/%m/%d %H:%M:%S",
        "%Y/%m/%d %H:%M",
        "%Y/%m/%d",
    )

    for pattern in candidates:
        try:
            return datetime.strptime(clean_text, pattern).date()
        except ValueError:
            continue
    return None


def normalize_daily_qty(value):
    return int(round(abs(parse_float(value))))


def build_engage_daily_history(rows):
    daily = {}

    for row in rows or []:
        date_value = row.get("Date") or row.get("We_datum") or row.get("date")
        report_date = parse_report_date(date_value)
        if report_date is None:
            continue

        raw_qty = parse_float(row.get("Qty") or row.get("We_stck") or row.get("qty"))
        if raw_qty == 0:
            continue

        key = report_date.isoformat()
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

    return [daily[key] for key in sorted(daily.keys())]


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


def sync_engage_transactions_to_mysql(rows, meta):
    if not rows:
        return True

    helper_path = ROOT_DIR / "sync_engage_transactions.php"
    if not helper_path.is_file():
        return True

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
                    log(f"[MySQL] {line}")

        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    log(f"[MySQL ERR] {line}")

        if result.returncode != 0:
            log(f"Sinkron transaksi MySQL keluar dengan exit code {result.returncode}")

        return result.returncode == 0
    except Exception as e:
        log(f"Gagal menjalankan sinkron transaksi MySQL: {e}")
        return False
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            pass


def sync_engage_daily_history_to_mysql(history_rows):
    if not history_rows:
        log("History harian Engage kosong, skip sinkron ke MySQL.")
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
                    log(f"[MySQL] {line}")

        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    log(f"[MySQL ERR] {line}")

        if result.returncode != 0:
            raise RuntimeError(f"Sinkron MySQL gagal dengan exit code {result.returncode}")

        return True
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            pass


async def fetch_report_page(page, page_number, timeout_ms=600000):
    return await page.evaluate(
        """async ({ pageNumber, timeoutMs }) => {
            async function refreshToken() {
                const response = await fetch(csreporturl + 'refresh-csrf');
                const data = await response.json();
                document.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.csrf_token;
                });
                return data.csrf_token;
            }

            function valueOrZero(selector) {
                const element = document.querySelector(selector);
                if (!element || element.value === '') return '0';
                return element.value;
            }

            const token = await refreshToken();
            const params = new URLSearchParams({
                _token: token,
                datvon: document.querySelector('#warein_datvon').value,
                datbis: document.querySelector('#warein_datbis').value,
                warein_lagnrvon: valueOrZero('#warein_lagnrvon'),
                warein_lagnrbis: valueOrZero('#warein_lagnrbis'),
                warein_lagfnrvon: valueOrZero('#warein_lagfnrvon'),
                warein_lagfnrbis: valueOrZero('#warein_lagfnrbis'),
                warein_artnr: valueOrZero('#warein_artnr'),
                warein_artgnr: valueOrZero('#warein_artgnr'),
                warein_artname: valueOrZero('#warein_artname'),
                warein_sernr: valueOrZero('#warein_sernr'),
                warein_adrnr: valueOrZero('#warein_adrnr'),
                warein_adrname: valueOrZero('#warein_adrname'),
                warein_kstnr: valueOrZero('#warein_kstnr'),
                warein_prdnr: valueOrZero('#warein_prdnr'),
                warein_name: valueOrZero('#warein_name'),
                warein_flds00: valueOrZero('#warein_flds00'),
                warein_flds01: valueOrZero('#warein_flds01'),
                warein_flds02: valueOrZero('#warein_flds02'),
                warein_flds03: valueOrZero('#warein_flds03'),
                warein_flds04: valueOrZero('#warein_flds04'),
                warein_flds05: valueOrZero('#warein_flds05'),
                warein_flds06: valueOrZero('#warein_flds06'),
                warein_flds07: valueOrZero('#warein_flds07'),
                warein_flds08: valueOrZero('#warein_flds08'),
                warein_flds09: valueOrZero('#warein_flds09'),
                warein_direction: document.querySelector('#warein_direction').value,
                warein_bennr: valueOrZero('#warein_bennr'),
                ipage: String(pageNumber),
            });

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
            let response;
            try {
                response = await fetch(csreporturl + 'khtx/warein/fillscreen', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: params.toString(),
                    signal: controller.signal,
                });
            } finally {
                clearTimeout(timeoutId);
            }

            if (!response.ok) {
                throw new Error(`fillscreen gagal: ${response.status}`);
            }

            return await response.json();
        }""",
        {"pageNumber": page_number, "timeoutMs": timeout_ms},
    )


async def fetch_report_page_with_retry(page, page_number):
    last_error = None

    for attempt in range(1, FETCH_PAGE_RETRY_COUNT + 1):
        try:
            return await fetch_report_page(page, page_number)
        except Exception as error:
            last_error = error
            if attempt == FETCH_PAGE_RETRY_COUNT:
                break

            log(
                f"Fetch page {page_number} gagal ({attempt}/{FETCH_PAGE_RETRY_COUNT}): {error}. "
                f"Coba ulang setelah jeda singkat."
            )
            await page.wait_for_timeout(FETCH_PAGE_RETRY_DELAY_MS)

    raise last_error


async def fetch_report_rows(page, storage, date_from, date_to, direction):
    rows = []
    total_pages = None
    page_number = 1
    recovery_attempt = 0

    while True:
        try:
            page_data = await fetch_report_page_with_retry(page, page_number)

            if page_number == 1:
                total_pages = int(page_data.get("total_page") or 1)
                rows = page_data.get("data") if isinstance(page_data.get("data"), list) else []
                log(f"Fetch page 1/{total_pages}: total row sementara {len(rows)}")
            else:
                data = page_data.get("data") if isinstance(page_data.get("data"), list) else []
                rows.extend(data)
                if page_number % 10 == 0 or page_number == total_pages:
                    log(f"Fetch page {page_number}/{total_pages}: total row sementara {len(rows)}")

            if page_number >= total_pages:
                return rows

            recovery_attempt = 0
            page_number += 1
            await page.wait_for_timeout(PAGE_FETCH_PAUSE_MS)
        except Exception as error:
            if recovery_attempt >= REPORT_RECOVERY_RETRY_COUNT:
                raise

            recovery_attempt += 1
            log(
                f"Fetch page {page_number} gagal: {error}. "
                f"Buka ulang report lalu coba lagi ({recovery_attempt}/{REPORT_RECOVERY_RETRY_COUNT})."
            )
            await prepare_report_context(page, storage, date_from, date_to, direction)
            await page.wait_for_timeout(PAGE_FETCH_PAUSE_MS)


async def download_reports(reference_date=None, reference_month=None, storage_filter=None, direction_filter=None, date_from=None, date_to=None):
    async with async_playwright() as p:
        clean_download_folder()
        downloaded_any = False

        browser = await p.chromium.launch(
            headless=True
        )

        context = await browser.new_context(
            accept_downloads=True
        )

        page = await context.new_page()
        page.set_default_timeout(600000)
        page.set_default_navigation_timeout(600000)

        # buka login
        await page.goto(LOGIN_URL)

        # isi login
        await page.fill("#email", USERNAME)
        await page.fill("#password", PASSWORD)

        # klik login
        await page.click("button[type='submit']")

        await page.wait_for_load_state("networkidle")
        await page.wait_for_timeout(3000)

        log("Login berhasil")

        # buka report
        await open_report(page)

        log("Halaman report berhasil dibuka")

        for period in get_report_periods(reference_date=reference_date, reference_month=reference_month, date_from=date_from, date_to=date_to):
            period_date_from = format_report_date(period["date_from"])
            period_date_to = format_report_date(period["date_to"])

            log(f"Proses periode {period['label']}: {period_date_from} sampai {period_date_to}")
            period_rows = []

            for condition in conditions:

                storage = condition["storage"]
                direction = condition["direction"]
                if storage_filter and storage.lower() != storage_filter.lower():
                    continue
                if direction_filter and direction != direction_filter:
                    continue

                log(f"Proses download Storage={storage}, Direction={direction}, Periode={period['label']}")

                row_count = await prepare_report_context(page, storage, period_date_from, period_date_to, direction)

                if row_count == 0:
                    log(f"Download dilewati karena data kosong: Storage={storage}, Direction={direction}, Periode={period['label']}")
                    continue

                filename = condition["filename"]

                download_path = DOWNLOAD_DIR / filename

                rows = await fetch_report_rows(page, storage, period_date_from, period_date_to, direction)
                log(f"Jumlah row export: {len(rows)}")
                save_report_rows(rows, download_path)
                downloaded_any = True

                sync_engage_transactions_to_mysql(
                    rows,
                    {
                        "storage": storage,
                        "direction": direction,
                        "source_report": Path(filename).stem,
                        "source_file": filename,
                        "period_key": period["key"],
                        "period_label": period["label"],
                        "date_from": period_date_from,
                        "date_to": period_date_to,
                    },
                )

                period_rows.extend(rows)

                log(f"Download berhasil: {download_path}")

                await page.wait_for_timeout(3000)

            archive_stale_download(DOWNLOAD_DIR / "32a_engage.xlsx")

            daily_history = build_engage_daily_history(period_rows)
            log(f"Sinkron history harian {period['key']} -> {len(daily_history)} hari")
            sync_engage_daily_history_to_mysql(daily_history)

        await browser.close()
        return downloaded_any


def run_async_job(coroutine):
    try:
        return asyncio.run(coroutine)
    except RuntimeError as error:
        if "asyncio.run() cannot be called from a running event loop" not in str(error):
            raise

    loop = asyncio.new_event_loop()
    try:
        asyncio.set_event_loop(loop)
        return loop.run_until_complete(coroutine)
    finally:
        loop.close()


def run_download_once(reference_date=None, reference_month=None, storage_filter=None, direction_filter=None, date_from=None, date_to=None):
    global RUN_IN_PROGRESS

    if RUN_IN_PROGRESS:
        log("Download dilewati karena proses sebelumnya masih berjalan")
        return False

    if reference_date is None and reference_month is None and (date_from is None or date_to is None):
        now = datetime.now()
        if now.hour < SCHEDULE_START_HOUR:
            reference_date = now.date() - timedelta(days=1)
        else:
            reference_date = now.date()

    RUN_IN_PROGRESS = True
    try:
        log("Mulai download report")
        run_async_job(download_reports(reference_date, reference_month, storage_filter, direction_filter, date_from, date_to))
        log("Selesai download report")
        return True
    except Exception as error:
        log(f"Download gagal: {error}")
        return False
    finally:
        RUN_IN_PROGRESS = False


def is_within_schedule(now=None):
    if now is None:
        now = datetime.now()
    # Jam operasional aktif: 07:00 - 01:00 (proses download 00:00 berjalan hingga selesai sebelum 01:00)
    # Jam istirahat (idle): 01:00 - 07:00 pagi
    return now.hour >= SCHEDULE_START_HOUR or now.hour == 0


def get_next_run_time(now=None):
    if now is None:
        now = datetime.now()

    today = now.date()
    candidates = []

    # Slot hari ini (07:00 - 22:00 dan 00:00 penutup hari ini)
    for hour, minute in SCHEDULE_DAILY_TIMES:
        if hour == 0 and minute == 0:
            candidates.append(datetime.combine(today + timedelta(days=1), dt_time(0, 0)))
        else:
            candidates.append(datetime.combine(today, dt_time(hour, minute)))

    # Slot esok hari (untuk antisipasi jika waktu sekarang sudah lewat tengah malam)
    for hour, minute in SCHEDULE_DAILY_TIMES:
        if hour == 0 and minute == 0:
            candidates.append(datetime.combine(today + timedelta(days=2), dt_time(0, 0)))
        else:
            candidates.append(datetime.combine(today + timedelta(days=1), dt_time(hour, minute)))

    candidates.sort()
    for dt in candidates:
        if dt > now:
            return dt

    return candidates[0]


def run_scheduler():
    if not acquire_process_lock():
        log("Scheduler sudah berjalan di proses lain. Instance ini dihentikan.")
        return

    log(
        f"Scheduler aktif. Download Engage setiap {SCHEDULE_INTERVAL_MINUTES} menit (1,5 jam) "
        f"dari {SCHEDULE_START_HOUR:02d}:00 sampai 00:00."
    )

    try:
        now = datetime.now()
        if is_within_schedule(now):
            run_download_once()

        while True:
            next_run = get_next_run_time()
            wait_seconds = max(0, (next_run - datetime.now()).total_seconds())
            log(f"Run berikutnya: {next_run:%Y-%m-%d %H:%M:%S}")
            time.sleep(wait_seconds)

            run_download_once()

            time.sleep(60)
    finally:
        release_process_lock()


def parse_reference_date(value):
    try:
        return datetime.strptime(value, "%Y-%m-%d").date()
    except ValueError as error:
        raise argparse.ArgumentTypeError("Format tanggal harus YYYY-MM-DD, contoh: 2026-09-02") from error


def parse_reference_month(value):
    try:
        return datetime.strptime(value, "%Y-%m").date()
    except ValueError as error:
        raise argparse.ArgumentTypeError("Format bulan harus YYYY-MM, contoh: 2026-09") from error


def main():
    parser = argparse.ArgumentParser(description="Engage RPA downloader")
    parser.add_argument("--once", action="store_true", help="Jalankan download sekali lalu keluar")
    parser.add_argument(
        "--reference-date",
        type=parse_reference_date,
        help="Tanggal download dalam format YYYY-MM-DD (default: hari ini).",
    )
    parser.add_argument(
        "--reference-month",
        type=parse_reference_month,
        help="Bulan download dalam format YYYY-MM jika ingin unduh satu bulan penuh.",
    )
    parser.add_argument(
        "--date-from",
        type=parse_reference_date,
        help="Tanggal awal download dalam format YYYY-MM-DD.",
    )
    parser.add_argument(
        "--date-to",
        type=parse_reference_date,
        help="Tanggal akhir download dalam format YYYY-MM-DD.",
    )
    parser.add_argument("--storage", help="Filter storage, contoh: 32a")
    parser.add_argument("--direction", help="Filter direction, contoh: 0")
    args = parser.parse_args()

    if args.once:
        if os.getenv("RPA_BYPASS_CHILD_LOCK") != "1" and not acquire_process_lock():
            log("Download sekali dilewati karena scheduler/proses lain masih berjalan.")
            return 1

        try:
            success = run_download_once(args.reference_date, args.reference_month, args.storage, args.direction, args.date_from, args.date_to)
        finally:
            release_process_lock()
        return 0 if success else 1

    run_scheduler()
    return 0


if __name__ == "__main__":
    main()
