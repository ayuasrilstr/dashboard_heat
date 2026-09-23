import argparse
import sys
import os
import time
import subprocess
import msvcrt
import shutil
from datetime import datetime, time as dt_time, timedelta
from pathlib import Path

try:
    import tkinter as tk
    from tkinter import messagebox
except Exception:
    tk = None
    messagebox = None

if getattr(sys, "frozen", False):
    exe_dir = Path(sys.executable).resolve().parent
    root_candidates = [
        exe_dir / "rpa",
        exe_dir.parent / "rpa",
        exe_dir,
        exe_dir.parent,
    ]

    ROOT_DIR = exe_dir
    for candidate in root_candidates:
        if (
            (candidate / "accessories-rpa").is_dir()
            and (candidate / "engage-rpa").is_dir()
            and (candidate / "aps-rpa").is_dir()
        ):
            ROOT_DIR = candidate
            break
else:
    ROOT_DIR = Path(__file__).resolve().parent
LOG_DIR = ROOT_DIR / "logs"
LOG_PATH = LOG_DIR / "scheduler.log"
LOCK_PATH = LOG_DIR / "scheduler.lock"

SCHEDULE_START_HOUR = 7
SCHEDULE_END_HOUR = 24  # 00:00 tengah malam
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

LOCK_FILE = None
RUN_IN_PROGRESS = False

# Ensure logs directory exists
LOG_DIR.mkdir(parents=True, exist_ok=True)

def log(message):
    log_text = f"[{datetime.now():%Y-%m-%d %H:%M:%S}] [Master Scheduler] {message}"
    print(log_text)
    try:
        with LOG_PATH.open("a", encoding="utf-8") as log_file:
            log_file.write(log_text + "\n")
    except Exception as e:
        print(f"Gagal menulis log: {e}")


def confirm_run():
    if tk is None or messagebox is None:
        return True

    root = tk.Tk()
    root.withdraw()
    root.attributes("-topmost", True)
    root.update()

    try:
        return messagebox.askyesno(
            "Konfirmasi RPA",
            "Anda yakin ingin menjalankan RPA?",
            parent=root,
            default=messagebox.YES,
        )
    finally:
        root.destroy()

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

def get_python_executable():
    if not getattr(sys, "frozen", False):
        return sys.executable

    for candidate in (shutil.which("python"), shutil.which("python.exe")):
        if candidate:
            return candidate

    common_paths = [
        Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Python" / "Python314" / "python.exe",
        Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Python" / "Python313" / "python.exe",
        Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Python" / "Python312" / "python.exe",
        Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Python" / "Python311" / "python.exe",
        Path(os.environ.get("LOCALAPPDATA", "")) / "Programs" / "Python" / "Python310" / "python.exe",
    ]

    for candidate in common_paths:
        if candidate.is_file():
            return str(candidate)

    return None

def run_rpa_script(name, args, cwd):
    log(f"Menjalankan RPA: {name}...")
    try:
        python_exe = get_python_executable()
        if not python_exe:
            log("Python executable tidak ditemukan.")
            return False

        script_path = cwd / "main.py"
        cmd = [python_exe, str(script_path)] + args
        log(f"Command: {' '.join(cmd)} di {cwd}")
        
        env = os.environ.copy()
        env["RPA_BYPASS_CHILD_LOCK"] = "1"

        result = subprocess.run(
            cmd,
            cwd=str(cwd),
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            env=env,
            creationflags=subprocess.CREATE_NO_WINDOW if hasattr(subprocess, "CREATE_NO_WINDOW") else 0,
        )
        
        if result.stdout:
            for line in result.stdout.splitlines():
                if line.strip():
                    log(f"[{name}] {line}")
                    
        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    log(f"[{name} ERR] {line}")
                    
        if result.returncode == 0:
            log(f"RPA {name} selesai dengan sukses.")
            return True
        else:
            log(f"RPA {name} gagal dengan exit code {result.returncode}.")
            return False
            
    except Exception as e:
        log(f"Error saat menjalankan RPA {name}: {e}")
        return False

def get_engage_reference_date(now=None):
    if now is None:
        now = datetime.now()
    # Jika berjalan di jam 00:00 - 06:59 (misal download penutup pukul 00:00),
    # maka reference_date adalah hari kemarin (H-1) untuk menutup rekap hari tersebut.
    # Jika berjalan dari jam 07:00 - 23:59, reference_date adalah hari ini (H).
    if now.hour < SCHEDULE_START_HOUR:
        return (now.date() - timedelta(days=1)).strftime("%Y-%m-%d")
    return now.date().strftime("%Y-%m-%d")

def run_all_rpa_once():
    global RUN_IN_PROGRESS
    if RUN_IN_PROGRESS:
        log("Download dilewati karena proses sebelumnya masih berjalan.")
        return False
        
    RUN_IN_PROGRESS = True
    overall_ok = True
    log("=== MEMULAI DOWNLOAD SEMUA RPA ===")

    # 1. Accessories RPA
    accessories_dir = ROOT_DIR / "accessories-rpa"
    if accessories_dir.exists():
        overall_ok = run_rpa_script("Accessories RPA", ["--headless"], accessories_dir) and overall_ok
    else:
        log("Folder accessories-rpa tidak ditemukan.")
        overall_ok = False
        
    # 2. Engage RPA
    engage_dir = ROOT_DIR / "engage-rpa"
    if engage_dir.exists():
        ref_date = get_engage_reference_date()
        log(f"Reference date Engage untuk run ini: {ref_date}")
        overall_ok = run_rpa_script("Engage RPA", ["--once", "--reference-date", ref_date], engage_dir) and overall_ok
    else:
        log("Folder engage-rpa tidak ditemukan.")
        overall_ok = False
        
    # 3. APS RPA
    aps_dir = ROOT_DIR / "aps-rpa"
    if aps_dir.exists():
        overall_ok = run_rpa_script("APS RPA", [], aps_dir) and overall_ok
    else:
        log("Folder aps-rpa tidak ditemukan.")
        overall_ok = False
        
    log("=== SELESAI DOWNLOAD SEMUA RPA ===")
    RUN_IN_PROGRESS = False
    return overall_ok

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
        
    log(f"Scheduler aktif. Download semua RPA setiap {SCHEDULE_INTERVAL_MINUTES} menit (1,5 jam) dari {SCHEDULE_START_HOUR:02d}:00 sampai 00:00.")
    
    try:
        now = datetime.now()
        if is_within_schedule(now):
            run_all_rpa_once()
            
        while True:
            next_run = get_next_run_time()
            wait_seconds = max(0, (next_run - datetime.now()).total_seconds())
            log(f"Run berikutnya: {next_run:%Y-%m-%d %H:%M:%S}")
            time.sleep(wait_seconds)
            
            run_all_rpa_once()
            
            time.sleep(60)
    finally:
        release_process_lock()

def main():
    parser = argparse.ArgumentParser(description="Master RPA Scheduler")
    parser.add_argument("--once", action="store_true", help="Jalankan semua RPA sekali lalu keluar")
    parser.add_argument(
        "--no-confirm",
        action="store_true",
        help="Jalankan tanpa dialog konfirmasi interaktif",
    )
    args = parser.parse_args()

    if not args.no_confirm and not confirm_run():
        log("Dibatalkan oleh pengguna.")
        return 0

    if args.once:
        if not acquire_process_lock():
            log("Download sekali dilewati karena scheduler/proses lain sedang berjalan.")
            return 1
        try:
            success = run_all_rpa_once()
        finally:
            release_process_lock()
        return 0 if success else 1

    run_scheduler()
    return 0

if __name__ == "__main__":
    sys.exit(main())
