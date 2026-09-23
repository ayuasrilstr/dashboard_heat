<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
if ($mysqli->connect_error) {
    die("Connect error: " . $mysqli->connect_error . "\n");
}

echo "=== tb_engage_transactions ===\n";
$r = $mysqli->query("SELECT storage_nr, MIN(transaction_date), MAX(transaction_date), COUNT(*) FROM tb_engage_transactions GROUP BY storage_nr");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query error: " . $mysqli->error . "\n";
}

echo "=== tb_engage_archieve ===\n";
$r = $mysqli->query("SELECT storage_nr, MIN(transaction_date), MAX(transaction_date), COUNT(*) FROM tb_engage_archieve GROUP BY storage_nr");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query error: " . $mysqli->error . "\n";
}

echo "=== Transaction dates in both tables ===\n";
$r = $mysqli->query("
    SELECT DATE(transaction_date) as t_date, storage_nr, COUNT(*) as cnt, SUM(CASE WHEN qty > 0 THEN qty ELSE 0 END) as in_qty, SUM(CASE WHEN qty < 0 THEN abs(qty) ELSE 0 END) as out_qty
    FROM (
        SELECT transaction_date, storage_nr, qty FROM tb_engage_transactions
        UNION ALL
        SELECT transaction_date, storage_nr, qty FROM tb_engage_archieve
    ) as t
    GROUP BY DATE(transaction_date), storage_nr
    ORDER BY t_date DESC
    LIMIT 30
");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "date: {$row['t_date']} | storage: {$row['storage_nr']} | cnt: {$row['cnt']} | in: {$row['in_qty']} | out: {$row['out_qty']}\n";
    }
} else {
    echo "Query error: " . $mysqli->error . "\n";
}
