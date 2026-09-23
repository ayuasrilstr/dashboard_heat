<?php
declare(strict_types=1);

$input_file = $argv[1] ?? '';
if ($input_file === '' || !is_file($input_file)) {
    fwrite(STDERR, "Input JSON tidak ditemukan.\n");
    exit(1);
}

$env_file = __DIR__ . '/.env';
if (is_file($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if (!getenv($k)) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
    }
}

$raw = file_get_contents($input_file);

$rows = json_decode($raw, true);
if (!is_array($rows)) {
    fwrite(STDERR, "Payload JSON tidak valid.\n");
    exit(1);
}

$host = getenv('ENGAGE_DB_HOST') ?: 'localhost';
$user = getenv('ENGAGE_DB_USER') ?: 'root';
$password = getenv('ENGAGE_DB_PASSWORD') ?: '';
$database = getenv('ENGAGE_DB_NAME') ?: 'db_dashboardgm';
$table = getenv('ENGAGE_DAILY_HISTORY_TABLE') ?: 'engage_daily_history';

$mysqli = @new mysqli($host, $user, $password, $database);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Koneksi MySQL gagal: " . $mysqli->connect_error . "\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

$create_sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
    `date` DATE NOT NULL,
    `input_qty` INT NOT NULL DEFAULT 0,
    `output_qty` INT NOT NULL DEFAULT 0,
    `ready_qty` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$mysqli->query($create_sql)) {
    fwrite(STDERR, "Gagal membuat tabel: " . $mysqli->error . "\n");
    exit(1);
}

$sql = "INSERT INTO `{$table}` (`date`, `input_qty`, `output_qty`, `ready_qty`)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `input_qty` = VALUES(`input_qty`),
            `output_qty` = VALUES(`output_qty`),
            `ready_qty` = VALUES(`ready_qty`)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    fwrite(STDERR, "Gagal menyiapkan query: " . $mysqli->error . "\n");
    exit(1);
}

$count = 0;
foreach ($rows as $row) {
    if (!is_array($row) || empty($row['date'])) {
        continue;
    }

    $date = (string) $row['date'];
    $input_qty = isset($row['input_qty']) ? (int) $row['input_qty'] : 0;
    $output_qty = isset($row['output_qty']) ? (int) $row['output_qty'] : 0;
    $ready_qty = isset($row['ready_qty']) ? (int) $row['ready_qty'] : 0;

    $stmt->bind_param('siii', $date, $input_qty, $output_qty, $ready_qty);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Gagal upsert tanggal {$date}: " . $stmt->error . "\n");
        $stmt->close();
        $mysqli->close();
        exit(1);
    }

    $count++;
}

$stmt->close();
$mysqli->close();

echo "Sinkron sukses: {$count} baris.\n";
exit(0);
