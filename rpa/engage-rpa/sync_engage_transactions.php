<?php

date_default_timezone_set('Asia/Jakarta');

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Script ini hanya bisa dijalankan dari CLI.\n");
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


if ($argc < 2) {
    $db = connect_db();
    $transaction_table = getenv('ENGAGE_TRANSACTIONS_TABLE') ?: 'tb_engage_transactions';
    $archive_table = getenv('ENGAGE_ARCHIVE_TABLE') ?: 'tb_engage_archieve';
    $retention_days = getenv('ENGAGE_ARCHIVE_RETENTION_DAYS') ?: '90';
    $timezone = new DateTimeZone('Asia/Jakarta');
    $today = new DateTimeImmutable('today', $timezone);

    $transaction_columns = load_table_columns($db, $transaction_table);
    $archive_columns = load_table_columns($db, $archive_table);

    $moved = move_past_transactions_to_archive($db, $transaction_table, $archive_table, $transaction_columns, $archive_columns, $today, $timezone);
    $purged = cleanup_archive_retention($db, $archive_table, $archive_columns, $retention_days, $timezone);

    echo "Maintenance selesai: {$moved} baris lama dipindahkan ke {$archive_table}. Arsip > {$retention_days} hari dihapus: {$purged} baris.\n";
    exit(0);
}

$payload_path = $argv[1];
if (!is_file($payload_path)) {
    fwrite(STDERR, "Payload tidak ditemukan: {$payload_path}\n");
    exit(1);
}

$payload_raw = file_get_contents($payload_path);
$payload = json_decode($payload_raw, true);
if (!is_array($payload)) {
    fwrite(STDERR, "Payload JSON tidak valid.\n");
    exit(1);
}

$rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : array();
$meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : array();

$db = connect_db();
$transaction_table = getenv('ENGAGE_TRANSACTIONS_TABLE') ?: 'tb_engage_transactions';
$archive_table = getenv('ENGAGE_ARCHIVE_TABLE') ?: 'tb_engage_archieve';
$retention_days = getenv('ENGAGE_ARCHIVE_RETENTION_DAYS') ?: '90';
$timezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTimeImmutable('today', $timezone);
$now = new DateTimeImmutable('now', $timezone);

$transaction_columns = load_table_columns($db, $transaction_table);
$archive_columns = load_table_columns($db, $archive_table);
ensure_duplicate_rows_allowed($db, $transaction_table);
ensure_duplicate_rows_allowed($db, $archive_table);

// 1. Pindahkan semua transaksi tanggal yang sudah berlalu (< today) ke tabel arsip
$moved_past_records = move_past_transactions_to_archive($db, $transaction_table, $archive_table, $transaction_columns, $archive_columns, $today, $timezone);

$split_rows = array(
    'transactions' => array(),
    'archive' => array(),
);

foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }

    $row_date = resolve_row_date($row, $meta, $timezone, $today);
    $target = $row_date->getTimestamp() < $today->getTimestamp() ? 'archive' : 'transactions';
    $split_rows[$target][] = $row;
}

$source_filters = build_source_filters($meta);

$deleted_transactions = delete_existing_today_storage_rows($db, $transaction_table, $transaction_columns, $meta, $today);
$deleted_archive = delete_existing_source_rows($db, $archive_table, $archive_columns, $source_filters, $meta);

$inserted_transactions = sync_rows_to_table($db, $transaction_table, $transaction_columns, $split_rows['transactions'], $meta, $now, $timezone);
$inserted_archive = sync_rows_to_table($db, $archive_table, $archive_columns, $split_rows['archive'], $meta, $now, $timezone);
$purged_archive = cleanup_archive_retention($db, $archive_table, $archive_columns, $retention_days, $timezone);

echo "Sinkron sukses: {$inserted_transactions} baris hari ini ke {$transaction_table}, {$inserted_archive} baris ke {$archive_table}.";
if ($moved_past_records > 0) {
    echo " {$moved_past_records} baris transaksi lama otomatis dipindahkan ke {$archive_table}.";
}
if ($deleted_transactions > 0) {
    echo " Data hari ini yang sudah ada sebelumnya diperbarui.";
}
if ($purged_archive > 0) {
    echo " Arsip lama yang lebih tua dari {$retention_days} hari dihapus: {$purged_archive} baris.";
}
echo "\n";

exit(0);

function connect_db()
{
    $host = getenv('ENGAGE_DB_HOST') ?: 'localhost';
    $user = getenv('ENGAGE_DB_USER') ?: 'root';
    $password = getenv('ENGAGE_DB_PASSWORD') ?: '';
    $name = getenv('ENGAGE_DB_NAME') ?: 'db_dashboardgm';
    $port = getenv('ENGAGE_DB_PORT') ?: '3306';

    $db = @new mysqli($host, $user, $password, $name, (int) $port);
    if ($db->connect_errno) {
        fwrite(STDERR, "Koneksi MySQL gagal: " . $db->connect_error . "\n");
        exit(1);
    }

    $db->set_charset('utf8mb4');
    return $db;
}

function load_table_columns($db, $table)
{
    $columns = array();
    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $result = $db->query("SHOW COLUMNS FROM {$table_sql}");
    if (!$result) {
        return $columns;
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    $result->free();
    return $columns;
}

function ensure_duplicate_rows_allowed($db, $table)
{
    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $index_name = 'unique_engage_data';

    $check = $db->query("SHOW INDEX FROM {$table_sql} WHERE Key_name = '" . $db->real_escape_string($index_name) . "'");
    if (!$check) {
        fwrite(STDERR, "Gagal memeriksa index pada {$table}: " . $db->error . "\n");
        exit(1);
    }

    if ($check->num_rows === 0) {
        $check->free();
        return;
    }

    $check->free();

    $drop_sql = "ALTER TABLE {$table_sql} DROP INDEX `" . str_replace('`', '``', $index_name) . "`";
    if (!$db->query($drop_sql)) {
        fwrite(STDERR, "Gagal menghapus unique index {$index_name} pada {$table}: " . $db->error . "\n");
        exit(1);
    }

    echo "Unique index {$index_name} di {$table} dihapus agar semua baris Excel bisa masuk.\n";
}

function delete_existing_source_rows($db, $table, array $columns, array $source_filters, array $meta = array())
{
    if (!$columns || !$source_filters) {
        return 0;
    }

    $where = array();
    foreach ($source_filters as $column => $value) {
        if ($value === NULL || $value === '') {
            continue;
        }

        $target_col = $column;
        if (!isset($columns[$target_col])) {
            if ($column === 'storage' && isset($columns['storage_nr'])) {
                $target_col = 'storage_nr';
            } elseif ($column === 'storage_nr' && isset($columns['storage'])) {
                $target_col = 'storage';
            } else {
                continue;
            }
        }

        $where[] = '`' . str_replace('`', '``', $target_col) . '` = ' . sql_value($db, $value, $columns[$target_col]);
    }

    if (isset($columns['transaction_date'])) {
        if (!empty($meta['date_from']) && !empty($meta['date_to'])) {
            $date_from_sql = $db->real_escape_string($meta['date_from']);
            $date_to_sql = $db->real_escape_string($meta['date_to']);
            $where[] = "DATE(`transaction_date`) BETWEEN '{$date_from_sql}' AND '{$date_to_sql}'";
        } elseif (!empty($meta['date_from'])) {
            $date_from_sql = $db->real_escape_string($meta['date_from']);
            $where[] = "DATE(`transaction_date`) = '{$date_from_sql}'";
        }
    }

    if (!$where) {
        return 0;
    }

    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $sql = "DELETE FROM {$table_sql} WHERE " . implode(' AND ', $where);
    if (!$db->query($sql)) {
        fwrite(STDERR, "Gagal membersihkan data lama pada {$table}: " . $db->error . "\n");
        exit(1);
    }

    return $db->affected_rows;
}

function sync_rows_to_table($db, $table, array $columns, array $rows, array $meta, DateTimeImmutable $now, DateTimeZone $timezone)
{
    if (!$columns || !$rows) {
        return 0;
    }

    $inserted = 0;
    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $column_names = array();
    foreach ($columns as $name => $info) {
        if (is_auto_increment_column($info)) {
            continue;
        }
        $column_names[] = $name;
    }

    if (!$column_names) {
        return 0;
    }

    $cols_header_sql = '`' . implode('`,`', array_map('escape_identifier', $column_names)) . '`';
    $batch_size = 250;
    $value_tuples = array();

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $values = array();
        $row_date = resolve_row_date($row, $meta, $timezone, new DateTimeImmutable('today', $timezone));
        foreach ($column_names as $column) {
            $values[] = sql_value($db, resolve_column_value($column, $row, $meta, $row_date, $now, $columns[$column]), $columns[$column]);
        }

        $value_tuples[] = '(' . implode(',', $values) . ')';

        if (count($value_tuples) >= $batch_size) {
            $sql = "INSERT INTO {$table_sql} ({$cols_header_sql}) VALUES " . implode(',', $value_tuples);
            if (!$db->query($sql)) {
                fwrite(STDERR, "Gagal insert batch ke {$table}: " . $db->error . "\n");
                exit(1);
            }
            $inserted += max(1, (int) $db->affected_rows);
            $value_tuples = array();
        }
    }

    if (!empty($value_tuples)) {
        $sql = "INSERT INTO {$table_sql} ({$cols_header_sql}) VALUES " . implode(',', $value_tuples);
        if (!$db->query($sql)) {
            fwrite(STDERR, "Gagal insert batch akhir ke {$table}: " . $db->error . "\n");
            exit(1);
        }
        $inserted += max(1, (int) $db->affected_rows);
    }

    return $inserted;
}

function cleanup_archive_retention($db, $table, array $columns, $retention_days, DateTimeZone $timezone)
{
    $retention_days = (int) $retention_days;
    if ($retention_days <= 0) {
        return 0;
    }

    $date_column = first_existing_column($columns, array(
        'Date',
        'date',
        'wedatum',
        'transactiondate',
        'transaction_date',
        'archivedate',
        'archive_date',
        'rowdate',
        'row_date',
        'reportdate',
        'report_date',
        'created_at',
        'synced_at',
    ));

    if ($date_column === NULL) {
        echo "Pembersihan arsip dilewati karena kolom tanggal tidak ditemukan di {$table}.\n";
        return 0;
    }

    $retain_days = max(1, $retention_days);
    $cutoff = (new DateTimeImmutable('today', $timezone))->modify('-' . ($retain_days - 1) . ' days');
    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $column_sql = '`' . str_replace('`', '``', $date_column) . '`';
    $sql = "DELETE FROM {$table_sql} WHERE {$column_sql} < '" . $cutoff->format('Y-m-d') . "'";

    if (!$db->query($sql)) {
        fwrite(STDERR, "Gagal membersihkan arsip lama pada {$table}: " . $db->error . "\n");
        exit(1);
    }

    return $db->affected_rows;
}

function first_existing_column(array $columns, array $candidates)
{
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    return NULL;
}

function escape_identifier($name)
{
    return str_replace('`', '``', $name);
}

function is_auto_increment_column(array $column_info)
{
    return isset($column_info['Extra']) && stripos($column_info['Extra'], 'auto_increment') !== false;
}

function build_source_filters(array $meta)
{
    $filters = array();
    foreach (array('source_file', 'report_key', 'source_report', 'storage', 'direction', 'period_key') as $key) {
        if (isset($meta[$key]) && $meta[$key] !== '') {
            $filters[$key] = $meta[$key];
        }
    }

    if (!isset($filters['source_report']) && isset($filters['report_key'])) {
        $filters['source_report'] = $filters['report_key'];
    }

    return $filters;
}

function resolve_column_value($column, array $row, array $meta, DateTimeImmutable $row_date, DateTimeImmutable $now, array $column_info)
{
    $row_map = build_normalized_map($row);
    $meta_map = build_normalized_map($meta);
    $column_key = normalize_identifier($column);
    if (isset($row[$column])) {
        return $row[$column];
    }
    if (isset($row_map[$column_key])) {
        return $row_map[$column_key];
    }

    foreach (column_aliases($column_key) as $candidate) {
        $candidate_key = normalize_identifier($candidate);
        if (isset($row_map[$candidate_key])) {
            return $row_map[$candidate_key];
        }
        if (isset($meta_map[$candidate_key])) {
            return $meta_map[$candidate_key];
        }
    }

    if (isset($meta_map[$column_key])) {
        return $meta_map[$column_key];
    }

    if (in_array($column_key, array('transactiondate', 'archivedate', 'rowdate', 'reportdate'), true)) {
        return $row_date->format('Y-m-d');
    }

    if (in_array($column_key, array('capturedat', 'updatedat', 'syncedat', 'createdat'), true)) {
        return $now->format('Y-m-d H:i:s');
    }

    return default_value_for_column($column, $column_key, $now, $column_info);
}

function resolve_row_date(array $row, array $meta, DateTimeZone $timezone, DateTimeImmutable $today)
{
    $candidates = array(
        'Date',
        'We_datum',
        'date',
        'transaction_date',
        'history_date',
        'archive_date',
    );

    foreach ($candidates as $key) {
        if (array_key_exists($key, $row)) {
            $parsed = parse_date_value($row[$key], $timezone);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }
    }

    foreach (array('date_from', 'period_key', 'report_date') as $key) {
        if (!isset($meta[$key])) {
            continue;
        }
        $parsed = parse_date_value($meta[$key], $timezone);
        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }
    }

    return $today;
}

function parse_date_value($value, DateTimeZone $timezone)
{
    if ($value instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($value)->setTime(0, 0, 0);
    }

    if ($value === NULL) {
        return NULL;
    }

    $text = trim((string) $value);
    if ($text === '') {
        return NULL;
    }

    if (is_numeric($text)) {
        $serial = (float) $text;
        if ($serial > 0 && $serial < 90000) {
            $raw_ts = ($serial - 25569) * 86400;
            $m = (int) gmdate('n', (int) $raw_ts);
            $d = (int) gmdate('j', (int) $raw_ts);
            $y = (int) gmdate('Y', (int) $raw_ts);
            if ($d <= 12) {
                return (new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $d, $m), $timezone))->setTime(0, 0, 0);
            }
            $base = new DateTimeImmutable('1899-12-30', $timezone);
            $days = (int) floor($serial);
            return $base->modify('+' . $days . ' days')->setTime(0, 0, 0);
        }
    }

    $patterns = array(
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y',
        'd/m/y H:i:s',
        'd/m/y H:i',
        'd/m/y',
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd-m-Y',
        'd-m-y H:i:s',
        'd-m-y H:i',
        'd-m-y',
        'd.m.Y H:i:s',
        'd.m.Y H:i',
        'd.m.Y',
        'd.m.y H:i:s',
        'd.m.y H:i',
        'd.m.y',
        'Y/m/d H:i:s',
        'Y/m/d H:i',
        'Y/m/d',
        'Ymd',
    );

    foreach ($patterns as $pattern) {
        $parsed = DateTimeImmutable::createFromFormat('!' . $pattern, $text, $timezone);
        if ($parsed instanceof DateTimeImmutable) {
            return $parsed->setTime(0, 0, 0);
        }
    }

    $timestamp = strtotime($text);
    if ($timestamp !== false) {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->setTime(0, 0, 0);
    }

    return NULL;
}

function normalize_identifier($value)
{
    return preg_replace('/[^a-z0-9]+/i', '', strtolower(trim((string) $value)));
}

function build_normalized_map(array $values)
{
    $map = array();
    foreach ($values as $key => $value) {
        $map[normalize_identifier($key)] = $value;
    }

    return $map;
}

function column_aliases($column_key)
{
    $aliases = array(
        'wedatum' => array('Date', 'We_datum', 'date', 'transaction_date', 'history_date', 'archive_date'),
        'welagnr' => array('Storage Nr', 'We_lagnr', 'storage', 'storage_nr'),
        'welagfnr' => array('Location Nr', 'We_lagfnr', 'location', 'location_nr'),
        'weartnr' => array('Item Nr', 'We_artnr', 'item_nr', 'artnr'),
        'artname' => array('Item Name', 'Art_name'),
        'artname2' => array('Item Name 2', 'Art_name2'),
        'wesernr' => array('Serial Nr', 'We_sernr'),
        'weadrnr' => array('Address Nr', 'We_adrnr'),
        'adrname' => array('Address Name', 'Adr_name'),
        'welagnr2' => array('Storage 2', 'We_lagnr2'),
        'welagfnr2' => array('Location 2', 'We_lagfnr2'),
        'westck' => array('Qty', 'We_stck', 'qty'),
        'artme' => array('Unit', 'Art_me'),
        'wename' => array('Text', 'We_name'),
        'wekstnr' => array('Cost Center', 'We_kstnr'),
        'weprdnr' => array('Prod. Nr', 'We_prdnr'),
    );

    // Database columns use the readable snake_case names, while the report
    // payload uses the Excel labels and legacy We_* field names.
    $database_aliases = array(
        'transactiondate' => array('Date', 'We_datum', 'date', 'transaction_date'),
        'storagenr' => array('Storage Nr', 'We_lagnr', 'storage', 'storage_nr'),
        'locationnr' => array('Location Nr', 'We_lagfnr', 'location', 'location_nr'),
        'itemnr' => array('Item Nr', 'We_artnr', 'item_nr', 'artnr'),
        'itemname' => array('Item Name', 'Art_name'),
        'itemname2' => array('Item Name 2', 'Art_name2'),
        'serialnr' => array('Serial Nr', 'We_sernr'),
        'addressnr' => array('Address Nr', 'We_adrnr'),
        'addressname' => array('Address Name', 'Adr_name'),
        'storage2' => array('Storage 2', 'We_lagnr2'),
        'location2' => array('Location 2', 'We_lagfnr2'),
        'qty' => array('Qty', 'We_stck', 'qty'),
        'unit' => array('Unit', 'Art_me'),
        'text' => array('Text', 'We_name'),
        'costcenter' => array('Cost Center', 'We_kstnr'),
        'prodnr' => array('Prod. Nr', 'We_prdnr'),
        'usercreator' => array('User Creator', 'We_bennr'),
    );

    foreach ($database_aliases as $database_key => $database_values) {
        $aliases[$database_key] = $database_values;
    }

    for ($i = 0; $i <= 9; $i++) {
        $aliases['weflds' . sprintf('%02d', $i)] = array('Udef ' . ($i + 1), 'We_flds' . sprintf('%02d', $i));
        $aliases['udef' . ($i + 1)] = array('Udef ' . ($i + 1), 'We_flds' . sprintf('%02d', $i));
    }

    $aliases['webennr'] = array('User Creator', 'We_bennr');
    $aliases['reportkey'] = array('report_key', 'source_report');
    $aliases['sourcefile'] = array('source_file');
    $aliases['storage'] = array('storage', 'Storage Nr', 'We_lagnr');
    $aliases['direction'] = array('direction', 'warein_direction');
    $aliases['periodkey'] = array('period_key');
    $aliases['periodlabel'] = array('period_label');
    $aliases['datefrom'] = array('date_from');
    $aliases['dateto'] = array('date_to');
    $aliases['transactiondate'] = array('transaction_date');
    $aliases['archivedate'] = array('archive_date');
    $aliases['capturedat'] = array('captured_at');
    $aliases['updatedat'] = array('updated_at');
    $aliases['syncedat'] = array('synced_at');
    $aliases['createdat'] = array('created_at');

    return isset($aliases[$column_key]) ? $aliases[$column_key] : array();
}

function default_value_for_column($column, $column_key, DateTimeImmutable $now, array $column_info)
{
    $type = isset($column_info['Type']) ? strtolower($column_info['Type']) : '';
    $date_like = preg_match('/(date|time|timestamp)/i', $column_key) || preg_match('/(date|time|timestamp)/i', $column);
    if ($date_like) {
        if (stripos($column, 'time') !== false || stripos($column, 'timestamp') !== false) {
            return $now->format('Y-m-d H:i:s');
        }
        return $now->format('Y-m-d');
    }

    if (preg_match('/\b(int|decimal|double|float|bigint|smallint|mediumint|tinyint|bit)\b/i', $type)) {
        return 0;
    }

    return '';
}

function parse_numeric_value($value)
{
    if ($value === NULL || $value === '') {
        return NULL;
    }

    if (is_int($value) || is_float($value)) {
        return $value;
    }

    $text = trim((string) $value);
    if ($text === '') {
        return NULL;
    }

    $text = str_replace(array(' ', ','), array('', ''), $text);
    if (!preg_match('/-?\d+(?:\.\d+)?/', $text, $match)) {
        return NULL;
    }

    return (float) $match[0];
}

function sql_value($db, $value, array $column_info)
{
    if ($value === NULL || $value === '') {
        if (isset($column_info['Default']) && $column_info['Default'] !== NULL && $column_info['Default'] !== '') {
            $default = strtoupper(trim((string) $column_info['Default']));
            if (in_array($default, array('CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'), true)) {
                return $default;
            } else {
                $value = $column_info['Default'];
            }
        } else {
            $column_name = isset($column_info['Field']) ? $column_info['Field'] : '';
            $column_key = normalize_identifier($column_name);
            $value = default_value_for_column($column_name, $column_key, new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')), $column_info);
        }
    }

    $type = isset($column_info['Type']) ? strtolower($column_info['Type']) : '';
    if (preg_match('/\b(int|decimal|double|float|bigint|smallint|mediumint|tinyint|bit)\b/', $type)) {
        $numeric = parse_numeric_value($value);
        if ($numeric === NULL) {
            return 'NULL';
        }
        if (preg_match('/int|bigint|smallint|mediumint|tinyint|bit/', $type)) {
            return (string) (int) round($numeric);
        }
        return rtrim(rtrim(sprintf('%.10F', $numeric), '0'), '.');
    }

    if (preg_match('/\b(date|datetime|timestamp|time)\b/', $type)) {
        if (is_string($value)) {
            $default_keyword = strtoupper(trim($value));
            if ($default_keyword === 'CURRENT_TIMESTAMP') {
                return 'CURRENT_TIMESTAMP';
            }
            if ($default_keyword === 'CURRENT_DATE') {
                return 'CURRENT_DATE';
            }
            if ($default_keyword === 'CURRENT_TIME') {
                return 'CURRENT_TIME';
            }
        }
        $timezone = new DateTimeZone('Asia/Jakarta');
        $parsed = parse_date_value($value, $timezone);
        if ($parsed instanceof DateTimeImmutable) {
            if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
                $value = $parsed->format('Y-m-d H:i:s');
            } else {
                $value = $parsed->format('Y-m-d');
            }
        } elseif (is_string($value)) {
            $value = trim($value);
        }
    }

    if ($value instanceof DateTimeInterface) {
        $value = $value->format('Y-m-d H:i:s');
    }

    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }

    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    $escaped = $db->real_escape_string((string) $value);
    return "'" . $escaped . "'";
}

function move_past_transactions_to_archive($db, $transaction_table, $archive_table, array $transaction_columns, array $archive_columns, DateTimeImmutable $today, DateTimeZone $timezone)
{
    $date_col = first_existing_column($transaction_columns, array(
        'transaction_date',
        'Date',
        'We_datum',
        'date',
        'wedatum',
        'row_date',
        'report_date',
    ));

    if (!$date_col) {
        return 0;
    }

    $common_columns = array();
    foreach ($transaction_columns as $col_name => $info) {
        if (is_auto_increment_column($info)) {
            continue;
        }
        if (isset($archive_columns[$col_name]) && !is_auto_increment_column($archive_columns[$col_name])) {
            $common_columns[] = $col_name;
        }
    }

    if (!$common_columns) {
        return 0;
    }

    $today_str = $today->format('Y-m-d 00:00:00');
    $trans_sql = '`' . str_replace('`', '``', $transaction_table) . '`';
    $arch_sql = '`' . str_replace('`', '``', $archive_table) . '`';
    $date_sql = '`' . str_replace('`', '``', $date_col) . '`';
    $cols_sql = '`' . implode('`, `', array_map('escape_identifier', $common_columns)) . '`';

    // 1. Salin baris yang tanggalnya sudah berlalu (< today) ke tabel arsip
    $insert_sql = "INSERT INTO {$arch_sql} ({$cols_sql}) SELECT {$cols_sql} FROM {$trans_sql} WHERE {$date_sql} < '{$today_str}'";
    if (!$db->query($insert_sql)) {
        fwrite(STDERR, "Gagal memindahkan transaksi lama ke arsip: " . $db->error . "\n");
        return 0;
    }

    $moved_count = (int) $db->affected_rows;

    // 2. Hapus baris yang sudah dipindahkan dari tabel transaksi
    if ($moved_count > 0) {
        $delete_sql = "DELETE FROM {$trans_sql} WHERE {$date_sql} < '{$today_str}'";
        if (!$db->query($delete_sql)) {
            fwrite(STDERR, "Gagal menghapus transaksi lama dari {$transaction_table}: " . $db->error . "\n");
        }
    }

    return $moved_count;
}

function delete_existing_today_storage_rows($db, $table, array $columns, array $meta, DateTimeImmutable $today)
{
    $storage_col = first_existing_column($columns, array('storage_nr', 'welagnr', 'storage'));
    $date_col = first_existing_column($columns, array('transaction_date', 'Date', 'We_datum', 'date', 'wedatum'));
    $storage_val = isset($meta['storage']) ? trim((string)$meta['storage']) : '';

    if (!$storage_col || !$date_col || $storage_val === '') {
        return 0;
    }

    $table_sql = '`' . str_replace('`', '``', $table) . '`';
    $storage_sql = '`' . str_replace('`', '``', $storage_col) . '`';
    $date_sql = '`' . str_replace('`', '``', $date_col) . '`';
    $today_str = $today->format('Y-m-d');

    $sql = "DELETE FROM {$table_sql} WHERE {$storage_sql} = '" . $db->real_escape_string($storage_val) . "' AND DATE({$date_sql}) = '{$today_str}'";
    if ($db->query($sql)) {
        return (int) $db->affected_rows;
    }

    return 0;
}
