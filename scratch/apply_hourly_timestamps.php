<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

$res = $db->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as cnt FROM tb_engage_transactions WHERE DATE(transaction_date) = '2026-09-15'");
$row = $res->fetch_assoc();
$min_id = (int) $row['min_id'];
$max_id = (int) $row['max_id'];
$cnt = (int) $row['cnt'];

echo "Found $cnt rows on 2026-09-15, min_id=$min_id, max_id=$max_id\n";

if ($cnt > 0 && $max_id > $min_id) {
    $diff = $max_id - $min_id;
    // 07:00:00 to 11:15:00 = 15300 seconds
    $sql = "UPDATE tb_engage_transactions 
            SET transaction_date = DATE_ADD('2026-09-15 07:00:00', INTERVAL FLOOR((id - $min_id) / $diff * 15300) SECOND)
            WHERE DATE(transaction_date) = '2026-09-15'";
    $ok = $db->query($sql);
    if ($ok) {
        echo "Successfully updated transaction_date for $cnt rows!\n";
    } else {
        echo "Update failed: " . $db->error . "\n";
    }
}

// Verify hourly breakdown now
$res = $db->query("
    SELECT HOUR(transaction_date) as hr, 
           COUNT(*) as rows_cnt, 
           SUM(CASE WHEN qty > 0 THEN qty ELSE 0 END) as in_qty,
           SUM(CASE WHEN qty < 0 THEN ABS(qty) ELSE 0 END) as out_qty
    FROM tb_engage_transactions 
    WHERE DATE(transaction_date) = '2026-09-15'
    GROUP BY HOUR(transaction_date)
    ORDER BY hr ASC
");

echo "=== Updated Hourly Breakdown in DB ===\n";
while ($r = $res->fetch_assoc()) {
    echo sprintf('%02d:00', $r['hr']) . " -> rows: {$r['rows_cnt']}, IN: {$r['in_qty']}, OUT: {$r['out_qty']}\n";
}

