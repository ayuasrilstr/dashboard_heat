<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
if ($db->connect_error) {
    die("Connect error: " . $db->connect_error . "\n");
}
$tables = $db->query('SHOW TABLES');
echo "=== TABLES ===\n";
while ($r = $tables->fetch_array()) {
    echo $r[0] . "\n";
}

echo "\n=== tb_engage_transactions COLUMNS ===\n";
$cols = $db->query('SHOW COLUMNS FROM tb_engage_transactions');
if ($cols) {
    while ($c = $cols->fetch_assoc()) {
        echo "{$c['Field']} ({$c['Type']})\n";
    }
}

echo "\n=== Sample tb_engage_transactions (Today) ===\n";
$sample = $db->query("SELECT * FROM tb_engage_transactions ORDER BY id DESC LIMIT 3");
if ($sample) {
    while ($s = $sample->fetch_assoc()) {
        print_r($s);
    }
}
